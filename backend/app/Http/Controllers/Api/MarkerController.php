<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marker;
use App\Models\MarkerImage;
use App\Models\MarkerReport;
use App\Models\MarkerVote;
use App\Services\MarkerLifetimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MarkerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'category' => ['nullable', 'string', Rule::in(Marker::CATEGORIES)],
        ]);

        $query = Marker::query()
            ->with(['images', 'user:id,name'])
            ->where('expires_at', '>', now());

        $authUser = auth('sanctum')->user();
        if ($authUser) {
            $query->addSelect([
                'user_vote_type' => MarkerVote::query()
                    ->select('vote_type')
                    ->whereColumn('marker_id', 'markers.id')
                    ->where('user_id', $authUser->id)
                    ->limit(1),
            ]);
        } else {
            $query->select('markers.*')->selectRaw('NULL as user_vote_type');
        }

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $radiusKm = (float) $validated['radius'];

        $query->whereRaw($this->haversineSql($latitude, $longitude, $radiusKm));

        if (! empty($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }
        if (! empty($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        $markers = $query->get();

        return response()->json(['data' => $markers]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'user_latitude' => ['required', 'numeric', 'between:-90,90'],
            'user_longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'category' => ['required', 'string', Rule::in(Marker::CATEGORIES)],
            'description' => ['required', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:6'],
            'images.*' => ['string', 'regex:/^markers\/[a-zA-Z0-9\-_.]+\.(jpe?g|png|webp)$/'],
        ]);

        $markerLat = (float) $validated['latitude'];
        $markerLon = (float) $validated['longitude'];
        $userLat = (float) $validated['user_latitude'];
        $userLon = (float) $validated['user_longitude'];

        $distanceKm = $this->haversineDistanceKm($markerLat, $markerLon, $userLat, $userLon);
        if ($distanceKm > 3) {
            return response()->json([
                'message' => 'Marker must be within 3 km of your current location.',
            ], 422);
        }

        $user = $request->user();

        $todayCount = Marker::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= 5) {
            return response()->json([
                'message' => 'Maximum 5 markers per day allowed.',
            ], 422);
        }

        $lifetimeService = app(MarkerLifetimeService::class);
        $policy = $lifetimeService->resolvePolicy($validated['category']);
        $lifetime = $lifetimeService->buildInitialLifetime($policy);

        $marker = Marker::create([
            'user_id' => $user->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'address' => $validated['address'] ?? null,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'expires_at' => $lifetime['expires_at'],
            'base_expires_at' => $lifetime['base_expires_at'],
            'max_expires_at' => $lifetime['max_expires_at'],
            'policy_snapshot' => $policy,
        ]);

        if (! empty($validated['images'])) {
            foreach ($validated['images'] as $imageUrl) {
                MarkerImage::create([
                    'marker_id' => $marker->id,
                    'image_url' => $imageUrl,
                ]);
            }
        }

        $marker->load('images');

        return response()->json(['data' => $marker], 201);
    }

    public function vote(Request $request, Marker $marker): JsonResponse
    {
        $validated = $request->validate([
            'vote_type' => ['required', 'string', Rule::in([MarkerVote::VOTE_STILL_THERE, MarkerVote::VOTE_NOT_THERE])],
        ]);

        $user = $request->user();
        $newType = $validated['vote_type'];
        $lifetimeService = app(MarkerLifetimeService::class);

        $voteResult = DB::transaction(function () use ($marker, $user, $newType, $lifetimeService) {
            $lockedMarker = Marker::query()
                ->whereKey($marker->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingVote = MarkerVote::query()
                ->where('marker_id', $lockedMarker->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $oldType = $existingVote?->vote_type;
            $updatedUserVoteType = $newType;

            if ($oldType === $newType && $existingVote) {
                $existingVote->delete();
                $updatedUserVoteType = null;
            } else {
                MarkerVote::updateOrCreate(
                    [
                        'marker_id' => $lockedMarker->id,
                        'user_id' => $user->id,
                    ],
                    ['vote_type' => $newType]
                );
            }

            $likes = (int) $lockedMarker->likes;
            $dislikes = (int) $lockedMarker->dislikes;

            if ($oldType === MarkerVote::VOTE_STILL_THERE) {
                $likes = max(0, $likes - 1);
            } elseif ($oldType === MarkerVote::VOTE_NOT_THERE) {
                $dislikes = max(0, $dislikes - 1);
            }

            if ($updatedUserVoteType === MarkerVote::VOTE_STILL_THERE) {
                $likes++;
            } elseif ($updatedUserVoteType === MarkerVote::VOTE_NOT_THERE) {
                $dislikes++;
            }

            $lockedMarker->likes = $likes;
            $lockedMarker->dislikes = $dislikes;

            $policy = $lifetimeService->resolvePolicy($lockedMarker->category);
            $lifetimeService->applyVoteLifetime($lockedMarker, $policy, $oldType, $updatedUserVoteType);
            $lockedMarker->save();

            return [
                'user_vote_type' => $updatedUserVoteType,
                'marker_id' => $lockedMarker->id,
            ];
        });

        $updatedMarker = Marker::query()
            ->with(['images', 'user:id,name'])
            ->findOrFail($voteResult['marker_id']);

        return response()->json([
            'data' => $updatedMarker,
            'user_vote_type' => $voteResult['user_vote_type'],
            'message' => 'Vote recorded.',
        ]);
    }

    public function destroy(Request $request, Marker $marker): JsonResponse
    {
        $user = $request->user();
        $canDelete = $marker->user_id === $user->id || $user->is_admin;

        if (! $canDelete) {
            return response()->json([
                'message' => 'You are not allowed to delete this marker.',
            ], 403);
        }

        $marker->delete();

        return response()->json([
            'message' => 'Marker deleted successfully.',
        ]);
    }

    public function report(Request $request, Marker $marker): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', Rule::in(MarkerReport::REASONS)],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $existingReport = MarkerReport::where('marker_id', $marker->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existingReport) {
            return response()->json([
                'message' => 'You have already reported this marker.',
            ], 422);
        }

        $report = MarkerReport::create([
            'marker_id' => $marker->id,
            'user_id' => $user->id,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
        ]);

        return response()->json([
            'message' => 'Marker reported successfully.',
            'data' => [
                'id' => $report->id,
                'marker_id' => $report->marker_id,
                'reason' => $report->reason,
            ],
        ], 201);
    }

    private function haversineSql(float $lat, float $lon, float $radiusKm): string
    {
        $earthRadius = 6371;

        return "
            ( {$earthRadius} * acos(
                cos( radians({$lat}) ) *
                cos( radians( markers.latitude ) ) *
                cos( radians( markers.longitude ) - radians({$lon}) ) +
                sin( radians({$lat}) ) *
                sin( radians( markers.latitude ) )
            ) ) <= {$radiusKm}
        ";
    }

    private function haversineDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

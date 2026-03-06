<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marker;
use App\Models\MarkerImage;
use App\Models\MarkerVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'category' => ['required', 'string', Rule::in(Marker::CATEGORIES)],
            'description' => ['required', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:6'],
            'images.*' => ['string', 'url'],
        ]);

        $user = $request->user();

        $todayCount = Marker::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= 5) {
            return response()->json([
                'message' => 'Maximum 5 markers per day allowed.',
            ], 422);
        }

        $expiresAt = Carbon::now()->addWeek();

        $marker = Marker::create([
            'user_id' => $user->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'expires_at' => $expiresAt,
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
            'vote_type' => ['required', 'string', Rule::in(['like', 'dislike'])],
        ]);

        $user = $request->user();
        $newType = $validated['vote_type'];

        $existingVote = MarkerVote::where('marker_id', $marker->id)
            ->where('user_id', $user->id)
            ->first();

        $oldType = $existingVote?->vote_type;

        MarkerVote::updateOrCreate(
            [
                'marker_id' => $marker->id,
                'user_id' => $user->id,
            ],
            ['vote_type' => $newType]
        );

        if ($oldType !== $newType) {
            if ($oldType === 'like') {
                $marker->decrement('likes');
            } elseif ($oldType === 'dislike') {
                $marker->decrement('dislikes');
            }
            if ($newType === 'like') {
                $marker->increment('likes');
            } elseif ($newType === 'dislike') {
                $marker->increment('dislikes');
            }
        }

        return response()->json([
            'data' => $marker->fresh(['images', 'user:id,name']),
            'message' => 'Vote recorded.',
        ]);
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
}

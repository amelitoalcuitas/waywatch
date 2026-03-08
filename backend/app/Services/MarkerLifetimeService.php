<?php

namespace App\Services;

use App\Models\Marker;
use App\Models\MarkerLifetimePolicy;
use App\Models\MarkerVote;
use Carbon\Carbon;

class MarkerLifetimeService
{
    private const DEFAULTS = [
        'base_lifetime_minutes' => 24 * 60,
        'min_lifetime_minutes' => 60,
        'max_lifetime_minutes' => 7 * 24 * 60,
        'still_there_extension_minutes' => 30,
        'not_there_reduction_minutes' => 45,
        'grace_period_minutes' => 30,
        'early_expiry_quorum' => 5,
        'early_expiry_not_there_ratio' => 0.8,
        'early_expiry_minutes' => 0,
        'is_active' => true,
    ];

    public function resolvePolicy(string $category): array
    {
        $default = MarkerLifetimePolicy::query()
            ->whereNull('category')
            ->where('is_active', true)
            ->latest('id')
            ->first();

        $specific = MarkerLifetimePolicy::query()
            ->where('category', $category)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        $resolved = self::DEFAULTS;

        if ($default) {
            $resolved = array_merge($resolved, $this->toPolicyArray($default));
        }

        if ($specific) {
            $resolved = array_merge($resolved, $this->toPolicyArray($specific));
        }

        return $resolved;
    }

    public function buildInitialLifetime(array $policy): array
    {
        $now = now();
        $minExpiresAt = $now->copy()->addMinutes((int) $policy['min_lifetime_minutes']);
        $baseExpiresAt = $now->copy()->addMinutes((int) $policy['base_lifetime_minutes']);
        $maxExpiresAt = $now->copy()->addMinutes((int) $policy['max_lifetime_minutes']);

        $expiresAt = $this->clamp($baseExpiresAt, $minExpiresAt, $maxExpiresAt);

        return [
            'expires_at' => $expiresAt,
            'base_expires_at' => $baseExpiresAt,
            'max_expires_at' => $maxExpiresAt,
        ];
    }

    public function applyVoteLifetime(Marker $marker, array $policy, ?string $oldType, ?string $newType): void
    {
        $this->ensureMarkerBounds($marker, $policy);

        $currentExpiresAt = $marker->expires_at instanceof Carbon
            ? $marker->expires_at->copy()
            : now()->copy();

        $oldEffect = $this->voteEffectMinutes($oldType, $policy);
        $newEffect = $this->voteEffectMinutes($newType, $policy);
        $netDelta = $newEffect - $oldEffect;

        $graceEndsAt = $marker->created_at->copy()->addMinutes((int) $policy['grace_period_minutes']);
        if (now()->lt($graceEndsAt) && $netDelta < 0) {
            $netDelta = 0;
        }

        $candidate = $currentExpiresAt->addMinutes($netDelta);

        $minExpiresAt = $marker->created_at->copy()->addMinutes((int) $policy['min_lifetime_minutes']);
        $maxExpiresAt = $marker->max_expires_at instanceof Carbon
            ? $marker->max_expires_at->copy()
            : $marker->created_at->copy()->addMinutes((int) $policy['max_lifetime_minutes']);

        $marker->expires_at = $this->clamp($candidate, $minExpiresAt, $maxExpiresAt);

        $this->applyEarlyExpiry($marker, $policy);
    }

    private function applyEarlyExpiry(Marker $marker, array $policy): void
    {
        $totalVotes = (int) $marker->likes + (int) $marker->dislikes;
        $quorum = (int) $policy['early_expiry_quorum'];

        if ($totalVotes < $quorum || $totalVotes === 0) {
            return;
        }

        $notThereRatio = (int) $marker->dislikes / $totalVotes;
        if ($notThereRatio < (float) $policy['early_expiry_not_there_ratio']) {
            return;
        }

        $earlyExpiryAt = now()->addMinutes((int) $policy['early_expiry_minutes']);
        if ($earlyExpiryAt->lt($marker->expires_at)) {
            $marker->expires_at = $earlyExpiryAt;
        }
    }

    private function ensureMarkerBounds(Marker $marker, array $policy): void
    {
        if (! $marker->base_expires_at) {
            $marker->base_expires_at = $marker->created_at->copy()
                ->addMinutes((int) $policy['base_lifetime_minutes']);
        }

        if (! $marker->max_expires_at) {
            $marker->max_expires_at = $marker->created_at->copy()
                ->addMinutes((int) $policy['max_lifetime_minutes']);
        }

        if (! is_array($marker->policy_snapshot)) {
            $marker->policy_snapshot = $this->snapshot($policy);
        }
    }

    private function clamp(Carbon $value, Carbon $min, Carbon $max): Carbon
    {
        if ($max->lt($min)) {
            return $min->copy();
        }

        if ($value->lt($min)) {
            return $min->copy();
        }

        if ($value->gt($max)) {
            return $max->copy();
        }

        return $value;
    }

    private function voteEffectMinutes(?string $voteType, array $policy): int
    {
        if ($voteType === MarkerVote::VOTE_STILL_THERE) {
            return (int) $policy['still_there_extension_minutes'];
        }

        if ($voteType === MarkerVote::VOTE_NOT_THERE) {
            return -1 * (int) $policy['not_there_reduction_minutes'];
        }

        return 0;
    }

    private function snapshot(array $policy): array
    {
        return [
            'base_lifetime_minutes' => (int) $policy['base_lifetime_minutes'],
            'min_lifetime_minutes' => (int) $policy['min_lifetime_minutes'],
            'max_lifetime_minutes' => (int) $policy['max_lifetime_minutes'],
            'still_there_extension_minutes' => (int) $policy['still_there_extension_minutes'],
            'not_there_reduction_minutes' => (int) $policy['not_there_reduction_minutes'],
            'grace_period_minutes' => (int) $policy['grace_period_minutes'],
            'early_expiry_quorum' => (int) $policy['early_expiry_quorum'],
            'early_expiry_not_there_ratio' => (float) $policy['early_expiry_not_there_ratio'],
            'early_expiry_minutes' => (int) $policy['early_expiry_minutes'],
        ];
    }

    private function toPolicyArray(MarkerLifetimePolicy $policy): array
    {
        return [
            'base_lifetime_minutes' => (int) $policy->base_lifetime_minutes,
            'min_lifetime_minutes' => (int) $policy->min_lifetime_minutes,
            'max_lifetime_minutes' => (int) $policy->max_lifetime_minutes,
            'still_there_extension_minutes' => (int) $policy->still_there_extension_minutes,
            'not_there_reduction_minutes' => (int) $policy->not_there_reduction_minutes,
            'grace_period_minutes' => (int) $policy->grace_period_minutes,
            'early_expiry_quorum' => (int) $policy->early_expiry_quorum,
            'early_expiry_not_there_ratio' => (float) $policy->early_expiry_not_there_ratio,
            'early_expiry_minutes' => (int) $policy->early_expiry_minutes,
            'is_active' => (bool) $policy->is_active,
        ];
    }
}

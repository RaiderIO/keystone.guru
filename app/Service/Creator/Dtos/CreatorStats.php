<?php

namespace App\Service\Creator\Dtos;

use App\Models\Season;
use Illuminate\Support\Carbon;

/**
 * What a creator's world-published routes say about them, as shown on the directory card and the
 * profile hero. Every figure is an aggregate over world-published routes only - unlisted, team and
 * private routes never contribute, so the numbers cannot reveal that such routes exist.
 */
readonly class CreatorStats
{
    public function __construct(
        public int     $publishedRouteCount,
        public int     $totalViews,
        public int     $seasonRouteCount,
        public int     $seasonViews,
        public ?float  $ratingAverage,
        public int     $ratingCount,
        public ?Carbon $lastPublishedAt,
        public ?Season $season,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes The columns of UserRepository's published route aggregate.
     * @param Season|null          $season     The season the season_* columns were counted for.
     */
    public static function fromAttributes(array $attributes, ?Season $season): self
    {
        $ratingCount       = (int)($attributes['rating_count'] ?? 0);
        $ratingWeightedSum = (float)($attributes['rating_weighted_sum'] ?? 0);
        $lastPublishedAt   = $attributes['last_published_at'] ?? null;

        return new self(
            (int)($attributes['published_route_count'] ?? 0),
            (int)($attributes['total_views'] ?? 0),
            $season === null ? 0 : (int)($attributes['season_route_count'] ?? 0),
            $season === null ? 0 : (int)($attributes['season_views'] ?? 0),
            $ratingCount > 0 ? round($ratingWeightedSum / $ratingCount, 1) : null,
            $ratingCount,
            $lastPublishedAt === null ? null : Carbon::parse($lastPublishedAt),
            $season,
        );
    }

    public function hasEnoughRatingsToShow(): bool
    {
        return $this->ratingAverage !== null
            && $this->ratingCount >= (int)config('keystoneguru.creators.min_ratings_shown');
    }

    /**
     * The one-line summary on a directory card: this season's output and its reach, or - for a
     * creator with nothing this season - what they have made overall.
     *
     * @return list<string>
     */
    public function getSummaryParts(): array
    {
        if ($this->season === null) {
            return [
                trans_choice('view_creator.stats.route_count', $this->publishedRouteCount, ['count' => $this->publishedRouteCount]),
                $this->formatViews($this->totalViews),
            ];
        }

        if ($this->seasonRouteCount === 0) {
            return [
                __('view_creator.stats.no_routes_this_season'),
                trans_choice('view_creator.stats.route_count_total', $this->publishedRouteCount, ['count' => $this->publishedRouteCount]),
            ];
        }

        return [
            trans_choice('view_creator.stats.season_route_count', $this->seasonRouteCount, ['count' => $this->seasonRouteCount]),
            $this->formatViews($this->seasonViews),
        ];
    }

    /**
     * The fuller line under the name in the profile hero.
     *
     * @return list<string>
     */
    public function getProfileParts(): array
    {
        if ($this->publishedRouteCount === 0) {
            return [trans_choice('view_creator.stats.route_count_total', 0)];
        }

        $parts = [];

        if ($this->season !== null) {
            $parts[] = trans_choice('view_creator.stats.season_route_count_named', $this->seasonRouteCount, [
                'count'  => $this->seasonRouteCount,
                'season' => $this->season->name_long,
            ]);

            if ($this->seasonRouteCount > 0) {
                $parts[] = $this->formatViews($this->seasonViews);
            }
        }

        if ($this->hasEnoughRatingsToShow()) {
            $parts[] = trans_choice('view_creator.stats.rating', $this->ratingCount, [
                'rating' => number_format($this->ratingAverage, 1),
                'count'  => $this->ratingCount,
            ]);
        }

        $parts[] = trans_choice('view_creator.stats.route_count_total', $this->publishedRouteCount, ['count' => $this->publishedRouteCount]);

        if ($this->season === null) {
            $parts[] = $this->formatViews($this->totalViews);
        }

        if ($this->lastPublishedAt !== null) {
            $parts[] = __('view_creator.stats.last_published', ['time' => $this->lastPublishedAt->diffForHumans()]);
        }

        return $parts;
    }

    private function formatViews(int $views): string
    {
        return trans_choice('view_creator.stats.views', $views, ['views' => abbreviateNumber($views)]);
    }
}

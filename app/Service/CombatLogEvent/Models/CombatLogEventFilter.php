<?php

namespace App\Service\CombatLogEvent\Models;

use App\Models\Dungeon;
use Codeart\OpensearchLaravel\Search\Query;
use Codeart\OpensearchLaravel\Search\SearchQueries\BoolQuery;
use Codeart\OpensearchLaravel\Search\SearchQueries\Must;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;
use RectorPrefix202402\Illuminate\Contracts\Support\Arrayable;

class CombatLogEventFilter implements Arrayable
{
    public function __construct(
        private readonly Dungeon $dungeon
    ) {
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function toArray(): array
    {
        return [
            'challenge_mode_id' => $this->dungeon->challenge_mode_id,
        ];
    }

    public function toOpensearchQuery(): array
    {
        return [
            Query::make([
                BoolQuery::make([
                    Must::make([
                        MatchOne::make('challenge_mode_id', $this->getDungeon()->challenge_mode_id),
                    ]),
                ]),
            ]),
        ];
    }

    public static function fromArray(array $requestArray): CombatLogEventFilter
    {
        return new CombatLogEventFilter(
            dungeon: Dungeon::firstWhere('id', $requestArray['dungeon_id'])
        );
    }
}

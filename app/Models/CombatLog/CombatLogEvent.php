<?php

namespace App\Models\CombatLog;

use App\Logic\Structs\IngameXY;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Opensearch\OpensearchModel;
use Codeart\OpensearchLaravel\Traits\HasOpenSearchDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $run_id
 * @property int    $challenge_mode_id
 * @property int    $level
 * @property string $affix_ids
 * @property bool   $success
 * @property string $start
 * @property string $end
 * @property int    $duration_ms
 * @property int    $ui_map_id
 * @property float  $pos_x
 * @property float  $pos_y
 * @property float  $pos_enemy_x
 * @property float  $pos_enemy_y
 * @property string $event_type
 * @property string $characters
 * @property string $context
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CombatLogEvent extends OpensearchModel
{
    use HasFactory, HasOpenSearchDocuments;

    public const EVENT_TYPE_PLAYER_DEATH = 'player_death';
    public const EVENT_TYPE_ENEMY_KILLED = 'enemy_killed';
    public const EVENT_TYPE_SPELL_CAST   = 'spell_cast';

    public const ALL_EVENT_TYPE = [
        self::EVENT_TYPE_PLAYER_DEATH,
        self::EVENT_TYPE_ENEMY_KILLED,
        self::EVENT_TYPE_SPELL_CAST,
    ];

    public const DATA_TYPE_PLAYER_POSITION = 'player_position';
    public const DATA_TYPE_ENEMY_POSITION  = 'enemy_position';

    public const ALL_DATA_TYPE = [
        self::DATA_TYPE_PLAYER_POSITION,
        self::DATA_TYPE_ENEMY_POSITION,
    ];

    protected $connection = 'combatlog';

    protected $fillable = [
        'id',
        'run_id',
        'challenge_mode_id',
        'level',
        'affix_ids',
        'success',
        'start',
        'end',
        'duration_ms',
        'ui_map_id',
        'pos_x',
        'pos_y',
        'pos_enemy_x',
        'pos_enemy_y',
        'event_type',
        'characters',
        'context',
        'created_at',
        'updated_at',
    ];


    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class, 'challenge_mode_id', 'challenge_mode_id');
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'ui_map_id', 'ui_map_id');
    }


    public function openSearchMapping(): array
    {
        return [
            'mapping' => [
                'properties' => [
                    '@timestamp'        => [
                        'format' => 'epoch_second',
                        'type'   => 'date',
                    ],
                    'run_id'            => [
                        'type' => 'keyword',
                    ],
                    'challenge_mode_id' => [
                        'type' => 'integer',
                    ],
                    'level'             => [
                        'type' => 'integer',
                    ],
                    'affix_id'          => [
                        'type' => 'integer',
                    ],
                    'success'           => [
                        'type' => 'boolean',
                    ],
                    'start'             => [
                        'format' => 'epoch_second',
                        'type'   => 'date',
                    ],
                    'end'               => [
                        'format' => 'epoch_second',
                        'type'   => 'date',
                    ],
                    'duration_ms'       => [
                        'type' => 'integer',
                    ],
                    'ui_map_id'         => [
                        'type' => 'integer',
                    ],
                    'pos_x'             => [
                        'type' => 'float',
                    ],
                    'pos_y'             => [
                        'type' => 'float',
                    ],
                    'pos_enemy_x'       => [
                        'type' => 'float',
                    ],
                    'pos_enemy_y'       => [
                        'type' => 'float',
                    ],
                    //                    'pos'               => [
                    //                        'type'             => 'geo_point',
                    //                        'ignore_malformed' => true,
                    //                    ],
                    'event_type'        => [
                        'type' => 'keyword',
                    ],
                    'characters'        => [
                        'type'       => 'nested',
                        'dynamic'    => false,
                        'properties' => [
                            'id'       => [
                                'type' => 'keyword',
                            ],
                            'class_id' => [
                                'type' => 'integer',
                            ],
                            'race_id'  => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'context'           => [
                        'type'       => 'nested',
                        'dynamic'    => true,
                        'properties' => [
                            'spell_id' => [
                                'type' => 'integer',
                            ],
                            'npc_id'   => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function openSearchArray(): array
    {
        return [
            '@timestamp'        => $this->created_at->getTimestamp(),
            'id'                => $this->id,
            'run_id'            => $this->run_id,
            'challenge_mode_id' => $this->challenge_mode_id,
            'level'             => $this->level,
            'affix_id'          => json_decode($this->affix_ids, true),
            'success'           => $this->success ? 'true' : 'false',
            'start'             => Carbon::parse($this->start)->getTimestamp(),
            'end'               => Carbon::parse($this->end)->getTimestamp(),
            'duration_ms'       => $this->duration_ms,
            'ui_map_id'         => $this->ui_map_id,
            //            'pos'               => sprintf('POINT (%f %f)', $this->pos_x, $this->pos_y),
            'pos_x'             => round($this->pos_x, 2),
            'pos_y'             => round($this->pos_y, 2),
            'pos_enemy_x'       => round($this->pos_enemy_x, 2),
            'pos_enemy_y'       => round($this->pos_enemy_y, 2),
            'event_type'        => $this->event_type,
            'characters'        => json_decode($this->characters, true),
            'context'           => json_decode($this->context, true),
        ];
    }

    public function openSearchArrayToModel(array $row): self
    {
//        // POINT (355.730000 -91.230000);
//        $posArr = explode(' ', str_replace(['POINT (', ')'], '', $row['pos']));

        $this->setRawAttributes([
            'id'                => $row['id'],
            'run_id'            => $row['run_id'],
            'challenge_mode_id' => $row['challenge_mode_id'],
            'level'             => $row['level'],
            'affix_ids'         => json_encode($row['affix_id'], true),
            'success'           => $row['id'],
            'start'             => Carbon::createFromTimestamp($row['start']),
            'end'               => Carbon::createFromTimestamp($row['end']),
            'duration_ms'       => $row['duration_ms'],
            'ui_map_id'         => $row['ui_map_id'],
            //            'pos_x'             => (float)$posArr[0],
            //            'pos_y'             => (float)$posArr[1],
            'pos_x'             => $row['pos_x'],
            'pos_y'             => $row['pos_y'],
            'pos_enemy_x'       => $row['pos_enemy_x'],
            'pos_enemy_y'       => $row['pos_enemy_y'],
            'event_type'        => $row['event_type'],
            'characters'        => json_encode($row['characters'], true),
            'context'           => json_encode($row['context'], true),
        ]);

        return $this;
    }

    public function openSearchIndexName(): string
    {
        return 'combat_log_events';
    }

    public function getIngameXY(): IngameXY
    {
        // Could use $this->floor but that doesn't work since this model is an Opensearch model - the floor should be added
        // later
        return new IngameXY($this->pos_x, $this->pos_y);
    }

    public function getIngameXYNpc(): IngameXY
    {
        // Could use $this->floor but that doesn't work since this model is an Opensearch model - the floor should be added
        // later
        return new IngameXY($this->pos_enemy_x, $this->pos_enemy_y);
    }

    public function setTimeInterval(Dungeon $dungeon, Carbon $start, int $durationMs): self
    {
        $success = $dungeon->currentMappingVersion->timer_max_seconds > ($durationMs / 1000);

        $this->setAttributes([
            'start'       => $start->toDateTimeString(),
            'end'         => $start->addMilliseconds($durationMs)->toDateTimeString(),
            'duration_ms' => $durationMs,
            'success'     => $success,
        ]);

        return $this;
    }
}

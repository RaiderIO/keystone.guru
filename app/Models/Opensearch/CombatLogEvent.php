<?php

namespace App\Models\Opensearch;

use Codeart\OpensearchLaravel\Traits\HasOpenSearchDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $timestamp
 * @property string $id
 * @property string $run_id
 * @property int    $challenge_mode_id
 * @property int    $level
 * @property int[]  $affix_id
 * @property bool   $success
 * @property string $start
 * @property string $end
 * @property int    $duration_ms
 * @property float  $pos_x
 * @property float  $pos_y
 * @property string $event_type
 * @property array  $characters
 * @property array  $context
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
                    'pos_x'             => [
                        'type' => 'float',
                    ],
                    'pos_y'             => [
                        'type' => 'float',
                    ],
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
            '@timestamp'        => $this->timestamp,
            'id'                => $this->id,
            'run_id'            => $this->run_id,
            'challenge_mode_id' => $this->challenge_mode_id,
            'level'             => $this->level,
            'affix_id'          => $this->affix_id,
            'success'           => $this->success,
            'start'             => $this->start,
            'end'               => $this->end,
            'duration_ms'       => $this->duration_ms,
            'pos_x'             => $this->pos_x,
            'pos_y'             => $this->pos_y,
            'event_type'        => $this->event_type,
            'characters'        => $this->characters,
            'context'           => $this->context,
        ];
    }
}

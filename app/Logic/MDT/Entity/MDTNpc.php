<?php

namespace App\Logic\MDT\Entity;

use Illuminate\Contracts\Support\Arrayable;

class MDTNpc implements Arrayable
{
    private array         $clones;
    private int           $id              = 0;
    private array         $spells          = [];
    private float         $scale           = 0.0;
    private bool          $stealthDetect   = false;
    private int           $countTeeming    = 0;
    private int           $count           = 0;
    private string        $name;
    private int           $displayId       = 0;
    private ?int          $encounterId     = null;
    private ?int          $instanceId      = null;
    private string        $creatureType;
    private int           $level           = 0;
    private int           $health          = 0;
    private readonly ?int $healthPercentage;
    private array         $characteristics = [];

    public function __construct(private readonly int $index, private array $rawMdtNpc)
    {
        // We need to do this ksort magic because php arrays that we get from Lua are in a random order - this makes it consistent
        $this->recur_ksort($this->rawMdtNpc['clones']);
        $this->clones = $this->rawMdtNpc['clones'];

        // Correct clones that don't have a sublevel set
        foreach ($this->clones as $index => $clone) {
            if (!isset($clone['sublevel'])) {
                $this->clones[$index]['sublevel'] = 1;
            }
        }

        $this->id = (int)$this->rawMdtNpc['id'];

        if (isset($this->rawMdtNpc['spells'])) {
            $this->spells = $rawMdtNpc['spells'];
        } else {
            $this->spells = [];
        }
        ksort($this->spells);

        $this->scale         = (float)$this->rawMdtNpc['scale'];
        $this->stealthDetect = isset($this->rawMdtNpc['stealthDetect']) && $this->rawMdtNpc['stealthDetect'];
        $this->countTeeming  = isset($this->rawMdtNpc['teemingCount']) ? (int)$this->rawMdtNpc['teemingCount'] : -1;
        $this->count         = (int)$this->rawMdtNpc['count'];
        // May not always be set?
        if (isset($this->rawMdtNpc['name'])) {
            $this->name = $this->rawMdtNpc['name'];
        }

        $this->displayId   = (int)($this->rawMdtNpc['displayId'] ?? 0);
        $this->encounterId = $this->rawMdtNpc['encounterID'] ?? null;
        $this->instanceId  = $this->rawMdtNpc['instanceID'] ?? null;
        // May not always be set?
        if (isset($this->rawMdtNpc['creatureType'])) {
            $this->creatureType = $this->rawMdtNpc['creatureType'];
        }

        $this->level            = (int)($this->rawMdtNpc['level'] ?? 0);
        $this->health           = (int)$this->rawMdtNpc['health'];
        $this->healthPercentage = $this->rawMdtNpc['health_percentage'] ?? null;

        // We need to do this ksort magic because php arrays that we get from Lua are in a random order - this makes it consistent
        if (isset($this->rawMdtNpc['characteristics'])) {
            $this->recur_ksort($this->rawMdtNpc['characteristics']);
            $this->characteristics = $this->rawMdtNpc['characteristics'];
        } else {
            $this->characteristics = [];
        }
    }

    private function recur_ksort(&$array): bool
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recur_ksort($value);
            }
        }

        return ksort($array);
    }

    public function isEmissary(): bool
    {
        return in_array($this->id, [
            155432,
            155433,
            155434,
        ]);
    }

    public function isAwakened(): bool
    {
        return in_array($this->id, [
            161244,
            161243,
            161124,
            161241,
        ]);
    }

    public function isEncrypted(): bool
    {
        return in_array($this->id, [
            185680,
            185683,
            185685,
        ]);
    }

    public function isValid(): bool
    {
        // Skip emissaries
        return !$this->isEmissary();
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getRawMdtNpc(): array
    {
        return $this->rawMdtNpc;
    }

    public function getClones(): array
    {
        return $this->clones;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array<int, array>
     */
    public function getSpells(): array
    {
        return $this->spells;
    }

    public function getScale(): float
    {
        return $this->scale;
    }

    public function getStealthDetect(): bool
    {
        return $this->stealthDetect;
    }

    public function getCountTeeming(): int
    {
        return $this->countTeeming;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDisplayId(): int
    {
        return $this->displayId;
    }

    public function getEncounterId(): ?int
    {
        return $this->encounterId;
    }

    public function getInstanceId(): ?int
    {
        return $this->instanceId;
    }

    public function getCreatureType(): ?string
    {
        return $this->creatureType;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getHealth(): int
    {
        return $this->health;
    }

    public function getHealthPercentage(): ?int
    {
        return $this->healthPercentage;
    }

    public function getCharacteristics(): array
    {
        return $this->characteristics;
    }

    public function toArray(): array
    {
        return [
            'index'           => $this->getIndex(),
            'clones'          => $this->getClones(),
            'id'              => $this->getId(),
            'spells'          => $this->getSpells(),
            'scale'           => $this->getScale(),
            'stealthDetect'   => $this->getStealthDetect(),
            'countTeeming'    => $this->getCountTeeming(),
            'count'           => $this->getCount(),
            'name'            => $this->getName(),
            'displayId'       => $this->getDisplayId(),
            'creatureType'    => $this->getCreatureType(),
            'level'           => $this->getLevel(),
            'health'          => $this->getHealth(),
            'characteristics' => $this->getCharacteristics(),
        ];
    }
}

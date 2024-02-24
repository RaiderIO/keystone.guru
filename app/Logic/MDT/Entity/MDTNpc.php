<?php


namespace App\Logic\MDT\Entity;


use Illuminate\Contracts\Support\Arrayable;

class MDTNpc implements Arrayable
{
    /** @var array */
    private array $clones;

    /** @var int */
    private int $id = 0;

    /** @var array */
    private array $spells = [];

    /** @var float */
    private float $scale = 0.0;

    /** @var bool */
    private bool $stealthDetect = false;

    /** @var int */
    private int $countTeeming = 0;

    /** @var int */
    private int $count = 0;

    /** @var string */
    private string $name;

    /** @var int */
    private int $displayId = 0;

    /** @var string */
    private string $creatureType;

    /** @var int */
    private int $level = 0;

    /** @var int */
    private int $health = 0;

    /** @var int|null */
    private readonly ?int $healthPercentage;

    /** @var array */
    private array $characteristics = [];

    function __construct(private readonly int $index, private array $rawMdtNpc)
    {
        // We need to do this ksort magic because php arrays that we get from Lua are in a random order - this makes it consistent
        $this->recur_ksort($this->rawMdtNpc['clones']);
        $this->clones = $this->rawMdtNpc['clones'];

        // Correct clones that don't have a sublevel set
        foreach ($this->clones as $this->index => $clone) {
            if (!isset($clone['sublevel'])) {
                $this->clones[$this->index]['sublevel'] = 1;
            }
        }
        $this->id = (int)$this->rawMdtNpc['id'];

        if (isset($this->rawMdtNpc['spells'])) {
            // #1760
//            $this->recur_ksort($rawMdtNpc['spells']);
//            $this->spells = $rawMdtNpc['spells'];
            $this->spells = [];
        } else {
            $this->spells = [];
        }

        $this->scale         = (float)$this->rawMdtNpc['scale'];
        $this->stealthDetect = isset($this->rawMdtNpc['stealthDetect']) && $this->rawMdtNpc['stealthDetect'];
        $this->countTeeming  = isset($this->rawMdtNpc['teemingCount']) ? (int)$this->rawMdtNpc['teemingCount'] : -1;
        $this->count         = (int)$this->rawMdtNpc['count'];
        // May not always be set?
        if (isset($this->rawMdtNpc['name'])) {
            $this->name = $this->rawMdtNpc['name'];
        }
        $this->displayId = (int)$this->rawMdtNpc['displayId'];
        // May not always be set?
        if (isset($this->rawMdtNpc['creatureType'])) {
            $this->creatureType = $this->rawMdtNpc['creatureType'];
        }
        $this->level            = (int)$this->rawMdtNpc['level'];
        $this->health           = (int)$this->rawMdtNpc['health'];
        $this->healthPercentage = $this->rawMdtNpc['health_percentage'] ?? null;

        if (isset($this->rawMdtNpc['characteristics'])) {
            // #1761
//            $this->recur_ksort($rawMdtNpc['characteristics']);
//            $this->characteristics = $rawMdtNpc['characteristics'];
            $this->characteristics = [];
        } else {
            $this->characteristics = [];
        }
    }

    /**
     * @param $array
     * @return bool
     */
    private function recur_ksort(&$array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recur_ksort($value);
            }
        }

        return ksort($array);
    }

    /**
     * @return bool
     */
    public function isEmissary(): bool
    {
        return in_array($this->id, [155432, 155433, 155434]);
    }

    /**
     * @return bool
     */
    public function isAwakened(): bool
    {
        return in_array($this->id, [161244, 161243, 161124, 161241]);
    }

    /**
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return in_array($this->id, [185680, 185683, 185685]);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        // Skip emissaries
        return !$this->isEmissary();
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @return array
     */
    public function getRawMdtNpc(): array
    {
        return $this->rawMdtNpc;
    }

    /**
     * @return array
     */
    public function getClones(): array
    {
        return $this->clones;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getSpells(): array
    {
        return $this->spells;
    }

    /**
     * @return float
     */
    public function getScale(): float
    {
        return $this->scale;
    }

    /**
     * @return bool
     */
    public function getStealthDetect(): bool
    {
        return $this->stealthDetect;
    }

    /**
     * @return int
     */
    public function getCountTeeming(): int
    {
        return $this->countTeeming;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getDisplayId(): int
    {
        return $this->displayId;
    }

    /**
     * @return string|null
     */
    public function getCreatureType(): ?string
    {
        return $this->creatureType;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @return int
     */
    public function getHealth(): int
    {
        return $this->health;
    }

    /**
     * @return int|null
     */
    public function getHealthPercentage(): ?int
    {
        return $this->healthPercentage;
    }

    /**
     * @return array
     */
    public function getCharacteristics(): array
    {
        return $this->characteristics;
    }

    /**
     * @return array
     */
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

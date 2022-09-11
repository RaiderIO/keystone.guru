<?php

namespace Database\Seeders\RelationImport\Mapping;

use Database\Seeders\RelationImport\Parsers\RelationParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class RelationMapping
 * @package Database\Seeders\RelationImport\Mapping
 * @author Wouter
 * @since 25/04/2021
 */
abstract class RelationMapping
{
    /** @var string */
    private string $fileName;

    /** @var string */
    private string $class;

    /** @var boolean True if the model's data remains in the DB and needs to be updated instead of inserted blindly */
    private bool $persistent;

    /** @var Collection|RelationParser[] */
    private $preSaveAttributeParsers;

    /** @var Collection|RelationParser[] */
    private $postSaveAttributeParsers;

    /**
     * RelationMapping constructor.
     * @param string $fileName
     * @param string $class
     * @param bool $persistent
     */
    public function __construct(string $fileName, string $class, bool $persistent = false)
    {
        $this->fileName   = $fileName;
        $this->class      = $class;
        $this->persistent = $persistent;

        $this->preSaveAttributeParsers  = collect();
        $this->postSaveAttributeParsers = collect();
    }

    /**
     * @param RelationParser[]|Collection $preSaveAttributeParsers
     * @return RelationMapping
     */
    protected function setPreSaveAttributeParsers($preSaveAttributeParsers): self
    {
        $this->preSaveAttributeParsers = $preSaveAttributeParsers;
        return $this;
    }

    /**
     * @param RelationParser[]|Collection $postSaveAttributeParsers
     * @return RelationMapping
     */
    protected function setPostSaveAttributeParsers($postSaveAttributeParsers): self
    {
        $this->postSaveAttributeParsers = $postSaveAttributeParsers;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return string|Model
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return bool
     */
    public function isPersistent(): bool
    {
        return $this->persistent;
    }

    /**
     * @return RelationParser[]|Collection
     */
    public function getPreSaveAttributeParsers(): Collection
    {
        return $this->preSaveAttributeParsers;
    }

    /**
     * @return RelationParser[]|Collection
     */
    public function getPostSaveAttributeParsers(): Collection
    {
        return $this->postSaveAttributeParsers;
    }
}

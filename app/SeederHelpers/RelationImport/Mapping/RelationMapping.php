<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\SeederHelpers\RelationImport\Conditionals\ConditionalInterface;
use App\SeederHelpers\RelationImport\Parsers\Attribute\AttributeParserInterface;
use App\SeederHelpers\RelationImport\Parsers\Relation\RelationParserInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class RelationMapping
 * @package App\SeederHelpers\RelationImport\Mapping
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

    /** @var Collection|ConditionalInterface[] */
    private Collection $conditionals;

    /** @var Collection|AttributeParserInterface[] */
    private Collection $attributeParsers;

    /** @var Collection|RelationParserInterface[] */
    private Collection $preSaveRelationParsers;

    /** @var Collection|RelationParserInterface[] */
    private Collection $postSaveRelationParsers;

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

        $this->conditionals            = collect();
        $this->attributeParsers        = collect();
        $this->preSaveRelationParsers  = collect();
        $this->postSaveRelationParsers = collect();
    }

    /**
     * @return ConditionalInterface[]|Collection
     */
    public function getConditionals(): Collection
    {
        return $this->conditionals;
    }

    /**
     * @param Collection $conditionals
     * @return $this
     */
    protected function setConditionals(Collection $conditionals): self
    {
        $this->conditionals = $conditionals;
        return $this;
    }

    /**
     * @return Collection|AttributeParserInterface[]
     */
    public function getAttributeParsers(): Collection
    {
        return $this->attributeParsers;
    }

    /**
     * @param Collection $attributeParsers
     * @return RelationMapping
     */
    public function setAttributeParsers(Collection $attributeParsers): RelationMapping
    {
        $this->attributeParsers = $attributeParsers;
        return $this;
    }

    /**
     * @return RelationParserInterface[]|Collection
     */
    public function getPreSaveRelationParsers(): Collection
    {
        return $this->preSaveRelationParsers;
    }

    /**
     * @param RelationParserInterface[]|Collection $preSaveRelationParsers
     * @return $this
     */
    protected function setPreSaveRelationParsers($preSaveRelationParsers): self
    {
        $this->preSaveRelationParsers = $preSaveRelationParsers;
        return $this;
    }

    /**
     * @return RelationParserInterface[]|Collection
     */
    public function getPostSaveRelationParsers(): Collection
    {
        return $this->postSaveRelationParsers;
    }

    /**
     * @param RelationParserInterface[]|Collection $postSaveRelationParsers
     * @return $this
     */
    protected function setPostSaveRelationParsers($postSaveRelationParsers): self
    {
        $this->postSaveRelationParsers = $postSaveRelationParsers;
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
}

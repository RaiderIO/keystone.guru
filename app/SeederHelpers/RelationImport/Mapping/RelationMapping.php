<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\SeederHelpers\RelationImport\Conditionals\ConditionalInterface;
use App\SeederHelpers\RelationImport\Parsers\Attribute\AttributeParserInterface;
use App\SeederHelpers\RelationImport\Parsers\Relation\RelationParserInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class RelationMapping
 *
 * @author Wouter
 *
 * @since 25/04/2021
 */
abstract class RelationMapping
{
    /** @var Collection<ConditionalInterface> */
    private Collection $conditionals;

    /** @var Collection<AttributeParserInterface> */
    private Collection $attributeParsers;

    /** @var Collection<RelationParserInterface> */
    private Collection $preSaveRelationParsers;

    /** @var Collection<RelationParserInterface> */
    private Collection $postSaveRelationParsers;

    /**
     * RelationMapping constructor.
     */
    public function __construct(private readonly string $fileName, private readonly string $class, private readonly bool $persistent = false)
    {
        $this->conditionals            = collect();
        $this->attributeParsers        = collect();
        $this->preSaveRelationParsers  = collect();
        $this->postSaveRelationParsers = collect();
    }

    /**
     * @return Collection<ConditionalInterface>
     */
    public function getConditionals(): Collection
    {
        return $this->conditionals;
    }

    /**
     * @return $this
     */
    protected function setConditionals(Collection $conditionals): self
    {
        $this->conditionals = $conditionals;

        return $this;
    }

    /**
     * @return Collection<AttributeParserInterface>
     */
    public function getAttributeParsers(): Collection
    {
        return $this->attributeParsers;
    }

    public function setAttributeParsers(Collection $attributeParsers): RelationMapping
    {
        $this->attributeParsers = $attributeParsers;

        return $this;
    }

    /**
     * @return Collection<RelationParserInterface>
     */
    public function getPreSaveRelationParsers(): Collection
    {
        return $this->preSaveRelationParsers;
    }

    /**
     * @param Collection<RelationParserInterface> $preSaveRelationParsers
     * @return $this
     */
    protected function setPreSaveRelationParsers(Collection $preSaveRelationParsers): self
    {
        $this->preSaveRelationParsers = $preSaveRelationParsers;

        return $this;
    }

    /**
     * @return Collection<RelationParserInterface>
     */
    public function getPostSaveRelationParsers(): Collection
    {
        return $this->postSaveRelationParsers;
    }

    /**
     * @param Collection<RelationParserInterface> $postSaveRelationParsers
     * @return $this
     */
    protected function setPostSaveRelationParsers(Collection $postSaveRelationParsers): self
    {
        $this->postSaveRelationParsers = $postSaveRelationParsers;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return Model|string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function isPersistent(): bool
    {
        return $this->persistent;
    }
}

<?php

namespace App\Http\Models\Request;

use Illuminate\Support\Collection;
use ReflectionNamedType;
use ReflectionProperty;

abstract class RequestModel
{
    /**
     * You MUST be able to make an instance without any parameters!
     */
    abstract public function __construct();

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof RequestModel) {
                // Recursively call toArray for nested RequestModel
                $result[$key] = $value->toArray();
            } elseif ($value instanceof Collection) {
                // Map each item in the collection to its array representation
                $result[$key] = $value->map(fn($item) => $item instanceof RequestModel ? $item->toArray() : $item)->toArray();
            } else {
                // Directly assign scalar or non-nested types
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function createFromArray(array $data): static
    {
        $object = new static();

        foreach ($data as $key => $value) {
            if (property_exists($object, $key)) {
                $propertyReflection = new ReflectionProperty($object, $key);
                $type               = $propertyReflection->getType();

                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();

                    // Handle Laravel Collection
                    if ($typeName === Collection::class) {
                        // Determine the class of the collection items using a naming convention or custom logic
                        $collection = collect();
                        $itemType   = static::getCollectionItemType($key);

                        if ($itemType && is_subclass_of($itemType, RequestModel::class) && is_array($value)) {
                            foreach ($value as $item) {
                                $collection->push($itemType::createFromArray(is_array($item) ? $item : []));
                            }
                        } else {
                            // If no item type is defined, just populate the collection directly
                            /** @var array<int, mixed> $valueArray */
                            $valueArray = is_array($value) ? $value : [];
                            $collection = collect($valueArray);
                        }

                        $object->$key = $collection;
                    } // Handle nested RequestModel
                    elseif (!$type->isBuiltin() && is_subclass_of($typeName, RequestModel::class)) {
                        $object->$key = $typeName::createFromArray(is_array($value) ? $value : []);
                    } else {
                        // Direct assignment for other types
                        $object->$key = $value;
                    }
                } else {
                    // No type hint, assign directly
                    $object->$key = $value;
                }
            }
        }

        return $object;
    }

    /**
     * Get the item type for a collection property.
     * Override this method in child classes to define the item type for specific keys.
     */
    protected static function getCollectionItemType(string $key): ?string
    {
        return null; // Default implementation returns null
    }
}

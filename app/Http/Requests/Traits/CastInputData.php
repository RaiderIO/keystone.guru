<?php

namespace App\Http\Requests\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

trait CastInputData
{

    /**
     * Cast request input data based on the target model's casts property.
     *
     * @param Request $request
     * @param string  $model
     * @return array
     */
    protected function castInputData(Request $request, string $model): array
    {
        // Ensure the class exists and is an instance of Model
        if (!class_exists($model) || !is_a($model, Model::class, true)) {
            throw new InvalidArgumentException("The provided model class '{$model}' is not a valid Eloquent model.");
        }

        $model = new $model();

        $casts = $model->getCasts();
        $data  = $request->all();

        foreach ($casts as $field => $type) {
            if (isset($data[$field])) {
                $data[$field] = $this->castValue($data[$field], $type);
            }
        }

        return $data;
    }

    /**
     * Cast a value to the given type.
     *
     * @param mixed  $value
     * @param string $type
     * @return mixed
     */
    protected function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int', 'integer' => (int)$value,
            'real', 'float', 'double' => (float)$value,
            'bool', 'boolean' => (bool)$value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}

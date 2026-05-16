<?php

namespace App\Http\Requests\Webhook;

use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RaiderIOCombatLogWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expectedUser     = (string)config('keystoneguru.webhook.raiderio.user', '');
        $expectedPassword = (string)config('keystoneguru.webhook.raiderio.password', '');

        if ($expectedUser === '' || $expectedPassword === '') {
            return false;
        }

        return hash_equals($expectedUser, (string)$this->getUser()) &&
            hash_equals($expectedPassword, (string)$this->getPassword());
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(response()->json(['message' => 'Unauthorized'], 401));
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
    }

    public function dungeon(): Dungeon
    {
        return once(fn() => Dungeon::query()
            ->where('challenge_mode_id', $this->validated('challenge_mode_id'))
            ->firstOrFail());
    }

    /** @return Collection<int, CharacterClassSpecialization> */
    public function characterClassSpecializations(): Collection
    {
        return once(fn() => CharacterClassSpecialization::query()
            ->whereIn('specialization_id', $this->validated('spec_ids'))
            ->get());
    }

    public function rules(): array
    {
        return [
            'challenge_mode_id' => [
                'required',
                Rule::exists(Dungeon::class, 'challenge_mode_id'),
            ],
            'spec_ids'   => ['required', 'array', 'min:1'],
            'spec_ids.*' => [
                'required',
                Rule::exists(CharacterClassSpecialization::class, 'specialization_id'),
            ],
            's3_bucket'          => ['required', 'string'],
            's3_path'            => ['required', 'string'],
            'combat_log_version' => ['required', 'integer'],
        ];
    }
}

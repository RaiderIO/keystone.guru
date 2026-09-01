<?php

namespace App\Http\Requests\Api\V1\Patreon;

use App\Http\Requests\Api\V1\APIFormRequest;
use App\Models\User;
use Illuminate\Validation\Validator;
use Override;

/**
 * Identifies the account to diagnose by exactly one of id, username or email.
 */
class PatreonUserDiagnosticsRequest extends APIFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id'  => ['nullable', 'integer', 'exists:users,id'],
            'username' => ['nullable', 'string', 'max:255'],
            'email'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->getTargetUser() === null) {
                $validator->errors()->add(
                    'user_id',
                    'Provide one of user_id, username or email that resolves to an existing account.',
                );
            }
        });
    }

    /**
     * The account being asked about, or null when nothing was given or nothing matched.
     *
     * Not named `getUser()`: Symfony's Request already has one, returning the HTTP Basic auth username.
     */
    public function getTargetUser(): ?User
    {
        return once(function (): ?User {
            $userId = $this->input('user_id');
            if ($userId !== null) {
                return User::query()->whereKey((int)$userId)->first();
            }

            $username = $this->input('username');
            if (is_string($username) && $username !== '') {
                return User::query()->where('name', $username)->first();
            }

            $email = $this->input('email');
            if (is_string($email) && $email !== '') {
                return User::query()->where('email', $email)->first();
            }

            return null;
        });
    }

    #[Override]
    protected function getRequestDtoClass(): ?string
    {
        return null;
    }
}

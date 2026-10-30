<?php

namespace App\Http\Requests;

use App\Logic\Utils\HtmlSanitizer;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\Notification\NotificationTarget;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminToolsNotificationStoreRequest extends FormRequest
{
    public const string AUDIENCE_USERS    = 'users';
    public const string AUDIENCE_ROLE     = 'role';
    public const string AUDIENCE_EVERYONE = 'everyone';

    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /** @return array<string, array<int, object|string>|string> */
    public function rules(): array
    {
        return [
            'body'         => ['required', 'string', 'max:1000'],
            'audience'     => ['required', 'string', sprintf('in:%s,%s,%s', self::AUDIENCE_USERS, self::AUDIENCE_ROLE, self::AUDIENCE_EVERYONE)],
            'user_names'   => [sprintf('required_if:audience,%s', self::AUDIENCE_USERS), 'nullable', 'array', 'max:500'],
            'user_names.*' => ['required', 'string', 'exists:users,name'],
            'role'         => [sprintf('required_if:audience,%s', self::AUDIENCE_ROLE), 'nullable', 'string', 'exists:roles,name'],
            // Relative paths only - a notification must not be able to send a reader off-site
            'url'        => ['nullable', 'string', 'max:255', 'starts_with:/'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_names.required_if' => __('validation.custom.notification.user_names_required'),
            'user_names.*.exists'    => __('validation.custom.notification.user_name_unknown'),
            'role.required_if'       => __('validation.custom.notification.role_required'),
            'url.starts_with'        => __('validation.custom.notification.url_relative'),
        ];
    }

    protected function prepareForValidation(): void
    {
        // The form takes the recipients as one comma separated field
        if (is_string($this->input('user_names'))) {
            $this->merge([
                'user_names' => Collection::make(explode(',', (string)$this->string('user_names')))
                    ->map(static fn(string $userName): string => trim($userName))
                    ->filter(static fn(string $userName): bool => $userName !== '')
                    ->values()
                    ->all(),
            ]);
        }

        // Admin announcements are plain text: tags are stripped on the way in, so the feed can
        // render the body without trusting it
        $this->merge([
            'body' => new HtmlSanitizer()->stripAllTags($this->input('body')),
        ]);
    }

    public function getBody(): string
    {
        return (string)$this->string('body');
    }

    public function getUrl(): ?string
    {
        return $this->filled('url') ? (string)$this->string('url') : null;
    }

    public function getExpiresAt(): ?Carbon
    {
        return $this->filled('expires_at') ? Carbon::parse((string)$this->string('expires_at')) : null;
    }

    public function getTarget(): NotificationTarget
    {
        return once(fn(): NotificationTarget => match ((string)$this->string('audience')) {
            self::AUDIENCE_EVERYONE => NotificationTarget::everyone(),
            self::AUDIENCE_ROLE     => NotificationTarget::role((string)$this->string('role')),
            default                 => NotificationTarget::userIds(
                User::query()->whereIn('name', (array)$this->input('user_names', []))->pluck('id')->all(),
            ),
        });
    }
}

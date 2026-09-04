<?php
/**
 * Language and theme, living inside a dropdown menu. Bootstrap 5 has no nested dropdown, so the
 * languages are a compact row of flags rather than a submenu (#4465).
 *
 * @var string $theme
 */

use App\Models\User;

/** @var User|null $user */
$user = Auth::user();
$currentUserLocale = Auth::check() ? $user->locale : config('app.locale');
/** @var array<int, array<string, mixed>> $allLanguagesConfig */
$allLanguagesConfig = config('language.all', []);
$allLanguages       = collect($allLanguagesConfig)->keyBy('long');
?>
<div class="ksg-nav-prefs">
    <div class="ksg-nav-prefs-languages">
        @foreach (language()->allowed() as $code => $name)
            <a class="ksg-nav-prefs-language {{ $currentUserLocale === $code ? 'active' : '' }}"
               href="{{ language()->back($code) }}" title="{{ $name }}">
                @include('vendor.language.flag', ['code' => $code, 'name' => $name])
                @if(isset($allLanguages[$code]['ai']) && $allLanguages[$code] && $allLanguages[$code]['ai'])
                    <sup class="text-warning">AI</sup>
                @endif
            </a>
        @endforeach
    </div>
    <a class="dropdown-item ksg-nav-prefs-contribute" href="https://crowdin.com/project/keystoneguru">
        <i class="fas fa-external-link-alt"></i> {{ __('view_vendor.language.flags.contribute_translations') }}
    </a>
    <div class="ksg-nav-prefs-theme">
        @include('common.layout.nav.themeswitch')
    </div>
</div>

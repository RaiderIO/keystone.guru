<?php
$user = Auth::user();
// Default local or user locale
$currentUserLocale     = Auth::check() ? $user->locale : config('app.locale');
$currentUserLocaleName = language()->getName($currentUserLocale);
$allLanguages = collect(config('language.all'))->keyBy('long');
?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button"
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @include('vendor.language.flag', ['code' => $currentUserLocale, 'name' => $currentUserLocaleName])
    </a>
    <div class="dropdown-menu text-center text-xl-left" aria-labelledby="languageDropdown">
        @foreach (language()->allowed() as $code => $name)
            <a class="dropdown-item {{ $currentUserLocale === $code ? 'active' : '' }}"
               href="{{ language()->back($code) }}">
                @include('vendor.language.flag', ['code' => $code, 'name' => $name]) {{ $name }}
                @if(isset($allLanguages[$code]['ai']) && $allLanguages[$code] && $allLanguages[$code]['ai'])
                    <sup class="text-warning">AI</sup>
                @endif
            </a>
        @endforeach
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="https://crowdin.com/project/keystoneguru">
            <i class="fas fa-external-link-alt"></i> {{ __('view_vendor.language.flags.contribute_translations') }}
        </a>

    </div>
</li>

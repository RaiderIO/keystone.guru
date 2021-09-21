<?php
$user = Auth::user();
// Default local or user locale
$currentUserLocale     = Auth::check() ? Auth::user()->locale : 'en';
$currentUserLocaleName = language()->getName($currentUserLocale);
?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button"
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @include('vendor.language.flag', ['code' => $currentUserLocale, 'name' => $currentUserLocaleName])
    </a>
    <div class="dropdown-menu text-center text-lg-left" aria-labelledby="languageDropdown">
        @foreach (language()->allowed() as $code => $name)
            <a class="dropdown-item {{ $currentUserLocale === $code ? 'active' : '' }}"
               href="{{ language()->back($code) }}">
                @include('vendor.language.flag', ['code' => $code, 'name' => $name]) {{ $name }}
            </a>
        @endforeach
    </div>
</li>

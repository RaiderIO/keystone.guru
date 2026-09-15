<?php
/**
 * A top-level navbar category: one toggle whose panel lists the category's destinations with a
 * one-line description each. New features land inside a category, never as a new toggle (#4465).
 *
 * @var string                                  $id
 * @var string                                  $fa
 * @var string                                  $text
 * @var array<int, array<string, mixed>>        $entries      route|modal, fa, text, description, strict
 * @var int                                     $columns
 * @var Closure(string, bool=): (string|null)   $isActiveRoute
 */
$columns ??= 1;

$hasActiveEntry = null;
foreach ($entries as $entry) {
    if (isset($entry['route'])) {
        $hasActiveEntry ??= $isActiveRoute($entry['route'], $entry['strict'] ?? false);
    }
}
?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ $hasActiveEntry }}" href="#" id="{{ $id }}" role="button"
       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="{{ $fa }}"></i> {{ $text }}
    </a>
    <div class="dropdown-menu ksg-nav-panel {{ $columns === 2 ? 'ksg-nav-panel--2col' : '' }}"
         aria-labelledby="{{ $id }}">
        @foreach($entries as $entry)
            <a class="dropdown-item ksg-nav-entry {{ $loop->first ? 'ksg-nav-entry--primary' : '' }} {{ isset($entry['route']) ? $isActiveRoute($entry['route'], $entry['strict'] ?? false) : '' }}"
               href="{{ $entry['route'] ?? '#' }}"
               @isset($entry['modal'])
                   data-bs-toggle="modal" data-bs-target="{{ $entry['modal'] }}"
               @endisset>
                <i class="{{ $entry['fa'] }} fa-fw"></i>
                <span class="ksg-nav-entry-text">
                    <span class="ksg-nav-entry-name">{{ $entry['text'] }}</span>
                    <span class="ksg-nav-entry-desc">{{ $entry['description'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</li>

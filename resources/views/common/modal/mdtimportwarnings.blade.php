<?php
/**
 * The warnings the MDT import that produced the draft being edited raised, shown once after the import.
 *
 * @var array<int, string> $warnings
 */
?>
<h3 class="card-title">{{ __('view_common.modal.mdtimportwarnings.title') }}</h3>

<p>{{ __('view_common.modal.mdtimportwarnings.description') }}</p>

<ul class="mdt_import_warnings_list">
    @foreach($warnings as $warning)
        <li>{{ $warning }}</li>
    @endforeach
</ul>

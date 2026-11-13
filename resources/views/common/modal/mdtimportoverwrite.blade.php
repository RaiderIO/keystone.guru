<?php

use App\Models\DungeonRoute\DungeonRoute;

/**
 * Replaces the contents of the route being edited with an MDT string, through a draft the author reviews first.
 *
 * @var DungeonRoute       $dungeonroute
 * @var array<string, int> $contentLoss  What the route holds that the string replaces, per kind
 */
$pendingDraftId  = $dungeonroute->upgradeDraft?->id;
$hasPendingDraft = $pendingDraftId !== null;
?>
@include('common.general.inline', ['path' => 'common/modal/mdtimportoverwrite', 'options' => [
    'modalSelector' => '#mdt_import_overwrite_modal',
    'importStringTextAreaSelector' => '#mdt_import_overwrite_string',
    'loaderSelector' => '.mdt_import_overwrite_loader',
    'detailsSelector' => '.mdt_import_overwrite_details',
    'warningsSelector' => '.mdt_import_overwrite_warnings',
    'errorsSelector' => '.mdt_import_overwrite_errors',
    'resetSelector' => '.mdt_import_overwrite_reset',
    'discardExistingDraftSelector' => '#mdt_import_overwrite_discard_existing_draft',
    'submitSelector' => '#mdt_import_overwrite_submit',
    'pendingDraftId' => $pendingDraftId,
    'detailsUrl' => route('mdt.details'),
    'importUrl' => route('dungeonroute.upgrade.mdtimport', [
        'dungeon' => $dungeonroute->dungeon,
        'dungeonroute' => $dungeonroute,
        'title' => $dungeonroute->getTitleSlug(),
    ]),
]])

<h3 class="card-title">{{ __('view_common.modal.mdtimportoverwrite.title') }}</h3>

<p>{{ __('view_common.modal.mdtimportoverwrite.description') }}</p>

<div class="mb-3">
    <div class="row mb-2">
        <div class="col">
            <label for="mdt_import_overwrite_string">
                {{ __('view_common.modal.mdtimportoverwrite.paste_mdt_export_string') }}<span class="form-required">*</span>
            </label>
        </div>
        <div class="col-auto mdt_import_overwrite_reset" style="display: none;">
            <div class="btn btn-outline-warning" data-bs-toggle="tooltip"
                 title="{{ __('view_common.modal.mdtimportoverwrite.reset_title') }}">
                <i class="fas fa-undo"></i>
            </div>
        </div>
    </div>
    <textarea id="mdt_import_overwrite_string" class="form-control" rows="4"></textarea>
</div>

<div class="mb-3">
    <div class="bg-info p-1 mdt_import_overwrite_loader" style="display: none;">
        <i class="fas fa-stroopwafel fa-spin"></i> {{ __('view_common.modal.mdtimportoverwrite.parsing_your_string') }}
    </div>
</div>

<div class="mb-3 mdt_import_overwrite_details"></div>
<div class="mb-3 mdt_import_overwrite_warnings"></div>
<div class="mb-3 mdt_import_overwrite_errors"></div>

<div class="alert alert-warning">
    <p class="mb-1">
        <i class="fas fa-exclamation-triangle"></i> {{ __('view_common.modal.mdtimportoverwrite.full_replace_warning') }}
    </p>
    @if(!empty($contentLoss))
        <p class="mb-1">{{ __('view_common.modal.mdtimportoverwrite.content_loss_intro') }}</p>
        <ul class="mb-1 mdt_import_overwrite_content_loss">
            @foreach($contentLoss as $kind => $count)
                <li>{{ trans_choice(sprintf('view_common.modal.mdtimportoverwrite.content_loss.%s', $kind), $count, ['count' => $count]) }}</li>
            @endforeach
        </ul>
    @endif
    <p class="mb-0">{{ __('view_common.modal.mdtimportoverwrite.metadata_kept') }}</p>
</div>

@if($hasPendingDraft)
    <div class="alert alert-danger">
        <p>
            <i class="fas fa-exclamation-circle"></i> {{ __('view_common.modal.mdtimportoverwrite.pending_draft') }}
        </p>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="mdt_import_overwrite_discard_existing_draft">
            <label for="mdt_import_overwrite_discard_existing_draft" class="form-check-label">
                {{ __('view_common.modal.mdtimportoverwrite.discard_existing_draft') }}
            </label>
        </div>
    </div>
@endif

<button id="mdt_import_overwrite_submit" class="btn btn-primary" disabled>
    <i class="fas fa-file-import"></i> {{ __('view_common.modal.mdtimportoverwrite.create_draft') }}
</button>

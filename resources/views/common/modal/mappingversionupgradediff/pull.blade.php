<?php

use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiffPull;

/**
 * The pull a diff row belongs to, carrying the same colour swatch the map draws it with.
 *
 * @var MappingVersionUpgradeDiffPull|null $pull
 */
?>
@if($pull !== null)
    <span class="badge me-1"
          style="background-color: {{ $pull->color ?? '#6c757d' }}; color: #fff;">
        {{ __('view_common.modal.mappingversionupgradediff.pull', ['index' => $pull->index]) }}
    </span>
@endif

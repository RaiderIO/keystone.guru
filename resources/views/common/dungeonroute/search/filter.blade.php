<?php
/** @var $key string */
/** @var $text string */
/** @var $expanded boolean */
$expanded = $expanded ?? true;
?>
<div class="form-group">
    <div id="filter_{{ $key }}">
        <div class="card">
            <div class="card-header p-0" id="filter_heading_{{ $key }}">
                <h5 class="mb-0">
                    <button class="btn btn-link" data-toggle="collapse" data-target="#filter_body_{{ $key }}"
                            aria-expanded="{{ $expanded ? 'true' : 'false' }}" aria-controls="filter_body_{{ $key }}">
                        {{ $text }}
                    </button>
                </h5>
            </div>

            <div id="filter_body_{{ $key }}" class="collapse {{ $expanded ? 'show' : '' }}"
                 aria-labelledby="filter_heading_{{ $key }}"
                 data-parent="#filter_{{ $key }}">
                <div class="card-body px-3 py-2">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>

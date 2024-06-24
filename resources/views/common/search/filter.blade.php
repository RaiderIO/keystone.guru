<?php
/**
 * @var string $key
 * @var string $text
 * @var boolean $expanded
 * @var string|null $title
 */
$expanded ??= true;
$title ??= null;
?>
<div class="form-group">
    <div id="filter_accordeon_{{ $key }}">
        <div class="card">
            <div class="card-header p-0" id="filter_heading_{{ $key }}">
                <div class="row">
                    <div class="col">
                        <h5 class="mb-0">
                            <label for="{{ $key }}" class="mb-0">
                                <button class="btn btn-link" data-toggle="collapse" data-target="#filter_body_{{ $key }}"
                                        aria-expanded="{{ $expanded ? 'true' : 'false' }}"
                                        aria-controls="filter_body_{{ $key }}">
                                    {{ $text }}
                                </button>
                            </label>
                        </h5>
                    </div>
                    @if( $title !== null )
                        <div class="col-auto">
                            <i class="fas fa-info-circle pr-2" data-toggle="tooltip" title="{{$title}}" style="padding-top: 0.75rem !important;"></i>
                        </div>
                    @endif
                </div>
            </div>

            <div id="filter_body_{{ $key }}" class="collapse {{ $expanded ? 'show' : '' }}"
                 aria-labelledby="filter_heading_{{ $key }}"
                 data-parent="#filter_accordeon_{{ $key }}">
                <div class="card-body px-3 py-2">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>

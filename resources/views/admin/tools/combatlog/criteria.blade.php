@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.combatlog.criteria.title')])

@section('header-title', __('view_admin.tools.combatlog.criteria.header'))

@section('content')
    <?php
    use App\Models\CombatLog\CombatLogParsingCriterion;
    use App\Models\Interfaces\CombatLogCriterionModelInterface;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Str;

    /**
     * @var Collection<int, Collection<int, CombatLogParsingCriterion>>            $criteriaByVersion
     * @var array<class-string, Collection<int, CombatLogCriterionModelInterface>> $modelsById
     */
    ?>

    <div class="d-flex mb-3" style="gap: 8px;">
        {{ html()->form('POST', route('admin.tools.combatlog.criteria.reset'))->open() }}
        {{ html()->input('submit')->value(__('view_admin.tools.combatlog.criteria.reset_all'))->class('btn btn-danger') }}
        {{ html()->form()->close() }}

        <button type="submit" form="thresholds-form" class="btn btn-primary">
            {{ __('view_admin.tools.combatlog.criteria.save_thresholds') }}
        </button>
    </div>

    @if($criteriaByVersion->isEmpty())
        <p class="text-muted">{{ __('view_admin.tools.combatlog.criteria.no_data') }}</p>
    @else
        <form id="thresholds-form" action="{{ route('admin.tools.combatlog.criteria.thresholds') }}" method="POST">
            @csrf

            @foreach($criteriaByVersion as $version => $versionCriteria)
                <div class="mb-3">
                    <h3 class="mt-4">{{ __('view_admin.tools.combatlog.criteria.version', ['version' => number_format(num: $version, thousands_separator: '.')]) }}</h3>

                    @foreach(array_keys(CombatLogParsingCriterion::VALID_CRITERIA) as $modelClass)
                        <?php $modelCriteria = $versionCriteria->where('model_class', $modelClass); ?>
                        @if($modelCriteria->isNotEmpty())
                            <h4 class="mt-3">{{ class_basename($modelClass) }}</h4>
                            {{-- One accordion item per band, all collapsed: with every criterion repeated
                                 across every band the page is otherwise thousands of pixels tall. The
                                 header carries the whole overview so it rarely needs expanding. --}}
                            <div class="accordion mb-3">
                                @foreach($modelCriteria->groupBy('mythic_level_min') as $bandCriteria)
                                    @php
                                        /** @var CombatLogParsingCriterion $firstOfBand */
                                        $firstOfBand = $bandCriteria->first();
                                        $isTopBand   = $firstOfBand->isTopBand();
                                        $totalCount  = $bandCriteria->sum('count');

                                        // The top band has no budget - its rows are counters for visibility only,
                                        // so "fulfilled" is meaningless there and it stays neutral.
                                        $fulfilled = $isTopBand ? 0 : $bandCriteria->filter(
                                            fn(CombatLogParsingCriterion $criterion): bool => $criterion->count >= $criterion->threshold
                                        )->count();

                                        // Both site themes keep Bootstrap's light palette (the theme is a
                                        // body class, not data-bs-theme), so the subtle background stays light
                                        // and needs the matching -emphasis text colour to stay readable.
                                        $headerClass = match (true) {
                                            $isTopBand                            => 'bg-info-subtle text-info-emphasis',
                                            $fulfilled === $bandCriteria->count() => 'bg-success-subtle text-success-emphasis',
                                            default                               => 'bg-warning-subtle text-warning-emphasis',
                                        };

                                        $collapseId = sprintf(
                                            'criteria-band-%d-%s-%d',
                                            $version,
                                            Str::slug(class_basename($modelClass)),
                                            $firstOfBand->mythic_level_min,
                                        );
                                    @endphp
                                    <div class="accordion-item">
                                        <h5 class="accordion-header mb-0">
                                            <button
                                                class="accordion-button collapsed {{ $headerClass }}"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $collapseId }}"
                                            >
                                                <span class="d-flex flex-wrap align-items-center w-100 pe-3" style="gap: 12px;">
                                                    <span class="fw-bold">
                                                        {{ $isTopBand
                                                            ? __('view_admin.tools.combatlog.criteria.band_top', ['band' => $firstOfBand->getBandName()])
                                                            : __('view_admin.tools.combatlog.criteria.band', ['band' => $firstOfBand->getBandName()]) }}
                                                    </span>
                                                    @if($isTopBand)
                                                        <span class="badge text-bg-info">
                                                            {{ __('view_admin.tools.combatlog.criteria.band_summary_top', ['count' => $totalCount]) }}
                                                        </span>
                                                    @else
                                                        <span class="badge {{ $fulfilled === $bandCriteria->count() ? 'text-bg-success' : 'text-bg-warning' }}">
                                                            {{ __('view_admin.tools.combatlog.criteria.band_summary', [
                                                                'fulfilled' => $fulfilled,
                                                                'total'     => $bandCriteria->count(),
                                                            ]) }}
                                                        </span>
                                                        <span class="small opacity-75">
                                                            {{ __('view_admin.tools.combatlog.criteria.band_summary_parsed', [
                                                                'count'     => $totalCount,
                                                                'threshold' => $bandCriteria->sum('threshold'),
                                                            ]) }}
                                                        </span>
                                                    @endif
                                                </span>
                                            </button>
                                        </h5>
                                        {{-- Collapsed only hides the panel, it stays in the DOM - which is what keeps
                                             the threshold inputs of every band part of the form on submit. --}}
                                        <div id="{{ $collapseId }}" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                @foreach($bandCriteria as $criterion)
                                                    @php
                                                        /** @var CombatLogCriterionModelInterface|null $model */
                                                        $model     = $modelsById[$modelClass]->get($criterion->model_id);
                                                        $label     = $model?->getName() ?? sprintf('#%d', $criterion->model_id);
                                                        $imageLink = $model?->getImageLink();
                                                        $pct       = $criterion->threshold > 0 ? min(100, round($criterion->count / $criterion->threshold * 100)) : 0;
                                                    @endphp
                                                    <div class="d-flex align-items-center mb-2">
                                                        @if($imageLink !== null)
                                                            <img src="{{ $imageLink }}" alt="{{ $label }}" style="height: 48px; width: auto; object-fit: cover;" class="me-3 rounded flex-shrink-0">
                                                        @endif
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <span>{{ $label }}</span>
                                                                <div class="d-flex align-items-center ms-3" style="gap: 4px;">
                                                                    @if($isTopBand)
                                                                        {{-- The top band is always parsed, so it has no threshold to configure --}}
                                                                        <small class="text-muted">{{ __('view_admin.tools.combatlog.criteria.parsed_count', ['count' => $criterion->count]) }}</small>
                                                                    @else
                                                                        <small class="text-muted">{{ $criterion->count }} /</small>
                                                                        <input
                                                                            type="number"
                                                                            name="thresholds[{{ $criterion->id }}]"
                                                                            value="{{ $criterion->threshold }}"
                                                                            min="1"
                                                                            class="form-control form-control-sm"
                                                                            style="width: 70px;"
                                                                        >
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            @unless($isTopBand)
                                                                <div class="progress">
                                                                    <div
                                                                        class="progress-bar {{ $criterion->count >= $criterion->threshold ? 'bg-success' : '' }}"
                                                                        role="progressbar"
                                                                        style="width: {{ $pct }}%;"
                                                                        aria-valuenow="{{ $criterion->count }}"
                                                                        aria-valuemin="0"
                                                                        aria-valuemax="{{ $criterion->threshold }}"
                                                                    >
                                                                        {{ $pct }}%
                                                                    </div>
                                                                </div>
                                                            @endunless
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </form>
    @endif
@endsection

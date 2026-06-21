@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.combatlog.criteria.title')])

@section('header-title', __('view_admin.tools.combatlog.criteria.header'))

@section('content')
    <?php
    use App\Models\CombatLog\CombatLogParsingCriterion;
    use App\Models\Interfaces\CombatLogCriterionModelInterface;
    use Illuminate\Support\Collection;

    /**
     * @var Collection<int, Collection<int, CombatLogParsingCriterion>>                                               $criteriaByVersion
     * @var array<class-string, \Illuminate\Support\Collection<int, CombatLogCriterionModelInterface>> $modelsById
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
                <div class="form-group">
                    <h3 class="mt-4">{{ __('view_admin.tools.combatlog.criteria.version', ['version' => number_format(num: $version, thousands_separator: '.')]) }}</h3>

                    @foreach(array_keys(CombatLogParsingCriterion::VALID_CRITERIA) as $modelClass)
                        <?php $modelCriteria = $versionCriteria->where('model_class', $modelClass); ?>
                        @if($modelCriteria->isNotEmpty())
                            <h4 class="mt-3">{{ class_basename($modelClass) }}</h4>
                            @foreach($modelCriteria as $criterion)
                                @php
                                    /** @var CombatLogCriterionModelInterface|null $model */
                                    $model     = $modelsById[$modelClass]->get($criterion->model_id);
                                    $label     = $model?->getName() ?? sprintf('#%d', $criterion->model_id);
                                    $imageLink = $model?->getImageLink();
                                    $pct       = $criterion->threshold > 0 ? min(100, round($criterion->count / $criterion->threshold * 100)) : 0;
                                @endphp
                                <div class="d-flex align-items-center mb-2">
                                    @if($imageLink !== null)
                                        <img src="{{ $imageLink }}" alt="{{ $label }}" style="height: 48px; width: auto; object-fit: cover;" class="mr-3 rounded flex-shrink-0">
                                    @endif
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span>{{ $label }}</span>
                                            <div class="d-flex align-items-center ml-3" style="gap: 4px;">
                                                <small class="text-muted">{{ $criterion->count }} /</small>
                                                <input
                                                    type="number"
                                                    name="thresholds[{{ $criterion->id }}]"
                                                    value="{{ $criterion->threshold }}"
                                                    min="1"
                                                    class="form-control form-control-sm"
                                                    style="width: 70px;"
                                                >
                                            </div>
                                        </div>
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
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            @endforeach
        </form>
    @endif
@endsection

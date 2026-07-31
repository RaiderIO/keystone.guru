<?php

namespace App\Http\Controllers;

use App\Http\Requests\AffixGroupFormRequest;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Season;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Session;

class AffixGroupController extends Controller
{
    /**
     * @throws Exception
     */
    public function store(AffixGroupFormRequest $request, Season $season, ?AffixGroup $affixGroup = null): AffixGroup
    {
        $validated = $request->validated();

        $attributes = [
            'season_id'      => $season->id,
            'expansion_id'   => $season->expansion_id,
            'seasonal_index' => $validated['seasonal_index'] ?? null,
            'confirmed'      => $validated['confirmed'] ?? 0,
        ];

        if ($affixGroup === null) {
            $affixGroup = AffixGroup::create($attributes);
        } else {
            $affixGroup->update($attributes);
        }

        $affixGroup->syncAffixGroupCouplings($this->getCouplingsFromValidated($validated));

        return $affixGroup;
    }

    /**
     * @return View
     */
    public function create(Season $season): View
    {
        return view('admin.affixgroup.edit', [
            'season'      => $season,
            'affixSelect' => $this->getAffixSelect(),
            'couplings'   => [],
        ]);
    }

    /**
     * @return View
     */
    public function edit(Season $season, AffixGroup $affixGroup): View
    {
        $this->assertBelongsToSeason($season, $affixGroup);

        return view('admin.affixgroup.edit', [
            'season'      => $season,
            'affixGroup'  => $affixGroup,
            'affixSelect' => $this->getAffixSelect(),
            'couplings'   => $affixGroup->affixGroupCouplings()->orderBy('id')->get(['affix_id', 'key_level'])->toArray(),
        ]);
    }

    /**
     * @return View
     *
     * @throws Exception
     */
    public function update(AffixGroupFormRequest $request, Season $season, AffixGroup $affixGroup)
    {
        $this->assertBelongsToSeason($season, $affixGroup);

        $affixGroup = $this->store($request, $season, $affixGroup);

        Session::flash('status', __('controller.affixgroup.flash.affixgroup_updated'));

        return $this->edit($season, $affixGroup);
    }

    /**
     * @throws Exception
     */
    public function savenew(AffixGroupFormRequest $request, Season $season): RedirectResponse
    {
        $affixGroup = $this->store($request, $season);

        Session::flash('status', __('controller.affixgroup.flash.affixgroup_created'));

        return redirect()->route('admin.affixgroup.edit', ['season' => $season, 'affixGroup' => $affixGroup]);
    }

    public function delete(Season $season, AffixGroup $affixGroup): RedirectResponse
    {
        $this->assertBelongsToSeason($season, $affixGroup);

        $affixGroup->syncAffixGroupCouplings([]);
        $affixGroup->delete();

        Session::flash('status', __('controller.affixgroup.flash.affixgroup_deleted'));

        return redirect()->route('admin.season.edit', ['season' => $season]);
    }

    private function assertBelongsToSeason(Season $season, AffixGroup $affixGroup): void
    {
        if ($affixGroup->season_id !== $season->id) {
            abort(404, __('controller.generic.error.not_found'));
        }
    }

    /**
     * @param  array<string, mixed>                             $validated
     * @return array<int, array{affix_id: int, key_level: int}>
     */
    private function getCouplingsFromValidated(array $validated): array
    {
        $couplings = [];

        for ($slot = 1; $slot <= AffixGroupFormRequest::SLOT_COUNT; $slot++) {
            $affixId = $validated[sprintf('affix_id_%d', $slot)] ?? -1;
            if ((int)$affixId === -1) {
                continue;
            }

            $couplings[] = [
                'affix_id'  => (int)$affixId,
                'key_level' => (int)$validated[sprintf('key_level_%d', $slot)],
            ];
        }

        return $couplings;
    }

    /**
     * @return Collection<int, string>
     */
    private function getAffixSelect(): Collection
    {
        return Affix::orderBy('key')->get()->mapWithKeys(fn(Affix $affix) => [$affix->id => __($affix->name)]);
    }
}

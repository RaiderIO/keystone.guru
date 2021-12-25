<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 12-4-2019
 * Time: 00:02
 */

namespace App\Http\Controllers;

use App\Http\Requests\Tag\TagFormRequest;
use App\Http\Requests\TeamFormRequest;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Session;
use Teapot\StatusCode;

class TeamController extends Controller
{
    /**
     * @param TeamFormRequest $request
     * @param Team|null $team
     * @return mixed
     * @throws Exception
     */
    public function store(TeamFormRequest $request, Team $team = null)
    {
        $new = $team === null;

        if ($new) {
            $team             = new Team();
            $team->name       = $request->get('name');
            $team->public_key = Team::generateRandomPublicKey();
        }

        $team->description  = $request->get('description');
        $team->invite_code  = Team::generateRandomPublicKey(12, 'invite_code');
        $team->icon_file_id = -1;

        // Update or insert it
        if ($team->save()) {
            $logo = $request->file('logo');

            // Save was successful, now do any file handling that may be necessary
            if ($logo !== null) {
                // Save was successful, now do any file handling that may be necessary
                try {
                    $team->saveUploadedFile($logo);
                } catch (Exception $ex) {
                    if ($new) {
                        // Roll back the saving of the expansion since something went wrong with the file.
                        $team->delete();
                    }
                    throw $ex;
                }
            }

            if ($new) {
                // If saving team + logo was successful, save our own user as its first member
                $team->addMember(Auth::user(), 'admin');
            }
        }

        return $team;
    }

    /**
     * @return Factory|View
     */
    public function new()
    {
        return view('team.new');
    }

    /**
     * @param Request $request
     * @param Team $team
     * @return Application|ResponseFactory|RedirectResponse|Response
     * @throws AuthorizationException
     */
    public function edit(Request $request, Team $team)
    {
        $this->authorize('edit', $team);

        return view('team.edit', ['team' => $team]);
    }

    /**
     * @param Request $request
     * @param Team $team
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function delete(Request $request, Team $team)
    {
        $this->authorize('delete', $team);

        try {
            $team->delete();
        } catch (Exception $ex) {
            abort(500);
        }

        return redirect()->route('team.list');
    }

    /**
     * @param TeamFormRequest $request
     * @param Team $team
     * @return Team|Factory|Builder|Model|RedirectResponse|View|object
     * @throws Exception
     */
    public function update(TeamFormRequest $request, Team $team)
    {
        $this->authorize('edit', $team);

        // Store it and show the edit page again
        $teamModel = $this->store($request, $team);

        // Message to the user
        Session::flash('status', __('controller.team.flash.team_updated'));

        // Display the edit page
        return $this->edit($request, $team);
    }

    /**
     * @param TeamFormRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(TeamFormRequest $request)
    {
        // Store it and show the edit page
        $team = $this->store($request);

        // Message to the user
        Session::flash('status', __('controller.team.flash.team_created'));

        return redirect()->route('team.edit', ['team' => $team]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function list()
    {
        $user = Auth::user();
        return view('team.list', ['models' => $user->teams]);
    }

    /**
     * @param Request $request
     * @param string $invitecode
     * @return Factory|View
     */
    public function invite(Request $request, string $invitecode)
    {
        /** @var Team $team */
        $team   = Team::where('invite_code', $invitecode)->first();
        $result = null;

        if ($team !== null) {
            if ($team->isCurrentUserMember()) {
                $result = view('team.invite', ['team' => $team, 'member' => true]);
            } else {
                $result = view('team.invite', ['team' => $team]);
            }
        } else {
            abort(StatusCode::NOT_FOUND, __('controller.team.flash.unable_to_find_team_for_invite_code'));
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param string $invitecode
     * @return Factory|View
     */
    public function inviteaccept(Request $request, string $invitecode)
    {
        /** @var Team $team */
        $team = Team::where('invite_code', $invitecode)->first();

        if ($team->isCurrentUserMember()) {
            $result = view('team.invite', ['team' => $team, 'member' => true]);
        } else {
            $team->addMember(Auth::getUser(), 'member');

            Session::flash('status', sprintf(__('controller.team.flash.invite_accept_success'), $team->name));
            $result = redirect()->route('team.edit', ['team' => $team]);
        }

        return $result;
    }

    /**
     * Creates a tag from the tag manager
     *
     * @param TagFormRequest $request
     * @return RedirectResponse
     */
    public function createtag(TagFormRequest $request)
    {
        $error = [];

        $tagCategoryId = TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM];

        if (!Tag::where('name', $request->get('tag_name_new'))
            ->where('user_id', Auth::id())
            ->where('tag_category_id', $tagCategoryId)
            ->exists()) {

            Tag::saveFromRequest($request, $tagCategoryId);

            Session::flash('status', __('controller.team.flash.tag_created_successfully'));
        } else {
            $error = ['tag_name_new' => __('controller.team.flash.tag_already_exists')];
        }

        return Redirect::back()->withErrors($error);
    }
}

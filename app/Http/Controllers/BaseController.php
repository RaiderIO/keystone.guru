<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Foundation\Http\FormRequest;

abstract class BaseController extends Controller
{
    /**
     * @var string The name of the model that this BaseController is representing. Used in echoing titles, echoing views and more.
     */
    private $_name;

    public function __construct($name)
    {
        $this->_name = $name;
    }

    public abstract function getNewHeaderTitle();

    public abstract function getEditHeaderTitle();

    /**
     * @param FormRequest $request
     * @param int $id
     * @return mixed
     */
    public abstract function store($request, int $id = -1);

    /**
     * Gets the fully qualified class name of the model this controller is describing
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function _getModelClassname(){
        return sprintf("\App\Models\%s", ucfirst($this->_name));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function _getModelInstance()
    {
        $className = $this->_getModelClassname();
        $model = new $className();
        // MUST be a model!
        assert($model instanceof Model);

        return $model;
    }

    /**
     * Saves a new Model to the database (from POST). This will redirect back to the Edit for that very item.
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    protected function _savenew($request)
    {
        // Store it and show the edit page for the new item upon success
        return redirect()->route(sprintf("admin.%s.edit", $this->_name), ["id" => $this->store($request)]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function view()
    {
        $className = $this->_getModelClassname();
        $models = $className::all();
        return view(sprintf("admin.%s.view", $this->_name), compact('models'));
    }

    /**
     * Handles the viewing of a new Model; shows the create Model page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        $headerTitle = $this->getNewHeaderTitle();
        return view(sprintf('admin.%s.edit', $this->_name), compact('headerTitle'));
    }

    /**
     * Edits a Model; displaying the edit Model page.
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($id)
    {
        $model = $this->_getModelInstance();
        $model = $model->find($id);
        if ($model === null) {
            abort(500, 'Unable to load ' . $this->_name);
        }
        $headerTitle = $this->getEditHeaderTitle();
        return view(sprintf('admin.%s.edit', $this->_name), compact('model', 'headerTitle'));
    }

    /**
     * Saves a new model to the database (from POST/PATCH). This will refresh the page and show the edit page again.
     *
     * @param FormRequest $request
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(FormRequest $request, $id)
    {
        // Store it and show the edit page again
        return $this->edit($this->store($request, $id));
    }
}
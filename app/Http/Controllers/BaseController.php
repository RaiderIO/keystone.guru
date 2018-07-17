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

    /**
     * @var string The class name of the model that this controller is representing.
     */
    private $_modelClassName;

    /**
     * @var string An optional prefix for the route.
     */
    private $_routePrefix;

    /**
     * @var array Any optional variables you want to pass to the view.
     */
    private $_variables = array();

    public function __construct($name, $modelClassName, $routePrefix = '')
    {
        $this->_name = $name;
        $this->_modelClassName = $modelClassName;
        $this->_routePrefix = trim($routePrefix, '.');
    }

    /**
     * @return string The prefix to prepend to any routes that are called for this model.
     */
    private function _getRoutePrefix()
    {
        $result = '';
        if (!empty($this->_routePrefix)) {
            $result = sprintf("%s.", $this->_routePrefix);
        }
        return $result;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function _getModelInstance()
    {
        $model = new $this->_modelClassName();
        // MUST be a model!
        assert($model instanceof Model);

        return $model;
    }

    protected function _setVariables(array $variables)
    {
        $this->_variables = $variables;
    }

    protected function _addVariable($key, $value)
    {
        $this->_variables[$key] = $value;
    }

    /**
     * Override the new action view.
     * @return string String with the name of the new action's view.
     */
    protected function _getNewActionView()
    {
        return 'edit';
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
        return redirect()->route(sprintf("%s%s.edit", $this->_getRoutePrefix(), $this->_name), ["id" => $this->store($request)]);
    }

    /**
     * Saves a new model to the database (from POST/PATCH). This will refresh the page and show the edit page again.
     *
     * @param FormRequest $request
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    protected function _update($request, $id)
    {
        // Store it and show the edit page again
        return $this->edit($this->store($request, $id));
    }

    /**
     * Gets a list of models to display when the view() is called.
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model[]
     */
    protected function _getViewModels()
    {
        return $this->_modelClassName::all();
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function view()
    {
        $models = $this->_getViewModels();

        return view(
            sprintf("%s%s.view", $this->_getRoutePrefix(), $this->_name),
            array_merge($this->_variables, compact('models'))
        );
    }

    /**
     * Handles the viewing of a new Model; shows the create Model page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        $headerTitle = $this->getNewHeaderTitle();
        return view(
            sprintf('%s%s.%s', $this->_getRoutePrefix(), $this->_name, $this->_getNewActionView()),
            array_merge($this->_variables, compact('headerTitle'))
        );
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
        return view(
            sprintf('%s%s.edit', $this->_getRoutePrefix(), $this->_name),
            array_merge($this->_variables, compact('model', 'headerTitle'))
        );
    }

    public abstract function getNewHeaderTitle();

    public abstract function getEditHeaderTitle();

    /**
     * @param FormRequest $request
     * @param int $id
     * @return mixed
     */
    public abstract function store($request, int $id = -1);
}
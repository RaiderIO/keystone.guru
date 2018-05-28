<?php

namespace App\Http\Controllers;

use \Illuminate\Foundation\Http\FormRequest;

abstract class BaseController extends Controller
{
    /**
     * @var string The name of the model that this BaseController is representing. Used in echoing titles, echoing views and more.
     */
    private $_name;

    public function __construct($name){
        $this->_name = $name;
    }

    public abstract function getNewHeaderTitle();
    public abstract function getEditHeaderTitle();

    /**
     * @param string $request
     * @param int $id
     * @return mixed
     */
    public abstract function storeModel($request, int $id = -1);

    /**
     * @return \Illuminate\Database\Eloquent\Model The string class name of the model that this controller is representing.
     * @note Type hint indicate this returns a model to help with PhpStorm warnings n auto-complete
     */
    private function _getModelClassName(){
        return sprintf("\App\Models\%s", ucfirst($this->_name));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function _getModelInstance(){
        $className = $this->_getModelClassName();
        $model = new $className();
        // MUST be a model!
        assert($model instanceof \Illuminate\Database\Eloquent\Model);

        return $model;
    }

    protected function _new()
    {
        $headerTitle = $this->getNewHeaderTitle();
        return view(sprintf('admin.%s.edit', $this->_name), compact('headerTitle'));
    }

    protected function _edit($id)
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
     * @param FormRequest $request
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    protected function _update(FormRequest $request, $id)
    {
        // Store it and show the edit page again
        return $this->_edit($this->storeModel($request, $id));
    }

    /**
     * @param FormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    protected function _savenew(FormRequest $request)
    {
        // Store it and show the edit page for the new item upon success
        return redirect()->route(sprintf("admin.%s.edit", $this->_name), ["id" => $this->storeModel($request)]);
    }

    protected function view()
    {
        $className = $this->_getModelClassName();
        $expansions = $className::select(['id', 'icon_file_id', 'name', 'color'])->with('icon')->get();

        return view('admin.expansion.view', compact('expansions'));
    }

    /**
     * Overriden from trait
     * @return string The path to the directory where we should upload the files to.
     */
    protected function getUploadDirectory(){
        return 'expansions';
    }
}

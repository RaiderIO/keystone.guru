<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DatatablesHandler
{

    /**
     * @var Request
     */
    private $_request;

    /**
     * @var Builder
     */
    private $_builder;

    /**
     * @var array
     */
    private $_columnHandlers;

    /**
     * @var int
     */
    private $_recordsTotal = 0;

    public function __construct(Request $request)
    {
        $this->_request = $request;
    }

    /**
     * @return Request
     */
    function getRequest()
    {
        return $this->_request;
    }

    /**
     * @return Builder
     */
    function getBuilder()
    {
        return $this->_builder;
    }

    /**
     * @param Builder $builder
     * @return $this
     */
    public function setBuilder(Builder $builder)
    {
        $this->_builder = $builder;
        $this->_recordsTotal = $builder->count();

        return $this;
    }

    /**
     * @param DatatablesColumnHandler|array $dtColumnHandlers
     * @return $this
     */
    public function addColumnHandler($dtColumnHandlers = [])
    {
        if (!is_array($dtColumnHandlers)) {
            $dtColumnHandlers = [$dtColumnHandlers];
        }

        foreach ($dtColumnHandlers as $handler) {
            $this->_columnHandlers[] = $handler;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function applyRequestToBuilder()
    {
        $this->_builder->offset((int)$this->_request->get('start'));
        $this->_builder->limit((int)$this->_request->get('limit'));

        foreach ($this->_columnHandlers as $columnHandler) {
            $columnHandler->applyToBuilder();
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        $result = [
            'draw' => (int)$this->_request->get('draw'),
            'recordsTotal' => $this->_recordsTotal,
            'recordsFiltered' => $this->_recordsTotal - $this->_builder->count(),
            'data' => $this->_builder->get(),
            'input' => $this->_request->toArray()
        ];

        return $result;
    }
}
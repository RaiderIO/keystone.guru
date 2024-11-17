<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables;

use App\Logic\Datatables\ColumnHandler\DatatablesColumnHandler;
use App\Logic\Datatables\ColumnHandler\SimpleColumnHandler;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class DatatablesHandler
{
    const VALID_COLUMN_NAMES = ['title', 'public_key', 'name', 'email'];

    protected Builder $builder;

    /** @var DatatablesColumnHandler[] */
    protected array $columnHandlers;

    public function __construct(protected Request $request)
    {
        $this->columnHandlers = [];
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * @return $this
     */
    public function setBuilder(Builder $builder): DatatablesHandler
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @param DatatablesColumnHandler|array $dtColumnHandlers
     * @return $this
     */
    public function addColumnHandler($dtColumnHandlers = []): DatatablesHandler
    {
        if (!is_array($dtColumnHandlers)) {
            $dtColumnHandlers = [$dtColumnHandlers];
        }

        foreach ($dtColumnHandlers as $handler) {
            /** @var $handler DatatablesColumnHandler */
            $this->columnHandlers[$handler->getColumnName()] = $handler;
        }

        return $this;
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    public function applyRequestToBuilder(): DatatablesHandler
    {
        // Set limits
        $this->builder->offset((int)$this->request->get('start'));
        $this->builder->limit((int)$this->request->get('length'));

        // For any custom column handlers, handle their wishes inside a single where
        $this->builder->where(function (Builder $subBuilder) {
            foreach ($this->columnHandlers as $columnHandler) {
                $columnHandler->applyToBuilder($subBuilder);
            }

            // Handle default filtering/sorting
            $columns = $this->request->get('columns', []);
            foreach ($columns as $column) {
                $columnName = $column['name'];
                // Only if the column name was set - column name comes from the client, do not trust it!!
                if (!empty($columnName) && in_array($columnName, self::VALID_COLUMN_NAMES)) {
                    // Only if not handled by a custom column handler
                    if (!isset($this->columnHandlers[$columnName])) {
                        // Handle filtering/sorting by this column
                        (new SimpleColumnHandler($this, $columnName))->applyToBuilder($subBuilder);
                    }
                }
            }
        });

        return $this;
    }

    public function getResult(): array
    {
        $isDev = config('app.env') !== 'production';
        if ($isDev) {
            DB::enableQueryLog();
        }

        // Fetch the data
        $data = $this->builder->get();

        $recordsTotal = $this->calculateRecordsTotal();
        $result       = [
            'draw'            => (int)$this->request->get('draw'),
            // Initial amount of records
            'recordsTotal'    => $recordsTotal,
            // The amount of records after filtering
            'data'            => $data,
            // The amount of rows there would have been, if it were not for the limits
            'recordsFiltered' => $this->calculateRecordsFiltered() ?? $recordsTotal,
            // Only show this info in dev instance
            'input'           => $isDev ? $this->request->toArray() : [],
            // Debug sql queries for optimization
            'queries'         => $isDev ? DB::getQueryLog() : [],
        ];

        if ($isDev) {
            DB::disableQueryLog();
        }

        return $result;
    }

    abstract protected function calculateRecordsTotal(): int;

    abstract protected function calculateRecordsFiltered(): ?int;
}

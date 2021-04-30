<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 4-3-2019
 * Time: 16:53
 */

namespace App\Logic\MDT\Exception;


/**
 * @package App\Logic\MDT\Exception
 * @author Wouter
 * @since 05/01/2019
 */
class ImportWarning extends \Exception
{
    private $_category;
    private $_data;

    function __construct($category, $message, $data = [])
    {
        parent::__construct($message);

        $this->_category = $category;
        $this->_data = $data;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->_category;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->_data;
    }

    /**
     * Get the data that is supposed to be echoed to the end user.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'category' => $this->_category,
            'message' => $this->getMessage(),
            'data' => $this->_data
        ];
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 4-3-2019
 * Time: 16:53
 */

namespace App\Logic\MDT\IO;


/**
 * @package App\Logic\MDT
 * @author Wouter
 * @since 05/01/2019
 */
class ImportWarning extends \Exception
{
    private $_category;
    private $_message;
    private $_data;

    function __construct($category, $message, $data = [])
    {
        parent::__construct($message);

        $this->_category = $category;
        $this->_message = $message;
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
     * @return mixed
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->_data;
    }
}
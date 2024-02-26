<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 4-3-2019
 * Time: 16:53
 */

namespace App\Logic\MDT\Exception;


use Exception;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @package App\Logic\MDT\Exception
 * @author Wouter
 * @since 05/01/2019
 */
class ImportWarning extends Exception implements Arrayable
{
    function __construct(private readonly string $category, string $message, private readonly array $data = [])
    {
        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the data that is supposed to be echoed to the end user.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'message'  => $this->getMessage(),
            'data'     => $this->data,
        ];
    }
}

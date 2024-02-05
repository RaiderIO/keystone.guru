<?php


namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait GeneratesPublicKey
 * @package App\Models\Traits
 *
 * @mixin Model
 */
trait GeneratesPublicKey
{
    /**
     * @param int    $length
     * @param string $column
     * @param bool   $checkUsages
     * @return string Generates a random public key that is displayed to the user in the URL.
     */
    public static function generateRandomPublicKey(int $length = 7, string $column = 'public_key', bool $checkUsages = true): string
    {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        do {
            $newKey = '';
            for ($i = 0; $i < $length; $i++) {
                $newKey .= $characters[rand(0, $charactersLength - 1)];
            }
        } while ($checkUsages && static::where($column, $newKey)->count() > 0);

        return $newKey;
    }
}

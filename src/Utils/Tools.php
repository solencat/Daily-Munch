<?php

namespace App\Utils;

class Tools
{
    public static function sanitize(string $str): string
    {
        $str = trim($str);
        $str = strip_tags($str);
        $str = htmlspecialchars($str, ENT_NOQUOTES);
        return $str;
    }

    public static function sanitizeArray(array &$data): void
    {
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $data[$key] = self::sanitize($value);
            }
        }
    }
}

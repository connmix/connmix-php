<?php

namespace Connmix;

class AutoIncrement
{

    /**
     * @var int
     */
    protected static $id = 0;

    /**
     * @return int
     */
    public static function id(): int
    {
        if (static::$id == PHP_INT_MAX) {
            static::$id = 0;
        }
        return static::$id++;
    }

}

<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class Cache extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'cache';
    }
}

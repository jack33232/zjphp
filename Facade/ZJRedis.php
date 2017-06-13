<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class ZJRedis extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'redis';
    }
}

<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class Router extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'router';
    }
}

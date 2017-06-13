<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class Session extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'session';
    }
}

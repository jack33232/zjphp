<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class Security extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'security';
    }
}

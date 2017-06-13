<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class Database extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'db';
    }
}

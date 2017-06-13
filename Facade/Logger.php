<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class Logger extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'logger';
    }
}

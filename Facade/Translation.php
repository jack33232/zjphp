<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class Translation extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'translation';
    }
}

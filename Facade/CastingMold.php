<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class CastingMold extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'cast';
    }
}

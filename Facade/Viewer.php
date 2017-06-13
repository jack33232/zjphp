<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class Viewer extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'viewer';
    }
}

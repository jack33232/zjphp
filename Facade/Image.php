<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class Image extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'image';
    }
}

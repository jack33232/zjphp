<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class MQConsumer extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'mqConsumer';
    }
}

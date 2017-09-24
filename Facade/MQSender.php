<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class MQSender extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'mqSender';
    }
}

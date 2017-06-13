<?php
namespace ZJPHP\Facade;

use ZJPHP\Base\Facade;

class HttpClient extends Facade
{
    /**
     * @inheritDoc
     */
    public static function getFacadeComponentId()
    {
        return 'httpClient';
    }
}

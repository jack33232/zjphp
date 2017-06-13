<?php
namespace ZJPHP\Base\Exception;

use Exception;

class InvalidConfigException extends Exception
{
    public function getName()
    {
        return 'Invalid Configuration';
    }
}

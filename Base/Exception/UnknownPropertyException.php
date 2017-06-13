<?php
namespace ZJPHP\Base\Exception;

use Exception;

class UnknownPropertyException extends Exception
{
    public function getName()
    {
        return 'Unknown Property';
    }
}

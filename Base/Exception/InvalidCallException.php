<?php
namespace ZJPHP\Base\Exception;

class InvalidCallException extends \BadMethodCallException
{
    public function getName()
    {
        return 'Invalid Call';
    }
}

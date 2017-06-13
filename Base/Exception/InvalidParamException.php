<?php
namespace ZJPHP\Base\Exception;

class InvalidParamException extends \BadMethodCallException
{
    public function getName()
    {
        return 'Invalid Parameter';
    }
}

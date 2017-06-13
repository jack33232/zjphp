<?php
namespace ZJPHP\Base\Exception;

class UnknownMethodException extends \BadMethodCallException
{
    public function getName()
    {
        return 'Unknown Method';
    }
}

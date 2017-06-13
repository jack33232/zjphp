<?php
namespace ZJPHP\Base\Exception;

use Exception;
use ZJPHP\Base\ZJPHP;

class DatabaseErrorException extends Exception
{
    public function __construct($message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if (!is_null($previous)) {
            $this->logPrevioursException($previous);
        }
    }

    public function logPrevioursException($exception)
    {
        $logger = ZJPHP::$app->get('logger');
        $exception_detail = [
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
        $logger->error($exception->getMessage(), $exception_detail);
    }

    public function getName()
    {
        return 'Database Error';
    }
}

<?php
namespace ZJPHP\Service;

use ZJPHP\Base\Component;
use ZJPHP\Base\ZJPHP;
use Katzgrau\KLogger\Logger as Klogger;
use Psr\Log\LogLevel as LogLevel;
use ZJPHP\Base\Exception\InvalidConfigException;

class Logger extends Component
{
    private $_logPath = '/var/log';
    private $_logLevel = 'debug';
    private $_logOptions = [];

    protected $logger;

    public function init()
    {
        parent::init();
        $this->logger = new Klogger($this->_logPath, $this->_logLevel, $this->_logOptions);
    }

    public function setLogPath($path)
    {
        if (is_writable($path)) {
            $this->_logPath = $path;
        } elseif (@mkdir($path, 0755, true)) {
            $this->_logPath = $path;
        } else {
            throw new InvalidConfigException('Log Path is not writable');
        }
    }

    public function setLogLevel($logLevel)
    {
        $logLevelReflection = new \ReflectionClass('Psr\Log\LogLevel');
        $allLogLevels = $logLevelReflection->getConstants();
        if (!in_array($logLevel, $allLogLevels)) {
            $logLevel = LogLevel::DEBUG;
        }
        $this->_logLevel = $logLevel;
    }

    public function setLogOptions(array $options)
    {
        $this->_logOptions = $options;
    }

    public function __call($name, $params)
    {
        if (method_exists($this->logger, $name)) {
            return call_user_func_array([$this->logger, $name], $params);
        }
        return parent::__call($name, $params);
    }
}

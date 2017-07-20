<?php
namespace ZJPHP\Service\Behavior\Debugger;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Service\Debugger;
use ZJPHP\Base\Kit\ArrayHelper;
use PhpConsole\Connector as PHPConsoleConnector;
use PhpConsole\Handler as PHPConsoleHandler;
use PhpConsole\Helper as PHPConsoleHelper;
use PhpConsole\Storage\File as PHPConsoleStorageFile;

class ErrorConsole extends Behavior
{
    private $_phpConsoleConfig = [
        'stripBasePath' => false,
        'postponeStorageLocation' => false,
        'password' => '',
        'sslOnly' => false,
        'enableGlobal' => false,
        'handleErrors' => false,
        'handleExceptions' => false,
        'callOldHandlers' => false
    ];

    private $_connector;

    public function events()
    {
        return [
            Debugger::EVENT_DEBUGGER_BOOTUP => 'onDebuggerBootup'
        ];
    }

    public function onDebuggerBootup($e)
    {
        if (is_string($this->_phpConsoleConfig['postponeStorageLocation']) && is_writable($this->_phpConsoleConfig['postponeStorageLocation'])) {
            $storageLocation = $this->_phpConsoleConfig['postponeStorageLocation'];
            PHPConsoleConnector::setPostponeStorage(new PHPConsoleStorageFile($storageLocation . '/pc.data'));
        }
        $this->_connector = $connector = PHPConsoleConnector::getInstance();
        if ($this->_phpConsoleConfig['stripBasePath'] === true) {
            $connector->setSourcesBasePath(SCRIPT_DIR);
        }
        if ($this->_phpConsoleConfig['sslOnly'] === true) {
            $connector->enableSslOnlyMode();
        }
        if (!empty($this->_phpConsoleConfig['password'])) {
            $connector->setPassword($this->_phpConsoleConfig['password'], true);
        }
        if ($this->_phpConsoleConfig['enableGlobal'] === true) {
            PHPConsoleHelper::register();
        }
        
        $handler = PHPConsoleHandler::getInstance();
        $handler->setErrorsHandlerLevel(error_reporting());
        $handler->setHandleErrors($this->_phpConsoleConfig['handleErrors']);
        $handler->setHandleExceptions($this->_phpConsoleConfig['handleExceptions']);
        $handler->setCallOldHandlers($this->_phpConsoleConfig['callOldHandlers']);

        $handler->start();
    }

    public function getPHPConsoleConnector()
    {
        return $this->_connector;
    }

    public function setPHPConsole($phpConsoleConfig)
    {
        $phpConsoleConfig = ArrayHelper::merge($this->_phpConsoleConfig, $phpConsoleConfig);

        $this->_phpConsoleConfig = $phpConsoleConfig;
    }
}

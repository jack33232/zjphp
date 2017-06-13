<?php
namespace ZJPHP\Base;

use ZJPHP\DI\Container;
use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\Kit\ArrayHelper;

defined('ZJPHP_DIR') or define('ZJPHP_DIR', dirname(__DIR__));
defined('WEB_ROOT_DIR') or define('WEB_ROOT_DIR', getcwd());
defined('SCRIPT_DIR') or define('SCRIPT_DIR', dirname(\ZJPHP_DIR));
defined('RUNTIME_ENV') or define('RUNTIME_ENV', 'production');
defined('RUNTIME_DIR') or define('RUNTIME_DIR', dirname(\SCRIPT_DIR) . '/runtime');
defined('UPLOAD_DIR') or define('UPLOAD_DIR', dirname(\SCRIPT_DIR) . '/upload');

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) !== 'cgi') {
    defined('ROOT_URL')
        or define(
            'ROOT_URL',
            (empty($_SERVER['HTTPS']) ? 'http://' : 'https://')
                . $_SERVER['SERVER_NAME']
        );

    defined('CURRENT_URL')
        or define(
            'CURRENT_URL',
            (empty($_SERVER['HTTPS']) ? 'http://' : 'https://')
            . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']
        );

    defined('BASE_URL')
        or define(
            'BASE_URL',
            (empty($_SERVER['HTTPS']) ? 'http://' : 'https://')
                . $_SERVER['SERVER_NAME']
                . (($subDir = dirname($_SERVER['SCRIPT_NAME'])) == '/' ? '' : $subDir)
        );
}

class ZJPHP
{
    public static $container = null;
    public static $app = null;

    //All ZJPHP Class files should obey the PSR-4 autoload rules
    public static function autoload($classname)
    {
        $psr4_file_path_with_extension = strtr($classname, '\\', DIRECTORY_SEPARATOR) . '.php';
        $real_file_path = SCRIPT_DIR . DIRECTORY_SEPARATOR . $psr4_file_path_with_extension;
        if (file_exists($real_file_path)) {
            require_once($real_file_path);
        }
    }

    public static function getVersion()
    {
        return '4.1.0';
    }

    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $property) {
            $object->$name = $property;
        }

        return $object;
    }

    public static function createObject($type, $params = [])
    {
        if (is_string($type)) {
            return static::$container->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::$container->get($class, $params, $type);
        } elseif (is_callable($type)) {
            return call_user_func($type, $params);
        } elseif (is_array($type)) {
            throw new InvalidConfigException('Object Configuration must be an array containing a "class" element.');
        } else {
            throw new InvalidConfigException('Unsupported configuration type: '. gettype($type));
        }
    }

    public static function generateAppConfig(array $config_files)
    {
        $config  = [];
        $tempArray = [];
        $apcu_enabled = function_exists('apcu_fetch');

        if ($apcu_enabled) {
            $config_mtime = static::getAppConfigMtime($config_files);
            $config_from_cache = apcu_fetch('array:' . RUNTIME_ENV . '_config');
            if ($config_from_cache !== false && $config_from_cache['configMtime'] === $config_mtime) {
                return $config_from_cache;
            }
        }

        foreach ($config_files as $configuration) {
            $configurationSet = [];
            $runtimeConfigurationSet = [];
            if (file_exists($configuration)) {
                $configurationInfo = pathinfo($configuration, PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_FILENAME | PATHINFO_EXTENSION);
                if (strtolower($configurationInfo['extension']) !== 'php') {
                    continue;
                }
                $configurationSet = require($configuration);

                if (RUNTIME_ENV !== 'production') {
                    $runtimeConfiguration = str_replace($configurationInfo['filename'], $configurationInfo['filename'] . '-' . RUNTIME_ENV, $configuration);
                    if (file_exists($runtimeConfiguration)) {
                        $runtimeConfigurationSet = require($runtimeConfiguration);
                    }
                }

                $tempArray[$configurationInfo['filename']] = ArrayHelper::merge($configurationSet, $runtimeConfigurationSet);
            }
        }

        if (!isset($tempArray['main'])) {
            throw new InvalidConfigException('Miss the main configuration.');
        }

        $config = $tempArray['main'];
        unset($tempArray['main']);
        foreach ($tempArray as $key => $configurationSet) {
                $config[$key] = $configurationSet;
        }

        if ($apcu_enabled) {
            apcu_store('int:' . RUNTIME_ENV . '_config_mtime', $config_mtime);
        }

        return $config;
    }

    public static function getAppConfigMtime(array $config_files)
    {
        $mtime = null;
        foreach ($config_files as $file) {
            $temp_time = filemtime($file);
            if ($temp_time > $mtime) {
                $mtime = $temp_time;
            }
        }

        return $mtime;
    }
}

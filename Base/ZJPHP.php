<?php
namespace ZJPHP\Base;

use ZJPHP\DI\Container;
use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\Exception\InvalidParamException;
use ZJPHP\Base\Kit\ArrayHelper;

defined('ZJPHP_DIR') or define('ZJPHP_DIR', dirname(__DIR__));
defined('WEB_ROOT_DIR') or define('WEB_ROOT_DIR', getcwd());
defined('SCRIPT_DIR') or define('SCRIPT_DIR', dirname(\ZJPHP_DIR));
defined('RUNTIME_ENV') or define('RUNTIME_ENV', 'production');
defined('RUNTIME_DIR') or define('RUNTIME_DIR', dirname(\SCRIPT_DIR) . '/runtime');
defined('UPLOAD_DIR') or define('UPLOAD_DIR', dirname(\SCRIPT_DIR) . '/upload');

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) !== 'cli') {
    $is_secure = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $is_secure = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
        $is_secure = true;
    }
    define('SCHEME', $is_secure ? 'https://' : 'http://');
    defined('ROOT_URL')
        or define(
            'ROOT_URL',
            SCHEME . $_SERVER['SERVER_NAME']
        );

    defined('CURRENT_URL')
        or define(
            'CURRENT_URL',
            SCHEME . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']
        );

    defined('BASE_URL')
        or define(
            'BASE_URL',
            SCHEME . $_SERVER['SERVER_NAME']
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

    public static function toCallable($handler)
    {
        if (is_callable($handler)) {
            return $handler;
        } else if (is_string($handler) && strpos($handler, '@') !== false) {
            list($class, $method) = explode('@', $handler);
            return [self::createObject($class), $method];
        } else {
            throw new InvalidParamException('Parameter not callable.');
        }
    }

    public static function generateAppConfig(array $config_files)
    {
        $config  = [];
        $apcu_enabled = function_exists('apcu_fetch');
        list($config_mtime, $checked_files, $cache_key) = static::checkConfigFiles($config_files);

        if ($apcu_enabled) {
            $cache_exists = false;
            $cached_config = apcu_fetch($cache_key, $cache_exists);
            if ($cache_exists !== false && $cached_config['config_mtime'] === $config_mtime) {
                $cached_config['from_cache'] = true;
                return $cached_config;
            }
        }

        foreach ($checked_files['origin'] as $filename => $file) {
            if ($filename === 'main') {
                $main_config = require($file);
                $config = ArrayHelper::merge($config, $main_config);
            } else {
                $config[$filename] = require($file);
            }
        }

        if (RUNTIME_ENV !== 'production') {
            foreach ($checked_files['runtime'] as $filename => $file) {
                if ($filename === 'main') {
                    $runtime_config = require($file);
                    $config = ArrayHelper::merge($config, $runtime_config);
                } else {
                    $runtime_config = require($file);
                    if (!isset($config[$filename])) {
                        $config[$filename] = [];
                    }
                    $config[$filename] = ArrayHelper::merge($config[$filename], $runtime_config);
                }
            }
        }

        $config['cache_key'] = $cache_key;
        $config['config_mtime'] = $config_mtime;
        $config['from_cache'] = false;

        return $config;
    }

    protected static function checkConfigFiles(array $config_files)
    {
        $config_mtime = null;
        $checked_files = [
            'origin' => [],
            'runtime' => []
        ];

        foreach ($config_files as $file) {
            if (file_exists($file)) {
                // Extract file info
                list($dir, $basename, $ext, $filename) = array_values(pathinfo($file, PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME));

                if (strtolower($ext) !== 'php') {
                    continue;
                }

                $checked_files['origin'][$filename] = $file;

                $file_mtime = filemtime($file);
                if ($file_mtime > $config_mtime) {
                    $config_mtime = $file_mtime;
                }

                if (RUNTIME_ENV !== 'production') {
                    $runtime_file = str_replace($filename, $filename . '-' . RUNTIME_ENV, $file);
                    if (file_exists($runtime_file)) {
                        $checked_files['runtime'][$filename] = $runtime_file;
                        $runtime_file_mtime = filemtime($runtime_file);
                        if ($runtime_file_mtime > $config_mtime) {
                            $config_mtime = $runtime_file_mtime;
                        }
                    }
                }
            }
        }

        if (file_exists($secret_file = SCRIPT_DIR . '/config/secret.php')) {
            $secret_file_mtime = filemtime($secret_file);
            if ($secret_file_mtime > $config_mtime) {
                $config_mtime = $secret_file_mtime;
            }
        }

        $cache_key = 'array:' . md5(implode(';', $checked_files['origin'])) . ':' . RUNTIME_ENV . '_config';
        return [
            $config_mtime,
            $checked_files,
            $cache_key
        ];
    }
}

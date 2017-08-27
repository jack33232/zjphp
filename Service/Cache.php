<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use phpFastCache\CacheManager;
use ZJPHP\Base\Kit\ArrayHelper;

class Cache extends Component
{
    private $_driverConfig = [];
    private $_cacheEngineList = [];

    private $_defaultEngine;
    private $_defaultDriver = 'files';

    private $_isDisabled = false;

    public function setDefaultDriver($driver)
    {
        return $this->_defaultDriver = $driver;
    }

    public function setFallbackDriver($driver)
    {
        CacheManager::setDefaultConfig('fallback', $driver);
    }

    public function setDriverConfig($config)
    {
        return $this->_driverConfig = $config;
    }

    public function setDisable($setting)
    {
        $this->_isDisabled = (bool) $setting;
    }

    public function engine($driver, $config = [])
    {
        if (isset($this->_cacheEngineList[$driver]) && empty($config)) {
            return $this->_cacheEngineList[$driver];
        }

        $real_config = (isset($this->_driverConfig[$driver])) ? ArrayHelper::merge($this->_driverConfig[$driver], $config) : $config;
        $engine = CacheManager::getInstance($driver, $real_config);

        if (empty($config)) {
            $this->_cacheEngineList[$driver] = $engine;
        }

        return $engine;
    }

    public function getDefaultEngine()
    {
        if (isset($this->_defaultEngine)) {
            return $this->_defaultEngine;
        } else {
            return $this->_defaultEngine = $this->engine($this->_defaultDriver);
        }
    }

    public function set($key, $value, $ttl = 0, $engine = 'default')
    {
        if ($this->_isDisabled) {
            return false;
        }

        $key = ZJPHP::$app->getAppName() . ':' . $key;

        $engine = ($engine === 'default') ? $this->getDefaultEngine() : $this->engine($engine);

        $cached_item = $engine->getItem($key);
        $is_hit = $cached_item->isHit();
        if (!$is_hit) {
            $cached_item->expiresAfter($ttl);
        }
        $cached_item->set($value);
        $engine->save($cached_item);
    }

    public function get($key, $default = null, $engine = 'default')
    {
        if ($this->_isDisabled) {
            return $default;
        }

        $key = ZJPHP::$app->getAppName() . ':' . $key;

        $engine = ($engine === 'default') ? $this->getDefaultEngine() : $this->engine($engine);

        $cached_item = $engine->getItem($key);
        if ($cached_item->get() === null) {
            return $default;
        } else {
            return $cached_item->get();
        }
    }

    public function has($key, $engine = 'default')
    {
        if ($this->_isDisabled) {
            return false;
        }

        $key = ZJPHP::$app->getAppName() . ':' . $key;

        $engine = ($engine === 'default') ? $this->getDefaultEngine() : $this->engine($engine);
        $cached_item = $engine->getItem($key);

        return $cached_item->isHit();
    }

    public function expire($key, $ttl = 0, $engine = 'default')
    {
        if ($this->_isDisabled) {
            return;
        }

        $key = ZJPHP::$app->getAppName() . ':' . $key;

        $engine = ($engine === 'default') ? $this->getDefaultEngine() : $this->engine($engine);
        $cached_item = $engine->getItem($key);

        $cached_item->expiresAfter($ttl);
        $engine->save($cached_item);
    }

    public function remove($key, $engine = 'default')
    {
        if ($this->_isDisabled) {
            return false;
        }

        $key = ZJPHP::$app->getAppName() . ':' . $key;

        $engine = ($engine === 'default') ? $this->getDefaultEngine() : $this->engine($engine);
        return $engine->deleteItem($key);
    }
}

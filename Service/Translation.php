<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Base\BootstrapInterface;
use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\Kit\StringHelper;
use ZJPHP\Service\Translation\ZJTranslator;

class Translation extends Component implements BootstrapInterface
{
    private $_defaultLocale = 'en-US';
    private $_currentLocale;
    private $_installedLocale;

    private $_loader;
    private $_translator;
    private $_resources;
    private $_cacheDir = null;

    public function init()
    {
        parent::init();
        foreach ($this->_resources as $indx => $resource) {
            foreach ($this->_installedLocale as $locale) {
                $resourceFile = str_replace('{{LOCALE}}', $locale, $resource['dir']) . DIRECTORY_SEPARATOR . $resource['file'];
                if (file_exists($resourceFile)) {
                    $this->_resources[$locale . $indx] = [
                        $resource['format'],
                        $resourceFile,
                        $locale,
                        $resource['domain']
                    ];
                }
            }
            unset($this->_resources[$indx]);
        }
    }

    public function bootstrap()
    {
        $this->setLocale($this->getUserLang());
        $translator = ZJPHP::createObject($this->_translator, [$this->getLocale(), null, $this->_cacheDir, (RUNTIME_ENV === 'dev')]);
        $this->initTranslator($translator);
    }

    public function initTranslator(ZJTranslator $translator)
    {
        $this->_translator = $translator;
        $loader = $this->getLoader();
        $this->_translator->addLoader($loader[0], $loader[1]);
        $this->_translator->setFallbackLocales([$this->_defaultLocale]);
        foreach ($this->_resources as $resource) {
            call_user_func_array([$this->_translator, 'addResource'], $resource);
        }
    }

    public function getUserLang()
    {
        $locale = $this->_defaultLocale;
        $langSet = $this->getInstalledLocale();
        $session = ZJPHP::$app->get('session', false);

        $paramGetLang = filter_input(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
        if ($paramGetLang !== null && in_array($paramGetLang, $langSet)) {
            unset($_GET['lang']);
            $locale = $paramGetLang;
        } elseif (!is_null($session) && $session->has('user_lang') && in_array($session->get('user_lang'), $langSet)) {
            $locale = $session->get('user_lang');
        } elseif (($acceptLang = filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE', FILTER_SANITIZE_STRING)) !== null) {
            $mostMatchPos = 999;
            $finalLang = $locale;
            foreach ($langSet as $langID => $langCode) {
                $langCodeCopy = $langCode;
                if ($langCodeCopy === 'zh-HK') {
                    $langCodeCopy = 'zh-TW';
                }
                $matches = [];
                preg_match("/\b$langCodeCopy\b/i", $acceptLang, $matches, PREG_OFFSET_CAPTURE);
                if (count($matches) > 0 && $matches[0][1] < $mostMatchPos) {
                    $mostMatchPos = $matches[0][1];
                    $finalLang = $langCode;
                }
            }
            $locale = $finalLang;
        }
        if (!is_null($session)) {
            $session->set('user_lang', $locale);
        }
        
        return $locale;
    }

    public function setTranslator($translator)
    {
        $this->_translator = $translator;
    }

    public function getTranslator()
    {
        return $this->_translator;
    }

    public function setDefaultLocale($locale)
    {
        $this->_defaultLocale = $locale;
    }

    public function setLocale($locale)
    {
        if (!in_array($locale, $this->getInstalledLocale())) {
            throw new InvalidConfigException("$locale is not installed.");
        }
        $this->_currentLocale = $locale;
        if (is_object($this->_translator)) {
            $this->_translator->setLocale($locale);
        }
    }

    public function getLocale()
    {
        if (!$this->_currentLocale) {
            return $this->_defaultLocale;
        }
        return $this->_currentLocale;
    }

    public function setInstalledLocale($locales)
    {
        $locales = StringHelper::explode($locales);
        $this->_installedLocale = $locales;
    }

    public function getInstalledLocale()
    {
        return ($this->_installedLocale) ? $this->_installedLocale : [];
    }

    public function setLoader($loader)
    {
        if (is_array($loader) && sizeof($loader) == 2) {
            $loaderName = $loader[0];
            $loaderClass = $loader[1];
        } else {
            $loaderName = $loader;
            $loaderClass = $loader;
        }

        if (!class_exists($loaderClass)) {
            throw new InvalidConfigException("No such translation loader: $loaderClass");
        }
        $this->_loader = [$loaderName, ZJPHP::createObject($loaderClass)];
    }

    public function getLoader()
    {
        if (!$this->_loader) {
            return $this->getDefaultLoader();
        }
        return $this->_loader;
    }

    public function getDefaultLoader()
    {
        $defaultLoaderName = 'file';
        $defaultLoaderClass = 'ZJPHP\\Service\\Translation\\Loader\\PhpFileLoader';
        return [$defaultLoaderName, ZJPHP::createObject($defaultLoaderClass)];
    }

    public function setResources($resources)
    {
        $this->_resources = [];
        foreach ($resources as $resource) {
            $newResource = [
                'dir' => ((isset($resource['dir'])) ? $resource['dir'] : ZJPHP_DIR . '/resource/lang') . '/{{LOCALE}}',
                'file' => $resource['file'],
                'format' => (isset($resource['format'])) ? $resource['format'] : 'file',
                'domain' => (isset($resource['domain'])) ? $resource['domain'] : 'messages'
            ];
            $this->_resources[] = $newResource;
        }
    }

    public function setCacheDir($path)
    {
        $this->_cacheDir = $path;
    }

    public function trans($key, array $parameters = array(), $domain = null, $locale = null)
    {
        $translatedText = $this->_translator->trans($key, $parameters, $domain, $locale);

        return $translatedText;
    }

    public function transChoice($key, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        $translatedText = $this->_translator->transChoice($key, $parameters, $domain, $locale);

        return $translatedText;
    }
}

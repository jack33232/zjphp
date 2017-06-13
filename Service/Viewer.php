<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use Twig_Loader_Filesystem;
use Twig_Environment;
use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Base\Exception\InvalidParamException;
use Illuminate\Pagination\Paginator;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

class Viewer extends Component
{
    private $_tplDir;
    private $_twigConfig;
    private $_twigEnviroment;
    private $_twigLoader;

    public function init()
    {
        parent::init();
        $this->initTwig();
        $this->loadTranslationExtension();
    }

    protected function initTwig()
    {
        $this->_twigLoader = new Twig_Loader_Filesystem();
        foreach ($this->_tplDir as $tplNamespace => $tplDir) {
            if (!is_numeric($tplNamespace)) {
                if ($tplDir[1]) {
                    $this->_twigLoader->prependPath($tplDir[0], $tplNamespace);
                } else {
                    $this->_twigLoader->addPath($tplDir[0], $tplNamespace);
                }
            } else {
                if ($tplDir[1]) {
                    $this->_twigLoader->prependPath($tplDir[0]);
                } else {
                    $this->_twigLoader->addPath($tplDir[0]);
                }
            }
        }

        $this->_twigEnviroment = new Twig_Environment($this->_twigLoader, $this->_twigConfig);
    }

    protected function loadTranslationExtension()
    {
        $translation = ZJPHP::$app->get('translation');
        $translator = $translation->getTranslator();
        $translation_extenstion = new TranslationExtension($translator);
        $this->_twigEnviroment->addExtension($translation_extenstion);
    }

    protected function initPagination()
    {
        $router = ZJPHP::$app->get('router');
        $request = $router->getRequest();
        Paginator::currentPageResolver(function ($pageName) use ($request) {
            return $request->param($pageName, 1);
        });
        Paginator::currentPathResolver(function () use ($request) {
            return $request->pathname();
        });
    }

    public function setTplDir(array $dir)
    {
        foreach ($dir as $indx => &$dirItem) {
            if (is_string($dirItem)) {
                // false means default append not prepend
                $dirItem = [$dirItem, false];
            }
            if (!file_exists($dirItem[0])) {
                unset($dir[$indx]);
            }
        }

        $this->_tplDir = $dir;
    }

    public function getTplDir()
    {
        return $this->_tplDir;
    }

    public function setTwigEnviroment(array $config)
    {
        if (!empty($config['cache'])) {
            if (!file_exists($config['cache'])) {
                mkdir($config['cache'], 0775, true);
            }
            if (!is_writable($config['cache'])) {
                $config['cache'] = false;
            }
        }

        if (RUNTIME_ENV !== 'production' && !isset($config['debug'])) {
            $config['debug'] = true;
        }

        if (RUNTIME_ENV !== 'production' && !isset($config['strict_variables'])) {
            $config['strict_variables'] = true;
        }

        $this->_twigConfig = $config;
    }

    public function render($tpl, $data = [])
    {
        $defaultData = ZJPHP::$app->getDefaultRenderData();

        $data = ArrayHelper::merge($defaultData, $data);

        return $this->_twigEnviroment->render($tpl, $data);
    }

    public function loadTemplate($tpl)
    {
        return $this->_twigEnviroment->loadTemplate($tpl);
    }

    public function addPath()
    {
        if (!isset($this->_twigLoader)) {
            $this->initTwig();
        }

        if (!file_exists($path)) {
            throw new InvalidParamException();
        }

        if (null !== $namespace) {
            $this->_twigLoader()->addPath($path, $namespace);
        } else {
            $this->_twigLoader()->addPath($path);
        }
        return $this;
    }

    public function prependPath($path, $namespace = null)
    {
        if (!isset($this->_twigLoader)) {
            $this->initTwig();
        }

        if (!file_exists($path)) {
            throw new InvalidParamException();
        }

        if (null !== $namespace) {
            $this->_twigLoader()->prependPath($path, $namespace);
        } else {
            $this->_twigLoader()->prependPath($path);
        }
        return $this;
    }
}

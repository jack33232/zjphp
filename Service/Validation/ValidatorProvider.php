<?php
namespace ZJPHP\Service\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Validation\Factory;
use Illuminate\Validation\DatabasePresenceVerifier;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use ZJPHP\Base\ZJPHP;

class ValidatorProvider extends ServiceProvider
{

    /**
     * @var Illuminate\Container\Container
     */
    protected $container;

    public function __construct(Container $container = null)
    {
        if ($container) {
            $this->container = $container;
        } else {
            $this->container = new Container;
        }

        $this->register();
    }

    protected function register()
    {
        $this->registerTranslator();
        $this->registerValidationResolverHook();
        $this->registerPresenceVerifier();
    }

    protected function registerTranslator()
    {
        $this->container->bind('translator', function () {
            $translator = ZJPHP::$app->get('translation')->getTranslator();
            $translatorForValidation = clone $translator;
            $translatorForValidation->setDomain('validation');
            return $translatorForValidation;
        }, true);
    }

    /**
     * Register the "ValidatesWhenResolved" container hook.
     *
     * @return void
     */
    protected function registerValidationResolverHook()
    {
        $this->container->afterResolving(function (ValidatesWhenResolved $resolved) {
        
            $resolved->validate();
        });
    }

    protected function registerPresenceVerifier()
    {
        $this->container->singleton('validation.presence', function () {
            $db = ZJPHP::$app->get('db');
            return new DatabasePresenceVerifier($db->getDBManager());
        });
    }

    /**
     * Register the validation factory.
     *
     * @return void
     */
    protected function registerValidationFactory()
    {
        $validator = new Factory($this->container['translator'], $this->container);

        // The validation presence verifier is responsible for determining the existence
        // of values in a given data collection, typically a relational database or
        // other persistent data stores. And it is used to check for uniqueness.
        if (isset($this->container['validation.presence'])) {
            $validator->setPresenceVerifier($this->container['validation.presence']);
        }

        return $validator;
    }

    /**
     * @brief getInstance
     *
     * @return validator
     */
    public function getInstance()
    {
        return $this->registerValidationFactory();
    }
}

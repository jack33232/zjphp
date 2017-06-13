<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Service\Validation\ValidatorProvider;

class Validation extends Component
{
    private $_provider;

    public function init()
    {
        if (is_null($this->_provider)) {
            $this->_provider = new ValidatorProvider();
        }
    }

    public function __call($name, $args = [])
    {
        $this->init();
        $provider = $this->_provider->getInstance();
        if (method_exists($provider, $name)) {
            return call_user_func_array([$provider, $name], $args);
        } else {
            return parent::__call($name, $args);
        }
    }
}

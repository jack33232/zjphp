<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Base\Exception\InvalidParamException;
use ZJPHP\Service\Validation\ValidatorProvider;
use Closure;

class Validation extends Component
{
    protected $provider;
    protected $validator;

    public function init()
    {
        $this->provider = new ValidatorProvider();
        $this->validator = $this->provider->getInstance();
    }

    public function make(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        return $this->validator->make($data, $rules, $messages, $customAttributes);
    }

    public function resetExt()
    {
        $this->validator = $this->provider->getInstance();
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function extend($rule, $extension, $message = null)
    {
        if (!$extension instanceof Closure) {
            $extension = $this->callableToClosure($extension);
        }
        $this->validator->extend($rule, $extension, $message);
    }

    public function extendImplicit($rule, $extension, $message = null)
    {
        if (!$extension instanceof Closure) {
            $extension = $this->callableToClosure($extension);
        }
        $this->validator->extendImplicit($rule, $extension, $message);
    }

    public function replacer($rule, $replacer)
    {
        if (!$replacer instanceof Closure) {
            $replacer = $this->callableToClosure($replacer);
        }
        $this->validator->replacer($rule, $replacer);
    }

    protected function callableToClosure($callable)
    {
        $callable = ZJPHP::toCallable($callable);
        $closure_func = function () use ($callable) {
            $args = func_get_args();
            return call_user_func_array($callable, $args);
        };

        return $closure_func;
    }
}

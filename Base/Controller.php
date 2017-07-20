<?php
namespace ZJPHP\Base;

use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Base\Exception\InvalidConfigException;

class Controller extends Component
{
    protected $dependency = []; // All the depencies here are domain dependency and will override system setting
    protected $callbacks = [];

    public function setDependency($setting)
    {
        $this->dependency = ArrayHelper::merge($this->dependency, $setting);

        if (isset($this->dependency['normal'])) {
            foreach ($this->dependency['normal'] as $alias => $definition) {
                ZJPHP::$container->set($alias, $definition);
            }
        }
        if (isset($this->dependency['singleton'])) {
            foreach ($this->dependency['singleton'] as $alias => $definition) {
                ZJPHP::$container->setSingleton($alias, $definition);
            }
        }
    }

    public function setCallbacks($settings)
    {
        $callbacks = [];
        $mask = ['class', 'event', 'handler', 'action', 'data', 'append', 'is_transaction_event'];
        $default = [
            'data' => null,
            'append' => true,
            'is_transaction_event' => false
        ];

        foreach ($settings as $name => $rule) {
            $rule = ArrayHelper::mask($mask, $rule, $default);
            $callbacks[$name] = $rule;
        }

        $this->callbacks = ArrayHelper::merge($this->callbacks, $callbacks);
    }

    public function init()
    {
        parent::init();
        $this->attachBindedEventListeners();
    }

    protected function attachBindedEventListeners()
    {
        foreach ($this->callbacks as $name => $event_binding) {
            $class = $event_binding['class'];
            if (!class_exists($class) && ZJPHP::$container->has($class)) {
                $class = ZJPHP::$container->getDefinitions($class)['class'];
            } elseif (!class_exists($class)) {
                throw new InvalidConfigException('Not found class "' . $class . '".', 500);
            }

            $name = $event_binding['event'];
            if (preg_match("/^EVENT_.+/", $name)) {
                $name = constant($class . '::' . $name);
            }

            $event_binding['handler'] = ZJPHP::createObject($event_binding['handler']);

            Event::on($class, $name, [$event_binding['handler'], $event_binding['action']], $event_binding['data'], $event_binding['append'], $event_binding['is_transaction_event']);
        }
    }
}

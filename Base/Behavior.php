<?php
namespace ZJPHP\Base;

class Behavior extends Object
{
    public $owner;

    public function events()
    {
        return [];
    }

    public function attach(Component $owner)
    {
        $this->owner = $owner;
        foreach ($this->events() as $event => $handler) {
            $owner->on($event, is_string($handler) ? [$this, $handler] : $handler);
        }
    }

    public function detach()
    {
        if ($this->owner !== null) {
            foreach ($this->events() as $event => $handler) {
                $this->owner->off($event, is_string($handler) ? [$this, $handler] : $handler);
            }
            $this->owner = null;
        }
    }
}

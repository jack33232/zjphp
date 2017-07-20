<?php
namespace ZJPHP\Base;

use ZJPHP\Base\Exception\InvalidParamException;
use ZJPHP\Base\DataCollection;
use ZJPHP\Base\Event;

class CascadingEvent extends Event
{
    public static $traceIds = [];
    public $eventType;

    public function __construct($event_type, array $payload = null, array $config = [])
    {
        parent::init();
        $this->eventType = $eventType;
    }

    public function getTraceId()
    {
        $event_type = $this->eventType;
        return isset(static::$traceIds[$event_type]) ? static::$traceIds[$event_type] : null;
    }

    public function hasBegun()
    {
        $event_type = $this->eventType;
        return is_set(static::$traceIds[$event_type]);
    }

    public function beginCascading()
    {
        $event_type = $this->eventType;
        $security = ZJPHP::$app->get('security');
        static::$traceIds[$event_type] = time() . $security->generateRandomString();
    }

    public function endCascading()
    {
        $event_type = $this->eventType;
        unset(static::$traceIds[$event_type]);
    }
}

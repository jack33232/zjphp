<?php
namespace ZJPHP\Service\Event;

use ZJPHP\Base\Event;
use SendEmail;

class QueueEmail extends Event
{
    public $bindingKey;
    public $bodyTpl;
    public $renderData;
    public $body;
    public $sendEmailEvent;
    public $scheduledAt;
}

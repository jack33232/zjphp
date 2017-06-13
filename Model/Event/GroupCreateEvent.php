<?php
namespace ZJPHP\Model\Event;

use ZJPHP\Base\Event;

class GroupCreateEvent extends Event
{
    public $group;
    public $groupInfo;
}

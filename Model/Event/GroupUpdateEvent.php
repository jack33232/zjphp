<?php
namespace ZJPHP\Model\Event;

use ZJPHP\Base\Event;

class GroupUpdateEvent extends Event
{
    public $group;
    public $groupInfo;
}

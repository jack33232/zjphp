<?php
namespace ZJPHP\Service\Behavior\NotifyCenter;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Service\NotifyCenter;
use PC;

class BlastPHPConsole extends Behavior
{
    public function events()
    {
        return [
            NotifyCenter::EVENT_BLAST_PHP_CONSOLE => 'pcDebug'
        ];
    }

    public function pcDebug($event)
    {
        PC::debug($event->variable, $event->tag);
        $event->handled = true;
    }
}

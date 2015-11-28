<?php

namespace PhpTus\Server\Listener;

use PhpTus\Server\Event\AfterEvent;
use PhpTus\Server\Extension\ExtensionInterface;

class AfterListener extends HookAbstract
{
    public function onProcess(AfterEvent $event)
    {
        $this->doProcess(
            $event,
            ExtensionInterface::HOOK_AFTER,
            $event->getMethod(),
            $event->getHeaders(),
            $event->getResponse(),
            $event->getData()
        );
    }
}

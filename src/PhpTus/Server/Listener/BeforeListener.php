<?php

namespace PhpTus\Server\Listener;

use PhpTus\Server\Event\BeforeEvent;
use PhpTus\Server\Extension\ExtensionInterface;

class BeforeListener extends HookAbstract
{
    public function onProcess(BeforeEvent $event)
    {
        $this->doProcess(
            $event,
            ExtensionInterface::HOOK_BEFORE,
            $event->getMethod(),
            $event->getHeaders(),
            $event->getResponse(),
            null
        );
    }
}

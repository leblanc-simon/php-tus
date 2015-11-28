<?php

namespace PhpTus\Server\Listener;

use PhpTus\Server\Extension\ExtensionInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

abstract class HookAbstract
{
    /**
     * @var ExtensionInterface[]
     */
    protected $extensions = [];

    public function __construct(array $extensions)
    {
        foreach ($extensions as $extension) {
            if ($extension instanceof ExtensionInterface) {
                $this->extensions[] = $extension;
            }
        }
    }

    /**
     * @param Event $event
     * @param string $type
     * @param string $method
     * @param HeaderBag $headers
     * @param Response $response
     * @param mixed|null $data
     */
    protected function doProcess(Event $event, $type, $method, HeaderBag $headers, Response $response, $data)
    {
        foreach ($this->extensions as $extension) {
            $support = $extension->hasSupport($method, $headers, $type);

            if (ExtensionInterface::NO_SUPPORT === $support) {
                continue;
            }

            if (
                false === $extension->process($headers, $response, $data)
                &&
                ExtensionInterface::STRICT_SUPPORT === $support
            ) {
                $event->stopPropagation();
                break;
            }
        }
    }
}
<?php

namespace PhpTus\Server\Extension\Checksum;

use PhpTus\Server\Extension\ExtensionInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

class Options extends ChecksumAbstract implements ExtensionInterface
{
    /**
     * @inheritdoc
     */
    public function hasSupport($method, HeaderBag $headers, $type)
    {
        if ('OPTIONS' !== $method || ExtensionInterface::HOOK_BEFORE !== $type) {
            return ExtensionInterface::NO_SUPPORT;
        }

        return ExtensionInterface::SOFT_SUPPORT;
    }

    /**
     * @inheritdoc
     */
    public function process(HeaderBag $headers, Response $response, $data = null)
    {
        $response->headers->add([
            'Tus-Checksum-Algorithm' => implode(',', $this->getAvailableAlgorithms()),
        ]);

        return true;
    }
}
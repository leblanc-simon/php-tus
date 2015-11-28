<?php

namespace PhpTus\Server\Method;

use Symfony\Component\HttpFoundation\Response;

class Options extends MethodAbstract implements MethodInterface
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'OPTIONS';
    }

    public function process($stream = null)
    {
        if ($this->processHookBefore() === false) {
            return $this->getResponse();
        }

        if ($this->processHookAfter() === false) {
            return $this->getResponse();
        }

        return $this->getResponse()->setStatusCode(Response::HTTP_NO_CONTENT);
    }

}
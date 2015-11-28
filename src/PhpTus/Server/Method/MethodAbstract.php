<?php

namespace PhpTus\Server\Method;

use PhpTus\Server\Event\AfterEvent;
use PhpTus\Server\Event\BeforeEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

abstract class MethodAbstract
{
    /**
     * The data send by client
     * @var mixed
     */
    protected $data;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var HeaderBag
     */
    protected $headers;

    /**
     * @var Response
     */
    protected $response;

    /**
     * MethodAbstract constructor.
     * @param HeaderBag $headers
     */
    public function __construct(HeaderBag $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Set the event dispatcher
     *
     * @param EventDispatcherInterface $dispatcher
     * @return $this
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * Return the name of the method
     *
     * @return string the name of the method : must be a valid HTTP method name (GET, POST, ...)
     */
    abstract public function getName();

    /**
     * Get the HTTP request headers
     * @return HeaderBag
     */
    protected function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the HTTP Response
     * @return Response
     */
    protected function getResponse()
    {
        if (null === $this->response) {
            $this->response = new Response();
        }

        return $this->response;
    }

    /**
     * @return bool
     */
    public function processHookBefore()
    {
        if (null === $this->dispatcher) {
            return true;
        }

        $event = new BeforeEvent($this->getName(), $this->getHeaders(), $this->getResponse());
        $this->dispatcher->dispatch('hook.before', $event);

        // If propagation is stopped, the dispatch failed
        return !$event->isPropagationStopped();
    }

    /**
     * @return bool
     */
    public function processHookAfter()
    {
        if (null === $this->dispatcher) {
            return true;
        }

        $event = new AfterEvent($this->getName(), $this->getHeaders(), $this->getResponse(), $this->data);
        $this->dispatcher->dispatch('hook.after', $event);

        // If propagation is stopped, the dispatch failed
        return !$event->isPropagationStopped();
    }
}
<?php

namespace PhpTus\Server\Method;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface MethodInterface : all methods must implement this interface
 *
 * @package PhpTus\Server\Method
 */
interface MethodInterface
{
    /**
     * Return the name of the method
     *
     * @return string the name of the method : must be a valid HTTP method name (GET, POST, ...)
     */
    public function getName();

    /**
     * Process the program
     * @param mixed|null $stream
     * @return Response
     */
    public function process($stream = null);

    /**
     * Set the event dispatcher
     *
     * @param EventDispatcherInterface $dispatcher
     * @return $this
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher);

    /**
     * @return bool True if the process must continue, false else
     */
    public function processHookBefore();

    /**
     * @return bool True if the process must continue, false else
     */
    public function processHookAfter();
}
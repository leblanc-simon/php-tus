<?php

namespace PhpTus\Method;

/**
 * Class Options
 * @package PhpTus\Method
 */
class Options extends MethodAbstract implements MethodInterface
{
    /**
     * Process the request
     *
     * @throws \Exception   if the uuid doesn't exist
     * @return self
     */
    public function process()
    {
        return $this;
    }

    /**
     * Init the UUID of the file
     *
     * @return self
     */
    public function initUuid()
    {
        return $this;
    }
}
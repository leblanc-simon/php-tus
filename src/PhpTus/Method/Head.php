<?php

namespace PhpTus\Method;

/**
 * Class Head
 * @package PhpTus\Method
 */
class Head extends MethodAbstract implements MethodInterface
{
    /**
     * Process the request
     *
     * @throws \Exception   if the uuid doesn't exist
     * @return self
     */
    public function process()
    {
        if ($this->storage->exists($this->uuid) === false) {
            throw new \Exception('The UUID doesn\'t exists');
        }

        $offset = $this->storage->get($this->uuid, 'Offset');

        $this->getResponse()->headers->set('Offset', $offset, true);

        return $this;
    }

    /**
     * Init the UUID of the file
     *
     * @return self
     */
    public function initUuid()
    {
        $this->getUserUuid();
        return $this;
    }
}
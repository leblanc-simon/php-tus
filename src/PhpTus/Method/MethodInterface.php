<?php

namespace PhpTus\Method;

use PhpTus\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Response;

interface MethodInterface
{
    /**
     * @param   string  $directory
     * @param   string  $path
     */
    public function __construct($directory, $path);

    /**
     * Init the UUID of the file
     *
     * @return self
     */
    public function initUuid();

    /**
     * @param StorageInterface $storage
     * @return self
     */
    public function setStorage(StorageInterface $storage);

    /**
     * Process the request
     *
     * @return self
     */
    public function process();

    /**
     * Send the HTTP Response
     *
     * @return Response
     */
    public function send();
}
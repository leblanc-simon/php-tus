<?php

namespace PhpTus\Server\Helper;

use Symfony\Component\HttpFoundation\HeaderBag;

class MethodHelper
{
    const HEADER_METHOD = 'X-HTTP-Method-Override';

    /**
     * @var string The HTTP method name
     */
    private $method;

    /**
     * @var HeaderBag The HTTP request's headers
     */
    private $headers;

    /**
     * Method constructor.
     * @param string $method The HTTP method name
     * @param HeaderBag $headers The HTTP request's headers
     */
    public function __construct($method, HeaderBag $headers)
    {
        $this->method = $method;
        $this->headers = $headers;
    }

    /**
     * Get the real HTTP Method
     *
     * @return string The HTTP method (GET, POST, ...) in uppercase
     */
    public function getMethodName()
    {
        if ($this->headers->has(self::HEADER_METHOD) === false) {
            return strtoupper($this->method);
        }

        return strtoupper($this->headers->get(self::HEADER_METHOD));
    }
}
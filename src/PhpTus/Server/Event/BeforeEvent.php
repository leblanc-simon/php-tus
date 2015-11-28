<?php

namespace PhpTus\Server\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

class BeforeEvent extends Event
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var HeaderBag
     */
    private $headers;

    /**
     * @var Response
     */
    private $response;

    /**
     * BeforeEvent constructor.
     * @param string $method
     * @param HeaderBag $headers
     * @param Response  $response
     */
    public function __construct($method, HeaderBag $headers, Response $response)
    {
        $this->method = $method;
        $this->headers = $headers;
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return HeaderBag
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}

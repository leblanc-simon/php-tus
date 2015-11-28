<?php

namespace PhpTus\Server\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

class AfterEvent extends Event
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
     * @var string
     */
    private $data;

    /**
     * AfterEvent constructor.
     * @param string $method
     * @param HeaderBag $headers
     * @param Response $response
     * @param string $data
     */
    public function __construct($method, HeaderBag $headers, Response $response, $data)
    {
        $this->method = $method;
        $this->headers = $headers;
        $this->response = $response;
        $this->data = $data;
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

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}

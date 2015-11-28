<?php

namespace PhpTus\Server\Helper;

use Symfony\Component\HttpFoundation\Response;

class ResponseHelper
{
    const VERSION = '1.0.0'; // The TUS Protocol Version

    /**
     * @var Response
     */
    private $response;

    /**
     * The list of extension names
     * @var array
     */
    private $extensions;

    /**
     * The max size of the upload or null if no limitation
     * @var null|int
     */
    private $max_size;

    /**
     * ResponseHelper constructor.
     *
     * @param Response $response the current HTTP Response
     * @param array $extensions an array with all available extension names
     * @param null|int $max_size The max size (bytes) of the allowed upload
     */
    public function __construct(Response $response, array $extensions, $max_size = null)
    {
        $this->response = $response;
        $this->extensions = $extensions;
        $this->max_size = $max_size;
    }

    /**
     * Add the common headers in the response
     * @return $this
     */
    public function addCommonHeaders()
    {
        $this->response->headers->add([
            'Tus-Resumable' => self::VERSION,
            'Tus-Version' => self::VERSION, // PhpTus support only one version by server
        ]);

        if (count($this->extensions) > 0) {
            $this->response->headers->add(['Tus-Extension' => implode(',', $this->extensions)]);
        }

        if (null !== $this->max_size) {
            $this->response->headers->add(['Tus-Max-Size' => $this->max_size]);
        }

        return $this;
    }

    /**
     * Send the response to the client
     */
    public function send()
    {
        $this->response->sendHeaders();

        if (empty($this->response->getContent()) === false) {
            $this->response->sendContent();
        }
    }
}
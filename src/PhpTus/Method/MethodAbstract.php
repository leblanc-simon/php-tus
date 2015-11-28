<?php

namespace PhpTus\Method;

use PhpTus\Exception\BadHeader;
use PhpTus\Exception\File;
use PhpTus\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class MethodAbstract
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var StorageInterface
     */
    protected $storage;


    /**
     * @param   string  $directory
     * @param   string  $path
     */
    public function __construct($directory, $path)
    {
        $this->setDirectory($directory);
        $this->setPath($path);
    }


    /**
     * Set the directory where the file will be store
     *
     * @param   string      $directory      The directory where the file are stored
     * @return  self                        The current Server instance
     * @throws  \InvalidArgumentException   If directory isn't string
     * @throws  File                        If directory isn't writable
     * @access  protected
     */
    protected function setDirectory($directory)
    {
        if (is_string($directory) === false) {
            throw new \InvalidArgumentException('Directory must be a string');
        }

        if (is_dir($directory) === false || is_writable($directory) === false) {
            throw new File($directory.' doesn\'t exist or isn\'t writable');
        }

        $this->directory = $directory.(substr($directory, -1) !== DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '');

        return $this;
    }


    /**
     * Set the path to use in the URI
     *
     * @param   string      $path           The path to use in the URI
     * @return  self                        The current Server instance
     * @throws  \InvalidArgumentException   If path isn't string
     * @access  protected
     */
    protected function setPath($path)
    {
        if (is_string($path) === false) {
            throw new \InvalidArgumentException('Path must be a string');
        }

        $this->path = $path;

        return $this;
    }


    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * Send the HTTP Response
     *
     * @return Response
     */
    public function send()
    {
        $this->addCommonHeader();

        return $this->getResponse();
    }


    /**
     * Get the HTTP Request
     *
     * @return Request
     */
    protected function getRequest()
    {
        if (null === $this->request) {
            $this->request = Request::createFromGlobals();
        }

        return $this->request;
    }


    /**
     * Get the HTTP Response
     *
     * @return Response
     */
    protected function getResponse()
    {
        if (null === $this->response) {
            $this->response = new Response(null, 200);
        }

        return $this->response;
    }


    /**
     * Add the commons headers to the HTTP response
     *
     * @access  protected
     */
    protected function addCommonHeader()
    {
        $this->getResponse()->headers->set('Allow', 'OPTIONS,GET,HEAD,POST,PATCH', true);
        $this->getResponse()->headers->set('Access-Control-Allow-Methods', 'OPTIONS,GET,HEAD,POST,PATCH', true);
        $this->getResponse()->headers->set('Access-Control-Allow-Origin', '*', true);
        $this->getResponse()->headers->set('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Final-Length, Offset', true);
        $this->getResponse()->headers->set('Access-Control-Expose-Headers', 'Location, Range, Content-Disposition, Offset', true);
    }


    /**
     * Extract a list of headers in the HTTP headers
     *
     * @param   array       $headers        A list of header name to extract
     * @return  array                       A list if header ([header name => header value])
     * @throws  \InvalidArgumentException   If headers isn't array
     * @throws  BadHeader                   If a header sought doesn't exist or are empty
     * @access  protected
     */
    protected function extractHeaders(array $headers = [])
    {
        if (is_array($headers) === false) {
            throw new \InvalidArgumentException('Headers must be an array');
        }

        $headers_values = array();
        foreach ($headers as $header) {
            $value = $this->getRequest()->headers->get($header);

            if (trim($value) === '') {
                throw new BadHeader($header.' can\'t be empty');
            }

            $headers_values[$header] = $value;
        }

        return $headers_values;
    }


    /**
     * Get the UUID of the request (use for HEAD and PATCH request)
     *
     * @return  string                      The UUID of the request
     * @throws  \InvalidArgumentException   If the UUID doesn't match with the path
     * @access  protected
     */
    protected function getUserUuid()
    {
        if ($this->uuid === null) {
            $uuid = $this->getRequest()->getRequestUri();

            if (strpos($uuid, $this->path) !== 0) {
                throw new \InvalidArgumentException('The uuid and the path doesn\'t match : '.$uuid.' - '.$this->path);
            }

            $this->uuid = $uuid;
        }

        return $this->uuid;
    }


    /**
     * Build a new UUID (use in the POST request)
     *
     * @throws  \DomainException    If the path isn't define
     * @access  protected
     */
    protected function buildUuid()
    {
        if ($this->path === null) {
            throw new \DomainException('Path can\'t be null when call '.__METHOD__);
        }

        $this->uuid = $this->path.hash('sha256', uniqid(mt_rand().php_uname(), true));
    }


    /**
     * Get the filename to use when save the uploaded file
     *
     * @return  string              The filename to use
     * @throws  \DomainException    If the path isn't define
     * @throws  \DomainException    If the uuid isn't define
     * @access  private
     */
    protected function getFilename()
    {
        if ($this->path === null) {
            throw new \DomainException('Path can\'t be null when call '.__METHOD__);
        }

        if ($this->uuid === null) {
            throw new \DomainException('Uuid can\'t be null when call '.__METHOD__);
        }

        return str_replace($this->path, '', $this->uuid);
    }
}
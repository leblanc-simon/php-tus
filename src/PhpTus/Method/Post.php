<?php

namespace PhpTus\Method;

use PhpTus\Exception\BadHeader as BadHeaderException;
use PhpTus\Exception\File as FileException;
use Symfony\Component\HttpFoundation\Response;

class Post extends MethodAbstract implements MethodInterface
{
    /**
     * Process the request
     *
     * @return self
     * @throws \Exception           If the uuid is not defined
     * @throws BadHeaderException   If the Final-Length header is invalid
     * @throws FileException
     */
    public function process()
    {
        if ($this->storage->exists($this->uuid) === true) {
            throw new \Exception('The UUID already exists');
        }

        $final_length = $this->getFinalLength();

        $this->initFile();

        $this->storage->set($this->uuid, 'Final-Length', $final_length);
        $this->storage->set($this->uuid, 'Offset', 0);

        return $this;
    }


    /**
     * Init the UUID of the file
     *
     * @return self
     */
    public function initUuid()
    {
        $this->buildUuid();
        return $this;
    }


    /**
     * @return Response
     */
    public function send()
    {
        $this->getResponse()->setStatusCode(201);
        $this->getResponse()->headers->set(
            'Location',
            $this->getRequest()->getSchemeAndHttpHost().$this->uuid,
            true
        );

        return parent::send();
    }


    /**
     * Get the final length in the request header
     *
     * @return int
     * @throws \PhpTus\Exception\BadHeader
     */
    private function getFinalLength()
    {
        $headers = $this->extractHeaders(array('Final-Length'));

        if (is_numeric($headers['Final-Length']) === false || $headers['Final-Length'] < 0) {
            throw new BadHeaderException('Final-Length must be a positive integer');
        }

        return (int)$headers['Final-Length'];
    }


    /**
     * @throws \PhpTus\Exception\File
     */
    private function initFile()
    {
        $file = $this->directory.$this->getFilename();

        if (file_exists($file) === true) {
            throw new FileException('File already exists : '.$file);
        }

        if (touch($file) === false) {
            throw new FileException('Impossible to touch '.$file);
        }
    }
}
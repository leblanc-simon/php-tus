<?php

namespace PhpTus\Method;

use PhpTus\Exception\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Get
 * @package PhpTus\Method
 */
class Get extends MethodAbstract implements MethodInterface
{
    /**
     * Process the request
     *
     * @throws \Exception   if the uuid doesn't exist
     * @return self
     */
    public function process()
    {
        $file = $this->directory.$this->getFilename();

        if (file_exists($file) === false || is_readable($file) === false) {
            throw new Request('The file '.$this->uuid.' doesn\'t exist', 404);
        }

        $this->response = new Response(null, 200);
        $this->addCommonHeader();

        $this->response->headers->set('Content-Type', 'application/force-download', true);
        $this->response->headers->set('Content-disposition', 'attachment; filename="'.str_replace('"', '', basename($this->uuid)).'"', true);
        $this->response->headers->set('Content-Transfer-Encoding', 'application/octet-stream', true);
        $this->response->headers->set('Pragma', 'no-cache', true);
        $this->response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0, public', true);
        $this->response->headers->set('Expires', '0', true);

        if ($send === true) {
            $this->response->sendHeaders();

            readfile($file);
            exit;
        }

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
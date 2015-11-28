<?php

namespace PhpTus\Server\Extension\Checksum;

use PhpTus\Exception\BadHeader;
use PhpTus\Server\Extension\ExtensionInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

class Patch extends ChecksumAbstract implements ExtensionInterface
{
    /**
     * @inheritdoc
     * @throws BadHeader if Upload-Checksum exists in request headers and has bad format
     * @throws \PhpTus\Exception\Rfc if the server has a misconfiguration with the RFC (sha1 not available)
     */
    public function hasSupport($method, HeaderBag $headers, $type)
    {
        if ('PATCH' !== $method || ExtensionInterface::HOOK_AFTER !== $type) {
            return ExtensionInterface::NO_SUPPORT;
        }

        if (true !== $headers->has('Upload-Checksum')) {
            return ExtensionInterface::NO_SUPPORT;
        }

        // Check if the hash algorithm is supported by the server
        $upload_checksum = $headers->get('Upload-Checksum', '');
        $upload_checksums = explode(' ', $upload_checksum);
        if (count($upload_checksums) !== 2) {
            throw new BadHeader('Upload-Checksum must contains algorithm and hash value', 400);
        }

        $hash = $upload_checksums[0];
        if (false === in_array($hash, $this->getAvailableAlgorithms())) {
            throw new BadHeader(sprintf('%s is not a supported hash algorithm', $hash), 400);
        }

        return ExtensionInterface::STRICT_SUPPORT;
    }

    /**
     * Process the checksum extension for PATCH HTTP Method :
     * Check the hash of sended datas with the hash in the HTTP Header
     *
     * @param HeaderBag $headers The headers of the request
     * @param Response $response The response to send
     * @param mixed $data The datas receive (null for before hook)
     * @return bool true if the processed is OK, false else
     */
    public function process(HeaderBag $headers, Response $response, $data = null)
    {
        // Check if the hash algorithm is supported by the server
        $upload_checksum = $headers->get('Upload-Checksum', '');
        list($algorithm, $hash) = explode(' ', $upload_checksum);

        if (base64_encode(hash($algorithm, $data, true)) !== $hash) {
            $response->setStatusCode(460, 'Checksum Mismatch');
            return false;
        }

        return true;
    }
}
<?php

namespace PhpTus\Server\Extension;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ExtensionInterface : all extensions must implement this interface
 *
 * @package PhpTus\Server\Extension
 */
interface ExtensionInterface
{
    const HOOK_BEFORE = 'before';
    const HOOK_AFTER = 'after';

    const NO_SUPPORT = 'none';
    const STRICT_SUPPORT = 'strict';
    const SOFT_SUPPORT = 'soft';

    /**
     * Validate that extension support method (GET, POST, ...) and type of hook (before, after)
     *
     * @param string $method
     * @param HeaderBag $headers The headers of the request
     * @param string $type
     * @return string the type of support [NO_SUPPORT, STRICT_SUPPORT, SOFT_SUPPORT]
     */
    public function hasSupport($method, HeaderBag $headers, $type);

    /**
     * Process the business code of the extension
     *
     * @param HeaderBag $headers The headers of the request
     * @param Response $response The response to send
     * @param mixed $data The datas receive (null for before hook)
     * @return bool
     */
    public function process(HeaderBag $headers, Response $response, $data = null);
}
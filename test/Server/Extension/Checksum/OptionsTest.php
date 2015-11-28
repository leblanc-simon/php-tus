<?php

namespace Server\Extension\Checksum;

use PhpTus\Server\Extension\Checksum\Options;
use PhpTus\Server\Extension\ExtensionInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportIsOnlyAvailableForOptionsMethodAndBeforeProcess()
    {
        $options = new Options();
        $headers = new HeaderBag();

        $cases = [
            'GET' => ExtensionInterface::NO_SUPPORT,
            'POST' => ExtensionInterface::NO_SUPPORT,
            'HEAD' => ExtensionInterface::NO_SUPPORT,
            'PATCH' => ExtensionInterface::NO_SUPPORT,
            'DELETE' => ExtensionInterface::NO_SUPPORT,
            'OPTIONS' => [
                ExtensionInterface::HOOK_BEFORE => ExtensionInterface::SOFT_SUPPORT,
                ExtensionInterface::HOOK_AFTER => ExtensionInterface::NO_SUPPORT,
            ]
        ];

        foreach ($cases as $method => $support) {
            if (is_string($support) === true) {
                $this->assertEquals(
                    $support,
                    $options->hasSupport($method, $headers, ExtensionInterface::HOOK_BEFORE)
                );
                $this->assertEquals(
                    $support,
                    $options->hasSupport($method, $headers, ExtensionInterface::HOOK_AFTER)
                );
            } else {
                foreach ($support as $hook => $final_support) {
                    $this->assertEquals(
                        $final_support,
                        $options->hasSupport($method, $headers, $hook)
                    );
                }
            }
        }
    }

    public function testProcessingHadTheGoodHeaders()
    {
        $options = new Options();
        $headers = new HeaderBag();
        $response = new Response();

        // Process must return true
        $this->assertTrue($options->process($headers, $response));

        // Response must contains Tus-Checksum-Algorithm
        $this->assertTrue($response->headers->has('Tus-Checksum-Algorithm'));

        // In Tus-Checksum-Algorithm, we must have at least "sha1"
        $this->assertContains('sha1', explode(',', $response->headers->get('Tus-Checksum-Algorithm')));
    }
}

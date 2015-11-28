<?php

namespace Server\Extension\Checksum;

use PhpTus\Server\Extension\Checksum\Patch;
use PhpTus\Server\Extension\ExtensionInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

class PatchTest extends \PHPUnit_Framework_TestCase
{
    public function testNoSupportWithoutClientHeader()
    {
        $patch = new Patch();
        $headers = new HeaderBag();

        foreach (['GET', 'POST', 'HEAD', 'PATCH', 'OPTIONS', 'DELETE'] as $method) {
            $this->assertEquals(
                ExtensionInterface::NO_SUPPORT,
                $patch->hasSupport($method, $headers, ExtensionInterface::HOOK_BEFORE)
            );
            $this->assertEquals(
                ExtensionInterface::NO_SUPPORT,
                $patch->hasSupport($method, $headers, ExtensionInterface::HOOK_AFTER)
            );
        }
    }

    public function testSupportIsOnlyAvailableForPatchMethodAndAfterProcessWithGoodHeader()
    {
        $patch = new Patch();
        $headers = new HeaderBag();
        $headers->add([
            'Upload-Checksum' => 'sha1 '.sha1('test'),
        ]);

        $cases = [
            'GET' => ExtensionInterface::NO_SUPPORT,
            'POST' => ExtensionInterface::NO_SUPPORT,
            'HEAD' => ExtensionInterface::NO_SUPPORT,
            'PATCH' => [
                ExtensionInterface::HOOK_BEFORE => ExtensionInterface::NO_SUPPORT,
                ExtensionInterface::HOOK_AFTER => ExtensionInterface::STRICT_SUPPORT,
            ],
            'DELETE' => ExtensionInterface::NO_SUPPORT,
            'OPTIONS' => ExtensionInterface::NO_SUPPORT,
        ];

        foreach ($cases as $method => $support) {
            if (is_string($support) === true) {
                $this->assertEquals(
                    $support,
                    $patch->hasSupport($method, $headers, ExtensionInterface::HOOK_BEFORE)
                );
                $this->assertEquals(
                    $support,
                    $patch->hasSupport($method, $headers, ExtensionInterface::HOOK_AFTER)
                );
            } else {
                foreach ($support as $hook => $final_support) {
                    $this->assertEquals(
                        $final_support,
                        $patch->hasSupport($method, $headers, $hook)
                    );
                }
            }
        }
    }

    /**
     * @expectedException \PhpTus\Exception\BadHeader
     * @expectedExceptionMessage Upload-Checksum must contains algorithm and hash value
     */
    public function testBadHeaderChecksum()
    {
        $patch = new Patch();
        $headers = new HeaderBag();
        $headers->add([
            'Upload-Checksum' => 'sha1', // Only the algorithm without the hash
        ]);

        $patch->hasSupport('PATCH', $headers, ExtensionInterface::HOOK_AFTER);
    }

    /**
     * @expectedException \PhpTus\Exception\BadHeader
     * @expectedExceptionMessage ## is not a supported hash algorithm
     */
    public function testBadHashRequired()
    {
        $patch = new Patch();
        $headers = new HeaderBag();
        $headers->add([
            'Upload-Checksum' => '## '.sha1('test'), // Bad algorithm
        ]);

        $patch->hasSupport('PATCH', $headers, ExtensionInterface::HOOK_AFTER);
    }

    public function testGoodHashMustReturnTrueAndGoodHttpReturnStatusCode()
    {
        $patch = new Patch();
        $headers = new HeaderBag();
        $headers->add([
            'Upload-Checksum' => 'sha1 '.base64_encode(hash('sha1', 'test', true)),
        ]);
        $response = new Response();

        $this->assertTrue($patch->process($headers, $response, 'test'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBadHashMustReturnFalseAndGoodHttpReturnStatusCode()
    {
        $patch = new Patch();
        $headers = new HeaderBag();
        $headers->add([
            'Upload-Checksum' => 'sha1 '.base64_encode(hash('sha1', 'bad-content', true)),
        ]);
        $response = new Response();

        $this->assertFalse($patch->process($headers, $response, 'test'));
        $this->assertEquals(460, $response->getStatusCode());
    }
}

<?php

namespace SilverStripe\Versioned\Tests;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\VersionedHTTPMiddleware;

class VersionedHTTPMiddlewareTest extends SapphireTest
{

    protected $usesDatabase = true;

    protected function setUp()
    {
        parent::setUp();
        Security::force_database_is_ready(true);
    }

    protected function tearDown()
    {
        Security::clear_database_is_ready();
        parent::tearDown();
    }

    public function testDoesNotRewriteLiveLinks()
    {
        $adminId = $this->logInWithPermission('ADMIN');
        $body = $this->getBody();

        $request = (new HTTPRequest('GET', '/'))
            ->addHeader('Host', 'silverstripe.org')
            ->setSession(new Session(['loggedInAs' => $adminId]));

        $middleware = new VersionedHTTPMiddleware();
        $response = $middleware->process(
            $request,
            function ($request) use ($body) {
                return new HTTPResponse($body, '200');
            }
        );
        $output = $this->getResponseBodyOutput($response);
        $this->assertContains(
            '<a href="https://silverstripe.org/some-path?some=var">Absolute onsite link</a>',
            $output
        );
        $this->assertContains(
            '<a href="/some-path?some=var">Relative onsite link</a>',
            $output
        );
        $this->assertContains(
            '<a href="https://google.com">Absolute offsite link</a>',
            $output
        );
    }

    public function testRewritesStageLinks()
    {
        $body = $this->getBody();
        $testFn = function (HTTPRequest $request) use ($body) {
          $middleware = new VersionedHTTPMiddleware();
            $response = $middleware->process(
                $request,
                function ($request) use ($body) {
                    return new HTTPResponse($body, '200');
                }
            );
            $output = $this->getResponseBodyOutput($response);
            $this->assertContains(
                '<a href="https://silverstripe.org/some-path?some=var&stage=Stage">Absolute onsite link</a>',
                $output
            );
            $this->assertContains(
                '<a href="/some-path?some=var&stage=Stage">Relative onsite link</a>',
                $output
            );
            $this->assertContains(
                '<a href="https://google.com">Absolute offsite link</a>',
                $output
            );
        };
        Director::mockRequest(
            $testFn,
            'http://silverstripe.org/?stage=Stage', // url
            [], // post
            ['loggedInAs' => $this->logInWithPermission('ADMIN')], // session
            'GET'
        );
    }

    protected function getBody()
    {
        return <<<HTML
<html>
<body>
<a href="https://silverstripe.org/some-path?some=var">Absolute onsite link</a>
<a href="/some-path?some=var">Relative onsite link</a>
<a href="https://google.com">Absolute offsite link</a>
</body>
</html>
HTML;
    }

    protected function getResponseBodyOutput(HTTPResponse $response)
    {
        ob_start();
        echo $response->output();
        $body = ob_get_contents();
        ob_end_clean();

        return $body;
    }

}

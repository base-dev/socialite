<?php

namespace Tests;

use Mockery as m;
use League\OAuth1\Client;
use Illuminate\Http\Request;
use Laravel\Socialite\One\User;
use PHPUnit_Framework_TestCase;
use Tests\Fixtures\OAuthOneTestProviderStub;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OAuthOneTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRedirectGeneratesTheProperSymfonyRedirectResponse()
    {
        $server = m::mock(Client\Server\Twitter::class);
        $server->shouldReceive('getTemporaryCredentials')->once()->andReturn('temp');
        $server->shouldReceive('getAuthorizationUrl')->once()->with('temp')->andReturn('http://auth.url');
        $request = Request::create('foo');
        $request->setSession($session = m::mock(SessionInterface::class));
        $session->shouldReceive('set')->once()->with('oauth.temp', 'temp');

        $provider = new OAuthOneTestProviderStub($request, $server);
        $response = $provider->redirect();

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testUserReturnsAUserInstanceForTheAuthenticatedRequest()
    {
        $server = m::mock(Client\Server\Twitter::class);
        $temp = m::mock(Client\Credentials\TemporaryCredentials::class);
        $server->shouldReceive('getTokenCredentials')->once()->with($temp, 'oauth_token', 'oauth_verifier')->andReturn(
            $token = m::mock(Client\Credentials\TokenCredentials::class)
        );
        $server->shouldReceive('getUserDetails')->once()->with($token)->andReturn($user = m::mock(Client\Server\User::class));
        $token->shouldReceive('getIdentifier')->once()->andReturn('identifier');
        $token->shouldReceive('getSecret')->once()->andReturn('secret');
        $user->uid = 'uid';
        $user->email = 'foo@bar.com';
        $user->extra = ['extra' => 'extra'];
        $request = Request::create('foo', 'GET', ['oauth_token' => 'oauth_token', 'oauth_verifier' => 'oauth_verifier']);
        $request->setSession($session = m::mock(SessionInterface::class));
        $session->shouldReceive('get')->once()->with('oauth.temp')->andReturn($temp);

        $provider = new OAuthOneTestProviderStub($request, $server);
        $user = $provider->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('uid', $user->id);
        $this->assertSame('foo@bar.com', $user->email);
        $this->assertSame(['extra' => 'extra'], $user->user);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionIsThrownWhenVerifierIsMissing()
    {
        $server = m::mock(Client\Server\Twitter::class);
        $request = Request::create('foo');
        $request->setSession($session = m::mock(SessionInterface::class));

        $provider = new OAuthOneTestProviderStub($request, $server);
        $user = $provider->user();
    }
}

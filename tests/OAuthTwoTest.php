<?php

namespace Tests;

use GuzzleHttp\ClientInterface;
use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tests\Fixtures\FacebookTestProviderStub;
use Tests\Fixtures\OAuthTwoTestProviderStub;

use Laravel\Socialite\Two\User;

class OAuthTwoTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRedirectGeneratesTheProperSymfonyRedirectResponse()
    {
        $request = Request::create('foo');
        $request->setSession($session = m::mock(SessionInterface::class));
        $session->shouldReceive('set')->once();
        $provider = new OAuthTwoTestProviderStub($request, 'client_id', 'client_secret', 'redirect');
        $response = $provider->redirect();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://auth.url', $response->getTargetUrl());
    }

    public function testUserReturnsAUserInstanceForTheAuthenticatedRequest()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('A', 40), 'code' => 'code']);
        $request->setSession($session = m::mock(SessionInterface::class));
        $session->shouldReceive('pull')->once()->with('state')->andReturn(str_repeat('A', 40));
        $provider = new OAuthTwoTestProviderStub($request, 'client_id', 'client_secret', 'redirect_uri');
        $provider->http = m::mock(StdClass::class);
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $provider->http->shouldReceive('post')->once()->with('http://token.url', [
            'headers' => ['Accept' => 'application/json'], $postKey => ['client_id' => 'client_id', 'client_secret' => 'client_secret', 'code' => 'code', 'redirect_uri' => 'redirect_uri'],
        ])->andReturn($response = m::mock(StdClass::class));
        $response->shouldReceive('getBody')->once()->andReturn('{ "access_token" : "access_token", "refresh_token" : "refresh_token", "expires_in" : 3600 }');
        $user = $provider->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('foo', $user->id);
        $this->assertSame('access_token', $user->token);
        $this->assertSame('refresh_token', $user->refreshToken);
        $this->assertSame(3600, $user->expiresIn);
    }

    public function testUserReturnsAUserInstanceForTheAuthenticatedFacebookRequest()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('A', 40), 'code' => 'code']);
        $request->setSession($session = m::mock(SessionInterface::class));
        $session->shouldReceive('pull')->once()->with('state')->andReturn(str_repeat('A', 40));
        $provider = new FacebookTestProviderStub($request, 'client_id', 'client_secret', 'redirect_uri');
        $provider->http = m::mock(StdClass::class);
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $provider->http->shouldReceive('post')->once()->with('https://graph.facebook.com/oauth/access_token', [
            $postKey => ['client_id' => 'client_id', 'client_secret' => 'client_secret', 'code' => 'code', 'redirect_uri' => 'redirect_uri'],
        ])->andReturn($response = m::mock(StdClass::class));
        $response->shouldReceive('getBody')->once()->andReturn('access_token=access_token&expires=5183085');
        $user = $provider->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('foo', $user->id);
        $this->assertSame('access_token', $user->token);
        $this->assertNull($user->refreshToken);
        $this->assertEquals(5183085, $user->expiresIn);
    }

    /**
     * @expectedException \Laravel\Socialite\Two\InvalidStateException
     */
    public function testExceptionIsThrownIfStateIsInvalid()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('B', 40), 'code' => 'code']);
        $request->setSession($session = m::mock(SessionInterface::class));
        $session->shouldReceive('pull')->once()->with('state')->andReturn(str_repeat('A', 40));
        $provider = new OAuthTwoTestProviderStub($request, 'client_id', 'client_secret', 'redirect');
        $user = $provider->user();
    }

    /**
     * @expectedException \Laravel\Socialite\Two\InvalidStateException
     */
    public function testExceptionIsThrownIfStateIsNotSet()
    {
        $request = Request::create('foo', 'GET', ['state' => 'state', 'code' => 'code']);
        $request->setSession($session = m::mock(SessionInterface::class));
        $session->shouldReceive('pull')->once()->with('state');
        $provider = new OAuthTwoTestProviderStub($request, 'client_id', 'client_secret', 'redirect');
        $user = $provider->user();
    }
}

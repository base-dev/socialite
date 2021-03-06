<?php

namespace Laravel\Socialite\One;

use Illuminate\Http\Request;
use InvalidArgumentException;
use League\OAuth1\Client\Server\Server;
use Symfony\Component\HttpFoundation\RedirectResponse;

use \Laravel\Socialite\Contracts\ProviderInterface;

abstract class AbstractProvider implements ProviderInterface
{
    /**
     * The HTTP request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The OAuth server implementation.
     *
     * @var \League\OAuth1\Client\Server\Server
     */
    protected $server;

    /**
     * Create a new provider instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \League\OAuth1\Client\Server\Server  $server
     * @return void
     */
    public function __construct(Request $request, Server $server)
    {
        $this->server = $server;
        $this->request = $request;
    }

    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */

    public function redirect()
    {
        $temp_credentials = $this->server->getTemporaryCredentials();

        $this->request->session()->set(
            'oauth.temp',
            $temp_credentials
        );

        return new RedirectResponse(
            $this->server->getAuthorizationUrl($temp_credentials));
    }

    /**
     * Get the User instance for the authenticated user.
     *
     * @throws \InvalidArgumentException
     * @return \Laravel\Socialite\One\User
     */
    public function user()
    {
        if (! $this->hasNecessaryVerifier()) {
            throw new InvalidArgumentException('Invalid request. Missing OAuth verifier.');
        }

        $token = $this->getToken();
        $user = $this->server->getUserDetails($token);

        // TODO(nicolai): This code is broken. $user->extra isn't a thing.
        $instance = (new User)->setRaw($user->extra)
                ->setToken($token->getIdentifier(), $token->getSecret());

        return $instance->map([
            'id'       => $user->uid,
            'nickname' => $user->nickname,
            'name'     => $user->name,
            'email'    => $user->email,
            'avatar'   => $user->imageUrl,
        ]);
    }

    /**
     * Get the token credentials for the request.
     *
     * @return \League\OAuth1\Client\Credentials\TokenCredentials
     */
    protected function getToken()
    {
        $temp = $this->request->session()->get('oauth.temp');

        return $this->server->getTokenCredentials(
            $temp,
            $this->request->get('oauth_token'),
            $this->request->get('oauth_verifier')
        );
    }

    /**
     * Determine if the request has the necessary OAuth verifier.
     *
     * @return bool
     */
    protected function hasNecessaryVerifier()
    {
        return $this->request->has('oauth_token')
            && $this->request->has('oauth_verifier');
    }

    /**
     * Set the request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}

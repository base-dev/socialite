<?php

namespace Laravel\Socialite\Two;

use Illuminate\Support\Arr;

use Laravel\Socialite\Contracts\ProviderInterface;

class GoogleProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $authUrl = 'https://accounts.google.com/o/oauth2/auth';

    /**
     * {@inheritdoc}
     */
    protected static $tokenUrl = 'https://accounts.google.com/o/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected static $userUrl = 'https://www.googleapis.com/plus/v1/people/me?';

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'openid',
        'profile',
        'email',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return array_add(parent::getTokenFields($code),
                         'grant_type', 'authorization_code');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()
                         ->get($this->userUrl, [
                             'query' => [
                                 'prettyPrint' => 'false',
                             ],
                             'headers' => [
                                 'Accept' => 'application/json',
                                 'Authorization' => 'Bearer '.$token,
                             ],
                         ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $userEmail = Arr::get($user, 'emails');
        if (!is_null($userEmail)) {
            $user['email'] = $userEmail[0]['value'];
        }

        $userAvatar = Arr::get($user, 'avatar');
        if (!is_null($userAvatar)) {
            $user['avatar'] = $userAvatar['url'];
        }

        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'id'),
            'nickname' => Arr::get($user, 'nickname'),
            'name'     => Arr::get($user, 'displayName'),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'avatar')
        ]);
    }
}

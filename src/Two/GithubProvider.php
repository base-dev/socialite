<?php

namespace Laravel\Socialite\Two;

use Exception;
use Illuminate\Support\Arr;

use Laravel\Socialite\Contracts\ProviderInterface;

class GithubProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * {@inheritdoc}
     */
    protected $authUrl = 'https://github.com/login/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $tokenUrl = 'https://github.com/login/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $userUrl = 'https://api.github.com/user?access_token=';

    /**
     * {@inheritdoc}
     */
    protected $emailsUrl = 'https://api.github.com/user/emails?access_token=';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['user:email'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->authUrl, $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userUrl = $this->userUrl.$token;

        $response = $this->getHttpClient()
                         ->get($userUrl, $this->getRequestOptions());

        $user = json_decode($response->getBody(), true);

        if (in_array('user:email', $this->scopes)) {
            $user['email'] = $this->getEmailByToken($token);
        }

        return $user;
    }

    /**
     * Get the email for the given access token.
     *
     * @param  string  $token
     * @return string|null
     */
    protected function getEmailByToken($token)
    {
        $emailsUrl = $this->emailsUrl.$token;

        try {
            $response = $this->getHttpClient()
                             ->get($emailsUrl, $this->getRequestOptions());
        } catch (Exception $e) {
            return;
        }

        foreach (json_decode($response->getBody(), true) as $email) {
            if ($email['primary'] && $email['verified']) {
                return $email['email'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'id'),
            'nickname' => Arr::get($user,'login'),
            'name'     => Arr::get($user, 'name'),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'avatar_url'),
        ]);
    }

    /**
     * Get the default options for an HTTP request.
     *
     * @return array
     */
    protected function getRequestOptions()
    {
        return [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
            ],
        ];
    }
}

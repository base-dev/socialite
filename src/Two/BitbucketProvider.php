<?php

namespace Laravel\Socialite\Two;

use Exception;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;

use Laravel\Socialite\Contracts\ProviderInterface;

class BitbucketProvider extends AbstractProvider implements ProviderInterface
{

    /**
     * {@inheritdoc}
     */
    protected static $authUrl = 'https://bitbucket.org/site/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected static $tokenUrl = 'https://bitbucket.org/site/oauth2/access_token';

    /**
     * {@inheritdoc}
     */
    protected static $userUrl = 'https://api.bitbucket.org/2.0/user?access_token=';

    /**
     * {@inheritdoc}
     */
    protected static $emailsUrl = 'https://api.bitbucket.org/2.0/user/emails?access_token=';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    public function __construct(Request $request, $clientId, $clientSecret, $redirectUrl) {
// i dont know wat teh fuk im suppose to do now
        parent($request, $clientId, $clientSecret, $redirectUrl);
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
        $userUrl = $this->userUrl . $token;
        $response = $this->getHttpClient()->get($userUrl);
        $user = json_decode($response->getBody(), true);

        if (in_array('email', $this->scopes)) {
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
        $emailsUrl = $this->emailsUrl . $token;

        try {
            $response = $this->getHttpClient()->get($emailsUrl);
        } catch (Exception $e) {
            return;
        }

        $emails = json_decode($response->getBody(), true);
        foreach ($emails['values'] as $email) {
            if ($email['type'] == 'email'
                && $email['is_primary']
                && $email['is_confirmed']) {
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
            'id'       => Arr::get($user, 'uuid'),
            'nickname' => Arr::get($user, 'username'),
            'name'     => Arr::get($user, 'display_name'),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'links.avatar.href'),
        ]);
    }

    /**
     * Get the access token for the given code.
     *
     * @param  string  $code
     * @return string
     */
    public function getAccessToken($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1)
            ? 'form_params'
            : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'auth'    => [$this->clientId, $this->clientSecret],
            'headers' => ['Accept' => 'application/json'],
            $postKey  => $this->getTokenFields($code),
        ]);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'code' => $code, 'redirect_uri' => $this->redirectUrl, 'grant_type' => 'authorization_code',
            'client_id' => $this->clientId, 'client_secret' => $this->clientSecret,
        ];
    }
}
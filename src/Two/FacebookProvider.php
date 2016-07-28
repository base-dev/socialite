<?php

namespace Laravel\Socialite\Two;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;

use Laravel\Socialite\Contracts\ProviderInterface;

class FacebookProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The base Facebook Graph URL.
     *
     * @var string
     */
    protected const GRAPH_URL_BASE = 'https://graph.facebook.com';

    /**
     * The Graph API version for the request.
     *
     * @var string
     */
    protected const GRAPH_VERSION = 'v2.6';

    protected const GRAPH_URL = GRAPH_URL_BASE.'/'.GRAPH_VERSION;

    /**
     * {@inheritdoc}
     */
    protected $authUrl = 'https://www.facebook.com/'.GRAPH_VERSION.'/dialog/oauth';

    /**
     * {@inheritdoc}
     */
    protected $tokenUrl = GRAPH_URL_BASE.'/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $userUrl = GRAPH_URL_BASE.'/me?access_token=';

    /**
     * The user fields being requested.
     *
     * @var array
     */
    protected $fields = ['name', 'email', 'gender', 'verified', 'link'];

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];

    /**
     * Display the dialog in a popup view.
     *
     * @var bool
     */
    protected $popup = false;

    /**
     * Re-request a declined permission.
     *
     * @var bool
     */
    protected $reRequest = false;

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
    public function getAccessTokenResponse($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1)
                   ? 'form_params'
                   : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            $postKey => $this->getTokenFields($code),
        ]);

        $data = [];

        parse_str($response->getBody(), $data);

        return Arr::add($data, 'expires_in', Arr::pull($data, 'expires'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $meUrl = $this->userUrl.$token.'&fields='.implode(',', $this->fields);

        if (!empty($this->clientSecret)) {
            $meUrl .= '&appsecret_proof='.makeAppSecretProof($token);
        }

        $response = $this->getHttpClient()->get($meUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    private function makeAppSecretProof($token) {
        return hash_hmac('sha256', $token, $this->clientSecret);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        // TODO(nicolai): Url
        $avatarUrl = GRAPH_URL.'/'.Arr::get($user, 'id').'/picture';

        return (new User)->setRaw($user)->map([
            'id'         => Arr::get($user, 'id'),
            'nickname'   => null,
            'name'       => Arr::get($user, 'name'),
            'email'      => Arr::get($user, 'email'),
            'profileUrl' => Arr::get($user, 'link'),
            'avatar'     => $avatarUrl.'?type=normal',
            'avatar_original' => $avatarUrl.'?width=1920'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        if ($this->popup) {
            $fields['display'] = 'popup';
        }

        if ($this->reRequest) {
            $fields['auth_type'] = 'rerequest';
        }

        return $fields;
    }

    /**
     * Set the user fields to request from Facebook.
     *
     * @param  array  $fields
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set the dialog to be displayed as a popup.
     *
     * @return $this
     */
    public function asPopup()
    {
        $this->popup = true;

        return $this;
    }

    /**
     * Re-request permissions which were previously declined.
     *
     * @return $this
     */
    public function reRequest()
    {
        $this->reRequest = true;

        return $this;
    }
}

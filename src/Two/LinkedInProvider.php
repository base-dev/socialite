<?php

namespace Laravel\Socialite\Two;

use Illuminate\Support\Arr;

use Laravel\Socialite\Contracts\ProviderInterface;

class LinkedInProvider extends AbstractProvider implements ProviderInterface
{

    /**
     * {@inheritdoc}
     */
    protected $authUrl = 'https://www.linkedin.com/oauth/v2/authorization';

    /**
     * {@inheritdoc}
     */
    protected $tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';

    /**
     * {@inheritdoc}
     */
    protected $userUrl = 'https://api.linkedin.com/v1/people/';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'r_basicprofile',
        'r_emailaddress'
    ];
    
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * The fields that are included in the profile.
     *
     * @var array
     */
    protected $fields = [
        'id', 'first-name', 'last-name', 'formatted-name',
        'email-address', 'headline', 'location', 'industry',
        'public-profile-url', 'picture-url', 'picture-urls::(original)',
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
        return parent::getTokenFields($code)
               + ['grant_type' => 'authorization_code'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $fields = implode(',', $this->fields);

        $url = $this->userUrl.'~:('.$fields.')';

        $response = $this->getHttpClient()->get($url, [
            'headers' => [
                'x-li-format'   => 'json',
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
        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'id'),
            'nickname' => null,
            'name'     => Arr::get($user, 'formattedName'),
            'email'    => Arr::get($user, 'emailAddress'),
            'avatar'   => Arr::get($user, 'pictureUrl'),
            'avatar_original' => Arr::get($user, 'pictureUrls.values.0'),
        ]);
    }

    /**
     * Set the user fields to request from LinkedIn.
     *
     * @param  array  $fields
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }
}

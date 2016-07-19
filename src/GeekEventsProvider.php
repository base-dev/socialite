<?php

namespace Laravel\Socialite;

use Illuminate\Support\Arr;

use Laravel\Socialite\Contracts\ProviderInterface;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

/**
 * This is a custom provider for Geek Events
 *
 * This is not using Oauth2, but it shares funcitonality with
 * those providers, hence the extending and such.
 *
 * If you find yourself wondering if you need to use this provider,
 * ask yourself this question: "Have I been explicitly told to use
 * Geek Events for authenticating my users?". There's your answer.
 */
class GeekEventsProvider extends AbstractProvider implements ProviderInterface
{
    protected $stateless = true;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://www.geekevents.org/sso/', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.geekevents.org/sso/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $req = $this->request->request;
        $id        = $req->get('id');
        $timestamp = $req->get('timestamp');
        $token     = $req->get('token');

        // TODO(nicolai): Refactor these two?
        $userinfo = $this->getHttpClient()->post('https://www.geekevents.org/sso/userinfo/', [
            'form_params' => [
                'user_id'   => $id,
                'timestamp' => $timestamp,
                'token'     => $token,
            ],
        ]);

        $hasTicket = $this->getHttpClient()->post('https://www.geekevents.org/sso/user-has-ticket/', [
            'form_params' => [
                'user_id'   => $id,
                'timestamp' => $timestamp,
                'token'     => $token,
                'event_id'  => env('GEEKEVENTS_EVENT_ID', 0),
            ],
        ]);

        $user = json_decode($userinfo->getBody(), true);
        $user['ticket_valid'] = (bool)json_decode($hasTicket->getBody(), true);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'next' => $this->redirectUrl,
        ];

        // TODO(nicolai): Find out if this provider should be stateless.
        //                If not, remove this.
        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $user['id'] = $this->request->request->get('id');
        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'id'),
            'nickname' => Arr::get($user, 'username'),
            'name'     => Arr::get($user, 'first_name')
                .' '.Arr::get($user, 'last_name'),
            'email'    => Arr::get($user, 'email'),
        ]);
    }
}

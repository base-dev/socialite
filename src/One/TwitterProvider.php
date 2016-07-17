<?php

namespace Laravel\Socialite\One;

use InvalidArgumentException;

class TwitterProvider extends AbstractProvider
{
    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if (! $this->hasNecessaryVerifier()) {
            throw new InvalidArgumentException('Invalid request. Missing OAuth verifier.');
        }

        $token = $this->getToken();
        $user = $this->server->getUserDetails($token);

        $extraDetails = [
            'location' => $user->location,
            'description' => $user->description,
        ];

        // TODO(nicolai): This code is broken. $user->extra isn't a thing.
        $instance = (new User)->setRaw(array_merge($user->extra, $user->urls, $extraDetails))
                ->setToken($token->getIdentifier(), $token->getSecret());

        return $instance->map([
            'id'       => $user->uid,
            'nickname' => $user->nickname,
            'name'     => $user->name,
            'email'    => $user->email,
            'avatar'   => $user->imageUrl,
            'avatar_original' => str_replace('_normal', '', $user->imageUrl),
        ]);
    }
}

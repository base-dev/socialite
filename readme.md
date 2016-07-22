# Laravel Socialite

[![Build Status](https://travis-ci.org/base-dev/socialite.svg?branch=2.0)](https://travis-ci.org/base-dev/socialite)

<!-- [![Latest Stable Version](https://poser.pugx.org/laravel/socialite/v/stable.svg)](https://packagist.org/packages/laravel/socialite) -->
<!-- [![Latest Unstable Version](https://poser.pugx.org/laravel/socialite/v/unstable.svg)](https://packagist.org/packages/laravel/socialite) -->
<!-- [![License](https://poser.pugx.org/laravel/socialite/license.svg)](https://packagist.org/packages/laravel/socialite) -->
<!-- [![Dependency Status](https://www.versioneye.com/php/laravel:socialite/dev-master/badge?style=flat)](https://www.versioneye.com/php/laravel:socialite/dev-master) -->

## Introduction

Laravel Socialite provides an expressive, fluent interface to OAuth
authentication with Facebook, Google, LinkedIn and GitHub. It handles almost
all of the boilerplate social authentication code you are dreading writing.

### Notice:
This is a very recent fork of laravel/socialite. It lacked adapters we wanted:
Twitter, Bitbucket and Geek Events; and it lacked features we wanted.
As we continue the work we realise that we want to refactor and restructure,
improve and simplify the codebase. This means that **the `2.0` branch in
this repository currently is unstable**. Until someone, other than the owners,
contributes or stars the repo we will assume no one is using it, which means
we probably won't cut a release until this is about to go into production code.

Required **PHP version: >=5.5**. Laravel/socialite supports 5.4, we don't
because we use [class name resolution via ::class](http://php.net/manual/en/migration55.new-features.php#migration55.new-features.class-name).
The Laravel framework does also use this feature, so if you're using a recent
version of Laravel you should be okay.


## Usage

Add Socialite as a dependency to your `composer.json` file, and add a
`repositories` block to use this fork:

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/base-dev/socialite"
        }
    ],
    "require": {
        "laravel/socialite": "^2.0"
    },

**This is the only place you will need to reference the `base-dev` name. The
package registers under the Laravel name, so `use` statements will use Laravel,
not Basedev.**

### Configuration

After installing the Socialite library, register the
`Laravel\Socialite\SocialiteServiceProvider` in your `config/app.php`
configuration file:

```php
'providers' => [
    // Other service providers...

    Laravel\Socialite\SocialiteServiceProvider::class,
],
```

Also, add the `Socialite` facade to the `aliases` array in your `app`
configuration file:

```php
'Socialite' => Laravel\Socialite\Facades\Socialite::class,
```

You will also need to add credentials for the OAuth services your application
utilizes. These credentials should be placed in your `config/services.php`
configuration file, and should use the key `facebook`, `linkedin`,
`google` or `github`, depending on the providers your application
requires. For example:

```php
'github' => [
    'client_id' => 'your-github-app-id',
    'client_secret' => 'your-github-app-secret',
    'redirect' => 'http://your-callback-url',
],
```
### Basic Usage

You will need two routes: one for redirecting the user to the OAuth provider,
and another for receiving the callback from the provider after authentication.
We will access Socialite using the `Socialite` facade:

```php
<?php

namespace App\Http\Controllers\Auth;

use Socialite;

class AuthController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return Response
     */
    public function handleProviderCallback()
    {
        $user = Socialite::driver('github')->user();

        // $user->token;
    }
}
```

The `redirect` method takes care of sending the user to the OAuth provider,
while the `user` method will read the incoming request and retrieve the user's
information from the provider. Before redirecting the user, you may also set
"scopes" on the request using the `setScopes` method. This method will overwrite
all existing scopes:

```php
return Socialite::driver('github')
            ->scopes(['scope1', 'scope2'])->redirect();
```

Of course, you will need to define routes to your controller methods:

```php
Route::get('auth/github', 'Auth\AuthController@redirectToProvider');
Route::get('auth/github/callback', 'Auth\AuthController@handleProviderCallback');
```

A number of OAuth providers support optional parameters in the redirect
request. To include any optional parameters in the request, call the `with`
method with an associative array:

```php
return Socialite::driver('google')
            ->with(['hd' => 'example.com'])->redirect();
```

When using the `with` method, be careful not to pass any reserved keywords such as `state` or `response_type`.

#### Retrieving User Details

Once you have a user instance, you can grab a few more details about the user:

```php
$user = Socialite::driver('github')->user();

// OAuth Two Providers
$refreshToken = $user->refreshToken; // not always provided
$expiresIn = $user->expiresIn;

// OAuth One Providers
$tokenSecret = $user->tokenSecret;

// All Providers
$token = $user->token;
$user->getId();
$user->getNickname();
$user->getName();
$user->getEmail();
$user->getAvatar();
```

## License

Socialite is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

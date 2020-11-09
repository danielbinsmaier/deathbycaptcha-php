# deathbycaptcha-php

Convenience package to simplify the usage of the official DeathByCaptcha PHP library.

Solves captchas using the service of [DeathByCaptcha](https://www.deathbycaptcha.com/).

## Install

Composer:
```
composer require danielbinsmaier/deathbycaptcha-php
```

## Example

```php
use DanielBinsmaier\DeathByCaptcha\Client;

// Create a client instance.
$client = new Client();

// Authenticate and use HTTP client.
$client->http('username', 'password');

// Alternatively, you can authenticate using socket based client.
// $client->socket('username', 'password');

// To authenticate using the authtoken, pick either http or socket and don't use a password.
// $client->http('authtoken');

// Get user information from the service.
$user = $client->user();

// Check balance.
$balance = $client->balance();

// Solve a captcha and wait for it.
$result = $client->solve('captcha.png');

// Additionally, you can also upload the captcha first and check later.
$captcha = $client->upload('captcha.png');

// Check the captcha by polling its state ...
if ($captcha->poll()) {
    // captcha solved ...
}

// ... or poll until it's solved.
$result = $captcha->solve();
```

### Upload and wait for the captcha using http client

```php
use DanielBinsmaier\DeathByCaptcha\Client;

$client = new Client();
$client->http('username', 'password');
$result = $client->solve('captcha.png');
```

### Upload and wait for the captcha using socket client and authtoken

```php
use DanielBinsmaier\DeathByCaptcha\Client;

$client = new Client();
$client->socket('authtoken');
$result = $client->solve('captcha.png');
```

### Solve reCAPTCHA v2

```php
use DanielBinsmaier\DeathByCaptcha\Client;

$client = new Client();
$client->socket('authtoken');

$data = [
    'googlekey' => 'sitekey',
    'pageurl' => 'url'
];

$params = json_encode($data);

$extra = [
    'type' => 4,
    'token_params' => $params
];

$result = $client->solve(null, $extra);
```

## Change Log

Please see [CHANGELOG](CHANGELOG.md) for notable changes.

## Credits

- [Daniel Binsmaier](https://github.com/danielbinsmaier)
- [All contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
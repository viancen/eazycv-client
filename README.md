# EazyCV PHP client

PHP HTTP client for the [EazyCV](https://www.eazycv.nl) API.

Package: [`viancen/eazycv-client`](https://packagist.org/packages/viancen/eazycv-client)  
Version: **1.4.0**  
License: [Apache-2.0](LICENSE)

This library wraps the EazyCV REST API with a small, stable PHP interface. It is designed for Laravel apps and plain PHP projects that already depend on Guzzle.

---

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Authentication](#authentication)
- [HTTP methods](#http-methods)
- [Request contract](#request-contract)
- [Constructor & options](#constructor--options)
- [Public properties](#public-properties)
- [Laravel usage](#laravel-usage)
- [Exceptions](#exceptions)
- [Error handling notes](#error-handling-notes)
- [Testing / dependency injection](#testing--dependency-injection)
- [Compatibility](#compatibility)
- [Development](#development)
- [Support](#support)
- [License](#license)

---

## Requirements

| Dependency | Version |
| --- | --- |
| PHP | `>= 7.3` |
| [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) | `^7.0` |

---

## Installation

```bash
composer require viancen/eazycv-client
```

Composer autoloads:

- `EazycvClient` (main client class)
- `Eazycv_Error`, `Eazycv_HttpError`, `Eazycv_ValidationError`, `Eazycv_Invalid_Key`

No service provider or framework binding is required.

---

## Quick start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$client = new EazycvClient(
    'your-api-key',
    'your-customer-slug',
    'https://api.eazycv.cloud' // optional; this is the default
);

// Optional: attach a logged-in user session token
$client->setUserKey($sessionToken);

$users   = $client->get('users', ['page' => 1]);
$created = $client->post('items', ['name' => 'Example']);
$updated = $client->put('items/1', ['name' => 'Updated']);
$deleted = $client->del('items/1');
```

All successful responses are returned as associative arrays (`json_decode($body, true)`).

---

## Authentication

The client supports two login helpers. Both expect the API to return a `session` object containing at least a `token`.

### Back-office user login

Stores the session token on `$client->userKey` (sent as `X-User` on later requests).

```php
$session = $client->loginUser(
    $email,
    $password,
    $persistant = false, // intentional API field spelling
    $captcha = null
);

// $session === ['token' => '...', ...]
// $client->userKey === $session['token']
```

### Candidate login

Replaces `$client->apiKey` with the session token (sent as `X-Authorization` on later requests).

```php
$session = $client->loginCandidate(
    $email,
    $password,
    $persistant = false,
    $captcha = null
);

// $client->apiKey === $session['token']
```

### Manual session token

```php
$client->setUserKey($tokenFromStorage);
```

### Login failures

| Situation | Behaviour |
| --- | --- |
| Missing email or password | throws `Eazycv_Error` |
| Response without `session` | throws `Eazycv_Error` (`Invalid credentials`) |

---

## HTTP methods

| Method | Signature | Wire format |
| --- | --- | --- |
| `get` | `get(string $endpoint, array $parameters = [])` | Query string |
| `post` | `post(string $endpoint, array $params = [])` | JSON body |
| `put` | `put(string $endpoint, mixed $params)` | JSON body |
| `del` | `del(string $endpoint)` | No body |

Examples:

```php
// GET https://api.eazycv.cloud/users?page=2&q=anna
$client->get('users', ['page' => 2, 'q' => 'anna']);

// POST https://api.eazycv.cloud/api-keys
$client->post('api-keys', ['name' => 'Integration']);

// PUT https://api.eazycv.cloud/help/12
$client->put('help/12', ['title' => 'Updated title']);

// DELETE https://api.eazycv.cloud/presets/9
$client->del('presets/9');
```

Endpoint paths are appended to `$client->root` (which always ends with `/`).

---

## Request contract

Every request sends these headers:

| Header | Source |
| --- | --- |
| `X-Authorization` | `$client->apiKey` |
| `X-Customer` | `$client->customer` |
| `X-User` | `$client->userKey` |
| `X-response-type` | always `json` |
| `Content-Type` | always `application/json` |

Additional behaviour (stable / backwards compatible):

- `POST` / `PUT` bodies are `json_encode(...)`
- `GET` parameters are sent as the Guzzle `query` option
- Response bodies are decoded with `json_decode(..., true)`
- TLS certificate verification is disabled (`verify => false`)

> **Note:** The login payload field is spelled `persistant` (not `persistent`). Keep this spelling when calling the API through this client.

---

## Constructor & options

```php
new EazycvClient(
    string $apikey,
    ?string $customer = null,
    ?string $root = null,
    array $options = []
);
```

| Argument | Required | Description |
| --- | --- | --- |
| `$apikey` | yes | API key used as `X-Authorization` |
| `$customer` | yes | Customer / instance slug used as `X-Customer` |
| `$root` | no | API base URL. Default: `https://api.eazycv.cloud` |
| `$options` | no | See below |

### Options

| Key | Type | Description |
| --- | --- | --- |
| `settings` | `array` | Client-side settings bag. Available via `getSettings()` / `$client->settings`. Not sent automatically on requests. |
| `http_client` | `GuzzleHttp\ClientInterface` | Inject a custom Guzzle client (useful for tests, proxies, timeouts, logging). |

```php
$client = new EazycvClient($apiKey, $customer, $root, [
    'settings' => [
        'module' => 'crm',
    ],
    'http_client' => new GuzzleHttp\Client([
        'timeout' => 15,
    ]),
]);

$settings = $client->getSettings(); // ['module' => 'crm']
```

Missing `$apikey` or `$customer` throws `Eazycv_Error`.

---

## Public properties

| Property | Type | Description |
| --- | --- | --- |
| `$apiKey` | `string` | Sent as `X-Authorization` |
| `$customer` | `string` | Sent as `X-Customer` |
| `$userKey` | `string\|null` | Sent as `X-User` |
| `$root` | `string` | Normalized base URL with trailing slash |
| `$settings` | `array` | Optional local settings |
| `$debug` | `bool` | Reserved flag (currently unused) |
| `$error_map` | `static array` | Maps API error names to exception class names |

These properties are part of the public surface and may be read or updated by consuming applications.

---

## Laravel usage

Typical service wrapper pattern:

```php
use EazycvClient;

$client = new EazycvClient(
    config('services.eazycv.key'),
    config('services.eazycv.instance'),
    config('services.eazycv.api_location', 'https://api.eazycv.cloud')
);

if (session()->has('user_session')) {
    $client->setUserKey(session('user_session'));
}

$instances = $client->get('instances', ['limit' => 50]);
```

Example `config/services.php` entries:

```php
'eazycv' => [
    'key' => env('EAZYCV_API_KEY'),
    'instance' => env('EAZYCV_INSTANCE'),
    'api_location' => env('EAZYCV_API_URL', 'https://api.eazycv.cloud'),
],
```

---

## Exceptions

| Class | Extends | When |
| --- | --- | --- |
| `Eazycv_Error` | `Exception` | Base client error |
| `Eazycv_HttpError` | `Eazycv_Error` | HTTP-layer client error |
| `Eazycv_ValidationError` | `Eazycv_Error` | Invalid / missing parameters |
| `Eazycv_Invalid_Key` | `Eazycv_Error` | Invalid API key |

```php
try {
    $session = $client->loginUser($email, $password);
} catch (Eazycv_Error $e) {
    // invalid credentials, missing fields, etc.
}
```

---

## Error handling notes

- Constructor and login helpers throw `Eazycv_Error` for validation / credential problems.
- Transport failures from Guzzle (`GuzzleHttp\Exception\GuzzleException`) propagate to the caller, same as in earlier releases.
- If an `Eazycv_HttpError` is thrown inside the request path, methods return:

```php
[
    'code' => (int) $error->getCode(),
    'message' => (string) $error->getMessage(),
]
```

---

## Testing / dependency injection

Inject a mocked Guzzle client via `http_client`:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$mock = new MockHandler([
    new Response(200, [], json_encode(['users' => []])),
]);

$client = new EazycvClient('api-key', 'demo', 'https://api.eazycv.cloud', [
    'http_client' => new Client([
        'handler' => HandlerStack::create($mock),
    ]),
]);

$result = $client->get('users');
```

---

## Compatibility

Version **1.4.0** keeps the historical public I/O contract used by existing CRM / admin integrations:

- Same constructor signature
- Same method names and argument order (`get`, `post`, `put`, `del`, `loginUser`, `loginCandidate`, `setUserKey`, `getSettings`)
- Same headers, JSON encoding, query handling and decoded array responses
- Same login side effects (`userKey` vs `apiKey`)
- Same global class / exception names
- Same default root URL and `verify => false` behaviour

Additive only: optional `http_client` constructor option.

---

## Development

Clone and install:

```bash
git clone https://github.com/viancen/eazycv-client.git
cd eazycv-client
composer install
```

Run the test suite:

```bash
composer test
```

Project layout:

```text
src/
  EazycvClient.php
  EazycvClient/Exceptions.php
tests/
  EazycvClientTest.php
composer.json
phpunit.xml
```

---

## Support

- Issues: [github.com/viancen/eazycv-client/issues](https://github.com/viancen/eazycv-client/issues)
- Email: [info@eazycv.nl](mailto:info@eazycv.nl)
- Product: [www.eazycv.nl](https://www.eazycv.nl)

---

## License

Licensed under the [Apache License 2.0](LICENSE).

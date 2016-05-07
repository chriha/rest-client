# rest-client

A simple REST client with PHPs cURL.

## Usage

Define your options

```php
$options = [
    'url' => 'http://api.localhost/v1',
];
```

See `\Chriha\Clients\Rest::getDefaultOptions()` for all default options.

### GET

```php
$rest = new \Chriha\Clients\Rest( $options );
$rest->get( '/posts' );
```

### POST

```php
$post = [
    "title" => "lorem",
    "body"  => "lorem ipsum dolor set"
];

$rest = new \Chriha\Clients\Rest( $options );
$rest->post( '/posts', $post );
```

### PUT / PATCH

```php
$post = [
    "title" => "lorem"
];

$rest = new \Chriha\Clients\Rest( $options );
$rest->put( '/posts/1', $post );
```

### DELETE

```php
$rest = new \Chriha\Clients\Rest( $options );
$rest->delete( '/posts/1' );
```

## Options

### Allow self signed certificates

Recommended only in dev environment. Default: `false`

```php
$options = [
    'allow_self_signed' => true,
];
```

### Set additional cURL options

```php
$options = [
    'curl_options' => [...],
];
```

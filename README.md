# rest-client

[![Build Status](https://travis-ci.org/chriha/rest-client.svg?branch=master)](https://travis-ci.org/chriha/rest-client)

A simple REST client with PHPs cURL.

## Install

```
composer require chriha/rest-client
```

## Usage

Define your options

```php
$options = [
    'url' => 'http://api.localhost/v1',
];
```

See `\Chriha\Clients\Rest::getDefaultOptions()` for all default options.

#### GET

```php
$rest = new \Chriha\Clients\Rest( $options );
$rest->get( '/posts' );
```

#### POST

```php
$post = [
    "title" => "lorem",
    "body"  => "lorem ipsum dolor set"
];

$rest = new \Chriha\Clients\Rest( $options );
$rest->post( '/posts', $post );
```

#### PUT / PATCH

```php
$post = [
    "title" => "lorem"
];

$rest = new \Chriha\Clients\Rest( $options );
$rest->put( '/posts/1', $post );
$rest->patch( '/posts/1', $post );
```

#### DELETE

```php
$rest = new \Chriha\Clients\Rest( $options );
$rest->delete( '/posts/1' );
```

## Options

#### Allow self signed certificates

Recommended only in dev environment, so default is `false`

```php
$options = [
    'allow_self_signed' => true,
];
```

#### Set additional cURL options

```php
$options = [
    'curl_options' => [...],
];
```

#### OAuth 1.0 authentication

```php
$options = [
    'authentication' => 'oauth1',
    'token'          => 'YOUR_API_TOKEN',
    'secret'         => 'YOUR_API_SECRET',
];
```

## Using the CLI rest client

Make an alias like `alias='vendors/bin/rest'` for simpler usage of the client inside the project.

With the following command you can do a request via the rest client.

```shell
$ ./rest GET http://api.localhost.io/v1/posts "parameters=specified&as=simple&query=string" "Content-Type:application/json;Accept-Charset: utf-8"
```

If you want to use `token` and `secret` for your authentication, you can place them as JSON in the `.rest` file of your project root:

```json
{
    "token": "YOUR_API_TOKEN",
    "secret": "YOUR_API_SECRET"
}
```

The output of the rest client will be shown as the following:

```
Request took 23.45ms
Response Code: 200
Response Body:
{
    "meta": "info",
    "data": [
        {
            "title": "lorem"
        }
    ]
}
```

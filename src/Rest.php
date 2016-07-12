<?php

namespace Chriha\Clients;

use Chriha\Clients\Exceptions\ResponseException;
use Chriha\Clients\Exceptions\RestException;

class Rest
{

    /**
     * Contains the result resource
     * @var resource
     */
    protected $result;

    /**
     * Contains the options for the REST request
     * @var array
     */
    protected $options = [];

    /**
     * Contains all occurred errors
     * @var array
     */
    protected $errors;

    /**
     * Contains the latest error code
     * @var integer
     */
    protected $errorCode;

    /**
     * Contains the URL to the REST API
     * @var string
     */
    protected $url = 'https://api.localhost/v1';

    /**
     * Contains the user agent
     * @var string
     */
    protected $agent = 'chriha/clients/rest/v1.0';

    /**
     * Contains the cURL handler
     * @var Resource
     */
    protected $handle;

    /**
     * Store used method
     * @var string
     */
    protected $method;

    /**
     * Contains the cURL response body
     * @var mixed
     */
    public $response;

    /**
     * Contains the cURL headers
     * @var object
     */
    public $headers;

    /**
     * Response info object
     * @var object
     */
    public $info;

    /**
     * The expected status codes
     * @var mixed
     */
    protected $expected;

    /**
     * The 'rules' for the expected status codes
     * @var array
     */
    public static $expectations = [
        'GET'    => 200,
        'POST'   => [ 200, 201 ],
        'PUT'    => [ 200, 202 ],
        'PATCH'  => [ 200, 202 ],
        'DELETE' => [ 200, 204 ]
    ];


    /**
     * Create a new REST client.
     *
     * @param array $options
     */
    public function __construct( array $options = [] )
    {
        $this->mergeOptions( $options );
    }



    /** handlers *********************/

    /**
     * Alias for GET request
     *
     * @param  string $uri        The URI to use for the request
     * @param  array  $parameters The expecting parameters
     * @param  array  $headers    The headers for the request
     * @return self
     */
    public function get( $uri, $parameters = [], $headers = [] )
    {
        return $this->send( $uri, 'GET', $parameters, $headers );
    }

    /**
     * Alias for POST request
     *
     * @param  string $uri        The URI to use for the request
     * @param  array  $parameters The expecting parameters
     * @param  array  $headers    The headers for the request
     * @return self
     */
    public function post( $uri, $parameters = [], $headers = [] )
    {
        return $this->send( $uri, 'POST', $parameters, $headers );
    }

    /**
     * Alias for PUT request
     *
     * @param  string $uri        The URI to use for the request
     * @param  array  $parameters The expecting parameters
     * @param  array  $headers    The headers for the request
     * @return self
     */
    public function put( $uri, $parameters = [], $headers = [] )
    {
        return $this->send( $uri, 'PUT', $parameters, $headers );
    }

    /**
     * Alias for PATCH request
     *
     * @param  string $uri        The URI to use for the request
     * @param  array  $parameters The expecting parameters
     * @param  array  $headers    The headers for the request
     * @return self
     */
    public function patch( $uri, $parameters = [], $headers = [] )
    {
        return $this->send( $uri, 'PATCH', $parameters, $headers );
    }

    /**
     * Alias for DELETE request
     *
     * @param  string $uri        The URI to use for the request
     * @param  array  $parameters The expecting parameters
     * @param  array  $headers    The headers for the request
     * @return self
     */
    public function delete( $uri, $parameters = [], $headers = [] )
    {
        return $this->send( $uri, 'DELETE', $parameters, $headers );
    }

    /**
     * Sending the request to the REST API
     *
     * @param  string $uri        The URI to use for the request
     * @param  string $method     The method of the request
     * @param  array  $parameters The expecting parameters
     * @param  array  $headers    The headers for the request
     * @throws RestException
     * @return self
     */
    public function send( $uri, $method = 'GET', $parameters = [], $headers = [] )
    {
        $url = $this->options['url'] . $uri;

        $this->method = $method = strtoupper( $method );

        $options = [
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => $this->agent,
        ];

        $parameters = array_merge( $this->options['parameters'], $parameters );

        $this->prepareAuth( $url, $parameters );

        if ( count( $this->options['headers'] ) || count( $headers ) )
        {
            $options[CURLOPT_HTTPHEADER] = [];

            $headers = array_merge( $this->options['headers'], $headers );

            foreach ( $headers as $key => $value )
            {
                $options[CURLOPT_HTTPHEADER][] = sprintf( "%s:%s", $key, $value );
            }
        }

        if ( $this->options['headers']['Content-Type'] === 'application/json' )
        {
            $parsedParams = json_encode( $parameters );
        }
        else
        {
            $parsedParams = http_build_query( $parameters );
        }

        if ( $this->method === 'POST' )
        {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = $parsedParams;
        }
        elseif ( $this->method !== 'GET' )
        {
            $options[CURLOPT_CUSTOMREQUEST] = $this->method;
            $options[CURLOPT_POSTFIELDS]    = $parsedParams;
        }
        elseif ( count( $parameters ) )
        {
            $url .= strpos( $url, '?' ) !== false ? '&' : '?';
            $url .= $parsedParams;
            $url  = preg_replace( "/%5B\d%5D/", "", $url );
        }

        $options[CURLOPT_URL] = $url;

        // don't verify certificates if we don't want to. for security
        // reasons, this should not be enabled in production
        if ( $this->options['allow_self_signed'] )
        {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        if ( $this->options['curl_options'] )
        {
            // array_merge would reset our numeric keys.
            foreach ( $this->options['curl_options'] as $key => $value )
            {
                $options[$key] = $value;
            }
        }

        $this->handle = curl_init();

        curl_setopt_array( $this->handle, $options );

        $this->response = curl_exec( $this->handle );
        $this->info     = (object)curl_getinfo( $this->handle );
        $error          = curl_error( $this->handle );

        curl_close( $this->handle );

        $this->debug( "Request took " . $this->getTotalTime() . "ms in total" );

        if ( ! empty( $error ) )
        {
            throw new RestException( $error );
        }

        $this->checkValidResponse();

        return $this;
    }

    /**
     * Checks if the request was successful
     *
     * @param integer $code
     * @return boolean
     */
    public function succeeded( $code = null )
    {
        if ( ! is_null( $code ) )
        {
            return $this->getStatusCode() === $code;
        }

        if ( is_int( $this->getExpected() ) )
        {
            return $this->getStatusCode() === $this->getExpected();
        }

        if ( is_array( $this->getExpected() ) )
        {
            return in_array( $this->getStatusCode(), $this->getExpected() );
        }

        return $this->getStatusCode() === 200;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->info->http_code;
    }

    /**
     * Total transaction time in ms for last transfer
     *
     * @return float
     */
    public function getTotalTime()
    {
        return $this->info->total_time * 1000;
    }

    /**
     * Time in ms it took to establish the connection
     *
     * @return float
     */
    public function getConnectionTime()
    {
        return $this->info->connect_time * 1000;
    }

    /**
     * TLS certificate chain
     *
     * @return array
     */
    public function getCertInfo()
    {
        return $this->info->certinfo;
    }

    /**
     * Content-Type of the requested document.
     * NULL indicates server did not send valid Content-Type header
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->info->content_type;
    }

    /**
     * Checks the response for valid HTTP code. To disable this
     * functionality, set "validate" option to 'false'.
     *
     * @throws ResponseException
     */
    public function checkValidResponse()
    {
        // do nothing if validation is disabled or request method not set
        if ( ! $this->options['validate'] || empty( $this->method ) ) return;

        $this->expected = $this->expectByMethod( $this->method );

        if ( $this->check( $this->getStatusCode(), $this->expected ) ) return;

        $exception = new ResponseException( "The request was not successful! Response message was: '{$this->response}'", $this->getStatusCode() );

        $exception->setExpectedStatusCode( $this->expected );

        throw $exception;
    }

    /**
     * Check the provided code and method for valid response
     *
     * @param int   $code     The code which was responded by the API
     * @param mixed $expected The expected status code(s)
     * @return bool
     */
    public function check( $code, $expected )
    {
        if ( is_array( $expected ) )
        {
            return in_array( $code, $expected );
        }

        return $code === $expected;
    }

    /**
     * Get the excepted HTTP status code by method
     *
     * @param  string        $method
     * @throws RestException
     * @return mixed
     */
    public function expectByMethod( $method )
    {
        if ( ! isset( static::$expectations[$method] ) )
        {
            throw new RestException( "Unsupported method '{$method}'." );
        }

        return static::$expectations[$method];
    }

    /**
     * Print debugging message
     *
     * @param  mixed $message
     * @return void
     */
    protected function debug( $message )
    {
        if ( ! $this->options['debug'] ) return;

        if ( php_sapi_name() !== 'cli' )
        {
            var_dump( $message );

            return;
        }

        if ( is_object( $message ) && method_exists( $message, '__toString' ) )
        {
            $print = (string)$message;
        }
        elseif ( is_object( $message ) || is_array( $message ) )
        {
            $print = json_encode( $message );
        }
        else
        {
            $print = $message;
        }

        // add some colors for the timestamp
        echo "\e[0;34m" . date( 'Y-m-d H:i:s' ) . ":\033[0m {$print}\n\r";
    }


    /** getters & setters ************/

    /**
     * Merge options from definition with the defaults
     *
     * @param $options
     */
    protected function mergeOptions( $options )
    {
        $defaults = $this->getDefaultOptions();

        $this->options = array_merge( $defaults, $options );

        foreach ( $this->options as $key => $value )
        {
            if ( ! is_array( $value ) || ! isset( $options[$key] ) ) continue;

            $this->options[$key] = array_merge( $defaults[$key], $options[$key] );
        }
    }

    /**
     * Returns the default options
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return [
            'headers'            => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
            'parameters'         => [],
            'curl_options'       => [],
            'url'                => $this->url,
            'authentication'     => false,
            'token'              => null,
            'secret'             => null,
            'username'           => null,
            'password'           => null,
            'algorithm'          => 'sha256',
            'debug'              => false,
            'allow_self_signed'  => false,
            'validate'           => true,
            'response_as_array'  => false,
        ];
    }

    /**
     * Checks if auth is used
     *
     * @return boolean
     */
    protected function hasAuth()
    {
        return !! $this->options['authentication'];
    }

    /**
     * Prepare the request for authentication
     *
     * @param string $url
     * @param array $params
     * @throws RestException
     */
    protected function prepareAuth( $url, $params )
    {
        if ( $this->options['authentication'] === false )
        {
            return;
        }
        elseif ( ! in_array( $this->options['authentication'], [ 'oauth1', 'basic' ] ) )
        {
            throw new RestException( 'Unsupported authentication.' );
        }

        switch ( $this->options['authentication'] )
        {
            case 'oauth1':
                $this->prepareOauth1( $url, $params );
                break;
            case 'basic':
                $this->prepareBasicAuth();
                break;
        }
    }

    /**
     * Prepare the request for OAuth authentication
     *
     * @throws RestException
     * @param  string $url The full URL of the request
     * @param  array $params The request parameters
     * @return string
     */
    protected function prepareOauth1( $url, $params )
    {
        if ( is_null( $this->options['token'] ) )
        {
            throw new RestException( 'No token key provided!' );
        }
        elseif ( is_null( $this->options['secret'] ) )
        {
            throw new RestException( 'No secret key provided!' );
        }

        $oauth = [
            'oauth_nonce'            => $this->generateNonce(),
            'oauth_signature_method' => strtoupper( "HMAC-{$this->options['algorithm']}" ),
            'oauth_timestamp'        => time(),
            'oauth_token'            => $this->options['token'],
            'oauth_version'          => '1.0',
        ];

        $params    = array_merge( $params, $oauth );
        $paramList = [];

        foreach ( $params as $key => $value )
        {
            $paramList[rawurlencode( $key )] = rawurlencode( $value );
        }

        // array needs to be sorted for a valid signature
        ksort( $paramList );

        $query = "";

        foreach ( $paramList as $key => $value )
        {
            $query .= "{$key}={$value}&";
        }

        $query  = rtrim( $query, "&" );
        $string = strtoupper( $this->method ) . "&" . rawurlencode( $url ) . "&" . rawurlencode( $query );

        // the & is important and has to be added, even if there's no secret *troll*
        $secret    = "&" . rawurlencode( $this->options['secret'] );
        $signature = base64_encode( hash_hmac( $this->options['algorithm'], $string, $secret, true ) );

        $oauth['oauth_signature'] = rawurlencode( $signature );

        $oauthString = "";

        foreach ( $oauth as $key => $value )
        {
            $oauthString .= $key . '="' . $value . '",';
        }

        $this->options['headers']['Authorization'] = "OAuth " . rtrim( $oauthString, ',' );
    }

    /**
     * Prepare the request for basic access authentication
     *
     * @return void
     */
    public function prepareBasicAuth()
    {
        $authString = base64_encode( $this->options['username'] . ':' . $this->options['password'] );

        $this->options['headers']['Authorization'] = "Basic {$authString}";
    }

    /**
     * Returns the cURL response
     *
     * @param  boolean $asObject When FALSE, returned objects will be converted into associative arrays
     * @return mixed
     */
    public function getResponse( $asObject = null )
    {
        if ( empty( $this->response ) ) return null;

        if ( ! is_null( $asObject ) )
        {
            $shouldBeObject = (bool)$asObject;
        }
        else
        {
            $shouldBeObject = ! $this->options['response_as_array'];
        }

        $response = json_decode( $this->response, ! $shouldBeObject );

        if ( json_last_error() ) return $this->response;

        return $response;
    }

    /**
     * Simple alias for the getResponse(...) method, which
     * returns an array by default.
     *
     * @param bool $asArray
     * @return mixed
     */
    public function json( $asArray = true )
    {
        return $this->getResponse( ! $asArray );
    }

    /**
     * Set additional options
     *
     * @param  string $key
     * @param  string $value
     * @return $this
     */
    public function setOption( $key, $value )
    {
        if ( ! array_key_exists( $key, $this->options ) ) return;

        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getExpected()
    {
        return $this->expected;
    }

    /**
     * @param mixed $expected
     */
    public function setExpected( $expected )
    {
        $this->expected = $expected;
    }

    /**
     * Generate a random string to use for the OAuth nonce
     *
     * See: https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
     *
     * @return string
     */
    private function generateNonce()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string     = '';
        $max        = mb_strlen( $characters, '8bit' ) - 1;

        for ( $i = 0; $i < 32; ++$i )
        {
            $string .= $characters[random_int( 0, $max )];
        }

        return $string;
    }

}

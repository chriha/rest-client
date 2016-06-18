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
     * GET request
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
     * POST request
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
     * PUT request
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
     * PATCH request
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
     * DELETE request
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

        if ( count( $this->options['headers'] ) || count( $headers ) )
        {
            $options[CURLOPT_HTTPHEADER] = [];

            $headers = array_merge( $this->options['headers'], $headers );

            foreach ( $headers as $key => $value )
            {
                $options[CURLOPT_HTTPHEADER][] = sprintf( "%s:%s", $key, $value );
            }
        }

        $parameters = array_merge( $this->options['parameters'], $parameters );

        if ( $this->method === 'POST' )
        {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = json_encode( $parameters );
        }
        elseif ( $this->method !== 'GET' )
        {
            $options[CURLOPT_CUSTOMREQUEST] = $this->method;
            $options[CURLOPT_POSTFIELDS]    = json_encode( $parameters );
        }
        elseif ( count( $parameters ) )
        {
            $urlParams = http_build_query( $parameters );

            if ( $this->hasAuth() )
            {
                $urlParams .= '&signature=' . $this->signature( $urlParams );
            }

            $url .= strpos( $url, '?' ) !== false ? '&' : '?';
            $url .= $urlParams;
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
     * Content-Type: of the requested document.
     * NULL indicates server did not send valid Content-Type: header
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
            'headers'            => [ 'Content-Type' => 'application/json' ],
            'parameters'         => [],
            'curl_options'       => [],
            'url'                => $this->url,
            'token'              => null,
            'secret'             => null,
            'debug'              => false,
            'allow_self_signed'  => false,
            'algorithm'          => 'sha256',
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
        return ! is_null( $this->options['secret'] );
    }

    /**
     * Create and return the signature for the request
     *
     * @throws RestException
     * @param  string $query The query with all its data
     * @return string
     */
    protected function signature( $query )
    {
        if ( is_null( $this->options['secret'] ) )
        {
            throw new RestException( 'No secret key provided!' );
        }

        $query = is_array( $query ) ? http_build_query( $query ) : $query;

        return base64_encode( hash_hmac( $this->options['algorithm'], $query, $this->options['secret'] ) );
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

        if ( $asObject )
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
     * Simple alias for the getResponse(...) method
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

}

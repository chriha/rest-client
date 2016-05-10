<?php

namespace Chriha\Clients\Tests;

use Chriha\Clients\Rest;
use PHPUnit_Framework_TestCase;

class RestTestCase extends PHPUnit_Framework_TestCase
{

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Rest
     */
    protected $client;


    public function setUp()
    {
        // TODO: provide own test API with faker
        $this->options = [
            'url' => 'http://jsonplaceholder.typicode.com',
        ];

        $this->client = new Rest( $this->options );
    }

}
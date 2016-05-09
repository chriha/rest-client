<?php

namespace Chriha\Clients\Tests;

use PHPUnit_Framework_TestCase;

class RestTestCase extends PHPUnit_Framework_TestCase
{

    protected $options;


    public function setUp()
    {
        // TODO: provide own test API with faker
        $this->options = [
            'url' => 'http://jsonplaceholder.typicode.com',
        ];
    }

}
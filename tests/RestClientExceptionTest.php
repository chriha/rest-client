<?php

namespace Chriha\Clients\Tests;

use Chriha\Clients\Exceptions\ResponseException;
use Chriha\Clients\Rest;

class RestClientExceptionTest extends RestTestCase
{

    /** @test */
    public function it_does_not_throw_response_exception()
    {
        $this->options['validate'] = false;

        $rest = new Rest( $this->options );
        $rest->get( '/postsS' );
    }

    /** TODO */
    public function it_throws_a_400_response_exception() {}

    /** TODO */
    public function it_throws_a_401_response_exception() {}

    /** TODO */
    public function it_throws_a_403_response_exception() {}

    /**
     * @test
     */
    public function it_throws_a_404_response_exception()
    {
        $this->expectException( ResponseException::class );
        $this->expectExceptionCode( 404 );

        $rest = new Rest( $this->options );
        $rest->get( '/postsS' );
    }

}
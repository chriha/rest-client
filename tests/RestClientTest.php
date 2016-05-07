<?php

use Chriha\Clients\Rest;

class RestClientTest extends PHPUnit_Framework_TestCase
{

    protected $options;


    public function setUp()
    {
        // TODO: provide own test API with faker
        $this->options = [
            'url' => 'http://jsonplaceholder.typicode.com',
        ];
    }

    /** @test */
    public function it_fetches_a_list_via_get_request()
    {
        $rest = new Rest( $this->options );
        $rest->get( '/posts' );

        $this->assertTrue( $rest->succeeded() );
        $this->assertGreaterThanOrEqual( 1, count( $rest->getData() ) );
    }

    /** @test */
    public function it_creates_an_item_via_post_request()
    {
        $post = [
            "title" => "lorem",
            "body"  => "lorem ipsum dolor set"
        ];

        $rest = new Rest( $this->options );
        $rest->post( '/posts', $post );

        $this->assertTrue( $rest->succeeded( 201 ) );
    }

    /** @test */
    public function it_updates_an_item_via_put_request()
    {
        $post = [
            "title" => "lorem ipsum"
        ];

        $rest = new Rest( $this->options );
        $rest->put( '/posts/1', $post );

        $this->assertTrue( $rest->succeeded() );
    }

    /** @test */
    public function it_updates_an_item_via_patch_request()
    {
        $post = [
            "title" => "lorem ipsum"
        ];

        $rest = new Rest( $this->options );
        $rest->patch( '/posts/1', $post );

        $this->assertContains( $rest->getStatusCode(), [ 200, 204 ] );
    }

    /** @test */
    public function it_deletes_an_item_via_delete_request()
    {
        $rest = new Rest( $this->options );
        $rest->delete( '/posts/1' );

        $this->assertContains( $rest->getStatusCode(), [ 200, 204 ] );
    }

    /** TODO */
    public function it_authenticates_via_oauth1() {}

}
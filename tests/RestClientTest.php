<?php

namespace Chriha\Clients\Tests;

class RestClientTest extends RestTestCase
{

    /** @test */
    public function it_fetches_a_list_via_get_request()
    {
        $this->client->get( '/posts' );

        $this->assertTrue( $this->client->succeeded() );
        $this->assertGreaterThanOrEqual( 1, count( $this->client->getResponse() ) );
    }

    /** @test */
    public function it_creates_an_item_via_post_request()
    {
        $post = [
            "title" => "lorem",
            "body"  => "lorem ipsum dolor set"
        ];

        $this->client->post( '/posts', $post );

        $this->assertTrue( $this->client->succeeded() );
    }

    /** @test */
    public function it_updates_an_item_via_put_request()
    {
        $post = [
            "title" => "lorem",
            "body"  => "lorem ipsum dolor set"
        ];

        $this->client->put( '/posts/1', $post );

        $this->assertTrue( $this->client->succeeded() );
    }

    /** @test */
    public function it_updates_an_item_via_patch_request()
    {
        $post = [
            "title" => "lorem ipsum"
        ];

        $this->client->patch( '/posts/1', $post );

        $this->assertTrue( $this->client->succeeded() );
    }

    /** @test */
    public function it_deletes_an_item_via_delete_request()
    {
        $this->client->delete( '/posts/1' );

        $this->assertTrue( $this->client->succeeded() );
    }

    /** @test */
    public function it_returns_the_response_in_the_correct_format()
    {
        $this->client->get( '/posts' );

        $arrays = $this->client->json();

        $this->assertTrue( is_array( $arrays ) );

        foreach ( $arrays as $array )
        {
            $this->assertTrue( is_array( $array ) );
        }

        $objects = $this->client->json( false );

        $this->assertTrue( is_array( $objects ) );

        foreach ( $objects as $object )
        {
            $this->assertTrue( is_object( $object ) );
        }
    }

}
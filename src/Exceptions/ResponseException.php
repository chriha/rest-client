<?php

namespace Chriha\Clients\Exceptions;

use Exception;

class ResponseException extends Exception
{

    /**
     * @var string
     */
    protected $url;


    /** getters & setters ************/

    /**
     * @param $url
     * @return $this
     */
    public function setUrl( $url )
    {
        $this->url = $url;

        return $this;
    }

}
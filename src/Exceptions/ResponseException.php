<?php

namespace Chriha\Clients\Exceptions;

class ResponseException extends RestException
{

    /**
     * @var mixed
     */
    protected $expectedStatusCode;


    /** getters & setters ************/

    /**
     * @return mixed
     */
    public function getExpectedStatusCode()
    {
        return $this->expectedStatusCode;
    }

    /**
     * @param mixed $expectedStatusCode
     */
    public function setExpectedStatusCode( $expectedStatusCode )
    {
        $this->expectedStatusCode = $expectedStatusCode;
    }

}
<?php

namespace Chriha\Clients\Exceptions;

class ResponseException extends RestException
{

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var float
     */
    protected $totalTime;

    /**
     * @var float
     */
    protected $connectTime;

    /**
     * @var array
     */
    protected $certInfo;

    /**
     * @var string
     */
    protected $contentType;


    /** getters & setters ************/

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl( $url )
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod( $method )
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param float $time
     * @return $this
     */
    public function setTotalTime( $time )
    {
        $this->totalTime = $time;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * @param float $time
     * @return $this
     */
    public function setConnectTime( $time )
    {
        $this->connectTime = $time;

        return $this;
    }

    /**
     * @return float
     */
    public function getConnectTime()
    {
        return $this->connectTime;
    }

    /**
     * @param array $certInfo
     * @return $this
     */
    public function setCertInfo( $certInfo )
    {
        $this->certInfo = $certInfo;

        return $this;
    }

    /**
     * @return array
     */
    public function getCertInfo()
    {
        return $this->certInfo;
    }

    /**
     * @param string $contentType
     * @return $this
     */
    public function setContentType( $contentType )
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

}
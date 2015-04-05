<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 11.10.14
 * Time: 14:28
 */

namespace Cundd\PersistentObjectStore\Server\ValueObject;

use Cundd\PersistentObjectStore\Server\Controller\ControllerResultInterface;

/**
 * Controller result implementation
 *
 * @package Cundd\PersistentObjectStore\Server\ValueObject
 */
class MutableControllerResult extends AbstractControllerResult implements ControllerResultInterface
{
    /**
     * Sets the content type of the request
     *
     * @param string $contentType
     * @return $this
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Sets the headers to send with the response
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Add the header with the given name
     *
     * @param string $name
     * @param mixed  $header
     * @return $this
     */
    public function addHeader($name, $header)
    {
        $this->headers[$name] = $header;

        return $this;
    }

    /**
     * Sets the status code for the response
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Sets the request's response data
     *
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

}

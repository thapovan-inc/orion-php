<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: Tracer.proto

namespace Com\Thapovan\Orion\Proto;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>com.thapovan.orion.proto.StreamResponse</code>
 */
class StreamResponse extends \Google\Protobuf\Internal\Message
{
    protected $response;

    public function __construct() {
        \GPBMetadata\Tracer::initOnce();
        parent::__construct();
    }

    /**
     * Generated from protobuf field <code>.com.thapovan.orion.proto.ServerResponse server_response = 1;</code>
     * @return \Com\Thapovan\Orion\Proto\ServerResponse
     */
    public function getServerResponse()
    {
        return $this->readOneof(1);
    }

    /**
     * Generated from protobuf field <code>.com.thapovan.orion.proto.ServerResponse server_response = 1;</code>
     * @param \Com\Thapovan\Orion\Proto\ServerResponse $var
     * @return $this
     */
    public function setServerResponse($var)
    {
        GPBUtil::checkMessage($var, \Com\Thapovan\Orion\Proto\ServerResponse::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->whichOneof("response");
    }

}


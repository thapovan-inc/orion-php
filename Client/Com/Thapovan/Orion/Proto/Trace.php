<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: Trace.proto

namespace Com\Thapovan\Orion\Proto;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>com.thapovan.orion.proto.Trace</code>
 */
class Trace extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string trace_id = 1;</code>
     */
    private $trace_id = '';

    public function __construct() {
        \GPBMetadata\Trace::initOnce();
        parent::__construct();
    }

    /**
     * Generated from protobuf field <code>string trace_id = 1;</code>
     * @return string
     */
    public function getTraceId()
    {
        return $this->trace_id;
    }

    /**
     * Generated from protobuf field <code>string trace_id = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setTraceId($var)
    {
        GPBUtil::checkString($var, True);
        $this->trace_id = $var;

        return $this;
    }

}


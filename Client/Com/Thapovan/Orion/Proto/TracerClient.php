<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
//
// Copyright (c) 2018 Thapovan info Systems
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//    http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
namespace Com\Thapovan\Orion\Proto;

/**
 */
class TracerClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function UploadSpanStream($metadata = [], $options = []) {
        return $this->_clientStreamRequest('/com.thapovan.orion.proto.Tracer/UploadSpanStream',
        ['\Com\Thapovan\Orion\Proto\ServerResponse','decode'],
        $metadata, $options);
    }

    /**
     * @param \Com\Thapovan\Orion\Proto\UnaryRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function UploadSpan(\Com\Thapovan\Orion\Proto\UnaryRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/com.thapovan.orion.proto.Tracer/UploadSpan',
        $argument,
        ['\Com\Thapovan\Orion\Proto\ServerResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Com\Thapovan\Orion\Proto\BulkRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function UploadSpanBulk(\Com\Thapovan\Orion\Proto\BulkRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/com.thapovan.orion.proto.Tracer/UploadSpanBulk',
        $argument,
        ['\Com\Thapovan\Orion\Proto\ServerResponse', 'decode'],
        $metadata, $options);
    }

}

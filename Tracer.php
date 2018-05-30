<?php

use Com\Thapovan\Orion\Proto;

/**
 * Description of Tracer.php
 *
 * @author DJ
 */
class Tracer {

    private $traceId = 0;
    private $headers = '';
    private $client = '';

    public function __construct() {
        $this->headers = getallheaders();
        $this->getTraceId($this->headers['traceId']);

        putenv("ENV_ORION_INSTANCE_WITH_PORT = 54.83.197.74:20691");
        $this->client = new Proto\TracerClient(getenv(ENV_ORION_INSTANCE_WITH_PORT), [
            'credentials' => Grpc\ChannelCredentials::createInsecure(),
        ]);
    }

    public static function UUID_V4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function getTraceId($traceId) {
        return $this->traceId = ($traceId > 0) ? $traceId : $this->UUID_V4();
    }

    public function startEvent($parentId, $eventId, $serviceLocation, $data = []) {
        $startEventObj = new Proto\StartEvent();
        $startEventObj->setEventId($eventId);
        $metaDataArr['api'] = $serviceLocation;
        $metaDataArr['data'] = $data;
        $metaDataArr['parentId'] = $parentId;
        $startEventObj->setJsonString(json_encode($metaDataArr));
    }

    public function endEvent($parentId, $eventId, $serviceLocation, $response = []) {
        $endEventObj = new Proto\EndEvent();
        $endEventObj->setEventId($eventId);
        $metaDataArr['api'] = $serviceLocation;
        $metaDataArr['data'] = $response;
        $metaDataArr['parentId'] = $parentId;
        $endEventObj->setJsonString(json_encode($metaDataArr));
    }

    public function logEvent($eventId, $level = 1, $message = '', $params = []) {
        $logEventObj = new Proto\LogEvent();
        $logEventObj->setEventId($eventId);
        $logEventObj->setLevel($level);
        $logEventObj->setMessage($message);
        $logEventObj->setJsonString(json_encode($params));
        return $logEventObj;
    }

    public function startSpan($serviceName, $serviceLocation, $spanId, $parentId, $data = [], $level = 1) {
        $traceObj = new Proto\Trace();
        $traceObj->setTraceId($this->traceId);

        $spanObj = new Proto\Span();
        $spanObj->setTraceContext($this->traceId);
        $spanObj->setSpanId($spanId);
        $spanObj->setTimestamp(strtotime(date('Y-m-d H:i:s')));
        $spanObj->setServiceName($serviceName);
        $spanObj->setEventLocation($serviceLocation);
        $spanObj->setParentSpanId($parentId);

        $spanObj->setLogEvent($this->logEvent(1, $level, '', $data));

        $unaryReq = new Proto\UnaryRequest();
        $unaryReq->getSpanData($spanObj);
        $unaryReq->setAuthToken($this->headers[CommonConstants::X_AUTH_TOKEN]);

        $res = $this->client->uploadSpan($unaryReq);
        echo $res->getSuccess();
        print_R($res);
        die;
    }

    public function endSpan($serviceName, $serviceLocation, $spanId, $parentId, $data = [], $level = 1) {
        $traceObj = new Proto\Trace();
        $traceObj->setTraceId($this->traceId);

        $spanObj = new Proto\Span();
        $spanObj->setTraceContext($this->traceId);
        $spanObj->setSpanId($spanId);
        $spanObj->setTimestamp(strtotime(date('Y-m-d H:i:s')));
        $spanObj->setServiceName($serviceName);
        $spanObj->setEventLocation($serviceLocation);
        $spanObj->setParentSpanId($parentId);

        $spanObj->setLogEvent($this->logEvent(1, $level, '', $data));

        $unaryReq = new Proto\UnaryRequest();
        $unaryReq->getSpanData($spanObj);
        $unaryReq->setAuthToken($this->headers[CommonConstants::X_AUTH_TOKEN]);

        $res = $this->client->uploadSpan($unaryReq);
        echo $res->getSuccess();
        print_R($res);
        die;
    }

}

<?php

use Com\Thapovan\Orion\Proto;

/**
 * Description of Tracer.php
 *
 * @author DJ
 */
class Tracer {

    private $logger = '';
    private $app = '';
    private $traceId = 0;
    private $headers = '';
    private $client = '';
    private $authToken = '';
    private $eventId = 0;
    private $parentId = '';
    private $spanId = '';
    private $serviceName = '';
    private $serviceLocation = '';
    private $metaData = [];
    private $logLevel = 1;
    private $message = '';
    private $spanType = 3; //1 = start, 2 = end, 3 = log span

    public function __construct() {
        $di = Phalcon\DI::getDefault();
        $this->logger = $di->get(CommonConstants::DI_LOGGER);
        $this->app = $di->get(CommonConstants::DI_APP);
        $this->headers = getallheaders();
        $traceId = (isset($this->headers[CommonConstants::X_TRACE_ID]) && !empty($this->headers[CommonConstants::X_TRACE_ID])) ? $this->headers[CommonConstants::X_TRACE_ID] : '';
        $this->getTraceId($traceId);

        $_SESSION[CommonConstants::X_PARENT_ID] = (isset($this->headers[CommonConstants::X_PARENT_ID]) && !empty($this->headers[CommonConstants::X_PARENT_ID])) ? $this->headers[CommonConstants::X_PARENT_ID] : '2';

        $this->authToken = (isset($this->headers[CommonConstants::X_AUTH_TOKEN_NEW]) && !empty($this->headers[CommonConstants::X_AUTH_TOKEN_NEW])) ? $this->headers[CommonConstants::X_AUTH_TOKEN_NEW] : '';
        $this->authToken = (empty($this->authToken) && isset($this->headers[CommonConstants::X_AUTH_TOKEN]) && !empty($this->headers[CommonConstants::X_AUTH_TOKEN])) ? $this->headers[CommonConstants::X_AUTH_TOKEN] : '8bc278cb-2fb6-413b-add6-8ba39bf830e8';
        //putenv("ENV_ORION_INSTANCE_WITH_PORT = 54.83.197.74:20691");
        //echo (ENV_ORION_INSTANCE_WITH_PORT);
        $this->client = new Proto\TracerClient(ENV_ORION_INSTANCE_WITH_PORT, [
            //$this->client = new Proto\TracerClient("54.83.197.74:20691", [
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

    public function getTraceId($traceId = '') {
        return $this->traceId = (!empty($traceId)) ? $traceId : $this->UUID_V4();
    }

    public function startEvent() {
        $startEventObj = new Proto\StartEvent();
        $startEventObj->setEventId($this->eventId);
        $metaDataArr = $this->getMetaDataArr('START_TRACE');
        $startEventObj->setJsonString(json_encode($metaDataArr));
        return $startEventObj;
    }

    public function endEvent() {
        $endEventObj = new Proto\EndEvent();
        $endEventObj->setEventId($this->eventId);
        $metaDataArr = $this->getMetaDataArr('END_TRACE');
        $endEventObj->setJsonString(json_encode($metaDataArr));
        return $endEventObj;
    }

    public function logEvent() {
        $logEventObj = new Proto\LogEvent();
        $logEventObj->setEventId($this->eventId);
        $logEventObj->setLevel($this->logLevel);
        $logEventObj->setMessage($this->message);
        $logEventObj->setJsonString(json_encode($this->metaData));
        return $logEventObj;
    }

    public function startAPISpan($serviceName, $serviceLocation, $metaData = []) {
        //echo "ParentId:" . $_SESSION[CommonConstants::X_PARENT_ID] . " service:" . $serviceName;
        $this->serviceName = $serviceName;
        $this->serviceLocation = $serviceLocation;
        $this->metaData = $metaData;
        $this->parentId = $_SESSION[CommonConstants::X_PARENT_ID];
        $this->spanId = '';
        $this->spanType = 1;
        $this->logLevel = 1;
        //$_SESSION['lastActivity'] = __FUNCTION__;
        $_SESSION['lastParentId'][] = $this->parentId;
        $spanId = $this->generateSpan();
        $_SESSION[CommonConstants::X_PARENT_ID . 'API'] = $spanId;
        $_SESSION['lastParentId'][] = $spanId;
        return true;
    }

    public function logAPISpan($serviceName, $serviceLocation, $metaData = []) {
        $this->serviceName = $serviceName;
        $this->serviceLocation = $serviceLocation;
        $this->metaData = $metaData;
        $this->parentId = $_SESSION[CommonConstants::X_PARENT_ID];
        $this->spanId = $_SESSION[CommonConstants::X_PARENT_ID . 'API'];
        $this->spanType = 3;
        $this->logLevel = 1;
        //echo "<BR>ParentId:" . $parentId . " service:" . $serviceName . " Spenid:" . $spanId;
        return $this->generateSpan();
    }

    public function endAPISpan($serviceName, $serviceLocation, $metaData = []) {
        $this->serviceName = $serviceName;
        $this->serviceLocation = $serviceLocation;
        $this->metaData = $metaData;
        $this->parentId = $_SESSION[CommonConstants::X_PARENT_ID];
        $this->spanId = $_SESSION[CommonConstants::X_PARENT_ID . 'API'];
        $this->spanType = 2;
        $this->logLevel = 1;
        //$_SESSION['lastActivity'] = __FUNCTION__;
        //echo "<BR>ParentId:" . $parentId . " service:" . $serviceName . " Spenid:" . $spanId;
        return $this->generateSpan();
    }

    public function startModelSpan($serviceName, $serviceLocation, $metaData = []) {
        $this->serviceName = $serviceName;
        $this->serviceLocation = $serviceLocation;
        $this->metaData = $metaData;
        /* if ($_SESSION['lastActivity'] == 'startAPISpan') {// || $_SESSION['lastActivity'] == 'endModelSpan') {
          $this->parentId = $_SESSION[CommonConstants::X_PARENT_ID . 'API'];
          } else {
          //$this->parentId = $_SESSION[CommonConstants::X_PARENT_ID . 'MODEL'][end(array_keys($_SESSION[CommonConstants::X_PARENT_ID . 'MODEL']))];
          $this->parentId = $_SESSION['lastParentId'][end(array_keys($_SESSION[CommonConstants::X_PARENT_ID . 'MODEL']))];
          } */
        $this->parentId = $_SESSION['lastParentId'][end(array_keys($_SESSION['lastParentId']))];
        //$_SESSION['lastActivity'] = __FUNCTION__;
        $this->spanId = '';
        $this->spanType = 1;
        $this->logLevel = 1;
        //echo "<BR>ParentId:" . $parentId . " service:" . $serviceName . " Spenid:0";
        $spanId = $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName] = $this->generateSpan();
        $_SESSION[CommonConstants::X_PARENT_ID . 'MODEL'][] = $spanId;
        $_SESSION[CommonConstants::X_PARENT_ID . 'DB'] = $spanId;
        $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName] = $spanId;
        $_SESSION[CommonConstants::X_PARENT_ID . 'MODEL' . $serviceName] = $this->parentId;
        $_SESSION['lastParentId'][] = $spanId;
        return true;
    }

    public function logModelSpan($serviceName, $serviceLocation, $metaData = []) {
        $this->serviceName = $serviceName;
        $this->serviceLocation = $serviceLocation;
        $this->metaData = $metaData;
        $this->parentId = $_SESSION[CommonConstants::X_PARENT_ID . 'MODEL' . $serviceName]; //$_SESSION[CommonConstants::X_PARENT_ID . 'API'];
        $this->spanId = (isset($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName]) && !empty($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName])) ? $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName] : '';
        $this->spanType = 3;
        $this->logLevel = 1;
        //echo "<BR>ParentId:" . $parentId . " service:" . $serviceName . " Spenid:" . $spanId;
        return $this->generateSpan();
    }

    public function endModelSpan($serviceName, $serviceLocation, $metaData = []) {
        $this->serviceName = $serviceName;
        $this->serviceLocation = $serviceLocation;
        $this->metaData = $metaData;
        $this->parentId = $_SESSION[CommonConstants::X_PARENT_ID . 'MODEL' . $serviceName]; //$_SESSION[CommonConstants::X_PARENT_ID . 'API'];
        $this->spanId = (isset($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName]) && !empty($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName])) ? $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName] : '';
        $this->spanType = 2;
        $this->logLevel = 1;
        //$_SESSION['lastActivity'] = __FUNCTION__;
        unset($_SESSION['lastParentId'][end(array_keys($_SESSION['lastParentId']))]);
        //echo "<BR>ParentId:" . $parentId . " service:" . $serviceName . " Spenid:" . $spanId;
        return $this->generateSpan();
    }

    /* public function generateModelSpan($serviceName, $serviceLocation, $metaData = []) {
      $parentId = $_SESSION[CommonConstants::X_PARENT_ID . 'API'];
      $spanId = (isset($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName]) && !empty($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName])) ? $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName] : 0;
      //echo "<BR>ParentId:" . $parentId . " service:" . $serviceName . " Spenid:" . $spanId;
      $_SESSION[CommonConstants::X_PARENT_ID . 'DB'] = $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName] = $this->generateSpan($serviceName, $serviceLocation, $metaData, $parentId, $spanId);
      return true;
      } */

    public function generateDBSpan($serviceName, $serviceLocation, $metaData = [], $level = 1) {
        $this->serviceName = $serviceName;
        $this->serviceLocation = $serviceLocation;
        $this->metaData = $metaData;
        $this->parentId = $_SESSION[CommonConstants::X_PARENT_ID . 'DB'];
        $this->spanId = (isset($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName . $this->parentId]) && !empty($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName . $this->parentId])) ? $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName . $this->parentId] : '';
        if ($level == 3) {
            $this->spanType = 3;
        } else {
            $this->spanType = (!empty($this->spanId)) ? 2 : 1;
        }
        $this->logLevel = $level;
        //echo "<BR>ParentId:" . $parentId . " service:" . $serviceName . " Spenid:" . $spanId;
        $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName . $this->parentId] = $this->generateSpan();
        return true;
    }

    public function generateQueueSpan($serviceName, $serviceLocation, $metaData = [], $level = 1) {
        $this->serviceName = $serviceName;
        $this->serviceLocation = $serviceLocation;
        $this->metaData = $metaData;
        $this->parentId = $_SESSION[CommonConstants::X_PARENT_ID . 'Q'];
        $this->spanId = (isset($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName . $this->parentId]) && !empty($_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName . $this->parentId])) ? $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName . $this->parentId] : '';
        if ($level == 3) {
            $this->spanType = 3;
        } else {
            $this->spanType = (!empty($this->spanId)) ? 2 : 1;
        }
        $this->logLevel = $level;
        //echo "<BR>ParentId:" . $parentId . " service:" . $serviceName . " Spenid:" . $spanId;
        $_SESSION[CommonConstants::X_PARENT_ID . '_' . $serviceName . $this->parentId] = $this->generateSpan();
        return true;
    }

    public function generateSpan() {
        //print_R($_SESSION);
        $this->metaData = $this->getDefinedVarDetails($this->metaData);
        $this->spanId = (!empty($this->spanId)) ? $this->spanId : $this->getTraceId();
        $spanObj = new Proto\Span();
        $spanObj->setTraceContext($this->getTraceObj());
        $spanObj->setSpanId($this->spanId);
        $spanObj->setTimestamp(microtime(true)); //strtotime(date('Y-m-d H:i:s')));
        $spanObj->setServiceName($this->serviceName);
        $spanObj->setEventLocation($this->serviceLocation);
        $spanObj->setParentSpanId($this->parentId);

        $this->eventId++;
        if ($this->spanType == 1) {
            $spanObj->setStartEvent($this->startEvent());
        } elseif ($this->spanType == 2) {
            $spanObj->setEndEvent($this->endEvent());
        } else {
            $this->message = $this->serviceName;
            $spanObj->setLogEvent($this->logEvent());
        }

        $unaryReq = new Proto\UnaryRequest();
        $unaryReq->setSpanData($spanObj);
        $unaryReq->setAuthToken($this->authToken);

        //echo "<BR>ParentId:" . $this->parentId . " service:" . $this->serviceName . " spanType:" . $this->spanType . " spanId: " . $this->spanId;
        list($res, $status) = $this->client->uploadSpan($unaryReq)->wait();
        if ($status->code > 0) {
            //echo "if--" .$status->details;
            $this->logger->log(\Phalcon\Logger::ERROR, __FUNCTION__ . $status->details . ' : Params - ' . $this->serviceName . ' : ParentId : ' . $this->parentId);
        } else {
            //echo "else--" .$res->getSuccess();
            $this->logger->log(\Phalcon\Logger::INFO, __FUNCTION__ . $res->getSuccess() . ' : Params - ' . $this->serviceName . ' : ParentId : ' . $this->parentId);
        }
        return $this->spanId;
    }

    private function getTraceObj() {
        $traceObj = new Proto\Trace();
        $traceObj->setTraceId($this->traceId);
        return $traceObj;
    }

    public function getDefinedVarDetails($vars = []) {
        if (is_array($vars) && count($vars) > 0) {
            unset($vars['di']);
            unset($vars['this']);
            unset($vars['app']);
            unset($vars['logger']);
            unset($vars['config']);
            unset($vars['commons']);
            unset($vars['mgrCom']);
            unset($vars['redisObj']);
            unset($vars['connection']);
            unset($vars['channel']);
            unset($vars['validator']);
            unset($vars['email']);
            unset($vars['sms']);
        }
        return $vars;
    }

    private function getMetaDataArr($traceType) {
        $metaDataArr['api'] = $this->serviceLocation;
        $metaDataArr['data'] = $this->metaData;
        $metaDataArr['parentId'] = $this->parentId;
        $metaDataArr['signal'] = $traceType;
        $metaDataArr['service']['os'] = PHP_OS;
        $metaDataArr['service']['version'] = PHP_VERSION;
        $metaDataArr['service']['name'] = $this->serviceName;
        $metaDataArr['user']['id'] = $this->authToken;
        $metaDataArr['user']['email'] = '';
        $metaDataArr['http']['method'] = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '');
        $metaDataArr['http']['content_type'] = (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '');
        $metaDataArr['http']['status_code'] = (isset($_SERVER['REDIRECT_STATUS']) ? $_SERVER['REDIRECT_STATUS'] : '');
        $metaDataArr['http']['url'] = $this->serviceLocation; //$_SERVER['REQUEST_URI'];
        $metaDataArr['http']['request']['body'] = (($traceType == 'START_TRACE') ? $this->metaData : '');
        $metaDataArr['http']['request']['ip'] = ((isset($_SERVER["HTTP_CF_CONNECTING_IP"])) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER['REMOTE_ADDR']);
        $metaDataArr['http']['request']['country'] = '';
        $metaDataArr['http']['request']['headers'] = $this->headers;
        $metaDataArr['http']['response']['body'] = (($traceType == 'END_TRACE') ? $this->metaData : '');
        $metaDataArr['http']['response']['content_length'] = '';
        $metaDataArr['http']['response']['headers'] = ((stripos($this->serviceName, 'End Response')) ? $this->app->response->getHeaders() : '');
        return $metaDataArr;
    }

}

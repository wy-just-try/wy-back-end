<?php
namespace app\wy\ao;

use Yii;
use component\qcloud\sms\SmsSender;
use includes\BizErrcode;

class QcloudSms {

    private $appid = 1400023181;
    private $appkey = "4e5fe20368da9e2478e5ac6728752d77";
    private $singleSender;

    const CAPTCHA_TEMPLATE_ID = 8681;
    const NEW_PASSWORD_TEMPLATE_ID = 9520;
    
    private static $instance;

    private function __construct() {
    	$this->singleSender = new SmsSender($this->appid, $this->appkey);
    	Yii::info("QcludSms construct is called");
    }

    public static function getInstance() {
    	if (is_null(self::$instance) || !(self::$instance instanceof self)) {
			self::$instance = new self;
    	}

    	return self::$instance;
    }

    public function __clone() {
    	trigger_error('Clone is not allowed!');
    }

    public function sendSmsWithParams($tel, $templateId, $params) {
    	try {
    		$result = $this->singleSender->sendWithParam("86", $tel, $templateId, $params, "", "", "");
		    //var_dump($result);
	    	$rsp = json_decode($result);
	    	//var_dump($rsp);
	    	if ($rsp->{'result'} == 0) {
	    		Yii::info("Success to send message");
	    		return BizErrcode::ERR_MSG_OK;
	    	} else {
	    		Yii::error("Response: $result");
	    		return BizErrcode::ERR_MSG_FAILED;
	    	}
		}catch (\Exception $e) {
		    Yii::error("Exception occurs in sendSms()");
		    //var_dump($e);
		    return BizErrcode::ERR_MSG_EXCEPTION;
		}
		
    }

  	// 普通单发
    public function sendSms($tel, $content) {
    	try {
    		Yii::info("sendSms $tel, $content");
		    $result = $this->singleSender->send(0, "86", $tel, $content, "", "");
		    //var_dump($result);
	    	$rsp = json_decode($result);
	    	//var_dump($rsp);
	    	if ($rsp->{'result'} == 0) {
	    		Yii::info("Success to send message");
	    		return BizErrcode::ERR_MSG_OK;
	    	} else {
	    		Yii::error("Response: $result");
	    		return BizErrcode::ERR_MSG_FAILED;
	    	}
		}catch (\Exception $e) {
		    Yii::error("Exception occurs in sendSms()");
		    //var_dump($e);
		    return BizErrcode::ERR_MSG_EXCEPTION;
		}
    }
}
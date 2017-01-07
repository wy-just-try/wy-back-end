<?php
namespace app\wy\ao;

use Yii;
use component\qcloud\sms\SmsSender;

class QcloudSms {

    private $appid = 1400023181;
    private $appkey = "4e5fe20368da9e2478e5ac6728752d77";
    private $singleSender;
    
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

  	// 普通单发
    public function sendSms($tel, $content) {
    	try {
    		Yii::info("sendSms $tel, $content");
		    $result = $this->singleSender->send(0, "86", $tel, $content, "", "");
		    //var_dump($result);
	    	$rsp = json_decode($result);
	    	//var_dump($rsp);
		}catch (\Exception $e) {
		    Yii::error("Exception occurs in sendSms()");
		    var_dump($e);
		}
    }
}
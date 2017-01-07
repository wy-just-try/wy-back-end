<?php
namespace app\wy\ao;

use Yii;
use app\wy\ao\QcloudSms;
use includes\BizErrcode;

/**
 * 发送短信
 */
class Massenger {

	private static $instance;

	private $contentFormat = "您的%s为%s，请您于%s分钟内填写。如非本人操作，请忽略本短信";

	private function __construct() {

	}

	public static function getInstance() {
		if (is_null(self::$instance) || !(self::$instance instanceof self)) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * 通过短信发送验证码给用户
	 * @param string $tel 手机号
	 * @param string $captcha 验证码
	 * @return 如果发送成功则返回TRUE, 否则返回FALSE
	*/
	public function sendCaptcha($tel, $captcha) {
		$content = sprintf($this->contentFormat, "短信验证码", $captcha, "2");
		return $this->sendMessage($tel, "$content");
	}

	/**
	 * 通过短信发送新密码给用户
	 * @param string $tel 手机号
	 * @param string $password 新密码
	 * @return 如果发送成功则返回TRUE, 否则返回FALSE
	*/
	public function sendNewPassword($tel, $password) {
		$content = sprintf($this->contentFormat, "新密码", $password, "2");
		return $this->sendMessage($tel, "$content");
	}

	/**
	 * 通过短信发送信息
	 * @param string $tel: 手机号
	 * @param string $content: 要发送的模板内容
	 * @return TRUE: 表示发送成功; FALSE: 表示发送失败
	 */
	private function sendMessage($tel, $content) {
		Yii::info("send $tel massenger: $content");
		$res = FALSE;
		$qcloudSms = QcloudSms::getInstance();
		if (BizErrcode::ERR_MSG_OK != $qcloudSms->sendSms($tel, $content)) {
			Yii::error("Failed to send message: $tel, $content");
			$res = TRUE;
		} else {
			Yii::info("Success to send message: $tel, $content");
			$res = TRUE;
		}

		return $res;
	}
}
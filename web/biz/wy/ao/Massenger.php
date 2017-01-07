<?php
namespace app\wy\ao;

use Yii;
USE app\wy\ao\QcloudSms;

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

	public function sendCaptcha($tel, $captcha) {
		$content = sprintf($this->contentFormat, "短信验证码", $captcha, "2");
		return $this->sendMessage($tel, "$content");
	}

	public function sendNewPassword($tel, $password) {
		$content = sprintf($this->contentFormat, "新密码", $password, "2");
		return $this->sendMessage($tel, "$content");
	}

	/**
	 * 通过短信发送信息
	 * @param string $tel: 手机号
	 * @param string $content: 要发送的内容，例如：验证码、密码
	 * @return TRUE: 表示发送成功; FALSE: 表示发送失败
	 */
	private function sendMessage($tel, $content) {
		Yii::info("send $tel massenger: $content");
		$qcloudSms = QcloudSms::getInstance();
		$qcloudSms->sendSms($tel, $content);
		return TRUE;
	}
}
<?php
namespace app\wy\ao;

use Yii;

/**
 * 发送短信
 */
class Massenger {

	/**
	 * 通过短信发送信息
	 * @param string $message: 提示信息
	 * @param string $content: 要发送的内容，例如：验证码、密码
	 * @return TRUE: 表示发送成功; FALSE: 表示发送失败
	 */
	public function sendMessage($message, $content) {

		Yii::info("Massenger: $message: $content");
		return TRUE;
	}
}
<?php
namespace app\wy\ao;

use Yii;
use app\wy\ao\Massenger;

/**
 * 验证码 
 */
class Captcha {

	const PIC_CAPTCHA = "pic_captcha";
	const MSG_CAPTCHA = "msg_captcha";

	const PIC_CAPTCHA_LEN = 4;
	const MSG_CAPTCHA_LEN = 4;

	public function createPicCaptcha() {

		session_start();
		// 生成随机数
		$picStr = $this->randStr(self::PIC_CAPTCHA_LEN);
		Yii::info("picture captcha: $picStr");

		// 生成图片验证码
		var_dump(gd_info());
		$img_handle = Imagecreate(80, 20);  //图片大小80X20
		$back_color = ImageColorAllocate($img_handle, 255, 255, 255); //背景颜色（白色）
		$txt_color = ImageColorAllocate($img_handle, 0,0, 0);  //文本颜色（黑色）
	    
	    //加入干扰线
		for($i=0;$i<3;$i++)
		{
			$line = ImageColorAllocate($img_handle,rand(0,255),rand(0,255),rand(0,255));
			Imageline($img_handle, rand(0,15), rand(0,15), rand(100,150),rand(10,50), $line);
		}
	    //加入干扰象素
		for($i=0;$i<200;$i++) 
		{
			$randcolor = ImageColorallocate($img_handle,rand(0,255),rand(0,255),rand(0,255));
			Imagesetpixel($img_handle, rand()%100 , rand()%50 , $randcolor);
		}

		Imagefill($img_handle, 0, 0, $back_color);             //填充图片背景色
		ImageString($img_handle, 28, 10, 0, $picStr, $txt_color);//水平填充一行字符串

		ob_clean();   // ob_clean()清空输出缓存区    
		header("Content-type: image/png"); //生成验证码图片    
		Imagepng($img_handle);//显示图片

		// 保存图片验证码到session中
		$_SESSION[self::PIC_CAPTCHA] = $picStr;

		Yii::info("The created picture captcha is $picStr");

		return TRUE;
	}

	public function createMsgCaptcha($tel) {

		session_start();

		// 生成随机数
		$msg = $this->randNumber(self::MSG_CAPTCHA_LEN);
		Yii::info("messanger captcha: $msg");

		// 通过短信发送随机数
		if (!$this->sendMsgCaptcha($tel, $msg)) {
			Yii::error("Failed to send message captcha: $msg");
			return FALSE;
		}

		// 保存短信验证码到session中
		$_SESSION[self::MSG_CAPTCHA] = $msg;

		return TRUE;
	}


	/**
	 * 用来验证图片验证码是否匹配，不管是否匹配成功，此函数在最后都会将session中的图片验证码清空
	 * 前台需要重新请求生成新的图片验证码
	 * @param String $picStr: 用户输入的图片验证码
	 * @return true: 表示图片验证码匹配成功；false：表示图片验证码不匹配
	 */
	public function verifyPicCaptcha($picStr) {

		session_start();

		if (is_string($picStr) && $picStr != null && strlen($picStr) == self::PIC_CAPTCHA_LEN) {

			if (isset($_SESSION[self::PIC_CAPTCHA])) {
				$realPicCaptcha = $_SESSION[self::PIC_CAPTCHA];
				if (strncasecmp($realPicCaptcha, $picStr, self::PIC_CAPTCHA_LEN) == 0) {
				//if ($_SESSION[self::PIC_CAPTCHA] === $picStr) {
					// 需要清除session中保存的图片验证码，前台需要重新请求生成新的验证码
					$_SESSION[self::PIC_CAPTCHA] = null;
					Yii::info("Success to verify picture captcha($picStr, $realPicCaptcha");
					return TRUE;
				} else {
					Yii::trace("The input picture captcha($picStr) does not match the real one($realPicCaptcha)");
				}
			} else {
				Yii::trace('The picture captcha is not created');
			}
		} else {
			Yii::trace("The format of inputting picture captcha is wrong($picStr)");
		}

		// 即使验证失败也需要清除session中保存的图片验证码，前台需要重新请求生成新的验证码
		$_SESSION[self::PIC_CAPTCHA] = null;

		return FALSE;
	}

	/**
 	 * 用来验证短信验证码是否匹配，如果匹配成功，会再清空session中的短信验证码，如果不匹配，不会清空session中的短信验证码
 	 * @param String $msgStr: 用户输入的短信验证码
 	 * @return true: 表示短信验证码匹配；false：表示短信验证码不匹配
	 */
	public function verifyMsgCaptcha($msgStr) {

		if ( is_string($msgStr) && $msgStr != null && strlen($msgStr) == self::MSG_CAPTCHA_LEN) {

			if (isset($_SESSION[self::MSG_CAPTCHA])) {
				$realMsgCaptcha = $_SESSION[SELF::MSG_CAPTCHA];
				if (strncasecmp($realMsgCaptch, $msgStr, self::MSG_CAPTCHA_LEN) == 0) {
				//if ($_SESSION[self::MSG_CAPTCHA] === $msgStr) {
					// 清空session中的短信验证码
					$_SESSION[self::MSG_CAPTCHA] = null;
					Yii::info("Success to verify the message captcha($msgStr, $realMsgCaptcha)");
					return TRUE;
				} else {
					Yii::trace("The message captcha($msgStr) dose not match the real($realMsgCaptcha)");
				}
			} else {
				Yii::trace('The message is not created');
			}
		} else {
			Yii::trace('The input message captcha is wrong');
		}

		return TRUE;
	}

	private function sendMsgCaptcha($tel, $captcha) {
		$massenger = Massenger::getInstance();

		return $massenger->sendCaptcha($tel, $captcha);
	}

	/**
	 * 用来生成0~9（包含0和9）之间的随机数字符串
	 * @param $bits 表示要生成的随机数字符串长度
	 * @return 返回生成的随机数字符串
	 */
	private function randNumber($bits = 4) {
		$str = "";
		for ($i = 0; $i < $bits; $i++) {
			$str .= chr(mt_rand(48, 57));
		}

		return $str;
	}


	/**
	 * 生成一串随机字符串
	 * @param  integer $bits [设置返回随机串字符个数，默认为16]
	 * @return [type]        [返回随机串]
	 */
	private function randStr($bits = 6)
	{
		$str = "";
		for ($i=0; $i<$bits; $i++) {
			$t = mt_rand(1, 3);
			if ($t === 1) {
				$str .= chr(mt_rand(48, 57));
			} else if ($t === 2) {
				$str .= chr(mt_rand(65, 90));
			} else {
				$str .= chr(mt_rand(97, 122));
			}
		}

		return $str;
	}
}
<?php
namespace app\wy\ao;

use Yii;
use yii\base\Behavior;
use includes\BizErrcode;

/**
*
*/
class LoginBehavior extends Behavior
{
	const LOGIN_ACCOUNT = '_user_';	//账户名称cookie
	const LOGIN_TOKEN = 'wy_token';	//登录校验cookie
	const LOGIN_USERNAME = 'username'; // 登录后获取的用户名
	const LOGIN_SESSION_NAME = 'login';	//登录校验的session名称
	const LOGIN_SESSION_TIMEOUT = 'login_timeout';	//过期时间的session名称
	const LOGIN_TIMEOUT = 24*60*60;	//登录token过期时间为24小时 //7200秒

	/**
	 * [loginAccout description]
	 * @return [type] [description]
	 */
	public static function loginAccout()
	{
		return self::LOGIN_ACCOUNT;
	}

	/**
	 * [loginUserName description]
	 * @return [type] [description]
	 */
	public static function loginUserName() {
		return self::LOGIN_USERNAME;
	}

	/**
	 * [loginToken description]
	 * @return [type] [description]
	 */
	public static function loginToken()
	{
		return self::LOGIN_TOKEN;
	}

	/**
	 * [sessName description]
	 * @return [type] [description]
	 */
	public static function sessName()
	{
		return self::LOGIN_SESSION_NAME;
	}

	/**
	 * [sessTimeout description]
	 * @return [type] [description]
	 */
	public static function sessTimeout()
	{
		return self::LOGIN_SESSION_TIMEOUT;
	}

	/**
	 * 生成一串随机字符串
	 * @param  integer $bits [设置返回随机串字符个数，默认为16]
	 * @return [type]        [返回随机串]
	 */
	private function randStr($bits = 16)
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

	/**
	 * 登陆校验接口
	 * @return [type] [返回校验结果]
	 */
	public function checkLogin()
	{
		//session_start();
		$sessionName = session_name();
		Yii::info("Current session name: $sessionName");

		$loginToken = self::loginToken();
		Yii::info("Checking cookie[$loginToke]");
		if (!isset($_COOKIE[self::loginToken()])) {
			Yii::info('Failed to get loginToken cookie');
			return BizErrcode::ERR_CHECKLOGIN_NO_LOGIN;
		}

		$sessName = self::sessName();
		Yii::info("Checking _SESSION[$sessName]");
		if (!isset($_SESSION[self::sessName()])) {
			Yii::info('The session of token is not set');
			return BizErrcode::ERR_CHECKLOGIN_NO_LOGIN;
		}

		$sessTime = self::sessTimeout();
		Yii::info("Checking _SESSION[$sessTime]");
		if (!isset($_SESSION[self::sessTimeout()])) {
			Yii::info('The session of timeout is not set');
			return BizErrcode::ERR_CHECKLOGIN_NO_LOGIN;
		}

		$user_cookie = trim((string)$_COOKIE[self::loginToken()]);
		$s_token = $_SESSION[self::sessName()];
		$s_timeout = $_SESSION[self::sessTimeout()];

		//if ($s_token === md5($_SESSION[self::sessName()])) {
			if ($s_timeout > time()) {
				if (md5(trim((string)$s_token)) === $user_cookie) {
					Yii::info('Success to check login');
					$this->updateSession('timeout');
					return BizErrcode::ERR_CHECKLOGIN_ALREADY_LOGIN;
				} else {
					Yii::info('Failed to check login');
					return BizErrcode::ERR_CHECKLOGIN_FAILED;
				}
			} else {
				Yii::info('The login session token is time out');
				return BizErrcode::ERR_CHECKLOGIN_NO_LOGIN;
			}
		//} else {
		//	Yii::error('登录的token不匹配');
		//	return BizErrcode::CHECKLOGIN_FAIL;
		//}

		return BizErrcode::ERR_CHECKLOGIN_FAILED;

	}

	/**
	 * 更新session校验信息
	 * @param  string $type all: 全部；token: 校验token；timeout: 过期时间；
	 * @return NULL
	 */
	public function updateSession($type = 'all')
	{
		$str = $this->randStr(30);
		if ($type === 'all') {
			$_SESSION[self::sessName()] = $str;
			$_SESSION[self::sessTimeout()] = time() + self::LOGIN_TIMEOUT;
		} elseif ($type === 'token') {
			$_SESSION[self::sessName()] = $str;
		} elseif ($type === 'timeout') {
			$_SESSION[self::sessTimeout()] = time() + self::LOGIN_TIMEOUT;
		} else {
			Yii::error(__FUNCTION__ . ' 调用传参错误');
		}
	}

	/**
	 * 在登录后初始化session和cookie
	 * @param string $username: 登录成功后要显示的用户名
	 * @return NULL
	 */
	public function initSessionAndCookie($userInfo) {

		//session_name(self::sessName());
		//session_start();
		$str = $this->randStr(30);
		$_SESSION[self::sessName()] = $str;
		$_SESSION[self::sessTimeout()] = time() + self::LOGIN_TIMEOUT;
		$_SESSION[self::loginAccout()] = $userInfo['Account'];
		setcookie(self::loginAccout(), $userInfo['Account'], time() + self::LOGIN_TIMEOUT, "/");
		setcookie(self::loginUserName(), $userInfo['UserName'], time() + self::LOGIN_TIMEOUT, "/");
		setcookie(self::loginToken(), md5($str), time() + self::LOGIN_TIMEOUT, "/");
		$sessionName = session_name();
		Yii::info("init session and cookie, Current session name: $sessionName, sessName=$str");
	}

	/**
	 * 在退出登录后，清理session和cookie
	 *
	 */
	public function uninitSessionAndCookie() {
		
		$sessionName = session_name();
		Yii::info("uninit session and cookie, session name: $sessionName");
		$_SESSION[self::sessName()] = NULL;
		$_SESSION[self::sessTimeout()] = NULL;
		setcookie(self::loginAccout());
		setcookie(self::loginToken());

		session_destroy();
	}

	/**
	 * 用来检查图片验证码是否匹配
	 * @return true 表示图片验证码匹配
	 *         false 表示图片验证码不匹配
	 */
	public function verifyPicCaptcha($picCaptcha) {
		return TRUE;
	}
}
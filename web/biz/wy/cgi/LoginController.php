<?php
namespace app\wy\cgi;

use Yii;
use component\controller\RenderController;
use includes\BizErrcode;
use app\wy\dao\LoginDAO;

/**
* 登录系统控制器
*/
class LoginController extends RenderController
{

	public function init()
	{
		parent::init();
	}

	private function _actionRegister()
	{
		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			//echo "key=$key, value=$value"."<br>";
			Yii::info("key=$key, value=$value");
		}

		//创建DAO对象实例
		$LoginDao = new LoginDAO();
		$ret = $LoginDao->register($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error('Failed to register');
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;
	}

	/**
	 * 注册
	 */
	public function actionRegister()
	{
		//echo "enter into actionRegister";
		$ret = $this->_actionRegister();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionLogin() {

		// 在数据库中查看此用户是否注册并比较password是否正确
		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			//echo "key=$key, value=$value"."<br>";
			Yii::info("key=$key, value=$value");
		}

		$loginDao = new LoginDAO();
		$ret = $loginDao->login($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error('Failed to login');
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;
	}

	/**
	 * 登录
	 */
	public function actionLogin() {

		$ret = $this->_actionLogin();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionLogout() {

		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			//echo "key=$key, value=$value"."<br>";
			Yii::info("key=$key, value=$value");
		}

		//
		$loginDao = new LoginDAO();
		$ret = $loginDao->logOut($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error('Failed to logout');
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;

	}

	/**
 	 * 退出登录
	 */
	public function actionLogout() {

		$ret = $this->_actionLogout();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionCheckRegistered() {

		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			//echo "key=$key, value=$value"."<br>";
			Yii::info("key=$key, value=$value");
		}

		$loginDao = new LoginDAO();
		$ret = $loginDao->checkRegistered($input, $output);

		$this->retdata['data'] = $output;

		return $ret;
	}

	/**
	 * 用来检查此用户是否注册
	 */
	public function actionCheckRegistered() {

		$ret = $this->_actionCheckRegistered();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionFindPassword() {

		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			//echo "key=$key, value=$value"."<br>";
			Yii::info("key=$key, value=$value");
		}

		// 
		$loginDao = new LoginDAO();
		$ret = $loginDao->findPassword($input, $output);
		if ($ret != BizErrcode::ERR_OK) {
			Yii::error('Failed to find password');
			return $ret;
		} else {
			Yii::info('Success to find password');
		}

		$this->retdata['data'] = $output;

		return $ret;
	}

	/**
	 * 找回密码
	 */
	public function actionFindPassword() {

		$ret = $this->_actionFindPassword();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionUpdatePasswd() {

		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			//echo "key=$key, value=$value"."<br>";
			Yii::info("key=$key, value=$value");
		}

		$loginDao = new LoginDAO();
		$ret = $loginDao->updatePassword($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error('Failed to update password');
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;
	}

	/**
	 * 修改密码
	 */
	public function actionUpdatePasswd() {

		$ret = $this->_actionUpdatePasswd();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionCheckReLogin() {
		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			Yii::info("input[$key]: $value");
		}

		$loginDao = new LoginDAO();
		$ret = $loginDao->checkLogin($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error("Failed to check login");
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;
	}

	public function actionCheck() {
		$ret = $this->_actionCheckReLogin();

		return $this->renderJson($ret, $this->retdata);
	}
}
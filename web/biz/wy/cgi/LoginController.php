<?php
namespace app\wy\cgi;

use Yii;
use component\controller\RenderController;
use includes\BizErrcode;
use app\wy\ao\LoginBehavior;
use app\wy\dao\LoginDAO;

/**
* 登录系统控制器
*/
class LoginController extends RenderController
{
	public function init()
	{
		parent::init();
		$ret = $this->checkLogin();
		// echo $ret;
		// exit();
	}

	//行为类
	public function behaviors()
	{
		return [
			LoginBehavior::className()
		];
	}

	public function _actionRegister()
	{
		$input = $this->GPValue();

		//创建DAO对象实例
		$LoginDao = new LoginDAO();
		$ret = $LoginDao->register($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error('注册用户失败');
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;
	}

	//注册接口
	public function actionRegister()
	{
		$ret = $this->_actionRegister();

		return $this->renderJson($ret, $this->retdata);
	}
}
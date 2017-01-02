<?php
namespace app\wy\dao;

use Yii;
use includes\BizErrcode;
use component\model\BaseModel;
use app\wy\ao\WeiSiteManager;
use app\wy\ao\LoginBehavior;

class WeiSitesDAO extends BaseModel {

	public function init()
	{
		parent::init();
	}

	//校验场景设置
	public function scenarios()
	{
		$scenarios = parent::scenarios();
		$scenarios['get-all-wei-sites'] = [];
		$scenarios['delete-wei-site'] = ['url'];

		return $scenarios;
	}

	//参数校验规则
	public function rules()
	{
		return [
			[['url'], 'required', 'on' => 'delete-wei-site'],
		];
	}

	//对象属性
	public function attributes()
	{
		return [
			'url',
		];
	}

	public function selfAttributes()
	{
		return [];
	}

	public function defaultVals()
	{
		return [
			'url'   	=>  '',
		];
	}

	/**
	 * 用来检查输入的参数是否合法
	 * @param string $scenario 要检查的场景
	 * @param array $input 要检查的参数数组
	 * @return BizErrCode::ERR_OK: 表示参数合法
	 *         BizErrCode::ERR_PARAM: 表示参数不合法
	 */
	private function checkInputParameters($scenario, $input) {
		$this->setScenario($scenario);
		$this->load($input, '');
		$this->setDefaultVal();
		if (!$this->validate()) {
			return BizErrcode::ERR_PARAM;
		}

		return BizErrcode::ERR_OK;
	}

	public function getAllWeiSites($input, &$output = []) {
		if ($this->checkInputParameters('get-all-wei-sites', $input) != BizErrcode::ERR_OK) {
			Yii::error('Parameters to get all wei-sites info are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('This user does not login. Please login firstly');
			//return BizErrcode::ERR_NOLOGIN;
		}

		// 获取账户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$weiSiteMgr = WeiSiteManager::getInstance();
		$ret = $weiSiteMgr->getAllWeiSites($account);
		if (FALSE == $ret) {
			Yii::error("Failed to get all of wei sites info of the $account");
			return BizErrcode::ERR_FAILED;
		}

		$output = $ret;

		return BizErrcode::ERR_OK;
	}

	public function deleteWeiSite($input, &$output = []) {
		if ($this->checkInputParameters('delete-wei-site', $input) != BizErrcode::ERR_OK) {
			Yii::error('Parameters to delete wei-site are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('User does not login. Please login firstly');
			//return BizErrcode::ERR_NOLOGIN;
		}

		// 获取账户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$url = $input['url'];
		$weiSiteMgr = WeiSiteManager::getInstance();
		$ret = $weiSiteMgr->deleteWeiSite($account, $input['url']);
		if (FALSE == $ret) {
			Yii::error("Failed to delete wei site whose short url is $url");
			return BizErrcode::ERR_FAILED;
		}

		$output = $ret;

		return BizErrcode::ERR_OK;
	}
}

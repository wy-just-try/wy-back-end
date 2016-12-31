<?php
namespace app\wy\dao;

use Yii;
use includes\BizErrcode;
use component\model\BaseModel;
use app\wy\ao\PageManager;

class PageDAO extends BaseModel {

	public function init()
	{
		parent::init();
	}

	//校验场景设置
	public function scenarios()
	{
		$scenarios = parent::scenarios();
		$scenarios['gen-page'] = ['title', 'desc', 'content'];
		$scenarios['update-page'] = ['url', 'title', 'desc', 'content'];
		$scenarios['get-page'] = ['url'];
		$scenarios['get-all-pages'] = [];
		$scenarios['delete-page'] = ['url'];

		return $scenarios;
	}

	//参数校验规则
	public function rules()
	{
		return [
			[['title', 'desc', 'content'], 'required', 'on' => 'gen-page'],
			[['url', 'title', 'desc', 'content'], 'required', 'on' => 'update-page'],
			[['url'], 'required', 'on' => 'get-page'],
			[['url'], 'required', 'on' => 'delete-page'],
		];
	}

	//对象属性
	public function attributes()
	{
		return [
			'title',
			'desc',
			'content',
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
			'title'		=>	'',
			'desc'  	=>  '',
			'content'  	=>  '',
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

	public function generatePage($input, &$output = []) {
		if ($this->checkInputParameters('gen-page', $input) != BizErrcode::ERR_OK) {
			Yii::error('Parameters to generate page are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			return BizErrcode::ERR_NOLOGIN;
		}

		// 获取账户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$pageManager = new PageManager;

		// 生成图文页面在服务器上的文件
		$pagePath = $pageManager->createPagePath($account);
		if (is_null($pagePath)) {
			Yii::error("Failed to create $account's page path");
			return BizErrcode::ERR_FAILED;
		}

		if (!$pageManager->writePageContent($pagePath, $content)) {
			Yii::error("Failed to write content to file: $pagePath");
			// It's best to delete the file pointed by pagePath
			if(!$pageManager->deletePageDir($pagePath)) {
				Yii::error("Failed to delete the ($pagePath)'s directory");
				return BizErrcode::ERR_FAILED;
			}
			return BizErrcode::ERR_FAILED;
		}

		// 生成图文页面的原始url链接
		$pageOriginalUrl = $pageManager->createUrl($pagePath);
		if (is_null($pageOriginalUrl)) {
			Yii::error("Failed to create page original url for $pagePath");
			// It's best to delete the file pointed by pagePath
			if(!$pageManager->deletePageDir($pagePath)) {
				Yii::error("Failed to delete the ($pagePath)'s directory");
				return BizErrcode::ERR_FAILED;
			}
			return BizErrcode::ERR_FAILED;
		}

		// 生成图文页面短链接
		$shortUrl = UrlConverter::getInstance()->convertUrl(true, $pageOriginalUrl);
		if (is_null($shortUrl)) {
			Yii::error("Failed to create short url for $pageOriginalUrl");
			// It's best to delete the file pointed by pagePath
			if(!$pageManager->deletePageDir($pagePath)) {
				Yii::error("Failed to delete the ($pagePath)'s directory");
				return BizErrcode::ERR_FAILED;
			}
			return BizErrcode::ERR_FAILED;
		}

		// Insert the page into PageInfo table
		$ret = pageManager->insertPageInfo($account, $pagePath, $input['title'], $input['desc'], $pageOriginalUrl, $shortUrl);
		if ($ret == FALSE) {
			Yii::error("Failed to insert page into database");
			// It's best to delete the file pointed by pagePath
			if(!$pageManager->deletePageDir($pagePath)) {
				Yii::error("Failed to delete the ($pagePath)'s directory");
				return BizErrcode::ERR_FAILED;
			}
			return BizErrcode::ERR_FAILED;
		}


		return BizErrcode::ERR_OK;
	}

	public function updatePage($input, &$output = []) {

		if ($this->checkInputParameters('update-page', $input) != BizErrcode::ERR_OK) {
			Yii::error('Parameters to uypdate page are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			return BizErrcode::ERR_NOLOGIN;
		}

		// 获取账户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$pageManager = new PageManager;

		// get the page path
		$url = $input['url'];
		$pagePath = $pageManager->resolveUrl($input['url']);
		if (is_null($pagePath)) {
			Yii::error("Failed to get page file path by url($url)");
			return BizErrcode::ERR_FAILED;
		}

		// copy the content to the file
		if (!$pageManager->writePageContent($pagePath, $input['content'])) {
			Yii::error("Failed to write the content to the page file: $pagePath");
			return BizErrcode::ERR_FAILED;
		}

		// update database
		if (FALSE == $pageManager->updatePageInfo($account, $input['title'], $input['desc'])) {
			Yii::error("Failed to update the page info into database");
			return BizErrcode::ERR_FAILED;
		}

		return BizErrcode::ERR_OK;
	}

	public function getPage($input, &$output = []) {

		if ($this->checkInputParameters('get-page', $input) != BizErrcode::ERR_OK) {
			Yii::error('Parameters to get page are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			return BizErrcode::ERR_NOLOGIN;
		}

		// 获取账户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$url = $input['url'];
		$pageManager = new PageManager;
		$ret = $pageManager->getPageInfo($account, $input['url']);
		if (FALSE == $ret) {
			Yii::error("Failed to get page info whose short url is $url");
			return BizErrcode::ERR_FAILED;
		}

		$output = $ret;

		return BizErrcode::ERR_OK;
	}

	public function getAllPages($input, &$ouput = []) {

		if ($this->checkInputParameters('get-all-pages', $input) != BizErrcode::ERR_OK) {
			Yii::error('Parameters to generate page are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			return BizErrcode::ERR_NOLOGIN;
		}

		// 获取账户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$pageManager = new PageManager;
		if (FALSE == $pageManager->getAllPageInfo($account)) {
			Yii::error("Failed to get the $account's page info");
			return BizErrcode::ERR_FAILED;
		}

		$output = $ret;

		return BizErrcode::ERR_OK;
	}

	public function deletePage($input, &$output = []) {

		if ($this->checkInputParameters('delete-page', $input) != BizErrcode::ERR_OK) {
			Yii::error('Parameters to generate page are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			return BizErrcode::ERR_NOLOGIN;
		}

		// 获取账户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$url = $input['url'];
		$pageManager = new PageManager;
		if (FALSE == $pageManager->deletePageInfo($account, $input['url'])) {
			Yii::error("Failed to delete page info whose short url is $url");
			return BizErrcode::ERR_FAILED;
		}

		return BizErrcode::ERR_OK;
	}
}
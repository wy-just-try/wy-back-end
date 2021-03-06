<?php
namespace app\wy\dao;

use Yii;
use component\model\BaseModel;
use includes\BizErrcode;
use app\wy\ao\LoginBehavior;
use app\wy\ao\TemplateManager;
use app\wy\ao\QCloud;
use component\qcloud\src\QcloudApi\QcloudApi;
use app\wy\ao\WeiSiteManager;

class TemplateDAO extends BaseModel {

	private $template_db = "TempIndex";

	public function init() {
		parent::init();
	}

	public function scenarios() {
		$scenarios = parent::scenarios();
		$scenarios['get-temp-index'] = ['type'];
		$scenarios['gen-temp'] = ['type', 'name'];
		$scenarios['update-template'] = ['weiName', 'weiDesc', 'url', 'content'];
		$scenarios['get-template-url'] = ['url'];

		return $scenarios;
	}

	public function rules() {
		return [
				[['type'], 'required', 'on' => 'get-temp-index'],
				[['type', 'name'], 'required', 'on' => 'gen-temp'],
				[['url','content'], 'required', 'on' => 'update-template'],
				[['url'], 'required', 'on' => 'get-template-url'],
		];
	}

	public function attributes() {
		return [
				'type',
				'name',
				'weiName',
				'weiDesc',
				'url',
				'content',
		];
	}

	public function selfAttributes()
	{
		return [];
	}

	public function defaultVals()
	{
		return [
			'type'		=>	'',
			'name'  	=>  '',
			'weiName'	=>  '',
			'weiDesc'	=>  '',
			'url'		=>  '',
			'content' 	=>  '',
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

	const TEMPLATE_PIC_URL_ROOT_DIR = 'http://wy626.com/template/';

	public function getTemplateIndex($input, &$output = []) {
		if ($this->checkInputParameters('get-temp-index', $input) != BizErrcode::ERR_OK) {
			Yii::error('The parameters of geting template index are wrong');
			return BizErrcode::ERR_GET_TEMPLATE_INDEX_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_CHECKLOGIN_ALREADY_LOGIN) {
			Yii::info('This account does not login');
			return BizErrcode::ERR_NOLOGIN;
		}

		$templateType = $input['type'];
		$tempMgr = TemplateManager::getInstance();
		$ret = $tempMgr->queryAllTemplateInfo($templateType);
		if (FALSE == $ret) {
			Yii::error("Failed to get all template info, template type: $templateType");
			return BizErrcode::ERR_GET_TEMPLATE_INDEX_FAILED;
		}

		// Convert picture path to url
		foreach ($ret as $index => $values) {
			$filename = $values['Id'];
			$title = $values['Title'];
			$description = $values['Description'];
			$picUrl = $values['ShowPic'];
			$values['ShowPic'] = self::TEMPLATE_PIC_URL_ROOT_DIR.$picUrl;
			$picUrl = $values['ShowPic'];

			Yii::info("$filename, $title, $description, $picUrl");
		}

		// Map the ret array to output array
		for ($i=0; $i < count($ret); $i++) {
			$output[$i]['name'] = $ret[$i]['Id'];
			$output[$i]['title'] = $ret[$i]['Title'];
			$output[$i]['desc'] = $ret[$i]['Description'];
			$output[$i]['picUrl'] = self::TEMPLATE_PIC_URL_ROOT_DIR.$ret[$i]['ShowPic'];
		}

		return BizErrcode::ERR_OK;
	}

	/**
	 * 用来生成模板
	 * @param array $input 用户输入的模板类型(1: 首页模板，2: 二级页面模板);模板文件名
	 * @return BizErrCode::ERR_FAILED: 表示调用失败
	 */
	public function genTemp($input, &$output = []) {
		if ($this->checkInputParameters('gen-temp', $input) != BizErrcode::ERR_OK) {
			Yii::error('The parameters of creating template are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_CHECKLOGIN_ALREADY_LOGIN) {
			Yii::info('This account does not login');
			return BizErrcode::ERR_NOLOGIN;
		}

		// 
		$templateType = $input['type'];
		$templateId = $input['name'];

		// 在服务器上从模板库中查找对应的模板文件
		$tempMgr = TemplateManager::getInstance();
		$templateDirPath = $tempMgr->getTemplateDirPath($templateType, $templateId);
		if (!is_string($templateDirPath) || strlen($templateDirPath) == 0) {
			Yii::error('Not found the template corresponding to the template id');
			return BizErrcode::ERR_FAILED;
		}

		Yii::info("template path: $templateDirPath");

		// 获取用户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$shortUrl = null;
		$url = null;

		if ($templateType == 1) {
			// 检查当前用户创建的微网站个数是否已经达到上限，只在生成首页的时候才被调用
			$weiSiteMgr = WeiSiteManager::getInstance();
			if ($weiSiteMgr->enoughWeisites($account)) {
				Yii::info("Enough wei-sites. Cannot create the new wei-site");
				return BizErrcode::ERR_ENOUGH_WEISITES;
			}
			// 生成首页
			list($shortUrl, $url) = $this->create1stPage($account, $templateDirPath);
			if (is_null($url)) {
				Yii::error("Failed to create the first page of this weisite");
				return BizErrcode::ERR_FAILED;
			}
		} else if ($templateType == 2) {
			// 检查首页的url是否传入，用首页的url来获取当前微网站的目录路径
			if (!array_key_exists('url', $input)) {
				Yii::warning("The passed first page url is NULL");
				$firstPageUrl = null;
			} else {
				$firstPageUrl = $input['url'];
			}
			// 检查子网页的url是否传入，
			// 如果传入的值不为null，表示重新生成这个子网页，即之前生成的子网页要被删除
			// 如果传入的值为null，表示此子网页是第一次生成
			if (!array_key_exists('subUrl', $input)) {
				Yii::warning("subUrl is not pass to me");
				$subPageUrl = null;
			} else {
				$subPageUrl = $input['subUrl'];
			}
			list($shortUrl, $url) = $this->create2ndPage($account, $firstPageUrl, $subPageUrl, $templateDirPath);
			if (is_null($url)) {
				Yii::error("Failed to create the sub page of this weisite");
				return BizErrcode::ERR_FAILED;
			}
		} else {
			Yii::error("Wrong template type($templateType)");
			return BizErrcode::ERR_FAILED;
		}

		$output['destUrl'] = $shortUrl;
		$output['originUrl'] = $url;

		return BizErrcode::ERR_OK;
	}

	/**
	 * 生成微网站首页
	 * @param string $account 创建微网站的账户名
	 * @param string $templateDirPath 创建微网站首页用到的模板文件夹在服务器上的绝对路径
	 * @return 如果成功，则返回微网站首页的短链接和原始链接；否则返回null
	*/
	private function create1stPage($account, $templateDirPath) {
		// 创建微网站首页目录
		$weiSiteMgr = WeiSiteManager::getInstance();
		$pageDir = $weiSiteMgr->create1stPageDir($account);
		if (is_null($pageDir)) {
			Yii::error("Failed to create the first page of this account($account)'s weisite");
			return null;
		}

		// 将首页用到的模板文件都copy到首页的目录中
		$pagePath = $weiSiteMgr->copyTemplate($templateDirPath, $pageDir);
		if (is_null($pagePath)) {
			Yii::error("Failed to copy the directory from $templatePath to $pageDir");
			return null;
		}

		// to-do，这里传入的参数，应该是$templateDirPath+{html file name}
		// 生成微网站首页的访问链接
		$pageUrl = $weiSiteMgr->createPageUrl($pagePath);
		if (is_null($pageUrl)) {
			Yii::error("Failed to create the weisite's url corresponding to the path($pageDir)");
			return null;
		}

		// 生成访问的链接的短链接
		$shortPageUrl = $weiSiteMgr->createShortUrl($pageUrl);
		if (is_null($shortPageUrl)) {
			Yii::error("Failed to create the first page's short url corresponding to the url($pageUrl)");
			return null;
		}

		// to-do，这里传入的$templatePath应该是首页html文件的路径
		// 将微网站信息插入到数据库中
		$ret = $weiSiteMgr->insertWeiSite($account, $pagePath, '微网站', $pageUrl, $shortPageUrl);
		if (FALSE == $ret) {
			Yii::error("Failed to insert this weisite to database");
			return null;
		}

		return [$shortPageUrl, $pageUrl];
	}

	/**
	 * 创建微网站的二级页面时，我们认为此二级页面对应的微网站就是此用户最新的微网站
	 * 所以必须保证:
	 * 1. 二级页面的生成，必须先生成一级页而
	 * 2. 当前用户最新的微网站目录下，已经创建首页，并且没有二组页面
	 * @param string $account 创建子网页的用户名
	 * @param string $firstPageUrl 创建子网页对应的首页的原始链接
	 * @param string $subPageUrl 创建子网页的url，如果subPageUrl为空，表示要新建子网页；如果subPageUrl不为空，表示更新子网页
	 * @param string $templateDirPath 创建子网页用到的模板文件夹路径
	 * @return 如果成功返回子网页的短链接和原始链接
	*/
	private function create2ndPage($account, $firstPageUrl, $subPageUrl, $templateDirPath) {

		// 创建微网站二级页面目录
		$weiSiteMgr = WeiSiteManager::getInstance();
		if (is_null($subPageUrl)) {
			$pageDir = $weiSiteMgr->create2ndPageDir($account, $firstPageUrl);
			if (is_null($pageDir)) {
				Yii::error("Failed to create the 2nd page's directory of the account($account)'s weisite");
				return null;
			}
		} else {
			$pageDir = $weiSiteMgr->getWeiSitePageDirByUrl($subPageUrl);
			if (is_null($pageDir)) {
				Yii::error("Failed to get the 2nd page's directory of the account($account)'s weisite");
				return null;
			}
		}

		// copy模板到微网站二级页面的目录中
		$pagePath = $weiSiteMgr->copyTemplate($templateDirPath, $pageDir);
		if (is_null($pagePath)) {
			Yii::error("Failed to copy the directory from $templatePath to $pageDir");
			return null;
		}
		// 生成微网站二级页面的访问链接
		$pageUrl = $weiSiteMgr->createPageUrl($pagePath);
		if (is_null($pageUrl)) {
			Yii::error("Failed to create the sub page's url corresponding to the path($pagePath)");
			return null;
		}
		// 生成短链接
		$shortPageUrl = $weiSiteMgr->createShortUrl($pageUrl);
		if (is_null($shortPageUrl)) {
			Yii::error("Failed to create the sub page's short url corresponding to the url($pageUrl)");
			return null;
		}

		return [null, $pageUrl];
	}

	public function updateTemplate($input, &$output = []) {

		if ($this->checkInputParameters('update-template', $input) != BizErrcode::ERR_OK) {
			Yii::error('The parameters of updating template are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_CHECKLOGIN_ALREADY_LOGIN) {
			Yii::info('This account does not login');
			return BizErrcode::ERR_NOLOGIN;
		}

		foreach ($input as $key => $value) {
			if($key == "weiName") {
				$weiName = $input['weiName'];
			} else if($key == 'weiDesc') {
				$weiDesc = $input['weiDesc'];
			} else if ($key == 'url') {
				$url = $input['url'];
			}
		}

		// 获取用户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		// 检查是否为首页
		if (isset($weiName) && isset($weiDesc)) {
			// 
			$ret = $this->update1stPage($account, $input);
		} else {
			$ret = $this->update2ndPage($account, $input);
		}

		return $ret;
	}

	private function update1stPage($account, $input) {

		// 在数据库中更新微网站信息
		$weiSiteMgr = WeiSiteManager::getInstance();
		if (BizErrcode::ERR_OK != $weiSiteMgr->updateWeiSiteInfo($account, $input['weiName'], $input['weiDesc'], $input['url'])) {
			Yii::error("Failed to update the weisite information in database");
			return BizErrcode::ERR_FAILED;
		}

		// 通过短链接从数据库中查找微网站首页在服务器上路径
		$pagePath = $weiSiteMgr->get1stPagePath($account, $input['url']);
		if (is_null($pagePath)) {
			Yii::error("Failed to get the first page's path of this weisite");
			return BizErrcode::ERR_FAILED;
		}

		// 
		$content = rawurldecode($input['content']);

		// 处理过后的content，会被保存到用户访问的网页和编辑使用的网页中
		$content = $this->preHandlePageContent($content);

		// 将原始的content保存起来
		$ret = $weiSiteMgr->saveContent($pagePath, $content);
		if (FALSE == $ret) {
			Yii::error("Failed to save the original content to $pagePath");
			return BizErrcode::ERR_FAILED;
		}

		// 修改传递进来的网页内容
		$content = $this->handlePageContent($content);
		if (is_null($content)) {
			Yii::error("Failed to handle the first page's content");
			return BizErrcode::ERR_FAILED;
		}

		// 将处理后的网页内容写到相应的文件中
		if (false == $this->updatePageContent($content, $pagePath)) {
			Yii::error("Failed to update the first page's content");
			return BizErrcode::ERR_FAILED;
		}

		return BizErrcode::ERR_OK;
	}

	private function preHandlePageContent($content) {
		$newContent = $content;

		/** 删掉<head>里面的script标签
		* <script charset="utf-8" async="" src="//wy626.com/js/editZone.js?1"></script>
		*/
		$pattern = '/<script\s{1,}charset="utf-8"\s{1,}async=""\s{1,}src="\/\/wy626\.com\/js\/editZone\.js\?\d{1,}"><\/script>/';
		$newContent = preg_replace($pattern, "", $newContent);

		/** 删掉<head>里面的script标签
		* <script charset="utf-8" async="" src="//wy626.com/js/tools/jquery-3.1.1.min.js"></script>
		*/
		$pattern = '/<script\s{1,}charset="utf-8"\s{1,}async=""\s{1,}src="\/\/wy626.com\/js\/tools\/jquery-\d{1}\.\d{1}\.\d{1}\.min\.js"><\/script>/';
		$newContent = preg_replace($pattern, "", $newContent);
		
		/**
		* 删除wy-active, 已在前端完成
		*/
		// $pattern = '/wy-active/';
		// $newContent = preg_replace($pattern, "", $content);
		// Yii::info("removed wy-active: $newContent");

		return $newContent;
	}

	private function handlePageContent($content) {
		// 删除<!-- START editZonejs -->与<!-- END editZonejs -->之间的内容
		$pattern = '/(<!-- START editZonejs -->)([\s\S]*)(<!-- END editZonejs -->)/';
		Yii::info("Content: $content");
		$newContent = preg_replace($pattern, "", $content);
		Yii::info("newContent: $newContent");


		// 删除所有class中的wy-edit开始的类
		//$pattern = '/(\s?class=")([\s\S]*)wy-edit[-|\s]1/';
		//$content = preg_replace($newContent, " ", $newContent);
		return $newContent;
	}

	private function updatePageContent($content, $pagePath) {
		//打开文件
		$handle = fopen($pagePath, "w");
		if (FALSE == $handle) {
			Yii::error("Failed to open file($pagePath)");
			return false;
		}

		$ret = fwrite($handle, $content);
		if (FALSE == $ret) {
			Yii::error("Failed to write file($pagePath)");
			if (FALSE == fclose($handle)) {
				Yii::error("Failed to close file($pagePath)");
				return false;
			}
			return false;
		}

		if (FALSE == fclose($handle)) {
			Yii::error("Failed to close file($pagePath)");
			return false;
		}

		return true;
	}

	private function update2ndPage($account, $input) {
		
		$weiSiteMgr = WeiSiteManager::getInstance();
		// 通过链接子网页在服务器上路径
		$pagePath = $weiSiteMgr->get2ndPagePath($account, $input['url']);
		if (is_null($pagePath)) {
			Yii::error("Failed to get the first page's path of this weisite");
			return BizErrcode::ERR_FAILED;
		}

		$content = rawurldecode($input['content']);

		$content = $this->preHandlePageContent($content);

		// 将原始的content保存起来
		$ret = $weiSiteMgr->saveContent($pagePath, $content);
		if (FALSE == $ret) {
			Yii::error("Failed to save the original content to $pagePath");
			return BizErrcode::ERR_FAILED;
		}

		// 修改传递进来的网页内容
		$content = $this->handlePageContent($content);
		if (is_null($content)) {
			Yii::error("Failed to handle the sub page's content");
			return BizErrcode::ERR_FAILED;
		}

		// 将处理后的网页内容写到相应的文件中
		if (false == $this->updatePageContent($content, $pagePath)) {
			Yii::error("Failed to update the $pagePath content");
			return BizErrcode::ERR_FAILED;
		}

		return BizErrcode::ERR_OK;
	}

	public function getTemplateUrl($input, &$output = []) {
		if ($this->checkInputParameters('get-template-url', $input) != BizErrcode::ERR_OK) {
			Yii::error('The parameters of getting template url are wrong');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_CHECKLOGIN_ALREADY_LOGIN) {
			Yii::info('This account does not login');
			return BizErrcode::ERR_FAILED;
		}

		$url = $input['url'];

		// 获取用户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$weiSiteMgr = WeiSiteManager::getInstance();
		if ($weiSiteMgr->isOriginalUrl($url)) {
			Yii::info("The sub page url($url) is passed in");
			$pagePath = $weiSiteMgr->get2ndPagePath($account, $input['url']);
			if (is_null($pagePath)) {
				Yii::error("Failed to get the sub page's path of this weisite");
				return BizErrcode::ERR_FAILED;
			}
		} else {
			Yii::info("The first page's url($url) is passed in");
			$pagePath = $weiSiteMgr->get1stPagePath($account, $input['url']);
			if (is_null($pagePath)) {
				Yii::error("Failed to get the first page's path of this weisite");
				return BizErrcode::ERR_FAILED;
			}
		}

		$editablePagePath = $weiSiteMgr->getEditablePagePath($pagePath);
		if (FALSE == $editablePagePath) {
			Yii::error("Cannot get editable page path: $pagePath");
			return BizErrcode::ERR_FAILED;
		}	

		$editablePageUrl = $weiSiteMgr->createPageUrl($editablePagePath);
		if (is_null($editablePageUrl)) {
			Yii::error("Failed to create the editable page's url corresponding to the path($editablePagePath)");
			return BizErrcode::ERR_FAILED;
		}
		$output['tempUrl'] = $editablePageUrl;
		
		if (!$weiSiteMgr->isOriginalUrl($url)) {
			list($weiName, $weiDesc, $originUrl) = $weiSiteMgr->getWeiSiteInfoByShortUrl($account, $url);

			$output['desc'] = $weiDesc;
			$output['weiName'] = $weiName;
		}
		return BizErrcode::ERR_OK;
	}
}
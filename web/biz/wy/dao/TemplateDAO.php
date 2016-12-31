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
		$scenarios['get-template-index'] = ['url'];

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

	private function queryTempIndexByType() {
		$sql = "SELECT Id, Title, Description, ShowPic from $this->template_db where Type=:type";
		$params[':type'] = $this->type;

		return [$sql, $params];
	}

	public function getTemplateIndex($input, &$output = []) {
		if ($this->checkInputParameters('get-temp-index', $input) != BizErrcode::ERR_OK) {
			Yii::error('获取模板索引传入的参数错误');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			return BizErrcode::ERR_NOLOGIN;
		}

		// Fetch the user's account, password and username from db
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->queryTempIndexByType();
		Yii::trace("query sql: $sql");
		$ret = $db_handler->getAll($sql, $params);
		if (!is_array($ret)) {
			Yii::error("模板索引为空");
			return BizErrcode::ERR_FAILED;
		} elseif (count($ret) == 0) {
			Yii::error("获取到的模板索引为空");
			return BizErrcode::ERR_FAILED;
		}

		foreach ($ret as $index => $values) {
			$filename = $values['Id'];
			$title = $values['Title'];
			$description = $values['Description'];
			$picUrl = $values['ShowPic'];
			Yii::info("$filename, $title, $description, $picUrl");
		}

		$output = $ret;

		return BizErrcode::ERR_OK;
	}

	/**
	 * 用来生成模板
	 * @param array $input 用户输入的模板类型(1: 首页模板，2: 二级页面模板);模板文件名
	 * @return BizErrCode::ERR_FAILED: 表示调用失败
	 */
	public function genTemp($input, &$output = []) {
		if ($this->checkInputParameters('gen-temp', $input) != BizErrcode::ERR_OK) {
			Yii::error('生成模板传入的参数错误');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			//return BizErrcode::ERR_NOLOGIN;
		}

		// 
		$templateType = $input['type'];
		$templateId = $input['name'];

		// 在服务器上从模板库中查找对应的模板文件
		$tempMgr = TemplateManager::getInstance();
		$templateDirPath = $tempMgr->getTemplateDirPath($templateType, $templateId);
		if (!is_string($templateDirPath) || strlen($templateDirPath) == 0) {
			Yii::error('未找到模板ID对应的模板文件');
			return BizErrcode::ERR_FAILED;
		}

		Yii::info("template path: $templateDirPath");

		// 创建此用户的微网站目录
		// 获取用户名
		if (isset($_SESSION[$loginBehavior::sessName()])) {
			$account = $_SESSION[$loginBehavior->loginAccout()];
		} else {
			$account = "kfc";
		}

		$url = null;

		if ($templateType == 1) {
			$url = $this->create1stPage($account, $templateDirPath);
			if (is_null($url)) {
				Yii::error("生成微网站首页失败");
				return BizErrcode::ERR_FAILED;
			}
		} else if ($templateType == 2) {
			$url = $this->create2ndPage($account, $templateDirPath);
			if (is_null($url)) {
				Yii::error("生成微网站二级页面失败");
				return BizErrcode::ERR_FAILED;
			}
		} else {
			Yii::error("错误的模板类型($templateType)");
			return BizErrcode::ERR_FAILED;
		}

		$output['destUrl'] = $url;

		return BizErrcode::ERR_OK;
	}

	/**
	 * 生成微网站首页
	 * @param string $account 创建微网站的账户名
	 * @param string $templateDirPath 创建微网站首页用到的模板文件夹路径
	 * @return 如果成功，则返回微网站首页的短链接；否则返回null
	*/
	private function create1stPage($account, $templateDirPath) {
		// 创建微网站首页目录
		$weiSiteMgr = WeiSiteManager::getInstance();
		$pageDir = $weiSiteMgr->create1stPageDir($account);
		if (is_null($pageDir)) {
			Yii::error("创建用户($account)的微网站首页目录失败");
			return null;
		}

		// to-do
		// 这里的$templateDirPath应该是模板目录
		// copy是将这个目录下的html和css文件复制到此用户的微网站目录中
		// copy模板到微网站首页目录中
		//if (!copy($templateDirPath, $pageDir.basename($templateDirPath))){
		//	Yii::error("复制$templatePath到$pageDir目录失败");
		//	return null;
		//}
		$pagePath = $weiSiteMgr->copyTemplate($templateDirPath, $pageDir);
		if (is_null($pagePath)) {
			Yii::error("复制$templatePath到$pageDir目录失败");
			return null;
		}

		// to-do，这里传入的参数，应该是$templateDirPath+{html file name}
		// 生成微网站首页的访问链接
		$pageUrl = $weiSiteMgr->createPageUrl($pagePath);
		if (is_null($pageUrl)) {
			Yii::error("生成微网站首页的url失败: $pageDir");
			return null;
		}

		// 生成访问的链接的短链接
		$shortPageUrl = $weiSiteMgr->createShortUrl($pageUrl);
		if (is_null($shortPageUrl)) {
			Yii::error("生成微网站首页短链接失败: $pageUrl");
			return null;
		}

		// to-do，这里传入的$templatePath应该是首页html文件的路径
		// 将微网站信息插入到数据库中
		$ret = $weiSiteMgr->insertWeiSite($account, $pagePath, $pageUrl, $shortPageUrl);
		if (FALSE == $ret) {
			Yii::error("将微网站插入到数据库出错");
			return null;
		}

		return $shortPageUrl;
	}

	/**
	 * 创建微网站的二级页面时，我们认为此二级页面对应的微网站就是此用户最新的微网站
	 * 所以必须保证:
	 * 1. 二级页面的生成，必须先生成一级页而
	 * 2. 当前用户最新的微网站目录下，已经创建首页，并且没有二组页面
	*/
	private function create2ndPage($account, $templteDirPath) {

		// 创建微网站二级页面目录
		$weiSiteMgr = WeiSiteManager::getInstance();
		$pageDir = $weiSiteMgr->create2ndPageDir($account);
		if (is_null($pageDir)) {
			Yii::error("创建用户($account)的微网站首页目录失败");
			return null;
		}

		// copy模板到微网站首页目录中
		$pagePath = $weiSiteMgr->copyTemplate($templateDirPath, $pageDir);
		if (is_null($pagePath)) {
			Yii::error("复制$templatePath到$pageDir目录失败");
			return null;
		}
		//if (!copy($templteDirPath, $pageDir.basename($templteDirPath))){
		//	Yii::error("复制$templatePath到$pageDir目录失败");
		//	return null;
		//}

		// 生成微网站首页的访问链接
		$pageUrl = $weiSiteMgr->createPageUrl($pagePath);
		if (is_null($pageUrl)) {
			Yii::error("生成微网站子网页的url失败: $pagePath");
			return null;
		}

		return $pageUrl;
	}

	private function testQcloudApi() {

	    $config = array('SecretId'       => 'AKIDIzpZFhyJMCqIYcKEpJcmsmU4SdfXJbWG',
		                'SecretKey'      => '31xkwztcsyyY0GKCvSs68mBVdZWIsFSo',
		                'RequestMethod'  => 'GET',
		                'DefaultRegion'  => 'gz');

		$cvm = QcloudApi::load(QcloudApi::MODULE_CVM, $config);

		$package = array('offset' => 0, 'limit' => 3);

		$a = $cvm->DescribeInstances($package);
		// $a = $cvm->generateUrl('DescribeInstances', $package);

		if ($a === false) {
		    $error = $cvm->getError();
		    echo "Error code:" . $error->getCode() . ".\n";
		    echo "message:" . $error->getMessage() . ".\n";
		    echo "ext:" . var_export($error->getExt(), true) . ".\n";
		} else {
		    //var_dump($a);
		}
	}

	public function updateTemplate($input, &$output = []) {

		if ($this->checkInputParameters('update-template', $input) != BizErrcode::ERR_OK) {
			Yii::error('更新模板传入的参数错误');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			//return BizErrcode::ERR_NOLOGIN;
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

	private $testContent = '<!DOCTYPE html>
<html>

<head>
	<!--#include virtual="/page/publicTemplate.shtml" -->
    <link href="//wy626.com/template/mod01/mod01.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div id="wrap" class="wy-edit wy-edit-img"></div>
    <div class="modbgd">
        <a href="javascript:;" class="wy-edit wy-edit-link">
            <div class="menu clr wy-edit-title">
                <img src="//wy626.com/template/mod01/arrow.png">菜单1
            </div>
        </a>
        <a href="javascript:;" class="wy-edit wy-edit-link">
            <div class="menu clr wy-edit-title">
                <img src="//wy626.com/template/mod01/arrow.png">菜单2
            </div>
        </a>
        <a href="javascript:;" class="wy-edit wy-edit-link">
            <div class="menu clr wy-edit-title">
                <img src="//wy626.com/template/mod01/arrow.png">菜单3
            </div>
        </a>
        <a href="javascript:;" class="wy-edit wy-edit-link">
            <div class="menu clr wy-edit-title">
                <img src="//wy626.com/template/mod01/arrow.png">菜单4
            </div>
        </a>
        <a href="javascript:;" class="wy-edit wy-edit-link">
            <div class="menu clr wy-edit-title">
                <img src="//wy626.com/template/mod01/arrow.png">菜单5
            </div>
        </a>
    </div>

    <!-- START editZonejs -->
    <!--#include virtual="/js/ssi/editZoneEntra.shtml" -->
    <!-- END editZonejs -->
</body>

</html>';

	// http://wy626.com/weisites/kfc/weisite_3/1st/322915.html
	private $testShortUrl = "http://t.cn/RI81Cee";

	private function update1stPage($account, $input) {

		// 在数据库中更新微网站信息
		$weiSiteMgr = WeiSiteManager::getInstance();
		if (BizErrcode::ERR_OK != $weiSiteMgr->updateWeiSiteInfo($account, $input['weiName'], $input['weiDesc'], $input['url'])) {
			Yii::error("在数据库中更新微网站信息失败");
			return BizErrcode::ERR_FAILED;
		}

		// 通过短链接从数据库中查找微网站首页在服务器上路径
		$pagePath = $weiSiteMgr->get1stPagePath($account, $input['url']);
		if (is_null($pagePath)) {
			Yii::error("获取微网站首页路径出错");
			return BizErrcode::ERR_FAILED;
		}

		// 修改传递进来的网页内容
		$content = $this->handlePageContent($input['content']);
		if (is_null($content)) {
			Yii::error("处理首页内容出错");
			return BizErrcode::ERR_FAILED;
		}

		// 将处理后的网页内容写到相应的文件中
		if (false == $this->updatePageContent($content, $pagePath)) {
			Yii::error("更新$pagePath的内容出错");
			return BizErrcode::ERR_FAILED;
		}

		return BizErrcode::ERR_OK;
	}

	private function handlePageContent($content) {
		// 删除<!-- START editZonejs -->与<!-- END editZonejs -->之间的内容
		$pattern = '/(<!-- START editZonejs -->)([\s\S]*)(<!-- END editZonejs -->)/';
		// 从后往前查找<!-- END editZone.js -->
		$newContent = preg_replace($pattern, "", $this->testContent);


		// 从后往前查找<!-- START editZone.js -->


		// 删除所有class中的wy-edit开始的类
		//$pattern = '/(\s?class=")([\s\S]*)wy-edit[-|\s]1/';
		//$content = preg_replace($newContent, " ", $newContent);
		return $newContent;
	}

	private function updatePageContent($content, $pagePath) {
		//打开文件
		$handle = fopen($pagePath, "w");
		if (FALSE == $handle) {
			Yii::error("打开文件$pagePath失败");
			return false;
		}

		$ret = fwrite($handle, $content);
		if (FALSE == $ret) {
			Yii::error("写文件$pagePath失败");
			if (FALSE == fclose($handle)) {
				Yii::error("关闭文件$pagePath失败");
				return false;
			}
			return false;
		}

		if (FALSE == fclose($handle)) {
			Yii::error("关闭文件$pagePath失败");
			return false;
		}

		return true;
	}

	private function update2ndPage($account, $input) {
		// 通过链接子网页在服务器上路径
		$pagePath = $weiSiteMgr->get2ndPagePath($account, $input['url']);
		if (is_null($pagePath)) {
			Yii::error("获取微网站首页路径出错");
			return BizErrcode::ERR_FAILED;
		}

		// 修改传递进来的网页内容
		$content = $this->handlePageContent($input['content']);
		if (is_null($content)) {
			Yii::error("处理首页内容出错");
			return BizErrcode::ERR_FAILED;
		}

		// 将处理后的网页内容写到相应的文件中
		if (false == $this->updatePageContent($content, $pagePath)) {
			Yii::error("更新$pagePath的内容出错");
			return BizErrcode::ERR_FAILED;
		}

		return BizErrcode::ERR_OK;
	}

	public function getTemplateUrl($input, &$output = []) {
		if ($this->checkInputParameters('get-template-url', $input) != BizErrcode::ERR_OK) {
			Yii::error('更新模板传入的参数错误');
			return BizErrcode::ERR_FAILED;
		}

		// Should check if this user is login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			//return BizErrcode::ERR_NOLOGIN;
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
			Yii::info("传入的是子网页链接$url");
			$output['tempUrl'] = $url;
		} else {
			Yii::info("传入的是首页链接$url");
			list($weiName, $weiDesc) = $weiSiteMgr->getWeiSiteInfoByShortUrl($account, $url);
			$output['tempUrl'] = $url;
			$output['desc'] = $weiDesc;
			$output['weiName'] = $weiName;
		}

		return BizErrcode::ERR_OK;
	}
}
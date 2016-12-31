<?php
namespace app\wy\ao;

use Yii;
use includes\BizErrcode;
use app\wy\ao\UrlConverter;

class WeiSiteManager {
	
	private $WEI_SITE_DB = 'WeiNetInfo';

	const WEI_SITES_LOCAL_ROOT_DIR = '/Users/apple/software/project/workspace/wy/src/wy-back-end/web/weiSites/';
	const WEI_SITES_URL_ROOT_DIR = 'http://wy626.com/weisites/';
	const FIRST_PAGE_DIR = '1st/';
	const SECOND_PAGE_DIR = '2nd/';


	/**
	** 微网站在服务器上的绝对路径
	** {WEI_SITES_LOCAL_ROOT_DIR}{account name}/{微网站id}/{FIRST_PAGE_DIR|SECOND_PAGE_DIR}/
	** {微网站id} <= weisite_{number}
	*/

	private static $instance;

	private function __construct() {

	}

	public static function getInstance() {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __clone() {
		trigger_error('Clone is not allowed!');
	}

	private function getWeiSiteNumber($weiSitePath) {

	}

	/**
	 * 在微网站目录下为此用户创建他自己的微网站目录
	 * 创建成功后的微网站目录格式：{WEI_SITE_LOCAL_ROOT_DIR}{account name}/weisite_{number}/
	 * @param string $account： 账户名称
	 * @return 如果成功，则返回此用户微网站的目录路径，并且此路径是绝对路径；否则返回null
	*/
	public function getWeiSiteDir($account) {

		//在微网站的目录中查找当前用户的微网站目录是否创建
		$weiSiteUserDir = self::WEI_SITES_LOCAL_ROOT_DIR.$account."/";
		if (!file_exists($weiSiteUserDir) || !is_dir($weiSiteUserDir)) {
			if (!mkdir($weiSiteUserDir, 0777, true)) {
				Yii::error("创建微网站目录$weiSiteDir失败");
				return null;
			}
		}

		//在此用户的微网站目录中查找最新的微网站id
		$lastWeiSiteNumber = 0;
		$weiSiteDirs = scandir($weiSiteUserDir);
		if (!is_array($weiSiteDirs) || count($weiSiteDirs) < 2) {
			Yii::error("读取微网站用户目录$weiSiteUserDir错误");
			return null;
		}
		for ($num = 0; $num < count($weiSiteDirs); $num++) {
			$weiSiteDir = $weiSiteDirs[$num];
			Yii::info("微网站目录: $weiSiteDir");
		}
		sort($weiSiteDirs, SORT_NATURAL);

		for ($num = 0; $num < count($weiSiteDirs); $num++) {
			$weiSiteDir = $weiSiteDirs[$num];
			Yii::info("排序后微网站目录: $weiSiteDir");
		}
		for ($num = count($weiSiteDirs)-1; $num >= 0; $num--) {
			$weisiteDir = $weiSiteDirs[$num];
			Yii::info("检查: $weiSiteUserDir$weisiteDir");
			if (is_dir($weiSiteUserDir.$weiSiteDirs[$num])
				&& strlen($weiSiteDirs[$num]) > strlen("weisite_")
				&& strncmp($weiSiteDirs[$num], "weisite_", strlen("weisite_")) == 0) {

				$lastWeiSiteNumberStr = substr($weiSiteDirs[$num], strlen("weisite_"));
				Yii::info("最新的weisite: $weisiteDir, number: $lastWeiSiteNumberStr");
				if (is_numeric($lastWeiSiteNumberStr)) {
					$lastWeiSiteNumber = intval($lastWeiSiteNumberStr);
					Yii::info("找到了最新的微网站目录: $lastWeiSiteNumber");
					break;
				}
			}
		}

		//创建当前用户的微网站目录
		$lastWeiSitePath = $weiSiteUserDir."weisite_".($lastWeiSiteNumber+1)."/";
		Yii::info("用户($account)的微网站目录$lastWeiSitePath");
		if (!mkdir($lastWeiSitePath, 0777, true)) {
			Yii::error("创建微网站目录失败$lastWeiSitePath");
			return BizErrcode::ERR_FAILED;
		}

		return $lastWeiSitePath;
	}

	/**
     * 生成此用户的微网站首页目录的路径
     * @param string $account 要生成微网站首页账户名
     * @return 如果成功，返回微网站首页在本地的绝对地址
	*/
	public function create1stPageDir($account) {

		$weiSiteDir = $this->getWeiSiteDir($account);
		if (is_null($weiSiteDir)) {
			Yii::error("获取用户$account的微网站目录失败");
			return null;
		}

		// 创建首页目录
		if (!file_exists($weiSiteDir.self::FIRST_PAGE_DIR) || !is_dir($weiSiteDir.self::FIRST_PAGE_DIR)) {
			if (!mkdir($weiSiteDir.self::FIRST_PAGE_DIR, 0777)) {
				Yii::error("创建用户$account的微网站首页目录失败: $weiSiteDir/self::1st_PAGE_DIR");
				return null;
			}
		}

		return $weiSiteDir.self::FIRST_PAGE_DIR;
	}

	private function getLatestAndActiveWeiSiteDir($account) {
		//在微网站的目录中查找当前用户的微网站目录是否创建，此用户的目录必须在创建首页时被创建
		//因此如果没有，则认为调用错误
		$weiSiteUserDir = self::WEI_SITES_LOCAL_ROOT_DIR.$account."/";
		if (!file_exists($weiSiteUserDir) || !is_dir($weiSiteUserDir)) {
			Yii::error("微网站目录$weiSiteDir未创建");
			return null;
		}

		//在此用户的微网站目录中查找最新的并且是正在被编辑的微网站id
		//如果用户的微网站目录中，只有首页目录被创建且里面有文件，并且二级目录没有被创建
		//则认为此微网站是active
		$lastWeiSiteNumber = 0;
		$weiSiteDirs = scandir($weiSiteUserDir);
		if (!is_array($weiSiteDirs) || count($weiSiteDirs) < 2) {
			Yii::error("读取微网站用户目录$weiSiteUserDir错误");
			return null;
		} else if (count($weiSiteDirs) == 2) {
			Yii::error("此用户未创建微网站($weiSiteUserDir)，请先创建微网站首页");
			return null;
		}

		sort($weiSiteDirs, SORT_NATURAL);

		for ($num = count($weiSiteDirs)-1; $num >= 0; $num--) {
			$weisiteDir = $weiSiteDirs[$num];
			Yii::info("检查: $weiSiteUserDir$weisiteDir");
			if (is_dir($weiSiteUserDir.$weiSiteDirs[$num])
				&& strlen($weiSiteDirs[$num]) > strlen("weisite_")
				&& strncmp($weiSiteDirs[$num], "weisite_", strlen("weisite_")) == 0) {

				// 检查首页目录是否已经创建
				if (!file_exists($weiSiteUserDir.$weiSiteDirs[$num]."/".self::FIRST_PAGE_DIR)
					|| !is_dir($weiSiteUserDir.$weiSiteDirs[$num]."/".self::FIRST_PAGE_DIR)) {
					Yii::error("此用户的微网站($weisiteDir)首页目录未创建，请先创建完微网站首页");
					return null;
				} else {
					$files = scandir($weiSiteUserDir.$weiSiteDirs[$num]."/".self::FIRST_PAGE_DIR);
					if (!is_array($files) || count($files) < 3) {
						Yii::error("此用户的微网站($weisiteDir)首页目录中没有文件，请先创建完微网站首页");
						return null;
					}
				}

				// 检查子网页目录
				if (!file_exists($weiSiteUserDir.$weiSiteDirs[$num]."/".self::SECOND_PAGE_DIR)
					|| !is_dir($weiSiteUserDir.$weiSiteDirs[$num]."/".self::SECOND_PAGE_DIR)) {
					break;
				}
			}
		}

		// 是否找到微网站目录
		if ($num < 0) {
			Yii::error("未找到此用户正在编辑的微网站目录");
			return null;
		}

		$weiSiteDir = $weiSiteUserDir.$weiSiteDirs[$num]."/";

		Yii::info("找到此用户正在编辑的微网站目录:");
		return $weisiteDir;
	}

	public function create2ndPageDir($account) {

		$weiSiteDir = $this->getLatestAndActiveWeiSiteDir($account);
		if (is_null($weiSiteDir)) {
			Yii::error("获取用户$account的微网站目录失败");
			return null;
		}

		// 创建首页目录
		if (!file_exists($weiSiteDir.self::SECOND_PAGE_DIR) || !is_dir($weiSiteDir.self::SECOND_PAGE_DIR)) {
			if (!mkdir($weiSiteDir.self::SECOND_PAGE_DIR, 0777)) {
				Yii::error("创建用户$account的微网站子网页目录失败: $weiSiteDir/self::SECOND_PAGE_DIR");
				return null;
			}
		}

		return $weiSiteDir.self::SECOND_PAGE_DIR;
	}

	/**
     * 用来生成此网页对应的url地址
     * @param string $localPath 是网页在服务器上的绝对路径
     * @return 如果成功，返回此网页对应的url地址；否则返回null
	*/
	public function createPageUrl($localPath) {
		if (!is_string($localPath) 
			|| strlen($localPath) < strlen(self::WEI_SITES_LOCAL_ROOT_DIR) 
			|| strncmp($localPath, self::WEI_SITES_LOCAL_ROOT_DIR, strlen(self::WEI_SITES_LOCAL_ROOT_DIR)) != 0) {
			Yii::error("生成首页url的路径错误：$localPath");
			return null;
		}

		return self::WEI_SITES_URL_ROOT_DIR.substr($localPath, strlen(self::WEI_SITES_LOCAL_ROOT_DIR));
	}

	/**
	 * 用来生成短链接
	 * @param string $originalUrl 用来生成短链接的原始url
	 * @return 如果成功返回生成的短链接，否则返回null
	*/
	public function createShortUrl($originalUrl) {
		if (!is_string($originalUrl)
			|| strlen($originalUrl) < strlen(self::WEI_SITES_URL_ROOT_DIR)
			|| strncmp($originalUrl, self::WEI_SITES_URL_ROOT_DIR, strlen(self::WEI_SITES_URL_ROOT_DIR))) {
			Yii::error("生成短链接时传入的url错误: $originalUrl");
			return null;
		}

		//$shortUrl = $this->convertUrlBySina(true, $originalUrl);
		$shortUrl = UrlConverter::getInstance()->convertUrl(true, $originalUrl);

		Yii::info("shorturl: $shortUrl");

		return $shortUrl;
	}

	private const SINA_APP_KEY = '4262014387';

	/**
	 * 使用新浪微博服务转换长链接和短链接
	 * @param bool $trye true表示将长链接转换成短链接; false表示将短链接恢复成长链接
	 * @param string $url 要操作的链接
	 * @return 如果成功返回生成的链接，否则返回null
	*/
	private function convertUrlBySina($type,$url){
	    if($type) {
	    	$baseurl = 'http://api.t.sina.com.cn/short_url/shorten.json?source='.self::SINA_APP_KEY.'&url_long='.$url;
	    }
	    else {
	    	$baseurl = 'http://api.t.sina.com.cn/short_url/expand.json?source='.self::SINA_APP_KEY.'&url_short='.$url;
	    }

	    $ch=curl_init();
	    curl_setopt($ch, CURLOPT_URL,$baseurl);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	    $strRes=curl_exec($ch);
	    curl_close($ch);
	    $arrResponse=json_decode($strRes,true);
	    if (isset($arrResponse->error) || !isset($arrResponse[0]['url_long']) || $arrResponse[0]['url_long'] == '') {
	    	return null;
		}

	    if($type) {
	    	return $arrResponse[0]['url_short'];
	    }
	    else {
	    	return $arrResponse[0]['url_long'];
	    }
    }

	/**
	 * 将微网站添加到数据库中
	*/
	public function insertWeiSite($account, $templateId, $pageUrl, $shortPageUrl) {

		$db_handler = Yii::$app->db->getSvcDb();
		list($sql, $params) = $this->insertWeiSiteSql($account, $templateId, $pageUrl, $shortPageUrl);

		Yii::info("sql: $sql");
		$ret = $db_handler->insert($sql, $params);
		return $ret;
	}

	private function insertWeiSiteSql($account, $templateId, $pageUrl, $shortPageUrl) {
		$now = date('Y-m-d H-i-s');
		$sql = "insert into {$this->WEI_SITE_DB} (Account,FileName,OriginUrl,DestUrl,InsertTime,ModifyTime) values(:account,:filename, :pageUrl,:shortPageUrl,'{$now}','{$now}')";
		$params[':account'] = $account;
		$params[':filename'] = $templateId;
		$params[':pageUrl'] = $pageUrl;
		$params[':shortPageUrl'] = $shortPageUrl;

		return [$sql, $params];
	}

	private function updateWeiSiteInfoSql($account, $weiName, $weiDesc, $weiShortUrl) {
		$now = date('Y-m-d H-i-s');
		$sql = "UPDATE $this->WEI_SITE_DB SET WeiName=:weiname, WeiText=:weidesc, ModifyTime='{$now}' WHERE Account=:account AND DestUrl=:shorturl";
		$params[':weiname'] = $weiName;
		$params[':weidesc'] = $weiDesc;
		$params[':account'] = $account;
		$params[':shorturl'] = $weiShortUrl;

		return [$sql, $params];
	}

	/**
	 * 用来更新微网站信息，通过账户名和短链接从数据库中查找相应的记录，然后再更新
	*/
	public function updateWeiSiteInfo($account, $weiName, $weiDesc, $weiShortUrl) {
		// 
		$db_handler = Yii::$app->db->getSvcDb();
		list($sql, $params) = $this->updateWeiSiteInfoSql($account, $weiName, $weiDesc, $weiShortUrl);

		Yii::info("sql: $sql");
		$ret = $db_handler->execute($sql, $params);
		if (false == $ret) {
			Yii::error("更新微网站失败");
			return BizErrcode::ERR_FAILED;
		}

		return BizErrcode::ERR_OK;
	}

	private $htmlExternalNames = ["html", "shtml", "htm"];
	private function is_html($filePath) {
		Yii::info("is_html() $filePath");
		$externalName = strrchr($filePath, ".");
		foreach ($this->htmlExternalNames as $key => $value) {
			if(strcmp($value, substr($externalName, 1)) == 0) {
				return true;
			}
		}

		Yii::info("$filePath 不是html文件");

		return false;
	}

	private $cssExternalNames = ['css'];
	private function is_css($filename) {
		$externalName = strrchr($filename, ".");
		foreach ($this->cssExternalNames as $key => $value) {
			if (strcmp($value, substr($externalName, 1)) == 0) {
				return true;
			}
		}

		Yii::info("$filename 不是css文件");
		return false;
	}

	/**
	 * 通过解析短链接来获取原始链接，然后再分析原始链接来获得微网站首页的绝对路径
	*/
	public function get1stPagePath($account, $weiShortUrl) {
		// 将短链接转换成原始链接
		//$originalUrl = $this->convertUrlBySina(false, $weiShortUrl);
		$originalUrl = UrlConverter::getInstance()->convertUrl(false, $weiShortUrl);
		if (is_null($originalUrl)) {
			Yii::error("将短链接$weiShortUrl转换成原始链接出错");
			return null;
		}

		// 检查原始链接是否正确
		if (strncmp($originalUrl, self::WEI_SITES_URL_ROOT_DIR, strlen(self::WEI_SITES_URL_ROOT_DIR)) != 0) {
			Yii::error("恢复后的原始链接不正确$originalUrl");
			return null;
		}

		// 生成微网站首页在服务器上的绝对路径
		$pagePath = self::WEI_SITES_LOCAL_ROOT_DIR.substr($originalUrl, strlen(self::WEI_SITES_URL_ROOT_DIR));

		// 检查首页路径是否合法
		if (!file_exists($pagePath)
			|| !is_file($pagePath)
			|| !$this->is_html($pagePath)) {
			Yii::error("恢复后的原始链接不合法: $pagePath");
			return null;
		}

		return $pagePath;
	}

	/**
	 * 通过解析短链接来获取原始链接，然后再分析原始链接来获得微网站首页的绝对路径
	*/
	public function get2ndPagePath($account, $pageUrl) {
		// 检查原始链接是否正确
		if (strncmp($pageUrl, self::WEI_SITES_URL_ROOT_DIR, strlen(self::WEI_SITES_URL_ROOT_DIR)) != 0) {
			Yii::error("子网页的链接不正确$pageUrl");
			return null;
		}

		// 生成微网站首页在服务器上的绝对路径
		$pagePath = self::WEI_SITES_LOCAL_ROOT_DIR.substr($pageUrl, strlen(self::WEI_SITES_URL_ROOT_DIR));

		// 检查首页路径是否合法
		if (!file_exists($pagePath)
			|| !is_file($pagePath)
			|| !$this->is_html($pagePath)) {
			Yii::error("子网页的路径不合法: $pagePath");
			return null;
		}

		return $pagePath;
	}

	public function isOriginalUrl($url) {
		if (strncmp($url, self::WEI_SITES_URL_ROOT_DIR, strlen(self::WEI_SITES_URL_ROOT_DIR)) != 0) {
			return false;
		}

		return true;
	}

	private function queryWeiSiteInfoByShortUrlSql($account, $shortUrl) {
		$sql = "SELECT weiName, weiText from $this->WEI_SITE_DB where Account=:account And DestUrl=:shortUrl";
		$params[':account'] = $account;
		$params[':shortUrl'] = $shortUrl;

		return [$sql, $params];
	}

	public function getWeiSiteInfoByShortUrl($account, $shortUrl) {
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->queryWeiSiteInfoByShortUrlSql($account, $shortUrl);
		Yii::info("query sql: $sql");
		$ret = $db_handler->getAll($sql, $params);
		if (!is_array($ret)) {
			Yii::error("获取短链接$shortUrl对应的微网站信息出错");
			return BizErrcode::ERR_FAILED;
		} elseif (count($ret) == 0) {
			Yii::error("短链接$shortUrl在微网站的数据库中不存在");
			return BizErrcode::ERR_FAILED;
		} elseif (count($ret) > 1) {
			Yii::error("短链接$shortUrl在微网站的数据库中存在多个");
			return BizErrcode::ERR_FAILED;
		}

		return [$ret[0]['weiName'], $ret[0]['weiText']];
	}


	public function copyTemplate($sourceTemplate, $destTemplate) {

		if (!file_exists($sourceTemplate) || !is_dir($sourceTemplate)) {
			Yii::error("模板文件夹$sourceTemplate不存在");
			return null;
		}
		if (!file_exists($destTemplate) || !is_dir($destTemplate)) {
			Yii::error("微网站目录不存在$destTemplate");
			return null;
		}

		$pagePath = null;
		$fileList = scandir($sourceTemplate);
		foreach ($fileList as $key => $filename) {
			if ($this->is_html($filename) || $this->is_css($filename)) {
				if (!copy($sourceTemplate."/".$filename, $destTemplate.$filename)) {
					Yii::error("复制$sourceTemplate$filename到$destTemplate$filename失败");
					continue;
				}
				if ($this->is_html($filename)) {
					$pagePath = $destTemplate.$filename;
					Yii::info("微网站首页路径$pagePath");
				}
			}
		}

		return $pagePath;
	}

	private function getAllWeiSitesSql($account) {
		$sql = "SELECT WeiName, WeiPic, WeiText, DestUrl FROM $this->WEI_SITE_DB where Account=:account";
		$params[':account'] = $account;

		return [$sql, $params];
	}

	public function getAllWeiSites($account) {

		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->getAllWeiSitesSql($account);
		Yii::info("query sql: $sql");
		$ret = $db_handler->getAll($sql, $params);
		if (!is_array($ret)) {
			Yii::error("Failed to get all of wei-sites info of the $account");
			return FALSE;
		} elseif (count($ret) == 0) {
			Yii::error("This $account does not create any wei-site");
			return FALSE;
		}

		return $ret;
	}

	private function deletWeiSiteSql($account, $shortUrl) {
		$sql = "DELETE FROM $this->WEI_SITE_DB WHERE Account=:account AND DestUrl=:shortUrl";
		$params[':account'] = $account;
		$params[':shortUrl'] = $shortUrl;

		return [$sql, $params];
	}

	public function deleteWeiSite($account, $shortUrl) {
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->deleteWeiSite($account, $shortUrl);
		Yii::info("query sql: $sql");
		$ret = $db_handler->execute($sql, $params);

		return $ret;
	}
}
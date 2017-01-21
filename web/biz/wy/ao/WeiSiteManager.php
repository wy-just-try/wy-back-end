<?php
namespace app\wy\ao;

use Yii;
use includes\BizErrcode;
use app\wy\ao\UrlConverter;

class WeiSiteManager {
	
	private $WEI_SITE_DB = 'WeiNetInfo';

	//const WEI_SITES_LOCAL_ROOT_DIR = '/Users/apple/software/project/workspace/wy/src/wy-back-end/web/weisites/';
	const WEI_SITES_LOCAL_ROOT_DIR = '/data/data/back/web/weisites/';
	const WEI_SITES_URL_ROOT_DIR = 'http://wy626.com/web/weisites/';
	const FIRST_PAGE_DIR = '1st/';
	const SECOND_PAGE_DIR = '2nd/';
	const SECOND_PAGE_ID_PREFIX = 'page_';
	const EDITABLE_PAGE_NAME = 'editable.shtml';

	const MAX_WEI_SITE_NUMBER = 5;


	/**
	** 微网站首页在服务器上的绝对路径
	** {WEI_SITES_LOCAL_ROOT_DIR}{account name}/{微网站id}/{FIRST_PAGE_DIR}/{FileName.shtml}
	** {微网站id} <= weisite_{number}
	** 微网站子网页在服务器上的绝对路径
	** {WEI_SITES_LOCAL_ROOT_DIR}{account name}/{微网站id}/{SECOND_PAGE_DIR}/{SECOND_PAGE_ID_PREFIX}_{NUM}/{FileName.shtml}
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
	* 用来判断此用户创建的微网站是否达到上限
	* @param string $account 账号
	* @return 如果达到上限，则返回true，否则返回FALSE
	*/
	public function enoughWeisites($account) {
		$ret = $this->getAllWeiSites($account);
		if (FALSE == $ret || count($ret) < self::MAX_WEI_SITE_NUMBER) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * 在微网站目录下为此用户创建他自己的微网站目录
	 * 创建成功后的微网站目录格式：{WEI_SITES_LOCAL_ROOT_DIR}{account name}/weisite_{number}/
	 * @param string $account： 账户名称
	 * @return 如果成功，则返回此用户微网站的目录路径，并且此路径是绝对路径；否则返回null
	*/
	public function getWeiSiteDir($account) {

		//在微网站的目录中查找当前用户的微网站目录是否创建
		$weiSiteUserDir = self::WEI_SITES_LOCAL_ROOT_DIR.$account."/";
		if (!file_exists($weiSiteUserDir) || !is_dir($weiSiteUserDir)) {
			if (!mkdir($weiSiteUserDir, 0777, true)) {
				Yii::error("Failed to create wei-site directory($weiSiteDir)");
				return null;
			}
		}

		//在此用户的微网站目录中查找最新的微网站id
		$lastWeiSiteNumber = 0;
		$weiSiteDirs = scandir($weiSiteUserDir);
		if (!is_array($weiSiteDirs) || count($weiSiteDirs) < 2) {
			Yii::error("Failed to read the wei-site user's directory($weiSiteUserDir)");
			return null;
		}
		for ($num = 0; $num < count($weiSiteDirs); $num++) {
			$weiSiteDir = $weiSiteDirs[$num];
			Yii::info("The read wei-site directory: $weiSiteDir");
		}
		sort($weiSiteDirs, SORT_NATURAL);

		for ($num = 0; $num < count($weiSiteDirs); $num++) {
			$weiSiteDir = $weiSiteDirs[$num];
			Yii::info("After sort, the wei-site directory: $weiSiteDir");
		}
		for ($num = count($weiSiteDirs)-1; $num >= 0; $num--) {
			$weisiteDir = $weiSiteDirs[$num];
			Yii::info("Checking the wei-site directory: $weiSiteUserDir$weisiteDir");
			if (is_dir($weiSiteUserDir.$weiSiteDirs[$num])
				&& strlen($weiSiteDirs[$num]) > strlen("weisite_")
				&& strncmp($weiSiteDirs[$num], "weisite_", strlen("weisite_")) == 0) {

				$lastWeiSiteNumberStr = substr($weiSiteDirs[$num], strlen("weisite_"));
				Yii::info("The latest wei-site directory: $weisiteDir, number: $lastWeiSiteNumberStr");
				if (is_numeric($lastWeiSiteNumberStr)) {
					$lastWeiSiteNumber = intval($lastWeiSiteNumberStr);
					Yii::info("Found the latest wei-site directory: $lastWeiSiteNumber");
					break;
				}
			}
		}

		//创建当前用户的微网站目录
		$lastWeiSitePath = $weiSiteUserDir."weisite_".($lastWeiSiteNumber+1)."/";
		Yii::info("The account($account)'s wei-site directory is $lastWeiSitePath");
		if (!mkdir($lastWeiSitePath, 0777, true)) {
			Yii::error("Failed to create the wei-site directory: $lastWeiSitePath");
			return null;
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
			Yii::error("Failed to get the account($account)'s wei-site directory");
			return null;
		}

		// 创建首页目录
		if (!file_exists($weiSiteDir.self::FIRST_PAGE_DIR) || !is_dir($weiSiteDir.self::FIRST_PAGE_DIR)) {
			if (!mkdir($weiSiteDir.self::FIRST_PAGE_DIR, 0777)) {
				Yii::error("Failed to create the account($account)'s wei-site's first page directory: $weiSiteDir/self::FIRST_PAGE_DIR");
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
			Yii::error("The wei-site directory($weiSiteDir) isn't created");
			return null;
		}

		//在此用户的微网站目录中查找最新的并且是正在被编辑的微网站id
		//如果用户的微网站目录中，只有首页目录被创建且里面有文件，并且二级目录没有被创建
		//则认为此微网站是active
		$lastWeiSiteNumber = 0;
		$weiSiteDirs = scandir($weiSiteUserDir);
		if (!is_array($weiSiteDirs) || count($weiSiteDirs) < 2) {
			Yii::error("Failed to read the wei-site's user directory($weiSiteUserDir)");
			return null;
		} else if (count($weiSiteDirs) == 2) {
			Yii::error("This account($account) does not create the wei-site($weiSiteUserDir). Please create it firstly");
			return null;
		}

		sort($weiSiteDirs, SORT_NATURAL);

		for ($num = count($weiSiteDirs)-1; $num >= 0; $num--) {
			$weisiteDir = $weiSiteDirs[$num];
			Yii::info("Checking wei-site directory: $weiSiteUserDir$weisiteDir");
			if (is_dir($weiSiteUserDir.$weiSiteDirs[$num])
				&& strlen($weiSiteDirs[$num]) > strlen("weisite_")
				&& strncmp($weiSiteDirs[$num], "weisite_", strlen("weisite_")) == 0) {

				// 检查首页目录是否已经创建
				if (!file_exists($weiSiteUserDir.$weiSiteDirs[$num]."/".self::FIRST_PAGE_DIR)
					|| !is_dir($weiSiteUserDir.$weiSiteDirs[$num]."/".self::FIRST_PAGE_DIR)) {
					Yii::error("The first page directory of this account($account)'s wei-site($weiSiteDir) is not created. Please create it firstly");
					return null;
				} else {
					$files = scandir($weiSiteUserDir.$weiSiteDirs[$num]."/".self::FIRST_PAGE_DIR);
					if (!is_array($files) || count($files) < 3) {
						Yii::error("There is no any file in this account($account)'s wei-site directory($weisiteDir). Please finish to create first page of the wei-site firstly");
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
			Yii::error("Not found the editing wei-site directory");
			return null;
		}

		$weiSiteDir = $weiSiteUserDir.$weiSiteDirs[$num]."/";

		Yii::info("Found the editing wei-site directory: $weiSiteDir");
		return $weiSiteDir;
	}

	private function parsePagePath($pagePath) {
		// 获取账号标识
		$account = strstr($pagePath, "/", TRUE);
		if (FALSE == $account) {
			Yii::error("The page url is invalid, no account: $originalPageUrl");
			return null;
		}

		// 获取微网站ID
		$tempPagePath = substr($pagePath, strlen($account) + 1);
		$weisiteId = strstr($tempPagePath, "/", TRUE);
		if (FALSE == $weisiteId || strncmp($weisiteId, "weisite_", strlen("weisite_")) != 0) {
			Yii::error("The page path is invalid, wei-site id is wrong: $pagePath");
			return null;
		}

		// 获取网页类型
		$tempPagePath 	= substr($tempPagePath, strlen($weisiteId) + 1);
		$pageType 		= strstr($tempPagePath, "/", TRUE);
		$firstPageName 	= null;
		$secondPageId 	= null;
		$secondPageName = null;
		if (FALSE == $pageType) {
			Yii::error("The page path is invalid, no page type: $pagePath");
			return null;
		} elseif (strncmp($pageType, self::FIRST_PAGE_DIR, strlen(self::FIRST_PAGE_DIR)-1) == 0) {
			// 获取首页的文件名
			$tempPagePath = substr($tempPagePath, strlen($pageType) + 1);
			$firstPageName = strstr($tempPagePath, "/", TRUE);
			if ($firstPageName != FALSE || !$this->is_html($tempPagePath)) {
				Yii::error("This page path is not 1st page path: $pagePath");
				return null;
			} else {	
				$firstPageName = $tempPagePath;
			}

			Yii::info("$pagePath => $account => $weisiteId => $pageType => $firstPageName");
		} elseif (strncmp($pageType, self::SECOND_PAGE_DIR, strlen(self::SECOND_PAGE_DIR)-1) == 0) {
			// 获取子网页的ID
			$tempPagePath = substr($tempPagePath, strlen($pageType) + 1);
			$secondPageId = strstr($tempPagePath, "/", TRUE);
			if (FALSE == $secondPageId || strncmp($secondPageId, self::SECOND_PAGE_ID_PREFIX, strlen(self::SECOND_PAGE_ID_PREFIX)) != 0) {
				Yii::error("This page path is not the 2nd page, wrong 2nd page id: $pagePath");
				return null;
			}

			// 获取子网页的文件名
			$tempPagePath = substr($tempPagePath, strlen($secondPageId) + 1);
			$secondPageName = strstr($tempPagePath, "/", TRUE);
			if ($secondPageName != FALSE || !$this->is_html($tempPagePath)) {
				Yii::error("This page path is not 2nd page, wrong file name: $pagePath");
				return null;
			} else {
				$secondPageName = $tempPagePath;
			}

			Yii::info("pagePath => $account => $weisiteId => $pageType => $secondPageId => $secondPageName");

		} else {
			Yii::error("The page path is invalid, page type($pageType) is wrong: $pagePath");
			return null;
		}

		$output['account'] 		= $account;
		$output['weisiteId'] 	= $weisiteId;
		$output['pageType'] 	= $pageType;
		$output['1stPageName'] 	= $firstPageName;
		$output['2ndPageId'] 	= $secondPageId;
		$output['2ndPageName']	= $secondPageName;

		return $output;
	}

	private function pageAbsPagePath($pagePath) {
		if (strncmp($pagePath, self::WEI_SITES_LOCAL_ROOT_DIR, strlen(self::WEI_SITES_LOCAL_ROOT_DIR)) != 0) {
			Yii::error("Invalid page path: wrong prefix, $pagePath");
			return FALSE;
		}

		$tempPagePath = substr($pagePath, strlen(self::WEI_SITES_LOCAL_ROOT_DIR));
		$parsedPath = $this->parsePagePath($tempPagePath);
		if (is_null($parsedPath)) {
			Yii::error("Failed to parse absolute page path: $pagePath");
			return null;
		}

		return $parsedPath;
	}

	/**
	* 解析网页的url，url可以是短链接也可以原始链接，可以首页也可以子网页
	* 	如果成功，则返回一个数组，数组的元素如下：
	* 		'account' 		=> '用户名或者唯一表示用户的标识'
	*		'weisiteId'		=> '微网站ID'
	*		'pageType'		=> '网页的类型{1st: 首页，2nd: 子网页}'
	*   	'1stPageName' => '首页的文件名，如果url不是首页网址，则为空'
	*		'2ndPageId'		=> '子网页的ID，实际是子网页的目录名: page_{xxx}'
	*		'2ndPageName'	=> '子网页的文件名'
	*
	* @param string $pageUrl 要解析的url
	* @return 如果成功返回相应的数组，否则返回null
	*/
	private function parseUrl($pageUrl) {
		$originalPageUrl = $pageUrl;
		if (!$this->isOriginalUrl($pageUrl)) {
			$originalPageUrl = UrlConverter::getInstance()->convertUrl(false, $pageUrl);
			if (is_null($originalPageUrl)) {
				Yii::error("Failed to convert the short url($pageUrl) to the original url");
				return null;
			}
		}

		// 检查原始链接是否正确
		if (strncmp($originalPageUrl, self::WEI_SITES_URL_ROOT_DIR, strlen(self::WEI_SITES_URL_ROOT_DIR)) != 0) {
			Yii::error("The page url is wrong: $originalPageUrl");
			return null;
		}

		$tempPagePath = substr($originalPageUrl, strlen(self::WEI_SITES_URL_ROOT_DIR));
		$parsedPath = $this->parsePagePath($tempPagePath);
		if (is_null($parsedPath)) {
			Yii::error("Failed to parse original url: $pageUrl => $originalPageUrl");
			return null;
		}

		return $parsedPath;

		// // 获取账号标识
		// $tempPagePath = substr($originalPageUrl, strlen(self::WEI_SITES_URL_ROOT_DIR));
		// $account = strstr($tempPagePath, "/", TRUE);
		// if (FALSE == $account) {
		// 	Yii::error("The page url is invalid, no account: $originalPageUrl");
		// 	return null;
		// }

		// // 获取微网站ID
		// $tempPagePath = substr($tempPagePath, strlen($account) + 1);
		// $weisiteId = strstr($tempPagePath, "/", TRUE);
		// if (FALSE == $weisiteId || strncmp($weisiteId, "weisite_", strlen("weisite_")) != 0) {
		// 	Yii::error("The page url is invalid, wei-site id is wrong: $originalPageUrl");
		// 	return null;
		// }

		// // 获取网页类型
		// $tempPagePath 	= substr($tempPagePath, strlen($weisiteId) + 1);
		// $pageType 		= strstr($tempPagePath, "/", TRUE);
		// $firstPageName 	= null;
		// $secondPageId 	= null;
		// $secondPageName = null;
		// if (FALSE == $pageType) {
		// 	Yii::error("The page url is invalid, no page type: $originalPageUrl");
		// 	return null;
		// } elseif (strncmp($pageType, self::FIRST_PAGE_DIR, strlen(self::FIRST_PAGE_DIR)-1) == 0) {
		// 	// 获取首页的文件名
		// 	$tempPagePath = substr($tempPagePath, strlen($pageType) + 1);
		// 	$firstPageName = strstr($tempPagePath, "/", TRUE);
		// 	if ($firstPageName != FALSE || !$this->is_html($tempPagePath)) {
		// 		Yii::error("This page url is not 1st page url: $originalPageUrl");
		// 		return null;
		// 	} else {	
		// 		$firstPageName = $tempPagePath;
		// 	}

		// 	Yii::info("pageUrl: $pageUrl => $originalPageUrl => $account => $weisiteId => $pageType => $firstPageName");
		// } elseif (strncmp($pageType, self::SECOND_PAGE_DIR, strlen(self::SECOND_PAGE_DIR)-1) == 0) {
		// 	// 获取子网页的ID
		// 	$tempPagePath = substr($tempPagePath, strlen($pageType) + 1);
		// 	$secondPageId = strstr($tempPagePath, "/", TRUE);
		// 	if (FALSE == $secondPageId || strncmp($secondPageId, self::SECOND_PAGE_ID_PREFIX, strlen(self::SECOND_PAGE_ID_PREFIX)) != 0) {
		// 		Yii::error("This page url is not the 2nd page url, wrong 2nd page id: $originalPageUrl");
		// 		return null;
		// 	}

		// 	// 获取子网页的文件名
		// 	$tempPagePath = substr($tempPagePath, strlen($secondPageId) + 1);
		// 	$secondPageName = strstr($tempPagePath, "/", TRUE);
		// 	if ($secondPageName != FALSE || !$this->is_html($tempPagePath)) {
		// 		Yii::error("This page url is not 2nd page url, wrong file name: $originalPageUrl");
		// 		return null;
		// 	} else {
		// 		$secondPageName = $tempPagePath;
		// 	}

		// 	Yii::info("pageUrl: $pageUrl => $originalPageUrl => $account => $weisiteId => $pageType => $secondPageId => $secondPageName");

		// } else {
		// 	Yii::error("The page url is invalid, page type($pageType) is wrong: $originalPageUrl");
		// 	return null;
		// }

		// $output['account'] 		= $account;
		// $output['weisiteId'] 	= $weisiteId;
		// $output['pageType'] 	= $pageType;
		// $output['1stPageName'] 	= $firstPageName;
		// $output['2ndPageId'] 	= $secondPageId;
		// $output['2ndPageName']	= $secondPageName;

		return $output;
	}

	/**
	 * 用来生成子网页在服务器上的绝对路径，如果firstPageUrl不为null，就先用首页的url来
	 * 生成子网页的绝对路径；否则用用户名来生成子网页的约绝对路径
	 * @param string $account 生成子网页的用户名
	 * @param string $firstPageUrl 子网页对应的首页url
	 * @return 如果成功，返回子网页在服务器上的绝对路径；否则返回null
	*/
	public function create2ndPageDir($account, $firstPageUrl) {

		$weiSiteDir = null;
		// 如果有给出微网站首页的url，则先用微网站的首页url来获取微网站目录在服务器上的绝对路径
		if (!is_null($firstPageUrl)) {
			$weiSiteDir = $this->getWeiSiteDirByUrl($firstPageUrl);
			Yii::info("The wei-site directory is $weiSiteDir by url($firstPageUrl)");
		}

		// 如果微网站目录路径没有获取到，再获取一次
		if (is_null($weiSiteDir)) {
			$weiSiteDir = $this->getLatestAndActiveWeiSiteDir($account);
			if (is_null($weiSiteDir)) {
				Yii::error("Failed to obtain the account($account)'s wei-site directory");
				return null;
			}
		}

		Yii::info("Found wei-site dir: $weiSiteDir");

		// 获取要创建的子网页所在目录的路径
		$subPageDir = $weiSiteDir.self::SECOND_PAGE_DIR;
		$lastSubPageNumber = 0;
		if (!file_exists($subPageDir) || !is_dir($subPageDir)) {
			//$subPageDir = $subPageDir.self::SECOND_PAGE_ID_PREFIX."0/";
		} else {
			$subPageDirList = scandir($subPageDir);
			if (FALSE != $subPageDirList) {
				sort($subPageDirList, SORT_NATURAL);

				for ($num = count($subPageDirList) - 1; $num >= 0; $num--) {
					$pageDir = $subPageDirList[$num];
					Yii::info("Checking 2nd page directory: $subPageDir$pageDir");
					if (is_dir($subPageDir.$pageDir) && strncmp($pageDir, self::SECOND_PAGE_ID_PREFIX, strlen(self::SECOND_PAGE_ID_PREFIX)) == 0) {
						$lastSubPageNumberStr = substr($pageDir, strlen(self::SECOND_PAGE_ID_PREFIX));
						if (is_numeric($lastSubPageNumberStr)) {
							$lastSubPageNumber = intval($lastSubPageNumberStr) + 1;
							Yii::info("Found the latest 2nd page id: $pageDir");
							break;
						}
					}
				}
			}
		}

		$subPageDir = $subPageDir.self::SECOND_PAGE_ID_PREFIX.$lastSubPageNumber."/";

		// 创建子页面目录
		if (!file_exists($subPageDir) || !is_dir($subPageDir)) {
			if (!mkdir($subPageDir, 0777, true)) {
				Yii::error("Failed to create the sub page directory: $subPageDir");
				return null;
			} else {
				Yii::info("Success to create the sub page directory: $subPageDir");
			}
		} else {
			Yii::info("The sub page directory: $subPageDir exists");
		} 

		return $subPageDir;
	}

	/**
	 * 通过微网站首页的url地址来获取微网站目录在服务器上的绝对路径
	 * @param string $pageUrl 微网站首页的url，可以是短链接也可以是原始链接
	 * @return 如果成功，再返回绝对路径；否则返回null
	*/
	public function getWeiSiteDirByUrl($pageUrl) {
		// $originalPageUrl = $pageUrl;
		// if (!$this->isOriginalUrl($pageUrl)) {
		// 	$originalPageUrl = UrlConverter::getInstance()->convertUrl(false, $pageUrl);
		// 	if (is_null($originalPageUrl)) {
		// 		Yii::error("Failed to convert the short url($pageUrl) to the original url");
		// 		return null;
		// 	}
		// }

		// // 检查原始链接是否正确
		// if (strncmp($originalPageUrl, self::WEI_SITES_URL_ROOT_DIR, strlen(self::WEI_SITES_URL_ROOT_DIR)) != 0) {
		// 	Yii::error("The page url is wrong: $originalPageUrl");
		// 	return null;
		// }

		// $tempPagePath = substr($originalPageUrl, strlen(self::WEI_SITES_URL_ROOT_DIR));
		// $account = strstr($tempPagePath, "/", TRUE);
		// if (FALSE == $account) {
		// 	Yii::error("The page url is invalid, no account: $originalPageUrl");
		// 	return null;
		// }

		// $tempPagePath = substr($tempPagePath, strlen($account) + 1);
		// $weisiteId = strstr($tempPagePath, "/", TRUE);
		// if (FALSE == $weisiteId || strncmp($weisiteId, "weisite_", strlen("weisite_")) != 0) {
		// 	Yii::error("The page url is invalid, wei-site id is wrong: $originalPageUrl");
		// 	return null;
		// }

		// $tempPagePath = substr($tempPagePath, strlen($weisiteId) + 1);
		// $pageType = strstr($tempPagePath, "/", TRUE);
		// if (FALSE == $pageType) {
		// 	Yii::error("The page url is invalid, no page type: $originalPageUrl");
		// 	return null;
		// } elseif (strncmp($pageType, self::FIRST_PAGE_DIR, strlen(self::FIRST_PAGE_DIR)-1) == 0
		// 	|| strncmp($pageType, self::SECOND_PAGE_DIR, strlen(self::SECOND_PAGE_DIR)-1) == 0) {

		// } else {
		// 	Yii::error("The page url is invalid, page type($pageType) is wrong: $originalPageUrl");
		// 	return null;
		// }

		// Yii::info("pageUrl: $pageUrl => $originalPageUrl => $account => $weisiteId => $pageType");

		// $weisiteDir = self::WEI_SITES_LOCAL_ROOT_DIR.$account."/".$weisiteId."/";

		// Yii::info("wei-site dir: $weisiteDir");

		// return $weisiteDir;
		$parsedUrl = $this->parseUrl($pageUrl);
		if (is_null($parsedUrl)) {
			Yii::error("Failed to parse url: $pageUrl");
			return null;
		}

		$weisiteDir = self::WEI_SITES_LOCAL_ROOT_DIR.$parsedUrl['account']."/".$parsedUrl['weisiteId']."/";
		Yii::info("wei-site dir: $weisiteDir");

		return $weisiteDir;
	}

	/**
	 * 通过微网站网页的url地址来获取微网站网页所在目录在服务器上的绝对路径
	 * 如果url是首页的地址，如果成功，则返回首页所在目录的绝对路径
	 * 如果url是子网页的地睛，如果成功，则返回子网页所以在目录的绝对路径
	 * @param string $pageUrl 微网站首页的url，可以是短链接也可以是原始链接
	 * @return 如果成功，再返回绝对路径；否则返回null
	*/
	public function getWeiSitePageDirByUrl($pageUrl) {
		// $originalPageUrl = $pageUrl;
		// if (!$this->isOriginalUrl($pageUrl)) {
		// 	$originalPageUrl = UrlConverter::getInstance()->convertUrl(false, $pageUrl);
		// 	if (is_null($originalPageUrl)) {
		// 		Yii::error("Failed to convert the short url($pageUrl) to the original url");
		// 		return null;
		// 	}
		// }

		// // 检查原始链接是否正确
		// if (strncmp($originalPageUrl, self::WEI_SITES_URL_ROOT_DIR, strlen(self::WEI_SITES_URL_ROOT_DIR)) != 0) {
		// 	Yii::error("The page url is wrong: $originalPageUrl");
		// 	return null;
		// }

		// $tempPagePath = substr($originalPageUrl, strlen(self::WEI_SITES_URL_ROOT_DIR));
		// $account = strstr($tempPagePath, "/", TRUE);
		// if (FALSE == $account) {
		// 	Yii::error("The page url is invalid, no account: $originalPageUrl");
		// 	return null;
		// }

		// $tempPagePath = substr($tempPagePath, strlen($account) + 1);
		// $weisiteId = strstr($tempPagePath, "/", TRUE);
		// if (FALSE == $weisiteId || strncmp($weisiteId, "weisite_", strlen("weisite_")) != 0) {
		// 	Yii::error("The page url is invalid, wei-site id is wrong: $originalPageUrl");
		// 	return null;
		// }

		// $tempPagePath = substr($tempPagePath, strlen($weisiteId) + 1);
		// $pageType = strstr($tempPagePath, "/", TRUE);
		// if (FALSE == $pageType) {
		// 	Yii::error("The page url is invalid, no page type: $originalPageUrl");
		// 	return null;
		// } elseif (strncmp($pageType, self::FIRST_PAGE_DIR, strlen(self::FIRST_PAGE_DIR)-1) == 0
		// 	|| strncmp($pageType, self::SECOND_PAGE_DIR, strlen(self::SECOND_PAGE_DIR)-1) == 0) {

		// } else {
		// 	Yii::error("The page url is invalid, page type($pageType) is wrong: $originalPageUrl");
		// 	return null;
		// }

		// Yii::info("pageUrl: $pageUrl => $originalPageUrl => $account => $weisiteId => $pageType");

		// $weisitePageDir = self::WEI_SITES_LOCAL_ROOT_DIR.$account."/".$weisiteId."/".$pageType."/";
		
		// Yii::info("wei-site dir: $weisitePageDir");

		// return $weisitePageDir;

		$parsedUrl = $this->parseUrl($pageUrl);
		if (is_null($parsedUrl)) {
			Yii::error("Failed to parse url: $pageUrl");
			return null;
		}

		if (strncmp($parsedUrl['pageType'], self::FIRST_PAGE_DIR, strlen(self::FIRST_PAGE_DIR) - 1) == 0) {
			$weisitePageDir = self::WEI_SITES_LOCAL_ROOT_DIR.$parsedUrl['account']."/".$parsedUrl['weisiteId']."/".$parsedUrl['pageType']."/";
		} elseif (strncmp($parsedUrl['pageType'], self::SECOND_PAGE_DIR, strlen(self::SECOND_PAGE_DIR) - 1) == 0) {
			$weisitePageDir = self::WEI_SITES_LOCAL_ROOT_DIR.$parsedUrl['account']."/".$parsedUrl['weisiteId']."/".$parsedUrl['pageType']."/".$parsedUrl['2ndPageId']."/";
		} else {
			Yii::error("Wrong page type");
			$weisitePageDir = null;
		}

		Yii::info("wei-site dir: $weisitePageDir");

		return $weisitePageDir;
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
			Yii::error("The page's local path is wrong: $localPath");
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
			Yii::error("The original url is wrong: $originalUrl");
			return null;
		}

		//$shortUrl = $this->convertUrlBySina(true, $originalUrl);
		$shortUrl = UrlConverter::getInstance()->convertUrl(true, $originalUrl);

		Yii::info("shorturl: $shortUrl");

		return $shortUrl;
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
			Yii::error("Failed to update wei-site info in database");
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

		Yii::info("$filePath is not html file");

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

		Yii::info("$filename is not css file");
		return false;
	}

	/**
	 * 通过解析短链接来获取原始链接，然后再分析原始链接来获得微网站首页的绝对路径
	*/
	public function get1stPagePath($account, $weiShortUrl) {
		// 将短链接转换成原始链接
		$originalUrl = UrlConverter::getInstance()->convertUrl(false, $weiShortUrl);
		if (is_null($originalUrl)) {
			Yii::error("Failed to convert the short url($weiShortUrl) to the original url");
			return null;
		}

		// 检查原始链接是否正确
		if (strncmp($originalUrl, self::WEI_SITES_URL_ROOT_DIR, strlen(self::WEI_SITES_URL_ROOT_DIR)) != 0) {
			Yii::error("The obtained original url is wrong: $originalUrl");
			return null;
		}

		// 生成微网站首页在服务器上的绝对路径
		$pagePath = self::WEI_SITES_LOCAL_ROOT_DIR.substr($originalUrl, strlen(self::WEI_SITES_URL_ROOT_DIR));

		// 检查首页路径是否合法
		if (!file_exists($pagePath)
			|| !is_file($pagePath)
			|| !$this->is_html($pagePath)) {
			Yii::error("The page's absolute path is wrong: $pagePath");
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
			Yii::error("The sub page url is wrong: $pageUrl");
			return null;
		}

		// 生成微网站首页在服务器上的绝对路径
		$pagePath = self::WEI_SITES_LOCAL_ROOT_DIR.substr($pageUrl, strlen(self::WEI_SITES_URL_ROOT_DIR));

		// 检查首页路径是否合法
		if (!file_exists($pagePath)
			|| !is_file($pagePath)
			|| !$this->is_html($pagePath)) {
			Yii::error("The sub page path is invalid: $pagePath");
			return null;
		}

		return $pagePath;
	}

	private function validatePagePath($pagePath) {
		if (strncmp($pagePath, self::WEI_SITES_LOCAL_ROOT_DIR, strlen(self::WEI_SITES_LOCAL_ROOT_DIR)) != 0) {
			Yii::error("Invalid page path: wrong prefix, $pagePath");
			return FALSE;
		}

		$tempPagePath = substr($pagePath, strlen(self::WEI_SITES_LOCAL_ROOT_DIR));
		$account = strstr($tempPagePath, "/", TRUE);
		if (FALSE == $account) {
			Yii::error("The page url is invalid, no account: $pagePath");
			return FALSE;
		}

		$tempPagePath = substr($tempPagePath, strlen($account) + 1);
		$weisiteId = strstr($tempPagePath, "/", TRUE);
		if (FALSE == $weisiteId || strncmp($weisiteId, "weisite_", strlen("weisite_")) != 0) {
			Yii::error("The page path is invalid, wei-site id is wrong: $pagePath");
			return FALSE;
		}

		$tempPagePath = substr($tempPagePath, strlen($weisiteId) + 1);
		$pageType = strstr($tempPagePath, "/", TRUE);
		if (FALSE == $pageType) {
			Yii::error("The page path is invalid, no page type: $pagePath");
			return FALSE;
		} elseif (strncmp($pageType, self::FIRST_PAGE_DIR, strlen(self::FIRST_PAGE_DIR)-1) == 0) {
			$tempPagePath = substr($tempPagePath, strlen($pageType) + 1);
			$slashToken = strpos($tempPagePath, "/");
			if (FALSE != $slashToken) {
				Yii::error("Invalid page path, wrong file name: $pagePath");
				return FALSE;
			}
			$fileName = $tempPagePath;
			if (!$this->is_html($fileName)) {
				Yii::error("Invalid file name, it's not html: $pagePath");
				return FALSE;
			}

		} elseif (strncmp($pageType, self::SECOND_PAGE_DIR, strlen(self::SECOND_PAGE_DIR)-1) == 0) {

		} else {
			Yii::error("The page path is invalid, page type($pageType) is wrong: $pagePath");
			return FALSE;
		}

		return [$account, $weisiteId, $pageType, $fileName];
	}

	/**
	* 保存content到pagePath对应的可编辑html文件中
	* @param string $pagePath 首页或子网页在服务器上的路径
	* @param string $content 要保存的内容
	* @return 如果成功返回可编辑网页的绝对路径；否则返回false
	*/
	public function saveContent($pagePath, $content) {
		if (is_null($content) || strlen($content) == 0) {
			Yii::error("Wrong content: length is 0");
			return FALSE;
		}
		// // 检查$pagePath是否合法
		// $ret = $this->validatePagePath($pagePath);
		// if (FALSE == $ret) {
		// 	Yii::error("Invalid page path: $pagePath");
		// 	return FALSE;
		// }
		// list($account, $weisiteId, $pageType, $fileName) = $ret;

		// // 生成网页所在的目录路径
		// $pageDir = self::WEI_SITES_LOCAL_ROOT_DIR.$account.'/'.$weisiteId.'/'.$pageType.'/';
		// if (!file_exists($pageDir) || !is_dir($pageDir)) {
		// 	Yii::error("Invalid page directory: not exist, $pageDir");
		// 	return FALSE;
		// }
		$parsedPath = $this->pageAbsPagePath($pagePath);
		if (is_null($parsedPath)) {
			Yii::error("Failed to parse page path: $pagePath");
			return FALSE;
		}

		if (strncmp($parsedPath['pageType'], self::FIRST_PAGE_DIR, strlen(self::FIRST_PAGE_DIR) - 1) == 0) {
			$pageDir = self::WEI_SITES_LOCAL_ROOT_DIR.$parsedPath['account'].'/'.$parsedPath['weisiteId'].'/'.$parsedPath['pageType'].'/';
		} elseif (strncmp($parsedPath['pageType'], self::SECOND_PAGE_DIR, strlen(self::SECOND_PAGE_DIR) - 1) == 0) {
			$pageDir = self::WEI_SITES_LOCAL_ROOT_DIR.$parsedPath['account'].'/'.$parsedPath['weisiteId'].'/'.$parsedPath['pageType'].'/'.$parsedPath['2ndPageId'].'/';
		} else {
			Yii::erorr("Wrong page type: $pagePath");
			return FALSE;
		}

		if (!file_exists($pageDir) || !is_dir($pageDir)) {
			Yii::error("Invalid page directory: not exist, $pageDir");
			return FALSE;
		}

		// 
		$pagePath = $pageDir.self::EDITABLE_PAGE_NAME;
		Yii::info("The editable page path: $pagePath");

		//打开文件
		$handle = fopen($pagePath, "w+");
		if (FALSE == $handle) {
			Yii::error("Failed to open file($pagePath)");
			return FALSE;
		}

		$ret = fwrite($handle, $content);
		if (FALSE == $ret) {
			Yii::error("Failed to write file($pagePath)");
			if (FALSE == fclose($handle)) {
				Yii::error("Failed to close file($pagePath)");
				return FALSE;
			}
			return FALSE;
		}

		if (FALSE == fclose($handle)) {
			Yii::error("Failed to close file($pagePath)");
			return FALSE;
		}

		Yii::info("Success to save the editable page path: $pagePath");

		return $pagePath;
	}

	public function getEditablePagePath($pagePath) {
		// 检查$pagePath是否合法
		// $ret = $this->validatePagePath($pagePath);
		// if (FALSE == $ret) {
		// 	Yii::error("Invalid page path: $pagePath");
		// 	return FALSE;
		// }
		// list($account, $weisiteId, $pageType, $fileName) = $ret;

		// // 生成网页所在的目录路径
		// $pageDir = self::WEI_SITES_LOCAL_ROOT_DIR.$account.'/'.$weisiteId.'/'.$pageType.'/';
		$parsedPath = $this->pageAbsPagePath($pagePath);
		if (is_null($parsedPath)) {
			Yii::error("Failed to parse page path: $pagePath");
			return FALSE;
		}

		if (strncmp($parsedPath['pageType'], self::FIRST_PAGE_DIR, strlen(self::FIRST_PAGE_DIR) - 1) == 0) {
			$pageDir = self::WEI_SITES_LOCAL_ROOT_DIR.$parsedPath['account'].'/'.$parsedPath['weisiteId'].'/'.$parsedPath['pageType'].'/';
		} elseif (strncmp($parsedPath['pageType'], self::SECOND_PAGE_DIR, strlen(self::SECOND_PAGE_DIR) - 1) == 0) {
			$pageDir = self::WEI_SITES_LOCAL_ROOT_DIR.$parsedPath['account'].'/'.$parsedPath['weisiteId'].'/'.$parsedPath['pageType'].'/'.$parsedPath['2ndPageId'].'/';
		} else {
			Yii::erorr("Wrong page type: $pagePath");
			return FALSE;
		}

		if (!file_exists($pageDir) || !is_dir($pageDir)) {
			Yii::error("Invalid page directory: not exist, $pageDir");
			return FALSE;
		}

		// 
		$pagePath = $pageDir.self::EDITABLE_PAGE_NAME;
		Yii::info("The editable page path: $pagePath");
		return $pagePath;
	}

	public function isOriginalUrl($url) {
		if (strncmp($url, self::WEI_SITES_URL_ROOT_DIR, strlen(self::WEI_SITES_URL_ROOT_DIR)) != 0) {
			return false;
		}

		return true;
	}

	private function queryWeiSiteInfoByShortUrlSql($account, $shortUrl) {
		$sql = "SELECT WeiName, WeiText, OriginUrl from $this->WEI_SITE_DB where Account=:account And DestUrl=:shortUrl";
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
			Yii::error("Failed to query the wei-site information corresponding to the short url($shortUrl)");
			return BizErrcode::ERR_FAILED;
		} elseif (count($ret) == 0) {
			Yii::error("The short url does not exist in the database");
			return BizErrcode::ERR_FAILED;
		} elseif (count($ret) > 1) {
			Yii::error("Multiple wei-sites exist corresponding to the short url($shortUrl) in database");
			return BizErrcode::ERR_FAILED;
		}

		return [$ret[0]['WeiName'], $ret[0]['WeiText'], $ret[0]['OriginUrl']];
	}

	/**
	* 将模版需要的文件复制到对应的微网站的目录中
	* @param string $sourceTemplate 模版文件夹的绝对路径
	* @param string $destTemplate 微网站网页所在的目录的绝对路径
	* @return 如果成功，返回网页文件的（即html文件）的绝对路径，否则返回null
	*/
	public function copyTemplate($sourceTemplate, $destTemplate) {

		if (!file_exists($sourceTemplate) || !is_dir($sourceTemplate)) {
			Yii::error("Template folder($sourceTemplate) does not exist");
			return null;
		}
		if (!file_exists($destTemplate) || !is_dir($destTemplate)) {
			Yii::error("Wei-site directory($destTemplate) does not exist");
			return null;
		}

		if (strncmp("/", substr($destTemplate, -1, 1), 1) != 0) {
			$destTemplate = $destTemplate."/";
		}

		// 将微网站目录中的文件全都删掉
		$fileList = scandir($destTemplate);
		foreach ($fileList as $key => $fileName) {
			if (strncmp($fileName, ".", 1) == 0
				|| strncmp($fileName, "..", 2) == 0) {
				// do nothing
			} else {
				if(unlink($destTemplate.$fileName) == FALSE) {
					Yii::error("Failed to delete file $destTemplate$fileName");
					return false;
				} else {
					Yii::info("Success to delete file $destTemplate$fileName");
				}
			}
		}

		// 复制相应的模版文件到微网站目录中
		$pagePath = null;
		$fileList = scandir($sourceTemplate);
		foreach ($fileList as $key => $filename) {
			if ($this->is_html($filename) || $this->is_css($filename)) {
				if (!copy($sourceTemplate."/".$filename, $destTemplate.$filename)) {
					Yii::error("Failed to copy $sourceTemplate to $destTemplate");
					continue;
				}
				if ($this->is_html($filename)) {
					$pagePath = $destTemplate.$filename;

					$editablePagePath = $destTemplate.self::EDITABLE_PAGE_NAME;
					if (!copy($sourceTemplate."/".$filename, $editablePagePath)) {
						Yii::error("Failed to copy $sourceTemplate/$filename to $editablePagePath");
					}
					Yii::info("Wei-site first page path: $pagePath");
				}
			}
		}

		return $pagePath;
	}

	/**
	 * 用来获取此账户下所有没有被删除的微网站信息
	 * @param string $account 要获取的微网站所属的账户名
	 * @return 
	*/
	private function getAllWeiSitesSql($account) {
		$sql = "SELECT WeiName, WeiPic, WeiText, DestUrl, OriginUrl FROM $this->WEI_SITE_DB where Account=:account AND DeleteFlag='0'";
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
		}

		return $ret;
	}

	/**
	 * 用来删除此账户下的微网站，实际只是将此微网站的DeleteFlag设置成1
	 * @param string $account 要删除的微网站所属账户
	 * @param string $shortUrl 要删除的微网站所对应的首页短链接
	 * @return 
	*/
	private function deleteWeiSiteSql($account, $shortUrl) {
		$sql = "UPDATE $this->WEI_SITE_DB SET DeleteFlag='1' WHERE Account=:account AND DestUrl=:shortUrl";
		$params[':account'] = $account;
		$params[':shortUrl'] = $shortUrl;

		return [$sql, $params];
	}

	public function deleteWeiSite($account, $shortUrl) {
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->deleteWeiSiteSql($account, $shortUrl);
		Yii::info("query sql: $sql");
		$ret = $db_handler->execute($sql, $params);

		return $ret;
	}
}
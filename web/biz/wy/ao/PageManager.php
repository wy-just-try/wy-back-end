<?php
namespace app\wy\ao;

use Yii;
use includes\BizErrcode;
use app\wy\ao\UrlConverter;

class PageManager {
	
	//const PAGE_LOCAL_ROOT_DIR = '/Users/apple/software/project/workspace/wy/src/wy-back-end/web/pages/';
	const PAGE_LOCAL_ROOT_DIR = '/data/back/web/pages/';
	const PAGE_URL_ROOT_DIR = 'http://wy626.com/web/pages/';
	const PAGE_DIR_PREFIX = 'page_';
	const PAGE_FILE_NAME = 'page.shtml';

	/**
	 * 判断此url链接是否是原始链接，通过看此url链接是否以PAGE_URL_ROOT_DIR开头来确定
	 * @param string $url 要检查的url
	 * @return 如果是原始链接则返回true; 否则返回false
	*/
	private function isOriginalUrl($url) {
		if (strlen($url) >= strlen(self::PAGE_URL_ROOT_DIR)
			&& strncmp($url, self::PAGE_URL_ROOT_DIR, strlen(self::PAGE_URL_ROOT_DIR)) == 0) {
			return true;
		}

		return false;
	}

	/**
	 * 用来将url转换成服务器本地的路径，如果url是短链接，需要先恢复成原始链接
	 * @param string $url 需要转换的url
	 * @return 如果成功则返回本地路径且是绝对路径，否则返回null
	*/
	public function resolveUrl($url) {
		// To-do 检查$url是否合法
		
		// 
		$originalUrl = $url;
		if (!$this->isOriginalUrl($originalUrl)) {
			$originalUrl = UrlConverter::getInstance()->convertUrl(false, $url);
		}

		$localPath = self::PAGE_LOCAL_ROOT_DIR.substr($originalUrl, strlen(self::PAGE_URL_ROOT_DIR));
		Yii::info("$url => $originalUrl => local path: $localPath");

		return $localPath;
	}

	/**
	 * 用来将本地的图文页面路径转换成url
	 * @param string $path 要转换的本地路径，是绝对路径
	 * @return 如果成功，则返回相应的url，否则返回null
	*/
	public function createUrl($path) {
		if (strncmp($path, self::PAGE_LOCAL_ROOT_DIR, strlen(self::PAGE_LOCAL_ROOT_DIR)) != 0) {
			Yii::error("$path is not page path, it should start with self::PAGE_LOCAL_ROOT_DIR");
			return null;
		}

		return self::PAGE_URL_ROOT_DIR.substr($path, strlen(self::PAGE_LOCAL_ROOT_DIR));
	}

	/**
	 * 
	*/
	private function createNewPageDir($account) {
		//在微网站的目录中查找当前用户的微网站目录是否创建，此用户的目录必须在创建首页时被创建
		//因此如果没有，则认为调用错误
		$pageUserDir = self::PAGE_LOCAL_ROOT_DIR.$account."/";
		if (!file_exists($pageUserDir) || !is_dir($pageUserDir)) {
			Yii::error("图文页面的用户目录$pageUserDir不存在");
			return null;
		}

		//在此用户的微网站目录中查找最新的并且是正在被编辑的微网站id
		//如果用户的微网站目录中，只有首页目录被创建且里面有文件，并且二级目录没有被创建
		//则认为此微网站是active
		$lastPageNumber = 0;
		$pageDirs = scandir($pageUserDir);
		if (!is_array($pageDirs) || count($pageDirs) < 2) {
			Yii::error("读取图文页面的用户目录$pageUserDir");
			return null;
		}
		sort($pageDirs, SORT_NATURAL);

		$latestPageNumber = -1;
		for ($num = count($pageDirs)-1; $num >= 0; $num--) {
			$pageDir = $pageDirs[$num];
			Yii::info("Checking page directory: $pageUserDir$pageDir");
			if (is_dir($pageUserDir.$pageDirs[$num])
				&& strlen($pageDirs[$num]) > strlen(self::PAGE_DIR_PREFIX)
				&& strncmp($pageDirs[$num], self::PAGE_DIR_PREFIX, strlen(self::PAGE_DIR_PREFIX)) == 0) {
				$latestPageNumberStr = substr($pageDirs[$num], strlen(self::PAGE_DIR_PREFIX));
				if (is_numeric($latestPageNumberStr)) {
					Yii::info("Found the latest page dir: $pageUserDir.$pageDir");
					$latestPageNumber = intval($latestPageNumberStr);
					break;
				}			
			}
		}

		$newPageDir = $pageUserDir.self::PAGE_DIR_PREFIX.($latestPageNumber+1)."/";
		Yii::info("The account($account)'s page directory: $newPageDir");
		if (!mkdir($newPageDir, 0777, true)) {
			Yii::error("创建微网站目录失败$newPageDir");
			return null;
		}

		return $newPageDir;
	}

	/**
	 * 生成此账户创建的图文页面文件
	 * @param string $account 账户名称
	 * @return 返回此账户创建的图文页面的绝对路径，否则返回null
	*/
	public function createPagePath($account) {

		$pageUserDir = self::PAGE_LOCAL_ROOT_DIR.$account;
		// 检查此账户在图文页面文件是否存在
		if (!file_exists($pageUserDir)
			|| !is_dir($pageUserDir)) {
			if (!mkdir($pageUserDir, 0777, true)) {
				Yii::error("Failed to create page directory: $pageUserDir");
				return null;
			}
		}

		// 查找此账户创建的最新的图文页面id
		$pageDir = $this->createNewPageDir($account);
		if (is_null($pageDir)) {
			Yii::error("Failed to create the account($account)'s page directory");
			return null;
		}

		$pagePath = $pageDir.self::PAGE_FILE_NAME;
		Yii::info("The account($account)'s page file path: $pagePath");

		return $pagePath;
	}

	/**
	 * 将content写到指定的文件中，如果文件不存在，则创建后，再写入；如果存在，则覆盖此文件
	 * @param string $pagePath 指定的文件路径
	 * @param string $content 要写入的内容
	 * @return 如果成功，则返回true; 否则返回false
	*/
	public function writePageContent($pagePath, $content) {
		if (file_exists($pagePath) && is_file($pagePath) && !is_writeable($pagePath)) {
			Yii::error("The file($pagePath) isn't written because of right");
			return false;
		}

		$fileHandler = fopen($pagePath, 'w');
		if (FALSE == $fileHandler) {
			Yii::error("Failed to open the page file: $pagePath");
			return false;
		}

		$count = fwrite($fileHandler, $content);
		if (FALSE == $count || $count < strlen($content)) {
			Yii::error("Failed to write content to page file: $pagePath");
			if (FALSE == fclose($fileHandler)) {
				Yii::error("Failed to close page file: $pagePath");
			}
			return false;
		}

		if (FALSE == fclose($fileHandler)) {
			Yii::error("Failed to close page file: $pagePath");
			return false;
		}

		return true;
	}

	/**
	 * 用来删除图文页面及对应的目录
	 * @param string $pagePath 要删除的图文页面路径
	 * @return 如果成功则返回true, 否则返回false
	*/
	public function deletPageDir($pagePath) {
		$slashPos = strrpos($pagePath, '/');
		if (false == $slashPos) {
			Yii::error("$pagePath is not valide path");
			return true;
		}

		$pageDir = substr($pagePath, 0, $slashPos);
		if (!file_exists($pageDir)
			|| !is_dir($pageDir)) {
			Yii::info("$pageDir is not directory, no need to delete it");
			return true;
		} 

		$fileList = scandir($pageDir);
		foreach ($fileList as $key => $fileName) {
			if (strncmp($fileName, ".", 1) == 0
				|| strncmp($fileName, "..", 2) == 0) {
				// do nothing
			} else {
				if(unlink($pageDir."/".$fileName) == FALSE) {
					Yii::error("Failed to delete file $pageDir/$fileName");
					return false;
				} else {
					Yii::info("Success to delete file $pageDir/$fileName");
				}
			}
		}

		return true;
	}

	private $PAGE_TABLE = 'PageInfo';

	private function insertPageInfoSql($account, $pagePath, $title, $desc, $originalUrl, $shortUrl) {
		$now = date('Y-m-d H-i-s');
		$sql = "INSERT INTO {$this->PAGE_TABLE} (Account, FileName, PageName, PageDesc, OriginUrl, DestUrl, InsertTime, ModifyTime) VALUES (:account, :filePath, :title, :description, :originalUrl, :shortUrl, '{$now}', '{$now}')";
		$params[':account'] = $account;
		$params[':filePath'] = $pagePath;
		$params[':title'] = $title;
		$params[':description'] = $desc;
		$params[':originalUrl'] = $originalUrl;
		$params[':shortUrl'] = $shortUrl;

		return [$sql, $params];
	}

	public function insertPageInfo($account, $pagePath, $title, $desc, $original, $shortUrl) {

		$db_handler = Yii::$app->db->getSvcDb();
		list($sql, $params) = $this->insertPageInfoSql($account, $pagePath, $title, $desc, $original, $shortUrl);

		Yii::info("sql: $sql");
		$ret = $db_handler->insert($sql, $params);
		if (false == $ret) {
			Yii::error("Failed to insert page info to database");
			return FALSE;
		}

		return TRUE;
	}

	private function updatePageInfoSql($account, $title, $desc, $shortUrl) {
		$now = date('Y-m-d H-i-s');
		$sql = "UPDATE {$this->PAGE_TABLE} SET PageName=:title, PageDesc=:description, ModifyTime='{$now}' WHERE Account=:account AND DestUrl=:shortUrl";
		$params[':account'] = $account;
		$params[':title'] = $title;
		$params[':description'] = $desc;
		$params[':shortUrl'] = $shortUrl;

		return [$sql, $params];
	}

	public function updatePageInfo($account, $title, $desc, $shortUrl) {
		$db_handler = Yii::$app->db->getSvcDb();
		
		list($sql, $params) = $this->updatePageInfoSql($account, $title, $desc, $shortUrl);
		$ret = $db_handler->execute($sql, $params);
		if (false == $ret) {
			Yii::error("Failed to update page info to database");
			return FALSE;
		}

		return TRUE;

	}

	private function getPageInfoSql($account, $shortUrl) {

		$sql = "SELECT PageName, PageDesc, DestUrl from {$this->PAGE_TABLE} WHERE Account=:account AND DestUrl=:shortUrl AND DeleteFlag='0'";
		$params[':account'] = $account;
		$params[':shortUrl'] = $shortUrl;

		return [$sql, $params];
	}

	public function getPageInfo($account, $shortUrl) {

		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->getPageInfoSql($account, $shortUrl);
		$ret = $db_handler->getOne($sql, $params);
		if(FALSE == $ret) {
			Yii::error("Failed to get all of page info of the account($account)");
			return FALSE;
		}

		return $ret;
	}

	private function getAllPageInfoSql($account) {
		$sql = "SELECT PageName, PageDesc, DestUrl from {$this->PAGE_TABLE} WHERE Account=:account AND DeleteFlag='0'";
		$params[':account'] = $account;

		return [$sql, $params];
	}

	public function getAllPageInfo($account) {
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->getAllPageInfoSql($account);
		$ret = $db_handler->getAll($sql, $params);
		if (!is_array($ret)) {
			Yii::error("Failed to get all of page info of the account($account)");
			return FALSE;
		} elseif(count($ret) == 0) {
			Yii::error("This account($account) does not create any page");
		}

		return $ret;
	}

	private function deletePageInfoSql($account, $shortUrl) {
		$sql = "UPDATE {$this->PAGE_TABLE} SET DeleteFlag='1' WHERE DestUrl=:shortUrl AND Account=:account";
		$params[':account'] = $account;
		$params[':shortUrl'] = $shortUrl;

		return [$sql, $params];
	}

	public function deletePageInfo($account, $shortUrl) {
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->deletePageInfoSql($account, $shortUrl);
		$ret = $db_handler->execute($sql, $params);
		if (FALSE == $ret) {
			Yii::error("Failed to delete page info whose short url is $shortUrl");
			return FALSE;
		}

		return TRUE;
	}
}
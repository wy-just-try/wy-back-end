<?php
namespace app\wy\ao;

use Yii;
use includes\BizErrcode;

/**
** 用来管理服务器上的模板文件
*/
class TemplateManager {

	// /data/front/html/template/
	const TEMPLATE_PATH_ROOT_DIR = '/Users/apple/software/project/workspace/wy/src/wy-back-end/web/templates/';

	private $db_template_index_table_name = 'TempIndex';

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

	/**
	 * 此函数用来将模板ID转换成模板在服务器上的路径
	 * 模板ID的格式: template-template-[1st|2nd]-template-{INDEX}-{File Name}
	 * 对应的路径为: /template/template/[1st|2nd]/template-{INDEX}/{File Name}.html
	 * @param string $tempId 模板文件ID，实际对应于数据库TempIndex表中的FileName字段
	 * @return 如果成功，返回模板ID在服务器上对应的路径名；否则返回空
	 */
	public function createTemplatePath($tempType, $tempId) {
		$path = '';
		// 检查参数是否合法
		if (!is_string($tempId) && strlen($tempId) == 0) {
			Yii::error("模板ID不合法");
			return null;
		}

		// 
		$path = sprintf("%s%s", self::TEMPLATE_PATH_ROOT_DIR, "template/template/1st/template-1/323915.html");

		Yii::info("path: $path");

		return $path;
	}

	private function queryTemplatePath($templateType, $templateId) {
		$sql = "SELECT Name, Path FROM $this->db_template_index_table_name WHERE Type=:type AND Name=:name";
		$params[':type'] = $templateType;
		$params[':name'] = $templateId;

		return [$sql, $params];
	}

	public function getTemplateDirPath($templateType, $templateId) {
		// 检查参数是否合法
		if (!is_string($templateId) || strlen($templateId) == 0
			|| !is_string($templateType) || strlen($templateType) == 0) {
			Yii::error("模板ID不合法");
			return null;
		}

		// Fetch the user's account, password and username from db
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->queryTemplatePath($templateType, $templateId);
		Yii::trace("query sql: $sql");
		$ret = $db_handler->getOne($sql, $params);
		if (!is_array($ret) || count($ret) == 0) {
			Yii::error("模板路径为空");
			return BizErrcode::ERR_FAILED;
		}

		$path = self::TEMPLATE_PATH_ROOT_DIR."1st/".$ret['Name'];

		return $path;
	}

	const TEMPLATE_TEMPORARY_DIR_PREFIX = "../tmp/";

	public function createTemporaryTemplateDirectory($templateType, $templateId) {
		// 获取用户名
		$loginUser = new LoginBehavior();
		if (isset($_SESSION[$loginUser::sessName()])) {
			$account = $_SESSION[$loginUser->loginAccout()];
		} else {
			$account = "kfc";
		}

		$tmpDir = getcwd()."/".self::TEMPLATE_TEMPORARY_DIR_PREFIX.$account."/".$templateType."/".$templateId."/";

		// 创建临时目录
		if (!file_exists($tmpDir) || !is_dir($tmpDir)) {
			if (!mkdir($tmpDir, 0777, true)) {
				Yii::error("生成模板文件的临时目录错误");
				return null;
			}
		}

		Yii::info("临时目录: $tmpDir");

		return $tmpDir;
	}

	public function copyToTemporaryDir($templateType, $templateId, $templatePath) {
		$tmpDir = $this->createTemporaryTemplateDirectory($templateType, $templateId);
		if ($tmpDir == null) {
			Yii::error("生成模板文件的临时目录错误");
			return BizErrcode::ERR_FAILED;
		}

		if (!copy($templatePath, $tmpDir.basename($templatePath))) {
			Yii::error("复制$templatePath到$tmpDir目录失败");
			return BizErrcode::ERR_FAILED;
		}

		return BizErrcode::ERR_OK;
	}

}
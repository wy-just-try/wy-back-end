<?php
namespace app\wy\ao;

use Yii;
use includes\BizErrcode;

/**
** 用来管理服务器上的模板文件
*/
class TemplateManager {

	/**
	 * In server, template root path is /data/front/html/template/
	 * One template's absolute path consists of template root path and template's filename, like:
	 * 
	 * self::TEMPLATE_PATH_ROOT_DIR + {FileName}
	 *
	 * FileName is obtained from database.
	*/
	// /data/front/html/template/
	const TEMPLATE_PATH_ROOT_DIR = '/Users/apple/software/project/workspace/wy/src/wy-back-end/web/templates/';

	// template directory path
	// 

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


	private function queryTempIndexByType() {
		$sql = "SELECT Id, Title, Description, ShowPic from $this->template_db where Type=:type";
		$params[':type'] = $this->type;

		return [$sql, $params];
	}

	/**
	 * 通过template type从数据库中获取所有的模板信息
	 * @param string $templateType 要获取的模板类型
	 * @return 如果成功返回获取到的所有模板信息；否则返回false
	*/
	public function queryAllTemplateInfo($templateType) {
		// Fetch the user's account, password and username from db
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->queryTempIndexByType();
		Yii::trace("query sql: $sql");
		$ret = $db_handler->getAll($sql, $params);
		if (!is_array($ret)) {
			Yii::error("模板索引为空");
			return FALSE;
		} elseif (count($ret) == 0) {
			Yii::error("获取到的模板索引为空");
			return FALSE;
		}

		return $ret;
	}

	private function queryTemplatePath($templateType, $templateId) {
		$sql = "SELECT Name, Path FROM $this->db_template_index_table_name WHERE Type=:type AND Id=:id";
		$params[':type'] = $templateType;
		$params[':id'] = $templateId;

		return [$sql, $params];
	}

	/**
	 * 用模板类型和模板id来获取此模板在服务器上的目录路径
	 * 先使用template type和template id从数据库中获取模板的路径名
	 * 再使用self::TEMPLATE_PATH_ROOT_DIR和获取到的路径名组合成模板的绝对路径
	 * @param string $templateType 模板类型
	 * @param string $templateId   模板id
	 * @return 如果成功则返回此模板在服务器上目录的绝对路径
	*/
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

		$path = self::TEMPLATE_PATH_ROOT_DIR.$ret['Name'];

		return $path;
	}

}
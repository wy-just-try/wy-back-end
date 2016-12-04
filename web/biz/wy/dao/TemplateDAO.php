<?php
namespace app\wy\dao;

use Yii;
use component\model\baseModel;
use includes\BizErrcode;
use app\wy\ao\LoginBehavior;
use app\wy\ao\TemplateManager;
use app\wy\ao\QCloud;

class TemplateDAO extends BaseModel {

	private $template_db = "TempIndex";

	public function init() {
		parent::init();
	}

	public function scenarios() {
		$scenarios = parent::scenarios();
		$scenarios['get-temp-index'] = ['type'];
		$scenarios['gen-temp'] = ['type', 'name'];

		return $scenarios;
	}

	public function rules() {
		return [
				[['type'], 'required', 'on' => 'get-temp-index'],
				[['type', 'name'], 'required', 'on' => 'gen-temp'],
		];
	}

	public function attributes() {
		return [
				'type',
				'name',
		];
	}

	public function selfAttributes()
	{
		return [];
	}

	public function defaultVals()
	{
		return [
			'type'	=>	'',
			'name'  =>  '',
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
		$sql = "SELECT FileName, Title, Description, ShowPic from $this->template_db where Type=:type";
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
			//return BizErrcode::ERR_NOLOGIN;
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
			$filename = $values['FileName'];
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

		// 
		$tempType = $input['type'];
		$tempId = $input['name'];

		// 在服务器上查找对应的模板
		$tempMgr = new TemplateManager();
		$tempPath = $tempMgr->createTemplatePath($tempId);
		if (!is_string($tempPath) || strlen($tempPath) == 0) {
			Yii::error('未找到模板ID对应的模板文件');
			return BizErrcode::ERR_FAILED;
		}

		// 将服务器上的模板上传到云服务器
		$cloudServer = QCloud::createInstance();

		// 调用新浪服务将首页模板的url转换成短链接

		return BizErrcode::ERR_OK;
	}
}
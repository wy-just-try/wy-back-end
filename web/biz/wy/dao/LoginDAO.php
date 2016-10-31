<?php
namespace app\wy\dao;

use Yii;
use component\model\BaseModel;
use includes\BizErrcode;
use includes\BizConst;

/**
*
*/
class LoginDAO extends BaseModel
{
	private $login_db_table = 'UserInfo';

	public function init()
	{
		parent::init();
	}

	//校验场景设置
	public function scenarios()
	{
		$scenarios = parent::scenarios();
		$scenarios['register'] = ['account','passwd','name','cellPhone','mailUrl','landLine','verifyPic','verifyMsg'];

		return $scenarios;
	}

	//参数校验规则
	public function rules()
	{
		return [
			[['account','passwd','name','cellPhone','verifyPic','verifyMsg'],'required','on'=>'register']
		];
	}

	//对象属性
	public function attributes()
	{
		return [
			'account',
			'passwd',
			'name',
			'cellPhone',
			'mailUrl',
			'landLine',
			'verifyPic',
			'verifyMsg'
		];
	}

	public function selfAttributes()
	{
		return [];
	}

	public function defaultVals()
	{
		return [
			'account'	=>	'',
			'passwd'	=>	'',
			'name'		=>	'',
			'cellPhone'	=>	'',
			'mailUrl'	=>	'',
			'landLine'	=>	'',
			'verifyPic'	=>	'',
			'verifyMsg'	=>	''
		];
	}

	public function register($input, &$output = [])
	{
		$this->setScenario('register');
		$this->load($input, '');
		$this->setDefaultVal();
		if (!$this->validate()) {
			Yii::error('注册接口传参验证失败');
			return BizErrcode::ERR_PARAM;
		}

		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->registerSql();
		$ret = $db_handler->insert($sql, $params);
		if (FALSE === $ret) {
			Yii::error("向数据库写入注册信息失败");
			return BizErrcode::ERR_DB;
		}

		$output['account'] = $this->account;
		$output['cellphone'] = $this->cellPhone;

		return BizErrcode::ERR_OK;
	}

	private function registerSql()
	{
		$now = date('Y-m-d H-i-s');
		$sql = "insert into {$this->login_db_table} (Account,Passwd,UserName,CellPhone,MailUrl,Landline,InsertTime,ModifyTime) values(:account,:passwd,:username,:cellphone,:mailurl,:landline,'{$now}','{$now}')";
		$params[':account'] = $this->account;
		$params[':passwd'] = $this->passwd;
		$params[':username'] = $this->name;
		$params[':cellphone'] = $this->cellPhone;
		$params[':mailurl'] = $this->mailUrl;
		$params[':landline'] = $this->landLine;

		return [$sql, $params];
	}
}
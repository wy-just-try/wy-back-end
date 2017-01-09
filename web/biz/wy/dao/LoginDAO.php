<?php
namespace app\wy\dao;

use Yii;
use component\model\BaseModel;
use includes\BizErrcode;
use includes\BizConst;
use app\wy\ao\LoginBehavior;
use app\wy\ao\Captcha;
use app\wy\ao\Massenger;

/**
*
*/
class LoginDAO extends BaseModel
{
	private $login_db_table = 'UserInfo';

	const USR_NAME_KEY = "username";
	const PASSWORD_KEY = "password";

	public function init()
	{
		parent::init();
	}

	//校验场景设置
	public function scenarios()
	{
		$scenarios = parent::scenarios();
		$scenarios['register'] = ['account','passwd','name','cellPhone','mailUrl','landLine','verifyPic','verifyMsg'];
		$scenarios['login'] = ['account', 'cellPhone', 'passwd', 'verifyPic'];
		$scenarios['logout'] = ['account'];
		$scenarios['repeat-register'] = ['account', 'cellPhone'];
		$scenarios['find-password'] = ['cellPhone', 'verifyPic', 'verifyMsg'];
		$scenarios['update-password'] = ['oldPassword', 'newPassword'];

		return $scenarios;
	}

	//参数校验规则
	public function rules()
	{
		return [
			[['account'], 'match', 'pattern' => '/^[a-z]\w{5,17}$/i', 'on' => 'register'],
			[['cellPhone'], 'match', 'pattern' => '/^1\d{10}$/i', 'on' => 'register'],
			[['mailUrl'], 'email', 'on' => 'register'],
			[['account','passwd','name','cellPhone','verifyPic','verifyMsg'],'required','on'=>'register'],
			[['account', 'passwd', 'verifyPic'], 'required', 'on'=>'login'],
			[['account'], 'required', 'on'=>'logout'],
			[['cellPhone', 'verifyPic', 'verifyMsg'], 'required', 'on'=>'find-password'],
			[['oldPasswd', 'newPasswd'], 'required', 'on'=>'update-password'],
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
			'verifyMsg',
			'oldPassword',
			'newPassword',
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
			'verifyMsg'	=>	'',
			'oldPassword' => '',
			'newPassword' => '',
		];
	}

	public function register($input, &$output = [])
	{
		$this->setScenario('register');
		$this->load($input, '');
		$this->setDefaultVal();
		if (!$this->validate()) {
			Yii::error('The parameters of register are wrong');
			return BizErrcode::ERR_REGISTER_FAILED;
		}

		// 检查图片验证码和短信验证码
		$captcha = new Captcha();
		if (!$captcha->verifyPicCaptcha($input['verifyPic'])) {
			Yii::trace('The picture captcha is wrong');
			return BizErrcode::ERR_WRONG_PIC_CAPTCHA;
		} 
		if (!$captcha->verifyMsgCaptcha($input['verifyMsg'])) {
			Yii::trace('The messager captcha is wrong');
			return BizErrcode::ERR_WRONG_MSG_CAPTCHA;
		}

		// 将用户信息添加到数据库
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->register_sql();
		Yii::trace("sql: $sql");
		$ret = $db_handler->insert($sql, $params);
		if (FALSE === $ret) {
			Yii::error("Failed to insert register information into database");
			return BizErrcode::ERR_REGISTER_FAILED;
		}

		$output['account'] = $this->account;
		$output['cellphone'] = $this->cellPhone;

		return BizErrcode::ERR_REGISTER_OK;
	}

	private function register_sql()
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

	/**
	 * 通过用户账号或者手机号从数据库中查找对应的账号、用户、密码
	 *
	 */
	private function queryUserInfoByAccountOrCellphone($loginName) {
		if (preg_match('/^1\d{10}$/i', $loginName) == 1) {
			$sql = "select Account, Passwd, UserName from $this->login_db_table where CellPhone=:account";
		} else {
			$sql = "select Account, Passwd, UserName from $this->login_db_table where Account=:account";
		}
		$params[':account'] = $loginName;

		return [$sql, $params];
	}

	/**
	 * 用来登录
	 */
	public function login($input, &$output = []) {
		// Check if the input parameters are validate or not
		if ($this->checkInputParameters('login', $input) != BizErrcode::ERR_OK) {
			Yii::error('The parameters of login are wrong');
			return BizErrcode::ERR_PARAM;
		}

		// Fetch the user's account, password and username from db
		$db_handler = Yii::$app->db->getSvcDb();
		list($sql, $params) = $this->queryUserInfoByAccountOrCellphone($input['account']);

		$loginname = $input['account'];		
		Yii::trace("input login name: $loginname, query sql: $sql");
		$ret = $db_handler->getAll($sql, $params);
		if (FALSE == $ret) {
			Yii::error("The user() doesn't exist");
			return BizErrcode::ERR_NO_ACCOUNT;
		} elseif (!is_array($ret)) {
			Yii::error("The user() doesn't exist");
			return BizErrcode::ERR_NO_ACCOUNT;
		} elseif (count($ret) == 0) {
			Yii::error("Fetched user info is empty");
			return BizErrcode::ERR_NO_ACCOUNT;
		}

		// Check if the input password is matched the one from db
		foreach ($ret as $index => $values) {
			// 前台会传md5后的数据，所以这里不用md5
			if ($input['passwd'] == $values['Passwd']) {
				Yii::info("The input password matches the real one");
				// 生成loginBehavior，初始化session和cookie
				$loginBehavior = new LoginBehavior();

				$captcha = new Captcha();
				if (!$captcha->verifyPicCaptcha($input['verifyPic'])) {
					Yii::error('The picture captcha of login is wrong');
					return BizErrcode::ERR_CAPTCHA;
				}

				$loginBehavior->initSessionAndCookie($values);

				// Login successfully
				Yii::info("Successfully login");

				return BizErrcode::ERR_LOGIN_OK;
			}
		}

		// Means the input password is wrong
		Yii::error("The input password is wrong");

		return BizErrcode::ERR_PASSWORD;
	}

	/**
	 * 通过用户账号从数据库中查找对应的账号、用户、密码
	 *
	 */
	private function queryUserInfoByAccount() {
		$sql = "select Account, Passwd, UserName from $this->login_db_table where Account=:account";
		$params[':account'] = $this->account;
		return [$sql, $params];
	}

	/**
	 * 用来退出登录
	 */
	public function logout($input, &$output = []) {
		// Check if the UserName exists in db
		if ($this->checkInputParameters('logout', $input) != BizErrcode::ERR_OK) {
			Yii::error('The parameters of logout are wrong');
			return BizErrcode::ERR_LOGOUT_FAILED;
		}

		// Check if the user already login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_CHECKLOGIN_ALREADY_LOGIN) {
			Yii::info('This user is not login');
			return BizErrcode::ERR_LOGOUT_OK;
		}

		// Clear session and cookie
		$loginBehavior->uninitSessionAndCookie();

		return BizErrcode::ERR_LOGOUT_OK;

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

	private function queryUserInfoByCellphone() {
		$sql = "select Account, Passwd, UserName from $this->login_db_table where CellPhone=:cellphone";
		$param[':cellphone'] = $this->cellPhone;

		return [$sql, $param];
	}

	/**
	 * 用来检查输入的账号或者手机号是否已经被注册
	 * @param array $input: 输入的参数
	 * @return BizErrcode::ERR_PARAM: 表示输入的参数错误
	 *         BizErrcode::ERR_NO_REGISTERED: 表示未注册
	 *         BizErrcode::ERR_REGISTERED: 表示已经注册
	 */
	public function checkRegistered($input, &$output = []) {
		// Check if the input parameters are valide or not
		if ($this->checkInputParameters('repeat-register', $input) != BizErrcode::ERR_OK) {
			Yii::error('The parameters of checking registered are wrong');
			return BizErrcode::ERR_PARAM;
		}

		$db_handler = Yii::$app->db->getSvcDb();
		foreach ($input as $key => $value) {
			$sql = null;
			$params = null;
			if ($key === 'account' && strlen($value) > 0) {
				list($sql, $params) = $this->queryUserInfoByAccount();
			} elseif ($key === 'cellPhone' && strlen($value) > 0) {
				list($sql, $params) = $this->queryUserInfoByCellphone();
			} else {
				continue;
			}

			if (strlen($sql) != 0 && count($params) != 0) {
				$ret = $db_handler->getOne($sql, $params);
				if (!is_array($ret)) {
					Yii::error("The user() doesn't exist");
					return BizErrcode::ERR_NO_REGISTERED;
				} elseif (count($ret) == 0) {
					Yii::error("Fetched user info is empty");
					return BizErrcode::ERR_NO_REGISTERED;
				} else {
					Yii::error('The account is used.');
					return BizErrcode::ERR_REGISTERED;
				}
			}
		}

		Yii::error('internal error');
		return BizErrcode::ERR_PARAM;
	}

	/**
	 * 用来生成ascii(33 ~ 126)之间的随机字符串
	 */
	private function randStr($length = 16) {
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= chr(mt_rand(33, 126));
		}
		return $str;
	}

	/**
	 * 用来生成更新密码的sql语句，其中$newPassword会进行一次md5，然后才保存到数据库中
	 * @param string $newPassword: 未进行过md5的密码
	 */
	private function updatePasswordSql($newPassword) {
		$sql = "update $this->login_db_table set Passwd=:password where CellPhone=:cellphone";
		$param[':password'] = md5($newPassword);
		$param[':cellphone'] = $this->cellPhone;

		return [$sql, $param];
	}

	/**
	 * 用来找回密码
	 * @param array $input: 用户输入的电话号码、图形验证码、短信验证码
	 * @return BizErrcode::ERR_OK: 表示找回密码成功
	 *         BizErrcode::ERR_WRONG_PIC_CAPTCHA： 表示图片验证码错误
	 *         BizErrcode::ERR_WRONG_MSG_CAPTCHA： 表示短信验证码错误
	 *         BizErrcode::ERR_UNREGISTERED_CELLPHONE： 表示该手机号未注册
	 *         BizErrcode::ERR_INTERNAL: 表示其他错误
	 */
	public function findPassword($input, &$output = []) {
		// Check if the input parameters are valide or not
		if ($this->checkInputParameters('find-password', $input) != BizErrcode::ERR_OK) {
			Yii::error('The parameters of finding password are wrong');
			return BizErrcode::ERR_INTERNAL;
		}

		// 检查手机号是否注册
		$db_handler = Yii::$app->db->getSvcDb();
		list($sql, $params) = $this->queryUserInfoByCellphone();
		if (strlen($sql) != 0 && count($params) != 0) {
			$ret = $db_handler->getOne($sql, $params);
			if (FALSE == $ret || !is_array($ret) || count($ret) == 0) {
				//Yii::error('此手机号码($input["cellPhone"]未注册')；
				return BizErrcode::ERR_UNREGISTERED_CELLPHONE;
			}
		}

		// 检查图片验证码和短信验证码
		$captcha = new Captcha();
		if (!$captcha->verifyPicCaptcha($input['verifyPic'])) {
			Yii::trace('The picture captcha is wrong');
			return BizErrcode::ERR_WRONG_PIC_CAPTCHA;
		} 
		if (!$captcha->verifyMsgCaptcha($input['verifyMsg'])) {
			Yii::trace('The messanger captcha is wrong');
			return BizErrcode::ERR_WRONG_MSG_CAPTCHA;
		}

		// 生成随机密码
		$newPassword = $this->randStr();
		$md5password = md5($newPassword);
		Yii::info("new random password: $newPassword => $md5password");

		// 发送新密码
		$massenger = Massenger::getInstance();
		if (!$massenger->sendNewPassword($input['cellPhone'], $newPassword)) {
			Yii::error('Failed to send random password');
			return BizErrcode::ERR_INTERNAL;
		}

		// update新密码到数据库
		list($sql, $params) = $this->updatePasswordSql($newPassword);
		if (strlen($sql) != 0 && count($params) != 0) {
			$ret = $db_handler->execute($sql, $params);
			if (FALSE == $ret) {
				Yii::error("Failed to update password");
				return BizErrcode::ERR_INTERNAL;
			}
		}

		return BizErrcode::ERR_OK;
	}


	/**
	 * 通过用户账号从数据库中查找对应的账号、用户、密码
	 *
	 */
	private function queryUserInfoBySessionAccount($account) {
		$sql = "select Account, Passwd, UserName from $this->login_db_table where Account=:account";
		$params[':account'] = $account;
		return [$sql, $params];
	}

	/**
	 * 用来生成更新密码的sql语句，其中$newPassword会进行一次md5，然后才保存到数据库中
	 * @param string $newPassword: 未进行过md5的密码
	 */
	private function updatePasswordSqlByAccount($newPassword, $account) {
		$sql = "update $this->login_db_table set Passwd=:password where Account=:account";
		$param[':password'] = $newPassword;
		$param[':account'] = $account;

		return [$sql, $param];
	}

	public function updatePassword($input, &$output = []) {

		// Check if the input parameters are valide or not
		if ($this->checkInputParameters('update-password', $input) != BizErrcode::ERR_OK) {
			Yii::error('The parameters of updating password are wrong');
			return BizErrCode::ERR_UPDATE_PASSWORD_FAILED;
		}

		// 检查用户是否登录
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_CHECKLOGIN_ALREADY_LOGIN) {
			Yii::error('This account is not login');
			return BizErrcode::ERR_NOT_LOGIN;
		}

		// 从数据库获取用户信息
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->queryUserInfoBySessionAccount($_SESSION[LoginBehavior::loginAccout()]);
		Yii::trace("query sql: $sql");
		$ret = $db_handler->getOne($sql, $params);
		if (!is_array($ret) || count($ret) == 0 || !isset($ret['Passwd'])) {
			Yii::error('This account is not found in database');
			return BizErrcode::ERR_NOT_LOGIN;
		}

		// 比较输入的旧密码是否正确
		if ($input['oldPasswd'] != $ret['Passwd']) {
			Yii::error('The old password is not right');
			return BizErrcode::ERR_WRONG_OLD_PASSWORD;
		}

		// update新密码到数据库
		list($sql, $params) = $this->updatePasswordSqlByAccount($input['newPasswd'], $_SESSION[LoginBehavior::loginAccout()]);
		if (strlen($sql) != 0 && count($params) != 0) {
			$ret = $db_handler->execute($sql, $params);
			if (FALSE == $ret) {
				Yii::error("Failed to update password");
				return BizErrcode::ERR_UPDATE_PASSWORD_FAILED;
			}
		}

		return BizErrcode::ERR_OK;
	}

	public function checkLogin($input, &$output = []) {
		$loginBehavior = new LoginBehavior();

		return $loginBehavior->checkLogin();
	}
}
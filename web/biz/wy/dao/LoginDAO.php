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
		$scenarios['login'] = ['account', 'passwd', 'verifyPic'];
		$scenarios['logout'] = ['name'];
		$scenarios['repeat-register'] = ['account', 'cellPhone'];
		$scenarios['find-password'] = ['cellPhone', 'verifyPic', 'verifyMsg'];

		return $scenarios;
	}

	//参数校验规则
	public function rules()
	{
		return [
			[['account','passwd','name','cellPhone','verifyPic','verifyMsg'],'required','on'=>'register'],
			[['account', 'passwd', 'verifyPic'], 'required', 'on'=>'login'],
			[['name'], 'required', 'on'=>'logout'],
			[['cellPhone', 'verifyPic', 'verifyMsg'], 'required', 'on'=>'find-password'],
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

		// 检查图片验证码和短信验证码
		$captcha = new Captcha();
		if (!$captcha->verifyPicCaptcha($input['verifyPic'])) {
			Yii::trace('图片验证码错误');
			return BizErrcode::ERR_WRONG_PIC_CAPTCHA;
		} elseif (!$captcha->verifyMsgCaptcha($input['verifyMsg'])) {
			Yii::trace('短信验证码错误');
			return BizErrcode::ERR_WRONG_MSG_CAPTCHA;
		}

		// 将用户信息添加到数据库
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->register_sql();
		Yii::trace("sql: $sql");
		$ret = $db_handler->insert($sql, $params);
		if (FALSE === $ret) {
			Yii::error("向数据库写入注册信息失败");
			return BizErrcode::ERR_DB;
		}

		$output['account'] = $this->account;
		$output['cellphone'] = $this->cellPhone;

		return BizErrcode::ERR_OK;
	}

	private function register_sql()
	{
		$now = date('Y-m-d H-i-s');
		$sql = "insert into {$this->login_db_table} (Account,Passwd,UserName,CellPhone,MailUrl,Landline,InsertTime,ModifyTime) values(:account,:passwd,:username,:cellphone,:mailurl,:landline,'{$now}','{$now}')";
		$params[':account'] = $this->account;
		$params[':passwd'] = md5($this->passwd);
		$params[':username'] = $this->name;
		$params[':cellphone'] = $this->cellPhone;
		$params[':mailurl'] = $this->mailUrl;
		$params[':landline'] = $this->landLine;

		return [$sql, $params];
	}

	/**
	 * 用来登录
	 */
	public function login($input, &$output = []) {
		// Check if the input parameters are validate or not
		if ($this->checkInputParameters('login', $input) != BizErrcode::ERR_OK) {
			Yii::error('登录接口传参验证失败');
			return BizErrcode::ERR_PARAM;
		}

		// Fetch the user's account, password and username from db
		$db_handler = Yii::$app->db->getSvcDb();

		list($sql, $params) = $this->queryUserInfoByAccount();
		Yii::trace("query sql: $sql");
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
			//$inputPasswd = md5($input['passwd']);
			//
			if (!strcmp(md5($input['passwd']), $values['Passwd'])) {
				// Login successfully
				Yii::trace("Successfully login");

				// 生成loginBehavior，初始化session和cookie
				$loginBehavior = new LoginBehavior();

				if (!$loginBehavior->verifyPicCaptcha($input['verifyPic'])) {
					Yii::error('登录的图片验证码错误');
					return BizErrcode::ERR_CAPTCHA;
				}

				$loginBehavior->initSessionAndCookie($values['UserName']);

				return BizErrcode::ERR_OK;
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
	 * 用来生成图片验证码
	 *
	 * @return 返回生成的图片验证码
	*/
	public function generatePicCaptcha() {

	}

	/**
	 * 用来退出登录
	 */
	public function logout($input, &$output = []) {
		// Check if the UserName exists in db
		if ($this->checkInputParameters('logout', $input) != BizErrcode::ERR_OK) {
			Yii::error('退出登录传入参数错误');
			return BizErrcode::ERR_PARAM;
		}

		// Check if the user already login
		$loginBehavior = new LoginBehavior();
		if ($loginBehavior->checkLogin() != BizErrcode::ERR_OK) {
			Yii::info('用户未登录');
			return BizErrcode::ERR_OK;
		}

		// Clear session and cookie
		$loginBehavior->uninitSessionAndCookie();

		return BizErrcode::ERR_OK;

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
		$sql = "select Account, UserName from $this->login_db_table where Cellphone=:cellphone";
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
			Yii::error('检查重复注册的参数错误');
			return BizErrcode::ERR_PARAM;
		}

		$db_handler = Yii::$app->db->getSvcDb();
		foreach ($input as $key => $value) {
			$sql = null;
			$params = null;
			if ($key === 'account') {
				list($sql, $params) = $this->queryUserInfoByAccount();
			} elseif ($key === 'cellPhone') {
				list($sql, $params) = $this->queryUserInfoByCellphone();
			} else {
				continue;
			}

			if (strlen($sql) != 0 && count($params) != 0) {
				$ret = $db_handler->getOne($sql, $params);
				if (FALSE == $ret) {
					Yii::error("此账号未被注册");
					return BizErrcode::ERR_NO_REGISTERED;
				} elseif (!is_array($ret)) {
					Yii::error("The user() doesn't exist");
					return BizErrcode::ERR_NO_REGISTERED;
				} elseif (count($ret) == 0) {
					Yii::error("Fetched user info is empty");
					return BizErrcode::ERR_NO_REGISTERED;
				} else {
					Yii::error('此账号已被注册');
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

	private function updatePassword($newPassword) {
		$sql = "update $this->login_db_table set Passwd=:password where Cellphone=:cellphone";
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
			Yii::error('找回密码的输入参数错误');
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
			Yii::trace('图片验证码错误');
			return BizErrcode::ERR_WRONG_PIC_CAPTCHA;
		} elseif (!$captcha->verifyMsgCaptcha($input['verifyMsg'])) {
			Yii::trace('短信验证码错误');
			return BizErrcode::ERR_WRONG_MSG_CAPTCHA;
		}

		// 生成随机密码
		$newPassword = $this->randStr();
		$md5password = md5($newPassword);
		Yii::info("new random password: $md5password");

		// update新密码到数据库
		list($sql, $params) = $this->updatePassword($newPassword);
		if (strlen($sql) != 0 && count($params) != 0) {
			$ret = $db_handler->execute($sql, $params);
			if (FALSE == $ret) {
				Yii::error("更新密码失败");
				return BizErrcode::ERR_INTERNAL;
			}
		}

		// 发送随机密码
		$massenger = new Massenger();
		if (!$massenger->sendMessage('新密码', $newPassword)) {
			Yii::error('发送随机密码失败');
			return BizErrcode::ERR_INTERNAL;
		}

		return BizErrcode::ERR_OK;
	}
}
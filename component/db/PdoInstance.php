<?php
/**
 * @描述 连接数据库这一块儿的代码，抽出来作为一个trait
 * @时间 2016年10月18日 15:54:31
 * @作者 Maker.xing
 */
namespace component\db;

use Yii;
use PDO;
use yii\base\Object;
use yii\base\InvalidConfigException;
use includes\BizConst;

class PdoInstance extends Object
{
	/**
	 * 保存PDO对象的实例，键名是md5(dsn+user+passwd)
	 */
	private $_pdoInstances = [];

	/**
	 * 保存db对象，map表，键名是md5(dsn+user+passwd)
	 */
	private $_dbInstances = [];

	/**
	 * 设置
	 */
	public $attributes = [];

	/**
	 * 获取PDO实例
	 */
	public function getPdo($dsn, $user, $passwd)
	{
		$hash = $this->instanceHash($dsn, $user, $passwd);
		if(!isset($this->_pdoInstances[$hash]) || !($this->_pdoInstances[$hash] instanceof PDO)) {
			try {
				$instance = new PDO($dsn, $user, $passwd);
				foreach ($this->attributes as $option => $value) {
					$instance->setAttribute($option, $value);
				}
				$this->_pdoInstances[$hash] = $instance;
			} catch (PDOException $e) {
				throw new Exception("get pdo instance failed!!!");
			}
		}
		return $this->_pdoInstances[$hash];
	}

	/**
	 * 根据数据库链接信息获取数据库链接
	 * @param string $dsn 数据库连接dsn信息
	 * @param string $user 连接数据库用户名
	 * @param string $passwd 连接数据库密码
	 * @return PdoDb object 包含PDO对象的PdoDb实例
	 * @throws InvalidConfigException 如果连接信息有错误返回该异常
	 * @throws Exception 如果获取数据库实例失败，返回该异常
	 */
	public function getDb($dsn, $user, $passwd)
	{
		$hash = $this->instanceHash($dsn, $user, $passwd);

		if(isset($this->_dbInstances[$hash]) && ($this->_dbInstances[$hash] instanceof PdoDb)) {
			return $this->_dbInstances[$hash];
		}

		$pdo = $this->getPdo($dsn, $user, $passwd);
		return $this->_dbInstances[$hash] = new PdoDb($pdo);
	}

	/**
	 * 通过service名称获取数据库对象
	 * 先根据$svc通过服务获取数据库连接信息，然后通过getDb获取连接
	 * @return PdoDb object返回包含PDO的数据库连接对象
	 */
	public function getSvcDb()
	{
		$mines = BizConst::DB_MINES;
		$host = BizConst::DB_HOST;
		$dbname = BizConst::DB_DATABASE;
		$charset = BizConst::DB_CHARSET;
		$dsn = "$mines:host=$host;dbname=$dbname;charset=$charset";
		return $this->getDb($dsn, BizConst::DB_USER, BizConst::DB_PASSWD);
	}

	/**
	 * 根据dsn、user、passwd得到md5值
	 * @param string $dsn 数据库连接dsn信息
	 * @param string $user 连接数据库用户名
	 * @param string $passwd 连接数据库密码
	 * @return string md5值
	 */
	public function instanceHash($dsn, $user, $passwd)
	{
		return md5($dsn.$user.$passwd);
	}

	/**
	 * 检测pdo连接数据库的时候信息是否齐全
	 */
	public function checkMysqlDsn($dsn, $user, $passwd)
	{
		if(!$dsn || !$user)
		{
			throw new InvalidConfigException("the wrong args to getInstance !!!");
		} else {
			return true;
		}
	}
}

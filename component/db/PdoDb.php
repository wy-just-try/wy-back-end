<?php
/**
 * @描述 数据库操作的一个实例
 * @时间 2016年10月18日 15:54:31
 * @作者 Maker.xing
 */
namespace component\db;

use PDO;
use yii\base\Object;
use yii\base\InvalidConfigException;

class PdoDb extends Object
{
	/**
	 * 连接数据库的PDO实例
	 */
	public $instance = null;

	/**
	 * array SQL查询时的参数
	 */
	private $_parameters = [];

	/**
	 * Object PDO statement object
	 */
	private $_sQuery = null;

	/**
	 * php类型和PDO类型对应的关系
	 */
	public static $typeMap = [
        'boolean' => PDO::PARAM_BOOL,
        'integer' => PDO::PARAM_INT,
        'string' => PDO::PARAM_STR,
        'resource' => PDO::PARAM_LOB,
        'NULL' => PDO::PARAM_NULL,
	];

	/**
	 * 构造函数
	 * 重写父类构造函数，关联本类中的instance实例
	 * @param PDO object $pdoInstance PDO连接实例
	 */
	public function __construct($pdoInstance)
	{
		if($pdoInstance instanceof PDO) {
			$this->instance = $pdoInstance;
		}
		parent::__construct();
	}

	/**
	 * 析构函数
	 * 关闭instance对应的PDO实例
	 */
	public function __destruct()
	{
		$this->instance = null;
	}

	/**
	 * prepare sql语句
	 * @return Object PDO statement
	 */
	private function _prepare($sql)
	{
		$this->_sQuery = $this->instance->prepare($sql);
	}

	/**
	 * 绑定变量操作
	 * 这里将值存放到$_parameters变量中
	 * @param string $name 绑定变量的名称
	 * @param mixed $value 绑定变量的值
	 */
	public function bind($name, $value)
	{
		$this->_parameters[$name] = $value;
	}

	/**
	 * 绑定多个变量操作
	 * 这里将值存放到$_parameters变量中
	 * @param string $name 绑定变量的名称
	 * @param mixed $value 绑定变量的值
	 */
	public function binds($params)
	{
		if(!is_array($params)) {
			return;
		}
		$this->_parameters = array_merge($this->_parameters, $params);
	}

	/**
	 * 绑定$_parameters中的变量到PDO statement中
	 */
	private function _bindValues()
	{
		if(!$this->_sQuery) {
			return;
		}
		foreach ($this->_parameters as $key => $value) {
			$this->_sQuery->bindValue($key, $value, $this->_dataType($value));
		}
	}

	/**
	 * 获取变量的类型
	 * @param $data mixed 要获取PDO中对应类型的变量
	 * @return integer PDO param type
	 */
	private function _dataType($data)
	{
		$type = gettype($data);
		return isset(self::$typeMap[$type]) ? self::$typeMap[$type] : PDO::PARAM_STR;
	}

	/**
	 * 每次执行前的初始化工作
	 * 清除查询语句的PDO statemeament
	 * 清楚查询语句的绑定参数
	 */
	public function init()
	{
		$this->_parameters = [];
		$this->_sQuery = null;
	}

	/**
	 * 插入新的数据
	 * @param string $sql 插入时要执行的sql语句 例如：insert into test(Uin, name) values(:uin, :name)
	 * @param array $params 键值map 例如： ['uin'=>123, 'name'=>'燕睿涛']
	 * @return boolean true/false 插入成功返回true，失败返回false
	 * @throws PDOException 如果插入过程中遇到异常，并且设置了PDO为异常模式，这里抛出异常
	 */
	public function insert($sql, $params = [])
	{
		$this->init();
		$this->_prepare($sql);
		$this->binds($params);
		$this->_bindValues();
		return $this->_sQuery->execute();
	}

	public function lastInsertId()
	{
		return $this->instance->lastInsertId();
	}

	/**
	 * 获取一行记录
	 * @param string $sql 要执行的sql语句
	 * @param array $params 要绑定到sql语句中的键值对
	 * @param constant $fetchMode 数据返回的模式，默认为关联数组
	 * @return mixed 成功时，返回数组，失败返回false
	 * @throws PDOException 如果插入过程中遇到异常，并且设置了PDO为异常模式，这里抛出异常
	 */
	public function getOne($sql, $params = [], $fetchMode = PDO::FETCH_ASSOC)
	{
		$this->init();
		$this->_prepare($sql);
		$this->binds($params);
		$this->_bindValues();
		$ret = $this->_sQuery->execute();
		if($ret) {
			return $this->_sQuery->fetch($fetchMode);
		} else {
			return false;
		}
	}

	/**
	 * 获取所有的记录
	 * @param string $sql 要执行的sql语句
	 * @param array $params 要绑定到sql语句中的键值对
	 * @param constant $fetchMode 数据返回的模式，默认为关联数组
	 * @return mixed 成功时，返回数组，失败返回false
	 * @throws PDOException 如果插入过程中遇到异常，并且设置了PDO为异常模式，这里抛出异常
	 */
	public function getAll($sql, $params = [], $fetchMode = PDO::FETCH_ASSOC)
	{
		$this->init();
		$this->_prepare($sql);
		$this->binds($params);
		$this->_bindValues();
		$ret = $this->_sQuery->execute();
		if($ret) {
			return $this->_sQuery->fetchAll($fetchMode);
		} else {
			return false;
		}
	}

	/**
	 * 执行一条sql语句，并返回是否执行成功
	 * @param string $sql 要执行的sql语句
	 * @param array $params 要绑定到sql语句中的键值对
	 * @return boolean 成功时，返回true，失败返回false
	 * @throws PDOException 如果插入过程中遇到异常，并且设置了PDO为异常模式，这里抛出异常
	 */
	public function execute($sql, $params = [])
	{
		$this->init();
		$this->_prepare($sql);
		$this->binds($params);
		$this->_bindValues();
		$ret = $this->_sQuery->execute();
		return $ret;
	}
}

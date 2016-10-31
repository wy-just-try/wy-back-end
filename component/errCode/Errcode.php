<?php
/**
 * @描述 对错误码的定义以及解释
 * @时间 2016年10月18日 18:16:30
 * @作者 Maker.xing
 */

namespace component\errCode;
/*
 * 使用很简单
 */

class Errcode
{
	//错误码的定义一律使用const
	const PROCESS_ERROR = -1;
	const NO_ERROR = 0;

	private static $allErrMsg = null;

	public static $errMsg = [
		'-1' => '操作失败',
		'0' => '操作成功',
	];

	public static function getErrMsg($id)
	{
		self::combineErrMsg();
		if(array_key_exists($id, self::$allErrMsg)) {
			return self::$allErrMsg[$id];
		}
		return self::$allErrMsg[self::UNKNOW_ERROR];
	}

	protected static function combineErrMsg()
	{
		if(self::$allErrMsg !== null) {
			return;
		}
		$class = get_called_class();
		$arrErrmsg = [$class::$errMsg];
		if(($class = get_parent_class($class))) {
			$arrErrmsg[] = $class::$errMsg;
		}
		while (($class = get_parent_class($class))) {
			$arrErrmsg[] = $class::$errMsg;
		}
		$errMsg = [];
		while (!empty($arrErrmsg)) {
			$arrErr = array_pop($arrErrmsg);
        	foreach ($arrErr as $k => $v) {
        		$errMsg[$k] = $v;
        	}
		}
		self::$allErrMsg = $errMsg;
	}
}

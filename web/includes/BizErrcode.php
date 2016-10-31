<?php
/**
 * @描述 京享街错误码的定义与描述
 * @时间 2016年09月16日
 * @作者 xhx
 */
namespace includes;
use component\errCode\Errcode;

/************** 错误码描述 *****************/
class BizErrcode extends Errcode {
	// 公共错误
	const ERR_FORBID_WRITE_BOSS_LOGIN = -5; // 免登录禁止写操作
	const UPLOAD_FAIL = -3; // 上传jss操作失败
	const ERR_DB = -2; // 操作DB出错
	const ERR_FAIL = -1; // 操作失败
	const ERR_OK = 0; // 操作成功
	const ERR_PARAM = 1; // 参数异常
	const ERR_PERMISSION = 2; // 没有访问权限
	const CHECKLOGIN_FAIL = 3; //登录校验失败
	const CHECKLOGIN_NOLOGIN = 4; //用户没有登录
	const NOLOGIN_NOREDIRECT = 5; //用户未登录，且不跳转登录页面
	const NOLOGIN_REDIRECT = 6; //用户未登录，跳转登录页面
	const ILLEGAL_PEODEL = 7; //删除用户非法
	const ILLEGAL_INPUT = 8; //非法输入
	const DIRTY_WORD_INPUT = 9; //输入数据含有敏感词汇


	public static $errMsg = [
		'-5' => '免登录禁止写操作',
		'-3' => '上传jss操作失败',
		'-2' => '操作DB出错',
		'-1' => '操作失败',
		'0' => '成功',
		'1' => '参数异常',
		'2' => '没有访问权限',
		'3' => '登录校验失败，请重新登录',
		'4' => '用户没有登录，请重新登录',
		'5' => '用户未登录，且不跳转登录页面',
		'6' => '用户未登录，跳转登录页面',
		'7' => '非法操作',
		'8' => '非法输入',
		'9' => '包含敏感词汇'
	];

	public static function getErrMsg($iErrCode) {
		$sMsg = self::$errMsg[$iErrCode];
		if (!$sMsg) {
			$sMsg = '系统错误';
		}
		return $sMsg;
	}
}

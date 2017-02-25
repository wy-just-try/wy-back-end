<?php

namespace app\c1001\common;
use component\errCode\Errcode;

/************** 错误码描述 *****************/
class C1001ErrCode extends Errcode {
    // 公共错误
    const CLIENT_SWEEP_FAIL = -1; // 扫码错误
    const INVALID_PARAMS = -2; // 无效参数
    const DB_FAIL = -3; // 无效参数
    const IMAGE_USER_TYPE_ERR = -4; // 无效参数--图片使用类型输入错误，要么为1-群二维码图片 2-公众号图片
    const SWEEP_MAX_NUM_ERR = -5; // 无效参数--设置的二维码扫描次数错误
    const UPLOAD_IMAGE_SIZE_ERR = -6; // 上传的图片大小为0
    const UPLOAD_IMAGE_TYPE_ERR = -7; // 上传的图片格式错误
    const UPLOAD_IMAGE_EXIST = -8; // 上传的图片已存在
    const UPLOAD_IMAGE_FAIL = -9; // 上传失败


    public static $errMsg = [
        '-9' => '上传失败,请重试!',
        '-8' => '该图片名称已存在,请换个名字再上传!',
        '-7' => '上传的图片格式错误,当前支持格式有:.gif,.tif,.bmp,.jpg,.jpef,.png',
        '-6' => '上传的图片大小为0',
        '-5' => '设置的二维码扫描次数错误',
        '-4' => '图片使用类型错误,要么为群二维码图片;要么为公众号图片',
        '-3' => 'Db操作失败',
        '-2' => '无效参数',
        '-1' => '扫码错误',
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

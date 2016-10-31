<?php
/**
 * 作者: Maker.xing
 * 创建日期: 2016/10/18 13:49
 * 文件描述：
 */
namespace component\helpers;

use yii\base\object;

class BrowserHelpers extends Object
{
    /**
     * 获取浏览器类型
     * @return string 浏览器类型
     */
    public static function getBrowser()
    {
        $agent=$_SERVER["HTTP_USER_AGENT"];
        $browserType = '';
        if(strpos($agent,'MSIE')!==false || strpos($agent,'rv:11.0')) //ie11判断
        {
            $browserType = "ie";
        }
        else if(strpos($agent,'Firefox')!==false)
        {
            $browserType = "firefox";
        }
        else if(strpos($agent,'Chrome')!==false)
        {
            $browserType = "chrome";
        }
        else if(strpos($agent,'Opera')!==false)
        {
            $browserType = "opera";
        }
        else if((strpos($agent,'Chrome')==false)&&strpos($agent,'Safari')!==false)
        {
            $browserType = "safari";
        }
        else
        {
            $browserType = "unknown";
        }

        return $browserType;
    }

}

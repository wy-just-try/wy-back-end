<?php
/**
 * Created by PhpStorm.
 * User: longzhenwen
 * Date: 2017/2/25
 * Time: 13:32
 */

namespace app\c1001\ao;
use app\c1001\dao\CC1001ImageInfoDao;
use yii;

class CAoClient
{
   public static function GetImage($oReq,&$oResp)
   {
       $oImageDao = new CC1001ImageInfoDao();
       $iRet = $oImageDao->QueryValidImageInfo($oReq,$oResp);
       if($iRet != 0)
       {
           Yii::error("ao QueryValidImageInfo fail,iRet:".$iRet);
           return $iRet;
       }

       Yii::info("ao QueryValidImageInfo success");
       return 0;
   }
    //扫图片--更新点击数量
    public static function Sweep($ImageId)
    {
        if(intval($ImageId) <= 0)
        {
            Yii::error("invalid params,imageid:".$ImageId);
            return -1;
        }

        $oImageDao = new CC1001ImageInfoDao();

        $iRet = $oImageDao->UpdateClickNum(intval($ImageId),1);
        if($iRet != 0)
        {
            Yii::error("dao update click num fail iRet:".$iRet);
            return $iRet;
        }

        Yii::info("ao sweep success");
        return 0;
    }
















}
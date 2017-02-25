<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/25
 * Time: 0:49
 */
namespace app\c1001\cgi;
use yii;
use app\c1001\cgi\BaseController;
use app\c1001\ao\CAoClient;
use app\c1001\common\C1001Const;
use app\c1001\common\C1001ErrCode;
class ClientController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    //拉取图片
    public function actionGetimage()
    {
        $httpParams = $this->GPValue();

        if($httpParams['ImageType'] != C1001Const::IMAGE_USE_TYPE_CROWD && $httpParams['ImageType'] != C1001Const::IMAGE_USE_TYPE_GZ)
        {
            Yii::error("invalid params,imagetype:".$httpParams['ImageType']);
            return $this->renderJson(C1001ErrCode::INVALID_PARAMS,[]);
        }

        $iRet = CAoClient::GetImage($httpParams,$this->retdata);
        if($iRet != 0)
        {
            Yii::error("get image fail,iret:".$iRet);
            return $this->renderJson($iRet,[]);
        }

        Yii::info("get image success");
        return $this->renderJson(0,$this->retdata);

    }
    //扫图片更新点击次数
    public function actionSweep()
    {
        $httpParams = $this->GPValue();

        if(intval($httpParams['id'] <= 0))
        {
            Yii::error("invalid params,id:".$httpParams['id']);
            return $this->renderJson(-1,[]);
        }

        $iRet = CAoClient::Sweep(intval($httpParams['id']));
        if($iRet != 0)
        {
            Yii::error("ao sweep fail,iret:".$iRet);
           return  $this->renderJson(-1,[]);
        }

        Yii::info("ao sweep success");
        return $this->renderJson(0,[]);
    }

}
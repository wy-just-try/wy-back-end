<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/25
 * Time: 0:41
 */
namespace app\c1001\cgi;
use app\c1001\common\C1001ErrCode;
use yii;
use app\c1001\cgi\BaseController;
use app\c1001\ao\CAoImage;

class ManageController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    //图片上传
    public function actionUpload()
    {
        $httpParams = $this->GPValue();
        $iRet = CAoImage::Upload($_FILES,$httpParams);
        if($iRet != 0)
        {
            Yii::error("upload fail,iret:".$iRet);
            return $this->renderJson($iRet,[]);
        }

        Yii::info("upload success");
        return $this->renderJson(0,[]);
    }

    //图片信息列表
    public function actionList()
    {
        $httpParams = $this->GPValue();

        $PageIndex = intval($httpParams['PageIndex']);
        $PageSize = intval($httpParams['PageSize']);

        $this->retdata['data'] = [];
        $this->retdata['total'] = 0;
        if($PageIndex < 0 || $PageSize <= 0)
        {
            Yii::error("invalid param,pageindex:".$PageIndex.";pagesize:".$PageSize);
            return $this->renderJson(C1001ErrCode::INVALID_PARAMS, $this->retdata);

        }

        $Req['PageIndex'] = $PageIndex;
        $Req['PageSize'] = $PageSize;

        $iRet = CAoImage::GetList($Req,$this->retdata);
        if($iRet != 0)
        {
            Yii::error("get list fail,iret:".$iRet);
            return $this->renderJson($iRet,$this->retdata);
        }

        return $this->renderJson(0,$this->retdata);
    }
    //图片删除
    public function actionDelete()
    {
        $httpParams = $this->GPValue();
        $Id = intval($httpParams['Id']);
        $ImageName = $httpParams['ImageName'];
        if($Id <= 0 || empty($ImageName))
        {
            Yii::error("invalid params,id:".$Id.";imagename:".$ImageName);
            return $this->renderJson(C1001ErrCode::INVALID_PARAMS,[]);
        }

        //删除db记录
        $iRet = CAoImage::Delete($Id,$ImageName);
        if($iRet != 0)
        {
            Yii::error("delete ao fail,iret:".$iRet);
            return $this->renderJson($iRet,[]);
        }

        Yii::info("delete success,id:".$Id);
        return $this->renderJson(0,[]);
    }
    //图片点击次数更新
    public function actionUpdspnum()
    {
        $httpParams = $this->GPValue();
        $Id = intval($httpParams['Id']);
        $MaxNum = intval($httpParams['MaxNum']);
        if($MaxNum <= 0)
        {
            Yii::error("invalid params,;maxnum:".$MaxNum);
            return $this->renderJson(C1001ErrCode::INVALID_PARAMS,[]);
        }
        //$Id = 0;
        $iRet = CAoImage::UpdateMaxNum($Id,$MaxNum);
        if($iRet != 0)
        {
            Yii::error("update ao fail,iret:".$iRet);
            return $this->renderJson($iRet,[]);
        }

        Yii::info("update click num success");
        return $this->renderJson(0,[]);
    }


}
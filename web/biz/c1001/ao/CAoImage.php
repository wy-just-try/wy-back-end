<?php
namespace app\c1001\ao;
use app\c1001\common\C1001ErrCode;
use yii;
use app\c1001\common\C1001Const;
use app\c1001\dao\CC1001ImageInfoDao;



class CAoImage
{
    
   public static function Upload($FileInfo,$httpParams)
   {
        //检验用户输入信息
       $iRet =CAoImage::CheckImageInput($httpParams);
       if($iRet != 0)
       {
           Yii::error("check image input fail,iret:".$iRet);
           return $iRet;
       }
       $ImagetName = time()."_".$FileInfo['imagefile']['name'];
       $FileInfo['imagefile']['name'] = $ImagetName;

       //校验图片文件本身信息
       $iRet = CAoImage::CheckImageFileInfo($FileInfo);
       if($iRet != 0)
       {
           Yii::error("check image file info fail,iret:".$iRet);
           return $iRet;
       }

       //写入图片文件
      // $ImagetName = $FileInfo['imagefile']['name'];
       $SrcPath = $FileInfo['imagefile']['tmp_name'];
       $desPath = C1001Const::IMAGE_SERVER_PATH.$ImagetName;
       $iRet = CAoImage::WriteImageFile($SrcPath,$desPath);
       if($iRet != 0)
       {
           Yii::error("write image fail,iRet:".$iRet);
           return $iRet;
       }

       //查入db
       $httpParams['Url']       = C1001Const::IMAGE_URL_PREFIX.$ImagetName;
       $httpParams['ImageName'] = $ImagetName;
       $oImageDao = new CC1001ImageInfoDao();
       $iRet = $oImageDao->Insert($httpParams);
       if($iRet != 0)
       {
           Yii::error("insert db fail,iRet:".$iRet);
           unlink($desPath);
           return $iRet;
       }

       //若是公众号图片，则删除数据库中原来的
       if($httpParams['ImageType'] == C1001Const::IMAGE_USE_TYPE_GZ)
       {
           $Ret = $oImageDao->QueryValidImageInfo($httpParams,$OldInfo);
           if($Ret != 0)
           {
               Yii::error("QueryValidImageInfo fail,iret:".$Ret);
               return $Ret;
           }

           if($OldInfo['Id'] <= 0 || empty($OldInfo['ImageName'] ))
           {
               return 0;
           }

           $Ret = self::Delete($OldInfo['Id'],$OldInfo['ImageName']);
           if($Ret != 0)
           {
               Yii::error("delete fail,iret:".$Ret);
               return $Ret;
           }
       }
       return 0;
    }

    //校验图片文件本身信息
    private static function CheckImageFileInfo($FileInfo)
    {
        //校验图片大小
        $ImageSize = $FileInfo['imagefile']['size'];
        if($ImageSize <= 0)
        {
            Yii::error("upload image size is 0");
            return C1001ErrCode::UPLOAD_IMAGE_SIZE_ERR;
        }
        //校验图片类型
        $ImageType = $FileInfo['imagefile']['type'];
        if(!in_array($ImageType,C1001Const::$imageType))
        {
            Yii::error("upload image type err,imagetype:".$ImageType);
            return C1001ErrCode::UPLOAD_IMAGE_TYPE_ERR;
        }

        //校验图片是否已存在
        $path = C1001Const::IMAGE_SERVER_PATH.$FileInfo['imagefile']['name'];
        if(file_exists($path))
        {
            Yii::error("image is exist,image:".$path);
            return C1001ErrCode::UPLOAD_IMAGE_EXIST;
        }

        return 0;

    }
    //校验用户输入信息
    private static function CheckImageInput($httpParams)
    {
        //校验图片使用类型
        $ImageType = intval($httpParams['ImageType']);
        if($ImageType != C1001Const::IMAGE_USE_TYPE_CROWD && $ImageType!= C1001Const::IMAGE_USE_TYPE_GZ)
        {
            Yii::error("image use type err,imagettype:".$ImageType);
            return C1001ErrCode::IMAGE_USER_TYPE_ERR;

        }
        $MaxClickNum = intval($httpParams['MaxNum']);
        if($MaxClickNum <= 0)
        {
            Yii::error("image use type err,imagettype:".$MaxClickNum);
            return C1001ErrCode::SWEEP_MAX_NUM_ERR;
        }

        return 0;
    }

    private static function WriteImageFile($TmpPath,$FileName)
    {
        if(!move_uploaded_file($TmpPath,$FileName))
        {
            Yii::error("move file fail,src path:".$TmpPath,";des path:".$FileName);
            return C1001ErrCode::UPLOAD_IMAGE_FAIL;
        }

        chmod($FileName,0777);
        Yii::error("move success");
        return 0;
    }

    public static function GetList($Req,&$vResp)
    {
        $oImageDao = new CC1001ImageInfoDao();
        $total = 0;
        $total = $oImageDao->GetTotal($Req);
        if($total <= 0)
        {
            Yii::error("query fail or data is empty");
            return $total;
        }
        $vResp['total'] = $total;

        $iRet = $oImageDao->QueryList($Req,$vResp['data']);
        if($iRet != 0)
        {
            Yii::error("query list fail,iret:".$iRet);
            return $iRet;
        }

        Yii::info("get list success");
        return 0;
    }
    public static function Delete($Id,$ImageName)
    {
        if($Id <= 0 || empty($ImageName))
        {
            Yii::error("invalid params,id is 0 or image is null");
            return C1001ErrCode::INVALID_PARAMS;
        }

        //删除db记录
        $Req['DelFlag'] = 1;
        $Req['Id'] = $Id;
        $oImageDao = new CC1001ImageInfoDao();
        $iRet = $oImageDao->Update($Req);
        if($iRet != 0)
        {
           Yii::error("dao delete fail,iret:".$iRet);
            return $iRet;
        }

        //删除本地文件
        $desPath = C1001Const::IMAGE_SERVER_PATH.$ImageName;
        unlink($desPath);

        Yii::info("delete success");
        return 0;
    }
    public static function UpdateMaxNum($Id,$MaxNum)
    {
        if($MaxNum <= 0)
        {
            Yii::error("invalid params,id:".$Id.";maxnum:".$MaxNum);
           return C1001ErrCode::INVALID_PARAMS;
        }

        $Req['Id'] = $Id;
        $Req['MaxNum'] = $MaxNum;

        $oImageDao = new CC1001ImageInfoDao();
        $iRet = $oImageDao->Update($Req);
        if($iRet != 0)
        {
            Yii::error("data UpdateMaxNum fail,iret:".$iRet);
            return $iRet;
        }

        Yii::info("UpdateMaxNum success");
        return 0;
    }

}
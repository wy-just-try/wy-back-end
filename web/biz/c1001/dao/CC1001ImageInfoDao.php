<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/25
 * Time: 0:56
 */
namespace app\c1001\dao;

use component\model\BaseModel;
use yii;
use app\c1001\common\C1001ErrCode;

class CC1001ImageInfoDao extends BaseModel
{
    private $tablename = "C1001_ImageInfo";

    public function init()
    {
        parent::init();
    }

    public function rules()
    {
        return [
        ];
    }
    public function attributes()
    {
        return [
            'Id',  //id
            'Url',//图片url
            'MaxNum',//最大扫描次数
            'ClickNum',        //已扫描次数
            'InsertTime',      //上传时间
            'DelFlag',       //是否删除 0-未删除  1-已删除
            'ImageType', //图片类型：1-群二维码图 2-公众号图片
            'ImageName',//图片名称
        ];
    }

    public function selfAttributes()
    {
        return [
            'PageIndex',//页号
            'PageSize',//页数
        ];
    }
    public function defaultVals()
    {
        return [
            'Id' => 0,  //id
            'Url'=> '',//图片url
            'MaxNum' => 0,//最大扫描次数
            'ClickNum' => 0,        //已扫描次数
            'InsertTime' => time(),      //上传时间
            'DelFlag' => 0,        //是否删除 0-未删除  1-已删除
            'ImageType' => 0, //图片类型：0-群二维码图 1-公众号图片
            'ImageName' => '',//图片名称
            'PageIndex' => 0,//页号
            'PageSize' => 0,//页数
        ];
    }

    public function UpdateClickNum($dwImageId,$dwSetClickVal)
    {
        if($dwImageId <= 0)
        {
            Yii::info("image is invalid");
            return -1;
        }
        Yii::info("imageid:".$dwImageId.";clicknum:".$dwSetClickVal);

        $params[":1"] = $dwImageId;
        $sql = "update ".$this->tablename." set ClickNum = ClickNum + 1 where Id = :1 ";

        Yii::info("update sql : [".$this->getSqlInfo($sql, $params)."]".";param:".var_export($params,true));

        $db_handler = Yii::$app->db->getSvcDb();
        $iRet = 0;
        $iRet = $db_handler->execute($sql, $params);
        if(false == $iRet)
        {
            Yii::error("update click fail");
            return -2;
        }

        Yii::info("update click success");
        return 0;
    }

    public function QueryValidImageInfo($oReq,&$Resp)
    {
        //查询存在扫描次数小于最大的，且上传时间最小的，未删除图片
        $params[':1'] = 0;
        $params[':2'] = intval($oReq['ImageType']);

        $sql = "select Id,Url,ImageType from ".$this->tablename." where ClickNum < MaxNum and DelFlag = :1 and ImageType = :2 order by InsertTime asc limit 1";

        Yii::info("update sql : [".$this->getSqlInfo($sql, $params)."]".";param:".var_export($params,true));

        $db_handler = Yii::$app->db->getSvcDb();
        $Resp = $db_handler->getOne($sql,$params);
        if($Resp == false)
        {
            Yii::error("query image fail");
            return C1001ErrCode::DB_FAIL;
        }

        Yii::info("QueryValidImageInfo success");
        return 0;
    }

    public function Insert($Req)
    {
        $this->setDefaultVal();
        $this->load($Req, "");

        $sql = "insert into ".$this->tablename."(";
        $sql  .=  implode(",", $this->attributes()).") values ";

        $params = [];
        $this->_BuildInsertSql($sql,$params);

        Yii::info("insert sql : [".$this->getSqlInfo($sql, $params)."]".";param:".var_export($params,true));

        $db_handler = Yii::$app->db->getSvcDb();
        $Resp = $db_handler->insert($sql,$params);
        if($Resp == false)
        {
            Yii::error("insert fail");
            return C1001ErrCode::DB_FAIL;
        }

        Yii::info("insert success");
        return 0;
    }
    private function _BuildInsertSql(&$sql,&$params)
    {
        $sql.= " (";
        foreach($this->attributes() as $value)
        {
            $params[":{$value}"] = $this->{$value};
            $sql.=":{$value},";
        }
        $sql = rtrim($sql,",");

        $sql.=" )";
    }

    public function QueryList($Req,&$vResp)
    {

        $this->setDefaultVal();
        $this->load($Req, "");

        $sql = "select ".implode(",", $this->attributes())." from ".$this->tablename." where DelFlag = :DelFlag ";
        $params[':DelFlag'] = 0;

        list($limit,$param_limit) = $this->_BuildLimit();

        $params = array_merge($params,$param_limit);
        $sql .=$limit;

        Yii::info("insert sql : [".$this->getSqlInfo($sql, $params)."]".";param:".var_export($params,true));

        $db_handler = Yii::$app->db->getSvcDb();
        $vResp = $db_handler->getAll($sql,$params);
        if($vResp == false)
        {
            Yii::error("query image fail");
            return C1001ErrCode::DB_FAIL;
        }

        Yii::info("query list success");
        return 0;
    }

    //分页
    public function _BuildLimit()
    {
        $sLimit = "";
        $params = array();

        if($this->PageIndex >0 && $this->PageSize > 0)
        {
            $sLimit .= " limit :begin,:end";
            $params[':begin'] = intval(($this->PageIndex - 1) * $this->PageSize);
            $params[':end'] = intval(($this->PageSize));
        }
        return [$sLimit,$params];
    }

    public function Update($Req)
    {
        $this->setDefaultVal();
        $this->load($Req, "");

        if($this->DelFlag > 0)
        {
            if($this->Id <= 0)
            {
                Yii::error("id is err,id:".$this->Id);
                return C1001ErrCode::INVALID_PARAMS;
            }
        }
        $sql = "update ".$this->tablename;
        list($params,$where) = $this->_BuildUpdateSql();
        $sql.=$where;

        Yii::info("update sql : [".$this->getSqlInfo($sql, $params)."]".";param:".var_export($params,true));

        $db_handler = Yii::$app->db->getSvcDb();
        $iRet = $db_handler->execute($sql,$params);
        if($iRet === false)
        {
            Yii::error("db fail,iret:".$iRet);
            return C1001ErrCode::DB_FAIL;
        }

        Yii::info("Update success");
        return 0;
    }

    private function _BuildUpdateSql()
    {
        $sql = " set ";
        $params = [];

        if($this->DelFlag > 0)
        {
            $params[':DelFlag'] = $this->DelFlag;
            $sql .= " DelFlag = :DelFlag ";
        }
        else if($this->MaxNum > 0)
        {
            $params[':MaxNum'] = $this->MaxNum;
            $sql .= " MaxNum = :MaxNum ";
        }

        $sql.= " where (1=1) ";
        if($this->Id > 0)
        {
            $params[':Id'] = $this->Id;
            $sql.=" and Id = :Id";
        }

        return [$params,$sql];
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/25
 * Time: 0:49
 */
namespace app\c1001\cgi;
use yii;
use component\controller\RenderController;

class ClientController extends RenderController
{
    public function init()
    {
        parent::init();
    }
    //拉取图片
    public function actionGetimage()
    {
        $httpParams = $this->GPValue();
        $iRet =






    }
    //扫图片更新点击次数
    public function actionSweep()
    {

    }

}
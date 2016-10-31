<?php
/**
 * @描述 渲染控制器基类
 * @时间 2016年10月19日 10:06:31
 * @作者 Maker.xing
 */
namespace component\controller;

use Yii;
use includes\BizErrcode;

class RenderController extends \component\controller\BaseController
{
    public $enableCsrfValidation = false;
    protected $retdata = [];
    /**
     * 返回错误码，Content-Type为json或者jsonp
     * @param $retcode integer
     * @param $retdata array
     * @return string 返回json串
     */
	public function renderJson($retcode = 0, Array $retdata = [])
	{
        $ret = [];
        $ret['errCode'] = $retcode;
        if(YII_DEBUG) {
            $ret['msg'] = BizErrcode::getErrMsg($ret['errCode']);
        }
        if(!empty($retdata)) {
            $ret = array_merge($ret, $retdata);
        }
        //先转化为utf-8
        $ret = $this->G2U($ret);
        //XSS转义
        // $ret = $this->XSSDefend($ret);
        Yii::$app->response->format = isset($ret['callback']) ? 'jsonp' : 'json';
        return $ret;
	}
    
    /**
     * 返回业务下面的异常信息
     * render Exception Json
     * @param $ErrorConst interger 错误码编号
     */
    public function renderEJson($Errcode)
    {
        Yii::$app->getResponse()->format = 'json';
        throw new BizException($Errcode);
    }
    
    /**
     * 渲染指定的模版页面
     * @param $view string 渲染的文件路径
     * @param $params array
     * @param $retcode integer
     */
    public function renderFile($view, $params = [])
    {
        $retcode = func_get_arg(2) === FALSE ? BizErrcode::NO_ERROR : func_get_arg(2);
        $params['retcode'] = $retcode;
        $params['retmsg'] = BizErrcode::getErrMsg($retcode);
        //先转化为utf-8
        $params = $this->G2U($params);
        $content = $this->getView()->render($view, $params, $this);
        return $content;
    }

    /**
     * 异常返回页面
     * render Exception File
     * @param $Errcode integer
     * @param $view string 要渲染的模版文件
     */
    public function renderEFile($Errcode, $view)
    {
        Yii::$app->getResponse()->format = 'html';
        throw new BizException($Errcode, $view);
    }
}

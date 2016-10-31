<?php
/**
 * 对errorhandler的继承
 * 2015年9月6日 14:43:04
 * Maker.xing
 */
namespace common\errCode;

use Yii;
use yii\base\UserException;
use yii\web\HttpException;
use common\exception\ViewExceptionInterface;

class BizErrorHandler extends \component\errCode\BaseErrorHandler
{
    protected function convertExceptionToArray($exception)
    {
        //定义一个系统错误
        if (!YII_DEBUG && !$exception instanceof UserException && !$exception instanceof HttpException) {
            $exception = new HttpException(200, 'There was an error at the server.');
        }

        $array = [];
        if($exception instanceof HttpException) {
            $array['errcode'] = $exception->getCode();
        }

        if(YII_DEBUG) {
            $array['errmsg'] = $exception->getMessage();
        }

        return $array;
    }

    /**
     * Renders the exception.
     * @param \Exception $exception the exception to be rendered.
     */
    protected function renderException($exception)
    {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            $response->isSent = false;
        } else {
            $response = new Response();
        }

        //是通过view渲染异常,Content-Type为text/html
        if(($exception instanceof ViewExceptionInterface) && ($view = $exception->getRenderView())) {
            $response->format = 'html';
            $response->data = Yii::$app->getView()->render($view, ['retcode' => $exception->getCode(), 'retmsg' => $exception->getMessage()]);
        } else {
            $response->format = 'json';
            $response->data = $this->convertExceptionToArray($exception);
        }

        if ($exception instanceof HttpException) {
            $response->setStatusCode($exception->statusCode);
        } else {
            $response->setStatusCode(200);
        }

        $response->send();
    }
}

<?php

namespace includes;

use Yii;
use yii\web\HttpException;
use common\exception\ViewExceptionInterface;
use includes\BizErrcode;

class BizException extends HttpException implements ViewExceptionInterface
{
    public $view = null;

    /**
     * Constructor.
     * @param integer $status HTTP status code, such as 404, 500, etc.
     * @param string $message error message
     * @param integer $code error code
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($errCode = 0, $view = null, $status = 200, \Exception $previous = null)
    {
        $this->view = $view;
        $message = JxjErrcode::getErrMsg($errCode);
        //$message = iconv('GBK', 'UTF-8', $message);
        parent::__construct($status, $message, $errCode, $previous);
    }

    public function getRenderView()
    {
        return $this->view ? $this->view : false;
    }
}

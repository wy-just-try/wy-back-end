<?php
namespace app\wy\cgi;

use Yii;
use component\controller\RenderController;
use app\wy\dao\MiscDAO;
use includes\BizErrcode;


class MiscController extends RenderController {


	public function actionGenPic() {

		$input = $this->GPValue();

		$miscDAO = new MiscDAO();
		$ret = $miscDAO->genPicCaptcha($input, $output);
		if ($ret != BizErrcode::ERR_OK) {
			Yii::error('生成图片验证码失败');
			return $ret;
		}

		//$this->retdata['data'] = $output;

		return $this->renderJson($ret, $this->retdata);

	}

	public function actionGenMsg() {

		$input = $this->GPValue();

		$miscDAO = new MiscDAO();
		$ret = $miscDAO->genMsgCaptcha($input, $output);
		if ($ret != BizErrcode::ERR_OK) {
			Yii::error('发送短信验证码失败');
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $this->renderJson($ret, $this->retdata);
	}
}
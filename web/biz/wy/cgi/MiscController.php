<?php
namespace app\wy\cgi;

use Yii;
use component\controller\RenderController;
use app\wy\dao\MiscDAO;
use includes\BizErrcode;


class MiscController extends RenderController {


	private function _actionGenPic() {

		$input = $this->GPValue();

		$miscDAO = new MiscDAO();
		$ret = $miscDAO->genPicCaptcha($input, $output);
		if ($ret != BizErrcode::ERR_OK) {
			Yii::error('生成图片验证码失败');
		}

		return $ret;
	}
	
	public function actionGenPic() {
		$ret = $this->_actionGenPic();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionGenMsg() {
		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			Yii::info("input[$key]: $value");
		}

		$miscDAO = new MiscDAO();
		$ret = $miscDAO->genMsgCaptcha($input, $output);
		if ($ret != BizErrcode::ERR_OK) {
			Yii::error('发送短信验证码失败');
			return $ret;
		}

		return $ret;
	}

	public function actionGenMsg() {
		$ret = $this->_actionGenMsg();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionUploadImg()
	{
		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			Yii::info("input[$key]: $value");
		}

		$miscDAO = new MiscDAO();
		$ret = $miscDAO->uploadImg($input, $output);
		if ($ret !== BizErrcode::ERR_OK) {
			Yii::error('上传图片失败');
			return $ret;
		}

		$this->retdata = $output;
		return $ret;
	}

	public function actionUploadImg()
	{
		$ret = $this->_actionUploadImg();

		return $this->renderJson($ret, $this->retdata);
	}
}
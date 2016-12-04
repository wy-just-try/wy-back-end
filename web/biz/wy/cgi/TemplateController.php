<?php
namespace app\wy\cgi;

use Yii;
use component\controller\RenderController;
use app\wy\dao\TemplateDAO;
use includes\BizErrcode;

class TemplateController extends RenderController {

	private function _actionGetTempIndex() {

		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			Yii::trace("key=$key, value=$value");
		}

		$tempDao = new TemplateDAO();
		$ret = $tempDao->getTemplateIndex($input, $output);
		if ($ret != BizErrcode::ERR_OK) {
			Yii::error("failed to get template index");
			return $ret;
		}

		$this->retdata['data'] = $output;

		if (is_array($output)) {
			foreach ($output as $key => $values) {
				$name = $values['FileName'];
				$title = $values['Title'];
				$desc = $values['Description'];
				$picUrl = $values['ShowPic'];
				Yii::info("$name, $title, $desc, $picUrl");
			}
		}

		return $ret;
	}

	public function actionGetTempIndex() {

		$ret = $this->_actionGetTempIndex();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionGenTemp() {
		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			Yii::info("key=$key, value=$value");
		}

		$tempDao = new TemplateDAO();
		$ret = $tempDao->genTemp($input, $output);
		if ($ret != BizErrcode::ERR_OK) {
			Yii::error("failed to generate template");
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;

	}

	public function actionGenTemp() {

		$ret = $this->_actionGenTemp();

		return $this->renderJson($ret, $this->retdata);
	}
}
<?php
namespace app\wy\cgi;

use Yii;
use component\controller\RenderController;
use app\wy\dao\TemplateDAO;
use includes\BizErrcode;
use component\qcloud\src\QcloudApi\QcloudApi;

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
				if (is_array($values)) {
					foreach ($values as $index => $value) {
						Yii::info("$value");
					}
				}
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

	private function _actionUpdateTemplate() {
		$input = $this->GPValue();
		foreach($input as $key => $value) {
			Yii::info("key=$key, value=$value");
		}

		$templateDao = new TemplateDAO();
		$ret = $templateDao->updateTemplate($input, $output);
		if ($ret != BizErrcode::ERR_OK) {
			Yii::error("更新模板页面失败");
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;
	}

	public function actionUpdateTemp() {
		$ret = $this->_actionUpdateTemplate();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionGetTemplateUrl() {
		$input = $this->GPValue();
		foreach($input as $key => $value) {
			Yii::info("key=$key, value=$value");
		}

		$templateDao = new TemplateDAO();
		$ret = $templateDao->getTemplateUrl($input, $output);
		if ($ret != BizErrcode::ERR_OK) {
			Yii::error("获取模板页面出错");
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;
	}

	public function actionGetTempUrl() {
		$ret = $this->_actionGetTemplateUrl();

		return $this->renderJson($ret, $this->retdata);
	}
}
<?php
namespace app\wy\cgi;

use Yii;
use component\controller\RenderController;
use app\wy\dao\WeiSitesDAO;
use includes\BizErrcode;

class WeisiteController extends RenderController {

	private function _actionGetAllWeiSites() {
		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			Yii::info("input[$key]: $value");
		}

		$weiSitesDao = new WeiSitesDAO();
		$ret = $weiSitesDao->getAllWeiSites($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error("Failed to get all wei-sites info");
			return $ret;
		}
		
		$this->retdata['data'] = $output;

		return $ret;
	}

	public function actionGetWeiAll() {
		$ret = $this->_actionGetAllWeiSites();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionDeleteWeiSite() {
		$input = $this->GPValue();
		foreach ($input as $key => $value) {
			Yii::info("input[$key]: $value");
		}

		$weiSitesDao = new WeiSitesDAO();
		$ret = $weiSitesDao->deleteWeiSite($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error("Failed to delete wei-site info");
			return $ret;
		}

		$this->retdata['data'] = $output;

		return $ret;
	}

	public function actionDelWei() {
		$ret = $this->_actionDeleteWeiSite();

		return $this->renderJson($ret, $this->retdata);
	}
}

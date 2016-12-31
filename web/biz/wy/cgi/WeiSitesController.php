<?php
namespace app\wy\cgi;

use Yii;
use includes\BizErrcode;
use component\controller\RenderController;
use app\wy\dao\WeiSitesDAO;

clase WeiSitesController extends RenderController {

	private function _actionGetAllWeiSites() {
		$input = $this->GPValue();

		$weiSitesDao = new WeiSitesDAO();
		$ret = $weiSitesDao->getAllWeiSites($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error("Failed to get all wei-sites info");
			return $ret;
		}

		return $ret;
	}

	public function actionGetWeiAll() {
		$ret = $this->_actionGetAllWeiSites();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionDeleteWeiSite() {
		$input = $this->GPValue();

		$weiSitesDao = new WeiSitesDAO();
		$ret = $weiSitesDao->deleteWeiSite($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error("Failed to delete wei-site info");
			return $ret;
		}

		return $ret;
	}

	public function actionDeleteWeiSite() {
		$ret = $this->_actionDeleteWeiSite();

		return $this->renderJson($ret, $this->retdata);
	}
}

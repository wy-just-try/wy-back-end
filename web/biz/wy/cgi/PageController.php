<?php
namespace app\wy\cgi;

use Yii;
use component\controller\RenderController;
use app\wy\dao\PageDAO;
use includes\BizErrcode;

class PageController extends RenderController {

	private function _actionGenPage() {
		$input = $this->GPValue();

		$pageDao = new PageDAO();
		$ret = $pageDao->generatePage($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error("Failed to create page");
			return $ret;
		}

		return $ret;
	}

	public function actionGenPage() {
		$ret = $this->_actionGenPage();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionUpdatePage() {
		$input = $this->GPValue();

		$pageDao = new PageDAO();
		$ret = $pageDao->updatePage($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error('Failed to update page');
			return $ret;
		}

		return $ret;
	}

	public function actionUpdatePage() {
		$ret = $this->_actionUpdatePage();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionGetPage() {
		$input = $this->GPValue();

		$pageDao = new PageDAO();
		$ret = $pageDao->getPage($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error("Failed to get page");
			return $ret;
		}

		return $ret;
	}

	public function actionGetPage() {
		$ret = $this->_actionGetPage();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionGetAllPages() {
		$input = $this->GPValue();

		$pageDao = new PageDAO();
		$ret = $pageDao->getAllPages($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error("Failed to get all pages");
			return $ret;
		}

		return $ret;
	}

	public function actionGetAll() {
		$ret = $this->getAllPages();

		return $this->renderJson($ret, $this->retdata);
	}

	private function _actionDeletePage() {
		$input = $this->GPValue();

		$pageDao = new PageDAO();
		$ret = $pageDao->deletePage($input, $output);
		if (BizErrcode::ERR_OK != $ret) {
			Yii::error("Failed to delete page");
			return $ret;
		}

		return $ret;
	}

	public function actionDelPage() {
		$ret = $this->_actionDeletePage();

		return $this->renderJson($ret, $this->retdata);
	}
}
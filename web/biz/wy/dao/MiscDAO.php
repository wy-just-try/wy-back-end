<?php
namespace app\wy\dao;

use Yii;
use component\model\BaseModel;
use includes\BizErrcode;
use includes\BizConst;
use app\wy\ao\Captcha;

class MiscDAO extends BaseModel {

	public function init()
	{
		parent::init();
	}

	//校验场景设置
	public function scenarios()
	{
		$scenarios = parent::scenarios();
		$scenarios['gen-pic-captcha'] = [];
		$scenarios['gen-msg-captcha'] = ['cellPhone'];

		return $scenarios;
	}

	//参数校验规则
	public function rules()
	{
		return [
			[['cellPhone'],'required','on'=>'gen-msg-captcha'],
		];
	}

	//对象属性
	public function attributes()
	{
		return [
			'cellPhone'
		];
	}

	public function selfAttributes()
	{
		return [];
	}

	public function defaultVals()
	{
		return [
			'cellPhone'	=>	'',
		];
	}

	/**
	 * 生成图片验证码
	 */
	public function genPicCaptcha($input, &$output = []) {
		$this->setScenario('gen-pic-captcha');
		$this->load($input, '');
		$this->setDefaultVal();
		if (!$this->validate()) {
			Yii::error('生成图片验证码参数错误');
			return BizErrcode::ERR_PARAM;
		}

		$picCaptcha = new Captcha();
		$picCaptcha->createPicCaptcha();

		return BizErrcode::ERR_OK;
	}

	/**
	 * 生成短信验证码
	 */
	public function genMsgCaptcha($input, &$output = []) {
		$this->setScenario('gen-msg-captcha');
		$this->load($input, '');
		$this->setDefaultVal();
		if (!$this->validate()) {
			Yii::error('生成短信验证码参数错误');
			return BizErrcode::ERR_INVALID_CELLPHONE;
		}

		$msgCaptcha = new Captcha();
		if (!$msgCaptcha->createMsgCaptcha()) {
			Yii::error('发送短信验证码失败');
			return BizErrcode::ERR_SEND_FAILED;
		}

		return BizErrcode::ERR_OK;

	}
}
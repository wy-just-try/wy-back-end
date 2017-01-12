<?php
namespace app\wy\dao;

use Yii;
use component\model\BaseModel;
use includes\BizErrcode;
use includes\BizConst;
use app\wy\ao\Captcha;
use app\wy\ao\UploadImg;

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
		$scenarios['upload-image'] = ['url'];

		return $scenarios;
	}

	//参数校验规则
	public function rules()
	{
		return [
			[['cellPhone'],'required','on'=>'gen-msg-captcha'],
			[['url'], 'required', 'on' => 'upload-image'],
		];
	}

	//对象属性
	public function attributes()
	{
		return [
			'cellPhone',
			'url',
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
			'url'		=>  '',
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
			Yii::error('The parameters of creating picture captcha are wrong');
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
			Yii::error('The parameters of create messanger captcha are wrong');
			return BizErrcode::ERR_INVALID_CELLPHONE;
		}

		$msgCaptcha = new Captcha();
		if (!$msgCaptcha->createMsgCaptcha($input['cellPhone'])) {
			Yii::error('Failed to send message');
			return BizErrcode::ERR_SEND_FAILED;
		}

		return BizErrcode::ERR_OK;
	}

	/**
	 * 单张图片上传
	 * @param  [array] &$output 图片访问地址，如 [picUrl=>'xxx']
	 * @return [int] 错误码
	 */
	public function uploadImg($input, &$output = [])
	{
		$this->setScenario('upload-image');
		$this->load($input, '');
		$this->setDefaultVal();
		if (!$this->validate()) {
			Yii::error('The paramters of uploading image are wrong');
			return BizErrcode::ERR_UPLOAD_FAILED;
		}

		$img = new UploadImg();
		$ret = $img->upload($input, $output);
		if (BizErrcode::ERR_OK !== $ret) {
			return BizErrcode::ERR_UPLOAD_FAILED;
		}

		return BizErrcode::ERR_OK;
	}
}
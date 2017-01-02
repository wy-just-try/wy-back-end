<?php
namespace app\wy\ao;

use Yii;
use includes\BizErrcode;

/**
* 图片上传
*/
class UploadImg
{
	/*
	 * 上传最大文件大小
	 */
	const MAX_FILE_SIZE = 2000000;

	/*
	 * 支持上传文件类型
	 */
	private $file_type = [
		'image/jpg',
		'image/jpeg',
		'image/png',
		'image/pjpeg',
		'image/gif',
		'image/bmp',
		'image/x-png'
	];

	private function getUserDir()
	{
		//coding by kfc
		return '/home/luffy/myGithub/';
	}

	/**
	 * 上传图片逻辑
	 * @param  array  &$output 图片访问地址，如 [picUrl=>'xxx']
	 * @return [int]          错误码
	 */
	public function upload(&$output = [])
	{
		if (!isset($_FILES['file'])) {
			Yii::error('文件不存在');
			return BizErrcode::ERR_PARAM;
		}
		$file = $_FILES['file'];
		Yii::info(var_export($file, true));

		if (0 !== $file['error']) {
			Yii::error($this->handleErr($file['error']));
			return BizErrcode::ERR_FAIL;
		}

		if (!is_uploaded_file($file['tmp_name'])) {
			Yii::error('文件非http post上传文件');
			return BizErrcode::ERR_PARAM;
		}

		if ($file['size'] > self::MAX_FILE_SIZE) {
			Yii::error('文件超出最大上传文件限制');
			return BizErrcode::ERR_PARAM;
		}

		if (!in_array($file['type'], $this->file_type)) {
			Yii::error('不支持上传的文件类型');
			return BizErrcode::ERR_PARAM;
		}

		$dir = $this->getUserDir();
		$type = explode('/', $file['type'])[1];
		
		do {
			$destFile = $dir . time() . $this->randNumber() . '.' . $type;
		} while (file_exists($destFile));
		Yii::info($destFile);

		if (!move_uploaded_file($file['tmp_name'], $destFile)) {
			Yii::info('临时文件移动失败');
		}

		$output = ['picUrl' => '']; //coding by kfc
		return BizErrcode::ERR_OK;
	}

	/**
	 * 错误码处理
	 * @param  integer $code 错误码
	 * @return [string]  错误码对应的信息
	 */
	private function handleErr($code = 0)
	{
		switch ($code) {
			case UPLOAD_ERR_OK:
				return '没有错误发生，文件上传成功';
			case UPLOAD_ERR_INI_SIZE:
				return '上传文件超过了php.ini里面upload_max_filesize选项限制的值';
			case UPLOAD_ERR_FORM_SIZE:
				return '上传文件超过了HTML表单MAX_FILE_SIZE选项指定的值';
			case UPLOAD_ERR_PARTIAL:
				return '文件只有部分被上传';
			case UPLOAD_ERR_NO_FILE:
				return '没有文件被上传';
			case UPLOAD_ERR_NO_TMP_DIR:
				return '找不到临时文件夹';
			case UPLOAD_ERR_CANT_WRITE:
				return '文件写入失败';
			default:
				return '未知上传错误';
		}
	}

	/**
	 * 用来生成0~9（包含0和9）之间的随机数字符串
	 * @param $bits 表示要生成的随机数字符串长度
	 * @return 返回生成的随机数字符串
	 */
	private function randNumber($bits = 4) {
		$str = '';
		for ($i = 0; $i < $bits; $i++) {
			$str .= chr(mt_rand(48, 57));
		}

		return $str;
	}
}
<?php
namespace app\wy\ao;

use Yii;

/**
** 用来管理服务器上的模板文件
*/
class TemplateManager {

	const TEMPLATE_PATH_PREFIX = "../../../template/";

	/**
	 * 此函数用来将模板ID转换成模板在服务器上的路径
	 * 模板ID的格式: template-template-[1st|2nd]-template-{INDEX}-{File Name}
	 * 对应的路径为: /template/template/[1st|2nd]/template-{INDEX}/{File Name}.html
	 * @param string $tempId 模板文件ID，实际对应于数据库TempIndex表中的FileName字段
	 * @return 如果成功，返回模板ID在服务器上对应的路径名；否则返回空
	 */
	public function createTemplatePath($tempId) {
		$path = '';
		// 检查参数是否合法
		if (!is_string($tempId) && strlen($tempId) == 0) {
			Yii::error("模板ID不合法");
			return null;
		}

		// 
		$path = sprintf("%s%s", self::TEMPLATE_PATH_PREFIX, "template/template/1st/template-1/323915.html");

		Yii::info("path: $path");

		return $path;
	}

}
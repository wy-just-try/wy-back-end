<?php
namespace app\wy\ao;

use Yii;

/**
* 短链接和原始链接之间的转换
*/
class UrlConverter {

	private static $instance;

	private function __construct() {

	}

	public static getInstance() {
		if (is_null($this->instance) 
			|| !(self::$instance instanceof self)) {
				self::$instance = new self;
		}

		return self::$instance;
	}

	private const SINA_APP_KEY = '4262014387';

	/**
	 * 使用新浪微博服务转换长链接和短链接
	 * @param bool $trye true表示将长链接转换成短链接; false表示将短链接恢复成长链接
	 * @param string $url 要操作的链接
	 * @return 如果成功返回生成的链接，否则返回null
	*/
	public function convertUrl($type, $url){
	    if($type) {
	    	$baseurl = 'http://api.t.sina.com.cn/short_url/shorten.json?source='.self::SINA_APP_KEY.'&url_long='.$url;
	    }
	    else {
	    	$baseurl = 'http://api.t.sina.com.cn/short_url/expand.json?source='.self::SINA_APP_KEY.'&url_short='.$url;
	    }

	    $ch=curl_init();
	    curl_setopt($ch, CURLOPT_URL,$baseurl);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	    $strRes=curl_exec($ch);
	    curl_close($ch);
	    $arrResponse=json_decode($strRes,true);
	    if (isset($arrResponse->error) || !isset($arrResponse[0]['url_long']) || $arrResponse[0]['url_long'] == '') {
	    	return null;
		}

	    if($type) {
	    	return $arrResponse[0]['url_short'];
	    }
	    else {
	    	return $arrResponse[0]['url_long'];
	    }
    }

}
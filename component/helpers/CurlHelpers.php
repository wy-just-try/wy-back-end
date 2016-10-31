<?php
/**
 * @描述 Curl类库，对curl进行封装，便于调用
 * @时间 2016年10月18日 09:44:27
 * @作者 Maker.xing
 *
 * @使用
 * $config = [
 *	'headers' => [
 *		'Content-Type' => 'application/x-protobuf',
 *	],
 * 	'options' => [
 *		CURLOPT_TIMEOUT => 10,
 *	],
 * 	'userAgent' => 'PHP v5.36XXXX',
 *	'referer' => 'http://www.jd.com',
 * ];
 *
 * $curl = new CurlHelpers($config);
 * $ret = $curl->post($url, $data);
 */
namespace component\helpers;

use yii\base\object;

define('CURL_ERR_HTTP_STATUS',  -1000000000);
define('CURL_ERR_PERFORM',      -1100000002);
define('CURL_ERR_PARSE_HEADER', -1100000010);
define('CURL_ERR_PARSE_BODY',   -1100000011);
define('CURL_ERR_MOD_INVALID',  -1100000012);

class CurlHelpers extends Object
{
    private $_ch = null;
    public $options = [];
    public $headers = [];
    public $userAgent = "";
    public $referer = "";

    public function get($url, $params = [])
    {
        return $this->request($url, 'GET', $params);
    }

    public function post($url, $params = [])
    {
        return $this->request($url, 'POST', $params);
    }

    public function request($url, $method, $params = [])
    {
        $this->_ch = curl_init();

        if(is_array($params))
        {
            $params = http_build_query($params, '', '&');
        }

        $errcode = 0;
        $errmsg  = "";
        $content = "";
        //$info = [];

        do
        {
            if(!$this->_setMethod($method))
            {
                $errcode = CURL_ERR_MOD_INVALID;
                $errmsg  = "the method is not support";
                break;
            }

            $this->_setOption($url, $params);
            $this->_setHeaders();

            $response = curl_exec($this->_ch);

            if($response === false)
            {
                $errcode = CURL_ERR_PERFORM; ;
                $errmsg  = "errcode:{" . curl_errno($this->_ch) . "}, errmsg:{" .curl_error($this->_ch) . "}";
                break;
            }

            $httpCode = curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);

            if($httpCode != 200)
            {
                $errcode = CURL_ERR_HTTP_STATUS - $httpCode;
                break;
            }

            $info = [];
            $ret  = $this->_parseResponseHead($this->_ch, $response, $info);
            if($ret === false)
            {
                $errcode = CURL_ERR_PARSE_HEADER;
                $errmsg  = "parse response header error";
                break;
            }

            if(isset($info['ErrCode']) && $info['ErrCode'] < 0)
            {
                $errcode = $info['ErrCode'];
                $errmsg  = $info['ErrMsg'];
                break;
            }

            $ret  = $this->_parseResponseBody($this->_ch, $response, $content);
            if($ret === false)
            {
                $errcode = CURL_ERR_PARSE_BODY;
                $errmsg  = "parse response body error";
                break;
            }

            $errcode = $info['ErrCode'];
            $errmsg  = $info['ErrMsg'];
            
        } while(0);

        curl_close($this->_ch);
        return ['errCode' => $errcode, 'errMsg' => $errmsg, 'content' => $content];
    }

    private function _parseResponseBody($ch, $response, &$body)
    {
        $ret = false;

        if ($ch && $response)
        {
            $bodySize = strlen($response);
            $headSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body     = substr($response, $headSize, $bodySize - $headSize);
            $ret = true;
        }

        return $ret;
    }

    private function _setMethod($method)
    {
        $flag = false;
        switch (strtoupper($method))
        {
            case 'GET':
                $flag = true;
                curl_setopt($this->_ch, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                $flag = true;
                curl_setopt($this->_ch, CURLOPT_POST, true);
                break;
            default:
                break;
        }

        return $flag ? true : false;
    }

    private function _setOption($url, $params = "")
    {
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        if(!empty($params))
        {
            curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        }

        # Set some default CURL options
        curl_setopt($this->_ch, CURLOPT_HEADER, true);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        if($this->userAgent)
        {
            curl_setopt($this->_ch, CURLOPT_USERAGENT, $this->userAgent);
        }
        if($this->referer)
        {
            curl_setopt($this->_ch, CURLOPT_REFERER, $this->referer);
        }

        curl_setopt_array($this->_ch, $this->options);
    }

    private function _setHeaders()
    {
        $headers = [];
        foreach ($this->headers as $key => $value)
        {
            array_push($headers, "{$key}: {$value}");
        }

        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $headers);
    }

    private function _parseResponseHead($ch, $response, &$head)
    {
        $ret = false;

        do
        {
            if(!$ch || !$response)
            {
                break;
            }

            $headSize    = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headContent = substr($response, 0, $headSize);
            $options = explode("\n", $headContent);

            foreach($options as $option)
            {
                $option = trim($option);
                if(empty($option))
                {
                    continue;
                }

                $item = explode(":", $option);
                $key = trim($item[0]);
                $val = trim($item[1]);
                $head[$key] = $val;
            }

            $ret = true;
        } while (0);

        return $ret;
    }
}

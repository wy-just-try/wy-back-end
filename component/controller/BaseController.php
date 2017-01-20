<?php
/**
 * @描述 将数据库的连接方法放到该方法中
 * @时间 2016年10月18日 18:18:14
 * @作者 Maker.xing
 */
namespace component\controller;

use Yii;
use yii\web\Controller;
use yii\base\InvalidValueException;
use yii\web\JsExpression;


class BaseController extends Controller
{
    /**
     * 获取http参数，GET优先级高于POST
     * @return array 返回键值形式的数组
     */
    public function GPValue()
    {
        $data = array_merge([], Yii::$app->request->post(), Yii::$app->request->get());
        $data = $this->U2G($data);
        return $data;
    }

    /**
     * 数据编码转换，从GBK到UTF-8
     * @param $data mixed 要转换的数据
     * @return mixed 转换之后的数据
     */
    public function G2U($data)
    {
        // if(is_array($data) ||  is_object($data)){
        //     foreach ($data as $key=>&$value){
        //         if(is_array($value) || is_object($value)){
        //             $value = $this->G2U($value);
        //         }
        //         elseif(is_string($value)){
        //             $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
        //         }else{
        //             continue;
        //         }
        //     }
        // }elseif(is_string($data)){
        //     $data = iconv('GBK', 'UTF-8//IGNORE', $data);
        // }

        return $data;
    }

    /**
     * 数据编码转化，总UTF-8到GBK
     */
    public function U2G($data)
    {
        // if(is_array($data) || is_object($data)) {
        //     foreach ($data as &$value) {
        //         if(is_array($value) || is_object($value)) {
        //             $value = $this->U2G($value);
        //         } elseif(is_string($value)) {
        //             $value = iconv('UTF-8', 'GBK//IGNORE', $value);
        //         }
        //     }
        // } elseif(is_string($data)) {
        //     $data = iconv('UTF-8', 'GBK//IGNORE', $data);
        // }

        return $data;
    }
    
    public function XSSDefend($data)
    {
        if($data instanceof JsExpression) {
            return $data;
        }
        if(is_array($data) || is_object($data)) {
            foreach ($data as &$value) {
                if(is_array($value) || is_object($value)) {
                    $value = $this->XSSDefend($value);
                } elseif(is_string($value)) {
                    $value = htmlspecialchars($value, ENT_QUOTES);
                }
            }
        } elseif(is_string($data)) {
            $data = htmlspecialchars($data, ENT_QUOTES);
        }
        return $data;
    }
}

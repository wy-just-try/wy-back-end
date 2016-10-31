<?php
/**
 * @描述 常用函数类库，静态函数，直接调用
 * @时间 2016年10月18日 16:13:20
 * @作者 Maker.xing
 */
namespace component\helpers;

use yii\base\object;

class ComFuncHelpers extends Object
{
    public static function resloveSvc($svcName, $svcSet = 0, $svcRoute = 0)
    {
        \Yii::info('the svc config info:[svcname=>'.$svcName.', svcSet=>'.$svcSet.', route key=>'.$svcRoute.']');
       // $ret = configcenter4_get_serv($svcName, $svcSet, $svcRoute);
        //升级配置中心v6  modify by longzhenwen 2016-0809
        $obj = new \config_access;
        $ret =$obj->GetAddrL5AndMod($svcName, $svcSet, $svcRoute, $ip, $port);             // 查询路由
        $ipstr = $obj->GetStrIP($ip);

        //$info = explode(":", $ret, 2);
        $info[0] = $ipstr;//ip
        $info[1] = $port;//port
        \Yii::info('get raw svc by config center v6 info: '.var_export($info, true));
        return $info;
    }

    /**
     * 获取当前微妙数
     * @return double
     * 这里要注意，32位软件int型最大不支持13位，这里返回double类型
     */
    public static function getTimeMs()
    {
        $timeMs = microtime(true);
        $timeMs = $timeMs * 1000;
        return floatval(sprintf("%.0f", $timeMs));
    }


    /**
     * @param null $string 传入字符串
     * @return int 返回字符串(汉字)长度
     */
    public static  function utf8_strlen($string = null)
    {
        preg_match_all("/./us", $string, $match); // 将字符串分解为单元
        return count($match[0]);// 返回单元个数
    }

    //字符过滤，需要过滤的字符：< > & ' " \ /
    public static function  checkSpecialChar($strParam)
    {
        //特殊字符校验值
        $regex = "/[\'&<>|\\/|\/|\\\\|\[\\\]|\"|\t|\n|\r/";
        if(preg_match($regex,$strParam))
        {
            return false;
        }

        return 0;
    }

    //字符过滤，需要过滤的字符：< > & ' " \ /
    public static function  escapeChars($sStr)
    {
        $aEscapeChars=array('<','>','&','\'','"','\\','/');
        return str_replace($aEscapeChars, "", $sStr);
    }

    /**
     * 数据编码转换，从GBK到UTF-8
     * @param $data mixed 要转换的数据
     * @return mixed 转换之后的数据
     */
    public function G2U($data)
    {
        if(is_array($data) || is_object($data)){
            foreach ($data as $key=>&$value){
                if(is_array($value) || is_object($value)){
                    $value = $this->G2U($value);
                }
                elseif(is_string($value)){
                    $value = iconv('GBK', 'UTF-8//IGNORE', $value);
                }else{
                    continue;
                }
            }
        }elseif(is_string($data)){
            $data = iconv('GBK', 'UTF-8//IGNORE', $data);
        }

        return $data;
    }

    /**
     * 数据编码转化，从UTF-8到GBK
     */
    public function U2G($data)
    {
        if(is_array($data) || is_object($data)) {
            foreach ($data as &$value) {
                if(is_array($value) || is_object($value)) {
                    $value = $this->U2G($value);
                } elseif(is_string($value)) {
                    $value = iconv('UTF-8', 'GBK//IGNORE', $value);
                }
            }
        } elseif(is_string($data)) {
            $data = iconv('UTF-8', 'GBK//IGNORE', $data);
        }

        return $data;
    }

    /**
     * 二维数组排序
     * @param $arrays 待排序的二维数组
     * @param $sort_key
     * @param int $sort_order 排列顺序，包括：SORT_ASC - 默认，按升序排列(A-Z、0-9) ；SORT_DESC - 按降序排列。(Z-A、9-0)
     * @param int $sort_type 指定排序的类型，包括：SORT_REGULAR - 默认，将每一项按常规顺序排列；SORT_NUMERIC - 将每一项按数字顺序排列；SORT_STRING - 将每一项按字母顺序排列
     * @return array|bool
     */
    public static function SortTwoDimArray($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC )
    {
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }

    /**
     * 数组分页函数 核心函数
     * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
     * $count  每页多少条数据
     * $page   当前第几页
     * $array  查询出来的所有数组
     * order 0 - 不变 1- 反序
     */
    public static function GetPageArray($count,$page,$array,$order)
    {
        global $countpage;              //定全局变量
        $page=(empty($page))?'1':$page; //判断当前页面是否为空 如果为空就表示为第一页面
        $start=($page-1)*$count;        //计算每次分页的开始位置
        if($order==1)
        {
            $array = array_reverse($array);
        }
        $dwTotalNum = count($array);
        $countpage = ceil($dwTotalNum/$count); //计算总页面数
        $pagedata = [];
        $pagedata = array_slice($array,$start,$count);
        return $pagedata;               //返回查询数据
    }

    // 对象转数组,使用get_object_vars返回对象属性组成的数组
    public function ObjectToArray($obj)
    {
        $arr = [];
        $_arr = is_object($obj) ? get_object_vars($obj) :$obj;

        foreach ($_arr as $key=>$val)
        {
            $val = (is_array($val) || is_object($val)) ? $this->ObjectToArray($val):$val;
            $arr[$key] = $val;
        }

        return $arr;
    }

    // 数组转对象
    public function ArrayToObject($arr)
    {
        if(is_array($arr))
        {
            return (object) array_map(__FUNCTION__, $arr);
        }
        else
        {
            return $arr;
        }
    }
}

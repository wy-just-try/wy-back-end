<?php
/**
 * @描述 将数据库的连接方法放到该方法中
 * @时间 2016年10月18日 18:18:14
 * @作者 Maker.xing
 */

namespace Component\Model;

use PDO;
use yii\base\Model;


class BaseModel extends Model
{

    //use PdoTrait;
    /**
     * static::load()的时候，变量存储的容器，map格式
     * 例如：
     *
     * 假设attributes为：
     * [
     *  'username',
     *  'age'
     * ]
     *
     * $this->load([
     *  'username' => '燕睿涛',
     *  'age' => '23'
     * ], "");
     *
     * print_r($this->$_attributes);
     * [
     *  'username' => '燕睿涛',
     *  'age' => '23'
     * ]
     */
    private $_attributes = [];

    /**
     * 自己定义的
     */
    public function selfAttributes()
    {
        return [];
    }

    /**
     * 要设定的默认值
     */
    public function defaultVals()
    {
        return [];
    }

    /**
     * 设置默认值
     */
    public function setDefaultVal()
    {
        foreach ($this->defaultVals() as $key => $value) {
            //未设置或者为空，设置默认值
            if($this->hasAttribute($key) && $this->$key === NULL || $this->$key === "") {
                $this->$key = $value;
            }
        }
    }

    /**
     * 判断该model是否会接收该属性
     * @param string $name 属性的名称
     * @return boolean true/false
     */
    public function hasAttribute($name)
    {
        return isset($this->_attributes[$name]) || in_array($name, array_merge($this->attributes(), $this->selfAttributes()));
    }

    /**
     * 获取通过魔术方法设置的变量
     * @param mixed string $name
     * @return mixed 获取到的属性值
     */
    public function getAttribute($name = null)
    {
        if($name && $this->hasAttribute($name)) {
            if(isset($this->_attributes[$name])) {
                return $this->_attributes[$name];
            } else {
                return null;
            }
        }
        return $this->_attributes;
    }

    /**
     * PHP的setter魔术方法.
     * 重写这个方法，让该model可以通过属性设置attributes数组中的变量
     * @param string $name 属性名称
     * @param mixed $value 属性值
     */
    public function __set($name, $value)
    {
        if($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        }else {
            parent::__set($name, $value);
        }
    }

    /**
     * PHP的getter魔术方法
     * 重写这个方法，让给model可以通过属性访问attributes数组中的变量
     * @param string $name 属性名称
     * @return mixed 属性名称对应的属性值
     */
    public function __get($name)
    {
        if(isset($this->_attributes[$name])) {
            return $this->_attributes[$name];
        } elseif(!$this->hasAttribute($name)) {
            return parent::__get($name);
        }
    }

    /**
     * PHP的unsetter魔术方法
     * 重写这个方法，让给model可以通过属性unset attributes数组中的变量
     * @param string $name 属性名称
     */
    public function __unset($name)
    {
        if(isset($this->_attributes[$name])) {
            unset($this->_attributes[$name]);
        } elseif (!$this->hasAttribute($name)) {
            parent::__unset($name);
        }
    }

    /**
     * PHP的issetter魔术方法
     * 重写这个方法，让给model可以对不存在的属性调用isset方法
     * @param string $name 属性名称
     * @return boolean true/false
     */
    public function __isset($name)
    {
        if($this->hasAttribute($name)) {
            return isset($this->_attributes[$name]);
        } else {
            return parent::__isset($name);
        }
    }

    public function setAttributes($values, $safeOnly = false)
    {
        if (is_array($values)) {
            $attributes = array_flip($safeOnly ? $this->safeAttributes() : array_merge($this->attributes(), $this->selfAttributes()));
            foreach ($values as $name => $value) {
                if (isset($attributes[$name])) {
                    $this->$name = $value;
                } elseif ($safeOnly) {
                    $this->onUnsafeAttribute($name, $value);
                }
            }
        }
    }

    public function getSqlInfo($sql, $params)
    {
        $pattrens = array_keys($params);
        $pattrens = array_map(function($v) {
            return "/$v/";
        }, $pattrens);
        $params = array_map(function($v) {
            if(gettype($v) == "string") {
                return "'$v'";
            } else {
                return $v;
            }
        }, $params);
        return preg_replace($pattrens, $params, $sql);
    }
}

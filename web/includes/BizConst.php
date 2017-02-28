<?php
/**
 * @描述 京享街宏定义
 * @时间 2016年09月16日
 * @作者 xhx
 */
namespace includes;

class BizConst
{
	//数据库配置
	const DB_MINES = 'mysql'; // 数据库类型
	const DB_HOST = '127.0.0.1'; // 数据库IP
	const DB_DATABASE = 'mpWeixin'; // 数据库名称
	const DB_USER = 'root'; // 用户名称
	const DB_PASSWD = '000000'; // 连接密码
	const DB_CHARSET = 'utf8';	// 连接数据库字符编码

	//线上数据库
	public static $Idc_Db =[
		'mines' => 'mysql',
		'host' => '127.0.0.1',
		'dbname' => 'mpWeixin',
		'user' => 'root',
		'password' => '000000',
		'charset' => 'utf8',
	];
	public static $Dev_Db =[
		'mines' => 'mysql',
		'host' => '127.0.0.1',
		'dbname' => 'devData',
		'user' => 'mydev',
		'password' => 'dev@wy',
		'charset' => 'utf8',
	];




}



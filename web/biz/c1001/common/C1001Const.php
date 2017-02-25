<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/25
 * Time: 0:53
 */
namespace app\c1001\common;




class C1001Const
{
     const IMAGE_SERVER_PATH = "/data/front/static/c1001/";//图片在服务器上的路经
     const IMAGE_URL_PREFIX="http://wy626.com/c1001/";//图片域名前缀
     public  static $imageType = ["image/jpg","image/png","image/gif","image/jpeg","image/tif","image/bmp"];//支持上传图片的类型

    const IMAGE_USE_TYPE_CROWD = 1;//群二维码图
    const IMAGE_USE_TYPE_GZ = 2; //公众号二维码图

    public static $imageUserType =[C1001Const::IMAGE_USE_TYPE_CROWD,C1001Const::IMAGE_USE_TYPE_GZ];

}
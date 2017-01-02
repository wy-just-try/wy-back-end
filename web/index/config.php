<?php

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__).'/biz',
    'bootstrap' => ['log'],
    'defaultRoute' => 'ad/cgi/site/index',
    'controllerNamespace' => 'app',
    'runtimePath' =>  dirname(__DIR__).'/runtime',
    'viewPath' =>  dirname(__DIR__).'/views/biz/wy',
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<module:\w+>/<Controller:\w+>/<action:[a-z0-9\\-_]+>' => '<module>/cgi/<Controller>/<action>'            ],
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'frn4Z2yKYLM8__hI1qJADzFImxJqRFup',
	#		'login' => false,
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            //'class' => 'common\errCode\BizErrorHandler',
        ],
        'log' => [
            'traceLevel' => 3,
            'targets' => [
                [
                    'class' => 'component\log\FileTarget',
                    'levels' => ['trace', 'info', 'error', 'warning'],
                ],
            ],
        ],
        'db' => [
            'class' => 'component\db\PdoInstance',
            'attributes' => [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        ],
        'xml' => [
            'class' =>'component\helpers\XMLConfig',
		#	'login' => false,
        ],
    ],
    'params' => [
        'templatePath' => dirname(__DIR__).'/template'
    ],
];


return $config;

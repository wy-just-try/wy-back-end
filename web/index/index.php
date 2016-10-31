<?php
// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'prod');

require(__DIR__ . '/../../framework/autoload.php');
require(__DIR__ . '/../../framework/Yii.php');

Yii::setAlias('@component', __DIR__ .'/../../component');
Yii::setAlias('@common', __DIR__ .'/../common');
Yii::setAlias('@includes', __DIR__ .'/../includes');

$config = require(__DIR__ . '/config.php');

$application = new yii\web\Application($config);

$application->run();

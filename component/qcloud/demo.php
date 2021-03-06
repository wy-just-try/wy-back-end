<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once './src/QcloudApi/QcloudApi.php';


$config = array('SecretId'       => 'AKIDIzpZFhyJMCqIYcKEpJcmsmU4SdfXJbWG',
                'SecretKey'      => '31xkwztcsyyY0GKCvSs68mBVdZWIsFSo',
                'RequestMethod'  => 'GET',
                'DefaultRegion'  => 'gz');

$cvm = QcloudApi::load(QcloudApi::MODULE_CVM, $config);

$package = array('offset' => 0, 'limit' => 3);

$a = $cvm->DescribeInstances($package);
// $a = $cvm->generateUrl('DescribeInstances', $package);

if ($a === false) {
    $error = $cvm->getError();
    echo "Error code:" . $error->getCode() . ".\n";
    echo "message:" . $error->getMessage() . ".\n";
    echo "ext:" . var_export($error->getExt(), true) . ".\n";
} else {
    var_dump($a);
}

echo "\nRequest :" . $cvm->getLastRequest();
echo "\nResponse :" . $cvm->getLastResponse();
echo "\n";
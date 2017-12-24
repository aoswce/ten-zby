<?php
/**
 * User: alderzhang
 * Date: 2017/3/21
 * Time: 10:36
 */
require_once dirname(__FILE__) . '/Path.php';

// 开发人员调整以下参数
define('DEFAULT_SDK_APP_ID', '1255625061'); //默认APPID
define('DEFAULT_SDK_APP_BIZ', '17972'); //默认APPID
define('APP_SECURITY_KEY', '4de5059347dca479c989836b3eba254d'); //推流防盗链Key
define('APP_AUTH_KEY', '65c7581b92dfa1bef321122999e5fc54'); //API鉴权key
define('VIDEO_RECORD_SECRET_ID', 'Your_Video_Secret_ID'); //录像Secret ID
define('VIDEO_RECORD_SECRET_KEY', 'Your_Video_Secret_Key'); //录像Secret Key
define('AUTHORIZATION_KEY', serialize([
    'Your_SDK_APP_ID' => 'Your_Authrization_Key'
])); //权限密钥表

define('DEFAULT_IM_SDK_APP_ID', '1400056252'); //默认APPID

?>

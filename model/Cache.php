<?php
/**
 * 独立账号模块
 * Date: 2016/11/20
 * Update：2016/12/23
 */

require_once dirname(__FILE__) . '/../Path.php';
require_once LIB_PATH . '/db/DB.php';

class Cache
{
    private static $redis;

    public function __Construct()
    {
        $redis = new redis();
        $redis->connect('127.0.0.1', 6379);
        //$redis->auth('');
        self::$redis = $redis;
    }

    public static function getUserToken($uid){
        $token = self::$redis->get($uid);

        if(!empty($token)){
            Log::info("Exist token".$token);
            return $token;
       }else{
            Log::info("Expired token".$token);
            return null;
        }
    }

    public static function setUserToken($uid,$token){
        Log::info("Set new token".$token);

        self::$redis->set("Token_".$uid,$token,60*60*24);
    }
}

?>

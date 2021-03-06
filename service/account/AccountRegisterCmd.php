<?php

/**
 * 用户注册接口
 * Date: 2016/11/15
 */

require_once dirname(__FILE__) . '/../../Path.php';
require_once SERVICE_PATH . '/Cmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Account.php';
require_once IM_PATH . '/TimRestApi.php';

class AccountRegisterCmd extends Cmd
{
    private $account;
    
    public function __construct()
    {
        $this->account = new Account();
    }

    public function parseInput()
    {
        if (empty($this->req['id']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of id');
        }
        if (!is_string($this->req['id']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid id');
        }
        $this->account->setUser($this->req['id']);
        
        if (empty($this->req['pwd']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of pwd');
        }
        if (!is_string($this->req['pwd']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid pwd');
        }
        $this->account->setPwd($this->req['pwd']);
        
        $this->account->setRegisterTime(date('U'));
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        $errorMsg = '';
        $ret = $this->account->register($errorMsg);
        $re = array();
        if($ret == 0){
            $userid = $this->account->getUser();
            $ret = $this->importAccountToIM($userid);

            foreach ($ret as $k=>$v){
                $re[lcfirst($k)] = $v;
            }
            return new CmdResp($re['errorCode'],$re['errorInfo']);
        }
        return new CmdResp($re, $errorMsg);
    }

    public function importAccountToIM($uid,$nick='',$face_url=''){
        $api = createRestAPI();
        #读取app配置文件
        $filename = IM_PATH."/TimRestApiConfig.json";
        $json_config = file_get_contents($filename);
        //var_dump($json_config);
        $app_config = json_decode($json_config, true);
        //var_dump($app_config);
        $sdkappid = $app_config["sdkappid"];
        $identifier = $app_config["identifier"];

        $private_pem_path = DEPS_PATH .$app_config["private_pem_path"];
        $user_sig = $app_config["user_sig"];

        $api->init($sdkappid, $identifier);
        if(is_64bit()){
            if(PATH_SEPARATOR==':'){
                $signature = IM_PATH."/signature/linux-signature64";
            }else{
                $signature = IM_PATH."\\signature\\windows-signature64.exe";
            }

        }else{
            if(PATH_SEPARATOR==':')
            {
                $signature = IM_PATH."/signature/linux-signature32";
            }else{
                $signature = IM_PATH."\\signature\\windows-signature32.exe";
            }
        }
        //echo "==============User-Sig0:=================================";
        //var_dump($signature,$private_pem_path,$identifier);
        $usersig = $api->generate_user_sig($identifier, '36000', $private_pem_path, $signature);
        Log::info($usersig);
        //echo "==============User-Sig1:=================================";
        //var_dump($usersig);
        //echo "==============User-Sig2:=================================";
        //var_dump($api->usersig);
        $ret = $api->account_import($uid,$nick,$face_url);
        //echo "==============User-Ret:=================================";
        //var_dump($ret);
        return $ret;
    }
}

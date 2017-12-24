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
        if($ret == 0){
            $userid = $this->account->getUser();
            $ret = $this->importAccountToIM($userid);
            var_dump($ret);
            return $ret;
        }
        return new CmdResp($ret, $errorMsg);
    }

    public function importAccountToIM($uid,$nick='',$face_url=''){
        $api = createRestAPI();
        #读取app配置文件
        $filename = IM_PATH."/TimRestApiConfig.json";
        $json_config = file_get_contents($filename);
        var_dump($json_config);
        $app_config = json_decode($json_config, true);
        var_dump($app_config);
        $sdkappid = $app_config["sdkappid"];
        $identifier = $app_config["identifier"];

        $private_pem_path = $app_config["private_pem_path"];
        $user_sig = $app_config["user_sig"];

        $api->init($sdkappid, $identifier);
        if(is_64bit()){
            if(PATH_SEPARATOR==':'){
                $signature = "signature/linux-signature64";
            }else{
                $signature = "signature\\windows-signature64.exe";
            }

        }else{
            if(PATH_SEPARATOR==':')
            {
                $signature = "signature/linux-signature32";
            }else{
                $signature = "signature\\windows-signature32.exe";
            }
        }
        $usersig = $api->generate_user_sig($identifier, '36000', $private_pem_path, $signature);
        var_dump("User-Sig:",$user_sig);
        $ret = $api->account_import($uid,$nick,$face_url);
        return $ret;
    }
}

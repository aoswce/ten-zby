<?php
/**
 * 创建房间接口
 * Date: 2016/11/17
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/AvRoom.php';
require_once MODEL_PATH . '/NewLiveRecord.php';
require_once MODEL_PATH . '/InteractAvRoom.php';
require_once MODEL_PATH . '/Account.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

class CreateLiveRoomCmd extends TokenCmd
{

    private $avRoom;

    public function parseInput()
    {
        /**
         * 不在进行判断type
        if (!isset($this->req['type']))
        {
        return new CmdResp(ERR_REQ_DATA, 'Lack of type');
        }
        if (!is_string($this->req['type']))
        {
        return new CmdResp(ERR_REQ_DATA, ' Invalid type');
        }

         */
        if (!isset($this->req['token']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack-1 of Token');
        }

        /*if(!isset($this->req['room'])){
            return new CmdResp(ERR_REQ_DATA,'Lack of room info');
        }*/
        
        $this->avRoom = new AvRoom($this->user);
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        /*$ret = $this->avRoom->load();
        // 加载房间出错
        if ($ret < 0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error');
        }

        //房间不存在，执行创建
        if($ret == 0)
        {
            $ret = $this->avRoom->create();
            if (!$ret)
            {
                return new CmdResp(ERR_SERVER, 'Server internal error: create av room fail');
            }
        }*/

        // 创建房间之前先清空数据
        $ret = $this->avRoom->load();
        // 存在旧房间
        if ($ret > 0)
        {
            //Log::info('uid '.$this->user.' has old room '.$this->avRoom->getId().' to delete');
            //删除直播记录
            NewLiveRecord::delete($this->user);
            //清空房间成员
            InteractAvRoom::ClearRoomByRoomNum($this->avRoom->getId());
        }

        //Log::info('uid '.$this->user.' create room now');

        // 每次请求都创建一个新的房间出来
        $ret = $this->avRoom->create();
        if (!$ret)
        {
            //Log::error('uid '.$this->user.' create room failed');
            return new CmdResp(ERR_SERVER, 'Server internal error: create av room fail');
        }

        //房间id
        $id = $this->avRoom->getId();
        //房间成员设置
        $interactAvRoom = new InteractAvRoom($this->user, $id, 'off', 1);
        //主播加入房间列表
        $ret = $interactAvRoom->enterRoom();    
        if(!$ret)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error:insert record into interactroom fail'); 
        }

        //调用上报创建房间结果的数据
        //$info = new ReportLiveRoomInfoCmd();
        //$resp = $info->execute();
        //$res = $resp->toArray();

        //if(intval($ret['errCode']) > 0){
        //    return new CmdResp(ERR_LIVE_NO_AV_ROOM_ID,'Server error :report root info fail!');
        //}
        $expire = strtotime("Y-m-d H:i:s",date("+1 day"));
        $streamId = $id.substr(md5($id),0,9);
        return new CmdResp(
            ERR_SUCCESS, '',
            array(
                'roomnum' => (int)$id,
                'groupid' => (string)$id,
                'push'=>getPushUrl(DEFAULT_SDK_APP_BIZ,$streamId,APP_SECURITY_KEY,$expire)
            )
        );
    }

    /**
     * 获取推流地址
     * 如果不传key和过期时间，将返回不含防盗链的url
     * @param bizId 您在腾讯云分配到的bizid
     *        streamId 您用来区别不同推流地址的唯一id
     *        key 安全密钥
     *        time 过期时间 sample 2016-11-12 12:00:00
     * @return String url */
    private function getPushUrl($bizId, $streamId, $key = null, $time = null){

        if($key && $time){
            $txTime = strtoupper(base_convert(strtotime($time),10,16));
            //txSecret = MD5( KEY + livecode + txTime )
            //livecode = bizid+"_"+stream_id  如 8888_test123456
            $livecode = $bizId."_".$streamId; //直播码
            $txSecret = md5($key.$livecode.$txTime);
            $ext_str = "?".http_build_query(array(
                    "bizid"=> $bizId,
                    "txSecret"=> $txSecret,
                    "txTime"=> $txTime
                ));
        }
        return "rtmp://".$bizId.".livepush.myqcloud.com/live/".$livecode.(isset($ext_str) ? $ext_str : "");
    }
}

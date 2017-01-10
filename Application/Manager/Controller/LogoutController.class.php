<?php
namespace Manager\Controller;
use Think\Controller;

class LogoutController extends Controller {
	public function _initialize(){
        $scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
    }
    public function logout()
    {
    	logger('附近客户 -- 区域经理：'.session('name').',退出登录...');
    	session(NULL);
		if(empty($_SESSION)){
			logger("附近客户 -- 退出成功\n");
			$data = array(
				'code' => 1,
				'message' => '退出成功！'
			);
		}else{
			logger("附近客户 -- 退出失败\n");
			$data = array(
				'code' => 0,
				'message' => '退出失败！'
			);
		}
		exit(json_encode($data));
    }
}
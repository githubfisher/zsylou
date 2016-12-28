<?php
namespace Home\Controller;
use Think\Controller;
class LogoutController extends Controller{

	public function index(){
		logger("清空SESSION数组");
		session(NULL);
		if($_SESSION == array()){
			logger("退出成功\n");
			echo "已清空SESSION";
		}
	}
}
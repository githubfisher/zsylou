<?php
namespace Pad\Controller;
use Think\Controller;
class LogoutController extends Controller{
	public function index(){
		logger("PAD端--清空SESSION数组");
		session(NULL);
		if($_SESSION == array()){
			logger("PAD端--退出成功\n");
			echo "PAD端--已清空SESSION";
		}
	}
}
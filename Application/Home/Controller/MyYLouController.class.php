<?php
namespace Home\Controller;
use Think\Controller;
class MyYLouController extends Controller{
	public function __initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	// 预留
	public function index(){

	}
	// 我的影楼 ，介绍本影楼的H5页面，之后可能转向影楼名片方向
	public function query(){
		logger('查询我的影楼的H5页面信息！');
		$sid = session('sid');
		$my_ylou = D('my_ylou');
		if($sid != '' && $sid != NULL){
			$where = array(
				'is_open' => 1,
				'sid' => array(array('eq',$sid),array('eq',0),'or')
			);
		}else{
			$where = array(
				'sid' => 0,
				'is_open' => 1
			);
		}
		$result = $my_ylou->field('sid,url')->where($where)->order('sid desc,time desc')->select();
		if($result){
			logger('存在我的影楼的H5页面信息，将其返回客户端');
			$data = array(
				'code' => 1,
				'message' => '我的影楼信息返回成功！',
				'result' => $result[0]
			);
			exit(json_encode($data));
		}else{
			logger('不存在我的影楼的H5页面信息\n');
			$data = array(
				'code' => 0,
				'message' => '不存在我的影楼信息',
				'result' => ''
			);
			exit(json_encode($data));
		}
	}
	//添加操作在后台管理员控制器里
}
<?php
namespace Home\Controller;
use Think\Controller;
class VersionController extends Controller{
	public function __initialize(){
		//版本信息在未登录情况也可以查询，故关闭审查session操作
		//$scheck = A('SessionCheck');
		//$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	// 预留
	public function index(){

	}
	// 查询客户端的最新版本 参数：操作系统 os 【 ios 2 ； Android 1 ；】
	public function query(){
		logger('查询客户端的最新版本信息！');
		$post = I();
		$os = $post['os'];
		$version = D('version');
		$where = array(
			'os' => $os
		);
		$result = $version->field('time,os,id',TRUE)->where($where)->order('time desc')->select();
		if($result){
			logger('存在版本，将其返回客户端');
			$data = array(
				'code' => 1,
				'message' => '版本信息返回成功！',
				'result' => $result[0]
			);
			exit(json_encode($data));
		}else{
			logger('不存在版本信息');
			$data = array(
				'code' => 0,
				'message' => '不存在版本信息',
				'result' => ''
			);
			exit(json_encode($data));
		}
	}
	//添加操作在后台管理员控制器里
}
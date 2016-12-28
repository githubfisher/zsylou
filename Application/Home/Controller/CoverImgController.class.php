<?php
namespace Home\Controller;
use Think\Controller;
class CoverImgController extends Controller{
	public function __initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	// 预留
	public function index(){

	}
	// 查询客户端的最新版本 参数：操作系统 os 【 ios 2 ； Android 1 ；】
	public function query(){
		logger('查询APP的封面信息！');
		//接收uid ,进而判断sid
		$post = I();
		$uid = $post['uid'];
		$app_user = D('app_user');
		$where = array(
			'uid' => $uid
		);
		$user = $app_user->where($where)->field('sid')->find();
		$sid = $user['sid'];
		$cover_img = D('cover_img');
		$where = array(
			'sid' => 0,
			'is_open' => 1
		);
		if($sid != '' && $sid != NULL){
			$where = array(
				'is_open' => 1,
				'sid' => array(array('eq',0),array('eq',$sid),'or')
			);
		}
		$result = $cover_img->field('sid,url')->where($where)->order('sid desc,time desc,show_order asc')->select();
		if($result){
			logger('存在封面图片信息，将其返回客户端');
			$data = array(
				'code' => 1,
				'message' => '封面图片信息返回成功！',
				'result' => $result
			);
			exit(json_encode($data));
		}else{
			logger('不存在封面图片信息');
			$data = array(
				'code' => 0,
				'message' => '不存在封面图片',
				'result' => ''
			);
			exit(json_encode($data));
		}
	}
	//添加操作在后台管理员控制器里
}
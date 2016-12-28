<?php
namespace Home\Controller;
use Think\Controller;
class AqueryController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function index(){

	}
	// 查询我发起的申请
	public function mysply(){
		logger('我的申请查询-开始');
		$uid = session('uid');
		$leave = D('appro_leave');
		$out = D('appro_out');
		$general = D('appro_general');
		$where = array(
			'uid' => $uid
		);
		$r_leave = $leave->where($where)->order('sply_time asc')->field('id,sply_time,status,uid')->select();
		$r_out = $out->where($where)->select();
		$r_general = $general->where($where)->select();
		if($r_leave || $r_out || $r_general){
			// $data = array(
			// 	'leave' => $r_leave,
			// 	'outgo' => $r_out,
			// 	'general' => $r_general,
			// 	'code' => 1,
			// 	'message' => '我发起的申请返回成功'
			// );
			$arr = array_merge_recursive($r_leave,$r_out,$r_general);
			$data = array(
				'result' => $arr,
				'code' => 1,
				'message' => '我发起的申请返回成功'
			);
			logger("返回申请成功\n");
			exit(json_encode($data));
		}else{
			$data = array(
				'code' => 0,
				'message' => '没有申请信息'
			);
			exit(json_encode($data));
		}
	}
	// 查询需要我审批的
	public function myappro(){
		logger('我的审批查询-开始');
		$suid = session('suid');
		$leave = D('appro_leave');
		$out = D('appro_out');
		$general = D('appro_general');
		$where = array(
			'approver' => $suid
		);
		$r_leave = $leave->where($where)->select();
		$r_out = $out->where($where)->select();
		$r_general = $general->where($where)->select();
		if($r_leave || $r_out || $r_general){
			$data = array(
				'leave' => $r_leave,
				'outgo' => $r_out,
				'general' => $r_general,
				'code' => 1,
				'message' => '我的审批返回成功'
			);
			logger("返回审批成功\n");
			exit(json_encode($data));
		}else{
			$data = array(
				'code' => 0,
				'message' => '没有审批信息'
			);
			exit(json_encode($data));
		}
	}
	//审批操作
	public function appro(){
		logger('审批操作开始');
		$post = I();
		
	}
}
?>
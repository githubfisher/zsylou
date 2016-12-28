<?php
namespace Home\Controller;
use Think\Controller;
class IosIsReadController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//查询未读的消息（请假，外出，通用，物品领用，公告）
	public function query_all_unread(){
			logger('IOS查询所有未读消息-------IOS--------unread---------开始');
			$suid = session('suid');
			$sid = session('sid');
			$uid = session('uid');
			$leave = D('appro_leave');
			$out = D('appro_out');
			$general = D('appro_general');
			$items = D('appro_items');
			$bbs = D('bbs');
			//新建数组存储查询结果： action:  1 公告  2 审批 type: 审批项目:  1 待我审批 , 2 我发起的
			$array = array();
			//如果是管理员，则查询公告，我的审批和我发起的申请
			if(session('wtype') == 1){
				// 查询条件
				$my_bbs = array(
					'sid' => $sid,
					'ios_is_read' => 0
				);
				$my_pro = array(
					'approver' => $suid,
					'ios_is_read' => 0
				);
				$my_sply = array(
					'uid' => $uid,
					'ios_is_read' => 0
				);
				//查询
				// 公告
				$r_bbs = $bbs->where($my_bbs)->find();
				if($r_bbs){
					$array[0]['action'] = 1;
					$array[0]['type'] = '';
				}else{
					$array[0]['action'] = 0;
					$array[0]['type'] = 0;
				}
				//待我审批
				$r_leave = $leave->where($my_pro)->find();
				$r_out = $out->where($my_pro)->find();
				$r_general = $general->where($my_pro)->find();
				$r_items = $items->where($my_pro)->find();
				if($r_leave || $r_out || $r_general || $r_items){
					$array[1]['action'] = 2;
					$array[1]['type'] = 1;
				}else{
					$array[1]['action'] = 0;
					$array[1]['type'] = 0;
				}
				//我的申请
				$r_leave = $leave->where($my_sply)->find();
				$r_out = $out->where($my_sply)->find();
				$r_general = $general->where($my_sply)->find();
				$r_items = $items->where($my_sply)->find();
				if($r_leave || $r_out || $r_general || $r_items){
					$array[2]['action'] = 2;
					$array[2]['type'] = 2;
				}else{
					$array[2]['action'] = 0;
					$array[2]['type'] = 0;
				}
				// 合并查询结果
				foreach($array as $k => $v){
					if($v['action'] != 0){
						$code = 1;
						$message = '服务器存在未读消息！';
						logger("服务器存在未读消息！\n");
					}else{
						if($code != 1){
							$code = 0;
							$message = '服务器不存在未读消息！';
							logger("服务器不存在未读消息！\n");
						}						
					}
				}
				$data = array(
					'code' => $code,
					'message' => $message,
					'result' => $array
				);
				logger("IOS客户端，服务器未读消息返回成功\n");
				exit(json_encode($data));
				
			}else{ //如果是普通员工，则查询公告和我发起的申请
				// 查询条件
				$my_bbs = array(
					'sid' => $sid,
					'ios_is_read' => 0
				);
				$my_sply = array(
					'uid' => $uid,
					'ios_is_read' => 0
				);
				//查询
				// 公告
				$r_bbs = $bbs->where($my_bbs)->find();
				if($_bbs){
					$array[0]['action'] = 1;
					$array[0]['type'] = '';
				}else{
					$array[0]['action'] = 0;
					$array[0]['type'] = 0;
				}
				//待我审批 ，不存在
					$array[1]['action'] = 0;
					$array[1]['type'] = 0;
				//我的申请
				$r_leave = $leave->where($my_sply)->find();
				$r_out = $out->where($my_sply)->find();
				$r_general = $general->where($my_sply)->find();
				$r_items = $items->where($my_sply)->find();
				if($r_leave || $r_out || $r_general || $r_items){
					$array[2]['action'] = 2;
					$array[2]['type'] = 2;
				}else{
					$array[2]['action'] = 0;
					$array[2]['type'] = 0;
				}
				// 合并查询结果
				foreach($array as $k => $v){
					if($v['action'] != 0){
						$code = 1;
						$message = '服务器存在未读消息！';
						logger("服务器存在未读消息！\n");
					}else{
						$code = 0;
						$message = '服务器不存在未读消息！';
						logger("服务器不存在未读消息！\n");
					}
				}
				$data = array(
					'code' => $code,
					'message' => $message,
					'result' => $array
				);
				logger("IOS客户端，服务器未读消息返回成功\n");
				exit(json_encode($data));
			}
}
	// 未读消息变更已读
	public function isreaded(){
		$post = I();
		$action = $post['action'];
		$type = $post['type'];
		$suid = session('suid');
		$sid = session('sid');
		$uid = session('uid');
		$read = array(
			'ios_is_read' => 1
		);
		if($action == 1){
			logger('公告消息---已读');
			$bbs = D('bbs');
			// 查询条件
			$my_bbs = array(
				'sid' => $sid,
				'ios_is_read' => 0
			);
			// 查询 - 修改
			$r_bbs = $bbs->where($my_bbs)->save($read);
			if($r_bbs){
				$data = array(
					'code' => 1,
					'message' => '消息已读修改成功'
				);
				logger("消息已读修改成功\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => 0,
					'message' => '消息已读修改失败！'
				);
				logger("消息已读修改失败！\n");
				exit(json_encode($data));
			}
		}else{
			$leave = D('appro_leave');
			$out = D('appro_out');
			$general = D('appro_general');
			$items = D('appro_items');
			$my_pro = array(
				'approver' => $suid,
				'ios_is_read' => 0
			);
			$my_sply = array(
				'uid' => $uid,
				'ios_is_read' => 0
			);
			// type 1 待我审批 , 2 我发起的
			switch($type){
				case 1:
					$r_leave = $leave->where($my_pro)->save($read);
					$r_out = $out->where($my_pro)->save($read);
					$r_general = $general->where($my_pro)->save($read);
					$r_items = $items->where($my_pro)->save($read);
					if($r_leave || $r_out || $r_general || $r_items){
						$data = array(
							'code' => 1,
							'message' => '消息已读修改成功'
						);
						logger("消息已读修改成功\n");
						exit(json_encode($data));
					}else{
						$data = array(
							'code' => 2,
							'message' => '消息已读修改失败！'
						);
						logger("消息已读修改失败！\n");
						exit(json_encode($data));
					}
					// break;
				case 2:
					$r_leave = $leave->where($my_sply)->save($read);
					$r_out = $out->where($my_sply)->save($read);
					$r_general = $general->where($my_sply)->save($read);
					$r_items = $items->where($my_sply)->save($read);
					if($r_leave || $r_out || $r_general || $r_items){
						$data = array(
							'code' => 1,
							'message' => '消息已读修改成功'
						);
						logger("消息已读修改成功\n");
						exit(json_encode($data));
					}else{
						$data = array(
							'code' => 2,
							'message' => '消息已读修改失败！'
						);
						logger("消息已读修改失败！\n");
						exit(json_encode($data));
					}
					// break;
				default:
					$data = array(
						'code' => 0,
						'message' => '没有要修改的消息！'
					);
					logger("没有要修改的消息！\n");
					exit(json_encode($data));
					// break;
			}
		}
	}
}
?>
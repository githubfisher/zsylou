<?php
namespace Home\Controller;
use Think\Controller;
class ApproveController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
		//引入极光推送类库文件
		// Vendor('Jpush.Jpush');
		// Vendor('Jpush.core.DevicePayload');
		// Vendor('Jpush.core.JPushException');
		// Vendor('Jpush.core.PushPayload');
		// Vendor('Jpush.core.ReportPayload');
		// Vendor('Jpush.core.SchedulePayload');
		Vendor('Jpush2.Client');
		Vendor('Jpush2.core.Config');
		Vendor('Jpush2.core.JPushException');
		Vendor('Jpush2.core.APIConnectionException');
		Vendor('Jpush2.core.APIRequestException');
		Vendor('Jpush2.core.Http');
		Vendor('Jpush2.core.DevicePayload');
		Vendor('Jpush2.core.PushPayload');
		Vendor('Jpush2.core.ReportPayload');
		Vendor('Jpush2.core.SchedulePayload');
	}
	//预留
	public function index(){

	}
	//待审批的申请
	public function unappro(){
		$post = I();
		//if(session('wtype') == 1){
			logger('我的待审批查询-开始');
			//$suid = session('suid');
			//利用uid查询 2016-06-01
			$uid = session('uid');
			$leave = D('appro_leave');
			$out = D('appro_out');
			$general = D('appro_general');
			$items = D('appro_items');
			//增加分页
			$page = $post['page'];
			$where = array(
				'approver' => $uid,
				'status' => 0
			);
			$r_leave = $leave->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
			$r_out = $out->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
			$r_general = $general->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
			$r_items = $items->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
			// 合并查询结果
			$arr = array_merge_recursive($r_leave,$r_out,$r_general,$r_items);
			//重新排序，按照申请时间从大到小
			$key = 'sply_time';
			$sort = 'DESC';
			$arr = array_sort($arr,$key,$sort);
			if($r_leave || $r_out || $r_general){
				//查询头像 2016-06-01
				$app_user = D('app_user');
				foreach($arr as $k => $v){
					//先查询申请者头像
					$where = array(
						'uid' => $v['uid']
					);
					$sply_user = $app_user->where($where)->field('head')->find();
					if(strpos($sply_user['head'],'Uploads/avatar/')){
						$arr[$k]['sply_head'] = C('base_url').$sply_user['head'];
					}else{
						$arr[$k]['sply_head'] = '';
					}
					// 再查询审核人头像
					$where = array(
						'uid' => $v['approver']
					);
					$approver_user = $app_user->where($where)->field('head')->find();
					if(strpos($approver_user['head'],'Uploads/avatar/')){
						$arr[$k]['appro_head'] = C('base_url').$approver_user['head'];
					}else{
						$arr[$k]['appro_head'] = '';
					}
				}
				//查询头像 END
				$data = array(
					// 'leave' => $r_leave,
					// 'outgo' => $r_out,
					// 'general' => $r_general,
					'result' => $arr,
					'code' => 1,
					'message' => '待审批返回成功'
				);
				logger("待审批返回成功\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => 0,
					'message' => '没有待审批信息'
				);
				logger("没有待审批信息\n");
				exit(json_encode($data));
			}
	// }else{
	// 	logger('员工无权限查询');
	// 	$data = array(
	// 		'code' => 2,
	// 		'message' => '员工账号无权限'
	// 	);
	// 	exit(json_encode($data));
	// }
}
	// 已审批的申请
	public function approed(){
		$post = I();
		//if(session('wtype') == 1){
			logger('我的已审批查询-开始');
			//$suid = session('suid');
			//利用uid查询 2016-06-01
			$uid = session('uid');
			$leave = D('appro_leave');
			$out = D('appro_out');
			$general = D('appro_general');
			$items = D('appro_items');
			//增加分页
			$page = $post['page'];
			$where = array(
				'approver' => $uid,
				'status' => array('neq',0)
			);
			$r_leave = $leave->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
			$r_out = $out->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
			$r_general = $general->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
			$r_items = $items->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
			// 合并查询结果
			$arr = array_merge_recursive($r_leave,$r_out,$r_general,$r_items);
			//重新排序，按照申请时间从大到小
			$key = 'sply_time';
			$sort = 'DESC';
			$arr = array_sort($arr,$key,$sort);
			if($r_leave || $r_out || $r_general){
				//查询头像 2016-06-01
				$app_user = D('app_user');
				foreach($arr as $k => $v){
					//先查询申请者头像
					$where = array(
						'uid' => $v['uid']
					);
					$sply_user = $app_user->where($where)->field('head')->find();
					if(strpos($sply_user['head'],'Uploads/avatar/')){
						$arr[$k]['sply_head'] = C('base_url').$sply_user['head'];
					}else{
						$arr[$k]['sply_head'] = '';
					}
					// 再查询审核人头像
					$where = array(
						'uid' => $v['approver']
					);
					$approver_user = $app_user->where($where)->field('head')->find();
					if(strpos($approver_user['head'],'Uploads/avatar/')){
						$arr[$k]['appro_head'] = C('base_url').$approver_user['head'];
					}else{
						$arr[$k]['appro_head'] = '';
					}
				}
				//查询头像 END
				$data = array(
					// 'leave' => $r_leave,
					// 'outgo' => $r_out,
					// 'general' => $r_general,
					'result' => $arr,
					'code' => 1,
					'message' => '已审批返回成功'
				);
				logger("已审批返回成功\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => 0,
					'message' => '没有已审批信息'
				);
				logger("没有已审批信息\n");
				exit(json_encode($data));
			}
		// }else{
		// 	logger('员工无权限查询');
		// 	$data = array(
		// 		'code' => 2,
		// 		'message' => '员工账号无权限'
		// 	);
		// 	exit(json_encode($data));
		// }
	}
	// 我发起的申请
	public function myappro(){
		$post = I();
		logger('我的申请查询-开始');
		$uid = session('uid');
		$leave = D('appro_leave');
		$out = D('appro_out');
		$general = D('appro_general');
		$items = D('appro_items');
		//增加分页
		$page = $post['page'];
		$where = array(
			'uid' => $uid
		);
		$r_leave = $leave->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_out = $out->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_general = $general->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_items = $items->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		// 合并查询结果
		$arr = array_merge_recursive($r_leave,$r_out,$r_general,$r_items);
		//重新排序，按照申请时间从大到小
		$key = 'sply_time';
		$sort = 'DESC';
		$arr = array_sort($arr,$key,$sort);
		if($r_leave || $r_out || $r_general){
			//查询头像 2016-06-01
			$app_user = D('app_user');
			foreach($arr as $k => $v){
				//先查询申请者头像
				$where = array(
					'uid' => $v['uid']
				);
				$sply_user = $app_user->where($where)->field('head')->find();
				if(strpos($sply_user['head'],'Uploads/avatar/')){
					$arr[$k]['sply_head'] = C('base_url').$sply_user['head'];
				}else{
					$arr[$k]['sply_head'] = '';
				}
				// 再查询审核人头像
				$where = array(
					'uid' => $v['approver']
				);
				$approver_user = $app_user->where($where)->field('head')->find();
				if(strpos($approver_user['head'],'Uploads/avatar/')){
					$arr[$k]['appro_head'] = C('base_url').$approver_user['head'];
				}else{
					$arr[$k]['appro_head'] = '';
				}
			}
			//查询头像 END
			$data = array(
				// 'leave' => $r_leave,
				// 'outgo' => $r_out,
				// 'general' => $r_general,
				'result' => $arr,
				'code' => 1,
				'message' => '我的申请返回成功'
			);
			logger("我的申请返回成功\n");
			exit(json_encode($data));
		}else{
			$data = array(
				'code' => 0,
				'message' => '没有我的申请信息'
			);
			logger("没有我的申请信息\n");
			exit(json_encode($data));
		}
	}
	// 查询审批详情接口
	public function query(){
		logger('申请详情查询--开始');
		$post = I();
		$where = array(
			'id' => $post['id'],
			'kind' => $post['kind']
		);
		if($post['id'] && $post['kind']){
			switch($post['kind']){
				case 1:
					$general = D('appro_general');
					$result = $general->where($where)->find(); //查询订单全部信息，并返回
					break;
				case 2:
					$leave = D('appro_leave');
					$result = $leave->where($where)->find();
					break;
				case 3:
					$out = D('appro_out');
					$result = $out->where($where)->find();
					break;
				default:
					$items = D('appro_items');
					$result = $items->where($where)->find();
					break;
			}
			if($result){
				logger("申请信息详情返回成功\n");
				//查询头像 2016-06-01
				$app_user = D('app_user');
				//先查询申请者头像
				$where = array(
					'uid' => $result['uid']
				);
				$sply_user = $app_user->where($where)->field('head')->find();
				if(strpos($sply_user['head'],'Uploads/avatar/')){
					$result['sply_head'] = C('base_url').$sply_user['head'];
				}else{
					$result['sply_head'] = '';
				}
				// 再查询审核人头像
				$where = array(
					'uid' => $result['approver']
				);
				$approver_user = $app_user->where($where)->field('head')->find();
				if(strpos($approver_user['head'],'Uploads/avatar/')){
					$result['appro_head'] = C('base_url').$approver_user['head'];
				}else{
					$result['appro_head'] = '';
				}
				//查询头像 END
				$data = array(
					'result' => $result,
					'code' => 1,
					'message' => '申请信息详情返回成功'
				);
				exit(json_encode($data));
			}else{
				logger("无申请信息\n");
				$data = array(
					'code' => 0,
					'message' => '无申请信息'
				);
				exit(json_encode($data));
			}
		}else{
			logger("参数信息不全\n");
			$data = array(
				'code' => 2,
				'message' => '参数信息不全'
			);
			exit(json_encode($data));
		}
	}
	//审核操作
	public function approve(){
		$post = I();
		logger('审核操作参数:'.var_export($post,true)); // debug
		$id = $post['id'];
		$opinion = $post['opinion'];
		$details = $post['details'];
		$kind = $post['kind'];
		if($opinion && $kind && $id){
			switch($kind){
				case 1:
					$link = D('appro_general');
					break;
				case 2:
					$link = D('appro_leave');
					break;
				case 3:
					$link = D('appro_out');
					break;
				default:
					$link = D('appro_items');
					break;
			}
			$where = array(
				'id' => $id
			);
			//判断当前管理员是否和申请的审核人是否为同一人
			$check = $link->where($where)->find();
			// logger('check:'.var_export($check,TRUE));//debug
			if($check['approver'] != session('uid')){
				$data = array(
					'code' => 3,
					'message' => '审核人与当前管理员不匹配!'
				);
				logger('审核人与当前管理员不匹配!'."\n");
				exit(json_encode($data));
			}
			//修改申请状态, 审核操作
			$where['aporover'] = session('suid');
			$data = array(
				'status' => $opinion,
				'appro_opinion' => $details,
				'appro_time' => time()
			);
			$result = $link->where($where)->save($data);
			if($result){
				logger('审核成功!');
				$data = array(
					'code' => '1',
					'message' => '审核成功!'
				);
				//Jpush推送
				//数据准备
				if($details == '' || $details == NULL){
					if($opinion == 1){
						$opinion_detail = '同意';
					}else{
						$opinion_detail = '拒绝';
					}
				}else{
					if(strlen($details) > 45){
						$opinion_detail = substr($details,0,45).'...';
					}else{
						$opinion_detail = $details;
					}	
				}
				$msg = array(
					'platform' => 'all',
					'alias' => array(0 => $check['uid']),
					'msg' => array(
						'content' => '审批意见: '.$opinion_detail,
						'title' => '审批回复',
						'category' => '',
						'message' => array(
							'action' => 2,
							'type' => 2,
							'details' => array(
								'id' => $id,
								'kind'=> $kind,
								'sply_nickname' => $check['sply_nickname'],
								'appro_nickname' => $check['appro_nickname'],
								'sply_time' => time(),
								'status' => $opinion
							)
						)
					)
				);
				$j_result = jpush($msg);
				logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => '0',
					'message' => '审核失败,请重试!'
				);
				logger('审核失败!'."\n");
				exit(json_encode($data));
			}
		}else{
			$data = array(
				'code' => '2',
				'message' => '信息提交不全,审核失败!'
			);
			logger('信息提交不全,审核失败!'."\n");
			exit(json_encode($data));
		}
	}

	// 计数
	public function count()
	{
		logger('审批意见计数...');
		$count = array();

		// 待审批的申请
		//利用uid查询 2016-06-01
		$uid = session('uid');
		$leave = D('appro_leave');
		$out = D('appro_out');
		$general = D('appro_general');
		$items = D('appro_items');
		//增加分页
		$page = $post['page'];
		$where = array(
			'approver' => $uid,
			'status' => 0
		);
		$r_leave = $leave->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_out = $out->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_general = $general->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_items = $items->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		// 合并查询结果
		$arr = array_merge_recursive($r_leave,$r_out,$r_general,$r_items);
		$count['unappro'] = count($arr);

		// 我已审批
		$where = array(
			'approver' => $uid,
			'status' => array('neq',0)
		);
		$r_leave = $leave->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_out = $out->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_general = $general->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_items = $items->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		// 合并查询结果
		$arr = array_merge_recursive($r_leave,$r_out,$r_general,$r_items);
		$count['approed'] = count($arr);

		// 我发起的
		unset($where['status']);
		unset($where['approver']);
		$where['uid'] = $uid;
		$r_leave = $leave->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_out = $out->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_general = $general->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		$r_items = $items->where($where)->field('id,uid,approver,kind,sply_nickname,appro_nickname,sply_time,status')->order('sply_time desc')->cache(true,60)->select();
		// 合并查询结果
		$arr = array_merge_recursive($r_leave,$r_out,$r_general,$r_items);
		$count['myappro'] = count($arr);

		// 返回结果
		$data = array(
			'code' => 1,
			'message' => '统计审批结果！',
			'result' => $count
		);
		exit(json_encode($data));
	}
}
?>
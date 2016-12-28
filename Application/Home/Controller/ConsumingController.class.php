<?php
namespace Home\Controller;
use Think\Controller;
class ConsumingController extends Controller{
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
	//物品领用申请函数
	public function sply(){
		logger('物品领用申请--------------------------Sply----------------------------------->开始');
		$post = I();
		$uid = session('uid');
		logger('用户'.$uid.'申请物品领用');
		// logger('提交参数：'.var_export($post,TRUE));//debug
		//查询审核人信息
		// $store_admin = D('store_admin');
		// $appro_admin = $store_admin->where(array('id'=> $post['approver']))->find();
		//弃用store_admin表 2016-5-25
		$app_user = D('app_user');
		$appro_admin = $app_user->where(array('uid'=> $post['approver']))->find();
		if($post['usages'] && $post['items'] && $post['details'] && $post['approver']){
			$items = D('appro_items');
			$data = array(
				'uid' => $uid,
				'sply_time' => time(),
				'usages' => $post['usages'],
				'nums' => $post['nums'],
				'items' => $post['items'],
				'details' => $post['details'],
				'sid' => session('sid'),
				'suid' => session('suid'),
				'approver' => $post['approver'],
				'sply_nickname' => session('admin_nickname'),
				'appro_nickname' => $appro_admin['nickname']
			);
			$result = $items->add($data);
			if($result){
				logger('物品领用申请成功！'."\n");
				//Jpush推送
				//数据准备
				$reason = $post['details'];
				if(strlen($reason) > 45){
					$content = session('admin_nickname').': '.mb_substr($reason,0,45,'utf8').'...';
				}else{
					$content = session('admin_nickname').': '.$reason;
				}
				$msg = array(
					'platform' => 'all',
					'alias' => array(0 => $appro_admin['uid']),
					'msg' => array(
						'content' => $content,
						'title' => '物品领用申请',
						'category' => '',
						'message' => array(
							'action' => 2,
							'type' => 1,
							'details' => array(
								'id' => $result,
								'kind'=> 4,
								'sply_nickname' => session('admin_nickname'),
								'appro_nickname' => $appro_admin['nickname'],
								'sply_time' => time(),
								'status' => 0
							)
						)
					)
				);
				$j_result = jpush($msg);
				if($j_result == 1101){
					logger('JPush---自定义简单发送----结果： 审核人未登录,消息推送失败!------完毕------'."\n");
					$data = array(
						'code' => '3',
						'message' => '申请成功,因审核人未登录,消息推送失败!'
					);
				}else{
					logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
					$data = array(
						'code' => '1',
						'message' => '物品领用申请成功'
					);
				}
				//回复客户端
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => '0',
					'message' => '申请失败'
				);
				logger('物品领用申请失败！'."\n");
				exit(json_encode($data));
			}
		}else{
			$data = array(
					'code' => '2',
					'message' => '申请信息不全，申请失败'
				);
				logger('物品领用,申请信息不全，申请失败！'."\n");
				exit(json_encode($data));
		} 
	}
	//物品领用查询函数
	public function query(){
		logger('物品领用查询--------------------------Query----------------------------------->开始');
		$wtype = session('wtype');
		if($wtype == 1){
			logger('店长（老板）：'.session('appuser').'查询申请记录');
			$items = D('appro_items');
			// 如果会员是店长，则返回全店的请假申请
			$where = array(
				'sid' => session('sid')
			);
			$result = $items->where($where)->order('sply_time asc')->select();
			if($result){
				$data = array(
					'result' => $result,
					'code' => 1,
					'message' => '物品领用记录返回成功'
				);
				logger('老板,请假申请记录返回成功'."\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => '0',
					'message' => '无物品领用申请记录'
				);
				logger('老板,无物品领用申请记录'."\n");
				exit(json_encode($data));
			}
		}else{
			$items = D('appro_items');
			$where = array(
				'suid' => session('suid')
			);
			$result = $items->where($where)->order('sply_time asc')->select();
			if($result){
				$data = array(
					'result' => $result,
					'code' => 1,
					'message' => '物品领用记录返回成功'
				);				
				logger('员工,请假申请记录返回成功'."\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => '0',
					'message' => '无物品领用申请记录'
				);
				logger('员工,无物品领用申请记录'."\n");
				exit(json_encode($data));
			}
		} 
	}
	//审核操作
	public function approve(){
		$post = I();
		$id = $post['id'];
		$opinion = $post['opinion'];
		$details = $post['details'];
		if($opinion){
			$items = D('appro_items');
			$where = array(
				'id' => $id
			);
			//判断当前管理员是否和申请的审核人是否为同一人
			$check = $items->where($where)->find();
			// logger('check:'.var_export($check,TRUE));//debug
			if($check['approver'] != session('suid')){
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
			$result = $items->where($where)->save($data);
			if($result){
				$data = array(
					'code' => '1',
					'message' => '物品领用申请,审核成功!'
				);
				logger('物品领用申请,审核成功!'."\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => '0',
					'message' => '物品领用申请,审核失败!'
				);
				logger('物品领用申请,审核失败!'."\n");
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
}
?>
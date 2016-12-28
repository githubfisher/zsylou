<?php
namespace Pad\Controller;
use Think\Controller;
class SessionCheckController extends Controller{
	public function index(){
		logger('PAD端---初始化');
		if(session('uid') && session('sid')){
			logger('PAD端---存在SESSION,判断用户合法性');
			$post = I();
			$userid = $post['uid']; 
			$uid = session('uid');
			// logger('SESSION_UID:'.$uid.' POST-Uid:'.$userid); //debug
			if($uid != $userid){
				$data = array(
					'code' => '5',
					'message' => '账户未登录'
				);
				logger("PAD端---APP用户未登录\n");
				exit(json_encode($data));
			}else{
				logger("PAD端---验证成功,合法登录\n");
			}
		}else{
			logger('PAD端---SESSION不存在,重新连接服务器');
			$post = I();
			$userid = $post['uid'];
			if($userid == '' || $userid == NULL){
				$data = array(
				'code' => '6',
				'message' => '非法用户请求'
				);
				logger("PAD端---非法APP用户请求，已驳回！\n");
				exit(json_encode($data));
			}else{
				$app_user = D('app_user');
				$where = array(
					'uid' => $userid
				);
				$user = $app_user->where($where)->find();
				if($user){
					session('uid',$userid); //APP用户uid写入session
					session('store_simple_name',$user['store_simple_name']); //影楼简写id 写入session
					session('wuser',$user['username']);
					session('wtype',$user['type']); //将员工的角色类型写入SESSION
					session('admin_name',$user['realname']); //员工姓名
					session('admin_nickname',$user['nickname']);//员工昵称
					session('dept',$user['dept']); //所属部门
					session('sid',$user['sid']); //店铺ID
				    logger("PAD端---重新登录成功\n");
				}else{
					logger("PAD端---无此用户\n");
					$arr = array(
						'code' => 7,
						'message' => '无此用户'
					);
					exit(json_encode($arr));
				}
			}
		}
	}
}
?>
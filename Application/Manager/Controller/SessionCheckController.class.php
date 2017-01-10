<?php
namespace Manager\Controller;
use Think\Controller;
class SessionCheckController extends Controller{
	public function index(){
		if(session('id')){
			logger('附近客户 -- 存在SESSION,判断用户合法性');
			$post = I('get.'); //先读取GET数组
			if(empty($post)){ //如果GET数组为空,则读取POST数组
				logger('附近客户 -- GET数组为空!');
				$post = I('post.');
			}
			$userid = $post['id']; 
			$uid = session('id');
			logger('附近客户 -- SESSION_ID:'.$uid.' POST-ID:'.$userid); //debug
			if($uid != $userid){
				logger("附近客户 -- 区域经理账户未登录\n");
				$data = array(
					'code' => '5',
					'message' => '账户未登录'
				);
				exit(json_encode($data));
			}else{
				logger("附近客户 -- 验证成功,合法登录\n");
			}
		}else{
			logger('附近客户 -- SESSION不存在,重新连接服务器');
			$post = I();
			$userid = $post['id'];
			if(empty($userid)){
				logger("附近客户 -- 非法APP用户请求，已驳回！\n");
				$data = array(
					'code' => '6',
					'message' => '非法用户请求'
				);
				exit(json_encode($data));
			}else{
				$managers = D('market_manager');
				$where = array(
					'id' => $userid
				);
				$manager = $managers->where($where)->field('id,name,phone')->find();
				if($manager){
					session('id',$manager['id']);
					session('name',$manager['name']);
					session('phone',$manager['phone']);
					logger('附近客户 -- 重新连接服务器成功！'."\n");
				}else{
					logger("附近客户 -- 无此用户\n");
					$data = array(
						'code' => 7,
						'message' => '无此用户'
					);
					exit(json_encode($data));
				}
			}
		}
	}
}
<?php
namespace Manager\Controller;
use Think\Controller;

class LoginController extends Controller {
	public function _initialize(){
		header("content-type:text/html; charset=utf-8;");
	}
	public function login(){
		$post = I();
		logger('附近客户 -- 区域经理：'.$post['username'].'，请求登录...'); //debug
		if($post['username'] && $post['password']){
			$managers = D('market_manager');
			$where = array(
				'phone' => $post['username'],
				'password' => $post['password']
			);
			$manager = $managers->where($where)->field('id,name')->find();
			if($manager){
				logger("附近客户 -- 登录成功！\n");
				session('id',$manager['id']);
				session('name',$manager['name']);
				session('phone',$post['phone']);
				$data = array(
					'code' => 1,
					'message' => '登录成功！',
					'result' => $manager
				);
			}else{
				logger("附近客户 -- 用户名或密码错误--登录失败\n");
				$data = array(
					'code' => 0,
					'message' => '用户名或密码错误，请重试！'
				);
			}
		}else{
			logger("附近客户 -- 用户名或密码为空--登录失败\n");
			$data = array(
				'code' => 2,
				'message' => '用户名或密码为空，请重试！'
			);
		}
		exit(json_encode($data));
	}
}

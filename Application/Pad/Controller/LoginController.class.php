<?php
namespace Pad\Controller;
use Think\Controller;
class LoginController extends Controller{
	public function _initialize(){
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//登录
	public function login(){
		$post = I();
		logger('PAD端用户：'.$post['username'].'，请求登录'); //debug
		if($post['username'] && $post['password']){
			//分离店铺标识和用户名 2016-5-23
			$username = ltrim(strchr($post['username'],'_'),'_');
			$store_simple_name = strchr($post['username'],'_',TRUE);
			//判断用户信息是否存在于本机数据库app_user表
			$appuser = D('app_user');
			$where = array(
				'username' => $username,
				'password' => $post['password'],  
				'store_simple_name' => $store_simple_name
			);
			// logger('查询用户条件：'.var_export($where,TRUE)); //debug
			$result = $appuser->field('password,qq,birth,head,attence_admin_group,attence_group,createtime,gender,is_register_easemob,location,loginip,logintime,modify_time,suid,vcip',TRUE)->where($where)->find();
			// logger('用户信息数组：'.var_export($result,TRUE)); //debug
			if($result){
				// 将生日时间戳转换成年月日形式返回
				foreach($result as $k => $v){
					if($k == 'birth'){
						$result[$k] = date('Y-m-d',$v); 
					}
				}
				$uid = $result['uid'];
				session('sid',$result['sid']); //店铺ID
				session('uid',$uid); //APP用户uid写入session
				session('store_simple_name',$result['store_simple_name']); //影楼简写id 写入session
				session('wuser',$result['username']);
				session('wtype',$result['type']); //将员工的角色类型写入SESSION
				session('admin_name',$result['realname']);
				session('admin_nickname',$result['nickname']);
				session('dept',$result['dept']); //所属部门
				// logger('输出SESSION数组:'.var_export($_SESSION,TRUE)); //debug
				//将登录信息写入数据库，最新登录时间，登录IP。
				$login_info = array(
					'logintime' => time(),
					'loginip' => get_client_ip()
				);
				$login_info_result = $appuser->where($where)->save($login_info);
				if($login_info_result){
					logger('登录信息，写入数据库成功！');
				}else{
					logger('登录信息，写入数据库失败！');
				}
				// $userstr = var_export($result,TRUE);  //debug
				// logger('用户信息：'.$userstr); //debug
				// 读取影楼PAD端logo
				$image = D('image');
				$cdition = array(
					'sid' => $result['sid'],
					'type' => 3,
					'status' => array('egt','1')
				);
				$logos = $image->where($cdition)->order('ctime desc')->select();
				// logger('logo数组:'.var_export($logos,TRUE)); //debug
				if(empty($logos)){
					$result['logo'] = '';
				}else{
					$result['logo'] = $_SERVER['HOST'].$logos[0]['path'];
				}
	        	$data = array(
	        		'code' => '1',
	        		'message' => '登录成功！',
	        		'result' => $result
	        	);
	        	logger("PAD端-登录成功\n");
			}else{
				//如果查询错误，则返回用户名或密码错误，登录失败
				$data = array(
					'code' => '0',
					'message' => '用户名或密码错误，登录失败！',
					'result' => ''
				);
				logger("用户名或密码错误--登录失败\n");
			}
		}else{
			//如果用户名或密码为空，返回错误信息
			$data = array(
				'code' => '2',
				'message' => '用户名或密码为空，登录失败！',
				'result' => ''
			);
			logger("用户名或密码为空--登录失败\n");
		}
		exit(json_encode($data));
	}
}
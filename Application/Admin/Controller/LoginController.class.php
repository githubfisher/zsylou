<?php
namespace Admin\Controller;
use Think\Controller;

class LoginController extends Controller{
	public function _initialize(){
		header("content-type:text/html; charset=utf-8;");
	}
	public function index(){
		$this->assign('title','登录--掌上影楼');
		$this->display();
	}
	public function login(){
		// $usr = var_export($_REQUEST,TRUE); //debug
		if(IS_AJAX){
			$name = $_POST['username'];
			$pwd = $_POST['password'];
			logger("APP会员--".$name."--请求登录管理后台-->");
			$user = D('app_user');
			$where = array(
				'password' => $pwd,
				'type' => array(array('eq',1),array('eq',3),'OR'),  //限制普通用户登录
			);
			if(strpos($name,"_")){
				//分离店铺标识和用户名 2016-5-23
				$username = ltrim(strchr($name,'_'),'_');
				$store_simple_name = strchr($name,'_',TRUE);
				$where['username'] = $username;
				$where['store_simple_name'] = $store_simple_name;
			}else{
				$where['username'] = $name;
			}
			$result = $user->where($where)->find();
			if($result){
				if($result['status'] != 1){
					$data['status'] = 0;
					$data['info'] = '账号未启用！';
					logger("账号未启用，登录失败！\n");
					$this->ajaxReturn($data);
				}
				session('name',$name);
				session('uid',$result['uid']); //用户ID
				session('sid',$result['sid']); //店铺ID
				//获取店铺信息
				$store = D('store');
				$where = array(
					'id' => $result['sid']
				);
				$store_result = $store->where($where)->find();
				if($store_result){
					logger('获取店铺信息，成功！');
					if(($store_result['expiring_on'] == 0 ) || (time() < strtotime(date('Y-m-d',$store_result['expiring_on']+86400)))){
						session('dogid',$store_result['dogid']);
						$url = 'http://' . $store_result['ip'] . ':' . $store_result['port'] . '/';
						logger('URL:'.$url); //debug
						session('url',$url); //将对应远程影楼服务器地址，保存至session
						session('store_simple_name',$store_result['store_simple_name']);
						session('pad',$store_result['pad']); //是否开启pad端
						session('expire_date',$store_result['expiring_on']); //到期日期
						session('location',$result['location']); //区域 对于区域经理来说这是id
						$data['status'] = 1;
						$data['info'] = '登录成功!';
						if($result['type'] == 3){
							$data['content'] = '/index.php/Admin/RegionalManager/index';
						}else{
							$data['content'] = '/index.php/Admin/Mylou/index';
						}
						logger("登录成功！\n");
					}else{
						logger('服务期满，拒绝登录！');
						$data['status'] = 0;
						$data['info'] = '服务期满，请尽快续费!';
						logger("登录失败！\n");
					}
				}else{
					logger('获取店铺信息，失败！');
					$data['status'] = 1;
					$data['info'] = '登录成功!';
					if($result['type'] == 3){
						$data['content'] = '/index.php/Admin/RegionalManager/index';
					}else{
						$data['content'] = '/index.php/Admin/Mylou/index';
					}
					logger("登录成功！\n");
				}
			}else{
				$data['status'] = 0;
				$data['info'] = '用户名或密码错误，登录失败！!';
				logger("用户名或密码错误，登录失败！\n");
			}

		}else{
			$data['status'] = 3;
			$data['info'] = '未收到任何信息，登录失败!';
			logger("未收到任何信息，登录失败!\n");
		}
		$this->ajaxReturn($data);
	}
	public function redirect(){
		$this->assign('title','请登录--掌上影楼');
		$this->display();
	}
	public function logout(){
		session('name',NULL);
		$this->redirect("{:U('Home/login/index')}",array(),0.1,'退出');
	}
}
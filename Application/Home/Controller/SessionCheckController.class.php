<?php
namespace Home\Controller;
use Think\Controller;
class SessionCheckController extends Controller{
	public function index(){
		logger('初始化');
		if(session('uid') && session('sid')){
			logger('存在SESSION,判断用户合法性');
			$post = I('get.'); //先读取GET数组
			if(empty($post)){ //如果GET数组为空,则读取POST数组
				logger('GET数组为空!');
				$post = I('post.');
			}
			// logger('传入入口的参数:'.var_export($post,TRUE)); //debug
			// logger('传入入口的$_GET:'.var_export($_GET,TRUE)); //debug
			// logger('传入入口的$_POST:'.var_export($_POST,TRUE)); //debug
			$userid = $post['uid']; 
			$uid = session('uid');
			logger('SESSION_UID:'.$uid.' POST-Uid:'.$userid); //debug
			if($uid != $userid){
				$data = array(
					'code' => '5',
					'message' => '账户未登录'
				);
				logger("APP用户未登录\n");
				exit(json_encode($data));
			}else{
				logger("验证成功,合法登录\n");
			}
		}else{
			logger('SESSION不存在,重新连接服务器');
			$post = I();
			$userid = $post['uid'];
			if($userid == '' || $userid == NULL){
				$data = array(
				'code' => '6',
				'message' => '非法用户请求'
				);
				logger("非法APP用户请求，已驳回！\n");
				exit(json_encode($data));
			}else{
				$app_user = D('app_user');
				$where = array(
					'uid' => $userid
				);
				$user = $app_user->where($where)->find();
				if($user){
					session('uid',$userid); //APP用户uid写入session
					session('appuser',$user['username']);
					logger('APP用户校验成功');
					session('store_simple_name',$user['store_simple_name']); //影楼简写id 写入session
					// session('suid',$workman['id']);
					session('wtype',$user['type']); //将员工的角色类型写入SESSION
					// session('wuser',$user['username']);//员工用户名
					session('admin_name',$user['realname']); //员工姓名
					session('admin_nickname',$user['nickname']);//员工昵称
					session('dept',$user['dept']); //所属部门
					session('group',$user['attence_group']);  //所在考勤组
					session('admin_group',$user['attence_admin_group']); //担任管理员的考勤组
					session('create_time', $result['createtime']); //账户创建时间
					session('vcip', $result['vcip']); //查看客户敏感信息权限
					logger('写入session/uid/wtype/sid/nickname/realname/dept:'.session('uid').'/'.session('wtype').'/'.session('sid').'/'.session('admin_nickname').'/'.session('admin_name').'/'.session('dept'));
					//登录影楼服务器
					// 查询对应服务器地址
					$store = D('store');
					$where = array(
						'id' => $user['sid']
					);
					$instore = $store->where($where)->find();
					session('sid',$instore['id']); //影楼id 写入session
					session('dogid',$instore['dogid']);
					if($instore){
						if(($store_result['expiring_on'] == 0 ) || (time() < strtotime(date('Y-m-d',$instore['expiring_on']+86400)))){
							$url = 'http://' . $instore['ip'] . ':' . $instore['port'] . '/';
							session('url',$url); //将对应远程影楼服务器地址，保存至session
							logger("查找到对应远程影楼服务器\n");
						}else{
							$arr = array(
				        		'code' => 8,
				        		'message' => '服务期满，请尽快续费！'
				        	);
				        	logger('服务期满，请尽快续费！');
				        	exit(json_encode($data));
						}
					}else{
						logger("未查找到对应远程影楼服务器,暂且放行\n");
					}
				}else{
					logger("无此用户\n");
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
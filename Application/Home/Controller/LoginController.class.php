<?php
namespace Home\Controller;
use Think\Controller;

class LoginController extends Controller{
	public function _initialize(){
		header("content-type:text/html; charset=utf-8;");
	}
	//显示登录页
	public function index(){
		$this->assign('title','登录--掌上影楼');
		$this->display();
	}
	//登录
	public function login(){
		$post = I();
		logger('APP用户：'.$post['username'].'，请求登录'); //debug
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
			$result = $appuser->field('password,modify_time',TRUE)->where($where)->find();
			// logger('用户信息数组：'.var_export($result,TRUE)); //debug
			if($result){
				// 将生日时间戳转换成年月日形式返回
				foreach($result as $k => $v){
					if($k == 'birth'){
						$result[$k] = date('Y-m-d',$v); 
					}
				}
				$uid = $result['uid'];
				session('uid',$uid); //APP用户uid写入session
				session('sid',$result['sid']); //将店铺id写入session
				session('appuser',$result['username']);
				session('store_simple_name',$result['store_simple_name']); //影楼简写id 写入session
				session('wtype',$result['type']); //将员工的角色类型写入SESSION
				session('admin_name',$result['realname']);
				session('admin_nickname',$result['nickname']);
				session('dept',$result['dept']); //所属部门
				session('group',$result['attence_group']);  //所在考勤组
				session('admin_group',$result['attence_admin_group']); //担任管理员的考勤组
				session('create_time', $result['createtime']); //账户创建时间
				session('vcip', $result['vcip']); //查看客户敏感信息权限
				logger('APP登录成功'); //debug
				logger('写入session/uid/wtype/sid/nickname/realname/dept:'.session('uid').'/'.session('wtype').'/'.session('sid').'/'.session('admin_nickname').'/'.session('admin_name').'/'.session('dept'));
				//将登录信息写入数据库，最新登录时间，登录IP。
				$login_info = array(
					'logintime' => time(),
					'loginip' => get_client_ip(),
					'sn'=> substr(md5($post['sn']),0,10)
				);
				$login_info_result = $appuser->where($where)->save($login_info);
				if($login_info_result){
					logger('登录信息，写入成功！');
				}else{
					logger('登录信息，写入失败！');
				}
				// $userstr = var_export($result,TRUE);  //debug
				// logger('用户信息：'.$userstr); //debug
				// 登录影楼服务器
				// 查询对应服务器地址
				$store = D('store');
				$where = array(
					'id' => $result['sid'],
					'store_simple_name' => $store_simple_name
				);
				$instore = $store->where($where)->find();
				if($instore){
					if(($store_result['expiring_on'] == 0 ) || (time() < strtotime(date('Y-m-d',$instore['expiring_on']+86400)))){
						$url = 'http://' . $instore['ip'] . ':' . $instore['port'] . '/';
						session('url',$url); 
						session('dogid',$instore['dogid']);
						logger(":):)-->查找到对应远程影楼服务器\n");
						$result['username'] = $result['store_simple_name'].'_'.$result['username'];
						if(strpos($result['head'],'Uploads/avatar/')){
							$result['head'] = C('base_url').$result['head'];
						}
						$arr = array(
			        		'code' => '1',
			        		'message' => '登录成功！',
			        		'result' => $result
			        	);
			        	logger("ALL-登录成功\n");
					}else{
						$arr = array(
			        		'code' => 8,
			        		'message' => '服务期满，请尽快续费！'
			        	);
			        	logger('服务期满，请尽快续费！');
					}
				}else{
					logger("Warning!:(:(-->未查找到对应店铺，暂时允许登录\n");
					$result['username'] = $result['store_simple_name'].'_'.$result['username'];
					if(strpos($result['head'],'Uploads/avatar/')){
						$result['head'] = C('base_url').$result['head'];
					}
					$arr = array(
		        		'code' => '1',
		        		'message' => '登录成功！',
		        		'result' => $result
		        	);
		        	logger("ONLY-USER-登录成功\n");
				}
				// if($instore){
				// 	$url = 'http://' . $instore['ip'] . ':' . $instore['port'] . '/';
				// 	// logger('URL:'.$url); //debug
				// 	session('url',$url); //将对应远程影楼服务器地址，保存至session
				// 	$xml = $this->transXML($result);
				// 	$getxml = getXML($url,$xml);
				// 	//将获取的XML数据转换为对象
		  //       	$obj = simplexml_load_string($getxml,'SimpleXMLElement',LIBXML_NOCDATA);
		  //       	if($obj->r == 'loginfailed'){
			 //        	$arr = array(
			 //        		'code' => '3',
			 //        		'message' => '远程影楼服务器登录失败！',
			 //        		'result' => $result
			 //        	);
			 //        	logger("远程影楼服务器--登录失败\n");
			 //        	exit(json_encode($arr));
			 //        }else{
			 //        	$arr = array(
			 //        		'code' => '1',
			 //        		'message' => '登录成功！',
			 //        		'result' => $result
			 //        	);
			 //        	logger("ALL-登录成功\n");
			 //        	exit(json_encode($arr));
			 //        }
				// }else{
				// 	$arr = array(
		  //       		'code' => '4',
		  //       		'message' => '未查找到对应远程服务器!',
		  //       		'result' => $result
		  //       	);
		  //       	logger("未查找到对应远程影楼服务器\n");
		  //       	exit(json_encode($arr));
				// }
			}else{
				//如果查询错误，则返回用户名或密码错误，登录失败
				$arr = array(
					'code' => '0',
					'message' => '用户名或密码错误，登录失败！',
					'result' => ''
				);
				logger("用户名或密码错误--登录失败\n");
			}
		}else{
			//如果用户名或密码为空，返回错误信息
			$arr = array(
				'code' => '2',
				'message' => '用户名或密码为空，登录失败！',
				'result' => ''
			);
			logger("用户名或密码为空--登录失败\n");
		}
		exit(json_encode($arr));
	}
	// 将数组转换成XML字符串
	public function transXML($arr){
    	$xmlTpl = "<aa>%s</aa><yy>%s</yy><uu>%s</uu><pp>%s</pp>";
    	$result = sprintf($xmlTpl,2,20150101080101888888,$arr['username'],$arr['password']);
    	return $result;
    }
}
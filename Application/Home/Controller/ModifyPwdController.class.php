<?php
namespace Home\Controller;
use Think\Controller;
class ModifyPwdController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
		Vendor('Easemob.Easemob');
	}
	//预留
	public function index(){

	}
	// 修改密码
	public function modify(){
		$post = I();
		$old_pwd = $post['oldpwd'];
		$new_pwd = $post['newpwd'];
		if($new_pwd && $old_pwd){
			$app_user = D('app_user');
			$where = array(
				'uid' => session('uid'),
				'password' => $old_pwd
			);
			$check_result = $app_user->where($where)->find();
			if($check_result){
				$data = array(
					'password' => $new_pwd,
					'modify_time' => time()
				);
				$result = $app_user->where($where)->save($data);
				if($result){
					logger('修改app——user密码成功！');
					$data = array(
						'code' => 1,
						'message' => '修改密码成功！'
					);
					//修改成功，下一步修改环信的密码
					$user = session('store_simple_name').'_'.session('appuser');
					$modify_result = modify_easemob_pwd($user,$new_pwd);
					logger('修改环信密码返回值：'.var_export($modify_result,TRUE));
					exit(json_encode($data));
				}else{
					logger('修改app——user密码成功！');
					$data = array(
						'code' => 0,
						'message' => '修改密码失败！'
					);
					exit(json_encode($data));
				}
			}else{
				logger('旧密码输入错误，修改密码操作终止！');
				$data = array(
					'code' => 3,
					'message' => '旧密码输入错误！'
				);
				exit(json_encode($data));
			}
		}else{
			$data = array(
				'code' => 2,
				'message' => '新密码不符合规范！'
			);
			exit(json_encode($data));
		}
	}
}
?>
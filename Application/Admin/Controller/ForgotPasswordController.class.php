<?php
namespace Admin\Controller;

use Think\Controller;

class ForgotPasswordController extends Controller
{
	public function mobile_exists()
	{
		$post = I();
		$mobile = $post['mobile'];
		$type = $post['type'];
		$user = D('app_user');
		$where = array(
			'mobile' => $mobile,
			'type' => 1,
		);
		$result = $user->where($where)->field('uid')->find(); /*用可能有重复，mobile字段需要唯一，但已经有重复的存在，所以只能到时候修改了*/
		if($result){
			$code = getRand(6,false);
			$msg = '验证码为：'.$code.'，您正在使用找回密码服务，请在2分钟内输入完成验证。如非本人操作请致电客服人员。提示：切勿将验证码信息泄露给他人！';
			if($this->sendCode($msg,$mobile)){
				$this->saveCode($code);
				$data = array(
					'status' => 1,
					'info' => '用户存在，发送验证码成功！',
					'data' => $result['uid']
				);
			}else{
				$data = array(
					'status' => 0,
					'info' => '验证码发送失败！'
				);
			}
		}else{
			$data = array(
				'status' => 2,
				'info' => '该手机号未注册管理员账号！'
			);
		}
		$this->ajaxReturn($data);
	}
	private function sendCode($content,$mobile)
	{
		Vendor('LeXin.HttpClient');
		Vendor('LeXin.SendSmsByDlsw');
		$result = \dlswSdk::sendSms($content.'【北京智诚】',$mobile);
		if($result === '-10000'){
			return false;
		}
		return true;
	}
	private function saveCode($code,$expire=120)
    {
        $sendCode = D('sendcode');
        $sendCode->add(array('name'=>session_id(),'code'=>$code,'create_at'=>time()));
    }
    private function getCode()
    {
        $sendCode = D('sendcode');
        $result = $sendCode->where(array('name'=>session_id()))->field('code,create_at,expire_time')->order('create_at desc')->limit(1)->select();
        return $result[0];
    }
	public function checkCode()
	{
		$post = I();
		$code = $post['code'];
		$saveCode = $this->getCode();
		if(($saveCode['create_at'] + $saveCode['expire_time']) <= time()){
			$data = array(
				'status' => 2,
				'info' => '验证码过期！'
			);
			$this->ajaxReturn($data);
		}
		if($code == $saveCode['code']){
			$data = array(
				'status' => 1,
				'info' => '验证码正确！'
			);
		}else{
			$data = array(
				'status' => 0,
				'info' => '验证码不正确！'
			);
		}
		$this->ajaxReturn($data);
	}
	public function reset()
	{
		$post = I();
		$pwd = $post['pwd'];
		$uid = $post['uid'];
		$mobile = $post['mobile'];
		if($pwd && $uid && $mobile){
			$user = D('app_user');
			$where = array(
				'uid' => $uid,
				'mobile' => $mobile,
				'type' => 1
			);
			$result = $user->where($where)->save(array('password'=>$pwd));
			if($result){
				$data = array(
					'status' => 1,
					'info' => '密码重置成功！现在为您转去登录页面'
				);
			}else{
				$data = array(
					'status' => 0,
					'info' => '密码重置失败！'
				);
			}
		}else{
			$data = array(
				'status' => 2,
				'info' => '参数不全，请重试！'
			);
		}
		$this->ajaxReturn($data);
	}
}
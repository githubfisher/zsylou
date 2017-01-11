<?php
namespace Manager\Controller;

use Think\Controller;

class MsgController extends Controller {
	public function _initialize()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function send()
	{
		logger("附近客户 -- 发送消息或推送...");
		$request = I();
		$title = $request['title'];
		$content = $request['content'];
		$uids = $request['customer'];
		$type = $request['type'];
		if($title && $content && $uids){
			$uids = chanslate_json_to_array($uids);
			if(empty($type) || $type == 1){
				$msgId = $this->saveMsg($title,$content,$uids,1);
				if($this->sendPush($title,$content,$uids)){
					$this->updateMsg($msgId);
					logger("附近客户 -- 发送消息 --发送成功\n");
					$data = array(
						'code' => 1,
						'message' => '消息发送成功！'
					);
				}else{
					logger("附近客户 -- 发送消息 --发送失败\n");
					$data = array(
						'code' => 0,
						'message' => '发送失败，请重试！'
					);
				}
			}else{
				$uids = implode(',',$uids);
				$msgId = $this->saveMsg($title,$content,$uids,2);
				if($this->sendMessage($title,$content,$uids)){
					$this->updateMsg($msgId);
					logger("附近客户 -- 发送消息 --发送成功\n");
					$data = array(
						'code' => 1,
						'message' => '消息发送成功！'
					);
				}else{
					logger("附近客户 -- 发送消息 --发送失败\n");
					$data = array(
						'code' => 0,
						'message' => '发送失败，请重试！'
					);
				}
			}
		}else{
			logger("附近客户 -- 查找附近客户，参数不全--登录失败\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	private function sendPush($title,$content,$uids)
	{
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
		$msg = array(
			'platform' => 'all',
			'alias' => $uids,
			'msg' => array(
				'content' => $content,
				'title' => $title,
				'category' => '',
				'message' => array(
					'action' => 9,
					'type' => 1,
					'details' => array(
						'title' => $title,
						'content'=> $content,
						'name' => session('name'),
						'phone' => session('phone'),
						'time' => time()
					)
				)
			)
		);
		$result = jpush($msg);
		return true;
	}
	private function sendMessage($title,$content,$uids)
	{
		Vendor('LeXin.HttpClient');
		Vendor('LeXin.SendSmsByDlsw');
		$result = \dlswSdk::sendSms($title.$content.'【北京智诚】',$uids);
		if($result === '-10000'){
			return false;
		}
		return true;
	}
	private function saveMsg($title,$content,$uids,$type)
	{
		$msg = D('market_message');
		$info = array(
			'mid' => session('id'),
			'title' => $title,
			'content' => $content,
			'customer' => $uids,
			'type' => $type,
			'create_at' => time()
		);
		$id = $msg->add($info);
		return $id;
	}
	private function updateMsg($id)
	{
		$msg = D('market_message');
		$msg->where(array('id'=>$id))->save(array('status'=>1));
	}
}
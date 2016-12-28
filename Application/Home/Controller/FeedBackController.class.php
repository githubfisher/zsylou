<?php
namespace Home\Controller;
use Think\Controller;
class FeedBackController extends Controller{
	public function __initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	// 预留
	public function index(){

	}
	// 提交问题反馈， 1 Bug，2 意见
	public function suggest(){
		logger('意见反馈提交-->');
		$post = I();
		$type = $post['type'];
		$content = $post['content'];
		$phone = $post['phone'];
		if($type && $content){
			$feedback = D('feedback');
			$data = array(
				'type' => $type,
				'content' => $content,
				'contacts' => $phone,
				'time' => time(),
				'sid' => session('sid'),
				'uid' => session('uid')
			);
			$feedback_result = $feedback->add($data);
			if($feedback_result){
				logger('意见反馈提交成功！\n');
				$return_data = array(
					'code' => 1,
					'message' => '意见反馈提交成功！'
				);
				exit(json_encode($return_data));
			}else{
				logger('意见反馈提交失败！\n');
				$return_data = array(
					'code' => 2,
					'message' => '意见反馈提交失败！'
				);
				exit(json_encode($return_data));
			}
		}else{
			logger('意见反馈提交数据参数不全！\n');
			$return_data = array(
				'code' => 0,
				'message' => '意见反馈提交数据参数不全！'
			);
			exit(json_encode($return_data));
		}
	}
}
<?php
namespace Home\Controller;
use Think\Controller;
class PayCodeController extends Controller{
	public function __initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//查询支付二维码 type 1 微信 2 支付宝
	public function query(){
		logger('查询支付二维码-->');
		$post = I();
		// logger('携带参数:'.var_export($post,TRUE)); //debug
		$type = $post['type'];
		$pay_code = D('pay_code');
		if($type){ //传类型时
			$where = array(
				'sid' => session('sid'),
				'type' => $type
			);
			$code = $pay_code->where($where)->field('url,type,sid')->order('time desc')->select();
			if($code){
				logger('支付二维码返回成功'."\n");
				$data = array(
					'code' => 1,
					'message' => '支付二维码返回成功!',
					'url' => $code[0]['url']
				);
			}else{
				logger('支付二维码不存在'."\n");
				$data = array(
					'code' => 0,
					'message' => '支付二维码不存在!'
				);
			}
			exit(json_encode($data));
		}else{ //不传类型,都返回
			$wei = array(
				'sid' => session('sid'),
				'type' => 1
			);
			$weixin = $pay_code->where($wei)->field('url,type,sid')->order('time desc')->select();
			$ali = array(
				'sid' => session('sid'),
				'type' => 2
			);
			$alipay = $pay_code->where($ali)->field('url,type,sid')->order('time desc')->select();
			if($weixin || $alipay){
				logger('支付二维码返回成功'."\n");
				$data = array(
					'code' => 1,
					'message' => '支付二维码返回成功!',
					'weipay' => $weixin[0]['url'],
					'alipay' => $alipay[0]['url']
				);
			}else{
				logger('支付二维码不存在'."\n");
				$data = array(
					'code' => 0,
					'message' => '支付二维码不存在!'
				);
			}
			exit(json_encode($data));
		}	
		
	}
}
?>
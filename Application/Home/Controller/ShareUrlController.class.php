<?php
namespace Home\Controller;

use Think\Controller;

class ShareUrlController extends Controller
{
	public function __construct()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}

	public function geturl(){
		logger('获取分享下载软件网页...');
		$post = I();
		$url = 'http://ylou.bjletu.com';
		$data = array(
			'code' => 1,
			'message' => 'URL返回成功!',
			'url' => $url
		);
		exit(json_encode($data));
	}
}
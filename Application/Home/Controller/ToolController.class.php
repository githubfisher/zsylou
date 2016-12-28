<?php
namespace Home\Controller;

use Think\Controller;

class ToolController extends Controller
{
	protected $check;
	public function __construct()
	{
		parent::__construct();
		$this->check = A('SessionCheck');
		// $scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}

	public function lists()
	{
		$this->check->index();
		logger('查询营销工具列表...');
		$tool = D('tool');
		$tools = $tool->field('id,name,ico1')->order('weight')->select();
		if($tools){
			logger('营销工具列表返回成功！'."\n");
			foreach($tools as $key => $value){
				$tools[$key]['ico'] = C('base_url').$tools[$key]['ico1'];
				unset($tools[$key]['ico1']);
			}
			$data = array(
				'code' => 1,
				'message' => '营销工具列表返回成功！',
				'result' => $tools 
			);
		}else{
			logger('营销工具列表无内容！'."\n");
			$data = array(
				'code' => 0,
				'message' => '营销工具列表无内容！',
			);
		}
		exit(json_encode($data));
	}

	// 获取应用详情
	public function content()
	{
		$this->check->index();
		$post = I();
		logger('查询id: '.$post['id'].' 营销工具的介绍页面...');
		if($post['id']){
			$tools = D('tool');
			$tool = $tools->where(array('id'=>$post['id']))->field('id,ico2,name,remarks,content')->find();
			if($tool){
				logger('营销工具介绍返回成功！'."\n");
				$tool['ico'] = C('base_url').$tool['ico2'];
				unset($tool['ico2']);
				$tool['content'] = C('base_url').'/index.php/home/tool/show?id='.$post['id'].'&uid='.session('uid');
				// 获取当前店铺是否购买该应用
				$tool['isBuy'] = 0; // 先定义未购买
				$isBuy = $this->getIsBuy($post['id']);
				if($isBuy)
					$tool['isBuy'] = 1;
				$data = array(
					'code' => 1,
					'message' => '营销工具介绍返回成功！',
					'result' => $tool
				);
			}else{
				logger('该营销工具不存在！'."\n");
				$data = array(
					'code' => 0,
					'message' => '不存在,请检查工具ID！'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		exit(json_encode($data));
	}

	// 显示应用说明H5
	public function show()
	{
		$post = I();
		logger('显示id:'.$post['id'].'营销工具介绍页面');
		$tools = D('tool');
		$tool = $tools->where(array('id'=>$post['id']))->field('content,name')->find();
		// logger(var_export($tool,true)); //debug
		if($tool){
			logger('显示id:'.$post['id'].'营销工具介绍成功!'."\n");
			$this->assign('title',$tool['name']);
			$this->assign('content',chansfer_to_html($tool['content']));
			$this->display('tool');
		}else{
			logger('id:'.$post['id'].'营销工具介绍无内容!'."\n");
			$this->display('nothing');
		}
	}

	// 获取当前店铺是否购买该应用
	private function getIsBuy($id)
	{
		$buy = D('tool_buy');
		$where = array(
			'sid' => session('sid'),
			'item' => $id
		);
		$field = 'create_at,expire_time';
		$result = $buy->where($where)->field($field)->find();
		if($result && ($result['create_at']+$result['expire_time']>time()))
			return true;
		return false;
	}
}
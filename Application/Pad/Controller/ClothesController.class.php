<?php
namespace Pad\Controller;
use Think\Controller;
class ClothesController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){
		
	}
	public function query(){
		logger("PAD端--查询服装");
		$post = I();
		logger('PAD端--携带参数：'.var_export($post,TRUE));
		$clothes = D('clothes');
		if($post['id'] && !$post['cid']){ //查询某一具体服装
			$result = $clothes->table('clothes a,clothes_category b')->where('a.style = b.id AND a.sid = '.session('sid').' AND a.id = '.$post['id'])->field('a.id,a.name,a.preview,a.sample,a.remark,b.id as type')->find();
			if($result){
				//处理图片路径
				if(trim($result['sample']) != '' && trim($result['sample']) != NULL){
					$imgs = explode(' ',trim($result['sample']));
				}else{
					$imgs = array();
				}
				$result['sample'] = json_encode($imgs);
				$data = array(
					'code' => 1,
					'message' => '服装内容返回成功！',
					'result' => $result
				);
				logger('PAD端---服装内容返回成功！'."\n");
			}else{
				$data = array(
					'code' => 0,
					'message' => '未查询到该服装内容！'
				);
				logger('PAD端---未查询到该服装内容！'."\n");
			}
		}elseif($post['cid'] && !$post['id']){ //查询某一类服装
			$result = $clothes->table('clothes a,clothes_category b')->where('a.style = b.id AND a.sid = '.session('sid').' AND a.style = '.$post['cid'])->field('a.id,a.name,a.preview,a.sample,a.remark,b.id as type')->order('a.id desc')->select();
			if($result){
				//处理图片路径
				foreach($result as $k => $v){
					if(trim($result[$k]['sample']) != '' && trim($result[$k]['sample']) != NULL){
						$imgs = explode(' ',trim($result[$k]['sample']));
					}else{
						$imgs = array();
					}
					$result[$k]['sample'] = json_encode($imgs);
				}
				$data = array(
					'code' => 1,
					'message' => '该类服装返回成功！',
					'result' => $result
				);
				logger('PAD端---该类服装返回成功！'."\n");
			}else{
				$data = array(
					'code' => 0,
					'message' => '未查询到该类服装！'
				);
				logger('PAD端---未查询到该类服装！'."\n");
			}
		}elseif(!$post['cid'] && !$post['id']){ //查询全部服装
			$result = $clothes->table('clothes a,clothes_category b')->where('a.style = b.id')->field('a.id,a.name,a.preview,a.sample,a.remark,b.id as type')->order('a.id desc')->select();
			if($result){
				//处理图片路径
				foreach($result as $k => $v){
					if(trim($result[$k]['sample']) != '' && trim($result[$k]['sample']) != NULL){
						$imgs = explode(' ',trim($result[$k]['sample']));
					}else{
						$imgs = array();
					}
					$result[$k]['sample'] = json_encode($imgs);
				}
				$data = array(
					'code' => 1,
					'message' => '全部服装返回成功！',
					'result' => $result
				);
				logger('PAD端---全部服装返回成功！'."\n");
			}else{
				$data = array(
					'code' => 0,
					'message' => '未查询到该类主题！'
				);
				logger('PAD端---未查询到该类主题！'."\n");
			}
		}else{
			$result = $clothes->table('clothes a,clothes_category b')->where('a.style = b.id AND a.sid = '.session('sid').' AND a.id = '.$post['id'].' AND b.id = '.$post['cid'])->field('a.id,a.name,a.preview,a.sample,a.remark,b.id as type')->find();
			if($result){
				//处理图片路径
				if(trim($result['sample']) != '' && trim($result['sample']) != NULL){
					$imgs = explode(' ',trim($result[$k]['sample']));
				}else{
					$imgs = array();
				}
				$result['sample'] = json_encode($imgs);
				$data = array(
					'code' => 1,
					'message' => '服装内容返回成功！',
					'result' => $result
				);
				logger('PAD端---服装内容返回成功！'."\n");
			}else{
				$data = array(
					'code' => 0,
					'message' => '未查询到该服装内容！'
				);
				logger('PAD端---未查询到该服装内容！'."\n");
			}
		}
		exit(json_encode($data));
	}
	//查询类型list
	public function lists(){
		logger('PAD端--查询服装类型列表');
		$category = D('clothes_category');
		$where = array(
			'sid' => session('sid')
		);
		$result = $category->where($where)->field('id,type')->select();
		if($result){
			$data = array(
				'code' => 1,
				'message' => '服装类型列表返回成功！',
				'result' => $result
			);
			logger('PAD端---服装类型列表返回成功！'."\n");
		}else{
			$data = array(
				'code' => 0,
				'message' => '服装类型列表返回失败！'
			);
			logger('PAD端---服装类型列表返回失败！'."\n");
		}
		exit(json_encode($data));
	}
}
<?php
namespace Pad\Controller;
use Think\Controller;
class SetController extends Controller{
	public function _initialize(){
		$padcheck = A('SessionCheck');
		$padcheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){
		
	}
	//列表
	public function lists($call = FALSE){
		logger("PAD端--查询套系列表");
		$set = D('sets');
		$where = array(
			'sid' => session('sid')
		);
		$sets = $set->where($where)->field('style')->order('id asc')->select();
		$sql = $set->getLastsql(); //debug
		logger('套系列表查询语句:'.$sql); //debug
		if($sets){
			$n = 0;
			foreach($sets as $k => $v){
				if($k == 0){
					$category[$n] = array(
						'id' => $n+1,
						'type' => $v['style']
					);
					$n++;
				}else{
					$size = count($category);
					$i = 1;
					foreach($category as $x => $y){
						if($y['type'] == $v['style']){
							break;
						}else{
							if($i == $size){
								$category[$size] = array(
									'id' => $n+1,
									'type' => $v['style']
								);
								$n++;
							}
							$i++;
						}
					}
				}
			}
			$data = array(
				'code' => 1,
				'message' => '套系列表返回成功！',
				'result' => $category
			);
			logger('PAD端---套系列表返回成功！');
		}else{
			$data = array(
				'code' => 0,
				'message' => '未查询到套系列表！'
			);
			logger('PAD端---未查询到套系列表！');
		}
		if($call == FALSE){
			logger('PAD端---返回前端'."\n");
			exit(json_encode($data));
		}else{
			logger('PAD端---返回系统调用'."\n");
			return $category;
		}
	}
	//内容
	public function query(){
		logger("PAD端--查询套系内容");
		$post = I();
		logger('PAD端--携带参数：'.var_export($post,TRUE));
		$set = D('sets');
		//获取列表
		$category = $this->lists(TRUE);
		if(empty($category)){
			$data = array(
				'code' => 0,
				'message' => '查询无内容！',
			);
		}else{
			if($post['id'] && !$post['cid']){ //查询某一具体套系
				$where = array(
					'id' => $post['id']
				);
				$result = $set->where($where)->field('id,name,price,products,preview,sample,remark,style')->find();
				if($result){	
					//处理图片路径
					if(trim($result['sample']) != '' && trim($result['sample']) != NULL){
						$imgs = explode(' ',trim($result['sample']));
					}else{
						$imgs = array();
					}
					$result['sample'] = json_encode($imgs);
					foreach($category as $k => $v){
						if($v['type'] == $result['style']){
							$result['type'] = $v['id'];
							unset($result['style']);
							break;
						}
					}
					//套系下产品
					$result['products'] = json_encode($this->chans_to_array($result['products']));
					$data = array(
						'code' => 1,
						'message' => '该套系返回成功！',
						'result' => $result
					);
				}else{
					$data = array(
						'code' => 0,
						'message' => '无该套系！',
					);
				}
			}elseif(!$post['id'] && $post['cid']){ //查询某一类套系
				foreach($category as $k => $v){
					if($v['id'] == $post['cid']){
						$where = array(
							'style' => $v['type']
						);
						break;
					}
				}
				$sets = $set->where($where)->field('id,name,price,products,preview,sample,remark')->select();
				if(empty($sets)){
					$data = array(
						'code' => 0,
						'message' => '该类套系无内容！',
					);
				}else{
					//处理图片路径
					foreach($sets as $k => $v){
						if(trim($sets[$k]['sample']) != '' && trim($sets[$k]['sample']) != NULL){
							$imgs = explode(' ',trim($sets[$k]['sample']));
						}else{
							$imgs = array();
						}
						$sets[$k]['sample'] = json_encode($imgs);
						$sets[$k]['type'] = $post['cid'];
						//套系下产品
						$sets[$k]['products'] = json_encode($this->chans_to_array($sets[$k]['products']));
					}
					$data = array(
						'code' => 1,
						'message' => '该类套系返回成功！',
						'result' => $sets
					);
				}
			}elseif($post['id'] && $post['cid']){ //查询某一具体套系
				foreach($category as $k => $v){
					if($v['id'] == $post['cid']){
						$where = array(
							'style' => $v['type']
						);
						break;
					}
				}
				$where['id'] = $post['id'];
				$result = $set->where($where)->field('id,name,price,products,preview,sample,remark')->find();
				if($result){
					//处理图片路径
					if(trim($result['sample']) != '' && trim($result['sample']) != NULL){
						$imgs = explode(' ',trim($result['sample']));
					}else{
						$imgs = array();
					}
					$result['sample'] = json_encode($imgs);
					$result['type'] = $post['cid'];
					//套系下产品
					$result['products'] = json_encode($this->chans_to_array($result['products']));
					$data = array(
						'code' => 1,
						'message' => '该套系返回成功！',
						'result' => $result
					);
				}else{
					$data = array(
						'code' => 0,
						'message' => '无该套系！',
					);
				}
			}else{ //查询全部服装
				$where = array(
					'sid' => session('sid')
				);
				$sets = $set->where($where)->field('id,name,price,products,preview,sample,remark,style')->select();
				if(empty($sets)){
					$data = array(
						'code' => 0,
						'message' => '套系无内容！',
					);
				}else{
					//处理图片路径
					foreach($sets as $k => $v){
						if(trim($sets[$k]['sample']) != '' && trim($sets[$k]['sample']) != NULL){
							$imgs = explode(' ',trim($sets[$k]['sample']));
						}else{
							$imgs = array();
						}
						$sets[$k]['sample'] = json_encode($imgs);
						foreach($category as $x => $y){
							if($y['type'] == $v['style']){
								$sets[$k]['type'] = $y['id'];
								break;
							}
						}
						unset($sets[$k]['style']);
						//套系下产品
						$sets[$k]['products'] = json_encode($this->chans_to_array($sets[$k]['products']));
					}
					$data = array(
						'code' => 1,
						'message' => '全部套系返回成功！',
						'result' => $sets
					);
				}
			}
		}
		exit(json_encode($data));
	}
	private function chans_to_array($str){
		$array = explode(' ',trim($str));
		$i = 0;
		$n = 0;
		$products = array();
		foreach($array as $v){
			switch($i%3){
				case 0:
					$products[$n]['name'] = $v;
					break;
				case 1:
					$products[$n]['price'] = $v;
					break;
				case 2:
					$products[$n]['num'] = $v;
					$n++;
					break;
				default:
					break;
			}
			$i++;
		}
		return $products;
	}
}
?>
<?php
namespace Pad\Controller;
use Think\Controller;
class ProductController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){
		
	}
	//列表
	public function lists($call = FALSE){
		logger("PAD端--查询产品列表");
		$product = D('product');
		$where = array(
			'sid' => session('sid')
		);
		$products = $product->where($where)->field('style')->order('id asc')->select();
		if($products){
			$n = 0;
			foreach($products as $k => $v){
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
				'message' => '产品列表返回成功！',
				'result' => $category
			);
			logger('PAD端---产品列表返回成功！');
		}else{
			$data = array(
				'code' => 0,
				'message' => '未查询到产品列表！'
			);
			logger('PAD端---未查询到产品列表！');
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
		logger("PAD端--查询产品内容");
		$post = I();
		logger('PAD端--携带参数：'.var_export($post,TRUE));
		$product = D('product');
		//获取列表
		$category = $this->lists(TRUE);
		if(empty($category)){
			$data = array(
				'code' => 0,
				'message' => '查询无内容！',
			);
		}else{
			if($post['id'] && !$post['cid']){ //查询某一具体产品
				$where = array(
					'id' => $post['id']
				);
				$result = $product->where($where)->field('id,name,price,preview,sample,remark,style')->find();
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
				$data = array(
					'code' => 1,
					'message' => '该产品返回成功！',
					'result' => $result
				);
			}elseif(!$post['id'] && $post['cid']){ //查询某一类产品
				foreach($category as $k => $v){
					if($v['id'] == $post['cid']){
						$where = array(
							'style' => $v['type']
						);
						break;
					}
				}
				$products = $product->where($where)->field('id,name,price,preview,sample,remark')->select();
				if(empty($products)){
					$data = array(
						'code' => 0,
						'message' => '该类产品无内容！',
					);
				}else{
					//处理图片路径
					foreach($products as $k => $v){
						if(trim($products[$k]['sample']) != '' && trim($products[$k]['sample']) != NULL){
							$imgs = explode(' ',trim($products[$k]['sample']));
						}else{
							$imgs = array();
						}
						$products[$k]['sample'] = json_encode($imgs);
						$products[$k]['type'] = $post['cid'];
					}
					$data = array(
						'code' => 1,
						'message' => '该类产品返回成功！',
						'result' => $products
					);
				}
			}elseif($post['id'] && $post['cid']){ //查询某一具体产品
				foreach($category as $k => $v){
					if($v['id'] == $post['cid']){
						$where = array(
							'style' => $v['type']
						);
						break;
					}
				}
				$where['id'] = $post['id'];
				$result = $product->where($where)->field('id,name,price,preview,sample,remark')->find();
				if($result){
					//处理图片路径
					if(trim($result['sample']) != '' && trim($result['sample']) != NULL){
						$imgs = explode(' ',trim($result['sample']));
					}else{
						$imgs = array();
					}
					$result['sample'] = json_encode($imgs);
					$result['type'] = $post['cid'];
					$data = array(
						'code' => 1,
						'message' => '该产品返回成功！',
						'result' => $result
					);
				}else{
					$data = array(
						'code' => 0,
						'message' => '无该产品！',
					);
				}
			}else{ //查询全部产品
				$where = array(
					'sid' => session('sid')
				);
				$products = $product->where($where)->field('id,name,price,preview,sample,remark,style')->select();
				if(empty($products)){
					$data = array(
						'code' => 0,
						'message' => '产品无内容！',
					);
				}else{
					//处理图片路径
					foreach($products as $k => $v){
						if(trim($products[$k]['sample']) != '' && trim($products[$k]['sample']) != NULL){
							$imgs = explode(' ',trim($products[$k]['sample']));
						}else{
							$imgs = array();
						}
						$products[$k]['sample'] = json_encode($imgs);
						foreach($category as $x => $y){
							if($y['type'] == $v['style']){
								$products[$k]['type'] = $y['id'];
								break;
							}
						}
						unset($products[$k]['style']);
					}
					$data = array(
						'code' => 1,
						'message' => '全部产品返回成功！',
						'result' => $products
					);
				}
			}
		}
		exit(json_encode($data));
	}
}
?>
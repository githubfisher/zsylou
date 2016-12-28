<?php
namespace Home\Controller;
use Think\Controller;
class MarketManagerController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//查询区域经理联系方式
	public function query(){
		logger('查询区域经理联系方式');
		$post = I();
		logger('携带参数:'.var_export($post,TRUE));
		$area = D('market_area');
		$manager = $area->join('store ON store.market_area = market_area.id AND store.id = '.session('sid'))->join('market_manager ON market_area.manager = market_manager.id')->field('market_manager.id,market_manager.name,market_manager.phone')->find();
		if($manager){
			logger('区域经理信息返回成功!');
			$data = array(
				'code' => 1,
				'message' => '区域经理信息返回成功',
				'result' => $manager
			);
		}else{
			logger('区域经理信息返回失败!');
			$data = array(
				'code' => 0,
				'message' => '区域经理信息返回失败'
			);
		}
		exit(json_encode($data));
	}
}
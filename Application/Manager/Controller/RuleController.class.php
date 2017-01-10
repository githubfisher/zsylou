<?php
namespace Manager\Controller;

use Think\Controller;

class RuleController extends Controller {
	public function _initialize()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function updateRule()
	{
		logger('附近客户 -- 更新查找设置...');
		$request = I();
		$area = $request['area'];
		$date = $request['date'];
		$status = false;
		if(isset($area)){
			$info['area'] = $area;
			$status = true;
		}
		if(isset($date)){
			$info['date'] = $date;
			$status = true;
		}
		if($status){
			$rules = D('location_search_rule');
			$where = array(
				'mid' => session('id')
			);
			$info['modify_at'] = time();
			if($rules->where($where)->field('id')->find()){
				if($rules->where($where)->save($info)){
					logger('附近客户 -- 更新查找设置，更新记录成功--更新成功！'."\n");
					$data = array(
						'code' => 1,
						'message' => '更新成功！'
					);
				}else{
					logger('附近客户 -- 更新查找设置，更新记录失败--更新失败！'."\n");
					$data = array(
						'code' => 0,
						'message' => '更新失败，请重试！'
					);
				}
			}else{
				$info['mid'] = session('id');
				if($rules->add($info)){
					logger('附近客户 -- 更新查找设置，添加记录成功--更新成功！'."\n");
					$data = array(
						'code' => 1,
						'message' => '更新成功！'
					);
				}else{
					logger('附近客户 -- 更新查找设置，添加记录失败--更新失败！'."\n");
					$data = array(
						'code' => 3,
						'message' => '更新失败，请重试！'
					);
				}
			}
		}else{
			logger("附近客户 -- 更新查找设置，参数不全--更新失败\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	public function myRule()
	{
		logger('附近客户 -- 查询查找设置...');
		$rules = D('location_search_rule');
		$where = array(
			'mid' => session('id')
		);
		$result = $rules->where($where)->field('date,area')->find();
		if($result){
			logger('附近客户 -- 已存在查找设置！'."\n");
			$data = array(
				'code' => 1,
				'message' => '返回成功！',
				'result' => $result
			);
		}else{
			logger('附近客户 -- 不存在查找设置，返回默认值！'."\n");
			$data = array(
				'code' => 1,
				'message' => '返回成功！',
				'result' => array(
					'date' => 0,
					'area' => 1000
				)
			);
		}
		exit(json_encode($data));
	}
}
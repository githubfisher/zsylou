<?php
namespace Home\Controller;

use Think\Controller;

class LocationController extends Controller {
	public function _initialize()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function myLocation()
	{
		$request = I();
		$location = $request['location'];
		if(isset($location)){
			$east = strchr($location,',',true);
			$north = ltrim(strchr($location,','),',');
			Vendor('Lvht.GeoHash');
			$geohash = new \Geohash();
			$location = D('location');
			$where = array(
				'uid' => session('uid')
			);
			if($location->where($where)->field('id')->find()){
				$info = array(
					'east' => $east,
					'north' => $north,
					'login_at' => time(),
					'hash' => $geohash->encode($east,$north)
				);
				if($location->where($where)->save($info)){
					logger('更新坐标成功！'."\n");
					$data = array(
						'code' => 1,
						'message' => '更新坐标成功！'
					);
				}else{
					logger('更新坐标失败，更新记录失败！'."\n");
					$data = array(
						'code' => 3,
						'message' => '更新位置信息失败！'
					);
				}
			}else{
				$info = array(
					'sid' => session('sid'),
					'uid' => session('uid'),
					'east' => $east,
					'north' => $north,
					'login_at' => time(),
					'hash' => $geohash->encode($east,$north)
				);
				if($location->add($info)){
					logger('插入新坐标成功！'."\n");
					$data = array(
						'code' => 1,
						'message' => '更新坐标成功！'
					);
				}else{
					logger('插入新坐标失败，更新记录失败！'."\n");
					$data = array(
						'code' => 0,
						'message' => '更新位置信息失败！'
					);
				}
			}
		}else{
			logger('更新坐标失败，参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '更新位置信息失败！'
			);
		}
		exit(json_encode($data));
	}
}
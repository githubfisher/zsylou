<?php
namespace Manager\Controller;
use Think\Controller;
class NearbyController extends Controller {
	protected $earth_radius = 6378.137;
    protected $pi = 3.1415926;
	protected $rule;
    public function _initialize(){
        $scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
    }
    public function find(){
    	logger("附近客户 -- 查找附近客户...");
        $request = I();
        $location = $request['location'];
        if(isset($location)){
        	$east = strchr($location,',',true);
			$north = ltrim(strchr($location,','),',');
        	$neighbor = D('Neighbor');
        	$neighbors = $neighbor->where($this->getCondition($east,$north,$request['area'],$request['date']))->order('login_at desc')->select();
        	// logger('查询语句：'.$neighbor->getLastsql()); //debug
        	if(count($neighbors) >= 1){
        		$neighbors = $this->measureDistance($east,$north,$neighbors);
        	}
        	$data = array(
        		'code' => 1,
				'message' => '附近客户返回成功！',
				'result' => $neighbors
        	);
        	logger("附近客户 -- 查找附近客户，返回成功!\n");
        }else{
        	logger("附近客户 -- 查找附近客户，参数不全--登录失败\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
        }
        exit(json_encode($data));
    }
    private function getCondition($east,$north,$area,$date){
    	$condition = array();
    	$condition['hash'] = array('like',$this->getNeighbor($east,$north,$area),'OR');
    	$condition['login_at'] = $this->getDate($date);
    	return $condition;
    }
    private function getDate($day)
    {
    	if($day >= 1){
    		return $this->getHistoryDate(true);
    	}else if((int)$day === 0){
    		return $this->getHistoryDate(false);
    	}else{
    		if(!isset($this->rule['area'])){
				$this->getSearchRule();
			}
			$date = $this->rule['date'];
			if($date == 0){
				return $this->getHistoryDate(false);
			}else{
				return $this->getHistoryDate(true);
			}
    	}
    }
    private function getHistoryDate($isToday=true,$days=7)
    {
        if($isToday){
            $now = time();
            $last = strtotime(date('Y-m-d',$now));
            return array(array('lt',$now),array('egt',$last));
        }else{
            $now = time();
            $last = strtotime(date('Y-m-d',$now-86400*$days));
            return array(array('lt',$now),array('egt',$last));
        }
    }
    private function getNeighbor($east,$north,$area)
    {
    	Vendor('Lvht.GeoHash');
		$geohash = new \Geohash();
		$hash = $geohash->encode($east,$north);
		if(empty($area)){
			if(!isset($this->rule['area'])){
				$this->getSearchRule();
			}
			$area = $this->rule['area'];
		}
		switch($area){
			case 200:
				$num = 7;
				break;
			case 1000:
				$num = 6;
				break;
			case 5000:
				$num = 5;
				break;
			default:
				$num = 6;
				break;
		}
		$prefix = substr($hash, 0, $num);
		$nearby = $geohash->neighbors($prefix);
		for($i=0;$i<8;$i++){
			$areas[$i] = substr($nearby[$i],0,$num).'%';
		}
		$areas[] = $prefix.'%';
		return $areas;
    }
    private function getSearchRule()
    {
    	$rules = D('location_search_rule');
    	$where = array(
    		'mid' => session('id') 
    	);
    	$rule = $rules->where($where)->field('area,date')->find();
    	if($rule){
    		$this->rule = $rule;
    	}else{
    		$this->rule = array(
    			'area' => 1000,
    			'date' => 0
    		);
    	}
    }
    private function measureDistance($east,$north,$neighbors)
    {
    	foreach($neighbors as $k => $v){
    		unset($neighbors[$k]['north']);
    		unset($neighbors[$k]['east']);
    		unset($neighbors[$k]['hash']);
    		$neighbors[$k]['distance'] = $this->getDistance($north,$east,$v['north'],$v['east'],2);
    		if(strpos($v['head'],'/Uploads/') === 0)
				$neighbors[$k]['head'] = C('base_url').$neighbors[$k]['head'];
    	}
    	return $neighbors;
    }

    private function getDistance($lat1, $lng1, $lat2, $lng2, $len_type = 1, $decimal = 2)
    {
        $radLat1 = $lat1 * pi()/ 180.0;   //PI()圆周率
        $radLat2 = $lat2 * pi() / 180.0;
        $a = $radLat1 - $radLat2;
        $b = ($lng1 * pi() / 180.0) - ($lng2 * pi() / 180.0);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $s = $s * $this->earth_radius;
        $s = round($s * 1000);
        if ($len_type === 1)
        {
            $s /= 1000;
        }
   		return round($s, $decimal);
	}
}
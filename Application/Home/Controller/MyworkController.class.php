<?php
namespace Home\Controller;
use Think\Controller;
class MyworkController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//我的工作 //http://119.29.6.140:8808/<aa>6</aa><yy>20150101080101888888</yy><date>2016-01-01</date><name>ls</name>
	public function mywork(){
		logger('!!!!!!!--我的工作-------------------------------------->开始--------->');
		$post = I();
		$date = $post['date'];
		if($date){
			//连接远程服务器 key钥匙
			$admin = array(
				'operation' => 6,
				'dogid' => session('dogid')
			);
			$xml = transXML($admin);
			$xml = strchr($xml,'<uu>',TRUE);
			$xml .= '<date>'.$date.'</date><name>'.session('admin_name').'</name>';
			// logger('查询xml:'.$xml." --->"); //debug
			//强制转码 由utf8转成gbk
			$xml = mb_convert_encoding($xml,'gbk','utf8');
			// logger('查询xml:'.$xml." --->"); //debug
			$url = session('url');
			$getxml = getXML($url,$xml);
			$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312'); 
			// logger('查询结果XML:'.$result." ---> 长度：".strlen($result)); //debug 
			if(strlen($result) < 39){
				logger('!!!!!!!--------------------------------------我的工作---- 无内容 ----->'."\n");
				$data = array(
					'code' => 0,
					'message' => '我的工作-无内容'
				);
				exit(json_encode($data));
			}else{
				//<?xml version='1.0'!><recipe><l0>我的拍照任务</l0><l0>我的选片任务</l0><l0>我的看设计任务</l0><l0>我的取件任务</l0><l0>我的修片任务</l0><l0>我的精修任务</l0><l0>我的设计任务</l0></recipe>
				//处理返回xml字符串
				$string = substr($result,33);
				// logger('string:'.$string); //debug
				$string = rtrim($string,'</recipe>');
				// logger('string:'.$string); //debug
				$arra = explode('<l0>',$string);
				// $stra = var_export($arra,TRUE); //debug
				logger('数组:'.$stra); //debug 
				// 处理我的工作数组	
				$work_array = $this->arr_work($arra);
				// logger('总数组:'.var_export($work_array,TRUE)); //debug
				$data = array(
					'code' => 1,
					'message' => '我的工作内容返回成功',
					'mywork' => $work_array
				);
				logger('!!!!!!!!------------------------------------我的工作----返回成功----->'."\n");
				exit(json_encode($data));
			}
		}else{
			$data = array(
				'code' => 2,
				'message' => '提交信息不全,请重新提交!'
			);
			exit(json_encode($data));
		}		
	}
		//摄控本返回信息   对比
	 // 0 => '1>客人：<i>ws/李四</i></l1',
	  // 1 => '2>爸爸电话：<i>18666532220</i></l2',
	  // 2 => '2>妈妈电话：<i></i></l2',
	  // 3 => '3>套系/价格：<i>1600婚纱照/1600</i></l3',
	  // 4 => '4>余款：<i>1100</i></l4',
	  // 5 => '5>景点：<i>系统默认景点</i></l5',
	  // 6 => '6>日期/时间：<i>2016-01-25 10:00</i></l6',
	  // 7 => '7>门市：<i>张三</i></l7',
	  // 8 => '8>摄影/助理：<i>管理员/</i></l8',
	  // 9 => '9>引导/助理：<i>张三/</i></l9',
	  // 10 => '1>客人：<i>zs/ls/王五</i></l1',
	  // 11 => '2>爸爸电话：<i>13656789865</i></l2',
	  // 12 => '2>妈妈电话：<i>18666532220</i></l2',
	  // 13 => '3>套系/价格：<i>3800婚纱照/3800</i></l3',
	  // 14 => '4>余款：<i>1900</i></l4',
	  // 15 => '5>景点：<i>系统默认景点</i></l5',
	  // 16 => '6>日期/时间：<i>2016-01-25 14:00</i></l6',
	  // 17 => '7>门市：<i>张三</i></l7',
	  // 18 => '8>摄影/助理：<i>管理员/ls</i></l8',
	  // 19 => '9>引导/助理：<i>张三/ls</i></l9',
	// 处理我的工作数组	
	public function arr_work($arr){
		$array = array();
		// logger('处理数组:'.var_export($arr,TRUE)); //debug
		$i = 0;
		foreach($arr as $k => $v){
			// logger('总switch:'); //debug
			switch($k){
				case 0:
					if(strlen($v) < 30){
						$shoot = array();
					}else{
						$str = substr($v,27);
						$fi_arr = explode('><l',$str);
						foreach($fi_arr as $key => $value){
							switch($key%10){
								case 0:
									$shoot[$i]['type'] = 0;
									$shoot[$i]['guest'] = ltrim(strchr(strchr($value,'</l',TRUE),'：'),'：'); //substr(strchr($value,'</l1>',TRUE),9);
									break;
								case 1:
									$i--;
									$shoot[$i]['paphone'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
									// if(session('vcip') == 1 || session('wtype') == 1){
				    	// 				$shoot[$i]['paphone'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
				    	// 			}else{
				    	// 				$shoot[$i]['paphone']  = '';
				    	// 			}
									break;
								case 2:
									$i--;
									$shoot[$i]['maphone'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
									// if(session('vcip') == 1 || session('wtype') == 1){
				    	// 				$shoot[$i]['maphone'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
				    	// 			}else{
				    	// 				$shoot[$i]['maphone']  = '';
				    	// 			}
									
									break;
								case 3:
									$i--;
									$ex_val = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
									$shoot[$i]['set_name'] = strchr($ex_val,'/',TRUE);
									$shoot[$i]['set_price'] = ltrim(strchr($ex_val,'/'),'/');
									break;
								case 4:
									$i--;
									$shoot[$i]['balance'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
									break;
								case 5:
									$i--;
									$shoot[$i]['vspot'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
									break;
								case 6:
									$i--;
									$shoot[$i]['time'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
									break;
								case 7:
									$i--;
									$shoot[$i]['store'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
									break;
								case 8:
									$i--;
									$ex_val = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
									$shoot[$i]['pger'] = strchr($ex_val,'/',TRUE);
									$shoot[$i]['pgassister'] = ltrim(strchr($ex_val,'/'),'/');
									break;
								case 9:
									$i--;
									$ex_val = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
									$shoot[$i]['guide'] = strchr($ex_val,'/',TRUE);
									$shoot[$i]['gassister'] = ltrim(strchr($ex_val,'/'),'/');
									break;
								default:
									break;
							}
							$i++;
						}
					}
					break;
				case 1:
					if(strlen($v) < 30){
						$seletp = array();
					}else{
						$type = 1; //选片
						$child_arr = $this->str_work($v);
						// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
						$seletp = $this->list_work_two($child_arr,$type);
					}
					break;
				case 2:
					if(strlen($v) < 30){
						$cdesign = array();
					}else{
						$type = 2; //看样
						$child_arr = $this->str_work($v);
						// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
						$cdesign= $this->list_work_two($child_arr,$type);
					}
					break;
				case 3:
					if(strlen($v) < 30){
						$pickup = array();
					}else{
						$type = 3; //取件
						$child_arr = $this->str_work($v);
						// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
						$pickup = $this->list_work_three($child_arr,$type);
					}
					break;
				case 4:
					if(strlen($v) < 30){
						$ps = array();
					}else{
						$type = 4; //修片
						$child_arr = $this->str_work($v);
						// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
						$ps = $this->list_work($child_arr,$type);
					}
					break;
				case 5:
					if(strlen($v) < 30){
						$exps = array();
					}else{
						$type = 5; //精修
						$child_arr = $this->str_work($v);
						// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
						$exps = $this->list_work($child_arr,$type);
					}
					break;
				case 6:
					if(strlen($v) < 30){
						$design = array();
					}else{
						$type = 6; //设计
						$child_arr = $this->str_work($v);
						// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
						$design = $this->list_work($child_arr,$type);
					}
					break;
				default:
					break;

			}
			$i++;
		}
		$array = array_merge_recursive($shoot,$seletp,$cdesign,$pickup,$ps,$exps,$design);
		// logger('结尾'); //debug
		return $array;
	}
	//拆分大数组成二级数组 ,清理标签字符
	public function str_work($string){
		// logger('二级数组'); //debug
		$str = substr($string,27);
		$ex_arr = explode('><l',$str);
		// logger('子数组:'.var_export($ex_arr,TRUE)); //debug
		$ex_array = array();
		foreach($ex_arr as $ki => $vi){
			$ex_array[$ki] = ltrim(strchr(strchr($vi,'<i>'),'</i>',TRUE),'<i>');
		}
		// logger('二级数组:'.var_export($array,TRUE)); //debug
		return $ex_array;
	}
	//处理id为4,5,6的数组
	public function list_work($list_arr,$type){
		$list_array = array();
		$i = 0;
		foreach($list_arr as $ke => $val){
			switch($ke%7){
				case 0:
					$list_array[$i]['type'] = $type;
					$list_array[$i]['guest'] = $val;
					break;
				case 1:
					$i--;
					$list_array[$i]['paphone'] = $val;
					// if(session('vcip') == 1 || session('wtype') == 1){
    	// 				$list_array[$i]['paphone'] = $val;
    	// 			}else{
    	// 				$list_array[$i]['paphone'] = '';
    	// 			}
					break;
				case 2:
					$i--;
					$list_array[$i]['maphone'] = $val;
					// if(session('vcip') == 1 || session('wtype') == 1){
    	// 				$list_array[$i]['maphone'] = $val;
    	// 			}else{
    	// 				$list_array[$i]['maphone'] = '';
    	// 			}
					break;
				case 3:
					$i--;
					$list_array[$i]['set_name'] = strchr($val,'/',TRUE);
					$list_array[$i]['set_price'] = ltrim(strchr($val,'/'),'/');
					break;
				case 4:
					$i--;
					$list_array[$i]['store'] = $val;
					break;
				case 5:
					$i--;
					$list_array[$i]['waitor'] = $val;
					break;
				case 6:
					$i--;
					$list_array[$i]['deadline'] = $val;
					break;
				default:
					break;
			}
			$i++;
		}
		return $list_array;
	}
	// id 为1,2,3的格式和其他的不一样,所以单写一遍, 注意是日期和服务人员的顺序
	public function list_work_two($list_arr,$type){
		$list_array = array();
		$i = 0;
		foreach($list_arr as $ke => $val){
			switch($ke%7){
				case 0:
					$list_array[$i]['type'] = $type;
					$list_array[$i]['guest'] = $val;
					break;
				case 1:
					$i--;
					$list_array[$i]['paphone'] = $val;
					// if(session('vcip') == 1 || session('wtype') == 1){
    	// 				$list_array[$i]['paphone'] = $val;
    	// 			}else{
    	// 				$list_array[$i]['paphone'] = '';
    	// 			}
					break;
				case 2:
					$i--;
					$list_array[$i]['maphone'] = $val;
					// if(session('vcip') == 1 || session('wtype') == 1){
    	// 				$list_array[$i]['maphone'] = $val;
    	// 			}else{
    	// 				$list_array[$i]['maphone'] = '';
    	// 			}
					break;
				case 3:
					$i--;
					$list_array[$i]['set_name'] = strchr($val,'/',TRUE);
					$list_array[$i]['set_price'] = ltrim(strchr($val,'/'),'/');
					break;
				case 4:
					$i--;
					$list_array[$i]['store'] = $val;
					break;
				case 5:
					$i--;
					$list_array[$i]['time'] = $val;
					break;
				case 6:
					$i--;
					$list_array[$i]['waitor'] = $val;
					break;
				default:
					break;
			}
			$i++;
		}
		return $list_array;
	}
	// id 为3的格式和其他的不一样 多了 和list_work比多了isok
	public function list_work_three($list_arr,$type){
		$list_array = array();
		$i = 0;
		foreach($list_arr as $ke => $val){
			switch($ke%8){
				case 0:
					$list_array[$i]['type'] = $type;
					$list_array[$i]['guest'] = $val;
					break;
				case 1:
					$i--;
					$list_array[$i]['paphone'] = $val;
					// if(session('vcip') == 1 || session('wtype') == 1){
    	// 				$list_array[$i]['paphone'] = $val;
    	// 			}else{
    	// 				$list_array[$i]['paphone'] = '';
    	// 			}
					break;
				case 2:
					$i--;
					$list_array[$i]['maphone'] = $val;
					// if(session('vcip') == 1 || session('wtype') == 1){
    	// 				$list_array[$i]['maphone'] = $val;
    	// 			}else{
    	// 				$list_array[$i]['maphone'] = '';
    	// 			}
					break;
				case 3:
					$i--;
					$list_array[$i]['set_name'] = strchr($val,'/',TRUE);
					$list_array[$i]['set_price'] = ltrim(strchr($val,'/'),'/');
					break;
				case 4:
					$i--;
					$list_array[$i]['store'] = $val;
					break;
				case 5:
					$i--;
					$list_array[$i]['time'] = $val;
					break;
				case 6:
					$i--;
					$list_array[$i]['waitor'] = $val;
					break;
				case 7:
					$i--;
					$list_array[$i]['isok'] = $val;
				default:
					break;
			}
			$i++;
		}
		return $list_array;
	}
}
?>
<?php
namespace Home\Controller;
use Think\Controller;
class MyworkController extends Controller{
	public function _initialize(){
		logger('initialize');
		if(session('uid') && session('suid') && session('sid')){
			logger('存在SESSION,判断用户合法性');
			$post = I();
			$userid = $post['uid']; 
			$uid = session('uid');
			// logger('SESSION_UID:'.$uid); //debug
			if($uid != $userid){
				$data = array(
					'code' => '5',
					'message' => '账户未登录'
				);
				logger("APP用户未登录\n");
				exit(json_encode($data));
			}
		}else{
			logger('SESSION不存在,重新连接服务器');
			$post = I();
			$userid = $post['uid'];
			if($userid == '' || $userid == NULL){
				$data = array(
				'code' => '6',
				'message' => '非法用户请求'
				);
				logger("非法APP用户请求，已驳回！\n");
				exit(json_encode($data));
			}else{
				$app_user = D('app_user');
				$where = array(
					'uid' => $userid
				);
				$user = $app_user->where($where)->find();
				if($user){
					session('uid',$userid); //APP用户uid写入session
					session('appuser',$user['username']);
					logger('APP用户校验成功');
					//登录影楼服务器
					// 查询对应服务器地址
					$store = D('store');
					$where = array(
						'id' => $user['sid']
					);
					$instore = $store->where($where)->find();
					session('sid',$instore['id']); //影楼id 写入session
					session('dogid',$instore['dogid']);
					if($instore){
						$url = 'http://' . $instore['ip'] . ':' . $instore['port'] . '/';
						session('url',$url); //将对应远程影楼服务器地址，保存至session
						// 查找对应的影楼员工id
						$worker = D('store_admin');
						$where = array(
							'id' =>$user['suid'],
							'sid' => $instore['id']
						);
						$workman = $worker->where($where)->find();
						session('suid',$workman['id']);
						session('wtype',$workman['type']); //将员工的角色类型写入SESSION
						session('wuser',$wordman['username']);
						logger('写入session/suid:'.session('suid').'/'.session('wtype').'/'.session('sid'));
						$workman['operation'] = 2;
						$workman['dogid'] = $instore['dogid'];
						$xml = transXML($workman);
						$xml .= '<pp>'.$workman['password'].'</pp>';
						logger('重新登录远程影楼服务器');
						$getxml = getXML($url,$xml);
						//将获取的XML数据转换为对象
			        	$obj = simplexml_load_string($getxml,'SimpleXMLElement',LIBXML_NOCDATA);
			        	if($obj->r == 'loginfailed'){
			        		logger("远程影楼服务器--登录失败\n");
				        	// $arr = array(
				        	// 	'code' => '3',
				        	// 	'message' => '远程影楼服务器登录失败！',
				        	// 	'uid' => $userid
				        	// );
				        	// exit(json_encode($arr));
				        }else{
				        	logger("ALL-登录成功");
				        	// $arr = array(
				        	// 	'code' => '1',
				        	// 	'message' => '登录成功！',
				        	// 	'uid' => $userid
				        	// );
				        	// exit(json_encode($arr));
				        }
					}else{
						logger("未查找到对应远程影楼服务器\n");
						// $arr = array(
			   //      		'code' => '4',
			   //      		'message' => '未查找到对应远程服务器!',
			   //      		'uid' => $userid
			   //      	);
			   //      	exit(json_encode($arr));
					}
				}else{
					logger("无此用户\n");
					$arr = array(
						'code' => 7,
						'message' => '无此用户'
					);
					exit(json_encode($arr));
				}
			}
		}
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
			// logger('查询xml:'.$xml."--->"); //debug
			$url = session('url');
			$getxml = getXML($url,$xml);
			$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');  
			if(strlen($result) < 39){
				logger('!!!!!!!--------------------------------------我的工作---- 无内容 ----->'."\n");
				$data = array(
					'code' => 0,
					'message' => '我的工作-无内容'
				);
				exit(json_encode($data));
			}else{
				//处理返回xml字符串
				$string = substr($result,33);
				// logger('string:'.$string); //debug
				$string = rtrim($string,'</recipe>');
				// logger('string:'.$string); //debug
				$arra = explode('<l0>',$string);
				// $stra = var_export($arra,TRUE); //debug
				// logger('数组:'.$stra); //debug 
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
	// 处理我的工作数组	
	public function arr_work($arr){
		$array = array();
		// logger('处理数组:'.var_export($arr,TRUE)); //debug
		foreach($arr as $k => $v){
			// logger('总switch:'); //debug
			switch($k){
				case 0:
					$str = substr($v,27);
					$fi_arr = explode('><l',$str);
					foreach($fi_arr as $key => $value){
						switch($key){
							case 0:
								$array['shoot']['guest'] = substr(strchr($value,'</l1>',TRUE),9);
								break;
							case 1:
								$array['shoot']['paphone'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
								break;
							case 2:
								$array['shoot']['maphone'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
								break;
							case 3:
								$ex_val = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
								$array['shoot']['set_name'] = strchr($ex_val,'/',TRUE);
								$array['shoot']['set_price'] = ltrim(strchr($ex_val,'/'),'/');
								break;
							case 4:
								$array['shoot']['balance'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
								break;
							case 5:
								$array['shoot']['vspot'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
								break;
							case 6:
								$array['shoot']['date'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
								break;
							case 7:
								$array['shoot']['store'] = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
								break;
							case 8:
								$ex_val = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
								$array['shoot']['pger'] = strchr($ex_val,'/',TRUE);
								$array['shoot']['pgassister'] = ltrim(strchr($ex_val,'/'),'/');
								break;
							case 9:
								$ex_val = ltrim(strchr(strchr($value,'<i>'),'</i>',TRUE),'<i>');
								$array['shoot']['guide'] = strchr($ex_val,'/',TRUE);
								$array['shoot']['gassister'] = ltrim(strchr($ex_val,'/'),'/');
								break;
							default:
								break;
						}
					}
					break;
				case 1:
					$child_arr = $this->str_work($v);
					// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
					$array['seletp'] = $this->list_work_two($child_arr);
					break;
				case 2:
					$child_arr = $this->str_work($v);
					// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
					$array['cdesign'] = $this->list_work_two($child_arr);
					break;
				case 3:
					$child_arr = $this->str_work($v);
					// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
					$array['pickup'] = $this->list_work_two($child_arr);
					break;
				case 4:
					$child_arr = $this->str_work($v);
					// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
					$array['ps'] = $this->list_work($child_arr);
					break;
				case 5:
					$child_arr = $this->str_work($v);
					// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
					$array['exps'] = $this->list_work($child_arr);
					break;
				case 6:
					$child_arr = $this->str_work($v);
					// logger('2222子数组:'.var_export($child_arr,TRUE)); //debug
					$array['design'] = $this->list_work($child_arr);
					break;
				default:
					break;

			}
		}
		logger('结尾');
		return $array;
	}
	//拆分大数组成二级数组 ,清理标签字符
	public function str_work($string){
		logger('二级数组');
		$str = substr($string,27);
		$ex_arr = explode('><l',$str);
		logger('子数组:'.var_export($ex_arr,TRUE));
		$ex_array = array();
		foreach($ex_arr as $ki => $vi){
			$ex_array[$ki] = ltrim(strchr(strchr($vi,'<i>'),'</i>',TRUE),'<i>');
		}
		// logger('二级数组:'.var_export($array,TRUE)); //debug
		return $ex_array;
	}
	//处理id为4,5,6的数组
	public function list_work($list_arr){
		$list_array = array();
		foreach($list_arr as $ke => $val){
			switch($ke){
				case 0:
					$list_array['guest'] = $val;
					break;
				case 1:
					$list_array['paphone'] = $val;
					break;
				case 2:
					$list_array['maphone'] = $val;
					break;
				case 3:
					$list_array['set_name'] = strchr($val,'/',TRUE);
					$list_array['set_price'] = ltrim(strchr($val,'/'),'/');
					break;
				case 4:
					$list_array['store'] = $val;
					break;
				case 5:
					$list_array['waitor'] = $val;
					break;
				case 6:
					$list_array['deadline'] = $val;
					break;
				default:
					break;
			}
		}
		return $list_array;
	}
	// id 为1,2,3的格式和其他的不一样,所以单写一遍, 注意是日期和服务人员的顺序
	public function list_work_two($list_arr){
		$list_array = array();
		foreach($list_arr as $ke => $val){
			switch($ke){
				case 0:
					$list_array['guest'] = $val;
					break;
				case 1:
					$list_array['paphone'] = $val;
					break;
				case 2:
					$list_array['maphone'] = $val;
					break;
				case 3:
					$list_array['set_name'] = strchr($val,'/',TRUE);
					$list_array['set_price'] = ltrim(strchr($val,'/'),'/');
					break;
				case 4:
					$list_array['store'] = $val;
					break;
				case 5:
					$list_array['date'] = $val;
					break;
				case 6:
					$list_array['waitor'] = $val;
					break;
				case 7:
					$list_array['isok'] = $val;
				default:
					break;
			}
		}
		return $list_array;
	}
}
?>
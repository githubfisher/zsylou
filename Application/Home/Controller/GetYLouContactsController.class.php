<?php
namespace Home\Controller;
use Think\Controller;
class GetYLouContactsController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
		Vendor('Easemob.Easemob');
	}
	//预留
	public function index(){

	}
	//影楼员工通讯录查询、更新函数;系统主动查询，并更新影楼之家通讯录内容。
	public function query_update(){
		logger('&&&&&&&&&&&&&&&&&&&&&&&&&&>>> 查询远程影楼员工通讯录列表 ----开始---- <<<&&&&&&&&&&&&&&&&&&&&&&&&&&');
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 9,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin);
		$xml = strchr($xml,'<uu>',TRUE);
		//强制转码 由utf8转成gbk
		$xml = mb_convert_encoding($xml,'gbk','utf8');
		// logger('查询xml:'.$xml."--->"); //debug
		$url = session('url');
		$getxml = getXML($url,$xml);
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
		// logger('XML:'.$result);//debug 
		if(strlen($result) < 38){
    		logger("该影楼无联系人信息");
        	logger("&&&&&&&&&&&&&&&&&&&&&&&&&&>>> 该影楼“无”联系人信息 ----完毕---- <<<&&&&&&&&&&&&&&&&&&&&&&&&&&\n");
        }else{
        	logger("处理该影楼联系人信息-->开始");
        	$str_xml = substr(rtrim($result,'></recipe>'),32);
        	// logger('截取xml:'.$str_xml."\n"); //debug
        	$tra_arr = explode('><l>',$str_xml);
        	// $tra_arr_str = var_export($tra_arr,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str."\n"); //debug
        	$tra_arr2 = $this->contact_arr2($tra_arr);
        	// $tra_arr_str2 = var_export($tra_arr2,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str2."\n"); //debug
        	logger("处理该影楼联系人信息-->更新或增加");
        	$app_user = D('app_user');
        	// 第一步先查询是否已存在该店铺的联系人信息，如果存在则更新，如果不存在则直接导
        	$where = array(
        		'sid' => session('sid')
        	);
        	$num_app_user= $app_user->where($where)->count();
        	if($num_app_user == 1){
        		logger('只存在管理员联系人信息！');
        		//若当前只有管理员一人，则添加全部联系人到app_user表中
        		$result = $app_user->addAll($tra_arr2);
        		if($result){
        			logger('员工联系人添加成功！');
        		}else{
        			logger('员工联系人添加失败！');
        		}
        	}else{ //循环判断是否有变化，如果有则更新。无变化则丢弃! 废弃该思路，删除原有的员工联系人（除管理员外），将新联系人新增到员工联系人中
        		//如果已有员工账户存在，则全部删除，重新导入。
        		logger('已存在员工联系人信息！');
        		$delete_where = array(
        			'sid' => session('sid'),
        			'type' => 0
        		);
        		$result = $app_user->where($delete_where)->delete();
        		if($result){
        			logger('删除员工联系人添加成功！之后重新导入-->DELETE--->OK-->');
        			$add_result = $app_user->addAll($tra_arr2);
	        		if($add_result){
	        			logger('员工联系人添加成功！');
	        		}else{
	        			logger('员工联系人添加失败！');
	        		}
        		}else{
        			logger('删除员工联系人添加失败！暂停导入！-->DELETE--->FAILED-->');
        		}
        	}
        	logger("&&&&&&&&&&&&&&&&&&&&&&&&&&>>> 查询影楼联系人信息 --处理--成功---- <<<&&&&&&&&&&&&&&&&&&&&&&&&&&\n");
        }
	}
	// 原处理联系人数组函数1 
	public function contact_arr($arr){
		  // 0 => '姓名：<i>邓威 / 男 / 摄影部</i></l',
		//   1 => '电话：<i>13555556666</i></l',
		//   2 => 'QQ：<i>123456</i></l',
		//   3 => '地址：<i>2</i></l',
		//   4 => '姓名：<i>张三 / 女 / 门市部</i></l',
		//   5 => '电话：<i>13512345678</i></l',
		//   6 => 'QQ：<i>223344</i></l',
		//   7 => '地址：<i>1</i></l',
		$array = array();
		$i = 0;
		foreach($arr as $k => $v){
			switch($k%4){
				case 0:
					$str_arr = explode(' / ',substr(rtrim($v,'</i></l'),12));
					foreach($str_arr as $key => $val){
						switch($key){
							case 0:
								$array[$i]['realname'] = $val; 
								break;
							case 1:
								$array[$i]['gender'] = $val;
								break;
							case 2:
								$array[$i]['dept'] = $val;
								break;
							default:
								break;
						}
					}
					break;
				case 1:
					$i--;
					$array[$i]['mobile'] = substr(rtrim($v,'</i></l'),12);
					break;
				case 2:
					$i--;
					$array[$i]['qq'] = substr(rtrim($v,'</i></l'),8);
					break;
				case 3:
					$i--;
					$array[$i]['location'] = substr(rtrim($v,'</i></l'),12);
				default:
					break;
			}
			$i++;
		}
		return $array;
	}
	//现处理联系人数组函数2 
	  // 0 => '姓名：<i>刘琼花 / 女 / 游泳馆</i></l',
	  // 1 => '电话：<i>15188039936</i></l',
	  // 2 => 'QQ：<i>云南省曲靖市麒麟区茨营乡红石岩村17号</i></l',
	  // 3 => '地址：<i>云南曲靖</i></l',
	  // 4 => '账号：<i></i></l',
	  // 5 => '姓名：<i>喻泽 / 男 / 摄影部</i></l',
	  // 6 => '电话：<i>13529853308</i></l',
	  // 7 => 'QQ：<i>云南曲靖市罗平</i></l',
	  // 8 => '地址：<i>曲靖</i></l',
	  // 9 => '账号：<i>yz</i></l',
	  // 10 => '姓名：<i>小杰 / 男 / 摄影部</i></l',
	  // 11 => '电话：<i>15188070419</i></l',
	  // 12 => 'QQ：<i>云南省曲靖市</i></l',
	public function contact_arr2($arr){
		$array = array();
		$i = 0;
		foreach($arr as $k => $v){
			switch($k%5){
				case 0:
					$str_arr = explode(' / ',substr(rtrim($v,'</i></l'),12));
					foreach($str_arr as $key => $val){
						switch($key){
							case 0:
								$array[$i]['nickname'] = $val; //将联系人的真实姓名，填写到app_user表中的昵称中
								$array[$i]['realname'] = $val; //增加真实姓名，因为开单需要填写pc端的姓名信息，而手机端可以随意修改，所以必须把nickname和realname分开保存
								// logger($val); //debug
								break;
							case 1:
								if($val == '男'){
									$array[$i]['gender'] = 1;
								}else{
									$array[$i]['gender'] = 2;
								}
								// logger($val); //debug
								break;
							case 2:
								$array[$i]['dept'] = $val;
								// logger($val); //debug
								break;
							default:
								break;
						}
					}
					break;
				case 1:
					$i--;
					$array[$i]['mobile'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
					// logger($array[$i]['phone']); //debug
					break;
				case 2:
					$i--;
					$array[$i]['qq'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
					// logger($array[$i]['qq']); //debug
					break;
				case 3:
					$i--;
					$array[$i]['location'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
					// logger($array[$i]['location']); //debug
					break;
				case 4:
					$i--;
					//添加 店铺标识 SID
					$array[$i]['sid'] = session('sid');
					$array[$i]['username'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
					//添加 初始password
					$array[$i]['password'] = '888888';
					//添加时间
					$array[$i]['createtime'] = time();
					//添加店铺标识
					$array[$i]['store_simple_name'] = session('store_simple_name');
					// logger($array[$i]['username']); //debug
					break;
				default:
					break;
			}
			$i++;
		}
		return $array;
	}
	// 注册环信方面用户
	public function create_easemob_user(){
		$app_user = D('app_user');
		$where = array(
			'sid' => session('sid')
		);
		$result = $app_user->where($where)->select();
		//循环注册 ，即便只有管理员一人也要注册
		foreach($result as $k => $v){
			if($v['username'] != '' && $v['username'] != NULL){
				$user = $v['store_simple_name'].'_'.$v['username'];
				$pwd = $v['password'];
				$create_result = easemob_create_user($user,$pwd);
				if($create_result['error'] != ''){
					logger('用户：'.$user.'创建失败，失败原因------>'.$create_result['error']);
				}else{
					logger('用户：'.$user.'创建环信用户成功！'."\n");
				}
			}
		}
		
	}
	// 删除环信用户
	public function delete_easemob_user(){
		$app_user = D('app_user');
		$where = array(
			'sid' => session('sid')
		);
		$result = $app_user->where($where)->select();
		//循环删除注册用户
		foreach($result as $k => $v){
			if($v['username'] != '' && $v['username'] != NULL){
				$user .= $v['store_simple_name'].'_'.$v['username'];
				$delete_result = delete_easemob_user($user);
				if($create_result['error'] != ''){
					logger('用户：'.$v['username'].'删除失败，失败原因------>'.$create_result['error']);
				}else{
					logger($user.'删除环信用户成功！'."\n");
				}
			}
		}
		return ture;
	}
}
?>
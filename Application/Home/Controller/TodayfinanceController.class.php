<?php
namespace Home\Controller;
use Think\Controller;
class TodayfinanceController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function index(){

	}
	public function query(){
		logger('===============================>>> 查询今日财务信息 ----开始---- <<<====================================');
		$post = I();
		if(session('wtype') == 1){
			//连接远程服务器 key钥匙
			$admin = array(
				'operation' => 3,
				'username' => 'admin', //session('wuser') //其他用户拥有了管理员权限后,却不能获得财务信息
				'dogid' => session('dogid')
			);
			$xml = transXML($admin);
			//如果未指定则自动查询今日的订单
			if($post['date'] == '' || $post['date'] == NULL){
				$xml .= '<dd>'.date('Y-m-d',time()).'</dd>';
			}else{
				$xml .= '<dd>'.$post['date'].'</dd>';
			}
			//强制转码 由utf8转成gbk
			$xml = mb_convert_encoding($xml,'gbk','utf8');
			// logger('查询xml:'.$xml."--->");//debug
			$url = session('url');
			$getxml = getXML($url,$xml);
			$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
			// logger('XML:'.$result."\n");//debug
			// 判断返回xml结果
			//如果xml总长度不超过40，则返回为空
			if(strlen($result) < 53){
				$data = array(
					'code' => 0,
					'message' => '未查找到相关订单信息！'
				);
	    		logger("===============================>>> “未”查找到相关财务信息 ----完毕---- <<<====================================\n");
	    		exit(json_encode($data));
			}else{
				$str_xml = substr($result,45);
				// logger('第一次截取：'.$str_xml."\n"); //debug
				$str2_xml = rtrim($str_xml,'></recipe>');
				// logger('第二次截取：'.$str2_xml."\n"); //debug
				if(strstr($str2_xml,'<l1>')){
					//存在财务明细的情况
					logger('存在财务明细');
					$str3_xml = strchr($str2_xml,'<l1>'); //订单详情的内容
					$arr_xq = explode('><l',ltrim($str3_xml,'<l'));
					// logger('详情数据截取数组：'.var_export($arr_xq,TRUE)); //debug
					// $de_arr = $this->details_arr2($arr_xq); //启用新的数组处理函数 2016-05-31
					$de_arr = $this->details_arr3($arr_xq); //启用新的数组处理函数 2016-11-08
					// logger('财务详情数据数组：'.var_export($de_arr,TRUE)); //debug
					$str4_xml = rtrim(strchr($str2_xml,'<l1>',TRUE),'></br>'); //订单统计的内容
					$arr_xml = explode('></br><n',$str4_xml);
					$fi_arr = $this->finance_arr($arr_xml);
					// logger('财务统计数据数组：'.var_export($fi_arr,TRUE)); //debug
				}else{
					//不存在财务明细的情况
					logger('不存在财务明细');
					//1>收入：<i>0</i></n1></br><n2>定金：<i>0</i></n2></br><n3>补款：<i>0</i></n3></br><n4>二销：<i>0</i></n4></br><n5>其它：<i>0</i></n5></br><n6>支出：<i>0</i></n6></br><n7>净收：<i>0</i></n7></br><n8>定单：<i>0</i></n8></b
					$de_arr = array();
					$str4_xml = strchr($str2_xml,'</n8>',TRUE).'</n8'; //订单统计的内容
					$arr_xml = explode('></br><n',$str4_xml);
					$fi_arr = $this->finance_arr($arr_xml);
					// logger('财务统计数据数组：'.var_export($fi_arr,TRUE)); //debug
				}
/*  弃用该处理方式，因为有未返回财务明细的情况
				$str3_xml = strchr($str2_xml,'<l1>'); //订单详情的内容
				// logger('拆分--订单详情部分：'.$str3_xml."\n"); //debug
				$str4_xml = rtrim(strchr($str2_xml,'<l1>',TRUE),'></br>');
				$arr_xq = explode('><l',ltrim($str3_xml,'<l'));
				//logger("数组：".var_export($arr_xp,TRUE)); //debug
				$de_arr = $this->details_arr($arr_xq);
				// $de_arr_xq = var_export($de_arr,TRUE);//debug
				// logger('解析详情数组'.$de_arr_xq."\n");//debug
				// $arr_xq_str = var_export($arr_xq,TRUE);//debug
				// logger('拆分数组-详情:'.$arr_xq_str."\n");//debug
				// logger('拆分--收款部分：'.$str4_xml."\n"); //debug
				$arr_xml = explode('></br><n',$str4_xml);
				// $string = var_export($arr_xml,TRUE); //debug
				// logger('拆分数组：'.$string."\n"); //debug
				$fi_arr = $this->finance_arr($arr_xml);
				// $arr2_xml = var_export($fi_arr,TRUE); //debug
				// logger('财务数组：'.$arr2_xml."\n");//debug
*/
				logger('查询今日财务完成'."\n");
				$data = array(
					'code' => 1,
					'message' => '今日财务信息返回成功',
					'finance' => $fi_arr,
					'details' => $de_arr
				);
				logger("===============================>>> 查询今日财务信息 ----成功---- <<<====================================\n");
				exit(json_encode($data));
			}
			
		}else{
			//logger('员工无权限查看今日财务');
			$data = array(
				'code' => 2,
				'message' => '员工无权限'
			);
			logger("===============================>>> 员工无权限 ----完毕---- <<<====================================\n");
			exit(json_encode($data));
		}
	}
	//处理财务数据数组
	// <?xml version='1.0'！>
	// 	<recipe>
	// 		<r>loginok</r>
	// 		<n1>收入：<i>3600</i></n1></br>
	// 		<n2>定金：<i>600</i></n2></br>
	// 		<n3>补款：<i>1000</i></n3></br>
	// 		<n4>二销：<i>2000</i></n4></br>
	// 		<n5>其它：<i>0</i></n5></br>
	// 		<n6>支出：<i>100</i></n6></br>
	// 		<n7>净收：<i>3500</i></n7></br>
	// 		<n8>定单：<i>2</i></n8></br>
	// 		<l1>摄影二销：20160427-013;邓威;客户:陈婷</l1>
	// 		<l1>2000</l1>收款人：<l1>管理员</l1>
	// 		<l1>2016-01-23 15:56:11</l1>
	// 		<l2>预约补款：20160427-013;张三;客户:陈婷</l2>
	// 		<l2>1000</l2>收款人：
	// 		<l2>管理员</l2>
	// 		<l2>2016-01-23 15:55:46</l2>
	// 		<l3>预约收款：20160427-013;张三;客户:陈婷</l3>
	// 		<l3>500</l3>收款人：
	// 		<l3>管理员</l3>
	// 		<l3>2016-01-23 15:55:31</l3>
	// 		<l4>预约收款：20100511-001;张三;客户:张三</l4>
	// 		<l4>100</l4>收款人：
	// 		<l4>管理员</l4>
	// 		<l4>2016-01-23 14:42:00</l4>
	// 		<l5>固定费用：生活费;张三;</l5>
	// 		<l5>100</l5>收款人：
	// 		<l5>管理员</l5>
	// 		<l5>2016-01-23 14:42:20</l5>
	// 	</recipe>
	
	// 修改处理函数 2016-05-27
			//<?xml version='1.0'！>
				// <recipe>
				// 	<r>loginok</r>
				// 		<n1>收入：<i>0</i></n1></br>
				// 		<n2>定金：<i>0</i></n2></br>
				// 		<n3>补款：<i>0</i></n3></br>
				// 		<n4>二销：<i>0</i></n4></br>
				// 		<n5>其它：<i>0</i></n5></br>
				// 		<n6>支出：<i>0</i></n6></br>
				// 		<n7>净收：<i>0</i></n7></br>
				// 		<n8>定单：<i>0</i></n8></br>
				// </recipe>
	public function finance_arr($arr){   //1>收入：<i>1100</i></n1
		$array = array();
		foreach($arr as $k => $v){
			switch($k){
				case 0:
					$array['income'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>'); //substr(rtrim($v,'</i></n1'),14);
					break;
				case 1:
					$array['deposit'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>'); //substr(rtrim($v,'</i></n2'),14);
					break;
				case 2:
					$array['extra'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>'); //substr(rtrim($v,'</i></n3'),14);
					break;
				case 3:
					$array['tsell'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>'); //substr(rtrim($v,'</i></n4'),14);
					break;
				case 4:
					$array['other'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>'); //substr(rtrim($v,'</i></n5'),14);
					break;
				case 5:
					$array['expend'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>'); //substr(rtrim($v,'</i></n6'),14);
					break;
				case 6:
					$array['netin'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>'); //substr(rtrim($v,'</i></n7'),14);
					break;
				case 7:
					$array['trade'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>'); //substr(rtrim($v,'</i></n8'),14);
					break;
				default:
					break;
			}
		}
		return $array;
	}
	//处理订单详情数组
	public function details_arr($arr){
		$array = array();
		foreach($arr as $k => $v){
			$i = substr($v,0,1)-1;
			switch($k%3){
				case 0:
					$array[$i]['info'] = substr(strchr($v,'</l',TRUE),2);
					break;
				case 1:
					$array[$i]['money'] = substr(strchr($v,'</l',TRUE),2);
					$array[$i]['payee'] = substr(strchr(strchr($v,'<l'),'</l',TRUE),4);
					break;
				case 2:
					$array[$i]['time'] = substr(strchr($v,'</l',TRUE),2);
					break;
				default:
					break;	
			}
		}
		return $array;
	}
    // 重写处理订单详情数组函数 2016-05-31
    public function details_arr2($arr){
    	$array = array();
    	foreach($arr as $k => $v){
			$i = strchr($v,'>',TRUE)-1;
			switch($k%3){
				case 0:
					$array[$i]['info'] = ltrim(strchr(strchr($v,'</l',TRUE),'>'),'>'); //substr(strchr($v,'</l',TRUE),2);
					break;
				case 1:
					$array[$i]['money'] = ltrim(strchr(strchr($v,'</l',TRUE),'>'),'>'); //substr(strchr($v,'</l',TRUE),2);
					if($i>8){
						$array[$i]['payee'] = substr(strchr(strchr($v,'<l'),'</l',TRUE),5);
					}else{
						$array[$i]['payee'] = substr(strchr(strchr($v,'<l'),'</l',TRUE),4);
					}
					break;
				case 2:
					$array[$i]['time'] = ltrim(strchr(strchr($v,'</l',TRUE),'>'),'>');
					break;
				default:
					break;	
			}
		}
		return $array;
    }
    // 重写处理订单详情数组函数 2016-11-08
    public function details_arr3($arr){
    	$array = array();
    	foreach($arr as $k => $v){
			$i = strchr($v,'>',TRUE)-1;
			switch($k%3){
				case 0:
					$info = ltrim(strchr(strchr($v,'</l',TRUE),'>'),'>'); //substr(strchr($v,'</l',TRUE),2);
					$info_ary = explode(';',$info);
					// logger('信息拆分：'.var_export($info_ary,true)); // debug
					$array[$i]['type'] = strchr($info_ary[0],'：',true); //  收款类型
					$array[$i]['order'] = ltrim(strchr($info_ary[0],'：'),'：'); // 订单号
					$array[$i]['drawer'] = $info_ary[1]; // 开单人
					$array[$i]['customer'] = ltrim(strchr($info_ary[2],':'),':'); //客户
					break;
				case 1:
					$array[$i]['money'] = ltrim(strchr(strchr($v,'</l',TRUE),'>'),'>'); //substr(strchr($v,'</l',TRUE),2);
					if($i>8){
						$array[$i]['payee'] = substr(strchr(strchr($v,'<l'),'</l',TRUE),5);
					}else{
						$array[$i]['payee'] = substr(strchr(strchr($v,'<l'),'</l',TRUE),4);
					}
					break;
				case 2:
					$array[$i]['time'] = ltrim(strchr(strchr($v,'</l',TRUE),'>'),'>');
					break;
				default:
					break;	
			}
		}
		$newary = array();
		$m = 0;
		foreach($array as $k => $v){
			if($k == 0){
				$newary[$m]['type'] = $v['type'];
				$newary[$m]['list'][] = $v;
			}else{
				$max = count($newary);
				$n = 1;
				foreach($newary as $x => $y){
					if($v['type'] == $y['type']){
						$newary[$x]['list'][] = $v;
						break;
					}else{
						if($n == $max){
							$m++;
							$newary[$m]['type'] = $v['type'];
							$newary[$m]['list'][] = $v; 
						}
					}
					$n++;
				}
			}
		}
		// 补全分类
		// $type = array('预约收款','预约补款','摄影二销','化妆二销','选片二销','其它收入');
		// $max = count($newary);
		// foreach($type as $x => $y){
		// 	$n = 1;
		// 	foreach($newary as $k => $v){
		// 		if($v['type'] != $y){
		// 			if($max == $n){
		// 				$newary[$max]['type'] = $y;
		// 				$newary[$max]['list'] = array();
		// 				$max++;
		// 			}	
		// 			$n++;
		// 		}else{
		// 			break;
		// 		}
		// 	}
		// }
		return $newary;
    }
}	
?>
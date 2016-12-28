<?php
namespace Home\Controller;
use Think\Controller;
class MonthfinanceController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function index(){

	}
	public function query(){
		logger('===============================>>> 查询财务统计信息（月） ----开始---- <<<====================================');
		$post = I();
		if(session('wtype') == 1){
			//连接远程服务器 key钥匙
			$admin = array(
				'operation' => 10,
				'username' => 'admin', //session('wuser')  //拥有管理员权限的用户,用户名不是admin不能查询财务信息
				'dogid' => session('dogid')
			);
			$xml = transXML($admin);
			//如果未指定则自动查询今日的订单
			if($post['date'] == '' || $post['date'] == NULL){
				$xml .= '<date>'.date('Y-m',time()).'</date>';
			}else{
				$xml .= '<date>'.$post['date'].'</date>';
			}
			//强制转码 由utf8转成gbk
			$xml = mb_convert_encoding($xml,'gbk','utf8');
			// logger('查询xml:'.$xml);//debug
			$url = session('url');
			$getxml = getXML($url,$xml);
			$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
			// logger('XML:'.$result."\n");//debug
			// 判断返回xml结果
			//如果xml总长度不超过40，则返回为空
			if(strlen($result) < 39){
				$data = array(
					'code' => 0,
					'message' => '未查找到相关财务统计信息！'
				);
	    		logger("===============================>>> “未”查找到相关财务信息 ----完毕---- <<<====================================\n");
	    		exit(json_encode($data));
			}else{
				$str_xml = substr($result,44);
				// logger('第一次截取：'.$str_xml."\n"); //debug
				$str2_xml = rtrim($str_xml,'></recipe>');
				// logger('第二次截取：'.$str2_xml."\n"); //debug
				$str3_xml = strchr($str2_xml,'<f>',TRUE); //订单详情的内容
				// logger('拆分--收入部分：'.$str3_xml."\n"); //debug
				$str4_xml = ltrim(strchr($str2_xml,'<f>'),'<');
				$arr_xq = explode('</i></l><l>',rtrim(ltrim($str3_xml,'l>'),'</i></l>'));
				$in_arr = $this->in_arr($arr_xq);
				// $de_arr_xq = var_export($de_arr,TRUE);//debug
				// logger('解析详情数组'.$de_arr_xq."\n");//debug
				// $arr_xq_str = var_export($arr_xq,TRUE);//debug
				// logger('拆分数组-收入:'.$arr_xq_str."\n");//debug
				// logger('拆分--支出部分：'.$str4_xml."\n"); //debug
				$arr_xml = explode('</i></f><f>',rtrim(ltrim($str4_xml,'f>'),'</i></f'));
				// $string = var_export($arr_xml,TRUE); //debug
				// logger('拆分数组-支出：'.$string."\n"); //debug 
				$out_arr = $this->out_arr($arr_xml);
				// $arr2_xml = var_export($fi_arr,TRUE); //debug
				// logger('财务数组：'.$arr2_xml."\n");//debug
				logger('查询今日财务完成'."\n");
				$data = array(
					'code' => 1,
					'message' => '财务统计信息返回成功',
					'in' => $in_arr,
					'out' => $out_arr
				);
				logger("===============================>>> 查询财务统计信息（月） ----成功---- <<<====================================\n");
				exit(json_encode($data));
			}
			
		}else{
			//logger('员工无权限查看财务统计'); 
			$data = array(
				'code' => 2,
				'message' => '员工无权限'
			);
			logger("===============================>>> 员工无权限 ----完毕---- <<<====================================\n");
			exit(json_encode($data));
		}
	}
	//处理收入部分数组
	// array (
	//   0 => '营业收入<i>16500',
	//   1 => '前期<i>3300',
	//   2 => '摄影二销<i>3300',
	//   3 => '化妆二销<i>4400',
	//   4 => '选片二销<i>5500',
	// )
	public function in_arr($arr){   
		$array = array();
		foreach($arr as $k => $v){
			switch($k){
				case 0:
					$array['totalin'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 1:
					$array['prepro'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 2:
					$array['ptsell'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 3:
					$array['mptsell'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 4:
					$array['sptsell'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				default:
					break;
			}
		}
		return $array;
	}
	//处理支出部分数组
	// array (
	//   0 => '总支出<i>2100',
	//   1 => '办公用品<i>600.0',
	//   2 => '加油<i>500.0',
	//   3 => '工资<i>400.0',
	//   4 => '水电<i>300.0',
	//   5 => '房租<i>200.0',
	//   6 => '生活费<i>100.0',
	// )
	public function out_arr($arr){
		$array = array();
		foreach($arr as $k => $v){
			$i = substr($v,0,1)-1;
			switch($k){
				case 0:
					$array['totalout'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 1:
					$array['office'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 2:
					$array['oil'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 3:
					$array['salary'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 4:
					$array['water'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 5:
					$array['rent'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				case 6:
					$array['live'] = ltrim(strchr($v,'<i>'),'<i>');
					break;
				default:
					break;	
			}
		}
		return $array;
	}
}
?>
<?php
namespace Home\Controller;
use Think\Controller;
class TodaytradeController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function index(){

	}
	public function query(){
		logger('===============================>>> 查询今日订单列表 ----开始---- <<<====================================');
		$post = I();
		// if(session('wtype') == 1){
			//连接远程服务器 key钥匙
			$admin = array(
				'operation' => 8,
				'dogid' => session('dogid')
			);
			$xml = transXML($admin); 
			$xml = strchr($xml,'<uu>',TRUE);
			//如果未指定则自动查询今日的订单
			if($post['date'] == '' || $post['date'] == NULL){
				$xml .= '<date>'.date('Y-m-d',time()).'</date>';
			}else{
				$the_date = trim(chtimetostr($post['date']),' ');
				$xml .= '<date>'.$the_date.'</date>';
			}
			//强制转码 由utf8转成gbk
			// $xml = mb_convert_encoding($xml,'gbk','utf8'); //弃用转码，都是英文或数字不存在乱码问题
			// logger('查询xml:'.$xml."--->"); //debug
			$url = session('url');
			$getxml = getXML($url,$xml);
			$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
			// logger('XML:'.$result.'字符串长度:'.strlen($result)); //debug
        	if(strlen($result) < 39){
        		logger("今日无订单\n");
	        	$data = array(
	        		'code' => '0',
	        		'message' => '今日无订单'
	        	);
	        	logger("===============================>>> 今日“无”订单 ----完毕---- <<<====================================\n");
	        	exit(json_encode($data));
	        }else{
	        	$str_xml = substr(rtrim($result,'></recipe>'),31);
	        	// logger('截取xml:'.$str_xml."\n"); //debug
	        	$tra_arr = explode('><l',$str_xml);
	        	// $tra_arr_str = var_export($tra_arr,TRUE);//debug
	        	// logger('截取数组：'.$tra_arr_str."\n"); //debug
	        	$tra_arr2 = $this->list_arr($tra_arr,$post['date']);
	        	// $tra_arr_str2 = var_export($tra_arr2,TRUE);//debug
	        	// logger('截取数组：'.$tra_arr_str2."\n"); //debug
	        	$data = array(
	        		'code' => '1',
	        		'message' => '今日订单返回成功',
	        		'result' => $tra_arr2
	        	);
	        	logger("===============================>>> 查询今日订单信息 ----成功---- <<<====================================\n");
	        	exit(json_encode($data));
	        }
		/*}else{
			//logger('员工无权限查看今日财务');
			$data = array(
				'code' => 2,
				'message' => '员工无权限'
			);
			logger("===============================>>> 员工无权限 ----完毕---- <<<====================================\n");
			exit(json_encode($data));
		} */
	}
	//   0 => '1>订单号：<id>20100511-003</id></l1',
	//   1 => '2>zs/ls/王五：13656789865/18666532220</l2',
	//   2 => '1>订单号：<id>20100511-002</id></l1',
	//   3 => '2>ws/李四：18666532220</l2',
	//   4 => '1>订单号：<id>20100511-001</id></l1',
	//   5 => '2>张三：13456789876</l2',
	// )
	//新增 套系和门市信息 20160513
	// 0 => '1>订单号：<id>20160513-006</id></l1',
	//   1 => '2>123：13794991569</l2',
	//   2 => '3>999婚纱照</l3',
	//   3 => '4>3999</l4',
	//   4 => '5>王海鹏</l5',

	// 处理订单列表数组
	public function list_arr($arr,$date){
		$array = array();
		$i = 0;
		foreach($arr as $k => $v){
			switch($k%5){
				case 0:
					$array[$i]['id'] = substr(strchr($v,'</id>',TRUE),18);
					break;
				case 1:
					$i--;
					$array[$i]['guest'] = strchr(ltrim($v,'2>'),'：',TRUE);
					$array[$i]['phone'] = ltrim(strchr(rtrim($v,'</l2'),'：'),'：');
					// if(session('vcip') == 1 || session('wtype') == 1){
    	// 				$array[$i]['phone'] = ltrim(strchr(rtrim($v,'</l2'),'：'),'：');
    	// 			}else{
    	// 				$array[$i]['phone'] = '';
    	// 			}
					break;
				case 2:
					$i--;
					$array[$i]['set'] = strchr(ltrim($v,'3>'),'</l',TRUE);
					break;
				case 3:
					$i--;
					$array[$i]['price'] = strchr(ltrim($v,'4>'),'</l',TRUE);
					break;
				case 4:
					$i--;
					$array[$i]['store'] = strchr(ltrim($v,'5>'),'</l',TRUE);
					$array[$i]['date'] = $date;
					break;
				default:
					break;
			}
			$i++;
		}
		return $array;
	}
}
?>
<?php
namespace Home\Controller;
use Think\Controller;
class SkbController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=UTF-8;");
	}
	//预留
	public function index(){

	}
	//摄控本查询
	public function query(){
		logger('=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=> 摄控本查询 ----开始---- <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=');
		$post = I();	
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 5,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin); 
		$xml = strchr($xml,'<uu>',TRUE);
		//如果未指定则自动查询今日的订单
		if($post['date'] == '' || $post['date'] == NULL){
			$xml .= '<date>'.date('Y-m-d',time()).'</date>';
		}else{
			$xml .= '<date>'.$post['date'].'</date>';
		}
		// 如果未指定查询类目，默认查询拍照
		if($post['type'] == '' || $post['type'] == NULL){
			$xml .= '<type>0</tyle>';
			$type = 0;
		}else{
			$xml .= '<type>'.$post['type'].'</type>';
			$type = $post['type'];
		}
		//强制转码 由utf8转成gbk
		// $xml = mb_convert_encoding($xml,'GB2312');
		// $xml = mb_convert_encoding($xml,'gbk','utf8');
		// $xml = iconv('UTF-8','GBK',$xml);
		$url = session('url');
		// $url = mb_convert_encoding($url,'GB2312','UTF-8');
		// logger('查询url+xml:'.$url.$xml."--->"); //debug
		$getxml = getXML($url,$xml);
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GBK');
		// logger('XML:'.$result); //debug
    	if(strlen($result) < 39){
    		logger("今日无订单\n");
        	$data = array(
        		'code' => '0',
        		'message' => '今日无预约'
        	);
        	logger("=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=> 今日'无'摄控本信息 ----完毕---- <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=\n");
        	exit(json_encode($data));
        }else{
        	$str_xml = substr(rtrim($result,'></recipe>'),31);
        	// logger('截取xml:'.$str_xml."\n"); //debug
        	$tra_arr = explode('><l',$str_xml);
        	// $tra_arr_str = var_export($tra_arr,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str."\n"); //debug
        	// 2016-06-02 返回数据，不同类型，返回值不一样
        	switch($type){
        		case 0:
        			$tra_arr2 = $this->charge_arr($tra_arr);
        			break;
        		case 1:
        			$tra_arr2 = $this->charge_arr_one($tra_arr);
        			break;
        		case 2:
        			$tra_arr2 = $this->charge_arr_two($tra_arr);
        			break;
        		case 3:
        			$tra_arr2 = $this->charge_arr_three($tra_arr);
        			break;
        		default:
        			break;
        	}
        	// $tra_arr_str2 = var_export($tra_arr2,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str2."\n"); //debug
        	$new_arr = array();
        	foreach($tra_arr2 as $k => $v){
        		$new_arr[$k] = $v;
        		$new_arr[$k]['type'] = $type;
        	}
        	// $tra_arr_str3 = var_export($new_arr,TRUE);//debug
        	// logger('截取=======数组：'.$tra_arr_str3."\n"); //debug
        	$data = array(
        		'code' => '1',
        		'message' => '今日订单返回成功',
        		'result' => $new_arr
        	);
        	logger("=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=> 摄控本查询 ----成功---- <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=\n");
        	exit(json_encode($data));
        }
	}
	//新处理函数 2016-06-02. 和原来比少了余款、景点、时间、摄影，助理、引导，助理；多了取件人和产品isOK  ------> 处理START

	public function charge_arr_one($arr){
		$array = $this->charge_arr_two($arr);
		return $array;
	}
	public function charge_arr_two($arr){
		// 0 => '1>客人：<i>张泽娟/张钰菡</i></l1',
	 //  1 => '2>爸爸电话：<i></i></l2',
	 //  2 => '2>妈妈电话：<i>18287451222</i></l2',
	 //  3 => '3>套系/价格：<i>合作医院百天照/0</i></l3',
	 //  4 => '4>门市：<i>喻泽</i></l4',
	 //  5 => '5>日期/时间：<i>2016-06-04 </i></l5',
	 //  6 => '6>看设计人：<i></i></l6',
	 //  7 => '1>客人：<i>袁如意/李濯瑶</i></l1',
	 //  8 => '2>爸爸电话：<i></i></l2',
	 //  9 => '2>妈妈电话：<i>15924905267</i></l2',
	 //  10 => '3>套系/价格：<i>合作医院百天照/0</i></l3',
	 //  11 => '4>门市：<i>程程</i></l4',
	 //  12 => '5>日期/时间：<i>2016-06-04 </i></l5',
	 //  13 => '6>看设计人：<i></i></l6',
	 //  14 => '1>客人：<i>后来</i></l1',
		$array = array();
		$i = 0;
		foreach($arr as $k => $v){
			switch($k%7){
				case 0:
					$array[$i]['guest'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					break;
				case 1:
					$i--;
					$array[$i]['paphone'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					// if(session('vcip') == 1 || session('wtype') == 1){
					// 	$array[$i]['paphone'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),20);
    	// 			}else{
    	// 				$array[$i]['paphone'] = '';
    	// 			}
					break;
				case 2:
					$i--;
					$array[$i]['maphone'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					// if(session('vcip') == 1 || session('wtype') == 1){
					// 	$array[$i]['maphone'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),20);		
    	// 			}else{
    	// 				$array[$i]['maphone'] = '';
    	// 			}
					break;
				case 3:
					$i--;
					$val = ltrim(strchr($v,'<i>'),'<i>');
					$array[$i]['set_name'] = strchr($val,'/',TRUE); //strchr(substr(strchr($v,'</i>',TRUE),21),'/',TRUE);
					$array[$i]['set_price'] = ltrim(strchr(strchr($v,'</i>',TRUE),'/'),'/'); //ltrim(strchr(substr(strchr($v,'</i>',TRUE),21),'/'),'/');
					break;
				case 4:
					$i--;
					$array[$i]['store'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),14);
					break;
				case 5:
					$i--;
					$array[$i]['time'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),21);
					break;
				case 6:
					$i--;
					$array[$i]['waitor'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),21);
					break;
				default:
					break;
			}
			$i++;
		}
		return $array;
	}
	public function charge_arr_three($arr){
		// 0 => '1>客人：<i>尹瑞欣妈妈/尹瑞欣</i></l1',
		 //  1 => '2>爸爸电话：<i></i></l2',
		 //  2 => '2>妈妈电话：<i>13988938862</i></l2',
		 //  3 => '3>套系/价格：<i>自定义套系/0</i></l3',
		 //  4 => '4>门市：<i>亮亮</i></l4',
		 //  5 => '5>日期/时间：<i>2016-06-02 </i></l5',
		 //  6 => '6>取件人：<i></i></l6',
		 //  7 => '7>产品是否OK：<i>未完成</i></l7',
		 //  8 => '1>客人：<i>段雪丽/陈沫涵</i></l1',
		 //  9 => '2>爸爸电话：<i></i></l2',
		 //  10 => '2>妈妈电话：<i>13769669122</i></l2',
		 //  11 => '3>套系/价格：<i>自定义套系/1399</i></l3',
		 //  12 => '4>门市：<i>多多</i></l4',
		 //  13 => '5>日期/时间：<i>2016-06-02 </i></l5',
		 //  14 => '6>取件人：<i></i></l6',
		 //  15 => '7>产品是否OK：<i>未完成</i></l7',
		 //  16 => '1>客人：<i>腾玉荣/王新怡</i></l1',
		$array = array();
		$i = 0;
		foreach($arr as $k => $v){
			switch($k%8){
				case 0:
					$array[$i]['guest'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					break;
				case 1:
					$i--;
					$array[$i]['paphone'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					// if(session('vcip') == 1 || session('wtype') == 1){
					// 	$array[$i]['paphone'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),20);
    	// 			}else{
    	// 				$array[$i]['paphone'] = '';
    	// 			}
					break;
				case 2:
					$i--;
					$array[$i]['maphone'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					// if(session('vcip') == 1 || session('wtype') == 1){
					// 	$array[$i]['maphone'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),20);
    	// 			}else{
    	// 				$array[$i]['maphone'] = '';
    	// 			}
					break;
				case 3:
					$i--;
					$val = ltrim(strchr($v,'<i>'),'<i>');
					$array[$i]['set_name'] = strchr($val,'/',TRUE); //strchr(substr(strchr($v,'</i>',TRUE),21),'/',TRUE);
					$array[$i]['set_price'] = ltrim(strchr(strchr($v,'</i>',TRUE),'/'),'/'); //ltrim(strchr(substr(strchr($v,'</i>',TRUE),21),'/'),'/');
					break;
				case 4:
					$i--;
					$array[$i]['store'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),14);
					break;
				case 5:
					$i--;
					$array[$i]['time'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),21);
					break;
				case 6:
					$i--;
					$array[$i]['waitor'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(strchr($v,'</i>',TRUE),21);
					break;
				case 7:
					$i--;
					$array[$i]['isok'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					break;
				default:
					break;
			}
			$i++;
		}
		return $array;
	}

	// 处理 END 2016-06-02
	// 处理拍照数组
	public function charge_arr($arr){
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
		$array = array();
		$i = 0;
		foreach($arr as $k => $v){
			switch($k%10){
				case 0:
					$array[$i]['guest'] = substr(strchr($v,'</i>',TRUE),14);
					break;
				case 1:
					$i--;
					$array[$i]['paphone'] = substr(strchr($v,'</i>',TRUE),20);
					// if(session('vcip') == 1 || session('wtype') == 1){
					// 	$array[$i]['paphone'] = substr(strchr($v,'</i>',TRUE),20);
    	// 			}else{
    	// 				$array[$i]['paphone'] = '';
    	// 			}
					break;
				case 2:
					$i--;
					$array[$i]['maphone'] = substr(strchr($v,'</i>',TRUE),20);
					// if(session('vcip') == 1 || session('wtype') == 1){
					// 	$array[$i]['maphone'] = substr(strchr($v,'</i>',TRUE),20);
    	// 			}else{
    	// 				$array[$i]['maphone'] = '';
    	// 			}
					break;
				case 3:
					$i--;
					$array[$i]['set_name'] = strchr(substr(strchr($v,'</i>',TRUE),21),'/',TRUE);
					$array[$i]['set_price'] = ltrim(strchr(substr(strchr($v,'</i>',TRUE),21),'/'),'/');
					break;
				case 4:
					$i--;
					$array[$i]['balance'] = substr(strchr($v,'</i>',TRUE),14);
					break;
				case 5:
					$i--;
					$array[$i]['vspot'] = substr(strchr($v,'</i>',TRUE),14);
					break;
				case 6:
					$i--;
					$array[$i]['time'] = substr(strchr($v,'</i>',TRUE),21);
					break;
				case 7:
					$i--;
					$array[$i]['store'] = substr(strchr($v,'</i>',TRUE),14);
					break;
				case 8:
					$i--;
					$array[$i]['pger'] = strchr(substr(strchr($v,'</i>',TRUE),21),'/',TRUE);
					$array[$i]['pgassister'] = ltrim(strchr(substr(strchr($v,'</i>',TRUE),21),'/'),'/');
					break;
				case 9:
					$i--;
					$array[$i]['guide'] = strchr(substr(strchr($v,'</i>',TRUE),21),'/',TRUE);
					$array[$i]['gassister'] = ltrim(strchr(substr(strchr($v,'</i>',TRUE),21),'/'),'/');
					break;
				default:
					break;
			}
			$i++;
		}
		return $array;
	}
	//阶段查询 http://119.29.6.140:8808/<aa>5</aa><yy>20150101080101888888</yy><date>2016-01-22</date><date2>2016-02-22</date2><type>0</type
	public function query_section(){
		logger('=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=> 摄控本查询 -----阶段查询----开始---- <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=');
		$post = I();	
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 5,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin); 
		$xml = strchr($xml,'<uu>',TRUE);
		// 如果未指定查询类目，默认查询拍照
		if($post['type'] == '' || $post['type'] == NULL){
			$xml .= '<type>0</tyle>';
			$type = 0;
		}else{
			$xml .= '<type>'.$post['type'].'</type>';
			$type = $post['type'];
		}
		//如果未指定则自动查询今日的订单
		if($post['date'] && $post['date']){
			// logger('1'); //debug
			$xml .= '<date>'.$post['date'].'</date><date2>'.$post['date2'].'</date2>';
			//初始化计数数组
			$numarr = array();
			$largeday = (strtotime($post['date2']) - strtotime($post['date']))/86400;
			for($i=0;$i<=$largeday;$i++){
				$key = date('Y-m-d',strtotime($post['date']) + $i * 86400);
				$numarr[$i] = array(
					'num' => 0,
					'date' => $key,
					'type' => $type
				);
			}
		}elseif($post['date'] && !$post['date2']){
			// logger('2'); //debug
			$xml .= '<date>'.$post['date'].'</date><date2>'.date('Y-m-d',time()+2592000).'</date2>';
			//初始化计数数组
			$numarr = array();
			$largeday = 30;
			for($i=0;$i<=$largeday;$i++){
				$key = date('Y-m-d',strtotime($post['date']) + $i * 86400);
				$numarr[$i] = array(
					'num' => 0,
					'date' => $key,
					'type' => $type
				);
			}
		}elseif(!$post['date'] && $post['date2']){
			// logger('3'); //debug
			$xml .= '<date>'.date('Y-m-d',time()).'</date><date2>'.$post['date2'].'</date2>';
			//初始化计数数组
			$numarr = array();
			$largeday = (strtotime($post['date2']) - strtotime(date('Y-m-d',time())))/86400;
			echo $largeday;
			for($i=0;$i<=$largeday;$i++){
				$key = date('Y-m-d',(time() + $i*86400));
				$numarr[$i] = array(
					'num' => 0,
					'date' => $key,
					'type' => $type
				);
			}
		}else{
			// logger('4'); //debug
			$xml .= '<date>'.date('Y-m-d',time()).'</date><date2>'.date('Y-m-d',time()+2592000).'</date2>';
			//初始化计数数组
			$numarr = array();
			$largeday = 30;
			for($i=0;$i<=$largeday;$i++){
				$key = date('Y-m-d',(time() + $i*86400));
				$numarr[$i] = array(
					'num' => 0,
					'date' => $key,
					'type' => $type
				);
			}
		}
		//echo "<pre>"; //debug
       	//var_dump($numarr); //debug
		//强制转码 由utf8转成gbk
		$xml = mb_convert_encoding($xml,'gbk','utf8');
		$url = session('url');
		logger('查询URL:'.$url.$xml."--->"); //debug
		$getxml = getXML($url,$xml);
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
		// logger('XML:'.$result); //debug
    	if(strlen($result) < 39){
    		logger("该日期段内无摄控本信息\n");
        	$data = array(
        		'code' => '1', 	//如果该日期段内无预约信息，则将预约量都设置成0，然后返回 2019-5-24
        		'message' => '阶段内摄控本信息返回成功，无预约信息',
        		'result' => $numarr
        	);
        	logger("=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=> 阶段内'无'摄控本信息 ------阶段查询----完毕---- <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=\n");
        	exit(json_encode($data));
        }else{
        	$str_xml = substr(rtrim($result,'></recipe>'),31);
        	// logger('截取xml:'.$str_xml."\n"); //debug
        	$tra_arr = explode('><l',$str_xml);
        	// $tra_arr_str = var_export($tra_arr,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str."\n"); //debug
        	// 2016-06-02 返回数据，不同类型，返回值不一样
        	switch($type){
        		case 0:
        			$tra_arr2 = $this->charge_arr($tra_arr);
        			break;
        		case 1:
        			$tra_arr2 = $this->charge_arr_one($tra_arr);
        			break;
        		case 2:
        			$tra_arr2 = $this->charge_arr_two($tra_arr);
        			break;
        		case 3:
        			$tra_arr2 = $this->charge_arr_three($tra_arr);
        			break;
        		default:
        			break;
        	}
        	// $tra_arr_str2 = var_export($tra_arr2,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str2."\n"); //debug
        	// logger('查询结果数组:'.var_export($tra_arr2,TRUE)); //debug
        	//日历 计数
        	foreach($tra_arr2 as $k => $v){
        		$day = date('Y-m-d',strtotime($v['time']));
        		foreach($numarr as $ki => $val){
    				if($day == $val['date']){
    					$numarr[$ki]['num']++;
    				}
        		}
        	}
        	// echo "<pre>"; //debug
        	// var_dump($numarr); //debug
        	logger("=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=> 阶段内摄控本查询 ----阶段查询----成功---- <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=\n");
        	$data = array(
        		'code' => '1',
        		'message' => '阶段内摄控本信息返回成功',
        		'result' => $numarr
        	);
        	exit(json_encode($data));

        	//返回具体数据详细信息 START
        	/*
        	$new_arr = array();
        	foreach($tra_arr2 as $k => $v){
        		$new_arr[$k] = $v;
        		$new_arr[$k]['type'] = $type;
        	}
        	// $tra_arr_str3 = var_export($new_arr,TRUE);//debug
        	// logger('截取=======数组：'.$tra_arr_str3."\n"); //debug
        	$data = array(
        		'code' => '1',
        		'message' => '阶段内摄控本信息返回成功',
        		'result' => $new_arr
        	);
        	logger("=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=> 摄控本查询 ----阶段查询----成功---- <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=\n");
        	exit(json_encode($data)); */
        	//返回具体数据详细信息 END
        }
	}
}
?>
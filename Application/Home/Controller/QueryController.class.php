<?php
namespace Home\Controller;
use Think\Controller;
class QueryController extends Controller{
	//判断APP用户登录状态
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//订单模糊查询
	public function index(){
		$detailinfo = I();
		$detailStr = json_encode($detailinfo);
		logger("【模糊查询订单-开始】--->账号信息：".$detailStr);
		if(I('detail')){
			// 查询服务器地址
			$url = session('url');      //http://119.29.6.140:8808/<aa>1</aa><yy>20150101080101888888</yy><name>zs</name>
			$detailinfo['dogid'] = session('dogid'); 
			$xml = $this->trans_name_XML($detailinfo);
			//强制转码 由utf8转成gbk
			$xml = mb_convert_encoding($xml,'gbk','utf8');
			// logger('xml:'.$url.$xml); //debug
			$result = getXML($url,$xml);
			// logger('原始XML:'.$result); //debug  //会导致日志乱码
			//转码 很重要
			$result = mb_convert_encoding($result, 'UTF-8', 'GBK');  //GB2312可能会造成某些字符“/” 乱码
			// logger('返回XML:'.$result.'长度:'.strlen($result)); //打印得到的XML字符串 //debug
			// 如果返回值少于多少字节，则没有查找到相关订单，直接返回错误信息
			if(strlen($result) < 39){
				$arr = array('code' => 0,'message' => '未查找到相关订单信息！');
	    		logger("查询结果： 未查找到相关订单，查询失败！");
	    		logger("{查询完成！}\n");
	    		exit(json_encode($arr));
			}
			$multi = substr($result,36,1);
			if($multi == 1){
				// echo "success"; //debug
				$str = substr($result,47);
				$str = rtrim($str,'></recipe>');
				$arra = explode("><l",$str);
				// $str2 = var_export($arra,TRUE); //打印数组a到变量 //debug
				$arrb = $this->listarr_new($arra);
				//添加一个单订单或多订单的区别标识
				$arrb['code'] = 1;
				$arrb['multi'] = 1;
				$arrb['message'] = '订单列表返回成功！';
				// $str3 = var_export($arrb,TRUE); //打印数组b到变量 //debug
				logger("{多订单--multi订单列表multi--完成！}\n");
				// logger("截取字段 \n".$str); //打印截取后的XML //debug
				// logger("数组a：\n".$str2); //打印数组a到文件 //debug
				// logger("数组b：\n".$str3); //打印数组b到文件 //debug
				// $new[] = $arrb;
				exit(json_encode($arrb));
			}else{
				// echo "die!"; //debug
				$str = substr($result,31);
				$str = rtrim($str,'></recipe>');
				// logger("截取字段 \n".$str); //打印截取后的XML //debug
		/* 新处理函数，分两步处理数组 START*/
				$string_one = strchr($str,'</l8',TRUE); // 1-8条数据
				$arr_one = explode("><l",$string_one);
				// logger('订单详情，进度详情数组：'.var_export($arr_one,TRUE)); //debug
				$arrb = $this->detailarr_one($arr_one);
				// logger('订单详情，进度详情数组-->处理结果：'.var_export($arrb,TRUE)); //debug
				$string_two = strchr($str,'</l8'); //产品数据
				// logger('产品详情字符串==>'.$string_two); //debug
				if(strlen($string_two)<5){ //产品数据可能为空
					$arrb['good'] = array();
				}else{
					$string_two = ltrim($string_two,'</l8><l'); //产品数据
					// logger('产品详情字符串==>'.$string_two); //debug
					$arr_two = explode("><l",$string_two);
					// logger('产品详情数组：'.var_export($arr_two,TRUE)); //debug
					$arr_products = $this->detailarr_two($arr_two);
					// logger('产品详情数组-->处理结果：'.var_export($arr_products,TRUE)); //debug
					$arrb['good'] = $arr_products;
				}
		/* 新处理函数，分两步处理数组 END*/
		/* 弃用原来的处理流程*/
		/*		$arra = explode("><l",$str);
				$str2 = var_export($arra,TRUE); //打印数组a到变量 //debug
				logger("数组a：\n".$str2); //打印数组a到文件 //debug
				// die; //debug
				$arrb = $this->detailarr($arra);
				$str3 = var_export($arrb,TRUE);  //打印数组b到变量 //debug
				logger("数组b：\n".$str3); //打印数组b到文件 //debug 
		*/
				// 添加一个单订单或多订单的区别标识
				$arrb['code'] = 1;
				$arrb['multi'] = 0;
				$arrb['message'] = '订单详情返回成功！';
				logger("{单订单--订单详情--完成！}\n");
				exit(json_encode($arrb));
			}
	    }else{
	    	// 顾客名称、手机号、订单号等未输入 返回最近10条订单
	    	// 查询服务器地址
	    	$url = session('url');
	    	$dogid = session('dogid');
			$url .= '<aa>4</aa><yy>'.$dogid.'</yy><name></name>';       //http://119.29.6.140:8808/<aa>1</aa><yy>20150101080101888888</yy><name>zs</name>
			$xml = '';
			$result = getXML($url,$xml);
			//转码 很重要
			$result = mb_convert_encoding($result, 'UTF-8', 'GB2312');
			// logger('xml:'.$result);  //打印得到的XML字符串
			$str = substr($result,47);
			$str = rtrim($str,'></recipe>');
			$arra = explode("><l",$str);
			$arrb = $this->listarr($arra);
			$arrb['1'] = 1;
			$arrb['multi'] = 1;
			$arrb['message'] = '订单列表返回成功！';
			logger("{最新10单--multi10列表10multi--完成！}\n");
			exit(json_encode($arrb));
	    }
	}
	// 订单详情查询 订单号 id
	public function detail(){
		$detailinfo = I();
		$detailStr = json_encode($detailinfo);
		logger("【查询订单详情-开始】--->账号信息：".$detailStr);
		if(I('id')){
			// 查询服务器地址
			$url = session('url');       //http://119.29.6.140:8808/<aa>1</aa><yy>20150101080101888888</yy><uu>zs</uu><pp>18666532220</pp>
			$detailinfo['dogid'] = session('dogid'); 
			$xml = $this->trans_id_XML($detailinfo);
			//强制转码 由utf8转成gbk
			$xml = mb_convert_encoding($xml,'gbk','utf8');
			$result = getXML($url,$xml);
			//转码 很重要
			$result = mb_convert_encoding($result, 'UTF-8', 'GB2312');
			// logger("订单详情(XML)：".$result);  //打印得到的XML字符串

			$str = substr($result,31);
			$str = rtrim($str,'></recipe>');
	/* 新处理函数，分两步处理数组 START*/
			$string_one = strchr($str,'</l8',TRUE); // 1-8条数据
			$arr_one = explode("><l",$string_one);
			// logger('订单详情，进度详情数组：'.var_export($arr_one,TRUE)); //debug
			$arrb = $this->detailarr_one($arr_one);
			// logger('订单详情，进度详情数组-->处理结果：'.var_export($arrb,TRUE)); //debug
			$string_two = strchr($str,'</l8'); //产品数据
			// logger('产品详情字符串==>'.$string_two); //debug
			if(strlen($string_two)<5){ //产品数据可能为空
				$arrb['good'] = array();
			}else{
				$string_two = ltrim($string_two,'</l8><l'); //产品数据
				// logger('产品详情字符串==>'.$string_two); //debug
				$arr_two = explode("><l",$string_two);
				// logger('产品详情数组：'.var_export($arr_two,TRUE)); //debug
				$arr_products = $this->detailarr_two($arr_two);
				// logger('产品详情数组-->处理结果：'.var_export($arr_products,TRUE)); //debug
				$arrb['good'] = $arr_products;
			}
		/* 新处理函数，分两步处理数组 END*/
	/* 弃用原处理流程*/
	/*
			$arra = explode("><l",$str);
			// $str2 = var_export($arra,TRUE); //打印数组a到变量
			$arrb = $this->detailarr($arra);
	*/
			//添加一个单订单或多订单的区别标识
			$arrb['code'] = 1;
			$arrb['multi'] = 0;
			$arrb['message'] = '订单详情返回成功！';
			logger("{订单详情--完成！}\n");
			// $str3 = var_export($arrb,TRUE);  //打印数组b到变量
			// logger("单订单--订单详情 \n");
			// logger("截取字段 \n".$str); //打印截取后的XML
			// logger("数组a：\n".$str2); //打印数组a到文件
			// logger("数组b：\n".$str3); //打印数组b到文件
			exit(json_encode($arrb));
	    }else{
	    	$arr = array('code' => 2,'message' => '未查找到相关信息！');
	    	logger("查询结果： 订单ID号为空或错误，查询失败！");
	    	logger("{查询完成！}\n");
	    	exit(json_encode($arr));
	    }

	}
	// 订单进度查询
	public function status(){
		$checkinfo = I();
		$checkStr = json_encode($checkinfo);
		logger("[查询订单进度-开始]----->账号信息：".$checkStr);
		if(I('guest') || I('phone')){
			// 查询服务器地址
			$url = session('url'); 
			$checkinfo['dogid'] = session('dogid');      //http://119.29.6.140:8808/<aa>1</aa><yy>20150101080101888888</yy><uu>zs</uu><pp>18666532220</pp>
			$xml = $this->transXML($checkinfo);
			//强制转码 由utf8转成gbk
			$xml = mb_convert_encoding($xml,'gbk','utf8');
			$result = getXML($url,$xml);
			//转码 很重要
			$result = mb_convert_encoding($result, 'UTF-8', 'GB2312');
			// $result = iconv("gb2312","utf8",$result); //出现意外错误，会自动在localhost前后加上www. .com
			// logger("订单进度详情(XML)：".$result.' 长度：'.strlen($result)); //debug
		/*新处理方式 START */
			if(strlen($result) < 39){
				$arr = array('code' => 0,'message' => '未查找订单的进度信息！');
	    		logger("查询结果： 未查找到订单D进度信息，查询失败！");
	    		logger("{查询完成！}\n");
	    		exit(json_encode($arr));
			}
			//<?xml version='1.0'!>
				// <recipe>
				// 	<r>loginok</r>
				// 	<l1>客人：<i>后来</i></l1>
				// 	<l2>爸爸电话：<i></i></l2>
				// 	<l3>妈妈电话：<i>13810918651</i></l3>
				// 	<l4>套系/价格：<i>合作医院满月照赠送/2688</i></l4>
				// 	<l5>余款：<i>1688</i></l5>
				// 	<l6>门市：<i>管理员</i></l6>
				// 	<l7>流程：<i>拍照->未拍</i><br/><i>修片->未修</i><br/><i>选片->未选</i><br/><i>精修->未修</i><br/><i>设计->未设计</i><br/><i>看设计->未看</i><br/><i>取件->未取</i></l7>
				// </recipe> 
			$string = strchr(strchr($result,'<l'),'</l7',TRUE);
			logger('截取字符串：'.$string); //debug
			$arr_one = explode('><l',$string);
			logger('处理前数组：'.var_export($arr_one,TRUE)); //debug
			$arrb = $this->detailarr_one($arr_one);
			logger('最终数组：'.var_export($arrb,TRUE)); //debug
			$data = array(
				'code' => 1,
				'message' => '订单进度返回成功！',
				'result' => $arrb
			);
			logger("订单进度返回成功！\n");
			exit(json_encode($data));
		/*新处理方式 END */
	/*	弃用原来的处理方式 2016-06-01
			// 将XML数据转换为对象
			// $obj = simplexml_load_string($result,'SimpleXMLElement',LIBXML_NOCDATA);
	  		// logger("查询结果1： \n".$obj);

	        // 将XML数据转换为JSON后再转为数组 （和上面的方式选择其一就行了）
	  		$res = simplexml_load_string($result);
	  		$res->addChild("code","1");
	  		$res->addChild("message","返回订单进度成功");
	  		$arr = json_encode($res);
			$data = json_decode($arr,TRUE);
			$str2 = implode(' ',$data);
	  		// logger('查询结果：'.$str2);
	  		logger("返回订单进度完成！");
	  		logger("{查询完成！}\n");
	  		// echo "<pre>";
	  		// var_dump($data);
	        exit($arr);
	*/
	    }else{
	    	$arr = array('code' => 0,'message' => '顾客名或手机号码错误或为空，查询失败');
	    	logger("查询结果： 客人名称和电话号码都为空或错误，查询失败！");
	    	logger("{查询完成！}\n");
	    	exit(json_encode($arr));
	    }
	}
	// 将APP传过来的JSON值->数组后，再转换成需要的XML形式
	public function transXML($arr){
		if($arr['guest'] == '' || $arr['guest'] == NULL || !$arr['guest']){
			$xmlTpl = "<aa>%s</aa><yy>%s</yy><pp>%s</pp><uu></uu>";
	    	$result = sprintf($xmlTpl,1,$arr['dogid'],$arr['phone']);  //20150101080101888888
	    	return $result;
		}else if($arr['phone'] == '' || $arr['phone'] == NULL || !$arr['phone']){
			$xmlTpl = "<aa>%s</aa><yy>%s</yy><uu>%s</uu><pp></pp>";
	    	$result = sprintf($xmlTpl,1,$arr['dogid'],$arr['guest']);
	    	return $result;
		}else{
			$xmlTpl = "<aa>%s</aa><yy>%s</yy><uu>%s</uu><pp>%s</pp>";
	    	$result = sprintf($xmlTpl,1,$arr['dogid'],$arr['guest'],$arr['phone']);
	    	return $result;
		}
    	
    }
	// 将APP传过来的JSON值->数组后，再转换成需要的XML形式  模糊查询
	public function trans_name_XML($arr){
			$xmlTpl = "<aa>%s</aa><yy>%s</yy><name>%s</name>";
	    	$result = sprintf($xmlTpl,4,$arr['dogid'],$arr['detail']);
	    	return $result;   	
    }
	// 将APP传过来的JSON值->数组后，再转换成需要的XML形式  模糊查询
	public function trans_id_XML($arr){
			$xmlTpl = "<aa>%s</aa><yy>%s</yy><id>%s</id>";
	    	$result = sprintf($xmlTpl,4,$arr['dogid'],$arr['id']);
	    	return $result;   	
    }
    //增加了套系和价格 2016-05-09
		   //  	<?xml version='1.0'!>
		   //  		<recipe><multi>1</multi>
					// 	<l1>订单号：<id>20160511-001</id></l1>
					// 	<l2>lili：13810918651</l2>
					// 	<l3>缇?濂藉勾??</l3>
					// 	<l1>订单号：<id>20160506-005</id></l1>
					// 	<l2>lili：13810918651</l2>
					// 	<l3>缇?濂藉勾??</l3>
					// 	<l1>订单号：<id>20160506-003</id>
					// </recipe>
    // 再次修改处理函数 2016-05-27
			// array (
			//   0 => '1>订单号：<id>20160525-013</id></l1',
			//   1 => '2>Akmz：13537729094</l2',
			//   2 => '3>五彩记忆</l3',
			//   3 => '4>688</l4',
			//   4 => '5>KD</l5',
			//   5 => '1>订单号：<id>20160525-009</id></l1',
			//   6 => '2>Akm：13537729094</l2',
			//   7 => '3>合作医院百天照</l3',
			//   8 => '4>1988</l4',
			//   9 => '5>KD</l5',
			//   10 => '1>订单号：<id>20160525-007</id></l1',
			//   11 => '2>Akm：13537729094</l2',
    //新的多订单(列表)处理函数
    public function listarr_new($arr){
    	$array = array();
    	$i = 0;
    	foreach($arr as $k => $v){
    		switch($k%5){
    			case 0:
    				$value = strchr(ltrim($v,'1>订单号：<id>'),'</id>',TRUE);
    				$array['result'][$i]['tradeID'] = $value;
    				break;
    			case 1:
    				$i--;
	    			$person = strchr(ltrim($v,'2>'),'：',TRUE);
	    			$phone = substr(rtrim($v,'</l2'),strpos($v,'：')+3);
	    			$array['result'][$i]['guestname'] = $person;
	    			$array['result'][$i]['phone'] = $phone;
    				break;
    			case 2:
    				$i--;
	    			$set = strchr(ltrim($v,'3>'),'</l3',TRUE);
	    			//$price = substr(rtrim($v,'</l3'),strpos($v,'：')+3);
	    			$array['result'][$i]['set'] = $set;
	    			//$array['result'][$i]['price'] = $price;
    				break;
    			case 3:
    				$i--;
    				$price = strchr(ltrim($v,'4>'),'</l4',TRUE);
    				$array['result'][$i]['price'] = $price;
    				break;
    			case 4:
    				$i--;
    				$store = strchr(ltrim($v,'5>'),'</l5',TRUE);
    				$array['result'][$i]['store'] = $store;
    				break;
    			default:
    				break;
    		}
    		$i++;
    	}
    	return $array;
    }
    //清理订单列表的xml数据，将其变成可用的数组
    public function listarr($arr){
    	$array = array();
    	$i = 0;
    	foreach($arr as $k => $v){
    		if($k%2 == 0){
    			$value = strchr(ltrim($v,'1>订单号：<id>'),'</id>',TRUE);
    			$array['result'][$i]['tradeID'] = $value;
    		}else{
    			$i--;
    			$person = strchr(ltrim($v,'2>'),'：',TRUE);
    			$phone = substr(rtrim($v,'</l2'),strpos($v,'：')+3);
    			$array['result'][$i]['guestname'] = $person;
    			$array['result'][$i]['phone'] = $phone;
    		}
    		$i++;
    	}
    	return $array;
    }
    //清理订单详情的xml数据，将其变成可用的数组  //弃用 2016-05-31
    public function detailarr($arr){
    	$array = array();
    	foreach($arr as $k => $v){
    		switch($k){
    			case 0:
    				$value= ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l1'),14);
    				$array['baby'] = $value; //宝贝姓名
    				break;
    			case 1:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l2'),20); 
    				$array['paphone'] = $value; //爸爸电话
    				break;
    			case 2:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l3'),20);
    				$array['maphone'] = $value; //妈妈电话
    				break;
    			case 3:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l4'),21);
    				$array['packages'] = $value; //套系and价格？
    				break;
    			case 4:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l5'),14);
    				$array['balance'] = $value; //余款?
    				break;
    			case 5:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l6'),14);
    				$array['store'] = $value; //门市
    				break;
    			case 6: //流程
    				$str = substr(rtrim($v,'</i></l7'),14);
    				$value = explode('</i><br/><i>',$str);
    				foreach($value as $k => $v){
    					switch($k){
    						case 0:
    							$proa['shoot'] = substr($v,8); //拍照
    							break; 
    						case 1:
    							$proa['ps'] = substr($v,8); //修片
    							break;
    						case 2:
    							$proa['selectp'] = substr($v,8); //选片
    							break;
    						case 3:
    							$proa['exps'] = substr($v,8); //精修
    							break;
    						case 4:
    							$proa['design'] = substr($v,8); //设计
    							break;
    						case 5:
    							$proa['cdesign'] = substr($v,11); //看设计
    							break;
    						case 6:
    							$proa['pickup'] = substr($v,8); //取件
    							break; 
    					}
    				}
    				$array['process'] = $proa;
    				break;
    			case 7: //8>订单号：<i>20100511-002</i></l8  新增 20160509
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l8'),17);
    				$array['tradeid'] = $value;
    				break;
    			case 8:
    				$value = substr(rtrim($v,'</l1'),11);
    				$array['goods'][0]['name']= $value; //套系商品1名称
    				break;
    			case 10:
    				$str = substr(rtrim($v,'</l3'),27);
    				if(strlen($str) < 3){
    					$array['goods'][0]['sendP'] = '';
    					$array['goods'][0]['sendT'] = '';
	    			}else{
	    				$person= strchr($str,'/',TRUE);
	    				$time = trim(strchr($str,'/'),'/');
	    				$array['goods'][0]['sendP'] = $person;
	    				$array['goods'][0]['sendT'] = $time;
	    			}   				
    				break;
    			case 12:
    				$str = substr(rtrim($v,'</l3'),27);
    				if(strlen($str) < 3){
    					$array['goods'][0]['makeP'] = '';
    					$array['goods'][0]['makeT'] = '';
    				}else{
    					$person= strchr($str,'/',TRUE);
	    				$time = trim(strchr($str,'/'),'/');
	    				$array['goods'][0]['makeP'] = $person;
	    				$array['goods'][0]['makeT'] = $time;
    				}   				
    				break;
    			case 13:
    				$value = substr(rtrim($v,'</l1'),11);
    				$array['goods'][1]['name']= $value;
    				break;
    			case 15:
    				$str = substr(rtrim($v,'</l3'),27);
    				if(strlen($str) < 3){
    					$array['goods'][1]['sendP'] = '';
    					$array['goods'][1]['sendT'] = '';
    				}else{
    					$person= strchr($str,'/',TRUE);
	    				$time = trim(strchr($str,'/'),'/');
	    				$array['goods'][1]['sendP'] = $person;
	    				$array['goods'][1]['sendT'] = $time;
    				}   				
    				break;
    			case 17:
    				$str = substr(rtrim($v,'</l3'),27);
    				if(strlen($str) < 3){
    					$array['goods'][1]['makeP'] = '';
    					$array['goods'][1]['makeT'] = '';
    				}else{
    					$person= strchr($str,'/',TRUE);
	    				$time = trim(strchr($str,'/'),'/');
	    				$array['goods'][1]['makeP'] = $person;
	    				$array['goods'][1]['makeT'] = $time;
    				}
    				break;
    			case 18:
    				$value = substr(rtrim($v,'</l1'),11);
    				$array['goods'][2]['name']= $value;
    				break;
    			case 20:
    				$str = substr(rtrim($v,'</l3'),27);
    				if(strlen($str) < 3){
    					$array['goods'][2]['sendP'] = '';
    					$array['goods'][2]['sendT'] = '';
    				}else{
    					$person= strchr($str,'/',TRUE);
    					$time = trim(strchr($str,'/'),'/');
    					$array['goods'][2]['sendP'] = $person;
    					$array['goods'][2]['sendT'] = $time;
    				}
    				break;
    			case 22:
    				$str = substr(rtrim($v,'</l3'),27);
    				if(strlen($str) < 3){
    					$array['goods'][2]['makeP'] = '';
    					$array['goods'][2]['makeT'] = '';
    				}else{
    					$person= strchr($str,'/',TRUE);
    					$time = trim(strchr($str,'/'),'/');
    					$array['goods'][2]['makeP'] = $person;
    					$array['goods'][2]['makeT'] = $time;
    				}
    				break;
    			default :
    				break;
    		}
    	}
    	return $array;
    }
    // 重写，订单详情数组处理函数； 分成两部分，一部分处理前8条数据 另一部分处理产品数据 2016-05-31
    public  function  detailarr_one($arr){
    	$array = array();
    	foreach($arr as $k => $v){
    		switch($k){
    			case 0:
    				$value= ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l1'),14);
    				$array['baby'] = $value; //宝贝姓名
    				break;
    			case 1:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l2'),20); 
    				$array['paphone'] = $value;
    				// if(session('vcip') == 1 || session('wtype') == 1){
    				// 	$array['paphone'] = $value; //爸爸电话
    				// 	logger('管理员或有查看权限的用户查看用户敏感信息!');
    				// }else{
    				// 	$array['paphone'] = ''; //爸爸电话
    				// 	logger('普通员工无权限查看用户敏感信息!');
    				// }
    				break;
    			case 2:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l3'),20);
    				$array['maphone'] = $value;
    				// if(session('vcip') == 1 || session('wtype') == 1){
    				// 	$array['maphone'] = $value; //妈妈电话
    				// }else{
    				// 	$array['maphone'] = ''; //妈妈电话
    				// }
    				break;
    			case 3:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l4'),21);
    				$array['packages'] = $value; //套系and价格？
    				break;
    			case 4:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l5'),14);
    				$array['balance'] = $value; //余款?
    				break;
    			case 5:
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l6'),14);
    				$array['store'] = $value; //门市
    				break;
    			case 6: //流程
    				$str = substr(rtrim($v,'</i></l7'),14);
    				$value = explode('</i><br/><i>',$str);
    				foreach($value as $k => $v){
    					switch($k){
    						case 0:
    							$proa['shoot'] = substr($v,8); //拍照
    							break; 
    						case 1:
    							$proa['ps'] = substr($v,8); //修片
    							break;
    						case 2:
    							$proa['selectp'] = substr($v,8); //选片
    							break;
    						case 3:
    							$proa['exps'] = substr($v,8); //精修
    							break;
    						case 4:
    							$proa['design'] = substr($v,8); //设计
    							break;
    						case 5:
    							$proa['cdesign'] = substr($v,11); //看设计
    							break;
    						case 6:
    							$proa['pickup'] = substr($v,8); //取件
    							break; 
    					}
    				}
    				$array['process'] = $proa;
    				break;
    			case 7: //8>订单号：<i>20100511-002</i></l8  新增 20160509
    				$value = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>'); //substr(rtrim($v,'</i></l8'),17);
    				$array['tradeid'] = $value;
    				break;
    			default :
    				break;
    		}
    	}
    	return $array;
    }
    //处理产品信息，产品数量不确定 2016-05-31
    public  function  detailarr_two($arr){
    	$array = array();
    	$i = 0;
    	foreach($arr as $k => $v){
    		switch($k%5){
    			case 0:
    				$value = ltrim(strchr(strchr($v,'</l',TRUE),'：'),'：');
    				$array[$i]['name']= $value; //套系商品1名称
    				break;
    			case 1:
    				$i--;
    				$value = ltrim(strchr(strchr($v,'</l',TRUE),'>'),'>');
    				$array[$i]['makeStatus']= $value; //产品制作状态
    				break;
    			case 2:
    				$i--;
    				$array[$i]['makeP']= ''; //产品制作人 默认为空，如果有信息，则自动填充
	    			$array[$i]['makeT']= ''; //产品制作时间 默认为空，如果有信息，则自动填充
    				if(strstr($v,'：')){
    					$value = ltrim(strchr(strchr($v,'</l',TRUE),'：'),'：');
	    				$person = strchr($value,'/',TRUE);
	    				$time = ltrim(strchr($value,'/'),'/');
	    				$array[$i]['makeP']= $person; //产品制作人
	    				$array[$i]['makeT']= $time; //产品制作时间
    				}
    				break;
    			case 3:
    				$i--;
    				$value = ltrim(strchr(strchr($v,'</l',TRUE),'>'),'>');
    				$array[$i]['sendStatus']= $value; //产品制作状态
    				break;
    			case 4:
    				$i--;
    				$array[$i]['sendP']= ''; //产品制作人 默认为空，如果有信息，则自动填充
	    			$array[$i]['sendT']= ''; //产品制作时间 默认为空，如果有信息，则自动填充
    				if(strstr($v,'：')){
    					$value = ltrim(strchr(strchr($v,'</l',TRUE),'：'),'：');
	    				$person = strchr($value,'/',TRUE);
	    				$time = ltrim(strchr($value,'/'),'/');
	    				$array[$i]['sendP']= $person; //产品制作人
	    				$array[$i]['sendT']= $time; //产品制作时间
    				}
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
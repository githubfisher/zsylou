<?php
namespace Home\Controller;
use Think\Controller;
class ProductsController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//模糊查询函数  按类别,关键字或查询全部
	public function query(){
		logger('==============<<<>>> 查询产品列表 ----开始---- <<<>>>==============');
		$post = I();
		$type = $post['type'];
		$name = $post['name'];
		$admin = array(
			'operation' => 15,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin); 
		$xml = strchr($xml,'<uu>',TRUE);
		//如果未指定type和name则自动查询全部的订单, 如果为指定name,而指定了type,则查询该类目下的所有产品
		$xml .= '<type>'.$type.'</type><name>'.$name.'</name>';  //http://119.29.6.140:8808/<aa>15</aa><yy>20150101080101888888</yy><type>相册<type><name>水晶相册</name>
		//强制转码 由utf8转成gbk
		$xml = mb_convert_encoding($xml,'gbk','utf8');
		// logger('查询xml:'.$xml."--->"); //debug
		$url = session('url');
		$getxml = getXML($url,$xml); 
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
		// logger('XML:'.$result.'长度:'.strlen($result)); //debug
    	if(strlen($result) < 39){
    		logger("产品列表为空\n");
        	$data = array(
        		'code' => '0',
        		'message' => '产品列表为空'
        	);
        	logger("==============<<<>>> 产品列表为空  ----完毕---- <<<>>>==============\n");
        	exit(json_encode($data));
        }else{
        	$str_xml = substr(rtrim($result,'></l></recipe>'),33);
        	// logger('截取xml:'.$str_xml."\n"); //debug
        	$tra_arr = explode('></l><l><',$str_xml);
        	// $tra_arr_str = var_export($tra_arr,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str."\n"); //debug
        	$tra_arr2 = $this->list_arr2($tra_arr);
        	// $tra_arr_str2 = var_export($tra_arr2,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str2."\n"); //debug
        	$data = array(
        		'code' => '1',
        		'message' => '产品列表返回成功',
        		'result' => $tra_arr2
        	);
        	logger("==============<<<>>> 产品列表  ----成功---- <<<>>>==============\n");
        	exit(json_encode($data));
        }
	}
	 // 0 => 't>钱包照</t><n>钱包照</n><p>0</p',
	 //  1 => 't>水晶</t><n>7寸水晶</n><p>0</p',
	 //  2 => 't>相册</t><n>12寸水晶相册</n><p>0'
	// 原处理产品列表数组函数1  
	public function list_arr($arr){
		$array = array();
		$cate = array();
		$i = 0;
		foreach($arr as $k => $v){
			$new_arr = explode('><',$v);
			foreach($new_arr as $key => $value){
				switch($key){
					case 0:
						$category = ltrim(strchr($value,'</',TRUE),'t>');
						$num = $cate[$category];
						if( $num > 0){
							$num++;
						}else{
							$cate[$category] = 0;
							$num = 0;
						}
						$array[$category][$num]['category'] = $category;
						break;
					case 1:
						$array[$category][$num]['pro_name'] = ltrim(strchr($value,'</',TRUE),'n>');
						break;
					case 2:
						$array[$category][$num]['pro_price'] = trim(rtrim(strchr($value,'>'),'</p'),'>');
						break;
					default:
						break;
				}
			}
			$i++;
		}
		return $array;
	}
	// array (
 //  0 => 't>摆台</t><n>8x8花边烤瓷</n><p>0</p',
 //  1 => 't>摆台</t><n>10寸水晶</n><p>0</p',
 //  2 => 't>摆台</t><n>8X12花边烤瓷</n><p>0</p',
 //  3 => 't>摆台</t><n>12寸白珍珠</n><p>0</p',
 //  4 => 't>摆台</t><n>8寸水晶</n><p>0</p',
 //  5 => 't>摆台</t><n>10寸拉米</n><p>0</p',
 //  6 => 't>摆台</t><n>8X10加百利摆台1副</n><p>0</p',
 //  7 => 't>摆台</t><n>10寸雅兰摆台</n><p>0</p',
 //  8 => 't>摆台</t><n>8X10曲边亚米奇</n><p>0</p',
 //  9 => 't>摆台</t><n>10寸兰蔻摆台</n><p>0</p',
 //  10 => 't>摆台</t><n>丹妮烤瓷（大众）</n><p>0</p',
 //  11 => 't>摆台</t><n>蝴蝶烤瓷（大众）</n><p>0</p',
 //  12 => 't>摆台</t><n>小月烤瓷（大众）</n><p>0</p',
 //  13 => 't>摆台</t><n>苹果烤瓷（大众）</n><p>0</p',
 //  14 => 't>摆台</t><n>10寸梵高版画（白边）</n><p>0</p',
 //  15 => 't>摆台</t><n>6X8烤瓷摆台一副</n><p>0</p',
 //  16 => 't>摆台</t><n>6x12寸韩国烤瓷</n><p>0</p',
 //  17 => 't>摆台</t><n>8寸梵高</n><p>0</p',
 //  18 => 't>单片</t><n>7寸单片</n><p>0</p',
 //  19 => 't>单片</t><n>5寸单片</n><p>0</p',
 //  20 => 't>单片</t><n>6寸单片</n><p>0</p',
 //  21 => 't>单片</t><n>8寸单片</n><p>0</p',

	// 现产品处理数组函数2 2016-05-18
	public function list_arr2($arr){
		//将每一条产品记录值拆分成更细的记录，同类型的记录分散在各处
		$array = array();
		foreach($arr as $k => $v){
			$array[$k]['category'] = strchr(ltrim($v,'t>'),'</t>',TRUE);
			$array[$k]['pro_name'] = ltrim(strchr(strchr($v,'<n>'),'</n>',TRUE),'<n>');
			$array[$k]['pro_price'] = rtrim(ltrim(strchr($v,'<p>'),'<p>'),'</p>');
		}
		// 新建数组，归类产品
		$pro_array = array();
		foreach($array as $k => $v){
			// 第一次循环时，直接添加到新数组中
			if($k == 0){
				$pro_array[$k]['name'] = $v['category'];
				$pro_array[$k]['productList'][$k]['pro_name'] = $v['pro_name'];
				$pro_array[$k]['productList'][$k]['pro_price'] = $v['pro_price'];
				$pro_array[$k]['nums'] = 1;
			}else{ //之后，匹配类目是否已存在新数组中。 不存在，则新建新数组元素；存在，则在旧元素下加子元素。 注意下标的控制。
				$pipol = 0;
				$nums = 0;
				foreach($pro_array as $key => $value){
					if($value['name'] != $v['category']){
						$pipol++;
					}else{
						$nums = $value['nums'];
						$mums = $key;
						break;
					}
				}
				if($pipol < sizeof($pro_array)){
					$pro_array[$mums]['productList'][$nums]['pro_name'] = $v['pro_name'];
					$pro_array[$mums]['productList'][$nums]['pro_price'] = $v['pro_price'];
					$pro_array[$mums]['nums']++;
				}else{				
					$pro_array[$pipol]['name'] = $v['category'];
					$pro_array[$pipol]['productList'][0]['pro_name'] = $v['pro_name'];
					$pro_array[$pipol]['productList'][0]['pro_price'] = $v['pro_price'];
					$pro_array[$pipol]['nums'] = 1;
				}
			}
		}
		return $pro_array;
	}
}
?>
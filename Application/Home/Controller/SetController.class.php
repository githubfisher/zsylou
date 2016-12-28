<?php
namespace Home\Controller;
use Think\Controller;
class SetController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//查询套系
	public function query_set(){
		logger('查询套系--------------------------SET----------------------------------->开始'); //http://119.29.6.140:8808/<aa>14</aa><yy>20150101080</yy>
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 14,
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
		// logger('XML:'.$result); //debug   <?xml version='1.0'!><recipe><l><t>婚纱照</t><n>3999婚纱照</n><p>3999</p></l><l><t>全家福</t><n>668全家福</n><p>668</p></l></recipe>
		//<?xml version='1.0'><recipe><l><t>宝宝照</t><n>合作医院百天照</n><p>0</p></l><l><t>宝宝照</t><n>合作医院满月照赠送</n><p>0</p></l><l><t>宝宝照</t><n>合作医院周岁</n><p>0</p></l><l><t>成长套系</t><n>白色天使</n><p>2680</p></l><l><t>成长套系</t><n>时尚无碳环保套箱</n><p>3680</p></l><l><t>成长套系</t><n>自定义套系</n><p>0</p></l></recipe> 
		if(strlen($result) < 39){
			logger("------------------>没有套系信息<--------------------\n");
			$data = array(
				'code' => 0,
				'message' => '没有套系信息'
			);
			exit(json_encode($data));
		}else{
			logger('------------------>存在套系信息<-------------------');
			$string = rtrim($result,'</l></recipe>');
			// logger('截取字符串:'.$string); //debug
			$string = substr($string,33);
			// logger('截取字符串:'.$string); //debug
			$arra = explode('</l><l><',$string);
			// $stra = var_export($arra,TRUE); //debug
			// logger('截取数组:'.$stra); //debug
			// die; //debug
			//处理数组
			$result = $this->str_set3($arra); 
			$data = array(
				'code' => 1,
				'message' => '套系信息返回成功',
				'result' => $result
			);
			logger("------------------>套系信息返回成功<--------------------\n");
			exit(json_encode($data));
		}
	}
	//原处理数组函数1
	public function arr_set($arr){
		$array = array();
		foreach($arr as $k => $v){
		// 0 => 't>婚纱照</t><n>3999婚纱照</n><p>3999</p>',
 		// 1 => 't>全家福</t><n>668全家福</n><p>668',
			$array[$k] = $this->str_set($v);
		}
		return $array;
	}
	//原处理数组函数2
	public function str_set($str){
		$array = array();
		// 假想:  t>婚纱照</t>   <n>3999婚纱照</n><p>3999  <n>668全家福</n><p>668   <n>668全家福</n><p>668   
		$array['set'] = strchr(ltrim($str,'t>'),'</t>',TRUE);
		// logger('set:'.$array['set']);            //debug
		$string = ltrim(strchr($str,'</t>'),'</t>');
		// logger('string:'.$string);           //debug
		$arr = explode('</p>',$string);
		// $stra = var_export($arr,TRUE);       //debug
		// logger('arr:'.$stra);              //debug
		foreach($arr as $k => $v){
			if(strlen($v) > 3){
				$array['child_set'][$k]['set_name'] = strchr(substr($v,3),'</n>',TRUE);
				$array['child_set'][$k]['set_price'] = ltrim(strchr($v,'<p>'),'<p>');
			}
		}
		// logger('数组:'.var_export($array,TRUE)); //debug
		return $array;
	}
	// 0 => 't>宝宝照</t><n>合作医院百天照</n><p>0</p>',
  	// 1 => 't>宝宝照</t><n>合作医院满月照赠送</n><p>0</p>',
  	// 2 => 't>宝宝照</t><n>合作医院周岁</n><p>0</p>',
  	// 3 => 't>成长套系</t><n>白色天使</n><p>2680</p>',
  	// 4 => 't>成长套系</t><n>时尚无碳环保套箱</n><p>3680</p>',
  	// 5 => 't>成长套系</t><n>自定义套系</n><p>0',

	// 原处理函数3 2016-05-18
	public function str_set2($arr){
		$array = array('1000' => array('set'=>'0000'));
		foreach($arr as $k => $v){
			$set = strchr(ltrim($v,'t>'),'</t>',TRUE);
			$child_set_name = ltrim(strchr(strchr($v,'<n>'),'</n>',TRUE),'<n>');
			$child_set_price = rtrim(ltrim(strchr($v,'<p>'),'<p>'),'</p>');
			logger('总套系：'.$set.'。 子套系：'.$child_set_name.'. 子套系价格：'.$child_set_price); //debug
			$i = 0;
			foreach($array as $key => $value){
				logger('开始'); //debug
				if($set == $value['set']){
					if($i == 0){
						$array[$key]['child_set'][]['set_name'] = $child_set_name;
						$array[$key]['child_set'][]['set_price'] = $child_set_price;
						$i++;
					}
				}else{
					if($i == 0){
						$array[]['set'] = $set;
						$array[]['child_set'][]['set_name'] = $child_set_name;
						$array[]['child_set'][]['set_price'] = $child_set_price;
						$i++;
					}	
				}
			}
		}
		return $array;
	}
	//现处理函数3 2016-05-18下午
	public function str_set3($arr){
		//先将每一条记录的各信息提取出来，组成更细分的数组
		$array = array();
		foreach($arr as $k => $v){
			$array[$k]['set'] = strchr(ltrim($v,'t>'),'</t>',TRUE);
			$array[$k]['set_name'] = ltrim(strchr(strchr($v,'<n>'),'</n>',TRUE),'<n>');
			$array[$k]['set_price'] = rtrim(ltrim(strchr($v,'<p>'),'<p>'),'</p>');
		}
		$set_array = array();
		foreach($array as $k => $v){
			if($k == 0){
				$set_array[$k]['set'] = $v['set'];
				$set_array[$k]['child_set'][$k]['set_name'] = $v['set_name'];
				$set_array[$k]['child_set'][$k]['set_price'] = $v['set_price'];
				$set_array[$k]['nums'] = 1;
			}else{
				$pipol = 0;
				$nums = 0;
				foreach($set_array as $key => $value){
					if($value['set'] != $v['set']){
						$pipol++;
					}else{
						$nums = $value['nums'];
						$mums = $key;
						break;
					}
				}
				if($pipol < sizeof($set_array)){
					$set_array[$mums]['child_set'][$nums]['set_name'] = $v['set_name'];
					$set_array[$mums]['child_set'][$nums]['set_price'] = $v['set_price'];
					$set_array[$mums]['nums']++;
				}else{				
					$set_array[$pipol]['set'] = $v['set'];
					$set_array[$pipol]['child_set'][0]['set_name'] = $v['set_name'];
					$set_array[$pipol]['child_set'][0]['set_price'] = $v['set_price'];
					$set_array[$pipol]['nums'] = 1;
				}
			}
		}
		return $set_array;
	}
}
?>
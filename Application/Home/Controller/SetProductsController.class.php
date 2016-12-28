<?php
namespace Home\Controller;
use Think\Controller;
class SetProductsController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//查询套系
	public function query_set_products(){
		logger('查询套系下产品--------------------------SET--PRODUCTS----------------------------------->开始'); //http://119.29.6.140:8808/<aa>14</aa><yy>20150101080</yy>
		$post = I();
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 16,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin);
		$xml = strchr($xml,'<uu>',TRUE);
		//如果查询某一套系，则需要name参数
		if($post['name']){
			$xml .= '<name>'.$post['name'].'</name>';
		}
		//强制转码 由utf8转成gbk
		$xml = mb_convert_encoding($xml,'gbk','utf8');
		// logger('查询xml:'.$xml."--->"); //debug
		$url = session('url');
		$getxml = getXML($url,$xml);
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
		// logger('XML:'.$result); //debug
		if(strlen($result) < 39){
			logger("------------------>没有套系下产品信息<--------------------\n");
			$data = array(
				'code' => 1,
				'message' => '该套系下没有包含产品',
				'result' => array()
			);
			exit(json_encode($data));
		}else{
			logger('------------------>存在套系下产品信息<-------------------');
			$string = rtrim($result,'</l></recipe>');
			// logger('截取字符串:'.$string); //debug
			$string = substr($string,33);
			// logger('截取字符串:'.$string); //debug
			$arra = explode('</l><l><',$string);
			// $stra = var_export($arra,TRUE); //debug
			// logger('截取数组:'.$stra); //debug
			// die; //debug
			// 写一个假的数组，供测试使用
			// $arra = array (
			//   0 => 'n>合作医院满月照赠送</n><p></p><c>1</c><j></j>',
			//   1 => 'n>合作医院满月照赠送</n><p>10寸水晶</p><c>1</c><j>160</j>',
			//   2 => 'n>合作医院百天照</n><p>掌中宝相册</p><c>1</c><j>128</j>',
			//   3 => 'n>合作医院百天照</n><p>10X10韩版册</p><c>1</c><j>898</j>',
			//   4 => 'n>合作医院百天照</n><p>10X10亚米其相册</p><c>1</c><j>1680</j>',
			//   5 => 'n>合作医院百天照</n><p>卡地亚10X10英寸方形册</p><c>1</c><j>2680</j>',
			//   6 => 'n>合作医院百天照</n><p>10寸水晶</p><c>1</c><j>160</j>',
			//   7 => 'n>合作医院百天照</n><p>时尚无碳环保摆台</p><c>1</c><j>268</j>',
			//   8 => 'n>合作医院百天照</n><p>钱包卡</p><c>1</c><j>30</j>',
			//   9 => 'n>合作医院百天照</n><p>8x8花边烤瓷</p><c>1</c><j>198</j>',
			//   10 => 'n>合作医院周岁</n><p>20寸单片</p><c>1</c><j>160</j>',
			//   11 => 'n>五彩记忆</n><p>钥匙扣</p><c>1</c><j>20</j>',
			//   12 => 'n>五彩记忆</n><p>掌中宝相册</p><c>1</c><j>128</j>',
			//   13 => 'n>五彩记忆</n><p>时尚精美抱枕</p><c>1</c><j>216</j>',
			//   14 => 'n>五彩记忆</n><p>小号旋转</p><c>1</c><j>298</j>',
			//   15 => 'n>白色天使</n><p>8X8琉璃册</p><c>1</c><j>698</j>',
			//   16 => 'n>白色天使</n><p>皮质3格框彩色</p><c>1</c><j>588</j>',
			//   17 => 'n>白色天使</n><p>8寸水晶</p><c>1</c><j>98</j>',
			//   18 => 'n>白色天使</n><p>钱包卡</p><c>1</c><j>30</j>',
			//   19 => 'n>白色天使</n><p>会员卡</p><c>1</c><j>10</j>',
			//   20 => 'n>白色天使</n><p>掌中宝相册</p><c>1</c><j>128</j>',
			//   21 => 'n>白色天使</n><p>7寸单片</p><c>1</c><j>15</j>',
			//   22 => 'n>白色天使</n><p>10寸单片</p><c>1</c><j>35</j>',
			//   23 => 'n>白色天使</n><p>百天</p><c>1</c><j>0</j>',
			//   24 => 'n>时尚无碳环保套箱</n><p>时尚无碳环保套箱</p><c>1</c><j>698</j>',
			//   25 => 'n>时尚无碳环保套箱</n><p>100X50双层亚米奇烤瓷</p><c>1</c><j>2388</j>',
			//   26 => 'n>时尚无碳环保套箱</n><p>8x8花边烤瓷</p><c>1</c><j>198</j>',
			//   27 => 'n>时尚无碳环保套箱</n><p>8X12花边烤瓷</p><c>1</c><j>288</j>',
			//   28 => 'n>时尚无碳环保套箱</n><p>小号旋转水晶</p><c>1</c><j>360</j',
			// ) ;
			//处理数组
			$result = $this->arr_set_products($arra); 
			$data = array(
				'code' => 1,
				'message' => '套系下产品信息返回成功',
				'result' => $result
			);
			// 查询单一套系的只返回其下的 productList
			if($post['name']){
				$data = array(
					'code' => 1,
					'message' => '该套系下产品信息返回成功',
					'result' => $result[0]['productList']
				);
			}
			logger("------------------>套系下产品信息返回成功<--------------------\n");
			exit(json_encode($data));
		}
	}
	// array (
	//   0 => 'n>合作医院满月照赠送</n><p></p><c>1</c><j></j>',
	//   1 => 'n>合作医院满月照赠送</n><p>10寸水晶</p><c>1</c><j>160</j>',
	//   2 => 'n>合作医院百天照</n><p>掌中宝相册</p><c>1</c><j>128</j>',
	//   3 => 'n>合作医院百天照</n><p>10X10韩版册</p><c>1</c><j>898</j>',
	//   4 => 'n>合作医院百天照</n><p>10X10亚米其相册</p><c>1</c><j>1680</j>',
	//   5 => 'n>合作医院百天照</n><p>卡地亚10X10英寸方形册</p><c>1</c><j>2680</j>',
	//   6 => 'n>合作医院百天照</n><p>10寸水晶</p><c>1</c><j>160</j>',
	//   7 => 'n>合作医院百天照</n><p>时尚无碳环保摆台</p><c>1</c><j>268</j>',
	//   8 => 'n>合作医院百天照</n><p>钱包卡</p><c>1</c><j>30</j>',
	//   9 => 'n>合作医院百天照</n><p>8x8花边烤瓷</p><c>1</c><j>198</j>',
	//   10 => 'n>合作医院周岁</n><p>20寸单片</p><c>1</c><j>160</j>',
	//   11 => 'n>五彩记忆</n><p>钥匙扣</p><c>1</c><j>20</j>',
	//   12 => 'n>五彩记忆</n><p>掌中宝相册</p><c>1</c><j>128</j>',
	//   13 => 'n>五彩记忆</n><p>时尚精美抱枕</p><c>1</c><j>216</j>',
	//处理函数1 2016-05-18晚
	public function arr_set_products($arr){
		//将每一条套系下产品记录值拆分成更细的记录，同类型的记录分散在各处
		$array = array();
		foreach($arr as $k => $v){
			$array[$k]['set'] = strchr(ltrim($v,'n>'),'</n>',TRUE);
			$array[$k]['pro_name'] = ltrim(strchr(strchr($v,'<p>'),'</p>',TRUE),'<p>');
			$array[$k]['pro_num'] = ltrim(strchr(strchr($v,'<c>'),'</c>',TRUE),'<c>');
			$array[$k]['pro_price'] = rtrim(ltrim(strchr($v,'<j>'),'<j>'),'</j>');
		}
		// logger('拆分数组：'.var_export($array,TRUE)); //debug
		// 新建数组，归类套系下产品
		$set_pro_array = array();
		foreach($array as $k => $v){
			// 第一次循环时，直接添加到新数组中
			if($k == 0){
				$set_pro_array[$k]['set'] = $v['set'];
				$set_pro_array[$k]['productList'][$k]['pro_name'] = $v['pro_name'];
				$set_pro_array[$k]['productList'][$k]['pro_num'] = $v['pro_num'];
				$set_pro_array[$k]['productList'][$k]['pro_price'] = $v['pro_price'];
				$set_pro_array[$k]['nums'] = 1;
			}else{ //之后，匹配类目是否已存在新数组中。 不存在，则新建新数组元素；存在，则在旧元素下加子元素。 注意下标的控制。
				$pipol = 0;
				$nums = 0;
				foreach($set_pro_array as $key => $value){
					if($value['set'] != $v['set']){
						$pipol++;
					}else{
						$nums = $value['nums'];
						$mums = $key;
						break;
					}
				}
				if($pipol < sizeof($set_pro_array)){
					$set_pro_array[$mums]['productList'][$nums]['pro_name'] = $v['pro_name'];
					$set_pro_array[$mums]['productList'][$nums]['pro_num'] = $v['pro_num'];
					$set_pro_array[$mums]['productList'][$nums]['pro_price'] = $v['pro_price'];
					$set_pro_array[$mums]['nums']++;
				}else{				
					$set_pro_array[$pipol]['set'] = $v['set'];
					$set_pro_array[$pipol]['productList'][0]['pro_name'] = $v['pro_name'];
					$set_pro_array[$pipol]['productList'][0]['pro_num'] = $v['pro_num'];
					$set_pro_array[$pipol]['productList'][0]['pro_price'] = $v['pro_price'];
					$set_pro_array[$pipol]['nums'] = 1;
				}
			}
		}
		return $set_pro_array;
	}
}
?>
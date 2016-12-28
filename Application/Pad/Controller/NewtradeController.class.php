<?php
namespace Pad\Controller;
use Think\Controller;
class NewtradeController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//开单 //http://119.29.6.140:8808/<aa>12</aa><yy>20150101080101888888</yy><n>柯涛</n><p1>张三</p1><p2>18666532220</p2><p3>699宝宝照</p3><p4>699</p4><p5>备注</p5>
	public function newtrade(){
		logger('PAD端--@@@@@@@@@@@@@@@@@@@@@-----开单----开始-----@@@@@@@@@@@@@@@@@@@@@@');
		$post = I();
		// logger('参数:'.var_export($post,TRUE)); //debug
		$guest = $post['guest'];
		$mobile = $post['mobile'];
		$set = $post['set'];
		$price = $post['price'];
		$msg = $post['msg'];
		$spot = $post['spot'];
		//增加套系下产品，产品数量，产品加急属性,二维数组 JSON 2016-05-19
		$productlist = $post['productlist'];
		//数据齐全,连接远程服务器
		if($guest && $mobile && $set &&$price && $productlist && $spot){
			//先判断是否存在session中的dogid和url
			if(!session('dogid') || !session('url')){
				logger('PAD端--session不存在完整店铺信息!');
				$store = D('store');
				$where = array(
					'id' => session('sid'),
					'store_simple_name' => session('store_simple_name')
				);
				$the_store = $store->where($where)->find();
				if($the_store){	
					session('dogid',$the_store['dogid']);
					$url = 'http://' . $the_store['ip'] . ':' . $the_store['port'] . '/';
					// logger('URL:'.$url); //debug
					session('url',$url); //将对应远程影楼服务器地址，保存至session
				}else{
					$data = array(
						'code' => 0,
						'message' => '获取店铺信息出错!'
					);
					exit(json_encode($data));
				}
			}
			//连接远程服务器 key钥匙
			$admin = array(
				'operation' => 12,
				'dogid' => session('dogid')
			);
			$xml = transXML($admin);
			$xml = strchr($xml,'<uu>',TRUE);
			$xml .= '<n>'.session('admin_name').'</n>'.'<p1>'.$guest.'</p1><p2>'.$mobile.'</p2><p3>'.$set.'</p3><p4>'.$price.'</p4><p5>'.$msg.'</p5>';
			//遍历景点数组
			$spots = stripslashes(html_entity_decode($spot)); //2016-07-19
			$spots = json_decode($spots,TRUE);
			foreach($spots as $s){
				$xml .= '<jd>'.$s.'</jd>';
			}
			//遍历productlist ,将产品信息取出
			// logger('PAD端--ProductList:'.$productlist); //debug
			//$productlist = str_replace('&quot;','"',$productlist);  //转换json字符串中的转义字符 ，方法一
			$productlist = stripslashes(html_entity_decode($productlist)); //转换json字符串中的转义字符 ，方法二
			// echo "<pre>"; //debug
			$products = json_decode($productlist,TRUE);
			// logger('PAD端--开单产品-Products:'.var_export($products,TRUE)); //debug
			//新建产品名字符串，便于存入数据库
			$products_str = ''; //不如存入json串
			foreach($products as $k => $v){
				// 是否加急
				if($v['isUrgent'] == 1){
					$i = 0; //计数
					while($i < $v['number']){
						//处理加急日期，中文年月日 统一换成 2016-05-31形式
						$urgent_date = trim(chtimetostr($v['urgentTime']),' ');
						$xml .= '<p>'.$v['pro_name'].'<u>是</u><d>'.$urgent_date.'</d></p>';
						$i++;
					}
				}else{
					$i = 0; //计数
					while($i < $v['number']){
						$xml .= '<p>'.$v['pro_name'].'</p>';
						$i++;
					}
				}
			}
			$url = session('url');
			// logger('PAD端--开单请求链接:'.$url.$xml); //debug
			// 强制转码 由utf8转成gbk
			$xml = mb_convert_encoding($xml,'gbk','utf8');
			$getxml = getXML($url,$xml);
			$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
			// logger('PAD端--开单返回值XML:'.$result); //debug 
			$keyword = substr($result,32,2);
			// logger('PAD端--'.$keyword); //debug
			$tid = strchr($result,'<id>');
			$tid = strchr($tid,'</id>',TRUE);
			$tid = ltrim($tid,'<id>');
			if($keyword == 'ok'){
				//开单数据写入数据库
				logger('PAD端--开单数据写入本地数据库-->');
				$new_trade = D('new_trade');
				$trade_data = array(
					'uid' => session('uid'),
					'sid' => session('sid'),
					'guest' => $guest,
					'phone' => $mobile,
					'set_name' => $set,
					'set_price' => $price,
					'message' => $msg,
					'new_time' => time(),
					'dept' => session('dept'),
					'store_admin' => session('admin_name'),
					'product_id' => $productlist,
					'trade_id' => $tid, //订单id
					'source' => 1, //订单来源
				);
				// logger('PAD端--添加数组：'.var_export($trade_data,TRUE)); //debug
				$tradein = $new_trade->add($trade_data);
				// $trade_res = $new_trade->getLastsql(); //debug
				// logger('PAD端--SQL:'.$trade_res); //debug
				if($tradein){
					logger('PAD端--开单数据写入本地数据库-->成功,ID:'.$tradein);
				}else{
					logger('PAD端--开单数据写入-->失败!请管理员查找错误原因!');
				}
				logger('PAD端--@@@@@@@@@@@@@@@@@@@@@-----开单----成功-----@@@@@@@@@@@@@@@@@@@@@@'."\n");
				//回复客户端
				$data = array(
					'code' => 1,
					'message' => '开单成功',
					'trade_id' => $tid
				);
				exit(json_encode($data));
			}else{
				logger('PAD端--@@@@@@@@@@@@@@@@@@@@@-----开单----失败-----@@@@@@@@@@@@@@@@@@@@@@'."\n");
				$data = array(
					'code' => 0,
					'message' => '开单失败'
				);
				exit(json_encode($data));
			}
		}else{
			logger('PAD端--@@@@@@@@@@@@@@@@@@@@@-----开单----参数不全-----@@@@@@@@@@@@@@@@@@@@@@'."\n");
			$data = array(
				'code' => 2,
				'message' => '提交信息不全,请重新提交!'
			);
			exit(json_encode($data));
		}
	}
}
?>
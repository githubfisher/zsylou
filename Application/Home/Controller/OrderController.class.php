<?php
namespace Home\Controller;
use Think\Controller;
class OrderController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//预约(按订单号--肖工意愿)    //http://119.29.6.140:8808/<aa>13</aa><yy>20150101080101888888</yy><id>20160506-001</id><type>1</type><date>2016-01-22</date><time>10:00</time><n>柯涛</n>
	public function order_id(){
		logger('%%%%%%%%%%%%%%%%%%%%%%%-----预约--ID--开始-----%%%%%%%%%%%%%%%%%%%%%%%%');
		$post = I();
		$tradeid = $post['tradeid'];
		$type = $post['type'];
		$date = $post['date'];
		$time = $post['time'];
		//数据齐全,连接远程服务器
		if($type && $date && $tradeid){
			//连接远程服务器 key钥匙
			$admin = array(
				'operation' => 13,
				'dogid' => session('dogid')
			);
			$xml = transXML($admin);
			$xml = strchr($xml,'<uu>',TRUE);
			$xml .= '<id>'.$tradeid.'</id><type>'.$type.'</type><date>'.$date.'</date>'.'<time>'.$time.'</time><n>'.session('admin_name').'</n>';
			//强制转码 由utf8转成gbk
			$xml = mb_convert_encoding($xml,'gbk','utf8');
			// logger('查询xml:'.$xml."--->"); //debug
			$url = session('url');
			$getxml = getXML($url,$xml);
			$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
			// logger('XML:'.$result); //debug 
			$keyword = substr($result,32,2);
			// logger($keyword); //debug
			if($keyword == 'ok'){
				//预约信息写入本地数据库
				logger('预约信息写入本地数据库-->');
				$new_order = D('new_order');
				//数据准备
				$date = chtimetostr($date);
				$time = chtimetostr($time);
				$order_data = array(
					'uid' => session('uid'),
					//'suid' => session('suid'),
					'sid' => session('sid'),
					'type' => $type,
					'trade_id' => $tradeid,
					'order_date' => strtotime($date),
					'order_time' => strtotime($time),
					'new_time' => time(),
					'dept' => session('dept'),
					'store_admin' => session('admin_nickname')
				);
				$orderin = $new_order->add($order_data);
				if($orderin){
					logger('预约信息写入本地数据库-->成功,ID:'.$tradein);
				}else{
					logger('预约信息写入本地数据库-->失败!请管理查找错误原因!');
				}
				logger('%%%%%%%%%%%%%%%%%%%%%%%-----预约----成功-----%%%%%%%%%%%%%%%%%%%%%%%'."\n");
				$data = array(
					'code' => 1,
					'message' => '预约成功'
				);
				exit(json_encode($data));
			}else{
				logger('%%%%%%%%%%%%%%%%%%%%%%%-----预约----失败-----%%%%%%%%%%%%%%%%%%%%%%%'."\n");
				$data = array(
					'code' => 0,
					'message' => '预约失败'
				);
				exit(json_encode($data));
			}
		}else{
			logger('%%%%%%%%%%%%%%%%%%%%%%%-----预约----无数据-----%%%%%%%%%%%%%%%%%%%%%%%'."\n");
			$data = array(
				'code' => 2,
				'message' => '提交信息不全,请重新提交!'
			);
			exit(json_encode($data));
		}
	}
	//预约(按姓名和手机号--乐兔设计)    //http://119.29.6.140:8808/<aa>13</aa><yy>20150101080101888888</yy><id>20160506-001</id><type>1</type><date>2016-01-22</date><time>10:00</time><n>柯涛</n>
	public function order_name(){
		logger('%%%%%%%%%%%%%%%%%%%%%%%-----预约--NAME--开始-----%%%%%%%%%%%%%%%%%%%%%%%%');
		$post = I();
		$guest = $post['guest'];
		$mobile = $post['mobile'];
		$type = $post['type'];
		$date = $post['date'];
		$time = $post['time'];
		//数据齐全,连接远程服务器  先查询订单,后预约
		if($type && $date && $name && $mobile){
			//先按照手机号或姓名查询订单
			$query = A('Query');
			// 查询服务器地址
			$url = session('url');      //http://119.29.6.140:8808/<aa>1</aa><yy>20150101080101888888</yy><name>zs</name>
			$detailinfo['dogid'] = session('dogid'); 
			//先用手机号作为查询条件
			$detailinfo['detail'] = $mobile; 
			$xml = $query->trans_name_XML($detailinfo);
			$result = getXML($url,$xml);
			//转码 很重要
			$result = mb_convert_encoding($result, 'UTF-8', 'GB2312');
			// logger('xml:'.$result);  //打印得到的XML字符串
			// 如果返回值少于多少字节，则没有查找到相关订单，直接返回错误信息
			if(strlen($result) < 40){
				$arr = array('code' => 0,'message' => '未查找到相关订单信息！');
	    		logger("查询结果： 未查找到相关订单，查询失败！");
	    		logger("{查询完成！}\n");
	    		exit(json_encode($arr));
			}
			$multi = substr($result,36,1);
			//multi字段为1,意味着多订单,则不能有针对的预约
			if($multi == 1){
				logger('按照手机号查询为多订单,错误返回!');
				$data = array(
					'code' => 3,
					'message' => '查询结果为多订单,请查证'
				);
				exit(json_encode($data));
			}else{
				// echo "die!"; //debug
				$str = substr($result,31);
				$str = rtrim($str,'></recipe>');
				$arra = explode("><l",$str);
				// $str2 = var_export($arra,TRUE); //打印数组a到变量
				$arrb = $query->detailarr($arra);
				logger("{单订单--订单详情--查询完成！}\n");
				//这里需要肖工修改接口,将单个订单的返回值中增加订单号
				//获得单个订单的订单号-->去预约
				$tradeid = $arrb['tradeid'];
			}
			//预约操作
			//连接远程服务器 key钥匙
			$admin = array(
				'operation' => 13,
				'dogid' => session('dogid')
			);
			$xml = transXML($admin);
			$xml = strchr($xml,'<uu>',TRUE);
			$xml .= '<id>'.$tradeid.'</id><type>'.$type.'</type><date>'.$date.'</date>'.'<time>'.$time.'</time><n>'.session('admin_name').'</n>';
			logger('查询xml:'.$xml."--->"); //debug
			$url = session('url');
			$getxml = getXML($url,$xml);
			$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
			logger('XML:'.$result); //debug 
			$keyword = substr($result,32,2);
			logger($keyword); //debug
			if($keyword == 'ok'){
				logger('%%%%%%%%%%%%%%%%%%%%%%%-----预约----成功-----%%%%%%%%%%%%%%%%%%%%%%%'."\n");
				$data = array(
					'code' => 1,
					'message' => '预约成功'
				);
				exit(json_encode($data));
			}else{
				logger('%%%%%%%%%%%%%%%%%%%%%%%-----预约----失败-----%%%%%%%%%%%%%%%%%%%%%%%'."\n");
				$data = array(
					'code' => 0,
					'message' => '预约失败'
				);
				exit(json_encode($data));
			}
		}else{
			logger('%%%%%%%%%%%%%%%%%%%%%%%-----预约----无数据-----%%%%%%%%%%%%%%%%%%%%%%%'."\n");
			$data = array(
				'code' => 2,
				'message' => '提交信息不全,请重新提交!'
			);
			exit(json_encode($data));
		}
	}
}
?>
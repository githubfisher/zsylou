<?php
namespace Home\Controller;
use Think\Controller;
class PayTradeController extends Controller{
	public function __initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//支付 支付类型 1微信 2支付宝 3现金 4POS机刷卡
	public function pay(){
		logger('$$$$$$$$$$$$$$$$$$$$$-$$-订单支付-&&-$$$$$$$$$$$$$$$$$$$$$$-->');
		$post = I();
		// logger('携带参数:'.var_export($post,TRUE)); //debug
		$money = $post['money'];
		$type = $post['type'];
		$id = $post['trade_id'];
		if($id && $type && $money){
			// 转化支付方式
			$pay_type = array(
				'1' => '微信',
				'2' => '支付宝',
				'3' => '现金',
				'4' => 'POS机刷卡'
			);
			//连接远程服务器 key钥匙
			$admin = array(
				'operation' => 17, //收款操作ID
				'dogid' => session('dogid')
			);
			$xml = transXML($admin);
			$xml = strchr($xml,'<uu>',TRUE);
			$xml .= '<n>'.session('admin_name').'</n>'.'<id>'.$id.'</id><money>'.$money.'</money><type>'.$pay_type[$type].'</type>';
			// logger('查询xml:'.$xml."--->"); //debug
			//强制转码 由utf8转成gbk
			$xml = mb_convert_encoding($xml,'gbk','utf8');
			$url = session('url');
			$getxml = getXML($url,$xml);
			$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
			// logger('支付返回值XML:'.$result); //debug 
			$keyword = substr($result,32,2);
			// logger($keyword); //debug
			if($keyword == 'ok'){
				//支付数据写入数据库
				logger('支付数据写入本地数据库-->');
				$trade = D('new_trade');
				$pay_data = array(
					'pay_money' => $money,
					'pay_type' => $type,
					'pay_time' => time(),
					'pay_admin' => session('uid')
				);
				logger('添加数组：'.var_export($pay_data,TRUE)); //debug
				$where = array(
					'trade_id' => $id,
					'sid' => session('sid')
				);
				$pay_in = $trade->where($where)->save($pay_data);
				$sql = $trade->getLastsql(); //debug
				logger('SQL:'.$sql); //debug
				if($pay_in){
					logger('支付数据写入本地数据库-->成功,订单ID:'.$tid);
				}else{
					logger('支付数据写入-->失败!请管理员查找错误原因!');
				}
				logger('$$$$$$$$$$$$$$$$$$$$$-$$-支付----成功-&&-$$$$$$$$$$$$$$$$$$$$$$'."\n");
				//回复客户端
				$data = array(
					'code' => 1,
					'message' => '支付成功'
				);
				exit(json_encode($data));
			}else{
				logger('$$$$$$$$$$$$$$$$$$$$$-$$-支付----失败-&&-$$$$$$$$$$$$$$$$$$$$$$'."\n");
				$data = array(
					'code' => 0,
					'message' => '开单失败'
				);
				exit(json_encode($data));
			}
		}else{
			logger('$$$$$$$$$$$$$$$$$$$$$-$$-支付----参数不全-&&-$$$$$$$$$$$$$$$$$$$$$$'."\n");
			$data = array(
				'code' => 2,
				'message' => '提交信息不全,请重新提交!'
			);
			exit(json_encode($data));
		}
	}
}
?>
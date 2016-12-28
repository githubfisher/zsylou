<?php
namespace Home\Controller;
use Think\Controller;
class SpotController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//获取景点 //http://119.29.6.140:8808/<aa>18</aa><yy>20150101080101888888</yy> //2016-07-19
	public function query(){
		logger('SSSSSSSSSSSSSSSSSSSSS-----查询----景点-----SSSSSSSSSSSSSSSSSSSSSS');
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 18,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin);
		$xml = strchr($xml,'<uu>',TRUE);
		$url = session('url');
		// logger('查询地址:'.$url.$xml."--->"); //debug
		//强制转码 由utf8转成gbk
		$xml = mb_convert_encoding($xml,'gbk','utf8');
		$getxml = getXML($url,$xml);
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
		// logger('开单返回值XML:'.$result); //debug 
		// <?xml version='1.0'!><recipe><n>棚景</n><n>公园景</n><n>海景</n></recipe> 
		if(strlen($result) < 39){
			logger('没有查询到景点信息');
			$data = array(
				'code' => 0,
				'message' => '没有景点信息'
			);
		}else{
			logger('查询到景点信息');
			$result = ltrim(strchr(strchr($result,'</recipe>',TRUE),'<recipe>'),'<recipe>');
			$result = rtrim(ltrim($result,'<n>'),'</n>');
			$spots = explode('</n><n>',$result);
			$data = array(
				'code' => 1,
				'message' => '景点信息返回成功!',
				'spot' => $spots
			);
		}
		exit(json_encode($data));
	}
}
?>
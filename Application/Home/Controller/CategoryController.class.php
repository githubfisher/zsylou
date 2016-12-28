<?php
namespace Home\Controller;
use Think\Controller;
class CategoryController extends Controller
{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function index(){

	}
	public function query(){
		logger('查询套系类别==》');
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 19,
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
		logger('查询套系类别结果：'.$result); //debug
		if(strlen($result) < 39){
			logger("--------->套系类别信息为空<---------\n");
			$data = array(
				'code' => 0,
				'message' => '没有套系类别信息！'
			);
		}else{
			logger('-------->存在套系信息<---------');
			$string = rtrim($result,'</n></recipe>');
			// logger('截取字符串:'.$string); //debug
			$string = substr($string,32);
			// logger('截取字符串:'.$string); //debug
			$arra = explode('</n><n>',$string);
			// logger('截取数组:'.var_export($arra,TRUE)); //debug
			//处理数组
			$data = array(
				'code' => 1,
				'message' => '套系类别信息返回成功！',
				'result' => $arra
			);
			logger("----->套系类别信息返回成功<-------\n");
		}
		exit(json_encode($data));
	}
}
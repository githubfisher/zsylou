<?php
namespace Home\Controller;
use Think\Controller;
class CustomerSourceController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	//客户来源 //http://192.168.0.128:8808/<aa>20</aa><yy>20150101080101888888</yy>
	public function query(){
		logger('查询影楼客户来源设置 ... ');
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 20,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin);
		$xml = strchr($xml,'<uu>',TRUE);
		$url = session('url');
		logger('客户来源查询请求链接:'.$url.$xml); //debug
		//强制转码 由utf8转成gbk
		$xml = mb_convert_encoding($xml,'gbk','utf8');
		$getxml = getXML($url,$xml);
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
		logger('客户来源查询返回值XML:'.$result); //debug
		// <!xml version='1.0'!><recipe><n>老客户介绍</n><n>广告</n><n>外展</n><n>省外</n><n>第一人民医院</n><n>九州医院</n><n>交通医院</n><n>第二人民医院</n><n>妇幼医院</n><n>妇女儿童医院</n><n>真爱医院</n><n>妇产医院</n><n>华府医院</n><n>自然进店</n><n>特约</n><n>会员生日</n><n>成长册</n><n>原定活动</n><n>女子医院</n><n>沾益县医院</n><n>老顾客</n></recipe>
		if(strpos($result,'needupdate')){
			logger('客户来源查询：手机端服务器需要更新！');
			$data = array(
				'code' => 0,
				'message' => '请更新手机端服务器！'
			);
			exit(json_encode($data));
		}
		$result = explode('</n><n>',rtrim(substr($result,32),'</n></recipe>'));
		// logger('客户来源列表：'.var_export($result,true)); //debug
		logger('客户来源信息返回成功！'."\n");
		$data = array(
			'code' => 1,
			'message' => '客户来源信息返回成功！',
			'result' => $result
		);
		exit(json_encode($data));
	}

}

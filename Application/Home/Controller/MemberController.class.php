<?php
namespace Home\Controller;
use Think\Controller;

class MemberController extends Controller
{
	public function __construct()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}

	public function query()
	{
		$post = I();
		// logger('查询会员卡信息 ...,条件：'.$post['info']);
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 21,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin);
		$xml = strchr($xml,'<uu>',TRUE);
		$xml .= '<name>'.$post['info'].'</name>';
		$url = session('url');
		logger('查询会员卡信息请求链接:'.$url.$xml); //debug
		//强制转码 由utf8转成gbk
		$xml = mb_convert_encoding($xml,'gbk','utf8');
		$getxml = getXML($url,$xml);
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
		// logger('会员卡查询返回值XML:'.$result); //debug
		// <?xml version='1.0'!><recipe><l1>6902505</l1><l2>刘朱槿</l2><l3>13769521353</l3><l4>1990.00</l4><l5></l5><l6>金卡会员</l6></recipe> 
		if(strpos($result,'<l1>')){
			$member = $this->getMemberList($result);
			// logger('会员查询结果:'.var_export($member,true)); // debug
			$data = array(
				'code' => 1,
				'message' => '会员信息成功返回！',
				'result' => $member
			);
		}else{
			$data = array(
				'code' => 0,
				'message' => '未查询到会员信息！'
			);
		}
		exit(json_encode($data));
	}

	private function getMemberList($string)
	{
		$array = explode('><l',$string);
		array_shift($array);
		// logger('info:'.var_export($array,true)); //debug
		$member = array();
		$n = 0;
		foreach($array as $k => $v){
			switch($k%6){
				case 0:
					$member[$n]['number'] = ltrim(strchr($v,'</l',true),'1>');
					break;
				case 1:
					$member[$n]['customer'] = ltrim(strchr($v,'</l',true),'2>');
					break;
				case 2:
					$member[$n]['phone'] = ltrim(strchr($v,'</l',true),'3>');
					break;
				case 3:
					// 积分 积分返回值都是软件中该值的10倍，故转INT类型后缩小10倍
					$member[$n]['credit'] = (int)ltrim(strchr($v,'</l',true),'4>')/10;
					break;
				case 4:
					// 余额 有时余额为空，转换成INT类型会转为0
					$member[$n]['balance'] = (int)ltrim(strchr($v,'</l',true),'5>');
					break;
				case 5:
					// 会员类型
					$member[$n]['type'] = ltrim(strchr($v,'</l',true),'6>');
					$n++;
					break;
				default:
					break;
			}
		} 
		return $member;
	}
}
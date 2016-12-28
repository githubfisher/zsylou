<?php
namespace Home\Controller;
use Think\Controller;
class RecordController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	// 查询业绩榜
	public function query(){
		logger('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 业绩查询（月）----开始---- <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
		$post = I();
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 11,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin); 
		$xml = strchr($xml,'<uu>',TRUE);
		//如果未指定则自动查询本月的订单
		if($post['date'] == '' || $post['date'] == NULL){
			$xml .= '<date>'.date('Y-m',time()).'</date>';
		}else{
			$xml .= '<date>'.$post['date'].'</date>';
		}
		//强制转码 由utf8转成gbk
		$xml = mb_convert_encoding($xml,'gbk','utf8');
		// logger('查询xml:'.$xml."--->"); //debug
		$url = session('url');
		$getxml = getXML($url,$xml);
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
		// logger('XML:'.$result); //debug
    	if(strlen($result) < 38){
    		logger("无业绩排名\n");
        	$data = array(
        		'code' => '0',
        		'message' => '无业绩排名信息'
        	);
        	logger('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 无业绩排名信息 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
        	exit(json_encode($data));
        }else{
        	$str_xml = substr(rtrim($result,'></recipe>'),40);
        	// logger('截取xml:'.$str_xml."\n"); //debug
        	$tra_arr = explode('<l',$str_xml);
        	// $tra_arr_str = var_export($tra_arr,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str."\n"); //debug
        	$tra_arr2 = $this->record_arr($tra_arr);
        	// $tra_arr_str2 = var_export($tra_arr2,TRUE);//debug
        	// logger('截取数组：'.$tra_arr_str2."\n"); //debug
        	// 查询头像
        	$app_user = D('app_user');
        	foreach($tra_arr2 as $key => $value){
        		foreach($value as $k => $v){
        			$where = array(
        				'sid' => session('sid'),
        				'realname' => $v['staff']
        			);
        			$record_user = $app_user->where($where)->field('head')->find();
        			if(strpos($record_user['head'],'Uploads/avatar/')){
        				$tra_arr2[$key][$k]['head'] = C('base_url').$record_user['head'];
        			}else{
        				$tra_arr2[$key][$k]['head'] = '';
        			}
        		}
        	}
        	$array = array(
        		'prepro',
        		'postpro',
        		'ptsell',
        		'mptsell',
        		'sptsell'
        	);
        	foreach($array as $k => $v){
        		if(!is_array($tra_arr2[$v])){
        			$tra_arr2[$v] = array();
        		}
        	}
        	// 查询头像 END
        	$data = array(
        		'code' => '1',
        		'message' => '业绩信息返回成功',
        		'result' => $tra_arr2
        	);
        	logger(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 业绩查询（月）----成功---- <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n");
        	exit(json_encode($data));
        }
	}
	//处理查询业绩返回xml数组
	public function record_arr($arr){
	  // 0 => '1>柯涛<i>3000</i></l1>前期：',
	  // 1 => '1>邓威<i>300</i></l1>后期：',
	  // 2 => '2>柯涛<i>12000</i></l2>后期：',
	  // 3 => '2>邓威<i>1200</i></l2>摄影二销：',
	  // 4 => '3>柯涛<i>3000</i></l3>摄影二销：',
	  // 5 => '3>邓威<i>300</i></l3>化妆二销：',
	  // 6 => '4>柯涛<i>4000</i></l4>化妆二销：',
	  // 7 => '4>邓威<i>400</i></l4>选片二销：',
	  // 8 => '5>柯涛<i>5000</i></l5>选片二销：',
	  // 9 => '5>邓威<i>500</i></l5',
		$array = array();
		$i = 0; $u = 0; $m = 0; $n = 0; $z = 0;
		foreach($arr as $k => $v){
			switch(substr($v,0,1)){
				case 1:
					$array['prepro'][$i]['staff'] = substr(strchr($v,'<i>',TRUE),2);
					$array['prepro'][$i]['record'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					$i++;
					break;
				case 2:
					$array['postpro'][$u]['staff'] = substr(strchr($v,'<i>',TRUE),2);
					$array['postpro'][$u]['record'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					$u++;
					break;
				case 3:
					$array['ptsell'][$m]['staff'] = substr(strchr($v,'<i>',TRUE),2);
					$array['ptsell'][$m]['record'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					$m++;
					break;
				case 4:
					$array['mptsell'][$n]['staff'] = substr(strchr($v,'<i>',TRUE),2);
					$array['mptsell'][$n]['record'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					$n++;
					break;
				case 5:
					$array['sptsell'][$z]['staff'] = substr(strchr($v,'<i>',TRUE),2);
					$array['sptsell'][$z]['record'] = ltrim(strchr(strchr($v,'</i>',TRUE),'<i>'),'<i>');
					$z++;
					break;
				default:
					break;
			}
		}
		return $array;
	}
}
?>
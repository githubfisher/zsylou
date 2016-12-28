<?php
namespace Home\Controller;
use Think\Controller;
class Bbscontroller extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
		//引入极光推送类库文件
		// Vendor('Jpush.Jpush');
		// Vendor('Jpush.core.DevicePayload');
		// Vendor('Jpush.core.JPushException');
		// Vendor('Jpush.core.PushPayload');
		// Vendor('Jpush.core.ReportPayload');
		// Vendor('Jpush.core.SchedulePayload');
		// Vendor('Easemob.Easemob');
		Vendor('Jpush2.Client');
		Vendor('Jpush2.core.Config');
		Vendor('Jpush2.core.JPushException');
		Vendor('Jpush2.core.APIConnectionException');
		Vendor('Jpush2.core.APIRequestException');
		Vendor('Jpush2.core.Http');
		Vendor('Jpush2.core.DevicePayload');
		Vendor('Jpush2.core.PushPayload');
		Vendor('Jpush2.core.ReportPayload');
		Vendor('Jpush2.core.SchedulePayload');
		Vendor('Easemob.Easemob');
	}
	public function index(){

	}
	public function publish(){
		logger('用户:'.session('uid').' 发布公告');
		//if(session('wtype') == 1){
			$post = I();
			if($post['title'] && $post['content'] && $post['type']){
				$bbs = D('bbs');
				$data = array(
					'title' => $post['title'],
					'content' => $post['content'],
					'type' => $post['type'],
					'create_time' => time(),
					'uid' => session('uid'),
					'sid' => session('sid')
				);
				//判断是否置顶
				if($post['top'] == 1){
					//查看是否已有置顶的公告
					$where = array(
							'top' => 1
						);
					$top_result = $bbs->where($where)->find();
					//如果已有置顶公告,则代替原来的置顶消息,原来的置顶消息状态改为普通公告
					if($top_result){
						//原来的消息变为普通公告
						$notop = array(
							'top' => 0
						);
						$notop_result = $bbs->where($where)->save($notop);
						if($notop_result){
							$data['top'] = 1;
							$result = $bbs->add($data);
						}else{
							logger("删除旧的置顶消息错误!\n");
							$data = array(
								'code' => 3,
								'message' => '置顶消息冲突,请重新提交公告!'
							);
							exit(json_encode($data));
						}
						
					}else{ //否则将其添加为置顶消息
						$data['top'] = 1;
						$result = $bbs->add($data);
					}
				}else{ //正常发公告(不置顶)
					$result = $bbs->add($data);
				}
				if($result){
					logger("发布公告成功 \n");
					//Jpush推送
					//数据准备
					$push_data = array(
						'title' => $post['title'],
						'content' => $post['content']
					);
					foreach($push_data as $k => $v){
						$strlong = 54;
						if($k == 'title'){
							$strlong = 22;
						}
						if(strlen($v) > $strlong){
							$push_data[$k] = mb_substr($v,0,$strlong,'utf8').'...';
						}
					}
					$msg = array(
						'platform' => 'all',
						'tag' => array('0' => session('sid')), //按照Tag推送本店铺的公告
						'msg' => array(
							'content' => $push_data['content'],
							'title' => $push_data['title'],
							'category' => '',
							'message' => array(
								'action' => 1,
								'type' => '',
								'details' => array()
							)
						)
					);
					logger('jpush广播数组:'.var_export($msg,TRUE)); //debug
					$j_result = Jboardcast_Tag($msg);
					logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------');
					//回复客户端
					$data = array(
						'code' => 1,
						'message' => '发布公告成功'
					);
					exit(json_encode($data));
				}else{
					logger("发布公告失败 \n");
					$data = array(
						'code' => 0,
						'message' => '发布公告失败'
					);
					exit(json_encode($data));
				}
			}else{
				logger("公告信息不全，发布失败 \n");
				$data = array(
					'code' => 2,
					'message' => '公告信息不全，发布失败'
				);
				exit(json_encode($data));
			}
		// }else{
		// 	logger('员工无权限发布'."\n");
		// 	$data = array(
		// 		'code' => 4,
		// 		'message' => '员工账号无权限'
		// 	);
		// 	exit(json_encode($data));
		// }
	}
	public function query(){
		$post = I();
		$page = $post['page'];
		logger('用户：'.session('uid').'查询公告');
		$bbs = D('bbs');
		$sid = session('sid');
		$where = array(
			'sid' => $sid
		);
		$result = $bbs->where($where)->order('top desc,create_time desc')->page($page.',5')->select();
		if($result){
			logger("返回公告成功\n");
			//查询发布者 昵称和真实姓名
			$app_user = D('app_user');
			foreach($result as $k => $v){
				$where = array(
					'uid' => $v['uid']
				);
				$publish_user = $app_user->where($where)->find();
				if($publish_user){
					$result[$k]['nickname'] = $publish_user['nickname'];
					$result[$k]['realname'] = $publish_user['realname'];
				}else{
					$result[$k]['nickname'] = '';
					$result[$k]['realname'] = '';
				}
			}
			//查询发布者 昵称和真实姓名 END
			$data = array(
				'result' => $result,
				'code' => 1,
				'message' => '返回公告成功'
			);
			exit(json_encode($data));
		}else{
			logger("没有公告\n");
			$data = array(
				'code' => 0,
				'message' => '没有公告'
			);
			exit(json_encode($data));
		}
	}
	//极光推送，推送消息 测试测试 测试测试
	public function ipush(){
		// 简单推送
		$platform = 'all';
		$content = 'Hi,This is a broadcast message!';
		//simple_push($platform,$content);

		//完整推送
		$content = 'Hi,This is a message!';
		$alias = 'syj';
		$tags = array('tag1', 'tag2');
		$android = array(
			'alert' => 'Hi, android notification',
			'title' => 'notification title',
			'extras' => array("key1"=>"value1", "key2"=>"value2")
		);
		$ios = array(
			'alert' => 'Hi, iOS notification',
			'title' => 'iOS sound',
			'category' => 'iOS category',
			'extras' => array("key1"=>"value1", "key2"=>"value2")
		);
		$msg = array(
			'content' => 'msg content',
			'title' => 'msg title',
			'type' => 'type',
			'extras' => array("key1"=>"value1", "key2"=>"value2")
		);
		$option = array(
			'tips' => 10000,
			'time' => 3600,
			'order' => null,
			'toggle' => false
		);
		// 完整推送
		//push($platform,$alias,$tags,$content,$android,$ios,$msg,$option);

		// 定时推送
		$alert = 'Hi,android!';
		$schedule = array(
			'message' => 'this is a  settime message!',
			'time' => array("time"=>"2016-05-10 17:33:00")
		);
		//set_time_push($platform,$alert,$schedule);
		$arr = array(
			'platform' => 'all'
		);
		$alias = array('1','2');
		//消息主体内容
		$array = array(
			'platform' => 'all',
			'alias' => array('1','2','3'),
			'msg' => array(
				'content' => '测试发送内容',
				'title' => '测试标题',
				'category' => '',
				'message' => array(
					'action' => 1,
					'type' => '',
					'details' => array()
					// array(
					// 	'id' => 1,
					// 	'kind'=> 2,
					// 	'sply_nickname' => 'nick',
					// 	'appro_nickname' => 'approver',
					// 	'sply_time' => '1461143710',
					// 	'status' => 0
					// )
				)
			)
		);
		//精简发送
		jpush($array);
		//推送短信
		// $alert = "Hi, JPush SMS";
		// $sms = array(
		// 	'message' => 'Hi, JPush SMS',
		// 	'time' => 60
		// );
		//sms_jpush($platform,$tags,$alert,$sms);
	}
	//环信注册新用户
	public function create_easemob_user(){
		$user = 'xnkl_admin';
		$pwd = '1234';
		$result = easemob_create_user($user,$pwd);
		echo "<pre>";
		var_dump($result); 
	}
	// array(5) {
	  // ["error"]=>
	  // string(32) "duplicate_unique_property_exists"
	  // ["timestamp"]=>
	  // int(1463983724127)
	  // ["duration"]=>
	  // int(0)
	  // ["exception"]=>
	  // string(81) "org.apache.usergrid.persistence.exceptions.DuplicateUniquePropertyExistsException"
	  // ["error_description"]=>
	  // string(135) "Application f242a800-b431-11e5-99dc-37f89ff75b90 Entity user requires that property named username be unique, value of jsty_user exists"
	// }

	// array(9) {
	//   ["action"]=>
	//   string(4) "post"
	//   ["application"]=>
	//   string(36) "f242a800-b431-11e5-99dc-37f89ff75b90"
	//   ["path"]=>
	//   string(6) "/users"
	//   ["uri"]=>
	//   string(51) "https://a1.easemob.com/tomsz2015/photographic/users"
	//   ["entities"]=>
	//   array(1) {
	//     [0]=>
	//     array(6) {
	//       ["uuid"]=>
	//       string(36) "f94066fa-20ac-11e6-aab3-d90e972bd718"
	//       ["type"]=>
	//       string(4) "user"
	//       ["created"]=>
	//       int(1463983796959)
	//       ["modified"]=>
	//       int(1463983796959)
	//       ["username"]=>
	//       string(10) "jsty_admin"
	//       ["activated"]=>
	//       bool(true)
	//     }
	//   }
	//   ["timestamp"]=>
	//   int(1463983796957)
	//   ["duration"]=>
	//   int(35)
	//   ["organization"]=>
	//   string(9) "tomsz2015"
	//   ["applicationName"]=>
	//   string(12) "photographic"
	// }
}
?>
<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class EasemobController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
		//引入环信库文件
		Vendor('Easemob.Easemob');
		//引入极光推送类库文件
		// Vendor('Jpush.Jpush');
		// Vendor('Jpush.core.DevicePayload');
		// Vendor('Jpush.core.JPushException');
		// Vendor('Jpush.core.PushPayload');
		// Vendor('Jpush.core.ReportPayload');
		// Vendor('Jpush.core.SchedulePayload');
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
	}
	public function index(){
		logger('统一修改修改服务期。。。');
		$store = D('store');
		$stores = $store->field('id,createtime')->select();
		foreach($stores as $k => $v){
			if($v['createtime'] > 0){
				$update_data = array(
					'modify_time' => time(),
					'expiring_on' => $v['createtime'] + 31536000
				);
				$where = array(
					'id' => $v['id']
				);
				$update_result = $store->where($where)->save($update_data);
				if($update_result){
					logger($v['id'].'修改成功！');
				}else{
					logger($v['id'].'修改失败！');
				}
			}
		}
		logger('统一修改修改服务期 完成！'."\n");
	}
	//联系人列表（影楼联系人 + 好友关系）
	public function my_contacts(){
		logger('环信——获取联系人列表');
		$app_user = D('app_user');
		$sid = session('sid');
		$where = array(
			'sid' => $sid,
			'username' => array('neq','')
		);
		$colleagues = $app_user->where($where)->field('uid,sid,username,store_simple_name,head,mobile,birth,realname,nickname,gender,qq,location,dept')->select(); //同事关系
		$cols = array();
		$n = 0;
		foreach($colleagues as $k => $v){
			if($k == 0){
				$cols[$n]['dept'] = $v['dept'];
				$cols[$n]['member'][0] = $v; 
				$cols[$n]['member'][0]['name'] = $v['store_simple_name'].'_'.$v['username'];
				$cols[$n]['member'][0]['birth'] = date('Y-m-d',$v['birth']);
				if(strpos($v['head'],'Uploads/avatar')){
					$cols[$n]['member'][0]['head'] = C('base_url').$v['head'];
				}
				unset($cols[$n]['member'][0]['store_simple_name']);
				unset($cols[$n]['member'][0]['username']);
			}else{
				$max = count($cols);
				$m = 1;
				foreach($cols as $x => $y){
					if($y['dept'] == $v['dept']){
						$size = count($cols[$x]['member']);
						$cols[$x]['member'][$size] = $v;
						$cols[$x]['member'][$size]['name'] = $v['store_simple_name'].'_'.$v['username'];
						$cols[$x]['member'][$size]['birth'] = date('Y-m-d',$v['birth']);
						if(strpos($v['head'],'Uploads/avatar')){
							$cols[$x]['member'][$size]['head'] = C('base_url').$v['head'];
						}
						unset($cols[$x]['member'][$size]['store_simple_name']);
						unset($cols[$x]['member'][$size]['username']);
						break;
					}else{
						if($m == $max){
							$n++;
							$cols[$n]['dept'] = $v['dept'];
							$cols[$n]['member'][0] = $v; 
							$cols[$n]['member'][0]['name'] = $v['store_simple_name'].'_'.$v['username'];
							$cols[$n]['member'][0]['birth'] = date('Y-m-d',$v['birth']);
							if(strpos($v['head'],'Uploads/avatar')){
								$cols[$n]['member'][0]['head'] = C('base_url').$v['head'];
							}
							unset($cols[$n]['member'][0]['store_simple_name']);
							unset($cols[$n]['member'][0]['username']);
						}
						$m++;
					}
				}
			}
		}
		// logger('员工总人数：'.count($colleagues));//debug
		// logger('同事联系人：：'.var_export($cols,TRUE));//debug
		$easemob_friends = D('easemob_friends');
		$condition = array(
			'uid' => session('uid')
		);
		if(session('store_simple_name') == 'aaa'){
			logger('影楼客服查询联系人%100+1%'."\n");
			$result = $easemob_friends->where($condition)->field('fid')->select(FALSE);
			$result = $app_user->join('JOIN ('.$result.') AS a ON app_user.uid = a.fid')->join('store AS b ON b.id = app_user.sid')->field('a.fid as uid,b.storename,app_user.sid,app_user.username,app_user.store_simple_name,app_user.head,app_user.mobile,app_user.realname,app_user.nickname,app_user.gender,app_user.qq,app_user.dept,app_user.location,app_user.birth')->select();
			// logger('好友联系人1：'.var_export($result1,TRUE));//debug
			if(!empty($result)){
				foreach($result as $k => $v){
					$result[$k]['realname'] = $result[$k]['storename'].' - '.$result[$k]['realname'];
					$result[$k]['nickname'] = $result[$k]['storename'].' - '.$result[$k]['nickname'];
				}
				$new_friends = $result;
			}else{
				$new_friends = array();
			}
		}else{
			$result = $easemob_friends->where($condition)->field('fid')->select(FALSE);
			$result = $app_user->join('JOIN ('.$result.') AS a ON app_user.uid = a.fid')->field('a.fid as uid,app_user.sid,app_user.username,app_user.store_simple_name,app_user.head,app_user.mobile,app_user.realname,app_user.nickname,app_user.gender,app_user.qq,app_user.dept,app_user.location,app_user.birth')->select();
			// logger('好友联系人1：'.var_export($result1,TRUE));//debug
			if(!empty($result)){
				logger('好友列表数组不为空！');
				$new_array = array();
				$max = count($result);
				$i = 1;
				foreach($result as $k => $v){
					if($v['store_simple_name'].'_'.$v['username'] != 'aaa_secretary'){
						if($i == $max){
							$result[$max] = array(
								'uid' => 2078,
								'name' => 'aaa_secretary',
								'sid' => 2,
								'head' => C('base_url').'/Uploads/avatar/2078.png',
								'realname' => '影楼小秘书',
								'nickname' => '影楼小秘书',
								'gender' => 2,
								'mobile' => '010-87586675',
								'birth' => '2016-10-01',
								'location' => '北京',
								'qq' => 0
							);
							$new_array[$max] = iconv('UTF-8','GBK','00000001');
						}
						$i++;
					}
					$result[$k]['name'] = $v['store_simple_name'].'_'.$v['username'];
					unset($result[$k]['store_simple_name']);
					unset($result[$k]['username']);
					$result[$k]['birth'] = date('Y-m-d',$v['birth']);
					if(strpos($v['head'],'Uploads/avatar')){
						$result[$k]['head'] = C('base_url').$v['head'];
					}
					$new_array[$k] = iconv('UTF-8','GBK',$v['nickname']);   //按照昵称的字母序排序
				}
				// logger('好友联系人：'.var_export($friends,TRUE));//debug
				asort($new_array);
				$n = 0;
				$new_friends = array();
				foreach($new_array as $k => $v){
					$new_friends[$n] = $result[$k];
					$n++;
				}
				// logger('字母序排序后的好友联系人：'.var_export($new_friend,TRUE));//debug
			}else{
				logger('好友列表数组为空！');
				$new_friends[0] = array(
					'uid' => 2078,
					'name' => 'aaa_secretary',
					'sid' => 2,
					'head' => C('base_url').'/Uploads/avatar/2078.png',
					'realname' => '影楼小秘书',
					'nickname' => '影楼小秘书',
					'gender' => 2,
					'mobile' => '010-87586675',
					'birth' => '2016-10-01',
					'location' => '北京',
					'qq' => 0
				);
			}
		}
		$result = array(
			'colleagues' => $cols,
			'friends' => $new_friends
		);
		$data = array(
			'code' => 1,
			'message' => '联系人列表返回成功！',
			'result' => $result
		);
		logger('联系人列表返回成功！'."\n");
		exit(json_encode($data));
	}
	//我加入群组的列表
	public function my_groups_list(){
		logger('我的群组列表');
		$post = I();
		$uid = $post['uid'];
		if(empty($uid)){
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}else{
			$mygroups = D('easemob_groups_users');
			$where = array(
				'uid' => $uid
			);
			$gids = $mygroups->where($where)->field('gid')->select();
			if($gids){
				$gid = array();
				foreach($gids as $k => $v){
					$gid[$k] = $v['gid']; 
				}
				$groups = D('easemob_groups');
				$codition['id'] = array('in',$gid);
				$glist = $groups->where($codition)->field('ctime,mtime,cuid,muid,members',TRUE)->select(); //members不准确
				// logger('群组列表:'.var_export($glist,TRUE)); //debug
				$data = array(
					'code' => 1,
					'message' => '群组列表返回成功！',
					'grouplist' => $glist
				);
			}else{
				$data = array(
					'code' => 1,
					'message' => '群组列表返回成功！',
					'grouplist' => array()
				);
			}
		}
		exit(json_encode($data));
	}
	public function get_group_users_list(){
		logger('我的个群组成员详细信息');
		$post = I();
		$gid = $post['gid'];
		if(empty($gid)){
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
			exit(json_encode($data));
		}
		$groups = D('easemob_groups_users');
		$where = array(
			'gid' => $gid
		); 
		$uids = $groups->where($where)->field('uid')->select();
		// logger('群组成员UIDS:'.var_export($uids,TRUE)); //debug
		$uid = array();
		foreach($uids as $k => $v){
			$uid[$k] = $v['uid']; 
		}
		if(count($uid) >= 1){
			$app_user = D('app_user');
			$codition['uid'] = array('in',$uid);
			$members = $app_user->where($codition)->field('uid,realname,nickname,qq,mobile,location,username,store_simple_name,birth,gender,head')->select();
			// logger('群组列表:'.var_export($members,TRUE)); //debug
			foreach($members as $k => $v){
				$members[$k]['name'] = $v['store_simple_name'].'_'.$v['username'];
				if(strpos($v['head'],'Uploads/avatar')){
					$members[$k]['head'] = C('base_url').$v['head'];
				}
				unset($members[$k]['store_simple_name']);
				unset($members[$k]['username']);
			}
			$data = array(
				'code' => 1,
				'message' => '群组成员列表返回成功！',
				'members' => $members
			);
		}else{
			$data = array(
				'code' => 1,
				'message' => '群组成员列表返回成功！',
				'members' => array()
			);
		}
		exit(json_encode($data));
	}
	//模糊查找 范围：店铺内（要考虑集团）+ 全局好友   info:用户名 昵称 手机号 真实姓名
	public function fuzzy_search(){
		logger('模糊查询用户');
		$post = I();
		$info = $post['info'];
		if(empty($info)){
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}else{
			$app_user = D('app_user');
			if(strrpos($info,'_')){
				$pre_name = strchr($info,'_',TRUE);
				$name = ltrim(strchr($info,'_'),'_');
				$string = "(store_simple_name = '".$pre_name."' AND username = '".$name."') OR nickname = '".$info."' OR mobile = '".$info."'";
			}else{
				$string = "nickname = '".$info."' OR mobile = '".$info."'";
			}
			$where['_string'] = $string;
			$result = $app_user->where($where)->field('uid,store_simple_name,username,qq,birth,location,mobile,head,nickname,realname,gender')->select();
			if($result){
				$result[0]['name'] = $result[0]['store_simple_name'].'_'.$result[0]['username'];
				if(strpos($result[0]['head'],'Uploads/avatar')){
					$result[0]['head'] = C('base_url').$result[0]['head'];
				}
				logger('查找环信用户存在！'."\n");
				$data = array(
					'code' => 1,
					'message' => '查找环信用户存在！',
					'user' => $result
				);
			}else{
				logger('查找环信用户不存在！'."\n");
				$data = array(
					'code' => 0,
					'message' => '查找环信用户不存在！'
				);
			}
		}
		exit(json_encode($data));
	}
	//请求好友 
	public function askfriend(){
		logger('请求添加好友');
		$post = I();
		$uid = session('uid');
		$aname = session('store_simple_name').'_'.session('appuser'); //申请人环信用户名 
		$anick = session('admin_nickname');
		$ahead = session('head');
		$message = $post['msg'];
		$fuid = $post['fuid'];
		$fname = $post['fname'];
		$fnick = $post['fnick'];
		$fhead = $post['fhead'];
		if($uid == $fuid){
			$data = array(
				'code' => 3,
				'message' => '不能向自己申请好友！'
			);
			exit(json_encode($data));
		}
		if($fuid && $fname){
			$ask = D('easemob_ask_friend');
			//先判断是否有相同申请，未处理
			$where = array(
				'auid' => $uid,
				'fuid' => $fuid,
				'status' => 0,
			);
			$ask_info = $ask->where($where)->field('id')->find();
			if($ask_info){
				logger('之前已发送过好友申请，更新申请，重新发送极光推送提醒对方处理！'."\n");
				$update_data = array(
					'mtime' => time(),
					'ahead' => $ahead ? $ahead : '',
					'anick' => $anick ? $anick : '',
					'amessage' => $message ? $message : '',
					'fnick' => $fnick ? $fnick : '',
					'fhead' => $fhead ? $fnick : '',
				);
				$update_result = $ask->where(array('id' => $ask_info['id']))->save($update_data);
				if($update_result){
					logger('更新申请好友记录成功！');
				}else{
					logger('更新申请好友记录失败！');
				}
				//发送极光推送
				$msg = array(
					'platform' => 'all',
					'alias' => array(0 => $fuid),
					'msg' => array(
						'content' => $message ? $message : '对方请求添加你为好友',
						'title' => '好友申请',
						'category' => '',
						'message' => array(
							'action' => 3, //添加好友申请
							'type' => 1,  //1申请 2同意 3拒绝 4忽略
							'details' => array(
								'uid' => $uid,
								'username' => $aname,
								'message'=> $message,
								'nickname' => $anick,
								'head' => $ahead,
								'time' => time()
							)
						)
					)
				);
				$j_result = jpush($msg);
				if($j_result == 1101){
					logger('JPush---自定义简单发送----结果： 对方未登录,消息推送失败!------完毕------'."\n");
					$data = array(
						'code' => 1,
						'message' => '申请成功！'
					);
				}else{
					logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
					$data = array(
						'code' => 1,
						'message' => '申请成功'
					);
				}
				exit(json_encode($data));
			}
			$friends = D('easemob_friends');
			$condition1 = array(
				'uid' => $uid,
				'fid' => $fuid,
			);
			$relaship = $friends->where($condition1)->field('id')->find();
			if($relaship){
				logger('对方已经是自己的好友！'."\n");
				$data = array(
					'code' => 4,
					'message' => '对方已经是好友了！'
				);
				exit(json_encode($data));
			}
			//判断是否有单向朋友关系存在
			$condition2 = array(
				'uid' => $fuid,
				'fid' => $uid,
			);
			$relaship = $friends->where($condition2)->field('id')->find();
			if($relaship){
				logger('双方间存在单向好友关系，被请求方是申请方好友！'."\n");
				$add_data = array(
					'uid' => $uid,
					'fid' => $fuid,
					'ctime' => time()
				);
				$add_result = $friends->add($add_data);
				if($add_result){
					logger('好友双向关系已经建立！'."\n");
					//发送消息
					$msg = array(
						'platform' => 'all',
						'alias' => array(0 => $uid),
						'msg' => array(
							'content' => '对方同意添加你为好友',
							'title' => '同意好友申请',
							'category' => '',
							'message' => array(
								'action' => 3, //添加好友申请
								'type' => 2,  //1申请 2同意 3拒绝 4忽略
								'details' => array(
									'uid' => $fuid,
									'username' => $fname,
									'message'=> '',
									'nickname' => $fnick,
									'head' => $fhead,
									'time' => time()
								)
							)
						)
					);
					$j_result = jpush($msg);
					if($j_result == 1101){
						logger('JPush---自定义简单发送----结果： 审核人未登录,消息推送失败!------完毕------'."\n");
						$data = array(
							'code' => 1,
							'message' => '申请成功！'
						);
					}else{
						logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
						$data = array(
							'code' => 1,
							'message' => '申请成功'
						);
					}
				}else{
					logger('好友双向关系建立失败'."\n");
					$data = array(
						'code' => 0,
						'message' => '好友申请失败！'
					);
				}
				exit(json_encode($data));
			}
			$add_data = array(
				'auid' => $uid,
				'aname' => $aname ? $aname : '',
				'ahead' => $ahead ? $ahead : '',
				'anick' => $anick ? $anick : '',
				'amessage' => $message ? $message : '',
				'fuid' => $fuid,
				'fname' => $fname,
				'fnick' => $fnick ? $fnick : '',
				'fhead' => $fhead ? $fnick : '',
				'ctime' => time()
			);
			$add_result = $ask->add($add_data);
			if($add_result){
				logger('好友申请记录添加成功！');
				//推送
				$msg = array(
					'platform' => 'all',
					'alias' => array(0 => $fuid),
					'msg' => array(
						'content' => $message ? $message : '对方请求添加你为好友',
						'title' => '好友申请',
						'category' => '',
						'message' => array(
							'action' => 3, //添加好友申请
							'type' => 1,  //1申请 2同意 3拒绝 4忽略
							'details' => array(
								'uid' => $uid,
								'username' => $aname,
								'message'=> $message,
								'nickname' => $anick,
								'head' => $ahead,
								'time' => time()
							)
						)
					)
				);
				$j_result = jpush($msg);
				if($j_result == 1101){
					logger('JPush---自定义简单发送----结果： 审核人未登录,消息推送失败!------完毕------'."\n");
					$data = array(
						'code' => 1,
						'message' => '申请成功！'
					);
				}else{
					logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
					$data = array(
						'code' => 1,
						'message' => '申请成功'
					);
				}
			}else{
				logger('好友申请记录添加失败！');
				$data = array(
					'code' => 0,
					'message' => '申请失败！'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	public function get_msg(){
		logger('查看环信好友申请或通知');
		$post = I();
		$uid = $post['uid'];
		$ask = D('easemob_ask_friend');
		//申请消息
		$where = array(
			'fuid' => $uid,
			'status' => 0,
		);
		$ask_msgs = $ask->where($where)->field('ctime,mtime',TRUE)->select();
		foreach($ask_msgs as $k => $v){
			$ask_msgs[$k]['type'] = 1;
		}
		//通知消息
		$condition = array(
			'auid' => $uid,
			'status' => array(array('eq',0),array('eq',2),array('eq',3),'OR')
		);
		$info_msgs = $ask->where($condition)->field('ctime,mtime',TRUE)->select();
		foreach($info_msgs as $k => $v){
			$info_msgs[$k]['type'] = 2;
		}
		$msgs = array_merge($ask_msgs,$info_msgs);
		if(empty($msgs)){
			$data = array(
				'code' => 0,
				'message' => '无消息'
			);
		}else{
			$data = array(
				'code' => 1,
				'message' => '消息返回成功',
				'result' => $msgs
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	public function handle_ask(){
		logger('处理好友申请');
		$post = I();
		$uid = $post['uid'];
		$message = $post['msg'];
		$auid = $post['auid'];
		$id = $post['id']; //记录id
		$status = $post['status']; //2 忽略 3 同意 4拒绝
		if(($id || $auid) && $status){
			$ask = D('easemob_ask_friend');
			if(!empty($id)){
				$where = array(
					'id' => $id
				);
			}else{
				$where = array(
					'auid' => $auid,
					'fuid' => $uid,
					'status' => 0
				);
			}
			if($status == 4){ //忽略
				logger('忽略消息');
				$update_data = array(
					'mtime' => time(),
					'status' => $status
				);
				$update_result = $ask->where($where)->save($update_data);
				if($update_result){
					logger('好友申请记录修改成功！'."\n");
					$data = array(
						'code' => 1,
						'message' => '好友申请处理成功！'
					);
				}else{
					logger('好友申请记录修改成功！'."\n");
					$data = array(
						'code' => 0,
						'message' => '好友申请处理失败！'
					);
				}
			}elseif($status == 2){ //同意
				M()->startTrans(); //事务开始
				$update_data = array(
					'mtime' => time(),
					'status' => $status
				);
				$update_result = $ask->where($where)->save($update_data);
				$ask_info = $ask->where($where)->field('ctime,mtime',TRUE)->find(); //取得好友申请记录详情
				if($update_result){
					logger('好友申请记录修改成功！');
					$add_data[0] = array(
						'uid' => $ask_info['auid'],
						'aname' => $ask_info['aname'],
						'fid' => $uid,
						'fname' => session('store_simple_name').'_'.session('appuser'),
						'ctime' => time()
					);
					$add_data[1] = array(
						'uid' => $uid,
						'aname' => session('store_simple_name').'_'.session('appuser'),
						'fid' => $ask_info['auid'],
						'fname' => $ask_info['aname'],
						'ctime' => time()
					);
					$friends = D('easemob_friends');
					$add_result = $friends->addAll($add_data);
					if($add_result){
						logger('好友关系表记录添加成功！');
						$e_result = easemob_add_friend($ask_info['aname'],session('store_simple_name').'_'.session('appuser'));
						logger('环信反馈信息：'.var_export($e_result,TRUE)); //debug
						if($e_result['error'] == ''){
							logger('环信好友关系建立成功！');
							M()->commit(); //事务提交
							//推送
							$msg = array(
								'platform' => 'all',
								'alias' => array(0 => $ask_info['auid']),
								'msg' => array(
									'content' => $message ? $message : '对方已同意添加您为好友',
									'title' => '同意好友申请',
									'category' => '',
									'message' => array(
										'action' => 3,
										'type' => $status, //回复
										'details' => array(
											'uid' => $uid,
											'username' => $ask_info['fname'],
											'nickname' => $ask_info['fnick'],
											'head' => $ask_info['fhead'],
											'time' => time()
										)
									)
								)
							);
							$j_result = jpush($msg);
							if($j_result == 1101){
								logger('JPush---自定义简单发送----结果： 因申请好友未登录,消息推送失败!------完毕------'."\n");
								$data = array(
									'code' => 1,
									'message' => '申请成功,因申请好友未登录,消息推送失败!'
								);
							}else{
								logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
								$data = array(
									'code' => 1,
									'message' => '好友申请处理成功'
								);
							}
						}else{
							M()->rollback();
							logger('环信好友关系建立失败！'."\n");
							$data = array(
								'code' => 4,
								'message' => '好友申请处理失败！'
							);
						}
					}else{
						logger('好友关系表记录添加失败！'."\n");
						M()->rollback();
						$data = array(
							'code' => 5,
							'message' => '好友申请处理失败!'
						);
					}
				}else{
					logger('好友申请记录修改失败！'."\n");
					M()->rollback();
					$data = array(
						'code' => 6,
						'message' => '好友申请处理失败！'
					);
				}
			}else{ //拒绝
				$update_data = array(
					'mtime' => time(),
					'status' => $status
				);
				$update_result = $ask->where($where)->save($update_data);
				$ask_info = $ask->where($where)->field('ctime,mtime',TRUE)->find(); //取得好友申请记录详情
				if($update_result){
					logger('拒绝好友申请，成功！'."\n");
					//推送
					$msg = array(
						'platform' => 'all',
						'alias' => array(0 => $ask_info['auid']),
						'msg' => array(
							'content' => $message ? $message : '对方已拒绝添加您为好友',
							'title' => '拒绝好友申请',
							'category' => '',
							'message' => array(
								'action' => 3,
								'type' => $status, //回复
								'details' => array(
									'uid' => $uid,
									'username' => $ask_info['fname'],
									'nickname' => $ask_info['fnick'],
									'head' => $ask_info['fhead'],
									'time' => time()
								)
							)
						)
					);
					$j_result = jpush($msg);
					if($j_result == 1101){
						logger('JPush---自定义简单发送----结果： 因申请好友未登录,消息推送失败!------完毕------'."\n");
						$data = array(
							'code' => 1,
							'message' => '好友申请处理成功,因申请好友未登录,消息推送失败!'
						);
					}else{
						logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
						$data = array(
							'code' => 1,
							'message' => '好友申请处理成功'
						);
					}
				}else{
					logger('拒绝好友申请，失败！'."\n");
					$data = array(
						'code' => 7,
						'message' => '好友申请修改失败！'
					);
				}
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	// 解除好友关系
	public function deletefriend(){
		logger('解除好友关系');
		$post = I();
		$uid = $post['uid'];
		$fuid = $post['fuid']; //方便查找记录
		$fname = $post['fname']; //解除环信好友关系所用
		if($fuid && $fname){
			M()->startTrans(); //事务开始
			$relation = D('easemob_friends');
			$where = array(
				'uid' => $uid,
				'fid' => $fuid,
			);
			$result = $relation->where($where)->delete();
			if($result){
				$codition = array(
					'uid' => $fuid,
					'fid' => $uid,
				);
				$find = $relation->where($codition)->find();
				if($find){
					M()->commit(); //事务提交
					logger('好友关系单方面解除'."\n");
					$data = array(
						'code' => 1,
						'message' => '解除好友关系成功!'
					);
				}else{
					logger('好友关系双方面均已解除！');
					$aname = session('store_simple_name').'_'.session('appuser') ;//解除环信好友关系所用
					$jresult = easemob_rm_friend($aname,$fname);
					logger('环信反馈信息：'.var_export($result,TRUE)); //debug
					if($jresult['error'] == ''){
						M()->commit(); //事务提交
						$data = array(
							'code' => 1,
							'message' => '解除好友关系成功！'
						);
					}else{
						M()->rollback(); //事务回滚
						$data = array(
							'code' => 3,
							'message' => '网络错误,请重试!'
						);
					}
				}
			}else{
				M()->rollback(); //事务回滚
				$data = array(
					'code' => 4,
					'message' => '网络错误,请重试!'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	// 创建群组 //默认私有群
	public function create_group(){
		logger('创建群组');
		$post = I();
		// logger('创建群组传入参数:'.var_export($post,true)); //debug
		$uid = $post['uid'];
		$gname = $post['name'];
		$desc = $post['dsp'] ? $post['dsp'] : '群主很懒,什么也没写';
		$members = $post['members']; //json
		$public = $post['public'];
		$maxusers = $post['maxusers'] ? $post['maxusers'] : 200; //默认最多200人
		if($public == 1){
			$public = true;
			$allowinvites = false;
			$approval = false;
		}else{ //默认创建私有群
			$public = false;
			$allowinvites = $post['allowinvites'] == 0 ? false : true; //默认允许成员邀请
			$approval = true;
		}
		if($gname && $members){
			$members = stripslashes(html_entity_decode($members)); //转换json字符串中的转义字符 ，方法二
			$members = json_decode($members,TRUE);
			$mem_string = '';
			$mems = array();
			$uids = array();
			foreach($members as $k => $v){
				$mems[$k] = $v['name'];
				$uids[$k] = $v['uid'];
				if($k == 0){
					$mem_string = ' '.$v['name'].' ';  
				}else{
					$mem_string .=  $v['name'].' ';
				}
			}
			// logger('成员数组：'.var_export($mems,TRUE));//debug
			$num_mem = count($members) + 1;
			$add_data = array(
				'gname' => $gname,
				'sid' => session('sid'),
				'pub' => $post['public'] == 1 ? 1 : 0,
				'owner' => session('store_simple_name').'_'.session('appuser'),
				'dsp' => $desc,
				'maxusers' => $maxusers,
				'affiliations' => $num_mem,
				'ctime' => time(),
				'cuid' => session('uid'),
				'members' => $mem_string
			);
			M()->startTrans();
			$groups = D('easemob_groups');
			$add_result = $groups->add($add_data);
			if($add_result){
				logger('创建群组记录写入数据库成功！-->去添加群组成员关系记录');
				$uers = array();
				foreach($members as $k => $v){
					$users[$k] = array(
						'gsid' => session('sid'),
						'sid' => $v['sid'],
						'uid' => $v['uid'],
						'name' => $v['name'],
						'gid' => $add_result,
						'ctime' => time()
					);
				}
				$users[] = array(
					'gsid' => session('sid'),
					'sid' => session('sid'),
					'uid' => session('uid'),
					'name' => session('store_simple_name').'_'.session('appuser'),
					'gid' => $add_result,
					'ctime' => time()
				);
				$relation = D('easemob_groups_users');
				$add_all_result = $relation->addAll($users);
				if($add_all_result){
					logger('群组成员关系记录写入数据库成功！--> 去创建环信群组');
					$options = array(
						'groupname' => $gname,
						'desc' => $desc,
						'public' => $public,
						'maxusers' => $maxusers,
						'approval' => $approval,
						'owner' => session('store_simple_name').'_'.session('appuser'),
						'members' => $mems,
						'allowinvites' => $allowinvites,
					);
					$result = easemob_create_group($options);
					logger('环信创建群组返回信息：'.var_export($result,TRUE));
					if($result['error'] == ''){
						logger('创建环信群组成功！将环信群id回写');
						$where = array(
							'id' => $add_result,
						);
						$update_data = array(
							'gid' => $result['data']['groupid']
						);
						$update_result = $groups->where($where)->save($update_data);
						if($update_result){
							M()->commit();
							//给群组成员发送 通知
							$msg = array(
								'platform' => 'all',
								'alias' => $uids,
								'msg' => array(
									'content' => session('admin_nickname').'邀请您加入群 '.$gname,
									'title' => '欢迎加入群聊',
									'category' => '',
									'message' => array(
										'action' => 4,
										'type' => 1, //群组消息
										'details' => array( //群组信息
											'id' => $add_result,
											'sid' => session('sid'),
											'gid' => $result['data']['groupid'],
											'gname' => $gname,
											'owner'=> session('store_simple_name').'_'.session('appuser'),
											'dsp' => $desc,
											'public' => $post['public'] == 1 ? 1 : 0,
											'affiliations' => $num_mem,
											'allowinvites' => $post['allowinvites'] == 0 ? 0 : 1,
											'maxusers' => $maxusers,
											'membersonly' => 1
										)
									)
								)
							);
							$j_result = jpush($msg);
							if($j_result == 1101){
								logger('JPush---自定义简单发送----结果： 因群组成员未登录,消息推送失败!------完毕------'."\n");
								$data = array(
									'code' => 1,
									'message' => '创建群组成功!'
								);
							}else{
								logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
								$data = array(
									'code' => 1,
									'message' => '创建群组成功!'
								);
							}
						}else{
							logger('回写环信群组ID失败！'."\n");
							M()->rollback();
							$data = array(
								'code' => 6,
								'message' => '网络错误,请重试!'
							);
						}
					}else{
						logger('创建环信群组失败！'."\n");
						M()->rollback();
						$data = array(
							'code' => 5,
							'message' => '网络错误,请重试！'
						);
					}
				}else{
					logger('群组成员关系记录写入数据库失败！'."\n");
					M()->rollback();
					$data = array(
						'code' => 4,
						'message' => '网络错误,请重试!'
					);
				}
			}else{
				logger('创建群组记录写入数据库失败！'."\n");
				M()->rollback();
				$data = array(
					'code' => 3,
					'message' => '网络错误,请重试!'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	// 邀请成员进群
	public function invite_to_group(){
		logger('邀请他人进入群组');
		$post = I();
		logger('打印参数：'.var_export($post,TRUE)); //debug
		$sid = $post['sid']; //群组群主所在店铺
		$gid = $post['gid']; //环信群组id
		$id = $post['id']; //环信群组在数据库中的id  需要在关系表中记录
		$members = $post['members']; //json格式  包含name uid sid
		if($id && $gid && $members && $sid){
			$groups = D('easemob_groups');
			$group = $groups->where(array('id'=>$id))->field('ctime,mtime,cuid,muid,members',TRUE)->find();
			if($group){
				$members = stripslashes(html_entity_decode($members)); //转换json字符串中的转义字符 ，方法二
				$members = json_decode($members,TRUE);
				logger('打印邀请成员列表：'.var_export($members,TRUE));  //debug
				$relas = array();
				$users = array();
				$uids = array();
				foreach($members as $k => $v){
					$users[$k] = $v['name']; 
					$uids[$k] = $v['uid'];
					$relas[$k] = array(
						'gsid' => $sid,
						'sid' => $v['sid'],
						'uid' => $v['uid'],
						'gid' => $id,
						'ctime' => time(),
						'name' => $v['name']
					);
				}
				M()->startTrans();
				$relation = D('easemob_groups_users');
				$add_result = $relation->addAll($relas);
				if($add_result){
					logger('成员记录写入群组与成员关系表，成功！-->去环信添加成员');
					$max = count($members);
					if($max == 1){
						logger('邀请单个成员！');
						$result = easemob_add_member($gid,$members[0]['name']);
						logger('环信反馈信息：'.var_export($result,TRUE)); //debug
						if($result['error'] == ''){
							logger('单个邀请成员进群成功！'."\n");
							M()->commit();
							// 改写成员数记录
							$update_result = $this->update_group_affiliations($gid);
							if($update_result){
								logger('更新群组成员人数成功！');
							}else{
								logger('更新群组成员人数失败！');
							}
							// 发送消息
							$msg = array(
								'platform' => 'all',
								'alias' => $uids[0],
								'msg' => array(
									'content' => session('admin_nickname').'邀请您加入群 '.$group['gname'],
									'title' => '欢迎加入群聊',
									'category' => '',
									'message' => array(
										'action' => 4,
										'type' => 1, //群组消息
										'details' => $group
									)
								)
							);
							$j_result = jpush($msg);
							if($j_result == 1101){
								logger('JPush---自定义简单发送----结果： 因群组成员未登录,消息推送失败!------完毕------'."\n");
								$data = array(
									'code' => 1,
									'message' => '邀请成功!'
								);
							}else{
								logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
								$data = array(
									'code' => 1,
									'message' => '邀请成功!'
								);
							}
						}else{
							logger('单个邀请成员进群失败！'."\n");
							M()->rollback();
							$data = array(
								'code' => 5,
								'message' => '网络错误,请重试!'
							);
						}
					}else{
						logger('邀请多个成员！');
						logger('群组ID:'.$uids); //debug
						$usernames = array(
							'usernames' => $users
						);
						$result = easemob_add_members($gid,$usernames);
						logger('环信反馈信息：'.var_export($result,TRUE)); //debug
						if($result['error'] == ''){
							logger('批量邀请成员进群成功！'."\n");
							M()->commit();
							// 改写成员数记录
							$update_result = $this->update_group_affiliations($gid);
							if($update_result){
								logger('更新群组成员人数成功！');
							}else{
								logger('更新群组成员人数失败！');
							}
							//发送消息
							$msg = array(
								'platform' => 'all',
								'alias' => $uids,
								'msg' => array(
									'content' => session('admin_nickname').'邀请您加入群 '.$group['gname'],
									'title' => '欢迎加入群聊',
									'category' => '',
									'message' => array(
										'action' => 4,
										'type' => 1, //群组消息
										'details' => $group
									)
								)
							);
							$j_result = jpush($msg);
							if($j_result == 1101){
								logger('JPush---自定义简单发送----结果： 因群组成员未登录,消息推送失败!------完毕------'."\n");
								$data = array(
									'code' => 1,
									'message' => '邀请成功!'
								);
							}else{
								logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
								$data = array(
									'code' => 1,
									'message' => '邀请成功!'
								);
							}
						}else{
							logger('批量邀请成员进群失败！'."\n");
							M()->rollback();
							$data = array(
								'code' => 4,
								'message' => '网络错误,请重试!'
							);
						}
					}
				}else{
					logger('成员记录写入群组与成员关系表，失败！'."\n");
					$data = array(
						'code' => 3,
						'message' => '网络错误,请重试!'
					);
				}
			}else{
				logger('群组不存在！'."\n");
				$data = array(
					'code' => 8,
					'message' => '群组不存在！'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	// 将成员踢出群组 群主权限
	public function kick_out_group(){
		logger('从群组踢出他人');
		$post = I();
		logger('打印参数：'.var_export($post,TRUE)); //debug
		$gid = $post['gid']; //环信群组id
		$id = $post['id']; //环信群组在数据库中的id  需要在关系表中记录
		$members = $post['members']; //json格式  包含name uid sid
		if($id && $gid && $members){
			$groups = D('easemob_groups');
			$group = $groups->where(array('id'=>$id))->field('ctime,mtime,cuid,muid,members',TRUE)->find();
			if($group){
				if(session('store_simple_name').'_'.session('appuser') == $group['owner']){
					$members = stripslashes(html_entity_decode($members)); //转换json字符串中的转义字符 ，方法二
					$members = json_decode($members,TRUE);
					logger('打印退出成员信息：'.var_export($members,TRUE));  //debug
					M()->startTrans();
					$relation = D('easemob_groups_users');
					$users = '';
					$max = 0;
					$uids = array();
					foreach($members as $k => $v){
						$where = array(
							'uid' => $v['uid'],
							'gid' => $id
						);
						$rm_result = $relation->where($where)->delete();
						if($rm_result){ //删除记录的 累计去环信删除
							$users .= $v['name'].','; 
							$uids[] = $v['uid'];
							$max++;
						}
					}
					if(!empty($users)){
						logger('成员记录从群组与成员关系表中，删除成功！-->去环信移除成员');
						if($max == 1){
							$result = easemob_rm_member($gid,rtrim($users,','));
							logger('环信反馈信息：'.var_export($result,TRUE)); //debug
							if($result['error'] == ''){
								logger('单个成员踢出成功！'."\n");
								M()->commit();
								// 改写成员数记录
								$update_result = $this->update_group_affiliations($gid);
								if($update_result){
									logger('更新群组成员人数成功！');
								}else{
									logger('更新群组成员人数失败！');
								}
								//发送消息
								$msg = array(
									'platform' => 'all',
									'alias' => $members[0]['uid'],
									'msg' => array(
										'content' => session('admin_nickname').'将您移除出群 '.$group['gname'],
										'title' => '您已退出群聊',
										'category' => '',
										'message' => array(
											'action' => 4, //踢出群
											'type' => 2, //群组消息
											'details' => $group
										)
									)
								);
								$j_result = jpush($msg);
								if($j_result == 1101){
									logger('JPush---自定义简单发送----结果： 因群组成员未登录,消息推送失败!------完毕------'."\n");
									$data = array(
										'code' => 1,
										'message' => '移除成功!'
									);
								}else{
									logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
									$data = array(
										'code' => 1,
										'message' => '移除成功!'
									);
								}
							}else{
								logger('单个邀请成员踢出失败！'."\n");
								M()->rollback();
								$data = array(
									'code' => 5,
									'message' => '网络错误,请重试!'
								);
							}
						}elseif($max > 1){
							$result = easemob_rm_members($gid,$users);
							logger('环信反馈信息：'.var_export($result,TRUE)); //debug
							if($result['error'] == ''){
								logger('批量成员踢出成功！'."\n");
								M()->commit();
								// 改写成员数记录
								$update_result = $this->update_group_affiliations($gid);
								if($update_result){
									logger('更新群组成员人数成功！');
								}else{
									logger('更新群组成员人数失败！');
								}
								//发送消息
								$msg = array(
									'platform' => 'all',
									'alias' => $uids,
									'msg' => array(
										'content' => session('admin_nickname').'将您移除出群 '.$group['gname'],
										'title' => '您已退出群聊',
										'category' => '',
										'message' => array(
											'action' => 4, //踢出群
											'type' => 2, //群组消息
											'details' => $group
										)
									)
								);
								$j_result = jpush($msg);
								if($j_result == 1101){
									logger('JPush---自定义简单发送----结果： 因群组成员未登录,消息推送失败!------完毕------'."\n");
									$data = array(
										'code' => 1,
										'message' => '移除成功!'
									);
								}else{
									logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
									$data = array(
										'code' => 1,
										'message' => '移除成功!'
									);
								}
							}else{
								logger('批量成员踢出失败！'."\n");
								M()->rollback();
								$data = array(
									'code' => 4,
									'message' => '网络错误,请重试!'
								);
							}
						}
					}else{
						logger('成员记录从群组与成员关系表中，删除失败！'."\n");
						$data = array(
							'code' => 3,
							'message' => '网络错误,请重试!'
						);
					}
				}else{
					logger('不是群主不能移除成员'."\n");
					$data = array(
						'code' => 9,
						'message' => '权限不足！'
					);
				}
			}else{
				logger('群组不存在！'."\n");
				$data = array(
					'code' => 8,
					'message' => '群组不存在！'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	//主动退出群
	public function quit_group(){
		logger('主动退出群组');
		$post = I();
		logger('打印参数：'.var_export($post,TRUE)); //debug
		$gid = $post['gid']; //群组在环信中的id
		$id = $post['id']; //群组在本地数据库中的id
		if($gid && $id){
			M()->startTrans();
			$relation = D('easemob_groups_users');
			$where = array(
				'uid' => session('uid'),
				'gid' => $id,
			);
			$del_result = $relation->where($where)->delete();
			if($del_result){
				logger('删除群组成员关系记录成功,下一步去移除环信群组中的该成员');
				$out_result = easemob_rm_member($gid,session('store_simple_name').'_'.session('appuser'));
				logger('环信反馈信息：'.var_export($out_result,TRUE)); //debug
				if($out_result['error'] == ''){
					logger('环信群组移除成员成功！');
					M()->commit();
					$data = array(
						'code' => 1,
						'message' => '退群成功！'
					);
					// 改写成员数记录
					$update_result = $this->update_group_affiliations($gid);
					if($update_result){
						logger('更新群组成员人数成功！');
					}else{
						logger('更新群组成员人数失败！');
					}
				}else{
					logger('环信群组移除成员成功！');
					M()->rollback();
					$data = array(
						'code' => 4,
						'message' => '网络错误,请重试!'
					);
				}
			}else{
				logger('删除群组成员关系记录失败!');
				M()->rollback();
				$data = array(
					'code' => 3,
					'message' => '网络错误,请重试!'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	// 更新群组成员人数记录
	private function update_group_affiliations($groupid){
		logger('更新群组成员人数...');
		$group_info = easemob_get_the_group(array(0 => $groupid));
		logger('群组信息：'.var_export($group_info,TRUE)); //debug
		$affiliations = $group_info['data'][0]['affiliations_count'];
		$groups = D('easemob_groups');
		$update_data = array(
			'affiliations' => $affiliations,
			'mtime' => time()
		);
		$update_result = $groups->where(array('gid' => $groupid))->save($update_data);
		if($update_result){
			return true;
		}else{
			return false;
		}
	}
	//解散群组
	public function dismiss_group(){
		logger('解散群组');
		$post = I();
		logger('打印参数：'.var_export($post,TRUE)); //debug
		$gid = $post['gid']; //群组在环信中的id
		$id = $post['id']; //群组在本地数据库中的id
		if($gid && $id){
			$groups = D('easemob_groups');
			$where = array(
				'id' => $id
			);
			$group = $groups->where($where)->field('sid,ctime,mtime,cuid,muid,members',TRUE)->find();
			if($group){
				logger('找寻到要解散的群组！');
				if($group['owner'] == session('store_simple_name').'_'.session('appuser')){
					logger('当前用户是群主身份！');
					M()->startTrans();
					$del_result = $groups->where($where)->delete();
					if($del_result){
						logger('删除群组记录成功');
						$relation = D('easemob_groups_users');
						$where = array(
							'gid' => $id
						);
						$uids = $relation->where($where)->field('uid')->select();
						$del_all_result = $relation->where($where)->delete();
						if($del_all_result){
							logger('删除群组成员关系记录成功，下一步去环信删除群组');
							$del_group = easemob_dismiss_group($gid);
							if($del_group['error'] == ''){
								logger('环信删除群组成功！');
								M()->commit();
								$new_uids = array();
								foreach($uids as $k => $v){
									$new_uids[$k] = $v['uid']; 
								}
								//发送消息
								$msg = array(
									'platform' => 'all',
									'alias' => $new_uids,
									'msg' => array(
										'content' => '群主已将群 '.$group['gname'].' 解散！',
										'title' => '群组解散',
										'category' => '',
										'message' => array(
											'action' => 4, //群解散
											'type' => 3, //群组消息
											'details' => $group
										)
									)
								);
								$j_result = jpush($msg);
								if($j_result == 1101){
									logger('JPush---自定义简单发送----结果： 因群组成员未登录,消息推送失败!------完毕------'."\n");
									$data = array(
										'code' => 7,
										'message' => '解散群组成功!'
									);
								}else{
									logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
									$data = array(
										'code' => 1,
										'message' => '解散群组成功!'
									);
								}
							}else{
								logger('环信删除群组失败！'."\n");
								M()->rollback();
								$data = array(
									'code' => 6,
									'message' => '网络错误,请重试!'
								);
							}
						}else{
							logger('删除群组成员关系记录失败'."\n");
							M()->rollback();
							$data = array(
								'code' => 5,
								'message' => '网络错误,请重试!'
							);
						}
					}else{
						logger('删除群组记录失败'."\n");
						M()->rollback();
						$data = array(
							'code' => 4,
							'message' => '网络错误,请重试!'
						);
					}
				}else{
					logger('不是群主不能移除成员'."\n");
					$data = array(
						'code' => 4,
						'message' => '权限不足！'
					);
				}
			}else{
				logger('群组不存在！'."\n");
				$data = array(
					'code' => 3,
					'message' => '群组不存在！'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	// 修改群组信息 // 目前仅接受群名称、群描述、最大成员数  // 群成员拥有部分权限
	public function edit_group_info(){
		logger('修改群组信息');
		$post = I();
		logger('打印参数：'.var_export($post,TRUE)); //debug
		$gid = $post['gid'];
		$id = $post['id'];
		$gname = $post['name'];
		$dsp = $post['dsp']; //如果
		$maxusers = $post['maxusers'];
		if($id && $gid && ($gname || isset($dsp) || $maxusers)){
			$groups = D('easemob_groups');
			$group = $groups->where(array('id' => $id))->field('owner,maxusers,gname,dsp')->find();
			if($group){
				if($maxusers){ //修改最大成员数 必须大于当前最大成员数且小于等于2000
					logger('修改最大成员数！');
					if($maxusers > $group['maxusers']){
						$update_data = array(
							'maxusers' => $maxusers
						);
						$options = array(
							'maxusers' => $maxusers
						);
					}else{
						logger('调整的最大成员数应大于当前最大成员数！'."\n");
						$data = array(
							'code' => 4,
							'message' => '最大成员数低于当前最大成员数！'
						);
						exit(json_encode($data));
					}
				}
				if($gname){
					logger('修改群组名称！');
					$options = array(
						'groupname' => $gname
					);
					$update_data = array(
						'gname' => $gname	
					);
				}
				if(isset($dsp)){
					logger('修改群组说明！');
					$options = array(
						'description' => $dsp
					);
					$update_data = array(
						'dsp' => $dsp	
					);
				}
				//更新数据库
				M()->startTrans();
				$update_result = $groups->where(array('id' => $id))->save($update_data);
				if($update_result){
					logger('修改群组信息记录成功--下一步去环信修改群组信息！');
					$ea_result = easemob_edit_group($gid,$options);
					if($ea_result['error'] == ''){
						logger('修改群组信息成功！'."\n");
						M()->commit();
						$data = array(
							'code' => 1,
							'message' => '修改群组信息成功！'
						);
					}else{
						logger('环信-修改群组信息失败！'."\n");
						M()->rollback();
						$data = array(
							'code' => 0,
							'message' => '修改群组信息失败！'
						);
					}
				}else{
					logger('修改群组信息记录失败！');
					M()->rollback();
					$data = array(
						'code' => 4,
						'message' => '修改群组信息失败！'
					);
				}
			}else{
				logger('未查找到相应群组！'."\n");
				$data = array(
					'code' => 3,
					'message' => '未查找到相应群组！'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		//回复客户端
		exit(json_encode($data));
	}
	// 获取用户信息
	public function get_user_info(){
		logger('获取群聊中陌生人的信息');
		$post = I();
		$name = $post['name']; //陌生人环信用户名
		if($name){
			$app_user = D('app_user');
			$where = array(
				'store_simple_name' => strchr($name,'_',TRUE),
				'username' => ltrim(strchr($name,'_'),'_')
			);
			$user = $app_user->where($where)->field('uid,sid,username,store_simple_name,head,mobile,birth,realname,nickname,gender,qq,location,dept')->find();
			if($user){
				logger('查询到该群聊用户信息，返回成功！'."\n");
				$user['name'] = $user['store_simple_name'].'_'.$user['username'];
				if(strpos($user['head'],'Uploads/avatar')){
					$user['head'] = C('base_url').$user['head'];
				}
				unset($user['store_simple_name']);
				unset($user['username']);
				$data = array(
					'code' => 1,
					'message' => '用户信息返回成功！',
					'user' => $user
				);
			}else{
				logger('未查询到该群聊用户信息，查找失败！'."\n");
				$data = array(
					'code' => 0,
					'message' => '未查找到该用户信息！'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		exit(json_encode($data));
	}
	// 获取用户信息 应用于会话中的群聊
	public function get_group_user_info(){
		logger('获取群聊中陌生人的信息');
		$post = I();
		$name = $post['name']; //陌生人环信用户名
		$id = $post['id']; //群组id
		if($name && $id){
			//先判断本用户和陌生人是否都存在于该群组
			$relation = D('easemob_groups_users');
			$condition['gid'] = $id;
			$condition['name'] = array('in',array(session('store_simple_name').'_'.session('appuser'),$name));
			$result = $relation->where($condition)->field('id')->select();
			if(count($result) == 2){
				logger('确定两个用户群友关系，下一步去查询成员信息！');
				$app_user = D('app_user');
				$where = array(
					'store_simple_name' => strchr($name,'_',TRUE),
					'username' => ltrim(strchr($name,'_'),'_')
				);
				$user = $app_user->where($where)->field('uid,sid,username,store_simple_name,head,mobile,birth,realname,nickname,gender,qq,location,dept')->find();
				if($user){
					logger('查询到该群聊用户信息，返回成功！'."\n");
					$user['name'] = $user['store_simple_name'].'_'.$user['username'];
					if(strpos($user['head'],'Uploads/avatar')){
						$user['head'] = C('base_url').$user['head'];
					}
					unset($user['store_simple_name']);
					unset($user['username']);
					$data = array(
						'code' => 1,
						'message' => '用户信息返回成功！',
						'user' => $user
					);
				}else{
					logger('未查询到该群聊用户信息，查找失败！'."\n");
					$data = array(
						'code' => 0,
						'message' => '未查找到该用户信息！'
					);
				}
			}else{
				logger('确定两个用户群友关系，失败！非法获取用户信息！'."\n");
				$data = array(
					'code' => 3,
					'message' => '非法获取用户信息！'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全！'
			);
		}
		exit(json_encode($data));
	}
	////////////////////////////////////////////////////////
	// 同步群组  分两步：第一步将当前APP下所有群组获取并存储群组信息，会清空easemob_groups表 第二步：存储群组与组员关系信息 会清空easemob_groups_users表
	public function sysc_groups(){
		$groups = easemob_get_groups();
		// logger('群组：'.var_export($groups,TRUE));
		$addgroups = array();
		$n = 0;
		foreach($groups['data'] as $k => $v){
			$addgroups[$n] = array(
				'gid' => (int)$v['groupid'],
				'gname' => $v['groupname'],
				'affiliations' => $v['affiliations'],
				'ctime' => substr($v['created'],0,10),
				'mtime' => substr($v['last_modified'],0,10),
				'owner' => substr($v['owner'],'23'),
			);
			$where = array(
				'store_simple_name' => strchr(substr($v['owner'],'23'),'_',TRUE)
			);
			$store = D('store');
			$sid = $store->where($where)->field('id')->find();
			$addgroups[$n]['sid'] = $sid['id'];
			$the_group = easemob_get_the_group(array($v['groupid'])); //群组具体信息
			// logger('具体群组信息：'.var_export($the_group,TRUE)); //debug
			$addgroups[$n]['public'] = $the_group['data'][0]['public'] ? 1 : 0;
			$addgroups[$n]['dsp'] = $the_group['data'][0]['description'];
			$addgroups[$n]['allowinvites'] = $the_group['data'][0]['allowinvites'] ? 1 : 0;
			$addgroups[$n]['membersonly'] = $the_group['data'][0]['membersonly'] ? 1 : 0;
			$addgroups[$n]['maxusers'] = $the_group['data'][0]['maxusers'];
			foreach($the_group['data'][0]['affiliations'] as $x => $y){
				if($x == 0){
					if($y['owner']){
						$addgroups[$n]['members'] .= ' '.$y['owner'].' ';
					}else{
						$addgroups[$n]['members'] .= ' '.$y['member'].' ';
					}
					continue;
				}else{
					if($y['owner']){
						$addgroups[$n]['members'] .= ' '.$y['owner'].' ';
					}else{
						$addgroups[$n]['members'] .= $y['member'].' ';
					}
					continue;
				}
			}
			$n++;
		}
		// logger('群组：'.var_export($addgroups,TRUE)); //debug
		$easemob_groups = D('easemob_groups_copy');
		$max = $easemob_groups->field('gid')->count();
		if($max == 0){
			logger('目前环信群组中没有任何群组信息！将全部同步群组加入表');
			$add_result = $easemob_groups->addAll($addgroups);
			if($add_result){
				logger('群组同步成功！'."\n");
			}else{
				logger('群组同步失败！'."\n");
			}
		}else{
			logger('目前环信群组中已存在群组信息！将清空表数据，重新同步群组信息');
			$model = new Model();
			$sql_result = $model->execute('TRUNCATE ylou.easemob_groups_copy');
			if(!$sql_result){
				logger('群组表已清空，将同步数据加入表');
				$add_result = $easemob_groups->addAll($addgroups);
				if($add_result){
					logger('群组同步成功！'."\n");
					echo "success\n";
				}else{
					logger('群组同步失败！'."\n");
					echo "failed\n";
				}
			}else{
				logger('清空群组表数据时出错，同步中断！'."\n");
				$this->error('同步出错，请咨询管理员！');
			}
		}
	}
	// 同步组员与群组关系 及 群组与组员关系  //运行前先要运行 sysc_groups 同步最新群组信息  //调整方案 关系放在一张表中
	public function sysc_groups_users_relation(){
		logger('组员与群组关系同步开始！');
		$easemob_groups = D('easemob_groups_copy');
		$relations = $easemob_groups->field('id,sid,members')->select();
		$relas = array();
		$n = 0;
		foreach($relations as $k => $v){
			$members = explode(' ',trim($v['members']));
			foreach($members as $x){
				if($x != '' && $x != NULL){
					$relas[$n] = array(
						'gsid' => $v['sid'],
						'gid' => $v['id'],
						'uid' => $x,
						'ctime' => time()
					);
					$n++;
				}
			}
		}
		$app_user = D('app_user');
		foreach($relas as $k => $v){
			$where = array(
				'store_simple_name' => strchr($v['uid'],'_',TRUE),
				'username' => ltrim(strchr($v['uid'],'_'),'_')
			);
			$user = $app_user->where($where)->field('uid,sid')->find();
			$relas[$k]['uid'] = $user['uid'];
			$relas[$k]['sid'] = $user['sid'];
			$relas[$k]['name'] = $v['uid'];
		}
		$model = new Model();
		$sql_result = $model->execute('TRUNCATE ylou.easemob_groups_users_copy');
		if(!$sql_result){
			logger('清空群组与组员关系表顺利完成！');
			$relation = D('easemob_groups_users_copy');
			$add_result = $relation->addAll($relas);
			if($add_result){
				logger('同步群组与组员关系成功！');
				echo 'success!';
			} else{
				logger('同步群组与组员关系失败！');
				echo 'failed!';
			}
		}else{
			logger('清空群组与组员关系表失败！');
			echo 'early_failed!';
		}
	}
	////////////////////////////////////////////////////////////////////////////////
}
	
<?php
namespace Home\Controller;
use Think\Controller;
class AttenceController extends Controller{
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
	}
	//预留
	public function index(){

	}
	//查询全局考勤设置 API接口
	public function query_whole(){
		logger('查询全局考勤设置'); 
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		if(session('wtype') == 1){
			//默认查询当前店铺
			$sid = session('sid');
			$whole = D('attence_whole');
			$where = array(
				'sid' => $sid
			);
			$the_whole = $whole->field('add_time,add_admin,modify_time,modify_admin,id,sid',TRUE)->where($where)->find();
			if($the_whole){
				logger('店铺存在全局设置，返回成功！'."\n"); 
				$data = array(
					'code' => 1,
					'message' => '全局设置返回成功！',
					'whole' => $the_whole
				);
				exit(json_encode($data));
			}else{
				logger('店铺不存在全局设置，将默认设置返回'."\n");
				$the_whole = array(
					'privilege_time' => 0,
					'latetime' => 0,
					'absent' => 0,
					'sply' => 1,
					'ontip' => 0,
					'offtip' => 0,
					'outtip' => 0,
					'earlytime' => 180
				);
				$data = array(
					'code' => 1,
					'message' => '全局设置返回成功！',
					'whole' => $the_whole
				);
				exit(json_encode($data));
			}
		}else{
			$data = array(
				'code' => '2',
				'message' => '没有权限！'
			);
			logger('没有权限！'."\n");
			exit(json_encode($data));
		}	
	}
	//更新考勤全局设置
	public function update_whole(){
		logger('更新考勤全局设置');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		//需要超级管理员权限
		if(session('wtype') == 1){
			$privilege_time = $post['privilege_time'];
			$latetime = $post['latetime'];
			$absent = $post['absent'];
			$sply = $post['sply'];
			$ontip = $post['ontip'];
			$offtip = $post['offtip'];
			$earlytime = $post['earlytime'];
			$outtip = $post['outtip'];
			//最少传入一个参数
			if($privilege_time || $latetime || $absent || $sply || $ontip || $offtip || $earlytime || $outtip){
				//数据准备
				$data =array(
					'privilege_time' => $privilege_time,
					'latetime' => $latetime,
					'absent' => $absent,
					'sply' => $sply,
					'ontip' =>	$ontip,
					'offtip' => $offtip,
					'earlytime' => $earlytime,
					'outtip' => $outtip
				);
				// $data = array_filter($data);
				// 先查验是否已存在全局设置
				$whole = D('attence_whole');
				$where = array(
					'sid' => session('sid')
				);
				$my_whole = $whole->where($where)->find();
				if($my_whole){
					logger('已存在该影楼全局设置--->去更新');
					$data['modify_time'] = time();
					$data['modify_admin'] = session('uid');
					$where = array(
						'id' => $my_whole['id']
					);
					$result = $whole->where($where)->save($data);
				}else{
					logger('还不存在该影楼全局设置--->去新建');
					$data['add_time'] = time();
					$data['add_admin'] = session('uid');
					$result = $whole->add($data);
				}
				if($result){
					$data = array(
						'code' => '1',
						'message' => '修改成功！'
					);
					logger('修改成功！'."\n");
					exit(json_encode($data));
				}else{
					$data = array(
						'code' => '0',
						'message' => '修改失败！'
					);
					logger('修改失败！'."\n");
					exit(json_encode($data));
				}
			}else{
				$data = array(
					'code' => '3',
					'message' => '参数不全，请重试！'
				);
				logger('参数不全！'."\n");
				exit(json_encode($data));
			}	
		}else{
			$data = array(
				'code' => '2',
				'message' => '没有权限！'
			);
			logger('没有权限！'."\n");
			exit(json_encode($data));
		}
	}
	//添加考勤组
	public function new_attence_group(){
		logger('新建考勤组-->');
		if(session('wtype') == 1){
			$post = I('get.'); //先读取GET数组
			if(empty($post)){ //如果GET数组为空,则读取POST数组
				logger('GET数组为空!');
				$post = I('post.');
			}
			logger('传入参数：'.var_export($post,TRUE));
			$name = $post['name'];
			$users = $post['users'];
			$type = $post['type'];
			$admins = $post['admins'];
			$routers= $post['routers'];
			$locations = $post['locations'];
			$privilege_meter = $post['privilege_meter'];
			$rules = $post['rules'];
			if(($name && $users && $type && $rules ) && ($routers || $locations)){
				logger('参数齐备，修改数据库->');
				//////////////////////////////////////////////////////////////////////////////////////////////////
				// 把json转成数组后转成字符串，将字符串保存到数据库中和保存json字符串一样 放弃费事倒腾一遍，直接存储json字符串试试
				//数据准备
				// $group_admins = session('uid').' '; //考勤组管理员默认是当前的admin和拥有管理员权限的当前用户
				// if($post['admins']){
				// 	$admins = json_decode($admins,TRUE);
				// 	$group_admins = $this->toString($admins);
				// }
				// $group_users = '';
				// if($users){
				// 	$users = json_decode($users,TRUE);
				// 	$group_users = $this->toString($users);
				// }
				// $group_routers = '';
				// if($routers){
				// 	$routers = json_decode($routers,TRUE);
				// 	$group_routers = $this->toString($routers);
				// }
				// $group_locations = '';
				// if($locations){
				// 	$locations = json_decode($locations,TRUE);
				// 	$group_locations = $this->toString($locations);
				// }
				//规则  顾定班制为以日期为单位，天为变化单位。 排班制以月为单位更新，天为变化单位
				// $rules = json_decode($rules,TRUE);
				// $rules = $this->toString($rules);
				//////////////////////////////////////////////////////////////////////////////////////////////////
				$new_group = array(
					'name' => $name,
					'type' => $type,
					'group_admin' => $admins,
					'sid' => session('sid'),
					'add_time' => time(),
					'add_admin' => session('uid'),
					'group_users' => $users,
					'routers' => $routers,
					'locations' => $locations,
					'rules' => $rules,
					'privilege_meter' => $privilege_meter,
					'month' => date('m',time())
				);
				$group = D('attence_group');
				$result = $group->add($new_group);
				if($result){
					logger('新建考勤组数据写入数据库成功！');
					logger('去修改考勤组负责人的员工记录-->');
					$admin = D('app_user');
					$susers = $this->query_users_agroup(); //获取店铺员工所在考勤组情况
					$admins = $this->chanslate_json_to_array($admins);
					// logger('管理员数组:'.var_export($admins,TRUE)); //debug
					foreach($susers as $x => $y){
						foreach($admins as $v){
							if($y['uid'] == $v){
								if($y['type'] == 2 || $y['type'] == 1){ //当该员工已经是管理员或超级管理员时,只追加组id
									$update_data = array(
										'attence_admin_group' => $y['attence_admin_group'].' '.$result, //当取出时需要先清除收尾两端的多余空格
										'modify_time' => time()
									);
								}else{
									$update_data = array(
										'type' => 2,
										'attence_admin_group' => $result,
										'modify_time' => time()
									);
								}
								$where = array(
									'uid' => $y['uid']
								);
								$update_admin_result = $admin->where($where)->save($update_data);
								if($update_admin_result){
									logger('修改管理员记录成功！');
								}else{
									logger('修改管理员记录失败！');
								}
							}
						}
					}
					logger('之后去修改考勤组成员员工的记录');
					$users = $this->chanslate_json_to_array($users);
					// logger('成员数组:'.var_export($users,TRUE)); //debug
					foreach($susers as $x => $y){
						foreach($users as $v){
							//在更新员工的考勤组id前，需要将已存在某考勤组的成员之前的签到记录状态更新
							// $the_user = $admin->where($where)->find();
							// if($the_user['attence_group'] != NULL || $the_user['attence_group'] != ''){
								//这可能会耗时比较长 2秒左右
								// $this->update_status_records($v,$result,session('sid'),1);
							// }
							if($y['uid'] == $v){
								if($y['attence_group'] == 0 || $y['attence_group'] == '' || $y['attence_group'] == NULL){
									$update_data = array(
										'attence_group' => $result,
										'modify_time' => time()
									);
									$where = array(
										'uid' => $y['uid']
									);
									$update_user_result = $admin->where($where)->save($update_data);
									if($update_admin_result){
										logger($v.' 该员工原不属于任何考勤组,修改员工记录成功！');
									}else{
										logger($v.'该员工原不属于任何考勤组,修改员工记录失败！');
									}
								}else{	
									logger($v.' 该员工原属于 '.$y['attence_group'].' 考勤组!');
									//修改员工的原考勤组,清除该员工的考勤规则和考勤组员信息
									$update_where = array(
										'id' => $y['attence_group']
									);
									//该员工原来所在考勤组
									$old_group = $group->where($update_where)->find();
									//删除原考勤组该成员
									$update_group_users = str_replace('&quot;'.$y['uid'].'&quot;,','',$old_group['group_users']);
									$update_data['group_users'] = $update_group_users; 
									//如果原考勤组是排班制,则还需删除该员工的考勤规则
									if($old_group['type'] == 2){
										$old_rules = chanslate_json_to_array($old_group['rules']);
										foreach($old_rules as $m => $n){
											if($n['uid'] == $v['uid']){
												unset($old_rules[$m]);
												break;
											}
										}
										//将新考勤规则转换为json
										$update_rules = '[';
										foreach($old_rules as $m => $n){
											$update_rules .= '{&quot;username&quot;:&quot;'.$n['username'].'&quot;,&quot;realname&quot;:&quot;'.$n['realname'].'&quot;,&quot;nickname&quot;:&quot;'.$n['nickname'].'&quot;,&quot;uid&quot;:&quot;'.$n['uid'].'&quot;,&quot;detail&quot;:[';
											foreach($n['detail'] as $p => $q){
												$update_rules .= '{&quot;classid&quot;:&quot;'.$q['classid'].'&quot;,&quot;classname&quot;:&quot;'.$q['classname'].'&quot;,&quot;start&quot;:&quot;'.$q['start'].'&quot;,&quot;end&quot;:&quot;'.$q['end'].'&quot;,&quot;date&quot;:&quot;'.$q['date'].'&quot;},';
											}
											$update_rules = rtrim($update_rules,',');
           	 								$update_rules .= ']},';
										}
										$update_rules = rtrim($update_rules,',');
         								$update_rules .= ']';
         								$update_data['rules'] = $update_rules;
									}
									$update_result = $group->where($update_where)->save($update_data);
									if($update_result){
										logger('员工'.$y['uid'].'的原考勤组设置更新success！');
									}else{
										logger('员工'.$y['uid'].'的原考勤组设置更新failed！');
									}
									//修改该员工新的考勤组ID
									$update_data = array();
									$update_data = array(
										'attence_group' => $result,
										'modify_time' => time()
									);
									$where = array(
										'uid' => $y['uid']
									);
									$update_user_result = $admin->where($where)->save($update_data);
									if($update_admin_result){
										logger($v.' 该员工原不属于任何考勤组,修改员工记录成功！');
									}else{
										logger($v.'该员工原不属于任何考勤组,修改员工记录失败！');
									}
								}
								break;
							}
						}
					}
					$data = array(
						'code' => '1',
						'message' => '新建考勤组成功！'
					);
					logger('新建考勤组成功！'."\n");
					exit(json_encode($data));
				}else{
					$data = array(
						'code' => '0',
						'message' => '新建考勤组失败！'
					);
					logger('新建考勤组失败！'."\n");
					exit(json_encode($data));
				}
			}else{
				$data = array(
					'code' => '3',
					'message' => '参数不全，请重试！'
				);
				logger('参数不全！'."\n");
				exit(json_encode($data));
			}
		}else{
			$data = array(
				'code' => '2',
				'message' => '没有权限！'
			);
			logger('没有权限！'."\n");
			exit(json_encode($data));
		}	
	}
	//更新考勤组设置
	public function update_attence_group(){
		logger('更新考勤组设置--->');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		$gid = $post['gid'];
		//从session中判断用户身份，不需再查询考勤组情况了
		// //查找考勤组
		$group = D('attence_group');
		$where = array(
			'id' => $gid
		);
		// $result = $group->where($where)->find();
		// 判断当前用户是否为考勤组管理员（负责人）或为店铺管理员（超级管理员）
		if((session('wtype') == 1) || strpos(session('admin_group'),session('uid'))){
			logger('管理员：'.session('admin_name').'更新ID为：'.$gid.'考勤组设置');
			$name = $post['name'];
			$users = $post['users'];
			$type = $post['type'];
			$admins = $post['admins'];
			$routers= $post['routers'];
			$locations = $post['locations'];
			$privilege_meter = $post['privilege_meter'];
			$rules = $post['rules'];
			if($name || $users || $admins || $type || $rules || $routers || $locations || $privilege_meter){
				//////////////////////////////////////////////////////////////////////////////////////////////////
				// 把json转成数组后转成字符串，将字符串保存到数据库中和保存json字符串一样 放弃费事倒腾一遍，直接存储json字符串试试
				//数据准备
				// $group_admins = ''; //考勤组管理员默认是当前的admin和拥有管理员权限的当前用户
				// if($post['admins']){
				// 	$admins = json_decode($admins,TRUE);
				// 	$group_admins = $this->toString($admins);
				// }
				// $group_users = '';
				// if($users){
				// 	$users = json_decode($users,TRUE);
				// 	$group_users = $this->toString($users);
				// }
				// $group_routers = '';
				// if($routers){
				// 	$routers = json_decode($routers,TRUE);
				// 	$group_routers = $this->toString($routers);
				// }
				// $group_locations = '';
				// if($locations){
				// 	$locations = json_decode($locations,TRUE);
				// 	$group_locations = $this->toString($locations);
				// }
				//////////////////////////////////////////////////////////////////////////////////////////////////
				$update_data = array(
					'name' => $name,
					'type' => $type,
					'group_admin' => $admins,
					'modify_time' => time(),
					'modify_admin' => session('uid'),
					'group_users' => $users,
					'routers' => $routers,
					'locations' => $locations,
					'rules' => $rules,
					'privilege_meter' => $privilege_meter
				);
				$update_data = array_filter($update_data);
				$letter = array('name','type','group_admin','group_users','routers','locations','rules','privilege_meter');
				foreach($letter as $l){
					if($update_data[$l] && ($update_data[$l] == 'null' || $update_data[$l] == 'NULL')){
						unset($update_data[$l]);
					}
				}
				// logger('更新数组:'.var_export($update_data,TRUE));//debug
				// die; 
				$result = $group->where($where)->save($update_data);
				// $sql = $group->getLastsql(); //debug
				// logger('插入语句:'.$sql); //debug
				if($result){
					// logger('修改考勤组设置完成，下面查看是否需要修改管理员记录');
					//先要查询管理员和组成员数组
					$where = array(
						'id' => $gid
					);
					$the_group = $group->where($where)->find();
					$admins = $the_group['group_admin'];
					$users = $the_group['group_users'];
					if($admins){
						// logger('考勤组管理员变动，需要修改用户表，更新管理员设置');
						$admins = $this->chanslate_json_to_array($admins); //将json字符串转成数组
						$susers = $this->query_users_agroup(); //获取店铺员工所在考勤组情况
						$app_user = D('app_user');
						$size = count($admins); //计算数组元素个数 
						if($susers){
							foreach($susers as $k => $v){ //用户组是二维数组
								$i = 1;
								foreach($admins as $value){ //管理员数组是一维
									if($v['uid'] == $value){
										// logger('原管理考勤组:'.$v['attence_admin_group'].'现考勤组:'.$gid);
										if(!strpos($v['attence_admin_group'],$gid)){ //当该员工作为管理员的考勤组不为当前考勤组时，更新管理员考勤组id设置
											$where = array(
												'uid' => $v['uid']
											);
											$data = array(
												'attence_admin_group' => $v['attence_admin_group'].' '.$gid
											);
											if($v['type'] == 0){
												$data['type'] = 2;
											}
											// logger('更新数组1:'.var_export($data,TRUE)); //debug
											$update = $app_user->where($where)->save($data);
											if($update){
												logger('员工'.$v['uid'].'考勤组ID设置更新完成！');
											}else{
												logger('员工'.$v['uid'].'考勤组ID设置更新失败失败失败！');
											}
											break;
										}
									}else{
										if($i == $size){ //如果员工id不在管理员数组里
											// logger('原管理考勤组:'.$v['attence_admin_group'].'现考勤组:'.$gid);
											if(strpos($v['attence_admin_group'],$gid)){ // 而且员工以前是该考勤组管理员，则现在撤销
												$where = array(
													'uid' => $v['uid']
												);
												if($v['attence_admin_group'] == ' '.$gid || $v['attence_admin_group'] == $gid){
													logger('原来只管理这一个组,现在撤销组长资格'); //debug
													$data = array(
														'attence_admin_group' => str_replace(' '.$gid,'',$v['attence_admin_group'])
													);
													if($v['type'] == 2){ //以前只是组长,现在撤销. 高级管理员不能更改
														$data['type'] == 0;
													}
												}elseif(strpos($v['attence_admin_group'],' '.$gid.' ')){
													// logger('还管理着其他考勤组,撤销这个组的组长,但还是考勤组长');
													$data = array(
														'attence_admin_group' => str_replace(' '.$gid.' ',' ',$v['attence_admin_group'])
													);
												}elseif(strpos($v['attence_admin_group'],' '.$gid)){
													// logger('可能还有其他组,当前组在最后一位'); 
													$data = array(
														'attence_admin_group' => str_replace(' '.$gid,'',$v['attence_admin_group'])
													);
												}else{
													logger('- -');
												}
												// logger('更新数组2:'.var_export($data,TRUE)); //debug
												$update = $app_user->where($where)->save($data);
												if($update){
													logger('员工'.$v['uid'].'考勤组ID设置更新完成！');
												}else{
													logger('员工'.$v['uid'].'考勤组ID设置更新失败失败失败！');
												}
											}
										}
										$i++;
									}
								}
							}
						}else{
							logger('获取店铺员工所在考勤组出错，请查证！');
						}
					}else{
						logger('考勤组管理员未变!');
						// logger('考勤组清空了管理员');
						// $susers = $this->query_users_agroup(); //获取店铺员工所在考勤组情况
						// foreach($susers as $k => $v){
						// 	if(strpos($v['attence_admin_group'],$gid.' ') == 0){
						// 		$update['attence_admin_group'] = str_replace($gid.' ','',$v['attence_admin_group']);
						// 	}elseif(strpos($v['attence_admin_group'],' '.$gid.' ') == 0){
						// 		$update['attence_admin_group'] = str_replace(' '.$gid.' ','',$v['attence_admin_group']);
						// 	}elseif(strpos($v['attence_admin_group'],' '.$gid.' ') > 0){
						// 		$update['attence_admin_group'] = str_replace($gid.' ','',$v['attence_admin_group']);
						// 	}else{
						// 		logger('- -');
						// 	}
						// 	$app_user = D('app_user');
						// 	$where = array(
						// 		'uid' => $v['uid']
						// 	);
						// 	$update = $app_user->where($where)->save($update);
						// 	if($update){
						// 		logger('员工'.$v['uid'].'考勤组管理员ID设置更新完成！');
						// 	}else{
						// 		logger('员工'.$v['uid'].'考勤组管理员ID设置更新失败失败失败！');
						// 	}
						// }
					}
					logger('修改考勤组设置完成，下面查看是否需要修改考勤组成员记录');
					if($users){
						logger('考勤组管理员变动，需要修改用户表，更新管理员设置');
						$users = $this->chanslate_json_to_array($users); //将json字符串转成数组
						$susers = $this->query_users_agroup(); //获取店铺员工所在考勤组情况
						$app_user = D('app_user');
						$size = count($users); //计算数组元素个数 
						if($susers){
							foreach($susers as $k => $v){ //用户组是二维数组
								$i = 1;
								foreach($users as $value){ //管理员数组是一维
									if($v['uid'] == $value){
										if($v['attence_group'] != $gid){ //当该员工的考勤组不为当前考勤组时，更新考勤组id设置
											// 更新考勤组id前，先判断是否需要更新员工之前的考勤记录
											// if($v['attence_group'] != NULL || $v['attence_group'] != ''){
												//耗时会长一些 2秒左右
												// $this->update_status_records($v['uid'],$v['attence_group'],$v['sid'],1);
											// }
											$where = array(
												'uid' => $v['uid']
											);
											$data = array(
												'attence_group' => $gid
											);
											$update = $app_user->where($where)->save($data);
											if($update){
												logger('员工'.$v['uid'].'考勤组成员ID更新完成！');
											}else{
												logger('员工'.$v['uid'].'考勤组成员ID更新失失败！');
											}
											//修改员工的原考勤组,清除该员工的考勤规则和考勤组员信息
											$update_where = array(
												'id' => $v['attence_group']
											);
											//该员工原来所在考勤组
											$old_group = $group->where($update_where)->find();
											// logger('原考勤组成员:'.$old_group['group_users'].'该成员:'.$v['uid']);  //debug
											// logger(strpos($old_group['group_users'],'&quot;'.$v['uid'].'&quot;'));
											//删除原考勤组该成员
											if(strpos($old_group['group_users'],'&quot;'.$v['uid'].'&quot;,')){
												// logger('原考勤组还有其他成员'); //debug
												$update_group_users = str_replace('&quot;'.$v['uid'].'&quot;,','',$old_group['group_users']);
											}elseif(strpos($old_group['group_users'],'&quot;'.$v['uid'].'&quot;') == 1 && !strpos($old_group['group_users'],'&quot;'.$v['uid'].'&quot;,')){
												// logger('原考勤组只有该成员'); //debug
												$update_group_users = '[]';
											}else{
												// logger('考勤组有其他成员,我在最后一位');
												$update_group_users = str_replace(',&quot;'.$v['uid'].'&quot;','',$old_group['group_users']);
											}
											$update_data['group_users'] = $update_group_users; 
											//如果原考勤组是排班制,则还需删除该员工的考勤规则
											if($old_group['type'] == 2){
												$old_rules = chanslate_json_to_array($old_group['rules']);
												foreach($old_rules as $m => $n){
													if($n['uid'] == $v['uid']){
														unset($old_rules[$m]);
														break;
													}
												}
												//将新考勤规则转换为json
												$update_rules = '[';
												foreach($old_rules as $m => $n){
													if($n['uid'] != $v['uid']){
														$update_rules .= '{&quot;username&quot;:&quot;'.$n['username'].'&quot;,&quot;realname&quot;:&quot;'.$n['realname'].'&quot;,&quot;nickname&quot;:&quot;'.$n['nickname'].'&quot;,&quot;uid&quot;:&quot;'.$n['uid'].'&quot;,&quot;detail&quot;:[';
														foreach($n['detail'] as $x => $y){
															$update_rules .= '{&quot;classid&quot;:&quot;'.$y['classid'].'&quot;,&quot;classname&quot;:&quot;'.$y['classname'].'&quot;,&quot;start&quot;:&quot;'.$y['start'].'&quot;,&quot;end&quot;:&quot;'.$y['end'].'&quot;,&quot;date&quot;:&quot;'.$y['date'].'&quot;},';
														}
														$update_rules = rtrim($update_rules,',');
	                   	 								$update_rules .= ']},';
	                   	 							}
												}
												$update_rules = rtrim($update_rules,',');
                 								$update_rules .= ']';
                 								$update_data['rules'] = $update_rules;
											}
											// logger('更新信息数组:'.var_export($update_data,TRUE)); //debug
											$update_result = $group->where($update_where)->save($update_data);
											$sql = $group->getLastsql(); //debug
											// logger('更新语句:'.$sql); //debug
											if($update_result){
												logger('员工'.$v['uid'].'的原考勤组设置更新success！');
											}else{
												logger('员工'.$v['uid'].'的原考勤组设置更新failed！');
											}
											break;
										}
									}else{
										if($i == $size){ //如果员工id不在成员员数组里
											if($v['attence_group'] == $gid){ // 而且员工以前是该考勤组成员，则现在撤销
												// 更新考勤组id前，先判断是否需要更新员工之前的考勤记录
												//耗时会长一些 2秒左右
												// $this->update_status_records($v['uid'],$v['attence_group'],$v['sid'],1);
												$where = array(
													'uid' => $v['uid']
												);
												$data = array(
													'attence_group' => '' //直接清空考勤组设置
												);
												$update = $app_user->where($where)->save($data);
												if($update){
													logger('员工'.$v['uid'].'考勤组ID设置更新完成！');
												}else{
													logger('员工'.$v['uid'].'考勤组ID设置更新失败失败失败！');
												}
											}
										}
										$i++;
									}
								}
							}
						}else{
							logger('获取店铺员工所在考勤组出错，请查证！');
						}
					}
					$data = array(
						'code' => '1',
						'message' => '修改成功！'
					);
					logger('修改成功！'."\n");
					exit(json_encode($data));
				}else{
					$data = array(
						'code' => '0',
						'message' => '修改失败！'
					);
					logger('修改失败！'."\n");
					exit(json_encode($data));
				}
			}else{
				$data = array(
					'code' => '3',
					'message' => '参数不全，请重试！'
				);
				logger('参数不全！'."\n");
				exit(json_encode($data));
			}
		}else{
			$data = array(
				'code' => '2',
				'message' => '没有权限！'
			);
			logger('没有权限！'."\n");
			exit(json_encode($data));
		}
	} 
	//查询店铺影楼所有员工（有username的） 所在考勤组情况
	public function query_users_agroup(){
		logger('查询店铺所有‘员工’所在考勤组情况');
		$app_user = D('app_user');
		$where = array(
			'sid' => session('sid'),
			'username' => array(array('neq',''),array('neq',NULL),'or')
		);
		$result = $app_user->where($where)->field('uid,type,attence_group,attence_admin_group')->select();
		if($result){
			logger('查询到店铺员工们所在考勤组情况');
			// logger('查询情况：'.var_export($result,TRUE)); //debug
			return $result;
		}else{
			logger('没有查询结果！可能有错误！');
			return FALSE;
		}
	}
	//删除考勤组
	public function del_attence_group(){
		$post = I();
		$gid = $post['gid'];
		// 判断当前用户是否为店铺管理员（超级管理员）
		if(session('wtype') == 1){
			logger('管理员：'.session('admin_name').'删除删除删除ID为：'.$gid.' 的考勤组！');
			$where = array(
				'id' => $gid
			);
			$group = D('attence_group');
			// 先需要释放考勤组成员和管理员
			logger('先清空考勤组成员和管理员');
			$the_group = $group->where($where)->find();
			//组成员
			$users = $this->chanslate_json_to_array($the_group['group_users']);
			$app_user = D('app_user');
			foreach($users as $value){
				$where = array(
					'uid' => $value
				);
				$data = array(
					'attence_group' => '' //将考勤组id清空
				);
				$result = $app_user->where($where)->save($data);
				if($result){
					logger('ID:'.$value.'组员清除完成！');
				}else{
					logger('ID:'.$value.'组员清除失败！');
				}
				//更新组成员过去的签到记录状态; 耗时会比较长，暂时没有设返回值
				// $this->update_status_records($value,$gid,session('sid'),1);
			}
			// 考勤组管理员
			$admins = $this->chanslate_json_to_array($the_group['group_admin']);
			$susers = $this->query_users_agroup(); //获取店铺员工所在考勤组情况
			foreach($susers as $x => $y){
				foreach($admins as $value){
					if($y['uid'] == $value){
						if($the_admin['attence_admin_group'] == ' '.$gid || $the_admin['attence_admin_group'] == $gid){ //如果只管理一个组
							$data['attence_admin_group'] == '';
							if($the_admin['type'] == 2){ // 如果现在是组管理员，则变为普通员工；如果是超级管理员则不变
								$data['type'] = 0;
							}
						}else{ // 如果管理多个组
							logger('该员工同时管理多个组');
							$data['attence_admin_group'] == str_replace(' '.$gid,'',$the_admin['attence_admin_group']);
							// 管理多个组，只清除一个组，还是组管理员
						}
						$where = array(
							'uid' => $value
						);
						$result = $app_user->where($where)->save($data);
						if($result){
							logger('清除管理员id成功!');
						}else{
							logger('清除管理员idfailed!');
						}
						break;
					}
				}
			}
			$where = array(
				'id' => $gid
			);
			$result = $group->where($where)->delete();
			if($result){
				$data = array(
					'code' => '1',
					'message' => '删除成功！'
				);
				logger('删除成功！'."\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => '0',
					'message' => '删除失败！'
				);
				logger('删除失败！'."\n");
				exit(json_encode($data));
			}
		}else{
			$data = array(
				'code' => '2',
				'message' => '没有权限！'
			);
			logger('没有权限！'."\n");
			exit(json_encode($data));
		}
	}
	//展示考勤组
	public function list_attence_group(){
		logger('展示影楼或管理员名下考勤组总体情况');
		if(session('wtype') == 1){
			$where = array(
				'sid' => session('sid')
			);
		}else{
			//查询作为管理员的多个考勤组
			$admin_groups = ltrim(session('admin_group'),' ');
			$admin_groups = explode(' ',$admin_groups);
			$string = '';
			foreach($admin_groups as $a){
				$string .= ','.$a;
			}
			$string = ltrim($string,',');
			$where = array(
				'sid' => session('sid'),
				'id' => array('in',$string)
			);
		}
		$group = D('attence_group');
		$result = $group->where($where)->field('add_time,add_admin,modify_time,modify_admin',TRUE)->select();
		$sql = $group->getLastsql(); //debug
		logger('查询考勤组语句:'.$sql); //debug
		if($result){
			foreach($result as $k => $v){
				$classes = array();
				// 解析JSON字符串成数组格式 再返回客户端
				$result[$k]['group_admin'] = $this->chanslate_json_to_array($v['group_admin']);
				$result[$k]['group_users'] = $this->chanslate_json_to_array($v['group_users']);
				$result[$k]['routers'] = $this->chanslate_json_to_array($v['routers']);
				$result[$k]['locations'] = $this->chanslate_json_to_array($v['locations']);
				$result[$k]['rules'] = $this->chanslate_json_to_array($v['rules']);
				//统计使用班次情况
				switch($result[$k]['type']){
					case 1:
						$i = 0;
						foreach($result[$k]['rules'] as $key => $value){
							if($i == 0){
								$i++;
								$classes[0]['classname'] = $value['classname'];
								$classes[0]['start'] = $value['start'];
								$classes[0]['end'] = $value['end'];
								$classes[0]['classid'] = $value['classid'];
							}else{
								$size = count($classes);
								$x = 1;
								foreach($classes as $m => $n){
									if($value['classname'] == $n['classname']){
										break;
									}else{
										if($x == $size){
											$classes[$size]['classname'] = $value['classname'];
											$classes[$size]['start'] = $value['start'];
											$classes[$size]['end'] = $value['end'];
											$classes[$size]['classid'] = $value['classid'];
										}
										$x++;
									}
								}
							}
						}
						$result[$k]['rule1'] = $result[$k]['rules'];
						$result[$k]['rules'] = NULL;
						break;
					case 2:
						$i = 0;
						foreach($result[$k]['rules'] as $key => $value){
							foreach($value['detail'] as $m => $n){
								if($i == 0 ){
									$i++;
									$classes[0]['classname'] = $n['classname'];
									$classes[0]['start'] = $n['start'];
									$classes[0]['end'] = $n['end'];
									$classes[0]['classid'] = $n['classid'];
								}else{
									$size = count($classes);
									$m = 1;
									foreach($classes as $x => $y){
										if($n['classname'] ==  $y['classname']){
											break;
										}else{
											if($m == $size){
												$classes[$size]['classname'] = $n['classname'];
												$classes[$size]['start'] = $n['start'];
												$classes[$size]['end'] = $n['end'];
												$classes[$size]['classid'] = $n['classid'];
											}
											$m++;
										}
									}
								}
							}
						}
						$result[$k]['rule2'] = $result[$k]['rules'];
						$result[$k]['rules'] = NULL;
						break;
					default:
						break;
				}
				$result[$k]['classes'] = $classes;
				unset($result[$k]['rules']);
			}
			// logger('考勤组数据：'.var_export($result,TRUE)); //debug
			$data = array(
				'code' => '1',
				'message' => '考勤组信息显示成功！',
				'result' => $result
			);
			logger('考勤组信息显示成功！'."\n");
			exit(json_encode($data));
		}else{
			$data = array(
				'code' => '0',
				'message' => '没有可管理考勤组！'
			);
			logger('没有可管理考勤组！'."\n");
			exit(json_encode($data));
		}
	}
	//转译数据库中读取的JSON字符串
	private function chanslate_json_to_array($json){
		$json = str_replace('&quot;','"',$json);
		// logger('替换转译符号后,字符串:'.$json); //debug
		$array = json_decode($json,TRUE);
		// logger('转译后数组:'.var_export($array,TRUE)); //debug
		return $array;
	}
	//查询员工是否已存在某个考勤组  //考勤组不能重复
	public function check_is_in_group(){
		logger('查询用户是否已存在于某个考勤组'); 
		$where = array(
			'sid' => session('sid'),
			'group_users' => array('like',session('uid'))
		);
		$group = D('attence_group');
		$result = $group->where($where)->find();
		if($result){
			$data = array(
				'code' => '0',
				'message' => '用户已存在于其他考勤组!',
				'aid' => $result['id']
			);
			logger('用户已存在于考勤组：'.$result['id']."\n");
			exit(json_encode($data));
		}else{
			$data = array(
				'code' => '1',
				'message' => '用户尚未存在于其他考勤组!'
			);
			logger('用户尚未存在于其他考勤组！'."\n");
			exit(json_encode($data));
		}
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// 将一维数组连成字符串 默认以空格为分隔符 || 把json转成数组后转成字符串，将字符串保存到数据库中和保存json字符串一样 放弃费事倒腾一遍，直接存储json字符串试试
	// public function toString($array,$str){
	// 	if(empty($str)){
	// 		$str = ' ';
	// 	}
	// 	if(is_array($array)){
	// 		$string = '';
	// 		foreach($array as $k => $v){
	// 			$string .= $v.$str;
	// 		}
	// 		$string = rtrim($group_users,$str);
	// 		return $string;
	// 	}else{
	// 		return 'error';
	// 	}
	// }
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//新增班次 更新班次
	public function new_attence_class(){
		logger('新建或更新考勤班次');
		if(session('wtype') == 1){
			$post = I();
			logger('传入参数：'.var_export($post,TRUE)); //debug
			$aid = $post['id'];
			$start = $post['start'];
			$end = $post['end'];
			$sid = session('sid');
			if(!empty($aid)){
				logger('更新考勤班次ID:'.$aid);
				$update_data = array(
					'start' => $start,
					'end' => $end,
					'modify_time' => time(),
					'modify_admin' => session('uid')
				);
				$update_data = array_filter($update_data);
				if(empty($update_data)){
					$data = array(
						'code' => '2',
						'message' => '提交参数不全!'
					);
					logger('提交参数不全'."\n");
					exit(json_encode($data));
				}
				$where = array(
					'classid' => $aid,
					'sid' => $sid
				);
				$attence_class = D('attence_class');
				//在更新前先将店铺所有员工之前的签到记录更新 
				// $map = array(
				// 	'sid' => $sid, //整个店铺的员工
				// 	// 'attence_group' => array(array('neq',''),array('neq',NULL),'OR') //考勤组id不为空
				// );
				// $app_user = D('app_user');
				// $users = $app_user->where($map)->select();
				// foreach($users as $k => $v){
				// 	if($v['attence_group'] != NULL && $v['attence_group'] != ''){
				// 		$this->update_status_records($v['uid'],$v['attence_group'],$v['sid'],1);
				// 	}else{
				// 		$this->update_status_records_nogroup($v['uid'],$v['sid'],1);
				// 	}
				// }
				//耗时会比较长，尤其是店铺员工比较多的情况
				$result = $attence_class->where($where)->save($update_data);
				if($result){
					$data = array(
						'code' => '1',
						'message' => '修改成功！'
					);
					logger('修改成功！'."\n");
					exit(json_encode($data));
				}else{
					$data = array(
						'code' => '0',
						'message' => '修改失败！'
					);
					logger('修改失败！'."\n");
					exit(json_encode($data));
				}
			}else{
				logger('新建考勤班次');
				if(!$start || !$end){
					$data = array(
						'code' => '2',
						'message' => '提交参数不全!'
					);
					logger('提交参数不全'."\n");
					exit(json_encode($data));
				}
				$attence_class = D('attence_class');
				// 先查询已存在的班次
				$where = array(
					'sid' => session('sid')
				);
				$result = $attence_class->where($where)->field('classname')->order('classid asc')->select();
				if($result){
					// logger('考勤班次:'.var_export($result,TRUE)); //debug
					//将result二维数组变为一维数组
					$class_name = array();
					foreach($result as $k => $v){
						$class_name[$k] = $v['classname']; 
					}
					// logger('考勤班次名称数组(一维):'.var_export($class_name,TRUE)); //debug
					// logger('最后一个班次名：'.end($class_name)); //debug
					$leter = substr(end($class_name),6,1);
					$leter++;
					$name = '班次'.$leter;
					// logger('新班次名:'.$name); //debug
				}else{
					$name = '班次A';
				}
				$data = array(
					'start' => $start,
					'end' => $end,
					'classname' => $name,
					'sid' => $sid,
					'add_time' => time(),
					'add_admin' => session('uid')
				);
				$result = $attence_class->add($data);
				if($result){
					$data = array(
						'code' => '1',
						'message' => '添加成功！'
					);
					logger('添加成功！'."\n");
					exit(json_encode($data));
				}else{
					$data = array(
						'code' => '0',
						'message' => '添加失败！'
					);
					logger('添加失败！'."\n");
					exit(json_encode($data));
				}
			}
		}else{
			$data = array(
				'code' => '3',
				'message' => '没有权限！'
			);
			logger('没有权限！'."\n");
			exit(json_encode($data));
		}
	}
	//显示班次表
	public function list_attence_class(){
		logger('查看影楼班次表');
		//此操作隐藏在考勤组管理内，之前已判断管理员身份，在此省略验证权限操作
		$attence_class = D('attence_class');
		$where = array(
			'sid' => session('sid')
		);
		$result = $attence_class->where($where)->field('classid,classname,start,end')->order('classid asc')->select();
		if($result){
			$data = array(
				'code' => '1',
				'message' => '班次表显示成功！',
				'result' => $result
			);
			logger('班次表显示成功！'."\n");
			exit(json_encode($data));
		}else{
			$data = array(
				'code' => '0',
				'message' => '还没有设置班次表！'
			);
			logger('还没有设置班次表！'."\n");
			exit(json_encode($data));
		}
	}
	//删除考勤班次
	public function del_attence_class(){
		logger('删除考勤班次');
		if(session('wtype') == 1){
			$post = I();
			logger('传入参数：'.var_export($post,TRUE));
			$aid = $post['aid'];
			$attence_class = D('attence_class');
			$where = array(
				'classid' => $aid
			);
			logger('管理员：'.session('admin_name').'删除删除删除ID为：'.$aid.' 的考勤班次！');
			//在删除前先将店铺所有员工之前的签到记录更新 
			// $map = array(
			// 	'sid' => session('sid'), //整个店铺的员工
			// 	// 'attence_group' => array(array('neq',''),array('neq',NULL),'OR') //考勤组id不为空
			// );
			// $app_user = D('app_user');
			// $users = $app_user->where($map)->select();
			// foreach($users as $k => $v){
			// 	if($v['attence_group'] != NULL && $v['attence_group'] != ''){
			// 		$this->update_status_records($v['uid'],$v['attence_group'],$v['sid'],1);
			// 	}else{
			// 		$this->update_status_records_nogroup($v['uid'],$v['sid'],1);
			// 	}
			// }
			//耗时会比较长，尤其是店铺员工比较多的情况
			$result = $attence_class->where($where)->delete();
			if($result){
				$data = array(
					'code' => '1',
					'message' => '删除成功！'
				);
				logger('删除成功！'."\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => '0',
					'message' => '删除失败！'
				);
				logger('删除失败！'."\n");
				exit(json_encode($data));
			}
		}else{
			$data = array(
				'code' => '3',
				'message' => '没有权限！'
			);
			logger('没有权限！'."\n");
			exit(json_encode($data));
		}
	}
	public function get_the_rule($gid,$uid){
		logger('查询用户的考勤规则[通用]');
		logger('考勤组id：'.$gid.'用户id:'.$uid); //debug
		$rule = $this->get_rule($gid,$uid);
		if($rule){
			//查询全局考勤设置
			$whole = $this->get_whole_rule();
			$rule['whole'] = $whole; 
			//当全局设置中开启了绑定审批类中的请假和外出时查询
			if($whole['sply'] == 1){
				$sply = $this->get_sply_info();
				$rule['sply'] = $sply;
			}
			//查询当前用户的今日签到情况
			$checkin = $this->query_checkin();
			$rule['checkin'] = $checkin;
			logger('排班信息返回成功！'."\n");
			return $rule;
		}else{
			logger('查询排班失败！'."\n");
			return FALSE;
		}
	}
	//获取员工的今天的考勤规则  //考勤签到展示页面 ，包含考勤规则，签到记录
	public function get_user_rule($id=0){
		logger('查询员工的考勤规则');
		$post = I();
		$uid = $post['userid']; //URL传值
		if(!empty($uid)){
			// 非当前用户时，先查询用户信息，找到考勤组id
			$app_user = D('app_user');
			$where = array(
				'uid' => $uid
			);
			$user = $app_user->where($where)->field('attence_group')->find();
			$gid = $user['attence_group'];
		}
		if(!empty($id)){ //如果主动函数调用传值
			$uid = $id;
			// 非当前用户时，先查询用户信息，找到考勤组id
			$app_user = D('app_user');
			$where = array(
				'uid' => $uid
			);
			$user = $app_user->where($where)->field('attence_group')->find();
			$gid = $user['attence_group'];
		}
		if(!$uid){ //默认查询当前用户
			logger('查询当前用户的考勤规则');
			$uid = session('uid');
			// $gid = session('group'); //session存的考勤组可能随时变化
			//实时查询当前用户的考勤组
			$app_user = D('app_user');
			$where = array(
				'uid' => $uid
			);
			$user = $app_user->where($where)->field('attence_group')->find();
			$gid = $user['attence_group'];
		}
		logger('查询ID:'.$uid.'用户的考勤规则');
		logger('查询到考勤组id为：'.$gid); //debug
		if(!$gid){ //考勤组id不存在，说明未排班
			$data = array(
				'code' => '0',
				'message' => '未排班！'
			);
			logger('未排班！'."\n");
			exit(json_encode($data));
		}
		$rule = $this->get_the_rule($gid,$uid);
		if($rule){
			$data = array(
				'code' => '1',
				'message' => '排班信息返回成功！',
				'result' => $rule
			);
			logger('排班信息返回成功！'."\n");
			exit(json_encode($data));
		}else{
			$data = array(
				'code' => '0',
				'message' => '查询排班失败！'
			);
			logger('查询排班失败！'."\n");
			exit(json_encode($data));
		}
	}
	//获取员工的今天的考勤规则
	// public function get_user_rule($id=0){
	// 	logger('查询员工的考勤规则');
	// 	$post = I();
	// 	$uid = $post['id']; //URL传值
	// 	if(!empty($id)){ //如果主动函数调用传值
	// 		$uid = $id;
	// 	}
	// 	if(!$uid){ //默认查询当前用户
	// 		logger('查询当前用户的考勤规则');
	// 		$uid = session('uid');
	// 		$gid = session('group');
	// 		if(!$gid){ //考勤组id不存在，说明未排班
	// 			$data = array(
	// 				'code' => '0',
	// 				'message' => '未排班！'
	// 			);
	// 			logger('未排班！'."\n");
	// 			exit(json_encode($data));
	// 		}
	// 		$rule = $this->get_rule($gid,$uid);
	// 		if($rule){
	// 			//查询全局考勤设置
	// 			$whole = $this->get_whole_rule();
	// 			$rule['whole'] = $whole; 
	// 			//当全局设置中开启了绑定审批类中的请假和外出时查询
	// 			if($whole['sply'] == 1){
	// 				$sply = $this->get_sply_info();
	// 				$rule['sply'] = $sply;
	// 			}
	// 			//查询当前用户的今日签到情况
	// 			$checkin = $this->query_checkin();
	// 			$rule['checkin'] = $checkin;
	// 			$data = array(
	// 				'code' => '1',
	// 				'message' => '排班信息返回成功！',
	// 				'result' => $rule
	// 			);
	// 			logger('排班信息返回成功！'."\n");
	// 			exit(json_encode($data));
	// 		}else{
	// 			$data = array(
	// 				'code' => '0',
	// 				'message' => '查询排班失败！'
	// 			);
	// 			logger('查询排班失败！'."\n");
	// 			exit(json_encode($data));
	// 		}
	// 	}
	// 	logger('查询ID:'.$uid.'用户的考勤规则');  
	// 	// 非当前用户时，先查询用户信息，找到考勤组id
	// 	$app_user = D('app_user');
	// 	$where = array(
	// 		'uid' => $uid
	// 	);
	// 	$user = $app_user->where($where)->field('attence_group')->find();
	// 	$gid = $user['attence_group'];
	// 	logger('查询到考勤组id为：'.$gid); //debug
	// 	if(!$gid){ //考勤组id不存在，说明未排班
	// 		$data = array(
	// 			'code' => '0',
	// 			'message' => '未排班！'
	// 		);
	// 		logger('未排班！'."\n");
	// 		exit(json_encode($data));
	// 	}
	// 	$rule = $this->get_rule($gid,$uid);
	// 	if($rule){
	// 		//查询全局考勤设置
	// 		$whole = $this->get_whole_rule($uid);
	// 		$rule['whole'] = $whole;
	// 		//当全局设置中开启了绑定审批类中的请假和外出时查询
	// 		if($whole['sply'] == 1){
	// 			$sply = $this->get_sply_info($uid);
	// 			$rule['sply'] = $sply;
	// 		}
	// 		//查询用户的今日签到情况
	// 		$checkin = $this->query_checkin($uid);
	// 		$rule['checkin'] = $checkin;
	// 		$data = array(
	// 			'code' => '1',
	// 			'message' => '排班信息返回成功！',
	// 			'result' => $rule
	// 		);
	// 		logger('排班信息返回成功！'."\n");
	// 		exit(json_encode($data));
	// 	}else{
	// 		$data = array(
	// 			'code' => '0',
	// 			'message' => '查询排班失败！'
	// 		);
	// 		logger('查询排班失败！'."\n");
	// 		exit(json_encode($data));
	// 	}
	// }
	//根据考勤组id查询规则
	public function get_rule($gid,$uid){
		$attence_group = D('attence_group');
		$where = array(
			'id' => $gid
		);
		$result = $attence_group->where($where)->field('add_time,add_admin,modify_time,modify_admin',TRUE)->find();
		if($result){
			// logger('所在考勤组设置：'.var_export($result,TRUE)); //debug
			switch($result['type']){ //判断考勤班制 1固定班制  2排班制
				case 1:
					logger('所在考勤组为固定班制！');
					$rules = $this->chanslate_json_to_array($result['rules']);
					// logger('考勤总规定:'.var_export($rules,TRUE)); //debug
					$week=array("日","一","二","三","四","五","六"); //预先定义一个星期的数组
					logger('今天'.$week[date('w',time())]); //debug
					foreach($rules as $k => $v){
						if(strpos($v['week'],$week[date('w',time())])){
							$rule = array(
								'classname' => $v['classname'],
								'classid' => $v['classid'],
								'start' => $v['start'],
								'end' => $v['end']
							);
							break;
						}
					}
					break;
				case 2:
					logger('所在考勤组为排班制！');
					$rules = $this->chanslate_json_to_array($result['rules']);
					// logger('考勤总规定:'.var_export($rules,TRUE)); //debug
					foreach($rules as $k => $v){
						if($v['uid'] == $uid){
							$size = count($v['detail']);
							$num = 1;
							foreach($v['detail'] as $key => $value){
								if(date('Y-m-d',time()) == $value['date']){
									$rule = array(
										'classname' => $value['classname'],
										'classid' => $value['classid'],
										'start' => $value['start'],
										'end' => $value['end']
									);
								}else{ //如果没有，则未排班
									if($num == $size){
										$rule = FALSE;
									}
									$num++;
								}
							}
							break;
						}
					}
					break;
				default:
					logger('错误班制！');
					break;
			}
			if($rule){
				$rule_info = array(
					'rule' => $rule,
					'routers' => $this->chanslate_json_to_array($result['routers']),
					'locations' => $this->chanslate_json_to_array($result['locations']),
					'privilege_meter' => $this->chanslate_json_to_array($result['privilege_meter'])
				);
				// logger('员工个人考勤规则:'.var_export($rule_info,TRUE)); //debug
				return $rule_info;
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}
	}
	// 获取全局考勤规则设置
	public function get_whole_rule($uid=0){
		logger('查询全局考勤规则设置');
		$sid = session('sid');
		if(!empty($uid)){
			$app_user = D('app_user');
			$where = array(
				'uid' => $uid
			);
			$user = $app_user->where($where)->field('sid')->find();
			logger('用户信息：'.var_export($user,TRUE)); //debug
			$sid = $user['sid'];
		}
		$whole = D('attence_whole');
		$where = array(
			'sid' => $sid
		);
		$result = $whole->where($where)->field('add_time,add_admin,modify_time,modify_admin,id,sid',TRUE)->find();
		if(!$result){
			$result = array(
				'privilege_time' => 0,
				'latetime' => 0,
				'absent' => 0,
				'sply' => 1,
				'ontip' => 0,
				'offtip' => 0,
				'outtip' => 0,
				'earlytime' => 180
			);
		}
		return $result;
	}
	//获取员工的审批类请假或外出数据
	public function get_sply_info($uid = 0,$date){
		logger('查询员工的请假或外出数据'); 
		$id = session('uid');
		$time = time();
		if(!empty($uid)){
			$id = $uid;
		}
		if(!empty($date)){
			$time = strtotime($date);
		}
		$leave = D('appro_leave');
		$out = D('appro_out');
		$where = array(
			'uid' => $id,
			// 'start' => array(array('egt',strtotime(date('Y-m-d',time()))),array('lt',strtotime(date('Y-m-d',time()+86400)))), // 请假开始时间，小于今日24时 大于等于今日0时
			'start' => array('elt',strtotime(date('Y-m-d',$time+86400))), // 请假开始时间，小于今日24时 
			'finish' => array('egt',strtotime(date('Y-m-d',$time))), // 请假结束时间，大于今日0时 
			'status' => 1
		);
		$result = $leave->where($where)->field('kind,start,finish')->find();
		// $sql = $leave->getLastsql(); //debug
		// logger('查询语句:'.$sql); //debug
		// $out_result = $out->where($where)->field('kind,start,finish')->select();  //外出的情况，就让其变为外勤打卡吧
		// $result = array_merge($leave_result,$out_result);
		if($result){
			logger('有请假或外出信息!');
			// 将时间戳转换为日期格式
			$result['start'] = date('Y-m-d H:i:s',$result['start']);
			$result['finish'] = date('Y-m-d H:i:s',$result['finish']);
			return $result;
		}else{
			logger('无请假或外出信息!');
			$result = (object)null;;
			return $result;
		}
	}
	//查询今日当前用户的签到情况
	public function query_checkin($uid = 0){
		logger('查询今日签到情况');
		$post = I();
		$id = $post['userid'];  //和签到接口传入的id有冲突,修改为userid
		if(!empty($uid)){
			$id = $uid;
		}
		if(empty($id)){
			$id = session('uid');
		}
		logger('UID:'.$id); //debug
		//创建空的对象
		$empty_object=(object)null;
		$checkin = array(
			'on' => array(
					'id' => '',
					'time' => '',
					'router' => '',
					'location' => $empty_object,
					'outside' => '',
					'status' => '',
					'mark' => '',
					'remark' => '',
					'img' => ''
				),
			'off' => array(
					'id' => '',
					'time' => '',
					'router' => '',
					'location' => $empty_object,
					'outside' => '',
					'status' => '',
					'mark' => '',
					'remark' => '',
					'img' => ''
				)
		);
		$records = D('attence_records');
		// $where = array(
		// 	'uid' => $id,
		// 	'on_time' => array('gt' , strtotime(date('Y-m-d',time())))
		// );
		$map['on_time']  = array('gt', strtotime(date('Y-m-d',time()))); //上午的签到时间或者下午的签到时间
	    $map['off_time']  = array('gt', strtotime(date('Y-m-d',time()))); //上午的签到时间或者下午的签到时间
	    $map['_logic'] = 'or';
	    $where['_complex'] = $map;
	    $where['uid']  = array('eq',$id);
		$result = $records->where($where)->find();
		// $sql = $records->getLastsql(); //debug
		// logger('查询语句:'.$sql); //debug
		// logger('签到记录:'.var_export($result,TRUE)); //debug
		if($result){
			logger('今日you签到记录');
			$on_location = $this->chanslate_json_to_array($result['on_location']);
			$off_location = $this->chanslate_json_to_array($result['off_location']);
			$checkin = array(
				'on' => array(
						'id' => $result['id'],
						'time' => date('Y-m-d H:i:s',$result['on_time']),
						'router' => $result['on_router'],
						'location' => $on_location,
						'outside' => $result['on_outside'],
						'status' => $result['on_status'],
						'mark' => $result['on_mark'],
						'remark' => $result['on_remark'],
						'img' => $result['on_remark_img']
					),
				'off' => array(
						'id' => $result['id'],
						'time' => date('Y-m-d H:i:s',$result['off_time']),
						'router' => $result['off_router'],
						'location' => $off_location,
						'outside' => $result['off_outside'],
						'status' => $result['off_status'],
						'mark' => $result['off_mark'],
						'remark' => $result['off_remark'],
						'img' => $result['off_remark_img']
					)
			);
			//当上午未打卡时,清空字段.给前端判断状态
			if($result['on_time'] == NULL || $result['on_time'] == 0){
				$checkin['on'] = array(
					'id' => '',
					'time' => '',
					'router' => '',
					'location' => $empty_object,
					'outside' => '',
					'status' => '',
					'mark' => '',
					'remark' => '',
					'img' => ''
				);
			}
			//当下午未打卡时,清空字段.给前端判断状态
			if($result['off_time'] == NULL || $result['off_time'] == 0){
				$checkin['off'] = array(
					'id' => '',
					'time' => '',
					'router' => '',
					'location' => $empty_object,
					'outside' => '',
					'status' => '',
					'mark' => '',
					'remark' => '',
					'img' => ''
				);
			}
		}else{
			logger('今日尚无签到记录');
		}
		// logger('签到记录:'.var_export($checkin,TRUE)); //debug
		return $checkin;
	}
	//签到接口
	public function checkin(){
		logger('签到');
		$post = I();
		// logger('传入参数：'.var_export($post,TRUE));
		$type = $post['type']; //上班 下班 ； 1为上班  2为下班 
		$id = $post['id']; //签到记录id
 		$time = $post['time']; //签到时间
		$router = $post['router']; //签到路由器
		$location = $post['location']; // 签到位置
		$status = $post['status'];  //签到状态
		$mark = $post['mark']; //签到状态说明
		$outside = $post['outside']; //是否外勤
		$remark = $post['remark']; // 备注
		$img = $post['img']; // 图片备注
		//必传参数 类型，时间 ，状态，是否正常， 路由，地理位置 路由可以不传，外勤时可能没有
		// outside 是否外勤 1 外勤 0 内勤；status 状态：1 正常打卡 2 迟到  3 严重迟到 4 旷工  5 早退 6 缺卡 7 请假 8 外出 9休息 10 未排班
		if(($type && $time && $status) && ($router || $location)){
			$records = D('attence_records');
			if($type == '1'){
				$data = array(
					'on_time' => strtotime($time),
					'on_router' => $router,
					'on_location' => $location,
					'on_status' => $status,
					'on_mark' => $mark,
					'on_outside' => $outside,
					'on_remark' => $remark,
					'on_remark_img' => $img
				);
			}else{
				$data = array(
					'off_time' => strtotime($time),
					'off_router' => $router,
					'off_location' => $location,
					'off_status' => $status,
					'off_mark' => $mark,
					'off_outside' => $outside,
					'off_remark' => $remark,
					'off_remark_img' => $img
				);
			}
			//判断是否是更新打卡或下午打卡
			$condition = array(
				'uid' => session('uid')
			);
			$start = strtotime(date('Y-m-d',time()));
			$end = strtotime(date('Y-m-d',time()+86400));
			$condition['_string'] = "((on_time >= ".$start." AND on_time < ".$end.") OR (off_time >= ".$start." AND off_time < ".$end."))";
			$check_info = $records->where($condition)->field('id')->find();
			if(!$check_info){
				logger('今日第一次签到');
				$data['uid'] = session('uid');
				$data['sid'] = session('sid');
				$result = $records->add($data);
			}else{
				logger('更新或下午签到');
				$where = array(
					'id' => $check_info['id']
				);
				$result = $records->where($where)->save($data);
			}
			//签到成功后,返回最新的考勤规则和签到结果
			$gid = session('group');
			$uid = session('uid');
			$checkin_result = $this->get_the_rule($gid,$uid);
			if($result){
				$data = array(
					'code' => '1',
					'message' => '签到成功！',
					'result' => $checkin_result
				);
				logger('签到成功！'."\n");
				exit(json_encode($data));
			}else{
				$data = array(
					'code' => '0',
					'message' => '签到失败！'
				);
				logger('签到失败！'."\n");
				exit(json_encode($data));
			}
		}else{
			logger('参数不全！');
			$data = array(
				'code' => '3',
				'message' => '参数不全，请重试！'
			);
			logger('参数不全！'."\n");
			exit(json_encode($data));
		}
	}
	//签到统计【个人】时间单位：月
	public function user_count_checkin($uid = 0){
		logger('个人签到统计');
		$post = I();
		// logger('传入参数：'.var_export($post,TRUE)); //debug
		$id = session('uid');
		if(!empty($post['id'])){
			$id = $post['id'];
		}
		if(!empty($uid)){
			$id = $uid;
		}
		$records = D('attence_records');
		//设定查询月份 只能查询当年的月份
		$month = $post['month'];
		if(empty($month)){
			$month = date('m',time());
		}
		//当月月份
		$now_month = date('Y',time()).'-'.$month;
		//当月开始时间戳
		$start = strtotime($now_month);
		//下月月份
		if($month == 12){
			$next_month = (date('Y',time()) + 1).'-01';
		}else{
			$next_month = date('Y',time()).'-'.($month + 1);
		}
		//下月开始时间戳，查询截止时间
		$end = strtotime($next_month);
		// $themonth = strtotime(date('Y-m',time()));
		// $nextmonth = date('m',time()) + 1;
		// $nextmonth = strtotime(date('Y',time()).'-'.$nextmonth);
		// // 查询本月的签到记录  月计划任务或考勤组规则成员变更时执行的更新签到记录操作已将on_time全部填满了，所以不必再考虑到on_time不存在的情况了。
		$where = array(
			'uid' => $id,
			// 'on_time' => array(array('egt',$start),array('lt',$end))
		);
		$where['_string'] = "((on_time >= ".$start." AND on_time < ".$end.") OR (off_time >= ".$start." AND off_time < ".$end."))";
		$result = $records->where($where)->select();
		$sql = $records->getLastsql(); //debug
		// logger('查询语句：'.$sql); //debug
		// logger('当月所有签到结果：'.var_export($result,TRUE)); //debug
		//循环判断所有签到记录，统计计数各状态情况
		//新建统计初始数组
		$count = array(
			'0' => array(), //未签到,在这个场景不应该出现
			'1' => array(), //正常签到
			'2' => array(), //迟到
			'3' => array(), //严重迟到
			'4' => array(), //旷工
			'5' => array(), //早退
			'6' => array(), //缺卡
			'7' => array(), //请假
			'8' => array(), //外出
			'9' => array(), //休息
			'10' => array(), //未排班
			'11' => array() //外勤打卡
		); 
		foreach($result as $key => $value){
			switch($value['on_status']){
				case 2:
				case 3:
					$size = count($count[$value['on_status']]);
					$count[$value['on_status']][$size] = array(
						'time' => date('Y-m-d H:i:s' , $value['on_time']),
						'mark' => '迟到'.abs($value['on_mark']).'分钟',
						'checkinid' => $value['id'] //加这个签到ID,方便对照排错
					);
					break;
				case 5:
					$size = count($count[$value['on_status']]);
					$count[$value['on_status']][$size] = array(
						'time' => date('Y-m-d H:i:s' , $value['on_time']),
						'mark' => '早退'.abs($value['on_mark']).'分钟',
						'checkinid' => $value['id'] //加这个签到ID,方便对照排错
					);
					break;
				default:
					$size = count($count[$value['on_status']]);
					$count[$value['on_status']][$size] = array(
						'time' => date('Y-m-d H:i:s' , $value['on_time']),
						'mark' => $value['on_mark'],
						'checkinid' => $value['id'] //加这个签到ID,方便对照排错
					);
					break;
			}
			switch($value['off_status']){
				case 2:
				case 3:
					$size = count($count[$value['off_status']]);
					$count[$value['off_status']][$size] = array(
						'time' => date('Y-m-d H:i:s' , $value['off_time']),
						'mark' => '迟到'.abs($value['off_mark']).'分钟',
						'checkinid' => $value['id'] //加这个签到ID,方便对照排错
					);
					break;
				case 5:
					$size = count($count[$value['off_status']]);
					$count[$value['off_status']][$size] = array(
						'time' => date('Y-m-d H:i:s' , $value['off_time']),
						'mark' => '早退'.abs($value['off_mark']).'分钟',
						'checkinid' => $value['id'] //加这个签到ID,方便对照排错
					);
					break;
				default:
					$size = count($count[$value['off_status']]);
					$count[$value['off_status']][$size] = array(
						'time' => date('Y-m-d H:i:s' , $value['off_time']),
						'mark' => $value['off_mark'],
						'checkinid' => $value['id'] //加这个签到ID,方便对照排错
					);
					break;
			}
			// //全天都为正常签到时
			// if(($value['on_status'] == 1 && $value['off_status'] == 1 ) || $value['on_status'] == 1 || $value['off_status'] == 1){
			// 	$size = count($count[1]);
			// 	$count[1][$size] = array(
			// 		'time' => date('Y-m-d',$value['on_time']),
			// 		'mark' => ''
			// 	);;
			// 	continue;
			// }
			// //全天都未旷工时
			// if($value['on_status'] == 4 && $value['off_status'] == 4){ 
			// 	$size = count($count[4]);
			// 	$count[4][$size] = array(
			// 		'time' => date('Y-m-d',$value['on_time']),
			// 		'mark' => 'all'
			// 	);
			// 	continue;
			// }
			// //上午为旷工时
			// if($value['on_status'] == 4 && $value['off_status'] != 4){
			// 	$size = count($count[4]);
			// 	$count[4][$size] = array(
			// 		'time' => date('Y-m-d',$value['on_time']),
			// 		'mark' => 'on'
			// 	);
			// 	$size = count($count[$value['off_status']]);
			// 	$count[$value['off_status']][$size] = array(
			// 		'time' => $value['off_time'],
			// 		'mark' => $value['off_mark']
			// 	);
			// 	continue;
			// }
			// //下午为旷工时
			// if($value['on_status'] != 4 && $value['off_status'] == 4){
			// 	$size = count($count[4]);
			// 	$count[4][$size] = array(
			// 		'time' => date('Y-m-d',$value['off_time']),
			// 		'mark' => 'off'
			// 	);
			// 	$size = count($count[$value['on_status']]);
			// 	$count[$value['on_status']][$size] = array(
			// 		'time' => $value['on_time'],
			// 		'mark' => $value['on_mark']
			// 	);
			// 	continue;
			// }
			// //全天都为外勤时
			// if($value['on_outside'] == 1 && $value['off_outside'] == 1){
			// 	$size = count($count[11]);
			// 	$count[11][$size] = array(
			// 		'time' => $value['on_time'],
			// 		'mark' => $this->chanslate_json_to_array($value['on_location'])
			// 	);
			// 	$size = count($count[11]);
			// 	$count[11][$size] = array(
			// 		'time' => $value['off_time'],
			// 		'mark' => $this->chanslate_json_to_array($value['off_location'])
			// 	);
			// 	continue;
			// }
			// //上午为外勤时
			// if($value['on_outside'] == 1 && $value['off_outside'] != 1){
			// 	$size = count($count[11]);
			// 	$count[11][$size] = array(
			// 		'time' => $value['on_time'],
			// 		'mark' => $this->chanslate_json_to_array($value['on_location'])
			// 	);
			// 	$size = count($count[$value['off_status']]);
			// 	$count[$value['off_status']][$size] = array(
			// 		'time' => $value['off_time'],
			// 		'mark' => $value['off_mark']
			// 	);
			// 	continue;
			// }
			// //下午为外勤时
			// if($value['on_outside'] != 1 && $value['off_outside'] == 1){
			// 	$size = count($count[11]);
			// 	$count[11][$size] = array(
			// 		'time' => $value['off_time'],
			// 		'mark' => $this->chanslate_json_to_array($value['off_location'])
			// 	);
			// 	$size = count($count[$value['on_status']]);
			// 	$count[$value['on_status']][$size] = array(
			// 		'time' => $value['on_time'],
			// 		'mark' => $value['on_mark']
			// 	);
			// 	continue;
			// }
			// //其他情况
			// $size = count($count[$value['on_status']]);
			// $count[$value['on_status']][$size] = array(
			// 	'time' => $value['on_time'],
			// 	'mark' => $value['on_mark']
			// );
			// $size = count($count[$value['off_status']]);
			// $count[$value['off_status']][$size] = array(
			// 	'time' => $value['off_time'],
			// 	'mark' => $value['off_mark']
			// );
		}
		// logger('员工月统计结果：'.var_export($count,TRUE)); //debug
		if($count){
			logger('员工月考勤统计返回成功!'."\n"); 
			$data = array(
				'code' => 1,
				'message' => '员工月考勤统计返回成功!',
				'count' => $count
			);
			exit(json_encode($data));
		}else{
			logger('员工月考勤统计查询失败!'."\n"); 
			$data = array(
				'code' => 0,
				'message' => '员工月考勤统计查询失败!'
			);
			exit(json_encode($data));
		}
	}
	//更新签到记录的状态【没有状态的记录】
	public function update_status_records($id = 0 ,$gid = 0,$store = 0,$max=0){
		logger('更新员工的签到记录===有考勤ID');
		//如果不是调用，默认更新当前用户的签到记录
		if(empty($gid)){
			logger('非主动调用,将更新当前用户签到记录'); //debug
			$gid = session('group');
			$uid = session('uid');
			$sid = session('sid');
		}else{
			$uid = $id;
			$gid = $gid;
			// //操作改变的都是统一店铺的员工，直接从session中取sid就是了
			// $sid = session('sid');
			$sid = $store;
			// $app_user = D('app_user');
			// $where = array(
			// 	'uid' => $uid
			// );
			// $user = $app_user->where($where)->find();
			// $sid = $user['sid'];
		}
		if(empty($max)){
			$max = 3;
		}
		logger('员工ID：'.$uid.'考勤组ID：'.$gid); //debug
		// die; //debug
		//查询当前店铺的全局设置
		$whole = $this->get_whole_rule();
		//查询过去所有缺少状态的记录，包括上午缺少状态或下午缺少状态，不可能上午和下午同时缺少状态，因为那样就不会有这条记录了。
		$records = D('attence_records');
		$where = array(
			'_query' => 'off_status=0&on_status=0&_logic=or',
			'uid' => $uid
		);
		$all_block_records = $records->where($where)->select();
		// $sql = $records->getLastsql(); //debug
		// logger('查询语句2:'.$sql); //debug
		// logger('查询结果2:'.var_export($all_block_records,TRUE)); //debug
		// 查询考勤规则
		$group = D('attence_group');
		$where = array(
			'id' => $gid
		);
		$my_group = $group->where($where)->find();
		// $sql = $group->getLastsql(); //debug
		// logger('查询语句3:'.$sql); //debug
		// logger('查询结果3:'.var_export($my_group,TRUE)); //debug
		if($all_block_records){
			logger('存在缺失状态的签到记录!');
			if($my_group['type'] == 1){ //固定班制
				logger('所在考勤组为固定班制！'); //debug
				$rule = $this->chanslate_json_to_array($my_group['rules']);
				$week=array("日","一","二","三","四","五","六"); //预先定义一个星期的数组
				// 循环判断缺少状态的记录
				foreach($all_block_records as $key => $value){
					if($value['on_status'] == 0){ //上午缺少状态
						logger('上午缺卡'); //debug
						foreach($rule as $k => $v){
							if(strpos($v['week'],$week[date('w',$value['off_time'])])){ //拿下午的时间判断当天是周几
								//是否休息
								if($v['classid'] == 0){ //当班次id为0时，说明当天休息
									$data = array(
										'on_status' => 9
									);
								}else{ //不休息，是否旷工或缺卡
									if($whole['absent'] > 0){ //查询全局设置中对旷工的规定，大于0说明有规定
										if((time() - strtotime(date('Y-m-d',$value['off_time']).' '.$v['start']))/60 > $whole['absent']){
											$data = array(
												'on_status' => 4
											);
										}
									}else{
										$data = array(
											'on_status' => 6
										);
									}
								}
							}
						}
					}
					if($value['off_status'] == 0){ //下午缺少状态
						logger('下午缺卡'); //debug
						foreach($rule as $k => $v){
							if(strpos($v['week'],$week[date('w',$value['on_time'])])){ //拿下午的时间判断当天是周几
								//是否休息
								if($v['classid'] == 0){ //当班次id为0时，说明当天休息
									$data = array(
										'off_status' => 9
									);
								}else{ //不休息，是否旷工或缺卡
									if($whole['absent'] > 0){ //查询全局设置中对旷工的规定，大于0说明有规定
										if((time() - strtotime(date('Y-m-d',$value['on_time']).' '.$v['start']))/60 > $whole['absent']){
											$data = array(
												'off_status' => 4
											);
										}
									}else{
										$data = array(
											'off_status' => 6
										);
									}
								}
							}
						}
					}
					$where = array(
						'id' => $value['id']
					);
					$result = $records->where($where)->save($data);
					if($result){
						logger('签到统计状态修改成功！');
					}else{
						logger('签到统计状态修改FAILED！');
					}
				}
			}else{ //排班制
				logger('所在考勤组为排班制！'); //debug
				$rules = $this->chanslate_json_to_array($my_group['rules']);
				foreach($rules as $k => $v){
					if($v['uid'] == $uid){
						$rule = $v['detail'];
					}
				}
				//循环判断每条缺少状态的记录
				foreach($all_block_records as $key => $value){
					if($value['on_status'] == 0){ //上午缺少状态
						logger('上午缺卡'); //debug
						foreach($rule as $k => $v){
							if($v['date'] == date('Y-m-d',$value['off_time'])){ //当天的日期和考勤规则里的日期相同时
								//是否休息
								if($v['classid'] == 0){ //当班次id为0时，说明当天休息
									$data = array(
										'on_status' => 9
									);
								}else{ //不休息，是否旷工或缺卡
									if($whole['absent'] > 0){ //查询全局设置中对旷工的规定，大于0说明有规定
										if((time() - strtotime(date('Y-m-d',$value['on_time']).' '.$v['start']))/60 > $whole['absent']){
											$data = array(
												'on_status' => 4
											);
										}
									}else{
										$data = array(
											'on_status' => 6
										);
									}
								}
							}
						}
					}
					if($value['off_status'] == 0){ // 下午缺少状态
						logger('下午缺卡'); //debug
						foreach($rule as $k => $v){
							if($v['date'] == date('Y-m-d',$value['on_time'])){ //当天的日期和考勤规则里的日期相同时
								//是否休息
								if($v['classid'] == 0){ //当班次id为0时，说明当天休息
									$data = array(
										'off_status' => 9
									);
								}else{ //不休息，是否旷工或缺卡
									if($whole['absent'] > 0){ //查询全局设置中对旷工的规定，大于0说明有规定
										if((time() - strtotime(date('Y-m-d',$value['on_time']).' '.$v['start']))/60 > $whole['absent']){
											$data = array(
												'off_status' => 4
											);
										}
									}else{
										$data = array(
											'off_status' => 6
										);
									}
								}
							}
						}
					}
					$where = array(
						'id' => $value['id']
					);
					$result = $records->where($where)->save($data);
					if($result){
						logger('签到统计状态修改成功！');
					}else{
						logger('修改成功！');
					}
				}
			}
		}else{
			logger('不存在缺少状态的签到记录，直接去下一步-->补齐记录！');
		}
		// logger('去处理没有签到记录的日期'); //debug
		//添加安全没有签到的记录  或旷工 或休息 或未排班 或请假
		// 查询过去30天内的签到记录 不包含今天
		//查询的最大时间不能超过员工记录中的添加时间
		$min_time = strtotime(date('Y-m-d',time()-$max*86400));
		if($min_time <= session('create_time')){
			$min_time = session('create_time');
		}
		$mip = array();
		$want = array();
		$mip['on_time']  = array(array('elt', strtotime(date('Y-m-d',time()))),array('egt', $min_time),'AND'); //上午的签到时间或者下午的签到时间
	    $mip['off_time']  = array(array('elt', strtotime(date('Y-m-d',time()))),array('egt',$min_time),'AND'); //上午的签到时间或者下午的签到时间
	    $mip['_logic'] = 'or';
	    $want['_complex'] = $mip;
	    $want['uid']  = array('eq',$uid);
	    $want['id']  = array('neq',0); //为了屏蔽莫名其妙的uid为131时，查询条件就多加上了一条 id=32，结果就只能查出一条记录了。
	    // logger('月查询条件:'.var_export($want,TRUE)); //debug
	    $all_records = $records->where($want)->select();
	    // $sql = $records->getLastsql(); //debug
		// logger('查询语句4:'.$sql); //debug
		// logger('查询结果4:'.var_export($all_records,TRUE)); //debug
	    for($i=1;$i<=$max;$i++){
	    	logger('循环第'.$i.'次！'); //debug
	    	$date = date('Y-m-d',time()-$i*86400);
	    	logger('Today日期:'.$date); //debug
	    	$size = count($all_records);
	    	logger('一共有多少条记录:'.$size); //debug
	    	$num = 1;
	    	if($all_records){
	    		foreach($all_records as $k => $v){
		    		if(!empty($v['on_time'])){
		    			$checkin_date = date('Y-m-d',$v['on_time']);
		    		}
		    		if(!empty($v['off_time'])){
		    			$checkin_date = date('Y-m-d',$v['off_time']);
		    		}
		    		// logger('签到记录日期:'.$checkin_date); //debug
		    		if($date != $checkin_date){
		    			if($num == $size){
		    				// logger($date.'号,没有签到记录!'); //debug
		    				$userid = 0;
		    				$sply_info = $this->get_sply_info($userid,$date); //获取用户的请假信息
		    				$sply_info = (array)$sply_info; //转成数组
		    				$relax = $this->query_user_relax($my_group,$date); //获取用户当天是否休息或未排班
		    				// logger('查询当日请假申请：'.var_export($sply_info,TRUE)); //debug
		    				// logger('查询当日休息或排班：'.var_export($relax,TRUE)); //debug
		    				if($relax){//先判断是否未排班或休息
		    					if($relax['classid'] == null || $relax['classid'] == ''){
		    						logger('当天休息！'); //debug
		    						$data = array(
		    							'uid' => $uid,
		    							'sid' => $sid,
			    						'on_time' => strtotime($date),
			    						'on_status' => 9,
				    					'on_mark' => '休息！',
				    					'off_time' => strtotime($date),
			    						'off_status' => 9,
				    					'off_mark' => '休息！',
			    					);
		    					}else{
		    						//判断是否有关于旷工的全局设置
		    						if($whole['absent'] == 0){
		    							logger('无全局旷工设置，缺卡！'); //debug
		    							$data = array(
		    								'uid' => $uid,
		    								'sid' => $sid,
				    						'on_time' => strtotime($date),
				    						'on_status' => 6,
					    					'on_mark' => '缺卡！',
					    					'off_time' => strtotime($date),
				    						'off_status' => 6,
					    					'off_mark' => '缺卡！',
				    					);
		    						}else{
		    							logger('有全局旷工设置，旷工！'); //debug
		    							$data = array(
		    								'uid' => $uid,
		    								'sid' => $sid,
				    						'on_time' => strtotime($date),
				    						'on_status' => 4,
					    					'on_mark' => '旷工！',
					    					'off_time' => strtotime($date),
				    						'off_status' => 4,
					    					'off_mark' => '旷工！'
				    					);
		    						}
		    					}
		    				}else{
		    					logger('未排班！'); //debug
		    					$data = array(
		    						'uid' => $uid,
		    						'sid' => $sid,
		    						'on_time' => strtotime($date),
		    						'on_status' => 10,
			    					'on_mark' => '未排班！',
			    					'off_time' => strtotime($date),
		    						'off_status' => 10,
			    					'off_mark' => '未排班！'
		    					);
		    				}
		    				if(!empty($sply_info)){
		    					// $sec =  $sply_info['finish'] - $sply_info['start'];
		    					// $hour = $sec%1800/2;
		    					logger('请假！'); //debug
		    					$data = array(
		    						'uid' => $uid,
		    						'sid' => $sid,
			    					'on_time' => strtotime($date),
			    					'on_status' => 7,
			    					'on_mark' => '请假'.$hour.'小时',
			    					'off_time' => strtotime($date),
			    					'off_status' => 7,
			    					'off_mark' => '请假'.$hour.'小时'
			    				);
		    				}
		    				$result = $records->add($data);
							$sql = $records->getLastsql(); //debug
							// logger('插入语句1:'.$sql); //debug
							if($result){
								logger('签到记录新增成功！');
							}else{
								logger('签到记录新增FAILED！');
							}
		    			}
		    		}else{
		    			break;
		    		}
		    		$num++;
		    	}
	    	}else{
	    		logger('查询时期内：没有签到记录---去补齐--->');
	    		// logger($date.'号,没有签到记录!'); //debug
				$userid = 0;
				$sply_info = $this->get_sply_info($userid,$date); //获取用户的请假信息
				$sply_info = (array)$sply_info; //转成数组
				$relax = $this->query_user_relax($my_group,$date); //获取用户当天是否休息或未排班
				// logger('查询当日请假申请：'.var_export($sply_info,TRUE)); //debug
				// logger('查询当日休息或排班：'.var_export($relax,TRUE)); //debug
				if($relax){//先判断是否未排班或休息
					if($relax['classid'] == null || $relax['classid'] == ''){
						logger('当天休息！'); //debug
						$data = array(
							'uid' => $uid,
							'sid' => $sid,
    						'on_time' => strtotime($date),
    						'on_status' => 9,
	    					'on_mark' => '休息！',
	    					'off_time' => strtotime($date),
    						'off_status' => 9,
	    					'off_mark' => '休息！',
    					);
					}else{
						//判断是否有关于旷工的全局设置
						if($whole['absent'] == 0){
							logger('无全局旷工设置，缺卡！'); //debug
							$data = array(
								'uid' => $uid,
								'sid' => $sid,
	    						'on_time' => strtotime($date),
	    						'on_status' => 6,
		    					'on_mark' => '缺卡！',
		    					'off_time' => strtotime($date),
	    						'off_status' => 6,
		    					'off_mark' => '缺卡！',
	    					);
						}else{
							logger('有全局旷工设置，旷工！'); //debug
							$data = array(
								'uid' => $uid,
								'sid' => $sid,
	    						'on_time' => strtotime($date),
	    						'on_status' => 4,
		    					'on_mark' => '旷工！',
		    					'off_time' => strtotime($date),
	    						'off_status' => 4,
		    					'off_mark' => '旷工！'
	    					);
						}
					}
				}else{
					logger('未排班！'); //debug
					$data = array(
						'uid' => $uid,
						'sid' => $sid,
						'on_time' => strtotime($date),
						'on_status' => 10,
    					'on_mark' => '未排班！',
    					'off_time' => strtotime($date),
						'off_status' => 10,
    					'off_mark' => '未排班！'
					);
				}
				if(!empty($sply_info)){
					// $sec =  $sply_info['finish'] - $sply_info['start'];
					// $hour = $sec%1800/2;
					logger('请假！'); //debug
					$data = array(
						'uid' => $uid,
						'sid' => $sid,
    					'on_time' => strtotime($date),
    					'on_status' => 7,
    					'on_mark' => '请假'.$hour.'小时',
    					'off_time' => strtotime($date),
    					'off_status' => 7,
    					'off_mark' => '请假'.$hour.'小时'
    				);
				}
				$result = $records->add($data);
				$sql = $records->getLastsql(); //debug
				logger('插入语句1:'.$sql); //debug
				if($result){
					logger('签到记录新增成功！');
				}else{
					logger('签到记录新增FAILED！');
				}
	    	}
	    }
	}
	//更新没有考勤组id的员工签到记录
	public function update_status_records_nogroup($id=0,$store=0,$max=0){
		$uid = $id;
		if(empty($uid)){
			$uid = session('uid');
		}
		if(empty($max)){
			$max = 3;
		}
		logger('更新没有考勤组id: '.$uid.' 的员工签到记录！');
		//默认店铺是当前用户所在的店铺
		$sid = $store;
		if(empty($sid)){
			$sid = session('sid');
		}
		//查询近一个月内的签到记录，将空着的天都设成未排班
		// 查询过去30天内的签到记录
		//查询的最大时间不能超过员工记录中的添加时间
		$min_time = strtotime(date('Y-m-d',time()-$max*86400));
		if($min_time <= session('create_time')){
			$min_time = session('create_time');
		}
		$map['on_time']  = array(array('elt', strtotime(date('Y-m-d',time()))),array('egt', $min_time),'AND'); //上午的签到时间或者下午的签到时间
	    $map['off_time']  = array(array('elt', strtotime(date('Y-m-d',time()))),array('egt', $min_time),'AND'); //上午的签到时间或者下午的签到时间
	    $map['_logic'] = 'or';
	    $month_where['_complex'] = $map;
	    $month_where['uid']  = array('eq',$uid);
	    // logger('月查询条件:'.var_export($month_where,TRUE)); //debug
	    $records = D('attence_records');
	    $all_records = $records->where($month_where)->select();
	    // $sql = $records->getLastsql(); //debug
		// logger('查询语句4:'.$sql); //debug
		// logger('查询结果4:'.var_export($all_records,TRUE)); //debug
		$size = count($all_records);
		if($size == 0){
			logger('查询时段内没有排班且没有一条签到记录!补齐所有记录!');
			for($i=1;$i<=$max;$i++){
				$date = date('Y-m-d',time()-$i*86400);
				$data = array(
					'uid' => $uid,
					'sid' => $sid,
					'on_time' => strtotime($date),
					'on_status' => 10,
					'on_mark' => '未排班！',
					'off_time' => strtotime($date),
					'off_status' => 10,
					'off_mark' => '未排班！'
				);
				$result = $records->add($data);
				// $sql = $records->getLastsql(); //debug
				// logger('插入语句 === '.$sql); //debug
				if($result){
					logger('签到记录新增成功！');
				}else{
					logger('签到记录新增FAILED！');
				}
			}
			logger('更新没有考勤组id: '.$uid.' 的员工签到记录！--- 完毕！'."\n");
		}else{
			for($i=1;$i<=$max;$i++){
				logger('无考勤组ID情形，循环第'.$i.'次！'); //debug
		    	$date = date('Y-m-d',time()-$i*86400);
		    	logger('今日日期:'.$date); //debug
		    	$size = count($all_records);
		    	logger('一共有多少条记录:'.$size); //debug
		    	$num = 1;
		    	foreach($all_records as $k => $v){
		    		if(!empty($v['on_time'])){
		    			$checkin_date = date('Y-m-d',$v['on_time']);
		    		}
		    		if(!empty($v['off_time'])){
		    			$checkin_date = date('Y-m-d',$v['off_time']);
		    		}
		    		// logger('签到记录日期:'.$checkin_date); //debug
		    		if($date != $checkin_date){
						if($num == $size){
							logger($date.'没有签到记录，将其设为未排班！');
							$data = array(
	    						'uid' => $uid,
	    						'sid' => $sid,
		    					'on_time' => strtotime($date),
		    					'on_status' => 10,
		    					'on_mark' => '未排班！',
		    					'off_time' => strtotime($date),
		    					'off_status' => 10,
		    					'off_mark' => '未排班！'
		    				);
							$result = $records->add($data);
							if($result){
								logger('签到记录新增成功！');
							}else{
								logger('签到记录新增FAILED！');
							}
						}
		    		}else{
		    			break;
		    		}
		    		$num++;
		    	}
			}
			logger('更新没有考勤组id: '.$uid.' 的员工签到记录！--- 完毕！'."\n");
		}
	}
	//查询用户是否休息或未排班 
	public function query_user_relax($group,$date){
		// logger('所在考勤组设置：'.var_export($group,TRUE)); //debug
		switch($group['type']){ //判断考勤班制 1固定班制  2排班制
			case 1:
				logger('所在考勤组为固定班制！');
				$rules = $this->chanslate_json_to_array($group['rules']);
				// logger('考勤总规定:'.var_export($rules,TRUE)); //debug
				$week=array("日","一","二","三","四","五","六"); //预先定义一个星期的数组
				foreach($rules as $key => $value){
					if(strpos($value['week'],$week[date('w',strtotime($date))])){
						$rule = array(
							'classid' => $value['classid'],
							'start' => $value['start'],
							'end' => $value['end']
						);
						break;
					}
				}
				break;
			case 2:
				logger('所在考勤组为排班制！');
				$rules = $this->chanslate_json_to_array($group['rules']);
				// logger('考勤总规定:'.var_export($rules,TRUE)); //debug
				foreach($rules as $k => $v){
					if($v['uid'] == $uid){
						$size = count($v['detail']);
						$num = 1;
						foreach($v['detail'] as $key => $value){
							if(date('Y-m-d',strtotime($date)) == $value['date']){
								$rule = array(
									'classid' => $value['classid'],
									'start' => $value['start'],
									'end' => $value['end']
								);
								break;
							}else{ //如果没有，则未排班
								if($num == $size){
									$rule = FALSE;
								}
								$num++;
							}
						}
						break;
					}
				}
				break;
			default:
				logger('错误班制！');
				$rule = FALSE;
				break;
		}
		if(!$rule){
			$rule = array();
		}
		return $rule;
	}
	//签到统计【团队】 超级管理员权限  时间单位：日【可选择】
	public function group_count_checkin(){
		logger('团队今日考勤统计!'); 
		$post = I();
		// logger('传入参数:'.var_export($post,TRUE));
		if(session('wtype') == 1){
			$date = $post['date'];
			//默认是当日
			if(empty($date)){
				$date = date('Y-m-d',time());
			}
			$sid = session('sid');
			$app_user = D('app_user');
			$leave = D('appro_leave');
			$out = D('appro_out');
			$record = D('attence_records');
			$where = array(
				'sid' => $sid,
				'username' => array(array('neq',NULL),array('neq',''),'OR')
			);
			$users = $app_user->where($where)->field('uid,dept,attence_group,realname,nickname,head')->select();
			// logger('团队成员：'.var_export($users,TRUE));//debug
			$totalstaff = count($users);
			$count = array(
				'0' => array(), //还未打卡
				'1' => array(), //正常签到
				'2' => array(), //迟到
				'3' => array(), //严重迟到
				'4' => array(), //旷工
				'5' => array(), //早退
				'6' => array(), //缺卡
				'7' => array(), //请假
				'8' => array(), //外出
				'9' => array(), //休息
				'10' => array(), //未排班
				'11' => array() //外勤打卡
			);
			//先判断出未分配考勤组的人员
			// foreach($users as $k => $v){
			// 	if($v['attence_group'] == NULL || $v['attence_group'] == ''){
			// 		$size = count($count[11]);
			// 		$count[10][$size] = array(
			// 			'uid' => $v['uid'],
			// 			'realname' => $v['realname'],
			// 			'nickname' => $v['nickname'],
			// 			'head' => $v['head'],
			// 			'dept' => $v['dept'],
			// 			'rule' => (object)null,
			// 			'checkin' => array()
			// 		);
			// 	}
			// }
			//再判断已签到的人员
			$map['on_time'] = array(array('egt',strtotime($date)),array('lt',strtotime($date)+86400),'AND');
		    $map['off_time']  = array(array('egt', strtotime($date)),array('lt', strtotime($date)+86400),'AND'); 
		    $map['_logic'] = 'OR';
		    $where['_complex'] = $map;
			$records = $record->where($where)->select();
			// logger('团队当日所有签到：'.var_export($records,TRUE));//debug
			// $sql = $record->getLastsql(); //debug
			// logger('查询语句:'.$sql);  //debug
			if($records){
				foreach($records as $key => $value){
					if($value['on_status'] != 0 || $value['off_status'] == 0){
						// logger('上午签到，下午未签。签到记录ID：'.$value['id']); //debug
						$size = count($cont[$value['on_status']]);
						$count[$value['on_status']][$size] = array(
							'uid' => $value['uid'],
							'realname' => '',
							'nickname' => '',
							'head' => '',
							'dept' => '',
							'rule' => (object)null,
							'mark' => '',
							'checkin' => array(
											'on' => date('Y-m-d',$value['on_time'])
										)
						);
						switch($value['on_status']){
							case 2:
							case 3:
								$count[$value['on_status']][$size]['mark'] = '迟到'.$value['on_mark'].'分钟';
								break;
							case 5:
								$count[$value['on_status']][$size]['mark'] = '早退'.$value['on_mark'].'分钟';
								break;
							default:
								break;
						}
						continue;
					}
					if($value['on_status'] == 0 || $value['off_status'] != 0){
						logger('上午未签，下午签到。签到记录ID：'.$value['id']); //debug
						$size = count($cont[$value['off_status']]);
						$count[$value['off_status']][$size] = array(
							'uid' => $value['uid'],
							'realname' => '',
							'nickname' => '',
							'head' => '',
							'dept' => '',
							'rule' => (object)null,
							'mark' => '',
							'checkin' => array(
											'off' => date('Y-m-d',$value['off_time'])
										)
						);
						switch($value['off_status']){
							case 2:
							case 3:
								$count[$value['off_status']][$size]['mark'] = '迟到'.$value['off_mark'].'分钟';
								break;
							case 5:
								$count[$value['off_status']][$size]['mark'] = '早退'.$value['off_mark'].'分钟';
								break;
							default:
								break;
						}
						continue;
					}
					if($value['on_status'] == $value['off_status']){
						logger('上午下午签到状态一样'); //debug
						$size = count($cont[$value['on_status']]);
						$count[$value['on_status']][$size] = array(
							'uid' => $value['uid'],
							'realname' => '',
							'nickname' => '',
							'head' => '',
							'dept' => '',
							'rule' => (object)null,
							'mark' => '',
							'checkin' => array(
											'on' => date('Y-m-d',$value['on_time']),
											'off' => date('Y-m-d',$value['off_time']),
										)
						);
						continue;
					}
				}
			}else{
				// $data = array(
				// 	'code' => 0,
				// 	'message' => '团队没有考勤记录,统计失败!'
				// );
				// exit(json_encode($data));
				logger('考勤组今日尚无一人签到!');
				// logger('统计数组-为空:'.var_export($count,TRUE));
			}
			logger('循环员工数组，补充资料，找出未排班或未签到的人员'); //debug
			//循环判断团队用户数组，补充员工信息，并将未排班的信息登记
			$p = 0; 
			foreach($users as $k => $v){
				logger('循环开始-->'); //debug
				if($v['attence_group'] == NULL || $v['attence_group'] == '' || $v['attence_group'] == 0){
					logger('ID:'.$v['uid'].'未加入任何考勤组！'); //debug
					$size = count($count[10]);
					$count[10][$size] = array(
						'uid' => $v['uid'],
						'realname' => $v['realname'],
						'nickname' => $v['nickname'],
						'dept' => $v['dept'],
						'mark' => '',
						'rule' => (object)null,
						'checkin' => (object)null
					);
					if(strpos($v['head'],'Uploads/avatar/')){
						$count[10][$size]['head'] = C('base_url').$v['head'];
					}else{
						$count[10][$size]['head'] = '';
					}
					continue;
				}
				$size = 0;
				foreach($count as $x => $y){
					$size += count($y);
				}
				logger('一共统计了'.$size.'个员工'); //debug
				if($size > 0){ //如果这时候已统计人员
					$num = 1;
					foreach($count as $key => $value){
						$m = 0;
						foreach($value as $n){
							if($n['uid'] == $v['uid']){
								logger('补充资料，ID：'.$v['uid']); //debug
								$count[$key][$m]['realname'] = $v['realname'];
								$count[$key][$m]['nickname'] = $v['nickname'];
								if(strpos($v['head'],'Uploads/avatar/')){
									$count[$key][$m]['head'] = C('base_url').$v['head'];
								}else{
									$count[$key][$m]['head'] = '';
								}
								$count[$key][$m]['dept'] = $v['dept'];
								$count[$key][$m]['rule'] = $this->get_one_classinfo($v['attence_group'],$v['uid'],$date);
								break;
							}else{
								if($num == $size){
									logger('未打卡，ID：'.$v['uid']); //debug
									$mm = count($count[0]);
									$count[0][$mm] = array(
										'uid' => $v['uid'],
										'realname' => $v['realname'],
										'nickname' => $v['nickname'],
										'dept' => $v['dept'],
										'mark' => '',
										'rule' => $this->get_one_classinfo($v['attence_group'],$v['uid'],$date),
										'checkin' => (object)null
									);
									if(strpos($v['head'],'Uploads/avatar/')){
										$count[0][$mm]['head'] = C('base_url').$v['head'];
									}else{
										$count[0][$mm]['head'] = '';
									}
								}
								$m++;
								$num++;
							}
						}
					}
				}else{//如果这时还没有统计人员
					$count[0][$p] = array(
						'uid' => $v['uid'],
						'realname' => $v['realname'],
						'nickname' => $v['nickname'],
						'dept' => $v['dept'],
						'mark' => '',
						'rule' => $this->get_one_classinfo($v['attence_group'],$v['uid'],$date),
						'checkin' => (object)null
					);
					if(strpos($v['head'],'Uploads/avatar/')){
						$count[0][$p]['head'] = C('base_url').$v['head'];
					}else{
						$count[0][$p]['head'] = '';
					}
					$p++;
				}
			} 
			//统计各部门的数据
			$new = array();
			$i = 1;
			foreach($count as $key => $value){
				foreach($value as $x){
					if($i == 1){
						logger('初次建立部门:'.$x['dept']);
						$new[0]['count'] = $count;
						$new[0]['dept'] = '所有员工';
						$new[0]['totalstaff'] = $totalstaff;
						//规定格式 数组
						$new[1]['count'] = array(
							'0' => array(),
							'1' => array(),
							'2' => array(),
							'3' => array(),
							'4' => array(),
							'5' => array(),
							'6' => array(),
							'7' => array(),
							'8' => array(),
							'9' => array(),
							'10' => array(),
							'11' => array()	
						);
						$new[1]['count'][$key][0] = $x;
						$new[1]['dept'] = $x['dept'];
						$new[1]['totalstaff'] = 1;
					}else{
						$size = count($new);
						$m = 1;
						foreach($new as $k => $v){
							// logger('x-dept:'.$x['dept'].'v-dept:'.$v['dept']); //debug
							if($x['dept'] == $v['dept']){
								$num = count($new[$k]['count'][$key]);
								$new[$k]['count'][$key][$num] = $x;
								$new[$k]['totalstaff']++;
								break;
							}else{
								if($m == $size){
									logger('新建部门'.$x['dept']); //debug
									//规定格式 数组
									$new[$size]['count'] = array(
										'0' => array(),
										'1' => array(),
										'2' => array(),
										'3' => array(),
										'4' => array(),
										'5' => array(),
										'6' => array(),
										'7' => array(),
										'8' => array(),
										'9' => array(),
										'10' => array(),
										'11' => array()	
									);
									$new[$size]['count'][$key][0] = $x;
									$new[$size]['dept'] = $x['dept'];
									$new[$size]['totalstaff'] = 1;
								}
								$m++;
							}
						}
					}
					$i++;
				}
			}
			//补齐各部门内的各状态
			// foreach($new as $key => $value){
			// 	//将count统一切换为数组形式
			// 	// $new[$key]['count'] = get_object_vars($new[$key]['count']);
			// 	for($i=0;$i<=11;$i++){
			// 		if(!isset($new[$key]['count'][$i])){
			// 			$new[$key]['count'][$i] = array();
			// 		}
			// 	}
			// }
			//统计应到实到人数  算法：应到包含【未打卡、正常打卡、迟到、严重迟到、请假、外出、外勤】 实到包含【正常打卡、迟到、严重迟到、外勤】 均以上午打卡情况为准
			foreach($new as $key => $value){
				foreach($value['count'] as $k => $v){
					switch($k){
						case 0:
							$size = count($new[$key]['count'][$k]); //未打卡的人数
							$new[$key]['should'] = $size; // 应到人数
							$new[$key]['infact'] = 0; //初始化实到人数
							break;
						case 1:
						case 2:
						case 3:
						case 7:
						case 8:
						case 11:
							$size = count($new[$key]['count'][$k]); //先计算该状态的‘人数’
							if($size == 0){
								break;
							}else{
								foreach($v as $p){
									if($p['checkin']['on'] != '' && $p['checkin']['on'] != NULL){
										$new[$key]['should'] += 1; // 如果是上午的签到 应到和实到都加一
										$new[$key]['infact'] += 1;
									}
								}
							}
							break;
						default:
							break;
					}
				}
			}
			if($new){
				logger('团队考勤统计返回成功!'."\n"); 
				$data = array(
					'code' => 1,
					'message' => '团队考勤统计返回成功!',
					'count' => $new
				);
				exit(json_encode($data));
			}else{
				logger('团队考勤统计查询失败!'."\n"); 
				$data = array(
					'code' => 0,
					'message' => '团队考勤统计查询失败!'
				);
				exit(json_encode($data));
			}
		}else{
			logger('没有权限!'."\n"); 
			$data = array(
				'code' => 2,
				'message' => '没有权限!'
			);
			exit(json_encode($data));
		}
	}
	//获取某个员工某一天的考勤规则
	public function get_one_classinfo($group,$user,$date){
		logger('查询员工某日的考勤班次规则'); //debug
		$groups = D('attence_group');
		$where = array(
			'id' => $group
		);
		$my_group = $groups->where($where)->find();
		$rules = $this->chanslate_json_to_array($my_group['rules']);
		$rule = (object)null;
		switch($my_group['type']){ //判断考勤班制 1固定班制  2排班制
			case 1:
				logger('所在考勤组为固定班制！');
				// logger('考勤总规定:'.var_export($rules,TRUE)); //debug
				$week=array("日","一","二","三","四","五","六"); //预先定义一个星期的数组
				foreach($rules as $key => $value){
					if(strpos($value['week'],$week[date('w',time())])){
						$rule = array(
							'classname' => $value['classname'],
							'classid' => $value['classid'],
							'start' => $value['start'],
							'end' => $value['end']
						);
						break;
					}
				}
				break;
			case 2:
				logger('所在考勤组为排班制！');
				// logger('考勤总规定:'.var_export($rules,TRUE)); //debug
				foreach($rules as $k => $v){
					if($v['uid'] == $uid){
						$size = count($v['detail']);
						$num = 1;
						foreach($v['detail'] as $key => $value){
							if(date('Y-m-d',time()) == $value['date']){
								$rule = array(
									'classname' => $value['classname'],
									'classid' => $value['classid'],
									'start' => $value['start'],
									'end' => $value['end']
								);
								break;
							}else{ //如果没有，则未排班
								if($num == $size){
									$rule = FALSE;
								}
								$num++;
							}
						}
						break;
					}
				}
				break;
			default:
				logger('错误班制！');
				break;
		}
		return $rule;
	}
	//团队 迟到、旷工 月排行榜
	public function group_month_records(){
		logger('团队考勤迟到、旷工排行榜');
		$post = I();
		logger('携带参数：'.var_export($post,TRUE)); //debug
		//需要超级管理员权限
		if(session('wtype') == 1){
			//店铺id
			$sid = session('sid');
			//查询月份
			$month = $post['month'];
			//默认为查询当月
			if(empty($month)){
				$month = date('m',time());
			}
			//当月月份
			$now_month = date('Y',time()).'-'.$month;
			//当月开始时间戳
			$start = strtotime($now_month);
			//下月月份
			$next_month = date('Y',time()).'-'.($month + 1);
			//下月开始时间戳，查询截止时间
			$end = strtotime($next_month);
			// 查询当月所有签到【迟到或旷工】
			$record = D('attence_records');
			// 查询本月的签到记录  月计划任务或考勤组规则成员变更时执行的更新签到记录操作已将on_time全部填满了，所以不必再考虑到on_time不存在的情况了。
			$where = array(
				'sid' => $sid,
				'on_time' => array(array('egt',$start),array('lt',$end))
			);
			//判断查询类型是旷工 还是迟到
			$status = $post['status']; 
			if($status == 4){
				//旷工
				$tag = '旷工';
				logger('查询旷工排行榜');
				$map['on_status']  = array('eq',4); //上午的签到状态为旷工
			    $map['off_status']  = array('eq',4); //下午的签到状态为旷工
			    $map['_logic'] = 'or'; // 或者
			    $where['_complex'] = $map;
			}else{
				// 查询迟到 2是迟到 3是严重迟到
				$tag = '迟到';
				logger('查询迟到排行榜');
				$map['on_status']  = array('in','2,3'); //上午的签到状态为迟到或严重迟到
			    $map['off_status']  = array('in','2,3'); //下午的签到状态为迟到或严重迟到
			    $map['_logic'] = 'or'; // 或者
			    $where['_complex'] = $map;
			}
			// logger('查询Where条件：'.var_export($where,TRUE)); //debug
			//查询所有签到记录
			$result = $record->where($where)->select();
			// logger('查询结果:'.var_export($result,TRUE)); //debug
			if($result){ //查询有结果
				$i = 1;
				$records = array();
				foreach($result as $key => $value){
					if($i == 1){
						$records[0]['uid'] = $value['uid'];
						$records[0]['realname'] = '';
						$records[0]['nickname'] = '';
						$records[0]['head'] = '';
						$records[0]['mark'] = 0;
						$records[0]['dept'] = '';
						$records[0]['checkinid'] = '';
						if($value['on_status'] == $status){
							$records[0]['mark']++;
							$records[0]['checkinid'] = $value['id'].' ';
						}
						if($value['off_status'] == $status){
							$records[0]['mark']++;
							$records[0]['checkinid'] = $records[0]['checkinid'].$value['id'].' ';
						}
					}else{
						$size = count($records);
						$m = 1;
						foreach($records as $k => $v){
							if($value['uid'] == $v['uid']){
								if($value['on_status'] == $status){
									$records[$k]['mark']++;
									$records[$k]['checkinid'] = $records[$k]['checkinid'].$value['id'].' ';
								}
								if($value['off_status'] == $status){
									$records[$k]['mark']++;
									$records[$k]['checkinid'] = $records[$k]['checkinid'].$value['id'].' ';
								}
								break;
							}else{
								if($m == $size){
									$records[$size]['uid'] = $value['uid'];
									$records[$size]['realname'] = '';
									$records[$size]['nickname'] = '';
									$records[$size]['head'] = '';
									$records[$size]['mark'] = 0;
									$records[$size]['dept'] = '';
									$records[$size]['checkinid'] = '';
									if($value['on_status'] == $status){
										$records[$size]['mark']++;
										$records[$size]['checkinid'] = $value['id'].' ';
									}
									if($value['off_status'] == $status){
										$records[$size]['mark']++;
										$records[$size]['checkinid'] = $records[$size]['checkinid'].$value['id'].' ';
									}
								}
							}
							$m++;
						}
					}
					$i++;
				}
				if($records){
					logger('团队考勤排行榜返回成功!'."\n"); 
					//补齐员工的个人信息
					$app_user = D('app_user');
					$where = array(
						'sid' => $sid
					);
					$users = $app_user->where($where)->select();
					foreach($records as $key => $value){
						foreach($users as $k => $v){
							if($value['uid'] == $v['uid']){
								if(strpos($v['head'],'Uploads/avatar/')){
									$records[$key]['head'] = C('base_url').$v['head'];
								}else{
									$records[$key]['head'] = '';
								}
								$records[$key]['nickname'] = $v['nickname'];
								$records[$key]['realname'] = $v['realname'];
								$records[$key]['dept'] = $v['dept'];
							}
						}
					}
					$data = array(
						'code' => 1,
						'message' => '团队考勤排行榜返回成功!',
						'count' => $records
					);
					exit(json_encode($data));
				}else{
					logger('团队考勤排行榜返回失败!'."\n"); 
					$data = array(
						'code' => 0,
						'message' => '团队考勤排行榜返回失败!'
					);
					exit(json_encode($data));
				}
			}else{
				logger($tag.'排行榜为空!'."\n"); 
				$data = array(
					'code' => 4,
					'message' => $tag.'排行榜为空!'
				);
				exit(json_encode($data));
			}
		}else{
			logger('没有权限!'."\n"); 
			$data = array(
				'code' => 2,
				'message' => '没有权限!'
			);
			exit(json_encode($data));
		}
	}
	// 同步更新店铺内所有成员的签到记录状态
	public function update_store_records($sid = 0,$max = 0){
		logger('同步更新店铺所有员工的签到记录-=====->');
		$post = I();
		logger('传入参数:'.var_export($post,TRUE)); //debug
		$sid = $post['sid']; //来自URL传值
		$max = $post['max']; //来自URL传值
		if(!empty($sid)){
			$sid = $sid; //来自内部主动调用
		}
		if(!empty($max)){
			$max = $max; //来自内部主动调用
		}
		if(empty($sid)){
			$sid = session('sid'); //默认更新当前店铺
		}
		if(empty($max)){
			$max = 3; //默认最大更新3天
		}
		logger('更新店铺所有员工考勤记录,店铺ID:'.$sid); 
		logger('更新时段:'.$max.' 天!'); //debug
		//查询店铺所有员工
		$app_user = D('app_user');
		$where = array(
			'sid' => $sid ,
			'username' => array('neq','') //用户名不能为空
		);
		$users = $app_user->where($where)->select();
		//循环更新每一个员工的签到记录 过去几天的 不包括今天
		foreach($users as $k => $v){
			if($v['attence_group'] == '' || $v['attence_group'] == NULL){
				$this->update_status_records_nogroup($v['uid'],$v['sid'],$max);
			}else{
				$this->update_status_records($v['uid'],$v['attence_group'],$v['sid'],$max);
			}
		}
		logger('店铺所有员工的签到记录更新完成!'); //debug
	}
	//计划任务 定时更新全部用户的签到记录
	public function cron_update_records($max = 0){
		logger('同步更新所有用户的签到记录-=====->全部用户---->已列入计划任务');
		$post = I();
		logger('传入参数:'.var_export($post,TRUE)); //debug
		$max = $post['max']; //来自URL传值
		if(!empty($max)){
			$max = $max; //来自内部主动调用
		}
		if(empty($max)){
			$max = 3; //默认最大更新3天
		}
		logger('更新期为:'.$max.' 天'); //debug
		//查询店铺所有员工
		$app_user = D('app_user');
		$where = array(
			'username' => array(array('neq',''),array('neq',NULL),'OR') //用户名不能为空
		);
		$users = $app_user->where($where)->select();
		// $sql = $app_user->getLastsql(); //debug
		// logger('查询语句:'.$sql); //debug
		// logger('全部用户信息:'.var_export($users,TRUE)); //debug
		//循环更新每一个员工的签到记录 过去几天的 不包括今天
		$i = 1;
		foreach($users as $k => $v){
			logger('定时更新任务循环第'.$i.' 次!'); //debug
			if($v['attence_group'] == '' || $v['attence_group'] == NULL){
				logger('用户'.$v['uid'].'_'.$v['username'].'没有加入任何考勤组!'); //debug
				$this->update_status_records_nogroup($v['uid'],$v['sid'],$max);
			}else{
				logger('用户'.$v['uid'].'_'.$v['username'].'加入考勤组:'.$v['attence_group'].' 中!'); //debug
				$this->update_status_records($v['uid'],$v['attence_group'],$v['sid'],$max);
			}
			$i++;
		}
		$data = array(
			'code' => 1,
			'message' => '定时任务执行完毕!'
		);
		exit(json_encode($data));
		logger('定时任务执行完毕!'."\n");
	}
}
?>
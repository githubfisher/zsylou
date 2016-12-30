<?php
namespace Home\Controller;

use Think\Controller;

class TaskController extends Controller
{
	public function _initialize()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}

	// 任务列表 【 任务基本信息：名称、所属项目、负责人信息 】
	public function lists()
	{
		logger(session('uid').' 查看任务列表...');
		$task = array(
			'code' => 1,
			'message' => '任务返回成功！'
		);
		$task['result']['manage'] =$this->get_manage_task();
		$task['result']['create'] = $this->get_create_task();
		$task['result']['care'] = $this->get_care_task();
		// logger('查询到的任务：'.var_export($task,true)); // debug
		exit(json_encode($task));
	}

	// 查看任务详情 【 基本信息、检查项以及动态历史 】
	public function detail()
	{
		logger('查看任务详情...');
		$get = I();
		$id = $get['id'];
		if($id){
			$task = $this->get_detail($id);
			if($task['delete_at'] == 0){
				$task['check'] = $this->get_check($id);
				$task['dynamic'] = $this->get_dynamic($id);
				$task['care'] = $this->get_careuser($id);
				$task['discuss'] = $this->get_discuss($id);

				// 当前用户是否在关注者之列
				$task['isCare'] = 0;
				if(array_search(session('uid'),$task['care'])){
					$task['isCare'] = 1;
				}
				$data = array(
					'code' => 1,
					'message' => '任务详情返回成功！',
					'result' => $task
				);
				logger('任务详情返回成功！'."\n");
			}else{
				logger('任务已经删除，不能返回任务详情！'."\n");
				$data = array(
					'code' => 3,
					'message' => '任务已经删除！'
				);
			}
		}else{
			logger('查询任务详情参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}

	// 创建任务 【 一次性设置 名称、负责人、到期日期、所属项目 】 非必须项目：【 检查项、关注人】
	public function create()
	{
		logger(session('uid').'创建任务...');
		$get = I();
		$pid = $get['pid'];
		$name = $get['name'];
		$manager = $get['manager'];
		$check = $get['check']; // json // 非必须
		$deadline = $get['deadline'];
		$care = $get['care']; // 非必须
		$description = $get['description']; // 非必须
		if($pid && $name && $manager && $deadline){
			$info = array(
				'pid' => $pid,
				'name' => $name,
				'manager' => $manager,
				'deadline' => strtotime($deadline),
				'create_user' => session('uid'),
				'create_at' => time(),
				'description' => $description
			);
			$task = D('task');
			M()->startTrans();
			$result = $task->add($info);
			if($result){
				logger('添加任务主记录成功！');
				$status = true;
				if($check){
					$check = chanslate_json_to_array($check);
					if(count($check) >= 1){
						logger('存在任务检查项');
						foreach($check as $k){
							$array[] = array(
								'tid' => $result,
								'content' => $k,
								'create_at' => time(),
							);
						}
						$task_check = D('TaskChecks');
						if($task_check->addAll($array)){
							logger('添加任务检查项记录成功');
						}else{
							logger('添加任务检查项记录失败失败');
							$status = false;
						}
					}else{
						logger('存在任务检查项,但检查项设置有问题，未加入记录中');
					}
				}
				if($care && $status){
					$care = chanslate_json_to_array($care);
					logger(var_export($care,true)); // debug
					if(count($care) >= 1){
						logger('存在任务关注人');
						foreach($care as $k){
							$users[] = array(
								'tid' => $result,
								'uid' => $k,
								'create_at' => time(),
							);
						}
						$task_user = D('TaskUsers');
						if($task_user->addAll($users)){
							logger('添加任务检查项记录成功');
						}else{
							logger('添加任务检查项记录失败失败');
							$status = false;
						}
					}else{
						logger('存在任务关注人,但关注人设置有问题，未加入记录中');
					}
				}
				if($status){
					logger('任务创建成功!');
					// 动态
					$info = array(
						'pid' => $pid,
						'sid' => session('sid'),
						'type' => 1,
						'item' => $result,
						'uid' => session('uid'),
						'time' => time(),
						'content' => session('admin_nickname').' 创建了任务'
					);
					$dynamic = D('dynamic');
					if($dynamic->add($info)){
						logger('写入创建任务动态记录成功！');
						M()->commit();
						$data = array(
							'code' => 1,
							'message' => '任务创建成功！'
						);
					}else{
						logger('写入创建任务动态记录失败失败!'."\n");
						M()->rollback();
						$data = array(
							'code' => 5,
							'message' => '创建任务失败，请重试！'
						);
					}
				}else{
					logger('任务创建失败失败!'."\n");
					M()->rollback();
					$data = array(
						'code' => 4,
						'message' => '创建任务失败，请重试！'
					);
				}
			}else{
				logger('创建任务失败'."\n");
				M()->rollback();
				$data = array(
					'code' => 3,
					'message' => '创建任务失败，请重试！'
				);
			}
		}else{
			logger('创建任务参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}

	public function update()
	{
		logger('更新任务设置...');
		$get = I();
		if($this->check_update_request($get)){
			$this->update_task($get);
		}else{
			logger('创建任务参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
			exit(json_encode($data));
		}
	}

	// 删除任务、任务检查项、任务关注， 增加删除动态 无权限要求
	public function delete()
	{
		logger('删除任务...');
		$get = I();
		$id = $get['id'];
		$pid = $get['pid']; // 存入动态记录时使用

		if($id && $pid){
			$this->delete_task($id,$pid);
		}else{
			logger('删除任务参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
			exit(json_encode($data));
		}
	}

	public function done()
	{
		logger('查看已完成任务...');
		$get = I();
		$type = $get['type']; // 分类 1负责 2发起 3关注的
		$page = $get['page']; // 非必选 页码
		$num = $get['num']; // 非必须 每页刷新数据条数 默认10

		if($type){
			if(empty($page))
				$page = 1;
			if(empty($num))
				$num = 10;
			switch($type){
				case 1:
					$task = $this->get_done_manage_task($page,$num);
					break;
				case 2:
					$task = $this->get_done_create_task($page,$num);
					break;
				case 3:
					$task = $this->get_done_care_task($page,$num);
					break;
				default:
					logger('查看已完成任务，类型值传输出错！');
					$task = array();
					break;
			}
			// 处理头像
			if(count($task) >= 1){
				foreach($task as $k => $v){
					if(strpos($v['head'],'/Uploads/avatar/') === 0){
						$task[$k]['head'] = C('base_url').$task[$k]['head'];
					}
				}
			}
			$data = array(
				'code' => 1,
				'message' => '已完成任务返回成功！',
				'result' => $task
			);
			logger('已完成任务返回成功！'."\n");
		}else{
			logger('查看已完成任务,参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// 我负责的任务 取未删除且未完成的任务
	private function get_manage_task()
	{
		$task = $this->manage_task_handler();
		if(count($task) >= 1){
			// 处理头像
			foreach($task as $k => $v){
				if(strpos($v['head'],'/Uploads/avatar/') === 0){
					$task[$k]['head'] = C('base_url').$task[$k]['head'];
				}
			}
			// 任务到期分组
			$today = strtotime(date('Y-m-d',time()));
			$tomorrow = strtotime(date('Y-m-d',time()+86400));
			$tasks = array(
				'today' => array(),
				'later' => array()
			);
			foreach($task as $k => $v){
				if($v['deadline'] >= $today && $v['deadline'] < $tomorrow){
					$tasks['today'][] = $v;
				}else{
					$tasks['later'][] = $v;
				}
			}
			$task = $tasks;
		}else{
			$task = array(
				'today' => array(),
				'later' => array()
			);
		}

		return $task;
	}

	// 模型或缓存中读取 我负责的任务
	private function manage_task_handler()
	{
		$taskList = D('TaskList');
		$task = $taskList->where(array('uid'=>session('uid'),'delete_at'=>0,'status'=>0))->order('deadline asc')->select();
		// logger('查询的SQL语句：'.$taskList->getLastsql()); // debug

		return $task;
	}

	// 我发起的任务 取未删除且未完成的任务
	private function get_create_task()
	{
		$taskList = D('TaskList');
		$task = $taskList->where(array('create_user'=>session('uid'),'delete_at'=>0,'status'=>0))->order('create_at desc')->select();
		// logger('查询的SQL语句：'.$taskList->getLastsql()); // debug
		// 处理头像
		if(count($task) >= 1){
			foreach($task as $k => $v){
				if(strpos($v['head'],'/Uploads/avatar/') === 0){
					$task[$k]['head'] = C('base_url').$task[$k]['head'];
				}
			}
		}

		return $task;
	}

	// 我关注的任务 取未删除且未完成的任务
	private function get_care_task()
	{
		$taskCare = D('TaskCare');
		$task = $taskCare->where(array('careuid'=>session('uid'),'delete_at'=>0,'status'=>0))->order('create_at desc')->select();
		// logger('查询的SQL语句：'.$taskCare->getLastsql()); // debug
		// 处理头像
		if(count($task) >= 1){
			foreach($task as $k => $v){
				if(strpos($v['head'],'/Uploads/avatar/') === 0){
					$task[$k]['head'] = C('base_url').$task[$k]['head'];
				}
			}
		}

		return $task;
	}

	// 任务基本信息
	private function get_detail($id)
	{
		$detail = D('TaskDetail');
		$details = $detail->where(array('id'=>$id))->find();
		// 处理头像
		if(strpos($details['head'],'/Uploads/avatar/') === 0){
			$details['head'] = C('base_url').$details['head'];
		}

		return $details;
	}

	// 任务检查项
	private function get_check($id)
	{
		$check = D('TaskCheck');
		$checks = $check->where(array('tid'=>$id))->order('create_at asc')->select();
		// logger('查询检查项的SQL语句：'.$check->getLastsql()); // debug

		return $checks;
	}

	// 任务动态历史
	private function get_dynamic($id)
	{
		$dynamic = D('TaskDynamic');
		$dynamics = $dynamic->where(array('id'=>$id,'type'=>1))->order('time asc')->select();
		// logger('查询动态历史的SQL语句：'.$dynamic->getLastsql()); // debug

		return $dynamics;
	}

	// 任务关注人员
	private function get_careuser($id)
	{
		$careuser = D('TaskCareUser');
		$careusers = $careuser->where(array('id'=>$id))->select();
		// logger('查询动态历史的SQL语句：'.$careuser->getLastsql()); // debug
		if(count($careusers) >= 1){
			foreach($careusers as $k => $v){
				$array[] = $v['uid'];
			}
		}

		return $array;
	}

	// 任务讨论
	private function get_discuss($id)
	{
		$discuss = D('TaskDiscuss');
		$discusses = $discuss->where(array('item'=>$id,'type'=>1))->order('create_at asc')->select();
		// 处理头像
		if(count($discusses) >= 1){
			foreach($discusses as $k => $v){
				if(strpos($v['head'],'/Uploads/avatar/') === 0){
					$discusses[$k]['head'] = C('base_url').$discusses[$k]['head'];
				}
			}
		}

		return $discusses;
	}

	// 检查更新任务设置的输入
	private function check_update_request($get)
	{
		if(!$get['id']){
			return false;
		}
		if(!$get['pid']){ // 写入动态时会用到
			return false;
		}
		if(!$get['name'] && !isset($get['status']) && !$get['manager'] && !$get['deadline'] && !$get['check'] && !$get['care'] && !($get['check_id'] && isset($get['check_status'])) && !$get['toproject'] && !isset($get['icare']) && !$get['del_check'] && !($get['check_id'] && $get['content']) && !$get['description']){
			return false;
		}
		return true;
	}

	// 更新任务设置
	private function update_task($get)
	{
		$array = array('name','status','manager','deadline','check','care','check_status','toproject','icare','content','del_check','description');

		foreach($array as $k){
			if(isset($get[$k])){
				$this->update_driect($k,$get,$get['id']);
				break;
			}
		}
	}

	// 更新跳转
	private function update_driect($type,$data,$id)
	{
		switch($type){
			case 'name':
				$this->update_name_action($data,$id);
				break;
			case 'status':
				$this->update_status_action($data,$id);
				break;
			case 'manager':
				$this->update_manager_action($data,$id);
				break;
			case 'deadline':
				$this->update_deadline_action($data,$id);
				break;
			case 'check':
				$this->update_check_action($data['check'],$id);
				break;
			case 'check_status':
				$this->update_check_status_action($data,$id);
				break;
			case 'care':
				$this->update_care_action($data['care'],$id);
				break;
			case 'toproject':
				$this->update_toproject_action($data,$id);
				break;
			case 'icare':
				$this->update_icare_action($data,$id);
				break;
			case 'content':
				$this->update_content_action($data,$id);
				break;
			case 'del_check':
				$this->update_del_check_action($data,$id);
				break;
			case 'description':
				$this->update_description_action($data,$id);
				break;
			default:
				break;
		}
	}

	// 更新任务名称
	private function update_name_action($data,$id)
	{
		$detail = $this->get_detail($id);

		$name = $data['name'];
		$pid = $data['pid'];
		$task = D('task');
		M()->startTrans();
		if($task->where(array('id'=>$id))->save(array('name'=>$name))){
			logger('任务名称更新成功');
			$info = array(
				'pid' => $pid,
				'sid' => session('sid'),
				'type' => 1,
				'item' => $id,
				'uid' => session('uid'),
				'time' => time(),
				'content' => session('admin_nickname').' 将任务"'.$detail['name'].'"修改为"'.$name.'"'
			);
			$dynamic = D('dynamic');
			if($dynamic->add($info)){
				logger('写入更新任务名称动态记录成功！');
				M()->commit();
				$data = array(
					'code' => 1,
					'message' => '更新任务名称成功！'
				);
			}else{
				logger('写入更新任务名称动态记录失败失败!'."\n");
				M()->rollback();
				$data = array(
					'code' => 4,
					'message' => '更新任务名称失败，请重试！'
				);
			}
		}else{
			logger('任务名称更新失败失败！'."\n");
			M()->rollback();
			$data = array(
				'code' => 3,
				'message' => '更新任务名称失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	// 更新任务状态
	private function update_status_action($data,$id)
	{
		$detail = $this->get_detail($id);

		$status = $data['status'];
		$pid = $data['pid'];
		$string = $status == 1 ? ' 完成了任务' : ' 重新打开了任务';
		$done_time = $status == 1 ? time() : 0;
		$task = D('task');
		M()->startTrans();
		if($task->where(array('id'=>$id))->save(array('status'=>$status,'done_at'=>$done_time))){
			logger('任务状态更新成功');
			$info = array(
				'pid' => $pid,
				'sid' => session('sid'),
				'type' => 1,
				'item' => $id,
				'uid' => session('uid'),
				'time' => time(),
				'content' => session('admin_nickname').$string
			);
			$dynamic = D('dynamic');
			if($dynamic->add($info)){
				logger('写入更新任务状态动态记录成功！');
				M()->commit();
				$data = array(
					'code' => 1,
					'message' => '更新任务状态成功！'
				);
			}else{
				logger('写入更新任务状态动态记录失败失败!'."\n");
				M()->rollback();
				$data = array(
					'code' => 6,
					'message' => '更新任务状态失败，请重试！'
				);
			}
		}else{
			logger('任务状态更新失败失败！'."\n");
			M()->rollback();
			$data = array(
				'code' => 5,
				'message' => '更新任务状态失败，请重试！'
			);
		}
		exit(json_encode($data));	
	}

	// 更新任务负责人
	private function update_manager_action($data,$id)
	{
		$detail = $this->get_detail($id);

		$manager = $data['manager'];
		$pid = $data['pid'];
		$task = D('task');
		M()->startTrans();
		if($task->where(array('id'=>$id))->save(array('manager'=>$manager))){
			logger('任务负责人更新成功');
			$newNickname = $this->get_nickname($manager);
			$info = array(
				'pid' => $pid,
				'sid' => session('sid'),
				'type' => 1,
				'item' => $id,
				'uid' => session('uid'),
				'time' => time(),
				'content' => session('admin_nickname').' 把 '.$detail['nickname'].' 的任务指派给 '.$newNickname
			);
			$dynamic = D('dynamic');
			if($dynamic->add($info)){
				logger('写入更新任务负责人动态记录成功！');
				M()->commit();
				$data = array(
					'code' => 1,
					'message' => '指派负责人成功！',
					'result' => $newNickname
				);
			}else{
				logger('写入更新任务负责人动态记录失败失败!'."\n");
				M()->rollback();
				$data = array(
					'code' => 8,
					'message' => '指派负责人失败，请重试！'
				);
			}
		}else{
			logger('任务负责人更新失败失败！'."\n");
			M()->rollback();
			$data = array(
				'code' => 7,
				'message' => '指派负责人失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	private function get_nickname($uid)
	{
		$user = D('app_user');
		$info = $user->where(array('uid'=>$uid))->field('nickname')->cache(true,60)->find();

		return $info['nickname'];
	}

	// 更新任务截止日期
	private function update_deadline_action($data,$id)
	{
		$detail = $this->get_detail($id);

		$deadline = $data['deadline'];
		$pid = $data['pid'];
		$task = D('task');
		M()->startTrans();
		if($task->where(array('id'=>$id))->save(array('deadline'=>strtotime($deadline)))) {
			logger('任务完成时间更新成功');
			$info = array(
				'pid' => $pid,
				'sid' => session('sid'),
				'type' => 1,
				'item' => $id,
				'uid' => session('uid'),
				'time' => time(),
				'content' => session('admin_nickname').' 将任务完成时间从 "'.date('Y-m-d',$detail['deadline']).'" 修改为 "'.$deadline.'"'
			);
			$dynamic = D('dynamic');
			if($dynamic->add($info)){
				logger('写入更新任务完成时间动态记录成功！');
				M()->commit();
				$data = array(
					'code' => 1,
					'message' => '修改完成时间成功！'
				);
			}else{
				logger('写入更新任务完成时间动态记录失败失败!'."\n");
				M()->rollback();
				$data = array(
					'code' => 10,
					'message' => '修改完成时间失败，请重试！'
				);
			}
		}else{
			logger('任务完成时间更新失败失败！'."\n");
			M()->rollback();
			$data = array(
				'code' => 9,
				'message' => '修改完成时间失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	// 更新任务检查项 增加
	private function update_check_action($data,$id)
	{
		$check = chanslate_json_to_array($data);
		if(($max = count($check)) >= 1){
			foreach($check as $k){
				$array[] = array(
					'tid' => $id,
					'content' => $k,
					'create_at' => time()
				); 
			}
			$task_check = D('TaskChecks');
			if($checkId = $task_check->addAll($array)){
				logger('增加任务检查项成功！'."\n");
				// $checkIds = array();
				// for($i=0;$i<$max;$i++){
				// 	$checkIds[$i] = (int)$result;
				// 	$result++;
				// }
				$data = array(
					'code' => 1,
					'message' => '添加检查项成功！',
					'result' => $checkId // 返回对应检查项的ID
				);
			}else{
				logger('增加任务检查项失败失败！'."\n");
				$data = array(
					'code' => 12,
					'message' => '添加检查项失败，请重试！'
				);
			}
		}else{
			logger('增加任务检查项，但检查项输入有误，更新失败！'."\n");
			$data = array(
				'code' => 11,
				'message' => '添加检查项失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	// 更新任务检查项的状态 变更
	private function update_check_status_action($data,$id)
	{
		$task_check = D('TaskChecks');
		if($task_check->where(array('id'=>$data['check_id']))->save(array('status'=>$data['check_status']))){
			logger('更新任务检查项状态成功！'."\n");
			$data = array(
				'code' => 1,
				'message' => '更新检查项成功！'
			);
		}else{
			logger('更新任务检查项状态失败失败！'."\n");
			$data = array(
				'code' => 0,
				'message' => '更新检查项失败，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// 更新任务关注人员 增加
	private function update_care_action($data,$id)
	{
		$care = chanslate_json_to_array($data);
		if(count($care) >= 1){
			foreach($care as $k){
				$array[] = array(
					'tid' => $id,
					'uid' => $k,
					'create_at' => time()
				); 
			}
			$task_user = D('TaskUsers');
			if($task_user->addAll($array)){
				logger('增加任务关注人员成功！'."\n");
				$data = array(
					'code' => 1,
					'message' => '添加关注人成功！',
					'result' => $care
				);
			}else{
				logger('增加任务关注人员失败失败！'."\n");
				$data = array(
					'code' => 14,
					'message' => '添加关注人失败，请重试！'
				);
			}
		}else{
			logger('增加任务关注人员，但关注人员输入有误，更新失败！'."\n");
			$data = array(
				'code' => 13,
				'message' => '添加关注人失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	// 移动任务到其他项目
	private function update_toproject_action($data,$id){
		$toproject = $data['toproject'];
		$pid = $data['pid'];
		$task = D('task');
		M()->startTrans();
		if($task->where(array('id'=>$id))->save(array('pid'=>$toproject))) {
			logger('移动任务成功');
			$info = array(
				'pid' => $pid,
				'sid' => session('sid'),
				'type' => 1,
				'item' => $id,
				'uid' => session('uid'),
				'time' => time(),
				'content' => session('admin_nickname').' 移动了任务'
			);
			$dynamic = D('dynamic');
			if($dynamic->add($info)){
				logger('写入移动任务动态记录成功！');
				M()->commit();
				$data = array(
					'code' => 1,
					'message' => '移动任务成功！'
				);
			}else{
				logger('写入移动任务动态记录失败失败!'."\n");
				M()->rollback();
				$data = array(
					'code' => 16,
					'message' => '移动任务失败，请重试！'
				);
			}
		}else{
			logger('移动任务失败失败！'."\n");
			M()->rollback();
			$data = array(
				'code' => 15,
				'message' => '移动任务失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	// 我关注或取消关注任务
	private function update_icare_action($data,$id)
	{
		$task_user = D('TaskUsers');
		if($data['icare'] == 1){
			if($task_user->add(array('tid'=>$id,'uid'=>session('uid'),'create_at'=>time()))){
				$data = array(
					'code' => 1,
					'message' => '关注成功！'
				);
			}else{
				$data = array(
					'code' => 17,
					'message' => '关注失败，请重试！'
				);
			}
		}else{
			if($task_user->where(array('tid'=>$id,'uid'=>session('uid')))->delete()){
				$data = array(
					'code' => 1,
					'message' => '取消关注成功！'
				);
			}else{
				$data = array(
					'code' => 18,
					'message' => '取消关注失败，请重试！'
				);
			}
		}
		exit(json_encode($data));
	}

	// 修改检查项内容
	private function update_content_action($data,$id)
	{
		$task_check = D('TaskChecks');
		if($task_check->where(array('id'=>$data['check_id']))->save(array('content'=>$data['content']))){
			logger('更新任务检查项内容成功！'."\n");
			$data = array(
				'code' => 1,
				'message' => '更新检查项成功！'
			);
		}else{
			logger('更新任务检查项内容失败失败！'."\n");
			$data = array(
				'code' => 19,
				'message' => '更新检查项失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	// 删除检查项
	private function update_del_check_action($data,$id)
	{
		$task_check = D('TaskChecks');
		if($task_check->where(array('id'=>$data['del_check']))->delete()){
			logger('删除任务检查项成功！'."\n");
			$data = array(
				'code' => 1,
				'message' => '删除检查项成功！',
				'result' => $id
			);
		}else{
			logger('删除任务检查项失败失败！'."\n");
			$data = array(
				'code' => 20,
				'message' => '删除检查项失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	// 修改任务描述内容
	private function update_description_action($data,$id)
	{
		$task = D('task');
		if($task->where(array('id'=>$id))->save(array('description'=>$data['description']))){
			logger('更新任务描述成功！'."\n");
			$data = array(
				'code' => 1,
				'message' => '更新任务描述成功！'
			);
		}else{
			logger('更新任务描述失败失败!'."\n");
			$data = array(
				'code' => 21,
				'message' => '更新任务描述失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	// 删除任务 、检查项 、关注人 ，增加删除动态
	private function delete_task($id,$pid)
	{
		M()->startTrans();

		$task_check = D('TaskChecks');
		if($task_check->where(array('tid'=>$id))->delete() >= 0){
			logger('删除任务检查项成功！');
			$task_user = D('TaskUsers');
			if($task_user->where(array('tid'=>$id))->delete() >= 0){
				logger('删除任务关注者成功！');
				$task = D('task');
				if($task->where(array('id'=>$id))->save(array('delete_at'=>time()))){ // 软删除
					logger('删除任务记录成功！');
					$info = array(
						'pid' => $pid,
						'sid' => session('sid'),
						'type' => 1,
						'item' => $id,
						'uid' => session('uid'),
						'time' => time(),
						'content' => session('admin_nickname').' 删除了任务'
					);
					$dynamic = D('dynamic');
					if($dynamic->add($info)){
						logger('添加删除任务动态记录成功！'."\n");
						M()->commit();
						$data = array(
							'code' => 1,
							'message' => '删除成功！'
						);
					}else{
						logger('添加删除任务动态记录失败，回滚'."\n");
						M()->rollback();
						$data = array(
							'code' => 5,
							'message' => '删除失败，请重试！'
						);
					}
				}else{
					logger('删除任务记录失败，回滚'."\n");
					M()->rollback();
					$data = array(
						'code' => 4,
						'message' => '删除失败，请重试！'
					);
				}
			}else{
				logger('删除任务关注者失败，回滚'."\n");
				M()->rollback();
				$data = array(
					'code' => 3,
					'message' => '删除失败，请重试！'
				);
			}
		}else{
			logger('删除任务检查项失败，回滚'."\n");
			M()->rollback();
			$data = array(
				'code' => 2,
				'message' => '删除失败，请重试！'
			);
		}
		exit(json_encode($data));
	}

	private function get_done_manage_task($page,$num)
	{
		if($tasks = S(session('uid').'manageDone')){
			$start = ($page-1)*$num;
			$task = array_slice($tasks,$start,$num);
		}else{
			$taskList = D('TaskList');
			$tasks = $taskList->where(array('uid'=>session('uid'),'delete_at'=>0,'status'=>1))->order('done_at desc')->cache(session('uid').'manageDone')->select(); // 默认每页10条
			$start = ($page-1)*$num;
			$task = array_slice($tasks,$start,$num);
		}
		return $task;
	}

	private function get_done_create_task($page,$num)
	{
		if($tasks = S(session('uid').'createDone')){
			$start = ($page-1)*$num;
			$task = array_slice($tasks,$start,$num);
		}else{
			$taskList = D('TaskList');
			$tasks = $taskList->where(array('create_user'=>session('uid'),'delete_at'=>0,'status'=>1))->order('done_at desc')->cache(session('uid').'createDone')->select();
			$start = ($page-1)*$num;
			$task = array_slice($tasks,$start,$num);
		}
		return $task;
	}

	private function get_done_care_task($page,$num)
	{
		if($tasks = S(session('uid').'careDone')){
			$start = ($page-1)*$num;
			$task = array_slice($tasks,$start,$num);
		}else{
			$taskCare = D('TaskCare');
			$tasks = $taskCare->where(array('careuid'=>session('uid'),'delete_at'=>0,'status'=>1))->order('done_at desc')->cache(session('uid').'careDone')->select();
			$start = ($page-1)*$num;
			$task = array_slice($tasks,$start,$num); 
		}
		
		
		return $task;
	}
}
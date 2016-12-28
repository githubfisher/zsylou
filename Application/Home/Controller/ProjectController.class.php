<?php
namespace Home\Controller;

use Think\Controller;

class ProjectController extends Controller
{
	public function _initialize()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}

	public function lists()
	{
		$sid = session('sid');
		logger('查看店铺项目列表...，店铺ID：'.$sid);

		// 检查是否存在默认项目
		if($this->check_default_project($sid)){
			logger('店铺'.$sid.'已存在默认项目，继续....');
			$PL = D('ProjectList');
			$project = $PL->where(array('uid' => session('uid')))->order('create_at asc')->cache(true,60)->select();

			$data = array(
				'code' => 1,
				'message' => '项目列表查询成功！',
				'result' => $project
			);
			logger('店铺'.$sid.'项目列表返回成功！'."\n");
		}else{
			logger('店铺'.$sid.'不存在默认项目，且创建失败...'."\n");
			$data = array(
				'code' => 0,
				'message' => '创建默认项目失败！'
			);
		}
		exit(json_encode($data));
	}

	public function create()
	{
		logger('创建项目...');
		$get = I();
		$name = $get['name'];
		$members = $get['member'];
		if($name && $members){
			$info = array(
				'name' => $name,
				'create_user' => session('uid'),
				'create_at' => time(),
				'sid' => session('sid')
			);
			$project = D('project');

			M()->startTrans();
			$result = $project->add($info);
			if($result){
				logger('1.创建项目成功！');
				$members = chanslate_json_to_array($members);
				if(count($members) >= 1){
					$array = array();
					$i = 0;
					foreach($members as $member){
						$array[$i]['uid'] = $member;
						$array[$i]['pid'] = $result;
						$array[$i]['create_at'] = time();
						$i++;
					}
					$project_user = D('project_user');

					$rela_result = $project_user->addAll($array);
					if($rela_result){
						logger('2.添加项目成员记录成功！');
						M()->commit();
						$data = array(
							'code' => 1,
							'message' => '创建项目成功！',
							'result' => $result
						);
					}else{
						logger('2.添加项目成员记录失败失败！');
						M()->rollback();
						$data = array(
							'code' => 4,
							'message' => '创建失败，请重试！'
						);
					}
				}else{
					logger('3.项目成员为空，失败失败！');
					M()->rollback();
					$data = array(
						'code' => 3,
						'message' => '创建失败，请重试！'
					);
				}
			}else{
				logger('1.创建项目失败失败！');
				M()->rollback();
				$data = array(
					'code' => 0,
					'message' => '创建失败，请重试！'
				);
			}
		}else{
			logger('创建项目参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}

	public function detail()
	{
		$get = I();
		$id = $get['id'];
		if($id){
			logger('查询项目：'.$id.'详情....');
			$pro = $this->get_detail($id);
			$pro['member'] = $this->get_member($id);
			$pro['task'] = $this->get_task($id);
			$pro['file'] = $this->get_file($id);
			$pro['discuss'] = $this->get_discuss($id);

			$data = array(
				'code' => 1,
				'message' => '项目详情返回成功！',
				'result' => $pro
			);
		}else{
			logger('创建项目参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}

	public function update()
	{
		$get = I();
		$id = $get['id'];
		$name = $get['name'];
		$members = $get['member'];
		if($id && ($name || $members)){
			logger('更新项目设置，项目ID：'.$id);
			$project = D('project');
			$pro = $project->where(array('id'=>$id))->field('name,create_user,sid')->cache(true,60)->find();
			if($pro['create_user'] == 0){
				logger('默认项目不可修改名称或组成员！'."\n");
				$data = array(
					'code' => 3,
					'message' => '默认项目不可修改！'
				);
				exit(json_encode($data));
			}
			if($pro['create_user'] == session('uid')){
				if($name){
					$data = $this->update_name($id,$name);
				}
				if($members){
					$data = $this->update_member($id,$members);
				}
			}else{
				logger('没有修改权限！'."\n");
				$data = array(
					'code' => 4,
					'message' => '没有修改权限！'
				);
			}
		}else{
			logger('创建项目参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}

	public function delete()
	{
		$get = I();
		$id = $get['id'];
		if($id){
			logger('删除项目：'.$id.'....');
			$project = D('project');
			$pro = $project->where(array('id'=>$id))->field('name,create_user,sid')->cache(true,60)->find();
			if($pro['create_user'] == 0){
				logger('默认项目不可删除！'."\n");
				$data = array(
					'code' => 3,
					'message' => '默认项目不可删除！'
				);
				exit(json_encode($data));
			}
			if($pro['create_user'] == session('uid')){
				if($this->project_delete($id)){
					logger('项目及相关信息删除成功！'."\n");
					$data = array(
						'code' => 1,
						'message' => '删除成功！'
					);
				}else{
					logger('项目及相关信息删除失败！'."\n");
					$data = array(
						'code' => 5,
						'message' => '删除失败，请重试！'
					);
				}
			}else{
				logger('没有删除权限！'."\n");
				$data = array(
					'code' => 4,
					'message' => '没有删除权限！'
				);
			}
		}else{
			logger('删除项目参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}

	private function check_default_project($sid)
	{
		// "默认项目" + SID + "0"
		$where = array(
			'name' => '默认项目',
			'sid' => $sid,
			'create_user' => 0
		);
		$project = D('project');

		$result = $project->where($where)->cache(true,60)->find();

		if(!$result){
			$data = array(
				'name' => '默认项目',
				'sid' => $sid,
				'create_user' => 0,
				'create_at' => time(),
			);
			$add_result = $project->add($data);
			if(!$add_result){
				logger('创建默认项目失败！店铺ID：'.$sid);
				return false;
			}
		}

		return true;
	}

	// 项目组设置
	private function get_detail($id)
	{
		$project = D('project');
		$pro = $project->where(array('id'=>$id))->field('id,name,create_user,sid')->cache(true,60)->find();

		return $pro;
	}

	// 项目组成员
	private function get_member($id)
	{
		$project = $this->get_detail($id);
		if(($project['create_user'] == 0) && ($project['name'] == '默认项目')){
			$user = D('app_user');
			$mbs = $user->where(array('sid'=>session('sid'),'username'=>array('neq','')))->field('nickname,uid,head')->cache(true,60)->select();
		}else{
			$PM = D('ProjectMember');
			$mbs = $PM->where(array('pid'=>$id))->cache(true,60)->select();
		}
		// 处理头像
		if(count($mbs) >= 1){
			foreach($mbs as $k => $v){
				if(strpos($v['head'],'/Uploads/avatar/') === 0){
					$mbs[$k]['head'] = C('base_url').$mbs[$k]['head'];
				}
			}
		}

		return $mbs;
	}

	// 项目组任务 取未删除的且未完成的任务
	private function get_task($id)
	{
		$taskList = D('TaskList');
		$tasks = $taskList->where(array('pid'=>$id,'delete_at'=>0,'status'=>0))->order('create_at desc')->cache(true,60)->select();
		// 处理头像
		if(count($tasks) >= 1){
			foreach($tasks as $k => $v){
				if(strpos($v['head'],'/Uploads/avatar/') === 0){
					$tasks[$k]['head'] = C('base_url').$tasks[$k]['head'];
				}
			}
		}

		return $tasks;
	}

	// 项目组文件 取未删除的文件
	private function get_file($id)
	{
		$FL = D('FileList');
		$files = $FL->where(array('pid'=>$id,'delete_at'=>0))->order('upload_at desc')->cache(true,60)->select();
		// 处理头像
		if(count($files) >= 1){
			foreach($files as $k => $v){
				if(strpos($v['head'],'/Uploads/avatar/') === 0){
					$files[$k]['head'] = C('base_url').$files[$k]['head'];
				}
			}
		}

		return $files;
	}

	private function get_discuss($id)
	{
		$time = strtotime(date('Y-m-d',time()-C('dynamic_time'))); // 取近3天的评论
		$DL = D('DiscussList');
		$disc = $DL->where(array('pid'=>$id,'create_at'=>array('egt',$time)))->order('create_at desc')->cache(true,60)->select();
		// 处理头像
		if(count($disc) >= 1){
			foreach($disc as $k => $v){
				if(strpos($v['head'],'/Uploads/avatar/') === 0){
					$disc[$k]['head'] = C('base_url').$disc[$k]['head'];
				}
			}
		}
		
		return $disc;
	}

	private function update_name($id,$name)
	{
		logger('更新项目名称，项目ID：'.$id);
		$project = D('project');

		$result = $project->where(array('id'=>$id))->save(array('name'=>$name,'modify_at'=>time()));
		if($result){
			logger('修改项目名称成功！'."\n");
			$data = array(
				'code' => 1,
				'message' => '更新项目成功！'
			);
		}else{
			logger('修改项目名称失败失败！'."\n");
			$data = array(
				'code' => 0,
				'message' => '更新项目失败失败！'
			);
		}
		return $data;
	}

	private function update_member($id,$members)
	{
		logger('更新项目成员，项目ID：'.$id);
		$project_user = D('project_user');
		$oldmbs = $project_user->where(array('pid'=>$id))->field('id,uid')->cache(true,60)->select();
		// logger('原有成员：'.var_export($oldmbs,true)); // debug
		$members = chanslate_json_to_array($members);

		// 被清除的成员
		$ids = '';
		$max = count($members);
		foreach($oldmbs as $k => $v){
			$i = 1;
			foreach($members as $y){
				if($v['uid'] != $y){
					if($i == $max){
						$ids .= $v['id'].',';
					}
					$i++;
				}
			}
		}
		$ids = rtrim($ids,',');
		// logger('要删除的项目成员ID：'.$ids); // debug

		M()->startTrans();
		if(strlen($ids) < 1){
			logger('没有需要删除的成员！');
			$result = true;
		}else{
			$result = $project_user->delete($ids);
		}
		if($result){
			// 被添加的成员
			$array = array();
			$max = count($oldmbs);
			$n = 0;
			foreach($members as $x){
				$i = 1;
				foreach($oldmbs as $k => $v){
					if($v['uid'] != $x){
						if($i == $max){
							$array[$n]['uid'] = $x;
							$array[$n]['pid'] = $id;
							$array[$n]['create_at'] = time();
							$n++;
						}
						$i++;
					}
				}
			}
			// logger('要添加的项目成员：'.var_export($array,true)); // debug
			if(count($array) < 1){
				logger('没有需要添加的成员！');
				$result = true;
			}else{
				$result = $project_user->addAll($array);
			}
			if($result){
				logger('更新项目成员成功！'."\n");
				M()->commit();
				$data = array(
					'code' => 1,
					'message' => '更新成功！'
				);
			}else{
				logger('更新项目成员,添加时失败！'."\n");
				M()->rollback();
				$data = array(
					'code' => 2,
					'message' => '更新失败，请重试！'
				);
			}
		}else{
			logger('更新项目成员，删除时失败！'."\n");
			M()->rollback();
			$data = array(
				'code' => 0,
				'message' => '更新失败，请重试！'
			);
		}

		return $data;
	}

	private function project_delete($id)
	{
		logger('删除项目及其相关信息....ID:'.$id);
		
		$status = true;
		M()->startTrans();
		
		$discuss = D('discuss');
		if($discuss->where(array('pid'=>$id))->delete() >= 0){
			logger('删除项目讨论信息成功！');
			$file = D('file');
			if($file->where(array('pid'=>$id))->delete() >= 0){
				logger('删除项目文件信息成功！');
				$task = D('task');
				$tasks = $task->where(array('pid'=>$id))->field('id')->select(false);
				if($task_user->join('JOIN ( '.$tasks.' ) AS t ON t.id = task_user.tid')->delete() >= 0){
					logger('删除项目任务关注人员信息成功！');
					if($task_check->join('JOIN ( '.$tasks.' ) AS t ON t.id = task_check.tid')->delete() >= 0){
						logger('删除项目任务检查项信息成功！');
						if($task->where(array('pid'=>$id))->delete() >= 0){
							logger('删除项目任务信息成功！');
							$dynamic = D('dynamic');
							if($dynamic->where(array('pid'=>$id))->delete() >= 0){
								logger('删除项目动态信息成功！');
								$project_user = D('project_user');
								if($project_user->where(array('pid'=>$id))->delete() >= 0){
									logger('删除项目成员信息成功！');
									$project = D('project');
									if($project->delete($id)){
										logger('删除项目及其相关信息,全部成功！'."\n");
										M()->commit();
									}else{
										M()->rollback();
										$status = false;
										logger('删除项目及其相关信息,全部失败，回滚'."\n");
									}
								}else{
									M()->rollback();
									$status = false;
									logger('删除项目成员信息失败！回滚'."\n");
								}
							}else{
								M()->rollback();
								$status = false;
								logger('删除项目动态信息失败！回滚'."\n");
							}
						}else{
							M()->rollback();
							$status = false;
							logger('删除项目任务信息失败！回滚'."\n");
						}
					}else{
						M()->rollback();
						$status = false;
						logger('删除项目任务检查项信息失败！回滚'."\n");
					}
				}else{
					M()->rollback();
					$status = false;
					logger('删除项目任务关注人员信息失败！回滚'."\n");
				}
			}else{
				M()->rollback();
				$status = false;
				logger('删除项目文件信息失败！回滚'."\n");
			}
		}else{
			M()->rollback();
			$status = false;
			logger('删除项目讨论信息失败！回滚'."\n");
		}
		
		return $status;
	}
}
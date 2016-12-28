<?php
namespace Home\Model;

use Think\Model\ViewModel;

class TaskCareModel extends ViewModel
{
	public $viewFields = array(
		'task_user' => array('uid'=>'careuid','_as'=>'tu'),
		'task' => array('id','pid','name','create_at','deadline','status','create_user','delete_at','done_at','_as'=>'t','_on'=>'tu.tid=t.id'),
		'project' => array('name'=>'project','_as'=>'p','_on'=>'p.id=t.pid'),
		'app_user' => array('nickname','head','uid','_as'=>'u','_on'=>'t.manager=u.uid'),
	);
}
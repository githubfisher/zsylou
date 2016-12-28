<?php
namespace Home\Model;

use Think\Model\ViewModel;

class TaskListModel extends ViewModel
{
	public $viewFields = array(
		'task' => array('id','pid','name','create_at','deadline','status','create_user','delete_at','done_at','_as'=>'t'),
		'project' => array('name'=>'project','_on'=>'t.pid=p.id','_as'=>'p'),
		'app_user' => array('nickname','head','uid','_on'=>'t.manager=u.uid','_as'=>'u'),
	);
}
<?php
namespace Home\Model;

use Think\Model\ViewModel;

class TaskDetailModel extends ViewModel
{
	public $viewFields = array(
		'task' => array('id','name','create_at','deadline','status','description','delete_at','_as'=>'t'),
		'app_user' => array('nickname','uid','head','_as'=>'u','_on'=>'t.manager=u.uid'),
		'project' => array('id'=>'pid','name'=>'project','_as'=>'p','_on'=>'p.id=t.pid')
	);
}
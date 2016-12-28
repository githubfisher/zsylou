<?php
namespace Home\Model;

use Think\Model\ViewModel;

class TaskCareUserModel extends ViewModel
{
	public $viewFields = array(
		'task' => array('id','_as'=>'t'),
		'task_user' => array('uid','_as'=>'u','_on'=>'t.id=u.tid')
	);
}
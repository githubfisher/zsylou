<?php
namespace Home\Model;

use Think\Model\ViewModel;

class TaskCheckModel extends ViewModel
{
	public $viewFields = array(
		'task' => array('id'=>'tid','_as'=>'t'),
		'task_check' => array('id','content','status','create_at','_as'=>'c','_on'=>'t.id=c.tid')
	);
}
<?php
namespace Home\Model;

use Think\Model\ViewModel;

class TaskDynamicModel extends ViewModel
{
	public $viewFields = array(
		'task' => array('id','_as'=>'t'),
		'dynamic' => array('time','content','type','_as'=>'d','_on'=>'t.id=d.item')
	);
}
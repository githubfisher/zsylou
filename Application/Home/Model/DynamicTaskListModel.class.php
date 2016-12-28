<?php
namespace Home\Model;

use Think\Model\ViewModel;

class DynamicTaskListModel extends ViewModel
{
	public $viewFields = array(
		'project_user' => array('uid'=>'cid','_as'=>'pu'),
		'project' => array('name'=>'project','_as'=>'p','_on'=>'pu.pid=p.id'),
		'dynamic' => array('id','pid','uid','content','type','item','time','_as'=>'d','_on'=>'d.pid=p.id'),
		'app_user' => array('nickname','head','_as'=>'u','_on'=>'u.uid=d.uid'),
		'task' => array('name'=>'item_name','_as'=>'t','_on'=>'t.id=d.item AND d.type=1'),
	);
}
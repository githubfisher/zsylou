<?php
namespace Home\Model;

use Think\Model\ViewModel;

class TaskDiscussModel extends ViewModel
{
	public $viewFields = array(
		'task' => array('_as'=>'t'),
		'discuss' => array('type','item','uid','content','am_uri','am_name','am_thumb','am_type','create_at','_as'=>'d','_on'=>'t.id=d.item'),
		'app_user' => array('nickname','head','_as'=>'u','_on'=>'d.uid=u.uid')
	);
}
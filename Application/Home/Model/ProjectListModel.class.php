<?php
namespace Home\Model;

use Think\Model\ViewModel;

class ProjectListModel extends ViewModel
{
	public $viewFields = array(
		'project_user' => array('uid','_as'=>'u'),
		'project' => array('id','name','create_user','create_at','_as'=>'p','_on'=>'u.pid=p.id'),
		
	);
}
<?php
namespace Home\Model;

use Think\Model\ViewModel;

class ProjectMemberModel extends ViewModel
{
	public $viewFields = array(
		'project_user' => array('pid','_as'=>'pu'),
		'app_user' => array('uid','nickname','head','_as'=>'u','_on'=>'pu.uid=u.uid')
	);
}
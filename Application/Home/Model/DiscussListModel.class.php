<?php
namespace Home\Model;

use Think\Model\ViewModel;

class DiscussListModel extends ViewModel
{
	public $viewFields = array(
		'discuss' => array('id','pid','type','item','uid','content','am_uri','am_thumb','am_type','am_name','create_at','_as'=>'d'),
		'project' => array('name'=>'project','_as'=>'p','_on'=>'p.id=d.pid'),
		'app_user' => array('nickname','head','_as'=>'u','_on'=>'d.uid=u.uid')
	);
}
<?php
namespace Home\Model;

use Think\Model\ViewModel;

class FileDiscussModel extends ViewModel
{
	public $viewFields = array(
		'file' => array('_as'=>'f'),
		'discuss' => array('type','item','uid','content','am_uri','am_name','am_thumb','am_type','create_at','_as'=>'d','_on'=>'f.id=d.item'),
		'app_user' => array('nickname','head','_as'=>'u','_on'=>'d.uid=u.uid')
	);
}
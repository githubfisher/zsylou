<?php
namespace Home\Model;

use Think\Model\ViewModel;

class FileDetailModel extends ViewModel
{
	public $viewFields = array(
		'file' => array('id','name','upload_at','uri','upload_user'=>'uid','type','thumb','pid','delete_at','_as'=>'f'),
		'app_user' => array('nickname','head','_as'=>'u','_on'=>'f.upload_user=u.uid'),
		'project' => array('name'=>'project','_as'=>'p','_on'=>'p.id=f.pid')
	);
}
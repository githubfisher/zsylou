<?php
namespace Home\Model;

use Think\Model\ViewModel;

class FileListModel extends ViewModel
{
	public $viewFields = array(
		'file' => array('id','name','thumb','pid','type','upload_at','upload_user','uri','_as'=>'f'),
		'project' => array('name'=>'project','_as'=>'p','_on'=>'p.id=f.pid'),
		'app_user' => array('nickname','head','_as'=>'u','_on'=>'f.upload_user=u.uid')
	);
}
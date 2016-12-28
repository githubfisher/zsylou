<?php
namespace Home\Model;

use Think\Model\ViewModel;

class DynamicFileListModel extends ViewModel
{
	public $viewFields = array(
		'project_user' => array('uid'=>'cid','_as'=>'pu'),
		'project' => array('name'=>'project','_as'=>'p','_on'=>'pu.pid=p.id'),
		'dynamic' => array('id','pid','uid','content','type','item','time','_as'=>'d','_on'=>'d.pid=p.id'),
		'app_user' => array('nickname','head','_as'=>'u','_on'=>'u.uid=d.uid'),
		'file' => array('name'=>'item_name','_as'=>'f','_on'=>'f.id=d.item AND d.type=2')
	);
}
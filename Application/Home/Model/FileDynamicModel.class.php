<?php
namespace Home\Model;

use Think\Model\ViewModel;

class FileDynamicModel extends ViewModel
{
	public $viewFields = array(
		'file' => array('id','_as'=>'f'),
		'dynamic' => array('time','content','type','_as'=>'d','_on'=>'f.id=d.item')
	);
}
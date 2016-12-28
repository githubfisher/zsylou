<?php
namespace Admin\Model;
use Think\Model\ViewModel;
class TempletImageModel extends ViewModel
{
	public $viewFields = array(
		'weiimgtem' => array('tid','_as'=>'t'),
		'weiimage' => array('id','url','_as'=>'i','_on'=>'i.id=t.iid')
	);
}
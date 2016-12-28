<?php
namespace Wei\Model;

use Think\Model\ViewModel;

class CareCategoryModel extends ViewModel
{
	public $viewFields = array(
		'weicareuser' => array('id'=>'careid','uid','cid','_as'=>'u'),
		'weicategory' => array('id','name','thumb','sid','weight','delete_at','_as'=>'c','_on'=>'u.cid=c.id')
	);
}
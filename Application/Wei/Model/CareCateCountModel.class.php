<?php
namespace Wei\Model;

use Think\Model\ViewModel;

class CareCateCountModel extends ViewModel
{
	public $viewFields = array(
		'weicareuser' => array('id'=>'careid','uid','cid','_as'=>'u'),
		'weicategory' => array('id','name','thumb','sid','weight','delete_at'=>'delete_time','_as'=>'c','_on'=>'u.cid=c.id'),
		'weitemplet' => array('id'=>'tid','create_at','delete_at','_as'=>'t','_on'=>'t.cid=c.id')
	);
}
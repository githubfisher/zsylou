<?php
namespace Wei\Model;

use Think\Model\ViewModel;

class MyTempletModel extends ViewModel
{
	public $viewFields = array(
		'weitemplet' => array('id','name','thumb','remind','author','delete_at','create_at','retranstimes','_as'=>'t'),
		'weicategory' => array('name'=>'category','weight','_as'=>'c','_on'=>'c.id=t.cid')
	);
}
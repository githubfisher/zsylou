<?php
namespace Admin\Model;
use Think\Model\ViewModel;
class CateTempletModel extends ViewModel
{
	public $viewFields = array(
		'weicategory' => array('name'=>'category','_as'=>'c'),
		'weitemplet' => array('id','cid','sid','name','content','thumb','create_at','modify_at','delete_at','retranstimes','commend','_as'=>'t','_on'=>'c.id=t.cid')
	);
}
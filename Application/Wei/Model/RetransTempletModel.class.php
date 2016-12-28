<?php
namespace Wei\Model;

use Think\Model\ViewModel;

class RetransTempletModel extends ViewModel
{
	public $viewFields = array(
		'weiretransmission' => array('sid','uid','cid','tid','create_at','_as'=>'r'),
		'weitemplet' => array('id','name','thumb','sid','delete_at','_as'=>'t','_on'=>'r.tid=t.id'),
		'weicategory' => array('name'=>'category','weight','_as'=>'c','_on'=>'c.id=t.cid')
	);
}
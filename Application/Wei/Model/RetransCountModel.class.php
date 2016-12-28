<?php
namespace Wei\Model;

use Think\Model\ViewModel;

class RetransCountModel extends ViewModel
{
	public $viewFields = array(
		'weiretransmission' => array('id'=>'rid','uid','sid','create_at','_as'=>'r'),
		'weicategory' => array('name'=>'category','_as'=>'c','_on'=>'r.cid=c.id'),
		'weitemplet' => array('id','name','thumb','_as'=>'t','_on'=>'t.id=r.tid'),
		// 'weiremind' => array('create_at'=>'remind_at','_as'=>'m','_on'=>'m.tid=t.id AND m.create_date=r.date')
	);
}
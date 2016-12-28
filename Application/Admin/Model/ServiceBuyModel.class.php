<?php
namespace Admin\Model;
use Think\Model\ViewModel;
class ServiceBuyModel extends ViewModel
{
	public $viewFields = array(
		'tool' => array('name','_as'=>'t'),
		'tool_buy' => array('sid','item'=>'id','create_at','expire_time'=>'expire','_as'=>'b','_on'=>'t.id=b.item')
	);
}
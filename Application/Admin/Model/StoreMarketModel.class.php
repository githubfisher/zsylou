<?php
namespace Admin\Model;

use Think\Model\ViewModel;

class StoreMarketModel extends ViewModel
{
	public $viewFields = array(
			'store' => array('id','storename','dogid','ip','port','createtime','store_simple_name','pad','expiring_on','_as'=>'s'),
			'market_area' => array('id'=>'aid','name','_as'=>'m','_on'=>'s.market_area=m.id')
		);
}
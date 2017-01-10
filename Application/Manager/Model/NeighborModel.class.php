<?php
namespace Manager\Model;

use Think\Model\ViewModel;

class NeighborModel extends ViewModel {
	public $viewFields = array(
		'location' => array('east','north','login_at','hash','_as'=>'l'),
		'app_user' => array('uid','nickname','type','mobile','head','_as'=>'u','_on'=>'u.uid=l.uid'),
		'store' => array('storename','_as'=>'s','_on'=>'s.id=u.sid'),
		'market_area' => array('name'=>'area','_as'=>'a','_on'=>'a.id=s.market_area')
	);
}
<?php
namespace Wei\Model;

use Think\Model\ViewModel;

class TempletRetransModel extends ViewModel
{
	public $viewFields = array(
		'weitemplet' => array('id','_as'=>'t'),
		'weiretransmission' => array('sid','cid','uid','create_at','_as'=>'r','_on'=>'r.tid=t.id'),
		'app_user' => array('head','nickname','_as'=>'u','_on'=>'u.uid=r.uid')
	);
}
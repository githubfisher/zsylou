<?php
namespace Admin\Model;
use Think\Model\ViewModel;
class UsersModel extends ViewModel
{
	public $viewFields = array(
		'store' => array('storename'=>'store','_as'=>'s'),
		'app_user' => array('uid','username','nickname','type','logintime','loginip','createtime','store_simple_name'=>'simple','sid','_as'=>'u','_on'=>'s.id=u.sid')
	);
}
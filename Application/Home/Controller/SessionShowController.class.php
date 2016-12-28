<?php
namespace Home\Controller;
use Think\Controller;
class SessionShowController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	//预留
	public function index(){

	}
	public function show(){
		echo "<pre>";
		var_dump($_SESSION);
	}
}
?>
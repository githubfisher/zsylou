<?php
namespace Admin\Controller;
use Think\Controller;
class WeitransController extends Controller {
	public function _initialize(){
		if(!session('?name')){
			$this->redirect('Admin/login/redirect',array(),0.1,'请登录。。。');
		}
		header("content-type:text/html; charset=utf-8;");
        Vendor('Easemob.Easemob');
	}
    //首页
    public function index(){
    	$this->assign('user',session('name'));
        $this->assign('title','首页--掌上影楼');
    	$this->display();
    }
    public function top(){
        $this->assign('user',session('name'));
        $this->display("Index/top");
    }
    public function footer(){
        $this->display("Index/left");
    }
}
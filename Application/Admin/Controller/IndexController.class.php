<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends Controller {
	public function _initialize(){
        header("content-type:text/html; charset=utf-8;");
		if(!session('?name')){
			$this->redirect('Admin/login/redirect',array(),0.1,'请先登录!');
		}	
	}
    public function index(){
    	$this->assign('title','首页--掌上影楼');
        $this->assign('admin',session('name'));
    	$this->display();
    }
}
 
<?php
namespace Home\Controller;
use Think\Controller;
class ShowMylouController extends Controller{
	public function __initialize(){
		header("content-type:text/html; charset=utf-8;");
	}
	// 预留
	public function index(){

	}
	//展示我的影楼H5页面
    public function show_mylou(){
        logger('展示我的影楼H5页面');
        $post = I();
        $id = $post['id'];
        $my_ylou = D('my_ylou');
        $where = array(
            'id' => $id
        );
        $the_ylou = $my_ylou->where($where)->find();
        $the_ylou['content'] = chansfer_to_html($the_ylou['content']);
        $this->assign('ylou',$the_ylou);
        $this->display();
    }
}
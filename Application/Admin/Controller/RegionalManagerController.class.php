<?php
namespace Admin\Controller;

use Think\Controller;

class RegionalManagerController extends Controller
{
    public function _initialize()
    {
        if(!session('?name')){
            $this->redirect('Admin/login/redirect',array(),0.1,'请登录。。。');
        }
        header("content-type:text/html; charset=utf-8;");
        Vendor('Easemob.Easemob');
    }
    public function index()
    {
        $this->assign('user',session('name'));
        $this->assign('title','首页--掌上影楼');
        $this->display();
    }
    public function top(){
        $this->assign('user',session('name'));
        $this->display();
    }
    public function left(){
        $this->assign('pad',session('pad')); //是否开启pad端
        $this->display();
    }
    public function footer(){
        $this->display();
    }
    public function ylou_list(){
        logger('区域经理查看影楼信息...');
        $id = session('location');
        $market_manager = D('market_manager');
        $manager = $market_manager->where(array('id' => $id))->field('market_area,id,name,phone')->find();
        if($manager){
            $areas = explode(' ',$manager['market_area']);
            if(!empty($areas)){
                logger('存在管理区域信息...');
                $where = array(
                    'market_area' => array('in',$areas)
                );
                $store = D('store');
                $ylous = $store->where($where)->field('modify_time,pad,icon',true)->select(false);
                $area = D('market_area');
                $ylous = $area->join('JOIN ('.$ylous.' ) AS a ON a.market_area = market_area.id')->field('a.*,market_area.name')->select();
                $this->assign('ylou',$ylous);
                logger('影楼信息返回成功！'."\n");
            }
        }else{
            logger('查询区域经理信息失败，经理id信息出错');
            $this->error('区域经理信息错误，请联系管理员！');
        }
        $this->display();
    }
    public function user_list(){
        logger('区域经理查看影楼员工信息...');
        $id = session('location');
        $market_manager = D('market_manager');
        $manager = $market_manager->where(array('id' => $id))->field('market_area,id,name,phone')->find();
        if($manager){
            $areas = explode(' ',trim($manager['market_area'],' '));
            logger(var_export($areas,TRUE));
            if(!empty($areas)){
                logger('存在管理区域信息...');
                $where = array(
                    'market_area' => array('in',$areas)
                );
                $store = D('store');
                $ylous = $store->where($where)->field('id')->select();
                if(!empty($ylous)){
                    $yids = '';
                    foreach($ylous as $k => $v){
                        $yids .= $v['id'].',';
                    }
                    $yids = trim($yids,',');
                    $user = D('app_user');
                    $users = $user->join('JOIN store AS a ON a.id = app_user.sid AND a.id IN ('.$yids.')')->field('a.storename AS store,app_user.uid,app_user.type,app_user.username,app_user.nickname,app_user.createtime,app_user.logintime,app_user.loginip,app_user.store_simple_name')->select();
                    $this->assign('user',$users);
                    logger('员工信息返回成功！'."\n");
                }else{
                    $this->assign('user',array());
                    logger('员工信息为空，返回成功！'."\n");
                }
            }
        }else{
            logger('查询区域经理信息失败，经理id信息出错');
            $this->error('区域经理信息错误，请联系管理员！');
        }
        $this->display();
    }
    public function area_list(){
        logger('区域经理查看负责区域信息...');
        $id = session('location');
        $market_manager = D('market_manager');
        $manager = $market_manager->where(array('id' => $id))->field('market_area,id,name,phone')->find();
        if($manager){
            $areas = explode(' ',trim($manager['market_area'],' '));
            if(!empty($areas)){
                logger('存在管理区域信息...');
                $where = array(
                    'id' => array('in',$areas)
                );
                $area = D('market_area');
                $areas = $area->where($where)->select();
                $this->assign('area',$areas);
                logger('负责区域信息返回成功！'."\n");
            }
        }else{
            logger('查询区域经理信息失败，经理id信息出错');
            $this->error('区域经理信息错误，请联系管理员！');
        }
        $this->display();
    }
}
<?php
namespace Admin\Controller;
use Think\Controller;
class MylouController extends Controller {
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
        $expire = session('expire_date');
        $this->assign('expire',$expire);
        if($expire == 0){
            $this->assign('color','green'); 
            $this->assign('display','none'); 
            $this->assign('line_height','35px');
        }else if($expire == -1){
           $this->assign('color','yellow'); 
           $this->assign('display','none'); 
           $this->assign('line_height','35px');
        }else{
           $now = time();
            $left = (int)$expire - (int)$now;
            if($left <= 604800){
              $this->assign('color','red'); 
              $this->assign('display','block');  
              $this->assign('line_height','20px');
            }else{
               $this->assign('color','white'); 
               $this->assign('display','none');
               $this->assign('line_height','35px');
            }
        }
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
    // 影楼管理 dogid 服务器ip地址等 一般不需要特别管理，一次配置就好
    public function manage(){
        $sid = session('sid');
        // logger('店铺ID：'.$sid); //debug
        if($sid){
            $store = D('store');
            $where = array(
                'id' => $sid
            );
            $result = $store->where($where)->select();
            // logger('影楼查询结果：'.var_export($result,TRUE)); //debug
            if($result){
                $app_user = D('app_user');
                $admin_num = $app_user->where(array('sid'=>$sid,'type'=>1))->count();
                $user_num = $app_user->where(array('sid'=>$sid,'type'=>0))->count();
                $this->assign('admin',$admin_num);
                $this->assign('users',$user_num);
                $this->assign('ylou',$result);
                $this->assign('user',session('name'));
                $this->display('store');
            }else{
                $this->error('未获取店铺的正确信息，请重新登录！');
            }
        }else{
            $this->error('未获取用户信息，请重新登录！');
        }
    }
    public function users(){
        $app_user = D('app_user');
        $where = array(
            'sid' => session('sid'),
            'username' => array(array('neq',''),array('neq',NULL),'OR')  //显示有用户名的员工账号
        );
        $result = $app_user->where($where)->field('password',TRUE)->order('type desc')->select();
        // $sql = $app_user->getLastsql(); //debug
        // logger('查询语句：'.$sql); //debug
        $this->assign('users',$result);
        $this->display();
    }
    //我的员工 同步
    public function my_user(){
        logger('同步影楼员工联系人-->开始');
        // $get_ylou_contacts = A('Home/GetYLouContacts');    //弃用
        //链接远程服务器，获取员工数据
        $admin = array(
            'operation' => 9,
            'dogid' => session('dogid')
        );
        $xml = transXML($admin);
        $xml = strchr($xml,'<uu>',TRUE);
        $url = session('url');
        // logger('查询xml:'.$url.$xml."--->"); //debug
        //强制转码 由utf8转成gbk
        $xml = mb_convert_encoding($xml,'gbk','utf8');
        $getxml = getXML($url,$xml);
        $result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
        // logger('XML:'.$result);//debug 
        if(strlen($result) < 38){
            logger("该影楼无联系人信息");
            logger("&&&>>> 该影楼“无”联系人信息 ----完毕---- <<<&&&\n");
            // $user = array();
            // $message = '无联系人信息！';
            // $this->assign('user',$user);
            // $this->assign('msg',$message);
            // $this->display('user');
            $data = array(
                'status' => 0,
                'info' => '没有账号信息可同步！'
            );
            $this->ajaxReturn($data);
        }else{
            logger("处理该影楼联系人信息-->开始");
            $str_xml = substr(rtrim($result,'></recipe>'),32);
            $tra_arr = explode('><l>',$str_xml);
            // logger('xml转数组：'.var_export($tra_arr,TRUE)); //debug
            // die;
            // $tra_arr2 = $get_ylou_contacts->contact_arr2($tra_arr);     //弃用
            $tra_arr2 = $this->contact_arr2($tra_arr); //函数体从Home/GetYLouContacts模块中复制过来
            // logger('新同步联系人情况：'.var_export($tra_arr2,TRUE));//debug
            logger("处理该影楼联系人信息-->更新或增加");
            // die;
            $app_user = D('app_user');
            // 第一步先查询是否已存在该店铺的联系人信息，如果存在则更新，如果不存在则直接导
            $where = array(
                'sid' => session('sid')
            );
            $num_app_user= $app_user->where($where)->count();
            $app_user_info = $app_user->where($where)->select();
            // $sql = $app_user->getLastsql(); //debug
            // logger('查询语句：'.$sql); //debug
            // logger('原有影楼联系人数量：'.$num_app_user); //debug
            if($num_app_user == 1){
                logger('只存在管理员联系人信息！');
                //若当前只有管理员一人，则添加全部联系人到app_user表中
                $result = $app_user->addAll($tra_arr2);
                // $sql = $app_user->getLastsql(); //debug
                // logger('添加SQL语句:'.$sql); //debug
                // die;
                if($result){
                    logger('员工联系人添加成功！');
                    $user = $app_user->where(array('sid'=>session('sid')))->select();
                    //注册环信
                    // $register_easemob_result = $get_ylou_contacts->create_easemob_user();    //弃用
                    $register_easemob_result = $this->create_easemob_user();  //函数体从Home/GetYLouContacts模块中复制过来
                    if($register_easemob_result){
                        logger('注册环信成功！');
                    }else{
                        logger('注册环信失败！');
                    }
                    $data = array(
                        'status' => 1,
                        'info' => '同步员工成功！'
                    );
                    $this->ajaxReturn($data);
                }else{
                    logger('员工联系人添加失败！');
                    $data = array(
                        'status' => 0,
                        'info' => '账号同步失败！'
                    );
                    $this->ajaxReturn($data);
                }
            }else{ //循环判断是否有变化，如果有则更新。无变化则丢弃! 废弃该思路，删除原有的员工联系人（除管理员外），将新联系人新增到员工联系人中
                //如果已有员工账户存在，则全部删除，重新导入。
                logger('已存在员工联系人信息！');
                logger('循环判断是否有新用户和更新用户的部门，qq等信息。判断依据为用户名和真实信息');
                foreach($tra_arr2 as $key => $value){
                    $i = 1;
                    foreach($app_user_info as $k => $v){
                        if($value['username'] == NULL || $value['username'] == ''){
                            if($value['realname'] == $v['realname']){
                                $i++;
                                $userinfo = array(
                                    'gender' => $value['gender'],
                                    'dept' => $value['dept'],
                                    'mobile' => $value['mobile'],
                                    'qq' => $value['qq'],
                                    'location' => $value['location'],
                                );
                                $where = array(
                                    'sid' => session('sid'),
                                    'realname' => $value['realname']
                                );
                                $update_result = $app_user->where($where)->save($userinfo);
                                // $sql = $app_user->getLastsql(); //debug
                                // logger($sql); //debug
                                if($update_result){
                                    logger('用户：'.$v['realname'].'更新成功！'."\n");
                                }else{
                                    logger('用户：'.$v['realname'].'更新失败。'."\n");
                                }
                                break;
                            }else{
                                $i++;
                                if($i > count($app_user_info)){
                                    $add_result = $app_user->add($value);
                                    if($add_result){
                                        logger('添加了新用户！姓名为：'.$value['realname'].'用户名为：'.$value['username']."\n");
                                        //如果用户名不为空，则创建环信账号
                                        if($value['username'] != '' && $value['username'] != NULL){
                                            $create_result = easemob_create_user($value['store_simple_name'].'_'.$value['username'],$value['password']);
                                            if($create_result['error'] != ''){
                                                logger('用户：'.$value['store_simple_name'].'_'.$value['username'].'创建失败，失败原因------>'.$create_result['error']);
                                            }else{
                                                logger('用户：'.$value['store_simple_name'].'_'.$value['username'].'创建环信用户成功！'."\n");
                                            }
                                        }
                                    }else{
                                        logger('添加新用户失败！其姓名为：'.$value['realname'].'用户名为：'.$value['username']."\n");
                                    }
                                    break;
                                }
                                continue;
                            }
                        }else{
                            if($value['username'] == $v['username']){  //用户名不为空时，就依据用户名判断用户的信息变化。 虽然这不能避免错误，但是在没有id的情形下只能这么做了。
                                $i++;
                                $userinfo = array(
                                    'realname' => $value['realname'],
                                    'gender' => $value['gender'],
                                    'dept' => $value['dept'],
                                    'mobile' => $value['mobile'],
                                    'qq' => $value['qq'],
                                    'location' => $value['location'],
                                );
                                $where = array(
                                    'username' => $value['username'],
                                    'sid' => session('sid')
                                );
                                $update_result = $app_user->where($where)->save($userinfo);
                                $sql = $app_user->getLastsql(); //debug
                                logger($sql); //debug
                                if($update_result){
                                    logger('用户：'.$v['realname'].'更新成功！'."\n");
                                }else{
                                    logger('用户：'.$v['realname'].'更新失败。'."\n");
                                }
                                break;
                            }else{
                                $i++;
                                if($i > count($app_user_info)){
                                    $add_result = $app_user->add($value);
                                    if($add_result){
                                        logger('添加了新用户！姓名为：'.$value['realname'].'用户名为：'.$value['username']."\n");
                                        //如果用户名不为空，则创建环信账号
                                        if($value['username'] != '' && $value['username'] != NULL){
                                            $create_result = easemob_create_user($value['store_simple_name'].'_'.$value['username'],$value['password']);
                                            if($create_result['error'] != ''){
                                                logger('用户：'.$value['store_simple_name'].'_'.$value['username'].'创建失败，失败原因------>'.$create_result['error']);
                                            }else{
                                                logger('用户：'.$value['store_simple_name'].'_'.$value['username'].'创建环信用户成功！'."\n");
                                            }
                                        }
                                    }else{
                                        logger('添加新用户失败！其姓名为：'.$value['realname'].'用户名为：'.$value['username']."\n");
                                    }
                                    break;
                                }
                                continue;
                            }
                        }
                    }
                }
                logger('影楼管理员: 查看是否有删除的员工账户');
                $max = count($tra_arr2);
                logger('erp端用户数量:'.$max);//debug
                foreach($app_user_info as $k => $v){
                    $n = 1;
                    foreach($tra_arr2 as $x => $y){
                        if($v['username'] != ''){
                            if($v['username'] == $y['username']){
                                break;
                            }else{
                                if(($max == $n ) && ($v['username'] != 'admin') && ($v['type'] != 1)){
                                    logger('影楼管理员: 店铺需要删除的员工账户__'.$v['username'].'__'.$v['realname']);
                                    $where = array();
                                    $where['uid'] = $v['uid'];
                                    $del_result = $app_user->where($where)->delete();
                                    if($del_result){
                                        logger('影楼管理员:删除员工账户记录成功!');
                                    }else{
                                        logger('影楼管理员:删除员工账户记录失败!');
                                    }
                                    $easemob_username = $v['store_simple_name'].'_'.$v['username'];
                                    $easemob_del_result = delete_easemob_user($easemob_username);
                                    if($easemob_del_result['error'] == ''){
                                        logger('影楼管理员:删除员工__环信__账户记录成功!');
                                    }else{
                                        logger('影楼管理员:删除员工__环信__账户记录失败!');
                                    }
                                }
                                $n++;
                            }
                        }else{
                            break;
                        }
                    }
                }
                logger('同步员工,成功完成！'."\n");
                $data = array(
                    'status' => 1,
                    'info' => '同步员工成功！'
                );
                $this->ajaxReturn($data);
            }
        }
    }
    //为员工账号注册环信 重新注册
    public function my_user_easemob(){
        // 先删除原来的环信账号
        $get_ylou_contacts = A('Home/GetYLouContacts');
        logger('删除原环信账号');
        $del_easemob_result = $get_ylou_contacts->delete_easemob_user();
        // 再重新注册
        logger('重新注册环信账号');
        $register_easemob_result = $get_ylou_contacts->create_easemob_user();
    }
    //修改密码，登录密码和环信密码
    public function modify_user_pwd(){
        logger('修改用户密码-->开始');
        $post = I();
        logger('传入参数：'.var_export($post,TRUE)); //debug
        $new_pwd = $post['pwd'];
        $uid = $post['uid'];
        $user = $post['user'];
        if($new_pwd && $uid && $user){
            $app_user = D('app_user');
            $where = array(
                'uid' => $uid
            );
            $update_data = array(
                'password' => $new_pwd,
                'modify_time' => time()
            );
            $update = $app_user->where($where)->save($update_data);
            if($update){
                logger('修改app用户密码成功！');
                $return_data = array(
                    'status' => 1,
                    'info' => '修改密码成功！'
                );
                // 更新环信密码
                //修改成功，下一步修改环信的密码
                $user = session('store_simple_name').'_'.$user;
                $modify_result = modify_easemob_pwd($user,$new_pwd);
                logger('修改环信密码返回值：'.var_export($modify_result,TRUE));
                //判断修改情况  需要进一步完善环信的返回值识别
                if($modify_result){
                    logger('app，环信密码都修改成功！'); 
                    $return_data = array(
                        'status' => 1,
                        'info' => '修改密码成功！'
                    );
                }else{
                    logger('app密码修改成功，环信密码修改失败！'); 
                     $return_data = array(
                        'status' => 1,
                        'info' => '修改密码成功！'
                    );
                }
                $this->ajaxReturn($return_data);
            }else{
                logger('修改app用户密码失败！');
                $return_data = array(
                    'status' => 0,
                    'info' => '修改密码失败！'
                );
                $this->ajaxReturn($return_data);
            }
        }else{
            logger('提交信息不全');
            $return_data = array(
                'status' => 2,
                'info' => '提交数据不全！'
            );
            $this->ajaxReturn($return_data);
        }
    }
    // 我的影楼介绍，H5页面。列表
    public function my_ylou_list(){
        logger('影楼管理后台：查看影楼H5页面');
        $my_ylou = D('my_ylou');
        $where = array(
            'sid' => session('sid')
        );
        $my_ylou_result = $my_ylou->where($where)->order('id desc')->select();
        $this->assign('ylou',$my_ylou_result);
        $this->display('mylou');
    }
    //新建h5页面 表单页
    public function new_redius(){
        $post = I();
        $id = $post['id'];
        if($id){
            logger('修改H5页面!');
            $my_ylou = D('my_ylou');
            $where = array(
                'id' => $id
            );
            $result = $my_ylou->where($where)->find();
            $result['content'] = chansfer_to_html($result['content']);
            $this->assign('ylou',$result);
            $this->assign('ttip','修改简介');
            $this->display();
        }else{
            logger('新建H5页面');
            $this->assign('ttip','新建简介');
            $this->display();
        }
    }
    // 我的影楼介绍，H5页面。添加
    public function my_ylou_add(){
        logger('我的影楼-->');
        $my_ylou = D('my_ylou');
        $post = I();
        $content = $post['content'];
        $title = $post['title'];
        $id = $post['id'];
        //先查询是否已存在影楼页面
        $where = array(
            'sid' => session('sid')
        );
        if($id){
            //修改页面
            logger('修改-->');
            $data = array(
                'title' => $title,
                'content' => $content,
                'modify_time' => time(),
            );
            $result = $my_ylou->where(array('id'=>$id))->save($data);
            if($result){
                $data = array(
                    'status' => 1,
                    'content' => '修改成功！'
                );
                logger('修改成功！'."\n");
                $this->ajaxReturn($data);
            }else{
                $data = array(
                    'status' => 0,
                    'content' => '修改失败！'
                );
                logger('修改失败！'."\n");
                $this->ajaxReturn($data);
            }
        }else{
            // 新增页面
            logger('增加-->');
            $data = array(
                'title' => $title,
                'content' => $content,
                'time' => time(),
                'is_open' => 1,
                'sid' => session('sid')
            );
            $result = $my_ylou->add($data);
            if($result){
                logger('增加成功！'."\n");
                $data = array(
                    'status' => 1,
                    'content' => '增加成功！'
                );
                $add = array(
                    'url' => 'http://'.$_SERVER['HTTP_HOST'].'/index.php/home/ShowMylou/show_mylou?id='.$result
                );
                $where = array(
                    'id' => $result
                );
                $readd = $my_ylou->where($where)->save($add);
                if($readd){
                    logger('访问地址回写成功!');
                }else{
                    logger('访问地址回写失败!');
                }
                $this->ajaxReturn($data);
            }else{
                logger('增加失败！'."\n");
                $data = array(
                    'status' => 0,
                    'content' => '增加失败！'
                );
                $this->ajaxReturn($data);
            }
        }
    }
    //删除我的影楼H5页面
    public function del_mylou(){
        logger('删除我的影楼H5页面');
        $post = I();
        $id = $post['id'];
        $my_ylou = D('my_ylou');
        $where = array(
            'id' => $id
        );
        $result = $my_ylou->where($where)->delete();
        if($result){
            logger('删除成功！'."\n");
            $data = array(
                'status' => 1,
                'content' => '删除成功！'
            );
            $this->ajaxReturn($data);
        }else{
            logger('删除失败！'."\n");
            $data = array(
                'status' => 0,
                'content' => '删除失败！'
            );
            $this->ajaxReturn($data);
    }
    }
    // APP封面图片
    public function my_coverimg_list(){
        $my_coverimg = D('cover_img');
        $where = array(
            'sid' => session('sid')
        );
        $my_coverimg_result = $my_coverimg->where($where)->order('time desc')->select();
        $this->assign('cover',$my_coverimg_result);
        $this->display('coverimg');
    }
    //意见反馈
    public function feedback_show(){
        $this->display('feedback');
    }
    public function feedback_in(){

    }
    //处理从影楼服务器获取的联系人信息 //函数体从Home/GetYLouContacts模块中复制过来
    public function contact_arr2($arr){
        $array = array();
        $i = 0;
        foreach($arr as $k => $v){
            switch($k%5){
                case 0:
                    $str_arr = explode(' / ',substr(rtrim($v,'</i></l'),12));
                    foreach($str_arr as $key => $val){
                        switch($key){
                            case 0:
                                $array[$i]['nickname'] = $val; //将联系人的真实姓名，填写到app_user表中的昵称中
                                $array[$i]['realname'] = $val; //增加真实姓名，因为开单需要填写pc端的姓名信息，而手机端可以随意修改，所以必须把nickname和realname分开保存
                                // logger($val); //debug
                                break;
                            case 1:
                                if($val == '男'){
                                    $array[$i]['gender'] = 1;
                                }else{
                                    $array[$i]['gender'] = 2;
                                }
                                // logger($val); //debug
                                break;
                            case 2:
                                $array[$i]['dept'] = $val;
                                // logger($val); //debug
                                break;
                            default:
                                break;
                        }
                    }
                    break;
                case 1:
                    $i--;
                    $array[$i]['mobile'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
                    // logger($array[$i]['phone']); //debug
                    break;
                case 2:
                    $i--;
                    $array[$i]['qq'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
                    // logger($array[$i]['qq']); //debug
                    break;
                case 3:
                    $i--;
                    $array[$i]['location'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
                    // logger($array[$i]['location']); //debug
                    break;
                case 4:
                    $i--;
                    //添加 店铺标识 SID
                    $array[$i]['sid'] = session('sid');
                    $array[$i]['username'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
                    //添加 初始password
                    $array[$i]['password'] = '888888';
                    //添加时间
                    $array[$i]['createtime'] = time();
                    //添加店铺标识
                    $array[$i]['store_simple_name'] = session('store_simple_name');
                    // logger($array[$i]['username']); //debug
                    break;
                default:
                    break;
            }
            $i++;
        }
        return $array;
    }
    // 注册环信方面用户  //函数体从Home/GetYLouContacts模块中复制过来
    public function create_easemob_user($arr){
        if(is_array($arr)){
            $result = $arr;
        }else{
            logger('再一次查询！'); //debug
            $app_user = D('app_user');
            $where = array(
                'sid' => session('sid')
            );
            $result = $app_user->where($where)->select();
            $sql=$app_user->getLastsql();
            // logger('查询语句：'.$sql);//debug
            // logger('员工数据:'.var_export($result,TRUE)); //debug
        }
        //循环注册 ，即便只有管理员一人也要注册
        foreach($result as $k => $v){
            if($v['username'] != '' && $v['username'] != NULL){
                $user = $v['store_simple_name'].'_'.$v['username'];
                $pwd = $v['password'];
                $create_result = easemob_create_user($user,$pwd);
                if($create_result['error'] != ''){
                    logger('用户：'.$user.'创建失败，失败原因------>'.$create_result['error']);
                }else{
                    logger('用户：'.$user.'创建环信用户成功！'."\n");
                }
            }
        }   
        return TRUE;
    }
     // 注册环信方面用户  //函数体从Home/GetYLouContacts模块中复制过来 //可以单独使用的
    public function private_create_easemob_user(){
        logger('单独为店铺的员工账号集体注册环信,先查询店铺员工账号！'); //debug
        $app_user = D('app_user');
        $where = array(
            'sid' => session('sid')
        );
        $result = $app_user->where($where)->select();
        $sql=$app_user->getLastsql();
        logger('查询语句：'.$sql);//debug
        logger('员工数据:'.var_export($result,TRUE)); //debug
        //循环注册 ，即便只有管理员一人也要注册
        foreach($result as $k => $v){
            if($v['username'] != '' && $v['username'] != NULL){
                $user = $v['store_simple_name'].'_'.$v['username'];
                $pwd = $v['password'];
                $create_result = easemob_create_user($user,$pwd);
                if($create_result['error'] != ''){
                    logger('用户：'.$user.'创建失败，失败原因------>'.$create_result['error']);
                }else{
                    logger('用户：'.$user.'创建环信用户成功！'."\n");
                }
            }
        }   
        return TRUE;
    }
    //显示我的意见
    public function my_opini(){
        logger('管理后台:我的意见');
        $opinion = D('feedback');
        $where = array(
            'sid' => session('sid')
        );
        $result = $opinion->where($where)->select();
        // logger('我的意见:'.var_export($result,TRUE)); //debug
        $this->assign('opi',$result);
        $this->display();
    }
    //////////////////////////////////////////////////////////// 考勤管理 ////////////////////////////////////////////////////////////
    // 显示考勤组
    public function group_list(){
        logger('影楼管理后台：查看考勤组');
        $groups = D('attence_group');
        $where = array(
            'sid' => session('sid')
        );
        $group = $groups->where($where)->select();
        // logger('所有考勤组:'.var_export($group,TRUE)); //debug
        if($group){
           foreach($group as $k => $v){
                //计算人数
                $users = chanslate_json_to_array($v['group_users']);
                $group[$k]['num'] = count($users);
                // 计算 休息和工作时间安排
                if($v['type'] == 1){
                    logger('考勤组类型:固定班制!');
                    $rules = chanslate_json_to_array($v['rules']);
                    // logger('固定班制考勤规则:'.var_export($rules,TRUE));//debug
                    //统计休息 和班次的信息
                    $group[$k]['relax'] = '每周';
                    $group[$k]['work'] = array(
                        '0'=>array(
                            'id' => '',
                            'name' => '',
                            'mark' => '每周'
                        )
                    );
                    $week = array('日','一','二','三','四','五','六');
                    $i = 1;
                    foreach($rules as $key => $value){
                        foreach($week as $w){
                            if(strpos($value['week'],$w)){
                                $the_week_day = $w;
                                break;
                            }
                        }
                        if($value['classid'] == '' || $value['classid'] == 0 || $value['classid'] == NULL){ //classid为空或为0的话 则为休息
                            $group[$k]['relax'] .= $the_week_day.'、';
                        }else{ //工作日  按班次不同区分
                            // logger('第'.$i.'次'); //debug
                            if($i == 1){ //第一次 保存第一个工作日信息
                                $group[$k]['work'][0]['id'] = $value['classid'];
                                $group[$k]['work'][0]['name'] = $value['classname'].': '.$value['start'].'-'.$value['end'];
                                $group[$k]['work'][0]['mark'] .= $the_week_day.'、';
                            }else{ // 之后，判断是否和之前的工作日是一类
                                $long = count($group[$k]['work']);
                                $l = 1;
                                foreach($group[$k]['work'] as $x => $y){
                                    if($y['id'] == $value['classid']){
                                        $group[$k]['work'][$x]['mark'] .= $the_week_day.'、';
                                        break;
                                    }else{ //不是一个班次 重新建立新记录
                                        if($l == $long){
                                            $group[$k]['work'][$long]['id'] = $value['classid'];
                                            $group[$k]['work'][$long]['name'] = $value['classname'].': '.$value['start'].'-'.$value['end'];
                                            $group[$k]['work'][$long]['mark'] = '每周'.$the_week_day.'、'; 
                                        }
                                    }
                                    $l++;
                                }
                            }
                            $i++;
                        }
                    }
                }else{ // 排班制 晒出用了哪些班次
                    logger('考勤组类型:排班制!');
                    $rules = chanslate_json_to_array($v['rules']);
                    $i = 1;
                    foreach($rules as $m => $n){
                        foreach($n['detail'] as $u => $o){
                            if($i == 1){
                                // logger('第一次'); //debug
                                $group[$k]['class'] = $o['classname'].' '.$o['start'].':'.$o['end'].'、';  
                                $i++;
                                break;
                            }else{
                                // logger('第一次以后,班次记录:'.$group[$k]['class']); //debug
                                // logger('班次名称:'.$o['classname']); //debug
                                if(strpos($group[$k]['class'],substr($o['classname'],-1))){
                                    // logger('继续->'); //debug
                                    continue;
                                }else{
                                    // logger('追加'); //debug
                                    $group[$k]['class'] .= $o['classname'].' '.$o['start'].':'.$o['end'].'、';
                                    break;
                                }
                            }
                        }
                    }
                }
            } 
            logger('存在考勤组!'."\n");
        }else{
            logger('店铺尚没有设置考勤组!'."\n");
            $group = array();
        }
        // logger('考勤组信息统计结果:'.var_export($group,TRUE));
        $this->assign('group',$group);
        $this->display();
    }
    //删除考勤组
    public function del_group(){
        logger('影楼管理后台：删除考勤组');
        $post = I();
        $id = $post['id'];
        if(empty($id)){
            logger('参数不全,删除失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '参数不全,删除失败!'
            );
            $this->ajaxReturn($data);
        }
        $groups = D('attence_group');
        $where = array(
            'id' => $id
        );
        $result = $groups->where($where)->delete();
        if($result){
            logger('删除考勤组成功!'."\n");
            $data = array(
                'status' => 1,
                // 'content' => $imgurl
                'content' => '删除成功!'
            );
        }else{
            logger('删除考勤组失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '删除失败!'
            );
        }
        $this->ajaxReturn($data);
    }
    // 更新考勤组-排班
    public function update_rules(){
        $post = I();
        $id = $post['id'];
        $rules = $post['rules'];
        $type = $post['type'];
        // logger('携带参数:'.var_export($post,TRUE));//debug
        if($id && $rules && $type){
            logger('影楼管理后台:更新考勤组排班规则');
            $groups = D('attence_group');
            if($type == '1'){
                logger('更新固定班制规则!');
                $rules = trim($rules,',');
                $rule = explode(',',$rules); 
                // logger('shuzu:'.var_export($rule,TRUE));//debug
                $week = array('一','二','三','四','五','六','日');
                $newrules = array();
                $i = 0;
                $m = 0;
                foreach($rule as $r){
                    switch($i%4){
                        case 0:
                            if($i != 0){$m++;}
                            $newrules[$m]['classid'] = $r;
                            $newrules[$m]['week'] = '星期'.$week[$m];
                            break;
                        case 1:
                            $newrules[$m]['classname'] = $r;
                            break;
                        case 2:
                            $newrules[$m]['start'] = $r;
                            break;
                        case 3:
                            $newrules[$m]['end'] = $r;
                            break;
                        default:
                            break;
                    }
                    $i++;
                }
                // logger('RULES:'.var_export($newrules,TRUE)); //debug
                $json_rules = '[';
                foreach($newrules as $k => $v){
                    $json_rules .= '{&quot;classid&quot;:&quot;'.$v['classid'].'&quot;,&quot;classname&quot;:&quot;'.$v['classname'].'&quot;,&quot;start&quot;:&quot;'.$v['start'].'&quot;,&quot;end&quot;:&quot;'.$v['end'].'&quot;,&quot;week&quot;:&quot;'.$v['week'].'&quot;},';
                }
                $json_rules = rtrim($json_rules,',');
                $json_rules .= ']';
                // logger('JSON:'.$json_rules);//debug
                $where = array(
                    'id' => $id
                );
                $update_data = array(
                    'rules' => $json_rules,
                    'modify_admin' => session('uid'),
                    'modify_time' => time()
                );
                $result = $groups->where($where)->save($update_data);
                if($result){
                    logger('更新考勤组成功!'."\n");
                    $data = array(
                        'status' => 1,
                        'content' => '更新成功!'
                    );
                }else{
                    logger('更新考勤组失败!'."\n");
                    $data = array(
                        'status' => 0,
                        'content' => '更新失败!'
                    );
                }
            }else{
                 logger('更新排班制规则!');
                 $newrules = array();
                 $rules = rtrim($rules,'{');
                 $per_rules = explode('{',$rules);
                 $i = 0;
                 $n = 0;
                 foreach($per_rules as $k){
                    switch($i%2){
                        case 0:
                            $rule = explode(',',$k);
                            $m = 0;
                            foreach($rule as $r){
                                switch($m){
                                    case 0:
                                        $newrules[$n]['username'] = $r;
                                        break;
                                    case 1:
                                        $newrules[$n]['realname'] = $r;
                                        break;
                                    case 2:
                                        $newrules[$n]['nickname'] = $r;
                                        break;
                                    case 3:
                                        $newrules[$n]['uid'] = $r;
                                        break;
                                    default:
                                        break;
                                }
                                $m++;
                            }
                            break;
                        case 1:
                            $rule = rtrim($k,',');
                            $rule = explode(',',$rule);
                            // logger('规则:'.var_export($rule,TRUE)); //debug
                            $m = 0;
                            $z = 0;
                            foreach($rule as $r){
                                switch($m%5){
                                    case 0:
                                        $newrules[$n]['detail'][$z]['classid'] = $r;
                                        break;
                                    case 1:
                                        $newrules[$n]['detail'][$z]['classname'] = $r;
                                        break;
                                    case 2:
                                        $newrules[$n]['detail'][$z]['start'] = $r;
                                        break;
                                    case 3:
                                        $newrules[$n]['detail'][$z]['end'] = $r;
                                        break;
                                    case 4:
                                        $newrules[$n]['detail'][$z]['date'] = $r;
                                        $z++;
                                        break;
                                    default:
                                        break;
                                }
                                $m++;
                            }
                            $n++;
                            break;
                        default:
                            break;
                    }
                    $i++;
                 }
                 // logger('新排班考勤规则:'.var_export($newrules,TRUE)); //debug
                 $json_rules = '[';
                 foreach($newrules as $k => $v){
                    $json_rules .= '{&quot;username&quot;:&quot;'.$v['username'].'&quot;,&quot;realname&quot;:&quot;'.$v['realname'].'&quot;,&quot;nickname&quot;:&quot;'.$v['nickname'].'&quot;,&quot;uid&quot;:&quot;'.$v['uid'].'&quot;,&quot;detail&quot;:[';
                    foreach($v['detail'] as $x => $y){
                        $json_rules .= '{&quot;classid&quot;:&quot;'.$y['classid'].'&quot;,&quot;classname&quot;:&quot;'.$y['classname'].'&quot;,&quot;start&quot;:&quot;'.$y['start'].'&quot;,&quot;end&quot;:&quot;'.$y['end'].'&quot;,&quot;date&quot;:&quot;'.$y['date'].'&quot;},';
                    }
                    $json_rules = rtrim($json_rules,',');
                    $json_rules .= ']},';
                 }
                 $json_rules = rtrim($json_rules,',');
                 $json_rules .= ']';
                 // logger('JSON形式新排班规则:'.$json_rules); //debug
                 $where = array(
                    'id' => $id
                );
                $update_data = array(
                    'rules' => $json_rules,
                    'modify_admin' => session('uid'),
                    'modify_time' => time()
                );
                $result = $groups->where($where)->save($update_data);
                if($result){
                    logger('更新考勤组成功!'."\n");
                    $data = array(
                        'status' => 1,
                        'content' => '更新成功!'
                    );
                }else{
                    logger('更新考勤组失败!'."\n");
                    $data = array(
                        'status' => 0,
                        'content' => '更新失败!'
                    );
                }
            }      
            $this->ajaxReturn($data);
        }elseif($id && !$rules){
            logger('影楼管理后台：显示考勤组排班规则');
            $groups = D('attence_group');
            $where = array(
                'id' => $id
            );
            $the_group = $groups->where($where)->field('sid,type,rules')->find();
            //查询班次
            $class_where = array(
                'sid' => $the_group['sid']
            );
            $classes = D('attence_class');
            $the_classes = $classes->where($class_where)->order('classid asc')->select();
            //添加班次背景颜色信息
            $color = array(
                '#2db7f5',
                '#f50',
                '#87d068',
                '#fa0',
                '#b856d7',
                '#338da5'
            );
            $i = 0;
            foreach($the_classes as $k => $v){
                $m = 0;
                foreach($color as $c){
                    if($i%6 == $m){
                        $the_classes[$k]['color'] = $c;
                        break;
                    }
                    $m++;
                }
                $i++;
            }
            $this->assign('classes',$the_classes);
            // logger('所有的班次:'.var_export($the_classes,TRUE)); //debug
            if($the_group['type'] == 1){
                logger('考勤班制:固定->');
                $rules = chanslate_json_to_array($the_group['rules']);
                //调整顺序
                $week = array('一','二','三','四','五','六','日');
                foreach($rules as $k => $v){
                    $i = 0;
                    foreach($week as $w){
                        if(strpos($v['week'],$w)){
                            $rule[$i] = $v;
                            break;
                         }
                         $i++;
                    }
                }
                //添加颜色
                foreach($rule as $k => $v){
                    foreach($the_classes as $x => $y){
                        if($v['classname'] == $y['classname']){
                            $rule[$k]['color'] = $y['color'];
                        }else{ //如果休息,颜色为灰
                            if($v['classid'] == '' || $v['classid'] == NULL || $v['classid'] == 0){
                                $rule[$k]['color'] = '#575757';
                                $rule[$k]['classname'] = '休息';
                            }
                        }
                    }
                }
                // logger('顺序的排班:'.var_export($rule,TRUE)); //debug
                // $this->assign('color','rgb(45, 183, 245)');
                $this->assign('type','1');
                $this->assign('rules',$rule);
                $this->assign('id',$id);
                $this->assign('num',0);
                $this->display('guding');
            }else{
                logger('考勤班制:排班->');
                $rules = chanslate_json_to_array($the_group['rules']);
                // logger('考勤总规则:'.var_export($rules,TRUE)); //debug
                //梳理今日之后32天的日期数组
                $date = array();
                $weeks = array('日','一','二','三','四','五','六');
                for($i=0;$i<32;$i++){
                    $weekday = $weeks[date('w',time()+$i*86400)];
                    $dateday = date('d',time()+$i*86400);
                    $datedate = date('Y-m-d',time()+$i*86400);
                    $date[$i] = array(
                        'datedate' => $dateday,
                        'week' => $weekday,
                        'color' => '#686868',
                        'date' => $datedate, //附加上考勤规则信息 下面梳理各员工考勤时省点事
                        'classid' => 'xxx',
                        'classname' => '未排班',
                        'start' => '',
                        'end' => '',
                    );
                    if($date[$i]['week'] == '六' || $date[$i]['week'] == '日'){
                        $date[$i]['color'] = '#ff0000';
                    }
                }
                //梳理人员的考勤规则
                $newrules = array();
                foreach($rules as $k => $v){
                    $newrules[$k]['uid'] = $v['uid'];
                    $newrules[$k]['realname'] = $v['realname'];
                    $newrules[$k]['nickname'] = $v['nickname'];
                    $newrules[$k]['username'] = $v['username'];
                    $newrules[$k]['detail'] = $date;
                    // logger('个人考勤:'.var_export($v['detail'],TRUE)); //debug
                    // logger('新个人考勤规则:'.var_export($)); //debug
                    foreach($newrules[$k]['detail'] as $key => $value){
                        foreach($v['detail'] as $x => $y){
                            // logger('原date/新date:'.$y['detail'].' / '.$value['date']); //debug
                            if($y['date'] == $value['date']){
                                $newrules[$k]['detail'][$key]['classid'] = $y['classid'];
                                $newrules[$k]['detail'][$key]['classname'] = $y['classname'];
                                $newrules[$k]['detail'][$key]['start'] = $y['start'];
                                $newrules[$k]['detail'][$key]['end'] = $y['end'];
                                break;
                            }
                        }
                    }
                }
                // logger('新考勤规则:'.var_export($newrules,TRUE)); //debug
                //上色
                foreach($newrules as $k => $v){
                    foreach($v['detail'] as $m => $n){
                        foreach($the_classes as $x => $y){
                            if($n['classname'] == $y['classname']){
                                $newrules[$k]['detail'][$m]['color'] = $y['color'];
                                break;
                            }else{ //如果休息,颜色为灰
                                // logger('ClassID:'.$n['classid']); //debug
                                switch($n['classid']){
                                    case 'xxx':
                                        $newrules[$k]['detail'][$m]['color'] = '#b8b8b8';
                                        break;
                                    case '':
                                    case NULL:
                                    case 0:
                                        $newrules[$k]['detail'][$m]['color'] = '#575757';
                                        $newrules[$k]['detail'][$m]['classname'] = '休息';
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    }
                }
                // logger('排班数组:'.var_export($newrules,TRUE)); //debug
                $this->assign('date',$date);
                $this->assign('rules',$newrules);
                $this->assign('type','2');
                $this->assign('id',$id);
                $this->display('paiban');
            }
        }else{
            logger('未知参数错误'."\n");
            $this->error('未知参数错误!');
        }
    }
    // 显示班次
    public function class_list(){
        logger('影楼管理后台：查看考勤班次');
        $sid = session('sid');
        $the_class = D('attence_class');
        $where = array(
            'sid' => $sid
        );
        $result = $the_class->where($where)->order('classid asc')->select();
        if($result){
            logger('已设置考勤班次！'."\n");
        }else{
            logger('未设置考勤班次！'."\n");
        }
        $this->assign('classes',$result);
        $this->display();
    }
    // 修改班次
    public function update_class(){
        logger('影楼管理后台：更新考勤班次');
        $post = I();
        $id = $post['id'];
        $start = $post['start'];
        $end = $post['end'];
        if(empty($id) || empty($start) || empty($end)){
            logger('参数不全,删除失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '参数不全,删除失败!'
            );
            $this->ajaxReturn($data);
        }
        $classes = D('attence_class');
        $where = array(
            'classid' => $id
        );
        $save = array(
            'start' => $start,
            'end' => $end
        );
        $result = $classes->where($where)->save($save);
        if($result){
            logger('更新考勤班次成功!'."\n");
            $data = array(
                'status' => 1,
                // 'content' => $imgurl
                'content' => '更新成功!'
            );
        }else{
            logger('更新考勤班次失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '更新失败!'
            );
        }
        $this->ajaxReturn($data);
    }
    // 删除班次
    public function del_class(){
        logger('影楼管理后台：删除考勤班次');
        $post = I();
        $id = $post['id'];
        if(empty($id)){
            logger('参数不全,删除失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '参数不全,删除失败!'
            );
            $this->ajaxReturn($data);
        }
        $classes = D('attence_class');
        $where = array(
            'classid' => $id
        );
        $result = $classes->where($where)->delete();
        if($result){
            logger('删除考勤班次成功!'."\n");
            $data = array(
                'status' => 1,
                // 'content' => $imgurl
                'content' => '删除成功!'
            );
        }else{
            logger('删除考勤班次失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '删除失败!'
            );
        }
        $this->ajaxReturn($data);
    }
    // 导出考勤报表
    public function export_attence_csv(){
        logger('导出考勤报表');
        $post = I();
        logger('携带参数:'.var_export($post,TRUE));//debug
        $from_date = $post['from'];
        $to_date = $post['to'];
        $type = $post['type'];
        $from = strtotime($from_date);
        $to = strtotime($to_date)+86400;
        // logger('开始日期转时间戳:'.$from); //debug
        $app_user = D('app_user');
        $condition = array(
            'sid' => session('sid'),
            'username' => array(array('neq',''),array('neq',NULL),'OR')
        );
        $users = $app_user->where($condition)->select();
        logger('全体员工:'.var_export($users,TRUE)); //debug
        $records = D('attence_records');
        $map['off_time']  = array(array('egt',$from),array('lt',$to)); //上午的签到状态为旷工
        $map['off_time']  = array(array('egt',$from),array('lt',$to)); //下午的签到状态为旷工
        $map['_logic'] = 'or'; // 或者
        $where['_complex'] = $map;
        $where['sid'] = session('sid');
        $result = $records->where($where)->select();
        $sql =  $records->getLastsql(); //debug;
        logger('查询签到记录语句:'.$sql); //debug
        if($result){
            if($type == 'detail'){
                logger('导出明细表');
                $str = iconv('utf-8','gb2312',"序号,部门,姓名,上午签到时间,上午签到状态,上午打卡地址,上午打卡路由,是否外勤,备注,下午签到时间,下午签到状态,下午打卡地址,下午打卡路由,是否外勤,备注\n"); 
                foreach($result as $k => $v){
                    if(($v['off_status'] == $v['off_status']) AND ($v['off_status'] == 4 || $v['off_status'] == 6 || $v['off_status'] == 9 || $v['off_status'] == 7 || $v['off_status'] == 8 || $v['off_status'] == 10)){
                        logger('用户并未真实签到,该记录由系统自动补齐,不导入报表中!');
                    }else{
                        $result[$k]['realname'] = $v['uid'];
                        $result[$k]['dept'] = '';
                        foreach($users as $u => $d){
                            if($v['uid'] == $d['uid']){
                                $result[$k]['realname'] = $d['realname'];
                                $result[$k]['dept'] = $d['dept'];
                                break;
                            }
                        }
                        $id = $v['id'];
                        $dept = iconv('utf-8','gb2312',$result[$k]['dept']);
                        $name = iconv('utf-8','gb2312',$result[$k]['realname']);
                        $on_time =  date('Y-m-d H:i:s',$result[$k]['off_time']);
                        switch($result[$k]['off_status']){
                            case 1:
                                $on_status = iconv('utf-8','gb2312','正常打卡');
                                break;
                            case 2:
                                $on_status = iconv('utf-8','gb2312','迟到');
                                break;
                            case 3:
                                $on_status = iconv('utf-8','gb2312','严重迟到');
                                break;
                            case 4:
                                $on_status = iconv('utf-8','gb2312','旷工');
                                break;
                            case 5:
                                $on_status = iconv('utf-8','gb2312','早退');
                                break;
                            case 6:
                                $on_status = iconv('utf-8','gb2312','缺卡');
                                break;
                            case 7:
                                $on_status = iconv('utf-8','gb2312','请假');
                                break;
                            case 8:
                                $on_status = iconv('utf-8','gb2312','外出');
                                break;
                            case 9:
                                $on_status = iconv('utf-8','gb2312','休息');
                                break;
                            case 10:
                                $on_status = iconv('utf-8','gb2312','未排班');
                                break;
                            default:
                                break;
                        }
                        $on_location = chanslate_json_to_array($result[$k]['off_location']);
                        $on_location = iconv('utf-8','gb2312',$on_location['address']);
                        $on_router = iconv('utf-8','gb2312',$result[$k]['off_router']);
                        if($result[$k]['off_outside'] == 1){
                            $on_outside = iconv('utf-8','gb2312','是');
                        }else{
                            $on_outside = iconv('utf-8','gb2312','否');
                        }
                        $on_mark = iconv('utf-8','gb2312',$result[$k]['off_mark']);
                        $off_time =  date('Y-m-d H:i:s',$result[$k]['off_time']);
                        switch($result[$k]['off_status']){
                            case 1:
                                $off_status = iconv('utf-8','gb2312','正常打卡');
                                break;
                            case 2:
                                $off_status = iconv('utf-8','gb2312','迟到');
                                break;
                            case 3:
                                $off_status = iconv('utf-8','gb2312','严重迟到');
                                break;
                            case 4:
                                $off_status = iconv('utf-8','gb2312','旷工');
                                break;
                            case 5:
                                $off_status = iconv('utf-8','gb2312','早退');
                                break;
                            case 6:
                                $off_status = iconv('utf-8','gb2312','缺卡');
                                break;
                            case 7:
                                $off_status = iconv('utf-8','gb2312','请假');
                                break;
                            case 8:
                                $off_status = iconv('utf-8','gb2312','外出');
                                break;
                            case 9:
                                $off_status = iconv('utf-8','gb2312','休息');
                                break;
                            case 10:
                                $off_status = iconv('utf-8','gb2312','未排班');
                                break;
                            default:
                                break;
                        }
                        $off_location = chanslate_json_to_array($result[$k]['off_location']);
                        $off_location = iconv('utf-8','gb2312',$off_location['address']);
                        $off_router = iconv('utf-8','gb2312',$result[$k]['off_router']);
                        if($result[$k]['off_outside'] == 1){
                            $off_outside = iconv('utf-8','gb2312','是');
                        }else{
                            $off_outside = iconv('utf-8','gb2312','否');
                        }
                        $off_mark = iconv('utf-8','gb2312',$result[$k]['off_mark']);
                        $str .= $id.','.$dept.','.$name.','.$on_time.','.$on_status.','.$on_location.','.$on_router.','.$on_outside.','.$on_mark.','.$off_time.','.$off_status.','.$off_location.','.$off_router.','.$off_outside.','.$off_mark."\n";
                    }
                }
                $filename = '考勤明细表-'.date('Ymdhis').'.csv'; //设置文件名
                logger("导出成功\n");
                $export = $this->export_csv($filename,$str); //导出 
            }else{
                logger('导出汇总表');
                $from_day = (int)strchr(ltrim(strchr($from_date,'/'),'/'),'/',TRUE);
                $to_day = (int)strchr(ltrim(strchr($to_date,'/'),'/'),'/',TRUE);
                $title = '序号,UID,部门,姓名,';
                // logger('起始日'.$from_day.' 结束日'.$to_day); //debug
                if($from_day <= $to_day){
                    // logger('本月的报表'); //debug
                    $date = array();
                    $month = (int)strchr($to_date,'/',TRUE);
                    $n = 0;
                    for($i=$from_day;$i<=$to_day;$i++){
                        $title .= $month.'月'.$i.'号'.',';
                        $date[$n]['date'] = date('Y',time()).'-'.$month.'-'.$i;
                        $n++;
                    }
                }else{
                    // logger('跨月的报表'); //debug
                    $now_month = (int)strchr($to_date,'/',TRUE);
                    $pre_month = (int)strchr($from_date,'/',TRUE);
                    $max_day = date('d',strtotime(date('Y',time()).'-'.$now_month)-500);
                    // logger($max_day.'号');//debug
                    $date = array();
                    $n = 0;
                    for($i=$from_day;$i<=$max_day;$i++){
                        // logger('上个月循环'); //debug
                        $title .= $pre_month.'月'.$i.'号'.',';
                        $date[$n]['date'] = date('Y',time()).'-'.$pre_month.'-'.$i;
                        $n++;
                    }
                    for($i=1;$i<=$to_day;$i++){
                        // logger('当月循环'); //debug
                        $title .= $now_month.'月'.$i.'号'.',';
                        $date[$n]['date'] = date('Y',time()).'-'.$now_month.'-'.$i;
                        $n++;
                    }
                }
                $title .= '出勤天数,休息天数,迟到(次),严重迟到(次),早退(次),上班缺卡(次),下班缺卡(次),旷工(天),请假(天),外出,未排班(天)'."\n";
                // logger('CSV标题:'.$title);//debug
                $str = iconv('utf-8','gb2312',$title);
                // logger('日期数组:'.var_export($date,TRUE)); //debug
                $count = array();
                $p = 1;
                foreach($result as $k => $v){
                    if($p == 1){
                        logger('x循环第一次');//debug
                        $count[0]['uid'] = $v['uid'];
                        $count[0]['id'] = $v['id'];
                        $count[0]['detail'] = $date;
                        // logger('detail:'.var_export($count[0]['detail'],TRUE)); //debug
                        foreach($count[0]['detail'] as $x => $y){
                            // logger('日期:'.$y['date'].'记录日期:'.date('Y-n-j',$v['on_time'])); //debug
                            if($y['date'] == date('Y-n-j',$v['on_time']) || $y['date'] == date('Y-n-j',$v['off_time'])){
                                $count[0]['detail'][$x]['on_status'] = $v['on_status'];
                                $count[0]['detail'][$x]['off_status'] = $v['off_status'];
                                logger('上午状态:'.$v['on_status'].'下午状态:'.$v['off_status']); //debug
                                //归类状态值  //迟到 严重迟到 早退 都在出勤天数上自增0.5天
                                if($v['on_status'] == $v['off_status']){
                                    switch($v['off_status']){
                                        case 1:
                                            $count[0]['normal'] = 1; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 2:
                                            $count[0]['late'] = 2;
                                            $count[0]['normal'] = 1; //天
                                            // $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 3:
                                            $count[0]['toolate'] = 2;
                                            $count[0]['normal'] = 1; //天
                                            $count[0]['late'] = 0;
                                            // $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 4:
                                            $count[0]['absent'] = 1; //天
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            // $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 5:
                                            $count[0]['left'] = 2; 
                                            $count[0]['normal'] = 1; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            // $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 6:
                                            $count[0]['on_lost'] = 1;
                                            $count[0]['off_lost'] = 1;
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            // $count[0]['lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 7:
                                            $count[0]['sply'] = 1; //天
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            // $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 8:
                                            $count[0]['out'] = 1;
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            // $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 9:
                                            $count[0]['relax'] = 1; //天
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            // $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 10:
                                            $count[0]['none'] = 1; //天
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            // $count[0]['none'] = 0; //天
                                            break;
                                        default:
                                            break;
                                    }
                                }else{
                                    switch($v['on_status']){
                                        case 1:
                                            $count[0]['normal'] = 0.5;
                                            // $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 2:
                                            $count[0]['late'] = 1;
                                            $count[0]['normal'] = 0.5; //天
                                            // $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 3:
                                            $count[0]['toolate'] = 1;
                                            $count[0]['normal'] = 0.5; //天
                                            $count[0]['late'] = 0;
                                            // $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 4:
                                            $count[0]['absent'] = 0.5;
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            // $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 5:
                                            $count[0]['left'] = 1;
                                            $count[0]['normal'] = 0.5; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            // $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 6:
                                            $count[0]['on_lost'] = 1;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            // $count[0]['lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 7:
                                            $count[0]['sply'] = 1;
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            // $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 8:
                                            $count[0]['out'] = 1;
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            // $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 9:
                                            $count[0]['relax'] = 1;
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            // $count[0]['relax'] = 0; //天
                                            $count[0]['none'] = 0; //天
                                            break;
                                        case 10:
                                            $count[0]['none'] = 1;
                                            $count[0]['normal'] = 0; //天
                                            $count[0]['late'] = 0;
                                            $count[0]['toolate'] = 0;
                                            $count[0]['absent'] = 0;  //天
                                            $count[0]['left'] = 0;
                                            $count[0]['on_lost'] = 0;
                                            $count[0]['off_lost'] = 0;
                                            $count[0]['sply'] = 0; //天
                                            $count[0]['out'] = 0;
                                            $count[0]['relax'] = 0; //天
                                            // $count[0]['none'] = 0; //天
                                            break;
                                        default:
                                            break;
                                    }
                                    switch($v['off_status']){
                                        case 1:
                                            $count[0]['normal'] += 0.5;
                                            break;
                                        case 2:
                                            $count[0]['late'] += 1;
                                            $count[0]['normal'] += 0.5;
                                            break;
                                        case 3:
                                            $count[0]['toolate'] += 1;
                                            $count[0]['normal'] += 0.5;
                                            break;
                                        case 4:
                                            $count[0]['absent'] += 0.5;
                                            break;
                                        case 5:
                                            $count[0]['left'] += 1;
                                            $count[0]['normal'] += 0.5;
                                            break;
                                        case 6:
                                            $count[0]['off_lost'] += 1;
                                            break;
                                        case 7:
                                            $count[0]['sply'] += 1;
                                            break;
                                        case 8:
                                            $count[0]['out'] += 1;
                                            break;
                                        case 9:
                                            $count[0]['relax'] += 1;
                                            break;
                                        case 10:
                                            $count[0]['none'] += 1;
                                            break;
                                        default:
                                            break;
                                    }
                                }
                            }
                        }
                    }else{
                        // logger('不是第一次!'); //debug
                        $i = 1;
                        $size = count($count);
                        foreach($count as $m => $n){
                            // logger('小循环'); ///debug
                            if($v['uid'] == $n['uid']){
                                // logger('已存在该id'); //debug
                                foreach($n['detail'] as $x => $y){
                                    // logger('再循环找到记录是哪天'); //debug
                                    if($y['date'] == date('Y-n-j',$v['on_time']) || $y['date'] == date('Y-n-j',$v['off_time'])){
                                        $count[$m]['detail'][$x]['on_status'] = $v['on_status'];
                                        $count[$m]['detail'][$x]['off_status'] = $v['off_status'];
                                        //归类状态值
                                        if($v['on_status'] == $v['off_status']){
                                            switch($v['off_status']){
                                                case 1:
                                                    $count[$m]['normal'] += 1; //天
                                                    break;
                                                case 2:
                                                    $count[$m]['late'] += 2;
                                                    $count[$m]['normal'] += 1; //天
                                                    break;
                                                case 3:
                                                    $count[$m]['toolate'] += 2;
                                                    $count[$m]['normal'] += 1; //天
                                                    break;
                                                case 4:
                                                    $count[$m]['absent'] += 1; //天
                                                    break;
                                                case 5:
                                                    $count[$m]['left'] += 2; 
                                                    $count[$m]['normal'] += 1; //天
                                                    break;
                                                case 6:
                                                    $count[$m]['on_lost'] += 1;
                                                    $count[$m]['off_lost'] += 1;
                                                    break;
                                                case 7:
                                                    $count[$m]['sply'] += 1; //天
                                                    break;
                                                case 8:
                                                    $count[$m]['out'] += 1;
                                                    break;
                                                case 9:
                                                    $count[$m]['relax'] += 1; //天
                                                    break;
                                                case 10:
                                                    $count[$m]['none'] += 1; //天
                                                    break;
                                                default:
                                                    break;
                                            }
                                        }else{
                                            switch($v['on_status']){
                                                case 1:
                                                    $count[$m]['normal'] +=0.5;
                                                    break;
                                                case 2:
                                                    $count[$m]['late'] += 1;
                                                    $count[$m]['normal'] +=0.5;
                                                    break;
                                                case 3:
                                                    $count[$m]['toolate'] += 1;
                                                    $count[$m]['normal'] +=0.5;
                                                    break;
                                                case 4:
                                                    $count[$m]['absent'] += 0.5;
                                                    break;
                                                case 5:
                                                    $count[$m]['left'] += 1;
                                                    $count[$m]['normal'] +=0.5;
                                                    break;
                                                case 6:
                                                    $count[$m]['on_lost'] += 1;
                                                    break;
                                                case 7:
                                                    $count[$m]['sply'] += 1;
                                                    break;
                                                case 8:
                                                    $count[$m]['out'] += 1;
                                                    break;
                                                case 9:
                                                    $count[$m]['relax'] += 1;
                                                    break;
                                                case 10:
                                                    $count[$m]['none'] += 1;
                                                    break;
                                                default:
                                                    break;
                                            }
                                            switch($v['off_status']){
                                                case 1:
                                                    $count[$m]['normal'] += 0.5;
                                                    break;
                                                case 2:
                                                    $count[$m]['late'] += 1;
                                                    $count[$m]['normal'] +=0.5;
                                                    break;
                                                case 3:
                                                    $count[$m]['toolate'] += 1;
                                                    $count[$m]['normal'] +=0.5;
                                                    break;
                                                case 4:
                                                    $count[$m]['absent'] += 0.5;
                                                    break;
                                                case 5:
                                                    $count[$m]['left'] += 1;
                                                    $count[$m]['normal'] +=0.5;
                                                    break;
                                                case 6:
                                                    $count[$m]['off_lost'] += 1;
                                                    break;
                                                case 7:
                                                    $count[$m]['sply'] += 1;
                                                    break;
                                                case 8:
                                                    $count[$m]['out'] += 1;
                                                    break;
                                                case 9:
                                                    $count[$m]['relax'] += 1;
                                                    break;
                                                case 10:
                                                    $count[$m]['none'] += 1;
                                                    break;
                                                default:
                                                    break;
                                            }
                                        }
                                    }
                                }
                                break;
                            }else{
                                if($i == $size){
                                    logger('新建新成员!');//debug
                                    $count[$size]['id'] = $v['id'];
                                    $count[$size]['uid'] = $v['uid'];
                                    $count[$size]['detail'] = $date;
                                    foreach($count[$size]['detail'] as $x => $y){
                                        // logger('新成员小循环,,'.$y['date'].'--'.date('Y-n-j',$v['on_time'])); //debug
                                        if($y['date'] == date('Y-n-j',$v['on_time']) || $y['date'] == date('Y-n-j',$v['off_time'])){
                                            $count[$size]['detail'][$x]['on_status'] = $v['on_status'];
                                            $count[$size]['detail'][$x]['off_status'] = $v['off_status'];
                                            //归类状态值
                                            if($v['on_status'] == $v['off_status']){
                                                switch($v['off_status']){
                                                    case 1:
                                                        $count[$size]['normal'] = 1; //天
                                                        // $count[0]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 2:
                                                        $count[$size]['late'] = 2;
                                                        $count[$size]['normal'] = 1; //天
                                                        // $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 3:
                                                        $count[$size]['toolate'] = 2;
                                                        $count[$size]['normal'] = 1; //天
                                                        $count[$size]['late'] = 0;
                                                        // $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 4:
                                                        $count[$size]['absent'] = 1; //天
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        // $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 5:
                                                        $count[$size]['left'] = 2; 
                                                        $count[$size]['normal'] = 1; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        // $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 6:
                                                        $count[$size]['on_lost'] = 1;
                                                        $count[$size]['off_lost'] = 1;
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        // $count[$size]['lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 7:
                                                        $count[$size]['sply'] = 1; //天
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        // $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 8:
                                                        $count[$size]['out'] = 1;
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        // $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 9:
                                                        $count[$size]['relax'] = 1; //天
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        // $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 10:
                                                        $count[$size]['none'] = 1; //天
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        // $count[$size]['none'] = 0; //天
                                                        break;
                                                    default:
                                                        break;
                                                }
                                            }else{
                                                switch($v['on_status']){
                                                    case 1:
                                                        $count[$size]['normal'] = 0.5;
                                                        // $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 2:
                                                        $count[$size]['late'] = 1;
                                                        $count[$size]['normal'] = 0.5; //天
                                                        // $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 3:
                                                        $count[$size]['toolate'] = 1;
                                                        $count[$size]['normal'] = 0.5; //天
                                                        $count[$size]['late'] = 0;
                                                        // $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 4:
                                                        $count[$size]['absent'] = 0.5;
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        // $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 5:
                                                        $count[$size]['left'] = 1;
                                                        $count[$size]['normal'] = 0.5; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        // $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 6:
                                                        $count[$size]['on_lost'] = 1;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        // $count[$size]['lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 7:
                                                        $count[$size]['sply'] = 1;
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['lost'] = 0;
                                                        // $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 8:
                                                        $count[$size]['out'] = 1;
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        // $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 9:
                                                        $count[$size]['relax'] = 1;
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        // $count[$size]['relax'] = 0; //天
                                                        $count[$size]['none'] = 0; //天
                                                        break;
                                                    case 10:
                                                        $count[$size]['none'] = 1;
                                                        $count[$size]['normal'] = 0; //天
                                                        $count[$size]['late'] = 0;
                                                        $count[$size]['toolate'] = 0;
                                                        $count[$size]['absent'] = 0;  //天
                                                        $count[$size]['left'] = 0;
                                                        $count[$size]['on_lost'] = 0;
                                                        $count[$size]['off_lost'] = 0;
                                                        $count[$size]['sply'] = 0; //天
                                                        $count[$size]['out'] = 0;
                                                        $count[$size]['relax'] = 0; //天
                                                        // $count[$size]['none'] = 0; //天
                                                        break;
                                                    default:
                                                        break;
                                                }
                                                switch($v['off_status']){
                                                    case 1:
                                                        $count[$size]['normal'] += 0.5;
                                                        break;
                                                    case 2:
                                                        $count[$size]['late'] += 1;
                                                        $count[$size]['normal'] += 0.5;
                                                        break;
                                                    case 3:
                                                        $count[$size]['toolate'] += 1;
                                                        $count[$size]['normal'] += 0.5;
                                                        break;
                                                    case 4:
                                                        $count[$size]['absent'] += 0.5;
                                                        break;
                                                    case 5:
                                                        $count[$size]['left'] += 1;
                                                        $count[$size]['normal'] += 0.5;
                                                        break;
                                                    case 6:
                                                        $count[$size]['off_lost'] += 1;
                                                        break;
                                                    case 7:
                                                        $count[$size]['sply'] += 1;
                                                        break;
                                                    case 8:
                                                        $count[$size]['out'] += 1;
                                                        break;
                                                    case 9:
                                                        $count[$size]['relax'] += 1;
                                                        break;
                                                    case 10:
                                                        $count[$size]['none'] += 1;
                                                        break;
                                                    default:
                                                        break;
                                                }
                                            }
                                        }
                                    }
                                    break;
                                }
                                $i++;
                            }
                        }
                    }
                    $p++;
                }
            }
            logger('统计数组:'.var_export($count,TRUE)); //debug
            foreach($count as $k => $v){
                foreach($users as $x => $y){
                    if($v['uid'] == $y['uid']){
                        $count[$k]['realname'] = $y['realname'];
                        $count[$k]['dept'] = $y['dept'];
                    }
                }
            }
            foreach($count as $k => $v){
                $str .= $v['id'].','.$v['uid'].','.iconv('utf-8','gb2312',$v['dept']).','.iconv('utf-8','gb2312',$v['realname']).',';
                foreach($count[$k]['detail'] as $x => $y){
                    if($y['on_status'] == $y['off_status']){
                        switch($y['on_status']){
                            case 1:
                                $str .= iconv('utf-8','gb2312','正常,');
                                break;
                            case 2:
                                $str .= iconv('utf-8','gb2312','迟到,');
                                break;
                            case 3:
                                $str .= iconv('utf-8','gb2312','严重迟到,');
                                break;
                            case 4:
                                $str .= iconv('utf-8','gb2312','旷工,');
                                break;
                            case 5:
                                $str .= iconv('utf-8','gb2312','早退,');
                                break;
                            case 6:
                                $str .= iconv('utf-8','gb2312','缺卡,');
                                break;
                            case 7:
                                $str .= iconv('utf-8','gb2312','请假,');
                                break;
                            case 8:
                                $str .= iconv('utf-8','gb2312','外出,');
                                break;
                            case 9:
                                $str .= iconv('utf-8','gb2312','休息,');
                                break;
                            case 10:
                                $str .= iconv('utf-8','gb2312','未排班,');
                                break;
                        }
                    }else{
                        switch($y['on_status']){
                            case 1:
                                $str .= iconv('utf-8','gb2312','上午:正常');
                                break;
                            case 2:
                                $str .= iconv('utf-8','gb2312','上午:迟到');
                                break;
                            case 3:
                                $str .= iconv('utf-8','gb2312','上午:严重迟到');
                                break;
                            case 4:
                                $str .= iconv('utf-8','gb2312','上午:旷工');
                                break;
                            case 5:
                                $str .= iconv('utf-8','gb2312','上午:早退');
                                break;
                            case 6:
                                $str .= iconv('utf-8','gb2312','上午:缺卡');
                                break;
                            case 7:
                                $str .= iconv('utf-8','gb2312','上午:请假');
                                break;
                            case 8:
                                $str .= iconv('utf-8','gb2312','上午:外出');
                                break;
                            case 9:
                                $str .= iconv('utf-8','gb2312','上午:休息');
                                break;
                            case 10:
                                $str .= iconv('utf-8','gb2312','上午:未排班');
                                break;
                        }
                        switch($y['off_status']){
                            case 1:
                                $str .= iconv('utf-8','gb2312','下午:正常,');
                                break;
                            case 2:
                                $str .= iconv('utf-8','gb2312','下午:迟到,');
                                break;
                            case 3:
                                $str .= iconv('utf-8','gb2312','下午:严重迟到,');
                                break;
                            case 4:
                                $str .= iconv('utf-8','gb2312','下午:旷工,');
                                break;
                            case 5:
                                $str .= iconv('utf-8','gb2312','下午:早退,');
                                break;
                            case 6:
                                $str .= iconv('utf-8','gb2312','下午:缺卡,');
                                break;
                            case 7:
                                $str .= iconv('utf-8','gb2312','下午:请假,');
                                break;
                            case 8:
                                $str .= iconv('utf-8','gb2312','下午:外出,');
                                break;
                            case 9:
                                $str .= iconv('utf-8','gb2312','下午:休息,');
                                break;
                            case 10:
                                $str .= iconv('utf-8','gb2312','下午:未排班,');
                                break;
                        }
                    }
                }
                // / $count[0]['normal'] = 0; //天
                //     $count[0]['late'] = 0;
                //     $count[0]['toolate'] = 0;
                //     $count[0]['absent'] = 0;  //天
                //     $count[0]['left'] = 0;
                //     $count[0]['lost'] = 0;
                //     $count[0]['sply'] = 0; //天
                //     $count[0]['out'] = 0;
                //     $count[0]['relax'] = 0; //天
                //     $count[0]['none'] = 0; //天
            // $title .= '出勤天数,休息天数,迟到(次),严重迟到(次),早退(次),上班缺卡(次),下班缺卡(次),旷工(天),请假(天),未排班(天)'."\n"
                 $str .= $count[$k]['normal'].','.$count[$k]['relax'].','.$count[$k]['late'].','.$count[$k]['toolate'].','.$count[$k]['left'].','.$count[$k]['on_lost'].','.$count[$k]['off_lost'].','.$count[$k]['absent'].','.$count[$k]['sply'].','.$count[$k]['out'].','.$count[$k]['none']."\n";
            }
            $filename = '考勤汇总表-'.date('Ymdhis').'.csv'; //设置文件名
            logger("导出成功\n");
            $export = $this->export_csv($filename,$str); //导出 
        }else{
            logger('该时间段内无签到记录!');
            $this->error('该时间段内无签到记录!');
        }
    }
    //导出操作写入函数
    public function export_csv($filename,$data) { 
        header("Content-type:text/csv"); 
        header("Content-Disposition:attachment;filename=".$filename); 
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0'); 
        header('Expires:0'); 
        header('Pragma:public'); 
        echo $data;
        exit;//结束输入，否则会把HTML源码也存入了
    }   
    //收款二维码
    public function pay_code(){
        logger('影楼管理后台:查看收款二维码');
        $pay_codes = D('pay_code');
        $where = array(
            'sid' => session('sid'),
            'type' => 1
        );
        $weixin = $pay_codes->where($where)->order('time desc')->select();
        $where['type'] = 2;
        $ali = $pay_codes->where($where)->order('time desc')->select();
        $this->assign('wcode',$weixin);
        $this->assign('acode',$ali);
        $this->display();
    }
    //上传图片
    public function uploadimg(){
        header("Content-Type:text/html;charset:utf-8");
        $post = I();
        // logger('文件系统:'.var_export($_FILES,TRUE)); //debug
        logger('上传文件开始--->');
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg','gif','png','jpeg');
        // $upload->rootPath = './Uploads/';
        $upload->savePath = '';
        $info = $upload->upload();
        // logger('info:'.var_export($info,TRUE)); //debug
        if(!$info){
            $data = array(
                'status' => 0,
                'content' => $upload->getError()
            );
            $this->ajaxReturn($data);
        }else{
            foreach ($info as $v) {
               $savepath=$v['savepath'];
               $savename=$v['savename'];
            }  
            //生成缩略图
            // $img= new \Think\Image();
            // $img->open('./Uploads/'.$savepath.$savename);
            // $img->thumb(200,200)->save('./Uploads/'.$savepath.'S'.$savename);
            $imgurl = '/Uploads/'.$savepath.$savename;
            // @unlink('./Uploads/'.$savepath.$savename);
            logger("APP封面图片上传处理成功");
            //去更新数据库中对应店铺的cover图片库
            $cover_imgs = D('cover_img');
            $img_info = array(
                'sid' => session('sid'),
                'url' => 'http://'.$_SERVER['HTTP_HOST'].$imgurl,
                'show_order' => 1,
                'is_open' => 1,
                'long_time' => 1,
                'time' => time()
            );
            $result = $cover_imgs->add($img_info);
            if($result){
                logger('添加封面图片记录成功!'."\n");
                $data = array(
                    'status' => 1,
                    // 'content' => $imgurl
                    'content' => '上传成功!'
                );
            }else{
                logger('添加封面图片记录失败!'."\n");
                $data = array(
                    'status' => 0,
                    // 'content' => $imgurl
                    'content' => '上传失败!'
                );
            }
            $this->ajaxReturn($data);
        }
    }
    //删除封面图片
    public function remove_cover(){
        logger('删除封面图片!');
        $post = I();
        $id = $post['id'];
        if(empty($id)){
            logger('参数不全,删除失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '参数不全,删除失败!'
            );
            $this->ajaxReturn($data);
        }
        $cover_imgs = D('cover_img');
        $where = array(
            'id' => $id
        );
        $result = $cover_imgs->where($where)->delete();
        if($result){
            logger('删除封面图片成功!'."\n");
            $data = array(
                'status' => 1,
                // 'content' => $imgurl
                'content' => '删除成功!'
            );
        }else{
            logger('删除封面图片失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '删除失败!'
            );
        }
        $this->ajaxReturn($data);
    }
    //上传支付宝收款二维码
    public function uploadacode(){
        header("Content-Type:text/html;charset:utf-8");
        $post = I();
        // logger('文件系统:'.var_export($_FILES,TRUE)); //debug
        logger('上传文件开始--->');
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg','gif','png','jpeg');
        // $upload->rootPath = './Uploads/';
        $upload->savePath = '';
        $info = $upload->upload();
        // logger('info:'.var_export($info,TRUE)); //debug
        if(!$info){
            $data = array(
                'status' => 0,
                'content' => $upload->getError()
            );
            $this->ajaxReturn($data);
        }else{
            foreach ($info as $v) {
               $savepath=$v['savepath'];
               $savename=$v['savename'];
            }  
            //生成缩略图
            // $img= new \Think\Image();
            // $img->open('./Uploads/'.$savepath.$savename);
            // $img->thumb(200,200)->save('./Uploads/'.$savepath.'S'.$savename);
            $imgurl = '/Uploads/'.$savepath.$savename;
            // @unlink('./Uploads/'.$savepath.$savename);
            logger("支付宝收款二维码上传处理成功");
            //去更新数据库中对应店铺的cover图片库
            $pay_codes = D('pay_code');
            $code_info = array(
                'sid' => session('sid'),
                'url' => 'http://'.$_SERVER['HTTP_HOST'].$imgurl,
                'type' => 2,
                'time' => time()
            );
            $result = $pay_codes->add($code_info);
            if($result){
                logger('添加支付宝收款二维码记录成功!'."\n");
                $data = array(
                    'status' => 1,
                    // 'content' => $imgurl
                    'content' => '上传成功!'
                );
            }else{
                logger('添加支付宝收款二维码记录失败!'."\n");
                $data = array(
                    'status' => 0,
                    // 'content' => $imgurl
                    'content' => '上传失败!'
                );
            }
            $this->ajaxReturn($data);
        }
    }
    //上传微信收款二维码
    public function uploadwcode(){
        header("Content-Type:text/html;charset:utf-8");
        $post = I();
        // logger('文件系统:'.var_export($_FILES,TRUE)); //debug
        logger('上传文件开始--->');
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg','gif','png','jpeg');
        // $upload->rootPath = './Uploads/';
        $upload->savePath = '';
        $info = $upload->upload();
        // logger('info:'.var_export($info,TRUE)); //debug
        if(!$info){
            $data = array(
                'status' => 0,
                'content' => $upload->getError()
            );
            $this->ajaxReturn($data);
        }else{
            foreach ($info as $v) {
               $savepath=$v['savepath'];
               $savename=$v['savename'];
            }  
            //生成缩略图
            // $img= new \Think\Image();
            // $img->open('./Uploads/'.$savepath.$savename);
            // $img->thumb(200,200)->save('./Uploads/'.$savepath.'S'.$savename);
            $imgurl = '/Uploads/'.$savepath.$savename;
            // @unlink('./Uploads/'.$savepath.$savename);
            logger("微信收款二维码上传处理成功");
            //去更新数据库中对应店铺的cover图片库
            $pay_codes = D('pay_code');
            $code_info = array(
                'sid' => session('sid'),
                'url' => 'http://'.$_SERVER['HTTP_HOST'].$imgurl,
                'type' => 1,
                'time' => time()
            );
            $result = $pay_codes->add($code_info);
            if($result){
                logger('添加微信收款二维码记录成功!'."\n");
                $data = array(
                    'status' => 1,
                    // 'content' => $imgurl
                    'content' => '上传成功!'
                );
            }else{
                logger('添加微信收款二维码记录失败!'."\n");
                $data = array(
                    'status' => 0,
                    // 'content' => $imgurl
                    'content' => '上传失败!'
                );
            }
            $this->ajaxReturn($data);
        }
    }
    //删除收款二维码图片
    public function remove_code(){
        logger('删除收款二维码图片!');
        $post = I();
        $id = $post['id'];
        if(empty($id)){
            logger('参数不全,删除失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '参数不全,删除失败!'
            );
            $this->ajaxReturn($data);
        }
        $pay_codes = D('pay_code');
        $where = array(
            'id' => $id
        );
        $result = $pay_codes->where($where)->delete();
        if($result){
            logger('删除收款二维码成功!'."\n");
            $data = array(
                'status' => 1,
                // 'content' => $imgurl
                'content' => '删除成功!'
            );
        }else{
            logger('删除收款二维码失败!'."\n");
            $data = array(
                'status' => 0,
                // 'content' => $imgurl
                'content' => '删除失败!'
            );
        }
        $this->ajaxReturn($data);
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
    //权限
    public function update_power(){
        logger('高级管理员:会员权限更改');
        $post = I();
        logger('传入参数:'.var_export($post,TRUE)); //debug
        $app_user = D('app_user');
        $where = array(
            'uid' => $post['uid']
        );
        $data = array(
            'modify_time' => time()
        );
        if($post['kind'] == 'up'){
            logger('高级管理员:提升权限');
            $data['type'] = 1;
        }else{
            logger('高级管理员:降低权限');
            //需要查看该会员是否为考勤组管理员
            $user = $app_user->where($where)->find();
            if($user['attence_admin_group'] == '' || $user['attence_admin_group'] == ' ' || $user['attence_admin_group'] == null){
                $data['type'] = 0;
            }else{
                $data['type'] = 2;
            }
        }
        $result = $app_user->where($where)->save($data);
        if($result){
            logger('高级管理员:更新会员权限成功!'."\n");
            $ajax_data = array(
                'status' => 1,
                'content' => '修改权限成功!'
            );
        }else{
            logger('高级管理员:更新会员权限失败!'."\n");
            $ajax_data = array(
                'status' => 0,
                'content' => '修改权限失败!'
            );
        }
        $this->ajaxReturn($ajax_data);
    }
    //查看客户敏感信息权限
    public function update_vcip(){
        logger('高级管理员:会员查看客户敏感信息权限更改');
        $post = I();
        logger('传入参数:'.var_export($post,TRUE)); //debug
        $app_user = D('app_user');
        $where = array(
            'uid' => $post['uid']
        );
        $data = array(
            'modify_time' => time()
        );
        if($post['vcip'] == 1){
            logger('高级管理员:取消查看权限');
            $data['vcip'] = 0;
        }else{
            logger('高级管理员:授予查看权限');
            $data['vcip'] = 1;
        }
        $result = $app_user->where($where)->save($data);
        if($result){
            logger('高级管理员:更新会员查看客户敏感信息权限成功!'."\n");
            $ajax_data = array(
                'status' => 1,
                'content' => '修改查看客户敏感信息权限成功!'
            );
        }else{
            logger('高级管理员:更新会员查看客户敏感信息权限失败!'."\n");
            $ajax_data = array(
                'status' => 0,
                'content' => '修改查看客户敏感信息权限失败!'
            );
        }
        $this->ajaxReturn($ajax_data);
    }
    //测试影楼服务器是否正常工作 
    public function test(){
        logger('普通管理员:测试影楼服务器是否正常工作');
        $post = I();
        // logger('携带参数:'.var_export($post,TRUE)); //debug
        $dogid = trim($post['dogid']);
        $ip = trim($post['ip']);
        $port = trim($post['port']);
        $type = $post['type'];
        //连接远程服务器 key钥匙
        $admin = array(
            'operation' => $type,
            'dogid' => $dogid
        );
        $xml = transXML($admin);
        $xml = strchr($xml,'<uu>',TRUE);
        //强制转码 由utf8转成gbk
        $xml = mb_convert_encoding($xml,'gbk','utf8');
        // logger('查询xml:'.$xml."--->"); //debug
        $url = 'http://'.$ip.':'.$port.'/';
        $getxml = getXML($url,$xml);
        $result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
        // logger('XML:'.$result);//debug
        if(strlen($result) < 38){
            logger('普通管理员:服务器返回值为空');
            $data = array(
                'status' => 0,
                'content' => '服务器返回值为空!'
            );
        }else{
            switch($type){
                case 9:
                    $reject = strpos($result,'账号');
                    $content = '通讯录';
                    break;
                case 14:
                    $reject = strpos($result,'<p>');
                    $content = '套系列表';
                    break;
                case 15:
                    $reject = strpos($result,'</n><p>');
                    $content = '产品列表';
                    break;
                case 18:
                    $reject = strpos($result,'景');
                    $content = '景点列表';
                    break;
                default:
                    break;
            }
            if($reject){//查看是否包含账号,是则正常
                logger('普通管理员:'.$content.'返回正常');
                $data = array(
                    'status' => 1,
                    'content' => $content.'返回正常!'
                );
            }else{
                logger('普通管理员:'.$content.'返回错误');
                $data = array(
                    'status' => 0,
                    'content' => $content.'返回错误!'
                );
            }
        }
        $this->ajaxReturn($data);
    }
    /* 拆解 同步员工账号函数
     * 总体思路：
     * 1、获取ERP软件员工信息，
     * 2、读取现有用户账号，判断老用户数量<=1，即仅有管理员账号
     * 3、判断：两种情形
     *    3.1 老用户数量<=1，全部导入新用户信息，过滤新用户组，批量注册环信用户，读取全体员工并过滤，全体员工循环添加小秘书好友(no 100%)
     *    3.2 老用户数量 >1，过滤新用户组，
     *        3.2.1 循环新用户组和老用户组找出新用户，为其添加用户记录，注册环信；添加好友关系记录，环信加小秘书好友。
     *        3.2.2 循环老用户组和新用户组找出删除用户，清除用户记录，注销环信；清除其创建的群组及群组成员关系记录(no 100%)；清除其组员关系记录(no 100%)；清除其好友关系记录(no 100%)；
     * 4、循环过滤后的老用户组，找出未注册环信用户，为其注册环信；修复与小秘书的好友关系记录，加小秘书为好友；
     * 注1：过滤是指 username字段去除 空值、NULL、含中文字符
     * 注2：删除用户 不能删除 type = 1的记录，因为其包含最初建立的管理员账号
     * 注3：整个过程较为复杂，有几处不能保证100%完成【新店铺可以保证100%】，后期需要大量测试并完善
    */
    public function sync_users(){
        logger('普通管理员:同步店铺员工用户===》'); 
        $options['dogid'] = session('dogid');
        $options['url'] = session('url');
        $options['sid'] = session('sid');
        $options['store_simple_name'] = session('store_simple_name');
        $new_users = $this->get_erp_users($options); //获取ERP端用户信息
        // logger('获取原始ERP用户组：'.var_export($new_users,TRUE)); //debug
        $users = D('app_user');
        $where = array(
            'sid' => $options['sid']
        );
        $old_users = $users->where($where)->field('type,uid,username,password,store_simple_name,sid,realname,dept,location,qq,gender,birth,mobile')->select(); //原有用户
        // logger('老用户组：'.var_export($old_users,TRUE)); //debug
        $olduser = $this->user_filter($old_users);
        // logger('筛选后的老用户组：'.var_export($olduser,TRUE)); //debug
        if(!empty($new_users)){
            logger('普通管理员: 获取到新员工信息，进一步与原员工信息比对？！');
            if(count($old_users) <= 1){
                logger('普通管理员: 原有用户数少于1人，可能仅存管理员，将新员工全部加入用户表...');
                M()->startTrans();
                $add_result = $users->addAll($new_users); //仅存在管理员的情形下，批量导入用户,这里将用户名为空或包含中文的也导入了用户表。
                if($add_result){
                    logger('普通管理员:新员工组全部加入用户表成功，下一步筛选用户，去除空用户名或包含汉字的用户...');
                    $new_users = $this->user_filter($new_users);
                    logger('筛选后的新ERP用户组：'.var_export($new_users,TRUE)); //debug
                    if(!empty($new_users)){
                        logger('普通管理员:筛选用户完成，下一步批量注册环信');
                        $new_easemob_users = $this->merge_sname_username($new_users);
                        $register_users = easemob_create_users($new_easemob_users); //批量注册环信用户
                        logger('批量注册环信用户返回信息：'.var_export($register_users,TRUE));
                        if($register_users['error'] == ''){ //环信有错误需要个别问题，个别查询
                            M()->commit();
                            logger('普通管理员:批量注册环信成功，'); //因为UID的问题，需要取出全部用户
                            $return_data = array(
                                'status' => 1,
                                'info' => '同步成功！'
                            );
                        }else{
                            M()->rollback();
                            logger('普通管理员:批量注册环信用户失败！');
                            $return_data = array(
                                'status' => 0,
                                'info' => '批量注册失败！'
                            );
                        }
                    }else{
                        logger('普通管理员:筛选用户后，无合格新用户！同步员工主体完成。下一步，全体员工加小秘书为好友... ...');
                        M()->commit();
                        $return_data = array(
                            'status' => 1,
                            'info' => '同步完成！'
                        );
                    }
                    $all_user = $users->where($where)->field('uid,username,store_simple_name')->select();
                    //过滤用户
                    $all_user = $this->user_filter($all_user);
                    logger('筛选后的全部用户组：'.var_export($all_user,TRUE)); //debug
                    $this->add_secretary_friend($all_user); //加好友 不能保证100%都成功
                }else{
                    M()->rollback();
                    logger('普通管理员:批量添加用户入表失败！');
                    $return_data = array(
                        'status' => 0,
                        'info' => '批量导入失败！'
                    );
                }
            }else{
                logger('普通管理员: 原有用户数多于1人，新员工和老成员比对...');
                $new_users = $this->user_filter($new_users);
                // logger('筛选后的新ERP用户组：'.var_export($new_users,TRUE)); //debug
                //循环判断是否有新增用户
                logger('普通管理员:循环判断是否有新增用户');
                $this->loop_for_new($olduser,$new_users);
                //循环判断是否有删除用户
                logger('普通管理员:循环判断是否有删除用户');
                $olduser = $this->loop_for_old($olduser,$new_users);
                $return_data = array(
                    'status' => 1,
                    'info' => '同步成功！'
                );
            }
        }else{
            logger('普通管理员:新员工信息为空！有可能是ERP出现问题，所以暂时不做处理，需要专门人工来解决！');
            $return_data = array(
                'status' => 0,
                'info' => '请联系管理员！'
            );
        }
        // 检查老成员是否有未注册环信的情况 不能保证100%都成功
        logger('普通管理员: 检查是否有老成员未注册环信.....');
        $this->loop_for_unregister_easemob($olduser);
        logger('同步工作完成。'."\n");
        $this->ajaxReturn($return_data);
    }
    //处理从影楼服务器获取的联系人信息 //函数体从Home/GetYLouContacts模块中复制过来
    public function contact_arr3($arr,$sid,$store_simple_name){
        $array = array();
        $i = 0;
        foreach($arr as $k => $v){
            switch($k%5){
                case 0:
                    $str_arr = explode(' / ',substr(rtrim($v,'</i></l'),12));
                    foreach($str_arr as $key => $val){
                        switch($key){
                            case 0:
                                $array[$i]['nickname'] = $val; //将联系人的真实姓名，填写到app_user表中的昵称中
                                $array[$i]['realname'] = $val; //增加真实姓名，因为开单需要填写pc端的姓名信息，而手机端可以随意修改，所以必须把nickname和realname分开保存
                                // logger($val); //debug
                                break;
                            case 1:
                                if($val == '男'){
                                    $array[$i]['gender'] = 1;
                                }else{
                                    $array[$i]['gender'] = 2;
                                }
                                // logger($val); //debug
                                break;
                            case 2:
                                $array[$i]['dept'] = $val;
                                // logger($val); //debug
                                break;
                            default:
                                break;
                        }
                    }
                    break;
                case 1:
                    $i--;
                    $array[$i]['mobile'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
                    // logger($array[$i]['phone']); //debug
                    break;
                case 2:
                    $i--;
                    $array[$i]['qq'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
                    // logger($array[$i]['qq']); //debug
                    break;
                case 3:
                    $i--;
                    $array[$i]['location'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
                    // logger($array[$i]['location']); //debug
                    break;
                case 4:
                    $i--;
                    //添加 店铺标识 SID
                    $array[$i]['sid'] = $sid;
                    $array[$i]['username'] = ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>');
                    //添加 初始password
                    $array[$i]['password'] = '888888';
                    //添加时间
                    $array[$i]['createtime'] = time();
                    //添加店铺标识
                    $array[$i]['store_simple_name'] = $store_simple_name;
                    // logger($array[$i]['username']); //debug
                    break;
                default:
                    break;
            }
            $i++;
        }
        return $array;
    }
    //拆解同步店铺用户函数：my_user， 第1步 获取ERP用户 2016-09-20
    private function get_erp_users($options){ //传入店铺信息数组
        logger('超级管理员: 连接远程ERP服务器获取店铺用户信息... ...');
        //链接远程服务器，获取员工数据
        $admin = array(
            'operation' => 9,
            'dogid' => $options['dogid']
        );
        $xml = transXML($admin);
        $xml = strchr($xml,'<uu>',TRUE);
        $url = $options['url'];
        // logger('超级管理员: 查询xml:'.$url.$xml."--->"); //debug
        //强制转码 由utf8转成gbk
        $xml = mb_convert_encoding($xml,'gbk','utf8');
        $getxml = getXML($url,$xml);
        $result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
        // logger('超级管理员: XML:'.$result);//debug
        if(strlen($result) < 38){
            logger('超级管理员: 店铺ERP软件端无用户信息！'."\n");
            return array();
        }else{
            logger("超级管理员: 获取店铺ERP软件端用户信息成功,最后处理该影楼联系人信息-->");
            $str_xml = substr(rtrim($result,'></recipe>'),32);
            $tra_arr = explode('><l>',$str_xml);
            $tra_arr2 = $this->contact_arr3($tra_arr,$options['sid'],$options['store_simple_name']); 
            logger('超级管理员:处理用户信息成功，返回用户数组！'."\n");
            return $tra_arr2;
        }
    }
    //拆解同步店铺用户函数：my_user， 第2步 店铺全部员工加小秘书为好友
    private function add_secretary_friend($users){
        $friend_relation = D('easemob_friends');
        foreach($users as $k => $v){
            logger('老用户：'.$v['store_simple_name'].'_'.$v['username'].'...');
            M()->startTrans();
            $relations[0] = array(
                'uid' => 2078,
                'aname' => 'aaa_secretary',
                'fid' => $v['uid'],
                'fname' => $v['store_simple_name'].'_'.$v['username'],
                'ctime' => time()
            );
            $relations[1] = array(
                'uid' => $v['uid'],
                'aname' => $v['store_simple_name'].'_'.$v['username'],
                'fid' => 2078,
                'fname' => 'aaa_secretary',
                'ctime' => time()
            );
            try{
                $add_relation = $friend_relation->add($relations[0]);
            }catch(\Exception $e){
                if(substr(strchr($e,':'),2,4) == 1062){
                    logger('已有此第一条记录！');
                }
            }
            try{
                $add_relation = $friend_relation->add($relations[1]);
            }catch(\Exception $e){
                if(substr(strchr($e,':'),2,4) == 1062){
                    logger('已有此第二条记录！');
                }
            }
            $ea_firend_result = easemob_add_friend('aaa_secretary',$v['store_simple_name'].'_'.$v['username']);
            if($ea_firend_result['error'] == ''){
                M()->commit();
                logger('超级管理员: '.$v['uid'].' '.$v['store_simple_name'].'_'.$v['username'].'添加小秘书好友成功！:)');
            }else{
                M()->rollback();
                logger('超级管理员: '.$v['uid'].' '.$v['store_simple_name'].'_'.$v['username'].'添加小秘书好友失败！:(');
            }
        }
        logger('超级管理员:店铺全部员工加小秘书为好友，执行完毕！.........。');
    }
    //拆解同步店铺用户函数：my_user， 第3步 循环判断是否有新增用户
    private function loop_for_new($old,$new){
        $max = count($old); //有效老员工数
        foreach($new as $k => $v){
            $i = 1;
            foreach($old as $x => $y){
                if($v['username'] != $y['username']){
                    if($i == $max){
                        logger('超级管理员:发现店铺新员工！'.$v['store_simple_name'].'_'.$v['username'].'下面为其注册环信，加小秘书');
                        $add_user_result = $this->new_user_in($v);
                        if($add_user_result){
                            logger('超级管理员:添加新用户'.$v['store_simple_name'].'_'.$v['username'].'成功！');
                        }else{
                            logger('超级管理员:添加新用户'.$v['store_simple_name'].'_'.$v['username'].'失败！');
                        }
                    }
                    $i++;
                }else{
                    //用户名相同，涉及是否有信息变动的情况
                    // logger("\n");
                    // logger($v['realname'].'=='.$y['realname']);
                    // logger($v['gender'].'=='.$y['gender']);
                    // logger($v['location'].'=='.$y['location']);
                    // logger($v['qq'].'=='.$y['qq']);
                    // logger($v['dept'].'=='.$y['dept']);
                    // logger($v['mobile'].'=='.$y['mobile']."\n");
                    if(($v['realname'] != $y['realname']) || ($v['qq'] != $y['qq']) || ($v['location'] != $y['location']) || ($v['dept'] != $y['dept']) || ($v['mobile'] != $y['mobile'])){
                        $update_data = array(
                            'realname' => $v['realname'],
                            'gender' => $v['gender'],
                            'dept' => $v['dept'],
                            'mobile' => $v['mobile'],
                            'qq' => $v['qq'],
                            'location' => $v['location'],
                        );
                        $condition = array(
                            'uid' => $y['uid']
                        );
                        $users = D('app_user');
                        $update_result = $users->where($condition)->save($update_data);
                        if($update_result){
                            logger('超级管理员:更新用户'.$v['store_simple_name'].'_'.$v['username'].'信息成功！');
                        }else{
                            logger('超级管理员:更新用户'.$v['store_simple_name'].'_'.$v['username'].'信息失败！');
                        }
                    }
                }
            }
        }
    }
    //拆解同步店铺用户函数：my_user， 第4步 新增用户添加、注册、好友
    private function new_user_in($user){
        logger('新员工加入...');
        M()->startTrans();
        $users = D('app_user');
        $add_result = $users->add($user);
        if($add_result){
            logger('新员工添加记录成功...');
            $register_result = easemob_create_user($user['store_simple_name'].'_'.$user['username'],$user['password']);
            if($register_result['error'] == '' || $register_result['error'] == 'duplicate_unique_property_exists'){
                logger('新员工注册环信账号成功...');
                // $relation = D('easemob_friends');
                // $relations[] = array(
                //     'uid' => 2078,
                //     'aname' => 'aaa_secretary',
                //     'fid' => $add_result,
                //     'fname' => $user['store_simple_name'].'_'.$user['username'],
                //     'ctime' => time()
                // );
                // $relations[] = array(
                //     'uid' => $add_result,
                //     'aname' => $user['store_simple_name'].'_'.$user['username'],
                //     'fid' => 2078,
                //     'fname' => 'aaa_secretary',
                //     'ctime' => time()
                // );
                // $add_relation_result = $relation->addAll($relations);
                // if($add_relation_result){
                //     logger('新员工添加好友关系记录成功...');
                //     $add_friend_result = easemob_add_friend('aaa_secretary',$user['store_simple_name'].'_'.$user['username']);
                //     if($add_friend_result['error'] == ''){
                //         logger('新员工添加小秘书为好友成功!!!');
                        M()->commit();
                        return true;
                //     }else{
                //         logger('新员工添加小秘书为好友失败...');
                //         M()->rollback();
                //         return false;
                //     }
                // }else{
                //     logger('新员工添加好友关系记录成功...');
                //     M()->rollback();
                //     return false;
                // }
            }else{
                logger('新员工注册环信账号失败...'."\n".'环信返回信息:'.var_export($register_result ,true));
                M()->rollback();
                return false;
            }
        }else{
            logger('新员工添加记录失败...');
            M()->rollback();
            return false;
        }
    }
    //拆解同步店铺用户函数：my_user， 第5步 循环判断是否有删除用户
    private function loop_for_old($old,$new){
        $max = count($new); //有效老员工数
        foreach($old as $k => $v){
            $i = 1;
            if($v['type'] != 1){ //不属于店长级别 在考察之类
                foreach($new as $x => $y){
                    if($v['username'] != $y['username']){
                        if($i == $max){
                            logger('超级管理员:发现店铺需删除员工账号！'.$v['store_simple_name'].'_'.$v['username'].'下面删除用户记录、好友关系、群组关系、群组、注销环信用户');
                            $del_user_result = $this->old_user_out($v);
                            if($del_user_result){
                                logger('超级管理员:删除老用户'.$v['store_simple_name'].'_'.$v['username'].'成功！');
                                unset($old[$k]);
                            }else{
                                logger('超级管理员:删除老用户'.$v['store_simple_name'].'_'.$v['username'].'失败！');
                            }
                        }
                        $i++;
                    }
                }
            }
        }
        return $old;
    }
    //拆解同步店铺用户函数：my_user， 第6步 删除用户 删除记录、好友关系记录、群主群组及该群关系记录、群组关系记录、环信用户
    private function old_user_out($user){
        logger('老员工删除...');
        M()->startTrans();
        $users = D('app_user');
        $del_result = $users->where(array('uid' => $user['uid']))->delete();
        if($del_result){
            logger('老员工记录删除成功...');
            $del_emuser_result = delete_easemob_user($user['store_simple_name'].'_'.$user['username']);
            if($del_emuser_result['error'] == ''){
                logger('老员工环信账号注销成功...');
                $del_group_owner = $this->delete_group_owner_relation($user);
                if($del_group_owner){
                    logger('老员工清除群主身份群组关系成功...');
                    $del_group_relation_result = $this->delete_relation_of_group($user);
                    if($del_group_relation_result){
                        logger('老员工清除群组关系成功...');
                        $del_friend_relation_result = $this->delete_relation_of_friend($user);
                        if($del_friend_relation_result){
                            logger('老员工清除好友关系记录成功...');
                            M()->commit();
                            return true;
                        }else{
                            logger('老员工清除好友关系记录失败...');
                            M()->rollback();
                            return false;
                        }
                    }else{
                        logger('老员工清除群组关系失败...');
                        M()->rollback();
                        return false;
                    }
                }else{
                    logger('老员工清除群主身份群组关系失败...');
                    M()->rollback();
                    return false;
                }
            }else{
                logger('老员工环信账号注销失败...');
                M()->rollback();
                return false;
            }
        }else{
            logger('老员工记录删除失败...');
            M()->rollback();
            return false;
        }
    }
    // 循环查找是否有注册环信的老员工-备份
    private function loop_for_unregister_easemob_bk($user){
        foreach($user as $k => $v){
            // logger('获取用户信息:'.$v['store_simple_name'].'_'.$v['username']);
            $get_result = easemob_get_user($v['store_simple_name'].'_'.$v['username']);
            if($get_result['error'] != ''){
                logger('老员工中 '.$v['store_simple_name'].'_'.$v['username'].'未注册环信...');
                $register_result = easemob_create_user($v['store_simple_name'].'_'.$v['username'],$v['password']);
                if($register_result['error'] == ''){
                    $friend_relation = D('easemob_friends');
                    $condition1 = array(
                        'uid' => 2078,
                        'fid' => $v['uid']
                    );
                    $find_relation_result1 = $friend_relation->where($condition1)->find();
                    if($find_relation_result1){
                        logger('已存在一条好友记录...');
                        $condition2 = array(
                            'uid' => $v['uid'],
                            'fid' => 2078
                        );
                        $find_relation_result2 = $friend_relation->where($condition2)->find();
                        if($find_relation_result2){
                            logger('存在第二条好友记录...');
                            $add_friend_result = easemob_add_friend('aaa_secretary',$v['store_simple_name'].'_'.$v['username']);
                            if($add_friend_result['error'] == ''){
                                logger('未注册环信的老员工添加小秘书为好友成功！');
                            }else{
                                logger('未注册环信的老员工添加小秘书为好友失败！');
                                $map['_string'] = '(uid = '.$user['uid'].' AND fid = 2078 ) OR (uid = 2078 AND fid = '.$user['uid'].' )';
                                $del_relation_result = $friend_relation->where($map)->delete();
                                if($del_relation_result){
                                    logger('未注册环信的老员工清除好友关系记录成功！');
                                }else{
                                    logger('未注册环信的老员工清除好友关系记录失败！');
                                }
                            }
                        }else{
                            logger('不存在第二条好友记录...');
                            $update_data2 = array(
                                'uid' => $v['uid'],
                                'aname' => $v['store_simple_name'].'_'.$v['username'],
                                'fid' => 2078,
                                'fname' => 'aaa_secretary',
                                'ctime' => time()
                            );
                            M()->startTrans();
                            $add_relation_result2 = $friend_relation->add($update_data2);
                            if($add_relation_result2){
                                logger('添加第二条好友记录成功...');
                                $add_friend_result = easemob_add_friend('aaa_secretary',$v['store_simple_name'].'_'.$v['username']);
                                if($add_friend_result['error'] == ''){
                                    logger('未注册环信的老员工添加小秘书为好友成功！');
                                    M()->commit();
                                }else{
                                    logger('未注册环信的老员工添加小秘书为好友失败！');
                                    M()->rollback();
                                    $del_relation_result1 = $friend_relation->where($condition1)->delete();
                                    if($del_relation_result1){
                                        logger('删除第一条好友记录成功');
                                    }else{
                                        logger('删除第一条好友记录失败');
                                    }
                                }
                            }else{
                                logger('添加第二条好友记录失败...');
                                M()->rollback();
                                $del_relation_result1 = $friend_relation->where($condition1)->delete();
                                if($del_relation_result1){
                                    logger('删除第一条好友记录成功');
                                }else{
                                    logger('删除第一条好友记录失败');
                                }
                            }
                        }
                    }else{
                        logger('不存在第一条好友记录...');
                        $condition2 = array(
                            'uid' => $v['uid'],
                            'fid' => 2078
                        );
                        $find_relation_result2 = $friend_relation->where($condition2)->find();
                        if($find_relation_result2){
                            logger('存在第二条好友记录...');
                            $update_data1 = array(
                                'uid' => 2078,
                                'aname' => 'aaa_secretary',
                                'fid' => $v['uid'],
                                'fname' => $v['store_simple_name'].'_'.$v['username'],
                                'ctime' => time()
                            );
                            M()->startTrans();
                            $add_relation_result1 = $friend_relation->add($update_data1);
                            if($add_relation_result1){
                                logger('添加第一条好友记录成功...');
                                $add_friend_result = easemob_add_friend('aaa_secretary',$v['store_simple_name'].'_'.$v['username']);
                                if($add_friend_result['error'] == ''){
                                    logger('未注册环信的老员工添加小秘书为好友成功！');
                                    M()->commit();
                                }else{
                                    logger('未注册环信的老员工添加小秘书为好友失败！');
                                    M()->rollback();
                                    $del_relation_result2 = $friend_relation->where($condition2)->delete();
                                    if($del_relation_result2){
                                        logger('删除第二条好友记录成功');
                                    }else{
                                        logger('删除第二条好友记录失败');
                                    }
                                }
                            }else{
                                logger('添加第一条好友记录失败...');
                                M()->rollback();
                                $del_relation_result2 = $friend_relation->where($condition2)->delete();
                                if($del_relation_result2){
                                    logger('删除第二条好友记录成功');
                                }else{
                                    logger('删除第二条好友记录失败');
                                }
                            }
                        }else{
                            logger('不存在第二条好友记录...');
                            $update_data[] = array(
                                'uid' => 2078,
                                'aname' => 'aaa_secretary',
                                'fid' => $v['uid'],
                                'fname' => $v['store_simple_name'].'_'.$v['username'],
                                'ctime' => time()
                            );
                            $update_data[] = array(
                                'uid' => $v['uid'],
                                'aname' => $v['store_simple_name'].'_'.$v['username'],
                                'fid' => 2078,
                                'fname' => 'aaa_secretary',
                                'ctime' => time()
                            );
                            M()->startTrans();
                            $add_relation_result = $friend_relation->addAll($update_data);
                            if($add_relation_result){
                                logger('添加好友关系记录成功..');
                                $add_friend_result = easemob_add_friend('aaa_secretary',$v['store_simple_name'].'_'.$v['username']);
                                if($add_friend_result['error'] == ''){
                                    logger('未注册环信的老员工添加小秘书为好友成功！');
                                    M()->commit();
                                }else{
                                    logger('未注册环信的老员工添加小秘书为好友失败！');
                                    M()->rollback();
                                }
                            }else{
                                logger('添加好友关系记录失败..');
                                M()->rollback();
                            }
                        }
                    }
                }else{
                    logger('创建老员工环信账号失败！');
                }
            }
        }
        logger('检查完毕...');
    }
    // 循环查找是否有注册环信的老员工
    private function loop_for_unregister_easemob($user){
        foreach($user as $k => $v){
            // logger('获取用户信息:'.$v['store_simple_name'].'_'.$v['username']);
            $get_result = easemob_get_user($v['store_simple_name'].'_'.$v['username']);
            if($get_result['error'] != ''){
                logger('老员工中 '.$v['store_simple_name'].'_'.$v['username'].'未注册环信...');
                $register_result = easemob_create_user($v['store_simple_name'].'_'.$v['username'],$v['password']);
                if($register_result['error'] == ''){
                    $friend_relation = D('easemob_friends');
                    M()->startTrans();
                    $relations[0] = array(
                        'uid' => 2078,
                        'aname' => 'aaa_secretary',
                        'fid' => $v['uid'],
                        'fname' => $v['store_simple_name'].'_'.$v['username'],
                        'ctime' => time()
                    );
                    $relations[1] = array(
                        'uid' => $v['uid'],
                        'aname' => $v['store_simple_name'].'_'.$v['username'],
                        'fid' => 2078,
                        'fname' => 'aaa_secretary',
                        'ctime' => time()
                    );
                    try{
                        $add_relation = $friend_relation->add($relations[0]);
                    }catch(\Exception $e){
                        if(substr(strchr($e,':'),2,4) == 1062){
                            logger('已有此第一条记录！');
                        }
                    }
                    try{
                        $add_relation = $friend_relation->add($relations[1]);
                    }catch(\Exception $e){
                        if(substr(strchr($e,':'),2,4) == 1062){
                            logger('已有此第二条记录！');
                        }
                    }
                    $add_friend_result = easemob_add_friend('aaa_secretary',$v['store_simple_name'].'_'.$v['username']);
                    if($add_friend_result['error'] == ''){
                        M()->commit();
                        logger('未注册环信的老员工添加小秘书为好友成功！');
                    }else{
                        M()->rollback;
                        logger('未注册环信的老员工添加小秘书为好友失败！');
                        
                    }
                }else{
                    logger('创建老员工环信账号失败！');
                }
            }
        }
        logger('检查完毕...');
    }
    // 清除以用户为群主的群组以及群组关系 不能保证100%成功
    private function delete_group_owner_relation($user){
        logger('查看用户是否建群...');
        $groups = D('easemob_groups');
        $group_relation = D('easemob_groups_users');
        $mygroup = $groups->where(array('owner' => $user['store_simple_name'].'_'.$user['username']))->field('id,gname')->select();
        if($mygroup){
            foreach($mygroup as $k => $v){
                M()->startTrans();
                $del_group_result = $groups->where(array('id' => $v['id']))->delete();
                if($del_group_result){
                    logger('群：'.$v['gname'].' 清除老员工建立的群记录成功...');
                    $del_relation_result = $group_relation->where(array('gid' => $v['id']))->delete();
                    if($del_relation_result){
                        logger('群：'.$v['gname'].' 清除老员工建立的群组关系记录成功...');
                        M()->commit();
                    }else{
                        logger('群：'.$v['gname'].' 清除老员工建立的群组关系记录失败...');
                        M()->rollback();
                    }
                }else{
                    logger('群：'.$v['gname'].' 清除老员工建立的群记录失败...');
                    M()->rollback();
                }
            }
        }else{
            logger('老员工未建立任何群！！');
        }
        return true;
    }
    // 清除用户的群组关系
    private function delete_relation_of_group($user){
        logger('清除用户的群组关系...先查看是否加入群组');
        $group_relation = D('easemob_groups_users');
        $find_result = $group_relation->where(array('uid' => $user['uid']))->find();
        if($find_result){
            $del_group_relation_result = $group_relation->where(array('uid' => $user['uid']))->delete();
            if($del_group_relation_result){
                logger('老员工有群组关系，清除用户群组关系成功');
            }else{
                logger('老员工有群组关系，清除用户群组关系失败');
            }
        }
        return true;
    }
    // 清除用户的好友关系
    private function delete_relation_of_friend($user){
        logger('清除老员工好友关系记录....先查看是否有好友记录');
        $friend_relation = D('easemob_friends');
        $map['_string'] = 'uid = '.$user['uid'].' OR fid = '.$user['uid'];
        $find_result = $friend_relation->where($map)->find();
        if($find_result){
            $del_friend_relation_result = $friend_relation->where($map)->delete();
            if($del_friend_relation_result){
                logger('老员工有好友关系记录，删除成功！');
            }else{
                logger('老员工有好友关系记录，删除失败！');
            }
        }
        return true;
    }
    // 用户筛选过滤函数 用于筛选从ERP获取来的用户数据，去除空用户名，含汉字的用户名
    private function user_filter($users){
        logger('用户数据过滤器...');
        $new = array();
        foreach($users as $k => $v){
            if($v['username'] != '' && $v['username'] != NULL && !preg_match('/[\x{4e00}-\x{9fa5}]+/u',$v['username'])){ 
                $new[] = $v;
            }
        }
        return $new;
    }
    // 合并用户的店铺简称和用户名
    private function merge_sname_username($user){
        $new = array();
        foreach($user as $k => $v){
            $new[$k]['username'] = $v['store_simple_name'].'_'.$v['username'];
            $new[$k]['password'] = $v['password'];
        }
        return $new;
    }
    //检查加密狗ID是否重复 + 检查 ERP软件版本
    public function new_checkdogid(){
        logger('校验加密狗ID是否重复');
        $post = I();
        $dogid = $post['dogid'];
        logger('传入id:'.$dogid);
        $store = D('store');
        $where = array(
            'dogid' => $dogid
        );
        $check_result = $store->where($where)->find();
        if($check_result){
            logger('ID重复!'."\n");
            $return_data = array(
                'status' => 0,
                'content' => 'ID号重复!!!'
            );
        }else{
            logger('ID未见重复,下一步去检测用户ERP软件版本。');
            //检测用户ERP版本
            $getxml = getVersion($dogid);
            $result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
            $number = ltrim(strchr(strchr($result,'</ver>',TRUE),'<ver>'),'<ver>');
            switch($number){
                case '0':
                    logger('ID号不重复! ERP版本:精灵版！！！'."\n");
                    $return_data = array(
                        'status' => 0,
                        'content' => 'ID号不重复! ERP版本:精灵版！！！'
                    );
                    break;
                case '1':
                    logger('ID号不重复! ERP版本:非精灵版。'."\n");
                    $return_data = array(
                        'status' => 1,
                        'content' => 'ID号不重复! ERP版本:非精灵版。'
                    );
                    break;
                default:
                    logger('ID号不重复! 未知版本，请手动查证！'."\n");
                    $return_data = array(
                        'status' => 0,
                        'content' => 'ID号不重复! 未知版本，请手动查证！'
                    );
                    break;
            }
        }
        $this->ajaxReturn($return_data);
    }
    public function update_status ()
    {
        logger('普通管理员:修改员工账号状态！');
        $post = I();
        if ($post['uid'] && isset($post['status'])){
            if (D('app_user')->where(array('uid' => $post['uid']))->save(array('status' => $post['status']))){
                logger('普通管理员:修改员工账号状态,成功！'."\n");
                $data = array(
                    'status' => 1,
                    'content' => '成功！'
                );
            }else{
                logger('普通管理员:修改员工账号状态,失败！'."\n");
                $data = array(
                    'status' => 0,
                    'content' => '失败！'
                );
            }
        } else {
            logger('普通管理员:修改员工账号状态,参数不全！'."\n");
            $data = array(
                'status' => 2,
                'content' => '参数不全！'
            );
        }
        $this->ajaxReturn($data);
    }
}
?>
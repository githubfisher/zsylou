<?php
namespace Admin\Controller;
use Think\Controller;

class ManageController extends Controller{
	public function _initialize(){
		Vendor('Easemob.Easemob');
	}
	public function index(){
		$this->display('templet_show');
	}
	public function top(){
		$this->assign('user',session('name'));
        $this->display();
	}
	public function ylou(){
		$yllist = D('store');
		$page = $post['page'];
		if(empty($page))
			$page = 1;
		Vendor("Page.page");
		$limit = 10;
		$join = 'LEFT JOIN market_area ON store.market_area = market_area.id';
		$field = 'store.*,market_area.name,market_area.id as aid';
		$count = $yllist->join($join)->field($field)->cache(true,120)->count();
        $page = new \Page($count,$limit);
        $ylinfo = $yllist->join($join)->field($field)->order('id desc')->limit($page->firstRow.','.$page->listRows)->cache(true,120)->select();
        $show = $page->show();
        $this->assign('page',$show);
		$market_area = D('market_area');
		$areas = $market_area->field('id,name')->select();
		$this->assign('title','影楼资料--掌上影楼管理后台');
		$this->assign('ylou',$ylinfo);
		$this->assign('area',$areas);
		$expire_date = array(
			0 => array(
				'time' => -1,
				'name' => '未设置服务期'
			),
			1 => array(
				'time' => 31536000,
				'name' => '1年'
			),
			2 => array(
				'time' => 15768000,
				'name' => '6个月'
			),
			3 => array(
				'time' => 7884000,
				'name' => '3个月'
			),
			4 => array(
				'time' => 2628000,
				'name' => '1个月'
			),
			5 => array(
				'time' => 604800,
				'name' => '7天体验周'
			),
			6 => array(
				'time' => 0,
				'name' => '无限期'
			)
		);
		$this->assign('expire',$expire_date);
		$renew = array(
			0 => array(
				'time' => -1,
				'name' => '未设置服务延期'
			),
			1 => array(
				'time' => 31536000,
				'name' => '1年'
			),
			2 => array(
				'time' => 63072000,
				'name' => '2年'
			),
			3 => array(
				'time' => 94608000,
				'name' => '3年'
			),
			4 => array(
				'time' => 2628000,
				'name' => '1个月'
			),
			5 => array(
				'time' => 5256000,
				'name' => '2个月'
			),
			6 => array(
				'time' => 7884000,
				'name' => '3个月'
			),
			7 => array(
				'time' => 15768000,
				'name' => '6个月'
			)
		);
		$this->assign('renew',$renew);
		$this->assign('date',date('Y-m-d',time()));
		$this->display();
	}
	public function add_ylou(){
		logger('超级管理员:添加影楼 ... ');
		$forminfo = I();
		if($forminfo){
			if($forminfo['storename'] && $forminfo['ip'] && $forminfo['dogid'] && $forminfo['port']&& $forminfo['store_simple_name']){
				$ylou = D('store');
				$_POST['createtime'] = time();
				if($_POST['expiring_on'] != 0 && $_POST['expiring_on'] != -1){
					$_POST['expiring_on'] += time();
				}
				$ylou->create();
				$result = $ylou->add();
				if($result){
					$return_data = array(
						'status' => 1,
						'info' => '添加成功！',
						'data'=> $result
					);
					logger('超级管理员:添加影楼成功!');
				}else{
					$return_data = array(
						'status' => 0,
						'info' => '添加失败！'
					);
					logger('超级管理员:添加影楼失败!');
				}
			}else{
				$return_data = array(
					'status' => 2,
					'info' => '提交信息不全！'
				);
				logger('超级管理员:添加影楼失败,提交信息不全!');
			}
		}else{
			$return_data = array(
				'status' => 3,
				'info' => '未提交任何信息！'
			);
			logger('超级管理员:添加影楼失败,未提交任何信息!');
		}
		$this->ajaxReturn($return_data);
	}
	public function update_ylou(){
		logger('修改影楼配置信息');
		$post = I();
		$sid = $post['sid'];
		$store = $post['store'];
		$dogid = $post['dogid'];
		$ip = $post['ip'];
		$port = $post['port'];
		$area = $post['area'];
		$expire = $post['expire'];
		if($sid && ($store || $dogid || $ip || $port || $area || $expire)){
			$update_data = array(
				'storename' => $store,
				'dogid' => $dogid,
				'ip' => $ip,
				'port' => $port,
				'market_area' => $area,
				'expiring_on' => $expire
			);
			$where = array(
				'id' => $sid
			);
			$store = D('store');
			$update_result = $store->where($where)->save($update_data);
			if($update_result){
				logger('影楼信息修改成功'."\n");
				$return_data = array(
					'status' => 1,
					'info' => '修改成功！'
				);
			}else{
				logger('影楼信息修改失败'."\n");
				$return_data = array(
					'status' => 0,
					'info' => '修改失败！'
				);
			}
		}else{
			logger('提交信息不全！'."\n");
			$return_data = array(
				'status' => 2,
				'info' => '提交信息不全！'
			);
		}
		$this->ajaxReturn($return_data);
	}
	public function del_ylou(){
		logger('删除影楼服务器信息');
		$post = I();
		$sid = $post['id'];
		// logger('删除影楼id为:'.$sid); //debug
		$store = D('store');
		$where = array(
			'id' => $sid
		);
		M()->startTrans();
		$del_result = $store->where($where)->delete();
		if($del_result){
			logger('影楼信息删除成功');
			$user = D('app_user');
			$users = $user->where(array('sid'=>$sid))->field('username,store_simple_name')->select();
			$del_result = $user->where(array('sid'=>$sid))->delete();
			if($del_result){
				logger('清除店铺用户成功');
				$limit = 30;
				$num = 0;
				foreach($users as $k => $v){
					if($v['username'] != ''){
						if(($num/$limit)<=1){
							delete_easemob_user($v['store_simple_name'].'_'.$v['username']);
							$num++;
						}else{
							sleep(1);
							$limit = ($num/30+1)*30;
							delete_easemob_user($v['store_simple_name'].'_'.$v['username']);
							$num++;
							continue;
						}
					}
				}
				M()->commit();
				logger('清除店铺用户环信账号成功'."\n");
				$return_data = array(
					'status' => 1,
					'info' => '删除成功！'
				);
			}else{
				logger('清除店铺用户失败'."\n");
				M()->rollback();
				$return_data = array(
					'status' => 3,
					'info' => '删除失败！'
				);
			}
		}else{
			logger('影楼信息删除失败'."\n");
			M()->rollback();
			$return_data = array(
				'status' => 0,
				'info' => '删除失败！'
			);
		}
		$this->ajaxReturn($return_data);
	}
	public function empty_users(){
		logger('超级管理员:清除店铺用户...!');
		$post = I();
		$sid = $post['id'];
		$user = D('app_user');
		$users = $user->where(array('sid'=>$sid))->field('username,store_simple_name')->select();
		if(count($users) == 0){
			$data = array(
				'status' => 0,
				'info' => '用户记录为空！'
			);
			$this->ajaxReturn($data);       
		}
		$del_result = $user->where(array('sid'=>$sid))->delete();
		if($del_result){
			logger('用户记录清除成功!');
			$limit = 30;
			$num = 0;
			foreach($users as $k => $v){
				if($v['username'] != ''){
					if(($num/$limit)<=1){
						delete_easemob_user($v['store_simple_name'].'_'.$v['username']);
						$num++;
					}else{
						sleep(1);
						$limit = ($num/30+1)*30;
						delete_easemob_user($v['store_simple_name'].'_'.$v['username']);
						$num++;
						continue;
					}
				}
			}
			logger('用户环信账号清除成功!'."\n");
			$data = array(
				'status' => 1,
				'info' => '用户清除成功！'
			);
		}else{
			logger('用户记录清除失败!'."\n");
			$data = array(
				'status' => 0,
				'info' => '用户清除失败！'
			);
		}
		$this->ajaxReturn($data);
	}
	public function account_search()
	{
		$market_area = D('market_area');
		$areas = $market_area->field('id,name')->select();
		$this->assign('area',$areas);
		$this->assign('date',date('Y-m-d',time()));
		$expire_date = array(
			0 => array(
				'time' => -1,
				'name' => '未设置服务期'
			),
			1 => array(
				'time' => 31536000,
				'name' => '1年'
			),
			2 => array(
				'time' => 15768000,
				'name' => '6个月'
			),
			3 => array(
				'time' => 7884000,
				'name' => '3个月'
			),
			4 => array(
				'time' => 2628000,
				'name' => '1个月'
			),
			5 => array(
				'time' => 604800,
				'name' => '7天体验周'
			),
			6 => array(
				'time' => 0,
				'name' => '无限期'
			)
		);
		$this->assign('expire',$expire_date);
		$renew = array(
			0 => array(
				'time' => -1,
				'name' => '未设置服务延期'
			),
			1 => array(
				'time' => 31536000,
				'name' => '1年'
			),
			2 => array(
				'time' => 63072000,
				'name' => '2年'
			),
			3 => array(
				'time' => 94608000,
				'name' => '3年'
			),
			4 => array(
				'time' => 2628000,
				'name' => '1个月'
			),
			5 => array(
				'time' => 5256000,
				'name' => '2个月'
			),
			6 => array(
				'time' => 7884000,
				'name' => '3个月'
			),
			7 => array(
				'time' => 15768000,
				'name' => '6个月'
			)
		);
		$this->assign('renew',$renew);
		$this->display();
	}
	public function search_accounts(){
		$post = I();
		$name = $post['name'];
		$simple = $post['simple'];
		$dogid = $post['dogid'];
		if($name || $simple || $dogid){
			$store = D('StoreMarket');
			$field = 'id,storename,dogid,ip,port,store_simple_name,createtime,expiring_on,pad,name,aid';
			$storesByName = array();
			$storesBySimple = array();
			$storesByDogid = array();
			if($name){
				$where = array(
					'storename' => array('like','%'.$name.'%')
				);
				$storesByName = $store->where($where)->field($field)->select();
			}
			if($simple){
				$where = array(
					'store_simple_name' => $simple
				);
				$storesBySimple = $store->where($where)->field($field)->select();
			}
			if($dogid){
				$where = array(
					'dogid' => $dogid
				);
				$storesByDogid = $store->where($where)->field($field)->select();
			}
			$stores = array_merge($storesByName,$storesBySimple,$storesByDogid);
			$data = array(
				'status' => 1,
				'info' => '搜索结果!',
				'data' => $stores
			);
		}else{
			logger('店铺搜索：参数不全！'."\n");
			$data = array(
				'status' => 2,
				'info' => '参数不全！'
			);
		}
		$this->ajaxReturn($data);
	}
	public function export_ylou(){
		$post = I();
		// logger('导出条件：'.var_export($post,true)); //debug
		// die;
		$area = $post['area'];
		$method1 = $post['method1'];
		$method2 = $post['method2'];
		$date1 = $post['date1'];
		$date2 = $post['date2'];
		$date3 = $post['date3'];
		$date4 = $post['date4'];
		$string = '';
		if($area != 'no'){
			$string = 'market_area = '.$area.' AND ';
		}
		if(($method1 != 'no') && ($date1 != '')){
			switch(trim($method1)){
				case 'gt':
					$string .= '(createtime > '.strtotime($date2).') AND ';
					break;
				case 'egt':
					$string .= '(createtime >= '.strtotime($date2).') AND ';
					break;
				case 'eq':
					$string .= '(createtime = '.strtotime($date2).') AND ';
					break;
				case 'lt':
					$string .= '(createtime < '.strtotime($date2).') AND ';
					break;
				case 'elt':
					$string .= '(createtime <= '.strtotime($date2).') AND ';
					break;
				case 'gt,lt':
					$string .= '(createtime > '.strtotime($date1).') AND (createtime < '.strtotime($date2).') AND ';
					break;
				case 'gt,elt':
					$string .= '(createtime > '.strtotime($date1).') AND (createtime <= '.strtotime($date2).') AND ';
					break;
				case 'egt,lt':
					$string .= '(createtime >= '.strtotime($date1).') AND (createtime < '.strtotime($date2).') AND ';
					break;
				case 'egt,elt':
					$string .= '(createtime >= '.strtotime($date1).') AND (createtime <= '.strtotime($date2).') AND ';
					break;
				default:
					break;
			}
		}
		if(($method2 != 'no') && ($date2 != '')){
			switch(trim($method2)){
				case 'gt':
					$string .= '((expiring_on > '.strtotime($date4).') OR (expiring_on = 0)) AND ';
					break;
				case 'egt':
					$string .= '((expiring_on >= '.strtotime($date4).') OR (expiring_on = 0)) AND ';
					break;
				case 'eq':
					$string .= '(expiring_on = '.strtotime($date4).') AND ';
					break;
				case 'lt':
					$string .= '(expiring_on < '.strtotime($date4).') AND ';
					break;
				case 'elt':
					$string .= '(expiring_on <= '.strtotime($date4).') AND ';
					break;
				case 'gt,lt':
					$string .= '(expiring_on > '.strtotime($date3).') AND (expiring_on < '.strtotime($date4).') AND ';
					break;
				case 'gt,elt':
					$string .= '(expiring_on > '.strtotime($date3).') AND (expiring_on <= '.strtotime($date4).') AND ';
					break;
				case 'egt,lt':
					$string .= '(expiring_on >= '.strtotime($date3).') AND (expiring_on < '.strtotime($date4).') AND ';
					break;
				case 'egt,elt':
					$string .= '(expiring_on >= '.strtotime($date3).') AND (expiring_on <= '.strtotime($date4).') AND ';
					break;
				default:
					break;
			}
		}
		$string = trim($string,' AND ');
		$ylou = D('store');
		if($string == ''){
			logger('未输入任何导出限制条件,默认全部导出!');
			$stores = $ylou->select(false);
		}else{
			logger('查询SQL语句：'.$string); //debug
			$map['_string'] = $string;
			$stores = $ylou->where($map)->select(false);
		}
		$area = D('market_area');
		$result = $area->join('RIGHT JOIN ('.$stores.') AS b ON market_area.id = b.market_area')->field('b.*,market_area.name AS area')->select();
		logger('导出目录：'.var_export($result,true)); //debug
		// $result = $ylou->join('LEFT JOIN market_area ON store.market_area = market_area.id')->field('store.*,market_area.name as area')->order('store.id asc')->select();
		$str = iconv('utf-8','gb2312',"ID,影楼名称,店铺简称,加密狗ID,P2P服务器IP,端口,PAD端,开通时间,到期时间,所属区域\n"); 
		foreach($result as $key => $value){ 
			$id = $value['id'];
			$store = iconv('utf-8','gb2312',$value['storename']); //中文转码 
			$sname = $value['store_simple_name'];
			$did = iconv('utf-8','gb2312','__'.$value['dogid']);
			$ip = $value['ip'];
			$port = $value['port'];
			if($value['pad'] == 1){
				$pad = iconv('utf-8','gb2312','是'); //中文转码 
			}else{
				$pad = iconv('utf-8','gb2312','否'); //中文转码 
			}
			$date = date('Y-m-d',$value['createtime']);
			if($value['expiring_on'] == 0){
				$expire_date = iconv('utf-8','gb2312','无限期'); //中文转码 
			}elseif($value['expiring_on'] == -1){
				$expire_date = iconv('utf-8','gb2312','未设置'); //中文转码 
			}else{
				$expire_date = date('Y-m-d',$value['expiring_on']);
			}
			$area = iconv('utf-8','gb2312',$value['area']); //中文转码 
			//$create = $value['createtime'];
			$str .= $id.",".$store.",".$sname.",".$did.",".$ip.",".$port.",".$pad.",".$date.",".$expire_date.",".$area."\n"; //用引文逗号分开 					
		} 
		$filename = 'YL'.date('Y-m-d-h-i-s').'.csv'; //设置文件名 
		$export = $this->export_file($filename,$str); //导出
		if($export){
			logger('影楼信息导出成功！');
			$table = D('export_excel');
			$info = array(
				'url' => '/Export/'.$filename,
				'create_at' => time(),
				'create_by' => session('uid')
			);
			$result = $table->add($info);
			if($result){
				logger('影楼信息导出记录添加成功！'."\n");
				$data = array(
					'status' => 1,
					'content' => C('base_url').'/Export/'.$filename
				);
			}else{
				logger('影楼信息导出记录添加失败！'."\n");
				$data = array(
					'status' => 0,
					'content' => '失败'
				);
			}
		}else{
			$data = array(
				'status' => 0,
				'content' => '导出失败！'
			);
			logger('影楼信息导出失败！'."\n");
		}
		exit(json_encode($data));
	}
	//导出到服务器本地文件
	public function export_file($filename,$data){
		header("Content-type:text/csv"); 
		header("Content-Disposition:attachment;filename=".$filename); 
		header('Cache-Control:must-revalidate,post-check=0,pre-check=0'); 
		header('Expires:0'); 
		header('Pragma:public'); 
		$folder = './Export/';
		if(!file_exists($folder)){
			mkdir($folder,0777,TRUE);
		}
		file_put_contents($folder.$filename,$data,FILE_APPEND);
		return true;
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
	public function get_store_id()
	{
		$post = I();
		$simpleName = $post['simple'];
		$store = D('store');
		$info = $store->where(array('store_simple_name'=>$simpleName))->field('id')->find();
		if($info){
			logger('查询店铺ID成功!'."\n");
			$data = array(
				'status' => 1,
				'info' => $info['id']
			);
		}else{
			logger('查询店铺ID失败!'."\n");
			$data = array(
				'status' => 0,
				'info' => ''
			);
		}
		$this->ajaxReturn($data);
	}
	public function user(){
		$post = I();
		$page = $post['page'];
		if(empty($page))
			$page = 1;
		Vendor("Page.page");
		$user = D('app_user');
		$limit = 15;
		$join = 'LEFT JOIN store ON app_user.sid = store.id';
		$field = 'app_user.uid,app_user.type,app_user.username,app_user.nickname,app_user.createtime,app_user.logintime,app_user.loginip,app_user.store_simple_name,app_user.status as sta,store.storename as store';
		$count = $user->join($join)->field($field)->cache(true,60)->count();
        $page = new \Page($count,$limit);
        $userinfo = $user->join($join)->order('id desc,createtime desc')->field($field)->limit($page->firstRow.','.$page->listRows)->cache(true,60)->select();
        $show = $page->show();
        $this->assign('page',$show);
        $this->assign('title','会员管理--掌上影楼管理后台');
		$this->assign('user',$userinfo);
		$this->display();
	}
	public function add_user(){
		logger('管理员添APP账号');
		$post = I();
		// logger('管理员添APP账号携带参数：'.var_export($post,TRUE)); //debug
		// die;
		if(I()){
			if(I('user') && I('sid') && I('pwd')){
				$user = D('app_user');
				$data = array(
					'username' => $post['user'],
					'password' => $post['pwd'],
					'type' => $post['type'],
					'sid' => $post['sid'],
					'store_simple_name' => $post['sname'],
					'realname' => '管理员',
					'nickname' => '管理员',
					'dept' => '管理层',
					'geneder' => 1,
					'createtime' => time()
				);
				$result = $user->add($data);
				if($result){
					logger('添加成功！'."\n");
					$this->success('添加成功！');
				}else{
					logger('添加失败！'."\n");
					$this->error('添加失败！');
				}
			}else{
				logger('提交的信息不准确'."\n");
				$this->error('提交的信息不准确，请重新提交！');
			}
		}else{
			logger('未提交任何信息！'."\n");
			$this->error('未提交任何信息！');
		}
	}
	public function new_add_user(){  //修改事务处理  添加用户即为其注册环信
		logger('管理员添APP账号');
		$post = I();
		logger('携带参数：'.var_export($post,TRUE)); //debug
		if(I()){
			if(I('user') && I('sid') && I('pwd')){
				$user = D('app_user');
				$data = array(
					'username' => $post['user'],
					'password' => $post['pwd'],
					'type' => $post['type'],
					'sid' => $post['sid'],
					'store_simple_name' => $post['sname'],
					'realname' => '管理员',
					'nickname' => '管理员',
					'dept' => '管理层',
					'geneder' => 1,
					'createtime' => time()
				);
				M()->startTrans(); //事务处理
				$result = $user->add($data);
				if($result){
					logger('添加用户成功！下一步去注册环信！');
					$register_result = easemob_create_user($post['sname'].'_'.$post['user'],$post['pwd']);
					// logger('创建管理员环信账号,环信返回信息:'.var_export($register_result,true));
					if(($register_result['error'] == '') || ($register_result['error'] == 'duplicate_unique_property_exists')){
						logger('创建环信用户成功! 取消添加小秘书为好友...');
						M()->commit(); //事务提交
						$return_data = array(
							'status' => 1,
							'content' => '创建环信用户成功!'
						);
					}else{
						logger('创建环信用户失败！'."\n");
						M()->rollback(); //事务回退
						$return_data = array(
							'status' => 5,
							'content' => '创建环信用户失败!'
						);
					}
				}else{
					logger('添加用户写入失败！'."\n");
					M()->rollback(); //事务回退
					$return_data = array(
						'status' => 4,
						'content' => '添加用户失败!'
					);
				}
			}else{
				logger('提交的信息不准确!'."\n");
				$return_data = array(
					'status' => 3,
					'content' => '提交的信息不准确!'
				);
			}
		}else{
			logger('未提交任何信息！'."\n");
			$return_data = array(
				'status' => 2,
				'content' => '未提交任何信息！'
			);
		}
		$this->ajaxReturn($return_data);
	}
	public function export_user(){
		//判断ID号是否为空
		if(is_array($_POST)){
			$user = D('app_user');	
			$result = $user->join('LEFT JOIN store ON store.id = app_user.sid')->field('app_user.*,store.storename as store')->order('app_user.uid asc')->select();
			$str = iconv('utf-8','gb2312',"ID,用户名,姓名,昵称,所属影楼,部门,职务,地址,QQ,创建时间\n"); 
			foreach($result as $key => $value){ 
				if($value['username'] != '' && $value['username'] != NULL){
					$id = $value['uid'];
					$user = iconv('utf-8','gb2312',$value['username']); //中文转码 
					$name = iconv('utf-8','gb2312',$value['realname']);
					$nickname = iconv('utf-8','gb2312',$value['nickname']);
					$store = iconv('utf-8','gb2312',$value['store']);
					$dept = iconv('utf-8','gb2312',$value['dept']);
					if($value['type'] == 1){
						$type = iconv('utf-8','gb2312','店长'); //中文转码 
					}else{
						$type = iconv('utf-8','gb2312','店员'); //中文转码 
					}
					$local = iconv('utf-8','gb2312',$value['location']);
					$qq = iconv('utf-8','gb2312',$value['qq']);
					$date = date('Y-m-d H:i:s',$value['createtime']);
					//$create = $value['createtime'];
					$str .= $id.",".$user.",".$name.",".$nickname.",".$store.",".$dept.",".$type.",".$local.",".$qq.",".$date."\n"; //用引文逗号分开 					
				}
			} 
			$filename = 'APP用户资料表'.date('Y-m-d-h-i-s').'.csv'; //设置文件名 
			$export = $this->export_csv($filename,$str); //导出 
		}else{
			$this->error('提交失败，未能成功导出！');
		}
	}
	public function store_admin(){
		$user = D('store_admin');
		$where = array();
		$userinfo = $user->where($where)->select();
		$this->assign('title','人员管理--掌上影楼管理后台');
		$this->assign('user',$userinfo);
		$this->display();
	}
	public function add_storeadmin(){
		if(I()){
			if(I('username') && I('password') && I('sid')){
				$user = D('store_admin');
				$_POST['createtime'] = time();
				$user->create();
				$result = $user->add();
				if($result){
					$this->success('添加成功！');
				}else{
					$this->error('添加失败！');
				}
			}else{
				$this->error('提交的信息不准确，请重新提交！');
			}
		}else{
			$this->error('未提交任何信息！');
		}
	}
	public function ylou_admin(){
		//查询影楼的员工通讯录
		// 需要调用肖工提供的通讯录接口
		$ylou_admin = A('Home/GetYLouContacts');
		$result = $ylou_admin->query_update();
		echo '<pre>';
		var_dump($result);
	}
	//检查加密狗ID是否重复
	public function checkdogid(){
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
	//检查影楼店铺简称是否重复 用于登录时区分不同店铺,不能重复
	public function checksimplename(){
		logger('校验影楼简称是否重复');
		$post = I();
		$name = $post['name'];
		logger('传入影楼简称:'.$name);
		$store = D('store');
		$where = array(
			'store_simple_name' => $name
		);
		$check_result = $store->where($where)->find();
		if($check_result){
			logger('影楼简称重复!'."\n");
			$return_data = array(
				'status' => 0,
				'info' => '影楼简称重复!'
			);
			$this->ajaxReturn($return_data);
		}else{
			logger('影楼简称未见重复!'."\n");
			$return_data = array(
				'status' => 1,
				'info' => '影楼简称正常!'
			);
			$this->ajaxReturn($return_data);
		}
	}
	//删除某店铺下所有员工账号，除admin和管理员外. 
	public function del_store_users(){
		logger('删除店铺所有员工账号');
		$post = I();
		logger('携带参数：'.var_export($post,TRUE)); //debug
		$where = array(
			'sid' => $post['sid'],
			// 'type' => $post['type'],
			'username' => array('neq','admin'),
			'dept' => array('neq','管理层')
		);
		$app_user = D('app_user');
		$users = $app_user->where($where)->field('password',TRUE)->select();
		if(empty($users)){
			$data = array(
				'status' => 0,
				'content' => '店铺已无用户!'
			);
		}else{
			$result = $app_user->where($where)->delete();
			if($result){
				logger('清空成功！');
				$data = array(
					'status' => 1,
					'content' => '清空成功!'
				);
			}else{
				logger('清空失败！');
				$data = array(
					'status' => 0,
					'content' => '清空失败!'
				);
			}
			foreach($users as $k => $v){
				$user = $v['store_simple_name'].'_'.$v['username'];
				$result = delete_easemob_user($user);
				if($create_result['error'] != ''){
					logger('用户：'.$v['username'].'删除失败，失败原因------>'.$create_result['error']);
				}else{
					logger($user.'删除环信用户成功！'."\n");
				}
			}
		}
		$this->ajaxReturn($data);
	}
	//将所有员工的账号的权限提升至管理员
	public function to_admin(){
		logger('提升店铺所有员工账号权限到管理员');
		$post = I();
		logger('携带参数：'.var_export($post,TRUE)); //debug
		$where = array(
			'sid' => $post['sid'],
			'type' => 0
		);
		$data = array(
			'type' => 1
		);
		$app_user = D('app_user');
		$result = $app_user->where($where)->save($data);
		if($result){
			logger('权限提升成功！');
		}else{
			logger('权限提升失败！');
		}
	}
	//将所有员工的账号的权限提升至管理员之后，将除admin之外的用户切换回员工
	public function to_workman(){
		logger('将除admin之外的用户切换回员工');
		$post = I();
		logger('携带参数：'.var_export($post,TRUE)); //debug
		$where = array(
			'sid' => $post['sid'],
			'username' => array('neq','admin')
		);
		$data = array(
			'type' => 0
		);
		$app_user = D('app_user');
		$result = $app_user->where($where)->save($data);
		if($result){
			logger('to_workman成功！');
		}else{
			logger('to_workman权限提升失败！');
		}
	}
	//显示封面图片
	public function coverimg(){
		$my_coverimg = D('cover_img');
        $where = array(
            'sid' => 0
        );
        $my_coverimg_result = $my_coverimg->where($where)->order('time desc')->select();
        $this->assign('cover',$my_coverimg_result);
        $this->display('coverimg');
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
            logger("产品封面图片上传处理成功");
            //去更新数据库中对应店铺的cover图片库
            $cover_imgs = D('cover_img');
            $img_info = array(
                'sid' => 0,
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
	//版本控制
	public function version(){
		$post = I();
		logger('掺入参数:'.var_export($post,TRUE)); //debug
		$version = D('version');
		if($post['name'] && $post['code'] && $post['detail'] && $post['url'] && $post['plat']){
			logger('管理后台:新增版本发布!');
			$data = array(
				'versionName' => $post['name'],
				'versionCode' => $post['code'],
				'versionContent' => $post['detail'],
				'down_url' => $post['url'],
				'os' => $post['plat'],
				'time' => time()
			);
			$result = $version->add($data);
			if($result){
				logger('成功发布新版本!');
				$reply = array(
					'status' => 1,
					'content' => '发布成功!'
				);
			}else{
				logger('发布新版本faile!');
				$reply = array(
					'status' => 0,
					'content' => '发布失败!'
				);
			}
			$this->ajaxReturn($reply);
		}else{
			logger('管理后台:全部版本信息');
	        $result = $version->select();
	        // logger('全部版本信息:'.var_export($result,TRUE)); //debug
	        $this->assign('version',$result);
	        $this->display();
		}
	}
	//显示我的意见
    public function all_opinis(){
        logger('管理后台:全部意见');
        $opinion = D('feedback');
        $result = $opinion->select();
        // logger('我的意见:'.var_export($result,TRUE)); //debug
        $this->assign('opi',$result);
        $this->display();
    }
    // 我的影楼介绍，H5页面。列表
    public function my_ylou_list(){
        logger('影楼管理后台：查看影楼H5页面');
        $my_ylou = D('my_ylou');
        $where = array(
            'sid' => 0
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
            logger('修改全局H5页面!');
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
            logger('新建全局H5页面');
            $this->assign('ttip','新建简介');
            $this->display();
        }
    }
    // 我的影楼介绍，H5页面。添加
    public function my_ylou_add(){
        logger('全局我的影楼-->');
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
                'sid' => 0
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
	//测试影楼服务器是否正常工作 
	public function test(){
		logger('超级管理员:测试影楼服务器是否正常工作');
		$post = I();
		// logger('携带参数:'.var_export($post,TRUE)); //debug
		$dogid = $post['dogid'];
		$ip = $post['ip'];
		$port = $post['port'];
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
			logger('超级管理员:服务器返回值为空');
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
				logger('超级管理员:'.$content.'返回正常');
				$data = array(
					'status' => 1,
					'content' => $content.'返回正常!'
				);
			}else{
				logger('超级管理员:'.$content.'返回错误');
				$data = array(
					'status' => 0,
					'content' => $content.'返回错误!'
				);
			}
		}
		$this->ajaxReturn($data);
	}
	//同步员工账号  //弃用
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
        logger('超级管理员:同步影楼员工联系人-->开始');
		$post = I();
		// logger('携带参数:'.var_export($post,TRUE)); //debug
		$dogid = $post['dogid'];
		$ip = $post['ip'];
		$port = $post['port'];
		$sid = $post['sid'];
		$store_simple_name = $post['simple'];
        //链接远程服务器，获取员工数据
        $admin = array(
            'operation' => 9,
            'dogid' => $dogid
        );
        $xml = transXML($admin);
        $xml = strchr($xml,'<uu>',TRUE);
        $url = 'http://'.$ip.':'.$port.'/';
        // logger('查询xml:'.$url.$xml."--->"); //debug
        //强制转码 由utf8转成gbk
        $xml = mb_convert_encoding($xml,'gbk','utf8');
        $getxml = getXML($url,$xml);
        $result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
        // logger('XML:'.$result);//debug 
        if(strlen($result) < 38){
            logger("超级管理员:该影楼无联系人信息");
            logger("超级管理员: &&&>>> 该影楼“无”联系人信息 ----完毕---- <<<&&&\n");
            $data = array(
                'status' => 0,
                'info' => '没有账号信息可同步！'
            );
            $this->ajaxReturn($data);
        }else{
            logger("超级管理员: 处理该影楼联系人信息-->开始");
            $str_xml = substr(rtrim($result,'></recipe>'),32);
            $tra_arr = explode('><l>',$str_xml);
            // logger('xml转数组：'.var_export($tra_arr,TRUE)); //debug
            // $tra_arr2 = $get_ylou_contacts->contact_arr2($tra_arr);     //弃用
            $tra_arr2 = $this->contact_arr2($tra_arr,$sid,$store_simple_name); //函数体从Home/GetYLouContacts模块中复制过来
            // logger('新同步联系人情况：'.var_export($tra_arr2,TRUE));//debug
            logger("超级管理员: 处理该影楼联系人信息-->更新或增加");
            // die;
            $app_user = D('app_user');
            // 第一步先查询是否已存在该店铺的联系人信息，如果存在则更新，如果不存在则直接导
            $where = array(
                'sid' => $sid
            );
            $num_app_user= $app_user->where($where)->count();
            $app_user_info = $app_user->where($where)->select();
            // $sql = $app_user->getLastsql(); //debug
            // logger('查询语句：'.$sql); //debug
            logger('超级管理员: 原有影楼联系人数量：'.$num_app_user); //debug
            if($num_app_user == 1){
                logger('超级管理员: 只存在管理员联系人信息！');
                //若当前只有管理员一人，则添加全部联系人到app_user表中
                $result = $app_user->addAll($tra_arr2);
                // $sql = $app_user->getLastsql(); //debug
                // logger('添加SQL语句:'.$sql); //debug
                if($result){
                    logger('超级管理员: 员工联系人添加成功！');
                    //注册环信    
                    $register_easemob_result = $this->create_easemob_user($app_user_info,$sid);  //函数体从Home/GetYLouContacts模块中复制过来
                    if($register_easemob_result){
                        logger('超级管理员: 注册环信成功！');
                    }else{
                        logger('超级管理员: 注册环信失败！');
                    }
                    $data = array(
                        'status' => 1,
                        'info' => '同步员工成功！'
                    );
                    $this->ajaxReturn($data);
                }else{
                    logger('超级管理员: 员工联系人添加失败！');
                    $data = array(
                        'status' => 0,
                        'info' => '账号同步失败！'
                    );
                    $this->ajaxReturn($data);
                }
            }else{ //循环判断是否有变化，如果有则更新。无变化则丢弃! 废弃该思路，删除原有的员工联系人（除管理员外），将新联系人新增到员工联系人中
                //如果已有员工账户存在，则全部删除，重新导入。
                logger('超级管理员: 已存在员工联系人信息！');
                logger('超级管理员: 循环判断是否有新用户和更新用户的部门，qq等信息。判断依据为用户名和真实信息');
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
                                    'sid' => $sid,
                                    'realname' => $value['realname']
                                );
                                $update_result = $app_user->where($where)->save($userinfo);
                                // $sql = $app_user->getLastsql(); //debug
                                // logger($sql); //debug
                                if($update_result){
                                    logger('超级管理员: 用户：'.$v['realname'].'更新成功！'."\n");
                                }else{
                                    logger('超级管理员: 用户：'.$v['realname'].'更新失败。'."\n");
                                }
                                break;
                            }else{
                                $i++;
                                if($i > count($app_user_info)){
                                    $add_result = $app_user->add($value);
                                    if($add_result){
                                        logger('超级管理员: 添加了新用户！姓名为：'.$value['realname'].'用户名为：'.$value['username']."\n");
                                        //如果用户名不为空，则创建环信账号
                                        if($value['username'] != '' && $value['username'] != NULL){
                                            $create_result = easemob_create_user($value['store_simple_name'].'_'.$value['username'],$value['password']);
                                            if($create_result['error'] != ''){
                                                logger('超级管理员: 用户：'.$value['store_simple_name'].'_'.$value['username'].'创建失败，失败原因------>'.$create_result['error']);
                                            }else{
                                                logger('超级管理员: 用户：'.$value['store_simple_name'].'_'.$value['username'].'创建环信用户成功！'."\n");
                                            }
                                        }
                                    }else{
                                        logger('超级管理员: 添加新用户失败！其姓名为：'.$value['realname'].'用户名为：'.$value['username']."\n");
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
                                    'sid' => $sid
                                );
                                $update_result = $app_user->where($where)->save($userinfo);
                                // $sql = $app_user->getLastsql(); //debug
                                // logger($sql); //debug
                                if($update_result){
                                    logger('超级管理员: 用户：'.$v['realname'].'更新成功！'."\n");
                                }else{
                                    logger('超级管理员: 用户：'.$v['realname'].'更新失败。'."\n");
                                }
                                break;
                            }else{
                                $i++;
                                if($i > count($app_user_info)){
                                    $add_result = $app_user->add($value);
                                    if($add_result){
                                        logger('超级管理员: 添加了新用户！姓名为：'.$value['realname'].'用户名为：'.$value['username']."\n");
                                        //如果用户名不为空，则创建环信账号
                                        if($value['username'] != '' && $value['username'] != NULL){
                                            $create_result = easemob_create_user($value['store_simple_name'].'_'.$value['username'],$value['password']);
                                            if($create_result['error'] != ''){
                                                logger('超级管理员: 用户：'.$value['store_simple_name'].'_'.$value['username'].'创建失败，失败原因------>'.$create_result['error']);
                                            }else{
                                                logger('超级管理员: 用户：'.$value['store_simple_name'].'_'.$value['username'].'创建环信用户成功！'."\n");
                                            }
                                        }
                                    }else{
                                        logger('超级管理员: 添加新用户失败！其姓名为：'.$value['realname'].'用户名为：'.$value['username']."\n");
                                    }
                                    break;
                                }
                                continue;
                            }
                        }
                    }
                }
                logger('超级管理员: 查看是否有删除的员工账户');
                $max = count($tra_arr2);
                // logger('erp端用户数量:'.$max); //debug
                foreach($app_user_info as $k => $v){
                	$n = 1;
                	foreach($tra_arr2 as $x => $y){
                		if($v['username'] != ''){
                			if($v['username'] == $y['username']){
                				break;
                			}else{
                				if(($max == $n ) && ($v['username'] != 'admin') && ($v['type'] != 1)){
                					logger('超级管理员: 店铺需要删除的员工账户__'.$v['username'].'__'.$v['realname']);
                					$where = array();
                					$where['uid'] = $v['uid'];
                					$del_result = $app_user->where($where)->delete();
                					if($del_result){
                						logger('超级管理员:删除员工账户记录成功!');
                					}else{
                						logger('超级管理员:删除员工账户记录失败!');
                					}
                					$easemob_username = $v['store_simple_name'].'_'.$v['username'];
                					$easemob_del_result = delete_easemob_user($easemob_username);
                					if($easemob_del_result['error'] == ''){
                						logger('超级管理员:删除员工__环信__账户记录成功!');
                					}else{
                						logger('超级管理员:删除员工__环信__账户记录失败!');
                					}
                				}
                				$n++;
                			}
                		}else{
                			break;
                		}
                	}
                }
                $data = array(
                    'status' => 1,
                    'info' => '同步员工成功！'
                );
                $this->ajaxReturn($data);
            }
        }
    }
    // 注册环信方面用户  //函数体从Home/GetYLouContacts模块中复制过来
    public function create_easemob_user($arr,$sid){
        if(is_array($arr)){
            $result = $arr;
        }else{
            logger('超级管理员: 再一次查询！'); //debug
            $app_user = D('app_user');
            $where = array(
                'sid' => $sid
            );
            $result = $app_user->where($where)->select();
            // $sql=$app_user->getLastsql(); //debug
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
                    logger('超级管理员: 用户：'.$user.'创建失败，失败原因------>'.$create_result['error']);
                }else{
                    logger('超级管理员: 用户：'.$user.'创建环信用户成功！'."\n");
                }
            }
        }   
        return TRUE;
    }
    //处理从影楼服务器获取的联系人信息 //函数体从Home/GetYLouContacts模块中复制过来
    public function contact_arr2($arr,$sid,$store_simple_name){
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
    //更新会员信息
    public function update_user(){
    	logger('超级管理员:更新会员信息');
    	$post = I();
    	logger('传入参数:'.var_export($post,TRUE)); //debug
    	// die;
    	$app_user = D('app_user');
    	$data = array(
    		'username' => $post['user'],
    		'type' => $post['type'],
    		'sid' => $post['sid'],
    		'store_simple_name' => $post['sname'],
    		'modify_time' => time()
    	);
    	if($post['pwd'] != '******'){
    		$data['password'] = $post['pwd'];
    	}
    	$where = array(
    		'uid' => $post['uid']
    	);
    	//先查询原会员信息
    	$user = $app_user->where($where)->find();
    	$result = $app_user->where($where)->save($data);
    	if($result){
    		logger('超级管理员:更新会员信息成功!');
    		$ajax_data = array(
    			'status' => 1,
    			'content' => '修改成功!'
    		);
    		//判断是否需要修改环信密码或用户名
    		if($user['store_simple_name'].'_'.$user['username'] == $post['sname'].'_'.$post['user']){ //如果用户名没有变 而是密码变了,修改密码
    			if($user['password'] != $post['pwd'] && $post['pwd'] != '******'){
    				//修改环信密码
    				$user_name = $post['sname'].'_'.$post['user'];
		            $modify_result = modify_easemob_pwd($user_name,$post['pwd']);
		            logger('超级管理员:修改环信密码返回值：'.var_export($modify_result,TRUE));
		            //判断修改情况  需要进一步完善环信的返回值识别
		            if($modify_result){
		                logger('超级管理员:app，环信密码都修改成功！'); 
		            }else{
		                logger('超级管理员:app密码修改成功，环信密码修改失败！'); 
		            }
    			}
    		}else{
    			//新建环信用户
    			$user_name = $post['sname'].'_'.$post['user'];
	            $create_result = easemob_create_user($user_name,$post['pwd']);
	            logger('超级管理员:新建环信用户返回值：'.var_export($create_result,TRUE));
	            //判断修改情况  需要进一步完善环信的返回值识别
	            if($create_result){
	                logger('超级管理员:新建环信用户成功！'); 
	            }else{
	                logger('超级管理员:新建环信用户失败！'); 
	            }
		        //删除老用户
		        $user_name = $post['sname'].'_'.$post['user'];
	            $del_result = delete_easemob_user($user_name);
	            logger('超级管理员:删除环信用户返回值：'.var_export($del_result,TRUE));
	            //判断修改情况  需要进一步完善环信的返回值识别
	            if($del_result){
	                logger('超级管理员:删除环信用户成功！'); 
	            }else{
	                logger('超级管理员:删除环信用户失败！'); 
	            }
    		}
    	}else{
    		logger('超级管理员:更新会员信息失败!');
    		$ajax_data = array(
    			'status' => 0,
    			'content' => '修改失败!'
    		);
    	}
    	$this->ajaxReturn($ajax_data);
    }
	//重新注册环信用户 整个店铺
	public function register_easemob_users(){
		logger('超级管理员:重新注册环信用户');
		$post = I();
    	logger('传入参数:'.var_export($post,TRUE)); //debug
    	logger('超级管理员: 再一次查询！'); //debug
        $app_user = D('app_user');
        $where = array(
            'sid' => $post['sid']
        );
        $result = $app_user->where($where)->select();
        // $sql=$app_user->getLastsql(); //debug
        // logger('查询语句：'.$sql);//debug
        // logger('员工数据:'.var_export($result,TRUE)); //debug
        
        //循环注册 ，即便只有管理员一人也要注册
        foreach($result as $k => $v){
            if($v['username'] != '' && $v['username'] != NULL){
                $user = $v['store_simple_name'].'_'.$v['username'];
                $pwd = $v['password'];
                $create_result = easemob_create_user($user,$pwd);
                if($create_result['error'] != ''){
                    logger('超级管理员: 用户：'.$user.'创建失败，失败原因------>'.$create_result['error']);
                }else{
                    logger('超级管理员: 用户：'.$user.'创建环信用户成功！'."\n");
                }
            }
        }   
        logger('超级管理员:重新注册环信用户,执行完毕!');
		$ajax_data = array(
			'status' => 1,
			'content' => '重新注册完成!'
		);
    	$this->ajaxReturn($ajax_data);
	}
	//更新店铺开通PAD端的状态
	public function update_pad_status(){
		logger('超级管理员:更新影楼账号开通PAD端的状态');
		$post = I();
    	logger('传入参数:'.var_export($post,TRUE)); //debug
    	$sid = $post['sid'];
    	$pad = $post['pad'];
    	$where = array(
    		'id' => $sid
    	);
    	$data = array(
    		'modify_time' => time(),
    	);
    	if($pad == 1){
    		$data['pad'] = 0;
    	}else{
    		$data['pad'] = 1;
    	}
    	$store = D('store');
    	$result = $store->where($where)->save($data);
        if($result){
            logger('超级管理员:更新影楼PAD端状态成功!'."\n");
            $ajax_data = array(
                'status' => 1,
                'content' => '更新影楼PAD端状态成功!'
            );
        }else{
            logger('超级管理员:更新影楼PAD端状态失败!'."\n");
            $ajax_data = array(
                'status' => 0,
                'content' => '更新影楼PAD端状态失败!'
            );
        }
        $this->ajaxReturn($ajax_data);
	}
	//显示区域经理列表
	public function list_manager(){
		logger('超级管理员:展示区域经理列表'."\n");
		$manager = D('market_manager');
		$area = D('market_area');
		$areas = $area->field('id,name')->select();
		// logger('数组a:'.var_export($areas,TRUE)); //debug
		// $a = array();
		// foreach($areas as $k => $v){
		// 	$a[$v['id']] = $v['name'];
		// }
		$managers = $manager->field('cuid,muid',TRUE)->select();
		foreach($managers as $k => $v){
			if($v['market_area'] != '' && $v['market_area'] != NULL && $v['market_area'] != ' '){
				$new_a = explode(' ',$v['market_area']);
				// logger('数组'.var_export($new_a,TRUE)); //debug
				foreach($new_a as $key){
					foreach($areas as $x => $y){
						if($y['id'] == $key){
							$managers[$k]['areas'] .= ' '.$y['name'];
							unset($areas[$x]);
						}
					}
				}
			}
		}
		$this->assign('area',$areas);
		$this->assign('manager',$managers);
		$this->display('manager');
	}
	public function add_manager(){
		logger('超级管理员:添加区域经理');
		$forminfo = I();
		// logger('超级管理员:携带参数：'.var_export($forminfo,TRUE));//debug
		if($forminfo){
			if($forminfo['name'] && $forminfo['phone']&& $forminfo['area']){
				$manager = D('market_manager');
				$data = array(
					'name' => $forminfo['name'],
					'phone' => $forminfo['phone'],
					'market_area' => $forminfo['area'],
					'ctime' => time(),
					'cuid' => session('uid')
				);
				M()->startTrans();
				$result = $manager->add($data);
				if($result){
					logger('超级管理员:添加成功！->之后去修改管理区域的记录值');
					$areas = explode(' ',$forminfo['area']);
					$area = D('market_area');
					foreach($areas as $a){
						$where = array(
							'id' => $a
						);
						$update_data = array(
							'manager' => $result,
							'mtime' => time(),
							'muid' => session('uid')
						);
						$update_result = $area->where($where)->save($update_data);
						if($update_result){
							logger('超级管理员:修改管理区域记录成功！');
						}else{
							logger('超级管理员:修改管理区域记录失败！');
						}
					}
					logger('超级管理员:地区管理者ID更新操作完毕！下一步去添加区域经理账号...');
					//创建区域经理账号
					$manager_account = array(
						'username' => $forminfo['phone'],
						'password' => substr($forminfo['phone'],5),
						'realname' => $forminfo['name'],
						'nickname' => $forminfo['name'],
						'mobile' => $forminfo['phone'],
						'location' => $result,
						'type' => 3,
						'createtime' => time(),
						'sid' => 3,
						'store_simple_name' => 'rm',
						'dept' => '销售部'
					); 
					$user = D('app_user');
					$add_account = $user->add($manager_account);
					if($add_account){
						M()->commit();
						logger('添加区域经理账号成功！');
						$data = array(
							'status' => 1,
							'content' => '添加成功！'
						);
					}else{
						M()->rollback();
						logger('添加区域经理账号失败！');
						$data = array(
							'status' => 0,
							'content' => '添加失败！'
						);
					}
				}else{
					$this->error('添加失败！');
					M()->rollback();
					$data = array(
						'status' => 0,
						'content' => '添加失败！'
					);
				}
			}else{
				logger('超级管理员:提交的信息不准确'."\n");
				$this->error('提交的信息不准确，请重新提交！');
				$data = array(
					'status' => 0,
					'content' => '提交的信息不准确，请重新提交！'
				);
			}
		}else{
			logger('超级管理员:未提交任何信息'."\n");
			$this->error('未提交任何信息！');
			$data = array(
				'status' => 0,
				'content' => '超级管理员:未提交任何信息！'
			);
		}
		$this->ajaxReturn($data);
	}
	public function update_manager(){
		logger('超级管理员:修改区域经理信息');
		$post = I();
		logger('超级管理员:携带参数：'.var_export($post,TRUE)); //debug
		$id = $post['id'];
		$name = $post['name'];
		$phone = $post['phone'];
		$oldphone = $post['oldphone'];
		$area = $post['area'];
		if($id && ($name || $phone || $id || $area)){
			$update_data = array(
				'name' => $name,
				'phone' => $phone,
				'market_area' => $area,
				'mtime' => time(),
				'muid' => session('uid')
			);
			$where = array(
				'id' => $id
			);
			$manager = D('market_manager');
			$update_result = $manager->where($where)->save($update_data);
			// $sql = $manager->getLastsql(); //debug
			// logger('更新语句1：'.$sql); //debug
			if($update_result && $area){
				logger('超级管理员:区域经理信息修改成功->之后去修改管理区域');
				$return_data = array(
					'status' => 1,
					'info' => '修改成功！'
				);
				$area = explode(' ',trim($area,' '));
				// logger('拆分数组：'.var_export($area,TRUE)); //debug
				$the_area = D('market_area');
				$areas = $the_area->field('id,manager')->select();
				// logger('超级管理员:区域总数组：'.var_export($areas,TRUE)); //debug
				$max = count($area);
				foreach($areas as $k => $v){
					$i = 1;
					foreach($area as $y){
						if($y == $v['id']){
							if($v['manager'] == '0' || $v['manager'] == '' || $v['manager'] == NULL){
								$update_da = array(
									'manager' => $id,
									'mtime' => time(),
									'muid' => session('uid')
								);
								$update_re = $the_area->where(array('id'=>$v['id']))->save($update_da);
								// $sql = $the_area->getLastsql(); //debug
								// logger('超级管理员:更新语句2：'.$sql); //debug
								if($update_re){
									logger('超级管理员:更新新增管理的地区管理者ID,成功！');
								}else{
									logger('超级管理员:更新新增管理的地区管理者ID,失败！');
								}
							}else if($v['manager'] == $id){
								logger('超级管理员:地区管理者未改变！');
							}else{
								logger('超级管理员:未知区域经理ID混乱错误，请联系管理员！');
							}
						}else{
							if($max == $i && $v['manager'] == $id){
								$update_da = array(
									'manager' => '0',
									'mtime' => time(),
									'muid' => session('uid')
								);
								$update_re = $the_area->where(array('id'=>$v['id']))->save($update_da);
								// $sql = $the_area->getLastsql(); //debug
								// logger('更新语句3：'.$sql); //debug
								if($update_re){
									logger('超级管理员:清除删除管理的地区管理者ID,成功！');
								}else{
									logger('超级管理员:清除删除管理的地区管理者ID,失败！');
								}
							}
							$i++;
						}
					}
				}
				if($phone != $oldphone){
					logger('超级管理员:原账号 '.$oldphone.'现账号 '.$phone.'需要修改区域经理用户账号...');
					$account = array(
						'username' => $phone,
						'password' => substr($phone,5),
						'modify_time' => time(),
					);
					$condition = array(
						'username' => $oldphone,
						'type' => 3
					);
					$user = D('app_user');
					$update_account = $user->where($condition)->save($account);
					if($update_account){
						logger('超级管理员:区域经理账号信息更新成功！');
					}else{
						logger('超级管理员:区域经理账号信息更新失败！');
					}
				}
				logger('超级管理员:地区管理者ID更新操作完毕！'."\n");
			}else{
				logger('超级管理员:区域经理信息修改失败'."\n");
				$return_data = array(
					'status' => 0,
					'info' => '修改失败！'
				);
			}
		}else{
			logger('超级管理员:提交信息不全！'."\n");
			$return_data = array(
				'status' => 2,
				'info' => '提交信息不全！'
			);
		}
		$this->ajaxReturn($return_data);
	}
	public function del_manager(){
		logger('超级管理员:删除区域经理信息');
		$post = I();
		$id = $post['id'];
		$phone = $post['phone'];
		logger('超级管理员:删除区域经理id为:'.$sid);
		$manager = D('market_manager');
		$where = array(
			'id' => $id
		);
		$del_result = $manager->where($where)->delete();
		if($del_result){
			logger('超级管理员:区域经理信息删除成功-> 下一步去清空区域管理者ID');
			$return_data = array(
				'status' => 1,
				'info' => '删除成功！'
			);
			$area = D('market_area');
			$condition = array(
				'manager' => $id
			);
			$update_data = array(
				'manager' => 0,
				'mtime' => time(),
				'muid' => session('uid')
			);
			$update_result = $area->where($condition)->save($update_data);
			if($update_result){
				logger('超级管理员:清空地区管理者ID成功！');
			}else{
				logger('超级管理员:清空地区管理者ID失败！');
			}
			//删除账号
			$condit = array(
				'username' => $phone,
				'type' => 3,
			);
			$user = D('app_user');
			$delete_result = $user->where($condit)->delete();
			if($delete_result){
				logger('超级管理员:清空地区管理者账号'.$phone.'成功！'."\n");
			}else{
				logger('超级管理员:清空地区管理者账号'.$phone.'失败！'."\n");
			}
		}else{
			logger('超级管理员:区域经理信息删除失败'."\n");
			$return_data = array(
				'status' => 0,
				'info' => '删除失败！'
			);
		}
		$this->ajaxReturn($return_data);
	}
	//显示区域列表
	public function list_area(){
		logger('超级管理员:展示区域列表'."\n");
		$area = D('market_area');
		$areas = $area->join('LEFT JOIN market_manager ON market_manager.id = market_area.manager')->field('market_area.id,market_area.name,market_area.manager,market_area.mtime,market_area.ctime,market_manager.name as mname')->select();
		$this->assign('areas',$areas);
		$this->display('area');
	}
	//添加区域
	public function add_area(){
		logger('超级管理员:添加区域'."\n");
		$post = I();
		logger('超级管理员:携带参数：'.var_export($post,TRUE)); //debug
		$name = $post['name'];
		if($name){
			$area = D('market_area');
			$data = array(
				'name' => $name,
				'ctime' => time(),
				'cuid' => session('uid')
			);
			$result = $area->add($data);
			if($result){
				logger('超级管理员:添加区域成功！'."\n");
				$return_data = array(
					'status' => 1,
					'info' => '添加成功！'
				);
			}else{
				logger('超级管理员:添加区域失败！'."\n");
				$return_data = array(
					'status' => 0,
					'info' => '添加失败！'
				);
			}
		}else{
			logger('超级管理员:提交信息不全！'."\n");
			$return_data = array(
				'status' => 0,
				'info' => '提交信息不全！'
			);
		}
		$this->ajaxReturn($return_data);
	}
	// 更新区域
	public function update_area(){
		logger('超级管理员:更新区域信息'."\n");
		$post = I();
		logger('超级管理员:携带参数：'.var_export($post,TRUE)); //debug
		$name = $post['name'];
		$id = $post['id'];
		if($name && $id){
			$area = D('market_area');
			$where = array(
				'id' => $id
			);
			$data = array(
				'name' => $name,
				'mtime' => time(),
				'muid' => session('uid')
			);
			$result = $area->where($where)->save($data);
			if($result){
				logger('超级管理员:更新区域成功！'."\n");
				$return_data = array(
					'status' => 1,
					'info' => '更新成功！'
				);
			}else{
				logger('超级管理员:更新区域失败！'."\n");
				$return_data = array(
					'status' => 0,
					'info' => '更新失败！'
				);
			}
		}else{
			logger('超级管理员:提交信息不全！'."\n");
			$return_data = array(
				'status' => 0,
				'info' => '提交信息不全！'
			);
		}
		$this->ajaxReturn($return_data);
	}
	// 删除区域
	public function del_area(){
		logger('超级管理员:删除区域信息'."\n");
		$post = I();
		logger('超级管理员:携带参数：'.var_export($post,TRUE)); //debug
		$id = $post['id'];
		$manager = $post['manager'];
		if($id && ($manager >= 0)){
			$area = D('market_area');
			$where = array(
				'id' => $id
			);
			$result = $area->where($where)->delete();
			// $result = true;//debug
			if($result){
				logger('超级管理员:删除区域成功！--下一步去修改其管理员的设置！');
				$return_data = array(
					'status' => 1,
					'info' => '删除成功！'
				);
				if($manager > 0){
					$managers = D('market_manager');
					$where = array(
						'id' => $manager
					);
					$the_manager = $managers->where($where)->find();
					$new_area = str_rep($the_manager['market_area'],$id,' ');
					// logger('新管理区域id：'.$new_area); //debug
					if($the_manager){
						$update_data = array(
							'market_area' => $new_area,
							'mtime' => time(),
							'muid' => session('uid')
						);
						$update_result = $managers->where($where)->save($update_data);
						if($update_result){
							logger('超级管理员:删除区域后处理区域管理员所管辖区域id成功！'."\n");
						}else{
							logger('超级管理员:删除区域后处理区域管理员所管辖区域id失败！'."\n");
						}
					}else{
						logger('超级管理员:删除区域后处理区域管理员所管辖区域id失败！'."\n");
					}
				}
			}else{
				logger('超级管理员:删除区域失败！'."\n");
				$return_data = array(
					'status' => 0,
					'info' => '删除失败！'
				);
			}
		}else{
			logger('超级管理员:提交信息不全！'."\n");
			$return_data = array(
				'status' => 0,
				'info' => '提交信息不全！'
			);
		}
		$this->ajaxReturn($return_data);
	}
	//创建小秘书  //测试
	public function create_easemob_secretary(){
		logger('超级管理员:创建环信_影楼小秘书角色用户!');
		$post = I();
		$username = 'aaa_secretary';//$post['user']; //默认 aaa_secretary
		$pwd = '8888';//$post['pwd']; //默认8888
		$result = easemob_create_user($username,$pwd);
		if($result['error'] != ''){
			logger('超级管理员:创建环信_影楼小秘书角色用户失败!');
			$data = array(
				'status' => 0,
				'info' => '创建小秘书用户失败!'
			);
			echo 'field';
		}else{
			logger('超级管理员:创建环信_影楼小秘书角色用户成功!');
			$data = array(
				'status' => 1,
				'info' => '创建小秘书用户成功!'
			);
			echo 'success';
		}
		// $this->ajaxReturn($return_data);
	}
	//获取全部已有用户,并为其添加小秘书为好友 //测试
	public function addFriend(){
		logger('超级管理员:为已有环信用户添加小秘书!');
		$result = easemob_add_friend('aermei_hh','aaa_secretary');
		if($result['error'] != ''){
			echo 'error';
		}else{
			echo 'success';
		}
	}
	//修改昵称 //测试
	public function editNickname(){
		logger('超级管理员:修改环信用户昵称!');
		$result = easemob_edit_nickname('zsylou_secretary','影楼小秘书');
		if($result['error'] != ''){
			echo 'error';
		}else{
			echo 'success';
		}
	}
	public function getuser(){
		logger('超级管理员:获取用户信息!');
		$post = I();
		$user = $post['user'];
		$result = easemob_get_user($user);
		if($result['error'] != ''){
			echo 'error';
		}else{
			logger('用户信息:'.var_export($result,TRUE));
			echo 'success';
		}
	}
	public function deluser(){
		logger('超级管理员:删除用户!');
		$post = I();
		$user = $post['user'];
		$result = delete_easemob_user($user);
		if($result['error'] != ''){
			echo 'error';
		}else{
			logger('用户信息:'.var_export($result,TRUE));
			echo 'success';
		}
	}
	//批量为用户设置环信昵称 一般用于店铺
	public function edit_nickname_for_users($arr,$sid){
		logger('超级管理员:批量设置昵称!');
		if(is_array($arr)){
			$users = $arr;
		}else{
			$app_user = D('app_user');
			$where = array(
				'sid' => $sid
			);
			$users = $app_user->where($where)->select();
		}
		if(empty($users)){
			logger('超级管理员:用户数组为空,无法设置环信昵称!'."\n");
		}else{
			logger('超级管理员:开始为用户设置昵称');
			foreach($users as $k => $v){
				if($v['username'] != '' && $v['username'] != NULL){
					$result = easemob_edit_nickname($v['store_simple_name'].'_'.$v['username'],$v['realname']); //用用户的真实姓名作为店铺聊天昵称
					if($result['error'] == ''){
						logger('超级管理员:为用户'.$v['store_simple_name'].'_'.$v['username'].'设置昵称成功!');
					}else{
						logger('超级管理员:为用户'.$v['store_simple_name'].'_'.$v['username'].'设置昵称失败!');
					}
				}
			}
			logger('超级管理员:批量设置昵称,完毕!'."\n");
		}
	}
	//批量为用户设置好友关系  店铺内互加好友及添加小秘书为共同好友
	public function set_friends_for_users($arr,$sid){
		logger('超级管理员:批量设置好友关系!');
		if(is_array($arr)){
			$users = $arr;
		}else{
			$app_user = D('app_user');
			$where = array(
				'sid' => $sid
			);
			$users = $app_user->where($where)->select();
		}
		$max = count($users);
		$users[$max] = array(
			'store_simple_name' => 'zsylou',
			'username' => 'secretary'
		);
		//整理用户组
		$n = 0;
		$n_users = array();
		foreach($users as $k => $v){
			if($v['username'] != '' && $v['username'] != NULL){
				$n_users[$n] = array(
					'store_simple_name' => $v['store_simple_name'],
					'username' => $v['username']
				);
				$n++;
			}
		}
		if(count($n_users) == 1){
			logger('超级管理员:用户数组为空,无法设置环信好友关系!'."\n");
		}else{
			foreach($n_users as $k => $v){
				foreach($n_users as $x => $y){
					if(($k != $x) && ($x >= ($k+1))){
						$result = easemob_add_friend($v['store_simple_name'].'_'.$v['username'],$y['store_simple_name'].'_'.$y['username']);
						if($result['error'] == ''){
							logger('超级管理员:为用户'.$v['store_simple_name'].'_'.$v['username'].'设置好友关系成功!');
						}else{
							logger('超级管理员:为用户'.$v['store_simple_name'].'_'.$v['username'].'设置好友关系失败!');
						}
					}
				}
			}
			logger('超级管理员:批量设置好友关系,完毕!'."\n");
		}
	}
	public function add_secretary_as_friend($arr,$sid){
		logger('超级管理员:批量设置店铺员工的共同好友:影楼小秘书!');
		if(is_array($arr)){
			$users = $arr;
		}else{
			$app_user = D('app_user');
			$where = array(
				'sid' => $sid
			);
			$users = $app_user->where($where)->select();
		}
		if(empty($users)){
			logger('超级管理员:用户数组为空,无法设置环信好友关系!'."\n");
		}else{
			foreach($users as $k => $v){
				if($v['username'] != '' && $v['username'] != NULL){
					$result = easemob_add_friend($v['store_simple_name'].'_'.$v['username'],'aaa_secretary');
					if($result['error'] == ''){
						logger('超级管理员:为用户'.$v['store_simple_name'].'_'.$v['username'].'设置添加共同好友成功!');
					}else{
						logger('超级管理员:为用户'.$v['store_simple_name'].'_'.$v['username'].'设置添加共同好友失败!');
					}
				}
			}
			logger('超级管理员:批量设置共同好友,完毕!'."\n");
		}
	}
	public function testtest(){
		logger('超级管理员:测试');
		$arr = '';
		$sid = 9;
		$this->add_secretary_as_friend($arr,$sid);//edit_nickname_for_users
	}
	// 重写同步店铺员工用户函数 my_user ----> sysc_users
    /* 拆解 同步员工账号函数
     * 总体设计：
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
    	logger('超级管理员:同步店铺员工用户===》');
    	$post = I();
    	// logger('超级管理员: 传入参数：'.var_export($post,TRUE)); //debug 
    	$options['dogid'] = $post['dogid'];
		$options['url'] = 'http://'.$post['ip'].':'.$post['port'].'/';
		$options['sid'] = $post['sid'];
		$options['store_simple_name'] = $post['simple'];
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
			logger('超级管理员: 获取到新员工信息，进一步与原员工信息比对？！');
			if(count($old_users) <= 1){
				logger('超级管理员: 原有用户数少于1人，可能仅存管理员，将新员工全部加入用户表...');
				M()->startTrans();
				$add_result = $users->addAll($new_users); //仅存在管理员的情形下，批量导入用户,这里将用户名为空或包含中文的也导入了用户表。
				if($add_result){
					logger('超级管理员:新员工组全部加入用户表成功，下一步筛选用户，去除空用户名或包含汉字的用户...');
					$new_users = $this->user_filter($new_users);
					// logger('筛选后的新ERP用户组：'.var_export($new_users,TRUE)); //debug
					if(!empty($new_users)){
						logger('超级管理员:筛选用户完成，下一步批量注册环信');
						$new_easemob_users = $this->merge_sname_username($new_users);
						//批量注册用户建议20-60
						$new_users_num = count($new_easemob_users);
						if($new_users_num > 50){
							logger('超级管理员:新的用户组人员众多,分批次注册环信用户');
							$maxnum = ceil($new_users_num/50);
							$register_info = true;
							for($i = 0;$i<$maxnum;$i++){
								logger('第'.($i+1).'批次注册环信用户!');
								$register_users = easemob_create_users(array_slice($new_easemob_users,$i*50,50)); //批量注册环信用户
								// logger('批量注册环信用户返回信息：'.var_export($register_users,TRUE));
								if($register_users['error'] != ''){
									$register_info = false;
								}
							}
							if($register_info){
								$register_users['error'] = '';
							}else{
								$register_users['error'] = 'error';
							}
						}else{
							$register_users = easemob_create_users($new_easemob_users); //批量注册环信用户
						}
						// logger('批量注册环信用户返回信息：'.var_export($register_users,TRUE));
						if($register_users['error'] == ''){ //环信有错误需要个别问题，个别查询
							M()->commit();
							logger('超级管理员:批量注册环信成功，'); //因为UID的问题，需要取出全部用户
							$return_data = array(
								'status' => 1,
								'info' => '同步成功！'
							);
						}else{
							M()->rollback();
							logger('超级管理员:批量注册环信用户失败！');
							$return_data = array(
								'status' => 0,
								'info' => '批量注册失败！'
							);
						}
					}else{
						logger('超级管理员:筛选用户后，无合格新用户！同步员工主体完成。下一步，全体员工加小秘书为好友... ...');
						M()->commit();
						$return_data = array(
							'status' => 1,
							'info' => '同步完成！'
						);
					}
					$all_user = $users->where($where)->field('uid,username,store_simple_name')->select();
					//过滤用户
					$all_user = $this->user_filter($all_user);
					// logger('筛选后的全部用户组：'.var_export($all_user,TRUE)); //debug
					$this->add_secretary_friend($all_user); //加好友 不能保证100%都成功
				}else{
					M()->rollback();
					logger('超级管理员:批量添加用户入表失败！');
					$return_data = array(
						'status' => 0,
						'info' => '批量导入失败！'
					);
				}
			}else{
				logger('超级管理员: 原有用户数多于1人，新员工和老成员比对...');
				$new_users = $this->user_filter($new_users);
				// logger('筛选后的新ERP用户组：'.var_export($new_users,TRUE)); //debug
				//循环判断是否有新增用户
				logger('超级管理员:循环判断是否有新增用户');
				$this->loop_for_new($olduser,$new_users);
				//循环判断是否有删除用户
				logger('超级管理员:循环判断是否有删除用户');
				$olduser = $this->loop_for_old($olduser,$new_users);
				$return_data = array(
					'status' => 1,
					'info' => '同步成功！'
				);
			}
		}else{
			logger('超级管理员:新员工信息为空！有可能是ERP出现问题，所以暂时不做处理，需要专门人工来解决！');
			$return_data = array(
				'status' => 0,
				'info' => '请联系管理员！'
			);
		}
		// 检查老成员是否有未注册环信的情况 不能保证100%都成功
		logger('超级管理员: 检查是否有老成员未注册环信.....');
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
                    $array[$i]['username'] = trim(ltrim(strchr(strchr($v,'<i>'),'</i>',TRUE),'<i>'));
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
    			// 	'uid' => 2078,
    			// 	'aname' => 'aaa_secretary',
    			// 	'fid' => $add_result,
    			// 	'fname' => $user['store_simple_name'].'_'.$user['username'],
    			// 	'ctime' => time()
    			// );
    			// $relations[] = array(
    			// 	'uid' => $add_result,
    			// 	'aname' => $user['store_simple_name'].'_'.$user['username'],
    			// 	'fid' => 2078,
    			// 	'fname' => 'aaa_secretary',
    			// 	'ctime' => time()
    			// );
    			// $add_relation_result = $relation->addAll($relations);
    			// if($add_relation_result){
    			// 	logger('新员工添加好友关系记录成功...');
    			// 	$add_friend_result = easemob_add_friend('aaa_secretary',$user['store_simple_name'].'_'.$user['username']);
	    		// 	if($add_friend_result['error'] == ''){
	    		// 		logger('新员工添加小秘书为好友成功!!!');
	    				M()->commit();
	    				return true;
	    			// }else{
	    			// 	logger('新员工添加小秘书为好友失败...');
	    			// 	M()->rollback();
	    			// 	return false;
	    			// }
    			// }else{
    			// 	logger('新员工添加好友关系记录成功...');
    			// 	M()->rollback();
    			// 	return false;
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
    public function list_market_tools(){
		logger('超级管理员:查看营销工具');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		$tool = D('tool');
		$category = D('tool_category');
		$type = $category->select();
		$condition = 'tool a,tool_category b';
		//if show some type or all types
		if($post['id']){
			logger('超级管理员:查询特定类型的营销工具 '.$post['id']);
			$result = $tool->table($condition)->where('a.style = b.id AND a.style = '.$post['id'])->field('a.*,b.type')->select();
			// $sql = $tab->getLastsql(); //debug
			// logger('查询语句：'.$sql); //debug
		}else{
			$result = $tool->table($condition)->where('a.style = b.id ')->field('a.*,b.type')->select();
			// $sql = $tab->getLastsql(); //debug
			// logger('查询语句：'.$sql); //debug
		}
		logger('超级管理员:返回营销工具查询结果成功！'."\n");
		$this->assign('type',$type);
		$this->assign('tools',$result);
		$this->display();
	}
	//create new clothes or theme type
	public function new_tool_category(){
		logger('超级管理员:新建营销工具类型');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		$category = D('tool_category');
		if($post['type']){
			$data = array(
				'type' => $post['type'],
				'create_time' => time(),
				'create_admin' => session('uid')
			);
			$result = $category->add($data);
			if($result){
				$ajax_data = array(
					'status' => 1,
					'content' => '新建营销工具类型成功！'
				);
				logger('超级管理员：新建营销工具类型成功！'."\n");
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '新建营销工具类型失败！'
				);
				logger('超级管理员：新建营销工具类型失败！'."\n");
			}
		}else{
			$ajax_data = array(
				'status' => 2,
				'content' => '参数不全！'
			);
			logger('超级管理员：参数不全'."\n");
		}
		$this->ajaxReturn($ajax_data);
	}
	//create new clothes or theme
	public function new_tool(){
		logger('超级管理员:新建营销工具');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		$tab = D('tool');
		if($post['type'] && $post['name']){
			$data = array(
				'name' => $post['name'],
				'style' => $post['type'],
				'remarks' => $post['remark'],
				'create_time' => time(),
				'create_admin' => session('uid'),
			);
			$result = $tab->add($data);
			if($result){
				$ajax_data = array(
					'status' => 1,
					'content' => '新建营销工具成功！'
				);
				logger('超级管理员：新建营销工具成功！'."\n");
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '新建营销工具失败！'
				);
				logger('超级管理员：新建营销工具失败！'."\n");
			}
		}else{
			$ajax_data = array(
				'status' => 2,
				'content' => '参数不全！'
			);
			logger('超级管理员：参数不全'."\n");
		}
		$this->ajaxReturn($ajax_data);
	}
	//delete clothes or theme
	public function del_tool(){
		logger('超级管理员:删除营销工具');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		$tab = D('tool');
		if($post['id']){
			$where = array(
				'id' => $post['id']
			);
			$result = $tab->where($where)->delete();
			if($result){
				$ajax_data = array(
					'status' => 1,
					'content' => '删除营销工具成功！'
				);
				logger('超级管理员：删除营销工具成功！'."\n");
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '删除营销工具失败！'
				);
				logger('超级管理员：删除营销工具失败！'."\n");
			}
		}else{
			$ajax_data = array(
				'status' => 2,
				'content' => '参数不全！'
			);
			logger('超级管理员：参数不全'."\n");
		}
		$this->ajaxReturn($ajax_data);
	}
	//show edit page or save edit data
	public function edit_tool(){
		$post = I();
		logger('修改营销工具-传入参数：'.var_export($post,TRUE)); //debug
		$table = D('tool');
		$category = D('tool_category');
		if($post['id'] && ($post['name'] || $post['type'] || $post['remark'] || $post['content'] || $post['ico'])){
			$data = array(
				'name' => $post['name'],
				'style' => $post['type'],
				'remarks' => $post['remark'],
				'ico1' => $post['ico1'],
				'ico2' => $post['ico2'],
				'content' => $post['content'],
				'modify_time' => time(),
				'modify_admin' => session('uid')
			);
			$where = array(
				'id' => $post['id']
			);
			$result = $table->where($where)->save($data);
			if($result){
				$ajax_data = array(
					'status' => 1,
					'content' => '修改营销工具成功！'
				);
				logger('超级管理员：修改营销工具成功！'."\n");
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '修改营销工具失败！'
				);
				logger('超级管理员：修改营销工具失败！'."\n");
			}
			$this->ajaxReturn($ajax_data);
		}else if(!$post['id']){
			logger('超级管理员:参数不全！');
			$this->error('提交参数不全，请重试！');
		}else{
			logger('超级管理员:显示修改营销工具页面'."\n");
			$where = array(
				'id' => $post['id']
			);
			$result = $table->where($where)->find();
			$type = $category->where($condition)->select();
			$this->assign('type',$type); //全部类型
			$this->assign('tool',$result); //个体内容
			$this->display('edit_market_tools');
		}
	}
	public function upload_img(){
		logger('超级管理员:上传营销工具图标');
		header("Content-Type:text/html;charset:utf-8");
        // logger('上传图片-附带参数：'.var_export($post,TRUE)); //debug
        // logger('文件系统:'.var_export($_FILES,TRUE)); //debug
        logger('超级管理员:上传文件开始--->');
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg','gif','png','jpeg');
        $upload->rootPath = './Uploads/';
        $upload->autoSub = false; //禁用自动目录
        $upload->subName = ''; //自动目录设置为空，原来为日期
        $upload->savePath = 'ico/';
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
                $savepath = $v['savepath'];
                $savename = $v['savename'];
                $savesize = $v['size'];
                //生成缩略图
	            // $img= new \Think\Image();
	            // $img->open('./Uploads/'.$savepath.$savename);
	            // $img->thumb(200,200)->save('./Uploads/'.$savepath.'S'.$savename);
	            $path = '/Uploads/'.$savepath.$savename;
            }
            if($path){
            	logger('超级管理员:上传图片记录成功!'."\n");
                $data = array(
                    'status' => 1,
                    'url' => $path,
                    'content' => '上传成功!'
                );
            }else{
                logger('超级管理员:上传图片记录失败!'."\n");
                $data = array(
                    'status' => 0,
                    'content' => '上传失败!'
                );
            }
            exit(json_encode($data));
        }
	}
	// 九宫格模板列表
	public function list_wei_category()
	{
		$post = I();
		$page = $post['page'];
		if(empty($page))
			$page = 1;
		$category = D('weicategory');
		$where = array('delete_at'=>0,'sid'=>C('publicSid'));
		$field = 'id,name,content,thumb,create_at,modify_at,weight';
		$order = 'weight asc,create_at desc,modify_at desc';
		$limit = 7;
        $count = $category->where($where)->count();
        Vendor("Page.page");
        $page = new \Page($count,$limit);
		// $cates = $category->where($where)->field($field)->order($order)->select();
		$cates = $category->where($where)->field($field)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
        $show = $page->show();
        $this->assign('page',$show);
		$this->assign('category',$cates);
		$this->display();
	}
	public function upload_cate_img(){
		logger('超级管理员:上传九宫格分类图标');
		header("Content-Type:text/html;charset:utf-8");
        // logger('上传图片-附带参数：'.var_export($post,TRUE)); //debug
        // logger('文件系统:'.var_export($_FILES,TRUE)); //debug
        logger('超级管理员:上传文件开始--->');
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg','gif','png','jpeg');
        $upload->rootPath = './Uploads/';
        $upload->autoSub = false; //禁用自动目录
        $upload->subName = ''; //自动目录设置为空，原来为日期
        $upload->savePath = 'weiretrans/category/';
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
                $savepath = $v['savepath'];
                $savename = $v['savename'];
	            $path = '/Uploads/'.$savepath.$savename;
            }
            if($path){
            	logger('超级管理员:上传图片记录成功!'."\n");
                $data = array(
                    'status' => 1,
                    'url' => $path,
                    'content' => '上传成功!'
                );
            }else{
                logger('超级管理员:上传图片记录失败!'."\n");
                $data = array(
                    'status' => 0,
                    'content' => '上传失败!'
                );
            }
            exit(json_encode($data));
        }
	}
	// 创建分类或编辑
	public function new_edit_category()
	{
		logger('新建或更新模板分类！'); // debug
		$post = I();
		$id = $post['id'];
		$name = $post['name'];
		$weight = $post['weight'];
		$content = $post['content'];
		$thumb = $post['thumb'];
		logger('参数：'.var_export($post,true)); // debug
		$cate = D('weicategory');
		if($id){ // 编辑
			logger('更新模板分类！'); // debug
			$info = array(
				'name' => $name,
				'weight' => $weight,
				'thumb' => $thumb,
				'content' => $content,
				'modify_at' => time(),
				'modify_by' => session('uid')
			);
			$result = $cate->where(array('id'=>$id))->save($info);
			if($result){
				logger('更新模板分类,创建成功！'."\n");
				$data = array(
					'status' => 1,
					'info' => '修改成功！'
				);
			}else{
				logger('更新模板分类,创建失败！'."\n");
				$data = array(
					'status' => 0,
					'info' => '修改失败，请重试！'
				);
			}
		}else{ // 新建
			logger('新建模板分类！');
			$info = array(
				'name' => $name,
				'weight' => $weight,
				'thumb' => $thumb,
				'content' => $content,
				'sid' => C('publicSid'),
				'create_at' => time(),
				'create_by' => session('uid')
			);
			$result = $cate->add($info);
			if($result){
				logger('新建模板分类,创建成功！'."\n");
				$data = array(
					'status' => 1,
					'info' => '创建成功！'
				);
			}else{
				logger('新建模板分类,创建失败！'."\n");
				$data = array(
					'status' => 0,
					'info' => '创建失败，请重试！'
				);
			}
		}
		$this->ajaxReturn($data);
	}
	// 删除分类
	public function del_category()
	{
		$post = I();
		$id = $post['id'];
		if($id){
			$cate = D('weicategory');
			$result = $cate->where(array('id'=>$id))->save(array('delete_at'=>time(),'delete_by'=>session('uid')));
			if($result){
				logger('删除模板分类,删除成功！'."\n");
				$data = array(
					'status' => 1,
					'info' => '删除成功！'
				);
			}else{
				logger('删除模板分类,删除失败！'."\n");
				$data = array(
					'status' => 0,
					'info' => '删除失败，请重试！'
				);
			}
		}else{
			$data = array(
				'status' => 2,
				'info' => '参数不全'
			);
		}
		$this->ajaxReturn($data);
	}
	// 九宫格模板列表
	public function list_wei_templet()
	{
		// 查询模板
		$post = I();
		$id = $post['id'];
		$page = $post['page'];
		if(empty($page))
			$page = 1;
		Vendor("Page.page");
		$templet = D('CateTemplet');
		$where = array(
			'sid' => C('publicSid'),
			'delete_at' => 0
		);
		if($id)
			$where['cid']  = $id;
		$field = 'id,name,content,thumb,category,create_at,modify_at,retranstimes,commend';
		$order = 'create_at desc,retranstimes desc,commend desc';
		$limit = 7;
        $count = $templet->where($where)->count();
        $page = new \Page($count,$limit);
        $templets = $templet->where($where)->field($field)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
        $show = $page->show();
        $this->assign('page',$show);
		$this->assign('templet',$templets);
		// 查询所有分类
		$cate = D('weicategory');
		$cates = $cate->where(array('delete_at'=>0))->field('id,name')->select();
		$this->assign('category',$cates);
		$this->display();
	}
	// 创建模板
	public function new_templet()
	{
		// 查询所有分类
		$cate = D('weicategory');
		$cates = $cate->where(array('delete_at'=>0,'sid'=>C('publicSid')))->field('id,name')->select();
		$this->assign('category',$cates);
		$this->assign('pageTitle','新建模板');
		$this->display();
	}
	// 接收模板图片
	public function upload_templet_img()
	{
		logger('超级管理员:上传九宫格模板图片');
		header("Content-Type:text/html;charset:utf-8");
        // logger('上传图片-附带参数：'.var_export($post,TRUE)); //debug
        // logger('文件系统:'.var_export($_FILES,TRUE)); //debug
        logger('超级管理员:上传文件开始--->');
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg','gif','png','jpeg');
        $upload->rootPath = './Uploads/';
        $upload->autoSub = false; //禁用自动目录
        $upload->subName = ''; //自动目录设置为空，原来为日期
        $upload->savePath = 'weiretrans/templet/';
        $info = $upload->upload();
        // logger('info:'.var_export($info,TRUE)); //debug
        if(!$info){
        	logger('图片上传失败！'."\n");
            $data = array(
                'status' => 0,
                'content' => $upload->getError()
            );
            $this->ajaxReturn($data);
        }else{
        	$images = array();
            foreach ($info as $v) {
	            $images[] = array(
	            	'url' => '/Uploads/'.$v['savepath'].$v['savename'],
	            	'create_at' => time()
	            );
            }
            $image = D('weiimage');
            $result = $image->addAll($images);
            if($result){
            	logger('超级管理员:上传图片记录成功!'."\n");
            	$max = count($images);
            	for($i=0;$i<$max;$i++){
            		$images[$i]['id'] = $result + $i;
            	}
                $data = array(
                    'status' => 1,
                    'images' => $images,
                    'content' => '上传成功!'
                );
            }else{
                logger('超级管理员:上传图片记录失败!'."\n");
                $data = array(
                    'status' => 0,
                    'content' => '上传失败!'
                );
            }
            exit(json_encode($data));
        }
	}
	public function makeThumb()
	{
		logger('合成缩略图...');
		$post = I();
		$images = $post['images'];
		// logger('images:'.$images);
		if($images){
			$images = chanslate_json_to_array($images);
			if(count($images) >= 1){
				// logger('图片地址：'.var_export($images,true));  // debug
				logger('解析图片地址成功...');
				$thumb = $this->getThumb($images);
				if($thumb){
					logger('生成缩略图片地址成功！'."\n");
					$data = array(
						'status' => 1,
						'info' => '缩略图生成成功！',
						'url' => $thumb
					);
				}else{
					logger('生成缩略图片地址失败！'."\n");
					$data = array(
						'status' => 0,
						'info' => '缩略图生成失败！'
					);
				}
			}else{
				logger('解析图片地址失败！'."\n");
				$data = array(
					'status' => 0,
					'info' => '没有图片！'
				);
			}
		}else{
			logger('参数不全！'."\n");
			$data = array(
				'status' => 0,
				'info' => '没有图片！'
			);
		}
		$this->ajaxReturn($data);
	}
	// 生成图片缩略图
	private function getThumb($images)
	{
		// 画布
		$newWidth = 160;
		$canvas = imagecreatetruecolor($newWidth,$newWidth);
		$white = imagecolorallocate($canvas,255,255,255);
		imagefill($canvas,0,0,$white);
		// 循环添加图片 没有图片填充浅灰色
		$perWidth = $newWidth/32;
		$xPos = array(0,$perWidth*11-1,$perWidth*22-1);
		for($i=0;$i<9;$i++){
			if($images[$i]){
				$source = $this->getImageSource($images[$i]);
				$url = '.'.$images[$i];
			}else{
				$url = './Uploads/weiretrans/img/hui.png';
				$source = imagecreatefrompng($url);
			}
			list($width,$height) = getimagesize($url);
			$x = $xPos[$i%3];
			$y = $xPos[floor($i/3)];
			$perW = $perWidth*10;
			$perH = &$perW;
			imagecopyresampled($canvas,$source,$x,$y,0,0,$perW,$perH,$width,$height);
		}
		$path = './Uploads/weiretrans/templet/Thumb'.$this->getRand(10).'.jpg';
		imagejpeg($canvas,$path,75);
		$path = ltrim($path,'.');
		return $path;
	}
	// 图片操作， 获取图片源
	private function getImageSource($url)
	{
		$type = strchr($url,'.');
		$url = '.'.$url;
		switch($type){
			case '.png':
				$source = imagecreatefrompng($url);
				break;
			case '.gif':
				$source = imagecreatefromgif($url);
				break;
			case '.jpg':
			case '.jpeg':
				$source = imagecreatefromjpeg($url);
				break;
			default:
				break;
		}
		return $source;
	}
	// 随机字符串
	private function getRand($num = 4){
		$string = '';
		$source = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_';
		$length = strlen($source);
		while(strlen($string) < $num){
			$string .= substr($source,rand(0,$num),1);
		}
		return $string;
	}
	public function new_edit_templet()
	{
		logger('新建或更新模板！');
		$post = I();
		$id = $post['id'];
		$name = $post['name'];
		$cate = $post['cate'];
		$content = $post['content'];
		$thumb = $post['thumb'];
		$order = $post['order'];
		$oldorder = $post['oldorder'];
		$commend = $post['commend'];
		// logger('参数：'.var_export($post,true)); // debug
		$templet = D('weitemplet');
		if($id){ // 编辑
			logger('更新模板！');
			$info = array(
				'name' => $name,
				'cid' => $cate,
				'thumb' => $thumb,
				'content' => $content,
				'commend' => $commend,
				'image_order' => $order,
				'modify_at' => time(),
				'modify_by' => session('uid')
			);
			M()->startTrans();
			$result = $templet->where(array('id'=>$id))->save($info);
			if($result){
				logger('更新模板记录,成功！');
				$result = $this->checkImgTem($oldorder,$order,$id);
				if($result){
					M()->commit();
					logger('更新模板和图片关系记录成功!'."\n");
					$data = array(
						'status' => 1,
						'info' => '修改成功！'
					);
				}else{
					M()->rollback();
					logger('更新模板,失败！'."\n");
					$data = array(
						'status' => 0,
						'info' => '修改失败，请重试！'
					);
				}
			}else{
				M()->rollback();
				logger('更新模板,失败！'."\n");
				$data = array(
					'status' => 0,
					'info' => '修改失败，请重试！'
				);
			}
		}else{ // 新建
			logger('新建模板分类！');
			$info = array(
				'name' => $name,
				'cid' => $cate,
				'sid' => C('publicSid'),
				'thumb' => $thumb,
				'author' => session('uid'),
				'commend' => $commend,
				'content' => $content,
				'image_order' => $order,
				'create_at' => time(),
				'create_by' => session('uid')
			);
			// logger('添加的信息数组:'.var_export($info,true)); // debug
			M()->startTrans();
			$result = $templet->add($info);
			if($result){
				logger('新建模板,模板记录创建成功！');
				$order = chanslate_json_to_array($order);
				$addResult = $this->updateImageTem($result,$order);
				if($addResult){
					M()->commit();
					logger('新建模板,模板和图片关系记录添加成功!'."\n");
					$data = array(
						'status' => 1,
						'info' => '创建成功！'
					);
				}else{
					M()->rollback();
					logger('新建模板,模板和图片关系记录添加失败!'."\n");
					$data = array(
						'status' => 0,
						'info' => '创建失败，请重试！'
					);
				}
			}else{
				M()->rollback();
				logger('新建模板,创建失败！'."\n");
				$data = array(
					'status' => 0,
					'info' => '创建失败，请重试！'
				);
			}
		}
		$this->ajaxReturn($data);
	}
	// 清除就图片关系 添加新图片关系
	private function checkImgTem($order,$newOrder,$id)
	{
		if(!empty($order)){
			logger('原有图片顺序不为空!');
			if(!empty($newOrder)){
				$order = chanslate_json_to_array($order);
				$newOrder = chanslate_json_to_array($newOrder);
				// logger('原有图片顺序数组:'.var_export($order,true)); // debug
				// logger('现有图片顺序数组:'.var_export($newOrder,true)); // debug
				$oMax = count($order);
				$nMax = count($newOrder);
				// 需清除的
				$del = '';
				foreach($order as $x){
					$i = 1;
					foreach($newOrder as $y){
						if($x == $y){
							break;
						}else{
							if($i == $nMax){
								$del .= ' (iid='.$x.' AND tid='.$id.') OR';
							}
							$i++;
						}
					}
				}
				$del = rtrim($del,' OR');
				// 需添加的
				$add = array();
				foreach($newOrder as $x){
					$i = 1;
					foreach($order as $y){
						if($x == $y){
							break;
						}else{
							if($i == $oMax){
								$add[] = array(
									'iid' => $x,
									'tid' => $id,
									'create_at' => time()
								);
							}
							$i++;
						}
					}
				}
				logger('需要添加的图片模板关系:'.var_export($add,true)); // debug
				logger('需要删除的图片模板关系:'.$del); // debug
				$imgTem = D('weiimgtem');
				$der = true;
				if(!empty($del))
					$der = $imgTem->where($del)->delete();
				if(!$der)
					return false;
				$adr = true;
				if(count($add) >= 1)
					$adr = $imgTem->addAll($add);
				if(!$adr)
					return false;
				return true;
			}else{
				logger('新图片顺序为空!不修改图片和顺序!');
				// $order = chanslate_json_to_array($order);
				// $result = $this->deleteImageTem($id,$order);
				// return $result;
				return true;
			}
		}else{
			logger('原有图片顺序为空!');
			if(!empty($newOrder)){
				$newOrder = chanslate_json_to_array($newOrder);
				logger('新图片顺序数组:'.var_export($newOrder,true)); // debug
				$result = $this->updateImageTem($id,$newOrder);
				return $result;
			}else{
				logger('新图片顺序也为空!不修改图片和顺序!');
				return true;
			}
		}
	}
	// 添加模板和图片的对应关系
	private function updateImageTem($id,$order)
	{
		foreach($order as $v){
			$data[] = array(
				'iid' => $v,
				'tid' => $id,
				'create_at' => time()
			);
		}
		// logger('将要全部添加的图片模板关系数组:'.var_export($data,true)); // debug
		$image = D('weiimgtem');
		$result = $image->addAll($data);
		return $result;
	}
	// 编辑模板
	public function edit_templet()
	{
		$post = I();
		$id = $post['id'];
		if($id){
			// 查询模板
			$templet = D('weitemplet');
			$thTem = $templet->where(array('id'=>$id))->field('id,name,content,thumb,image_order,cid,commend')->find();
			$this->assign('it',$thTem);
			$image = D('TempletImage');
			$images = $image->where(array('tid'=>$id))->field('id,url')->select();
			$this->assign('image',$images);
			// 查询所有分类
			$cate = D('weicategory');
			$cates = $cate->where(array('delete_at'=>0,'sid'=>C('publicSid')))->field('id,name')->select();
			$this->assign('category',$cates);

			$this->assign('pageTitle','编辑模板');
			$this->display('new_templet');
		}else{
			$this->error('未选择任何模板！');
		}
	}
	// 删除模板
	public function delete_templet()
	{
		$post = I();
		$id = $post['id'];
		if($id){
			$templet = D('weitemplet');
			$result = $templet->where(array('id'=>$id))->save(array('delete_at'=>time(),'delete_by'=>session('uid')));
			if($result){
				logger('删除模板,成功！'."\n");
				$data = array(
					'status' => 1,
					'info' => '删除失败，请重试！'
				);
			}else{
				logger('删除模板,失败！'."\n");
				$data = array(
					'status' => 0,
					'info' => '删除失败，请重试！'
				);
			}
			$this->ajaxReturn($data);
		}else{
			$this->error('未选择任何模板！');
		}
	}
	// 添加模板和图片的对应关系
	private function deleteImageTem($id,$order)
	{
		$result = true;
		$del = '';
		foreach($order as $v){
			$del .= ' (iid='.$v.' AND tid='.$id.') OR';
		}
		$del = rtrim($del,' OR');
		logger('将要删除的图片模板关系:'.$del); // debug
		$image = D('weiimgtem');
		$result = $image->delete($del);
		return $result;
	}
	public function new_account()
	{
		$market_area = D('market_area');
		$areas = $market_area->field('id,name')->select();
		$expire_date = array(
			0 => array(
				'time' => -1,
				'name' => '未设置服务期'
			),
			1 => array(
				'time' => 31536000,
				'name' => '1年'
			),
			2 => array(
				'time' => 15768000,
				'name' => '6个月'
			),
			3 => array(
				'time' => 7884000,
				'name' => '3个月'
			),
			4 => array(
				'time' => 2628000,
				'name' => '1个月'
			),
			5 => array(
				'time' => 604800,
				'name' => '7天体验周'
			),
			6 => array(
				'time' => 0,
				'name' => '无限期'
			)
		);
		$this->assign('area',$areas);
		$this->assign('expire',$expire_date);
		$tool = D('tool');
		$tools = $tool->field('id,name')->select();
		$this->assign('tools',$tools);
		$this->display();
	}
	public function count_info()
	{
		$yllist = D('store');
		$userlist = D('app_user');
		$count = array();
		$dtime = strtotime(date('Y-m-d',time()));
		$allstore = $yllist->count();
		$count['allstore'] = $allstore;
		$newstore = $yllist->where('createtime >='.$dtime)->field('id')->count();
		$count['newstore'] = $newstore;
		$count['nsper'] = ceil($newstore/($allstore - $newstore)*100);
		$allusers = $userlist->where('username != '."''")->count();
		$count['allusers'] = $allusers;
		$newusers = $userlist->where(' createtime > '.$dtime)->count();
		$count['newusers'] = $newusers;
		$count['nuper'] = ceil($newusers/($allusers - $newusers)*100);
		$allinstall = $userlist->where('logintime > 0')->count();
		$count['allinstall'] = $allinstall;
		$newinstall = $userlist->where('logintime >= '.$dtime)->count();
		$count['newinstall'] = $newinstall;
		$count['niper'] = ceil($newinstall/($allinstall - $newinstall)*100);
		$this->assign('count',$count);
		$this->display();
	}
	public function new_tool_buy(){
		$post = I();
		// logger('营销工具参数:'.var_export($post,true)); //debug
		// die;
		$sid = $post['store'];
		$tools = $post['tools'];
		$expire_time = $post['expire_time'];
		if($sid && $tools && $expire_time){
			$tools = chanslate_json_to_array($tools);
			if(count($tools) >= 1){
				$info = array();
				foreach($tools as $v){
					$info[] = array(
						'sid' => $sid,
						'item' => $v,
						'create_at' => time(),
						'expire_time' => $expire_time,
						'create_by' => session('uid')
					);
				}
				$buy = D('tool_buy');
				$result = $buy->addAll($info);
				if($result){
					logger('开通营销服务，添加记录成功！');
					$data = array(
						'status' => 1,
						'info' => '添加记录成功'
					);
				}else{
					logger('开通营销服务，添加记录失败！');
					$data = array(
						'status' => 0,
						'info' => '添加记录失败'
					);
				}
			}else{
				logger('开通营销服务，参数tools有错误！');
				$data = array(
					'status' => 0,
					'info' => '参数不全'
				);
			}
		}else{
			logger('开通营销服务，参数不全！');
			$data = array(
				'status' => 0,
				'info' => '参数不全'
			);
		}
		$this->ajaxReturn($data);
	}
	public function ylou_excel()
	{
		$market_area = D('market_area');
		$areas = $market_area->field('id,name')->select();
		$this->assign('area',$areas);
		$this->assign('date',date('Y-m-d',time()));
		$this->display();
	}
	private function get_time_long($time){
		$array = array(
			'604800' => '7天',
			'2628000' => '一个月',
			'7884000' => '三个月',
			'15768000' => '六个月',
			'31536000' => '一年',
			'0' => '无限期',
		);
		if($array[$time])
			return $array[$time];
		$str = get_time_length($time);
		return $str;
	}
	public function buy(){
		$post = I();
		$sid = $post['id'];
		// logger('开通服务参数：'.var_export($post,true)); // debug
		if($sid){
			$buy = D('ServiceBuy');
			$where = array('sid'=>$sid);
			$tools = $buy->where($where)->field('id,create_at,expire,name')->select();
			if(count($tools) >= 1){
				logger('获取营销服务：有购买记录！'."\n");
				$tool = D('tool');
				$ts = $tool->field('id,name')->select();
				foreach($ts as $k => $v){
					$ts[$k]['create_at'] = '';
					$ts[$k]['expire'] = '';
					$ts[$k]['end'] = '';
					foreach($tools as $x => $y){
						if($v['id'] == $y['id']){
							$ts[$k]['create_at'] = date('Y-m-d',$y['create_at']);
							$ts[$k]['expire'] = $this->get_time_long($y['expire']);
							$end = (int)$y['expire'] + (int)$y['create_at'];
							$ts[$k]['end'] = date('Y-m-d',$end);
							$ts[$k]['check'] = 1;
							break;
						}
					}
				}
				$data = array(
					'status' => 1,
					'data' => $ts
				);
			}else{
				logger('获取营销服务：购买为零！'."\n");
				$tool = D('tool');
				$ts = $tool->field('id,name')->select();
				foreach($ts as $k => $v){
					$ts[$k]['create_at'] = '';
					$ts[$k]['expire'] = '';
					$ts[$k]['end'] = '';
					$ts[$k]['check'] = 0;
				}
				$data = array(
					'status' => 1,
					'info' => '失败！',
					'data' => $ts
				);
			}
		}else{
			logger('获取营销服务：参数不全！'."\n");
			$data = array(
				'status' => 2,
				'info' => '参数不全！'
			);
		}
		$this->ajaxReturn($data);
	}
	public function tool_buy(){
		$post = I();
		// logger('开通服务参数：'.var_export($post,true)); // debug
		// die;
		$add = $post['add'];
		$del = $post['del'];
		$expire = $post['expire'];
		$sid = $post['sid'];
		if($sid && $expire && ($add || $del)){
			$buy = D('tool_buy');
			M()->startTrans();
			if(!empty($del)){
				logger('需要删除的服务不为空！');
				$del = explode(',',$del);
				if(count($del) >= 1){
					logger('解析删除服务数组成功！');
					$info = '';
					foreach($del as $v){
						$info .= ' (item='.$v.' AND sid='.$sid.') OR';
					}
					$info = rtrim($info,' OR');
					$del_result = $buy->where($info)->delete(); 
				}else{
					logger('解析删除服务数组失败！');
					$del_result = false;
				}
			}else{
				logger('需要删除的服务为空！');
				$del_result = true;
			}
			if(!empty($add)){
				logger('需要添加的服务不为空！');
				$add = explode('_',$add);
				if(count($add) >= 1){
					logger('解析添加服务数组成功！');
					$info = array();
					foreach($add as $v){
						$info[] = array(
							'sid' => $sid,
							'item' => $v,
							'create_at' => time(),
							'expire_time' => $expire,
							'create_by' => session('uid')
						);
					}
					$add_result = $buy->addAll($info);
					if($add_result){
						logger('添加服务记录成功！');
						$add_result = true;
					}else{
						logger('添加服务记录失败！');
						$add_result = false;
					}
				}else{
					logger('解析添加服务数组失败！');
					$add_result = false;
				}
			}else{
				logger('需要添加的服务为空！');
				$add_result = true;
			}
			if($add_result && $del_result){
				M()->commit();
				logger('修改营销服务记录成功！'."\n");
				$data = array(
					'status' => 1,
					'info' => '成功！'
				);
			}else{
				M()->rollback();
				logger('修改营销服务记录失败！'."\n");
				$data = array(
					'status' => 0,
					'info' => '失败！'
				);
			}
		}else{
			logger('营销服务：参数不全！'."\n");
			$data = array(
				'status' => 2,
				'info' => '参数不全！'
			);
		}
		$this->ajaxReturn($data);
	}
	public function user_search()
	{
		$this->display();
	}

	public function search_users(){
		$post = I();
		logger('会员账号搜索参数：'.var_export($post,true)); // debug
		// die;
		$name = $post['name'];
		if($name){
			$user = D('Users');
			$where['username'] = array('neq','');
			$where['_string'] = "username ='".$name."' OR u.store_simple_name='".$name."' OR nickname='".$name."'";
			$field = 'uid,sid,username,nickname,type,loginip,logintime,store,simple,createtime,status as sta';
			$order = 'createtime desc,uid asc';
			$users = $user->where($where)->field($field)->order($order)->select();
			if($users){
				logger('会员账号搜索：搜索无结果！'."\n");
				foreach($users as $k => $v){
					if($v['type'] == 1){
						$users[$k]['type'] = '店长';
					}else if($v['type'] == 2){
						$users[$k]['type'] = '考勤组长';
					}else{
						$users[$k]['type'] = '店员';
					}
					if($v['createtime'] > 0){
						$users[$k]['createtime'] = date('Y-m-d',$v['createtime']);
					}else{
						$users[$k]['createtime'] = '';
					}
					if($v['logintime'] > 0){
						$users[$k]['logintime'] = date('Y-m-d',$v['logintime']);
					}else{
						$users[$k]['logintime'] = '';
					}
				}
				$data = array(
					'status' => 1,
					'info' => '搜索无结果!',
					'data' => $users
				);
			}else{
				logger('会员账号搜索：搜索无结果！'."\n");
				$data = array(
					'status' => 0,
					'info' => '搜索无结果!'
				);
			}
		}else{
			logger('会员账号搜索：参数不全！'."\n");
			$data = array(
				'status' => 2,
				'info' => '参数不全！'
			);
		}
		$this->ajaxReturn($data);
	}
}
?>	
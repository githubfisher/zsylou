<?php
namespace Home\Controller;
use Think\Controller;
// 签到类
class CheckinController extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function index(){
		logger('用户:'.session('uid').' ,用户签到。');
		$post = I();
		if($post['x'] && $post['y'] && $post['location']){
			$checkin = D('check_in');
			$chinfo = array(
				'uid' => session('uid'),
				'sid' => session('sid'),
				'intime' => time(),
				'location_x' => $post['x'],
				'location_y' => $post['y'],
				'location' => $post['location'],
				'wtype' => session('wtype')
			);
			// $_POST['intime'] = time();
			// $_GET['intime'] = time();
			// $_POST['uid'] = $uid;
			// $_GET['uid'] = $uid;
			// $checkin->create();
			$result = $checkin->add($chinfo);
			if($result){
				//签到成功后返回该用户今天签到次数
				// 当天零时的时间戳
				$timestamp = strtotime(date('Y-m-d',time()));
				$where = array(
				'uid' => session('uid'),
				'intime' => array('gt',$timestamp)
				);
				$times = $checkin->where($where)->count();
				$data = array(
					'code' => '1',
					'message' => '签到成功！',
					'times' => $times
				);
				logger("签到成功！\n");
				exit(json_encode($data));
			} else{
				$data = array(
					'code' => '0',
					'message' => '签到失败！'
				);
				logger("签到失败！\n");
				exit(json_encode($data));
			}
		}else{
			// 如果是仅仅访问签到页面，返回该用户的签到次数
			$checkin = D('check_in');
			// 当天零时的时间戳
			$timestamp = strtotime(date('Y-m-d',time()));
			$where = array(
				'uid' => session('uid'),
				'intime' => array('gt',$timestamp)
			);
			$result = $checkin->where($where)->count();
			$data = array(
					'code' => '1',
					'message' => '返回该用户签到次数',
					'times' => $result
			);
			logger("查询该用户签到次数\n");
			exit(json_encode($data));
		}
	}
	//查询当天所有员工的签到情况
	public function query(){
		logger('查询签到记录');
		$post = I();
		$page = $post['page'];
		$checkin = D('check_in');
		// 当天零时的时间戳
		$timestamp = strtotime(date('Y-m-d',time()));
		$where = array(
			'sid' => session('sid'),
			'intime' => array('gt',$timestamp)
		);
		//分页 + 当天店铺所有员工的签到记录
		$result = $checkin->where($where)->order('intime asc')->page($page.',5')->select();
		$sql = $checkin->getLastSql();
		// logger('sql：'.$sql); //debug
		if($result){
			//查询头像 2016-06-01
			$app_user = D('app_user');
			foreach($result as $k => $v){
				$where = array(
					'uid' => $v['uid']
				);
				$checkin_user = $app_user->where($where)->field('head')->find();
				if(strpos($checkin_user['head'],'Uploads/avatar/')){
					$result[$k]['head'] = C('base_url').$checkin_user['head'];
				}else{
					$result[$k]['head'] = '';
				}
			}
			//查询头像 END
			$data = array(
				'result' => $result,
				'code' => 1,
				'message' => '查询签到成功！'
			);
			logger("查询签到成功！\n");
			exit(json_encode($data));
		}else{
			$data = array(
				'code' => '0',
				'message' => '查询签到失败！'
			);
			logger("查询签到失败！\n");
			exit(json_encode($data));
		}
		
	}
	// 查询（当天或某一天）所有员工的签到情况，除管理员外，并整理签到次数（是否签到）
	public function query_all_times(){
		logger('查询签到统计-开始');
		$post = I();
		$date = $post['date']; //按日期查询 
		$date = chtimetostr($date); //转换中文时间成标准格式
		$datestamp = strtotime($date); //转换成时间戳
		logger('查询日期的时间戳：'.$datestamp); //debug
		$checkin = D('check_in');
		// 当天零时的时间戳
		$timestamp = strtotime(date('Y-m-d',time()));
		// 默认查询今天的签到信息
		$where = array(
			'sid' => session('sid'),
			'intime' => array('gt',$timestamp)
		);
		//如果查询特定某天的签到信息
		if($datestamp){
			$zerostamp = strtotime(date('Y-m-d',$datestamp)); //当天零时的时间戳
			$nextzerostamp = strtotime(date('Y-m-d',$datestamp+86400)); //当天24点的时间戳
			$where['intime'] = array(array('egt',$zerostamp),array('elt',$nextzerostamp));
		}
		$result = $checkin->where($where)->select();
		// logger('签到查询结果： '.var_export($result,TRUE)); //查询结果
		// 取出店铺所以员工，组成计数为零的签到数组
		$app_user = D('app_user');
		$where = array(
			'sid' => session('sid')
		);
		$users = $app_user->where($where)->field('uid,head,realname,nickname')->select();
		//初始化员工签到数组，初始签到次数为零
		foreach($users as $k => $v){
			$users[$k]['times'] =0;
			//处理头像
			if(strpos($v['head'],'Uploads/avatar/')){
				$users[$k]['head'] = C('base_url').$v['head'];
			}
		}
		//如果查询到有员工的签到信息
		if($result){
			//当有签到数据时，遍历计数
			foreach($result as $key => $value){
				foreach($users as $k => $v){
					if($value['uid'] == $v['uid']){
						$users[$k]['times']++;
					}
				}
			}
		}
		$data = array(
			'code' => 1,
			'message' => '签到统计信息返回成功！',
			'result' => $users
		);
		logger("签到统计信息返回成功！\n");
		exit(json_encode($data));
	}
	// 查询某一个员工的签到详细情况
	public function query_the_one(){
		logger('某员工的签到详细信息--查询开始');
		$post = I();
		$id = $post['id'];
		$date = $post['date']; //按日期查询 
		$date = chtimetostr($date); //转换中文时间成标准格式
		$datestamp = strtotime($date); //转换成时间戳
		logger('查询日期的时间戳：'.$datestamp); //debug
		$checkin = D('check_in');
		// 当天零时的时间戳
		$timestamp = strtotime(date('Y-m-d',time()));
		// 默认查询今天的签到信息
		$where = array(
			'sid' => session('sid'),
			'uid' => $id,
			'intime' => array('gt',$timestamp)
		);
		//如果查询特定某天的签到信息
		if($datestamp){
			$zerostamp = strtotime(date('Y-m-d',$datestamp)); //当天零时的时间戳
			$nextzerostamp = strtotime(date('Y-m-d',$datestamp+86400)); //当天24点的时间戳
			$where['intime'] = array(array('egt',$zerostamp),array('elt',$nextzerostamp));
		}
		$result = $checkin->where($where)->select();
		//查询该员工的头像，nick、realname
		$where = array(
			'sid' => session('sid'),
			'uid' => $id
		);
		$app_user = D('app_user');
		$the_user = $app_user->where($where)->field('head,realname,nickname')->find();
		if($result){ //如果有签到信息，就把员工的头像等信息写入到签到记录中
			foreach($result as $k => $v){
				$result[$k]['realname'] = $the_user['realname'];
				$result[$k]['nickname'] = $the_user['nickname'];
				$result[$k]['head'] = $the_user['head'];
			}
		}
		logger('签到查询结果： '.var_export($result,TRUE)); //查询结果
		$data = array(
			'code' => 1,
			'message' => '签到详细信息返回成功！',
			'result' => $result
		);
		logger("签到详细信息返回成功！\n");
		exit(json_encode($data));

	}
}
?>
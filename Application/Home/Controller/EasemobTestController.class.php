<?php
namespace Home\Controller;
use Think\Controller;

class EasemobTestController extends Controller
{
	public function _initialize(){
		// $scheck = A('SessionCheck');
		// $scheck->index();
		header("content-type:text/html; charset=utf-8;");
		//引入环信库文件
		Vendor('Easemob.Easemob');
	}
	// 删除环信用户
	public function delete_user(){
		$post = I();
		$name = $post['name'];
		$delete_result = delete_easemob_user($name);
		logger('环信删除用户信息：'.var_export($delete_result,TRUE));
		exit(json_encode($delete_result));
	}
	// 创建环信用户
	public function create_user(){
		$post = I();
		$name = $post['name'];
		$pwd = $post['pwd'];
		$create_info = easemob_create_user($name,$pwd);
		logger('环信用户信息：'.var_export($create_info,TRUE));
		exit(json_encode($create_info));
	}
	//获取用户环信信息
	public function get_user_info(){
		$post = I();
		$name = $post['name'];
		$user = easemob_get_user($name);
		logger('环信用户信息：'.var_export($user,TRUE));
		exit(json_encode($user));
	}
	public function get_user_friends(){
		$post = I();
		$name = $post['name'];
		$friends = easemob_get_friends($name);
		logger('环信用户好友信息：'.var_export($friends,TRUE));
		exit(json_encode($friends));
	}
	public function add_friend(){
		$post = I();
		$name = $post['name'];
		$fname = $post['fname'];
		$friends = easemob_add_friend($name,$fname);
		logger('环信用户好友信息：'.var_export($friends,TRUE));
		exit(json_encode($friends));
	}
	public function get_group_info(){
		$post = I();
		$gid = $post['gid'];
		$info = easemob_get_the_group(array(0 => $gid));
		logger('环信群组信息：'.var_export($info,TRUE));
		$affiliations = $info['data'][0]['affiliations_count'];
		logger('成员人数：'.$affiliations);
		exit(json_encode($info));
	}
	//获取全部环信群组信息
	public function get_all_groups(){
		$users = D('app_user');
		$where = array(
			'sid' => 9,
			'username' => array('neq','')
		);
		$all_users = $users->where($where)->field('uid,username,sid')->select();
		logger('9号全部用户信息：'.var_export($all_users,TRUE));
		exit(json_encode($all_users));
		// $groups = easemob_get_groups();
		// logger('全部环信群组信息：'.var_export($groups,TRUE));
	}
	// 同步好友关系
	public function sysc_friends(){
		$app_user = D('app_user');
		$sid = session('sid');
		$where = array(
			'sid' => $sid,
			'username' => array('neq','')
		);
		$colleagues = $app_user->where($where)->field('uid,store_simple_name,username')->select(); //同事关系
		$colleagues[] = array(
			'uid' => 2078,
			'store_simple_name' => 'aaa',
			'username' => 'secretary'
		);
		$relation = array();
		$n = 0;
		foreach($colleagues as $k => $v){
			foreach($colleagues as $x => $y){
				if($x != $k){
					$relation[$n]['uid'] = $v['uid'];
					$relation[$n]['aname'] = $v['store_simple_name'].'_'.$v['username'];
					$relation[$n]['fid'] = $y['uid'];
					$relation[$n]['fname'] = $y['store_simple_name'].'_'.$y['username'];
					$relation[$n]['ctime'] = time();
					$n++;
				}
			}
		}
		$model = new Model();
		$sql_result = $model->execute('TRUNCATE ylou.easemob_friends');
		// logger('同步数组：'.var_export($relation,TRUE));  //debug
		$easemob_friends = D('easemob_friends');
		$result = $easemob_friends->addAll($relation);
		if($result){
			echo "success\n";
		}else{
			echo "failed\n";
		}
	}
	//清空用户的所以好友关系
	public function rm_all_friends(){
		// $rmfriend = easemob_rm_friend('aermei_dapeng','aermei_dapeng'); 
		// if($rmfriend['error'] == ''){
		// 	logger('清除环信双向好友关系成功！');
		// 	echo 'success!'."\n";
		// }else{
		// 	logger('清除环信双向好友关系失败！');
		// 	echo 'failed!'."\n";
		// }
		// die;

		logger('清除用户的所有好友关系---环信双向');
		$post = I();
		$username = $post['name'];
		//先查看所有好友
		$all_friends = easemob_get_friends($username);
		logger($username.' 用户所有的好友：'.var_export($all_friends,TRUE)); //debug
		exit(json_encode($all_friends));
		die;
		//清楚所有好友
		$relation = D('easemob_friends');
		foreach($all_friends['data'] as $v){
			M()->startTrans();
			$where = array(
				'aname' => $username,
				'fname' => $v
			);
			$del = $relation->where($where)->delete();
			if($del){
				$where = array(
					'aname' => $v,
					'fname' => $username
				);
				$del = $relation->where($where)->delete();
				if($del){
					$rmfriend = easemob_rm_friend($username,$v); 
					if($rmfriend['error'] == ''){
						logger($username.'==='.$v.'清除环信双向好友关系成功！');
						echo 'success!';
						M()->commit();
					}else{
						logger($username.'==='.$v.'清除环信双向好友关系失败！');
						echo 'failed 3!';
						M()->rollback();
					}
				}else{
					logger($username.'==='.$v.'删除好友关系单向2时失败！');
					echo 'failed 2!';
					M()->rollback();
				}
			}else{
				logger($username.'==='.$v.'删除好友关系单向1时失败！');
				echo 'failed 1!';
				M()->rollback();
			}
		}
		echo '清除用户的所有好友关系____处理完毕！';
		logger('清除用户的所有好友关系____处理完毕！'."\n");
	}
////////////////////////////////////////////////////////////////////////////////
	//// test
	public function test(){
		logger('-- TEST --');
		$users = array(
			0 => array(
				'username' => '',
				'age' => 0
			),
			1 => array(
				'username' => '我是谁',
				'age' => 1
			),
			2 => array(
				'username' => 'a19889azji',
				'age' => 2
			),
			3 => array(
				'username' => NULL,
				'age' => 3
			),
			4 => array(
				'username' => 'lading',
				'age' => 4
			),
			5 => array(
				'username' => '我abc',
				'age' => 5
			),
			6 => array(
				'username' => ')jj',
				'age' => 6
			),
			7 => array(
				'username' => '12a我jlj0',
				'age' => 7
			),
			8 => array(
				'username' => '*11a',
				'age' => 8
			),
			9 => array(
				'username' => '我a们',
				'age' => 9
			),
			10 => array(
				'username' => 'yalll98*',
				'age' => 10
			),
			11 => array(
				'username' => 'fisher',
				'age' => 11
			),
			12 => array(
				'username' => 'fisher2',
				'age' => 12
			),
		);
		$olduser = array(
			0 => array(
				'username' => '',
				'age' => 0
			),
			1 => array(
				'username' => '我是谁',
				'age' => 1
			),
			2 => array(
				'username' => 'a19889azji',
				'age' => 2
			),
			3 => array(
				'username' => NULL,
				'age' => 3
			),
			4 => array(
				'username' => 'lading',
				'age' => 4
			),
			5 => array(
				'username' => '我abc',
				'age' => 5
			),
			6 => array(
				'username' => ')jj',
				'age' => 6
			),
			7 => array(
				'username' => '12a我jlj0',
				'age' => 7
			),
			8 => array(
				'username' => '*11a',
				'age' => 8
			),
			9 => array(
				'username' => '我a们',
				'age' => 9
			),
			10 => array(
				'username' => 'yalll98*',
				'age' => 10
			),
		);
		// $new = $this->user_filter($users);
		// exit(json_encode($new));
		$max = count($olduser); //有效老员工数
		foreach($users as $k => $v){
			$i = 1;
			foreach($olduser as $x => $y){
				if($v['username'] != $y['username']){
					if($i == $max){
						echo '超级管理员:发现店铺新员工！'.$v['username']."<br>";
					}
					$i++;
				}
			}
		}
		$max = count($users); //有效老员工数
		foreach($olduser as $k => $v){
			$i = 1;
			foreach($users as $x => $y){
				if($v['username'] != $y['username']){
					if($i == $max){
						echo '超级管理员:发现店铺lizhi员工！'.$v['username']."<br>";
					}
					$i++;
				}
			}
		}
		logger('-- END --');
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
    public function sql_test(){
    	$friends = D('easemob_friends');
    	$user['uid'] = 147;
    	$map['_string'] = 'uid = '.$user['uid'].' OR fid = '.$user['uid'];
    	$find_friend_relation_result = $friends->where($map)->select();
    	exit(json_encode($find_friend_relation_result));
    }
}
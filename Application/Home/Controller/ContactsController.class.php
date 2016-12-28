<?php
namespace Home\Controller;

use Think\Controller;
use Org\Net\Http;

class Contactscontroller extends Controller{
	public function _initialize(){
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}
	public function index(){

	}
	//查询联系人信息
	public function query(){
		$appuser = session('appuser');
		logger($appuser.'请求联系人信息');
		$sid = session('sid');
		$user = D('app_user');
		$where = array(
			'sid' => $sid,
			'username' => array('neq','')
		);
		$result = $user->where($where)->order('uid asc')->select();
		if($result){
			// 处理头像
			foreach($result as $k => $v){
				if(strpos($v['head'],'Uploads/avatar/')){
					$result[$k]['head'] = C('base_url').$v['head'];
				}
			}
			$data = array(
				'result' => $result,
				'code' => 1,
				'message' => '联系人返回成功'
			);
			logger('联系人返回成功');
			exit(json_encode($data));
		}else{
			$data = array(
				'code' => '0',
				'message' => '联系人信息为空'
			);
		}
	}
	//联系人信息更新，数据来源为APP用户设置;头像、昵称、手机号码、生日,单一更新
	public function update(){
		logger('修改联系人信息--开始');
		$post = I();
		$head = $post['head'];
		$nickname = $post['nickname'];
		$mobile = $post['mobile'];
		$birth = strtotime($post['birth']);
		if($head || $nickname || $mobile || $birth){
			$user = D('app_user');          //暂时先更新到APP用户表，未更新到store_admin表
			$where = array(
				'uid' => session('uid')
			);
			$updata = array(
				'head' => $head,
				'nickname' => $nickname,
				'mobile' => $mobile,
				'birth' => $birth
			); 
			$updata = array_filter($updata);
			$result = $user->where($where)->save($updata);
			// $sql = $user->getLastSql(); //debug
			// logger('sql:'.$sql); //debug
			if($result){
				logger("更新用户信息成功\n");
				$user_info = $user->where($where)->field('password,modify_time',TRUE)->find();
				$data = array(
					'code' => 1,
					'message' => '更新用户信息成功',
					'result' => $user_info
				);
				exit(json_encode($data));
			}else{
				logger("更新用户信息失败\n");
				$data = array(
					'code' => 0,
					'message' => '更新用户信息失败'
				);
				exit(json_encode($data));
			}

		}else{
			logger("未提交任何数据\n");
			$data = array(
				'code' => 2,
				'message' => '未提交任何数据'
			);
			exit(json_encode($data));
		}
	}
	public function upload_avatar(){
		logger('修改个人头像...');
		$upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg','gif','png','jpeg');
        $upload->rootPath = './Uploads/';
        $upload->autoSub = false; //禁用自动目录
        $upload->subName = ''; //自动目录设置为空，原来为日期
        $upload->savePath = 'avatar/';  //头像目录
        $info = $upload->upload();
        // logger('info:'.var_export($info,TRUE)); //debug
        if(!$info){
            $data = array(
                'code' => 0,
                'content' => '头像上传失败！'
            );
            logger('头像上传失败，原因:'.$upload->getError());
        }else{
        	logger("头像上传成功-->下面将头像信息存入数据库用户表中");
            $savepath = $info['file']['savepath'];
            $savename = $info['file']['savename'];
            // $savesize = $info['size'];
            //生成缩略图
            // $img= new \Think\Image();
            // $img->open('./Uploads/'.$savepath.$savename);
            // $img->thumb(200,200)->save('./Uploads/'.$savepath.'S'.$savename);
            $path = '/Uploads/'.$savepath.$savename;
            $avatar = array(
	            'head' => $path,
	            'modify_time' => time()
	        );
	        $users = D('app_user');
	        $where = array(
	        	'uid' => session('uid')
	        );
	        $old_avatar = $users->where($where)->field('head')->find();
	        if($old_avatar){ // 删除原图像
	        	@unlink('.'.$old_avatar['head']);
	        }
            $result = $users->where($where)->save($avatar);
            if($result){
            	$data = array(
	                'code' => 1,
	                'content' => '头像上传成功！',
	                'head' => C('base_url').$path
	            );
            }else{
            	$data = array(
	                'code' => 2,
	                'content' => '头像上传失败！'
	            );
            }
        }
        exit(json_encode($data));
	}
	public function avatar_local_init(){
		logger('用户头像本地化');
		$user = D('app_user');
		// $where = array(
		// 	'head' => array('neq','')
		// );
		$where = array(
			'uid' => 135
		);
		$users = $user->where($where)->field('uid,head')->limit()->select();
		if($users){
			logger('集齐已设置头像的用户组-成功...');
			// logger(var_export($users,TRUE)); //debug
			foreach($users as $k => $v){
				if(substr($v['head'],-5) == '-file'){
					$name = $v['uid'].'.png';
				}elseif(substr($v['head'],-4,1) == '.'){
					$name = $v['uid'].substr($v['head'],-4);
				}elseif(substr($v['head'],-5,1) == '.'){
					$name = $v['uid'].substr($v['head'],-5);
				}else{
					$name = $v['uid'].substr($v['head'],-9);
				}
				$local = '/Uploads/avatar/'.$name;
				// logger($local); //debug
				Http::curlDownload($v['head'],'.'.$local);
				$condition = array(
					'uid' => $v['uid']
				);
				$update_data = array(
					'head' => $local,
					'modify_time' => time()
				);
				$update_result = $user->where($condition)->save($update_data);
				if($update_result){
					logger('用户：'.$v['uid'].'头像本地初始化完成！');
				}else{
					logger('用户：'.$v['uid'].'头像本地初始化failed！');
					@unlink($local);
				}
			}
			echo 'success!';
		}else{
			logger('集齐已设置头像的用户组-失败...');
			echo 'failed!';
		}
	}
}
?>
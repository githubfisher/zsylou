<?php
namespace Home\Controller;

use Think\Controller;

class FileController extends Controller
{
	public function _initialize()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}

	public function lists()
	{
        return true;
	}

	public function upload()
	{
        $get = I();
        $pid = $get['pid'];
        if(!$pid){
            logger('上传文件，参数不全，失败！'."\n");
            $data = array(
                'code' => 2,
                'message' => '参数不全，请重试！'
            );
            exit(json_encode($data));
        }
		logger('任务-上传文件...');

		$upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('bmp','tif','jpg','gif','png','jpeg','txt','rar','zip','pdf','html','htm','xls','doc','rtf','wps');
        $upload->rootPath = './Uploads/';
        $upload->autoSub = false; //禁用自动目录
        $upload->subName = ''; //自动目录设置为空，原来为日期
        $upload->savePath = 'taskfile/';  //文件目录
        // $upload->saveName = ''; // 保存原文件名不变
        $info = $upload->upload();
        // logger('任务-上传文件,文件信息:'.var_export($info,TRUE)); //debug
          // array(
		  //    'file' => array (
		  //              'name' => 'header.jpg',
		  //              'type' => 'image/png',
		  //              'size' => 152119,
		  //              'key' => 'file',
		  //              'ext' => 'jpg',
		  //              'md5' => 'f1d15aa781c34b6e4c87b34f6f6f8c3d',
		  //              'sha1' => '650785bc22450c60adf9a42d2502c1e4cc4bd1e9',
		  //              'savename' => '582b10730bf2a.jpg',
		  //              'savepath' => 'avatar/',
		  //          )
          // )
        if(!$info){
            $data = array(
                'status' => 0,
                'content' => '上传失败！'
            );
            logger('任务-上传文件，上传失败，原因:'.$upload->getError()."\n");
        }else{
        	logger("任务-上传文件,上传成功-->下面将文件信息存入数据表中");
            if($this->fileHandler($info,$pid)){
            	$data = array(
	                'code' => 1,
	                'message' => '上传成功！'
	            );
	            logger('任务-上传文件，上传成功！'."\n");
            }else{
            	$data = array(
	                'code' => 3,
	                'message' => '上传失败！'
	            );
	            logger('任务-上传文件，文件处理失败！'."\n");
            }
        }
        exit(json_encode($data));
	}

	public function update()
	{
        return true;
	}

    // 文件详情
    public function detail()
    {
        logger('查看文件详情...');
        $get = I();
        $id = $get['id'];
        if($id){
            $file = $this->get_detail($id);
            if($file['delete_at'] == 0){
                $file['dynamic'] = $this->get_dynamic($id);
                $file['discuss'] = $this->get_discuss($id);
                $data = array(
                    'code' => 1,
                    'message' => '任务详情返回成功！',
                    'result' => $file
                );
                logger('任务详情返回成功！'."\n");
            }else{
                logger('文件已经删除，不能返回文件详情！'."\n");
                $data = array(
                    'code' => 3,
                    'message' => '文件已经删除！'
                );
            }
        }else{
            logger('查询任务详情参数不全！'."\n");
            $data = array(
                'code' => 2,
                'message' => '参数不全，请重试！'
            );
        }
        exit(json_encode($data));
    }

    // 删除文件
	public function delete()
	{
        $get = I();
        $id = $get['id'];
        $pid = $get['pid'];
        logger(session('uid').'删除文件'.$id.'....');

        if($id && $pid){
            $file = D('file');
            M()->startTrans();
            if($file->where(array('id'=>$id))->save(array('delete_at'=>time()))){
                logger('删除文件记录成功！');
                $info = array(
                    'pid' => $pid,
                    'sid' => session('sid'),
                    'type' => 2,
                    'item' => $id,
                    'uid' => session('uid'),
                    'time' => time(),
                    'content' => session('admin_nickname').' 删除了文件'
                );
                $dynamic = D('dynamic');
                if($dynamic->add($info)){
                    logger('添加删除文件动态记录成功！'."\n");
                    M()->commit();
                    $data = array(
                        'code' => 1,
                        'message' => '删除成功！'
                    );
                    // 删除原始文件
                    $the_file = $this->get_detail($id);
                    @unlink('.'.ltrim($the_file['uri'],C('base_url')));
                    if($the_file['type'] == 1){ // 图片文件还要删除缩略图
                        @unlink('.'.ltrim($the_file['thumb'],C('base_url')));
                    }
                }else{
                    logger('添加删除文件动态记录失败，回滚'."\n");
                    M()->rollback();
                    $data = array(
                        'code' => 3,
                        'message' => '删除失败，请重试！'
                    );
                }
            }else{
                logger('删除文件记录失败！'."\n");
                M()->rollback();
                $data = array(
                    'code' => 0,
                    'message' => '删除失败，请重试！'
                );
            }
        }else{
            logger('删除文件参数不全！'."\n");
            $data = array(
                'code' => 2,
                'message' => '参数不全，请重试！'
            );
        }
        exit(json_encode($data));
	}

    // 修改文件名称
	public function rename()
	{
        $get = I();
        $id = $get['id'];
        $pid = $get['pid'];
        $name = $get['name'];
        logger(session('uid').'重命名文件'.$get['id'].'....');

        if($id && $pid && $name){
            $file = D('file');
            $theFile = $this->get_detail($id);
            M()->startTrans();
            if($file->where(array('id'=>$id))->save(array('name'=>$name))){
                logger('修改文件名称成功！');
                $info = array(
                    'pid' => $pid,
                    'sid' => session('sid'),
                    'type' => 2,
                    'item' => $id,
                    'uid' => session('uid'),
                    'time' => time(),
                    'content' => session('admin_nickname').' 将文件 "'.$theFile['name'].'" 修改为 '.$name
                );
                $dynamic = D('dynamic');
                if($dynamic->add($info)){
                    logger('添加文件重命名动态记录成功！'."\n");
                    M()->commit();
                    $data = array(
                        'code' => 1,
                        'message' => '重命名成功！'
                    );
                }else{
                    logger('添加文件重命名动态记录失败，回滚'."\n");
                    M()->rollback();
                    $data = array(
                        'code' => 3,
                        'message' => '重命名失败，请重试！'
                    );
                }
            }else{
                logger('修改文件名称失败！'."\n");
                M()->rollback();
                $data = array(
                    'code' => 0,
                    'message' => '重命名失败，请重试！'
                );
            }
        }else{
            logger('重命名文件参数不全！'."\n");
            $data = array(
                'code' => 2,
                'message' => '参数不全，请重试！'
            );
        }
        exit(json_encode($data));
	}

    // 移动文件到其他项目
	public function move()
	{
        $get = I();
        $id = $get['id'];
        $pid = $get['pid'];
        $toproject = $get['toproject'];
        logger(session('uid').'移动文件'.$get['id'].'....');

        if($id && $pid && $name){
            $file = D('file');
            $theFile = $this->get_detail($id);
            M()->startTrans();
            if($file->where(array('id'=>$id))->save(array('pid'=>$toproject))){
                logger('移动文件记录成功！');
                $info = array(
                    'pid' => $pid,
                    'sid' => session('sid'),
                    'type' => 2,
                    'item' => $id,
                    'uid' => session('uid'),
                    'time' => time(),
                    'content' => session('admin_nickname').' 移动了文件'
                );
                $dynamic = D('dynamic');
                if($dynamic->add($info)){
                    logger('添加移动文件动态记录成功！'."\n");
                    M()->commit();
                    $data = array(
                        'code' => 1,
                        'message' => '移动成功！'
                    );
                }else{
                    logger('添加移动文件动态记录失败，回滚'."\n");
                    M()->rollback();
                    $data = array(
                        'code' => 3,
                        'message' => '移动失败，请重试！'
                    );
                }
            }else{
                logger('移动文件记录失败！'."\n");
                M()->rollback();
                $data = array(
                    'code' => 0,
                    'message' => '移动失败，请重试！'
                );
            }
        }else{
            logger('移动文件参数不全！'."\n");
            $data = array(
                'code' => 2,
                'message' => '参数不全，请重试！'
            );
        }
        exit(json_encode($data));
	}

	// 上传文件处理
	private function fileHandler($info,$pid){
		$savePath = $info['file']['savepath'];
        $saveName = $info['file']['savename'];
        $fileName = $info['file']['name'];
        $fileType = $info['file']['type'];
        $fileExt = $info['file']['ext'];
        $filePath = '/Uploads/'.$savePath.$saveName;

        if(strpos($fileType,'mage')){
        	//生成图片文件缩略图
            $img= new \Think\Image();
            $img->open('.'.$filePath);
            $thumbPath = '/Uploads/'.$savePath.'S'.$saveName;
            $img->thumb(58,58)->save('.'.$thumbPath);
            $fileType = 1;
        }else{
        	$fileType = 2;
        	switch($fileExt){
        		case 'txt':
        		case 'rtf':
        			$thumbPath = '/Uploads/icon/txt.png';
        			break;
        		case 'rar':
        		case 'zip':
        			$thumbPath = '/Uploads/icon/zip.png';
        			break;
        		case 'html':
        		case 'htm':
        			$thumbPath = '/Uploads/icon/html.png';
        			break;
        		case 'pdf':
        			$thumbPath = '/Uploads/icon/pdf.png';
        			break;
        		case 'doc':
        			$thumbPath = '/Uploads/icon/doc.png';
        			break;
        		case 'xls':
        			$thumbPath = '/Uploads/icon/execl.png';
        			break;
        		case 'wps':
        			$thumbPath = '/Uploads/icon/wps.png';
        			break;
        		default:
        			$thumbPath = '/Uploads/icon/default.png';
        			break;
        	}
        }

        $file = array(
            'uri' => C('base_url').$filePath,
            'thumb' => C('base_url').$thumbPath,
            'name' => $fileName,
            'type' => $fileType,
            'upload_at' => time(),
            'upload_user' => session('uid'),
            'pid' => $pid
        );
        $file = D('file');
        M()->startTrans();
        $result = $file->add($file);
        if($result){
        	logger('文件记录写入成功！');
        	$info = array(
        		'pid' => $pid,
				'sid' => session('sid'),
				'type' => 2,
				'item' => $result,
				'uid' => session('uid'),
				'time' => time(),
				'content' => session('admin_nickname').' 上传了文件'
        	);
        	$dynamic = D('dynamic');
			if($dynamic->add($info)){
				logger('写入上传文件动态记录成功！');
				M()->commit();
				return true;
			}else{
				logger('写入上传文件动态记录失败失败!'."\n");
				M()->rollback();
                // 删除文件
                @unlink($filePath);
                @unlink($thumbPath);
				return false;
			}
        }else{
        	M()->rollback();
        	logger('文件记录写入失败失败！'."\n");
            // 删除文件
            @unlink($filePath);
            @unlink($thumbPath);
        	return false;
        }
	}

    // 获取文件的基本信息
    private function get_detail($id)
    {
        $FD = D('FileDetail');
        $detail = $FD->where(array('id'=>$id))->cache(true,60)->find();
        if(strpos($detail['head'],'/Uploads/avatar/') === 0){
            $detail['head'] = C('base_url').$detail['head'];
        }
        return $detail;
    }

    // 获取文件评论
    private function get_discuss($id)
    {
        $FD = D('FileDiscuss');
        $discusses = $FD->where(array('item'=>$id,'type'=>2))->order('create_at asc')->cache(true,60)->select();
        // 处理头像
        if(count($discusses) >= 1){
            foreach($discusses as $k => $v){
                if(strpos($v['head'],'/Uploads/avatar/') === 0){
                    $discusses[$k]['head'] = C('base_url').$discusses[$k]['head'];
                }
            }
        }

        return $discusses;
    }

    // 获取文件动态
    private function get_dynamic($id)
    {
        $FD = D('FileDynamic');
        $disc = $FD->where(array('id'=>$id,'type'=>2))->order('time asc')->cache(true,60)->select();
        // 处理头像
        if(count($disc) >= 1){
            foreach($disc as $k => $v){
                if(strpos($v['head'],'/Uploads/avatar/') === 0){
                    $disc[$k]['head'] = C('base_url').$disc[$k]['head'];
                }
            }
        }

        return $disc;
    }
}
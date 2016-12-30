<?php
namespace Home\Controller;

use Think\Controller;

class DiscussController extends Controller
{
	public function _initialize()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}

	public function onlyFile()
	{
		logger('发表评论，仅包含图片或文件....');
		$get = I();
		$pid = $get['pid'];
		$type = $get['type'];
		$item = $get['item'];
		if($pid && $type && $item){
			// 接收上传文件
			$upload = new \Think\Upload();
	        $upload->maxSize = 3145728;
	        $upload->exts = array('bmp','tif','jpg','gif','png','jpeg','txt','rar','zip','pdf','html','htm','xls','doc','rtf','wps');
	        $upload->rootPath = './Uploads/';
	        $upload->autoSub = false; //禁用自动目录
	        $upload->subName = ''; //自动目录设置为空，原来为日期
	        $upload->savePath = 'discussfile/';  //文件目录
	        $info = $upload->upload();
	        if(!$info){
	            $data = array(
	                'status' => 0,
	                'content' => '上传失败，请重试！'
	            );
	            logger('评论-上传文件，上传失败，原因:'.$upload->getError()."\n");
	        }else{
	        	// 处理上传文件或图片
	        	$this->fileHandler($info,$pid,$type,$item);
	        }
		}else{
			logger('上传评论文件或图片，参数不全，失败！'."\n");
            $data = array(
                'code' => 2,
                'message' => '参数不全，请重试！'
            );
		}
		exit(json_encode($data));
	}

	public function onlyText()
	{
		logger('发表评论，仅包含文字....');
		$get = I();
		$pid = $get['pid'];
		$type = $get['type'];
		$item = $get['item'];
		$content = $get['content'];
		if($pid && $type && $item && $content){
			$disc = array(
				'pid' => $pid,
				'uid' => session('uid'),
				'type' => $type,
				'item' => $item,
				'create_at' => time(),
				'content' => $content
			);
			M()->startTrans();
			$discuss = D('discuss');
			if($discuss->add($disc)){
				logger('发布文字评论成功！');
				$info = array(
	        		'pid' => $pid,
					'sid' => session('sid'),
					'type' => $type,
					'item' => $item,
					'uid' => session('uid'),
					'time' => time()
	        	);
	        	if($type == 1){
	        		$info['content'] = session('admin_nickname').' 回复了任务';
	        	}else{
	        		$info['content'] = session('admin_nickname').' 回复了文件';
	        	}
	        	$dynamic = D('dynamic');
				if($dynamic->add($info)){
					logger('写入文字评论动态记录成功！'."\n");
					M()->commit();
					$data = array(
						'code' => 1,
						'message' => '评论成功！',
					);
				}else{
					logger('写入文字评论动态记录失败失败！'."\n");
					M()->rollback();
					$data = array(
						'code' => 3,
						'message' => '评论失败！',
					);
				}
			}else{
				logger('发布文字评论失败失败！'."\n");
				M()->rollback();
				$data = array(
					'code' => 0,
					'message' => '评论失败！',
				);
			}
		}else{
			logger('上传评论文件或图片，参数不全，失败！'."\n");
            $data = array(
                'code' => 2,
                'message' => '参数不全，请重试！'
            );
		}
		exit(json_encode($data));
	}

	public function textWithAt()
	{
		logger('发表评论，仅包含文字....');
		$get = I();
		$pid = $get['pid'];
		$type = $get['type'];
		$item = $get['item'];
		$content = $get['content'];
		$at = $get['at']; // json 

		if($pid && $type && $item && $content){
			Vendor('Jpush2.Client');
			Vendor('Jpush2.core.Config');
			Vendor('Jpush2.core.JPushException');
			Vendor('Jpush2.core.APIConnectionException');
			Vendor('Jpush2.core.APIRequestException');
			Vendor('Jpush2.core.Http');
			Vendor('Jpush2.core.DevicePayload');
			Vendor('Jpush2.core.PushPayload');
			Vendor('Jpush2.core.ReportPayload');
			Vendor('Jpush2.core.SchedulePayload');
			$disc = array(
				'pid' => $pid,
				'uid' => session('uid'),
				'type' => $type,
				'item' => $item,
				'create_at' => time(),
				'content' => $content
			);
			$discuss = D('discuss');
			M()->startTrans();
			if($discuss->add($disc)){
				logger('写入文字评论记录成功！');
				// 写入评论动态
				$info = array(
	        		'pid' => $pid,
					'sid' => session('sid'),
					'type' => $type,
					'item' => $item,
					'uid' => session('uid'),
					'time' => time()
	        	);
	        	if($type == 1){
	        		$info['content'] = session('admin_nickname').' 回复了任务';
	        	}else{
	        		$info['content'] = session('admin_nickname').' 回复了文件';
	        	}
	        	$dynamic = D('dynamic');
				if($dynamic->add($info)){
					logger('写入文字评论动态记录成功！');
				}else{
					logger('写入文字评论动态记录失败失败！'."\n");
					M()->rollback();
					$data = array(
						'code' => 4,
						'message' => '评论失败！',
					);
				}
				// 处理评论中的@
				$at = chanslate_json_to_array($at);
				if(count($at) >= 1){
					$ats = D('at');
					foreach($at as $k){
						$array[] = array(
							'pid' => $pid,
							'sid' => session('sid'),
							'uid' => $k,
							'type' => $type,
							'item' => $item,
							'create_at' => time(),
							'from_user' => session('uid')
						);
					}
					if($ats->addAll($array)){
						// 发送消息
						logger('发送@消息....');
						$this->atHandler($at,$type,$item,$content);
						$status = true;
					}else{
						$status = false;
					}
				}else{
					$status = true; // 如果解析出错，直接发布评论
				}
				if($status){
					M()->commit();
					logger('处理评论中的@成功，评论成功！'."\n");
					$data = array(
						'code' => 1,
						'message' => '评论成功！',
					);
				}else{
					M()->rollback();
					logger('处理评论中的@失败，评论失败！'."\n");
					$data = array(
						'code' => 3,
						'message' => '评论失败，请重试！',
					);
				}
			}else{
				M()->rollback();
				logger('写入文字评论记录失败失败！'."\n");
				$data = array(
					'code' => 0,
					'message' => '评论失败，请重试！',
				);
			}
		}else{
			logger('上传评论文件或图片，参数不全，失败！'."\n");
            $data = array(
                'code' => 2,
                'message' => '参数不全，请重试！'
            );
		}
		exit(json_encode($data));
	}

	public function withAttachment()
	{
		return true;
	}

	private function atHandler($array,$type,$item,$content)
	{
		$item = $this->get_item($type,$item);
		// 消息数据准备
		$msg = array(
			'platform' => 'all',
			'alias' => $array,
			'msg' => array(
				'content' => session('admin_nickname').':'.$content,
				'title' => $item['name'].' 有人@你',
				'category' => '',
				'message' => array(
					'action' => 7,
					'type' => 1,
					'details' => array(
						'item' => $item,
						'type'=> $type,
					)
				)
			)
		);
		$j_result = jpush($msg);
		logger('JPush---自定义简单发送----结果：'.$j_result.'------完毕------'."\n");
	}

	private function fileHandler($info,$pid,$type,$item)
	{
		$savePath = $info['savepath'];
        $saveName = $info['savename'];
        $fileName = $info['name'];
        $fileType = $info['type'];
        $fileExt = $info['ext'];
        $filePath = '/Uploads/'.$savePath.$saveName;

        if(strpos($fileType,'mage')){
        	//生成图片文件缩略图
            $img= new \Think\Image();
            $img->open('./Uploads/'.$savePath.$saveName);
            $img->thumb(58,58)->save('./Uploads/'.$savePath.'S'.$saveName);

            $thumbPath = '/Uploads/'.$savePath.'S'.$saveName;
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
        // 评论记录信息
        $file = array(
            'am_uri' => C('base_url').$filePath,
            'am_thumb' => C('base_url').$thumbPath,
            'am_name' => $fileName,
            'am_type' => $fileType,
            'create_at' => time(),
            'uid' => session('uid'),
            'pid' => $pid,
            'type' => $type,
            'item' => $item
        );
        M()->startTrans();
        $discuss = D('discuss');
        if($discuss->add($file)){
        	logger('评论上传文件记录写入成功！'."\n");
        	$info = array(
        		'pid' => $pid,
				'sid' => session('sid'),
				'type' => $type,
				'item' => $item,
				'uid' => session('uid'),
				'time' => time()
        	);
        	if($type == 1){
        		$info['content'] = session('admin_nickname').' 回复了任务';
        	}else{
        		$info['content'] = session('admin_nickname').' 回复了文件';
        	}
        	$dynamic = D('dynamic');
			if($dynamic->add($info)){
				logger('写入文件评论动态记录成功！'."\n");
				M()->commit();
				$data = array(
	                'code' => 1,
	                'message' => '评论成功！',
	                'result' => $file
	            );
			}else{
				logger('写入文字评论动态记录失败失败！'."\n");
				M()->rollback();
				$data = array(
					'code' => 4,
					'message' => '评论失败！',
				);
			}
        }else{
        	logger('评论上传文件记录写入失败失败！'."\n");
        	M()->rollback();
            // 删除文件
            @unlink($filePath);
            if($fileType == 1){
            	@unlink($thumbPath);
            }
        	$data = array(
                'code' => 3,
                'message' => '评论失败，请重试！'
            );
        }
        exit(json_encode($data));
	}

	private function get_item($type,$id)
	{
		if($type == 1){
			$task = D('task');
			return $task->where(array('id'=>$id))->field('name')->find();
		}else{
			$file = D('file');
			return $file->where(array('id'=>$id))->field('name')->find();
		}
	}
}
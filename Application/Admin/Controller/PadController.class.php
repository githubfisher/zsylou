<?php
namespace Admin\Controller;
use Think\Controller;
class PadController extends Controller {
	public function _initialize(){
		if(!session('?name')){
			$this->redirect('Admin/login/redirect',array(),0.1,'请登录。。。');
		}
		header("content-type:text/html; charset=utf-8;");
        Vendor('Easemob.Easemob');
	}
	//主题管理
	public function list_ct(){
		logger('高级管理员:clothes or theme展示');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		switch($post['type']){
			case 'theme':
				$text = '主题';
				$tab = D('theme');
				$category = D('theme_category');
				$page = 'list_theme';
				$condition = 'theme a,theme_category b';
				break;
			case 'clothes':
				$text = '服装';
				$tab = D('clothes');
				$category = D('clothes_category'); 
				$page = 'list_clothes';
				$condition = 'clothes a,clothes_category b';
				break;
			default:
				break;
		}
		logger('高级管理员:'.$text.'展示');
		$where = array(
			'sid' => session('sid')
		);
		$type = $category->where($where)->select();
		//if show some type or all types
		if($post['id']){
			logger('高级管理员:查询特定类型的主题 '.$post['id']);
			$result = $tab->table($condition)->where('a.style = b.id AND a.sid = '.session('sid').' AND a.style = '.$post['id'])->field('a.*,b.type')->select();
			// $sql = $tab->getLastsql(); //debug
			// logger('查询语句：'.$sql); //debug
		}else{
			$result = $tab->table($condition)->where('a.style = b.id AND a.sid = '.session('sid'))->field('a.*,b.type')->select();
			// $sql = $tab->getLastsql(); //debug
			// logger('查询语句：'.$sql); //debug
		}
		// logger(var_export($result,TRUE)); //debug
		logger('高级管理员:返回结果成功！'."\n");
		$this->assign('type',$type);
		$this->assign('ct',$result);
		$this->display($page);
	}
	//create new clothes or theme type
	public function new_ct_category(){
		logger('高级管理员:新建clothes or theme类型');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		switch($post['style']){
			case 'theme':
				$text = '主题';
				$category = D('theme_category');
				break;
			case 'clothes':
				$text = '服装';
				$category = D('clothes_category'); 
				break;
			default:
				break;
		}
		logger('高级管理员:新建'.$text.'类型');
		if($post['type']){
			$data = array(
				'type' => $post['type'],
				'create_time' => time(),
				'create_admin' => session('name'),
				'sid' => session('sid')
			);
			$result = $category->add($data);
			if($result){
				$ajax_data = array(
					'status' => 1,
					'content' => '新建'.$text.'类型成功！'
				);
				logger('高级管理员：新建'.$text.'类型成功！'."\n");
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '新建'.$text.'类型失败！'
				);
				logger('高级管理员：新建'.$text.'类型失败！'."\n");
			}
		}else{
			$ajax_data = array(
				'status' => 2,
				'content' => '参数不全！'
			);
			logger('高级管理员：参数不全'."\n");
		}
		$this->ajaxReturn($ajax_data);
	}
	//create new clothes or theme
	public function new_ct(){
		logger('高级管理员:新建clothes or theme');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		switch($post['style']){
			case 'theme':
				$text = '主题';
				$tab = D('theme');
				break;
			case 'clothes':
				$text = '服装';
				$tab = D('clothes');
				break;
			default:
				break;
		}
		logger('高级管理员:新建'.$text);
		if($post['type'] && $post['name']){
			$data = array(
				'name' => $post['name'],
				'style' => $post['type'],
				'remark' => $post['remark'],
				'create_time' => time(),
				'create_admin' => session('name'),
				'sid' => session('sid')
			);
			$result = $tab->add($data);
			if($result){
				$ajax_data = array(
					'status' => 1,
					'content' => '新建'.$text.'成功！'
				);
				logger('高级管理员：新建'.$text.'成功！'."\n");
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '新建'.$text.'失败！'
				);
				logger('高级管理员：新建'.$text.'失败！'."\n");
			}
		}else{
			$ajax_data = array(
				'status' => 2,
				'content' => '参数不全！'
			);
			logger('高级管理员：参数不全'."\n");
		}
		$this->ajaxReturn($ajax_data);
	}
	//delete clothes or theme
	public function del_ct(){
		logger('高级管理员:删除clothes or theme');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		switch($post['style']){
			case 'theme':
				$text = '主题';
				$tab = D('theme');
				break;
			case 'clothes':
				$text = '服装';
				$tab = D('clothes');
				break;
			default:
				break;
		}
		logger('高级管理员:删除'.$text);
		if($post['id']){
			$where = array(
				'id' => $post['id']
			);
			$result = $tab->where($where)->delete();
			if($result){
				$ajax_data = array(
					'status' => 1,
					'content' => '删除'.$text.'成功！'
				);
				logger('高级管理员：删除'.$text.'成功！'."\n");
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '删除'.$text.'失败！'
				);
				logger('高级管理员：删除'.$text.'失败！'."\n");
			}
		}else{
			$ajax_data = array(
				'status' => 2,
				'content' => '参数不全！'
			);
			logger('高级管理员：参数不全'."\n");
		}
		$this->ajaxReturn($ajax_data);
	}
	//show edit page or save edit data
	public function edit_ct_edit(){
		$post = I();
		logger('修改主题-传入参数：'.var_export($post,TRUE)); //debug
		switch($post['style']){
			case 'theme':
				$text = '主题';
				$table = D('theme');
				$category = D('theme_category');
				$page = 'edit_theme';
				break;
			case 'clothes':
				$text = '服装';
				$table = D('clothes');
				$category = D('clothes_category'); 
				$page = 'edit_clothes';
				break;
			default:
				break;
		}
		logger('高级管理员:修改'.$text);
		if($post['id'] && ($post['name'] || $post['type'] || $post['remark'])){
			logger('高级管理员:修改'.$text);
			$data = array(
				'name' => $post['name'],
				'style' => $post['type'],
				'remark' => $post['remark'],
				'modify_time' => time(),
				'modify_admin' => session('name')
			);
			$data = array_filter($data);
			$where = array(
				'id' => $post['id']
			);
			$result = $table->where($where)->save($data);
			if($result){
				$ajax_data = array(
					'status' => 1,
					'content' => '修改'.$text.'成功！'
				);
				logger('高级管理员：修改'.$text.'成功！'."\n");
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '修改'.$text.'失败！'
				);
				logger('高级管理员：修改'.$text.'失败！'."\n");
			}
			$this->ajaxReturn($ajax_data);
		}else if(!$post['id']){
			logger('高级管理员:参数不全！');
			$this->error('提交参数不全，请重试！');
		}else{
			logger('高级管理员:显示修改'.$text.'页面'."\n");
			$where = array(
				'id' => $post['id']
			);
			$result = $table->where($where)->find();
			$condition = array(
				'sid' => session('sid')
			);
			$type = $category->where($condition)->select();
			$imgs = trim($result['sample'],' ');
			if($imgs == '' || $imgs == NULL){
				$num = 0;
				$imgs = array();
			}else{
				$imgs = explode(' ',$imgs);
				$num = count($imgs);
			}
			logger('图片记录："'.$result['sample'].'"'); //debug
			logger('图片数量：'.$num); //debug
			logger('产品数组：'.var_export($imgs,TRUE)); //debug
			$zone = $this->list_picture(TRUE);
			$this->assign('type',$type); //全部类型
			$this->assign('ct',$result); //个体内容
			$this->assign('num',$num); //个体图片数量
			$this->assign('sum',$num); //个体图片数量
			$this->assign('imgs',$imgs); //个体图片
			$this->assign('files',$zone['files']); //图片空间
			$this->assign('level',$zone['level']); //图片空间
			$this->assign('path',$zone['path']); //图片空间
			$this->display($page);
		}
	}
	public function upload_img(){
		logger('高级管理员:上传图片');
		header("Content-Type:text/html;charset:utf-8");
        $post = I();
        $folder = $post['folder'];
        $level = $post['level'];
        $status = $post['status'];
        $spath = $post['savepath'];
        if(!$folder && !$level && !$status && !$spath){
        	$folder = session('sid');
        	$level = 0;
        	$status = 0;
        	$spath = session('sid').'/';
        	$exit = 1;
        }
        // logger('上传图片-附带参数：'.var_export($post,TRUE)); //debug
        // logger('文件系统:'.var_export($_FILES,TRUE)); //debug
        logger('高级管理员:上传文件开始--->');
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg','gif','png','jpeg');
        $upload->rootPath = './Uploads/';
        $upload->autoSub = false; //禁用自动目录
        $upload->subName = ''; //自动目录设置为空，原来为日期
        $upload->savePath = $spath;
        $info = $upload->upload();
        // logger('info:'.var_export($info,TRUE)); //debug
        if(!$info){
            $data = array(
                'status' => 0,
                'content' => $upload->getError()
            );
            $this->ajaxReturn($data);
        }else{
        	$i = 0;
            foreach ($info as $v) {
                $savepath = $v['savepath'];
                $savename = $v['savename'];
                $savesize = $v['size'];
                //生成缩略图
	            // $img= new \Think\Image();
	            // $img->open('./Uploads/'.$savepath.$savename);
	            // $img->thumb(200,200)->save('./Uploads/'.$savepath.'S'.$savename);
	            $path = '/Uploads/'.$savepath.$savename;
	            logger("高级管理员:APP封面图片上传处理成功-->下面将图片信息存入数据库");
	            $image = D('image');
	            $imginfo[$i] = array(
	            	'name' => $savename,
	            	'type' => 2,
	            	'folder' => $folder,
	            	'level' => $level+1,
	            	'ctime' => time(),
	            	'cuid' => session('uid'),
	            	'size' => $savesize,
	            	'status' => $status,
	            	'sid' => session('sid'),
	            	'path' => $path
	            );
	            $i++;
            } 
            // logger('图片记录数组:'.var_export($imginfo,TRUE)); //debug
            $result = $image->addAll($imginfo);
            // $sql = $image->getLastsql(); //debug
            // logger('图片记录插入语句:'.$sql); //debug
            if($result){
            	logger('高级管理员:上传图片记录成功!'."\n");
                $data = array(
                    'status' => 1,
                    'url' => $imginfo,
                    'content' => '上传成功!'
                );
            }else{
                logger('高级管理员:上传图片记录失败!'."\n");
                $data = array(
                    'status' => 0,
                    'content' => '上传失败!'
                );
            }
            $msg = 'callback ('.json_encode($data).')';
            if($exit == 1){
            	exit(json_encode($data));
            }else{
            	exit($msg);
            }
        }
	}
	// 保存主题图片修改
	public function edit_img(){
		logger('高级管理员:编辑图片');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		switch($post['type']){
			case 'set':
				$text = '套系';
				$table = D('sets');
				break;
			case 'theme':
				$text = '主题';
				$table = D('theme');
				break;
			case 'product':
				$text = '产品';
				$table = D('product');
				break;
			case 'spot':
				$text = '景点';
				$table = D('spot');
				break;
			case 'clothes':
				$text = '服装';
				$table = D('clothes');
				break;
			default:
				break;
		}
		logger('高级管理员:编辑'.$text.'图片');
		if($post['img'] && $post['id'] && $post['preview']){
			$data = array(
				'sample' => trim($post['img'],' '),
				'preview' => trim($post['preview']),
				'modify_time' => time(),
				'modify_admin' => session('name')
			);
			$where = array(
				'id' => $post['id']
			);
			$result = $table->where($where)->save($data);
			if($result){
				logger('高级管理员：修改'.$text.'图片成功！->下一步给启用图片状态+1'."\n");
				$add_path = explode(' ',trim($post['img'],' '));
				$image = D('image');
				if(!empty($add_path)){
					foreach($add_path as $v){
						$where = array();
						$where = array(
							'sid' => session('sid'),
							'path' => $v
						);
						$data = array();
						$data['status'] += 1;
						$update_image = $image->where($where)->save($data);
						if($update_image){
							logger('高级管理员：更新图片状态+1成功！');
						}else{
							logger('高级管理员：更新图片状态+1失败！');
						}
					}
				}
				$unuse_path = explode(' ',trim($post['unuse'],' '));
				logger('高级管理员：下一步给弃用的图片状态-1');
				if(!empty($unuse_path)){
					foreach($unuse_path as $v){
						$where = array();
						$where = array(
							'sid' => session('sid'),
							'path' => $v
						);
						$data = array();
						$data['status'] -= 1;
						$update_image = $image->where($where)->save($data);
						if($update_image){
							logger('高级管理员：更新图片状态-1成功！');
						}else{
							logger('高级管理员：更新图片状态-1失败！');
						}
					}
				}
				$ajax_data = array(
					'status' => 1,
					'content' => '修改'.$text.'图片成功！'
				);
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '修改'.$text.'图片失败！'
				);
				logger('高级管理员：修改'.$text.'图片失败！'."\n");
			}
		}else{
			logger('高级管理员:参数不全！');
			$ajax_data = array(
				'status' => 2,
				'content' => '参数不全！'
			);
		}
		$this->ajaxReturn($ajax_data);
	}
	//show the list of sets or products or spots
	public function list_sps(){
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		switch($post['type']){
			case 'set':
				$text = '套系';
				$table = D('sets');
				$page = 'list_set';
				break;
			case 'product':
				$text = '产品';
				$table = D('product');
				$page = 'list_product';
				break;
			case 'spot':
				$text = '景点';
				$table = D('spot');
				$page = 'list_spot';
				break;
			default:
				break;
		}
		logger('高级管理员:'.$text.'展示');
		$where = array(
			'sid' => session('sid')
		);
		//选取全部记录
		$result = $table->where($where)->select();
		// 遍历提取类型
		foreach($result as $k => $v){
			if($k == 0){
				$type[0]['type'] = $v['style'];
				continue;
			}else{
				$i = 1;
				$sum = count($type);
				foreach($type as $x => $y){
					if($y['type'] == $v['style']){
						break;
					}else{
						if($i == $sum){
							$type[$sum]['type'] = $v['style'];
						}
					}
					$i++;
				}
			}
		}
		//if show some type or all types
		if($post['style']){
			logger('高级管理员:查询特定类型的个体 '.$post['style']);
			//遍历 提取特定类型的个体
			$i = 0;
			foreach($result as $k => $v){
				if($v['style'] == $post['style']){
					$the_result[$i] = $v;
					$i++;
				}
			}
			$result = $the_result;
		}
		// logger('展示数组：'.var_export($result,TRUE)); //debug
		logger('高级管理员:返回结果成功！'."\n");
		$this->assign('type',$type);
		$this->assign('sps',$result);
		$this->display($page);
	}
	// 同步套系 sync set // 两张表存储套系和套系类型 有些复杂化，改用一张表然后用遍历的方式提取套系类型
	/////////////////////////////////////////////////////////////////////////////////////
	//重写同步套系函数  //利用一张表 简化判断复杂程度
	public function sync_sets(){
		logger('高级管理员:同步套系');
		$set = D('sets');
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 14,
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
		if(strlen($result) < 39){
			logger("高级管理员:------------------>没有套系信息<--------------------\n");
			$ajax_data = array(
				'status' => 2,
				'content' => '没有套系信息'
			);
		}else{
			logger('高级管理员:------------------>存在套系信息<-------------------');
			$string = rtrim($result,'</l></recipe>');
			$string = substr($string,33);
			$arra = explode('</l><l><',$string);
			$new_sets = $this->str_set3($arra); 
			// logger('套系数组:'.var_export($sets,TRUE)); //debug
			$where = array(
				'sid' => session('sid')
			);
			$old_sets = $set->where($where)->select();
			$sum = count($old_sets);
			if($sum == 0 || $sum == NULL){
				logger('高级管理员:当前店铺没有任何套系信息！');
				//全部套系下产品
				$productlist = $this->sync_set_products();
				if(empty($productlist)){
					$i = 0;
					foreach($new_sets as $k => $v){
						foreach($v['child_set'] as $key => $value){
							$data[$i] = array(
								'name' => $value['set_name'],
								'style' => $v['set'],
								'price' => $value['set_price'],
								'sid' => session('sid'),
								'create_time' => time(),
								'create_admin' => session('name')
							);
							$i++;
						}
					}
				}else{
					$i = 0;
					foreach($new_sets as $k => $v){
						foreach($v['child_set'] as $key => $value){
							$data[$i] = array(
								'name' => $value['set_name'],
								'style' => $v['set'],
								'price' => $value['set_price'],
								'sid' => session('sid'),
								'create_time' => time(),
								'create_admin' => session('name')
							);
							foreach($productlist as $x => $y){
								if($y['set'] == $value['set_name']){
									$str = '';
									foreach($y['productList'] as $m => $n){
										$str .= $n['pro_name'].' '.$n['pro_price'].' '.$n['pro_num'].' ';
									}
									$data[$i]['products'] = rtrim($str,' ');
								}
							}
							$i++;
						}
					}
				}
				$result = $set->addAll($data);
				if($result){
					logger('高级管理员:添加套系数组成功！');
				}else{
					logger('高级管理员:添加套系数组失败！');
				}
			}else{
				logger('高级管理员:当前店铺已有套系！');
				$num = 0;
				//循环找出新套系
				foreach($new_sets as $k => $v){
					$num += count($v['child_set']);
					foreach($v['child_set'] as $key => $value){
						$i = 1;
						foreach($old_sets as $x => $y){
							if($v['set'] == $y['style'] && $value['set_name'] == $y['name']){
								if($value['set_price'] == $y['price']){
									logger('高级管理员:该套系没有变化！');
								}else{
									// logger('旧价格：'.$y['price'].'新价格：'.$value['set_price']); //debug
									logger('高级管理员:该套系价格变化！');
									$where = array(
										'id' => $y['id']
									);
									$data = array(
										'price' => $value['set_price'],
										'modify_time' => time(),
										'modify_admin' => session('name')
									);
									$result = $set->where($where)->save($data);
									if($result){
										logger('高级管理员:更新套系信息成功！');
									}else{
										logger('高级管理员:更新套系信息失败！');
									}
								}
								break;
							}else{
								if($i == $sum){
									logger('高级管理员:发现新增套系');
									$data = array();
									$data = array(
										'name' => $value['set_name'],
										'style' => $v['set'],
										'price' => $value['set_price'],
										'sid' => session('sid'),
										'create_time' => time(),
										'create_admin' => session('name')
									);
									//该套系下产品
									$productlist = $this->sync_set_products($value['set_name']);
									if(empty($productlist)){
										$data['products'] = '';
									}else{
										$str = '';
										foreach($productlist as $m => $n){
											$str .= $n['pro_name'].' '.$n['pro_price'].' '.$n['pro_num'].' ';
										}
										$data['products'] = rtrim($str,' ');
									}
									$result = $set->add($data);
									if($result){
										logger('高级管理员:添加套系信息成功！');
									}else{
										logger('高级管理员:添加套系信息失败！');
									}
								}
							}
							$i++;
						}
					}
				}
				// 循环找出废弃套系
				foreach($old_sets as $x => $y){
					$i = 1;
					foreach($new_sets as $k => $v){
						foreach($v['child_set'] as $key => $value){
							if($y['style'] == $v['set'] && $y['name'] == $value['set_name']){
								if($y['price'] == $value['set_price']){
									logger('高级管理员:该套系没有变化！');
								}else{
									// logger('旧价格：'.$y['price'].'新价格：'.$value['set_price']); //debug
									logger('高级管理员:该套系价格变化！');
									$where = array(
										'id' => $y['id']
									);
									$data = array(
										'price' => $value['set_price'],
										'modify_time' => time(),
										'modify_admin' => session('name')
									);
									$result = $set->where($where)->save($data);
									if($result){
										logger('高级管理员:更新套系信息成功！');
									}else{
										logger('高级管理员:更新套系信息失败！');
									}
								}
								break;
							}else{
								if($i == $num){
									logger('高级管理员:发现废弃套系！');
									$where = array(
										'id' => $y['id']
									);
									$result = $set->where($where)->delete();
									if($result){
										logger('高级管理员:删除废弃套系成功！');
									}else{
										logger('高级管理员:删除废弃套系失败！');
									}
								}
							}
							$i++;
						}
					}
				}
			}
		}
		$ajax_data = array(
			'status' => 1,
			'content' => '同步套系完成！'
		);
		$this->ajaxReturn($ajax_data);
	}
	//现处理函数3 2016-05-18下午
	public function str_set3($arr){
		//先将每一条记录的各信息提取出来，组成更细分的数组
		$array = array();
		foreach($arr as $k => $v){
			$array[$k]['set'] = strchr(ltrim($v,'t>'),'</t>',TRUE);
			$array[$k]['set_name'] = ltrim(strchr(strchr($v,'<n>'),'</n>',TRUE),'<n>');
			$array[$k]['set_price'] = rtrim(ltrim(strchr($v,'<p>'),'<p>'),'</p>');
		}
		$set_array = array();
		foreach($array as $k => $v){
			if($k == 0){
				$set_array[$k]['set'] = $v['set'];
				$set_array[$k]['child_set'][$k]['set_name'] = $v['set_name'];
				$set_array[$k]['child_set'][$k]['set_price'] = $v['set_price'];
				$set_array[$k]['nums'] = 1;
			}else{
				$pipol = 0;
				$nums = 0;
				foreach($set_array as $key => $value){
					if($value['set'] != $v['set']){
						$pipol++;
					}else{
						$nums = $value['nums'];
						$mums = $key;
						break;
					}
				}
				if($pipol < sizeof($set_array)){
					$set_array[$mums]['child_set'][$nums]['set_name'] = $v['set_name'];
					$set_array[$mums]['child_set'][$nums]['set_price'] = $v['set_price'];
					$set_array[$mums]['nums']++;
				}else{				
					$set_array[$pipol]['set'] = $v['set'];
					$set_array[$pipol]['child_set'][0]['set_name'] = $v['set_name'];
					$set_array[$pipol]['child_set'][0]['set_price'] = $v['set_price'];
					$set_array[$pipol]['nums'] = 1;
				}
			}
		}
		return $set_array;
	}
	//show edit page or save edit data
	public function edit_info(){
		$post = I();
		logger('修改信息--传入参数：'.var_export($post,TRUE)); //debug
		switch($post['type']){
			case 'set':
				$text = '套系';
				$table = D('sets');
				$page = 'edit_set';
				break;
			case 'product':
				$text = '产品';
				$table = D('product');
				$page = 'edit_product';
				break;
			case 'spot':
				$text = '景点';
				$table = D('spot');
				$page = 'edit_spot';
				break;
			default:
				break;
		}
		logger('高级管理员:修改'.$text);
		if($post['id'] && $post['remark']){
			logger('高级管理员:修改套系');
			$data = array(
				'remark' => $post['remark'],
				'modify_time' => time(),
				'modify_admin' => session('name')
			);
			$where = array(
				'id' => $post['id']
			);
			$result = $table->where($where)->save($data);
			if($result){
				$ajax_data = array(
					'status' => 1,
					'content' => '修改'.$text.'成功！'
				);
				logger('高级管理员：修改'.$text.'成功！'."\n");
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '修改'.$text.'失败！'
				);
				logger('高级管理员：修改'.$text.'失败！'."\n");
			}
			$this->ajaxReturn($ajax_data);
		}else if(!$post['id']){
			logger('高级管理员:参数不全！');
			$this->error('提交参数不全，请重试！');
		}else{
			logger('高级管理员:显示修改'.$text.'页面'."\n");
			$where = array(
				'sid' => session('sid')
			);
			$all_things = $table->where($where)->select();
			// 遍历提取类型
			foreach($all_things as $k => $v){
				if($v['id'] == $post['id']){
					//特定编辑的个体
					$result = $v;
				}
				if($k == 0){
					$type[0]['type'] = $v['style'];
					continue;
				}else{
					$i = 1;
					$sum = count($type);
					foreach($type as $x => $y){
						if($y['type'] == $v['style']){
							break;
						}else{
							if($i == $sum){
								$type[$sum]['type'] = $v['style'];
							}
						}
						$i++;
					}
				}
			}
			$imgs = trim($result['sample'],' ');
			if($imgs == '' || $imgs == NULL){
				$num = 0;
				$imgs = array();
			}else{
				$imgs = explode(' ',$imgs);
				$num = count($imgs);
			}
			logger('图片记录："'.$result['sample'].'"'); //debug
			logger('图片数量：'.$num); //debug
			logger('产品数组：'.var_export($imgs,TRUE)); //debug
			$zone = $this->list_picture(TRUE);
			$this->assign('type',$type); //全部类型
			$this->assign('sps',$result); //个体内容
			$this->assign('num',$num); //个体图片数量
			$this->assign('sum',$num); //个体图片数量
			$this->assign('imgs',$imgs); //个体图片
			$this->assign('files',$zone['files']); //图片空间
			$this->assign('level',$zone['level']); //图片空间
			$this->assign('path',$zone['path']); //图片空间
			$this->display($page);
		}
	}
	//同步产品
	public function sync_products(){
		logger('高级管理员:同步产品');
		$product = D('product');
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 15,
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
		if(strlen($result) < 39){
			logger("高级管理员:------------------>没有产品信息<--------------------\n");
			$ajax_data = array(
				'status' => 2,
				'content' => '没有产品信息'
			);
		}else{
			logger('高级管理员:------------------>存在产品信息<-------------------');
			$str_xml = substr(rtrim($result,'></l></recipe>'),33);
        	$tra_arr = explode('></l><l><',$str_xml);
        	$new_products = $this->list_arr2($tra_arr);
			// logger('产品数组:'.var_export($new_products,TRUE)); //debug
			$where = array(
				'sid' => session('sid')
			);
			$old_products = $product->where($where)->select();
			$sum = count($old_products);
			if($sum == 0 || $sum == NULL){
				logger('高级管理员:当前店铺没有任何产品信息！');
				$i = 0;
				foreach($new_products as $k => $v){
					foreach($v['productList'] as $key => $value){
						$data[$i] = array(
							'name' => $value['pro_name'],
							'style' => $v['name'],
							'price' => $value['pro_price'],
							'sid' => session('sid'),
							'create_time' => time(),
							'create_admin' => session('name')
						);
						$i++;
					}
				}
				$result = $product->addAll($data);
				if($result){
					logger('高级管理员:添加产品数组成功！');
				}else{
					logger('高级管理员:添加产品数组失败！');
				}
			}else{
				logger('高级管理员:当前店铺已有产品！');
				$num = 0;
				//循环找出新套系
				foreach($new_products as $k => $v){
					$num += count($v['productList']);
					foreach($v['productList'] as $key => $value){
						$i = 1;
						foreach($old_products as $x => $y){
							if($v['name'] == $y['style'] && $value['pro_name'] == $y['name']){
								if($value['pro_price'] == $y['price']){
									logger('高级管理员:该产品没有变化！');
								}else{
									// logger('旧价格：'.$y['price'].'新价格：'.$value['set_price']); //debug
									logger('高级管理员:该产品价格变化！');
									$where = array(
										'id' => $y['id']
									);
									$data = array(
										'price' => $value['pro_price'],
										'modify_time' => time(),
										'modify_admin' => session('name')
									);
									$result = $product->where($where)->save($data);
									if($result){
										logger('高级管理员:更新产品信息成功！');
									}else{
										logger('高级管理员:更新产品信息失败！');
									}
								}
								break;
							}else{
								if($i == $sum){
									logger('高级管理员:发现新增产品');
									$data = array();
									$data = array(
										'name' => $value['pro_name'],
										'style' => $v['name'],
										'price' => $value['pro_price'],
										'sid' => session('sid'),
										'create_time' => time(),
										'create_admin' => session('name')
									);
									$result = $product->add($data);
									if($result){
										logger('高级管理员:添加产品信息成功！');
									}else{
										logger('高级管理员:添加产品信息失败！');
									}
								}
							}
							$i++;
						}
					}
				}
				// 循环找出废弃套系
				foreach($old_products as $x => $y){
					$i = 1;
					foreach($new_products as $k => $v){
						foreach($v['productList'] as $key => $value){
							if($y['style'] == $v['name'] && $y['name'] == $value['pro_name']){
								if($y['price'] == $value['pro_price']){
									logger('高级管理员:该产品没有变化！');
								}else{
									// logger('旧价格：'.$y['price'].'新价格：'.$value['set_price']); //debug
									logger('高级管理员:该产品价格变化！');
									$where = array(
										'id' => $y['id']
									);
									$data = array(); 
									$data = array(
										'price' => $value['pro_price'],
										'modify_time' => time(),
										'modify_admin' => session('name')
									);
									$result = $product->where($where)->save($data);
									if($result){
										logger('高级管理员:更新产品信息成功！');
									}else{
										logger('高级管理员:更新产品信息失败！');
									}
								}
								break;
							}else{
								if($i == $num){
									logger('高级管理员:发现废弃产品！');
									$where = array(
										'id' => $y['id']
									);
									$result = $product->where($where)->delete();
									if($result){
										logger('高级管理员:删除废弃产品成功！');
									}else{
										logger('高级管理员:删除废弃产品失败！');
									}
								}
							}
							$i++;
						}
					}
				}
			}
		}
		$ajax_data = array(
			'status' => 1,
			'content' => '同步产品完成！'
		);
		$this->ajaxReturn($ajax_data);
	}
	// 现产品处理数组函数2 2016-05-18
	public function list_arr2($arr){
		//将每一条产品记录值拆分成更细的记录，同类型的记录分散在各处
		$array = array();
		foreach($arr as $k => $v){
			$array[$k]['category'] = strchr(ltrim($v,'t>'),'</t>',TRUE);
			$array[$k]['pro_name'] = ltrim(strchr(strchr($v,'<n>'),'</n>',TRUE),'<n>');
			$array[$k]['pro_price'] = rtrim(ltrim(strchr($v,'<p>'),'<p>'),'</p>');
		}
		// 新建数组，归类产品
		$pro_array = array();
		foreach($array as $k => $v){
			// 第一次循环时，直接添加到新数组中
			if($k == 0){
				$pro_array[$k]['name'] = $v['category'];
				$pro_array[$k]['productList'][$k]['pro_name'] = $v['pro_name'];
				$pro_array[$k]['productList'][$k]['pro_price'] = $v['pro_price'];
				$pro_array[$k]['nums'] = 1;
			}else{ //之后，匹配类目是否已存在新数组中。 不存在，则新建新数组元素；存在，则在旧元素下加子元素。 注意下标的控制。
				$pipol = 0;
				$nums = 0;
				foreach($pro_array as $key => $value){
					if($value['name'] != $v['category']){
						$pipol++;
					}else{
						$nums = $value['nums'];
						$mums = $key;
						break;
					}
				}
				if($pipol < sizeof($pro_array)){
					$pro_array[$mums]['productList'][$nums]['pro_name'] = $v['pro_name'];
					$pro_array[$mums]['productList'][$nums]['pro_price'] = $v['pro_price'];
					$pro_array[$mums]['nums']++;
				}else{				
					$pro_array[$pipol]['name'] = $v['category'];
					$pro_array[$pipol]['productList'][0]['pro_name'] = $v['pro_name'];
					$pro_array[$pipol]['productList'][0]['pro_price'] = $v['pro_price'];
					$pro_array[$pipol]['nums'] = 1;
				}
			}
		}
		return $pro_array;
	}
	//同步景点
	public function sync_spots(){
		logger('高级管理员:同步景点');
		$spot = D('spot');
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 18,
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
		if(strlen($result) < 39){
			logger("高级管理员:------------------>没有景点信息<--------------------\n");
			$ajax_data = array(
				'status' => 2,
				'content' => '没有景点信息'
			);
		}else{
			logger('高级管理员:------------------>存在景点信息<-------------------');
			$result = ltrim(strchr(strchr($result,'</recipe>',TRUE),'<recipe>'),'<recipe>');
			$result = rtrim(ltrim($result,'<n>'),'</n>');
			$new_spots = explode('</n><n>',$result);
			// logger('产品数组:'.var_export($new_spots,TRUE)); //debug
			$where = array(
				'sid' => session('sid')
			);
			$old_spots = $spot->where($where)->select();
			$sum = count($old_spots);
			if($sum == 0 || $sum == NULL){
				logger('高级管理员:当前店铺没有任何景点信息！');
				$i = 0;
				foreach($new_spots as $v){
					$data[$i] = array(
						'name' => $v,
						'style' => $v,
						'sid' => session('sid'),
						'create_time' => time(),
						'create_admin' => session('name')
					);
					$i++;
				}
				$result = $spot->addAll($data);
				if($result){
					logger('高级管理员:添加景点数组成功！');
				}else{
					logger('高级管理员:添加景点数组失败！');
				}
			}else{
				logger('高级管理员:当前店铺已有景点信息！');
				//循环找出新景点
				foreach($new_spots as $v){
					$i = 1;
					foreach($old_spots as $x => $y){
						if($v == $y['style'] && $v == $y['name']){
							logger('高级管理员:该景点没有变化！');
							break;
						}else{
							if($i == $sum){
								logger('高级管理员:发现新增景点');
								$data = array();
								$data = array(
									'name' => $v,
									'style' => $v,
									'sid' => session('sid'),
									'create_time' => time(),
									'create_admin' => session('name')
								);
								$result = $spot->add($data);
								if($result){
									logger('高级管理员:添加景点信息成功！');
								}else{
									logger('高级管理员:添加景点信息失败！');
								}
							}
						}
						$i++;
					}
				}
				// 循环找出废弃景点
				$num = count($new_spots);
				foreach($old_spots as $x => $y){
					$i = 1;
					foreach($new_spots as $v){
						if($y['style'] == $v && $y['name'] == $v){
							logger('高级管理员:该景点没有变化！');
							break;
						}else{
							if($i == $num){
								logger('高级管理员:发现废弃景点！');
								$where = array(
									'id' => $y['id']
								);
								$result = $spot->where($where)->delete();
								if($result){
									logger('高级管理员:删除废弃景点成功！');
								}else{
									logger('高级管理员:删除废弃景点失败！');
								}
							}
						}
						$i++;
					}
				}	
			}
		}
		$ajax_data = array(
			'status' => 1,
			'content' => '同步景点完成！'
		);
		$this->ajaxReturn($ajax_data);
	}
	//图片管理 //通过遍历目录的方式
	public function list_picture_bypath(){
		logger('高级管理员:展示图片空间！');
		$path= './Uploads/'.session('sid');
		// logger($path); //debug
		$array = traverse_sigle_folder($path);
		// logger('一级目录:'.var_export($array,TRUE));//debug
		foreach($array as $k => $v){
			if($v['type'] == 'directory'){
				$array[$k]['files'] = traverse_sigle_folder($path.'/'.$array[$k]['file']);
			}
		}
		// logger('目录:<br>'.var_export($array,TRUE)); //debug
		$this->assign('sid',session('sid'));
		$this->assign('folder','9');
		$this->assign('level','0');
		$this->assign('files',$array);
		$this->display();
	}
	// 图片空间管理 通过数据库查询的方式
	public function list_picture($type = FALSE){
		logger('高级管理员:展示图片空间！');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		$folder = $post['folder'];
		$level = $post['level'];
		$sid = session('sid');
		$image = D('image');
		if($folder && ($level >= 0)){
			$where = array(
				'sid' => $sid,
				'folder' => $folder,
				'level' => $level+1,
			);
		}else{ //默认查询店铺根目录
			$where = array(
				'sid' => $sid,
				'folder' => $sid,
				'level' => 1,
			);
			$folder = $sid;
			$level = 0;
		}
		$result = $image->where($where)->select(); //查目标文件夹中的文件
		if($level == 0){
			$path = array(
				'0' => array(
					'folder' => session('sid'),
					'level' => 0,
				)
			);
			$savepath = session('sid').'/'; //上传存储路径
		}else{
			$directory = $image->where(array('name'=>$folder,'level'=>$level))->find();
			if($directory){ //如果目录存在
				$path = explode('/',trim(ltrim($directory['path'],'/Uploads'),'/')); //  /Uploads/9/2016/  --->  9/2016
				$i = 0;
				foreach($path as $a){
					$new_path[$i]['folder'] = $a;
					$new_path[$i]['level'] = $i;
					$i++;
				}
				$path = $new_path;
				$savepath = ltrim($directory['path'],'/Uploads/');
			}else{
				$this->error('目录不存在');
			}
		}
		if($result){ //如果目录中有文件
			foreach($result as $k => $v){
				if($v['type'] == 1){ //是文件夹
					$result[$k]['url'] = '/index.php/Admin/Pad/list_picture/folder/'.$v['name'].'/level/'.$v['level'].'.html';  // "{:U('Admin/Pad/list_picture',array('folder'=>'".$v['name']."','level'=>'".$v['level']."'))}";
				}
			}
		}else{
			$result = array(); //设成空数组
		}
		if($type == FALSE && $post['way'] != 'ajax'){
			logger('高级管理员:图片空间显示'."\n");
			$this->assign('sid',session('sid'));
			$this->assign('folder',$folder); //当前文件夹
			$this->assign('level',$level); //当前层级
			$this->assign('files',$result); //文件
			$this->assign('path',$path); //面包屑
			$this->assign('savepath',$savepath);
			$this->display();
		}elseif($type == TRUE){
			logger('高级管理员:系统内部调用'."\n");
			$data = array(
				'level' => $level,
				'files' => $result,
				'path' => $path
			);
			return $data;
		}else{
			logger('高级管理员:AJAX请求'."\n");
			$data = array(
				'status' => 1,
				'content' => '图片信息返回成功！',
				'files' => $result,
				'path' => $path
			);
			$this->ajaxReturn($data);
		}
	}
	//新建文件夹
	public function new_folder(){
		logger('高级管理员:新建文件夹！');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		$folder = $post['folder'];
		$level = $post['level'];
		$name = $post['name'];
		if($folder && $name && ($level >= 0)){
			$image = D('image');
			if($level == 0){
				$path = './Uploads/'.$folder.'/'.$name.'/';  // ./Uploads/9/2016/
			}else{
				$where = array(
					'folder' => $folder,
					'level' => $level,
					'sid' => session('sid')
				);
				$pre_d = $image->where($where)->find();
				$path = '.'.$pre_d['path'].$name.'/';
			}
			if(!file_exists($path)){
				mkdir($path,0777,TRUE); //创建
			}
			if(file_exists($path)){ //是否存在
				logger('高级管理员：创建新文件夹成功！下一步去写入数据库！');
				$data = array(
					'name' => $name,
					'type' => 1,
					'size' => 10,
					'folder' => $folder,
					'sid' => session('sid'),
					'level' => $level+1,
					'path' => ltrim($path,'.'),
					'ctime' => time(),
					'cuid' => session('uid'),
					'status' => 0
				);
				$result = $image->add($data);
				if($result){
					$ajax_data = array(
						'status' => 1,
						'content' => '新文件夹记录写入成功！'
					);
					logger('高级管理员：新文件夹记录写入成功！'."\n");
				}else{
					$ajax_data = array(
						'status' => 0,
						'content' => '新文件夹记录写入失败！'
					);
					logger('高级管理员：新文件夹记录写入失败！'."\n");
				}
			}else{
				$ajax_data = array(
					'status' => 0,
					'content' => '创建新文件夹失败！'
				);
				logger('高级管理员：创建新文件夹失败！'."\n");
			}
		}else{
			$ajax_data = array(
				'status' => 2,
				'content' => '参数不全！'
			);
			logger('高级管理员：参数不全'."\n");
		}
		$this->ajaxReturn($ajax_data);
	}
	//LOGO管理
	public function list_logo(){
		logger('高级管理员：展示店铺LOGO');
		$image = D('image');
		$where = array(
			'sid' => session('sid'),
			'type' => 3
		);
		$result = $image->where($where)->order('ctime desc')->select();
		$this->assign('logo',$result);
		$zone = $this->list_picture(TRUE);
		$this->assign('files',$zone['files']); //图片空间
		$this->assign('level',$zone['level']); //图片空间
		$this->assign('path',$zone['path']); //图片空间
		$this->display('edit_logo');
	}
	//更新logo,用于直接从图片空间选择
	public function updatelogo(){
		logger('高级管理员:从图片空间更新logo');
		$post = I();
		logger('携带参数:'.var_export($post,TRUE));//debug
		$path = $post['path'];
		// $id = $post['id'];
		$image = D('image');
		$where = array(
			// 'id' => $id,
			'path' => $path,
			'sid' => session('sid')
		);
		$data = array(
			'type' => 3,
			'ctime' => time(),
			'cuid' => session('uid'),
			'mtime' => time(),
			'muid' => session('uid')
		);
		$data['status'] += 1;
		$result = $image->where($where)->save($data);
        if($result){
        	logger('高级管理员:更新logo记录成功!'."\n");
            $data = array(
                'status' => 1,
                'content' => '更新成功!'
            );
        }else{
            logger('高级管理员:更新图片记录失败!'."\n");
            $data = array(
                'status' => 0,
                'content' => '更新失败!'
            );
        }
        $this->ajaxReturn($data);
	}
	//上传logo
	public function uploadlogo(){
		logger('高级管理员:上传logo');
		header("Content-Type:text/html;charset:utf-8");
        // logger('文件系统:'.var_export($_FILES,TRUE)); //debug
        logger('高级管理员:上传logo开始--->');
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg','gif','png','jpeg');
        $upload->rootPath = './Uploads/';
        $upload->autoSub = false; //禁用自动目录
        $upload->subName = ''; //自动目录设置为空，原来为日期
        $upload->savePath = session('sid').'/';
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
               // $savetype = $v['type'];
               $savesize = $v['size'];
            }  
            //生成缩略图
            // $img= new \Think\Image();
            // $img->open('./Uploads/'.$savepath.$savename);
            // $img->thumb(200,200)->save('./Uploads/'.$savepath.'S'.$savename);
            $path = '/Uploads/'.$savepath.$savename;
            // @unlink('./Uploads/'.$savepath.$savename);
            logger("高级管理员:APP封面图片上传处理成功-->下面将图片信息存入数据库");
            $image = D('image');
            $imginfo = array(
            	'name' => $savename,
            	'type' => 3,
            	'folder' => session('sid'),
            	'level' => 1,
            	'ctime' => time(),
            	'cuid' => session('uid'),
            	'size' => $savesize,
            	'status' => 1,
            	'sid' => session('sid'),
            	'path' => $path
            );
            $result = $image->add($imginfo);
            if($result){
            	logger('高级管理员:上传logo记录成功!'."\n");
                $data = array(
                    'status' => 1,
                    'content' => '上传成功!'
                );
            }else{
                logger('高级管理员:上传图片记录失败!'."\n");
                $data = array(
                    'status' => 0,
                    'content' => '上传失败!'
                );
            }
            $this->ajaxReturn($data);
        }
	}
	//删除文件或文件夹
	public function remove_files(){
		logger('高级管理员:删除文件');
		$post = I();
		logger('传入参数：'.var_export($post,TRUE)); //debug
		if($post['type'] && $post['id']){
			$image = D('image');
			if($post['dfile'] == 'true'){
				$dfile = true;
				logger('高级管理员:文件和记录都删除！');
			}else{
				$dfile = false;
				logger('高级管理员:记录删除，文件保留！');
			}
			switch($post['type']){
				case 1:
					$where = array(
						'folder' => $post['name'],
						'level' => $post['level']+1,
						'sid' => session('sid')
					);
					$files = $image->where($where)->select();
					if(!empty($files)){
						foreach($files as $k => $v){
							$this->remove_file($image,$v['id'],$dfile,$v['path']); //删除图片记录
						}
						$result = $this->remove_directory($image,$post['id'],$dfile,$post['path']); //删除文件夹记录
					}else{
						$result = $this->remove_directory($image,$post['id'],$dfile,$post['path']); //删除文件夹记录
					}
					if($result){
						$ajax_data = array(
							'status' => 1,
							'content' => '删除文件成功！'
						);
						logger('高级管理员：删除文件成功！'."\n");
					}else{
						$ajax_data = array(
							'status' => 0,
							'content' => '删除文件失败！'
						);
						logger('高级管理员：删除文件失败！'."\n");
					}
					break;
				case 2:
					$result = $this->remove_file($image,$post['id'],$dfile,$post['path']); //删除图片记录
					if($result){
						$ajax_data = array(
							'status' => 1,
							'content' => '删除文件成功！'
						);
						logger('高级管理员：删除文件成功！'."\n");
					}else{
						$ajax_data = array(
							'status' => 0,
							'content' => '删除文件失败！'
						);
						logger('高级管理员：删除文件失败！'."\n");
					}
					break;
				case 3:
					$result = $this->remove_file($image,$post['id'],$dfile,$post['path']); //删除LOGO记录
					if($result){
						$ajax_data = array(
							'status' => 1,
							'content' => '删除文件成功！'
						);
						logger('高级管理员：删除文件成功！'."\n");
					}else{
						$ajax_data = array(
							'status' => 0,
							'content' => '删除文件失败！'
						);
						logger('高级管理员：删除文件失败！'."\n");
					}
					break;
				default:
					break;
			}
		}else{
			$ajax_data = array(
				'status' => 2,
				'content' => '参数不全！'
			);
			logger('高级管理员：参数不全'."\n");
		}
		$this->ajaxReturn($ajax_data);
	}
	private function remove_file($table,$id,$dfile,$path){
		logger('高级管理员:删除文件ID:'.$id);
		$result = $table->where(array('id'=>$id))->delete();
		if($result){
			logger('高级管理员：删除文件记录ID:'.$id.'成功！');
			if($dfile){
				$path = '.'.$path;
				if(unlink($path)){
					logger('高级管理员：删除文件ID:'.$id.'成功！');
				}else{
					logger('高级管理员：删除文件ID:'.$id.'失败！');
				}
			}
			return true;
		}else{
			logger('高级管理员：删除文件记录'.$id.'失败！');
			return false;
		}
	}
	private function remove_directory($table,$id,$dfile,$path){
		logger('高级管理员:删除文件夹ID:'.$id);
		$result = $table->where(array('id'=>$id))->delete();
		if($result){
			logger('高级管理员：删除文件夹记录ID:'.$id.'成功！');
			if($dfile){
				$path = '.'.rtrim($path,'/');
				if(rm_dir($path)){
					logger('高级管理员：删除文件夹ID:'.$id.'成功！');
				}else{
					logger('高级管理员：删除文件夹ID:'.$id.'失败！');
				}
			}
			return true;
		}else{
			logger('高级管理员：删除文件夹记录ID:'.$id.'失败！');
			return false;
		}
	}
	// 同步套系下产品
	private function sync_set_products($name = FALSE){
		logger('高级管理员:同步全部套系下产品');
		//连接远程服务器 key钥匙
		$admin = array(
			'operation' => 16,
			'dogid' => session('dogid')
		);
		$xml = transXML($admin);
		$xml = strchr($xml,'<uu>',TRUE);
		//如果查询某一套系，则需要name参数
		if($name){
			$xml .= '<name>'.$name.'</name>';
		}
		//强制转码 由utf8转成gbk
		$xml = mb_convert_encoding($xml,'gbk','utf8');
		// logger('高级管理员:查询xml:'.$xml."--->"); //debug
		$url = session('url');
		$getxml = getXML($url,$xml);
		$result = mb_convert_encoding($getxml, 'UTF-8', 'GB2312');
		// logger('高级管理员:--XML:'.$result); //debug
		if(strlen($result) < 39){
			logger("高级管理员:------------------>没有套系下产品信息<--------------------\n");
			return FALSE;
		}else{
			logger('高级管理员:------------------>存在套系下产品信息<-------------------');
			$string = rtrim($result,'</l></recipe>');
			$string = substr($string,33);
			$arra = explode('</l><l><',$string);
			//处理数组
			$result = $this->arr_set_products($arra); 
			return $result;
		}
	}
	private function arr_set_products($arr){
		//将每一条套系下产品记录值拆分成更细的记录，同类型的记录分散在各处
		$array = array();
		foreach($arr as $k => $v){
			$array[$k]['set'] = strchr(ltrim($v,'n>'),'</n>',TRUE);
			$array[$k]['pro_name'] = ltrim(strchr(strchr($v,'<p>'),'</p>',TRUE),'<p>');
			$array[$k]['pro_num'] = ltrim(strchr(strchr($v,'<c>'),'</c>',TRUE),'<c>');
			$array[$k]['pro_price'] = rtrim(ltrim(strchr($v,'<j>'),'<j>'),'</j>');
		}
		// logger('拆分数组：'.var_export($array,TRUE)); //debug
		// 新建数组，归类套系下产品
		$set_pro_array = array();
		foreach($array as $k => $v){
			// 第一次循环时，直接添加到新数组中
			if($k == 0){
				$set_pro_array[$k]['set'] = $v['set'];
				$set_pro_array[$k]['productList'][$k]['pro_name'] = $v['pro_name'];
				$set_pro_array[$k]['productList'][$k]['pro_num'] = $v['pro_num'];
				$set_pro_array[$k]['productList'][$k]['pro_price'] = $v['pro_price'];
				$set_pro_array[$k]['nums'] = 1;
			}else{ //之后，匹配类目是否已存在新数组中。 不存在，则新建新数组元素；存在，则在旧元素下加子元素。 注意下标的控制。
				$pipol = 0;
				$nums = 0;
				foreach($set_pro_array as $key => $value){
					if($value['set'] != $v['set']){
						$pipol++;
					}else{
						$nums = $value['nums'];
						$mums = $key;
						break;
					}
				}
				if($pipol < sizeof($set_pro_array)){
					$set_pro_array[$mums]['productList'][$nums]['pro_name'] = $v['pro_name'];
					$set_pro_array[$mums]['productList'][$nums]['pro_num'] = $v['pro_num'];
					$set_pro_array[$mums]['productList'][$nums]['pro_price'] = $v['pro_price'];
					$set_pro_array[$mums]['nums']++;
				}else{				
					$set_pro_array[$pipol]['set'] = $v['set'];
					$set_pro_array[$pipol]['productList'][0]['pro_name'] = $v['pro_name'];
					$set_pro_array[$pipol]['productList'][0]['pro_num'] = $v['pro_num'];
					$set_pro_array[$pipol]['productList'][0]['pro_price'] = $v['pro_price'];
					$set_pro_array[$pipol]['nums'] = 1;
				}
			}
		}
		return $set_pro_array;
	}
	public function sync_set_products_two(){
		logger('高级管理员:同步套系下产品');
		$set = D('sets');
		$where = array(
			'sid' => session('sid')
		);
		$sets = $set->where($where)->select();
		if(!empty($sets)){
			logger('高级管理员:统一同步套系下产品');
			//全部套系下产品
			$productlist = $this->sync_set_products();
			if(empty($productlist)){
				$data = array(
					'code' => 0,
					'message' => '产品数据为空！'
				);
				logger('高级管理员:产品数据为空！'."\n");
			}else{
				foreach($sets as $k => $v){
					foreach($productlist as $m => $n){
						if($n['set'] == $v['name']){
							$str = '';
							foreach($n['productList'] as $x => $y){
								$str .= $y['pro_name'].' '.$y['pro_price'].' '.$y['pro_num'].' ';
							}
							$update['products'] = rtrim($str,' ');
							$condition['id'] = $v['id'];
							$result = $set->where($condition)->save($update);
							$sql = $set->getLastsql(); //debug
							logger('更新语句:'.$sql);
							if($result){
								logger('高级管理员:同步套系：'.$v['name'].'下产品成功！');
							}else{
								logger('高级管理员:同步套系：'.$v['name'].'下产品失败！');
							}
						}
					}
				}
				$data = array(
					'code' => 1,
					'message' => '同步套系下产品成功！'
				);
				logger('高级管理员:同步套系下产品成功！'."\n");
			}
		}else{
			$data = array(
				'code' => 0,
				'message' => '套系数据为空！'
			);
			logger('高级管理员:套系数据为空！'."\n");
		}
		exit(json_encode($data));
	}
}
?>
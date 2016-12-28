<?php
namespace Wei\Controller;
use Think\Controller;
class RetransmissionController extends Controller
{
	public function _initialize()
	{
		header("content-type:text/html; charset=utf-8;");
		$scheck = A('Home/SessionCheck');
		$scheck->index();
	}
	// 九宫格首页
	public function index()
	{
		$category = $this->getCategory();
		$hotTemplet = $this->getHotTemplet();
		$commendTemplet = $this->getCommendTemplet();
		$data = array(
			'code' => 1,
			'message' => '九宫格首页返回成功！',
			'result' => array(
					'category' => $category,
					'hot' => $hotTemplet,
					'commend' => $commendTemplet
				)
		);
		exit(json_encode($data));
	}
	// 获取权重高的分类
	private function getCategory($limit)
	{
		$category = D('weicategory');
		$where = array(
			'sid' => array('eq',C('publicSid')),
			'delete_at' => array('eq',0),
		);
		if(!isset($limit))
			$limit = 100;
		$categorys = $category->where($where)->field('id,name')->order('sid desc,weight asc,create_at desc')->limit($limit)->select();
		if(count($categorys) < 1)
			$categorys = array();

		return $categorys;
	}
	// 获取热门模板
	private function getHotTemplet()
	{	
		// 从模板表直观提取
		$templet = D('weitemplet');
		$where = array(
			'sid' => array('eq',C('publicSid')),
			'delete_at' => array('eq',0),
		);
		$templets = $templet->where($where)->field('id,name,sid,thumb')->order('retranstimes desc,create_at desc')->limit(3)->select();
		if(count($templets) < 1){
			$templets = array();
		}else{
			// 处理图片路径
			foreach($templets as $k => $v){
				if(strpos($v['thumb'],'/Uploads/') === 0){
					$templets[$k]['thumb'] = C('base_url').$templets[$k]['thumb'];
				}
			}
		}

		return $templets;
	}
	// 获取推荐模板
	private function getCommendTemplet()
	{
		// 从模板表直观提取
		$templet = D('weitemplet');
		$where = array(
			'sid' => array('eq',C('publicSid')),
			'delete_at' => array('eq',0),
		);
		$templets = $templet->where($where)->field('id,name,sid,thumb')->order('commend desc,create_at desc')->limit(9)->select();
		if(count($templets) < 1){
			$templets = array();
		}else{
			// 处理图片路径
			foreach($templets as $k => $v){
				if(strpos($v['thumb'],'/Uploads/') === 0){
					$templets[$k]['thumb'] = C('base_url').$templets[$k]['thumb'];
				}
			}
		}
		return $templets;
	}
	// 搜索模板
	public function search()
	{
		$post = I();
		$keyword = $post['keyword'];
		if(isset($keyword)){
			$where = array(
				'name' => array('like',array('%'.$keyword.'%','%'.$keyword,$keyword.'%','OR')),
				'delete_at' => array('eq',0),
				'isShare' => array('eq',1) // 模板共享
			);
			if(C('publicSid') == session('sid')){
				$where['sid'] = array('eq',session('sid'));
			}else{
				$where['sid'] = array('in',C('publicSid').','.session('sid'));
			}
			$templet = D('weitemplet');
			$templets = $templet->where($where)->field('id,name,sid,thumb')->order('commend desc,create_at desc')->select();
			if(count($templets) < 1)
				$templets = array();
			// 图像处理
			foreach($templets as $k => $v){
				if(strpos($v['thumb'],'/Uploads/') === 0)
					$templets[$k]['thumb'] = C('base_url').$templets[$k]['thumb'];
			}
			$data = array(
				'code' => 1,
				'message' => '模板搜索返回成功！',
				'result' => $templets
			);
		}else{
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// 查看分类下模板  //热门模板 hot 推荐模板 commend 全部(更多) all 店铺内部 store
	public function lists()
	{
		$post = I();
		$id = $post['id'];
		$page = $post['page'];
		$nums = $post['nums'];
		if($id && $page){
			$where = array(
				'delete_at' => array('eq',0)
			);
			$field = 'id,name,sid,thumb,create_at,retranstimes,commend';
			switch($id){
				case 'more':
				case 'all':
					$where['sid'] = array('eq',C('publicSid'));
					$order = 'create_at desc,retranstimes desc,commend desc';
					break;
				case 'hot':
					$where['sid'] = array('eq',C('publicSid'));
					$order = 'retranstimes desc,commend desc,create_at desc';
					break;
				case 'commend':
					$where['sid'] = array('eq',C('publicSid'));
					$order = 'commend desc,retranstimes desc,create_at desc';
					break;
				case 'group':
					$where['sid'] = array('eq',session('sid'));
					$where['isShare'] = array('eq',1); // 内部共享
					$order = 'create_at desc';
					break;
				default:
					$where['sid'] = array('eq',C('publicSid'));
					$where['cid'] = array('eq',$id);
					$order = 'create_at desc,commend desc,retranstimes desc';
					break;
			}
			$templet = D('weitemplet');
			if(!isset($nums))
				$nums = 10;
			$allNums = $templet->where($where)->count(); // 最大记录数
			$allPages = ceil($allNums/$nums); // 最大页数
			$templets = $templet->where($where)->field($field)->order($order)->page($page.','.$nums)->select();
			if(count($templets) < 1){
				$templets = array();
			}else{
				// 处理图片路径
				foreach($templets as $k => $v){
					if(strpos($v['thumb'],'/Uploads/') === 0){
						$templets[$k]['thumb'] = C('base_url').$templets[$k]['thumb'];
					}
				}
			}
			$data = array(
				'code' => 1,
				'message' => '模板列表返回成功！',
				'result' => $templets,
				'page' => $allPages
			);
		}else{
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// 我关注的分类列表
	public function myCare()
	{
		// $field = 'count("tid") as count ,id,name,sid,thumb';
		$time = strtotime(date('Y-m-d',time()));
		$field = 'count(CASE WHEN t.create_at > '.$time.' THEN 1 ELSE NULL END) as count ,id,name,sid,thumb';
		$where = array(
			'uid' => session('uid'),
			'delete_at' => array('eq',0),
			'delete_time' => array('eq',0)
			// 'create_at' => array('gt',$time)
		);
		$group = 'id'; 
		$category = $this->getCareCateCount($field,$where,$group);
		if(count($category) < 1)
			$category = array();
		$data = array(
			'code' => 1,
			'message' => '关注分类返回成功！',
			'result' => $category
		);
		exit(json_encode($data));
	}
	// 我关注分类最新发布统计
	private function getCareCateCount($field,$where,$group)
	{
		$careCount = D('CareCateCount');
		$category = $careCount->field($field)->where($where)->group($group)->select();
		// $sql = $careCount->getLastsql(); // debug
		// logger('查询语句：'.$sql); // debug
		if(count($category) < 1){
			$category = array();
		}else{
			// 处理图片路径
			foreach($category as $k => $v){
				if(strpos($v['thumb'],'/Uploads/') === 0){
					$category[$k]['thumb'] = C('base_url').$category[$k]['thumb'];
				}
			}
		}
		return $category;
	}
	// 更新我关注的分类
	public function updateCareCate()
	{
		$post = I();
		$care = $post['care'];
		if($care){
			$care = chanslate_json_to_array($care);
			// logger('新关注分类数组：'.var_export($care,true)); // debug
			$careCate = $this->getCareCategory('id,name,sid,thumb,careid');
			// logger('旧关注分类数组：'.var_export($careCate,true)); // debug
			if(count($care) >= 1){
				logger('新关注分类不为空');
				if(count($careCate) >= 1){
					logger('原关注分类不为空');
					$checkResult = $this->checkCare($care,$careCate);
					logger('新老比对结果：'.var_export($checkResult,true)); // debug
					M()->startTrans();
					if($this->addCare($checkResult['add'])){
						if($this->deleteCare($checkResult['delete'])){
							M()->commit();
							$data = array(
								'code' => 1,
								'message' => '更新关注分类成功！'
							);
						}else{
							M()->rollback();
							$data = array(
								'code' => 6,
								'message' => '更新失败，请重试！'
							);
						}
					}else{
						M()->rollback();
						$data = array(
							'code' => 5,
							'message' => '更新失败，请重试！'
						);
					}
				}else{
					// 全部添加
					logger('原关注分类为空空空');
					$add = array();
					foreach($care as $c){
						$add[] = array(
							'cid' => $c,
							'uid' => session('uid'),
							'create_at' => time()
						);
					}
					if($this->addCare($add)){
						$data = array(
							'code' => 1,
							'message' => '更新关注分类成功！'
						);
					}else{
						$data = array(
							'code' => 4,
							'message' => '更新失败，请重试！'
						);
					}
				}
			}else{
				// 清空所有关注分类
				logger('新关注分类为空空空空');
				$del = '';
				foreach($careCate as $k => $v){
					$del .= $v['id'].',';
				}
				$del = rtrim($del,',');
				if($this->deleteCare($del)){
					$data = array(
						'code' => 1,
						'message' => '更新关注分类成功！'
					);
				}else{
					$data = array(
						'code' => 3,
						'message' => '更新失败，请重试！'
					);
				}
			}
		}else{
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// 获取我关注的分类
	private function getCareCategory($field)
	{
		$care = D('CareCategory');
		$where = array(
			'uid' => session('uid'),
			'delete_at' => array('eq',0)
		);
		$category = $care->where($where)->field($field)->order('weight asc')->select();
		if(count($category) < 1){
			$category = array();
		}else{
			// 处理图片路径
			foreach($category as $k => $v){
				if(strpos($v['thumb'],'/Uploads/') === 0){
					$category[$k]['thumb'] = C('base_url').$category[$k]['thumb'];
				}
			}
		}
		return $category;
	}
	// 比较新关注分组和旧关注分组
	private function checkCare($care,$category)
	{
		$maxcare = count($care);
		$maxcate = count($category);
		// 查找新增关注
		$add = array();
		foreach($care as $c){
			$i = 1;
			foreach($category as $k => $v){
				if($c != $v['id']){
					if($i == $maxcate){
						$add[] = array(
							'cid' => $c,
							'uid' => session('uid'),
							'create_at' => time()
						);
					}
					$i++;
				}else{
					break;
				}
			}
		}
		// 查找取消关注
		$del = '';
		foreach($category as $k => $v){
			$i = 1;
			foreach($care as $c){
				if($c != $v['id']){
					if($i == $maxcare){
						$del .= ','.$v['careid'];
					}
					$i++;
				}else{
					break;
				}
			}
		}
		$del = ltrim($del,',');
		// 汇总结果
		$array = array(
			'add' => $add,
			'delete' => $del
		);
		return $array;
	}
	// 添加关注分类
	private function addCare($add)
	{
		$result = true;
		if(count($add) >= 1){
			$careuser = D('weicareuser');
			$result = $careuser->addAll($add);
		}

		return $result;
	}
	// 清除关注分类
	private function deleteCare($del)
	{
		$result = true;
		if(strlen($del) >= 1){
			$careuser = D('weicareuser');
			$result = $careuser->delete($del);
		}

		return $result;
	}
	// 我已转发
	public function myRetrans()
	{
		$post = I();
		$date = $post['date'];
		$where = array('uid'=>session('uid'));
		$field = 'id,name,thumb,category,create_at';
		$order = 'weight asc';
		$group = 'id';
		$retrans = $this->getMyRetrans($date,$where,$field,$order,$group);
		if(count($retrans) < 1)
			$retrans = array();
		$data = array(
			'code' => 1,
			'message' => '我已使用模板返回成功！',
			'result' => $retrans,
			'isEnd' => 0
		);
		// 判断是否已到最后
		$startTime = strtotime($date);
		if(!$startTime)
			$startTime = strtotime(date('Y-m-d',time()));
		if($startTime < C('startTime') || ($startTime-86400*C('queryDay')) <= C('startTime'))
			$data['isEnd'] = 1;
		exit(json_encode($data));
	}
	// 获取我转发的记录
	private function getMyRetrans($date,$where,$field,$order,$group)
	{
		$allRetrans = array();
		$startTime = strtotime($date);
		if(!$startTime)
			$startTime = strtotime(date('Y-m-d',time()));
		for($i=0;$i<C('queryDay');$i++){
			$bigTime = strtotime(date('Y-m-d',$startTime+86400));
			$smallTime = strtotime(date('Y-m-d',$startTime));
			$where['create_at'] = array(array('lt',$bigTime),array('egt',$smallTime),"AND");
			$retrans = D('RetransTemplet');
			$myRetrans = $retrans->where($where)->field($field)->order($order)->group($group)->select();
			if(count($myRetrans) >= 1){
				$max = count($allRetrans);
				foreach($myRetrans as $k => $v){
					$allRetrans[$max] = $v;
					// 每条记录+日期信息
					$allRetrans[$max]['date'] = date('Y-m-d',$startTime);
					// 图像处理
					if(strpos($allRetrans[$max]['thumb'],'/Uploads/') === 0)
						$allRetrans[$max]['thumb'] = C('base_url').$allRetrans[$max]['thumb'];
					$max++;
				}
			}
			$startTime -= 86400;
		}
		return $allRetrans;
	}
	// 团队已使用
	public function groupRetrans()
	{
		$post = I();
		$date = $post['date'];
		$where = array(
			'sid'=>session('sid')
		);
		$field = 'COUNT("rid") AS times,COUNT(DISTINCT "uid") AS users,id,name,thumb,category,create_at,CASE WHEN uid = '.session('uid').' THEN 1 ELSE 0 END AS useis';
		$order = 'create_at desc';
		$group = 'id';
		$retrans = $this->getGroupRetransmission($date,$where,$field,$order,$group);
		if(count($retrans) < 1)
			$retrans = array();
		$data = array(
			'code' => 1,
			'message' => '团队已使用模板返回成功！',
			'result' => $retrans,
			'isEnd' => 0
		);
		// 判断是否已到最后
		$startTime = strtotime($date);
		if(!$startTime)
			$startTime = strtotime(date('Y-m-d',time()));
		if($startTime < C('startTime') || ($startTime-86400*C('queryDay')) <= C('startTime'))
			$data['isEnd'] = 1;
		exit(json_encode($data));
	}
	// 获取团队转发的记录
	private function getGroupRetransmission($date,$where,$field,$order,$group)
	{
		$allRetrans = array();
		$startTime = strtotime($date);
		if(!$startTime)
			$startTime = strtotime(date('Y-m-d',time()));
		for($i=0;$i<C('queryDay');$i++){
			$bigTime = strtotime(date('Y-m-d',$startTime+86400));
			$smallTime = strtotime(date('Y-m-d',$startTime));
			$where['create_at'] = array(array('lt',$bigTime),array('egt',$smallTime),"AND");
			// $field .= ',remind_at';
			$retrans = D('RetransCount');
			$groupRetrans = $retrans->where($where)->field($field)->order($order)->group($group)->select();
			if(count($groupRetrans) >= 1){
				$max = count($allRetrans);
				foreach($groupRetrans as $k => $v){
					$allRetrans[$max] = $v;
					// 每条记录+日期信息
					$allRetrans[$max]['date'] = date('Y-m-d',$startTime);
					// 图像处理
					if(strpos($allRetrans[$max]['thumb'],'/Uploads/') === 0)
						$allRetrans[$max]['thumb'] = C('base_url').$allRetrans[$max]['thumb'];
					$max++;
				}
			}
			$startTime -= 86400;
		}
		if(count($allRetrans) >= 1)
			$allRetrans = $this->getTempletRemind($allRetrans);
		return $allRetrans;
	}
	// 多一步查询，查询团队转发的模板在当天有没有必发提醒
	private function getTempletRemind($templets)
	{
		$remind = D('weiremind');
		foreach($templets as $k => $v){
			$templets[$k]['mind'] = 0;
			$where = array(
				'tid' => $v['id'],
				'sid' => session('sid'),
				'create_date' => $v['date']
			);
			$result = $remind->where($where)->field('id,create_by')->find();
			if($result)
				$templets[$k]['mind'] = 1;
		}
		return $templets;
	}
	// 模板详情
	public function detail()
	{
		$post = I();
		$id = $post['id'];
		if(!$id){
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
			exit(json_encode($data));
		}
		$templet = D('weitemplet');
		$detail = $templet->where(array('id'=>$id))->field('id,cid,name,content,image_order')->find();
		if($detail){
			$templetImage = D('TempletImage');
			$images = $templetImage->where(array('tid'=>$id))->field('id,url')->select();
			// 图片顺序
			$order = chanslate_json_to_array($detail['image_order']);
			// logger('图片顺序：'.var_export($order,true)); //debug
			// 排序
			if(count($order) >= 1){
				$max = count($images);
				$newImages = array();
				foreach($order as $x => $y){
					$i = 1;
					foreach($images as $k => $v){
						if($y == $v['id']){
							$newImages[$x] = $v;
							// 图像处理
							if(strpos($v['url'],'/Uploads/') === 0)
								$newImages[$x]['url'] = C('base_url').$newImages[$x]['url'];
							break;
						}else{
							if($i == $max){
								$newImages[$x]['id'] = '';
								$newImages[$x]['url'] = '';
							}
							$i++;
						}
					}
				}
				$images = &$newImages;
			}else{
				foreach($images as $k => $v){
					// 图像处理
					if(strpos($v['url'],'/Uploads/') === 0)
						$images[$k]['url'] = C('base_url').$images[$k]['url'];
				}
			}
			$detail['images'] = $images;
			unset($detail['image_order']);
			$date = $post['date'];
			if((session('wtype') == 1) && $date){ // 管理员查看统计详情
				$smallTime = strtotime($date);
				// if(!$smallTime)
				// 	$smallTime = strtotime(date('Y-m-d',time()));
				$bigTime = $smallTime + 86400;
				$where = array(
					'id' => array('eq',$id),
					'sid' => session('sid'),
					'create_at' => array(array('lt',$bigTime),array('egt',$smallTime),'AND')
				);
				$field = 'count("sid") AS times,uid,head,nickname,create_at';
				$order = 'create_at desc';
				$group = 'uid';
				$retrans = $this->getTempletCount($where,$field,$order,$group);
				if(count($retrans) < 1)
					$retrans = array();
				foreach($retrans as $k => $v){
					// 图像处理
					if(strpos($v['head'],'/Uploads/') === 0)
						$retrans[$k]['head'] = C('base_url').$retrans[$k]['head'];
				}
				// 团队人数
				$app_user = D('app_user');
				$userNums = $app_user->where(array('sid'=>session('sid'),'username'=>array('neq','')))->cache(true,600)->count();
				// 转发人数
				$retransNums = count($retrans);
				$detail['count']['all'] = $userNums;
				$detail['count']['done'] = $retransNums;
				$detail['count']['dont'] = $userNums - $retransNums;
				$detail['count']['personlist'] = $retrans;
			}
			$data = array(
				'code' => 1,
				'message' => '模板详情返回成功！',
				'result' => $detail
			);
		}else{
			$data = array(
				'code' => 0,
				'message' => '模板不存在，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// 转发结果计数
	public function retransTimes()
	{
		$post = I();
		$id = $post['id'];
		$cid = $post['cid'];
		if($id && $cid){
			logger('用户转发九宫格模板计数....');
			M()->startTrans();
			$addResult = $this->postRetrans($id,$cid);
			if($addResult){
				logger('添加转发计数成功...');
				$updateTempletResult = $this->updateTempletRetrans($id,$cid);
				if($updateTempletResult){
					M()->commit();
					logger('更新模板转发计数成功！'."\n");
					$data = array(
						'code' => 1,
						'message' => '计数成功！'
					);
				}else{
					M()->rollback();
					logger('更新模板转发计数失败，记录回滚！'."\n");
					$data = array(
						'code' => 4,
						'message' => '计数失败，请重试！'
					);
				}
			}else{
				M()->rollback();
				logger('添加转发计数失败，记录回滚！'."\n");
				$data = array(
					'code' => 3,
					'message' => '计数失败，请重试！'
				);
			}
		}else{
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// 添加转发记录
	private function postRetrans($id,$cid)
	{
		$retrans = D('weiretransmission');
		$data = array(
			'sid' => session('sid'),
			'cid' => $cid,
			'tid' => $id,
			'uid' => session('uid'),
			'create_at' => time()
		);
		$result = $retrans->add($data);
		return $result;
	}
	// 更新模板转发次数
	private function updateTempletRetrans($id,$cid)
	{
		$templet = D('weitemplet');
		$where = array('id'=>$id);
		$result = $templet->where($where)->setInc('retranstimes');
		return $result;
	}
	// 转发提醒
	public function retransRemind()
	{
		$post = I();
		$id = $post['id'];
		if($id){
			$result = $this->createTempletRemind($id);
			if($result){
				$result = $this->sendRemind($id);
				if($result == 1101){
					logger('消息发送成功，消息接收人可能未登录！');
					logger('JPush消息发送结果：'.$result.'------完毕------'."\n");
					$data = array(
						'code' => 0,
						'message' => '提醒失败，请重试！'
					);
				}else{
					logger('JPush消息发送结果：'.$result.'------完毕------'."\n");
					$data = array(
						'code' => 1,
						'message' => '提醒成功！'
					);
				}
			}else{
				$data = array(
					'code' => 3,
					'message' => '提醒失败，请重试！'
				);
			}
		}else{
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// 更新模板转发提醒 必发提醒
	private function createTempletRemind($id)
	{
		$remind = D('weiremind');
		$date = date('Y-m-d',time());
		$where = array(
			'tid' => $id,
			'create_date' => $date
		);
		$result = $remind->where($where)->find();
		if($result)
			return true; // 能找到今天发的提醒 就不必再写入记录
		$data = array(
			'sid' => session('sid'),
			'tid' => $id,
			'create_date' => date('Y-m-d',time()),
			'create_by' => session('uid')
		);
		$result = $remind->add($data);
		return $result;
	}
	// 发送极光消息
	private function sendRemind($id)
	{
		// 极光推送文件
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
			//Jpush推送 //数据准备
			$msg = array(
				'platform' => 'all',
				'tag' => array('0' => session('sid')), //按照Tag推送本店铺的公告（转发提醒）
				'msg' => array(
					'content' => '您有新的转发任务，点击查看！',
					'title' => '转发提醒',
					'category' => '',
					'message' => array(
						'action' => 6,
						'type' => 1,
						'details' => array(
							'id' => $id
						)
					)
				)
			);
			logger('转发提醒消息数组:'.var_export($msg,TRUE)); //debug
			$result = Jboardcast_Tag($msg);
			return $result;
	}
	// 单条模板转发统计
	private function getTempletCount($where,$field,$order,$group)
	{
		$retran = D('TempletRetrans');
		$retrans = $retran->where($where)->field($field)->order($order)->group($group)->select();
		if(count($retrans) < 1)
			$retrans = array();
		
		return $retrans;
	}
	private function getTempletThumb($order)
	{
		if(count($order)<1){
			logger('解读图片顺序错误！'."\n");
			return false;
		}
		$where = array();
		$where['id'] = array('in',$order);
		$image = D('weiimage');
		$images = $image->where($where)->field('id,url')->select();
		if($images){
			// 图片排序
			foreach($order as $v){
				foreach($images as $x => $y){
					if($v == $y['id']){
						$newImages[] = $y;
					}
				}
			}
			$thumb = $this->getThumb($newImages);
			return $thumb;
		}else{
			logger('读取图片记录错误！'."\n");
			return false;
		}
	}
	// 新创建模板，模板信息和图片一起接收，按上传图片顺序组成缩略图，合成顺序字符串
	public function create()
	{
		$post = I('get.'); //先读取GET数组
		if(empty($post)){ //如果GET数组为空,则读取POST数组
			$post = I('post.');
		}
		$name = $post['name'];
		$content = $post['content'];
		$isShare = $post['isShare'];
		if($name && $content && isset($isShare)){
			// 获取上传图片
			$imgInfo = $this->getUpload($post);
			if($imgInfo){ // 用户上传了图片
				if($imgInfo['result'] == 'success'){
					logger('创建新模板，图片上传成功！'."\n");
					$thumb = $imgInfo['thumb'];
					$order = $imgInfo['order'];
				}else{
					logger('创建新模板，图片处理失败！'."\n");
					$data = array(
						'code' => 4,
						'message' => '创建模板失败，请重试！',
					);
					$this->deleteImg($imgInfo['images'],false);
					exit(json_encode($data));
				}
			}else{ // 用户未上传图片 // 创建无图片的"空"模板
				logger('创建新模板，未上传图片或图片上传失败！');
				$data = array(
					'code' => 6,
					'message' => '未上传图片，请重试！',
				);
				exit(json_encode($data));
			}
			M()->startTrans();
			$newId = $this->createTemplet($name,$content,$order,$thumb,$isShare);
			if($newId){
				logger('创建新模板，添加模板记录成功！');
				if(!empty($order)){
					$order = chanslate_json_to_array($order);
					$updateResult = $this->updateImageTem($newId,$order);
				}else{
					$updateResult = true;
				}
				if($updateResult){
					logger('添加模板和图片关系记录成功！'."\n");
					M()->commit();
					$data = array(
						'code' => 1,
						'message' => '创建模板成功！',
						'result' => $newId
					);
				}else{
					M()->rollback();
					logger('添加模板和图片关系记录失败！'."\n");
					$data = array(
						'code' => 5,
						'message' => '创建模板失败，请重试！'
					);
				}
			}else{
				M()->rollback();
				$this->deleteImg($imgInfo['images'],true); // 删除图片
				logger('创建新模板，添加模板记录失败！'."\n");
				$data = array(
					'code' => 3,
					'message' => '创建模板失败，请重试！'
				);
			}
		}else{
			logger('创建新模板参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// BASE64方式接受图片
	private function getUpload($post)
	{
		for($i=1;$i<10;$i++){
			$postName = 'file'.$i;
			$img = $post[$postName];
			if(empty($img)){ // 没有发现图片就终止
				logger('没有接收的图片了！');
				break;
			}else{
				logger('接收到图片！');
				$image = base64_decode($img);
				$fileName = './Uploads/weiretrans/templet/'.$this->getRand(10).'.jpg';
				$size = file_put_contents($fileName, $image);
				if($size){
					$fileName = ltrim($fileName,'.');
					$images[] = array(
	               		'url' => $fileName,
	               		'create_at' => time()
	               	);
				}
			}
		}
		if(count($images) >= 1){
			$image = D('weiimage');
	        $result = $image->addAll($images);
	        if($result){
	        	logger('模板图片记录添加成功！');
	        	$order = '["';
	        	$max = count($images);
	        	for($i=0;$i<$max;$i++){
	        		$imgId = $result + $i; // 图片记录ID
	        		$images[$i]['id'] = $imgId;
	        		$order .= $imgId.'","';
	        	}
	        	$order = rtrim($order,',"');
	        	$order .= '"]';
	        	// 获取缩略图
	        	$thumb = $this->getThumb($images);
	        	$result = array( // 结果数组
	        		'thumb' => $thumb,
	        		'order' => $order,
	        		'result' => 'success'
	        	);
	        }else{
	        	logger('模板图片记录添加失败！');
	        	$result = array( // 结果数组
	        		'thumb' => 0,
	        		'order' => 0,
	        		'result' => 'failed',
	        		'images' => $images
	        	);
	        }
	    }else{
	    	logger('没有接收到任何图片！');
	    	return false;
	    }
        return $result;
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
			if($images[$i]['url']){
				$source = $this->getImageSource($images[$i]['url']);
				$url = '.'.$images[$i]['url'];
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
	// 添加新模板记录
	private function createTemplet($name,$content,$order,$thumb,$isShare)
	{
		$templet = D('weitemplet');
		$data = array(
			'sid' => session('sid'),
			'author' => session('uid'),
			'name' => $name,
			'content' => $content,
			'create_at' => time(),
			'create_by' => session('uid'),
			'thumb' => $thumb
		);
		if(!empty($order)) // 如果有图片顺序，设置。 默认没有图片顺序。
			$data['image_order'] = $order;
		if($isShare == 0) // 如果不共享，设置值。默认共享！
			$data['isShare'] = 0;
		$result = $templet->add($data);
		return $result;
	}
	// 删除处理错误的图片和记录
	private function deleteImg($images,$type)
	{
		// 删除图片
		$ids = '';
    	foreach($images as $k => $v){
    		$url = '.'.$v['url'];
    		unset($url);
    		$ids .= $v['id'].',';
    	}
    	if($type){ // 清除图片记录
    		$ids = rtrim($ids,',');
	    	if(!empty($ids)){
	    		$image = D('weiimage');
	    		$result = $image->delete($ids);
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
		$image = D('weiimgtem');
		$result = $image->addAll($data);
		return $result;
	}
	// 更新模板
	public function update()
	{
		$post = I();
		$id = $post['id'];
		$name = $post['name'];
		$content = $post['content'];
		$order = $post['order'];
		if($id && ($name || $content || $order)){
			$where = array('id'=>$id);
			$field = 'image_order AS "order",sid,name,content,thumb';
			$info = $this->getTempletDetail($where,$field);
			if(session('sid') == $info['sid']){
				// 修改团队内模板
				$data = array(
					'name' => $name,
					'content' => $content,
					'image_order' => $order,
					'modify_at' => time(),
					'modify_by' => session('sid')
				);
				$data = array_filter($data);
				if(empty($order)){
					// 只修改名称和说明
					$result = $this->updateTemplet($where,$data);
					if($result){
						logger('只修改名称和说明，保存更新记录成功！'."\n");
						$data = array(
							'code' => 1,
							'message' => '更新成功！',
							'result' => $id
						);
					}else{
						logger('只修改名称和说明，保存更新记录失败！'."\n");
						$data = array(
							'code' => 0,
							'message' => '更新失败，请重试！'
						);
					}
				}else{
					// 修改了图片顺序或更新了图片
					$newOrder = chanslate_json_to_array($order);
					$thumb = $this->getTempletThumb($newOrder);
					if($thumb){
						$data['thumb'] = $thumb;
						M()->startTrans();
						$result = $this->updateTemplet($where,$data);
						if($result){
							$result = $this->checkImgTem($info['order'],$newOrder,$id);
							if($result){
								M()->commit();
								logger('更新了图片或图片顺序，全部成功！'."\n");
								$data = array(
									'code' => 1,
									'message' => '更新成功！',
									'result' => $id
								);
							}else{
								M()->rollback();
								logger('更新了图片或图片顺序，处理图片和模板关系失败！'."\n");
								$data = array(
									'code' => 5,
									'message' => '更新失败，请重试！'
								);
							}
						}else{
							M()->rollback();
							logger('更新了图片或图片顺序，添加新记录失败！'."\n");
							$data = array(
								'code' => 4,
								'message' => '更新失败，请重试！'
							);
						}
					}else{
						logger('更新了图片或图片顺序，生成新缩略图失败！'."\n");
						$data = array(
							'code' => 3,
							'message' => '更新失败，请重试！'
						);
					}
				}
			}else{
				// 将非团队模板转换为团队内部模板
				if(empty($name))
					$name = $info['name'];
				if(empty($content))
					$content = $info['content'];
				if(empty($order)){
					$order = $info['order']; // JSON
					$thumb = $info['thumb'];
				}else{
					$newOrder = chanslate_json_to_array($order); // 数组
					$thumb = $this->getTempletThumb($newOrder);
				}
				M()->startTrans();
				$result = $this->createTemplet($name,$content,$order,$thumb);
				if($result){
					$addResult = $this->addImgTem($result,$order);
					if($addResult){
						M()->commit();
						logger('转化为团队内部模板，添加新记录失败！'."\n");
						$data = array(
							'code' => 1,
							'message' => '更新成功！',
							'result' => $result
						);
					}else{
						M()->rollback();
						logger('转化为团队内部模板，添加图片模板关系新记录失败！'."\n");
						$data = array(
							'code' => 7,
							'message' => '更新失败，请重试！'
						);
					}
				}else{
					M()->rollback();
					logger('转化为团队内部模板，添加新记录失败！'."\n");
					$data = array(
						'code' => 6,
						'message' => '更新失败，请重试！'
					);
				}
			}
		}else{
			logger('创建新模板参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	private function getTempletDetail($where,$field)
	{
		$templet = D('weitemplet');
		$info = $templet->where($where)->field($field)->find();
		return $info;
	}
	private function updateTemplet($where,$data)
	{
		$templet = D('weitemplet');
		$result = $templet->where($where)->save($data);
		return $result;
	}
	// 清除就图片关系 添加新图片关系
	private function checkImgTem($order,$newOrder,$id)
	{
		if(!empty($order)){
			$order = chanslate_json_to_array($order);
			$oMax = count($order);
			$nMax = count($newOrder);
			// 需清除的
			// $del = array();
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
			$imgTem = D('weiimgtem');
			$der = $imgTem->where($del)->delete();
			if(!$der)
				return false;
			$adr = $imgTem->addAll($add);
			if(!$adr)
				return false;
			return true;
		}else{
			$result = $this->updateImageTem($id,$newOrder);
			return $result;
		}
	}
	private function addImgTem($id,$order)
	{
		$order = chanslate_json_to_array($order);
		$add = array();
		foreach($order as $v){
			$add[] = array(
				'iid' => $v,
				'tid' => $id,
				'create_at' => time()
			);
		}
		$imgTem = D('weiimgtem');
		$result = $imgTem->addAll($add);
		return $result;
	}
	// 删除模板 软删除
	public function delete()
	{
		$post = I();
		$id = $post['id'];
		if($id){
			$templet = D('weitemplet');
			$info = $templet->where(array('id'=>$id))->field('sid,author')->find();
			if($info['author'] == session('uid') || ((session('wtype') == 1) && $info['sid'] == session('sid'))){
				$result = $templet->where(array('id'=>$id))->save(array('delete_at'=>time(),'delete_by'=>session('uid')));
				if($result){
					logger('删除模板，删除成功！'."\n");
					$data = array(
						'code' => 1,
						'message' => '删除成功！'
					);
				}else{
					logger('删除模板，删除失败！'."\n");
					$data = array(
						'code' => 0,
						'message' => '删除失败，请重试！'
					);
				}
			}else{
				logger('删除模板，权限不足！'."\n");
				$data = array(
					'code' => 3,
					'message' => '权限不足！'
				);
			}
		}else{
			logger('删除模板参数不全！'."\n");
			$data = array(
				'code' => 2,
				'message' => '参数不全，请重试！'
			);
		}
		exit(json_encode($data));
	}
	// 我的模板
	public function myTemplet()
	{
		$post = I();
		$date = $post['date'];
		$where = array(
			'author' => session('uid'),
			'delete_at' => array('eq',0)
		);
		$field = 'id,name,thumb,category';
		$order = 'retranstimes desc,create_at desc,weight';
		$group = 'id';
		$retrans = $this->getMyTemplet($date,$where,$field,$order,$group);
		if(count($retrans) < 1)
			$retrans = array();
		$data = array(
			'code' => 1,
			'message' => '我的模板返回成功！',
			'result' => $retrans,
			'isEnd' => 0
		);
		// 判断是否已到最后
		$startTime = strtotime($date);
		if(!$startTime)
			$startTime = strtotime(date('Y-m-d',time()));
		if($startTime < C('startTime') || ($startTime-86400*C('queryDay')) <= C('startTime'))
			$data['isEnd'] = 1;
		exit(json_encode($data));
	}
	// 获取我的模板的转发的记录
	private function getMyTemplet($date,$where,$field,$order,$group)
	{
		$allMyTemplets = array();
		$startTime = strtotime($date);
		if(!$startTime)
			$startTime = strtotime(date('Y-m-d',time()));
		for($i=0;$i<C('queryDay');$i++){
			$bigTime = strtotime(date('Y-m-d',$startTime+86400));
			$smallTime = strtotime(date('Y-m-d',$startTime));
			$where['create_at'] = array(array('lt',$bigTime),array('egt',$smallTime),"AND");
			$templet = D('MyTemplet');
			$myTemplets = $templet->where($where)->field($field)->order($order)->group($group)->select();
			if(count($myTemplets) >= 1){
				$max = count($allMyTemplets);
				foreach($myTemplets as $k => $v){
					$allMyTemplets[$max] = $v;
					// 每条记录+日期信息
					$allMyTemplets[$max]['date'] = date('Y-m-d',$startTime);
					// 图像处理
					if(strpos($allMyTemplets[$max]['thumb'],'/Uploads/') === 0)
						$allMyTemplets[$max]['thumb'] = C('base_url').$allMyTemplets[$max]['thumb'];
					$max++;
				}
			}
			$startTime -= 86400;
		}
		if(count($allMyTemplets) >= 1)
			$allMyTemplets = $this->getMyTempletCount($allMyTemplets);
		return $allMyTemplets;
	}
	
	// 多一步查询，查询wo的模板转发了多少次
	private function getMyTempletCount($templets)
	{
		$retrans = D('weiretransmission');
		foreach($templets as $k => $v){
			$templets[$k]['users'] = 0;
			$templets[$k]['useis'] = 0;
			$templets[$k]['times'] = 0;
			$where = array(
				'tid' => $v['id']
			);
			$field = 'COUNT("uid") AS times,COUNT(DISTINCT "uid") AS users,CASE WHEN uid = '.session('uid').' THEN 1 ELSE 0 END AS useis';
			$result = $retrans->where($where)->field($field)->group('id')->select();
			if($result){
				$templets[$k]['users'] = $result['users'];
				$templets[$k]['useis'] = $result['useis'];
				$templets[$k]['times'] = $result['times'];
			}
		}
		return $templets;
	}
}
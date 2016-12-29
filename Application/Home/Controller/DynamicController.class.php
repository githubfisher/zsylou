<?php
namespace Home\Controller;

use Think\Controller;

class DynamicController extends Controller
{
	public function _initialize()
	{
		$scheck = A('SessionCheck');
		$scheck->index();
		header("content-type:text/html; charset=utf-8;");
	}

	// 动态列表 3天一返回
	public function lists()
	{
		$get = I();
		$date = $get['date']; // 不传或为空表示今天起
		if($date){
			$time = strtotime($date)-C('dynamic_time');
			if(!$time || ($time < (time()-864000))){
				$data = array(
					'code' => 0,
					'message' => '日期错误，请重试！'
				);
				exit(json_encode($data));
			}
		}else{
			$time = strtotime(date('Y-m-d',time()-C('dynamic_time')));
		}
		// 任务动态
		$DTL = D('DynamicTaskList');
		$task_dynamic = $DTL->where(array('cid'=>session('uid'),'time'=>array('egt',$time)))->cache(true,60)->select();
		// 文件动态
		$DFL = D('DynamicFileList');
		$file_dynamic = $DFL->where(array('cid'=>session('uid'),'time'=>array('egt',$time)))->cache(true,60)->select();
		// 合并动态
		$dynamic = array_merge($task_dynamic,$file_dynamic);
		
		$data = array(
			'code' => 1,
			'message' => '动态返回成功！',
			'result' => $this->sort_by_project($this->sort_by_date($dynamic)) // 数组分组并排序
		);
		exit(json_encode($data));
	}

	private function sort_by_date($d)
	{
		foreach($d as $k => $v){
			if($k == 0){
				$new[0]['date'] = date('Y-m-d',$v['time']);
				$new[0]['lists'][] = $v;
			}else{
				$max = count($new);
				$n = 1;
				foreach($new as $x => $y){
					$date = date('Y-m-d',$v['time']);
					if($date == $y['date']){
						$new[$x]['lists'][] = $v;
						break;
					}else{
						if($n == $max){
							$new[$max]['date'] = date('Y-m-d',$v['time']);
							$new[$max]['lists'][] = $v;
						}
						$n++;
					}
				}
			}
		}
		return $new;
	}

	private function sort_by_project($d)
	{
		foreach($d as $k => $v){
			foreach($v['lists'] as $m => $n){
				if($m == 0){
					$d[$k]['dynamics'][0]['project'] = $n['project'];
					$d[$k]['dynamics'][0]['pid'] = $n['pid'];
					$d[$k]['dynamics'][0]['lists'][] = $n;
				}else{
					$max = count($d[$k]['dynamics']);
					$i = 1;
					foreach($d[$k]['dynamics'] as $x => $y){
						if($n['pid'] == $y['pid']){
							$d[$k]['dynamics'][$x]['lists'][] = $n;
							break;
						}else{
							if($i == $max){
								$d[$k]['dynamics'][$max]['pid'] = $n['pid'];
								$d[$k]['dynamics'][$max]['project'] = $n['project'];
								$d[$k]['dynamics'][$max]['lists'][] = $n;
							}
							$i++;
						}
					}
				}
			}
			unset($d[$k]['lists']);
		}
		return $d;
	}
}
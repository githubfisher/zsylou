<?php
// 调用CURL函数，组合访问参数和URL，得到返回值
function getVersion($dogid){
		$url = 'http://114.215.121.114/getversion.php?id='.$dogid;
    	$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_HEADER,0);
		// curl_setopt($ch, CURLOPT_ENCODING, "");
		$output = curl_exec($ch);
		curl_close($ch);
	    return $output;
    }
// 调用CURL函数，组合访问参数和URL，得到返回值
function getXML($url,$xml){
		// $header = 'Content-type:text/xml;';
		$url .= $xml;
    	$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_HEADER,0);
		// curl_setopt($ch, CURLOPT_ENCODING, "");
		$output = curl_exec($ch);
		curl_close($ch);
	    return $output;
    }
// 日志函数
function logger($log_content){ 
	//$folder = "./Log/";
	$folder = './Log/'.date('Y',time()).'/'.date('m',time()).'/'.date('d',time()).'D/';
	if(!file_exists($folder)){
		mkdir($folder,0777,TRUE);
	}
	for($i=80;$i--;$i>0){
		$max_size = 100000;   
    	$log_filename = $folder.'/'.date('Ymd',time()).'-'.$i.'.txt';
		if(file_exists($log_filename) && (abs(filesize($log_filename)) >= $max_size)){
			$next = $i + 1;
			$log_filename = $folder.'/'.date('Ymd',time()).'-'.$next.'.txt';
			//新内容写入日志，内容前加上时间， 后面加上换行， 以追加的方式写入
    		file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content." \r\n", FILE_APPEND);  
		}elseif(file_exists($log_filename) && (abs(filesize($log_filename)) < $max_size)){
			//写入日志，内容前加上时间， 后面加上换行， 以追加的方式写入
			file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content." \r\n", FILE_APPEND); 
		}else{
			if($i == 1){
				//写入日志，内容前加上时间， 后面加上换行， 以追加的方式写入
				file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content." \r\n", FILE_APPEND); 
			}
		}
	} 
}
// 将数组转换成XML字符串
function transXML($arr){
	$xmlTpl = "<aa>%s</aa><yy>%s</yy><uu>%s</uu>";
	$result = sprintf($xmlTpl,$arr['operation'],$arr['dogid'],$arr['username']);
	return $result;
}
//二维数组按指定项指定顺序排序
function array_sort($array,$key,$sort='ASC'){
	$array_temp = array();
	//循环数组，将需要排序的键值取出组成新数组
	foreach($array as $k => $v){
		$array_temp[$k] = $v[$key];
	}
	// 判断排序类型
	if($sort === 'ASC'){
		asort($array_temp);
	}else{
		arsort($array_temp);
	}
	$result_array = array();
	// 按照新顺序重新组装数组
	foreach($array_temp as $k => $v){
		$result_array[] = $array[$k];
	}
	return $result_array;
}
//Jpush 函数 Start //
//极光推送
//初始化Jpush
function jpush_init(){
	$app_key = C('DevKey');
	$master_secret = C('ApiDevSecret');
	// 初始化
	$client = new \Client($app_key, $master_secret);
	return $client;
}
// Jpush简单推送函数
function simple_push($platform = 'all' ,$content){
	// 初始化
	$client = jpush_init();
	// 简单推送示例
	$result = $client->push()
    ->setPlatform($platform) //'all'
    ->addAllAudience()
    ->setNotificationAlert($content) //'Hi, JPush'
    ->send();

	echo 'Result=' . json_encode($result) . '</br>';
	logger('JPush---简单推送----结果：'.json_encode($result).'------完毕------');
}
// 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
function push($platform = 'all',$alias,$tags,$content,$android,$ios,$msg,$option){
	// 初始化
	$client = jpush_init();
	$result = $client->push()
				    ->setPlatform($platform) //array('ios', 'android')
				    ->addAlias($alias) //'alias1'
				    ->addTag($tags) //array('tag1', 'tag2')
				    ->setNotificationAlert($content) //'Hi, JPush'
				    ->addAndroidNotification($android['alert'],$android['title'],1,$android['extras']) //'Hi, android notification', 'notification title', 1, array("key1"=>"value1", "key2"=>"value2")
				    ->addIosNotification($ios['alert'],$ios['title'],Config::DISABLE_BADGE,true,$ios['category'],$ios['extras']) //"Hi, iOS notification", 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("key1"=>"value1", "key2"=>"value2")
				    ->setMessage($msg['content'],$msg['title'],$msg['type'],$msg['extras']) //"msg content", 'msg title', 'type', array("key1"=>"value1", "key2"=>"value2")
				    ->setOptions($option['tips'],$option['time'],$option['order'],$option['toggle']) //100000, 3600, null, false
				    ->send();
	echo 'Result=' . json_encode($result) . '</br>';
	logger('JPush---完整推送----结果：'.json_encode($result).'------完毕------');
}
//定时推送消息
function set_time_push($platform = 'all',$alert,$schedule){
	// 初始化
	$client = jpush_init();

	$payload = $client->push()
				    ->setPlatform($platform)  //"all"
				    ->addAllAudience()
				    ->setNotificationAlert($alert) //"Hi, 这是一条定时发送的消息"
				    ->build();

	// 创建一个2016-12-22 13:45:00触发的定时任务
	$response = $client->schedule()->createSingleSchedule($schedule['message'], $payload, $schedule['time']); //"每天14点发送的定时任务", $payload, array("time"=>"2016-12-22 13:45:00")
	echo 'Result=' . json_encode($response) . '</br>';
	logger('JPush---定时推送----结果：'.json_encode($result).'------完毕------');
}
//Jpush,发送短信推送消息
function sms_jpush($platform = 'all',$tags,$alert,$sms){
	// 初始化
	$client = jpush_init();

	$result = $client->push()
				    ->setPlatform($platform)  //'all'
				    ->addTag($tags)  //'tag1'
				    ->setNotificationAlert($alert) //"Hi, JPush SMS"
				    ->setSmsMessage($sms['message'], $sms['time'])  //'Hi, JPush SMS', 60
				    ->send();

	echo 'Result=' . json_encode($result) . '</br>';
	logger('JPush---短信推送----结果：'.json_encode($result).'------完毕------');
}
//Jpush ,自定义简单发送
function jpush($array){
	// 初始化
	$client = jpush_init();
	// 简单推送示例
	$result = $client->push()
    ->setPlatform($array['platform']) //'all'
    ->addAlias($array['alias'])
    ->addAndroidNotification($array['msg']['content'],$array['msg']['title'],2,$array['msg']['message'])
    ->addIosNotification($array['msg']['content'],$array['msg']['title'],Config::DISABLE_BADGE,true,$array['msg']['category'],$array['msg']['message'])
    ->setOptions(100000, 3600, null, false) //100000, 3600, null, false
    ->send();

	return json_encode($result);
}
//Jpush 自定义广播函数__全平台全用户广播
function Jboardcast($array){
	// 初始化
	$client = jpush_init();
	// 简单推送示例
	$result = $client->push()
    ->setPlatform($array['platform']) //'all'
    ->addAllAudience()
    ->addAndroidNotification($array['msg']['content'],$array['msg']['title'],2,$array['msg']['message'])
    ->addIosNotification($array['msg']['content'],$array['msg']['title'],Config::DISABLE_BADGE,true,$array['msg']['category'],$array['msg']['message'])
    ->setOptions(100000, 3600, null, false) //100000, 3600, null, false
    ->send();

	return json_encode($result);
}
//Jpush 自定义广播函数_全平台按标签tag广播 2016-5-24
function Jboardcast_Tag($array){
	// 初始化
	$client = jpush_init();
	// 简单推送示例
	$result = $client->push()
    ->setPlatform($array['platform']) //'all'
    ->addTag($array['tag'])
    ->addAndroidNotification($array['msg']['content'],$array['msg']['title'],2,$array['msg']['message'])
    ->addIosNotification($array['msg']['content'],$array['msg']['title'],Config::DISABLE_BADGE,true,$array['msg']['category'],$array['msg']['message'])
    ->setOptions(100000, 3600, null, false) //100000, 3600, null, false
    ->send();

	return json_encode($result);
}
function getDevices(){
	// 初始化
	$client = jpush_init();

	//从现在不如写一个类(控制器),各方法写进去.随时调用.

	// 获取指定设备的Mobile,Alias,Tags等信息
	$result = $client->device()->getDevices($REGISTRATION_ID1);
	return $result;
}
function getTags(){
	// 初始化
	$client = jpush_init();
	// 获取Tag列表
	$result = $client->device()->getTags();
	return $result;
}
function isDeviceInTag(){
	// 初始化
	$client = jpush_init();
	// 判断指定RegistrationId是否在指定Tag中
	$result = $client->device()->isDeviceInTag($REGISTRATION_ID1, $TAG1);
	return $result;
}
function getAliasDevices(){
	// 初始化
	$client = jpush_init();
	// 获取指定Alias下的设备
	$result = $client->device()->getAliasDevices($ALIAS1);
	return $result;
}
function updateDevice(){
	// 初始化
	$client = jpush_init();
	// 更新指定的设备的Alias(亦可以增加/删除Tags)
	$result = $client->device()->updateDevice($REGISTRATION_ID1, $ALIAS1);
	return $result;
}
function updateTag(){
	// 初始化
	$client = jpush_init();
	// 增加指定Tag下的设备(亦可以删除设备)
	$result = $client->device()->updateTag($TAG1, array($REGISTRATION_ID1, $REGISTRATION_ID2));
	return $result;
}
function deleteAlias(){
	// 初始化
	$client = jpush_init();
	// 删除指定Alias
	$result = $client->device()->deleteAlias($ALIAS1);
	return $result;
}
//Jpush 函数 END //
//中文时间处理函数
function chtimetostr($string){
	$array = array(
		0 => '年',
		1 => '月',
		2 => '日',
		3 => '时',
		4 => '分',
		5 => '秒'
	);
	foreach($array as $k => $v){
		if($k == 0 || $k == 1){
			if (strstr($string,$v)) {
					$string = str_replace($v,'-',$string);
				}
		}elseif($k == 3 || $k == 4){
			if (strstr($string,$v)) {
					$string = str_replace($v,':',$string);
				}
		}else{
			if (strstr($string,$v)) {
					$string = str_replace($v,'',$string);
				}
		}
	}
	return $string;
}
// 环信处理函数，初始化
function easemob_init(){
	$option = C('Easemob');
	$client = new \Easemob($option);
	return $client;
}
function easemob_create_user($user,$pwd){
	//初始化
	$client = easemob_init();
	// 新建用户
	$result  = $client->createUser($user,$pwd);
	return $result;
}
function easemob_create_users($users){
	//初始化
	$client = easemob_init();
	// 新建用户
	$result  = $client->createUsers($users);
	return $result;
}
function delete_easemob_user($user){
	//初始化
	$client = easemob_init();
	// 删除用户
	$result  = $client->deleteUser($user);
	return $result;
}
function modify_easemob_pwd($user,$pwd){
	//初始化
	$client = easemob_init();
	//修改密码
	$result = $client->resetPassword($user,$pwd);
	return $result;
}
function easemob_edit_nickname($user,$nickname){
	//初始化
	$client = easemob_init();
	//修改密码
	$result = $client->editNickname($user,$nickname);
	return $result;
}
function easemob_add_friend($user,$friend){
	//初始化
	$client = easemob_init();
	//添加好友
	$result = $client->addFriend($user,$friend);
	return $result;
}
function easemob_rm_friend($user,$friend){
	//初始化
	$client = easemob_init();
	//添加好友
	$result = $client->deleteFriend($user,$friend);
	return $result;
}
function easemob_get_friends($user){
	//初始化
	$client = easemob_init();
	//获取好友列表
	$result = $client->showFriends($user);
	return $result;
}
function easemob_get_user($user){
	//初始化
	$client = easemob_init();
	//获取用户信息
	$result = $client->getUser($user);
	return $result;
}
function easemob_get_users($limit = 50){
	//初始化
	$client = easemob_init();
	//获取全部用户 不分页
	$result = $client->getUsers($limit);
	return $result;
}
function easemob_get_users_for_pages($limit = 10,$cursor = ''){
	//初始化
	$client = easemob_init();
	//获取全部用户 不分页
	$result = $client->getUsersForPage($limit,$cursor);
	return $result;
}
function easemob_read_cursor($filename = 'userfile.txt'){
	//初始化
	$client = easemob_init();
	//读取cursor文件内容 游标
	$result = $client->readCursor($filename);
	return $result;
}
function easemob_get_groups($limit = 50){
	//初始化
	$client = easemob_init();
	//获取全部用户 不分页
	$result = $client->getGroups($limit);
	return $result;
}
function easemob_get_the_group($groupid){ //数组
	//初始化
	$client = easemob_init();
	//获取全部用户 不分页
	$result = $client->getGroupDetail($groupid);
	return $result;
}
function easemob_create_group($options){ //数组
	//初始化
	$client = easemob_init();
	//创建群组
	$result = $client->createGroup($options);
	return $result;
}
function easemob_edit_group($groupid,$options){ //数组
	//初始化
	$client = easemob_init();
	//创建群组
	$result = $client->modifyGroupInfo($groupid,$options);
	return $result;
}
function easemob_add_member($groupid,$user){ //数组
	//初始化
	$client = easemob_init();
	//单个添加成员
	$result = $client->addGroupMember($groupid,$user);
	return $result;
}
function easemob_add_members($groupid,$users){ //数组
	//初始化
	$client = easemob_init();
	//批量添加成员
	$result = $client->addGroupMembers($groupid,$users);
	return $result;
}
function easemob_rm_member($groupid,$user){ 
	//初始化
	$client = easemob_init();
	//单个添加成员
	$result = $client->deleteGroupMember($groupid,$user);
	return $result;
}
function easemob_rm_members($groupid,$users){ //数组
	//初始化
	$client = easemob_init();
	//批量添加成员
	$result = $client->deleteGroupMembers($groupid,$users);
	return $result;
}
function easemob_dismiss_group($groupid){
	//初始化
	$client = easemob_init();
	//解散群组
	$result = $client->deleteGroup($groupid);
	return $result;
}
//转译数据库中读取的JSON字符串
function chanslate_json_to_array($json){
	$json = str_replace('&quot;','"',$json);
	// logger('替换转译符号后,字符串:'.$json); //debug
	$array = json_decode($json,TRUE);
	// logger('转译后数组:'.var_export($array,TRUE)); //debug
	return $array;
}
//转译数据库中存储的HTML代码中的<>括号的转译符
function chansfer_to_html($str){
	$str = str_replace('&lt;','<', $str);
	$str = str_replace('&gt;', '>', $str);
	$str = str_replace('&quot;','"', $str);
	return $str;
}
//输出文件夹目录
function traverse($path = '.') {
    $current_dir = opendir($path);    //opendir()返回一个目录句柄,失败返回false
    while(($file = readdir($current_dir)) !== false) {    //readdir()返回打开目录句柄中的一个条目
        $sub_dir = $path . DIRECTORY_SEPARATOR . $file;    //构建子目录路径
        if($file == '.' || $file == '..') {
            continue;
        }else if(is_dir($sub_dir)) {    //如果是目录,进行递归
            echo 'Directory ' . $file . ':<br>'; //如果是文件夹，输出文件夹名称
            traverse($sub_dir);
        }else{    //如果是文件,直接输出
           echo 'File in Directory ' . $path . '/' . $file .'<br>';
        }
    }
}
// 返回单层文件夹目录数组
function traverse_sigle_folder($path = '.') {
    $current_dir = opendir($path);    //opendir()返回一个目录句柄,失败返回false
    $i = 0;
    $folder = array(); //用数组记录文件夹结构
    while(($file = readdir($current_dir)) !== false) {    //readdir()返回打开目录句柄中的一个条目
        $sub_dir = $path . DIRECTORY_SEPARATOR . $file;    //构建子目录路径
        if($file == '.' || $file == '..') {
            continue;
        }else if(is_dir($sub_dir)) {    //如果是目录,进行递归
        	$folder[$i]['file'] = $file; 
        	$folder[$i]['type'] = 'directory';
            $i++;
        }else{    //如果是文件,直接输出
        	$folder[$i]['file'] = $file; 
        	$type = ltrim(strchr($file,'.'),'.');
        	switch($type){
        		case 'jpg':
        		case 'JPG':
        		case 'gif':
        		case 'GIF':
        		case 'jpeg':
        		case 'JPEG':
        		case 'bmp':
        		case 'BMP':
        		case 'png':
        		case 'PNG':
        			$folder[$i]['type'] = 'image';
        			break;
        		default:
        			$folder[$i]['type'] = 'file';
        			break;
        	}
        	$i++;
        }
    }
    return $folder;
}
function rm_dir($path){
	$dh = opendir($path);
	while($file = readdir($dh)){
	    if($file != '.' && $file != '..'){
	     	$fullpath = $path.$file;
	      	if(!is_dir($fullpath)) {
	          	unlink($fullpath);
	      	}else{
	          deldir($fullpath);
	      	}
	    }
	}
	closedir($dh);
	//删除当前文件夹：
	if(rmdir($path)){
	   return true;
	}else{
	   return false;
	}
}
function PinYinInit(){
	$client = new \PinYin();
	return $client;
}
function getAllPY($string){
	$client = PinYinInit();
	$result = $client->getAllPY($string);
	return $result;
}
function getFirstPY($string){
	$client = PinYinInit();
	$result = $client->getFirstPY($string);
	return $result;
}
function get_time_length($time){
	$str = '';
	if($time >= 31536000){
		$num = floor($time/31536000);
		$str = $num.'年';
		$left = $time%31536000;
		if($left){
			$str .= get_time_length($left);
		}
	}else if($time >= 2628000){
		$num = floor($time/2628000);
		$str = $num.'月';
		$left = $time%2628000;
		if($left){
			$str .= get_time_length($left);
		}
	}else if($time >= 86400){
		$num = floor($time/86400);
		$str = $num.'日';
		$left = $time%86400;
		if($left){
			$str .= get_time_length($left);
		}
	}else if($time >= 3600){
		$num = floor($time/3600);
		$str = $num.'时';
		$left = $time%3600;
		if($left){
			$str .= get_time_length($left);
		}
	}else if($time >= 60){
		$num = floor($time/60);
		$str = $num.'分';
		$left = $time%60;
		if($left){
			$str .= get_time_length($left);
		}
	}else{
		$str = $time.'秒';
	}
	return $str;
}
?>
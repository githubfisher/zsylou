<?php
//环信设置信息
$easemob = array(
	'client_id' => 'YXA68kKoALQxEeWZ3Df4n_dbkA',
	'client_secret' => 'YXA6AfrO_bjWl_qMhoZH-fexpKa6i6Y',
	'org_name' => 'tomsz2015',
	'app_name' => 'photographic'
);
return array(
	//'配置项'=>'配置值'
	"DB_TYPE"=>"mysql",
	"DB_HOST"=>"localhost",
	"DB_NAME"=>"ylou",
	"DB_USER"=>"root",
	"DB_PWD"=>"root",
	"DB_PORT"=>"3306",
	"DB_PREFIX"=>"",
	'DB_CHARSET'=>'utf8',
	'DevKey' => 'de578d321f948bee7fbbcdea', //'3ed10c2cfd338aa41228d7fc',//'e0d6ccc14593f8b18bb806b1', //'41f71a3c16eceb6a69406a65', //极光推送 账号
	'ApiDevSecret' => '643718aad0ea09aed9784b92',//'855c60bf984abb992325eea1',//'dd638e232fc55c091915220c',//'c39e8ac03c6a8c66f6c0a602', //极光推送密码
	'Easemob' => $easemob,
	'base_url' => 'http://zsylou.wxwkf.com',
	// 九宫格微信转发配置
	'publicSid' => 402, // 公共模板生产店铺ID
	'groupCategory' => 1, // 团队模板类ID
	'startTime' => 1481558400, // 2016-12-13
	'queryDay' => 7,
);
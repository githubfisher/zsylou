<?php
// require_once 'HttpClient.class.php';
class dlswSdk {
	const HOST = 'www.lx198.com';
	final private static function __replyResult($xmlStr) {
		$doc = new DOMDocument ();
		$doc->loadXML ( $xmlStr );
		print($xmlStr);
		$xpath = new DOMXpath ( $doc );
		$arr = $xpath->query ( '/reply/ErrorCode/text()', $doc );
		$ret = '';
		foreach ( $arr as $o ) {
			$ret = $o->nodeValue;
		}
		return $ret;
	}
	final public static function sendSms($content,$mobiles,$user='707752331@qq.com', $password='ZC4000191951') {
		$client = new HttpClient (self::HOST);
		$client->setDebug (false);
		if (!$client->post('/sdk/send',array(
				'accName'  => $user,
				'accPwd'   => strtoupper(md5($password)),
				'bizId'    => date ( 'YmdHis' ),
				'content'  => mb_convert_encoding ( $content, 'UTF-8', 'UTF-8' ),
				'aimcodes' => $mobiles,
				'dataType' => "xml"
		))) {
			return '-10000';
		} else {
			// return self::__replyResult ( $client->getContent () );
			return true;
		}
	}
}
?>
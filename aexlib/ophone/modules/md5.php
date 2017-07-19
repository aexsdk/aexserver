<?php
define("_KEY_", "ophone");
// define("_KEY_","123456");
if (!extension_loaded('md5ext')) {
	dl('php_md5ext.' . PHP_SHLIB_SUFFIX);
}
/**
 * 函数名：ophone_encrypt($string)
 * @string :需要进行加密的字符串；
 * 说明：该函数为在ophone请求中使用的加密方式
 * 时间：2009-03-18
 * author：se7en
 * */
function ophone_encrypt($string){
	$encryptString  =  buf2hex(md5_encrypt($string,_KEY_));
	return   $encryptString;
}
/*
 *api_param:
action=recharge
bsn=MT012345678901234567
imei=354631015016640
pno=8615014020610
pin=12806000006
pass=888888
prefix=0086
pid=20100419
vid=100010
rpin=25339
rpass=972662

 * */
//$string = "ophone_bsn,357116020333630,33000169,332550,13096969968,15191493514";
//active
$string   = "query,MT012345678901234567,357586001814844,13086233855,5188000011,139168,25336,663093";
echo $encrptstring = ophone_encrypt($string);
echo "\r\n";
/**
 * 函数名：ophone_decrypt($string)
 * @string :需要进行解密的字符串；
 * 说明：该函数为在ophone请求中使用的解密方式
 * 时间：2009-03-18
 * author：se7en
 * */
function ophone_decrypt($string){
	$decryptString  =   md5_decrypt(hex2buf($string),_KEY_);
	return   $decryptString;
}

$string = "AE82198A049DEA51000024BD0EFAB540C854E35AE3E7B30B24DBDF3412A0AC63B3E4BDB8B86CEEBD110D9ACBE43591";
echo $destring = ophone_decrypt($string);
?>
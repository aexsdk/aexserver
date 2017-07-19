<?php
$s = create_sign_code(4);
$_SESSION['signed_code'] = $s;
display_sign_code($s);

/**
 * 动态生成验证码
 *
 * @param int $count  验证码的位数
 */
function create_sign_code($count)
{
	$code = '';
	//设置印上去的文字
	$Str[0] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$Str[1] = "abcdefghijklmnopqrstuvwxyz";
	$Str[2] = "01234567891234567890123456";
	while ($count > 0)
	{
		$code .= $Str[rand(0,2)][rand(0,25)];
		$count = $count -1;
	}
	return $code;
}

/**
 * 显示验证码图片
 *
 * @param string $code 验证码字符串
 */
function display_sign_code($code)
{
	//设置字体大小
	$font_size=12;
	$code_len = strlen($code);	
	//创建真彩色白纸
	//echo $code;
	$im = @imagecreatetruecolor($code_len*($font_size+1), 20);
	//获取背景颜色
	$background_color = imagecolorallocate($im, 255, 255, 255);
	//填充背景颜色(这个东西类似油桶)
	imagefill($im,0,0,$background_color);
	//获取边框颜色
	$border_color = imagecolorallocate($im,200,200,200);
	//画矩形，边框颜色200,200,200
	imagerectangle($im,0,0,49,19,$border_color);
	
	//逐行炫耀背景，全屏用1或0
	for($i=2;$i<18;$i++){
		//获取随机淡色
		$line_color = imagecolorallocate($im,rand(200,255),rand(200,255),rand(200,255));
		//画线
		imageline($im,2,$i,47,$i,$line_color);
	}
	
	$x = 0;
	for($i=0;$i<$code_len;$i++)
	{
		$imstr[$i]['s'] = $code[$i];
		$x = ($x > 0) ? ($x+ $font_size-1+rand(0,1)) : rand(2,5);
		$imstr[$i]['x'] = $x;
		$imstr[$i]['y'] = rand(1,4);
		//获取随机较深颜色
		$text_color = imagecolorallocate($im,rand(50,180),rand(50,180),rand(50,180));
		//画文字
		imagechar($im,$font_size,$imstr[$i]["x"],$imstr[$i]["y"],$imstr[$i]["s"],$text_color);
	}
	//var_dump($imstr);
	//文件头...
	header("Content-type: image/png");
	//显示图片
	imagepng($im);
	//销毁图片
	imagedestroy($im);
}

?> 


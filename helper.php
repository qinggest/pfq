<?php

//生成a标签,默认另开窗口打开链接
function anchor($href, $title, $props=null)
{
	$anchor = '<a href="'. $href. '"';
	if(!$props['target'])
		$anchor .= ' target="_blank" ';

	foreach($props as $k=>$v)
		$anchor .= ' '.$k.'="'.$v.'"';
	$anchor .= ">$title</a>";

	return $anchor;
}

//生成img标签
function img($src,$props = null)
{
	$img = '<img src="'.$src.'"';
	foreach($props as $k=>$v)
		$img .= ' '.$k.'="'.$v.'"';

	$img .= ' />';
	return $img;
}

function qaptcha()
{
	$src=WEBROOT."/script.php?act=captcha";
	$props = array("title"=>"点击可更换图片", "onclick"=>"this.src='$src'");
	return img($src, $props);
}

function captcha()
{
	Header("Content-type: image/PNG");
	$im = imagecreate(44,18);
	$back = ImageColorAllocate($im, 245,245,245);
	imagefill($im,0,0,$back); //背景

	srand((double)microtime()*1000000);
	//生成4位数字
	for($i=0;$i<4;$i++){
		$font = ImageColorAllocate($im, rand(100,255),rand(0,100),rand(100,255));
		$authnum=rand(1,9);
		$vcodes.=$authnum;
		imagestring($im, 5, 2+$i*10, 1, $authnum, $font);
	}

	for($i=0;$i<10;$i++){ //加入干扰象素 
		$randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255));
		imagesetpixel($im, rand()%70 , rand()%30 , $randcolor);
	} 

	ImagePNG($im);
	ImageDestroy($im);
	$_SESSION['captcha'] = $vcodes;

	return $vcodes;
}
?>

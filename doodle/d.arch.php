<?php

function data_get_visible_archives() {
global	$u_flag;
	$a = array(0);
	if (is_dir($da = DIR_ARCH))
	foreach (scandir($da) as $r) if (($u_flag['god'] || !ROOM_HIDE || ROOM_HIDE != $r[0]) && trim($r, '.')
	&& ($mt = data_get_archive_mtime($r))) {
		$a[$r] = array(data_get_archive_count($r, 1), $mt);
		if ($a[0] < $mt) $a[0] = $mt;
	}
	return ($a[0]?$a:0);
}

function data_get_thumb($src, $xMax, $yMax) {
	if (!is_file($src)) return false;

	ob_start();
	$gis = getImageSize($src);
	$w = $xMax;	$gis['w'] = $width = $gis[0];
	$h = $yMax;	$gis['h'] = $height = $gis[1];
	switch ($gis['mime']) {
		case 'image/jpg': case 'image/jpeg': case 'image/pjpeg':
					$orig = imageCreateFromJPEG($src);	break;
		case 'image/png': case 'image/x-png':
					$orig = imageCreateFromPNG($src);	break;
//		case 'image/gif':	$orig = imageCreateFromGIF($src);	break;
	}
	ob_end_clean();
	if (!$orig) return 0;

	imageAlphaBlending($orig, false);
	imageSaveAlpha($orig, true);
	if ($r = ($w&&$width>$w) + ($h&&$height>$h) * 2) {
		if ($r == 3) $r = ($width/$w < $height/$h) + 1;
		if ($r == 1) $h = round($w*$height/$width);	//* <- w tops, h depends
		if ($r == 2) $w = round($h*$width/$height);	//* <- h tops, w depends
		$res = imageCreateTrueColor($w,$h);
		imageAlphaBlending($res, false);
		imageSaveAlpha($res, true);
		imageCopyResampled($res, $orig, 0,0,0,0, $w,$h, $width,$height);
		imageDestroy($orig);
		imageTrueColorToPalette($res, false, 255);
		$gis['w'] = $w;
		$gis['h'] = $h;
		$gis['imgdata'] = $res;
	} else $gis['imgdata'] = $orig;
	return $gis;
}

function data_put_thumb($src, $dest, $xMax, $yMax) {
	if (!is_array($gis = data_get_thumb($src, $xMax, $yMax))) return false;
/*	switch ($gis['mime']) {
		case 'image/jpg': case 'image/jpeg': case 'image/pjpeg':
					return imageJPEG($gis['imgdata'], $dest);
//		case 'image/gif':	return imageGIF($gis['imgdata'], $dest);
	}
*/	return imagePNG($gis['imgdata'], $dest);
}

function data_line2time($line) {return strtotime(substr($line, 0, strpos($line, '	')));}
function data_get_template_page($room, $num, $tsv) {
	if ($num <= 0) return $num;
	$p = $num-1;
	$n = $num+1;
	$lines = explode(NL, trim($tsv));
	return get_template_page(
array(	'title' => $room
,	'head' => ($p ? '<link rel="prev" href="'.$p.PAGE_EXT.'">'.NL : '').
			'<link rel="next" href="'.$n.PAGE_EXT.'">'
,	'body' => get_date_class(data_line2time(reset($lines)), data_line2time(end($lines)))
,	'task' => ($p ? '<a href="'.$p.PAGE_EXT.'" title="previous">'.$num.'</a>' : $num)
,	'content' => $tsv
,	'js' => 'arch'));
}

function data_archive_full_threads($threads) {
global	$room;
	if (!is_dir($b = ($a = DIR_ARCH.$room.'/').DIR_THUMB)) mkdir($b, 0755, true);
	$done = $gone = 0;
	$c = data_get_archive_count();
	foreach ($threads as $f) {
		$th = $b.(++$c).THUMB_EXT;
		if (is_file($f[2]) && data_put_thumb($f[2], $th, THUMB_MAX_WIDTH, THUMB_MAX_HEIGHT)) {
			optimize_pic($th);
		} else copy(NAMEPRFX.THUMB_EXT, $th);
		if (file_put_contents($a.$c.PAGE_EXT, data_get_template_page($room, $c, $f[1]))
		&& unlink($f[0])) ++$done;
	}
	data_put(1, $c);
	if (R1 && R1_DEL
	&& ($k = data_get_archive_count(0, 1))	//* <- check number to keep 1 page
	&& (($k -= TRD_PER_PAGE) > 0)) {
		$c -= TRD_PER_PAGE;
		while ($k--) {
			if (is_file($f = $a.$c.PAGE_EXT) && data_del_thread($f,0,1)) ++$gone;
			if (is_file($f = $b.($c--).THUMB_EXT)) unlink($f);
		}
	}
	return array($done, $gone);
}

function data_archive_rewrite() {
	$i = '	<img src="';			//* <- uniq for pics in posts
	if (!is_dir($da = DIR_ARCH)) return;
	$sd1 = scandir($da);
	natsort($sd1);
	foreach ($sd1 as $dn) if (trim($dn, '.') && is_dir($d = "$da$dn/")) {
		$t = 0;
		$sd2 = scandir($d);
		natsort($sd2);
		foreach ($sd2 as $fn) if (trim($fn, '.')
		&& strpos($fn, PAGE_EXT) && is_file($f = $d.$fn)
		&& preg_match(PAT_CONTENT, $old = file_get_contents($f), $m)) {
			$x = explode($i, $m[1]);
			$new = array_shift($x);
			foreach ($x as $y) {
				$src = substr($y, 0, $q = strpos($y, '"'));	//* <- web path
				$n = substr($src, strrpos($src, '/')+1);	//* <- pic filename
				$n = (PIC_SUB?pic_subpath($n):DIR_PICS.$n);	//* <- new relative path
				$new .= $i.ROOTPRFX.$n.substr($y, $q);		//* <- new web path
			}
			$new = data_get_template_page($dn, rtrim($fn, PAGE_EXT), $new);
			if ($old == $new) $x = 'same';
			else {
				if (!rename($f, $x = "$f.bak")) $x = 'rename old to bak failed'; else
				if (!($sz = file_put_contents($f, $new))) $x = 'save new failed'; else
				if (!unlink($x)) $x = 'delete old failed'; else
				$x = strlen($old)." => $sz bytes";
			}
			$done .= NL."$f	$x";
			++$t;
		}
		++$a;
		if (TIME_PARTS && $t) time_check_point("done $a: $d, $t threads");
	}
	return $done;
}

?>
<?php

function data_get_visible_archives() {
	global $u_flag;
	$a = array(0);
	$h = get_const('ROOM_HIDE');
	if (is_dir($da = DIR_ARCH))
	foreach (scandir($da) as $r) if (
		(
			GOD
		||	$u_flag['mod']
		||	$u_flag['mod_'.$r]
		||	!$h
		||	$h != $r[0]
		)
	&&	trim($r, '.')
	&&	($mt = data_get_archive_mtime($r))
	) {
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
		array(
			'title' => $room
		,	'head' => ($p ? '<link rel="prev" href="'.$p.PAGE_EXT.'">'.NL : '').
					'<link rel="next" href="'.$n.PAGE_EXT.'">'
		,	'body' => get_date_class(data_line2time(reset($lines)), data_line2time(end($lines)))
		,	'task' => ($p ? '<a href="'.$p.PAGE_EXT.'" title="previous">'.$num.'</a>' : $num)
		,	'content' => $tsv
		,	'js' => 'arch'
		)
	);
}

function data_archive_full_threads($threads) {
	global $room;
	if (!is_array($threads) || !count($threads)) return false;
	if (!is_dir($b = ($a = DIR_ARCH.$room.'/').DIR_THUMB)) mkdir($b, 0755, true);
	$done_count = 0;
	$gone_count = 0;
	$c = data_get_archive_count();
	foreach ($threads as $f) {
		$th = $b.(++$c).THUMB_EXT;
		if (
			is_file($f[2])
		&&	data_put_thumb($f[2], $th, THUMB_MAX_WIDTH, THUMB_MAX_HEIGHT)
		) {
			optimize_pic($th);
		} else copy(NAMEPRFX.THUMB_EXT, $th);
		if (
			file_put_contents($a.$c.PAGE_EXT, data_get_template_page($room, $c, $f[1]))
	//	&&	unlink($f[0])
		&&	data_del_thread($f[0])	//* <- clean up comments, etc
		) ++$done_count;
	}
	data_put(1, $c);
	if (
		R1
	&&	R1_DEL
	&&	($k = data_get_archive_count(0, 1))	//* <- check number to keep 1 page
	&&	($k -= TRD_PER_PAGE) > 0
	) {
		$c -= TRD_PER_PAGE;
		while ($k--) {
			if (is_file($f = $a.$c.PAGE_EXT) && data_del_thread($f, false, 1)) ++$gone_count;
			if (is_file($f = $b.($c--).THUMB_EXT)) unlink($f);
		}
	}
	return array(
		'done' => $done_count
	,	'gone' => $gone_count
	);
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
				$new .= $i.get_pic_url($n).substr($y, $q);	//* <- new web path
			}
			$new = data_get_template_page($dn, rtrim($fn, PAGE_EXT), $new);
			if ($old == $new) $x = 'same';
			else {
				if (!rename($f, $x = "$f.bak")) $x = 'rename old to bak failed'; else
				if (!($sz = file_put_contents($f, $new))) $x = 'save new failed'; else
				if (!unlink($x)) $x = 'delete old failed'; else
				$x = strlen($old)." => $sz bytes";
			}
			$text_report .= NL."$f	$x";
			++$t;
		}
		++$a;
		if (TIME_PARTS && $t) time_check_point("done $a: $d, $t threads");
	}
	return $text_report;
}

function data_archive_find_by($where, $what = '') {
	global $room;
	$where = array_filter(is_array($where) ? $where : array($where => $what), 'strlen');
	if ($t = $where['time']) {
		$time_ranges = array();
		$signs = str_split('<>-');
		$min = false;
		while (preg_match('~^(\D*?)(-)?(\d+(:+\d+)*)~', $t, $match)) {
			$t = substr($t, strlen($match[0]));
			$prefix = $match[1];
			$minus = $match[2];
			if ($minus && !$prefix && false !== $min) {$prefix = '-'; $minus = '';}
			$v = get_time_seconds($minus.$match[3]);
			$k = '';
			foreach ($signs as $sign) if (false !== strpos($prefix, $sign)) {$k = $sign; break;}
			if ('-' === $k && false !== $min) {
				array_pop($time_ranges);
				$time_ranges[] = (
					$min < $v
					? array('min' => $min, 'max' => $v)
					: array('min' => $v, 'max' => $min)
				);
				$min = false;
			} else {
				if ($k) $min = false;
				else {
					$k = '=';
					$min = $v;
				}
				$time_ranges[] = array(
					'operator' => $k
				,	'value' => $v
				);
			}
		}
		if (!count($time_ranges)) unset($where['time']);
	}
	if (!count($where) || !is_dir($d = DIR_ARCH.$room.'/')) return '';
	if (TIME_PARTS) time_check_point('inb4 search');
	$n_found = 0;
	$elen = strlen(PAGE_EXT);
	foreach (scandir($d) as $f) if (
		trim($f, '.')
	&&	substr($f, -$elen) == PAGE_EXT
	&&	is_file($path = $d.$f)
	&&	preg_match(PAT_CONTENT, file_get_contents($path), $match)
	) {
		$i = intval($f);
		$n_check = '';
		foreach (explode(NL, $match[1]) as $line) {
			$found = $draw_time = '';
			$tab = explode('	', $line);
			foreach ($where as $type => $what) {
				$t = $draw_time_check = '';
				if ($type == 'name') $t = $tab[1];			//* <- username
				else
				if ($tab[2][0] != '<') {
					if ($type == 'post') $t = $tab[2];		//* <- text-only post content
				} else {
					if ($type == 'file') {
						$t = $tab[2];
						$t = substr($t, strrpos($t, '/')+1);	//* <- pic filename
						$t = substr($t, 0, strrpos($t, '"'));
					} else
					if ($type != 'post') {
						$t = $tab[3];				//* <- what was used to draw
						if ($type == 'time') {
							$draw_time_check = 1;
							if (preg_match('~^[\d:-]+~i', $t, $t)) {
								$t = $t[0];
								if (strrpos($t, '-')) {
									$t1 = $t0 = false;
									foreach (explode('-', $t) as $n) if (strlen($n)) {
										if (false === $t0) $t0 = $n;
										$t1 = $n;
									}
									$t = intval(($t1-$t0)/1000);	//* <- msec. from JS
								} else {
									$t = get_time_seconds($t);
								}
								foreach ($time_ranges as $cond) if (
									array_key_exists($k = 'operator', $cond)
									? (
										($cond[$k] == '=' && $t == $cond['value'])
									||	($cond[$k] == '<' && $t < $cond['value'])
									||	($cond[$k] == '>' && $t > $cond['value'])
									)
									: ($t >= $cond['min'] && $t <= $cond['max'])
								) {
									$draw_time = "(drawn in $t sec.)";
									break;
								}
							}
						}
					}
				}
				$found = ($draw_time_check ? $draw_time : ($t && false !== strpos(mb_strtolower(html_entity_decode($t), ENC), $what)));
				if (!$found) continue 2;
			}
			if ($found) {
				$content .= ($n_check?'':NL.'	'.$i).NL.$line;
				$n_check .= '='.(++$n_found).$draw_time;
			}
		}
		if (TIME_PARTS) time_check_point('done '.$i.$n_check);
	}
	return $content;
}

?>
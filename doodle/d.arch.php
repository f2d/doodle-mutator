<?php

function data_get_visible_archives() {
	$last = 0;
	$a = array();
	foreach (get_dir_contents($da = DIR_ARCH, 1, 1) as $r) if ($mt = data_get_archive_mtime($r)) {
		if ($last < $mt) $last = $mt;
		$a[$r] = array(
			'last' => $mt
		,	'count' => data_get_archive_count($r, 1)
		);
	}
	return $a ? array(
		'last' => $last
	,	'list' => $a
	) : $a;
}

function data_get_thumb($src, $xMax = 0, $yMax = 0) {
	if (!is_file($src)) return false;

	ob_start();
	$data = getImageSize($src);
	$data['w'] = $width = $data[0];
	$data['h'] = $height = $data[1];
	switch ($data['mime']) {
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
	$w = $xMax;
	$h = $yMax;
	if ($r = ($w && $width > $w) + ($h && $height > $h)*2) {
		if ($r == 3) $r = ($width/$w < $height/$h ? 2 : 1);
		if ($r == 2) $w = round($h*$width/$height); else	//* <- h tops, w depends
		if ($r == 1) $h = round($w*$height/$width);		//* <- w tops, h depends
		$res = imageCreateTrueColor($w,$h);
		imageAlphaBlending($res, false);
		imageSaveAlpha($res, true);
		imageCopyResampled($res, $orig, 0,0,0,0, $w,$h, $width,$height);
		imageDestroy($orig);
		imageTrueColorToPalette($res, false, 255);
		$data['w'] = $w;
		$data['h'] = $h;
		$data['imgdata'] = $res;
	} else $data['imgdata'] = $orig;
	return $data;
}

function data_put_thumb($src, $dest, $xMax = 0, $yMax = 0) {
	if (!is_array($data = data_get_thumb($src, $xMax, $yMax))) return false;
/*	switch ($data['mime']) {
		case 'image/jpg': case 'image/jpeg': case 'image/pjpeg':
					return imageJPEG($data['imgdata'], $dest);
//		case 'image/gif':	return imageGIF($data['imgdata'], $dest);
	}
*/	return imagePNG($data['imgdata'], $dest);
}

function data_get_line_fix_time($line) {
	global $line_time_min, $line_time_max;
	$before_tab = substr($line, 0, $i = strpos($line, '	'));
	$t = (
		preg_match('~\sdata-t=[\'"](\d+)~i', $before_tab, $match)
		? intval($match[1])
		: strtotime($before_tab)
	) ?: intval($before_tab);
	if (!$line_time_min || $line_time_min > $t) $line_time_min = $t;
	if (!$line_time_max || $line_time_max < $t) $line_time_max = $t;
	return $t.','.date(DATE_ATOM, $t).substr($line, $i);
}

function data_get_archive_page_html($room, $num, $tsv) {
	global $line_time_min, $line_time_max;
	if ($num <= 0) return $num;
	$p = $num-1;
	$n = $num+1;
	$line_time_min = $line_time_max = 0;
	$lines = array_map('data_get_line_fix_time', explode(NL, trim($tsv)));
	sort($lines);
	return get_template_page(
		array(
			'title' => $room
		,	'head' => ($p ? '<link rel="prev" href="'.$p.PAGE_EXT.'">'.NL : '').
					'<link rel="next" href="'.$n.PAGE_EXT.'">'
		,	'body' => get_date_class($line_time_min, $line_time_max)
		,	'task' => ($p ? '<a href="'.$p.PAGE_EXT.'" title="previous">'.$num.'</a>' : $num)
		,	'content' => NL.implode(NL, $lines)
		,	'js' => array('capture' => 1, 'arch' => 1)
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
			file_put_contents($a.$c.PAGE_EXT, data_get_archive_page_html($room, $c, $f[1]))
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
	global $date_classes, $line_time_min, $line_time_max;
	$a = 0;
	$elen = strlen(PAGE_EXT);
	$img_src = array(
		'<img src="'
	,	'<a href="'
	);
	foreach (get_dir_contents($da = DIR_ARCH, 1) as $room) {
		$t = 0;
		foreach (get_dir_contents($d = "$da$room/", 1) as $f) if (
			substr($f, -$elen) == PAGE_EXT
		&&	is_file($path = $d.$f)
		&&	preg_match(PAT_CONTENT, $old = file_get_contents($path), $m)
		) {
			$new = $m[1];
			foreach ($img_src as $i) {
				$x = explode($i, $new);
				$new = array_shift($x);
				foreach ($x as $y) {
					$src = substr($y, 0, $q = strpos($y, '"'));	//* <- web path
					$n = substr($src, strrpos($src, '/')+1);	//* <- pic filename
					$new .= $i.get_pic_url($n).substr($y, $q);	//* <- new web path
				}
			}
			$new = data_get_archive_page_html($room, intval($f), $new);
			if ($old == $new) $x = 'same';
			else {
				if (!rename($path, $x = "$path.bak")) $x = 'rename old to bak failed'; else
				if (!($sz = file_put_contents($path, $new))) $x = 'save new failed'; else
				if (!unlink($x)) $x = 'delete old failed'; else
				$x = strlen($old)." => $sz bytes";
			}
			$d0 = date(TIMESTAMP, $line_time_min);
			$d1 = date(TIMESTAMP, $line_time_max);
			$dc = ($date_classes ? '	'.implode(' ', $date_classes) : '');
			$text_report .= NL."$path	$x	$d0 - $d1$dc";
			++$t;
		}
		++$a;
if (TIME_PARTS && $t) time_check_point("done $a: $d, $t threads");
	}
	return $text_report;
}

function data_get_archive_search_terms() {
	global $tmp_archive_find_by, $query;
	$terms = array();
	if ($query) {
		foreach ($tmp_archive_find_by as $k => $v) if (
			array_key_exists($k, $query)
		&&	strlen($q = $query[$k])
		&&	strlen($q = mb_strtolower(trim_post(fix_encoding($q), FIND_MAX_LENGTH)))
		) {
			$terms[$k] = $q;
		}
	}
	return $terms;
}

function data_archive_find_by($where, $what = '', $caseless = 1) {
	global $room;
if (TIME_PARTS) time_check_point('inb4 archive search prep');
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
		if (!$time_ranges) unset($where['time']);
	}
	if (!$where) return false;
	$elen = strlen(PAGE_EXT);
if (TIME_PARTS) time_check_point('inb4 archive search iteration'.NL);
	foreach (($room ? array($room) : get_dir_contents(DIR_ARCH, 1, 1)) as $r) {
		$n_found = 0;
		$files = array();
		foreach (get_dir_contents($d = DIR_ARCH.$r.'/', 1) as $f) if (
			substr($f, -$elen) == PAGE_EXT
		&&	is_file($path = $d.$f)
		) $files[$path] = intval($f);
if (TIME_PARTS) time_check_point(count($files).' files in '.$d);
		foreach ($files as $path => $i) if (
			preg_match(PAT_CONTENT, file_get_contents($path), $match)
		) {
			$n_check = '';
			foreach (explode(NL, $match[1]) as $line) {
				$draw_time = '';
				$tab = explode('	', $line);
				foreach ($where as $type => $what) {
					$found = $draw_time_check = $t = '';
					if ($type == 'name') $t = $tab[1];			//* <- username
					else
					if ($tab[2][0] != '<') {
						if ($type == 'post') $t = $tab[2];		//* <- text-only post content
					} else {
						if ($type == 'file') {
							$t = explode('"', $tab[2]);
							$t = array_filter($t, 'is_tag_attr');
							$t = array_map('get_file_name', $t);
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
										$found = $draw_time = "(drawn in $t sec.)";
										break;
									}
								}
							}
						}
					}
					if (!$draw_time_check) foreach ((array)$t as $v) if (strlen($v)) {
						$v = html_entity_decode($v);
						if ($caseless) $v = mb_strtolower($v);
						if ($found = (false !== strpos($v, $what))) break;
					}
					if (!$found) continue 2;
				}
				if ($found) {
					if (strpos($tab[0], ',')) {
						$tab[0] = intval($tab[0]);
						$line = implode('	', $tab);
					}
					$content .= ($n_found || $room?'':($content?NL:'')."
room = $r").($n_check?'':"
t = $i").NL.$line;
					$n_check .= '='.(++$n_found).$draw_time;
				}
			}
if (TIME_PARTS) time_check_point('done '.$i.$n_check);
		}
	}
	return $content;
}

?>
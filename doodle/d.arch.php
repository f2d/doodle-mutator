<?php

function data_get_visible_archives($type = '') {
	$last = 0;
	$a = array();
	foreach (get_dir_rooms(DIR_ARCH, '', F_NATSORT | F_HIDE, $type) as $r) if ($mt = data_get_mtime(COUNT_ARCH, $r)) {
		if ($last < $mt) $last = $mt;
		$a[$r] = array(
			'last' => $mt
		,	'count' => data_get_count(COUNT_ARCH, $r)
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
	$before_tab = mb_substr($line, 0, $i = mb_strpos($line, '	'));
	$t = (
		preg_match('~\sdata-t=[\'"](\d+)~i', $before_tab, $match)
		? intval($match[1])
		: strtotime($before_tab)
	) ?: intval($before_tab);
	if (!$line_time_min || $line_time_min > $t) $line_time_min = $t;
	if (!$line_time_max || $line_time_max < $t) $line_time_max = $t;
	return $t.','.date(DATE_ATOM, $t).mb_substr($line, $i);
}

function data_get_archive_page_html($room, $num, $tsv) {
	global $cfg_langs, $line_time_min, $line_time_max;
	$line_time_min = $line_time_max = 0;
	if ($num <= 0) return false;
	$p = $num-1;
	$n = $num+1;
	$lines = array_map('data_get_line_fix_time', mb_split_filter(trim($tsv), NL));
	sort($lines);
	return get_template_page(
		array(
			'title' => $room
		,	'lang' => $cfg_langs[0]
		,	'link' => ROOTPRFX.DIR_ARCH."$room/$num".PAGE_EXT
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
	global $room, $room_type;
	if (!(
		is_array($threads)
	&&	count($threads)
	&&	$room
	)) return false;

	mkdir_if_none($p = ($d = DIR_ARCH."$room/").DIR_THUMB);
	$done_count = 0;
	$gone_count = 0;
	$i = data_get_count(COUNT_ARCH);
	$e = PAGE_EXT;
	$x = THUMB_EXT;
	$icon = NAMEPRFX.$x;
	foreach ($threads as $a) {
		++$i;
		if (
			file_put_contents("$d$i$e", data_get_archive_page_html($room, $i, $a['content']))
	//	&&	unlink($a['name'])
		&&	data_del_thread($a['name'])	//* <- clean up comments, etc
		) {
			$t = "$p$i$x";
			if (
				($f = $a['thumb'])
			&&	is_file($f)
			&&	data_put_thumb($f, $t, THUMB_MAX_WIDTH, THUMB_MAX_HEIGHT)
			) {
				optimize_pic($t);
			} else copy($icon, $t);
			++$done_count;
		}
	}
	data_put_count($i, COUNT_ARCH);
	if (
		$room_type['arch_prune']
	&&	($i -= TRD_PER_PAGE) > ($stop_count = 0)
	) do {
		if (is_file($f = "$d$i$e") && data_del_thread($f, false, 1)) ++$gone_count;
		if (is_file($f = "$p$i$x") && unlink($f)) $stop_count = 0; else ++$stop_count;
	} while (--$i && $stop_count < 3);
	return array(
		'done' => $done_count
	,	'gone' => $gone_count
	);
}

function data_archive_rewrite() {
	global $date_classes, $line_time_min, $line_time_max;
	$a = 0;
	$d = DIR_ARCH;
	$elen = -strlen(PAGE_EXT);
	$img_src = array(
		'<img src="'
	,	'<a href="'
	);
	foreach (get_dir_rooms($d, '', F_NATSORT) as $room) {
		$t = 0;
		foreach (get_dir_contents($dr = "$d$room", F_NATSORT) as $fn) if (
			substr($fn, $elen) == PAGE_EXT
		&&	is_file($f = "$dr/$fn")
		&&	preg_match(PAT_CONTENT, $old = file_get_contents($f), $match)
		) {
			$new = $match['content'];
			foreach ($img_src as $i) {
				$x = mb_split($i, $new);
				$new = array_shift($x);
				foreach ($x as $y) {
					$src = mb_substr($y, 0, $q = mb_strpos($y, '"'));	//* <- web path
					$n = mb_substr($src, mb_strrpos_after($src, '/'));	//* <- pic filename
					$new .= $i.get_pic_url($n).mb_substr($y, $q);		//* <- new web path
				}
			}
			$new = data_get_archive_page_html($room, intval($fn), $new);
			if ($old == $new) $x = 'same';
			else if (!$new) $x = 'error';
			else {
				if (!rename($f, $x = "$f.bak")) $x = 'rename old to bak failed'; else
				if (!($sz = file_put_contents($f, $new))) $x = 'save new failed'; else
				if (!unlink($x)) $x = 'delete old failed'; else
				$x = strlen($old)." => $sz bytes";
			}
			$d0 = date(TIMESTAMP, $line_time_min);
			$d1 = date(TIMESTAMP, $line_time_max);
			$dc = ($date_classes ? '	'.implode(' ', $date_classes) : '');
			$text_report .= NL."$f	$x	$d0 - $d1$dc";
			++$t;
		}
		++$a;
if (TIME_PARTS && $t) time_check_point("done $a: $room, $t threads");
	}
	return $text_report;
}

function data_archive_rename_last_pic($old, $new, $n_last_pages = 0) {
	if ($new === $old) return 0;
	$c = intval($n_last_pages);
	$d = DIR_ARCH.$room;
	$e = PAGE_EXT;
	$j = ';';
	list($old_name, $old_size) = mb_split($j, $old, 2);
	list($new_name, $new_size) = mb_split($j, $new, 2);
	if ($i = get_dir_top_file_id($d, $e)) while ($i > 0) {
		if (
			is_file($f = "$d$i$e")
		&&	preg_match(PAT_CONTENT, file_get_contents($f), $match)
		&&	false !== ($pos_before = mb_strpos($t = $match['content'], $old_name))
		&&	false !== ($pos_after = mb_strpos($t, '	', $pos_before))
		) {
			$before = mb_substr($t, 0, $pos_before);
			$after = mb_substr($t, $pos_after);
			$old = mb_substr($t, $pos_before, $pos_after - $pos_before);
			$new = mb_str_replace($old, $old_name, $new_name);
			if ($new_size && false !== ($k = mb_strpos($new, $j))) $new = mb_substr($t, 0, $k).$j.$new_size;
			if ($new !== $old) $done += file_put_contents($f, "$match[before]$before$new$after$match[after]");
		}
		if ($c > 0 && !(--$c)) break;
		--$i;
	}
	return $done;
}

function data_get_archive_search_terms() {
	global $tmp_archive_find_by, $query;
	$terms = array();
	if ($query) {
		foreach ($tmp_archive_find_by as $k => $v) if (
			array_key_exists($k, $query)
		&&	strlen($q = $query[$k])
		&&	strlen($q = trim_post(fix_encoding($q), FIND_MAX_LENGTH))
		) {
			$terms[$k] = $q;
		}
	}
	return $terms;
}

function data_archive_find_by($where, $what = '', $caseless = 1) {
	global $r_type, $room;
if (TIME_PARTS) time_check_point('inb4 archive search prep');
	$where = array_filter(is_array($where) ? $where : array($where => $what), 'strlen');
	if ($t = $where['time']) {
		$time_ranges = array();
		$signs = array('<','>','-');
		$min = false;
		while (preg_match('~^(\D*?)(-)?(\d+(:+\d+)*)~', $t, $match)) {
			$t = substr($t, strlen($match[0]));
			$prefix = $match[1];
			$minus = $match[2];
			if ($minus && !$prefix && false !== $min) {$prefix = '-'; $minus = '';}
			$v = get_time_seconds($minus.$match[3]);
			$k = '';
			foreach ($signs as $sign) if (false !== mb_strpos($prefix, $sign)) {$k = $sign; break;}
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
	$d = DIR_ARCH;
	$elen = -strlen(PAGE_EXT);
	$rooms = (array)($room ?: get_dir_rooms($d, '', F_NATSORT | F_HIDE, $r_type));
	$c = count($rooms);
if (TIME_PARTS) time_check_point("got $c rooms, inb4 archive search iteration".NL);
	foreach ($rooms as $r) {
		$n_found = 0;
		$files = array();
		foreach (get_dir_contents($dr = "$d$r", F_NATSORT) as $f) if (
			substr($f, $elen) == PAGE_EXT
		&&	is_file($path = "$dr/$f")
		) $files[$path] = intval($f);
if (TIME_PARTS) time_check_point(count($files)." files in $dr");
		foreach ($files as $path => $i) if (
			preg_match(PAT_CONTENT, file_get_contents($path), $match)
		) {
			$n_check = '';
			foreach (mb_split_filter($match['content'], NL) as $line) {
				$draw_time = '';
				$tab = mb_split('	', $line);
				foreach ($where as $type => $what) {
					$found = $draw_time_check = $t = '';
					if ($type == 'name') {
						$t = $tab[1];		//* <- username
					} else
					if ($type == 'post') {
						$t = $tab[2];		//* <- text-only post content
						if (false !== mb_strpos($t, '<')) {
							$t = preg_replace('~<[^>]+>~u', '', mb_str_replace('<br>', NL, $t));
						}
					} else
					if ($type == 'file') {
						$t = mb_split('"', $tab[2]);
						$t = array_filter($t, 'is_tag_attr');
						$t = array_map('get_file_name', $t);
					} else {
						$t = $tab[3];		//* <- what was used to draw
						if ($type == 'time') {
							$draw_time_check = 1;
							if (preg_match('~^[\d:-]+~i', $t, $t)) {
								$t = $t[0];
								if (mb_strrpos($t, '-')) {
									$t1 = $t0 = false;
									foreach (mb_split('-', $t) as $n) if (strlen($n)) {
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
					if (!$draw_time_check) {
						$is_regex = preg_match(PAT_REGEX_FORMAT, $what);
						$lowhat = ($caseless ? mb_strtolower($what) : $what);
						foreach ((array)$t as $v) if (strlen($v)) {
							$v = html_entity_decode($v);
							if ($found = (
								false !== mb_strpos($caseless ? mb_strtolower($v) : $v, $lowhat)
							||	($is_regex && @preg_match($what, $v))
							)) break;
						}
					}
					if (!$found) continue 2;
				}
				if ($found) {
					if (mb_strpos($tab[0], ',')) {
						$tab[0] = intval($tab[0]);
						$line = implode('	', $tab);
					}
					$content .= ($n_found || $room?'':($content?NL:'')."
room = $r").($n_check?'':"
t = $i").NL.$line;
					$n_check .= '='.(++$n_found).$draw_time;
				}
			}
if (TIME_PARTS) time_check_point("done $i$n_check");
		} else
if (TIME_PARTS) time_check_point("$i: content not found in $path");
	}
	return $content;
}

?>
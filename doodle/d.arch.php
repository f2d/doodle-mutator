<?php

define(ARCH_P_BF, '~(?:^|[;,\s]+)');
define(ARCH_P_AF, '(?=[;,]|$)~ui');
define(ARCH_PAT_POST_PIC_BYTES, ARCH_P_BF.'(\d+)\s*B'.ARCH_P_AF);
define(ARCH_PAT_POST_PIC_WDXHD, ARCH_P_BF.'(\d+)\D(\d+)'.ARCH_P_AF);
define(ARCH_PAT_POST_PIC, '~
	(?P<open><a\s+[^>]+>)?
	(?P<image><img\s+[^>]+?(?P<w>\s+width="\d+")?(?P<h>\s+height="\d+")?(?:\s+[^>]+?)?>)
	(?P<close></a>)?
	(?P<csv>(?:[;,]\s*[^;,]+)+\s+B)
~uix');

function data_archive_get_visible_rooms($type = '') {
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

function data_archive_get_images($room = '') {
	$a = array();
	$d = DIR_ARCH;
	$rooms = ($room ? (array)$room : get_dir_rooms($d));
	foreach ($rooms as $r) if (is_dir($s = "$d$r/")) {
		foreach (get_dir_contents($s) as $f) if (is_file($path = $s.$f)) {
			foreach (data_get_thread_images($path) as $i) {
				if (false === array_search($i, $a)) $a[] = $i;
			}
		}
	}
	return $a;
}

function data_archive_get_thumb($src, $xMax = 0, $yMax = 0) {
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

function data_archive_put_thumb($src, $dest, $xMax = 0, $yMax = 0) {
	if (!is_array($data = data_archive_get_thumb($src, $xMax, $yMax))) return false;
/*	switch ($data['mime']) {
		case 'image/jpg': case 'image/jpeg': case 'image/pjpeg':
					return imageJPEG($data['imgdata'], $dest);
//		case 'image/gif':	return imageGIF($data['imgdata'], $dest);
	}
*/	return imagePNG($data['imgdata'], $dest);
}

function data_archive_get_fixed_content_line($line) {
	global $recheck_img, $line_time_min, $line_time_max;
	$not_found = '<img src="'.ROOTPRFX.PIC_404.'">';
	$sep = '	';
	$tab = mb_split($sep, $line);

//* timestamp:
	$t = $tab[0];
	$t = (
		preg_match('~\sdata-t=[\'"](\d+)~i', $t, $match)
		? intval($match[1])
		: strtotime($t)
	) ?: intval($t);
	if (!$line_time_min || $line_time_min > $t) $line_time_min = $t;
	if (!$line_time_max || $line_time_max < $t) $line_time_max = $t;
	$tab[0] = $t.','.date(DATE_ATOM, $t);

//* image/link:
	if (
		count($tab) > 3
	&&	false !== mb_strpos($p = $tab[2], '<img')
	&&	(
			$recheck_img
		||	!(
				preg_match(ARCH_PAT_POST_PIC, $p, $match)
			&&	preg_match(ARCH_PAT_POST_PIC_BYTES, $match['csv'])
			&&	preg_match(ARCH_PAT_POST_PIC_WDXHD, $match['csv'])
			)
		)
	) {
		$a = mb_split($b = '>', $p);
		$csv = array_pop($a);
		if ($csv.$b == $not_found) $csv = array_pop($a);
		$full_path = $p = get_pic_subpath(get_first_arg($a[0]));
		$full_url = get_pic_url($p);
		if (is_file($p)) {
			$full_res = getImageSize($full_path);
			$full_bytes = filesize($full_path);
			$full_bytes_f = format_filesize($full_bytes);
			$csv = "$full_res[0]*$full_res[1], $full_bytes_f, $full_bytes B";
		} else {
			$csv = implode(', ', mb_split_filter($csv, '[;,]\\s*'));
		}
		if ($full_res && $full_res[0] > DRAW_PREVIEW_WIDTH) {
			$resized_path = $p = get_pic_resized_path($full_path);
			$resized_url = get_pic_url($p);
			if (is_file($p)) {
				$resized_res = getImageSize($resized_path);
			}
			$p = '<a href="'.$full_url.'">'
			.	'<img src="'.$resized_url.($resized_res ? '" width="'.$resized_res[0].'" height="'.$resized_res[1] : '').'">'
			.'</a>';
		} else {
			$p = '<img src="'.$full_url.($full_res ? '" width="'.$full_res[0].'" height="'.$full_res[1] : '').'">';
		}
		if ($csv) $p .= "; $csv";
		if ($recheck_img && !is_file($full_path)) {
			$p = "<!--$p-->$not_found";
		}
		$tab[2] = $p;
	}

	return implode($sep, $tab);
}

function data_archive_is_a_content_line($line) {return (false !== mb_strpos($line, '	'));}
function data_archive_get_page_html($room, $num, $tsv) {
	global $cfg_langs, $line_time_min, $line_time_max;
	$line_time_min = $line_time_max = 0;
	if ($num <= 0) return false;
	$p = $num-1;
	$n = $num+1;
	$lines = mb_split_filter(trim(fix_encoding($tsv)), NL);
	$lines = array_filter($lines, 'data_archive_is_a_content_line');
	$lines = array_map('data_archive_get_fixed_content_line', $lines);
	sort($lines);
	$tsv = NL.implode(NL, $lines);
	return get_template_page(
		array(
			'title' => $room
		,	'lang' => $cfg_langs[0]
		,	'link' => ROOTPRFX.DIR_ARCH."$room/$num".PAGE_EXT
		,	'head' => ($p ? '<link rel="prev" href="'.$p.PAGE_EXT.'">'.NL : '').
					'<link rel="next" href="'.$n.PAGE_EXT.'">'
		,	'body' => get_date_class($line_time_min, $line_time_max)
		,	'task' => ($p ? '<a href="'.$p.PAGE_EXT.'" title="previous">'.$num.'</a>' : $num)
		,	'content' => $tsv
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
			file_put_contents("$d$i$e", data_archive_get_page_html($room, $i, $a['content']))
	//	&&	unlink($a['name'])
		&&	data_del_thread($a['name'])	//* <- clean up comments, etc
		) {
			$t = "$p$i$x";
			if (
				($f = $a['thumb'])
			&&	is_file($f)
			&&	data_archive_put_thumb($f, $t, THUMB_MAX_WIDTH, THUMB_MAX_HEIGHT)
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

function data_archive_rewrite($check = false) {
	global $recheck_img, $date_classes, $line_time_min, $line_time_max;
	$recheck_img = $check;
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
			$new = data_archive_get_page_html($room, intval($fn), $new);
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

function data_archive_get_search_terms($query) {
	global $tmp_archive_find_by;
	$terms = array();
	if ($query && is_array($query)) {
		foreach ($tmp_archive_find_by as $k => $v) if (
			array_key_exists($k, $query)
		&&	strlen($q = $query[$k])
		&&	strlen($q = trim_post(fix_encoding($q), FIND_MAX_LENGTH))
		) {
			$terms[$k] = $q;
		}
	}
	return data_archive_get_search_ranges($terms);
}

function data_archive_get_search_ranges($where, $what = '') {
	$where = array_filter(is_array($where) ? $where : array($where => $what), 'strlen');
	$signs = array('<','>','-');
	$before = '^(?P<before>\D*?)(?P<minus>-)?';
	$pat_oom = '~'.SUBPAT_OOM_LETTERS.'~iu';
	$patterns = array(
		'(?P<number>\d+)'
			=> array('width', 'height')
	,	'(?P<number>\d+)((?P<float>[,.]\d+)?\s*(?P<oom>'.SUBPAT_OOM_LETTERS.'))?'
			=> array('bytes')
	,	'(?P<csv>\d+(:+\d+)*)'
			=> array('time')
	);
	foreach ($patterns as $subpattern => $keys)
	foreach ($keys as $key) if (strlen($t = $where[$key])) {
		$sub_ranges = array();
		$min = false;
		while (preg_match("~$before$subpattern~iux", $t, $match)) {
			$t = substr($t, strlen($match[0]));
			$prefix = $match['before'] ?: '';
			$minus = $match['minus'] ?: '';
			if (strlen($minus) && !strlen($prefix) && false !== $min) {$prefix = '-'; $minus = '';}
			if (strlen($v = $match['csv'])) {
				$v = get_time_seconds($x = "$minus$v");
			} else {
				$v = intval($match['number']);
				$x = "$minus$v";
				if (($oom = $match['oom']) && preg_match($pat_oom, $oom, $m)) {
					$v = (float)"$x$match[float]";
					$x = "$v$oom";
					$i = 0;
					do { $v *= 1024; } while (!$m[++$i] && $i < 255);
				} else {
					$v = intval($x);
				}
			}
			$k = '';
			if (strlen($prefix)) foreach ($signs as $sign) if (false !== mb_strpos($prefix, $sign)) {$k = $sign; break;}
			if ('-' === $k && false !== $min) {
				array_pop($sub_ranges);
				$sub_ranges[] = (
					$min < $v
					? array('min' => $min, 'max' => $v, 'min_arg' => $min_arg, 'max_arg' => $x)
					: array('min' => $v, 'max' => $min, 'min_arg' => $x, 'max_arg' => $min_arg)
				);
				$min = false;
			} else {
				if ($k) $min = false;
				else {
					$k = '=';
					$min = $v;
					$min_arg = $x;
				}
				$sub_ranges[] = array(
					'operator' => $k
				,	'argument' => $x
				,	'value' => $v
				);
			}
		}
		if ($sub_ranges) $where[$key] = $sub_ranges;
		else unset($where[$key]);
	}
	return $where;
}

function data_archive_get_search_array_item($v) {
	if ($v['min_arg']) return "$v[min_arg]-$v[max_arg]";
	if ($v['min']) return "$v[min]-$v[max]";
	if (($o = $v['operator']) && $o !== '=') return "$v[operator] $v[argument]";
	return "$v[argument]";
}

function data_archive_get_search_value($v) {
	if (is_array($v)) return implode(', ', array_map('data_archive_get_search_array_item', $v));
	return $v;
}

function data_archive_get_search_url($terms) {
	$q = array();
	if (is_array($terms)) foreach ($terms as $k => $v) {
		if ($k === '_charset_' && $v === ENC) continue;
		if (strlen($v = data_archive_get_search_value($v))) $q[] = URLencode($k).'='.URLencode($v);
	}
	return implode('&', $q);
}

function data_archive_find_by($terms, $caseless = 1) {
	global $r_type, $room;
	if (!$terms) return false;
if (TIME_PARTS) time_check_point('inb4 archive search prep');
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
				$tab = mb_split('	', $line);
				foreach ($terms as $type => $what) {
					$found = $t = '';

				//* get values from relevant post field:
					if ($type == 'name') {
						$t = $tab[1];		//* <- username
					} else
					if ($type == 'post') {
						if (count($tab) > 3) continue 2;
						$t = $tab[2];		//* <- text-only post content
						if (false !== mb_strpos($t, '<')) {
							$t = preg_replace('~<[^>]+>~u', '', mb_str_replace('<br>', NL, $t));
						}
					} else
					if (count($tab) < 4) continue 2; else
					if ($type == 'file') {
						$t = mb_split('"', $tab[2]);
						$t = array_filter($t, 'is_tag_attr');
						$t = array_map('get_file_name', $t);
					} else
					if ($type == 'width' || $type == 'height' || $type == 'bytes') {
						$t = $tab[2];
						$pat = ($type == 'bytes' ? ARCH_PAT_POST_PIC_BYTES : ARCH_PAT_POST_PIC_WDXHD);
						if (preg_match($pat, mb_substr_after($t, '>'), $match)) {
							$t = intval($match[$type == 'height'?2:1]);
						} else continue 2;
					} else
					if ($type == 'time') {
						if (preg_match('~^[\d:-]+~i', $tab[3], $match)) {
							$t = $match[0];
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
						} else continue 2;
					} else {
						$t = $tab[3];		//* <- what was used to draw
					}

				//* compare:
					if (is_array($what)) {
						foreach ($what as $cond) if (
							array_key_exists($k = 'operator', $cond)
							? (
								($cond[$k] == '=' && $t == $cond['value'])
							||	($cond[$k] == '<' && $t < $cond['value'])
							||	($cond[$k] == '>' && $t > $cond['value'])
							)
							: ($t >= $cond['min'] && $t <= $cond['max'])
						) {
							if ($type == 'time') {
								$found = "drawn in $t sec.";
							} else $found = "found $t";
							break;
						}
					} else {
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

				//* reformat image post:
					if (count($tab) > 3) {
						$cleanup = 0;
						if (mb_strpos($tab[0], ',')) {
							$tab[0] = intval($tab[0]);
							++$cleanup;
						}
						if (
							mb_strpos($t = $tab[2], ',')
						&&	($cut = strlen($v = mb_substr_after($t, '>')))
						) {
							$t = substr($t, 0, -$cut);
							if (
								preg_match(ARCH_PAT_POST_PIC_WDXHD, $v, $match)
							&&	intval($match[1]) > DRAW_PREVIEW_WIDTH
							) {
								$t .= preg_replace(ARCH_PAT_POST_PIC_BYTES, '', $v);
							}
							$tab[2] = $t;
							++$cleanup;
						}
						if ($cleanup) $line = implode('	', $tab);
					}

				//* add post to result output:
					$content .= ($n_found || $room?'':($content?NL:'')."
room = $r").($n_check?'':"
t = $i").NL.$line;
					++$n_found;
					$n_check .= "=$n_found: $found";
				}
			}
if (TIME_PARTS) time_check_point("done $i$n_check");
		} else
if (TIME_PARTS) time_check_point("$i: content not found in $path");
	}
	return $content;
}

?>
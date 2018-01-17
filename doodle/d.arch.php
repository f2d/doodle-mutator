<?php

define(ARCH_PIC_NOT_FOUND, '<img src="'.ROOTPRFX.PIC_404.'">');
define(ARCH_PAT_POST_PIC, '~
	(?P<open><a\s+[^>]+>)?
	(?P<image><img\s+[^>]+?(?P<width>\s+width="\d+")?(?P<height>\s+height="\d+")?(?:\s+[^>]+?)?>)
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
			foreach (data_get_thread_pics($path) as $i) {
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
/*
	switch ($data['mime']) {
		case 'image/jpg': case 'image/jpeg': case 'image/pjpeg':
					return imageJPEG($data['imgdata'], $dest);
//		case 'image/gif':	return imageGIF($data['imgdata'], $dest);
	}
*/
	return imagePNG($data['imgdata'], $dest);
}

function data_archive_get_post_pic_info($img_html, $check_file = 1) {
	$a = mb_split($b = '>', $img_html);

	$csv = array_pop($a);
	if ($csv.$b == ARCH_PIC_NOT_FOUND) $csv = array_pop($a);

	$p = get_first_arg($img_html);
	$a = get_post_pic_info($p, $csv, $check_file);

	return $a;
}

function data_archive_get_fixed_content_line($line) {
	global $data_archive_re_params;
	$sep = '	';
	$tab = mb_split($sep, $line);

//* timestamp:
	$t = $tab[0];
	$t = (
		preg_match('~\sdata-t=[\'"](\d+)~i', $t, $match)
		? intval($match[1])
		: strtotime($t)
	) ?: intval($t);

	if (is_array($date = &$data_archive_re_params['date'])) {
		if (!$date['min'] || $date['min'] > $t) $date['min'] = $t;
		if (!$date['max'] || $date['max'] < $t) $date['max'] = $t;
	}

	$tab[0] = $t.','.date(DATE_ATOM, $t);

//* image/link:
	$recheck = $data_archive_re_params['recheck_img'] ?: array();
	if (
		count($tab) > 3
	&&	false !== mb_strpos($p = $tab[2], '<img')
	&&	(
			count(array_filter($recheck))
		||	!(
				preg_match(ARCH_PAT_POST_PIC, $p, $match)
			&&	($csv = $match['csv'])
			&&	preg_match(PAT_POST_PIC_CRC32, $csv)
			&&	preg_match(PAT_POST_PIC_BYTES, $csv)
			&&	preg_match(PAT_POST_PIC_W_X_H, $csv)
			)
		)
	) {
		$a = data_archive_get_post_pic_info($p, $recheck['hash'] ? 2 : 1);
		$csv = (
			($full_res = $a['full_res'])
		?	"$full_res[0]*$full_res[1], $a[full_bytes_f], $a[full_bytes] B, 0x$a[crc32]"
		:	implode(', ', mb_split_filter($a['csv'], '[;,]\\s*'))
		);
		if ($full_res && $full_res[0] > DRAW_PREVIEW_WIDTH) {
			$a['resized_path'] = $p = get_pic_resized_path($a['rel_path']);
			$a['resized_url'] = get_pic_url($p);
			if (is_file($p)) {
				$a['resized_res'] = getImageSize($p);
			}
			$p = '<a href="'.$a['full_url'].'">'
			.	'<img src="'.$a['resized_url'].(
					($resized_res = $a['resized_res'])
					? '" width="'.$resized_res[0].'" height="'.$resized_res[1]
					: ''
				).'">'
			.'</a>';
		} else {
			$p = '<img src="'.$a['full_url'].(
				$full_res
				? '" width="'.$full_res[0].'" height="'.$full_res[1]
				: ''
			).'">';
		}
		if ($csv) $p .= "; $csv";
		if ($recheck['exists'] && !is_file($a['rel_path'])) {
			$p = "<!--$p-->".ARCH_PIC_NOT_FOUND;
		}
		$tab[2] = $p;
	}

	return implode($sep, $tab);
}

function data_archive_is_a_content_line($line) {return (false !== mb_strpos($line, '	'));}
function data_archive_get_page_html($room, $num, $tsv) {
	global $data_archive_re_params, $cfg_langs;
	if (!is_array($data_archive_re_params)) $data_archive_re_params = array();
	$date = (array(
		'min' => 0
	,	'max' => 0
	));
	$data_archive_re_params['date'] = &$date;
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
		,	'body' => get_date_class($date['min'], $date['max'])
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

	data_lock($lk = LK_ARCH.$r, true);

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

	data_unlock($lk);

	return array(
		'done' => $done_count
	,	'gone' => $gone_count
	);
}

function data_archive_rewrite($params = false) {
	global $data_archive_re_params, $date_classes;
	$data_archive_re_params = (array)$params;
	$a = 0;
	$d = DIR_ARCH;
	$elen = -strlen(PAGE_EXT);
	$img_src = array(
		'<img src="'
	,	'<a href="'
	);

	data_lock($lk = LK_ARCH.$r, true);
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
			$date = array();
			foreach ($data_archive_re_params['date'] as $k => $v) {
				$date[$k] = date(TIMESTAMP, $v);
			}
			$dc = ($date_classes ? '	'.implode(' ', $date_classes) : '');
			$text_report .= NL."$f	$x	$date[min] - $date[max]$dc";
			++$t;
		}
		++$a;
if (TIME_PARTS && $t) time_check_point("done $a: $room, $t threads");
	}
	data_unlock($lk);

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

	data_lock($lk = LK_ARCH.$r, true);
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
	data_unlock($lk);

	return $done;
}

function data_archive_get_search_terms($query, $caseless = true) {
	global $tmp_archive_find_by;
	$terms = array();

	if ($query && is_array($query)) {
		foreach ($tmp_archive_find_by as $k => $v) if (	//* <- fixed order to canonically sort query arguments
			array_key_exists($k, $query)
		&&	strlen($q = $query[$k])
		&&	strlen($q = trim_post(fix_encoding($q), FIND_MAX_LENGTH))
		) {
			$terms[$k] = $q;
		}
	}

	return get_search_ranges($terms, $caseless);
}

function data_archive_get_search_url($terms) {
	$q = array();

	if (is_array($terms)) foreach ($terms as $k => $v) {
		if ($k === '_charset_' && $v === ENC) continue;
		if (strlen($v = data_archive_get_search_value($v))) $q[] = URLencode($k).'='.URLencode($v);
	}

	return implode('&', $q);
}

function data_archive_get_search_value($v) {
	if (is_array($v)) return implode(', ', array_map('data_archive_get_search_array_item', $v));
	return $v;
}

function data_archive_get_search_array_item($v) {
	if ($v['min_arg']) return "$v[min_arg]-$v[max_arg]";
	if ($v['min']) return "$v[min]-$v[max]";
	if (($o = $v['operator']) && $o !== '=') return "$v[operator] $v[argument]";
	return "$v[argument]";
}

function data_archive_find_by($terms, $caseless = true, $include_hidden = false) {
	global $r_type, $room;
	$results = array();
	if (!$terms) return $results;

if (TIME_PARTS) time_check_point('inb4 archive pages search prep, terms = '.get_print_or_none($terms));
	$d = DIR_ARCH;
	$e = PAGE_EXT;
	$elen = -strlen($e);
	$rooms = (array)($room ?: get_dir_rooms($d, '', F_NATSORT | ($include_hidden ? 0 : F_HIDE), $r_type));
	$c = count($rooms);
if (TIME_PARTS) time_check_point("got $c rooms, inb4 search iteration".NL);

	foreach ($rooms as $r) {
		$files = array();

		data_lock($lk = LK_ARCH.$r);
		foreach (get_dir_contents($dr = "$d$r", F_NATSORT) as $f) if (
			substr($f, $elen) === $e
		&&	is_file($path = "$dr/$f")
		) $files[$path] = intval($f);
if (TIME_PARTS) {$n_found = 0; time_check_point(count($files)." files in $dr");}

		foreach ($files as $path => $i) if (
			preg_match(PAT_CONTENT, file_get_contents($path), $match)
		) {
if (TIME_PARTS) $n_check = '';
			foreach (mb_split_filter($match['content'], NL) as $line) {
				$tab = mb_split('	', $line);
				$post = array(
					'date' => intval($tab[0]) ?: '?'
				,	'username' => $tab[1] ?: '?'
				,	'post' => $tab[2] ?: '?'
				);
				if (count($tab) > 3) $post['meta'] = $tab[3];	//* <- faster than array_filter on empty values

				if ($found = is_post_matching($post, $terms, $caseless)) {
					$results[$r][$i][] = get_post_fields_to_display($post);
if (TIME_PARTS) {++$n_found; $n_check .= "=$n_found: $found";}
				}
			}
if (TIME_PARTS) time_check_point("done $i$n_check");
		} else
if (TIME_PARTS) time_check_point("$i: content not found in $path");

		data_unlock($lk);
	}

	return $results;
}

?>
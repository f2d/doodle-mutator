<?php

define('ARCH_SITE_NAME', 'Doodle Mutator');
define('ARCH_DESCRIPTION', 'Archived thread.');
define('ARCH_POST_FIELD_SEPARATOR', "\t");
define('ARCH_PIC_NOT_FOUND', '<img src="'.ROOTPRFX.PIC_404.'">');
define('ARCH_PAT_HTML_TAG', '~<\w+("[^">]*"|[^>])*>~u');
define('ARCH_PAT_PLACEHOLDER_TEXT', 'v\d|'.mb_escape_regex(NOR).'|'.mb_escape_regex(html_entity_decode(NOR)));
define('ARCH_PAT_PLACEHOLDER', '
	(?P<PlaceholderText>'.ARCH_PAT_PLACEHOLDER_TEXT.')
	(?P<PlaceholderLink>
		[\s()]*
		<a\s+[^<]+</a>
		[\s()]*
	)?
	(?P<PlaceholderComment>\s*?<!--.*?-->)?
');

define('ARCH_PAT_POST_PLACEHOLDER', '~^'.ARCH_PAT_PLACEHOLDER.'$~uix');
define('ARCH_PAT_POST_PLACEHOLDER_SPAN', '~^
	<span\s+title="\s*
	(?P<Title>
		(?P<Time>\d+)
		:\s*
		(?P<Task>[^">]*?)
	)
	\s*">
	(?P<Text>'.ARCH_PAT_PLACEHOLDER_TEXT.')
	</span>
$~uix');

define('ARCH_PAT_POST', '~^
	(?P<Date>
		[^'.ARCH_POST_FIELD_SEPARATOR.']*
	)
	(?P<Post>
		['.ARCH_POST_FIELD_SEPARATOR.']
		[^'.ARCH_POST_FIELD_SEPARATOR.']*
		['.ARCH_POST_FIELD_SEPARATOR.']
		(?:
			(?P<Placeholder>'.ARCH_PAT_PLACEHOLDER.')
		|	(?P<Image><(?:a|img)\s.+)
		|	(?P<Text>.+)
		)
	)
$~uix');

define('ARCH_PAT_POST_PIC', '~
	(?P<open><a\s+[^>]+>)?
	(?P<image><img\s+[^>]+?(?P<width>\s+width="\d+")?(?P<height>\s+height="\d+")?(?:\s+[^>]+?)?>)
	(?P<close></a>)?
	(?P<csv>(?:[;,]\s*[^;,]+)+\s+B)
~uix');

define('ARCH_SEARCH_ARG_ORDER', [
	'post',
	'file',
	'bytes',
	'width',
	'height',
	'time',
	'used',
	'name',
	ARG_FULL_NAME,
]);

function data_archive_get_visible_rooms($type = '') {
	$last = 0;
	$a = array();

	foreach (get_dir_rooms(DIR_ARCH, '', F_NATSORT | F_HIDE, $type) as $each_room) if ($mt = data_get_mtime(COUNT_ARCH, $each_room)) {
		if ($last < $mt) {
			$last = $mt;
		}

		$a[$each_room] = array(
			'last' => $mt
		,	'count' => data_get_count(COUNT_ARCH, $each_room)
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

	foreach ($rooms as $each_room) if (is_dir($s = "$d$each_room/")) {
		foreach (get_dir_contents($s) as $f) if (is_file($path = $s.$f)) {
			foreach (data_get_thread_pics($path) as $i) {
				if (false === array_search($i, $a)) {
					$a[] = $i;
				}
			}
		}
	}

	return $a;
}

function data_archive_get_thumb($src, $xMax = 0, $yMax = 0) {
	if (!is_file($src)) {
		return false;
	}

	ob_start();
	$data = getImageSize($src);
	$data['w'] = $width = $data[0];
	$data['h'] = $height = $data[1];

	switch ($data['mime']) {
		case 'image/jpg':
		case 'image/jpeg':
		case 'image/pjpeg':
		{
			$orig = imageCreateFromJPEG($src);

			break;
		}
		case 'image/png':
		case 'image/x-png':
		{
			$orig = imageCreateFromPNG($src);

			break;
		}
	}
	ob_end_clean();

	if (!$orig) {
		return 0;
	}

	imageAlphaBlending($orig, false);
	imageSaveAlpha($orig, true);
	$w = $xMax;
	$h = $yMax;
	if ($ratio = ($w && $width > $w) + ($h && $height > $h)*2) {
		if ($ratio == 3) $ratio = ($width/$w < $height/$h ? 2 : 1);
		if ($ratio == 2) $w = round($h*$width/$height); else	//* <- h tops, w depends
		if ($ratio == 1) $h = round($w*$height/$width);		//* <- w tops, h depends
		$res = imageCreateTrueColor($w,$h);
		imageAlphaBlending($res, false);
		imageSaveAlpha($res, true);
		imageCopyResampled($res, $orig, 0,0,0,0, $w,$h, $width,$height);
		imageDestroy($orig);
		imageTrueColorToPalette($res, false, 255);
		$data['w'] = $w;
		$data['h'] = $h;
		$data['imgdata'] = $res;
	} else {
		$data['imgdata'] = $orig;
	}

	return $data;
}

function data_archive_put_thumb($src, $dest, $xMax = 0, $yMax = 0) {
	if (!is_array($data = data_archive_get_thumb($src, $xMax, $yMax))) {
		return false;
	}

	return imagePNG($data['imgdata'], $dest);
}

function data_archive_get_post_date($line) {
	return intval(mb_substr_before($line, ','));
}

function data_archive_get_post_pic_info($img_html, $check_file = 1) {
	$a = mb_split($b = '>', $img_html);

	$csv = array_pop($a);
	if ($csv.$b == ARCH_PIC_NOT_FOUND) $csv = array_pop($a);

	$p = get_first_arg($img_html);
	$a = get_post_pic_info($p, $csv, $check_file);

	return $a;
}

function data_archive_fix_image_path($post_content) {
	foreach (array(
		'<img src="'
	,	'<a href="'
	) as $i) {
		$x = mb_split($i, $post_content);
		$post_content = array_shift($x);

		foreach ($x as $y) {
			$src = mb_substr($y, 0, $q = mb_strpos($y, '"'));	//* <- web path
			$n = mb_substr($src, mb_strrpos_after($src, '/'));	//* <- pic filename
			$post_content .= $i.get_pic_url($n).mb_substr($y, $q);	//* <- new web path
		}
	}

	return $post_content;
}

function data_archive_fix_image_html_and_meta($post_content, $recheck = false) {
	if (!$recheck) {
		$recheck = array();
	}

	$a = data_archive_get_post_pic_info($post_content, $recheck['hash'] ? 2 : 1);

	$csv = (
		($full_res = $a['full_res'])
	?	"$full_res[0]*$full_res[1], $a[full_bytes_f], $a[full_bytes] B, 0x$a[crc32]"
	:	implode(', ', mb_split_filter($a['csv'], '[;,]\\s*'))
	);

	if ($full_res && $full_res[0] > DRAW_PREVIEW_WIDTH) {
		$a['resized_path'] = $post_content = get_pic_resized_path($a['rel_path']);
		$a['resized_url'] = get_pic_url($post_content);

		if (is_file($post_content)) {
			$a['resized_res'] = getImageSize($post_content);
		}

		$post_content = (
			'<a href="'.$a['full_url'].'">'
			.	'<img src="'.$a['resized_url'].(
					($resized_res = $a['resized_res'])
					? '" width="'.$resized_res[0].'" height="'.$resized_res[1]
					: ''
				).'">'
			.'</a>'
		);
	} else {
		$post_content = (
			'<img src="'.$a['full_url'].(
				$full_res
				? '" width="'.$full_res[0].'" height="'.$full_res[1]
				: ''
			).'">'
		);
	}

	if ($csv) {
		$post_content .= "; $csv";
	}

	if ($recheck['exists'] && !is_file($a['rel_path'])) {
		$post_content = "<!--$post_content-->".ARCH_PIC_NOT_FOUND;
	}

	return $post_content;
}

function data_archive_get_post_fixed_values($line) {
	global $data_archive_rewrite_params;

	$tab = mb_split(ARCH_POST_FIELD_SEPARATOR, $line);
	$is_post_with_pic = (count($tab) > 3);

//* date:

	$t = $tab[0];
	$post_date_int = intval(
		preg_match('~\s+data-timestamp=[\'"](\d+)~i', $t, $match)
		? $match[1]
		: strtotime($t)
	) ?: intval($t);

//* text placeholder:

	$post_content = $tab[2];
	if (
		!$is_post_with_pic
	&&	preg_match(ARCH_PAT_POST_PLACEHOLDER_SPAN, $post_content, $match)
	) {
		$post_content = (
			strlen($match['Time']) && strlen($match['Task'])
			? "$match[Text]<!-- $match[Title] -->"
			: "$match[Text]"
		);
	}

	$is_post_placeholder = (
		!$is_post_with_pic
	&&	preg_match(ARCH_PAT_POST_PLACEHOLDER, $post_content)
	);

//* image + optional link:

	$recheck = $data_archive_rewrite_params['recheck_img'] ?: array();

	if (
		$is_post_with_pic
	&&	false !== mb_strpos($post_content, '<img')
	&&	($post_content = data_archive_fix_image_path($post_content))
	&&	(
			count(array_filter($recheck))
		||	!(
				preg_match(ARCH_PAT_POST_PIC, $post_content, $match)
			&&	($csv = $match['csv'])
			&&	preg_match(PAT_POST_PIC_CRC32, $csv)
			&&	preg_match(PAT_POST_PIC_BYTES, $csv)
			&&	preg_match(PAT_POST_PIC_W_X_H, $csv)
			)
		)
	) {
		$post_content = data_archive_fix_image_html_and_meta($post_content, $recheck);
	}

	return array(
		'tab' => $tab
	,	'post_content' => $post_content
	,	'post_date_int' => $post_date_int
	,	'is_post_with_pic' => $is_post_with_pic
	,	'is_post_placeholder' => $is_post_placeholder
	);
}

function data_archive_get_post_fixed_lines($post) {
	global $data_archive_rewrite_params;

//* set local variables:

	extract($post);

//* store min/max dates:

	if (is_array($date_span = &$data_archive_rewrite_params['date_span'])) {
		if (!$date_span['min'] || $date_span['min'] > $post_date_int) $date_span['min'] = $post_date_int;
		if (!$date_span['max'] || $date_span['max'] < $post_date_int) $date_span['max'] = $post_date_int;
	}

//* store post text for meta tags:

	if (
		!$is_post_with_pic
	&&	is_array($meta = &$data_archive_rewrite_params['meta'])
	) {
		$k = ($is_post_placeholder ? 'placeholder' : 'description');
		$j = $k.'-date';

		if (
			(
				!$meta[$k]
			||	$meta[$j] > $post_date_int
			)
		&&	($post_text = preg_replace(ARCH_PAT_HTML_TAG, '', $post_content))
		) {
			$meta[$k] = $post_text;
			$meta[$j] = $post_date_int;
		}
	}

	$tab[0] = $post_date_int.','.date(DATE_ATOM, $post_date_int);
	$tab[2] = $post_content;

	return implode(ARCH_POST_FIELD_SEPARATOR, $tab);
}

function data_archive_sort_post_by_date($a, $b) {
	return (
		($a['post_date_int'] <=> $b['post_date_int'])
	?:	strcmp($a['post_content'], $b['post_content'])
	);
}

function data_archive_fix_post_date(&$posts, $i, $increment = false) {
	$old_date = $post_date = $posts[$i]['post_date_int'];
	$last_i = count($posts) - 1;

	if ($posts[$i]['is_post_placeholder']) {
		$post_date = $posts[
			($i > 0)
		||	($i > 1 && $i === $last_i)
			? 0
			: 1
		]['post_date_int'] - 1;
	}

	if ($post_date <= 0) {
		$post_date = (
			(
				$increment
				? ($i > 0)
				: ($i === $last_i)
			)
			? ($posts[$i-1]['post_date_int'] + 1)
			: ($posts[$i+1]['post_date_int'] - 1)
		);
	}

	if ($post_date > 0) {
		$same_date_count = 0;

		foreach ($posts as $post) if ($post_date === $post['post_date_int']) {
			++$same_date_count;
		}

		if ($same_date_count > 1) {
			if ($increment) {
				++$post_date;
			} else {
				--$post_date;
			}
		}

		$changed = ($old_date !== $post_date);

		if ($changed) {
			$posts[$i]['post_date_int'] = $post_date;
		}
	}

	return intval($changed);
}

function data_archive_is_a_content_line($line) {
	return (false !== mb_strpos($line, ARCH_POST_FIELD_SEPARATOR));
}

function data_archive_get_page_html($room, $num, $tsv) {
	global $data_archive_rewrite_params;

	if (!is_array($data_archive_re_params)) {
		$data_archive_re_params = array();
	}
	$meta = array();
	$date_span = array(
		'min' => 0
	,	'max' => 0
	);
	$data_archive_rewrite_params['date_span'] = &$date_span;
	$data_archive_rewrite_params['meta'] = &$meta;

	if ($num <= 0) {
		return false;
	}

	$p = $num-1;
	$n = $num+1;

//* deconstruct text block into post values:

	$lines = mb_split_filter(trim(fix_encoding($tsv)), NL);
	$lines = array_filter($lines, 'data_archive_is_a_content_line');
	$posts = array_map('data_archive_get_post_fixed_values', $lines);

//* fix non-positive date values without sorting, to keep paired posts together:

	$tries = 0;
	$max_tries = TRD_MAX_POSTS * 2;

	do {
		$changed = 0;

		foreach ($posts as $i => $post) if ($post['is_post_placeholder']) {
			$changed += data_archive_fix_post_date($posts, $i);
		}

		foreach ($posts as $i => $post) if (!$post['is_post_placeholder']) {
			$changed += data_archive_fix_post_date($posts, $i, true);
		}
	} while (
		$changed
	&&	(++$tries < $max_tries)
	);

//* sort by fixed date values:

	usort($posts, 'data_archive_sort_post_by_date');

//* reconstruct posts into text block:

	$lines = array_map('data_archive_get_post_fixed_lines', $posts);
	$tsv = NL.implode(NL, $lines);

	if (!strlen(trim($tsv))) {
		return false;
	}

	$full_link_prefix = rtrim($GLOBALS['cfg_link_canonical_base'], './');
	$link_here = ROOTPRFX.DIR_ARCH."$room/$num".PAGE_EXT;
	$thumbnail = ROOTPRFX.DIR_ARCH."$room/".DIR_THUMB.$num.THUMB_EXT;

	$date_min = date('Y-m-d', $date_span['min']);
	$description = $meta['description'] ?: "$room - $num";
	$description = "$date_min. $description";

	return get_template_page(
		array(
			'title' => $room
		,	'lang' => $GLOBALS['cfg_langs'][0]
		,	'link_here' => $link_here
		,	'links' => array(
				'prev' => ($p > 0 ? $p.PAGE_EXT : '')
			,	'next' => ($n > 0 ? $n.PAGE_EXT : '')
			)
		,	'meta' => array(
				'og:type'  => 'article'
			,	'og:url'   => $full_link_prefix.$link_here
			,	'og:image' => $full_link_prefix.$thumbnail
			,	'og:title' => $description
			,	'og:site_name' => ARCH_SITE_NAME
			)
		,	'page_class' => get_date_class($date_span['min'], $date_span['max'])
		,	'task' => ($p > 0 ? '<a href="'.$p.PAGE_EXT.'" title="previous">'.$num.'</a>' : $num)
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
	)) {
		return false;
	}

	data_lock($lk = LK_ARCH.$room, true);

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
			($file_content = data_archive_get_page_html($room, $i, $a['content']))
		&&	file_put_contents("$d$i$e", $file_content)
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
	global $data_archive_rewrite_params, $date_classes;

	$data_archive_rewrite_params = (array)$params;
	$a = 0;
	$d = DIR_ARCH;
	$elen = -strlen(PAGE_EXT);

	data_lock($lk = LK_ARCH.$room, true);
	foreach (get_dir_rooms($d, '', F_NATSORT) as $room) {
		$t = 0;

		foreach (get_dir_contents($dr = "$d$room", F_NATSORT) as $fn) if (
			substr($fn, $elen) == PAGE_EXT
		&&	is_file($f = "$dr/$fn")
		&&	preg_match(PAT_CONTENT, $old = file_get_contents($f), $match)
		) {
			$new = data_archive_get_page_html($room, intval($fn), $match['content']);

			if ($old === $new) {
				$status = 'same';
			} else
			if (!$new) {
				$status = 'error generating new content';
			} else
			if (!rename($f, $f_bak = "$f.bak")) {
				$status = 'rename old to bak failed';
			} else
			if (!($sz = file_put_contents($f, $new))) {
				$status = 'save new failed';
			} else
			if (!unlink($f_bak)) {
				$status = 'delete old failed';
			} else {
				$status = strlen($old)." => $sz bytes";
			}

			$date_span = array();

			foreach ($data_archive_rewrite_params['date_span'] as $k => $v) {
				$date_span[$k] = date(CONTENT_DATETIME_FORMAT, $v);
			}

			$dc = ($date_classes ? ARCH_POST_FIELD_SEPARATOR.implode(' ', $date_classes) : '');
			$text_report .= NL.implode(ARCH_POST_FIELD_SEPARATOR, array($f, $status, "$date_span[min] - $date_span[max]$dc"));
			++$t;
		}

		++$a;

if (TIME_PARTS && $t) time_check_point("done $a: $room, $t threads");

	}
	data_unlock($lk);

	return $text_report;
}

function data_archive_rename_last_pic($old, $new, $n_last_pages = 0) {
	global $room;

	if ($new === $old) {
		return 0;
	}

	$c = intval($n_last_pages);
	$d = DIR_ARCH.$room;
	$e = PAGE_EXT;
	$j = ';';

	list($old_name, $old_size) = mb_split($j, $old, 2);
	list($new_name, $new_size) = mb_split($j, $new, 2);

	data_lock($lk = LK_ARCH.$room, true);
	if ($i = get_dir_top_file_id($d, $e)) {
		while ($i > 0) {
			if (
				is_file($f = "$d$i$e")
			&&	preg_match(PAT_CONTENT, file_get_contents($f), $match)
			&&	false !== ($pos_before = mb_strpos($t = $match['content'], $old_name))
			&&	false !== ($pos_after = mb_strpos($t, ARCH_POST_FIELD_SEPARATOR, $pos_before))
			) {
				$before = mb_substr($t, 0, $pos_before);
				$after = mb_substr($t, $pos_after);
				$old = mb_substr($t, $pos_before, $pos_after - $pos_before);
				$new = mb_str_replace($old, $old_name, $new_name);

				if ($new_size && false !== ($k = mb_strpos($new, $j))) {
					$new = mb_substr($t, 0, $k).$j.$new_size;
				}

				if ($new !== $old) {
					$done += file_put_contents($f, "$match[before]$before$new$after$match[after]");
				}
			}

			if ($c > 0 && !(--$c)) {
				break;
			}

			--$i;
		}
	}
	data_unlock($lk);

	return $done;
}

function data_archive_get_search_terms($query, $caseless = true) {
	$terms = array();

	if ($query && is_array($query)) {
		foreach (ARCH_SEARCH_ARG_ORDER as $arg_name) if (	//* <- fixed order to canonically sort query arguments
			array_key_exists($arg_name, $query)
		&&	strlen($arg_value = $query[$arg_name])
		&&	strlen($arg_value = trim_post(fix_encoding($arg_value), FIND_MAX_LENGTH))
		) {
			$terms[$arg_name] = $arg_value;
		}
	}

	return get_search_ranges($terms, $caseless);
}

function data_archive_get_search_url($terms) {
	$q = array();

	if (is_array($terms)) {
		foreach ($terms as $k => $v) {
			if ($k === '_charset_' && $v === ENC) {
				continue;
			}

			if (strlen($v = data_archive_get_search_value($v))) {
				$q[] = URLencode($k).'='.URLencode($v);
			}
		}
	}

	return implode('&', $q);
}

function data_archive_get_search_value($v) {
	if (is_array($v)) {
		return implode(', ', array_map('data_archive_get_search_array_item', $v));
	}

	return $v;
}

function data_archive_get_search_array_item($v) {
	if ($v['min_arg']) {
		return "$v[min_arg]-$v[max_arg]";
	}

	if ($v['min']) {
		return "$v[min]-$v[max]";
	}

	if (($o = $v['operator']) && $o !== '=') {
		return "$v[operator] $v[argument]";
	}

	return "$v[argument]";
}

function data_archive_find_by($terms, $caseless = true, $include_hidden = false) {
	global $r_type, $room;

	$results = array();

	if (!$terms) {
		return $results;
	}

if (TIME_PARTS) time_check_point('inb4 archive pages search prep, terms = '.get_print_or_none($terms));

	$d = DIR_ARCH;
	$e = PAGE_EXT;
	$elen = -strlen($e);
	$rooms = (array)($room ?: get_dir_rooms($d, '', F_NATSORT | ($include_hidden ? 0 : F_HIDE), $r_type));
	$c = count($rooms);

if (TIME_PARTS) time_check_point("got $c rooms, inb4 search iteration".NL);

	foreach ($rooms as $each_room) {
		$files = array();

		data_lock($lk = LK_ARCH.$each_room);
		foreach (get_dir_contents($dr = "$d$each_room", F_NATSORT) as $f) if (
			substr($f, $elen) === $e
		&&	is_file($path = "$dr/$f")
		) {
			$files[$path] = intval($f);
		}

if (TIME_PARTS) {$n_found = 0; time_check_point(count($files)." files in $dr");}

		foreach ($files as $path => $i) if (
			preg_match(PAT_CONTENT, file_get_contents($path), $match)
		) {

if (TIME_PARTS) $n_check = '';

			foreach (mb_split_filter($match['content'], NL) as $line) {
				$tab = mb_split(ARCH_POST_FIELD_SEPARATOR, $line);
				$post = array(
					'date' => intval($tab[0]) ?: '?'
				,	'username' => $tab[1] ?: '?'
				,	'post' => $tab[2] ?: '?'
				);
				if (count($tab) > 3) $post['meta'] = $tab[3];	//* <- faster than array_filter on empty values

				if ($found = is_post_matching($post, $terms, $caseless)) {
					$results[$each_room][$i][] = get_post_fields_to_display($post);

if (TIME_PARTS) {++$n_found; $n_check .= "=$n_found: $found";}

				}
			}

if (TIME_PARTS && (LOCALHOST || $n_check)) time_check_point("done $i$n_check");

		} else {

if (TIME_PARTS) time_check_point("$i: content not found in $path");

		}

		data_unlock($lk);
	}

	return $results;
}

?>
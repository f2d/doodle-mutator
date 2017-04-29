<?php

//* Constants only for internal use: ------------------------------------------

define(HTML_VERSION, '2017-04-28 22:44');	//* <- change this to autoupdate old browser-cached pages
define(HTACCESS_VERSION, '2017-04-16 01:41');	//* <- change this + open index as admin to autoupdate old .htaccess

//* Function argument flags: --------------------------------------------------

define(F_GET_FULL_IF_NONE, 1);
define(F_GET_DIR_PART, 2);
define(F_HIDE, 4);
define(F_NATSORT, 8);

//* ---------------------------------------------------------------------------

function exit_if_not_mod($t = 0, $change = 0) {
	$t = gmdate('r', $t ? max(data_global_announce('last'), $t) : T0);
	$q = 'W/"'.md5(
		'Refresh any page cached before '.HTML_VERSION
	.NL.	'Or if user key, options or date-related decoration changed: '.ME_VAL
	.NL.	implode(NL, get_date_class())
	.NL.	$GLOBALS['target']['deadline']
	).'"';
	header("Etag: $q");
	if (
		!$change
	&&	!$GLOBALS['u_opts']['modtime304']
	&&	isset($_SERVER[$m = 'HTTP_IF_MODIFIED_SINCE'])
	&&	isset($_SERVER[$n = 'HTTP_IF_NONE_MATCH'])
	&&	$_SERVER[$m] == $t
	&&	$_SERVER[$n] == $q
	) {
		header('HTTP/1.0 304 Not Modified');
		exit;
	}
	header("Last-Modified: $t");
}

function rewrite_htaccess($write_to_file = 1) {
	$start_mark = '# 8<-- start mark: '.NAMEPRFX.', version: ';
	$end_mark = '# 8<-- end mark: '.NAMEPRFX.', placed automatically: ';
	$new_mark = $start_mark.ROOTPRFX.' '.HTACCESS_VERSION;

	data_lock($lk = LK_MOD_HTA);
	if (
		!strlen($old = (is_file($f = '.htaccess') ? trim_bom(file_get_contents($f)) : ''))
	||	false === strpos($old, $new_mark)
	||	false === strpos($old, $end_mark)
	) {
		$s = '+([^/]+/+)*';
		$d = '('.implode('|', $GLOBALS['cfg_dir']).')(/+[^/]+)*';
		$dd = mb_escape_regex(get_const('DATA_DIR') ?: get_const('DIR_DATA'));
		$new = $new_mark.' -- Do not touch these marks. Only delete them along with the whole block.
<IfModule rewrite_module>
	RewriteEngine On
	RewriteBase '.ROOTPRFX.($dd?'

# hide data:
	RewriteRule ^'.$dd.' . [L,R]':'').'

# expand simple image path:
	RewriteRule ^('.DIR_PICS.'+)(([^/])[^/]+\.([^/])[^/]+)$ $1$4/$3/$2'.'

# file found, skip next 4 lines:
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^.? - [S=4]

# file not found:
	RewriteRule ^('.DIR_PICS.$s.'|'.DIR_ARCH.$s.DIR_THUMB.'+)[^/]+$ '.PIC_404.' [L]
	RewriteRule ^('.DIR_ARCH.$s.')[^/]+$ $1 [L,R]

# virtual folders:
	RewriteRule ^'.$d.'/+[-\d]*$ . [L]'.'
	RewriteRule ^'.$d.'$ $0/ [L,R]'.'

</IfModule>
'.$end_mark.date(TIMESTAMP, T0).' -- Manual changes inside will be lost on the next autoupdate.';
		if ($old) {
			$b_found = (false !== ($i = mb_strpos($old, $start_mark)));
			$before = ($b_found ? trim(mb_substr($old, 0, $i)) : '');

			$a_found = (false !== ($i = mb_strpos($old, $end_mark)));
			$after = ($a_found && false !== ($i = mb_strpos($old, NL, $i)) ? trim(mb_substr($old, $i)) : '');

			if ($b_found || $a_found) $new = (
				(strlen($before) ? $before.NL.NL : '')
			.	$new
			.	(strlen($after) ? NL.NL.$after : '')
			); else $new .= NL.NL.$old;
		} else $old = 'none';
		$old = preg_replace('~\v~u', PHP_EOL, $old);
		$new = preg_replace('~\v~u', PHP_EOL, $new);	//* <- workaround for Apache ignoring .htaccess with "\n" (not "\r\n") on Windows
		$changed = ($new != $old);
	} else $new = 'no change';
	$report = "---- old version: ----

$old

---- new version: ----

$new";

//* rewrite htaccess if none/changed, when logged in and viewing root folder:
	if ($write_to_file && $changed) {
		$saved = (
			file_put_contents($f, $new)
			? 'Succesfully updated'
			: 'Failed to update'
		);
		data_log_action($report = "$saved $f

$report");
	}
	data_unlock($lk);

	return $report;
}

function time_check_point($comment) {$GLOBALS['tcp'][microtime()][] = $comment;}
function get_print_or_none($a) {return $a ? trim(print_r($a, true)) : 'none';}

//* ---------------------------------------------------------------------------
//* Always use mb_* for text, but simple str* for non-empty checks,
//* encoded URLs and binary content (BOM, images, etc);
//* ---------------------------------------------------------------------------
//* not sure about safety of explode("\n", 'UTF-8 text');
//* not sure about mb_split() vs preg_split('//u');
//* mb_split() takes regex without delimiters, and allows encoding other that UTF-8;
//* ---------------------------------------------------------------------------

function fix_encoding($text) {
	global $fix_encoding_chosen;
	if (mb_check_encoding($text)) {
		$fix_encoding_chosen[] = ENC;
		return $text;
	}
	$a = array_filter(explode(',', "$_REQUEST[_charset_],".get_const('ENC_FALLBACK')));
	if (function_exists('iconv')) {
		foreach ($a as $e) if (
			strlen($e = trim($e))
		&&	false !== ($fix = iconv($e, ENC, $text))
		&&	mb_check_encoding($fix)
		) {
			$fix_encoding_chosen[] = $e;
			return $fix;
		}
	}
	if (false !== ($e = mb_detect_encoding($text, implode(',', $a), true))) {
		$fix_encoding_chosen[] = $e;
		return mb_convert_encoding($text, ENC, $e);
	}
	return $text;
}

function get_const($name) {return defined($s = mb_strtoupper($name)) ? constant($s) : '';}
function abbr($a, $sep = '_') {foreach ((is_array($a) ? $a : mb_split_filter($a, $sep)) as $word) $r .= mb_substr($word,0,1); return $r;}
function mb_escape_regex($s, $delim = '/') {return preg_replace("~[.:?*^$\\\\|\\$delim\\[\\](){}-]~u", '\\\\$0', $s);}
function mb_normalize_slash($s) {return mb_str_replace('\\', '/', $s);}
function mb_sanitize_filename($s) {return strtr($s, array('"' => "'", ':' => '_', '?' => '_', '*' => '_', '<' => '_', '>' => '_'));}
function mb_str_split($s) {return preg_split('//u', $s);}
function mb_split_filter($s, $by = '/', $limit = 0) {
	return preg_split(
		$by === NL
		? '~\v+~u'
		: '/('.mb_escape_regex($by).')+/u'
	, $s, $limit, PREG_SPLIT_NO_EMPTY);
}

function mb_strpos_after ($where, $what, $offset = 0) {return false !== ($pos = mb_strpos ($where, $what, $offset)) ? $pos + mb_strlen($what) : $pos;}
function mb_strrpos_after($where, $what, $offset = 0) {return false !== ($pos = mb_strrpos($where, $what, $offset)) ? $pos + mb_strlen($what) : $pos;}
function mb_str_replace($what, $to, $where) {return implode($to, preg_split('/('.mb_escape_regex($what).')/u', $where));}
function mb_str_replace_first($what, $to, $where) {
	return (
		false === ($pos = mb_strpos($where, $what))
		? $where
		: mb_substr($where, 0, $pos).$to.mb_substr($where, $pos + mb_strlen($what))
	);
}

function str_replace_first($what, $to, $where) {
	return (
		false === ($pos = strpos($where, $what))
		? $where
		: substr_replace($where, $to, $pos, strlen($what))
	);
}

function trim_bom($str) {return trim(str_replace(BOM, '', $str));}
function trim_post($p, $len = 456) {
	$s = trim(preg_replace('~\s+~u', ' ', $p));
	if ($len > 0) $s = mb_substr($s, 0, $len);
	return POST ? htmlspecialchars($s) : $s;
}

function trim_room($name, $also_allow_chars = '') {
	$t = mb_escape_regex("$GLOBALS[cfg_room_prefix_chars]$also_allow_chars");
	$x = '\x{0400}-\x{04ff}\x{2460}-\x{2468}\x{2605}-\x{2606}';
	$w = "-\\w$x$t";
	return mb_substr(
		preg_replace("/([^0-9a-z$x])\\1+/u", '$1',
		preg_replace("/[^$w]+/u", '_',
		trim(trim(mb_strtolower($name)), '\\/')
	)), 0, ROOM_NAME_MAX_LENGTH);
}

function encode_URL_parts($path, $raw = false) {
	return implode('/', array_map(
		$raw ? 'rawURLencode' : 'URLencode'
	,	is_array($path) ? $path : mb_split_filter($path)
	));
}

function encode_opt_value($v) {
	if (URLencode($v) !== $v) return B64_PRFX.str_replace(
		array('/', '+')
	,	array('_', '-')
	,	base64_encode($v)
	);
	return $v;
}

function decode_opt_value($v) {
	if (0 === strpos($v, B64_PRFX)) return base64_decode(str_replace(
		array('_', '-')
	,	array('/', '+')
	,	substr($v, strlen(B64_PRFX))
	));
	return $v;
}

function is_url_external($url) {
	if ($url && ($s = $_SERVER['SERVER_NAME'])) {
		$i = (strpos($url, '://'  ) ?: 0)+3;
		$j = (strpos($url, '/', $i) ?: 0);
		if ($s !== substr($url, $i, $j-$i)) return true;
	}
	return false;
}

function is_deny_arg($k) {return is_prefix($k, ARG_DENY);}
function is_desc_arg($k) {return is_prefix($k, ARG_DESC);}
function is_draw_arg($k) {return is_prefix($k, ARG_DRAW);}
function is_opt_arg($k) {return is_prefix($k, OPT_PRFX);}
function is_tag_attr($t) {return mb_strpos($t, '<') === mb_strpos($t, '>');}	//* <- if only both === false
function is_not_dot($path) {return !!trim($path, './\\');}
function is_not_hidden($room) {
	global $u_flag;
	return (
		GOD
	||	$u_flag['mod']
	||	$u_flag["mod_$room"]
	||	!get_room_type($room, 'hide_in_room_list')
	);
}

function mkdir_if_none($file_path) {
	if (
		($i = mb_strrpos($file_path, '/'))
	&&	($d = mb_substr($file_path, 0, $i))
	&&	!is_dir($d)
	&&	!is_file($d)
	) mkdir($d, 0755, true);
	return $file_path;
}

function get_dir_contents($path = '.', $flags = 0) {
	$a = (is_dir($path) ? array_filter(scandir($path), 'is_not_dot') : array());
	if ($a && ($flags & F_HIDE)) $a = array_filter($a, 'is_not_hidden');
	if ($a && ($flags & F_NATSORT)) natcasesort($a);
	return $a;
}

function get_dir_rooms($source_subdir = '', $output_subdir = '', $flags = 0, $type = '') {
	global $cfg_game_type_dir;
	if ($s = $source_subdir ?: '') $s = trim($s, '/.').'/';
	if ($o = $output_subdir ?: '') $o = trim($o, '/.').'/';
	$a = array();
	$types = (array)($type ?: $cfg_game_type_dir);
	foreach ($types as $t) {
		if ($t) $t = "$t/";
		foreach (get_dir_contents("$s$t", $flags) as $r) {
			if (($t || !in_array($r, $cfg_game_type_dir)) && is_dir("$s$t$r")) $a[] = "$o$t$r";
		}
	}
	return $a;
}

function get_file_lines($path) {
	return (
		is_file($path)
		? mb_split_filter(file_get_contents($path), NL)
	//	? mb_split_filter(trim_bom(file_get_contents($path)), NL)	//* <- trim messes up line indexes, don't touch for now
	//	? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
		: array()
	);
}

function get_file_ext($path, $flags = 0) {return mb_strtolower(get_file_name($path, $flags, '.'));}
function get_file_dir($path, $flags = F_GET_FULL_IF_NONE, $delim = '/') {return get_file_name($path, $flags | F_GET_DIR_PART, $delim);}
function get_file_name($path, $flags = F_GET_FULL_IF_NONE, $delim = '/') {
	return (
		false === ($pos = mb_strrpos($path, $delim))
		? ($flags & F_GET_FULL_IF_NONE ? $path : '')
		: (
			$flags & F_GET_DIR_PART
			? mb_substr($path, 0, $pos)	//* <- last delimiter not included
			: mb_substr($path, $pos + mb_strlen($delim))
		)
	);
}

function get_room_skip_list($add = '', $room = '') {
	$a = array();
	if (
		!$GLOBALS['room_type']['single_active_thread']
	&&	($r = $room ?: $GLOBALS['room'])
	) {
		$room_sep = '//';
		$skip_sep = '/';
		$k = array();
		$e = array(
			encode_URL_parts($r, true)
	//	,	encode_opt_value($r)		//* <- uses base64, but atob() in JS turns utf8 into garbage, not usable
		);
//* find existing list in cookies:
		foreach ($e as $r) {
			$k[] = $q = ME.'-skip-'.md5($r);
			if ($v = $_COOKIE[$q]) {
				$a = array_slice(
					explode(
						$skip_sep
					,	end(explode($room_sep, $v, 2))
					,	TRD_MAX_SKIP_PER_ROOM + 1
					)
				,	0
				,	TRD_MAX_SKIP_PER_ROOM - ($add?1:0)
				);
				break;
			}
		}
		if (strlen($add)) {
//* remove same value:
			if ($a) {
				foreach ($a as $k => $v) if ($v === $add) unset($a[$k]);
			} else {
//* if room name not yet defined, find shortest encoding result:
				foreach ($e as $i => $j) if (strlen($j) < strlen($r)) {
					$q = $k[$i];
					$r = $j;
				}
			}
//* prepend given value + return new cookie:
			array_unshift($a, $add);
			$v = implode($skip_sep, $a);
			return "$q=$r$room_sep$v";
		}
	}
//* if not adding, return the list array:
	return $a;
}

function get_room_name_length($name, $arr = false) {
	global $cfg_room_prefixes;
	$found_prefixes = array();
	do {
		$found = 0;
		foreach ($cfg_room_prefixes as $v) if (
			0 === strpos($name, $v)
		&&	!in_array($v, $found_prefixes)
		) {
			$found++;
			$found_prefixes[] = $v;
			$name = substr($name, strlen($v));
		}
	} while (strlen($name) && $found);
	$l = mb_strlen($name);
	return ($arr ? array($l, $name, $found_prefixes) : $l);
}

function get_room_type($room, $type = '') {
	global $cfg_game_type_dir, $cfg_room_types;
	$g = 'game_type';
	$sub = mb_split_filter($room);
	$name = array_pop($sub);
//* 1) check game types in room subfolders:
	if ($type) {
		if ($v = $cfg_game_type_dir[$type]) return ($v ? in_array($v, $sub) : !$sub);
		$result = 0;
	} else {
		$result = array();
		foreach ((array)($sub ?: GAME_TYPE_DEFAULT) as $v) if (false !== ($k = array_search($v, $cfg_game_type_dir))) {
			$result[$g] = $k;
		}
	}
//* 2) check room name:
	list($l, $name, $found_prefixes) = get_room_name_length($name, true);
//* if any, match all defined criteria in each rule set:
	foreach ($cfg_room_types as $set_id => $set) if (
		(!isset($set['if_game_type']) || (isset($result[$g]) && $set['if_game_type'] === $result[$g]))
	&&	(!($v = $set['if_name_prefix']) || in_array($v, $found_prefixes))
	&&	(!($v = intval($set['if_name_length'    ])) || $l == $v)
	&&	(!($v = intval($set['if_name_length_min'])) || $l >= $v)
	&&	(!($v = intval($set['if_name_length_max'])) || $l <= $v)
	) {
//* latest found values for a key overwrite any previous:
		if ($type) {
			if (array_key_exists($type, $set)) $result = $set[$type];
		} else {
			$result['set'][] = $set_id;
			foreach ($set as $k => $v) $result[$k] = $v;
		}
	}
	return $result;
}

function get_dir_top_file_id($d, $e = '') {
	$i = 0;
	foreach (get_dir_contents($d) as $f) if (($n = intval($f)) && (!$e || $f === "$n$e") && $i < $n) $i = $n;
	return $i;
}

function get_dir_top_filemtime($d) {
	$t = 0;
	foreach (get_dir_contents($d) as $f) if (($m = filemtime("$d/$f")) && $t < $m) $t = $m;
	return $t;
}

function get_pic_url($p) {return ROOTPRFX.(PIC_SUB ? get_pic_subpath($p) : DIR_PICS.get_file_name($p));}
function get_pic_normal_path($p) {return preg_replace('~(^|[\\/])([^._-]+)[^._-]*(\.[^.,;]+)([;,].*$)?~u', '$2$3', $p);}
function get_pic_resized_path($p) {$s = '_res'; return (false === ($i = mb_strrpos($p, '.')) ? $p.$s : mb_substr($p,0,$i).$s.mb_substr($p,$i));}
function get_pic_subpath($p, $mk = false) {
	$p = get_pic_normal_path(get_file_name($p));
	$i = mb_strpos_after($p, '.');
	$e = mb_substr($p,$i,1);
	$n = mb_substr($p,0,1);
	$p = DIR_PICS."$e/$n/$p";
	return ($mk ? mkdir_if_none($p) : $p);
}

function get_date_class($t_first = 0, $t_last = 0) {	//* <- use time frame for archive pages; default = current date
	global $cfg_date_class, $date_classes;
	if (!$t_first && !$t_last && $date_classes) return $date_classes;
	if (!$t_first) $t_first = T0;
	if (!$t_last) $t_last = $t_first;
	$date_classes = array();
	foreach ($cfg_date_class as $a) if (is_array($a) && $a[0] && $a[1]) {
		$now = array(
			date($a[1], $t_first)
		,	date($a[1], $t_last)
		);
		$due1 = ($a[2] ?: false);
		$due2 = ($a[3] ?: false);
		$flag = ($a[4] ?: 2);
		$wrap = ($due1 !== false && $due2 !== false && $due1 > $due2);	//* <- wrap around new year, etc
		if (!$flag) $flag = 7;
		for ($i = 0; $i < 2; $i++) if ($flag & (1<<$i)) {
			$check = array(
				($due1 === false || $now[$i] >= $due1)
			,	($due2 === false || $now[$i] <= $due2)
			);
			if ($wrap
			?	($check[0] || $check[1])
			:	($check[0] && $check[1])
			) {
				$date_classes[] = $a[0];
				break;
			}
		}
	}
	return $date_classes;
}

function get_draw_app_list($allow_upload = true) {
	global $cfg_draw_app, $tmp_draw_app, $tmp_draw_app_select, $tmp_require_js, $tmp_upload_file, $u_draw_app, $query;
	$a = $tmp_draw_app_select;
	$da = 'draw_app';
	if (!$allow_upload && ($k = array_search(DRAW_APP_NONE, $cfg_draw_app)) !== false) unset($cfg_draw_app[$k]);
	if (
		!($n = $query[$da] ?: $u_draw_app)
	||	!in_array($n, $cfg_draw_app)
	) $n = $cfg_draw_app[0];
	foreach ($cfg_draw_app as $k => $v) $a .= ($k?',':':').NL.(
		$n == $v
		? $tmp_draw_app[$k]
		: '<a href="?'.$da.'='.$v.'">'.$tmp_draw_app[$k].'</a>'
	);
	$a = array('list' => '
<p class="hint" id="draw-app-select">'.indent("$a.").'</p>');
	if ($n !== DRAW_APP_NONE) {
		$f = $n;
		if (false !== ($s = mb_strrpos      ($n, '.'))) $n = mb_substr($n, 0, $s); else $f .= DRAW_APP_DEFAULT_EXT;
		if (false !== ($s = mb_strrpos_after($n, '/'))) $n = mb_substr($n, $s);
		$ext = get_file_ext($f);
		$f = ROOTPRFX.$f.(LINK_TIME?'?'.filemtime($f):'');
		$a['name'] = $n;
	}
	if ($ext == 'js') {
		$a['noscript'] = '
<noscript>'.indent('<p class="hint">'.$tmp_require_js.'</p>').'</noscript>';
		$a['embed'] = '
<div id="draw-app">'.indent('<script id="'.$n.'-vars" src="'.$f.'" data-vars="'.get_draw_vars($allow_upload ? DRAW_SEND : '').'"></script>').'</div>';
	} else {
		$a['embed'] = '
<form method="post" enctype="multipart/form-data">
	<b>
		<b><input type="file" name="pic" required></b>
		<b><input type="submit" value="Submit"></b>
	</b>
	<input type="hidden" name="t0" value="'.T0.'">
</form>';
	}
	return $a;
}

function get_draw_vars($send = '') {
	global $cfg_draw_vars, $cfg_wh, $u_opts, $query;
	$vars = ($send?"$send;":'').DRAW_REST.
		';keep_prefix='.DRAW_PERSISTENT_PREFIX
	.($u_opts['save2common']?'':
		';save_prefix='.DRAW_BACKUPCOPY_PREFIX.';saveprfx='.NAMEPRFX
	);
	foreach ($cfg_draw_vars as $k => $v) {
		if (($i = $GLOBALS["u_$v"]) || ($i = get_const($v))) $vars .= ";$k=$i";
	}
	if (($res = $query['draw_res']) && false !== mb_strpos($res, 'x')) $wh = array_map('intval', mb_split('x', $res, 2));
	if ($send) {
		foreach (array('DEFAULT_', 'LIMIT_') as $i => $j)
		foreach ($cfg_wh as $k => $l) {
			$p = mb_strtolower(mb_substr($l,0,1)).($i?'l':'');
			if ((!$i && $wh && ($v = $wh[$k])) || ($v = get_const("DRAW_$j$l"))) $vars .= ";$p=$v";
		}
	}
	return csv2nl($vars);
}

function get_flag_vars($a) {
	global $page, $u_opts;
	$r = '';
	foreach ($a as $k => $v) {
		if (!is_array($v)) {
			$mode = $v;
			if ($k == 'caps') $v = array(
				'at'
			,	array(
					!$u_opts['capture_altclick']
				,	!$u_opts['capture_textselection']
				)
			);
		}
		list($letter, $switch) = $v;
		$v = '';
		foreach ($switch as $i => $on) if ($on) $v .= $letter[$i];
		if ($v) {
			$r .= "
$k = $v";		if ($k == 'caps') {
				$r .= "
caps_around = $mode
caps_width = ".DRAW_PREVIEW_WIDTH;
				$page['js']['capture']++;
			}
		}
	}
	return $r;
}

function get_time_html($t = 0) {
	$sep = 'T';
	$f = date(DATE_ATOM, $uint = ($t ?: T0));
	$i = mb_strpos($f, $sep);
	$d = mb_substr($f, 0, $i);
	$t = mb_substr($f, $i + mb_strlen($sep), 8);
	return '<time datetime="'.$f.'" data-t="'.$uint.'">'.$d.' <small>'.$t.'</small></time>';
}

function get_time_elapsed($t = 0) {
	$t = explode(' ', $t ?: microtime());
	return ($t[1]-T0) + ($t[0]-M0);
}

function get_time_seconds($t) {
	if (mb_strrpos($t, ':') > 0) {
		$sec = 0;
		foreach (explode(':', $t) as $n) $sec = $sec*60 + intval($n);
		return ($t[0] == '-'?-$sec:$sec);
	}
	return floor($t);	//* <- 32-bit signed integer overflow workaround
}

function format_time_units($t) {
	while (is_array($t)) $t = reset($t);
	global $tmp_time_units;
	foreach ($tmp_time_units as $k => $v) if ($t >= $k) {
		if ($k) {$rem = $t%$k; $t = floor($t/$k);}
		$i = count($v)-1;
		if ($t < 11 || $t >= 20) {
			$s = $t%10;
			if ($s == 1) $i = 0; else
			if ($s > 1 && $s < 5) $i = 1;
		}
		$r .= ($r?' ':'').$t.' '.$v[$i];
		if ($rem) $t = $rem; else break;
	}
	return $r;
}

function format_filesize($b = 0, $frac = 2) {
	$m = 'BkMGTPEZY';
	if ($i = floor((strlen($b) - 1) / 3)) {
		$b = sprintf("%.{$frac}f", $b/pow(1024, $i));
		return "$b $m[$i]$m[0]";
	}
	return "$b $m[0]";
}

function optimize_pic($filepath) {
	if (
		function_exists('exec')
	&&	($f = $filepath)
	&&	($ext = get_file_ext($f))
	&&	is_file($f)
	) {
		$out_path = $f.($out = '.out');
		$bad_path = $f.($bad = '.bad');
		$bak_path = $f.($bak = '.bak');
		$d = DIRECTORY_SEPARATOR;
		$i = (function_exists('ini_get') && function_exists('ini_set'));
		$m = 'max_execution_time';
		global $cfg_optimize_pics;
		foreach ($cfg_optimize_pics as $format => $tool) if ($ext == $format)
		foreach ($tool as $arr) {
			list($program, $command) = array_map('mb_normalize_slash', $arr);
			$program_name = get_file_name($program);
			if (is_file($p = $program) || is_file($p .= '.exe')) $p = "./$p";
			else if (PIC_OPT_TRY_GLOBAL_EXEC) $p = $program_name;
			else continue;

			$return_code = $size = 0;
			$output = array('');
			$cmd = sprintf($command, $p, $f);
			if ($d !== '/') $cmd = str_replace('/', $d, $cmd);
			if ($i) ini_set($m, ini_get($m) + max(1, intval(PIC_OPT_ADD_TIMEOUT)));

			data_lock($lk = LK_PIC_OPT);
			$return = exec($cmd, $output, $return_code);
			$done = ($return_code ? 'error' : 'done');
			if (is_file($out_path)) {
				$size = filesize($f);
				$out_size = filesize($out_path);
				$old = "old $f = $size bytes";
				$new = "new $out_path = $out_size bytes";
				if (
					$out_size
				&&	($out_size < $size)
				&&	!$return_code
				&&	unlink($f)
				) {
					$ren = (rename($out_path, $f) ? 'renamed' : 'could not rename');
					$done .= ",\n deleted $old,\n $ren $new -> $f";
				} else
				if (is_file($f)) {
					$done .= ",\n kept $old";
					if (is_file($out_path)) {
						$del = (unlink($out_path) ? 'deleted' : 'not deleted');
						$done .= ",\n $del $new";
					}
				}
			} else
			if (is_file($bak_path) && ($bak_size = filesize($bak_path))) {
				$del = (
					is_file($f)
					? (
						($size = filesize($f))
						? (
							($rest = rename($f, $bad_path))
							? "renamed to *$bad"
							: 'not renamed'
						) : (
							($rest = unlink($f))
							? 'deleted'
							: 'not deleted'
						)
					) : ($rest = 'not found')
				);
				if ($rest) $rest = ".\nRestoring from $bak_path = $bak_size bytes, ".(
					!is_file($f) && rename($bak_path, $f)
					? 'done'
					: 'failed'
				);
				$done = "failed, $f = $size bytes, $del$rest";
				if (!$return_code) $return_code = '0, fallback';
			}
			data_unlock($lk);

			if ($return_code) {
				$o = trim(preg_replace('~\v+~u', NL, implode(NL, (array)$output)));
				if (strlen($o)) {
					if (false !== mb_strpos($o, NL)) $o = NL.'['.indent($o).']';
				} else $o = 'empty';
				data_log_action("Optimization $done.
Command line: $cmd
Return code: $return_code
Return text: $return
Shell output: $o");
			} else return $program_name;
		}
	}
}

//* front end templates -------------------------------------------------------

function indent($t, $n = 0) {
	return (
		!strlen($t = trim($t))
	||	(!$n && false === mb_strpos($t, NL))
	)
	? $t
	: preg_replace(
		'~(?:^|\v+)(?:(\h*<(pre|textarea)\b.+?)(?=\v+\h*</\2>))?~uis'
	,	NL.str_repeat("\t", $n > 0?$n:1).'$1'
	,	$t
	).NL;
}

function csv2nl($v, $c = ';', $n = 1) {
	$d = "\s*[$c]+\s*";
	$n = NL.($n > 0?str_repeat("\t", $n):'');
	return $n.implode($c.$n, preg_split("~$d~u", preg_replace("~^$d|$d$~u", '', $v))).$c.NL;
}

function get_template_attr($a = '', $prefix = 'data-') {
	foreach ((array)$a as $k => $v) if (strlen($v)) $line .= ' '.$prefix.$k.'="'.$v.'"';
	return $line ?: '';
}

function get_template_welcome_row($sep, $a, $cfg_k = '') {
	global $cfg_welcome_links;
	$a = (array)$a;
	foreach ($a as $k => $v) if ($v) {
		if ($cfg_k && ($i = $cfg_welcome_links[$cfg_k][$k])) {
			$txt = $v;
			$url = $i;
		} else
		if ($k !== intval($k)) {
			$txt = $k;
			$url = $v;
		} else continue;

		if (
			false === ($i = mb_strpos($txt, '<img'))
		||	false === ($j = mb_strpos_after($txt, '>', $i))
		) continue;

		if (is_array($url)) {
			list($f, $g) = $url;
			$url = (function_exists($f) ? $f($g) : $g);
		}
		$a[$k] = indent(
			mb_substr($txt, 0, $i)
		.NL.	'<a href="'.$url.'" target="_blank">'
		.NL.	'	'.mb_substr($txt, $i, $j-$i)
		.NL.	'</a>'
		.NL.	mb_substr($txt, $j)
		);
	}
	return implode($sep, $a);
}

function get_template_welcome_see_do($c, $u, $see = '', $do = '') {
	$user = NL."<td>	$u[who]	</td>";
	$skip = NL.'<td>	...	</td>';
	$class = '<td class="thread">';
	$td = '<td></td><td></td>';
	return '<tr class="'.$c.' see">'.indent(
		$user.$skip.$class.get_template_welcome_row(
			'</td>'.NL.'<td class="then"></td>'.$class
		,	$see ?: array(
				"$u[desc_see]:"
			,	"$u[pic_see]:"
			,	"$u[desc_see]:"
			)
		,	$see?'see':''
		).'</td>'.$skip.$user
	).NL.'</tr><tr class="'.$c.' do">'.indent(
		$td.NL.$class.get_template_welcome_row(
			'</td><td></td>'.NL.$class
		,	$do ?: array(
				$u['desc_do']
			,	$u['pic_do']
			,	$u['desc_do']
			)
		,	$do?'do':''
		).'</td>'.NL.$td
	).'</tr>';
}

function get_template_welcome_interleave($c = '', $t = '') {
	if ($c) $c = ' class="'.$c.'"';
	if ($t) $t = "	$t	";
	$d = '<td></td>';
	$a = NL.$d.$d;
	$b = NL.'<td class="thread">'.$t.'</td>';
	return "<tr$c>".indent("$a$b$d$b$d$b$a").'</tr>';
}

function get_template_form($t) {
	if (is_array($t)) {
		foreach ($t as $k => $v) $$k = $v ?: '';
	} else $name = $t;
	if (is_array($a = $select)) {
		$n = '';
		foreach ((array)$a as $k => $v) {
			if (!$name) $name = $k;
			$n .= NL."	$k	$v[select]	$v[placeholder]";
		}
		$select = htmlspecialchars($n).NL;
	}
	if ($name) {
		foreach (
			explode(',', 'head,hint,placeholder,submit') as $v
		) if (!$$v) {
			$$v = $GLOBALS["tmp_$name".($v == 'head'?'':"_$v")] ?: $GLOBALS["tmp_$v"] ?: '';
		}
	}
	if ($a = $method) $method = ' method="'.$a.'"';
	if ($a = $filter) $attr .= ' id="filter" data-filter="'.$a.'"';
	if ($a = $placeholder ?: ($a?$GLOBALS['tmp_filter_placeholder']:'')) $attr .= ' placeholder="'.$a.'"';
	if (!$GLOBALS['u_opts']['focus']) $attr .= ' autofocus';
	if ($name) {
		$name = ' name="'.$name.'"'.$attr.' required';
		if ($min||$max) $name .= (
			$textarea
			? ($min?' minlength="'.$min.'"':'').($max?' maxlength="'.$max.'"':'')
			: ' pattern="\s*(\S\s*){'.($min ?: 0).','.($max ?: '').'}"'
		);
	}
	if ($head) {
		$head = NL
		.'<p>'
		.	indent($head.':')
		.'</p>';
	}
	if ($a = $hint) {
		$hint = '';
		foreach ((array)$a as $k => $v) $hint .= NL
		.'<p class="hint'.($k?" $k":'').'">'
		.	indent(get_template_hint(is_array($v) ? implode(',', array_map(function($a) {return is_array($a)?$a[0]:$a;}, $v)) : $v))
		.'</p>';
	}
	if ($a = $checkbox) {
		if (!is_array($a)) $a = array('label' => $a);
		if ($n = $a['name'] ?: 'check') $n = ' name="'.$n.'"';
		if ($r = $a['required'] ?: '') $r = ' required';
		$checkbox = NL
		.	'<label class="r">'
		.		indent("$a[label]:".NL.'<input type="checkbox"'.$n.$r.'>')
		.	'</label>';
	}
	$submittable = ($submit || $method);
	return $head.NL.(
		$name || $method
		? "<form$method>".indent(
			'<b>'.indent(
				'<b><'.(
					$textarea
					? "textarea$name></textarea"
					: 'input type="text"'.$name
				).(
					$select
					? ' data-select="'.$select.'"'
					: ''
				).'></b>'.(
					$submittable
					? NL.'<b><input type="submit" value="'.($submit ?: $GLOBALS['tmp_submit']).'"></b>'
					: ''
				)
			).'</b>'.$checkbox.(
				$submittable
//* about "_charset_": https://www.w3.org/TR/html5/forms.html#naming-form-controls:-the-name-attribute
				? NL.'<input type="hidden" name="_charset_">'.(
					$GLOBALS['u_key']
					? ''
					: NL.'<input type="text" name="pass" value="" placeholder="'.($GLOBALS['tmp_spam_trap'] ?: 'spam').'">'
				) : ''
			)
		).'</form>'
		: '<p><b><input type="text"'.$attr.'></b></p>'
	).$hint;
}

function get_template_hint($t) {
	return str_replace(
		'href="+"'
	,	'href="javascript:void this" onClick="toggleClass(this.nextElementSibling,\'hid\')"'
	,	str_replace(
			str_split('{`}[|]\\')
		,	array('<a href="', '" onClick="', '</a>', '<span class="', '">', '</span>', NL)
		,	nl2br(
				htmlspecialchars(
					preg_replace(
						'~([\\\\])\\v+~u'
					,	'$1'
					,	preg_replace_callback(
							'~\b\d+s\b~u'
						,	'format_time_units'
						,	$t
						)
					)
				)
			,	false
			)
		)
	);
}

function get_template_content($p, $static = 0, $tag = '', $attr = '') {
	global $tmp_require_js, $tmp_result;
	if (is_array($p)) {
		$a = $p;
		$p = '';
		foreach ($a as $k => $v) if (!$k) $p = $v; else if ($v) $attr .= " $k=\"$v\"";
	}
	if (strlen($p)) {
		if (GOD && !$static && ($v = $GLOBALS[$k = 'fix_encoding_chosen'])) {
			$v = implode(',', (array)$v);
			$p = "
$k = $v$p";
		}
		$t = $tag ?: 'pre';
		$p = "
<$t$attr>$p
</$t>
<noscript>".indent('<p class="hint report">'.($static?'JavaScript support required.':$tmp_require_js).'</p>').'</noscript>';
		return '<div class="'.($static?'thread':'content" id="content').'">'.indent($p).'</div>';
	}
	return '';
}

function get_template_page($page) {
	global $lang, $tmp_announce, $tmp_post_err, $room;
	if (!is_array($j = $page['js'] ?: array())) $j = array($j => 1);
	$R = !!$j['arch'];
	$RL = $page['link'] ?? '';
	$LN = $page['listing'] ?? '';
	$N = ROOTPRFX.NAMEPRFX;
	$static = ($LN || $R);
	$class = (($v = $page['body']) ? (array)$v : array());
	$anno = array();
	if ($page['anno']) {
		foreach (data_global_announce('all') as $k => $v) {
			if (strlen($v)) {
				if (MOD) $v = "<span id=\"$k\">$v</span>";
				$v = ": $v";
				$c = (false !== mb_strpos($k, 'stop')?'cold':'dust');
			} else $c = 'new';
			$anno[$c][] = $tmp_announce[$k].$v;
		}
	}
	if (!$static) {
		$L = LINK_TIME;
		if ($a = $page['report']) {
			$e_class = array(
				'trd_arch' => 'gloom'
			,	'trd_miss' => 'warn'
			);
			if (!is_array($a)) $a = mb_split_filter($a, ARG_ERROR_SPLIT);
			foreach ($a as $v) if ($v = trim($v)) {
				$anno[$e_class[$v] ?: 'report'][] = $tmp_post_err[$v] ?: $v;
			}
		}
		if (FROZEN_HELL) $class[] = 'frozen-hell';
		if ($d = get_date_class()) $class = array_merge($class, $d);
	}
	if ($a = $page[$k = 'content']) {
		$attr = get_template_attr($page['data'][$k]);
		if ($LN) {
			foreach ((array)$a as $k => $v) $content .= ($content?NL:'').NL.$k.$LN.$v;
			$content = "
<pre$attr>$content
</pre>";
		} else $content = get_template_content($a, $static, '', $attr);
	}
	if ($a = $page[$k = 'welcome']) {
		if (is_array($a)) {
			$i = '<br><img src="'.ROOTPRFX.NAMEPRFX.'.demo';
			$e = '.png">';
			$sdo = get_template_welcome_see_do($v = 'other', $a[$v]);
			$sdu = get_template_welcome_see_do($v = 'you', $u = $a[$v]
			,	array(
					"$u[pic_see]:	$i.1a$e"
				,	"$u[desc_see]:	$i.2a$e"
				,	"$u[pic_see]:	$i.3a$e"
				)
			,	array(
					"$u[pic_do]	$i.1b$e"
				,	"$u[desc_do]	$i.2b$e"
				,	"$u[pic_do]	$i.3b$e"
				)
			);
			$$k = (($i = $a['header']) ? "<p>$i</p>".NL : '')
			.'<table>'.indent(
				get_template_welcome_interleave('prev', $a['head']).$sdo
			.	get_template_welcome_interleave('prev').$sdu
			.	get_template_welcome_interleave('next').$sdo
			.	get_template_welcome_interleave('next', $a['tail'])
			).'</table>'
			.(($i = $a['footer']) ? NL."<p>$i</p>" : '');
		} else $$k = $a;
		if ($$k) $$k = '<div class="'.$k.'">'.indent($$k).'</div>';
	}
	if ($RL || !ME_VAL) {
		$k = $GLOBALS['cfg_link_schemes'] ?? '';
		if ($k = (is_array($k)?$k[0]:$k)) {
			$v = $RL ?: $_SERVER['REQUEST_URI'];
			$canon = "$k://$_SERVER[SERVER_NAME]$v";
		}
	}
	$head = '<meta charset="'.ENC.'">'.($LN?'':'
<meta name="viewport" content="width=690">
<link rel="stylesheet" type="text/css" href="'.$N.($v = '.css').($L?'?'.filemtime(NAMEPRFX.$v):'').'">').'
<link rel="shortcut icon" type="image/png" href="'.(($v = $page['icon'])?ROOTPRFX.$v:$N).'.png">'
.($canon?'
<link rel="canonical" href="'.$canon.'">':'')
.($R || ME_VAL?'
<link rel="index" href="//'.$_SERVER['SERVER_NAME'].ROOTPRFX.($room?'" data-room="'.$room:'').'">':'')
.(($v = $page['head'])?NL.$v:'')
.(($v = $page['title'])?NL."<title>$v</title>":'');

	if ($a = (array)$anno) {
		ksort($a);
		$i = ($page['anno']?'anno':'r');
		foreach ($a as $k => $v) {
			$block = '';
			if (is_array($v)) foreach ($v as $line) {
				$block .= NL.'<b>'.indent($line).'</b>';
			}
			$anno_lines .= NL.'<p class="'.$i.($k?" $k":'').'">'.indent($block ?: $v).'</p>';
		}
		if ($page['anno']) {
			$header .= $anno_lines;
			$anno_lines = '';
		}
	}
	if ($a = $page[$k = 'header']) {
		if (is_array($a)) {
			foreach ($a as $i => &$v) if ($v) $v = ($i?'<u class="'.$i.'">':'<u>').indent($v).'</u>';
			$a = implode(NL, $a);
		}
		$header .= NL.'<p>'.indent($a).'</p>';
	}
	if ($header) {
		$i = '"'.$k.'"';
		$attr = get_template_attr($page['data'][$k]);
		$header = "<$k id=$i class=$i$attr>".indent($header)."</$k>";
	}
	if ($v = $page[$txt = 'textarea']) $$txt = NL.trim(htmlspecialchars($v));
	if ($v = $page[$k = 'task']) {
		$attr = get_template_attr($page['data'][$k]);
		if ($sub = $page['subtask']) $v = '<div class="task">'.indent($v).'</div>'.$sub;
		else if (!$static) $attr = ' class="task"'.$attr;
		if ($sub = $$txt) {
			$k = ' class="dump" id="dump"';
			$v .= "
<div$k>
	<$txt>$sub
	</$txt>
</div>";
		}
		$task = '<div id="task"'.$attr.'>'.indent($anno_lines.$v).'</div>';
	} else
	if ($v = $$txt) $content .= get_template_content($anno_lines.$v, $static, $txt);
	if ($v = $page['footer']) $footer = '<footer>'.indent($v).'</footer>';

	if ($j) foreach ($j as $k => $v) $scripts .= '
<script src="'.$N.($v = ($k?".$k":'').'.js').($L?'?'.filemtime(NAMEPRFX.$v):'').'"></script>';

	return '<!doctype html>
<html lang="'.($page['lang'] ?: $lang ?: 'en').'">
<head>'
.indent($head)
.'</head>
<body'.($class?' class="'.implode(' ', $class).'"':'').'>'
.indent($header, 1)
.indent($task, 1)
.indent($welcome, 1)
.indent($content, 1).(($t =
 indent($footer, 1)
.indent($scripts, 1)) && ($k = get_const('TOOK'))?str_replace($k, round(get_time_elapsed(), 9), $t):$t)
.'</body>
</html>';
}

?>
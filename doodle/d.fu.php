<?php

function exit_if_not_mod($t = 0) {
	$t = gmdate('r', $t ? max(data_global_announce('last'), $t) : T0);
	$q = 'W/"'.md5(
		'Refresh any page cached before 2017-02-20 00:00'	//* <- change this line to invalidate browser cache after breaking changes
	.NL.	'Or if user key, options or date-related decoration changed: '.ME_VAL
	.NL.	implode(NL, get_date_class())
	).'"';
	header('Etag: '.$q);
	if (
		!$GLOBALS['u_opts']['modtime304']
	&&	isset($_SERVER[$m = 'HTTP_IF_MODIFIED_SINCE'])
	&&	isset($_SERVER[$n = 'HTTP_IF_NONE_MATCH'])
	&&	$_SERVER[$m] == $t
	&&	$_SERVER[$n] == $q
	) {
		header('HTTP/1.0 304 Not Modified');
		exit;
	}
	header('Last-Modified: '.$t);
}

function get_const($name) {return defined($name) ? constant($name) : '';}
function rewrite_htaccess($write_to_file = 1) {
	$start_mark = '# 8<-- start mark: '.NAMEPRFX.', version: ';
	$end_mark = '# 8<-- end mark: '.NAMEPRFX.', placed automatically: ';
	$new_mark = $start_mark.ROOTPRFX.' 2016-09-13 17:58';		//* <- change this to invalidate old version
	if (
		!strlen($old = (is_file($f = '.htaccess') ? trim(file_get_contents($f)) : ''))
	||	false === strpos($old, $new_mark)
	||	false === strpos($old, $end_mark)
	) {
		$d = '('.implode('|', $GLOBALS['cfg_dir']).')(/([^/]+))?';
		$ddre = str_regex_escaped($dd = get_const('DIR_DATA'));
		$dere = get_const('DERE') ?: str_regex_escaped('.log');
		$new = $new_mark.' -- Do not touch these marks. Only delete them along with the whole block.
<IfModule rewrite_module>
	RewriteEngine On
	RewriteBase '.ROOTPRFX.'
# virtual folders:'.($dd?'
	RewriteRule ^'.$ddre.'.*$ . [L,R=301]':'').'
	RewriteRule ^('.DIR_PICS.')(([^/])[^/]+\.([^/])[^/]+)$ $1$4/$3/$2'.'
	RewriteRule ^'.$d.'$ $0/ [L,R=301]'.'
	RewriteRule ^'.$d.'(/[-\d]*|[^?]*'.$dere.'([?/].*)?)$ . [L]
# files not found:
	RewriteCond %{REQUEST_FILENAME} -f [OR]
	RewriteCond %{REQUEST_FILENAME} -d
	RewriteRule ^.? - [S=2]
	RewriteRule ^('.DIR_PICS.'|'.DIR_ARCH.'[^/]+/'.DIR_THUMB.').*$ err.png [L]
	RewriteRule ^('.$d.'/).+$ $1 [L,R]'.'
</IfModule>
'.$end_mark.date(TIMESTAMP, T0).' -- Manual changes inside will be lost on the next update.';
		if ($old) {
			$b_found = (false !== ($i = strpos($old, $start_mark)));
			$before = ($b_found ? trim(substr($old, 0, $i)) : '');

			$a_found = (false !== ($i = strpos($old, $end_mark)));
			$after = ($a_found && false !== ($i = strpos($old, NL, $i)) ? trim(substr($old, $i)) : '');

			if ($b_found || $a_found) $new =
				(strlen($before) ? $before.NL.NL : '')
			.	$new
			.	(strlen($after) ? NL.NL.$after : '')
			; else $new .= NL.NL.$old;
		} else $old = 'none';
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
	return $report;
}

function fix_encoding($text) {
	global $fix_encoding_chosen;
	if (mb_check_encoding($text)) {
		$fix_encoding_chosen[] = ENC;
		return $text;
	}
//* Apache / nginx: OK for GET archive search;
//* nginx only: does not work for POST room name input or manual folder request via address bar:
	if (function_exists('iconv')) {
		foreach (explode(',', $ef = get_const('ENC_FALLBACK')) as $e) if (
			strlen($e = trim($e))
		&&	false !== ($fix = iconv($e, ENC, $text))
		&&	mb_check_encoding($fix)
		) {
			$fix_encoding_chosen[] = $e;
			return $fix;
		}
	}
//* another futile attempt:
	if (false !== ($e = mb_detect_encoding($text, $ef, true))) {
		$fix_encoding_chosen[] = $e;
		return mb_convert_encoding($text, ENC, $e);
	}
	return $text;
}

function str_regex_escaped($s) {return str_replace('.', '\\.', $s);}
function str_replace_first($f, $to, $s) {return false === ($pos = strpos($s, $f)) ? $s : substr_replace($s, $to, $pos, strlen($f));}
function abbr($a, $sep = '_') {foreach ((is_array($a)?$a:explode($sep, $a)) as $word) $r .= $word[0]; return $r;}
function trim_post($p, $len = 456) {
	$s = trim(preg_replace('~\s+~us', ' ', $p));
	if ($len > 0) $s = mb_substr($s, 0, $len);
	return POST ? htmlspecialchars($s) : $s;
}

function trim_room($r) {
	return mb_strtolower(mb_substr(
		preg_replace('/\.+/u', '.',
		preg_replace('/[^\w\x{0400}-\x{04ff}\x{2460}-\x{2468}\x{2605}-\x{2606}.!-]+/u', '_',
		trim(trim($r), '\\/')
	)), 0, ROOM_NAME_MAX_LENGTH));
}

function is_tag_attr($t) {return strpos($t, '<') === strpos($t, '>');}	//* <- if only both === false
function is_not_dot($path) {return !!trim($path, './\\');}
function is_not_hidden($room) {
	global $u_flag;
	return (
		GOD
	||	$u_flag['mod']
	||	$u_flag["mod_$room"]
	||	$room[0] != get_const('ROOM_HIDE')
	);
}

function get_dir_contents($path = '.', $num_sort = 0, $hiding = 0) {
	$a = (is_dir($path) ? array_filter(scandir($path), 'is_not_dot') : array());
	if ($a && $hiding) $a = array_filter($a, 'is_not_hidden');
	if ($a && $num_sort) natcasesort($a);
	return $a;
}

function get_file_lines($path) {return is_file($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : array();}
function get_file_name($path, $full = 1, $delim = '/') {return false === ($rr = strrpos($path, $delim)) ? ($full?$path:'') : substr($path, $rr+1);}
function get_file_ext($path, $full = 0) {return mb_strtolower(get_file_name($path, $full, '.'));}
function get_room_skip_name($r) {return array(ME.'-skip-'.md5($r = rawURLencode($r)), $r);}
function get_room_skip_list($k = '') {
	return ($v = $_COOKIE[$k ?: reset(get_room_skip_name($GLOBALS['room']))])
	?	array_slice(explode('/', $v, TRD_MAX_SKIP_PER_ROOM+1), 1, TRD_MAX_SKIP_PER_ROOM-($k?1:0))
	:	array();
}

function get_dir_top_file_id($d) {
	$i = 0;
	foreach (get_dir_contents($d) as $f) if (($n = intval($f)) && $i < $n) $i = $n;
	return $i;
}

function get_dir_top_filemtime($d) {
	$t = 0;
	foreach (get_dir_contents($d) as $f) if (($m = filemtime("$d/$f")) && $t < $m) $t = $m;
	return $t;
}

function get_pic_normal_path($p) {return preg_replace('~(^|[\\/])([^._-]+)[^._-]*(\.[^.,;]+)([;,].*$)?~', '$2$3', $p);}
function get_pic_resized_path($p) {return substr_replace($p, '_res', -4, 0);}
function get_pic_subpath($p, $mk = 0) {
	$p = get_pic_normal_path($p);
	$n = DIR_PICS.$p[strrpos($p, '.')+1].'/'.$p[0].'/';
	if ($mk && !is_dir($n)) mkdir($n, 0755, true);
	return $n.($mk === ''?'':$p);
}

function get_pic_url($p) {return ROOTPRFX.(PIC_SUB?get_pic_subpath($p):DIR_PICS.$p);}
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
	global $cfg_draw_app, $tmp_draw_app, $tmp_options_input, $tmp_require_js, $tmp_upload_file, $u_draw_app, $query;
	$a = $tmp_options_input['input'][$da = 'draw_app'];
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
	$a = array('list' => $a.'.');
	if ($n !== DRAW_APP_NONE) {
		$f = $n;
		if (false !== ($s = strrpos($n, '.'))) $n = substr($n, 0, $s); else $f .= DRAW_APP_DEFAULT_EXT;
		if (false !== ($s = strrpos($n, '/'))) $n = substr($n, $s+1);
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
	global $cfg_draw_vars, $tmp_wh, $tmp_whu, $u_opts, $query;
	$vars = ($send?"$send;":'').DRAW_REST.
		';keep_prefix='.DRAW_PERSISTENT_PREFIX
	.($u_opts['save2common']?'':
		';save_prefix='.DRAW_BACKUPCOPY_PREFIX.';saveprfx='.NAMEPRFX
	);
	foreach ($cfg_draw_vars as $k => $v) {
		if (($i = $GLOBALS['u_'.$v]) || ($i = get_const(strtoupper($v)))) $vars .= ";$k=$i";
	}
	if (($res = $query['draw_res']) && strpos($res, 'x')) $wh = explode('x', $res);
	if ($send) {
		foreach (array('DEFAULT_', 'LIMIT_') as $i => $j)
		foreach ($tmp_whu as $k => $l) {
			$p = $tmp_wh[$k].($i?'l':'');
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
	$f = date(DATE_ATOM, $uint = ($t ?: T0));
	$i = strpos($f, 'T');
	$d = substr($f, 0, $i);
	$t = substr($f, $i+1, 8);
	return '<time datetime="'.$f.'" data-t="'.$uint.'">'.$d.' <small>'.$t.'</small></time>';
}

function get_time_elapsed($t = 0) {
	$t = explode(' ', $t ?: microtime());
	return ($t[1]-T0) + ($t[0]-M0);
}

function get_time_seconds($t) {
	if (strrpos($t, ':') > 0) {
		$sec = 0;
		foreach (explode(':', $t, 3) as $n) $sec = $sec*60 + intval($n);
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

function format_filesize($B, $D = 2) {
	if ($F = floor((strlen($B) - 1) / 3)) $S = 'BkMGTPEZY';
	else return $B.' B';
	return sprintf("%.{$D}f", $B/pow(1024, $F)).' '.$S[$F].'B';
}

function optimize_pic($filepath) {
	if (
		function_exists('exec')
	&&	($f = $filepath)
	&&	($ext = get_file_ext($f))
	) {
		$bad_path = "$f.bad";
		$bak_path = "$f.bak";
		$d = DIRECTORY_SEPARATOR;
		$i = (function_exists('ini_get') && function_exists('ini_set'));
		$m = 'max_execution_time';
		global $cfg_optimize_pics;
		foreach ($cfg_optimize_pics as $format => $tool) if ($ext == $format)
		foreach ($tool as $arr) {
			list($program, $command) = $arr;
			if (is_file($p = $program) || is_file($p .= '.exe')) $p = "./$p";
			else continue;

			$return_code = 0;
			$output = array('');
			$cmd = sprintf($command, $p, $f);
			if ($d !== '/') $cmd = str_replace('/', $d, $cmd);
			if ($i) ini_set($m, ini_get($m) + 30);

			data_lock($lk = 'pic');
			$return = exec($cmd, $output, $return_code);
			data_unlock($lk);

			if (
				is_file($bak_path)
			&&	($bak_size = filesize($bak_path))
			) {
				$del = (
					($size = filesize($f))
					? (
						($rest = rename($f, $bad_path))
						? 'renamed to *.bad'
						: 'not renamed'
					) : (
						($rest = unlink($f))
						? 'deleted'
						: 'not deleted'
					)
				);
				if ($rest) $rest = ".\nRestoring from $bak_path = $bak_size bytes, ".(
					rename($bak_path, $f)
					? 'done'
					: 'failed'
				);
				$done = "failed, $f = $size bytes, $del$rest";
				if (!$return_code) $return_code = '0, fallback';
			} else $done = (
				$return_code
				? 'error'
				: 'done'
			);
			if ($return_code) {
				$o = trim(preg_replace('~\v+~u', NL, implode(NL, (array)$output)));
				if (strlen($o)) {
					if (false !== strpos($o, NL)) $o = NL.'['.indent($o).']';
				} else $o = 'empty';
				data_log_action("Optimization $done.
Command line: $cmd
Return code: $return_code
Return text: $return
Shell output: $o");
			} else return;
		}
	}
}

//* front end templates -------------------------------------------------------

function indent($t, $n = 0) {
	return (
		!strlen($t = trim($t))
	||	(!$n && false === strpos($t, NL))
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
		if ($min||$max) $name .= ' pattern="\s*(\S\s*){'.($min ?: 0).','.($max ?: '').'}"';
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
					($submit || $method)
					? NL.'<b><input type="submit" value="'.($submit ?: $GLOBALS['tmp_submit']).'"></b>'
					: ''
				)
			).'</b>'.$checkbox
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
					preg_replace_callback(
						'~\b\d+s\b~'
					,	'format_time_units'
					,	$t
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
		if (GOD && ($v = $GLOBALS[$k = 'fix_encoding_chosen'])) {
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
	global $lang, $tmp_announce, $tmp_post_err;
	if (!is_array($j = $page['js'] ?: array())) $j = array($j => 1);
	$R = !!$j['arch'];
	$RL = $page['link'] ?? '';
	$LN = $page['listing'] ?? '';
	$N = ROOTPRFX.NAMEPRFX;
	$static = ($LN || $R);
	$class = (($v = $page['body']) ? (array)$v : array());
	if ($page['anno']) foreach (data_global_announce('all') as $k => $v) {
		if (strlen($v)) {
			$v = ': '.(MOD ? "<span id=\"$k\">$v</span>" : $v);
			$c = '';
		} else $c = ' class="twilight"';
		$ano .= NL."<b$c>$tmp_announce[$k]$v</b>";
	}
	if (!$static) {
		$L = LINK_TIME;
		if ($a = $page['report']) {
			$e_class = array(
				'trd_arch' => 'gloom'
			,	'trd_miss' => 'warn'
			);
			if (!is_array($a)) $a = preg_split('~\W+~', $a);
			foreach ($a as $v) if ($v = trim($v)) {
				$e = $tmp_post_err[$v];
				$err .= NL.'<p class="anno '.($e_class[$v] ?: 'report').'"><b>'.indent($e ?: $v).'</b></p>';
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
<link rel="index" href="//'.$_SERVER['SERVER_NAME'].ROOTPRFX.'">':'')
.(($v = $page['head'])?NL.$v:'')
.(($v = $page['title'])?NL."<title>$v</title>":'');

	if ($ano) $header .= NL.'<p class="anno">'.indent($ano).'</p>';
	if ($err) $header .= NL.$err;
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
		$task = '<div id="task"'.$attr.'>'.indent($v).'</div>';
	} else
	if ($v = $$txt) $content .= get_template_content($v, $static, $txt);
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
.indent($content, 1).(($t =
 indent($footer, 1)
.indent($scripts, 1)) && ($k = get_const('TOOK'))?str_replace($k, round(get_time_elapsed(), 9), $t):$t)
.'</body>
</html>';
}

//* ---------------------------------------------------------------------------

$arch_list_href = ROOTPRFX.DIR_ARCH;
$room_list_href = ROOTPRFX.DIR_ROOM;
$tmp_wh = 'wh';
$tmp_whu = array('WIDTH','HEIGHT');
$tmp_room_new = '{'.$room_list_href.'new/|new}';
if ($s = get_const('ROOM_HIDE')) $tmp_room_new_hide = '{'.$room_list_href.($s .= 'test')."/|$s}";
if ($s = get_const('ROOM_DUMP')) $tmp_room_new_dump = '{'.$room_list_href.($s .= 'dump')."/|$s}";

?>
<?php

function exit_if_not_mod($t = 0) {
	$t = gmdate('r', $t ?: T0);
	$q = 'W/"'.md5(
		'To refresh page if broken since 2016-08-01 00:21'.NL.	//* <- change this to invalidate old pages cached in browsers
		'Or user key/options changed: '.$_REQUEST[ME]
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
function rewrite_htaccess($read_only = 0) {
	$start_mark = '# 8<-- start mark: '.NAMEPRFX.', version: ';
	$end_mark = '# 8<-- end mark: '.NAMEPRFX.', placed automatically: ';
	$new_mark = $start_mark.ROOTPRFX.' 2016-07-11 17:04';		//* <- change this to invalidate old version
	if (
		!($old = (is_file($f = '.htaccess') ? trim(file_get_contents($f)) : ''))
	||	false === strpos($old, $new_mark)
	||	false === strpos($old, $end_mark)
	) {
		if ($old) {
			$b_found = (false !== ($i = strpos($old, $start_mark)));
			$before = ($b_found ? trim(substr($old, 0, $i)) : '');

			$a_found = (false !== ($i = strpos($old, $end_mark)));
			$after = ($a_found && false !== ($i = strpos($old, NL, $i)) ? trim(substr($old, $i)) : '');

			if ($b_found || $a_found) $new = ($before?$before.NL.NL:'').$new.($after?NL.NL.$after:'');
			else $new .= NL.NL.$old;
		} else $old = 'none';
		$n = 'NO_CACHE';
		$e_cond = " env=$n";
		$e_set = "E=$n:1";
		$d = '('.implode('|', $GLOBALS['cfg_dir']).')(/([^/]+))?';
		$dd = get_const('DIR_DATA');
		$new = $new_mark.' -- Do not touch these marks. Only delete them along with the whole block.
<IfModule rewrite_module>
	RewriteEngine On
	RewriteBase '.ROOTPRFX.'
# variable fix:
	RewriteCond %{ENV:REDIRECT_'.$n.'} !^$
	RewriteRule .* - [E='.$n.':%{ENV:REDIRECT_'.$n.'}]
# virtual folders:'.($dd?'
	RewriteRule ^'.$dd.'.*$ . [L,R=301]':'').'
	RewriteRule ^('.DIR_PICS.')(([^/])[^/]+\.([^/])[^/]+)$ $1$4/$3/$2'.'
	RewriteRule ^'.$d.'$ $0/ [L,R=301]'.'
	RewriteRule ^'.$d.'(/[-\d]*)$ index.php?dir=$1&room=$3&etc=$4 [L,'.$e_set.']
# files not found:
	RewriteCond %{REQUEST_FILENAME} -f [OR]
	RewriteCond %{REQUEST_FILENAME} -d
	RewriteRule ^.? - [S=2]
	RewriteRule ^('.DIR_PICS.'|'.DIR_ARCH.'[^/]+/'.DIR_THUMB.').*$ err.png [L,'.$e_set.']
	RewriteRule ^('.$d.'/).+$ $1. [L,R,'.$e_set.']
</IfModule>
<IfModule headers_module>
	Header set Cache-Control "max-age=0; must-revalidate; no-cache"'.$e_cond.'
	Header set Expires "Wed, 27 Jan 2016 00:00:00 GMT"'.$e_cond.'
	Header set Pragma "no-cache"'.$e_cond.'
	Header unset Vary'.$e_cond.'
</IfModule>
'.$end_mark.date(TIMESTAMP, T0).' -- Manual changes inside will be lost on the next update.';
		$changed = ($new != $old);
	} else $new = 'no change';
	$report = "---- old version: ----

$old

---- new version: ----

$new";

//* rewrite htaccess if none/changed, when logged in and viewing root folder:
	if (!$read_only && $changed) {
		$saved = (
			file_put_contents($f, $new)
			? 'Succesfully updated'
			: 'Failed to update'
		);
		data_log_adm($report = "$saved $f

$report");
	}
	return $report;
}

function str_replace_first($f, $to, $s) {return false === ($pos = strpos($s, $f)) ? $s : substr_replace($s, $to, $pos, strlen($f));}
function abbr($a, $sep = '_') {foreach ((is_array($a)?$a:explode($sep, $a)) as $word) $r .= $word[0]; return $r;}
function trim_post($p, $len = 456) {
	$s = trim(preg_replace('~\s+~us', ' ', $p));
	if ($len > 0) $s = mb_substr($s, 0, $len, ENC);
	return POST ? htmlspecialchars($s) : $s;
}

function trim_room($r) {
	return strtolower(mb_substr(preg_replace('/\.+/', '.', preg_replace(
'/[^\w\x{0400}-\x{04ff}\x{2460}-\x{2468}\x{2605}-\x{2606}.!-]+/u', '_', trim(trim($r), '\\/')	//* <- add more unicode alphabets to complement \w?
	)), 0, ROOM_NAME_MAX_LENGTH, ENC));
}

function is_tag_attr($t) {return strpos($t, '<') === strpos($t, '>');}	//* <- if only both === false
function is_not_dot($path) {return !!trim($path, './\\');}
function is_not_hidden($room) {
	global $u_flag;
	return (
		GOD
	||	$u_flag['mod']
	||	$u_flag['mod_'.$room]
	||	$room[0] != get_const('ROOM_HIDE')
	);
}

function get_dir_contents($path, $num_sort = 0, $hiding = 0) {
	$a = (is_dir($path) ? array_filter(scandir($path), 'is_not_dot') : array());
	if ($a && $hiding) $a = array_filter($a, 'is_not_hidden');
	if ($a && $num_sort) natcasesort($a);
	return $a;
}

function get_file_lines($path) {return is_file($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : array();}
function get_file_name($path, $full = 1, $delim = '/') {return false === ($rr = strrpos($path, $delim)) ? ($full?$path:'') : substr($path, $rr+1);}
function get_file_ext($path, $full = 0) {return strtolower(get_file_name($path, $full, '.'));}
function get_room_skip_name($r) {return array(ME.'-skip-'.md5($r = rawurlencode($r)), $r);}
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
	global $cfg_date_class;
	if (!$t_first) $t_first = T0;
	if (!$t_last) $t_last = $t_first;
	$classes = array();
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
				$classes[] = $a[0];
				break;
			}
		}
	}
	return $classes;
}

function get_draw_app_list() {
	global $cfg_draw_app, $tmp_draw_app, $tmp_options_input, $u_draw_app, $query;
	if (!(($n = $query[$da = 'draw_app']) || ($n = $u_draw_app)) || !in_array($n, $cfg_draw_app)) $n = $cfg_draw_app[0];
	$a = $tmp_options_input['input'][$da];
	foreach ($cfg_draw_app as $k => $v) $a .= ($k?',':':').NL.(
		$n == $v
		? $tmp_draw_app[$k]
		: '<a href="?'.$da.'='.$v.'">'.$tmp_draw_app[$k].'</a>'
	);
	$f = $n;
	if (false !== ($s = strrpos($n, '.'))) $n = substr($n, 0, $s); else $f .= DRAW_DEFAULT_APP_EXT;	//* <- fix to fit <script src=$a>
	if (false !== ($s = strrpos($n, '/'))) $n = substr($n, $s+1);
	return array('name' => $n, 'src' => ROOTPRFX.$f.(LINK_TIME?'?'.filemtime($f):''), 'list' => $a);
}

function get_draw_vars($v = '') {
	global $cfg_draw_vars, $tmp_wh, $tmp_whu, $u_draw_max_undo, $u_opts, $query;
	$vars = ($v?"$v;":'').DRAW_REST.
		';keep_prefix='.DRAW_PERSISTENT_PREFIX
	.($u_opts['save2common']?'':
		';save_prefix='.DRAW_BACKUPCOPY_PREFIX.';saveprfx='.NAMEPRFX
	);
	foreach ($cfg_draw_vars as $k => $v) {
		if (($i = ${'u_'.$v}) || ($i = get_const(strtoupper($v)))) $vars .= ";$k=$i";
	}
	if (($res = $query['draw_res']) && strpos($res, 'x')) $wh = explode('x', $res);
	foreach (array('DEFAULT_', 'LIMIT_') as $i => $j)
	foreach ($tmp_whu as $k => $l) {
		$p = $tmp_wh[$k].($i?'l':'');
		if ((!$i && $wh && ($v = $wh[$k])) || ($v = get_const("DRAW_$j$l"))) $vars .= ";$p=$v";
	}
	return csv2nl($vars);
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
	&&	($e = get_file_ext($f))
	) {
		global $cfg_optimize_pics;
		foreach ($cfg_optimize_pics as $format => $tool) if ($e == $format)
		foreach ($tool as $program => $command) {
			if (is_file($p = $program) || is_file($p .= '.exe')) $p = "./$p";
			else continue;

			$e = vsprintf($command, array($p, $f));
			$output = array('');
			data_lock('/pic');
			exec(DIRECTORY_SEPARATOR == '/' ? $e : str_replace('/', DIRECTORY_SEPARATOR, $e), $output, $return);
			data_unlock('/pic');
			if (
				is_file($f .= '.bak')
			&&	filesize($f)
			) {
				$del = (
					($size = filesize($filepath))
					? (
						($rest = rename($filepath, $filepath.'.bad'))
						? 'renamed to *.bad'
						: 'not renamed'
					) : (
						($rest = unlink($filepath))
						? 'deleted'
						: 'not deleted'
					)
				);
				if ($rest) $rest = ".\nRestoring from $f ".(
					rename($f, $filepath)
					? 'done'
					: 'failed'
				);
				data_log_adm("Optimizing $filepath failed, $size bytes, $del$rest.");
				if (!$return) $return = 'fallback';
			}
			if ($return) {
				if (!strlen($o = trim(is_array($output) ? implode(NL, $output) : $output))) $o = 'empty';
				data_log_adm("Command line: $e\nReturn code: $return\nShell output: $o");
			}
			return;
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
		str_split('{`}[|]\\')
	,	array('<a href="', '" onClick="', '</a>', '<span class="', '">', '</span>', NL)
	,	nl2br(htmlspecialchars(preg_replace_callback(
			'~\b\d+s\b~'
		,	'format_time_units'
		,	$t
		)), false)
	);
}

function get_template_content($p, $static = 0, $tag = '', $attr = '') {
	global $tmp_require_js, $tmp_result;
	if (is_array($p)) {
		$a = $p;
		$p = '';
		foreach ($a as $k => $v) if (!$k) $p = $v; else if ($v) $attr .= " $k=\"$v\"";
	}
	if ($p) {
		$t = $tag ?: 'pre';
		$p = "
<$t$attr>$p
</$t>".'
<noscript><p class="hint report">'.($static?'JavaScript support required.':$tmp_require_js).'</p></noscript>';
		return '<div class="'.($static?'thread':'content" id="content').'">'.indent($p).'</div>';
	}
	return '';
}

function get_template_page($t, $NOS = 0) {
	global $tmp_announce, $tmp_post_err;
	$N = ROOTPRFX.NAMEPRFX;
	if (!is_array($j = $t['js'])) $j = ($j ? array($j => 1) : array());
	$R = !!$j['arch'];
	$static = ($NOS || $R);
	$class = (($v = $t['body']) ? (array)$v : array());
	if ($t['anno']) foreach (data_global_announce() as $k => $v) {
		if (MOD) $v = "<span id=\"$k\">$v</span>";
		$ano .= NL."<b>$tmp_announce[$k]: $v</b>";
	}
	if (!$static) {
		$L = LINK_TIME;
		if ($a = $t['report']) {
			if (!is_array($a)) $a = preg_split('~\W+~', $a);
			foreach ($a as $v) if ($v = trim($v)) {
				$e = $tmp_post_err[$v];
				$err .= NL.'<p class="anno '.($v == 'trd_arch'?'gloom':'report').'"><b>'.indent($e ?: $v).'</b></p>';
			}
		}
		if (FROZEN_HELL) $class[] = 'frozen-hell';
		if ($d = get_date_class()) $class = array_merge($class, $d);
	}
	if ($a = $t[$k = 'content']) {
		$attr = get_template_attr($t['data'][$k]);
		if ($NOS) {
			foreach ((array)$a as $k => $v) $content .= ($content?NL:'').NL.$k.$NOS.$v;
			$content = "
<pre$attr>$content
</pre>";
		} else $content = get_template_content($a, $static, '', $attr);
	}

	$head = '<meta charset="'.ENC.'">'.($NOS?'':'
<meta name="viewport" content="width=690">
<link rel="stylesheet" type="text/css" href="'.$N.($v = '.css').($L?'?'.filemtime(NAMEPRFX.$v):'').'">').'
<link rel="shortcut icon" type="image/png" href="'.(($v = $t['icon'])?ROOTPRFX.$v:$N).'.png">'
.($R || $_REQUEST[ME]?'
<link rel="index" href="http://'.$_SERVER['SERVER_NAME'].ROOTPRFX.'">':'')
.(($v = $t['head'])?NL.$v:'')
.(($v = $t['title'])?NL."<title>$v</title>":'');

	if ($ano) $header .= NL.'<p class="anno">'.indent($ano).'</p>';
	if ($err) $header .= NL.$err;
	if ($a = $t['header']) {
		if (is_array($a)) {
			foreach ($a as $k => &$v) if ($v) $v = ($k?'<u class="'.$k.'">':'<u>').indent($v).'</u>';
			$a = implode(NL, $a);
		}
		$header .= NL.'<p>'.indent($a).'</p>';
	}
	if ($header) $header = '<header id="header">'.indent($header).'</header>';

	if ($v = $t[$txt = 'textarea']) $$txt = htmlspecialchars($v);
	if ($v = $t[$k = 'task']) {
		$attr = get_template_attr($t['data'][$k]);
		if ($sub = $t['subtask']) $v = '<div class="task">'.indent($v).'</div>'.$sub;
		else if (!$static) $attr = ' class="task"'.$attr;
		if ($sub = $$txt) $v .= "
<$txt>$sub
</$txt>";
		$task = '<div id="task"'.$attr.'>'.indent($v).'</div>';
	} else
	if ($v = $$txt) $content .= get_template_content($v, $static, $txt);
	if ($v = $t['footer']) $footer = '<footer>'.indent($v).'</footer>';

	if ($j) foreach ($j as $k => $v) $scripts .= '
<script src="'.$N.($v = ($k?".$k":'').'.js').($L?'?'.filemtime(NAMEPRFX.$v):'').'"></script>';

	return '<!doctype html>
<html lang="'.($t['lang'] ?: 'en').'">
<head>'
.indent($head)
.'</head>
<body'.($class?' class="'.implode(' ', $class).'"':'').'>'
.indent($header, 1)
.indent($task, 1)
.indent($content, 1).(($t =
 indent($footer, 1)
.indent($scripts, 1)) && ($k = get_const('TOOK'))?str_replace($k, get_time_elapsed(), $t):$t)
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
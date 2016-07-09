<?php

function exit_if_not_mod($t = 0) {
	$t = gmdate('r', $t?$t:T0);
	$q = 'W/"'.md5(
		'To refresh page if broken since 2016-07-05 00:42:55'.NL.
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
function str_replace_first($f, $to, $s) {return false === ($pos = strpos($s, $f)) ? $s : substr_replace($s, $to, $pos, strlen($f));}
function abbr($a, $sep = '_') {foreach ((is_array($a)?$a:explode($sep, $a)) as $word) $r .= $word[0]; return $r;}
function trim_post($p, $len = 456) {return htmlspecialchars(mb_substr(stripslashes(trim(preg_replace('~\s+~us', ' ', $p))),0,$len,ENC));}
function trim_room($r) {
	return strtolower(mb_substr(preg_replace('/\.+/', '.', preg_replace(
'/[^\w\x{0400}-\x{04ff}\x{2460}-\x{2468}\x{2605}-\x{2606}.!-]+/u', '_', trim(trim($r), '\\/')	//* <- add more unicode alphabets to complement \w?
	)), 0, ROOM_NAME_MAX_LENGTH, ENC));
}

function csv2nl($v, $c = ';', $n = 1) {
	$d = "\s*[$c]+\s*";
	$n = NL.($n > 0?str_repeat("\t", $n):'');
	return $n.implode($c.$n, preg_split("~$d~u", preg_replace("~^$d|$d$~u", '', $v).$c));
}

function get_req() {return GET_Q ? explode('=', urldecode(end(explode('?', $_SERVER['REQUEST_URI'], 2))), 2) : array();}
function get_file_name($path, $full = 1, $delim = '/') {return false === ($rr = strrpos($path, $delim)) ? ($full?$path:'') : substr($path, $rr+1);}
function get_file_ext($path, $full = 0) {return strtolower(get_file_name($path, $full, '.'));}
function get_room_skip_name($r) {return array(ME.'-skip-'.md5($r = rawurlencode($r)), $r);}
function get_room_skip_list($k = '') {
	return ($v = $_COOKIE[$k?$k:reset(get_room_skip_name($GLOBALS['room']))])
	?	array_slice(explode('/', $v, TRD_MAX_SKIP_PER_ROOM+1), 1, TRD_MAX_SKIP_PER_ROOM-($k?1:0))
	:	array();
}

function fln($f) {return file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);}
function get_dir_top_file_id($d) {
	$i = 0;
	if (is_dir($d)) foreach (scandir($d) as $f) if (preg_match('~^\d+~', $f, $m) && $i < ($n = intval($m[0]))) $i = $n;
	return $i;
}

function get_dir_top_filemtime($d) {
	$t = 0;
	if (is_dir($d)) foreach (scandir($d) as $f) if (trim($f, '.') && $t < ($mt = filemtime("$d/$f"))) $t = $mt;
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
		$due1 = (count($a) > 2 && $a[2]?$a[2]:false);
		$due2 = (count($a) > 3 && $a[3]?$a[3]:false);
		$flag = (count($a) > 4 && $a[4]?$a[4]:2);
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
	global $cfg_draw_app, $tmp_draw_app, $tmp_options_input, $u_draw_app;
	list($n, $res) = get_req();
	if (!$n || $n == '!') $n = $u_draw_app;
	if (!in_array($n, $cfg_draw_app)) $n = $cfg_draw_app[0];
	$a = $tmp_options_input['input']['draw_app'];
	foreach ($cfg_draw_app as $k => $v) $a .= ($k?',':':').NL.($n == $v
		? $tmp_draw_app[$k]
		: '<a href="?'.$v.'">'.$tmp_draw_app[$k].'</a>'
	);
	$f = $n;
	if (false !== ($s = strrpos($n, '.'))) $n = substr($n, 0, $s); else $f .= DRAW_DEFAULT_APP_EXT;	//* <- fix to fit <script src=$a>
	if (false !== ($s = strrpos($n, '/'))) $n = substr($n, $s+1);
	return array('name' => $n, 'src' => ROOTPRFX.$f.(LINK_TIME?'?'.filemtime($f):''), 'list' => $a);
}

function get_draw_vars($v = '') {
	global $cfg_draw_vars, $tmp_wh, $tmp_whu, $u_draw_max_undo, $u_opts;
	$vars = ($v?"$v;":'').DRAW_REST.
		';keep_prefix='.DRAW_PERSISTENT_PREFIX
	.($u_opts['save2common']?'':
		';save_prefix='.DRAW_BACKUPCOPY_PREFIX.';saveprfx='.NAMEPRFX
	);
	foreach ($cfg_draw_vars as $k => $v) {
		if (($i = ${'u_'.$v}) || ($i = get_const(strtoupper($v)))) $vars .= ";$k=$i";
	}
	list($n, $res) = get_req();
	if ($n && $n != '!' && $res && strpos($res, 'x')) $wh = explode('x', $res);
	foreach (array('DEFAULT_', 'LIMIT_') as $i => $j)
	foreach ($tmp_whu as $k => $l) {
		$p = $tmp_wh[$k].($i?'l':'');
		if ((!$i && $wh && ($v = $wh[$k])) || ($v = get_const("DRAW_$j$l"))) $vars .= ";$p=$v";
	}
	return csv2nl($vars);
}

function get_time_html($t = 0) {
	$f = date(DATE_ATOM, $uint = ($t?$t:T0));
	$i = strpos($f, 'T');
	$d = substr($f, 0, $i);
	$t = substr($f, $i+1, 8);
	return '<time datetime="'.$f.'" data-t="'.$uint.'">'.$d.NL.'<small>'.$t.'</small></time>';
}

function get_time_elapsed($t = 0) {
	$t = explode(' ', $t?$t:microtime());
	return ($t[1]-T0) + ($t[0]-M0);
}

function format_time_units($t) {
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
	if (function_exists('exec') && ($f = $filepath) && ($e = get_file_ext($f))) {
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
			if (is_file($f .= '.bak') && filesize($f)) {
				data_log_adm("Optimizing $filepath failed, restoring from $f");
				if (filesize($filepath) ? rename($filepath, $filepath.'.bad') : unlink($filepath)) rename($f, $filepath);
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
	return !strlen($t = trim($t)) || (!$n && false === strpos($t, NL))
	?	$t
	:	preg_replace(
			'~(?:^|\v+)(?:(\h*<(pre|textarea)\b.+?)(?=\v+\h*</\2>))?~uis'
		,	NL.str_repeat("\t", $n > 0?$n:1).'$1'
		,	$t
		).NL;
}

function get_template_form($a, $min = 0, $max = 0, $area = 0, $check = 0) {
	if (is_array($a)) {
		list($name, $head, $hint, $butn, $plhd) = $a;
		$a = 0;
	} else $name = $a;
	if ($name) {
		foreach (array(
			'?' => ''
		,	';' => 0
		,	0 => ' method="post"'
		) as $k => $v) if (!$k || $name[0] == $k) {
			$method = $v;
			if ($k) $name = substr($name, 1);
			break;
		}
		if ($a) foreach (array(
			'head' => ''
		,	'hint' => '_hint'
		,	'plhd' => '_placeholder'
		,	'butn' => '_submit'
		) as $k => $v) $$k = $GLOBALS["tmp_$name$v"];
		if ($name) $name = ' name="'.$name.'"';
	}
	if ($min||$max) $name .= ' pattern="\s*(\S\s*){'.($min?$min:0).','.($max?$max:'').'}"';
	if ($name && $plhd) $name .= ' placeholder="'.$plhd.'"';
	$name .= ($GLOBALS['u_opts']['focus']?'':' autofocus').' required';
	return ($head?'
<p>'.$head.':</p>'
:'').(($name !== false)?(($name === 0 || $method === 0)?'
<p><b><input type="text'.($butn?'" id="'.$butn:'').'"'.$name.'></b></p>'
:'
<form'.$method.'>
	<b>
		<b><'.($area?'textarea'.$name.'></textarea':'input type="text"'.$name).'></b>
		<b><input type="submit" value="'.($butn?$butn:$GLOBALS['tmp_submit']).'"></b>
	</b>'.($check?'
	<label class="r">
		'.$check.':
		<input type="checkbox" name="check">
	</label>':'').'
</form>'
):'').($hint?'
<p class="hint'.(is_array($hint)?' r">'.implode(',', $hint):'">'.indent(get_template_hint($hint))).'</p>'
:'');
}

function get_template_hint($t) {
	return str_replace(
		str_split('{|}[]\\')
	,	array('<a href="', '">', '</a>', '<span class="', '</span>', NL)
	,	nl2br(htmlspecialchars(preg_replace_callback(
			'~\b(\d+)s\b~'
		,	function ($match) {return format_time_units($match[1]);}
		,	$t
		)))
	);
}

function get_template_pre($p, $static = 0, $t = 'pre') {
	global $tmp_require_js, $tmp_result;
	if (is_array($a = $p)) {
		foreach ($a as $k => $v) if (!$k) $p = $v; else if ($v) $attr .= " $k=\"$v\"";
	}
	if ($p) {
		$p = "
<$t$attr>$p
</$t>";
		if ($t = ($t == 'pre'?'':' task')) $p = "
<p>$tmp_result</p>$p";
		else $p .= '
<noscript><p class="hint report">'.($static?'JavaScript support required.':$tmp_require_js).'</p></noscript>';
		return '<div class="thread'.$t.'">'.indent($p).'</div>';
	} else return '';
}

function get_template_page($t, $NOS = 0) {
	global $tmp_announce, $tmp_post_err;
	$N = ROOTPRFX.NAMEPRFX;
	$j = $t['js'];
	$static = ($NOS || $j === 'arch');
	$class = (($v = $t['body']) ? (is_array($v)?$v:array($v)) : array());
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
				$err .= NL.'<p class="anno '.($v == 'trd_arch'?'gloom':'report').'">'.indent($e?$e:$v).'</p>';
			}
		}
		if (FROZEN_HELL) $class[] = 'frozen-hell';
		if ($d = get_date_class()) $class = array_merge($class, $d);
	}
	if (is_array($a = $t['content'])) {
		if ($NOS) {
			foreach ($a as $k => $v) $pre .= ($pre?NL:'').NL.$k.$NOS.$v;
			$pre = '<pre>'.$pre.NL.'</pre>';
		} else foreach ($a as $v) $pre .= get_template_pre($v, $static);
	} else $pre = get_template_pre($a, $static);

	$head = '<meta charset="'.ENC.'">'.($NOS?'':'
<meta name="viewport" content="width=690">
<link rel="stylesheet" type="text/css" href="'.$N.'.css'.($L?'?'.filemtime(NAMEPRFX.'.css'):'').'">').'
<link rel="shortcut icon" type="image/png" href="'.($t['icon']?ROOTPRFX.$t['icon']:$N).'.png">'
.($t['head']?NL.$t['head']:'')
.($t['title']?NL.'<title>'.$t['title'].'</title>':'');

	if ($ano) $header .= NL.'<p class="anno">'.indent($ano).'</p>';
	if ($err) $header .= NL.$err;
	if ($v = $t['header']) $header .= NL.'<div>'.indent($v).'</div>';
	if ($header) $header = '<header id="header">'.indent($header).'</header>';

	if ($v = $t['task']) {
		if ($sub = $t['subtask']) $v = '<div class="task">'.indent($v).'</div>'.$sub;
		else if (!$static) $data = ' class="task"';
		if (is_array($a = $t['data'])) foreach ($a as $k => $d) $data .= ' data-'.$k.'="'.$d.'"';
		if ($sub = $t[$k = 'textarea']) $v .= "
<$k>$sub
</$k>";
		$task = '<div id="task"'.$data.'>'.indent($v).'</div>';
	} else
	if ($v = $t[$k = 'textarea']) $pre .= get_template_pre($v, $static, $k);
	if ($v = $t['footer']) $footer = '<footer>'.indent($v).'</footer>';

	if (is_array($a = $j) || ($j && ($a = array(".$j" => 0)))) foreach ($a as $k => $v) $scripts .= '
<script src="'.$N.($v = ($v?'':$k).'.js').($L?'?'.filemtime(NAMEPRFX.$v):'').'"></script>';

	return '<!doctype html>
<html lang="'.($t['lang']?$t['lang']:'en').'">
<head>'
.indent($head)
.'</head>
<body'.($class?' class="'.implode(' ', $class).'"':'').'>'
.indent($header, 1)
.indent($task, 1)
.indent($pre, 1).(($t =
 indent($footer, 1)
.indent($scripts, 1)) && ($k = get_const('TOOK'))?str_replace($k, get_time_elapsed(), $t):$t)
.'</body>
</html>';
}

$tmp_wh = 'wh';
$tmp_whu = array('WIDTH','HEIGHT');
$tmp_room_new = '{'.($cfg_room = ROOTPRFX.DIR_ROOM).'new/|new}';
if ($s = get_const('ROOM_HIDE')) $tmp_room_new_hide = '{'.$cfg_room.($s .= 'test')."/|$s}";
//if ($s = get_const('ROOM_DUMP')) $tmp_room_new_dump = '{'.$cfg_room.($s .= 'dump')."/|$s}";

?>
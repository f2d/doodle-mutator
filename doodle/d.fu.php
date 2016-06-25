<?php

function str_replace_first($f, $to, $s) {return (false !== ($pos = strpos($s, $f)) ? substr_replace($s, $to, $pos, strlen($f)) : $s);}
function indent($t, $n = 1) {return preg_replace('~\v+~u', NL.str_repeat("\t", $n), $t);}
function csv2nl($v, $d = ';', $n = 3) {return ($n = NL.($n?str_repeat("\t", $n):'')).str_replace($d, $d.$n, trim($v, $d).$d);}
function abbr($a, $sep = '_') {foreach ((is_array($a)?$a:explode($sep, $a)) as $word) $r .= $word[0]; return $r;}
function fln($f) {return file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);}
function get_req() {return GET_Q ? explode('=', rawurldecode(end(explode('?', $_SERVER['REQUEST_URI'], 2))), 2) : array();}
function trim_post($p, $len = 456) {return htmlspecialchars(mb_substr(stripslashes(trim(preg_replace('~\s+~us', ' ', $p))),0,$len,ENC));}
function trim_room($r) {
	return strtolower(mb_substr(preg_replace('/\.+/', '.', preg_replace(
'/[^\w\x{0400}-\x{04ff}\x{2460}-\x{2468}\x{2605}-\x{2606}.!-]+/u', '_', trim(trim($r), '\\/')	//* <- add more unicode alphabets to complement \w?
	)), 0, ROOM_NAME_MAX_LENGTH, ENC));
}
function get_file_name($path, $full = 1, $delim = '/') {return false === ($rr = strrpos($path, $delim)) ? ($full?$path:'') : substr($path, $rr+1);}
function get_file_ext($path, $full = 0) {return strtolower(get_file_name($path, $full, '.'));}
function get_room_skip_name($r) {return array(ME.'-skip-'.md5($r = rawurlencode($r)), $r);}
function get_room_skip_list($k = '') {
	return ($v = $_COOKIE[$k?$k:reset(get_room_skip_name($GLOBALS['room']))])
	?	array_slice(explode('/', $v, TRD_MAX_SKIP_PER_ROOM+1), 1, TRD_MAX_SKIP_PER_ROOM-($k?1:0))
	:	array();
}
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
function get_draw_app_list($n) {
	global $cfg_draw_app, $tmp_draw_app, $tmp_options_input;
	if (!in_array($n, $cfg_draw_app)) $n = $cfg_draw_app[0];
	$a = $tmp_options_input['input']['draw_app'];
	foreach ($cfg_draw_app as $k => $v) $a .= ($k?', ':': ').($n == $v
		? $tmp_draw_app[$k]
		: '<a href="?'.$v.'">'.$tmp_draw_app[$k].'</a>'
	);
	$f = $n;
	if (false !== ($s = strrpos($n, '.'))) $n = substr($n, 0, $s); else $f .= DRAW_DEFAULT_APP_EXT;	//* <- fix to fit <script src=$a>
	if (false !== ($s = strrpos($n, '/'))) $n = substr($n, $s+1);
	return array('name' => $n, 'src' => ROOTPRFX.$f.(LINK_TIME?'?'.filemtime($f):''), 'list' => $a);
}
function get_draw_vars() {
	global $cfg_draw_vars, $tmp_wh, $tmp_whu, $u_draw_app, $u_draw_max_undo, $u_opts;
	$vars = DRAW_REST.
		';keep_prefix='.DRAW_PERSISTENT_PREFIX
	.($u_opts['save2common']?'':
		';save_prefix='.DRAW_BACKUPCOPY_PREFIX.';saveprfx='.NAMEPRFX
	);
	if (($r = get_req()) && $r[0] && $r[0] != '!') {
		$u_draw_app = $r[0];
		if (strpos($r[1], 'x')) $wh = explode('x', $r[1]);
	}
	foreach ($cfg_draw_vars as $k => $v) {
		if (($i = ${'u_'.$v}) || (defined($i = strtoupper($v)) && ($i = constant($i)))) $vars .= ";$k=$i";
	}
	foreach (array('DEFAULT_', 'LIMIT_') as $i => $j)
	foreach ($tmp_whu as $k => $l) {
		$p = $tmp_wh[$k].($i?'l':'');
		if ((!$i && $wh && ($v = $wh[$k])) || (defined($v = "DRAW_$j$l") && ($v = constant($v)))) $vars .= ";$p=$v";
	}
	return $vars;
}
function format_filesize($B, $D = 2) {
	if ($F = floor((strlen($B) - 1) / 3)) $S = 'BkMGTPEZY';
	else return $B.' B';
	return sprintf("%.{$D}f", $B/pow(1024, $F)).' '.$S[$F].'B';
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
function exit_if_not_mod($t) {
	if (!$GLOBALS['u_opts']['modtime304'] && isset($_SERVER[$h = 'HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER[$h]) == $t) {
		header('HTTP/1.0 304 Not Modified');
		exit;
	}
	header('Last-Modified: '.gmdate('r', $t?$t:T0));
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

//* front end template --------------------------------------------------------

$tmp_wh = 'wh';
$tmp_whu = array('WIDTH','HEIGHT');
$tmp_room_new = '{'.($cfg_room = ROOTPRFX.DIR_ROOM).'new/|new}';
if (constant('ROOM_HIDE') && ROOM_HIDE) $tmp_room_new_hide = '{'.$cfg_room.($s = ROOM_HIDE.'test')."/|$s}";
//if (constant('ROOM_DUMP') && ROOM_DUMP) $tmp_room_new_dump = '{'.$cfg_room.($s = ROOM_DUMP.'dump')."/|$s}";

function get_template_form($a, $min = 0, $max = 0, $area = 0) {
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
		<p>'.$head.'</p>'
:'').(($name !== false)?(($name === 0 || $method === 0)?'
		<p><b><input type="text'.($butn?'" id="'.$butn:'').'"'.$name.'></b></p>'
:'
		<form'.$method.'>
			<b>
			<b><'.($area?'textarea'.$name.'></textarea':'input type="text"'.$name).'></b>
			<b><input type="submit" value="'.($butn?$butn:$GLOBALS['tmp_submit']).'"></b>
			</b>
		</form>'
):'').($hint?'
		<p class="hint'.(is_array($hint)?' r">'.implode(',', $hint):'">'.get_template_hint($hint)).'</p>'
:'');
}

function get_template_hint($t) {
	return str_replace(
		str_split('{|}[]\\')
	,	array('<a href="', '">', '</a>', '<span class="', '</span>', NL)
	, nl2br(htmlspecialchars(preg_replace_callback('~\b(\d+)s\b~', function ($match) {
		return format_time_units($match[1]);
	}, $t))));
}

function get_template_pre($p, $R = 0) {
	if (is_array($a = $p)) {
		foreach ($a as $k => $v) if (!$k) $p = $v; else if ($v) $attr .= " $k=\"$v\"";
	}
	return ($p?'
	<div class="thread">
		<pre'.$attr.'>'.$p.'
		</pre>
		<noscript><p class="hint report">'.($R?'JavaScript support required.':$GLOBALS['tmp_require_js']).'</p></noscript>
	</div>
':'');
}

function get_template_page($t) {
	global $tmp_announce, $tmp_post_err;
	$j = $t['js'];
	$R = ($j === 'arch');
	$n = ROOTPRFX.NAMEPRFX;
	$class = (($v = $t['body']) ? (is_array($v)?$v:array($v)) : array());
	if (!$R) {
		$L = LINK_TIME;
		foreach (data_global_announce() as $k => $v) $ano .= ($ano?NL.'<br>':'').NL."$tmp_announce[$k]: $v";
		if ($a = $t['report']) {
			if (!is_array($a)) $a = preg_split('~\W+~', $a);
			foreach ($a as $v) if ($v = trim($v)) {
				$err .= NL.
				'<p class="anno '.($v == 'trd_arch'?'gloom':'report').'">'.
					(($e = $tmp_post_err[$v])?$e:$v).
				'</p>';
			}
		}
		if (FROZEN_HELL) $class[] = 'frozen-hell';
		if ($d = get_date_class()) $class = array_merge($class, $d);
	}
	if (is_array($a = $t['data'])) foreach ($a as $k => $v) $data .= ' data-'.$k.'="'.$v.'"';
	if (is_array($a = $t['content'])) foreach ($a as $v) $pre .= get_template_pre($v, $R); else $pre = get_template_pre($a, $R);
	if (is_array($a = $j) || ($j && ($a = array(".$j" => 0)))) foreach ($a as $k => $v) $scr .= '
	<script src="'.$n.($v = ($v?'':$k).'.js').($L?'?'.filemtime(NAMEPRFX.$v):'').'"></script>';

	return '<!doctype html>
<html lang="'.($t['lang']?$t['lang']:'en').'">
<head>
	<meta charset="'.ENC.'">
	<meta name="viewport" content="width=690">
	<link rel="shortcut icon" type="image/png" href="'.($t['icon']?ROOTPRFX.$t['icon']:$n).'.png">
	<link rel="stylesheet" type="text/css" href="'.$n.'.css'.($L?'?'.filemtime(NAMEPRFX.'.css'):'').'">'.($t['head']?'
	'.indent($t['head']):'').($t['title']?'
	<title>'.$t['title'].'</title>':'').'
</head>
<body'.($class?' class="'.implode(' ', $class).'"':'').'>'.(($v = $t['header']) || $ano || $err?'
	<header id="header">'.($ano?'
		<p class="anno">'.indent($ano, 3).'
		</p>':'').($err?indent($err, 2):'').($v?'
		<div>'.indent($v, 3).'
		</div>':'').'
	</header>
':'').($t['task']?'
	<div id="task"'.$data.($t['subtask']
		?'>
		<div class="task">'.indent($t['task']).'
		</div>'.$t['subtask']
		:($R?'>':' class="task">').$t['task']).'
	</div>
':'').$pre.($t['footer']?'
	<footer>'.indent($t['footer'], 2).'
	</footer>
':'').$scr.'
</body>
</html>';
}

?>
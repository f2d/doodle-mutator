<?php

function str_replace_first($f, $to, $s) {return (false !== ($pos = strpos($s, $f)) ? substr_replace($s, $to, $pos, strlen($f)) : $s);}
function abbr($a, $sep = '_') {foreach ((is_array($a)?$a:explode($sep, $a)) as $word) $r .= $word[0]; return $r;}
function fln($f) {return file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);}
function get_req() {return GET_Q ? explode('=', end(explode('?', $_SERVER['REQUEST_URI'])), 2) : array();}
function trim_post($p, $len = 456) {return htmlspecialchars(mb_substr(stripslashes(trim(preg_replace('~\s+~us', ' ', $p))),0,$len,ENC));}
function trim_room($r) {
	return strtolower(mb_substr(preg_replace('/\.+/', '.', preg_replace(
'/[^\w\x{0400}-\x{04ff}\x{2460}-\x{2468}\x{2605}-\x{2606}.!-]+/u', '_', trim(trim($r), '\\/')	//* <- add more unicode alphabets to complement \w?
	)), 0, ROOM_NAME_MAX_LENGTH, ENC));
}
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
function pic_normal_path($p) {return preg_replace('~(^|[\\/])([^._-]+)[^._-]*(\.[^.,;]+)([;,].*$)?~', '$2$3', $p);}
function pic_resized_path($p) {return substr_replace($p, '_res', -4, 0);}
function pic_subpath($f, $mk = 0) {
	$f = pic_normal_path($f);
	$n = DIR_PICS.$f[strrpos($f, '.')+1].'/'.$f[0].'/';
	if ($mk && !is_dir($n)) mkdir($n, 0755, true);
	return $n.($mk === ''?'':$f);
}
function get_date_class($first = 0, $last = 0) {
	$first = intval(date('nd', $first?$first:T0));
	$last = ($last ? intval(date('nd', $last)) : $first);
	if (/*$first > 1230 || $first < 110 || */$last > 1230 || $last < 110) return 'new-year';
}
function get_draw_app_list($n) {
	global $cfg_draw_app, $tmp_draw_app, $tmp_options_field;
	if (!in_array($n, $cfg_draw_app)) $n = $cfg_draw_app[0];
	$a = $tmp_options_field['draw_app'];
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
	global $tmp_wh, $tmp_whu, $u_draw_app, $u_draw_max_undo, $u_opts;
	$vars = DRAW_REST;
	if (!$u_opts['save2common']) $vars .= ';saveprfx='.NAMEPRFX;
	if ($u_draw_max_undo) $vars .= ';undo='.$u_draw_max_undo;
	$r = get_req();
	if ($r[0] && $r[0] != '!') {
		$u_draw_app = $r[0];
		if (strpos($r[1], 'x')) $wh = explode('x', $r[1]);
	}
	foreach (array('DEFAULT_', 'LIMIT_') as $i => $j)
	foreach ($tmp_whu as $k => $l) {
		$p = $tmp_wh[$k].($i?'l':'');
		if ((!$i && $wh && ($v = $wh[$k]))
		|| (defined($v = "DRAW_$j$l") && ($v = constant($v)))
		) $vars .= ";$p=$v";
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
		$i = 1;
		if ($t < 11 || $t >= 20) {
			$s = $t%10;
			if ($s == 1) $i = 0; else
			if (count($v) > 2 && ($s < 1 || $s >= 5)) $i = 2;
		}
		$r .= ($r?' ':'').$t.' '.$v[$i];
		if ($rem) $t = $rem; else break;
	}
	return $r;
}
function exit_if_not_mod($t) {
	header('Cache-Control: max-age=0; must-revalidate; no-cache');
	if (!$GLOBALS['u_opts']['modtime304'] && isset($_SERVER[$h = 'HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER[$h]) == $t) {
		header('HTTP/1.0 304 Not Modified');
		exit;
	}
	header('Last-Modified: '.gmdate('r', $t?$t:T0));
}

function optimize_pic($filepath) {
	if (function_exists('exec') && (substr($f = $filepath, -4) == '.png') && is_file('optipng.exe')) {
		$e = './optipng.exe -fix "'.$f.'"';
	//	$e = './optipng.exe -fix -quiet "'.$f.'"';
		$output = array('');
		data_lock('/pic');
		exec(DIRECTORY_SEPARATOR == '/' ? $e : str_replace('/', DIRECTORY_SEPARATOR, $e), $output, $return);
		data_unlock('/pic');
		if (is_file($f .= '.bak') && filesize($f) && !filesize($filepath)) {
			data_log_adm("Optimizing $filepath failed, restoring from $f");
			unlink($filepath);
			rename($f, $filepath);
			if (!$return) $return = 'fallback';
		}
		if ($return) data_log_adm("Command line: $e\nReturn code: $return\nOutput: ".implode(NL, $output));
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
	if ($min||$max) $name .= ' onKeyUp="submitLimit('.($min?$min:0).($max?','.$max:'').')"';
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
	$j = $t['js'];
	$R = ($j === 'arch');
	$L = (LINK_TIME && !$R);
	$n = ROOTPRFX.NAMEPRFX;
	global $tmp_announce;
	foreach (data_global_announce() as $k => $v) $ano .= ($ano?'
		<br>':'')."
			$tmp_announce[$k]: $v";
	$class = array();
	if ($t['body']) $class[] = $t['body'];
	if (!$R) {
		if (FROZEN_HELL) $class[] = 'frozen-hell';
		if ($d = get_date_class()) $class[] = $d;
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
	'.preg_replace('~\v+~u', NL.'	', $t['head']):'').($t['title']?'
	<title>'.$t['title'].'</title>':'').'
</head>
<body'.($class?' class="'.implode(' ', $class).'"':'').'>'.($t['header']?'
	<header>'.($ano?'
		<p class="anno">'.$ano.'
		</p>':'').$t['header'].'
	</header>
':'').($t['task']?'
	<div id="task"'.$data.($t['subtask']
		?'>
		<div class="task">'.str_replace(NL, NL.'	', $t['task']).'
		</div>'.$t['subtask']
		:($R?'>':' class="task">').$t['task']).'
	</div>
':'').$pre.($t['footer']?'
	<footer>'.$t['footer'].'
	</footer>
':'').$scr.'
</body>
</html>';
}

?>
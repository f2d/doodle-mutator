<?php
function str_replace_first($f, $to, $s) {return (false !== ($pos = strpos($s, $f)) ? substr_replace($s, $to, $pos, strlen($f)) : $s);}
function abbr($o) {foreach (explode('_', $o) as $word) $a .= $word[0]; return $a;}
function fln($f) {return file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);}
function get_req() {return GET_Q ? explode('=', end(explode('?', $_SERVER['REQUEST_URI'])), 2) : array();}
function trim_post($p, $len = 456) {return htmlspecialchars(mb_substr(stripslashes(trim(preg_replace('~\s+~s', ' ', $p))),0,$len,ENC));}
function trim_room($r) {
	return strtolower(mb_substr(preg_replace('/\.+/', '.', preg_replace(
'/[^\w\x{0400}-\x{04ff}\x{2460}-\x{2468}\x{2605}-\x{2606}.!-]+/u', '_', trim(trim($r), '\\/')	//* <- add more unicode alphabets to complement \w?
	)), 0, ROOM_NAME_MAX_LENGTH, 'UTF-8'));
}
function get_room_skip_name($r) {return array(ME.'-skip-'.md5($r = rawurlencode($r)), $r);}
function get_room_skip_list($k = '') {
	global $room;
	return ($v = $_COOKIE[$k?$k:reset(get_room_skip_name($room))])
	?	array_slice(explode('/', $v, TRD_MAX_SKIP_PER_ROOM+1), 1, TRD_MAX_SKIP_PER_ROOM-($k?1:0))
	:	array();
}
function pic_normal_path($p) {return preg_replace('~(^|[\\/])([^._-]+)[^._-]*(\.[^.,;]+)([;,].*$)?~', '$2$3', $p);}
function pic_resized_path($p) {return substr_replace($p, '_res', -4, 0);}
function pic_subpath($f, $mk = 0) {
	$f = pic_normal_path($f);
	$n = DIR_PICS.$f[strrpos($f, '.')+1].'/'.$f[0].'/';
	if ($mk && !is_dir($n)) mkdir($n, 0755, true);
	return $n.($mk === ''?'':$f);
}
function get_draw_app_list($n) {
global	$cfg_draw_app, $tmp_draw_app, $tmp_options_field;
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
function format_filesize($B, $D = 2) {
	if ($F = floor((strlen($B) - 1) / 3)) $S = 'BkMGTPEZY';
	else return $B.' B';
	return sprintf("%.{$D}f", $B/pow(1024, $F)).' '.$S[$F].'B';
}
function exit_if_not_mod($t) {
	header('Cache-Control: max-age=0; must-revalidate; no-cache');
	if (isset($_SERVER[$h = 'HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER[$h]) == $t) {
		header('HTTP/1.0 304 Not Modified');
		exit;
	} else	header('Last-Modified: '.gmdate('r', $t));
}

function optimize_pic($filepath) {
	if (function_exists('exec') && (substr($filepath, -4) == '.png') && is_file('optipng.exe')) {
		$f = './optipng.exe -fix -quiet "'.$filepath.'"';
		$output = array('');
		data_lock('/pic');
		exec(DIRECTORY_SEPARATOR == '/' ? $f : str_replace('/', DIRECTORY_SEPARATOR, $f), $output, $return);
		data_unlock('/pic');
		if (is_file($f = $filepath.'.bak') && filesize($f) && !filesize($filepath)) {
			data_log_adm("Optimizing $filepath failed, restoring from $f");
			unlink($filepath);
			rename($f, $filepath);
			if (!$return) $return = 'fallback';
		}
		if ($return) data_log_adm("Return code: $return, command output:".implode(NL, $output));
	}
}

//* front end template --------------------------------------------------------

$tmp_wh = 'wh';
$tmp_whu = array('WIDTH','HEIGHT');
$tmp_room_new = '{'.($cfg_room = ROOTPRFX.DIR_ROOM).'new/|new}';
if (constant('ROOM_HIDE') && ROOM_HIDE) $tmp_room_new_hide = '{'.$cfg_room.($s = ROOM_HIDE.'test')."/|$s}";
//if (constant('ROOM_DUMP') && ROOM_DUMP) $tmp_room_new_dump = '{'.$cfg_room.($s = ROOM_DUMP.'dump')."/|$s}";
$tmp_title_var = 1;

function get_template_form($name, $min = 0, $max = 0, $area = 0) {
global	$tmp_submit;
	if (is_array($name)) list($name, $head, $hint, $butn) = $name;
	else {
		$head = $GLOBALS['tmp_'.$name];
		$hint = $GLOBALS['tmp_'.$name.'_hint'];
		$plhd = $GLOBALS['tmp_'.$name.'_placeholder'];
		$butn = $GLOBALS['tmp_'.$name.'_submit'];
	}
	if ($name) {
		if ($name[0] == '?') $name = substr($name, 1); else
		if ($name[0] == ';') $method = 0;
		else $method = ' method="post"';
		$name = ' name="'.$name.'"';
	}
	if ($min||$max) $name .= ' onKeyUp="submitLimit('.($min?$min:0).($max?','.$max:'').')"';
	if ($name && $plhd) $name .= ' placeholder="'.$plhd.'"';
	return ($head?'
		<p>'.$head.'</p>'
:'').(($name !== false)?(($name === 0 || $method === 0)?'
		<p><b><input type="text'.($butn?'" id="'.$butn:'').'"'.($name?$name:'').'></b></p>'
:'
		<form'.$method.'>
			<b>
			<b><'.($area?'textarea'.$name.'></textarea':'input type="text"'.$name).'></b>
			<b><input type="submit" value="'.($butn?$butn:$tmp_submit).'"></b>
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
	, nl2br(htmlspecialchars($t)));
}

function get_template_pre($p, $R = 0) {
	if (is_array($a = $p)) {
		foreach ($a as $k => $v) if (!$k) $p = $v; else if ($v) $attr .= " $k=\"$v\"";
	}
global	$tmp_require_js;
	return ($p?'
	<div class="thread">
		<pre'.$attr.'>'.$p.'
		</pre>
		<noscript><p class="hint report">'.($R?'JavaScript support required.':$tmp_require_js).'</p></noscript>
	</div>
':'');
}

function get_template_page($t) {
	$j = $t['js'];
	$R = ($j === 'arch');
	$L = (LINK_TIME && !$R);
	$n = ROOTPRFX.NAMEPRFX;
global	$cfg_langs, $tmp_icon, $tmp_announce;
	foreach (data_global_announce() as $k => $v) $ano .= ($ano?'
		<br>':'')."
			$tmp_announce[$k]: $v";
	if (!($class = $t['body']) && (FROZEN_HELL && !$R)) $class = 'frozen-hell';
	if (is_array($a = $t['data'])) foreach ($a as $k => $v) $data .= ' data-'.$k.'="'.$v.'"';
	if (is_array($a = $t['content'])) foreach ($a as $v) $pre .= get_template_pre($v, $R); else $pre = get_template_pre($a, $R);
	if (is_array($a = $j) || ($j && ($a = array(".$j" => 0)))) foreach ($a as $k => $v) $scr .= '
	<script src="'.$n.($v = ($v?'':$k).'.js').($L?'?'.filemtime(NAMEPRFX.$v):'').'"></script>';

	return '<!doctype html>
<html lang="'.(in_array($t['lang'], $cfg_langs)?$t['lang']:'en').'">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=690">
	<link rel="shortcut icon" type="image/png" href="'.($tmp_icon?ROOTPRFX.$tmp_icon:$n).'.png">
	<link rel="stylesheet" type="text/css" href="'.$n.'.css'.($L?'?'.filemtime(NAMEPRFX.'.css'):'').'">'.($t['head']?'
	'.preg_replace('~\v+~', NL.'	', $t['head']):'').($t['title']?'
	<title>'.$t['title'].'</title>':'').'
</head>
<body'.($class?' class="'.$class.'"':'').'>'.($t['header']?'
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
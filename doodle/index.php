<?php

$t = microtime();

if (PHP_MAJOR_VERSION >= 8) {
	error_reporting(~(
		E_NOTICE
	// |	E_STRICT
	|	E_WARNING
	// |	E_DEPRECATED
	));	//* <- TODO many fixes for PHP 8.0+ warnings, shut up until then, change for testing and rewrites
}

define('IS_LOCALHOST', $_SERVER['SERVER_ADDR'] === $_SERVER['REMOTE_ADDR']);

//* Wrapper to avoid PHP 8 Warning: Undefined array key:

function get_value_or_empty($array, $key) {
	if (
		array_key_exists($key, $array)
	&&	!empty($array[$key])
	) {
		return $array[$key];
	}

	return '';
}

function is_prefix ($text, $part) { return (strpos ($text ?? '', $part) === 0); }
function is_postfix($text, $part) { return (strrpos($text ?? '', $part) === strlen($text) - strlen($part)); }

// function is_prefix ($text, $part) { return substr($text ?? '', 0, strlen($part)) === $part; }
// function is_postfix($text, $part) { return substr($text ?? '',   -strlen($part)) === $part; }

function exit_no_access($why) {
	header('HTTP/1.1 403 Forbidden');

	die("Error 403: Forbidden. Reason: $why.");
}

function remove_header_if_possible($header_name) {
	if (function_exists($f = 'header_remove')) $f($header_name);
}

function replace_header_value($header_name, $header_value) {
	remove_header_if_possible($header_name);

	header("$header_name: $header_value");
}

function set_cache_control_header($can_store) {
	replace_header_value('Cache-Control', "max-age=0; must-revalidate; $can_store");
}

replace_header_value('Vary', 'Accept-Language, Cookie');
set_cache_control_header('no-cache');

header('Expires: Mon, 12 Sep 2016 00:00:00 GMT');
header('Pragma: no-cache');

if (!empty($_REQUEST['pass'])) {
	exit_no_access(IS_LOCALHOST ? "pass = $_REQUEST[pass]" : 'pass');	//* <- ignore spam bot requests
}

define('NAMEPRFX', 'd');

//* Config parts that are not safe to change: ---------------------------------

define('ENC', 'utf-8');
mb_internal_encoding(ENC);
mb_regex_encoding(ENC);

define('ROOTPRFX', mb_substr($s = $_SERVER['PHP_SELF'] ?: $_SERVER['SCRIPT_NAME'] ?: '/', 0, mb_strrpos($s, '/')+1));

if (!is_prefix($s = URLdecode($p = $_SERVER['REQUEST_URI']), ROOTPRFX)) {
	exit_no_access('"'.ROOTPRFX.'" path does not match "'.$s.'"');
}

ob_start();

//* source: http://php.net/security.magicquotes.disabling#91653

if (function_exists($func_name = 'get_magic_quotes_gpc') && $func_name()) {
	function strip_magic_slashes(&$value, $key) { $value = stripslashes($value); }
	$gpc = array(&$_COOKIE, &$_GET, &$_POST, &$_REQUEST, &$_SESSION);
	array_walk_recursive($gpc, 'strip_magic_slashes');
}

$debug_dump = array(ob_get_clean());

define('ME', 'me');
define('ME_VAL', $_POST[ME] ?? $_COOKIE[ME] ?? '');	//* <- don't rely on $_REQUEST and EGPCS order
define('POST', 'POST' === $_SERVER['REQUEST_METHOD']);

if (POST) {
	set_cache_control_header('no-store');

	if (!ME_VAL) {
		exit_no_access('post');			//* <- ignore anonymous posting
	}

	ignore_user_abort(true);
}

$t = explode(' ', $t);
define('T0', $t[1]);
define('M0', $t[0]);

$s = $_SERVER['SERVER_SOFTWARE'];
define('WS_NGINX', stripos($s, 'nginx') !== false);
define('WS_HTACCESS_SUPPORTED', stripos($s, 'apache') !== false);

define('GET_Q', strpos($p, '?'));

define('ARG_ERROR', '!');
define('ARG_ERROR_SPLIT', '!');
define('ARG_ABOUT', 'about');
define('ARG_DENY', 'deny');
define('ARG_DESC', 'desc');
define('ARG_DRAW', 'draw');
define('ARG_DROP', 'drop');
define('ARG_KEEP', 'keep');
define('ARG_SKIP', 'skip');
define('ARG_UNSKIP', 'unskip');
define('ARG_CHANGE', 'change');
define('ARG_CHANGE_TO', 'change_to');
define('ARG_UNDESCRIBED', 'undescribed');
define('ARG_UNDRAWN', 'undrawn');
define('ARG_UNKNOWN', 'unknown');
define('ARG_FINAL_TASK', 'final_task');
define('ARG_REPORT', 'report');
define('ARG_ANY', 'any');
define('ARG_ANY_OF', 'any_of');
define('ARG_DRAW_APP', 'draw_app');
define('ARG_LEGACY_POST_TEXT', 'post');
define('ARG_LEGACY_POST_FILE', 'file');
define('ARG_LEGACY_POST_SIZE', 'bytes');
define('ARG_LEGACY_POST_WIDTH', 'width');
define('ARG_LEGACY_POST_HEIGHT', 'height');
define('ARG_LEGACY_POST_TIME', 'time');
define('ARG_LEGACY_POST_USED', 'used');
define('ARG_LEGACY_POST_NAME', 'name');
define('ARG_LEGACY_POST_FULL_NAME', 'fullname');
define('ARG_POST_TEXT_PART', 'post_text_part');
define('ARG_POST_IMAGE_FILE_NAME_PART', 'file_name_part');
define('ARG_POST_IMAGE_FILE_SIZE', 'file_size_bytes');
define('ARG_POST_IMAGE_WIDTH', 'image_width');
define('ARG_POST_IMAGE_HEIGHT', 'image_height');
define('ARG_POST_USED_TIME', 'used_time');
define('ARG_POST_USED_TOOLS', 'used_tools');
define('ARG_POST_USER_NAME_PART', 'user_name_part');
define('ARG_POST_USER_NAME_FULL', 'full_user_name');
define('ARG_POST_USER_ID', 'user_id');
define('ARG_NAMING_VAR_PREFIX', '$');
define('ARG_LANG', 'lang');

define('LK_MOD_HTA', 'htaccess');
define('LK_MOD_ACT_LOG', 'done');
define('LK_MOD_ACT', 'mod');
define('LK_MOD_OPT', 'opt');
define('LK_PIC_OPT', 'pic');
define('LK_REF_LIST', 'reflinks');
define('LK_USERLIST', 'users');
define('LK_USER', 'user/');
define('LK_ROOM', 'room/');
define('LK_ARCH', 'arch/');
define('LK_ARCH_DL', 'archiver');
define('LK_VERSION', 'version');
define('COUNT_ARCH', 'arch');
define('COUNT_ROOM', 'room');
define('COUNT_POST', 'post');

define('NL', "\n");
define('TAB', "\t");
define('BOM', pack('CCC', 239, 187, 191));	//* <- UTF-8 Byte Order Mark
define('TRANSPARENT_COLOR', 0x7f000000);	//* <- alpha 0 = opaque, 127 = transparent, https://php.net/imagecolorallocatealpha
define('ARCH_DL_HASH_TYPE', 'crc32b');
define('B64_PRFX', 'base64:');
define('OPT_PRFX', 'opt_');
define('CONTENT_DATETIME_FORMAT', 'Y-m-d H:i:s');
define('FILENAME_DATETIME_FORMAT', 'Y-m-d H-i-s');
define('HTTP_MOD_TIME_FORMAT', 'D, d M Y H:i:s \G\M\T');
define('PAT_DATE', '~(?P<YearMonth>(?P<Year>\d+)-(?P<Month>\d+))-(?P<Day>\d+)~');
define('PAT_REPORT', '~^(?P<Thread>\d+)\D+(?P<Post>\d+)\D+(?P<Side>\d+)$~');
define('PAT_CONTENT', '~^(?P<Before>.*?<pre>)(?P<Content>.*?\S)(?P<After>\s*</pre>.*)$~uis');

define('SUBPAT_PP_BEFORE', '~(?:^|[;,]\s*)');
define('SUBPAT_PP_AFTER', '(?=$|[;,])~ui');
define('PAT_POST_PIC_CRC32', SUBPAT_PP_BEFORE.'(?P<Prefix>0x)(?P<CRC32>[0-9a-f]{8})'.SUBPAT_PP_AFTER);
define('PAT_POST_PIC_BYTES', SUBPAT_PP_BEFORE.'(?P<Bytes>[^0\D]\d*)\s+B'.SUBPAT_PP_AFTER);
define('PAT_POST_PIC_W_X_H', SUBPAT_PP_BEFORE.'(?P<Width>[^0\D]\d*)\D(?P<Height>[^0\D]\d*)'.SUBPAT_PP_AFTER);
define('PAT_REGEX_FORMAT', '~^/.+/[imsu]*$~u');

define('SUBPAT_EM_NONLETTERS', '@#?<>()\[\]\s\\\\/');	//* <- characters not allowed in email parts; keep it simple, for browser-side check
define('PAT_EMAIL_FORMAT', '^.*?([^'.SUBPAT_EM_NONLETTERS.']+@[^'.SUBPAT_EM_NONLETTERS.'.]+\\.[^'.SUBPAT_EM_NONLETTERS.']+).*?$');
define('RELATIVE_LINK_PREFIX', 'http://*/');

//* Start buffering to clean up included output, like BOMs: *------------------

ob_start();
require(NAMEPRFX.'.cfg.php');
require(NAMEPRFX.'.fu.php');

if (!empty($v =
	get_value_or_empty($_GET, ARG_LANG)
?:	get_value_or_empty($_POST, ARG_LANG)
)) {
	add_cookie_header(ARG_LANG, $v);

	exit_redirect(get_value_or_empty($_SERVER, 'HTTP_REFERER') ?: ROOTPRFX, 'change language');
}

$cfg_wh = array('width', 'height');
$cfg_room_prefixes = array();

foreach ($cfg_room_types as $v) if (!empty($v = get_value_or_empty($v, 'if_name_prefix'))) {
	$cfg_room_prefixes[] = $v;
}

foreach ($cfg_dir as $k => $v) {
	define('DIR_'.strtoupper($k), $v .= '/');
	${$k.'_list_href'} = ROOTPRFX.$v;
}

$cfg_room_prefix_chars = implode('', array_unique(mb_str_split(fix_encoding(implode('', $cfg_room_prefixes)))));

//* Select UI language *-------------------------------------------------------

if (
	!empty($v = $_REQUEST[ARG_LANG])
&&	(
		in_array($v, $cfg_langs)
	||	in_array($v = strtolower(substr($v, 0, 2)), $cfg_langs)
	)
) {
	$lang = $v;
} else

//* source: http://www.dyeager.org/blog/2008/10/getting-browser-default-language-php.html

if ($v = $_SERVER['HTTP_ACCEPT_LANGUAGE']) {
	$a = array();
	foreach (explode(',', $v) as $v) if (preg_match('~(\S+);q=([.\d]+)~ui', $v, $m)) {
		$a[$m[1]] = (float)$m[2];
	} else $a[$v] = 1.0;

//* check for highest q-value. No q-value means 1 by rule

	$q = 0.0;
	foreach ($a as $k => $v) if (
		$v > $q
	&&	(
			in_array($k, $cfg_langs)
		||	in_array($k = strtolower(substr($k, 0, 2)), $cfg_langs)
		)
	) {
		$q = (float)$v;
		$lang = $k;
	}
}

if (!$lang) {
	$lang = $cfg_langs[0];
}

define('LANG', $lang);
replace_header_value('Content-Language', LANG);

require(NAMEPRFX.".localization.$lang.php");
require(NAMEPRFX.'.db.php');

//* End buffering, hide output. *----------------------------------------------

while ($v = ob_get_clean()) {
	$debug_dump[] = $v;
}

if (strlen($v = implode(NL, array_filter(array_map('trim_bom', $debug_dump), 'strlen')))) {
	data_log_action('debug dump', $v);
}
time_check_point('done cfg');

//* Data maintenance *---------------------------------------------------------

data_fix();

if (!POST) {
	$r = array($_SERVER['HTTP_REFERER']);
	if (
		($v = $_SERVER['HTTP_USER_AGENT'])
	&&	preg_match_all('~\b\w+(:/+|\S*?@)\S+~i', $v, $m)
	) {
		foreach ($m[0] as $v) {
			if (false === strpos($v, '(')) $v = trim($v, ')');
			if (
				($v = trim($v))
			&&	!in_array($v .= '#(user-agent)', $r)
			) $r[] = $v;
		}
	}
	data_log_ref(array_filter($r, 'is_url_external'));
	time_check_point('done ref log');
}

//* Location check/fix *-------------------------------------------------------

$query = array();
$qpath = array();
$q = '';

if (false !== GET_Q) {
	if ($q = $query_in_url = ltrim(substr($p, GET_Q), '?')) {
		foreach (explode('&', $q) as $chain) {
			$a = explode('=', $chain);
			$v = (count($a) > 1 ? array_pop($a) : '');
			foreach ($a as $k) $query[URLdecode($k)] = URLdecode($v);
		}
	}
	$p = substr($p, 0, GET_Q);
}

$p = mb_strtolower(URLdecode(substr($p, strlen(ROOTPRFX))));

if ($a = mb_split_filter($p)) {
	$d = array_shift($a);

//* 1) only accept recognized folders:

	if (in_array($d, $cfg_dir)) {
		$etc = '';
		$qredir = ($qpath['dir'] = $qdir = $d).'s';
		foreach ($cfg_dir as $k => $v) if ($qdir == $v) {
			${"qd_$k"} = 1;

			break;
		}

//* 2) user profile/number, first non-zero found:

		if ($qd_user) {
			foreach ($a as $v) if ($v = intval($v)) $etc = $v;
			$a = array();
		} else {

//* 2) if recognized room type found in path parts, next after it be the room name, last part optional, anything else thrown away:

			foreach ($a as $v) {
				if ($qroom) $etc = $v; else
				if ($qr_type) $qroom = $v; else
				if (in_array($v, $cfg_game_type_dir)) $qr_type = $v;
			}

//* 3) if no valid room type, assume the default one, take 1st part to be room name, last part optional, all other thrown away:

			if (!$qr_type && $a) {
				$qr_type = GAME_TYPE_DEFAULT;
				$qroom = array_shift($a);
				if ($a) $etc = array_pop($a);
			}
			if (isset($qr_type)) $r_type = $qr_type;
			if ($r_type) $qpath['room_type'] = $r_type;
			if ($r_type || !GAME_TYPE_DEFAULT) {

//* 4) validate and fix room name:

				if (
					($room = trim_room($qroom))
				&&	get_room_name_length($room)
				) {
					$qpath['room_name'] = $room;
				} else $etc = $room = '';
			}
		}

//* 5) this will be either "/last-part" (kept for legacy requests) or just a trailing "/" before optional "?query":

		if ($etc && !preg_match('~^[-\d]+$~', $etc)) $etc = '';
		$qpath['etc'] = $etc;
	}
}

$qfix = ROOTPRFX.encode_URL_parts($qpath).($q ? "?$q" : '');

if (
	!POST
&&	!isset($query[LK_MOD_ACT])
&&	!is_url_equivalent($qfix, $_SERVER['REQUEST_URI'])
&&	(SERVE_AS_INDEX_PAGE || 'index' !== get_file_name_no_ext($_SERVER['REQUEST_URI'], 0))
) {
	exit_redirect($qfix);
}

time_check_point('done location check');

//* User settings *------------------------------------------------------------
//* keep legacy formats until cookies expire in 3 years;
//* mb strings are not expected here without escape/encoding or user meddling;
//* ---------------------------------------------------------------------------

$u_flag = array();
$u_opts = array();
$opt_sufx = 'aoprui';
$opt_name = array('opta', 'opti', 'trd_per_page', 'draw_max_recovery', 'draw_max_undo', 'draw_time_idle');
$opt_lvls = array('a' => 'admin', 'i' => 'check');

if (ME_VAL && ($me = fix_encoding(URLdecode(ME_VAL)))) {
	if (false === mb_strpos($me, '/')) {

//* v1, one separator for all, like "hash_etc_etc":

		list($u_qk, $u_opti, $u_trd_per_page, $u_room_default) = mb_split('_', $me, 4);
	} else
	if (false === mb_strpos($me, ':')) {

//* v2, plain ordered numbers, like "hash/vvv_val_val/etc":

		list($u_qk, $i, $u_room_default, $u_draw_app) = mb_split('\\/', $me, 4);
		if (!preg_match_all('~(\d+)([a-z])~u', mb_strtolower($i), $m)) {
			list($u_opti, $u_trd_per_page, $u_draw_max_undo) = mb_split(false !== mb_strpos($i, '_')?'_':'.', $i);
		} else {

//* v3, abbreviated suffixes for middle part, like "01adm_0010opt_30per_page_99undo", or "0a1o2p3u", in any order:

			foreach ($m[1] as $k => $v) if (false !== ($i = mb_strpos($opt_sufx, $m[2][$k]))) ${'u_'.$opt_name[$i]} = $v;
		}
	} else {

//* v4, key prefixes for all, like "hash/key:val/key:val/etc:etc", in any order:

		$a = mb_split('\\/', $me);
		$u_qk = array_shift($a);
		$key_value_pairs = $a;
	}

	if (
		isset($_POST[ME])
	&&	strlen($_POST[ME])
	&&	strlen($reg = trim($_POST[ME]))
	&&	($u_qk !== $reg)
	) {
		$u_qk = $reg;
	}

	if (data_check_user($u_qk, $reg)) {
		$u_name = $usernames[$u_num];

		if (LOG_IP) {
			data_log_ip();
		}

		if ($u_flag['ban']) {
			$message = get_localized_text('ban');

			die(get_template_page(array(
				'title' => $message
			,	'task' => $message
			,	'page_class' => 'burnt-hell'
			)));
		}

		if ($key_value_pairs) {
			$i = 'input';
			foreach ($a as $key_value_pair) {
				list($k, $v) = mb_split(':', $key_value_pair, 2);
				if (strlen($v = decode_opt_value($v))) {
					if (array_key_exists($k, $cfg_opts_order[$i])) {
						${'u_'.$cfg_opts_order[$i][$k]} = $v;
					} else
					if (array_key_exists($k, $cfg_opts_order) && $k !== $i) {
						$y = (false !== mb_strpos($v, '.') ? mb_split('\\.', $v) : mb_str_split($v));
						foreach ($cfg_opts_order[$k] as $v) if (in_array(get_abbr($v), $y)) $u_opts[$v] = 1;
					}
				}
			}
		} else {
			foreach ($opt_lvls as $i => $a) if ($p = ${"u_opt$i"})
			foreach ($cfg_opts_order[$a] as $k => $v) if ($x = intval($p[$k])) $u_opts[$v] = $x;
		}

		if (POST) $post_status = 'user_qk';
	}
}

if (!POST) {
	set_cache_control_header($u_key ? 'private' : 'public');
}

define('GOD', !!$u_flag['god']);
if (GOD && !POST && $u_opts['display_php_errors']) {
	ini_set('display_errors', '1');
	ini_set('html_errors', '1');
	error_reporting(~0);
}

define('TIME_PARTS', !POST && GOD && !$u_opts['time_check_points']);	//* <- profiling
if (TIME_PARTS) time_check_point('done user settings, GOD = '.GOD
	.NL.'u_flag = '.get_print_or_none($u_flag)
	.NL.'u_opts = '.get_print_or_none($u_opts)
); else unset($tcp);

//* Location access check *----------------------------------------------------

$room_type = array();

if ($qdir) {
	if ($room_name = $room) {
		if ($r_type) $room = "$r_type/$room";
		$room_type = get_room_type($room);
	}
} else {
	if ($u_key && !$u_room_default) $qd_opts = 1;	//* <- root page
	if (
		GOD
	&&	WS_HTACCESS_SUPPORTED
	&&	!is_prefix($query[LK_MOD_OPT], 'hta')
	) rewrite_htaccess();
}

$top_title = (
	false !== ($k = array_search($r_type, $cfg_game_type_dir))
	? get_localized_text('room_types_title', $k)
	: get_localized_text('title')
);

define('MOD', GOD || $u_flag['mod'] || $u_flag["mod_$room"]);
define('NO_MOD', !$room_type['mod'] || $u_flag['nor']);
define('FROZEN_HELL', data_global_announce('stop'));	//* <- after $room is defined

if (FROZEN_HELL && !(MOD || $qd_arch || ($qd_opts && $u_key))) {
	if (POST) goto after_posting;
	if (FROZEN_HELL < 0) {
		$message = get_localized_text('stop_all');

		die(
			$etc == '-'
			? $message
			: get_template_page(array(
				'title' => "$message $top_title."
			,	'task' => $message
			,	'anno' => 1
			,	'header' => array(
					'<a href="'.ROOTPRFX.'">'.$top_title.'.</a>'
				, 'r' =>
					'<a href="'.ROOTPRFX.DIR_ARCH.'">'.get_localized_text('archive').'.</a>'.NL.
					'<a href="'.ROOTPRFX.DIR_OPTS.'">'.get_localized_text('options').'.</a>'
				)
			))
		);
	}
}

if ($u_key && isset($_REQUEST[LK_ARCH_DL])) {
	if (ARCH_DL_ENABLED) die(get_archiver_dl_list());
	else exit_no_access('disabled');
}

if (TIME_PARTS) time_check_point('frozen = '.FROZEN_HELL.', MOD = '.MOD.', inb4 action fork');
if (POST) goto posting;




//* GET: view some page content *----------------------------------------------

$page = array();

//* mod panel -----------------------------------------------------------------

if (GOD && (
	($q = isset($query[$qmod = LK_MOD_ACT]))
//||	($etc && strlen(trim($etc, '-')))
)) {
	$qdo = LK_MOD_OPT;
	$qday = 'day';
	$qid = 'id';
	$qpath = 'path';
	$a = array_keys(get_localized_text_array('mod_pages'));

	if ($q) {
		$q = "$query[$qmod]" ?: reset($a);
		$do = "$query[$qdo]";
		$path = "$query[$qpath]";
		$i = intval($query[$qid]);
	} else {
		$q = $a[intval($etc)-1];
		if ($i = strpos($etc, '-')) {
			$i = intval(substr($etc, $i+1));
			$a = array_keys(get_localized_text_array('mod_files'));
			$do = $a[$i-1];
		}
	}

	$k = (is_prefix($q, 'user') ? LK_USERLIST : $q);

	if ($k === LK_USERLIST && $i) {
		die(get_template_page(array(
			'title' => get_localized_text('mod_pages', $q).": $i, $usernames[$i]"
		,	'content' => (
				($a = data_get_user_info($i))
				? array_merge(array(
					'Current date' => date(CONTENT_DATETIME_FORMAT, T0)
				,	'User ID' => $i
				,	'User name' => $usernames[$i]
				), $a)
				: get_localized_text('empty')
			)
		,	'data_listing' => array(
				'key-value' => ':'.NL
			,	'next-item' => NL.'<hr>'
			)
		)));
	}

	$lnk = $t = '';
	$mod_title = $mod_page = (
		get_localized_or_empty_text('mod_pages', $q)
	?:	get_localized_text('empty')
	);

	if ($q === 'welcome') {
		$page['welcome'] = get_localized_text_array('welcome_parts');
	} else
	if ($q === 'logs') {
		$day = $query[$qday] ?: $etc;
		$ymd = ($day ? preg_match(PAT_DATE, $day) : false);
		if ($mod_logs = data_get_mod_log()) {
			$page['data']['content']['type'] = 'reports';
			$page['content'] = "
day_link = .?$qmod=$q&$qday=";
			$last = end(end($mod_logs));
			$last = data_get_mod_log($last_ymd = key($mod_logs).'-'.$last, 1);
			if (!$day) $day = $ymd = $last_ymd;
			if ($ymd) {
				exit_if_not_mod(data_get_mod_log($day, 1));
				$mod_title = "$mod_page: $day";
				if ($a = data_get_mod_log($day)) {
					$attr = ' data-day="'.$day.'"';
					$page['content'] .= '
images = '.ROOTPRFX.DIR_PICS.'
rooms = '.ROOTPRFX.DIR_ROOM.'
flags = a
'.$a;
				}
			} else {
				exit_if_not_mod($last);
			}
			foreach ($mod_logs as $ym => $d) $lnk .= ($lnk?'</p>':'').'
<p>'.$ym.'-'.implode(',', $d);
			$lnk .= ' <small>'.date('H:i:s', $last).'</small></p>';
		}
	} else
	if ($q === 'files') {
		$mod_file_actions = get_localized_text_array('mod_files');
		$check_functions = array(
			'opcache_check' => 'opcache_get_status'
		,	'opcache_reset' => 'opcache_reset'
		,	'opcache_inval' => 'opcache_invalidate'
		);
		foreach ($check_functions as $k => $v) if (!function_exists($v)) unset($mod_file_actions[$k]);
		foreach ($mod_file_actions as $k => $v) $lnk .= "
<li><a href=\"?$qmod=$q&$qdo=$k\">".mb_str_replace_first(' ', '</a> ', $v).'</li>';
		$lnk = '
<ul'.(isset($query[$qpath]) ? ' class="hid"' : '').'>'.indent($lnk).'</ul>';
		if (!$do) $do = end(array_keys($mod_file_actions));
		if ($do && array_key_exists($do, $mod_file_actions)) {
			if ($a = $mod_file_actions[$do]) $lnk .= '
<p>'.rtrim($a, ':.').':</p>';
			ignore_user_abort(true);
			data_lock($lk = LK_MOD_ACT);
if (TIME_PARTS) time_check_point('ignore user abort');
			if ($do === 'list') {
				$tab_count = 0;
				$dirs = array();
				$files = array();
				$path = trim_slash_dots(mb_normalize_slash($path));
				if (strlen($path) && is_file($path)) {
					$t = trim_bom(file_get_contents($path));
				} else {
					$dd = trim_slash_dots(get_const('DATA_DIR') ?: get_const('DIR_DATA'));
					$no_direct_access = (mb_substr_before($path, '/') === $dd);
					foreach (get_dir_contents($path ?: '.', F_NATSORT) as $fn) {
						$f = trim_slash_dots("$path/$fn");
						$m = date(CONTENT_DATETIME_FORMAT, filemtime($f));
						if ($d = is_dir($f)) $fn .= '/';
						$a = (
							$d
						||	$no_direct_access
						||	is_prefix($fn, '.hta')
						||	is_postfix($fn, '.bak')
							? "?$qmod=$q&$qdo=$do&$qpath=$f"
							: ROOTPRFX.$f
						);
						$a = "<a href=\"$a\">$fn</a>";
						if ($d) {
							$a = array($a, $m);
						} else {
							$e = get_file_ext($fn);
							$s = filesize($f);
							$a = array($a, $e, $s, $m);
						}
						if ($tab_count < ($k = count($a))) $tab_count = $k;
						${$d?'dirs':'files'}[] = $a;
					}
					if ($path) {
						$up = trim_slash_dots(get_file_dir("/$path"));
						array_unshift($dirs, array("<a href=\"?$qmod=$q&$qdo=$do&$qpath=$up\">$path/..</a>"));
					}
					if ($a = array_merge($dirs, $files)) {
						$a = array_map(
							function($v) use ($tab_count) {
								return indent(
									is_array($v)
									? '
<td'.(($k = $tab_count - count($v)) > 0 ? ' colspan="'.($k+1).'"' : '').'>'.implode('</td>
<td>', $v).'</td>'
									: $v
								);
							}
						,	$a
						);
						$page['subtask'] = '
<center class="filelist">'.indent('
<table>'.indent('
<tr>'.implode('</tr>
<tr>', $a).'</tr>')
.'</table>').'
</center>';
					}
				}
			} else
			if ($do === 'opcache_check') {
				if (is_array($a = opcache_get_status())) {
					if (array_key_exists($k = 'scripts', $a)) {
						if (is_array($b = &$a[$k])) ksort($b);
						unset($b);
					}

					if ($t = get_print_or_none($a)) {
						$t = preg_replace_callback(
							'~time[^=\v]+=>\s*(?P<Time>\d+)+~iu'
						,	function($match) {
								return (
									intval($match['Time']) > 0
									? $match[0].' ('.date(DATE_ATOM, $match['Time']).')'
									: $match[0]
								);
							}
						,	$t
						);
					}
				} else $t = $a;
			} else
			if ($do === 'opcache_reset') {
				$t = opcache_reset();
			} else
			if ($do === 'opcache_inval') {
				$t = implode(NL, array_filter(array_map(
					function($f) {
						return (
							get_file_ext($f) === 'php'
							? "$f\t".opcache_invalidate($f)
							: ''
						);
					}
				,	get_dir_contents()
				)));
			} else
			if ($do === 'room_list_reset') {
				$t = data_post_refresh(true);
			} else
			if ($do === 'img2subdir') {
				$i = 0;
				foreach (get_dir_contents($d = DIR_PICS) as $f) if (is_file($old = $d.$f)) {
					$dup_count = 0;
					while (is_file($new = get_pic_subpath($f, true).($dup_count++ ? "_$dup_count" : '')));
					$t .=
NL.(++$i)."	$old => $new	".($old === $new?'same':(rename($old, mkdir_if_none($new))?'OK':'fail'));
				}
if (TIME_PARTS && $i) time_check_point("done $i pics");
			} else
			if (is_prefix($do, 'img2orphan')) {
				require_once(NAMEPRFX.'.arch.php');
				$i = $k = 0;
				$c = count($links = array_unique(array_merge($r = data_get_visible_images(), $a = data_archive_get_images())));
				$check = is_postfix($do, 'check');

				function move_leftover_files($d) {
					global $check, $links, $t, $i, $k;

					if (!is_dir($d)) {
						return;
					}

					foreach (get_dir_contents($d) as $f) if (is_file($old = "$d$f")) {
						if (false === array_search($f, $links)) {
							if ($check) {
								$t .=
NL.(++$i)."	$old => ".DIR_PICS_ORPHAN.' ?';
							} else {
								$dup_count = 0;
								while (is_file($new = DIR_PICS_ORPHAN.$f.($dup_count++ ? "_$dup_count" : '')));
								$t .=
NL.(++$i)."	$old => $new	".($old === $new?'same':(rename($old, mkdir_if_none($new))?'OK':'fail'));
							}
						} else ++$k;
					} else if (($old .= '/') != DIR_PICS_ORPHAN && $old != DIR_PICS_DEL) {
						move_leftover_files($old);
					}
				}

				move_leftover_files(DIR_PICS);
if (TIME_PARTS && $i) time_check_point("done $i orphan pics, $k linked / $c links");
			} else
			if ($do === 'nginx') {
				$last = 0;
				$a = array();
				foreach (get_dir_contents() as $f) if (is_file($f) && mb_stripos($f, $do) !== false) {
					$last = max($last, filemtime($f));
					$a[] = $f;
				}
				if ($last) exit_if_not_mod($last);
				foreach ($a as $f) if (strlen($x = trim(file_get_contents($f)))) {
					if (preg_match_all('~\$([_A-Z][_A-Z\d]*)~', $x, $match)) foreach (array_unique($match[1]) as $k) {
						if ($v = get_const($k) ?: $_SERVER[$k]) $x = mb_str_replace('$'.$k, $v, $x);
					}
					$t .= "# Example: $f #
$x
# End of example. #";
				}
			} else
			if (is_prefix($do, 'arch')) {
				require_once(NAMEPRFX.'.arch.php');
				$h = is_postfix($do, 'hash');
				$t = data_archive_rewrite(array(
					'line_break' => (is_postfix($do, 'poems') ? ' '.POST_LINE_BREAK.' ' : false)
				,	'recheck_img' => array(
						'exists' => $h || is_postfix($do, '404')
					,	'hash' => $h
					)
				));
			} else
			if (is_prefix($do, 'hta')) {
				$t = rewrite_htaccess(is_postfix($do, 'write'));
			} else {
				$t = data_fix($do);
			}
			if (!$t) $t = get_localized_text('no_change');
			data_unlock($lk);
		}
	} else
	if (is_prefix($q, 'vars')) {
		exit_if_not_mod();				//* <- never exits, to check HTTP_IF_MODIFIED_SINCE, etc
		$sort = (is_postfix($q, 'sort'));
		$t = '';

		foreach (
			array(
				'DATE_RFC822',
				'DATE_RFC2822',
			) as $k
		) {
			if ($v = get_const($k)) {
				$t .= "gmdate($k) = ".gmdate($v, T0).NL;
			}
		}

		foreach (
			array(
				'shmop_open' => 'function_exists',
				'strip_magic_slashes' => 'function_exists',
				'normalizer_normalize' => 'function_exists',

				'php_uname',
				'PHP_OS',
				'PHP_VERSION',
				'PCRE_VERSION',
				'mb_regex_encoding',
				'mb_regex_set_options',

				'sys_getloadavg',
				'get_system_memory_info',

				'ROOTPRFX',
				'NAMEPRFX',
				'DATA_VERSION',
				'HTML_VERSION',
				'HTACCESS_VERSION',

				'cfg_room_prefix_chars',
				'cfg_room_prefixes',
				'qfix',
				'qpath',
				'query',
				'room',
				'room_type',

				'_GET' => $_GET,
				'_POST' => $_POST,
				'_COOKIE' => $_COOKIE,
				'_SESSION' => $_SESSION,
				'_SERVER' => $_SERVER,
				'_ENV' => $_ENV,

				'getenv',
				'headers_list',
				'gd_info',
			) as $k => $v
		) {
			if (is_string($v)) {
				if (is_string($k) && function_exists($v)) {
					$e = "$v($k)";
					$v = $v($k);
					$k = $e;
				} else {
					$k = $v;
					$v = (
						function_exists($k) ? $k() :
						(get_const($k) ?: $$k)
					);
				}
			}

			if ($v) {
				if ($sort && is_array($v)) {
					if (isset($v[0])) natsort($v);
					else ksort($v);
				}

				if ($v = trim(print_r($v, true))) {
					$v = fix_encoding($v);
					$e = end($fix_encoding_chosen);
					$t .= "$k: $e = $v".NL;
				}
			}
		}
	} else
	if ($q && array_key_exists($q, get_localized_text_array('mod_pages'))) {

		data_lock($k, false);

		exit_if_not_mod(data_get_mod_log($q, 1));
		if ($t = data_get_mod_log($q)) {
			if ($k === LK_USERLIST) {
				$page['content'] .= "
archives = $arch_list_href
profiles = ".ROOTPRFX.DIR_USER."
left_link = .?$qmod=$q&$qid=
left = ".get_localized_text('mod_user_info')."
right = ".get_localized_text('mod_user_hint')."
flags = cgu
v,$u_num,u	v
$t";
			} else
			if ($k === LK_REF_LIST) {
				$page['content'] .= "
flags = c
$t";
			}

			$page['data']['content']['type'] = $k;
			$lnk .= get_template_form(array('filter' => 1));
		}

		data_unlock($k);
	}
	if (
		$page['content']
	||	$page['subtask']
	||	$page['welcome']
	||	($page['textarea'] = $t)
	||	$lnk
	) {
		if ($page['content'] || $lnk) {
			$page['js']['mod']++;
			$page['js'][0]++;
		}
	} else $lnk = get_localized_text('empty');
	$page['task'] = "
<p$attr>$mod_page:</p>$lnk";
} else

//* info page, about website, rules -------------------------------------------

if (isset($query[ARG_ABOUT])) {
	$about_open = preg_split('~\W+~u', $query[ARG_ABOUT], 0, PREG_SPLIT_NO_EMPTY);

	$page['task'] = '<p>'.get_localized_text('about').':</p>';
	$page['welcome'] = get_localized_text_array('welcome_parts');
	$page['js'][0]++;

	foreach (get_localized_text_array('rules') as $k => $v) {
		if (is_array($hint = $v['hint'] ?? $v['list'])) {
			$s = '';
			foreach ($hint as $i) $s .= NL.'<li>'.indent(get_template_hint($i)).'</li>';
			$hid = (
				($qdir || $page['task'])
			&&	(false === array_search($k, $about_open))
				? ' class="hid"'
				: ''
			);
			$s = NL."<ul$hid>".indent($s).'</ul>';
		} else {
			$s = NL.'<p class="hint">'.indent(get_template_hint($hint)).'</p>';
		}
		$page['task'] .= NL."<p id=\"$k\">$v[head]</p>$s";
	}
} else

//* user profile page ---------------------------------------------------------

if ($qd_user) {
	if (
		($i = intval($etc))
	&&	($a = data_get_user_profile($i))
	) {
		exit_if_not_mod($a['last modified']);

		$name = $usernames[$i] ?: get_localized_text('not_found');
		$r = array();
		if (($t = $a['email']) && $t['show']) {
			$r['email'] = '<p>'.get_localized_text('user_email').": $t[addr]</p>";
		}
		if ($t = $a['about'] ?: '') {
			$t = get_template_profile_html($t);
			$r['about'] = '<p>'.indent(get_localized_text('user_about').":<br><br>$t").'</p>';
		}
		if ($r) {
			array_unshift($r, '<p>'.get_localized_text('user_name').": $name</p>");
			$page['task'] = '<p>'.get_localized_text('user').":</p>";
			$page['profile'] = implode(NL, $r);
		}
	}
	if (!$page['profile']) {
		$page['task'] =
		$page['profile'] = get_localized_text('empty');
	}
} else

//* archived threads ----------------------------------------------------------

if ($qd_arch) {
	require_once(NAMEPRFX.'.arch.php');

	$search = data_archive_get_search_terms($query);
	$q = data_archive_get_search_url($search);

	if (
		is_not_empty($query_in_url)
	&&	!is_url_equivalent($q, $query_in_url)
	) {
		exit_redirect(ROOTPRFX.encode_URL_parts($qpath).($q ? "?$q" : ''));
	}

//* archive threads list ------------------------------------------------------

	if (
		$room
		? ARCH_FULL_THREADS_AT_ROOM_VIEW
		: ARCH_FULL_THREADS_AT_ROOM_LIST
	) {
		data_archive_ready_go(true);
	}

	if ($room && ($thread_count = data_get_count(COUNT_ARCH))) {
		exit_if_not_mod(data_get_mtime(COUNT_ARCH));

		if (!$search) {
			if ($a = abs($room_type['arch_pages'] ?: 0)) {
				$a *= TRD_PER_PAGE;
				$start = max(0, $thread_count - $a);
			} else {
				$start = 0;
			}

			$page['content'] = '
images = '.DIR_THUMB.'
image_ext = '.THUMB_EXT.'
page_ext = '.PAGE_EXT.'
on_page = '.($a ? "$a
start = $start" : ($u_trd_per_page ?: TRD_PER_PAGE)).'
total = '.$thread_count.($u_key?'':'
last = <a href="'.$thread_count.'.htm">'.$thread_count.'</a><!-- static link for scriptless bots -->');
			$p = $start+1;
			$n = $thread_count;
			$page['links'] = array(
				'prev' => ($p > 0 ? $p.PAGE_EXT : '')
			,	'next' => ($n > 0 ? $n.PAGE_EXT : '')
			);
			$page['data']['content']['type'] = 'archive pages';
		}
	} else

//* archive rooms list --------------------------------------------------------

	if ($visible = data_archive_get_visible_rooms($r_type)) {
		exit_if_not_mod($visible['last']);

		if (!$search) {
			if ($c = !$u_opts['count']) {
				$page['content'] = (
					NL
					.get_localized_text('arch_last')
					.TAB
					.get_localized_text('arch_count')
				);
			}

			$prev_type = false;

			foreach ($visible['list'] as $room => $n) {
				$room_type = get_room_type($room);
				$a = mb_split_filter($room);
				$room = array_pop($a);

				if (!$r_type && ($prev_type !== ($type = implode('/', $a)))) {
					if (false !== ($k = array_search($type, $cfg_game_type_dir))) {
						$page['content'] .= "

type = $type
type_title = ".get_localized_text('room_types_title', $k);
					}
					$prev_type = $type;
				}

				if ($v = $room_type['arch_pages']) {
					$n['count'] .= '*'.$v;
				}

				$page['content'] .= NL.(
					$c
					? "$n[last]	$n[count]	$room"
					: NB.TAB.NB.TAB.$room
				);
			}

			$room = '';
			$page['data']['content']['type'] = 'archive rooms';
		}
	} else $search = 0;

//* archive posts search ------------------------------------------------------

	if (is_array($search)) {
		$t = array(
			'r' => sprintf(
				sprintf(
					get_localized_text('regex_hint')
				,	HINT_REGEX_LINK
				,	HINT_REGEX_FORMAT
				)
			,	LANG
			,	get_localized_text('regex_hint_pat')
			)
		);

		if (!($search || $room)) {
			$t[''] = get_localized_text('archive_hint');
		}

		$page['task'] = get_template_form(
			array(
				'head'		=> get_localized_text('archive')
			,	'submit'	=> get_localized_text('archive_find')
			,	'select'	=> get_localized_text_array('archive_find_by')
			,	'hint'		=> $t
			,	'min'		=> FIND_MIN_LENGTH
			)
		);

		if ($search) {
			$research = '';

			foreach ($search as $k => $v) {
				$t = get_localized_text_array('archive_find_by', $k);
				$t = $t['found_by'] ?: $t['found by'] ?: $t['select'];

				$v = data_archive_get_search_value($v);

				$research .= (
					($research?',':'')
				.	NL
				.	'<a name="'.$k.'">'
				.		($t ? "$t: " : '')
				.		'<span>'
				.			htmlspecialchars($v, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5)
				.		'</span>'
				.	'</a>'
				);
			}

			$page['task'] .= '
<p class="hint" id="research">'.indent(get_localized_text('archive_found').$research).'</p>';

			if ($found = data_archive_find_by($search)) {
				$t = '';

				foreach ($found as $r_i => $threads) {
					if (!$room) {
						$t .= ($t ? NL.NL : NL)."room = $r_i";
					}

					foreach ($threads as $t_i => $posts) {
						$t .= NL."t = $t_i";

						foreach ($posts as $v) {
							if (isset($v['meta'])) {
								$v['post'] = get_post_pic_to_display($v['post']);
							}

							$t .= NL.implode('	', $v);
						}
					}
				}

				$page['content'] = "
arch_term_name = ".ARG_POST_USER_NAME_FULL."
archives = $arch_list_href
profiles = ".ROOTPRFX.DIR_USER."
page_ext = ".PAGE_EXT.get_flag_vars(
					array(
						'flags' => array(
							'ac', array(
								1
							,	!$u_opts['count']
							)
						)
					,	'caps' => 0
					)
				).$t;

				$page['data']['content']['type'] = 'archive found';
			}
		}

		$page['js'][0]++;
	}
	if (!$page['content']) {
		$page['task'] .= get_localized_text('empty');
	}
} else

//* draw test -----------------------------------------------------------------

if ($draw_test = (($qd_opts || !$qdir) && GET_Q)) {
	$qd_opts = 2;

	if ($draw_app = get_draw_app_list(false)) {
		$draw_test = $draw_app['name'];

		$page['icon'] = (
			$draw_app['path_prefix'] ?:
			$draw_app['path'] ?:
			$draw_app['name']
		);

		$page['task'] = '
<p>'.get_localized_text('draw_free').':</p>
<p class="hint">'.get_localized_text('draw_hint').'</p>'.$draw_app['noscript'];

		$page['subtask'] = $draw_app['embed'].'
<div class="task">'.indent($draw_app['list']).'</div>';
	} else {
		$page['task'] = get_localized_text('empty');
	}
} else

if ($u_key) {

//* options -------------------------------------------------------------------

	if ($qd_opts) {
		$page['data']['content']['type'] = 'options';
		$so = '|';
		$sp = ';';
		$t = ':	';
		$c = array();

//* personal info -------------------------------------------------------------

		$u_profile = data_get_user_profile($u_num);
		$u_email = ($u_profile['email'] ?? array());
		$u_flag_text = '';

		if ($u_flag) {
			$u_flag_lines = array();
			$u_flag_lines_with_time = array();

			asort($u_flag, SORT_NATURAL);

			foreach($u_flag as $flag => $time) {
				if ($flag === $time) {
					$u_flag_lines[] = '<span>'.$flag.'</span>';
				} else {
					$u_flag_lines_with_time[] = '<span>'.$flag.' &mdash; '.get_time_html(intval($time)).'</span>';
				}
			}

			$u_flag_text = (
				NL
				.get_localized_text('options_flags')
				.$t
				.implode('<br>', array_merge($u_flag_lines, $u_flag_lines_with_time))
			);
		}

		$a = '" onChange="allowApply(\'user\')">';

		$c['user'] =
NL.get_localized_text('options_name').$t.(
	$u_profile
	? '<a href="'.ROOTPRFX.DIR_USER.$u_num.'">'
	.	preg_replace('~\s+~u', '&nbsp;', $u_name)
	.'</a>'
	: $u_name
).$u_flag_text
.NL.get_localized_text('options_qk').$t.'<div class="half-hidden" title="'.get_localized_text('options_qk_hint').'">'.$u_key.'</div>'
.NL.get_localized_text('options_email').',<label><input type="checkbox"'
.(
	$u_email['show'] ? ' checked' : ''
).' name="email_show'.$a.get_localized_text('options_email_show').':</label>	<input type="text" name="email" id="email" value="'
.(
	$u_email['addr'] ?: ''
).(
	$u_email['verified'] ? '" data-verified="1' : ''
).'" pattern="'.PAT_EMAIL_FORMAT.'" placeholder="'
.get_localized_text('options_email_hint').'" title="'
.get_localized_text('options_email_hint').$a
.NL
.get_localized_text('options_self_intro').$t.'<textarea name="about" placeholder="'
.get_localized_text('options_self_intro_hint').'" title="'
.get_localized_text('options_self_intro_hint').$a
.(
	get_template_profile_text($u_profile['about']) ?: ''
).'</textarea>	&nbsp;';

//* site view options ---------------------------------------------------------

		$d = '';
		$draw_app_names = array_map('get_draw_app_name', $cfg_draw_app);
		$draw_app_labels = array_map('get_draw_app_label', $draw_app_names);
		$draw_app = implode($sp, array(
			(array_search($u_draw_app, $draw_app_names) ?: 0)
		,	implode($so, $draw_app_labels)
		,	implode($so, $draw_app_names)
		,	DRAW_APP_NONE
		,	'?'.ARG_DRAW_APP.'=*'
		));

		foreach (get_localized_text_array('options_input') as $i => $o) {
			if (
				!$u_flag['god']
			&&	$i === 'admin'
			) {
				continue;
			}

			foreach ($o as $k => $line) {
				$r = get_abbr($k).'='.(
					$i === 'input'
					? (
						$$k ?: (
							!$qdir && $k == 'room_default'
							? (${"u_$k"} ?: get_const($k))
							: ${"u_$k"}
						).$so.get_const($k)
					) : ($u_opts[$k]?1:'')
				);

				if ($i === 'admin') {
					$line = '<span class="gloom">'.$line.'</span>';
				}

				$d .= NL.$line.$t.$r;
			}
		}

		$c['view'] = $d
.NL.get_localized_text('options_time').$t.date('e, T, P')
.NL.get_localized_text('options_time_client').$t.'<time id="time-zone"></time>';

//* archive downloader --------------------------------------------------------

		if (ARCH_DL_ENABLED) {
			$a = $b = '';
			$j = ARG_NAMING_VAR_PREFIX;
			$s = ARCH_DL_NAME_PART_SEPARATOR;
			foreach (get_localized_text_array('archiver_naming_parts') as $k => $v) {
				$a .= ($a ? ', ' : '')."$j$k".($v ? " = $v" : '');
				if ($k === 'i') $b .= "$j$k";
				else if ($k === 'width' || $k === 'bytes') $b .= "<$j$k";
				else if ($k === 'height') $b .= " x $j$k$s>";
				else if ($k === 'fbytes') $b .= " B, $j$k$s>";
				else $b .= "<$j$k$s>";
			}
			$archiver_naming_hint = $a;
			$archiver_naming_default = $b;
			$a = '<input type="checkbox" name="';
			$d = '<label>';
			$b = '</label>';
			$i = ' onChange="enableFirstIfNone(\'';
			$j = '\', this)">';

			$c['arch'] = '
arch_dl_list_ext = '.ARCH_DL_LIST_EXT.'
arch_dl_path = '.ROOTPRFX.DIR_ARCH_DL
.NL
.$d.$a.'by_user_id"'.$i.'by_user_names'.$j.get_localized_text('archiver_by_user_id').$b.'	'
.$d.get_localized_text('archiver_from_arch').$a.'from_arch" checked'.$i.'from_room'.$j.$b
.NL
.$d.$a.'by_user_names" checked'.$i.'by_user_id'.$j.get_localized_text('archiver_by_user_names').':'.$b.'	'
.$d.get_localized_text('archiver_from_room').$a.'from_room" checked'.$i.'from_arch'.$j.$b
.NL
.'<textarea name="names" placeholder="'
.get_localized_text('archiver_by_user_names_hint').'" title="'
.get_localized_text('archiver_by_user_names_hint').'">'.$u_name.'</textarea>	&nbsp;'
.NL
.get_localized_text('archiver_naming').':<br>'
.'<input type="text" name="naming" placeholder="'
.$archiver_naming_hint.'" title="'
.$archiver_naming_hint.'" value="'.$archiver_naming_default.'">	&nbsp;
|<input type="submit" name="'.LK_ARCH_DL.'" value="'
.get_localized_text('archiver_button').'" onClick="return prepareArchiveDownload(this)">';
		}

//* manage save data, log off, etc --------------------------------------------

		$i = '
|<input type="submit" value="';
		$j = '
-
|<input type="button" value="';
		$d = '';
		$prefixes_to_keep = DRAW_PERSISTENT_PREFIX;
		$prefixes_to_del = implode(',', array_merge(
			[NAMEPRFX, DRAW_BACKUPCOPY_PREFIX]
		,	array_filter($cfg_draw_app, 'is_not_draw_none')
		));

		foreach (array(
			'save'	=> array($j, 'id="unsave'
				.'" data-prefixes-to-keep="'.$prefixes_to_keep
				.'" data-prefixes-to-delete="'.$prefixes_to_del
			)
		,	'pref'	=> array($i, 'name="'.OPT_PRFX.'reset')
		,	'skip'	=> array($j, 'id="unskip')
		,	'out'	=> array($i, 'name="quit')
		) as $k => $v) {
			$d .= $v[0].get_localized_text('options_drop', $k).'" '.$v[1].'">';
		}

		$c['save'] = $d;

//* compile sections ----------------------------------------------------------

		if (!$qdir) {
			if (GOD && WS_NGINX) $page['content'] .= vsprintf('
||<b class="anno report">%s<br><a href=".?'.LK_MOD_ACT.'=files&'.LK_MOD_OPT.'=nginx">%s</a></b>', get_localized_text_array('options_warning'));
			$page['content'] .= '
||<b class="anno">'.sprintf(get_localized_text('options_first'), get_localized_text('options_apply'), $room_list_href).'</b>';
		}
		$a = '" class="apply" name="'.OPT_PRFX.'apply_';
		$j = ':
apply_change = ';
		$b = '<br>';
		$k = '
		';

		$page['task'] = '<p>'.get_localized_text('options').':</p>';
		$page['content'] .= '
opt_prefix = '.OPT_PRFX.'
separator = '.$so.'
sep_select = '.$sp.'
<form method="post">'
.($c['user'] ? $k.get_localized_text('options_area', 'user').$j.'user'.$c['user'].$i.get_localized_text('options_apply').$a.'user" disabled>'
//.NL.get_localized_text('options_profile').$t.'<a href="'.ROOTPRFX.DIR_USER.$u_num.'">'.get_localized_text('options_profile_link').'</a>'
.$k.$b : '')
.($c['view'] ? $k.get_localized_text('options_area', 'view').$j.'view'.$c['view'].$i.get_localized_text('options_apply').$a.'view"'.($qdir?' disabled':'').'>
</form><form method="post">'
.$k.$b : '')
.($c['arch'] ? $k.get_localized_text('options_area', 'arch').':'.$c['arch'].'
</form><form method="post">'
.$k.$b : '')
.($c['save'] ? $k.get_localized_text('options_area', 'save').':'.$c['save']
: '').'
</form>';
		$page['js'][0]++;
	} else

//* rooms ---------------------------------------------------------------------

	if ($qd_room) {
		if (
			$room
			? ARCH_FULL_THREADS_AT_ROOM_VIEW
			: ARCH_FULL_THREADS_AT_ROOM_LIST
		) {
			data_archive_ready_go(true);
		}

		if ($room) {
			if (!MOD && FROZEN_HELL) {
				$page['task'] = (
					get_localized_or_empty_text('stop_room')
				?:	get_localized_text('stop_all')
				);

				goto template;
			}

//* task manipulation, implies that $room is set and fixed already ------------

			if (is_not_empty($v = $query['report_post'])) $etc = $v; else
			if (is_not_empty($v = $query['skip_thread'])) $etc = '-'.abs(intval($v)); else
			if (is_not_empty($v = $query['check_task'])) {
				if ($v == 'keep' || $v == 'prolong') $etc = '-'; else
				if ($v == 'post' || $v == 'sending') $etc = '--'; else
				if ($v == 'save' || $v == 'preserve') $etc = '---';
			}

			if ($etc) {
				if ($etc[0] == '-') {

			//* skip current task (obsolete way by GET):

					if (strlen($t = trim($etc, '-')) > 0) {
						$add_qk = get_room_skip_list($t);
						$post_status = 'skip';

						goto after_posting;
					} else {

			//* keep current task until changed manually:

						if (strlen($etc) === 3) {
							$t = data_check_my_task(ARG_KEEP);
						} else {

			//* check current task and prolong deadline:

							$sending = (strlen($etc) === 2);
							$t = data_check_my_task();
						}

			//* show current task (simple page content for AJAX):

						die(
							'<!--'.date(CONTENT_DATETIME_FORMAT, T0).'-->'
							.NL.'<meta charset="'.ENC.'">'
							.NL.'<title'.get_template_attr(array('deadline' => $target['deadline'])).(
								is_array($t)
								? '>'.(
									$sending
									? get_localized_text('sending')
									: get_localized_text('target_status', $t[0]).(
										!$u_opts['task_timer']
										? ''
										: (
											'. '
											.get_localized_text('time_limit')
											.': '
											.format_time_units($t[1])
										)
									)
								)
								: (
									$sending
									? ' id="confirm-sending"'
									: ''
								).'>'.get_localized_text('target_status', $t)
							).'</title>'
							.NL.(
								($t = $target['task'])
							&&	$target['pic']
							&&	($a = get_post_pic_to_display($t, true))
								? '<img src="'.$a['src'].'" alt="'.$a['alt'].'">'
								: $t
							)
						);
					}
				}

//* report form ---------------------------------------------------------------

				$page['task'] = get_template_form(
					array(
						'method' =>	'post'
					,	'name' =>	'report'
					,	'min' =>	REPORT_MIN_LENGTH
					,	'max' =>	REPORT_MAX_LENGTH
					,	'textarea' =>	($is_report_page = 1)
				/*	,	'checkbox' =>	array(
							'name' => 'freeze'
						,	'label' => get_localized_text('report_freeze')
						)*/
					,	'radiogroup' => array(
							'name' => 'freeze'
						,	'options' => array(
								1 => get_localized_text('report_freeze')
							,	0 => get_localized_text('report_hotfix')
							)
						)
					)
				);
			} else {

//* active room task and visible content --------------------------------------

				$errors_in_query = ($query[ARG_ERROR] ?? '');
				$errors_after_POST = array_filter(mb_split_filter($errors_in_query, ARG_ERROR_SPLIT), 'is_post_error');

				$asked_to_keep_desc = isset($query[ARG_DESC]);
				$asked_to_keep_draw = !!array_filter($query, 'is_draw_arg', ARRAY_FILTER_USE_KEY);
				$asked_to_keep = isset($query[ARG_KEEP]);
				$asked_to_drop = isset($query[ARG_DROP]);
				$asked_to_change = isset($query[ARG_CHANGE]);

				$change_to = ($query[ARG_CHANGE] ?? '' ?: ARG_CHANGE);
				$asked_to_desc = (
					$change_to === ARG_DESC
				// ||	$asked_to_keep_desc
				);
				$asked_to_draw = (
					$change_to === ARG_DRAW
				// ||	$asked_to_keep_draw
				);

				$skip_threads = get_room_skip_list();
				$dont_change = (
					$errors_after_POST
				||	$query[LK_MOD_ACT_LOG]
				||	$asked_to_keep_desc
				||	$asked_to_keep_draw
				);

			//* deprecated way via GET request, may cause displaying wrong task in modern browsers due to double requests:

				$what_change = (
					false
				?:	($asked_to_keep ? ARG_KEEP : false)
				?:	($asked_to_drop ? ARG_DROP : false)
				?:	($asked_to_change ? ARG_CHANGE : false)
				// ?:	true
				);

				$what_change_to = (
					false
				?:	($asked_to_desc ? ARG_DESC : false)
				?:	($asked_to_draw ? ARG_DRAW : false)
				?:	($asked_to_change ? $change_to : false)
				);

				$task_changing_params = array(
					'dont_change' => $dont_change
				,	'what_change' => $what_change
				,	'what_change_to' => $what_change_to
				,	'skip_threads' => $skip_threads
				);

if (TIME_PARTS) time_check_point('inb4 aim lock'
	.', params = '.get_print_or_none($task_changing_params)
	.', errors_after_POST = '.get_print_or_none($errors_after_POST)
);

				data_aim($task_changing_params);

				$visible = data_get_visible_threads();

				data_unlock();

if (TIME_PARTS) time_check_point('got visible threads data, unlocked all'
	.', changes = '.get_print_or_none($visible['changes'])
	.', target = '.get_print_or_none($target)
);

				exit_if_not_mod(max($t = $target['time'], $visible['last']));

				$task_time = ($t ?: T0);	//* <- UTC seconds
				$x = 'trd_max';
				$t = '';

				if ($target['task']) {
					$t = '
check_task_post = .?check_task=post
check_task_keep = .?check_task=keep';
					$post_rel = '_reply';
					$draw = !$target['pic'];
					if (!$room_type['alternate_reply_type']) {
						if ($asked_to_desc) $draw = 0; else
						if ($asked_to_draw) $draw = 1;
					}
				} else {
					$post_rel = '_op';
					if (
				//		!in_array($x, $errors_after_POST)
						!($full_threads = data_get_full_threads(false))
					&&	data_is_thread_cap()
					) {
						$draw = -1;
				//		$query[ARG_ERROR] = ($errors_in_query ? $errors_in_query.ARG_ERROR_SPLIT : '').$x;
					} else
					if ($asked_to_desc || $asked_to_keep_desc) $draw = 0; else
					if ($asked_to_draw || $asked_to_keep_draw) $draw = 1;
				}

				if ($draw >= 0) {
					$post_type = ($draw?'image':'text').$post_rel;
					$other_type = ($draw?'text':'image').$post_rel;
					if (!$room_type["allow_$post_type"]) $draw = ($room_type["allow_$other_type"] ? !$draw : -1);
				}

				if ($vts = $visible['threads']) {
					if (MOD || !NO_MOD) $t = (
						MOD ? '
left = '.get_localized_text('mod_post_hint').'
right = '.get_localized_text('mod_user_hint')
						: '
left = '.get_localized_text('report_post_hint').'
right = '.get_localized_text('report_user_hint')
					)."
report_to = .?report_post=$t";
					$t .= "
arch_term_name = ".ARG_POST_USER_NAME_FULL."
archives = $arch_list_href
profiles = ".ROOTPRFX.DIR_USER."
images = ".ROOTPRFX.DIR_PICS;
					$t .= get_flag_vars(
						array(
							'flags' => array(
								'acgmnp', array(
									$u_opts['active']
								,	!$u_opts['count']
								,	GOD
								,	MOD
								,	NO_MOD
								,	PIC_SUB
								)
							)
						,	'caps' => 3
						)
					);
					$page['content'] = $t.NL;
					$a = array();
					$b = '<br>';
					$u_profiles = array();

					function u_profile_exists($u_num) {
						return (
							$u_profiles[$u_num] ?? (
							$u_profiles[$u_num] = data_get_user_profile($u_num, false)
							)
						);
					}

if (TIME_PARTS) time_check_point('inb4 vts -> tsv'.NL);
					foreach ($vts as $tid => $posts) {
						$tsv = '';
if (TIME_PARTS) $pi = $ri = $li = 0;
						foreach ($posts as $postnum => $post) {
							$uid_append = '';
							if ($t = $post['time']) {
								if ($u_opts['times']) {
									$l = mb_split($b, $t, 2);
									$l[0] = NB;
									$l = implode($b, $l);
								} else $l = $t;
							} else $l = NB;
							if ($t = $post['user']) {
								$r = mb_split($b, $t, 2);
								$uid = $r[0];
								if (!$u_opts['names'] && array_key_exists($uid, $usernames)) {
									if (u_profile_exists($uid)) {
										$uid_append .= '@';
									}
									$r[0] = $usernames[$uid];
								} else {
									$r[0] = NB;
								}
								$r = implode($b, $r);
							} else $r = NB;
							if ($t = $post['post']) {
								if ($post['pic']) $t = get_post_pic_to_display($t);
							} else $t = NB;
							$tabs = array(
								'color' => $u_opts['own']?0:$post['flag']
							,	'time' => $l
							,	'user' => $r
							,	'content' => $t
							);
							if ($t = $post['used']) {
								$tabs['used'] = $t;
if (TIME_PARTS) ++$pi;
							}
							if (GOD) {
								$uid_append .= '#';
								if ($t = $post['browser']) $tabs['browser'] = $t;
							}
							if ($uid_append) {
								$tabs['color'] .= $uid_append[0].$uid;
							}
							if (is_array($r = $visible['reports'][$tid][$postnum])) {
if (TIME_PARTS) $ri += count($r);
								foreach ($r as $k => $lines) {
if (TIME_PARTS) $li += count($lines);
									$k = 'reports_on_'.($k > 0?'user':'post');
									$v = '';
									foreach ($lines as $time => $line) $v .= ($v?'<br>':'').$time.': '.$line;
									if ($v) $tsv .= NL.$k.' = '.$v;
								}
							}
							$tsv .= NL.(
								$postnum > 0 || (!MOD && NO_MOD)
								? ''
								: end(explode('/', $tid)).','
							).implode('	', $tabs);
						}
						$a[$tid] = $tsv;
if (TIME_PARTS) time_check_point("done trd $tid, ".count($posts).' posts'.($pi?", $pi pics":'').($ri?", $li reports on $ri posts":''));
					}
					ksort($a);
					$page['content'] .= implode(NL, array_reverse($a));
if (TIME_PARTS) time_check_point('done sort + join');
					if (GOD) $filter = 1;
				} else if (MOD) {
					$left = (GOD || !NO_MOD?'v':'');
					$flags = get_flag_vars(array('flags' => array('vgmn', array(1, GOD, MOD, NO_MOD))));
					$page['content'] = "$t
left = ".get_localized_text('empty')."
right = ".get_localized_text('empty')."$flags

0,0	$left	v";	//* <- dummy thread for JS drop-down menus
				} else {
					$page['content'] = $t;
				}

				$task_content = $target['task'];

				if ($draw < 0) {
					$page['task'] = '
<p>'.indent(get_localized_text('room_thread_cap')).'</p>
<p class="hint">'.indent(get_template_hint(get_localized_text('room_thread_cap_hint'))).'</p>';
				} else {
					$final_task_notice = (
						$target['is_task_final']
						? get_localized_text('final_task_notice')
						: ''
					);

					if ($draw) {
						$n = get_draw_app_list(true);
						$head = (
							$target['pic']
							? get_localized_text('draw_next').':'
							: (
								$task_content
								? get_localized_text('draw_this').':
<span id="task-text">'.$task_content.'</span>'
								: get_localized_text('draw_free').':'
							)
						);
						$page['task'] = '
<p>'.indent($head).'</p>';
						$hint = $n['list'];

						if ($final_task_notice) {
							$hint .= '
<p class="hint">'.indent($final_task_notice).'</p>';
						}

						if ($x = $n['noscript']) {
							$page['task'] .= $x;
							$page['subtask'] = $n['embed'].'
<div class="task">'.indent($hint).'</div>';
						} else {
							$w = explode(',', DRAW_LIMIT_WIDTH);
							$h = explode(',', DRAW_LIMIT_HEIGHT);
							$limit_hint = sprintf(
								get_localized_text('draw_limit_hint')
							,	$w[0], $h[0]
							,	$w[1], $h[1]
							,	DRAW_MAX_FILESIZE
							,	format_filesize(DRAW_MAX_FILESIZE)
							,	mb_strtoupper(implode(', ', $cfg_draw_file_types))
							);
							$draw = $n['embed'].'
<p class="hint">'.indent($limit_hint).'</p>'.$hint;
							if ($target['pic']) {
								$page['subtask'] = '
<div class="task">'.indent('<p></p>'.NL.$draw).'</div>';
							} else {
								$page['task'] .= $draw;
							}
						}
					} else {
						$head = (
							$task_content
							? (
								$target['pic']
								? get_localized_text('describe_this')
								: get_localized_text('describe_next').':
<span id="task-text">'.$task_content.'</span>'
							)
							: (
								$room_type['single_active_thread']
							||	!$room_type['allow_image_reply']
								? get_localized_text('describe_free')
								: get_localized_text('describe_new')
							)
						);
						$page['task'] = get_template_form(
							array(
								'method' =>	'post'
							,	'name' =>	'describe'
							,	'min' =>	DESCRIBE_MIN_LENGTH
							,	'max' =>	DESCRIBE_MAX_LENGTH
							,	'head' =>	$head
							,	'hint' =>	[
									get_localized_text('describe_hint')
									.(
										$u_flag['nop']
										? '\\'.get_localized_text('no_play_hint')
										: ''
									)
								,	$final_task_notice
								]
							,	'filter' =>	$filter
							,	'checkbox' => (
									$u_opts['kbox']
									? array(
										'label' => get_localized_text('check_required')
									,	'required' => 1
									)
									: ''
								)
							)
						);
					}
					if (!$u_flag['nop']) {
						if (!$u_opts['task_timer']) $page['data']['task']['autoupdate'] = TARGET_AUTOUPDATE_INTERVAL;
						if ($task_content) $page['data']['task']['taken'] = T0;
					}
					if ($task_content) {
						if ($target['pic']) {
							$a = get_post_pic_to_display($task_content, true);
							$page['subtask'] = '
<img src="'.$a['src'].'" alt="'.$a['alt'].'" id="task-img">'.$page['subtask'];
						}
						if ($room_type['lock_taken_task']) {
							$page['data']['task']['taken'] = $task_time;
							$page['data']['task']['deadline'] = $target['deadline'];
						}
						$a = array();
						if ($room_type['allow_text_op']) $a[] = ARG_DESC;
						if ($room_type['allow_image_op']) $a[] = ARG_DRAW;
						if ($a && ($full_threads ?? data_get_full_threads(false))) {
							$page['data']['task'][ARG_DROP] = implode(',', $a);
						}
					} else {
						$post_type = ($draw?'text':'image');
						if ($room_type["allow_$post_type$post_rel"]) {
							$page['data']['task']['free'] = ($draw ? ARG_DESC : ARG_DRAW);
						}
					}
				}
				if ($free_task_counts = $target['free_task_counts']) {
					$free_task_types = array_keys(array_filter($free_task_counts));

					sort($free_task_types);

					$page['data']['task'][ARG_CHANGE] = implode(',', $free_task_types);
				}
				if ($task_content) {
					$page['data']['task'][ARG_SKIP] = $thread_num = intval($target['thread']);
					$page['data']['task'][ARG_KEEP] = ($target['keep'] ? 'on' : $thread_num);

					if (!NO_MOD) {
						$post_num = intval($target['posts']) ?: 1;
						$page['data']['task'][ARG_REPORT] = "$thread_num-$post_num-0";
					}
				}
				if ($skip_threads_count = count($skip_threads)) {
					$page['data']['task'][ARG_UNSKIP] = $skip_threads_count;
				}
				if ($page['content']) {
					$page['data']['content']['type'] = 'threads';
					if (MOD) $page['js']['mod']++;
				}
				$page['js'][0]++;
			}
		} else {

//* active rooms list ---------------------------------------------------------

			if ($visible = data_get_visible_rooms($r_type)) {
				exit_if_not_mod($visible['last']);

				$prev_type = false;
				$y = ($r_type?"$r_type/":'');
				$t = !$u_opts['times'];
				$c = !$u_opts['count'];
				$s = ', ';
				$top = (
					!$c
					? ''
					: NL.get_localized_text('room_count_threads')
					.TAB.get_localized_text('room_count_posts')
				);

				$page['content'] = "
archives = $arch_list_href$y
separator = \"$s\"
$top";
				foreach ($visible['list'] as $room => $n) {
					$room_type = get_room_type($room);
					$a = mb_split_filter($room);
					$mid = array_pop($a);
					if (!$y && ($prev_type !== ($type = implode('/', $a)))) {
						if (false !== ($k = array_search($type, $cfg_game_type_dir))) {
							$page['content'] .= "

type = $type
type_title = ".get_localized_text('room_types_title', $k).$top;
						}
						$prev_type = $type;
					}
					if (!$type && $y) $room = $y.$room;
					if ($u_flag["mod_$room"]) $mid .= "$s(mod)";
					if ($u_room_default === $room) $mid .= "$s(home)";
					if ($a = $n['marked']) foreach ($a as $k => $v) $mid .= "$s$v$k[0]";
					if ($c) {
						$left = $n['threads now'].$s.$n['threads ever'];
						if ($v = $n['threads arch']) {
							$left .= (
								$s.$v
							.	(($v = $room_type['arch_pages']) ? '*'.$v : '')
							.	($t ? $s.$n['last arch'] : '')
							);
						}
						$right = $n['pics'].$s.$n['desc'].($t ? $s.$n['last post'] : '');
					} else {
						$left = ($n['threads arch']?'*':NB);
						$right = NB;
					}
					$page['content'] .= "
$left	$right	$mid";

				//* announce/frozen:

					if ($a = $n['anno']) {
						if (!$a['room_anno']) {
							$page['content'] .= TAB;
						}

						foreach ($a as $k => $v) {
							$page['content'] .= (
								TAB
								.get_localized_text('announce', $k)
								.': '
								.trim(
									preg_replace('~\s+~u', ' ',
									preg_replace('~<[^>]*>~', '',
									preg_replace('~<br[ /]*>~i', NL,
								$v))))
							);
						}
					}
				}
				unset($room);
			}
			$t = array();
			$a = '{';
			$b = ($c = '}').NL;
			$x = get_localized_or_empty_text('room_types_name_example') ?: 'test';
			$e = ", $x: ";
			foreach ($cfg_room_types as $k => $v) if ($r = get_localized_or_empty_text('room_types_names', $k)) {
				$n = $v['name_example'] ?: $x;
				if ($i =
					$v['if_name_length']
				?:	$v['if_name_length_min']
				?:	$v['if_name_length_max']
				) {
					while (mb_strlen($n) < $i) $n .= $n;
					$n = mb_substr($n, 0, $i);
				}
				if ($i = $v['if_name_prefix']) $n = $i.$n;
				if ($i = $v['if_game_type']) $n = "$i/$n";
				$t[0] .= "$r$e$a$room_list_href$n/|$n$b";
			}
			$j = '`filterPrefix(\'';
			$l = '\')';
			$m = '}\\'.NL;
			$r = get_localized_or_empty_text('room_types_title', 'all') ?: '*';
			$f = ($r_type ? '' : get_localized_text('room_types_select').":\\
$a#$j$l|$r$m");
			foreach (get_localized_text_array('room_types') as $k => $v)
			if ($r = get_localized_or_empty_text('room_types_title', $k)) {
				if ($i = $cfg_game_type_dir[$k]) {
					$k = "$i/";
					$n = get_room_type("$i/$x", 'name_example') ?: $x;
					$n = "$i/$n";
				} else {
					$n = get_room_type($x, 'name_example') ?: $x;
					$k = ($r_type ? "#$i/" : "#$i/$j$i/$l");
				}
				$t[1] .= "$a$room_list_href$k|$r$c: $v$e$a$room_list_href$n/|$n$b";
				if ($f) $f .= "$a#$i/$j$i/$l|$r$m";
			}

			if ($t) {
				foreach ($t as &$v) if ($v) $v = '[p|'.indent($v).']';
				unset($v);
				$t = trim(implode('', $t)).'\\';
				$rooms_hint = get_localized_text('rooms_hint').($f ? '\\
[buttons r|\\'.indent($f).']' : '').'\\
[a|'.get_localized_text('room_types_hint').']\\
[hid|'.indent($t).']';
			}

			$page['task'] = get_template_form(
				array(
					'method' =>	'post'
				,	'name' =>	$qredir
				,	'hint' =>	$rooms_hint
				,	'min' =>	ROOM_NAME_MIN_LENGTH
				,	'filter' =>	'/'
				)
			);

			$page['data']['content']['type'] = 'rooms';
			$page['js'][0]++;
		}
	} else

//* home page substitute ------------------------------------------------------

	if (!$room) {
		$room = (strlen(trim($u_room_default, '.')) ? "$u_room_default/" : '');

		exit_redirect($room_list_href.$room, 'home room');
	}
} else {

//* not registered ------------------------------------------------------------

	if ($etc) {
		die('x');
	}

	foreach ($cfg_dir as $k => $v) {
		unset(${"qd_$k"});
	}

	$page['signup'] = true;
	$page['welcome'] = get_localized_text_array('welcome_parts');
	$page['task'] = get_template_form(
		array(
			'method' =>	'post'
		,	'name' =>	ME
		,	'min' =>	USER_NAME_MIN_LENGTH
		)
	);
}

//* generate page, put content into template ----------------------------------

template:

define('S', '. ');

$room_title = (
	$room_name == ROOM_DEFAULT
	? get_localized_text('room_default')
	: get_localized_text('room')." $room_name"
);

$page['title'] = (
	$mod_title
	? get_localized_text('mod_panel')." - $mod_title".S.(
		$ymd && $room
		? $room_title.S
		: ''
	)
	: (
		$qd_opts == 1
		? get_localized_text('options').S
		: (
			$qd_arch
			? (
				$room
				? $room_title.S
				: ''
			).get_localized_text('archive').S
			: (
				$qd_room
				? (
					$room
					? (
						$is_report_page
						? get_localized_text('report').S
						: ''
					).$room_title.S
					: get_localized_text('rooms').S
				)
				: ''
			)
		)
	)
).(
	$qd_user
	? get_localized_text('user').": $name".S
	: ''
).$top_title.(
	$qd_opts == 2
	? S.get_localized_text('draw_app_select')
	: ''
);

if (!$u_key) {
	$page['meta'] = array(
		'og:type' => 'website'
	,	'og:url' => $cfg_link_canonical_base.ltrim($_SERVER['REQUEST_URI'],  './')
	,	'og:image' => $cfg_meta_image
	,	'og:title' => $page['title']
	,	'og:site_name' => $top_title
	);
}

if (!$is_report_page) {
	$index_link_url = (SERVE_AS_INDEX_PAGE ? ROOTPRFX : $_SERVER['PHP_SELF']);

	define('A', NL.'<a href="');
	define('AB', '</a>');
	define('CHK_ON', '&#x2611; ');
	define('CHK_OFF', '&#x2610; ');

	$short = !!$u_opts['head'];

	$a_head = array(
		'/' => $top_title
	,	'..' => get_localized_text('rooms')
	,	'.' => $room_title
	,	'a' => get_localized_text('archive')
	,	'*' => get_localized_text('archives')
	,	'?' => get_localized_text('options')
	,	'~' => get_localized_text('draw_test')
	,	'#' => get_localized_text('mod_panel')
	);

	foreach ($a_head as $k => &$v) $v = '">'.(
		$short
		? $k
		: $v.(mb_substr($v, -1) == '.'?'':'.')
	).'</a>';

	unset($v);

	if (MOD && is_not_empty($t = $query[LK_MOD_ACT_LOG])) {
		$page['mod_act_log'] = $t;
	}

	if (GOD) {
		define('M', A.'.?'.LK_MOD_ACT);

		foreach (get_localized_text_array('mod_pages') as $k => $v) {
			$mod_list .= M.'='.$k.'">'.$v.AB;
		}

		$mod_link_menu = get_template_menu(M.$a_head['#'], $mod_list);
	}

	$a = (array)$cfg_link_schemes;

	if ($s = strtolower($_SERVER['REQUEST_SCHEME'] ?? $_SERVER['HTTPS'] ?? $a[0])) {
		if ($s === 'off') $s = 'http'; else
		if ($s === 'on') $s = 'https';
	}

	$is_room_list = ($qd_room && !$room);

	$r = ($room?"$room/":'');
	$t = ($r_type?"$r_type/":'');
	$this_href = ($r && $t?'../..':($r || $t?'..':'.'));
	$room_list_link = A.(DIR_DOTS && $qd_room ? $this_href : $room_list_href).$a_head['..'];
	$arch_list_link = (
		$qd_arch || is_dir(DIR_ARCH)
		? A.(DIR_DOTS && $qd_arch ? $this_href : $arch_list_href).$a_head['*']
		: ''
	);

	if ($room) {
		$room_link = A.(DIR_DOTS && $qd_room ? '.' : "$room_list_href$r").$a_head['.'];
		$arch_link = (
			$qd_arch || is_dir(DIR_ARCH.$room)
			? A.(DIR_DOTS && $qd_arch ? '.' : "$arch_list_href$r").$a_head['a']
			: ''
		);
	}

	if ($a_head['/']) {
		$i_a = array();

		if (!SERVE_AS_INDEX_PAGE) {
			$i_a[] = [
				A.ROOTPRFX.'">'
			.	get_localized_text('header_main_page')
			.	AB
			];
		}

//* lang selection:

		$i_b = array();

		foreach ($cfg_langs as $v) {
			$i_b[] = (
				A.ROOTPRFX.'?lang='.$v.'">'
			.	($v === LANG ? CHK_ON : CHK_OFF)
			.	strtoupper($v)
			.	AB
			);
		}

		if ($i_b) $i_a[] = $i_b;

//* HTTP(S) selection:

		$i_b = array();

		foreach ($cfg_link_schemes as $v) {
			$i_b[] = (
				A."$v://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]\">"
			.	($v === $s ? CHK_ON : CHK_OFF)
			.	strtoupper($v)
			.	AB
			);
		}

		if ($i_b) $i_a[] = $i_b;

//* room type selection:

		$i_b = array();

		if ($u_key) foreach ($cfg_game_type_dir as $k => $v) {
			$i_b[] = (
				A
			.	($is_room_list ? '' : $room_list_href)
			.	'#'.$v.'/"'
			.	($is_room_list ? ' onclick="filterPrefix(\''.$v.'/\')"' : '')
			.	'>'
			.	(get_localized_or_empty_text('room_types_title', $k) ?: $v ?: $k)
			.	AB
			);
		}

		if ($i_b) $i_a[] = $i_b;

//* other links:

		$i_b = array();

		foreach ($cfg_header_links as $k => $v) {
			$i_b[] = (
				A.$v.'">'
			.	get_localized_text('header_links', $k)
			.	AB
			);
		}

		if ($i_b) $i_a[] = $i_b;

//* compile menu blocks:

		if ($index_list = array_map('implode', $i_a)) {
			$index_link_menu = get_template_menu(A.$index_link_url.$a_head['/'], $index_list);
		}
	}

	$page['header'] = (
		$u_key
		? array(
			($index_link_menu ?: A.$index_link_url.$a_head['/'])
		.	($short?$room_list_link:'')
		.	$room_link
		.	($short?'':$arch_link)
		, 'ar' =>
			$mod_link_menu
		.	($short?$arch_link:'')
		.	$arch_list_link
		.	($short?'':$room_list_link)
		.	A.(DIR_DOTS && $qdir && $qd_opts?'.':ROOTPRFX.DIR_OPTS.($r ?: $t)).$a_head['?']
		)
		: array(
			($index_link_menu ?: A.$index_link_url.$a_head['/'])
		.	A.ROOTPRFX.'?draw_test'.$a_head['~']
		, 'ar' => (
				is_dir(DIR_ARCH)
				? A.ROOTPRFX.DIR_ARCH.$a_head['*']
				: ''
			)
		)
	);

	$footer = $links = $took = '';

	if (!$u_opts['names'] && defined('FOOT_NOTE')) {
		$links = vsprintf(FOOT_NOTE, get_localized_text_array('foot_notes'));
	}

	if (!$u_opts['times'] && $u_key) {
		define('TOOK', $took = '<!--?-->');

		if (TIME_PARTS) {
			time_check_point('inb4 template');
			$took = '<a href="javascript:'.(++$page['js'][0]).',toggleHide(took),took.scrollIntoView()">'.$took.'</a>';
		}

		$took = get_time_html().mb_str_replace_first(' ', NL, sprintf(get_localized_text('took'), $took));
	}

	foreach (array(
		'l' => $took
	,	'r' => $links
	) as $k => $v) if (strlen($v)) {
		$footer .= NL.'<span class="'.$k.'">'.indent($v).'</span>';
	}

	if ($footer) {
		$page['footer'] = '<p class="hint">'.indent($footer).'</p>';
	}

	$page['anno'] = 1;
}

if (is_not_empty($v = $query[ARG_ERROR])) {
	$page['report'] = $v;
}

die(get_template_page($page));




//* POST: add/modify content *-------------------------------------------------

posting:

ob_start();

if ($u_key) {
	$post_status = (
		$_POST[ME]
	||	$_POST[ARG_UNSKIP]
	||	$_POST[$qredir]
		? 'OK'
		: 'unkn_req'
	);

	if (isset($_POST[$qredir])) goto after_posting;

//* options change ------------------------------------------------------------

	if (isset($_POST[$p = 'quit'])) {
		$post_status = 'user_quit';
		$u_key = $p;
	} else
	if ($p = array_filter($_POST, 'is_opt_arg', ARRAY_FILTER_USE_KEY)) {
		$post_status = 'user_opt';

//* user-side settings --------------------------------------------------------

		if (isset($p[OPT_PRFX.'reset'])) {
			$u_opts = 'default';
		} else {
			if (isset($p[OPT_PRFX.'apply_view'])) {
				foreach ($cfg_opts_order as $i => $o)
				foreach ($o as $k) {
					$v = (isset($p[$j = OPT_PRFX.get_abbr($k)]) ? $p[$j] : '');

					if ($i === 'input') {
						${"u_$k"} = $v;
					} else {
						$u_opts[$k] = $v;
					}
				}
			}

//* server-side user profile content ------------------------------------------

			if (isset($p[OPT_PRFX.'apply_user'])) {
				$old = data_get_user_profile($u_num);
				$new = array();

				if ($t = $_POST['email'] ?: '') {
				//	$t = filter_var($t, FILTER_SANITIZE_EMAIL);
					$pat = '~'.PAT_EMAIL_FORMAT.'~u';

					if (
						preg_match($pat, $t, $match)
					&&	$t = trim(mb_strtolower(fix_encoding($match[1])))
					) {
						$was = ($old['email'] ?? array());
						$new['email'] = array_filter(array(
							'addr' => $t
						,	'show' => (
								$_POST['email_show']
								? 'show'
								: ''
							)
						,	'verified' => (
								$was['verified']
							&&	$was['addr'] === $t
								? 'verified'
								: ''
							)
						));
					}
				}

				if ($t = $_POST['about'] ?: '') {

					//* optimize text blob for storage.
					//* links and other templates can better be added later dynamically.

					$t = fix_encoding($t);
					$t = trim($t);
					$t = preg_replace('~\r\n|\v~u', NL, $t);	//* <- normalize line breaks
					$t = preg_replace('~\v{3,}~u', NL.NL, $t);	//* <- 1 max empty line
					$t = htmlspecialchars($t);			//* <- no HTML code allowed
					$t = nl2br($t, false);				//* <- prepare HTML line breaks for display
					$t = preg_replace('~\v+~u', '', $t);		//* <- collapse into one line for simpler storage
					$t = preg_replace('~\s+~u', ' ', $t);		//* <- normalize any other whitespace, same for display
					$t = preg_replace("~\bhttps?://+$_SERVER[SERVER_NAME](/+\.+)*/*~u", RELATIVE_LINK_PREFIX, $t);
					if ($t = trim($t)) {
						$new['about'] = $t;
					}
				}
				$u_profile = data_save_user_profile($new);
			}
		}
	} else

//* admin/mod actions ---------------------------------------------------------

	if (isset($_POST['mod'])) {
		if (
			MOD
		&&	(
				($qd_room && $room)
			||	(
					GOD
				&&	(
						$etc === '3'
					||	(
							($q = $query[LK_MOD_ACT] ?? '')
						&&	(
								($q === LK_USERLIST)
							||	is_prefix($q, 'user')
							)
						)
					)
				)
			)
		) {
			$d = ord('a');

			foreach ($_POST as $numbers => $text)
			if (preg_match('~^m\d+_(\d+)_(\d+)_(\d+)$~i', $numbers, $match)) {
				$match[0] = $text;
				$k = chr($d + substr_count($text, '+'));
				$k = str_replace_first('_', "-$k-", $numbers);
				$act[$k] = $match;
			}

			if ($act) {
				krsort($act, SORT_NATURAL);		//* <- SORT_NATURAL - since php v5.4.0 only

				$done = 0;
				$failed = 0;
				$result = array();

				data_lock(LK_MOD_ACT);
				data_lock(LK_ROOM.$room);

				foreach ($act as $v) {
					$m = data_mod_action($v);	//* <- act = array(option name, thread, row, column)

					if ($m) {
						if (array_key_exists($m, get_localized_text_array('post_err'))) {
							++$result[$m];
						} else {
							++$done;
						}
					} else {
						++$failed;
					}
				}

				data_unlock();

				if ($result) {
					$post_status = implode(ARG_ERROR_SPLIT, array_keys($result));
				} else {
					$post_status = ($done && !$failed?'OK':'unkn_res');
				}
			}
		}
	} else
	if (!$qd_room || !$room); else	//* <- no posting outside room

//* report problem in active room ---------------------------------------------

	if (isset($_POST[$k = ARG_REPORT])) {
		if (!NO_MOD && ($report_post_ID = ($query['report_post'] ?? '') ?: $etc)) {

			$post_status = 'no_path';

			if (preg_match(PAT_REPORT, $report_post_ID, $match)) {

				$post_status = 'text_short';

				if (mb_strlen($t = trim_post($_POST[$k], REPORT_MAX_LENGTH)) >= REPORT_MIN_LENGTH) {

					data_lock(LK_ROOM.$room);

					$result = data_log_report(array(
						'thread' => $match['Thread']
					,	'post' => $match['Post']
					,	'side' => $match['Side']
					,	'report' => $t
					,	'freeze' => (
							$_POST['freeze']
						||	$_POST['stop']
						||	$_POST['check']
						)
					));
					$post_status = ($result > 0 ? 'OK' : 'trd_n_a');

					data_unlock();
				}
			}
		}
	} else
	if ($etc); else			//* <- no "etc" posting without report

//* keep current task ---------------------------------------------------------

	if (
		isset($_POST[$k = ARG_CHANGE])
	// ||	isset($_POST[$t = ARG_DESC])
	// ||	isset($_POST[$t = ARG_DRAW])
	) {
		data_aim([
			'what_change' => $_POST[$k]
		,	'what_change_to' => $_POST[ARG_CHANGE_TO]
		,	'skip_threads' => get_room_skip_list()
		]);
		data_unlock();

		$post_status = 'OK';
	} else

//* keep current task ---------------------------------------------------------

	if (
		isset($_POST[$k = ARG_KEEP])
	||	isset($_POST[$k = ARG_DROP])
	) {
		if (preg_match('~^\d+~', $_POST[$k], $digits)) {
			data_check_my_task($k);

			$post_status = 'OK';
		} else {
			$post_status = 'no_id_to_keep';
		}
	} else

//* skip current task ---------------------------------------------------------

	if (isset($_POST[$k = ARG_SKIP])) {
		if (preg_match('~^\d+~', $_POST[$k], $digits)) {
			$add_qk = get_room_skip_list($digits[0]);

			$post_status = 'OK';
		} else {
			$post_status = 'no_id_to_skip';
		}
	} else

//* process new text post -----------------------------------------------------

	if (isset($_POST[$k = 'describe'])) {
		$post_status = 'text_short';
		$trim_len = mb_strlen($post_text = trim_post($_POST[$k], DESCRIBE_MAX_LENGTH));

		if ($trim_len >= DESCRIBE_MIN_LENGTH) {
			$full_len = mb_strlen($full_text = trim_post($_POST[$k]));

			if ($full_len > $trim_len) {
				data_log_action("full post length = $full_len > $trim_len, full text", $full_text);
			}

			$x = format_post_text($post_text, $full_text);
			$post_status = 'new_post';
		}
	} else

//* process new pic post ------------------------------------------------------

	if (isset($_POST[$k = 'pic']) || isset($_FILES[$k])) {
		$post_status = 'file_pic';
		$log = 0;

		data_aim();

		if ($upload = $_FILES[$k]) {
			$t = min($_POST['t0'] ?: T0, $target['time'] ?: T0).'000-'.T0.'000';
			$post_text = "time: $t
file: $upload[name]";
			if ($upload['error']) {
				$log = get_print_or_none($_FILES);
			} else {
				$x = $upload['type'];
				$file_type = mb_strtolower(mb_substr($x, mb_strpos_after($x, '/')));

				if (in_array($file_type, $cfg_draw_file_types)) {
					$file_size = $upload['size'];
					$file_meta_text = "$t,file: $upload[name]";
				} else {
					$log = "File type $x not allowed.";
				}
			}
		} else
		if ($post_data = $_POST[$k]) {
			$post_data_size = strlen($post_data);
			unset($_POST[$k]);

	//* check pic content: "data:image/png;base64,EncodedFileContent"

			if (!$log) {
				$i = strpos($post_data, ':');
				$j = strpos($post_data, '/');
				$k = strpos($post_data, ';');
				$l = strpos($post_data, ',');
				$x = max($i, $j)+1;
				$y = min($i, $j)+1;
				$z = min($k, $l);
				$file_type = strtolower(substr($post_data, $x, $z-$x));
				$mime_type = strtolower(substr($post_data, $y, $z-$y));

				if (in_array($file_type, $cfg_draw_file_types)) {
					$file_content = base64_decode(substr($post_data, max($i, $j, $k, $l)+1));

					if (false === $file_content) {
						$log = "invalid content, $post_data_size bytes: ".(
							$post_data_size > REPORT_MAX_LENGTH
							? substr($post_data, 0, REPORT_MAX_LENGTH).'(...)'
							: $post_data
						);
					} else $file_size = strlen($file_content);
				} else {
					$log = "File type $mime_type not allowed.";
				}
			}
		}

		if (isset($_POST['txt'])) {
			$post_text = ($_POST['txt'] ?: '0-0,(?)');

	//* metadata, newline-separated key-value format:

			if (false !== mb_strpos($post_text, NL)) {
				$a = explode(',', 'app,active_time,draw_time,open_time,t0,time,used');	//* <- to add to picture mouseover text
				$b = explode(',', 'bytes,length');					//* <- to validate
				$x = mb_split_filter($post_text, NL);
				$y = array();
				$z = 0;

				foreach ($x as $line) if (
					preg_match('~^(\w+)[\s:=]+(.+)$~u', $line, $m)
				&&	($k = mb_strtolower($m[1]))
				) {
					if (in_array($k, $a)) $y[$k] = $m[2]; else
					if (in_array($k, $b)) $z = $m[2];
				}

				if ($z) {
					if ($post_data_size) {
						if ($post_data_size != $z) {
							$post_status = 'file_part';
							$log = "post_data_size: $post_data_size != declared: $z";
						}
					} else
					if ($file_size) {
						if ($file_size != $z) {
							$post_status = 'file_part';
							$log = "file_size: $file_size != declared: $z";
						}
					}
				}

				if (!$log) {
					$t = min($y['t0'] ?: T0, $target['time'] ?: T0);
					$t = array($t.'000', T0.'000');
					$z = ($target['task'] ? "/$t[0]-$t[1]" : '');
					if ($x = $y['time']) {
						if (!preg_match('~^(\d+:)+\d+$~', $x)) {
							if (preg_match('~^(\d+)\D+(\d+)$~', $x, $m)) {
								if ($m[1] && $m[1] != $m[2]) $t[0] = $m[1];
								if ($m[2]) $t[1] = $m[2];
							}
							$x = "$t[0]-$t[1]";
						}
					} else {
						$a = explode('-', $y['open_time'] ?: '-');
						$b = explode('-', $y['draw_time'] ?: '-');
						if ($b[0] == $b[1]) $b[0] = 0;
						foreach ($t as $k => $v) $t[$k] = $b[$k] ?: $a[$k] ?: $v;
						$x = "$t[0]-$t[1]";
					}
					$t = $x.$z;
					$a = $y['app'] ?: '[?]';
					if ($x = $y['used']) $a .= " (used $x)";
					if ($x = $y['active_time']) $t .= "=$x";
					$file_meta_text = "$t,$a";
				}
			} else

	//* metadata, legacy CSV:

			if (preg_match('~^(?:(\d+),)?(?:([\d:]+)|(\d+)-(\d+)),(.*)$~is', $post_text, $t)) {
				if ($t[2]) $file_meta_text = $t[2].','.$t[5];
				else {
					if (!$t[4]) $t[4] = T0.'000';
					if (!$t[3] || $t[3] == $t[4]) $t[3] = ($t[1] ?: $target['time']).'000';
					if (!$t[3]) $t[3] = $t[4];
					$file_meta_text = "$t[3]-$t[4],$t[5]";
				}
			}
		}

		if (preg_match('~^jp[eg]+$~i', $file_type)) {
			$file_type = 'jpeg';	//* <- for PHP function name
			$ext = 'jpg';		//* <- for image file name
		} else $ext = $file_type;

		if ($log); else
		if (($x = $file_size) && $x > 0 && $x > DRAW_MAX_FILESIZE) {
			$post_status = 'file_size';
			$log = $x;
		} else

	//* decide file name:

		if (
			($hash = (
				$upload
				? md5_file($f = $upload['tmp_name'])
				: md5($file_content)
			))
		&&	($fn = "$hash.$ext")
		&&	is_file($pic_final_path = get_pic_subpath($fn))
		) {
			$post_status = 'file_dup';
			$log = $fn;
		} else {

	//* save pic file:

			if (!$upload && ($log = file_put_mkdir($f = $pic_final_path, $file_content)) != $x) {
				$x = 0;
				$post_status = 'file_put';
			} else

	//* check image data:

			if ($sz = getImageSize($f)) {
				unset($file_content, $post_data);

				foreach ($cfg_wh as $k => $v)
				if ($a = (
					get_const("DRAW_LIMIT_$v")
				?:	get_const("DRAW_DEFAULT_$v")
				)) {
					list($a, $b) = preg_split('~\D+~u', $a, 0, PREG_SPLIT_NO_EMPTY);
					$y = ($b ?: $a);
					$z = ${mb_strtolower(mb_substr($v,0,1))} = $sz[$k];

					if (($a && $z < $a) || ($y && $z > $y)) {
						$x = 0;
						$post_status = 'pic_size';
						$log = "$sz[0]x$sz[1]";

						break;
					}
				}

				$resize = ($w > DRAW_PREVIEW_WIDTH);

				if ($x > 0 && ($x < 9000 || $resize)) {
					$post_status = 'pic_fill';
					$i = "imageCreateFrom$file_type";
					$log = imageColorAt($pic = $i($f), 0, 0);

					for ($x = $w; --$x;)
					for ($y = $h; --$y;) if (imageColorAt($pic, $x, $y) != $log) {
						break 2;
					}
				}

	//* invalid image:

			} else $x = 0;

	//* ready to save post:

			if ($x > 0) {
				if ($upload && !rename($f, mkdir_if_none($pic_final_path))) {
					$x = 0;
					$post_status = 'file_put';
				} else {
					$fwh = "$fn;$w*$h, ";

					if ($resize) {
						$fn = $fwh.format_filesize($file_size);
					} else if ($pic) {
						imageDestroy($pic);
					} else $log = "imageDestroy(none): $f";

	//* gather post data fields to store:

					$x = array($fn, trim_post($file_meta_text));
					if (LOG_UA) $x[] = trim_post($_SERVER['HTTP_USER_AGENT']);
					$post_status = 'new_post';
				}
			}
		}
	}

//* write new post to a thread ------------------------------------------------

	if ($post_status == 'new_post') {
		if (!$target) data_aim();
		$x = data_log_post($x);

	//* no unlock here, wait for pic optimization

		$t = array();
		if ($log = $x['fork']) $t[] = 'trd_miss';
		if ($log = $x['cap']) $t[] = 'trd_max'; else
		if (!$x['post']) {
			if ($a = array_filter(array_keys($x), 'is_deny_arg')) $t = array_merge($t, $a);
			else $t[] = 'unkn_res';
			$del_pic = $pic_final_path;
		}
		if (is_array($x = $x['arch']) && $x['done']) $t[] = 'trd_arch';
		if (count($t)) $post_status = implode(ARG_ERROR_SPLIT, $t);
	} else {
		data_unlock();
		$del_pic = $f;
	}

//* after user posting --------------------------------------------------------

	if ($f = $del_pic) {
		if (is_file($f)) unlink($f);
		unset($pic_final_path);
	}

	if ($post_text) {
		if ($log) {
			$op = ' = {';
			$ed = NL.'}';
			$i = NL.'	';
			$t = '';
			if ($target || data_aim()) foreach ($target as $key => $val) $t .= "$i$key: $val";
			$post_text = preg_replace('~\v+~u', $i, trim($post_text));
			data_log_action("Denied $post_status: $log
Post$op$i$post_text$ed
Target$op$t$ed"
			);
		} else if (!(
			$u_room_default || $u_opts['room']
		)) {
			$u_room_default = $u_opts['room'] = $room;
		}
	}
} else

//* register new user ---------------------------------------------------------

if (isset($_POST[ME]) && mb_strlen($name = trim_post($_POST[ME], USER_NAME_MAX_LENGTH)) >= USER_NAME_MIN_LENGTH) {
	$post_status = (data_log_user($u_key = md5($name.T0.substr(M0,2,3)), $name)?'user_reg':'unkn_res');
}




//* redirect after POST -------------------------------------------------------

after_posting:

if (strlen($v = trim(ob_get_clean()))) data_log_action('POST buffer dump', $v);

if ($p = $post_status) foreach (array(
	'OK' => get_localized_text_array('post_ok')
,	'NO' => get_localized_text_array('post_err')
) as $k => $v) {
	if ($$k = array_key_exists($p, $v)) $msg = $v[$p];
	else $$k = ($p == (get_const($k) ?: $k));
}
if ($OK && isset($_POST['report'])) die(get_template_page(array(
	'head' => '<script>window.close();</script>'
,	'title' => $msg
,	'task' => $p
)));

header("HTTP/1.1 303 Refresh after POST: $p");

if ($u_profile) {
	$refresh_location = ROOTPRFX.DIR_USER.$u_num;
} else
if (is_not_empty($query[LK_MOD_ACT])) {
	$refresh_location = $qfix;
} else {

//* go to room name via post form:

	if (
		$qdir
	&&	($v = $_POST[$qredir])
	&&	strlen($v = trim_room(URLdecode($v), '/'))
	&&	($a = mb_split_filter($v))
	) {
		if (!$r_type) foreach ($a as $v) {
			if ($r_type) {
				$room = $v;

				break;
			} else
			if (in_array($v, $cfg_game_type_dir)) {
				$r_type = $v;
			}
		}

		if (!$r_type) {
			$r_type = GAME_TYPE_DEFAULT;
		}

		if (!$room && ($r_type || !GAME_TYPE_DEFAULT)) {
			$room = reset($a);
		}

		$qpath = array($qdir, $r_type, $room);
	} else

//* after renaming the room:

	if (
		$room
	&&	$_POST['mod']
	&&	($a = mb_split_filter($room))
	) {
		$qpath = array($qdir, $a[0], $a[1]);
	}

	$refresh_location = (
		(
			SERVE_AS_INDEX_PAGE
		||	'index' !== get_file_name_no_ext($_SERVER['REQUEST_URI'], 0)
		)
		? ROOTPRFX
		: $_SERVER['PHP_SELF']
	);

	if (!empty($v = encode_URL_parts(array_filter($qpath, 'is_not_empty')))) {
		$refresh_location .= (!empty($qpath['etc']) ? $v : "$v/");
	}
}

if ($OK) {
	if ($u_key) {
		$a = (
			QK_KEEP_AFTER_LOGOUT
			? preg_replace('~^[0-9a-z]+~i', '', $_COOKIE[ME] ?: '')		//* <- keep after quit
			: ''
		);
		if (isset($_POST[ME])) $a = "$u_key$a";					//* <- restore at enter
		else if ($u_key !== 'quit') {
			$a = array($u_key);
			if ($u_opts !== 'default') {
				foreach ($cfg_opts_order as $i => $o) if ($i === 'input') {
					foreach ($o as $k => $u) if (isset(${$n = "u_$u"})) {
						if (!in_array($k, $cfg_opts_text)) {
							if ($v = intval($$n)) $a[] = "$k:$v";
						} else
						if (strlen($raw = trim_room($$n, '/'))) {	//* <- currently OK for any text setting
							$v = encode_opt_value($raw);
							$a[] = "$k:$v";
						}
					}
				} else {
					$v = array();
					$s = '';
					foreach ($o as $k) if (intval($u_opts[$k])) {
						$v[] = $k = get_abbr($k);
						if (!$s && mb_strlen($k) > 1) $s = '.';
					}
					if (strlen($v = implode($s, $v))) $a[] = "$i:$v$s";
				}
			}
			$a = encode_URL_parts($a);
		}
		add_cookie_header(ME, $a);
		if ($add_qk) add_cookie_header($add_qk);
	}

//* after changing task:

	if (
		$target
	&&	$target['changed']
	&&	!$target['task']
	) {
		$k = (
			(
				isset($_POST[ARG_DRAW])
			||	ARG_DRAW === $_POST[ARG_CHANGE]
			||	ARG_DRAW === $_POST[ARG_CHANGE_TO]
			)
			? ARG_DRAW
			: ARG_DESC
		);

		$query[$k] = '';
	} else
	if ($room_type['single_active_thread']) {
		$query = (
			array_filter($query, 'is_desc_arg', ARRAY_FILTER_USE_KEY)
		?:	array_filter($query, 'is_draw_arg', ARRAY_FILTER_USE_KEY)
		);
	} else {
		unset($query);
	}
} else {
	$query = array_filter($query, 'is_draw_arg', ARRAY_FILTER_USE_KEY);
	if ($report_post_ID) $query['report_post'] = $report_post_ID;
	if ($p) $query[ARG_ERROR] = $p;
}

if (MOD && $_POST['mod']) {
	$query[LK_MOD_ACT_LOG] = T0;
}

if ($query && is_array($query)) {
	ksort($query);
	$refresh_args = array();

	foreach ($query as $k => $v) if (
		$k !== ARG_CHANGE
	&&	$k !== ARG_CHANGE_TO
	&&	$k !== ARG_DROP
	&&	$k !== ARG_KEEP
	) {
		$refresh_args[] = (strlen($v) ? "$k=$v" : $k);
	}

	$refresh_location .= (false === strpos($refresh_location, '?') ? '?' : '&').implode('&', $refresh_args);
}

//* show pic processing progress ----------------------------------------------

$page_reload_after_pause = 0;

if ($f = $pic_final_path) {

	function pic_opt_get_size($f) {
		global $page_reload_after_pause, $TO;

		$old = filesize($f);

		if ($page_reload_after_pause) {
			echo format_filesize($old).$TO;
			flush();
		}

		$program_name = optimize_pic($f);
		$new = filesize($f);

		if ($new === $old) {
			if ($page_reload_after_pause) {
				echo (
					$program_name
					? get_localized_text('no_change')
					: get_localized_text('post_progress', 'no_program')
				);
			}

			return '';
		} else {
			$size_text = format_filesize($new);

			if ($page_reload_after_pause) {
				$made_with = get_localized_text('post_progress', 'used_program');

				echo "$size_text. $made_with: $program_name.";
			}

			return $size_text;
		}
	}

	function pic_opt_get_time() {
		global $AT;

		return '</p>
<p>'.get_time_elapsed().$AT;
	}

	if ($u_opts['picprogress']) {
		ob_start();
	} else {
		if (false === mb_strpos('.,;:?!', mb_substr($msg, -1))) {
			$msg .= '.';
		}

		$AT = ' &mdash; ';
		$BY = ' x ';
		$TO = ' &#x2192; ';
		$page_reload_after_pause = max(intval(POST_PIC_WAIT), 1);
		$refresh_header_content = "$page_reload_after_pause; url=$refresh_location";

	//* this is not working here anyway, must set in php.ini:

	//	if (function_exists('ini_set')) {
	//		ini_set('zlib.output_compression', 'Off');
	//		ini_set('output_buffering', 'Off');
	//	}

	//* this is for nginx and gzip, to allow showing progress as soon as available:

		if (WS_NGINX) {
			header('X-Accel-Buffering: no');
			header('Content-Encoding: none');
		}

	//* this is for the browser, some may be configured to prevent though:

		header("Refresh: $refresh_header_content");

		echo '<!doctype html>
<html lang="'.LANG.'">
<head>
	<meta charset="'.ENC.'">
	<meta http-equiv="refresh" content="'.$refresh_header_content.'">
	<title>'.get_localized_text('sending').'</title>
	<style>
		html {background-color: #eee;}
		body {background-color: #f5f5f5; margin: 1em; padding: 1em; font-family: sans-serif; font-size: 14px;}
		p {padding: 0 1em;}
		p:first-child {background-color: #ddd; padding: 1em;}
		p:last-child {background-color: #def; padding: 1em; margin-block-end: 0;}
	</style>
</head>
<body>
<p>'
.get_time_html()
.$AT
.get_localized_text('post_progress', 'starting')
.pic_opt_get_time()
.get_localized_text('post_progress', 'opt_full')
.': ';
	}

//* rewriting already stored post is a crutch, but nothing better for now:

	if ($changed = pic_opt_get_size($f)) {
		if (IS_LOCALHOST) echo pic_opt_get_time().get_localized_text('post_progress', 'update_pic').": $fn$TO$fwh$changed";

		$changed_result = data_rename_last_pic($fn, $fwh.$changed);

		if (IS_LOCALHOST) echo pic_opt_get_time().get_localized_text('post_progress', $changed_result ? 'done' : 'failed');
	}

	if ($pic && $resize) {
		$new_width = DRAW_PREVIEW_WIDTH;
		$new_height = round($h / $w * $new_width);
		$z = filesize($f);

		if (is_file($f = get_pic_resized_path($f))) {
			unlink($f);
		}

		if ($page_reload_after_pause) {
			echo pic_opt_get_time().get_localized_text('post_progress', 'low_res').": $w$BY$h$TO$new_width$BY$new_height";
		}

		$pic_resized = imageCreateTrueColor($new_width, $new_height);

		imageAlphaBlending($pic_resized, true);
		imageSaveAlpha($pic_resized, true);

		imageFill($pic_resized, 0,0, TRANSPARENT_COLOR);
		imageCopyResampled($pic_resized, $pic, 0,0,0,0, $new_width, $new_height, $w,$h);

		imageDestroy($pic);

		$i = "image$file_type";
		$i($pic_resized, $f);

		if ($page_reload_after_pause) {
			echo pic_opt_get_time().get_localized_text('post_progress', 'opt_res').': ';
		}

		pic_opt_get_size($f);

		if (
			PIC_OPT_REDUCE_COLORS
		&&	$file_type == 'png'
		&&	($z < filesize($f))
		&&	!pic_has_transparency($pic_resized, $new_width, $new_height)
		) {
			if ($page_reload_after_pause) {
				echo pic_opt_get_time().get_localized_text('post_progress', 'low_bit').': 255';
			}

			$pic_less_colored = imageCreateTrueColor($new_width, $new_height);

			imageAlphaBlending($pic_less_colored, true);
			imageSaveAlpha($pic_less_colored, true);

			imageFill($pic_less_colored, 0,0, TRANSPARENT_COLOR);
			imageCopyMerge($pic_less_colored, $pic_resized, 0,0,0,0, $new_width, $new_height, 100);
			imageTrueColorToPalette($pic_resized, false, 255);
			imageColorMatch($pic_less_colored, $pic_resized);

			imageDestroy($pic_less_colored);

			unlink($f);
			$i($pic_resized, $f);

			imageDestroy($pic_resized);

			if ($page_reload_after_pause) {
				echo pic_opt_get_time().get_localized_text('post_progress', 'opt_res').': ';
			}

			pic_opt_get_size($f);
		} else {
			imageDestroy($pic_resized);
		}
	}
	data_unlock();

	if ($page_reload_after_pause) {
		$page_reload_msg = sprintf(
			get_localized_text('post_progress', 'refresh')
		,	$refresh_location
		,	format_time_units($page_reload_after_pause)
		);

//* Closing "</html>" should be the last thing to output:

		die(pic_opt_get_time().$msg.'</p>
<p>'.$page_reload_msg.'</p>
</body>
</html>');
	} else {
		ob_end_clean();
	}
}

//* use Refresh header for printing content.
//* use Location header otherwise:

if (headers_sent()) {
	exit_redirect($refresh_location, 'after post', $page_reload_after_pause);
} else {
	header("Location: $refresh_location");
}

?>
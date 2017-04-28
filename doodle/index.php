<?php

$t = microtime();

function is_prefix($s, $p) {return substr($s, 0, strlen($p)) === $p;}
function is_postfix($s, $p) {return substr($s, -strlen($p)) === $p;}
function exit_403($why) {
	header('HTTP/1.1 403 Forbidden');
	die("Error 403: Forbidden. Reason: $why.");
}

header('Cache-Control: max-age=0; must-revalidate; no-cache');
header('Expires: Mon, 12 Sep 2016 00:00:00 GMT');
header('Pragma: no-cache');
if (function_exists($f = 'header_remove')) $f('Vary');
if ($_REQUEST['pass']) exit_403('pass');		//* <- ignore spam bot requests

define(NAMEPRFX, 'd');

//* Config parts that are not safe to change: ---------------------------------

define(ENC, 'utf-8');
mb_internal_encoding(ENC);
mb_regex_encoding(ENC);

define(ROOTPRFX, mb_substr($s = $_SERVER['PHP_SELF'] ?: $_SERVER['SCRIPT_NAME'] ?: '/', 0, mb_strrpos($s, '/')+1));

if (!is_prefix($s = URLdecode($p = $_SERVER['REQUEST_URI']), ROOTPRFX)) {
	exit_403('"'.ROOTPRFX.'" path does not match "'.$s.'"');
}

//* source: http://php.net/security.magicquotes.disabling#91653
if (function_exists($f = 'get_magic_quotes_gpc') && $f()) {
	function strip_magic_slashes(&$value, $key) {$value = stripslashes($value);}
	$gpc = array(&$_COOKIE, &$_GET, &$_POST, &$_REQUEST, &$_SESSION);
	array_walk_recursive($gpc, 'strip_magic_slashes');
}

define(ME, 'me');
define(ME_VAL, $_POST[ME] ?? $_COOKIE[ME] ?? '');	//* <- don't rely on $_REQUEST and EGPCS order
define(POST, 'POST' == $_SERVER['REQUEST_METHOD']);

if (POST) {
	if (!ME_VAL) exit_403('post');			//* <- ignore anonymous posting
	ignore_user_abort(true);
}

$t = explode(' ', $t);
define(T0, $t[1]);
define(M0, $t[0]);

$s = $_SERVER['SERVER_SOFTWARE'];
define(WS_NGINX, stripos($s, 'nginx') !== false);
define(WS_HTACCESS_SUPPORTED, stripos($s, 'apache') !== false);

define(GET_Q, strpos($p, '?'));
define(ARG_ERROR, '!');
define(ARG_ERROR_SPLIT, '!');
define(ARG_DENY, 'deny');
define(ARG_DESC, 'desc');
define(ARG_DRAW, 'draw');
define(ARG_DROP, 'drop');
define(ARG_CHANGE, 'change');

define(LK_MOD_HTA, 'htaccess');
define(LK_MOD_ACT, 'mod');
define(LK_MOD_OPT, 'opt');
define(LK_PIC_OPT, 'pic');
define(LK_REF_LIST, 'reflinks');
define(LK_USERLIST, 'users');
define(LK_USER, 'user/');
define(LK_ROOM, 'room/');
define(LK_VERSION, 'version');
define(COUNT_ARCH, 'arch');
define(COUNT_ROOM, 'room');
define(COUNT_POST, 'post');

define(NL, "\n");
define(BOM, pack('CCC', 239, 187, 191));		//* <- UTF-8 Byte Order Mark
define(B64_PRFX, 'base64:');
define(OPT_PRFX, 'opt_');
define(TIMESTAMP, 'Y-m-d H:i:s');
define(PAT_DATE, '~(?P<ym>(?P<y>\d+)-(?P<m>\d+))-(?P<d>\d+)~');
define(PAT_REPORT, '~^(?P<thread>\d+)\D+(?P<post>\d+)\D+(?P<side>\d+)$~');
define(PAT_CONTENT, '~^(?P<before>.*?<pre>)(?P<content>.*?\S)(?P<after>\s*</pre>.*)$~uis');
define(PAT_REGEX_FORMAT, '~^/.+/[imsu]*$~u');

//* Start buffering to clean up included output, like BOMs: *------------------

ob_start();
require(NAMEPRFX.'.cfg.php');
require(NAMEPRFX.'.fu.php');
require(NAMEPRFX.'.db.php');

$cfg_wh = array('width', 'height');
$cfg_room_prefixes = array();
foreach ($cfg_room_types as $v) if ($v = $v['if_name_prefix']) $cfg_room_prefixes[] = $v;
foreach ($cfg_dir as $k => $v) {
	define('DIR_'.strtoupper($k), $v .= '/');
	${$k.'_list_href'} = ROOTPRFX.$v;
}
$cfg_room_prefix_chars = implode('', array_unique(mb_str_split(fix_encoding(implode('', $cfg_room_prefixes)))));

//* Select UI language *-------------------------------------------------------

//* source: http://www.dyeager.org/blog/2008/10/getting-browser-default-language-php.html
if ($v = $_SERVER['HTTP_ACCEPT_LANGUAGE']) {
	$a = array();
	foreach (explode(',', $v) as $v) if (preg_match('~(\S+);q=([.\d]+)~ui', $v, $m)) {
		$a[$m[1]] = (float)$m[2];
	} else $a[$v] = 1.0;
//* check for highest q-value. No q-value means 1 by rule
	$q = 0.0;
	foreach ($a as $k => $v) if ($v > $q && in_array($l = strtolower(substr($k, 0, 2)), $cfg_langs)) {
		$q = (float)$v;
		$lang = $l;
	}
}

require(NAMEPRFX.".cfg.$lang.php");

//* End buffering, hide output. *----------------------------------------------

if ($v = trim_bom(ob_get_clean())) data_log_action('cfg buffer dump', $v);
time_check_point('done cfg');

//* Data maintenance *---------------------------------------------------------

data_fix();

if (!POST) {
	$r = array($_SERVER['HTTP_REFERER']);
	if (
		($v = $_SERVER['HTTP_USER_AGENT'])
	&&	preg_match_all('~(?<=^|\s|\+)(\w+:/+\S+)~i', $v, $m)
	) {
		foreach ($m[1] as $v) {
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

if (false !== GET_Q) {
	if ($q = ltrim(substr($p, GET_Q), '?')) {
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
		foreach ($cfg_dir as $k => $v) if ($qdir == $v) {${"qd_$k"} = 1; break;}
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
//* 5) this will be either "/last-part" (kept for legacy requests) or just a trailing "/" before optional "?query":
		if ($etc && !preg_match('~^[-\d]+$~', $etc)) $etc = '';
		$qpath['etc'] = $etc;
	}
}

$qfix = ROOTPRFX.encode_URL_parts($qpath).($query ? "?$q" : '');

if (!POST && $qfix !== $_SERVER['REQUEST_URI'] && !$query[LK_MOD_ACT]) {
	header('HTTP/1.1 303 Redirect: path fix');
	header("Location: $qfix");
	exit;
}

time_check_point('done location check');

//* User settings *------------------------------------------------------------
//* keep legacy formats until cookies expire in 3 years;
//* mb strings are not expected here without escape/encoding or user meddling;
//* ---------------------------------------------------------------------------

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
	if (($reg = trim($_POST[ME])) && $u_qk != $reg) $u_qk = $reg;
	if ($u_qk && data_check_u($u_qk, $reg)) {
		if (LOG_IP) data_log_ip();
		if ($u_flag['ban']) die(get_template_page(array(
			'title' => $tmp_ban
		,	'task' => $tmp_ban
		,	'body' => 'burnt-hell'
		)));

		if (!$u_flag['god']) unset($cfg_opts_order['admin'], $tmp_options_input['admin']);
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
						foreach ($cfg_opts_order[$k] as $v) if (in_array(abbr($v), $y)) $u_opts[$v] = 1;
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
define(GOD, !!$u_flag['god']);
define(TIME_PARTS, !POST && GOD && !$u_opts['time_check_points']);	//* <- profiling
if (TIME_PARTS) time_check_point('done user settings, GOD = '.GOD); else unset($tcp);

//* Location access check *----------------------------------------------------

$room_type = array();
if ($qdir) {
	if ($room_name = $room) {
		if ($r_type) $room = "$r_type/$room";
		$room_type = get_room_type($room);
	}
} else {
	if ($u_key && !$u_room_default) $qd_opts = 1;	//* <- root page
	if (GOD && WS_HTACCESS_SUPPORTED && substr($query[LK_MOD_OPT],0,3) !== 'hta') rewrite_htaccess();
}
$top_title = (false !== ($k = array_search($r_type, $cfg_game_type_dir)) ? $tmp_room_types_title[$k] : $tmp_title);

define(MOD, GOD || $u_flag['mod'] || $u_flag["mod_$room"]);
define(NO_MOD, !$room_type['mod']);
define(FROZEN_HELL, data_global_announce('stop'));	//* <- after $room is defined

if (FROZEN_HELL && !(MOD || $qd_arch || ($qd_opts && $u_key))) {
	if (POST) goto after_posting;
	if (FROZEN_HELL < 0) die(
		$etc == '-'
		? $tmp_stop_all
		: get_template_page(array(
			'title' => "$tmp_stop_all $top_title."
		,	'task' => $tmp_stop_all
		,	'anno' => 1
		,	'header' => array(
				'<a href="'.ROOTPRFX.'">'.$t.'.</a>'
			, 'r' =>
				'<a href="'.ROOTPRFX.DIR_ARCH.'">'.$tmp_archive.'.</a>'.NL.
				'<a href="'.ROOTPRFX.DIR_OPTS.'">'.$tmp_options.'.</a>'
			)
		))
	);
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
	$a = array_keys($tmp_mod_pages);
	if ($q) {
		$q = $query[$qmod] ?: reset($a);
		$do = $query[$qdo];
		$i = intval($query[$qid]);
	} else {
		$q = $a[intval($etc)-1];
		if ($i = strpos($etc, '-')) {
			$i = intval(substr($etc, $i+1));
			$a = array_keys($tmp_mod_files);
			$do = $a[$i-1];
		}
	}
	if ($q === LK_USERLIST && $i) {
		die(get_template_page(array(
			'title' => "$tmp_mod_pages[$q]: $i, $usernames[$i]"
		,	'content' => (
				($a = data_get_user_info($i))
				? array_merge(array(
					'Current date' => date(TIMESTAMP, T0)
				,	'User ID' => $i
				,	'User name' => $usernames[$i]
				), $a)
				: $tmp_empty
			)
		,	'listing' => ':'.NL
		)));
	}
	$lnk = $t = '';
	$mod_title = $mod_page = $tmp_mod_pages[$q] ?: $tmp_empty;

	if ($q === 'welcome') {
		$page['welcome'] = $tmp_welcome_parts;
	} else
	if ($q === 'logs') {
		$day = $query[$qday] ?: $etc;
		$ymd = preg_match(PAT_DATE, $day);
		if ($l = data_get_mod_log()) {
			$page['data']['content']['type'] = 'reports';
			$page['content'] = "
day_link = .?$qmod=$q&$qday=";
			$last = end(end($l));
			$last = data_get_mod_log($last_ymd = key($l).'-'.$last, 1);
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
			foreach ($l as $ym => $d) $lnk .= ($lnk?'</p>':'').'
<p>'.$ym.'-'.implode(',', $d);
			$lnk .= ' <small>'.date('H:i:s', $last).'</small></p>';
		}
	} else
	if ($q === 'files') {
		$a = array(
			'opcache_check' => 'opcache_get_status'
		,	'opcache_reset' => 'opcache_reset'
		,	'opcache_inval' => 'opcache_invalidate'
		);
		foreach ($a as $k => $v) if (!function_exists($v)) unset($tmp_mod_files[$k]);
		foreach ($tmp_mod_files as $k => $v) $lnk .= "
<li><a href=\"?$qmod=$q&$qdo=$k\">".mb_str_replace_first(' ', '</a> ', $v).'</li>';
		$lnk = '
<ul>'.indent($lnk).'</ul>';
		if (!$do) $do = end(array_keys($tmp_mod_files));
		if ($do && array_key_exists($do, $tmp_mod_files)) {
			if ($a = $tmp_mod_files[$do]) $lnk .= '
<p>'.rtrim($a, ':.').':</p>';
			ignore_user_abort(true);
			data_lock($lk = LK_MOD_ACT);
if (TIME_PARTS) time_check_point('ignore user abort');
			if ($do === 'list') {
				$len = array(0,0,0);
				$dirs = array();
				$files = array();
				foreach (get_dir_contents() as $f) {
					$d = is_dir($f);
					$m = date(TIMESTAMP, filemtime($f));
					$s = ($d ? 'DIR' : filesize($f).' B');
					$a = array($f, $s, $m);
					foreach ($len as $k => &$v) $v = max($v, mb_strlen($a[$k]));
					${$d?'dirs':'files'}[] = $a;
				}
				foreach (array_merge($dirs, $files) as $a) {
					foreach ($len as $k => $v) {
						$s = mb_strlen($a[$k]);
						if ($s < $v) $a[$k] .= str_repeat(' ', $v-$s);
					}
					$t .= implode('	', $a).NL;
				}
			} else
			if ($do === 'opcache_check') {
				if (is_array($a = opcache_get_status())) {
					if (array_key_exists($k = 'scripts', $a) && is_array($b = &$a[$k])) ksort($b);
					$t = print_r($a, true);
				} else $t = $a;
			} else
			if ($do === 'opcache_reset') {
				$t = opcache_reset();
			} else
			if ($do === 'opcache_inval') {
				$t = implode(NL, array_filter(array_map(
					function($f) {
						return get_file_ext($f) === 'php' ? "$f\t".opcache_invalidate($f) : '';
					}
				,	get_dir_contents()
				)));
			} else
			if ($do === 'arch') {
				require_once(NAMEPRFX.'.arch.php');
				$t = data_archive_rewrite();
			} else
			if ($do === 'room_list_reset') {
				$t = data_post_refresh(true);
			} else
			if ($do === 'img2subdir') {
				foreach (get_dir_contents($d = DIR_PICS) as $f) if (is_file($old = $d.$f)) {
					$new = get_pic_subpath($f, true);
					$t .=
NL.(++$a)."	$old => $new	".($old === $new?'same':(rename($old, $new)?'OK':'fail'));
				}
if (TIME_PARTS && $a) time_check_point("done $a pics");
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
			if (substr($do, 0,3) === 'hta') {
				$t = rewrite_htaccess(substr($do, -5) === 'write');
			} else {
				$t = data_fix($do);
			}
			if (!$t) $t = $tmp_no_change;
			data_unlock($lk);
		}
	} else
	if (substr($q,0,4) === 'vars') {
		exit_if_not_mod();				//* <- never exits, to check HTTP_IF_MODIFIED_SINCE, etc
		$sort = (substr($q, -4) === 'sort');
		$headers = headers_list();
		$t =	'strip magic slashes = '.($gpc?'on':'off').NL
		.	'DATE_RFC822 = '	.gmdate(DATE_RFC822, T0).NL
		.	'DATE_RFC2822 = '	.gmdate('r', T0).NL
		;
		$v = '
			ROOTPRFX, NAMEPRFX, DATA_VERSION, HTML_VERSION, HTACCESS_VERSION
		,	mb_regex_encoding, mb_regex_set_options, headers
		,	qfix, qpath, query, room, room_type
		,	cfg_room_prefix_chars, cfg_room_prefixes
		,	_COOKIE, _ENV, _GET, _POST, _SERVER, _SESSION, gd_info
		';
		foreach (explode(',', $v) as $k) if ($v = (function_exists($k = trim($k)) ? $k() : (get_const($k) ?: $$k))) {
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
	} else
	if ($q && array_key_exists($q, $tmp_mod_pages)) {
		data_lock($q, false);
		exit_if_not_mod(data_get_mod_log($q, 1));
		if ($t = data_get_mod_log($q)) {
			if ($q === LK_USERLIST) {
				$page['content'] .= "
left_link = .?$qmod=$q&$qid=
left = $tmp_mod_user_info
right = $tmp_mod_user_hint
flags = cgu
v,$u_num,u	v
$t";
			} else
			if ($q === LK_REF_LIST) {
				$page['content'] .= "
flags = c
$t";
			}
			$page['data']['content']['type'] = $q;
			$lnk .= get_template_form(array('filter' => 1));
		}
		data_unlock($q);
	}
	if ($page['content'] || ($page['textarea'] = $t) || $page['welcome'] || $lnk) {
		if ($page['content'] || $lnk) {
			$page['js']['mod']++;
			$page['js'][0]++;
		}
	} else $lnk = $tmp_empty;
	$page['task'] = "
<p$attr>$mod_page:</p>$lnk";
} else

//* archived threads ----------------------------------------------------------

if ($qd_arch) {
	require_once(NAMEPRFX.'.arch.php');
	$search = data_get_archive_search_terms();

//* archive threads list ------------------------------------------------------

	if ($room && ($thread_count = data_get_count(COUNT_ARCH))) {
		exit_if_not_mod(data_get_mtime(COUNT_ARCH));

		if (!$search) {
			if ($a = abs($room_type['arch_pages'] ?: 0)) {
				$a *= TRD_PER_PAGE;
				$start = max(0, $thread_count - $a);
			} else $start = 0;
			$page['content'] = '
images = '.DIR_THUMB.'
image_ext = '.THUMB_EXT.'
page_ext = '.PAGE_EXT.'
on_page = '.($a ? "$a
start = $start" : ($u_trd_per_page ?: TRD_PER_PAGE)).'
total = '.$thread_count.($u_key?'':'
last = <a href="'.$thread_count.'.htm">'.$thread_count.'</a><!-- static link for scriptless bots -->');
			$page['head'] = '
<link rel="prev" href="'.($start+1).PAGE_EXT.'">
<link rel="next" href="'.$thread_count.PAGE_EXT.'">';
			$page['data']['content']['type'] = 'archive pages';
		}
	} else

//* archive rooms list --------------------------------------------------------

	if ($visible = data_get_visible_archives($r_type)) {
		exit_if_not_mod($visible['last']);

		if (!$search) {
			if ($c = !$u_opts['count']) $page['content'] = "
$tmp_arch_last	$tmp_arch_count";
			$prev_type = false;
			foreach ($visible['list'] as $room => $n) {
				$a = mb_split_filter($room);
				$room = array_pop($a);
				if (!$r_type && ($prev_type !== ($type = implode('/', $a)))) {
					if (false !== ($k = array_search($type, $cfg_game_type_dir))) {
						$page['content'] .= "

type = $type
type_title = $tmp_room_types_title[$k]$top";
					}
					$prev_type = $type;
				}
				$page['content'] .= ($c ? "
$n[last]	$n[count]	$room" : NL.NB.'	'.NB.'	'.$room);
			}
			$room = '';
			$page['data']['content']['type'] = 'archive rooms';
		}
	} else $search = 0;

//* archive posts search ------------------------------------------------------

	if (is_array($search)) {
		$t = array('r' => sprintf(sprintf($tmp_regex_hint, HINT_REGEX_LINK, HINT_REGEX_FORMAT), $lang, $tmp_regex_hint_pat));
		if (!($search || $room)) $t[''] = $tmp_archive_hint;
		$page['task'] = get_template_form(
			array(
				'head' =>	$tmp_archive
			,	'select' =>	$tmp_archive_find_by
			,	'submit' =>	$tmp_archive_find
			,	'hint' =>	$t
			,	'min' =>	FIND_MIN_LENGTH
			)
		);
		if ($search) {
			$research = '';
			foreach ($search as $k => $v) {
				$t = $tmp_archive_find_by[$k];
				$t = $t['found by'] ?: $t['select'];
				$research .=
					($research?',':'')
				.	NL
				.	'<a name="'.$k.'">'
				.	($t ? "$t: " : '')
				.		'<span>'
				.			htmlspecialchars($v)
				.		'</span>'
				.	'</a>';
			}
			$page['task'] .= '
<p class="hint" id="research">'.indent($tmp_archive_found.$research).'</p>';
			if ($found = data_archive_find_by($search)) {
				$page['content'] = '
page_ext = '.PAGE_EXT.get_flag_vars(
					array(
						'flags' => array(
							'ac', array(
								1
							,	!$u_opts['count']
							)
						)
					,	'caps' => 0
					)
				).NL.$found;
				$page['data']['content']['type'] = 'archive found';
			}
		}
		$page['js'][0]++;
	}
	if (!$page['content']) $page['task'] .= $tmp_empty;
} else

//* draw test -----------------------------------------------------------------

if (($qd_opts || !$qdir) && GET_Q) {
	$qd_opts = 2;
	$n = get_draw_app_list(false);
	$page['icon'] = $n['name'];
	$page['task'] = '
<p>'.$tmp_draw_free.':</p>
<p class="hint">'.$tmp_draw_hint.'</p>'.$n['noscript'];
	$page['subtask'] = $n['embed'].'
<div class="task">'.indent('<p class="hint">'.indent($n['list']).'</p>').'</div>';
} else

if ($u_key) {

//* options -------------------------------------------------------------------

	if ($qd_opts) {
		$page['data']['content']['type'] = 'options';
		$so = '|';
		$sp = ';';
		$t = ':	';
		$draw_app = implode($sp, array(
			(array_search($u_draw_app, $cfg_draw_app) ?: 0)
		,	implode($so, $tmp_draw_app)
		,	implode($so, $cfg_draw_app)
		,	DRAW_APP_NONE
		,	'?draw_app=*'
		));
		$c = $d = '';
		foreach ($tmp_options_input as $i => $o)
		foreach ($o as $k => $l) {
			$r = abbr($k).'='.(
				$i === 'input'
				? (
					$$k ?: (
						!$qdir && $k == 'room_default'
						? (${"u_$k"} ?: get_const($k))
						: ${"u_$k"}
					).$so.get_const($k)
				) : ($u_opts[$k]?1:'')
			);
			if ($i === 'admin') $l = '<span class="gloom">'.$l.'</span>';
			$c .= NL.$l.$t.$r;
		}
		$i = '
|<input type="submit" value="';
		$j = '
|<input type="button" value="';
		foreach (array(
			'out'	=> array($i, 'name="quit')
		,	'save'	=> array($j, 'id="unsave" data-keep="'.DRAW_PERSISTENT_PREFIX)
		,	'skip'	=> array($j, 'id="unskip')
		,	'pref'	=> array($i, 'name="'.OPT_PRFX.'reset')
		) as $k => $v) $d .= $v[0].$tmp_options_drop[$k].'" '.$v[1].'">';
		if (!$qdir) {
			if (GOD && WS_NGINX) $page['content'] .= vsprintf('
||<b class="anno report">%s<br><a href=".?'.LK_MOD_ACT.'=files&'.LK_MOD_OPT.'=nginx">%s</a></b>', $tmp_options_warning);
			$page['content'] .= '
||<b class="anno">'.sprintf($tmp_options_first, $tmp_options_apply, $room_list_href).'</b>';
		}
		$page['content'] .= '
<form method="post">'.$d.'
</form><form method="post">'
.NL.$tmp_options_name.$t.$usernames[$u_num]
.NL.$tmp_options_qk.$t.'<input type="text" readonly value="'.$u_key.'" title="'.$tmp_options_qk_hint.'">
opt_prefix = '.OPT_PRFX.'
separator = '.$so.'
sep_select = '.$sp.$c
.NL.$tmp_options_time.$t.date('e, T, P')
.NL.$tmp_options_time_client.$t.'<time id="time-zone"></time>'
.($u_flag ? NL.$tmp_options_flags.$t.implode(', ', $u_flag) : '')
.$i.$tmp_options_apply.'" id="apply"'.($qdir?' disabled':'').'>
</form>';
		foreach ($tmp_rules as $head => $hint) {
			if (is_array($hint)) {
				$s = '';
				foreach ($hint as $i) $s .= NL.'<li>'.indent(get_template_hint($i)).'</li>';
				$hid = ($qdir || $page['task']?' class="hid"':'');
				$s = NL."<ul$hid>".indent($s).'</ul>';
			} else	$s = NL.'<p class="hint">'.indent(get_template_hint($hint)).'</p>';
			$page['task'] .= NL."<p>$head</p>$s";
		}
		$page['js'][0]++;
	} else

//* rooms ---------------------------------------------------------------------

	if ($qd_room) {
		if ($room) {
			if (!MOD && FROZEN_HELL) {
				$page['task'] = $tmp_stop_room ?: $tmp_stop_all;
				goto template;
			}

//* task manipulation, implies that $room is set and fixed already ------------

			if (strlen($v = $query['report_post'])) $etc = $v; else
			if (strlen($v = $query['skip_thread'])) $etc = '-'.abs(intval($v)); else
			if (strlen($v = $query['check_task'])) {
				if ($v == 'keep' || $v == 'prolong') $etc = '-'; else
				if ($v == 'post' || $v == 'sending') $etc = '--';
			}
			if ($etc) {
				if ($etc[0] == '-') {
			//* show current task:
					$sending = (strlen($etc) > 1);
					if (!strlen($t = trim($etc, '-'))) {
						$t = data_check_my_task();
						die(
							'<!--'.date(TIMESTAMP, T0).'-->'
							.NL.'<meta charset="'.ENC.'">'
							.NL.'<title'.get_template_attr(array('deadline' => $target['deadline'])).(
								is_array($t)
								? '>'.(
									$sending
									? $tmp_sending
									: $tmp_target_status[$t[0]].(
										$u_opts['task_timer']
										? ". $tmp_time_limit: ".format_time_units($t[1])
										: ''
									)
								)
								: (
									$sending
									? ' id="confirm-sending"'
									: ''
								).'>'.$tmp_target_status[$t]
							).'</title>'
							.NL.(
								($t = $target['task']) && $target['pic']
								? '<img src="'.get_pic_url($t).'" alt="'.$t.'">'
								: $t
							)
						);
					}
			//* skip current task, obsolete way by GET:
					$add_qk = get_room_skip_list($t);
					$post_status = 'skip';
					goto after_posting;
				}

//* report form ---------------------------------------------------------------

				$page['task'] = get_template_form(
					array(
						'method' =>	'post'
					,	'name' =>	'report'
					,	'min' =>	REPORT_MIN_LENGTH
					,	'max' =>	REPORT_MAX_LENGTH
					,	'textarea' =>	($is_report_page = 1)
					,	'checkbox' =>	array(
							'name' => 'freeze'
						,	'label' => $tmp_report_freeze
						)
					)
				);
			} else {

//* active room task and visible content --------------------------------------

				$y = $query[ARG_ERROR];
				if ($ay = mb_split_filter($y, ARG_ERROR_SPLIT)) unset($ay['trd_arch'], $ay['trd_miss']);
				$drop = isset($query[ARG_DROP]);
				$change = ($drop || isset($query[ARG_CHANGE]) ? ($query[ARG_DROP] ?: $query[ARG_CHANGE] ?: true) : false);
				$desc_query = ($change === ARG_DESC || isset($query[ARG_DESC]));
				$draw_query = ($change === ARG_DRAW || !!array_filter($query, 'is_draw_arg', ARRAY_FILTER_USE_KEY));
				$dont_change = (!$change && (
					$desc_query
				||	$draw_query
				||	$ay
				));
				$skip_list = get_room_skip_list();

if (TIME_PARTS) time_check_point('inb4 aim lock');
				data_aim(
					$drop ? ARG_DROP : $change
				,	$dont_change		//* <- after POST with error
				,	$skip_list
				,	!$u_opts['unknown']
				);
				$visible = data_get_visible_threads();
				data_unlock();
if (TIME_PARTS) time_check_point('got visible threads data, unlocked all, target = '.get_print_or_none($target));

				exit_if_not_mod(max($t = $target['time'], $visible['last']), $change || $target['changed']);
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
						if ($desc_query) $draw = 0; else
						if ($draw_query) $draw = 1;
					}
				} else {
					$post_rel = '_op';
					if (
						!in_array($x, $ay)
					&&	data_is_thread_cap()
					) {
						$draw = -1;
				//		$query[ARG_ERROR] = ($y?$y.ARG_ERROR_SPLIT:'').$x;
					} else
					if ($desc_query) $draw = 0; else
					if ($draw_query) $draw = 1;
				}
				if ($draw >= 0) {
					$post_type = ($draw?'image':'text').$post_rel;
					$other_type = ($draw?'text':'image').$post_rel;
					if (!$room_type["allow_$post_type"]) $draw = ($room_type["allow_$other_type"] ? !$draw : -1);
				}

				if ($vts = $visible['threads']) {
					if (MOD || !(NO_MOD || $u_flag['nor'])) $t = (
						MOD ? "
left = $tmp_mod_post_hint
right = $tmp_mod_user_hint"
						: "
left = $tmp_report_post_hint
right = $tmp_report_user_hint"
					).'
report_to = .?report_post='.$t;
					$t .= '
images = '.ROOTPRFX.DIR_PICS;
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
if (TIME_PARTS) time_check_point('inb4 vts -> tsv'.NL);
					foreach ($vts as $tid => $posts) {
						$tsv = '';
if (TIME_PARTS) $pi = $ri = $li = 0;
						foreach ($posts as $postnum => $post) {
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
								$r[0] = (
									!$u_opts['names'] && array_key_exists($uid, $usernames)
									? $usernames[$uid]
									: NB
								);
								$r = implode($b, $r);
							} else $r = NB;
							$tabs = array(
								'color' => $u_opts['own']?0:$post['flag']
							,	'time' => $l
							,	'user' => $r
							,	'content' => $post['post']
							);
							if ($t = $post['used']) {
								$tabs['used'] = $t;
if (TIME_PARTS) ++$pi;
							}
							if (GOD) {
								$tabs['color'] .= '#'.$uid;
								if ($t = $post['browser']) $tabs['browser'] = $t;
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
								$postnum > 0 || $u_flag['nor'] || (NO_MOD && !MOD)
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
left = $tmp_empty
right = $tmp_empty$flags

0,0	$left	v";	//* <- dummy thread for JS drop-down menus
				} else $page['content'] = $t;

				$t = $target['task'];
				if ($draw < 0) {
					$page['task'] = '
<p>'.indent($tmp_room_thread_cap).'</p>
<p class="hint">'.indent(get_template_hint($tmp_room_thread_cap_hint)).'</p>';
				} else {
					if ($draw) {
						$n = get_draw_app_list(true);
						$head = (
							$target['pic']
							? $tmp_draw_next.':'
							: (
								$t
								? $tmp_draw_this.':
<span id="task-text">'.$t.'</span>'
								: $tmp_draw_free.':'
							)
						);
						$page['task'] = '
<p>'.indent($head).'</p>';
						$hint = '
<p class="hint">'.indent($n['list']).'</p>';
						if ($x = $n['noscript']) {
							$page['task'] .= $x;
							$page['subtask'] = $n['embed'].'
<div class="task">'.indent($hint).'</div>';
						} else {
							$w = explode(',', DRAW_LIMIT_WIDTH);
							$h = explode(',', DRAW_LIMIT_HEIGHT);
							$limit_hint = sprintf(
								$tmp_draw_limit_hint
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
							$t
							? $tmp_describe_this
							: (
								$room_type['single_active_thread']
								? $tmp_describe_free
								: $tmp_describe_new
							)
						);
						$page['task'] = get_template_form(
							array(
								'method' =>	'post'
							,	'name' =>	'describe'
							,	'min' =>	DESCRIBE_MIN_LENGTH
							,	'head' =>	$head
							,	'hint' =>	$tmp_describe_hint.($u_flag['nop'] ? '\\'.$tmp_no_play_hint : '')
							,	'filter' =>	$filter
							,	'checkbox' => (
									$u_opts['kbox']
									? array(
										'label' => $tmp_check_required
									,	'required' => 1
									)
									: ''
								)
							)
						);
					}
					if (!$u_opts['task_timer']) $page['data']['task']['autoupdate'] = TARGET_AUTOUPDATE_INTERVAL;
					$page['data']['task']['taken'] = T0;
					if ($t) {
						if ($target['pic']) {
							$src = (mb_strpos($t, ';') ? get_pic_resized_path(get_pic_normal_path($t)) : $t);
							$page['subtask'] = '
<img src="'.get_pic_url($src).'" alt="'.$t.'">'.$page['subtask'];
						}
						if ($room_type['lock_taken_task']) {
							$page['data']['task']['taken'] = $task_time;
							$page['data']['task']['deadline'] = $target['deadline'];
						}
						$a = array();
						if ($room_type['allow_text_op']) $a[] = ARG_DESC;
						if ($room_type['allow_image_op']) $a[] = ARG_DRAW;
						if ($a && data_get_full_threads(false)) {
							$page['data']['task'][ARG_DROP] = implode(',', $a);
						}
					} else {
						$post_type = ($draw?'text':'image');
						if ($room_type["allow_$post_type$post_rel"]) {
							$page['data']['task']['free'] = ($draw?ARG_DESC:ARG_DRAW);
						}
					}
				}
				if ($f = $target['count_free_tasks']) {
					$page['data']['task'][ARG_CHANGE] = implode(',', array_keys($f));
				}
				if ($t) {
					$page['data']['task']['skip'] = intval($target['thread']);
				} else
				if ($s = count($skip_list)) {
					$page['data']['task']['unskip'] = $s;
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
				$top = ($c?"
$tmp_room_count_threads	$tmp_room_count_posts":'');
				$page['content'] = "
archives = $arch_list_href$y
separator = \"$s\"
$top";
				foreach ($visible['list'] as $room => $n) {
					$a = mb_split_filter($room);
					$mid = array_pop($a);
					if (!$y && ($prev_type !== ($type = implode('/', $a)))) {
						if (false !== ($k = array_search($type, $cfg_game_type_dir))) {
							$page['content'] .= "

type = $type
type_title = $tmp_room_types_title[$k]$top";
						}
						$prev_type = $type;
					}
					if (!$type && $y) $room = $y.$room;
					if ($u_flag["mod_$room"]) $mid .= "$s(mod)";
					if ($u_room_default === $room) $mid .= "$s(home)";
					if ($a = $n['marked']) foreach ($a as $k => $v) $mid .= "$s$v$k[0]";
					if ($c) {
						$left = $n['threads now'].$s.$n['threads ever'];
						if ($v = $n['threads arch']) $left .= $s.$v.($t ? $s.$n['last arch'] : '');
						$right = $n['pics'].$s.$n['desc'].($t ? $s.$n['last post'] : '');
					} else {
						$left = ($n['threads arch']?'*':NB);
						$right = NB;
					}
					$page['content'] .= "
$left	$right	$mid";
				//* announce/frozen:
					if ($a = $n['anno']) {
						if (!$a['room_anno']) $page['content'] .= '	';
						foreach ($a as $k => $v) $page['content'] .= "	$tmp_announce[$k]: ".trim(
							preg_replace('~\s+~u', ' ',
							preg_replace('~<[^>]*>~', '',
							preg_replace('~<br[ /]*>~i', NL,
						$v))));
					}
				}
				unset($room);
			}
			$t = array();
			$a = '{';
			$b = ($c = '}').NL;
			$x = $tmp_room_types_name_example ?: 'test';
			$e = ", $x: ";
			foreach ($cfg_room_types as $k => $v) if ($r = $tmp_room_types_names[$k]) {
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
			$r = $tmp_room_types_title['all'] ?: '*';
			$f = ($r_type ? '' : "$tmp_room_types_select:\\
$a#$j$l|$r$m");
			foreach ($tmp_room_types as $k => $v) if ($r = $tmp_room_types_title[$k]) {
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
				$t = trim(implode('', $t)).'\\';
				$tmp_rooms_hint .= ($f ? '\\
[buttons r|\\'.indent($f).']' : '').'\\
[a|'.$tmp_room_types_hint.']\\
[hid|'.indent($t).']';
			}
			$page['task'] = get_template_form(
				array(
					'method' =>	'post'
				,	'name' =>	$qredir
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
		header('HTTP/1.1 303 To home room');
		header("Location: $room_list_href$room");
		exit;
	}
} else {

//* not registered ------------------------------------------------------------

	if ($etc) die('x');
	foreach ($cfg_dir as $k => $v) unset(${"qd_$k"});
	$page['welcome'] = $tmp_welcome_parts;
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

define(S, '. ');
$room_title = ($room_name == ROOM_DEFAULT ? $tmp_room_default : "$tmp_room $room_name");
$page['title'] = (
	$mod_title
	? "$tmp_mod_panel - $mod_title".S.(
		$ymd && $room
		? $room_title.S
		: ''
	)
	: (
		$qd_opts == 1
		? $tmp_options.S
		: (
			$qd_arch
			? (
				$room
				? $room_title.S
				: ''
			).$tmp_archive.S
			: (
				$qd_room
				? (
					$room
					? (
						$is_report_page
						? $tmp_report.S
						: ''
					).$room_title.S
					: $tmp_rooms.S
				)
				: ''
			)
		)
	)
).$top_title.(
	$qd_opts == 2
	? S.$tmp_draw_app_select
	: ''
);

if (!$is_report_page) {
	define(A, NL.'<a href="');
	$short = !!$u_opts['head'];
	$a_head = array(
		'/' => $top_title
	,	'..' => $tmp_rooms
	,	'.' => $room_title
	,	'a' => $tmp_archive
	,	'*' => $tmp_archives
	,	'?' => $tmp_options
	,	'~' => $tmp_draw_test
	,	'#' => $tmp_mod_panel
	);
	foreach ($a_head as $k => &$v) $v = '">'.(
		$short
		? $k
		: $v.(mb_substr($v, -1) == '.'?'':'.')
	).'</a>';

	if (GOD) {
		define(M, A.'.?'.LK_MOD_ACT);
		foreach ($tmp_mod_pages as $k => &$v) $mod_list .= M.'='.$k.'">'.$v.'</a><br>';
		$mod_link =
			'<u class="menu-head">'.indent(
				M.$a_head['#'].NL.
				'<u class="menu-top">'.
				'<u class="menu-hid">'.
				'<u class="menu-list">'.indent(
					$mod_list
				).'</u></u></u>'
			).'</u>';
	}

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
	$page['header'] = (
		$u_key
		? array(
			A.ROOTPRFX.$a_head['/']
		.	($short?$room_list_link:'')
		.	$room_link
		.	($short?'':$arch_link)
		, 'ar' =>
			$mod_link
		.	($short?$arch_link:'')
		.	$arch_list_link
		.	($short?'':$room_list_link)
		.	A.(DIR_DOTS && $qdir && $qd_opts?'.':ROOTPRFX.DIR_OPTS.($r ?: $t)).$a_head['?']
		)
		: array(
			A.ROOTPRFX.$a_head['/']
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
		$links = vsprintf(FOOT_NOTE, $tmp_foot_notes);
	}
	if (!$u_opts['times'] && $u_key) {
		define(TOOK, $took = '<!--?-->');
		if (TIME_PARTS) {
			time_check_point('inb4 template, u_opts = '.get_print_or_none($u_opts));
			$took = '<a href="javascript:'.(++$page['js'][0]).',toggleHide(took),took.scrollIntoView()">'.$took.'</a>';
			foreach ($tcp as $t => $comment) {
				$t = get_time_elapsed($t);
				$t_diff = ltrim(sprintf('%.6f', $t - $t_prev), '0.');
				$t = sprintf('%.6f', $t_prev = $t);
				$comment = mb_str_replace(NL, '<br>-', is_array($comment)?implode('<br>', $comment):$comment);
				$took_list .= NL."<tr><td>$t +</td><td>$t_diff:</td><td>$comment</td></tr>";
			}
		}
		$took = get_time_html().mb_str_replace_first(' ', NL, sprintf($tmp_took, $took));
	}
	if (
		($a = (array)$cfg_link_schemes)
	&&	($s = strtolower($_SERVER['REQUEST_SCHEME'] ?? $_SERVER['HTTPS'] ?? $a[0]))
	) {
		if ($s === 'off') $s = 'http'; else
		if ($s === 'on') $s = 'https';
		if (($i = array_search($s, $a)) !== false) unset($a[$i]);
		foreach ($a as $k) {
			$j = "$k://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]";
			$took .= NL.'<a href="'.$j.'">'.$tmp_link_schemes[$k].'</a>';
		}
	}
	foreach (array(
		'l' => $took
	,	'r' => $links
	) as $k => $v) if (strlen($v)) $footer .= NL.'<span class="'.$k.'">'.indent($v).'</span>';

	if ($footer) $page['footer'] = '<p class="hint">'.indent($footer).'</p>'.(
		$took_list
		? NL.'<table id="took" style="display:none">'.indent($took_list).'</table>'
		: ''
	);
	if ($v = $query[ARG_ERROR]) $page['report'] = $v;
	$page['anno'] = 1;
}

die(get_template_page($page));




//* POST: add/modify content *-------------------------------------------------

posting:

ob_start();

if ($u_key) {
	$post_status = (($_POST[ME] || $_POST[$qredir])?OK:'unkn_req');

	if (isset($_POST[$qredir])) goto after_posting;

//* options change ------------------------------------------------------------

	if (isset($_POST[$p = 'quit'])) {
		$post_status = 'user_quit';
		$u_key = $p;
	} else
	if ($p = array_filter($_POST, 'is_opt_arg', ARRAY_FILTER_USE_KEY)) {
		$post_status = 'user_opt';
		if (isset($p[OPT_PRFX.'reset'])) {
			$u_opts = 'default';
		} else {
			foreach ($cfg_opts_order as $i => $o)
			foreach ($o as $k) {
				$v = (isset($p[$j = OPT_PRFX.abbr($k)]) ? $p[$j] : '');
				if ($i === 'input') ${"u_$k"} = $v;
				else $u_opts[$k] = $v;
			}
		}
	} else

//* admin/mod actions ---------------------------------------------------------

	if (isset($_POST['mod']) && MOD && (($qd_room && $room) || (GOD && ($query[LK_MOD_ACT] === LK_USERLIST || $etc === '3')))) {
		$d = 'abcdefg';
		$k = array();
		$result = array();
		$done = 0;
		$failed = 0;
		foreach ($_POST as $i => $a) if (preg_match('~^m\d+_(\d+)_(\d+)_(\d+)$~i', $i, $m)) {
			$m[0] = $a;
			$act[$k[] = str_replace_first('_', $d[substr_count($a, '+')], $i)] = $m;
		}
		if ($act) {
			natsort($k);

			data_lock(LK_MOD_ACT);
			data_lock(LK_ROOM.$room);
			foreach (array_reverse($k) as $i) {
				$m = data_mod_action($act[$i]);	//* <- act = array(option name, thread, row, column)
				if ($m) {
					if (array_key_exists($m, $tmp_post_err)) ++$result[$m];
					else ++$done;
				} else ++$failed;
			}
			data_unlock();

			if ($result) $post_status = implode(ARG_ERROR_SPLIT, array_keys($result));
			else $post_status = ($done && !$failed?OK:'unkn_res');
		}
	} else
	if (!$qd_room || !$room); else	//* <- no posting outside room

//* report problem in active room ---------------------------------------------

	if (isset($_POST[$k = 'report']) && ($postID = $query['report_post'] ?: $etc) && (MOD || !(NO_MOD || $u_flag['nor']))) {
		$post_status = 'no_path';
		if (preg_match(PAT_REPORT, $postID, $r)) {
			$post_status = 'text_short';
			if (mb_strlen($t = trim_post($_POST[$k], REPORT_MAX_LENGTH)) >= REPORT_MIN_LENGTH) {
				data_lock(LK_ROOM.$room);
				$r['freeze'] = ($_POST['freeze'] || $_POST['stop'] || $_POST['check']);
				$r['report'] = $t;
				$r = data_log_report($r);
				$post_status = ($r > 0?OK:'unkn_res');
				data_unlock();
			}
		}
	} else
	if ($etc); else			//* <- no "etc" posting without report

//* skip current task ---------------------------------------------------------

	if (isset($_POST[$k = 'skip'])) {
		if (preg_match('~^\d+~', $_POST[$k], $digits)) {
			$add_qk = get_room_skip_list($digits[0]);
			$post_status = 'skip';
		}
	} else

//* process new text post -----------------------------------------------------

	if (isset($_POST[$k = 'describe'])) {
		$post_status = 'text_short';
		if (mb_strlen($x = $ptx = trim_post($_POST[$k], DESCRIBE_MAX_LENGTH)) >= DESCRIBE_MIN_LENGTH) {
			$unlim = trim($_POST[$k]);
			$n = mb_strlen($delim = '/');
			if (
				mb_substr($unlim, 0, $n) == $delim
			&&	mb_substr($unlim, -$n) == $delim
			&&	mb_substr_count($x = trim($x, $spaced = " $delim "), $spaced)
			) {
				$x = '<i class="poem">'
				.	mb_str_replace($spaced, '<br>',
					preg_replace("~\s+($delim\s+){2,}~", '<br><br>',
						trim($x, $spaced)
					))
				.'</i>';
			}
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
			$ptx = "time: $t
file: $upload[name]";
			if ($upload['error']) {
				$log = print_r($_FILES, true);
			} else {
				$x = $upload['type'];
				$file_type = mb_strtolower(mb_substr($x, mb_strpos_after($x, '/')));
				if (in_array($file_type, $cfg_draw_file_types)) {
					$file_size = $upload['size'];
					$txt = "$t,file: $upload[name]";
				} else {
					$log = "File type $x not allowed.";
				}
			}
		} else {
			$post_data_size = strlen($post_data = $_POST[$k]);
			$txt = $ptx = ($_POST['txt'] ?: '0-0,(?)');
			unset($_POST[$k]);
	//* metadata, newline-separated key-value format:
			if (false !== mb_strpos($txt, NL)) {
				$a = explode(',', 'app,active_time,draw_time,open_time,t0,time,used');	//* <- to add to picture mouseover text
				$b = explode(',', 'bytes,length');					//* <- to validate
				$x = mb_split_filter($txt, NL);
				$y = array();
				$z = 0;
				foreach ($x as $line) if (preg_match('~^(\w+)[\s:=]+(.+)$~u', $line, $m) && ($k = mb_strtolower($m[1]))) {
					if (in_array($k, $a)) $y[$k] = $m[2]; else
					if (in_array($k, $b)) $z = $m[2];
				}
				if ($z && $z != $post_data_size) {
					$post_status = 'file_part';
					$log = "$post_data_size != $z";
				} else {
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
					$txt = "$t,$a";
				}
			} else
	//* metadata, legacy CSV:
			if (preg_match('~^(?:(\d+),)?(?:([\d:]+)|(\d+)-(\d+)),(.*)$~is', $txt, $t)) {
				if ($t[2]) $txt = $t[2].','.$t[5];
				else {
					if (!$t[4]) $t[4] = T0.'000';
					if (!$t[3] || $t[3] == $t[4]) $t[3] = ($t[1] ?: $target['time']).'000';
					if (!$t[3]) $t[3] = $t[4];
					$txt = "$t[3]-$t[4],$t[5]";
				}
			}
			if (!$log) {
	//* check pic content: "data:image/png;base64,EncodedFileContent"
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
		&&	is_file($pic_final_path = get_pic_subpath($fn, true))
		) {
			$post_status = 'file_dup';
			$log = $fn;
		} else {
	//* save pic file:
			if (!$upload && ($log = file_put_contents($f = $pic_final_path, $file_content)) != $x) {
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
				if ((($resize = ($w > DRAW_PREVIEW_WIDTH)) || $x < 9000) && $x > 0) {
					$post_status = 'pic_fill';
					$i = "imageCreateFrom$file_type";
					$log = imageColorAt($pic = $i($f), 0, 0);
					for ($x = $w; --$x;)
					for ($y = $h; --$y;) if (imageColorAt($pic, $x, $y) != $log) break 2;
				}
	//* invalid image:
			} else $x = 0;
	//* ready to save post:
			if ($x > 0) {
				if ($upload && !rename($f, $pic_final_path)) {
					$x = 0;
					$post_status = 'file_put';
				} else {
					if ($resize) {
						$fwh = $fn .= ";$w*$h, ";
						$fn .= format_filesize($file_size);
					} else if ($pic) {
						imageDestroy($pic);
					} else $log = "imageDestroy(none): $f";
	//* gather post data fields to store:
					$x = array($fn, trim_post($txt));
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
	if ($ptx) {
		if ($log) {
			$op = ' = {';
			$ed = NL.'}';
			$i = NL.'	';
			$t = '';
			if ($target || data_aim()) foreach ($target as $key => $val) $t .= "$i$key: $val";
			$ptx = preg_replace('~\v+~u', $i, trim($ptx));
			data_log_action("Denied $post_status: $log
Post$op$i$ptx$ed
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
	'OK' => $tmp_post_ok
,	'NO' => $tmp_post_err
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

if ($query[LK_MOD_ACT]) {
	$l = $qfix;
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
			if (in_array($v, $cfg_game_type_dir)) $r_type = $v;
		}
		if (!$r_type) $r_type = GAME_TYPE_DEFAULT;
		if (!$room && ($r_type || !GAME_TYPE_DEFAULT)) $room = reset($a);
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
	$l = ROOTPRFX;
	if (strlen($v = encode_URL_parts(array_filter($qpath, 'strlen')))) $l .= (strlen($qpath['etc'])?$v:"$v/");
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
						$v[] = $k = abbr($k);
						if (!$s && mb_strlen($k) > 1) $s = '.';
					}
					if (strlen($v = implode($s, $v))) $a[] = "$i:$v$s";
				}
			}
			$a = encode_URL_parts($a);
		}
		$s = 'Set-Cookie: ';
		$x = '; expires='.gmdate(DATE_COOKIE, ($a ? T0 + QK_EXPIRES : 0)).'; Path='.ROOTPRFX;
		$a = ME."=$a";
		header("$s$a$x");
		if ($add_qk) header("$s$add_qk$x");
	}
	if ($room_type['single_active_thread']) {
		$query = (
			array_filter($query, 'is_desc_arg', ARRAY_FILTER_USE_KEY)
		?:	array_filter($query, 'is_draw_arg', ARRAY_FILTER_USE_KEY)
		);
	} else unset($query);
} else {
	$query = array_filter($query, 'is_draw_arg', ARRAY_FILTER_USE_KEY);
	if ($_POST['report'] && $postID) $query['report_post'] = $postID;
	if ($p) $query[ARG_ERROR] = $p;
}

if ($query && is_array($query)) {
	$q = '';
	ksort($query);
	foreach ($query as $k => $v) $q .= ($q?'&':'?').$k.(strlen($v)?"=$v":'');
	$l .= $q;
}

//* show pic processing progress ----------------------------------------------

$ri = 0;
if ($f = $pic_final_path) {

	function pic_opt_get_size($f) {
		global $ri, $tmp_no_change, $tmp_post_progress, $TO;
		$old = filesize($f);
		if ($ri) {
			echo format_filesize($old).$TO;
			flush();
		}
		$program = optimize_pic($f);
		if ($old === ($new = filesize($f))) {
			if ($ri) echo $tmp_no_change;
			return '';
		} else {
			$f = format_filesize($new);
			if ($ri) echo "$f, $tmp_post_progress[program]: $program.";
			return $f;
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
		if (false === mb_strpos('.,;:?!', mb_substr($msg, -1))) $msg .= '.';
		$AT = ' &mdash; ';
		$BY = ' x ';
		$TO = ' &#x2192; ';
		$ri = max(intval(POST_PIC_WAIT), 1);

	//* this is not working here, must set in php.ini:
	//	ini_set('zlib.output_compression', 'Off');
	//	ini_set('output_buffering', 'Off');

	//* this is for nginx and gzip:
		if (WS_NGINX) {
			header('X-Accel-Buffering: no');
			header('Content-Encoding: none');
		}

	//* this is for the browser, some may be configured to prevent though:
		header("Refresh: $ri; url=$l");

		echo '<!doctype html>
<html lang="'.$lang.'">
<head>
	<meta charset="'.ENC.'">
	<title>'.$tmp_sending.'</title>
	<style>
		html {background-color: #eee;}
		body {background-color: #f5f5f5; margin: 1em; padding: 1em; font-family: sans-serif; font-size: 14px;}
		p {padding: 0 1em;}
		p:first-child {background-color: #ddd; padding: 1em;}
		p:last-child {background-color: #def; padding: 1em; margin-block-end: 0;}
	</style>
</head>
<body>
<p>'.get_time_html()."$AT$tmp_post_progress[starting].".pic_opt_get_time()."$tmp_post_progress[opt_full]: ";
	}
	$changed = pic_opt_get_size($f);

	if ($pic && $resize) {
		if ($changed) data_rename_last_pic($fn, $fwh.$changed);	//* <- rewriting already stored post is a crutch, but nothing better for now
		$x = DRAW_PREVIEW_WIDTH;
		$y = round($h/$w*$x);
		$z = filesize($f);
		if ($ri) echo pic_opt_get_time()."$tmp_post_progress[low_res]: $w$BY$h$TO$x$BY$y";
		$p = imageCreateTrueColor($x,$y);
		imageAlphaBlending($p, false);
		imageSaveAlpha($p, true);
		imageCopyResampled($p, $pic, 0,0,0,0, $x,$y, $w,$h);
		imageDestroy($pic);
		$i = "image$file_type";
		$i($p, $f = get_pic_resized_path($f));
		if ($ri) echo pic_opt_get_time()."$tmp_post_progress[opt_res]: ";
		pic_opt_get_size($f);

		if ($file_type == 'png' && ($z < filesize($f))) {
			if ($ri) echo pic_opt_get_time()."$tmp_post_progress[low_bit]: 255";
			$c = imageCreateTrueColor($x,$y);
			imageCopyMerge($c, $p, 0,0,0,0, $x,$y, 100);
			imageTrueColorToPalette($p, false, 255);
			imageColorMatch($c, $p);
			imageDestroy($c);
			$i($p, $f);
			imageDestroy($p);
			if ($ri) echo pic_opt_get_time()."$tmp_post_progress[opt_res]: ";
			pic_opt_get_size($f);
		} else {
			imageDestroy($p);
		}
	}
	data_unlock();

	if ($ri) {
		echo pic_opt_get_time().$msg.'</p>
<p>'.sprintf($tmp_post_progress['refresh'], $l, format_time_units($ri)).'</p>
</body>
</html>';
	} else ob_end_clean();
}

//* use Refresh header for printing content.
//* use Location header otherwise:

if (!headers_sent()) header("Location: $l");

?>
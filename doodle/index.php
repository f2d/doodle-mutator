<?php

define(T0, end($t = explode(' ',microtime())));
define(ME, 'me');
define(POST, 'POST' == $_SERVER['REQUEST_METHOD']);

if (POST) {
	if (!isset($_REQUEST[ME])) goto post_refresh;	//* <- no anonymous posting
	ignore_user_abort(true);
}
ob_start();

define(NAMEPRFX, 'd');
define(M0, $t[0]);
define(GET_Q, strpos($_SERVER['REQUEST_URI'], '?'));
define(ROOTPRFX, substr($s = $_SERVER['PHP_SELF'], 0, strrpos($s, '/')+1));

if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
	function strip_magic_slashes(&$value, $key) {$value = stripslashes($value);}
	$gpc = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
	array_walk_recursive($gpc, 'strip_magic_slashes');
}

require(NAMEPRFX.'.cfg.php');
foreach ($cfg_dir as $k => $v) define('DIR_'.strtoupper($k), $v.'/');

require(NAMEPRFX.'.fu.php');
require(NAMEPRFX.'.db.php');

//* copycat from http://www.dyeager.org/blog/2008/10/getting-browser-default-language-php.html
if (isset($_SERVER[$h = 'HTTP_ACCEPT_LANGUAGE'])) {
	foreach (explode(',', $_SERVER[$h]) as $v) if (preg_match('~(\S+);q=([.\d]+)~ui', $v, $m)) {
		$a[$m[1]] = (float)$m[2];
	} else	$a[$v] = 1.0;
//* check for highest q-value. No q-value means 1 by rule
	$q = 0.0;
	foreach ($a as $k => $v) if ($v > $q && in_array($l = strtolower(substr($k, 0, 2)), $cfg_langs)) {
		$q = (float)$v;
		$lang = $l;
	}
}
require(NAMEPRFX.".cfg.$lang.php");
define(FROZEN_HELL, data_global_announce('stop'));
data_log_ref();

ob_end_clean();

function time_check_point($comment) {global $tcp; $tcp[microtime()][] = $comment;}
time_check_point('done cfg, inb4 user settings');

//* keep legacy formats until cookies expire in 3 years:
$u_opts = array();
$opt_sufx = 'aoprui';
$opt_name = array('opta', 'opti', 'trd_per_page', 'draw_max_recovery', 'draw_max_undo', 'draw_time_idle');
$opt_lvls = array('a' => 'admin', 'i' => 'check');

if ($me = URLdecode($_REQUEST[ME])) {
	if (false === strpos($me, '/')) {
//* v1, one separator for all, like "hash_etc_etc":
		list($u_qk, $u_opti, $u_trd_per_page, $u_room_default) = explode('_', $me, 4);
	} else
	if (false === strpos($me, ':')) {
//* v2, plain ordered numbers, like "hash/vvv_val_val/etc":
		list($u_qk, $i, $u_room_default, $u_draw_app) = explode('/', $me, 4);
		if (!preg_match_all('~(\d+)([a-z])~', strtolower($i), $m)) {
			list($u_opti, $u_trd_per_page, $u_draw_max_undo) = explode(false !== strpos($i, '_')?'_':'.', $i);
		} else {
//* v3, abbreviated suffixes for middle part, like "01adm_0010opt_30per_page_99undo", or "0a1o2p3u", in any order:
			foreach ($m[1] as $k => $v) if (false !== ($i = strpos($opt_sufx, $m[2][$k]))) ${'u_'.$opt_name[$i]} = $v;
		}
	} else {
//* v4, key prefixes for all, like "hash/key:val/key:val/etc:etc", in any order:
		$a = explode('/', $me);
		$u_qk = array_shift($a);
		$key_value_pairs = $a;
	}
	if (($q = trim($_POST[ME])) && $u_qk != $q) $u_qk = $q;
	if ($u_qk && data_check_u($u_qk, $q)) {
		if (LOG_IP) data_log_ip();
		if ($u_flag['ban']) die(get_template_page(array(
			'lang' => $lang
		,	'title' => $tmp_ban
		,	'task' => $tmp_ban
		,	'body' => 'burnt-hell'
		)));

		if (!$u_flag['god']) unset($cfg_opts_order['admin']);
		if ($key_value_pairs) {
			$i = 'input';
			foreach ($a as $key_value_pair) {
				$b = explode(':', $key_value_pair);
				$k = reset($b);
				$v = end($b);
				if (strlen($v)) {
					if (count($b > 2) && $b[1] == 'base64') $v = base64_decode($v);
					if (array_key_exists($k, $cfg_opts_order[$i])) {
						${'u_'.$cfg_opts_order[$i][$k]} = $v;
					} else
					if (array_key_exists($k, $cfg_opts_order) && $k !== $i) {
						$y = (false !== strpos($v, '.') ? explode('.', $v) : str_split($v));
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
if (TIME_PARTS) time_check_point('GOD defined'); else unset($tcp);

$etc = trim($_REQUEST['etc'], '/');
$qdir = strtolower($_REQUEST['dir']);
$qredir = $qdir.'s';
foreach ($cfg_dir as $k => $v) if ($qdir == $v) ${'qd_'.$k} = 1;

if (FROZEN_HELL && !GOD && !($u_key && $qd_opts) && !$qd_arch) {
	if (POST) goto post_refresh;
	die($etc == '-'?'-':get_template_page(array(
		'lang' => $lang
	,	'title' => $tmp_stop_all.' '.$tmp_title.'.'
	,	'header' => array(
			'<a href="'.ROOTPRFX.'">'.$tmp_title.'.</a>'
		, 'r' =>
			'<a href="'.ROOTPRFX.DIR_ARCH.'">'.$tmp_archive.'.</a>'.NL.
			'<a href="'.ROOTPRFX.DIR_OPTS.'">'.$tmp_options.'.</a>'
		)
	,	'task' => $tmp_stop_all
	)));
}

$query = array();
if (GET_Q && ($s = substr($_SERVER['REQUEST_URI'], GET_Q+1))) {
	foreach (explode('&', $s) as $chain) {
		$a = explode('=', $chain);
		$v = (count($a) > 1 ? array_pop($a) : '');
		foreach ($a as $k) $query[URLdecode($k)] = URLdecode($v);
	}
}

if ($qdir) {
	if ($l = mb_strlen($room = trim_room($room_url = URLdecode($_REQUEST['room'])))) define(R1, $l = (mb_strlen(ltrim($room, '.')) <= 1));
} else {
	if ($u_key && !$u_room_default) $qd_opts = 1;
	if (GOD) rewrite_htaccess();
}

define(MOD, GOD || $u_flag['mod'] || $u_flag['mod_'.$room]);
if (TIME_PARTS) time_check_point('MOD defined, inb4 action fork');




if (POST) {//*	--------	post/setting/reg	--------
ob_start();

if ($u_key) {
	$post_status = (($_POST[ME] || $_POST[$qredir])?OK:'unkn_req');

	if (isset($_POST[$qredir])) goto post_refresh;

//* options change ------------------------------------------------------------

	if (isset($_POST[$p = 'quit'])) {
		$post_status = 'user_quit';
		$u_key = $p;
	} else
	if (isset($_POST[$p = O.'o'])) {
		$post_status = 'user_opt';
		if (strlen($_POST[$p]) > 1) $u_opts = 'default';
		else {
			foreach ($cfg_opts_order as $i => $o)
			foreach ($o as $k) {
				$v = (isset($_POST[$p = O.abbr($k)]) ? $_POST[$p] : '');
				if ($i === 'input') ${"u_$k"} = $v;
				else $u_opts[$k] = $v;
			}
		}
	} else

//* admin/mod actions ---------------------------------------------------------

	if (isset($_POST['mod']) && MOD && (($qd_room && $room) || (GOD && ($query['users'] || $etc === '3')))) {
		$d = 'abcdefg';
		$k = array();
		foreach ($_POST as $i => $a) if (preg_match('~^m\d+_(\d+)_(\d+)_(\d+)$~i', $i, $m)) {
			$m[0] = $a;
			$act[$k[] = str_replace_first('_', $d[substr_count($a, '+')], $i)] = $m;
		}
		if ($act) {
			natsort($k);
			if (!data_lock($room)) {
				$post_status = 'no_lock';
			} else {
				foreach (array_reverse($k) as $i) {
					$m = data_mod_action($act[$i]);	//* <- act = array(option name, thread, row, column)
					if ($post_status != 'unkn_res') $post_status = ($m?OK:'unkn_res');
				}
				data_unlock();
			}
		}
	} else
	if (!$qd_room || !$room); else	//* <- no posting outside room

//* report problem in active room ---------------------------------------------

	if (isset($_POST['report']) && ($postID = $query['report_post'] ?: $etc) && (MOD || !(R1 || $u_flag['nor']))) {
		$post_status = 'no_path';
		if (preg_match(PAT_DATE, $postID, $r) && ($postID == $r[0])) {	//* <- r = array(t-r-c, t-r, thread, row, column)
			$post_status = 'text_short';
			if (mb_strlen($r[1] = trim_post($_POST['report'], REPORT_MAX_LENGTH), ENC) >= REPORT_MIN_LENGTH) {
				if (!data_lock($room)) {
					$post_status = 'no_lock';
				} else {
					$post_status = (
						data_log_report($r, $_POST['freeze'] || $_POST['check']) > 0
						? OK
						: 'unkn_res'
					);
					data_unlock();
				}
			}
		}
	} else
	if ($etc); else			//* <- no "etc" posting without report

//* skip current task ---------------------------------------------------------

	if (isset($_POST['skip'])) {
		if (preg_match('~^\d+~', $_POST['skip'], $digits)) {
			$i = $digits[0];
			list($a, $r) = get_room_skip_name($room);
			if ($q = get_room_skip_list($a)) {
				array_unshift($q, $i);
				$i = implode('/', $q);
			}
			$add_qk = "$a=$r/$i";
			$post_status = 'skip';
		}
	} else

//* process new text post -----------------------------------------------------

	if (isset($_POST['describe'])) {
		$post_status = 'text_short';
		if (mb_strlen($x = $ptx = trim_post($_POST['describe'], DESCRIBE_MAX_LENGTH), ENC) >= DESCRIBE_MIN_LENGTH) {
			if (data_lock($room)) {
				$unlim = trim($_POST['describe']);
				$n = strlen($delim = '/');
				if (
					substr($unlim, 0, $n) == $delim
				&&	substr($unlim, -$n) == $delim
				&&	substr_count($x = trim($x, $spaced = " $delim "), $spaced)
				) {
					$x = '<i class="poem">'
					.	str_replace($spaced, '<br>',
						preg_replace("~\s+($delim\s+){2,}~", '<br><br>',
							trim($x, $delim.' ')
						))
					.'</i>';
				}
				$post_status = 'new_post';
			} else $post_status = 'no_lock';
		}
	} else

//* process new pic post ------------------------------------------------------

	if (isset($_POST['pic'])) {
		$post_status = 'file_pic';
		$log = 0;
		$ppl = strlen($pp = $_POST['pic']);
		$txt = (($ptx = $_POST['txt']) ?: '0-0,(?)');
	//* metadata, got newline separated tagged format:
		if (false !== strpos($txt, NL)) {
			$a = explode(',', 'app,active_time,draw_time,open_time,t0,time,used');	//* <- to add to picture mouseover text
			$b = explode(',', 'bytes,length');					//* <- to validate
			$x = preg_split('~\v+~u', $txt);
			$y = array();
			$z = 0;
			foreach ($x as $line) if (preg_match('~^(\w+)[\s:=]+(.+)$~u', $line, $m) && ($k = strtolower($m[1]))) {
				if (in_array($k, $a)) $y[$k] = $m[2]; else
				if (in_array($k, $b)) $z = $m[2];
			}
			if ($z && $z != $ppl) {
				$post_status = 'file_part';
				$log = "$ppl != $z";
			} else {
				if (!$y['time']) {
					$t = array($target['time'].'000', T0.'000');
					$a = ($x = $y['open_time']) ? explode('-', $x) : array();
					$b = ($x = $y['draw_time']) ? explode('-', $x) : array();
					if ($b[0] == $b[1]) $b[0] = 0;
					foreach ($t as $k => $v) $t[$k] = $b[$k] ?: $a[$k] ?: $v;
					$y['time'] = "$t[0]-$t[1]";
				} else
				if (!preg_match('~^(\d+:)+\d+$~', $y['time'])) {
					$t = array(($y['t0'] ?: $target['time']).'000', T0.'000');
					if (preg_match('~^(\d+)\D+(\d+)$~', $y['time'], $m)) {
						if ($m[1] && $m[1] != $m[2]) $t[0] = $m[1];
						if ($m[2]) $t[1] = $m[2];
					}
					$y['time'] = "$t[0]-$t[1]";
				}
				$t = $y['time'] ?: '0-0';
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
		if ($log); else
	//* parse pic content:
		if ((count($a = explode(',', $pp, 2)) < 2)
			|| !(($png = strpos($a[0], 'png'))
				||   strpos($a[0], 'jpeg'))
			|| (false === ($raw_data = base64_decode($a[1])))
		) $log = 'invalid, '.$ppl.' bytes: '.($ppl > REPORT_MAX_LENGTH ? substr($pp, 0, REPORT_MAX_LENGTH).'(...)' : $pp);
		else
		if (($x = strlen($raw_data)) > DRAW_MAX_FILESIZE) {
			$post_status = 'file_size';
			$log = $x;
		} else
		if (is_file($f = get_pic_subpath($fn = ($md5 = md5($raw_data)).($png?'.png':'.jpg'), 1))) {
			$post_status = 'file_dup';
			$log = $fn;
		} else
		if (!data_lock($room)) {
			$post_status = 'no_lock';
		} else {
	//* save pic file:
			if (($log = file_put_contents($f, $raw_data)) != $x) {
				$x = 0;
				$post_status = 'file_put';
			} else
			if ($sz = getImageSize($f)) {
				foreach ($tmp_whu as $k => $v)
				if ($a = (
					get_const('DRAW_LIMIT_'.$v)
				?:	get_const('DRAW_DEFAULT_'.$v)
				)) {
					list($a, $b) = preg_split('~\D+~', $a);
					$y = ($b ?: $a);
					$z = ${$tmp_wh[$k]} = $sz[$k];
					if (($a && $z < $a) || ($y && $z > $y)) {
						$x = 0;
						$post_status = 'pic_size';
						$log = "$sz[0]x$sz[1]";
						break;
					}
				}
				if ($x > 0 && ($x < 9000 || $w > DRAW_PREVIEW_WIDTH)) {
					$post_status = 'pic_fill';
					$g = ($png?'PNG':'JPEG');
					$i = "imageCreateFrom$g";
					$log = imageColorAt($pic = $i($f), 0, 0);
					for ($x = $w; --$x;)
					for ($y = $h; --$y;) if (imageColorAt($pic, $x, $y) != $log) break 2;
				}
			} else $x = 0;		//* <- post denied: image decoding error
	//* save post:
			if ($x > 0) {
				optimize_pic($f);
				if ($w > DRAW_PREVIEW_WIDTH) {
					$fn .= ";$w*$h, ".format_filesize($z = filesize($f));
					$p = imageCreateTrueColor($x = DRAW_PREVIEW_WIDTH, $y = round($h/$w*$x));
					imageAlphaBlending($p, false);
					imageSaveAlpha($p, true);
					imageCopyResampled($p, $pic, 0,0,0,0, $x,$y, $w,$h);
					imageDestroy($pic);
					$i = "image$g";
					$i($p, $f = get_pic_resized_path($f));
					optimize_pic($f);
					if ($png && ($z < filesize($f))) {
						$c = imageCreateTrueColor($x, $y);
						imageCopyMerge($c, $p, 0, 0, 0, 0, $x, $y, 100);
						imageTrueColorToPalette($p, false, 255);
						imageColorMatch($c, $p);
						imageDestroy($c);
						$i($p, $f);
						imageDestroy($p);
						optimize_pic($f);
					} else	imageDestroy($p);
				}
	//* gather post data fields to store:
				$x = array($fn, trim_post($txt));
				if (LOG_UA) $x[] = trim_post($_SERVER['HTTP_USER_AGENT']);
				$post_status = 'new_post';
			} else if (is_file($f)) unlink($f);
		}
	}

//* write new post to a thread ------------------------------------------------

	if ($post_status == 'new_post') {
		data_aim();
		$x = data_log_post($x);
		data_unlock();
		$t = array();
		if ($log = $x['fork']) $t[] = 'trd_miss';
		if ($log = $x['cap']) $t[] = 'trd_max'; else
		if (!$x['post']) $t[] = 'unkn_res';
		if (is_array($x = $x['arch']) && $x['done']) $t[] = 'trd_arch';
		if (count($t)) $post_status = implode('!', $t);
	}

//* after user posting --------------------------------------------------------

	if ($ptx) {
		if ($log) {
			$op = ' = {';
			$ed = NL.'}';
			$i = NL.'	';
			$t = '';
			if ($target || data_aim()) foreach ($target as $key => $val) $t .= "$i$key: $val";
			$ptx = preg_replace('~\v+~u', $i, trim($ptx));
			data_log_adm("Denied $post_status: $log
Post$op$i$ptx$ed
Target$op$t$ed"
			);
		} else if (!$u_room_default) $u_room_default = $room;
	}
} else

//* register new user ---------------------------------------------------------

if (isset($_POST[ME]) && strlen($me = trim_post($_POST[ME], USER_NAME_MAX_LENGTH)) >= USER_NAME_MIN_LENGTH) {
	$post_status = (data_log_user($u_key = md5($me.T0.substr(M0,2,3)), $me)?'user_reg':'unkn_res');
}




} else {//*	--------	view, not post	--------





//* mod panel -----------------------------------------------------------------

	if (GOD && (($q = isset($query['mod'])) || ($etc && strlen(trim($etc, '-'))))) {
		$a = array_keys($tmp_mod_pages);
		if ($q) {
			$q = $query['mod'] ?: reset($a);
			$do = $query['do'];
			$i = intval($query['id']);
		} else {
			$q = $a[intval($etc)-1];
			if ($i = strpos($etc, '-')) {
				$i = intval(substr($etc, $i+1));
				$a = array_keys($tmp_mod_files);
				$do = $a[$i-1];
			}
		}
		if ($q == 'users' && $i) {
			die(get_template_page(array(
				'title' => $tmp_mod_pages[3].': #'.$i
			,	'content' => (
					($a = data_get_user_info($i))
					? array_merge(array(
						'Current date' => date(TIMESTAMP, T0)
					,	'User ID' => $i
					), $a)
					: $tmp_empty
				)
			), ':'.NL));
		}
		$lnk = $t = '';
		$mod_page = $mod_p = $tmp_mod_pages[$q] ?: $tmp_empty;

		if ($q == 'logs') {
			$day = $query['day'] ?: $etc;
			$ymd = preg_match(PAT_DATE, $day, $m);
			if ($l = data_get_mod_log()) {
				$data_attr['content']['type'] = 'reports';
				$content = '
day_link = ?mod=logs&day=';
				if ($ymd) {
					exit_if_not_mod(data_get_mod_log($day, 1));
					if ($a = data_get_mod_log($mod_page = $day)) {
						$content .= '
images = '.ROOTPRFX.DIR_PICS.'
rooms = '.ROOTPRFX.DIR_ROOM.'
flags = a
'.implode(NL, array_map(function($k, $v) {
	return "
room = $k".
	preg_replace('~(\v\S+)\s+(\S+)\s+~u', '$1	$2	',	//* <- transform data fields
	preg_replace('~\h+~u', ' ',
	preg_replace('~<br[^>]*>(\d+)([^\d\s]\S+)\s~ui', NL.'$1	',	//* <- keep multiline entries atomic
	preg_replace('~\v+~u', '<br>',
	NL.htmlspecialchars($v)))));
}, array_keys($a), $a));
					}
				}

				$last = end(end($l));
				$last = data_get_mod_log(key($l).'-'.$last, 1);
				if (!$ymd) exit_if_not_mod($last);

				foreach ($l as $ym => $d) $lnk .= ($lnk?'</p>':'').'
<p>'.$ym.'-'.implode(',', $d);
				$lnk .= ' <small>'.date('H:i:s', $last).'</small></p>';
			}
		} else
		if ($q == 'files') {
			foreach ($tmp_mod_files as $k => $v) $lnk .= '
<p><a href="?mod='.$q.'&do='.$k.'">'.str_replace_first(' ', '</a> ', $v).'</p>';
			if ($do) {
				ignore_user_abort(true);
				$a = intval($a[1]);
if (TIME_PARTS) time_check_point('ignore user abort');
				if ($do == 'arch') {
					require_once(NAMEPRFX.'.arch.php');
					$t = data_archive_rewrite();
				} else
				if ($do == 'post_count') {
					$t = data_post_refresh(true);
				} else
				if ($do == 'img2subdir') {
					foreach (get_dir_contents($d = DIR_PICS) as $f) if (is_file($old = $d.$f)) {
						$new = get_pic_subpath($f, 1);
						$t .=
NL.(++$a)."	$old => $new	".($old == $new?'same':(rename($old, $new)?'OK':'fail'));
					}
if (TIME_PARTS && $a) time_check_point("done $a pics");
				} else
				if (substr($do, 0,3) == 'hta') {
					$t = rewrite_htaccess(substr($do, -5) == 'write');
				} else {
					$t = data_fix($do);
				}
				if (!$t) $t = $tmp_no_change;
			}
		} else
		if ($q == 'vars') {
			exit_if_not_mod();				//* <- never exits, to check HTTP_IF_MODIFIED_SINCE, etc
			$t = '_SERVER = '		.print_r($_SERVER, true)
			.NL.'strip magic slashes = '	.print_r($gpc ?: 'off'.NL, true)
			.NL.'DATE_RFC822 = '		.gmdate(DATE_RFC822, T0)
			.NL.'DATE_RFC2822 = '		.gmdate('r', T0);
		} else
		if ($q) {
			exit_if_not_mod(data_get_mod_log($q, 1));
			if ($t = data_get_mod_log($q)) {
				if ($q == 'users') {
					$content .= "
left_link = ?mod=users&id=
left = $tmp_mod_user_info
right = $tmp_mod_user_hint
flags = cgu
v,$u_num,u	v
".trim(
preg_replace('~(\V+)(	\V+)	(\V+)\+\V+(	\V+?)~Uu', '$3,$1$4$2',	//* <- transform data fields
NL.$t));
				} else
				if ($q == 'reflinks') {
					$content .= '
flags = c'.NL.
preg_replace('~(\d+)([^\d\s]\V+)?	(\V+)~u', '$1	$3', $t);	//* <- transform data fields
				}
				$data_attr['content']['type'] = $q;
				$lnk .= get_template_form(array('filter' => 1));
			}
		}
		if (!$content) $textarea = $t;				//* <- dump plain text as is
		$task = '
<p>'.$mod_p.'</p>'.($lnk || $content || $textarea ? $lnk : $tmp_empty);
		$js['mod']++;
		$js[0]++;
	} else

//* archived threads ----------------------------------------------------------

	if ($qd_arch) {
		require_once(NAMEPRFX.'.arch.php');
		$search = data_get_archive_search_terms();

//* archive threads list ------------------------------------------------------

		if ($room && ($thread_count = data_get_archive_count())) {
			exit_if_not_mod(data_get_archive_mtime());

			if (!$search) {
				$content = '
images = '.DIR_THUMB.'
image_ext = '.THUMB_EXT.'
page_ext = '.PAGE_EXT.'
on_page = '.(!R1 ? ($u_trd_per_page ?: TRD_PER_PAGE) : TRD_PER_PAGE.'
start = '.max(0, $thread_count - TRD_PER_PAGE)).'
total = '.$thread_count.($u_key?'':'
last = <a href="'.$thread_count.'.htm">'.$thread_count.'</a><!-- static link for scriptless bots -->');
				$data_attr['content']['type'] = 'archive pages';
			}
		} else

//* archive rooms list --------------------------------------------------------

		if ($visible = data_get_visible_archives()) {
			exit_if_not_mod($visible['last']);

			if (!$search) {
				if ($c = !$u_opts['count']) $content = "
$tmp_arch_last	$tmp_arch_count";
				foreach ($visible['list'] as $room => $n) $content .= ($c ? "
$n[last]	$n[count]	$room" : NL.NB.'	'.NB.'	'.$room);
				$room = '';
				$data_attr['content']['type'] = 'archive rooms';
			}
		} else $search = 0;

//* archive posts search ------------------------------------------------------

		if (is_array($search)) {
			$task = get_template_form(
				array(
					'head' =>	$tmp_archive
				,	'select' =>	$tmp_archive_find_by
				,	'submit' =>	$tmp_archive_find
				,	'hint' =>	$search || $room ? '' : $tmp_archive_hint
				,	'min' =>	FIND_MIN_LENGTH
			/*	,	'checkbox' =>	array(
						'hint' => $tmp_regex_hint
					,	'each' => '_regex'
					)
			*/	)
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
				$task .= '
<p class="hint" id="research">'.indent($tmp_archive_found.$research).'</p>';
				if ($found = data_archive_find_by($search)) {
					$flags = 'a';
					if (!$u_opts['count']) $flags .= 'c';
					$content = '
page_ext = '.PAGE_EXT."
flags = $flags
".$found;
					$data_attr['content']['type'] = 'archive found';
				}
			}
			$js[0]++;
		}
		if (!$content) $task .= $tmp_empty;
	} else

//* draw test -----------------------------------------------------------------

	if (($qd_opts || !$qdir) && GET_Q) {
		$qd_opts = 2;
		$n = get_draw_app_list();
		$icon = $n['name'];
		$task = '
<p>'.$tmp_draw_free.':</p>
<p class="hint">'.$tmp_draw_hint.'</p><noscript>
<p class="hint">'.$tmp_require_js.'</p></noscript>';
		$subtask = '
<script id="'.$n['name'].'-vars" src="'.$n['src'].'" data-vars="'.get_draw_vars().'"></script>
<div class="task">'.indent('<p class="hint">'.indent($n['list']).'</p>').'</div>';
	} else

if ($u_key) {

//* options -------------------------------------------------------------------

	if ($qd_opts) {
		$data_attr['content']['type'] = 'options';
		$draw_app = implode(';', array(
			(array_search($u_draw_app, $cfg_draw_app) ?: 0)
		,	implode(',', $cfg_draw_app)
		,	implode(',', $tmp_draw_app)
		,	'?draw_app=*'
		));
		$s = ':	';
		$c = $d = '';
		foreach ($cfg_opts_order as $i => $o)
		foreach ($o as $k => $v) {
			$k = $tmp_options_input[$i][$v];
			$v = abbr($v).'='.(
				$i === 'input'
				? ($$v ?: '='.(${'u_'.$v} ?: get_const(strtoupper($v))))
				: ($u_opts[$v]?1:'')
			);
			if ($i === 'admin') $k = '<span class="gloom">'.$k.'</span>';
			$c .= NL.$k.$s.$v;
		}
		$i = '
|<input type="submit" value="';
		$j = '
|<input type="button" value="';
		foreach (array(
			'out'	=> array($i, 'name="quit')
		,	'save'	=> array($j, 'id="unsave" data-keep="'.DRAW_PERSISTENT_PREFIX)
		,	'skip'	=> array($j, 'id="unskip')
		,	'pref'	=> array($i, 'name="'.O.'o')
		) as $k => $v) $d .= $v[0].$tmp_options_drop[$k].'" '.$v[1].'">';

		if (!$qdir) $content = '
||<b class="anno">'.$tmp_options_first.'</b>';
		$content .= '
<form method="post">'.$d.'
</form><form method="post">'
.NL.$tmp_options_name.$s.$usernames[$u_num]
.NL.$tmp_options_qk.$s.'<input type="text" readonly value="'.$u_key.'" title="'.$tmp_options_qk_hint.'">'.$c
.NL.$tmp_options_time.$s.date('e, T, P')
.NL.$tmp_options_time_client.$s.'<time id="time-zone"></time>'
.($u_flag ? NL.$tmp_options_flags.$s.implode(', ', $u_flag) : '')
.$i.$tmp_options_apply.'" id="apply">
</form>';
		foreach ($tmp_rules as $head => $hint) {
			if (is_array($hint)) {
				$s = '';
				foreach ($hint as $i) $s .= NL.'<li>'.indent(get_template_hint($i)).'</li>';
				$s = NL.'<ul>'.indent($s).'</ul>';
			} else	$s = NL.'<p class="hint">'.indent(get_template_hint($hint)).'</p>';
			$task .= NL."<p>$head</p>$s";
		}
		$js[0]++;
	} else

	if ($qd_room) {
		if ($room != $room_url) {
			header('HTTP/1.1 303 Fixed room name');
			header('Location: '.$room_list_href.$room.$_REQUEST['etc']);
			exit;
		}

//* task manipulation ---------------------------------------------------------

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
				if (!strlen(trim($etc, '-'))) die(
					data_lock($room)
					? '<!--'.date(TIMESTAMP, T0).'-->'
					.NL.'<meta charset="'.ENC.'">'
					.NL.'<title'.(
						is_array($t = data_check_my_task())
						? '>'.(
							$sending
							? $tmp_sending
							: $tmp_target_status[$t[0]].'. '.$tmp_time_limit.': '.format_time_units($t[1])
						)
						: (
							$sending
							? ' id="confirm-sending"'
							: ''
						).'>'.$tmp_target_status[$t]
					).'</title>'
					.NL.(
						($t = $target['task'])
					&&	$target['pic']
						? '<img src="'.get_pic_url($t).'" alt="'.$t.'">'
						: $t
					)
					: $tmp_post_err['no_lock']
				);
		//* skip current task, obsolete way by GET:
				$t = substr($etc, 1);
				list($a, $r) = get_room_skip_name($room);
				if ($q = get_room_skip_list($a)) {
					array_unshift($q, $t);
					$t = implode('/', $q);
				}
				$add_qk = "$a=$r/$t";
				$post_status = 'skip';
				goto post_refresh;
			}

//* report form ---------------------------------------------------------------

			$task = get_template_form(
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
		} else

//* active room task and visible content --------------------------------------

		if ($room) {
			foreach ($query as $k => $v) if (substr($k, 0, 4) == 'draw') {
				$draw_query = 1;
				break;
			}
			$y = $query['!'];
			$dont_change = ($draw_query || trim(str_replace(array('trd_arch', 'trd_miss'), '', $y), '&'));
			$skip_list = get_room_skip_list();

if (TIME_PARTS) time_check_point('inb4 aim lock');
			data_lock($room);
			data_aim(
				!$u_opts['unknown']
			,	$skip_list
			,	$dont_change		//* <- after POST with error
			);
			$visible = data_get_visible_threads();
			data_unlock();
if (TIME_PARTS) time_check_point('got visible data, unlocked');

			exit_if_not_mod(max($t = $target['time'], $visible['last']));
			$task_time = ($t ?: T0);	//* <- UTC seconds
			$x = 'trd_max';
			if ($draw_query && !$target['task'] && (!$y || false === strpos($y, $x))) {
				if (data_is_thread_cap()) $query['!'] = ($y?$y.'!':'').$x;
				else $draw_free = 1;
			}
			$desc = ($target['pic'] || !($target['task'] || $draw_free));
			if (MOD) $js['mod']++;
			if ($vts = $visible['threads']) {
				$flags = '';
				$flag = 'acgmp';
				foreach (array(
					$u_opts['active']
				,	!$u_opts['count']
				,	GOD
				,	MOD
				,	PIC_SUB
				) as $k => $v) if ($v) $flags .= $flag[$k];
				$images = ROOTPRFX.DIR_PICS;
				$content = (
					MOD ? "
left = $tmp_mod_post_hint
right = $tmp_mod_user_hint"
					: (R1 || $u_flag['nor'] ? '' : "
left = $tmp_report_post_hint
right = $tmp_report_user_hint"
					)
				)."
images = $images
flags = $flags
";
				$a = array();
				$b = '<br>';
if (TIME_PARTS) time_check_point('inb4 raw data iteration'.NL);
				foreach ($vts as $tid => $posts) {
					$tsv = '';
					foreach ($posts as $postnum => $post) {
						if ($t = $post['time']) {
							if ($u_opts['times']) {
								$l = explode($b, $t, 2);
								$l[0] = NB;
								$l = implode($b, $l);
							} else $l = $t;
						} else $l = NB;
						if ($t = $post['user']) {
							$r = explode($b, $t, 2);
							$uid = $r[0];
							$r[0] = (
								!$u_opts['names']
							&&	array_key_exists($uid, $usernames)
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
						if ($t = $post['used']) $tabs['used'] = $t;
						if (GOD) {
							$tabs['color'] .= '#'.$uid;
							if ($t = $post['browser']) $tabs['browser'] = $t;
						}
					//	if (MOD)
						if (is_array($r = $visible['reports'][$tid][$postnum])) {
							foreach ($r as $k => $lines) {
								$k = 'reports_on_'.($k > 0?'user':'post');
								$v = '';
								foreach ($lines as $time => $line) $v .= ($v?'<br>':'').$time.': '.$line;
								if ($v) $tsv .= NL.$k.' = '.$v;
							}
						}
						$tsv .= NL.(
							$postnum > 0
						||	$u_flag['nor']
						||	(R1 && !MOD)
							? ''
							: end(explode('/', $tid)).','
						).implode('	', $tabs);
					}
					$a[$tid] = $tsv;
if (TIME_PARTS) time_check_point('done trd '.$tid);
				}
				ksort($a);
				$content .= implode(NL, array_reverse($a));
if (TIME_PARTS) time_check_point('after sort + join');
				if (GOD) $filter = 1;
			} else if (GOD) $content = "
left = $tmp_empty
right = $tmp_empty
flags = vg

0,0	v	v	&mdash;";	//* <- dummy thread for JS drop-down menus
			$t = $target['task'];
			if ($desc) {
				$task = get_template_form(
					array(
						'method' =>	'post'
					,	'name' =>	'describe'
					,	'min' =>	DESCRIBE_MIN_LENGTH
					,	'head' =>	$t ? $tmp_describe_this : $tmp_describe_new
					,	'hint' =>	$tmp_describe_hint.($u_flag['nop'] ? '\\'.$tmp_no_play_hint : '')
					,	'filter' =>	$filter
					,	'checkbox' => (
							$u_opts['kbox']
							?  array(
								'label' => $tmp_check_required
							,	'required' => 1
							)
							: ''
						)
					)
				);
				if ($t) {
					$src = (strpos($t, ';') ? get_pic_resized_path(get_pic_normal_path($t)) : $t);
					$task .= '
<img src="'.get_pic_url($src).'" alt="'.$t.'">';
				} else {
					$task_time = '-';
					$s = count($skip_list);
					$n = $target['count_free_tasks'];
					if ($s && !$n) $data_attr['task']['unskip'] = "$s/$n/$target[count_free_unknown]";
				}
			} else {
				$task = '
<p>'.($t?$tmp_draw_this.':</p>
<p>'.$t:$tmp_draw_free.':').'</p><noscript>
<p class="hint">'.$tmp_require_js.'</p></noscript>';
				$n = get_draw_app_list();
				$subtask = '
<script id="'.$n['name'].'-vars" src="'.$n['src'].'" data-vars="'.get_draw_vars(DRAW_SEND).'"></script>
<div class="task">'.indent('<p class="hint">'.indent($n['list']).'</p>').'</div>';
			}
			if ($t || $desc) $data_attr['task']['t'] = $task_time;
			if ($t) $data_attr['task']['skip'] = intval($target['thread']);
			$data_attr['content']['type'] = 'threads';
			$js[0]++;
		} else {

//* active rooms list ---------------------------------------------------------

			if ($visible = data_get_visible_rooms()) {
				exit_if_not_mod($visible['last']);

				$t = !$u_opts['times'];
				$c = !$u_opts['count'];
				$s = ', ';
				$content = "
archives = $arch_list_href
separator = \"$s\"
".($c?"
$tmp_room_count_threads	$tmp_room_count_posts":'');
				foreach ($visible['list'] as $room => $n) {
					if ($a = $n['marked']) foreach ($a as $k => $v) $room .= "$s$v$k[0]";
					if ($c) {
						$left = $n['threads now'].$s.$n['threads ever'];
						if ($v = $n['threads arch']) $left .= $s.$v.($t ? $s.$n['last arch'] : '');
						$right = $n['pics'].$s.$n['desc'].($t ? $s.$n['last post'] : '');
					} else {
						$left = ($n['threads arch']?'*':NB);
						$right = NB;
					}
					$content .= "
$left	$right	$room";
				//* announce/frozen:
					if ($a = $n['anno']) {
						if (!$a['room_anno']) $content .= '	';
						foreach ($a as $k => $v) $content .= "	$tmp_announce[$k]: ".trim(
							preg_replace('~\s+~u', ' ',
							preg_replace('~<[^>]*>~', '',
							preg_replace('~<br[ /]*>~i', NL,
						$v))));
					}
				}
				$room = '';
			}
			$task = get_template_form(
				array(
					'method' =>	'post'
				,	'name' =>	$qredir
				,	'min' =>	ROOM_NAME_MIN_LENGTH
				,	'filter' =>	2
				)
			);
			$data_attr['content']['type'] = 'rooms';
			$js[0]++;
		}
	} else

	if (!$room) {
		header('HTTP/1.1 303 To home room');
		header('Location: '.$room_list_href.($u_room_default?$u_room_default.'/':''));
		exit;
	}
} else {

//* not registered ------------------------------------------------------------

	if ($etc) die('x');
	foreach ($cfg_dir as $k => $v) unset(${'qd_'.$k});
	$task = get_template_form(
		array(
			'method' =>	'post'
		,	'name' =>	ME
		,	'min' =>	USER_NAME_MIN_LENGTH
		)
	);
}

//* generate page, put content into template ----------------------------------

define(S, '. ');
define(A, NL.'<a href="');
$s = array();
$short = !!$u_opts['head'];
$room_title = ($room == ROOM_DEFAULT ? $tmp_room_default : $tmp_room.' '.$room);
foreach (
	array(
		'/' => $tmp_title
	,	'..' => $tmp_rooms
	,	'.' => $room_title
	,	'a' => $tmp_archive
	,	'*' => $tmp_archives
	,	'?' => $tmp_options
	,	'~' => $tmp_draw_test
	,	'#' => '&#9662; '.$tmp_mod_panel
	) as $k => $v
) {
	$s[$k] = '">'.(
		$short
		? $k
		: $v.(substr($v, -1) == '.'?'':'.')
	).'</a>';
}
$header = $footer = $links = $took = '';

if (!$u_opts['names'] && defined('FOOT_NOTE')) {
	$links = vsprintf(FOOT_NOTE, $tmp_foot_notes);
}

if ($u_key && !$u_opts['times']) {
	$js[0]++;
	define(TOOK, $took = '<!--?-->');
	if (TIME_PARTS) {
		time_check_point('inb4 template');
		$took = '<a href="javascript:'.$js[0].',toggleHide(took)">'.$took.'</a>';
		foreach ($tcp as $t => $comment) {
			$t = get_time_elapsed($t);
			$t_diff = ltrim(sprintf('%.6f', $t - $t_prev), '0.');
			$t = sprintf('%.6f', $t_prev = $t);
			$comment = str_replace(NL, '<br>-', is_array($comment)?implode('<br>', $comment):$comment);
			$took_list .= NL."<tr><td>$t +</td><td>$t_diff:</td><td>$comment</td></tr>";
		}
	}
	$took = get_time_html().str_replace_first(' ', NL, sprintf($tmp_took, $took));
}

if (GOD) {
	$r = '<a href="?mod';
	foreach ($tmp_mod_pages as $k => $v) $mod_list .= $r.'='.$k.'">'.$v.'</a><br>'.NL;
	$mod_link =
		'<u class="menu-head">'.indent(
			$r.$s['#'].NL.
			'<u class="menu-top">'.
			'<u class="menu-hid">'.
			'<u class="menu-list">'.indent(
				$mod_list
			).'</u></u></u>'
		).'</u>';
}

if (!$is_report_page) {
	$this_href = ($room?'..':'.');
	$room_list_link = A.($qd_room ? $this_href : $room_list_href).$s['..'];
	$arch_list_link = (
		$qd_arch || is_dir(DIR_ARCH)
		? A.($qd_arch ? $this_href : $arch_list_href).$s['*']
		: ''
	);
	if ($room) {
		$room_link = A.($qd_room ? '.' : "$room_list_href$room/").$s['.'];
		$arch_link = (
			$qd_arch || is_dir(DIR_ARCH.$room)
			? A.($qd_arch ? '.' : "$arch_list_href$room/").$s['a']
			: ''
		);
	}
	$header = (
		$u_key
		? array(
			A.ROOTPRFX.$s['/']
		.	($short?$room_list_link:'')
		.	$room_link
		.	($short?'':$arch_link)
		, 'ar' =>
			$mod_link
		.	($short?$arch_link:'')
		.	$arch_list_link
		.	($short?'':$room_list_link)
		.	A.($qdir && $qd_opts?'.':ROOTPRFX.DIR_OPTS.($room?$room.'/':'')).$s['?']
		)
		: array(
			A.ROOTPRFX.$s['/']
		.	A.ROOTPRFX.'?draw_test'.$s['~']
		, 'ar' => (
				is_dir(DIR_ARCH)
				? A.ROOTPRFX.DIR_ARCH.$s['*']
				: ''
			)
		)
	);
	if ($took)	$footer .= NL.'<span class="l">'.indent($took).'</span>';
	if ($links)	$footer .= NL.'<span class="r">'.indent($links).'</span>';
	if ($footer)	$footer = NL.'<p class="hint">'.indent($footer).'</p>';
	if ($took_list)	$footer .= NL.'<table id="took" style="display:none">'.indent($took_list).'</table>';
}

die(get_template_page(array(
	'icon' => $icon
,	'lang' => $lang
,	'title' => (
		$mod_page
		? $tmp_mod_panel.' - '.$mod_page.S
		: ''
	).(
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
	).$tmp_title.(
		$qd_opts == 2
		? S.$tmp_options_input['input']['draw_app']
		: ''
	)
,	'header' => $header
,	'anno' => !$is_report_page
,	'report' => $query['!']
,	'data' => $data_attr
,	'task' => $task ?: 'Err... What?'
,	'subtask' => $subtask
,	'textarea' => $textarea
,	'content' => $content
,	'footer' => $footer
,	'js' => $js
)));

}




//* redirect after posting ----------------------------------------------------

post_refresh:

if ($o = trim(ob_get_clean())) data_log_adm('POST buffer dump: '.$o);

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

header('HTTP/1.1 303 Refresh after POST: '.$p);

$up = ($room?'../':'');
$l = ((
	($room && $room != $_REQUEST['room'])					//* <- move after rename
	|| (($room = $_POST[$qredir]) && ($room = trim_room(URLdecode($room))))	//* <- create new room
	)
	? $up.URLencode($room).'/'
	: ($etc && $etc[0] != '-'?$etc:'.')
);
if ($OK) {
	if ($u_key) {
		if ($u_key === 'quit') {
			$a = '';
			$t = 0;
		} else {
			$a = array($u_key);
			if ($u_opts !== 'default') {
				foreach ($cfg_opts_order as $i => $o) if ($i === 'input') {
					foreach ($o as $k => $u) if (isset(${$n = "u_$u"})) {
						if (!in_array($k, $cfg_opts_text)) {
							if ($v = intval($$n)) $a[] = "$k:$v";
						} else
						if (strlen($raw = trim_room($$n))) {	//* <- OK for any text setting
							$enc = URLencode($raw);
							$v = (
								$enc === $raw
								? $raw
							//	: $enc			//* <- full URLencode is OK, but longer
								: 'base64:'.URLencode(base64_encode($raw))
							);
							$a[] = "$k:$v";
						}
					}
				} else {
					$v = array();
					$s = '';
					foreach ($o as $k) if (intval($u_opts[$k])) {
						$v[] = $k = abbr($k);
						if (!$s && strlen($k) > 1) $s = '.';
					}
					if (strlen($v = implode($s, $v))) $a[] = "$i:$v$s";
				}
			}
			$a = implode('/', $a);
			$t = T0 + QK_EXPIRES;
		}
		$h = 'Set-Cookie: ';
		$a = ME.'='.$a;
		$x = '; expires='.gmdate(DATE_COOKIE, $t).'; Path='.ROOTPRFX;
		header("$h$a$x");
		if ($add_qk) header("$h$add_qk$x");
	}
} else {
	$l .= '?!='.$p;
	foreach ($query as $k => $v) if (substr($k, 0, 4) == 'draw') $l .= '&'.$k.(strlen($v)?'='.$v:'');
}
if (false === strpos('.,;:?!', substr($msg, -1))) $msg .= '.';

header('Location: '.$l);
printf($tmp_post_refresh, '<meta charset="'.ENC.'">'.$msg, $l);

?>
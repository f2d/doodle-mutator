<?php
define(T0, end($t = explode(' ',microtime())));
define(ME, 'me');
define(POST, 'POST' == $_SERVER['REQUEST_METHOD']);

if (POST) {
	if (!isset($_REQUEST[ME])) goto post_refresh;	//* <- no anonymous posting
	ignore_user_abort(true);
}
function time_check_point($comment) {global $tcp; $tcp[microtime()] = $comment;}

time_check_point('inb4 cfg');
ob_start();

define(NAMEPRFX, 'd');
define(M0, $t[0]);
define(GET_Q, strpos($_SERVER['REQUEST_URI'], '?'));
define(ROOTPRFX, substr($s = $_SERVER['PHP_SELF'], 0, strrpos($s, '/')+1));

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
time_check_point('after cfg');

$opt_sufx = 'aopu';
$opt_name = array('opta', 'opti', 'per_page', 'draw_max_undo');
$opt_lvls = array('a' => 'admin', 'i' => 'check');

if ($me = $_REQUEST[ME]) {
	if (false === strpos($me, '/')) {
		list($u_qk, $u_opti, $u_per_page, $u_room_home) = explode('_', $me, 4);		//* <- v1, one separator is not enough
	} else {
		list($u_qk, $i, $u_room_home, $u_draw_app) = explode('/', $me, 4);
		if (false !== strpos($i, $sep = '_')
		||  false !== strpos($i, $sep = '.')) {
			list($u_opti, $u_per_page, $u_draw_max_undo) = explode($sep, $i);	//* <- v2 opts = plain ordered numbers, not enough
		} else
		if (preg_match_all('~(\d+)(\D)~', $i, $m)) {		//* <- v3, '01adm_0010opt_30per_page_99undo', or '0a1o2p3u', in any order
			foreach ($m[1] as $k => $v) if (false !== ($i = strpos($opt_sufx, $m[2][$k]))) ${'u_'.$opt_name[$i]} = $v;
		}
	}
	if (($q = trim($_POST[ME])) && $u_qk != $q) $u_qk = $q;
	if ($u_qk && data_check_u($u_qk, $q)) {
		data_log_ip();
		if ($u_flag['ban']) die(get_template_page(array(
	'lang' => $lang
,	'title' => $tmp_ban
,	'task' => $tmp_ban
,	'body' => 'burnt-hell')));
		if (POST) $post_status = OQ.$tmp_post_ok_user_qk;
	}
	foreach ($opt_lvls as $i => $a) if (${$p = "u_opt$i"})
	foreach ($cfg_opts_order[$a] as $k => $v) $u_opts[$v] = intval($$p[$k]);
}
define(GOD, $u_flag['god']?1:0);
define(TIME_PARTS, !$u_opts['time_check_points']);		//* <- profiling

if (!($u_per_page = intval($u_per_page))) $u_per_page = TRD_PER_PAGE;
$etc = trim($_REQUEST['etc'], '/');
$qdir = strtolower($_REQUEST['dir']);
foreach ($cfg_dir as $k => $v) if ($qdir == $v) ${'qd_'.$k} = 1;

if (FROZEN_HELL && !GOD && !($u_key && $qd_opts) && !$qd_arch) {
	if (POST) goto post_refresh;
	die($etc == '-'?'-':get_template_page(array(
	'lang' => $lang
,	'title' => $tmp_stop_all.' '.$tmp_title.'.'
,	'header' => '
		<div>
			<a href="'.ROOTPRFX.'">'.$tmp_title.'.</a>
		</div>
		<div class="r">
			<a href="'.ROOTPRFX.DIR_ARCH.'">'.$tmp_archive.'.</a>
			<a href="'.ROOTPRFX.DIR_OPTS.'">'.$tmp_options.'.</a>
		</div>'
,	'task' => $tmp_stop_all)));
}

if ($qdir) {
	if ($l = mb_strlen($room = trim_room($room_url = urldecode($_REQUEST['room'])))) define(R1, $l = (mb_strlen(ltrim($room, '.')) <= 1));
	define(MOD, (GOD || $u_flag['mod'] || $u_flag['mod_'.$room])?1:0);
} else if ($u_key) {
	if (!$u_room_home) $qd_opts = 1;
	$rd = ($rr = '
	RewriteRule ^').($d = '('.implode('|', $cfg_dir).')').($sub = '(/([^/]+))?');
	$rc = '
	RewriteCond %{REQUEST_FILENAME} -';
	if (!($e = is_file($f = '.htaccess')) || !strpos($e = file_get_contents($f), $rd)) file_put_contents($f, ($e?NL:'')
.'<IfModule rewrite_module>
	RewriteEngine On
	RewriteBase '.ROOTPRFX
.(defined('DIR_DATA')?$rr.DIR_DATA.'.*$ .':'')
.$rr.'('.DIR_PICS.')(([^/])[^/]+\.([^/])[^/]+)$ $1$4/$3/$2'
.$rd.'$ $0/ [NC,R=301,L]'
.$rd.'(/[-\d]*)$ index.php?dir=$1&room=$3&etc=$4'
.$rc.'f [OR]'.$rc.'d'
.$rr.'.? - [S=2]'
.$rr.'('.DIR_PICS.'|'.DIR_ARCH.'[^/]+/'.DIR_THUMB.').*$ err.png [L]'
.$rr.'('.$d.'/([^/]+/)?).+$ $1. [R,L,E=nocache:1]'.'
	<IfModule headers_module>
		Header always set Cache-Control "no-store, no-cache, must-revalidate" env=nocache
		Header always set Expires "Thu, 01 Jan 1970 00:00:00 GMT" env=nocache
	</IfModule>
</IfModule>', FILE_APPEND);
}
if (TIME_PARTS) time_check_point('inb4 action fork');




if (POST) {//*	--------	post/setting/reg	--------

if ($u_key) {
	$post_status = (($_POST[ME] || $_POST['rooms'])?OK:-1);

	if (isset($_POST['rooms'])) goto post_refresh;

//* options change ------------------------------------------------------------
	if (isset($_POST[$p = 'quit'])) {
		$post_status = OQ.$tmp_post_ok_user_quit;
		$u_key = $p;
	} else
	if (isset($_POST[$p = O.'o'])) {	//* <- options work, no matter whatever else
		$post_status = OQ.$tmp_post_ok_user_opt;
		if (strlen($_POST[$p]) > 1) $u_opti = 'd';
		else {
			foreach ($opt_lvls as $k => $v) {
				$p = '';
				foreach ($cfg_opts_order[$v] as $o) $p .= ($_POST[O.abbr($o)]?1:0);
				${'u_opt'.$k} = rtrim($p, 0);
			}
			foreach ($cfg_opts_order['input'] as $v) if (isset($_POST[$p = O.abbr($v)])) ${'u_'.$v} = $_POST[$p];
		}
	} else
	if (!$qd_room || !$room);		//* <- no posting outside room
	else

//* report problem in active room ---------------------------------------------
	if (isset($_POST['report']) && $etc && !(R1 || $u_flag['nor'])) {
		$post_status = 'dest';
		if (preg_match(PAT_DATE, $etc, $r) && ($etc == $r[0])) {	//* <- r = array(t-r-c, t-r, thread, row, column)
			$post_status = 'text_short';
			if (mb_strlen($r[1] = trim_post($_POST['report'], REPORT_MAX_LENGTH), ENC) >= REPORT_MIN_LENGTH) {
				data_lock($room);
				$r = data_log_report($r);
				$post_status = ($r > 0?OQ.$tmp_post_ok_text:1);
			}
		}
	} else
	if ($etc && !(MOD && $etc == 3)); else	//* <- no "etc" posting without report

//* process new text post -----------------------------------------------------
	if (isset($_POST['describe'])) {
		$post_status = 'text_short';
		if (mb_strlen($t = trim_post($_POST['describe'], DESCRIBE_MAX_LENGTH), ENC) >= DESCRIBE_MIN_LENGTH) {
			data_lock($room);
			data_aim();
			$t = data_log_post($t);
			$post_status = ($t > 0?OQ.$tmp_post_ok_text:($t?'trd_max':1));
			if ($t < 0) $log = -$t;
		}
		if ($log) data_log_adm("Denied $post_status: $log");
		else if (!$u_room_home) $u_room_home = $room;
	} else

//* process new pic post ------------------------------------------------------
	if (isset($_POST['pic'])) {
		$post_status = 'file_pic';
		$log = 0;
		$ppl = strlen($pp = $_POST['pic']);
		$txt = (($ptx = $_POST['txt']) ? $ptx : '0-0,(?)');
	//* metadata, newline separated tagged format:
		if (false !== strpos($txt, NL)) {
			$a = explode(',', 'app,length,t0,time,used');
			$x = preg_split('~\v+~u', $txt);
			$y = array();
			foreach ($x as $line) if (preg_match('~^(\w+)[\s:=]+(.+)$~u', $line, $m) && in_array($k = strtolower($m[1]), $a)) $y[$k] = $m[2];
			if ($y['length'] && $y['length'] != $ppl) {
				$post_status = 'file_part';
				$log = $ppl.' bytes';
			} else {
				if (!preg_match('~^(\d+:)+\d+$~', $y['time'])) {
					$t = array(($y['t0']?$y['t0']:$target['time']).'000', T0.'000');
					if (preg_match('~^(\d+)\D+(\d+)$~', $y['time'], $m)) {
						if ($m[1] && $m[1] != $m[2]) $t[0] = $m[1];
						if ($m[2]) $t[1] = $m[2];
					}
					$y['time'] = "$t[0]-$t[1]";
				}
				if (!$y['app']) $y['app'] = '[?]';
				if ($y['used']) $y['app'] .= " (used $y[used])";
				$txt = "$y[time],$y[app]";
			}
		} else
	//* metadata, old CSV:
		if (preg_match('~^(?:(\d+),)?(?:([\d:]+)|(\d+)-(\d+)),(.*)$~is', $txt, $t)) {
			if ($t[2]) $txt = $t[2].','.$t[5];
			else {
				if (!$t[4]) $t[4] = T0.'000';
				if (!$t[3] || $t[3] == $t[4]) $t[3] = ($t[1]?$t[1]:$target['time']).'000';
				if (!$t[3]) $t[3] = $t[4];
				$txt = $t[3].'-'.$t[4].','.$t[5];
			}
		}
		if ($log); else
	//* parse pic content:
		if ((count($a = explode(',', $pp, 2)) < 2)
			|| !(($png = strpos($a[0], 'png'))
				||   strpos($a[0], 'jpeg'))
			|| (false === ($data = base64_decode($a[1])))
		) $log = 'invalid, '.$ppl.' bytes: '.(($ppl > REPORT_MAX_LENGTH) ? substr($pp, 0, REPORT_MAX_LENGTH).'(...)' : $pp);
		else
		if (($x = strlen($data)) > DRAW_MAX_FILESIZE) {
			$post_status = 'file_size';
			$log = $x;
		} else
		if (is_file($f = pic_subpath($fn = ($md5 = md5($data)).($png?'.png':'.jpg'), 1))) {
			$post_status = 'file_dup';
			$log = $fn;
		} else
	//* save pic file:
		if (data_lock($room)) {
			if (($log = file_put_contents($f, $data)) != $x) {
				$x = 0;
				$post_status = 'file_put';
			} else
			if ($sz = getImageSize($f)) {
				foreach ($tmp_whu as $k => $v) {
					$z = $$tmp_wh[$k] = $sz[$k];
					$a = preg_split('~\D+~', ($a = constant('DRAW_LIMIT_'.$v)) ? $a : constant('DRAW_DEFAULT_'.$v));
					if (($a[0] && $z < $a[0]) || (($a[1]?$a[1]:$a[1]=$a[0]) && $z > $a[1])) {
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
					$i($p, $f = pic_resized_path($f));
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
				data_aim();
	//* gather post data fields to store:
				$x = array($fn, trim_post($txt));
				if (LOG_UA) $x[] = trim_post($_SERVER['HTTP_USER_AGENT']);
	//* write data:
				$x = data_log_post($x);
				$post_status = ($x > 0?OQ.$tmp_post_ok_file:($x?'trd_miss':1));
				$log = ($post_status != 1?0:$x);
			} else if (is_file($f)) unlink($f);
		}
		if ($log) data_log_adm("Denied $post_status: $log".($ptx?"
{
$ptx
}":''));
		else if (!$u_room_home) $u_room_home = $room;
	} else

//* admin/mod actions ---------------------------------------------------------
	if (isset($_POST['mod']) && MOD) {
		$d = 'abcdefg';
		$k = array();
		foreach ($_POST as $i => $a) if (preg_match('~^m\d+_(\d+)_(\d+)_(\d+)$~i', $i, $m)) {
			$m[0] = $a;
			$act[$k[] = str_replace_first('_', $d[substr_count($a, '+')], $i)] = $m;
		}
		if ($act) {
			$d = '';	$t = array();
			$uf = array();	$u = array();		//* <- cached ids, etc, for batch processing; name collisions, urgh
		//	ksort($act, SORT_NATURAL);		//* <- since php v5.4.0 only; bummer
			natsort($k);
			data_lock($room);
			foreach (array_reverse($k) as $i) {
				$m = data_log_mod($act[$i]);	//* <- act = array(option name, thread, row, column)
				if ($post_status != 1) $post_status = ($m?OK:1);
			}
		}
	}
} else if (isset($_POST[ME]) && strlen($me = trim_post($_POST[ME], USER_NAME_MAX_LENGTH)) >= USER_NAME_MIN_LENGTH) {

//* register new user ---------------------------------------------------------
	$post_status = (data_log_user($u_key = md5($me.T0.substr(M0,2,3)), $me)?OQ.$tmp_post_ok_user_reg:1);
}




} else {//*	--------	view, not post	--------




	if ($qd_arch) {
		require_once(NAMEPRFX.'.arch.php');
		$rda = ROOTPRFX.DIR_ARCH;
		if ($room && ($thread_count = data_get_archive_count())) {
			exit_if_not_mod(data_get_archive_mtime());

			$js['.arch'] = 0;
			$js[0]++;
			$task = array('?'.($qa = 'post,file,name'), $tmp_archive, $tmp_archive_find_by, $tmp_archive_find);
			$task = get_template_form($task, FIND_MIN_LENGTH);

//* archive posts search ------------------------------------------------------
			if (list($subj, $que) = get_req()) {
				$s = $tmp_archive_find_by[$k = array_search($subj, $qa = explode(',', $qa))];
				if (!mb_check_encoding($q = urldecode($que), ENC)) $q = iconv('windows-1251', ENC, $q);
				$search_res = $tmp_archive_found.' '.$tmp_archive_found_by[$k].': <a id="r">'.$q.'</a>';
				$q = mb_strtolower(trim_post($q, FIND_MAX_LENGTH), ENC);
				$task .= '
		<p class="hint">'.$search_res.'</p>';
				$content = '|||';	//* <- to keep JS happy for now
				$n = 0;
if (TIME_PARTS) time_check_point('inb4 search');
				if (strlen($q) && is_dir($d = DIR_ARCH.$room.'/'))
				for ($i = (R1?(($i = $thread_count-TRD_PER_PAGE) < 0?0:$i):0); $i <= $thread_count; $i++)
				if (is_file($f = $d.$i.PAGE_EXT)) {
					$dn = '';
					if (preg_match(PAT_CONTENT, $txt = file_get_contents($f), $m)) foreach (explode(NL, $m[1]) as $line) {
						$tab = explode('	', $line);
						if ($k == 2) $t = $tab[1];				//* <- username
						else if (!$k	 && $tab[2][0] != '<') $t = $tab[2];	//* <- text post only
						else if ($k == 1 && $tab[2][0] == '<') {
							$t = $tab[2];
							$t = substr($t, strrpos($t, '/')+1);		//* <- pic filename
							$t = substr($t, 0, strrpos($t, '"'));
						} else $t = '';
						if (false !== strpos(mb_strtolower($t, ENC), $q)) {
							$content .= ($dn?'':NL.'	'.$i).NL.$line;
							$dn .= '='.(++$n);
						}
					}
if (TIME_PARTS) time_check_point('done '.$i.$dn);
				}
				if (!$n) $task .= $tmp_empty;
			} else {

//* archive threads list ------------------------------------------------------
				if (($i = (R1?($thread_count-TRD_PER_PAGE):0)) < 0) $i = 0;
				$content = DIR_THUMB.'|'.$thread_count.'|'.(R1?TRD_PER_PAGE.'|'.$i:$u_per_page).($u_key?'':'
			<!-- static link for scriptless bots, last in chain -->
			<a href="'.$thread_count.'.htm">'.$thread_count.'</a>');
			}
		} else {

//* archive rooms list --------------------------------------------------------
			$task = get_template_form('archive', ROOM_NAME_MIN_LENGTH);
			$task_data['filter'] = 2;
			if ($vr = data_get_visible_archives()) {
				$mt = array_shift($vr);
				exit_if_not_mod($mt);

				$content = '
'.$tmp_arch_last.'	'.$tmp_arch_count;

				foreach ($vr as $room => $n) $content .= '
'.$n[1].'	'.$n[0].'	<a href="'.$rda.$room.'/">'.$room.'</a>';
				$room = '';
				$js[0]++;
			}
		}
		if (!$content) $task .= $tmp_empty;
	} else

//* draw test -----------------------------------------------------------------
	if (($qd_opts || !$qdir) && (list($subj, $que) = get_req())) {
		$qd_opts = 2;
		$n = get_draw_app_list($subj);
		$tmp_icon = $n['name'];
		$task = ('
		<p>'.$tmp_draw_free.'</p>
		<p class="hint">'.$tmp_draw_hint.'</p>').'<noscript>
		<p class="hint">'.$tmp_require_js.'</p></noscript>';
		$subtask = '
		<script id="'.$n['name'].'-vars" src="'.$n['src'].'" data-vars="'.DRAW_REST.($u_opts['save2common']?'':';saveprfx='.NAMEPRFX).'"></script>
		<div class="task">
			<p class="hint">'.$n['list'].'</p>
		</div>';
	} else

if ($u_key) {

//* options -------------------------------------------------------------------
	if ($qd_opts) {
		$nst = '
 	 	';
		$draw_app = (($s = array_search($u_draw_app, $cfg_draw_app))?$s:0)
.':'.implode('|', $cfg_draw_app).':'.implode('|', $tmp_draw_app).':?*:'
//':'.ROOTPRFX.'*.htm:'
.$tmp_draw_test;
		if (!$u_draw_max_undo) $u_draw_max_undo = DRAW_MAX_UNDO;
		if (!$u_room_home) {
			$u_room_home = ROOM_DEFAULT;
			if (!$qdir) $o1 = "$nst<br>$nst<b class=\"anno\">$tmp_options_first</b>$nst<br>";
		}
		$s = ':	';
		$a = $b = $c = '';
		if (GOD)
		foreach ($cfg_opts_order['admin'] as $k => $v) $a .= NL."$tmp_options_admin[$v]$s".abbr($v).'='.($u_opta[$k]?1:'');
		foreach ($cfg_opts_order['input'] as $k => $v) $b .= NL."$tmp_options_field[$v]$s".abbr($v).'='.($$v?$$v:'='.${'u_'.$v});
		foreach ($cfg_opts_order['check'] as $k => $v) $c .= NL."$tmp_options_show[$v]$s".abbr($v).'='.($u_opti[$k]?1:'');
		$i = '
|<input type="submit" value="';

		$content = $tmp_options_turn_on.'|'.$tmp_options_turn_off.$o1.'
<form method="post">'
.$i.$tmp_options_logout.'" name="quit">'
.$i.$tmp_options_unskip.'" onClick="unskip(); return false">'
.$i.$tmp_options_reset.'" name="'.O.'o">
</form><form method="post">'
.NL.$tmp_options_name.$s.$usernames[$u_num]
.NL.$tmp_options_qk.$s.'<input type="text" readonly value="'.$u_key.'" title="'.$tmp_options_qk_hint.'">'.$b.$c
.NL.$tmp_options_time.$s.date('e, T, P')
.($u_flag ? NL.$tmp_options_flags.$s.implode(', ', $u_flag) : '').$a
.$i.$tmp_options_apply.'">
</form>';
		foreach ($tmp_rules as $head => $hint) {
			if (is_array($hint)) {
				$s = '';
				foreach ($hint as $i) $s .= '
			<li>'.get_template_hint($i).'</li>';
				$s = "
		<ul>$s
		</ul>";
			} else	$s = '
		<p class="hint">
'.get_template_hint($hint).'
		</p>';
			$task .= "
		<p>$head</p>$s";
		}
		$js[0]++;
	} else

	if ($qd_room) {
		if ($room != $room_url) {
			header('HTTP/1.1 303 Fixed room name');
			header('Location: '.$cfg_room.$room.$_REQUEST['etc']);
			exit;
		}

//* task manipulation ---------------------------------------------------------
		if ($etc) {
			if ($etc[0] == '-') {
				if (strlen($etc) == 1) die(
data_lock($room)
? '<meta charset="utf-8"><title>'.(
	is_array($t = data_check_my_task())
		? $tmp_target_status[$t[0]].'. '.$tmp_time_limit.': '.format_time_units($t[1])
		: $tmp_target_status[$t]
	).'</title><!--'.date(TIMESTAMP, T0).'-->'
	.NL.$target['pic']
	.NL.$target['task']
: $tmp_post_err);
				$t = substr($etc, 1);
				list($a, $r) = get_room_skip_name($room);
				if ($q = get_room_skip_list($a)) {
					array_unshift($q, $t);
					$t = implode('/', $q);
				}
				$add_qk = "$a=$r/$t";
				$post_status = OQ.$tmp_post_ok_skip;
				goto post_refresh;
			}

//* mod panel -----------------------------------------------------------------
			if (GOD) {
				if ($etc == 3 && ($a = strpos($etc, '-'))) {
					$a = intval(substr($etc, $a+1));
					die('<html><head><meta charset="utf-8"><title>'.$tmp_mod_pages[3].': #'.$a.'</title></head>
<body><pre>'.date(TIMESTAMP, T0).NL.(($a = data_check_user_info($a))?$a:$tmp_empty).'</pre></body></html>');
				}
				$lnk = $done = '';
				$ymd = preg_match(PAT_DATE, $etc, $m);		//* <- Y-m-d
				if (!($mod_page = $tmp_mod_pages[intval($etc)])) $mod_page = $tmp_empty;
				if ($ymd || $etc == 1) {
					if ($l = data_get_mod_log()) {
						if ($ymd) {
							exit_if_not_mod(data_get_mod_log($etc, 1));
							if ($a = data_get_mod_log($mod_page = $etc)) $content = 'rep'.
preg_replace('~(\v\S+)\s+(\S+)\s+~u', '$1	$2	',			//* <- transform data fields
preg_replace('~\h+~u', ' ',
preg_replace('~<br[^>]*>(\d+)([^\d\s]\S+)?\s~ui', NL.'$1	',		//* <- keep multiline entries atomic
preg_replace('~\v+~u', '<br>', NL.htmlspecialchars($a)))));
						}

						$last = end(end($l));
						$last = data_get_mod_log(key($l).'-'.$last, 1);
						if (!$ymd) exit_if_not_mod($last);

						foreach ($l as $ym => $d) $lnk .= ($lnk?'</p>':'').'
		<p>'.$ym.'-'.implode(',', $d);
						$lnk .= date(' (H:i:s)', $last).'</p>';
					} else $done = $tmp_empty;
				} else
				if ($etc == 2) {
					foreach ($tmp_mod_files as $k => $v) $lnk .= '
		<p>'.$k.': <a href="2-'.$k.'">'.str_replace_first(' ', '</a> ', $v).'</p>';
					if (count($a = explode('-', $etc, 2)) > 1) {
						ignore_user_abort(true);
						$a = $a[1];
if (TIME_PARTS) time_check_point('ignore user abort');
						if ($a == 0) {
							if (is_dir($d = DIR_PICS))
							foreach (scandir($d) as $f) if (trim($f, '.') && is_file($old = $d.$f)) {
								$new = pic_subpath($f, 1);
								$done .=
NL.(++$a)."	$old => $new	".($old == $new?'same':(rename($old, $new)?'OK':'fail'));
							}
if (TIME_PARTS && $a) time_check_point("done $a pics");
						} else
						if ($a == 1) {
							require_once(NAMEPRFX.'.arch.php');
							$done = data_archive_rewrite();
						} else
						if ($a == 2) {
							$a = 0;
							$e = '.log';
							if (is_dir($d = DIR_DAUS))
							foreach (glob($d.'/*'.$e, GLOB_NOSORT) as $f) if (is_file($f) && ($tasks = fln($f))) {
								$flags = array();
								$ips = array();
								foreach (($csv = explode(',', trim(array_shift($tasks), BOM))) as $flag) {
									if (rtrim($flag, '1234567890.')) $flags[] = $flag;
									else $ips[] = ($ips?'':'old').'	'.$flag;
								}
								$new = rtrim($f, $e);
								$done .= NL.(++$a).'	'.$f;
								foreach (array('ip', 'flag', 'task') as $x) {
									$s = $x.'s';
									if ($i = trim(implode(NL, $$s))) {
										$done .= '	'.$x.'s = '.count($$s);
										file_put_contents($new.'.'.$x, $i);
									}
								}
							}
if (TIME_PARTS && $a) time_check_point("done $a users");
						}
						$done = ($done?'
		<textarea>Done:'.$done.'</textarea>':$tmp_empty);
					}
				} else
				if ($etc > 2) {
					if ($etc == 5) {
						exit_if_not_mod(T0);		//* <- never exits, just to check if HTTP_IF_MODIFIED_SINCE is sent
						$t = print_r($_SERVER, true)
.NL.'DATE_RFC822 = '.gmdate(DATE_RFC822, T0)
.NL.'DATE_RFC2822 = '.gmdate('r', T0);
					} else {
						exit_if_not_mod(data_get_mod_log($etc, 1));
						$t = data_get_mod_log($etc);
					}
					if ($etc < 5) {
						$lnk .= get_template_form(';filter', $task_data['filter'] = 1);
					}
					if ($etc == 3) {
						$js['.mod'] = 0;
					//	$a = ($u_opts['active']?'a':'').($u_opts['count']?'':'c');
						$content .= "$tmp_mod_user_info:$tmp_mod_user_hint::ugc
0,u	&nbsp;	 	$u_num.

1,".trim(
str_replace(NL."$u_num	", NL.'u	',					//* <- mark self
preg_replace('~(\V+)	(\V+)	(\V+)\+\V+(	\V+?)~Uu', '$1	$3$4	$1. $2',//* <- transform data fields; TODO: move this to db.php?
NL.$t)));
					} else
					if ($etc == 4) {
						$content .= 'ref'.NL.
preg_replace('~(\d+)([^\d\s]\V+)?	(\V+)~u', '$1	$3', $t);		//* <- transform data fields
					} else	$done = ($t?'
		<textarea>'.$t.'</textarea>':$tmp_empty);
				}
				$task = '
		<p id="tabs">'.implode('|', $tmp_mod_pages).'</p>'.$lnk.$done;
			} else {

//* report form ---------------------------------------------------------------
				$task = get_template_form('report', REPORT_MIN_LENGTH, REPORT_MAX_LENGTH, $rt = 1);
			}
			$js[0]++;
		} else

//* active room task and visible content --------------------------------------
		if ($room) {
			data_lock($room);
if (TIME_PARTS) time_check_point('inb4 aim, locked');
			data_aim(!$u_opts['unknown'], get_room_skip_list());
			list($thread, $report, $last) = data_get_visible_threads();
			data_unlock();
if (TIME_PARTS) time_check_point('got visible data, unlocked');

			$t = $target['time'];
			exit_if_not_mod($t > $last?$t:$last);
			$task_time = ($t?$t:0);

			list($err_sign, $err_name) = get_req();
			if (GET_Q && ($err_sign != '!') && !($target['task'])) {
				if (data_is_thread_cap()) {
					$err_sign = '!';
					$err_name = 'trd_max';
				} else $draw_free = 1;
			}
			$desc = ($target['pic'] || !($target['task'] || $draw_free));
			if (MOD) $js['.mod'] = 0;
			if ($thread) {
				$content = (MOD
? $tmp_mod_post_hint   .':'.$tmp_mod_user_hint : (R1 || $u_flag['nor']?':'
: $tmp_report_post_hint.':'.$tmp_report_user_hint)).':'.ROOTPRFX.DIR_PICS.':';
				$flag = 'ackgmp';
				foreach (array(
					$u_opts['active']
				,	!$u_opts['count']
				,	$desc && $u_opts['kbox']
				,	GOD, MOD, PIC_SUB) as $k => $v) if ($v) $content .= $flag[$k];
				$a = array();
				$b = '<br>';
if (TIME_PARTS) time_check_point('inb4 raw data iteration'.NL);
				foreach ($thread as $tid => $post) {
					$t = '';
					$k = $post[count($post)][1].'_'.$tid;
					foreach ($post as $postnum => $tab) {
						if ($u_opts['times'] && $tab[1]) {
							$l = explode($b, $tab[1], 2);
							$l[0] = NB;
							$l = implode($b, $l);
						} else $l = $tab[1];
						if ($tab[2]) {
							$r = explode($b, $tab[2], 2);
							$r[0] = (!$u_opts['names'] && isset($usernames[$r[0]])?$usernames[$r[0]]:NB);
							$r = implode($b, $r);
						} else $r = NB;
						$ta = array(
							$u_opts['own']?0:$tab[0]	//* <- trd.num, userbar color code
						,	$l				//* <- time: format in JS
						,	$r				//* <- username
						,	$tab[4]				//* <- post content
						);
					//	if ($ta[1] === NB && $ta[2] === NB) $ta[1] = $ta[2] = ' ';
						if (count($tab) > 5) $ta[] = $tab[5];	//* <- pic comment
						if (/*MOD &&*/ is_array($r = $report[$tid][$postnum])) {
							foreach ($r as $col => $l)
							foreach ($l as $time => $line)
							$ta[$col+1] .= "<br>$time: $line";
						}
						$t .= (($postnum != 1)||$u_flag['nor']||(R1&&!MOD)?NL:NL.$tid.',').implode('	', $ta);
					}
					$a[$k] = $t;
if (TIME_PARTS) time_check_point('done trd '.$k);
				}
				ksort($a);
				$content .= implode(NL, array_reverse($a));
if (TIME_PARTS) time_check_point('after sort + join');
				if (GOD) {$task_data['filter'] = 1;}
			} else if (GOD) $content = "$tmp_empty:$tmp_empty::vg
0,0	0	&mdash;	";
			$t = $target['task'];
			if ($desc) {
				$task = get_template_form(array('describe'
,	$t?$tmp_describe_this:$tmp_describe_new
,	$tmp_describe_hint.($u_flag['nop']?' '.$tmp_no_play_hint:'')
,	''
,	GOD?$tmp_filter_placeholder:''
), DESCRIBE_MIN_LENGTH);
				if ($t) {
					$src = (strpos($t, ';') ? pic_resized_path(pic_normal_path($t)) : $t);
					$task .= '
		<img src="'.ROOTPRFX.(PIC_SUB?pic_subpath($src):DIR_PICS.$src).'" alt="'.$t.'">';
				} else $task_time = '-';
			} else {
				$f = "t0=$task_time;send".DRAW_REST;
				if (!$u_opts['save2common']) $f .= ';saveprfx='.NAMEPRFX;
				if (DRAW_JPG_PREF) $f .= ';jp='.DRAW_JPG_PREF;
				if ($u_draw_max_undo) $f .= ';undo='.$u_draw_max_undo;
				if ($err_sign && $err_sign != '!') {
					$u_draw_app = $err_sign;
					if (strpos($err_name, 'x')) $wh = explode('x', $err_name);
				}
				foreach (array('DEFAULT_', 'LIMIT_') as $i => $j)
				foreach ($tmp_whu as $k => $l) {
					$p = $tmp_wh[$k].($i?'l':'');
					if ((!$i && $wh && ($v = $wh[$k]))
					|| (defined($v = "DRAW_$j$l") && ($v = constant($v)))
					) $f .= ";$p=$v";
				}
				$task = '
		<p>'.($t?$tmp_draw_this.'</p>
		<p>'.$t:$tmp_draw_free).'</p><noscript>
		<p class="hint">'.$tmp_require_js.'</p></noscript>';
				$n = get_draw_app_list($u_draw_app);
				$subtask = '
		<script id="'.$n['name'].'-vars" src="'.$n['src'].'" data-vars="'.$f.'"></script>
		<div class="task">
			<p class="hint">'.$n['list'].'</p>
		</div>';
			}
			if ($desc || $t) $task_data['t'] = $task_time.($t?'-'.intval($target['thread']):'');
			$js[0]++;
		} else {

//* active rooms list ---------------------------------------------------------
			if ($vr = data_get_visible_rooms()) {
				exit_if_not_mod(array_shift($vr));

				$rda = ROOTPRFX.DIR_ARCH;
				$s = (($c = $u_opts['count'])?' ':', ');
				$content = "$rda*$s*$cfg_room".($c?'':"
$tmp_room_count_threads	$tmp_room_count_posts");
				foreach ($vr as $rn => $n) {
					if ($c) {
						if ($n[7]) foreach ($n[7] as $v) $rn .= $s.($v?'?':0);
						$content .=
NL.($n[2]?'*':NB).'	'.NB."	$rn";
					} else {
						if ($n[7]) $rn .= $s.implode($s, $n[7]);	//* <- colored counts of reports, frozen, etc
						if ($n[2]) $n[1] .= $s.$n[2];
						if (!$u_opts['times']) {
							if ($n[2]
							&&  $n[3]) $n[1] .= $s.date(TIMESTAMP, $n[3]);	//* <- last archived
							if ($n[6]) $n[5] .= $s.date(TIMESTAMP, $n[6]);	//* <- last active post
						}
						$content .=
NL."$n[0]$s$n[1]	$n[4]$s$n[5]	$rn";
					}
					if ($n[8]) {
						foreach ($n[8] as $k => $v) if ($k) $n[8][$k] = "$tmp_announce[$k]: $v"; else $content .= '/';
						$content .=
'/'.preg_replace('~\s+~u', ' ', preg_replace('~<[^>]*>~', '', preg_replace('~<br[ /]*>~i', NL, implode(NL, $n[8]))));	//* <- announce/frozen
					}
				}
			}
			$task = get_template_form('rooms', ROOM_NAME_MIN_LENGTH);
			$task_data['filter'] = 2;
			$js[0]++;
		}
	} else

	if (!$room) {
		header('HTTP/1.1 303 To home room');
		header('Location: '.$cfg_room.($u_room_home?$u_room_home.'/':''));
		exit;
	}
} else {

//* not registered ------------------------------------------------------------
	if ($etc) die('x');
	foreach ($cfg_dir as $k => $v) unset(${'qd_'.$k});
	$task = get_template_form(array(ME, $tmp_name_yourself, $tmp_name_hint), USER_NAME_MIN_LENGTH);
	$js[0]++;
}

$room_title = ($room == ROOM_DEFAULT ? $tmp_room_default : $tmp_room.' '.$room);
$s = array();
foreach (($short = $u_opts['head'])
	? array('/','..','.','a','?','?','#')
	: array($tmp_title, $tmp_rooms, $room_title, $tmp_archive, $tmp_options, $tmp_draw_test, $tmp_mod_panel)
as $v) $s[] = $v.($short||(substr($v, -1) == '.')?'':'.').'</a>';
$r = ($a = '
			<a href="').($qd_room ? ($room?'..':'.') : $cfg_room).'">'.$s[1];

//* timings -------------------------------------------------------------------
if (!MOD || !TIME_PARTS || !is_array($tcp)) $tcp = 0;
$t = explode(' ',microtime());
$t = ($t[1]-T0) + ($t[0]-M0);
$took = date(TIMESTAMP, T0).sprintf($tmp_took, ($tcp?'<a href="javascript:'.(++$js[0]).',toggleHide(took)">'.$t.'</a>':$t));
if ($tcp) {
	foreach ($tcp as $t => $comment) {
		$t = explode(' ', $t);
		$t = ($t[1]-T0) + ($t[0]-M0);
		$l = strlen($t_diff = sprintf('%.6f', $t - $t_prev));
		for ($i = 0; $i < $l; $i++) if (trim($t_diff[$i], '0.')) break; else $t_diff[$i] = ' ';
		$t_prev = $t;
		$tfc .= NL.sprintf('%.6f, +', $t).$t_diff.' : '.$comment;
	}
	$took .= '<span id="took" style="display:none">'.$tfc.'</span>';
}

//* final page data to show ---------------------------------------------------
define(S, '. ');
die(get_template_page(array(
	'lang' => $lang
,	'title' =>
		($qd_opts == 1 ? $tmp_options.S :
		($qd_arch ? ($room ? $room_title.S : '').$tmp_archive.S :
		($qd_room ? ($room ? (
			$etc ? (GOD?$tmp_mod_panel.' - '.$mod_page:$tmp_report).S : ''
		).$room_title.S : $tmp_rooms.S) : ''))).$tmp_title.
		($qd_opts == 2 ? S/*.$tmp_draw_test.' '*/.$tmp_options_field['draw_app'] : '')
,	'header' => $rt?'':
		($u_key?('
		<div>'.
			$a.ROOTPRFX.'">'.$s[0].($short?$r:'').
				($room ?
			$a.($qd_room ? '.' : $cfg_room.$room.'/').'">'.$s[2].
				($qd_arch || is_dir($arch = DIR_ARCH.$room) ?
			$a.($qd_arch ? '.' : ROOTPRFX.$arch.'/').'">'.$s[3]
				: '') : '').'
		</div>
		<div class="r">'.(GOD?
			$a.$cfg_room.($room?$room:ROOM_DEFAULT).'/1">'.$s[6]:'').($short?'':$r).
			$a.($qd_opts ? '.' : ROOTPRFX.DIR_OPTS.($room?$room.'/':'')).'">'.$s[4].'
		</div>'
		):('
		<div>'.
			$a.ROOTPRFX.'">'.$s[0].
			$a.ROOTPRFX.'?drawtest">'.$s[5].'
		</div>'.(is_dir(DIR_ARCH)?'
		<div class="r">'.$a.ROOTPRFX.DIR_ARCH.'">'.$s[3].'
		</div>':'')
		)).($err_sign != '!'?'':'
		<br clear="all">
		<p class="anno report">'.(($e = $tmp_post_err[$err_name])?$e:$err_name).'</p>')
,	'data' => $task_data
,	'task' => $task?$task:'Err... What?'
,	'subtask' => $subtask
,	'content' => $content
,	'footer' => $rt?'':($u_opts['times'] || !$u_key?'':'
		<p class="l hint">'.$took.'</p>').($u_opts['names'] || !constant('FOOT_NOTE')?'':'
		<p class="r hint">'.vsprintf(FOOT_NOTE, $tmp_foot_notes).'</p>')
,	'js' => $js
)));

}




//* after posting -------------------------------------------------------------
post_refresh:

$p = $post_status;
$ok = (!$p || OK == substr($p, 0, strlen(OK)));
$msg = ($ok?$p:$tmp_post_err[$p]);

if (isset($_POST['report'])) die(get_template_page(array(
	'title' => $msg
,	'task' => $p.($ok?'<script>window.close();</script>':'')
)));
header('HTTP/1.1 303 Refresh after POST. '.($p = rawurlencode($p)));

$l = (($room = $_POST['rooms']) && ($room = trim_room(urldecode($room)))
	? rawurlencode($room).'/'
	: ($etc && $etc[0] != '-'?$etc:'.')
);
if ($ok) {
	if ($u_key) {
		$o = '';
		if (!($q = ($u_key[0] == 'q')) && $u_opti[0] != 'd') {
			foreach ($opt_name as $k => $v) if (${$n = "u_$v"}) $o .= $$n.$opt_sufx[$k];
			$r = trim_room($u_room_home);
			$o = "/$o/$r/$u_draw_app";
		}
		$h = 'Set-Cookie: ';
		$x = '; expires='.gmdate(DATE_COOKIE, T0 + ($q?-1234:QK_EXPIRES)).'; Path='.ROOTPRFX;
		header($h.ME."=$u_key$o$x");
		if ($add_qk) header($h.$add_qk.$x);
	}
} else $l .= '?!='.$p;
header('Location: '.$l);
printf($tmp_post_ok_goto, $msg, $l);
?>
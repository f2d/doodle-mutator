<?php

define(DATA_SERIALIZE, 'json_encode');
define(DATA_UNSERIALIZE, 'json_decode');
define(DIR_DATA, 'l/');				//* <- all data except the threads content
define(DIR_USER, 'u');				//* <- userlist filename
define(DIR_META_R, DIR_DATA.DIR_ROOM);		//* <- room names inside, separate from any reserved file/dirnames
define(DIR_META_U, DIR_DATA.DIR_USER);		//* <- per user files
define(BOM, pack('CCC', 239, 187, 191).NL);	//* <- UTF-8 Byte Order Mark
define(NOR, '&mdash;');				//* <- no-request placeholder
define(TXT, '		');
define(IMG, '	<	');
define(TRD_MOD, '~^((\d+)(\..+)?(\.log))(\.(s)top|\.(d)el)?$~i');
define(TRD_PLAY, '~^(\d+)(?:\.p(\d+|f))?(?:\.u(\d+))?(?:\.t(\d+))?(\..+)?(\.log)$~i');

function trim_bom($str) {return trim(str_replace(BOM, '', $str));}

function data_ensure_filepath_mkdir($file_path) {
	if (($i = strrpos($file_path, '/')) && !is_dir($d = substr($file_path, 0, $i))) mkdir($d, 0755, true);
	return $file_path;
}

function data_cache($file_path) {
	global $file_cache;
	return ($file_cache[$file_path]
	?: (	$file_cache[$file_path] = file_get_contents($file_path)
	));
}

function data_post_refresh($r = '') {
	global $room;
	$d = DIR_META_R;
	foreach ((
		true === $r
		? get_dir_contents($d)
		: (array)($r ?: $room)
	) as $r) if (is_file($f = "$d$r/post.count") && unlink($f)) $report .= ($report?NL:'')."deleted $f";
	return $report;
}

function data_put($file_path, $content = '', $r = '') {
	global $room;
	if (!$file_path || $file_path === 1) $file_path = DIR_META_R.($r ?: $room).'/'.($file_path?'arch':'room').'.count';
	return file_put_contents(data_ensure_filepath_mkdir($file_path), $content);
}

function data_log($file_path, $line, $n = BOM, $report = 1) {
	$old = is_file($file_path);
	$line = ($old?NL:$n).$line;
	$written = file_put_contents(data_ensure_filepath_mkdir($file_path), $line, FILE_APPEND);

	if (!$written && $old) {	//* <- wrong user rights, maybe
		$log = 'Cannot write to '.$file_path;
		if (
			rename($file_path, $old = $file_path.'.old'.T0.'.bak')
		&&	($written = file_put_contents($file_path, file_get_contents($old).$line))
		) {
			$del = (unlink($old)?'deleted':'cannot delete');
			$log .= NL."Copied $written to new file, $del $old";
		}
	}
	if ($log && $report) data_log_adm($log);
	return $written;
}

function data_global_announce($type = 'all', $room = '') {
	if ($d = ($room ?: $_REQUEST['room'] ?: '')) $d = DIR_ROOM.$d.'/';
	$x = '.txt';
//* check single presence:
	global $tmp_announce;
	if (array_key_exists($type, $tmp_announce)) return (
		is_file(DIR_DATA.($f = $type.$x))
	||	($d && is_file(DIR_DATA.$d.$f))
	);
//* get all contents, or last mod.date:
	switch ($type) {
		case 'all': $a = array(); break;
		case 'last': $a = 0; break;
		default: return false;
	}
	foreach ($tmp_announce as $k => $v) {
		if ($i = strrpos($k, '_')) {
			if (!$d) continue;
			$f = $d.substr($k, $i+1);
		} else {
			if ($room) continue;
			$f = $k;
		}
		if (is_file($f = DIR_DATA.$f.$x)) switch ($type) {
			case 'all': if (trim_bom($v = file_get_contents($f))) $a[$k] = $v; break;
			case 'last': if (($v = filemtime($f)) && $a < $v) $a = $v; break;
		}
	}
	return $a;
}

function data_lock($path) {
	global $lock;
	if (!$path) {
//* lock all existing rooms (not users):
		$path = array();
		foreach (get_dir_contents($d = DIR_ROOM) as $r) if (is_dir($d.$r)) $path[] = $r;
	}
	$i = 0;
	foreach ((is_array($path) ? $path : array($path)) as $r) {
		if (!$lock) $lock = array();
		if (!$lock[$r]) {
			$d = DIR_DATA.($r[0] == '/'?DIR_META_U:DIR_META_R);	//* "data/lock/user/num.lock" = "l/l/u/0.lock"
			if (
				($k = fopen(data_ensure_filepath_mkdir("$d$r.lock"), 'a'))
			&&	flock($k, LOCK_EX)				//* <- acquire an exclusive lock
			) $lock[$r] = $k;
			else die('Unable to lock data!');
		}
		$i++;
	}
	return $i;
}

function data_unlock_key($f) {
	global $lock;
	if ($k = $lock[$f]) {
		flock($k, LOCK_UN);					//* <- release the lock
		fclose($k);
		unset($lock[$f]);
	}
}

function data_unlock($r = '') {
	global $lock;
	if (!is_array($lock)) return;

	if ($r) data_unlock_key($lock[($r[0] == '/'?DIR_META_U:DIR_META_R).$r]);
	else foreach ($lock as $f) data_unlock_key($f);
}

function data_fix($t) {
	$a = 0;
	$e = '.log';
	if ($t == 'users') {
		if (is_dir($d = DIR_META_U) && ($arr = glob("$d/*$e", GLOB_NOSORT))) {
			natcasesort($arr);
			$data_types = array('ip', 'flag', 'task');
			foreach ($arr as $f) if ($tasks = get_file_lines($f)) {
				$flags = array();
				$ips = array();
				foreach (($csv = explode(',', trim_bom(array_shift($tasks)))) as $flag) {
					if (rtrim($flag, '1234567890.')) $flags[] = $flag;
					else $ips[] = ($ips?'':'old').'	'.$flag;
				}
				$new = rtrim($f, $e);
				$done .= NL.(++$a).'	'.$f;
				foreach ($data_types as $x) {
					$s = $x.'s';
					if ($i = trim(implode(NL, $$s))) {
						$done .= '	'.$x.'s = '.count($$s);
						file_put_contents("$new.$x", $i);
					}
				}
			}
		}
if (TIME_PARTS && $a) time_check_point("done $a $t");
	} else
	if ($t == 'reports') {
		foreach (get_dir_contents($d = DIR_META_R) as $room) {
			if ($arr = glob("$d$room/*.report.*", GLOB_NOSORT)) {
				natcasesort($arr);
				$a = 0;
				$t = 'reports';
				$dest = "$d$room/$t/";
				foreach ($arr as $f) if (is_file($f)) {
					$n = data_ensure_filepath_mkdir($dest.intval(get_file_name($f)).$e);
					$done .= NL.(++$a).': '.$f.' -> '.$n.' : '.rename($f, $n);
				}
if (TIME_PARTS && $a) time_check_point("done $a $t in $room");
			}
			if ($arr = glob("$d$room/*$e", GLOB_NOSORT)) {
				natcasesort($arr);
				$a = 0;
				$t = 'actions';
				$dest = "$d$room/$t/";
				foreach ($arr as $f) if (is_file($f)) {
					$n = data_ensure_filepath_mkdir($dest.get_file_name($f));
					$done .= NL.(++$a).': '.$f.' -> '.$n.' : '.rename($f, $n);
				}
if (TIME_PARTS && $a) time_check_point("done $a $t in $room");
			}
		}
	}
	return $done;
}

function data_check_u($u, $reg) {
	global $u_key, $u_num, $u_flag, $usernames, $last_user, $room;
	$d = DIR_META_U;
	if (is_file($f = "$d.log")) foreach (get_file_lines($f) as $line) if (strpos($line, '	')) {
		list($i, $k, $t, $name) = explode('	', $line);
		if ($last_user < $i) $last_user = $i;
		if ($u === $k) {
			$u_key = $k;
			$u_num = $i;
			data_lock($n = '/'.$u_num);
			if ($reg) return $u_num;
			if (is_file($f = "$d$n.flag")) foreach (get_file_lines($f) as $g) $u_flag[$g] = $g;
		}
		if (!$reg) $usernames[$i] = $name;
	}
	return $u_num;
}

function data_log_user($u_key, $u_name) {
	global $last_user;
	$u_num = $last_user+1;
	$d = DIR_META_U;
	data_lock('/new');
	$r = data_log($f = "$d.log", "$u_num	$u_key	".T0.'+'.M0."	$u_name");
	if ($r && !$last_user) data_put("$d/$u_num.flag", 'god');	//* <- 1st registered = top supervisor
	data_unlock('/new');
	return $r;
}

function data_collect($f, $uniq) {
	if (!is_file($f)
	|| false === strpos(file_get_contents($f).NL, '	'.$uniq.NL)
	) data_log($f, T0.'+'.M0.'	'.$uniq, '');
}

function data_log_ip() {
	global $u_num;
	data_collect(DIR_META_U."/$u_num.ip", $_SERVER['REMOTE_ADDR']);
}

function data_log_ref() {
	if (!POST
	&& ($r = $_SERVER['HTTP_REFERER'])
	&& ($r != ($s = "http://$_SERVER[SERVER_NAME]"))
	&& (0 !== strpos($r, $s.'/'))
	) data_collect(DIR_DATA.'ref.log', $r);
}

function data_log_adm($a) {			//* <- keep logs of administrative actions by date
	global $u_num, $room;
	$d = date('Y-m-d', T0);
	$u = (GOD?'g':(MOD?'m':'r'));
	$r = ($room?DIR_META_R.$room:DIR_DATA);
	return data_log("$r/actions/$d.log", T0.'+'.M0."	$u$u_num	$a", BOM, 0);
}

function data_log_report($r, $freeze = 0) {	//* <- r = array(t-r-c, reason, thread, row, column)
	global $u_num, $room;
	$u_tab = '	'.$u_num.TXT;
	foreach (get_dir_contents($d = DIR_ROOM.$room.'/') as $f) if (
		preg_match(TRD_MOD, $f, $m)
	&&	($m[2] == $r[2])
	) {
		if (
			is_file($f = $d.$f)
		&&	strpos(str_replace(IMG, TXT, file_get_contents($f)), $u_tab)	//* <- cannot report invisible
		) {
			if (!$m[5] && $freeze) rename($f, $f.'.stop');
			data_log(DIR_META_R."$room/reports/$m[2].log", T0.'+'.M0."	$r[3]	$r[4]	$r[1]");
			if ($r = data_log_adm("$r[0]	$r[1]")) data_post_refresh();
			return $r;
		}
		break;
	}
	return 0;
}

function data_get_mod_log($t = 0, $mt = 0) {	//* <- Y-m-d|int, 1|0
	global $room;
	$d = DIR_META_R."$room/actions";
	if ($t) {
		if ($t === 'reflinks') $t = DIR_DATA.'ref'; else
		if ($t === 'users') $t = DIR_META_U; else $t = "$d/$t";
		if (is_file($f = "$t.log")) return ($mt ? filemtime($f) : trim_bom(file_get_contents($f)));
	} else {
		$t = array();
		foreach (get_dir_contents($d) as $f) if (preg_match(PAT_DATE, $f, $m)) $t[$m[1]][] = $m[4];
		return $t;
	}
}

function data_get_last_post_time($t) {return intval(substr($t, $last_line = strrpos($t, NL)+1, strpos($t, '	', $last_line)-$last_line));}
function data_get_thread_count($r = 0, $a = 0) {
	if (!$r) {global $room; $r = $room;}
	$d = ($a?'arch':'room');
	return intval(
		$a <= 0 && is_file($f = DIR_META_R."$r/$d.count")	//* <- seems ~2x faster than scandir() on ~50 files
		? file_get_contents($f)
		: (
			($d = get_const('DIR_'.strtoupper($d))) && is_dir($d .= $r)
			? get_dir_top_file_id($d)
	/*		? count(
				array_diff(scandir($d), array('.', '..', trim(DIR_THUMB, '/')))	//* <- seems ~2x faster than glob()
			//	glob($d.'/*.htm', GLOB_NOSORT)
			)
	*/		: 0
		)
	);
}

function data_get_archive_count($r = 0, $re = -1) {return data_get_thread_count($r, $re);}
function data_get_archive_mtime($r = 0) {
	if (!$r) {global $room; $r = $room;}
	return intval(
		is_file($f = DIR_META_R.$r.'/arch.count')
		? filemtime($f)
		: get_dir_top_filemtime(DIR_ARCH.$r)
	);
}

function data_is_thread_cap($r = 0) {
	if (!$r) {global $room; $r = $room;}
	foreach (get_dir_contents(DIR_ROOM.$r) as $f) if (
		preg_match(TRD_MOD, $f, $m)
	&&	!$m[7]						//* <- "burnt" not counted
	&&	++$n >= TRD_MAX_COUNT
	) return $n;
	return 0;
}

function data_get_thread_by_num($n) {
	global $room, $d_cache, $t_cache;			//* <- ids/paths/content cached for batch processing
	if (!is_array($t_cache)) $t_cache = array(); else
	if (($t = $t_cache[$n]) && is_file($t[0].$t[1])) return $t;
	if ($d_cache || is_dir($d_cache = DIR_ROOM.$room.'/'))
	foreach (get_dir_contents($d_cache) as $f) if (
		preg_match(TRD_MOD, $f, $m)
	&&	$n == $m[2]
	) return ($t_cache[$n] = array($d_cache, $f, $m));	//* <- dir/path/, filename, match array [num,etc,ext,.stop]
	return 0;
}

function data_get_u_by_file($f, $line = 0, $get_pic = 0) {
	if (!$line) $f = file_get_contents($f); else
	if ($line > 0) {
		if ($line >= count($f = get_file_lines($f))) return 0;
		$f = $f[$line];
	}
	if ($get_pic) {
		if (false === ($start = strrpos($f, IMG))) return 0;
		$start = substr($f, $start+strlen(IMG));
		return substr($start, 0, strpos($start, '	'));
	}
	$l = strrpos($t = str_replace(IMG, TXT, $f), TXT);
	$t = substr($t, 0, $l);
	return substr($t, strrpos($t, '	')+1);
}

function data_get_u_by_post($a) {
	global $u_cache;
	if (!is_array($u_cache)) $u_cache = array(); else
	if ($p = $u_cache[$tp = "$a[0]-$a[1]"]) return $p;
	if (list($d, $f) = data_get_thread_by_num($a[0]))
	return ($u_cache[$tp] = data_get_u_by_file($d.$f, $a[1]));
	return 0;
}

function data_set_u_flag($u, $flag, $on = -1, $harakiri = 0) {
	global $u_num, $u_flag, $room;
	if (is_array($u)) $u = data_get_u_by_post($u);

	if ($on < 0) {
		if ($u && data_lock('/new') && ($ul = get_file_lines($f = DIR_META_U.'.log')))
		foreach ($ul as $k => $line)
		if (intval($line) == $u) {
			$ul[$k] = substr($line, 0, $s = strrpos($line, '	')+1).$flag;
			data_put($f, implode(NL, $ul));
			$f = substr($line, $s);
			return "$u: $f -> $flag";			//* <- rename user
		}
		return 'no user with ID '.$u;
	}

	if (!$u) return 0;

	data_lock($n = '/'.$u);
	if (is_file($f = DIR_META_U.$n.'.flag')) {
		$flags = array();
		foreach (get_file_lines($f) as $k) $flags[$k] = $k;
		if (
			!GOD
		&&	(
				$flags['god']
			||	(
					$on
				&&	$flag == 'ban'			//* <- mods cannot ban mods
				&&	($flags['mod'] || $flags['mod_'.$room])
				)
			||	(
					!$on
				&&	!$harakiri
				&&	$u == $u_num
				&&	substr($flag,0,3) == 'mod'	//* <- mods cannot self-resign
				)
			)
		) return -$u;

		foreach ($flags as $k => $v) if ($k == $flag) {
			if ($on) return $u;		//* <- add, exists
			unset($flags[$k]);		//* <- remove
			++$rem;
		}
		if ($on) data_log($f, $flag, '');	//* <- add
		else if (!$rem) return 0;		//* <- remove, not exists
		else if ($flags) data_put($f, implode(NL, $flags));
		else unlink($f);
	} else if ($on) data_put($f, $flag);		//* <- add, new file
	else $u = 0;
	return $u;
}

function data_replace_u_flag($f, $from, $to = '') {
	if (!$from) return;
	$log_prefix = NL.$from.' -> '.($to ?: 'unset').': user ID = ';
	if ($f) {
		$f = get_file_name($f);
		$i = '/'.intval($f);
		if (is_file($f = DIR_META_U.$i.'.flag') && data_lock($i)) {
			if (false !== ($line = array_search($from, $x = get_file_lines($f)))) {
				if ($to) $x[$line] = $to;
				else unset($x[$line]);
				if ($x = implode(NL, $x)) file_put_contents($f, $x);
				else unlink($f);
				$log .= $log_prefix.substr($i, 1);
			}
			data_unlock($i);
		}
	} else foreach (glob(DIR_META_U.'/*.flag') as $f) $log .= data_replace_u_flag($f, $from, $to);
	return $log;
}

function data_get_user_info($u) {
	$r = array();
	if (data_lock($u = '/'.$u)) {
		$n = DIR_META_U.$u;
		foreach (array(
			'flag'	=> 'Flags'
		,	'ip'	=> 'IPs'
		,	'task'	=> 'Tasks'
		) as $k => $v) if (is_file($f = "$n.$k") && ($f = trim(file_get_contents($f)))) $r[$v] = $f;
		data_unlock($u);
	}
	return $r;
}

function data_get_full_threads() {
	global $room, $usernames;
	if (!$usernames) die('no usernames');			//* <- bug checking
	$threads = array();
	foreach (get_dir_contents($d = DIR_ROOM.$room.'/') as $f) if (
		preg_match(TRD_PLAY, $f, $n)
	&&	$n[2]
	&&	($n[2] == 'f' || $n[2] >= TRD_MAX_POSTS)
	&&	(R1 || !$n[4] || ($n[4] + TRD_ARCH_TIME < T0))
	&&	is_file($f = $d.$f)
	) {
		$a = array($f,'','');
		foreach (get_file_lines($f) as $line) if (strpos($line, '	')) {
			$tab = explode('	', $line);
			$tab[0] = date(TIMESTAMP, $last_time = $tab[0]);
			$tab[1] = $usernames[$tab[1]];
			if ($tab[2]) {
				if (!$a[2]) $a[2] = get_pic_subpath($tab[3]);		//* <- thumb
				$p = get_pic_url($tab[3]);
				if ($b = (strpos($p, ';') ? explode(';', $p, 2) : '')) $p = get_pic_resized_path($b[0]);
				$p = '<img src="'.$p.'">';
				$tab[3] = ($b ? '<a href="'.$b[0].'">'.$p.'</a>;'.$b[1] : $p);
			}
			unset($tab[2], $tab[5]);
			$a[1] .= NL.implode('	', $tab);	//* <- content
		}
		if (R1 || $n[2] == 'f' || ($last_time + TRD_ARCH_TIME < T0)) $threads["$last_time-$f"] = $a;
	}
	ksort($threads);
	return $threads;
}

function data_archive_ready_go() {
	if ($threads = data_get_full_threads()) {
		require_once(NAMEPRFX.'.arch.php');
		return data_archive_full_threads($threads);
	}
}

function data_del_pic_file($f, $keep) {
	if (!is_file($f)) return false;
	return (
		$keep
		? rename($f, $keep.get_file_name($f))
		: unlink($f)
	);
}

function data_del_pic($f, $keep) {
	global $room;
	if ($keep && !is_dir($keep = DIR_PICS."deleted/$room/")) mkdir($keep, 0755, true);
	foreach (array(get_pic_resized_path($f), $f) as $f) $status = data_del_pic_file($f, $keep);
	return $status;
}

function data_del_thread($f, $del_pics = 0) {
	global $room;
	$count = array();
	if (is_file($f)) {
		if ($del_pics && preg_match_all('~(<img src="[^>]*/|'.IMG.')([^/">	]+)[	"]~is', file_get_contents($f), $m)) {
			$to_trash = (1 == $del_pics);
			foreach ($m[2] as $p) if (data_del_pic(get_pic_subpath($p), $to_trash)) $count['pics']++;
		}
		if (unlink($f)) {
			$count['files']++;
			$n = intval(get_file_name($f));
			if (is_file($r = DIR_META_R."$room/reports/$n.log") && unlink($r)) $count['reports']++;
		}
	}
	return $count;
}

function data_del_tree($d, $del_pics = 0) {
	$count = array();
	if (is_dir($d)) {
		foreach (get_dir_contents($d) as $sub)
		foreach (data_del_tree("$d/$sub", $del_pics) as $k => $v) $count[$k] += $v;
		$count['dirs'] += (int)rmdir($d);
	} else
	if (is_file($d)) foreach ((
		$del_pics
		? data_del_thread($d, $del_pics)
		: array('files' => (int)unlink($d))
	) as $k => $v) $count[$k] += $v;
	return $count;
}

function data_mod_action($a) {			//* <- array(option name, thread, row, column, option num)
	global $u_num, $u_flag, $room, $merge;

	if (!MOD) return 0;

	$ok = 0;
	$q = explode('+', array_shift($a));
	$o = array_shift($q);
	$un = count($q);
	$msg = ($_POST[$msg = "t_$a[0]_$a[1]_$a[2]"] ? stripslashes($_POST[$msg]) : '');
	if ($a[2] > 1) $a = $a[0];

//* mod left ------------------------------------------------------------------

	if ($o == 'archive') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			if ($un > 1) {
				$p = substr_count($t = file_get_contents($d.$f), IMG);
				$t = data_get_last_post_time($t);
				if (rename($d.$f, "$d$m[2].p$p.t$t$m[4]")) $ok = OK;	//* <- put to wait
			} else {
				if (rename($d.$f, "$d$m[2].pf$m[4]")) $ok = OK;		//* <- get ready
				if ($ok && !$un && is_array($r = data_archive_ready_go())) {
					foreach ($r as $k => $v) if ($v) $ok .= ", $v $k";
				}
			}
			if ($ok) data_post_refresh();
		}
	} else
	if (substr($o,0,8) == 'freeze t') {
		if (
			(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&&	$f != ($n = $m[1].($un > 1?'.del':($un?'':'.stop')))
		&&	rename($d.$f, $d.$n)
		) {
			$ok = OK;
			data_post_refresh();
		}
	} else
	if (substr($o,0,8) == 'delete c') {
		if (
			(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&&	is_file($r = DIR_META_R."$room/reports/$m[2].log")
		&&	unlink($r)
		) {
			$ok = OK;
			data_post_refresh();
		}
	} else
	if (substr($o,0,8) == 'delete t') {
		if (
			(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&&	GOD
			? (
				($bak = NL.'['.NL.trim_bom(file_get_contents($d.$f)).NL.']')
			&&	($count = data_del_thread($d.$f, $un))
			)
			: ($bak = rename($d.$f, $d.$m[1].'.del'))
		) {
			$ok = OK.$bak;
			if ($count) $ok .= NL.'deleted counts: '.print_r($count, true);
			data_post_refresh();
		}
	} else
	if (substr($o,0,11) == 'delete post') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			$ok = $a[1];
			$old = get_file_lines($f = $d.$f);
			if (count($old) > $a[1]) {
				$ok .= '='.$old[$a[1]];			//* <- save post contents in log, just in case
				unset($old[$a[1]]);
				if (count($old) > 1) {
					data_put($f, $new = implode(NL, $old));
					if (strpos($m[3], 'p')) {$p = substr_count($new, IMG); rename($f, "$d$m[2].p$p$m[4]$m[5]");}
				} else {
					data_del_thread($f);
					$ok .= '	> void';
				}
				data_post_refresh();
			} else $ok = -$ok;
		}
	} else
	if (substr($o,0,7) == 'delete ') {
		if (
			(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&&	($fn = data_get_u_by_file($d.$f, $a[1], 1))
		&&	is_file($f = get_pic_subpath($fn))
		&&	(
				$un == 1
				? (file_put_contents($f, '') === 0)	//* <- 0-truncate
				: data_del_pic($f, !(GOD && $un > 1))
			)
		) $ok = "$a[1]=$f";
	} else
	if (substr($o,0,7) == 'merge t') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			if ($un) {
				$ok = $a[1];
				$old = count($f = get_file_lines($d.$f));		//* <- source to add
				if ($old-- > $ok) {
					$merge[$m[2]] = trim_bom(implode(NL, array_slice($f, $ok)));
					$ok = "+t$m[2] p$ok-$old";
				} else $ok .= ' post not found';
			} else if (is_array($merge) && count($merge)) {
				$n = array_unique(explode(NL, trim_bom($old = file_get_contents($f = $d.$f)).NL.implode(NL, $merge)));
				natsort($n);
				if ($old != ($new = BOM.trim_bom(implode(NL, $n)))) {
					file_put_contents($f, $new);
					$ok = $m[2].'<-'.implode(',', array_keys($merge));
				} else $ok = 'no change';
				data_post_refresh();
			}
		}
	} else
	if (substr($o,0,7) == 'split t') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			$ok = $a[1];
			if (
				$ok > 1
			&&	count($old = get_file_lines($f = $d.$f)) > $ok
			&&	count($p = array_slice($old, 0, $ok))
			&&	count($q = array_slice($old, $ok))
			) {
				$lst = BOM;
				if ($fst = strpos($old[$ok], IMG)) $lst .= substr($old[$ok], 0, $fst).TXT.NOR.NL;	//* <- add placeholder if pic first
				$p = substr_count($fst = implode(NL, $p), IMG);
				$q = substr_count($lst .= implode(NL, $q), IMG);
				data_put(0, $ok = data_get_thread_count()+1);
				data_put("$d$ok.p$p.log$m[5]", $fst);		//* <- put 1st half into new thread, un/frozen like old
				data_put($f, $lst);				//* <- put 2nd half into old thread, to keep people's target
				if (
					preg_match(TRD_PLAY, $m[1], $n)
				&&	$n[2]
				&&	($n[2] == 'f' || $n[2] >= TRD_MAX_POSTS)
				) {
					rename($f, "$d$n[1].p$q$n[6]$m[5]");	//* <- rename full thread to drop pics count
				}
				data_post_refresh();
			} else $ok .= ' post not found';
		}
	} else
	if ($o == 'add post') {
		if (
			($msg = trim($msg))
		&&	(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		) {
			$ok = $a[1];
			$l = get_file_lines($f = $d.$f);
			if (count($l) > ($n = $a[1])) {
				$old = $l[$n];
				$new = '';
		//* check line-separated "key: value" pairs: ------------------
				$lsv = array();
				$keys = explode(',', 're,text,file,meta'.(GOD?',user,time,browser':''));
				foreach (preg_split('~\v+~u', $msg) as $line)
				if (
					preg_match('~^(\w+)[\s:=]\s*(\S.*)$~u', trim($line), $match)
				&&	in_array($k = strtolower($match[1]), $keys)
				) {
					$v = preg_replace('~\s+~u', ' ', $match[2]);
					$ok .= NL."	$k => $v";
					if ($k == 're') {
						$k = 'text';
						$v = '<span class="mod">'.$v.'</span>';
					}
					$lsv[$k] = $v;
				}
			//	if (!$lsv) $lsv['text'] = preg_replace('~\s+~u', ' ', $msg);
				if ($lsv) {
					$tab = (
						$un < 2
						? array(T0, $u_num)		//* <- add, insert (own post from now, if not specified)
						: explode('	', $old)	//* <- edit, replace (assume old post as valid)
					);

			//* timestamp/ID, accept digits only, or no change:
					foreach (array('time', 'user') as $i => $k)
					if (($v = $lsv[$k]) && !trim($v, '0123456789')) $tab[$i] = $v;

			//* text, just make a post and be done:
					if ($v = $lsv['text']) $new = "$tab[0]	$tab[1]".TXT.$v;

			//* file/info, edit parts if post with file, or replace full post if enough values:
					else {
						$old_mark = $tab[2];
						$img_mark = trim(IMG);
						$v = get_file_name(strtr($lsv['file'], '"\\:?*<>', '\'/_____'));
						$t = $lsv['meta'];
						if ($old_mark == $img_mark) {
							if ($v) $tab[3] = $v; else if ($tab[3]) $v = 1;
							if ($t) $tab[4] = $t; else if ($tab[4]) $t = 1;
						} else {
							$tab[2] = $img_mark;
							if ($v) $tab[3] = $v;
							if ($t) $tab[4] = $t; else $tab[4] = '-';
						}
						if ($v && $t) {
							if ($v = $lsv['browser']) $tab[5] = $v;
							$new = implode('	', $tab);
						}
					}
				}
		//* save result: ----------------------------------------------
				if (!trim($new) || ($old == $new)) $ok .= NL.'! no change';
				else {
					if ($un == 1) $l[$n] = $new.NL.$old;	//* <- add before
					else if (!$un) $l[$n] = $old.NL.$new;	//* <- add after
					else {
						$l[$n] = $new;
						$ok .= NL.'old = '.$old;
					}
					if (data_put($f, implode(NL, $l))) {
						$ok .= NL.'new = '.$new;
						data_post_refresh();
					} else $ok .= NL.'! save failed';
				}
			} else $ok = -$ok;
		}
	} else

//* mod right -----------------------------------------------------------------

	if ($o == 'ban'		) $ok = data_set_u_flag($a, 'ban', !$un); else
	if ($o == 'can report'	) $ok = data_set_u_flag($a, 'nor', $un); else
	if ($o == 'give mod'	) $ok = data_set_u_flag($a, 'mod_'.$room, !$un); else
	if (substr($o,0,4) == 'hara') $ok = data_set_u_flag($u_num, 'mod_'.$room, 0, 1); else

	if (!GOD && $o != 'room announce') return 0; else

//* god right -----------------------------------------------------------------

	if (substr($o,0,3) == 'get') $ok = data_set_u_flag($a, 'nop', $un); else
	if (substr($o,0,3) == 'see') $ok = data_set_u_flag($a, 'see', !$un); else
	if ($o == 'give god'	) $ok = data_set_u_flag($a, 'god', !$un); else
	if ($o == 'rename') {
		if (!($new = trim_post($msg, USER_NAME_MAX_LENGTH))) return 0;
		$ok = data_set_u_flag($a, $new);
	} else

	if ((	($g = (0 === strpos($o, 'global')))
	||	($r = (0 === strpos($o, 'room')))
	) && (	($n = strpos($o, 'announce'))
	||	($z = strpos($o, 'freeze'))
	)) {
		$f = DIR_DATA.($r?DIR_ROOM.$room.'/':'').($n?'anno':'stop').'.txt';
		$ok = (($n?$msg:!$un)
			? ((data_put($f, $msg) === strlen($msg))?($msg ?: '-'):0)
			: (is_file($f) && unlink($f))
		);
	} else

//* god left ------------------------------------------------------------------

	if ($o == 'rename room') {
		if (!($msg = trim_room($msg))) return 0;
		if (!is_dir(DIR_ROOM.$room)) $ok = $room.' does not exist';
		else {
			if ($un) {
				if (!is_dir(DIR_ROOM.$msg)) $ok = $msg.' does not exist';
				else if (
					(list($d,$f,$m) = data_get_thread_by_num($a[0]))
				&&	data_lock($msg)
				&&	($n = data_get_thread_count($msg)+1)
				&&	copy($d.$f, DIR_ROOM."$msg/$n$m[3]$m[4]$m[5]")
				) {
					if (is_file($r = DIR_META_R."$room/reports/$a[0].log")) {
						copy($r, data_ensure_filepath_mkdir(DIR_META_R."$msg/reports/$n.log"));
					}
					$ok = $msg;
					data_put(0, $n, $msg);
					data_post_refresh($msg);
				}
			} else if (is_dir(DIR_ROOM.$msg)) $ok = $msg.' already exists';
			else {
				data_post_refresh();
				$ok = "$room -> $msg";
				foreach (array('arch', 'room', 'meta_r') as $f) if ($r = get_const('DIR_'.strtoupper($f))) {
					$ok .= ",$f:"
						.(is_dir($rr = $r.$msg) && rename($rr, $rr.'.'.T0.'.old_bak') ?'old_bak+':'')
						.(is_dir($rr = $r.$room) && rename($rr, $r.$msg) ?1:0);
				}
				$ok .= data_replace_u_flag(0, "mod_$room", "mod_$msg");
				$room = $msg;
			}
		}
	} else
	if ($o == 'nuke room') {
		$ok = 1;
		$da = ($un > 1);
		$r = DIR_META_R.$room;
		data_del_tree("$r/reports");

		$c = array('room', 'post');
		if ($da) $c[] = 'arch';
		foreach ($c as $f) if (($f .= '.count') && is_file($n = "$r/$f")) $ok .= ", $f: ".unlink($n);

		$count = array();
		$c = array('trds' => DIR_ROOM);
		if ($da) $c['arch'] = DIR_ARCH;
		foreach ($c as $f => $d) if (is_dir($d .= $room)) {
			foreach (data_del_tree($d, $un?2:1) as $k => $v) if ($v) $count[$k == 'files'?$f:$k] += $v;
		}
		foreach ($count as $k => $v) $ok .= ", $k: $v";
		$ok .= data_replace_u_flag(0, "mod_$room");

		clearstatcache(true);	//* <- does not help?
		global $d_cache, $t_cache;
		$d_cache = $t_cache = '';
	} else

//* no action -----------------------------------------------------------------

	return 0;

	if ($un) $o .= '+'.end($q);
	if (is_array($a)) $a = implode('-', $a);
	return data_log_adm("$a	$o: $ok");
}

function data_get_visible_rooms() {
	global $u_flag;
	$last = 0;
	$a = array();
	if (!function_exists($export = get_const('DATA_SERIALIZE'))) $export = 0;
	if (!function_exists($import = get_const('DATA_UNSERIALIZE'))) $import = 0;
	$rooms = get_dir_contents($dr = DIR_ROOM, 1, 1);
	ob_start();
if (TIME_PARTS) time_check_point('done scan, inb4 room iteration'.NL);
	foreach ($rooms as $r) if (is_dir($d = "$dr$r/")) {
		$last_time_in_room = 0;
		if (
			is_file($cf = DIR_META_R.$r.'/post.count')
		&&	($im = (
				$import
			&&	($cfc = file_get_contents($cf))
				? (
					$import == 'json_decode'
					? $import($cfc, true)
					: $import($cfc)
				) : include($cf)
			))
		&&	is_array($im)
		&&	($last_time_in_room = $im['last modified'])
		) {
			$c	= $im['counts'];
			$mod	= $im['marked'];
			$t = data_global_announce('last', $r);
			if ($last_time_in_room < $t) $last_time_in_room = $t;
		} else {
			$last_time_in_room = intval(T0);	//* <- force to now, less problems
			$last_post_time =
			$count_thrd =
			$count_desc =
			$count_pics = 0;
			$mod = array();
			data_lock($r);
			foreach (get_dir_contents($d) as $fn) if (is_file($path = $d.$fn)) {
				$thread_time = data_get_last_post_time($f = file_get_contents($path));
				if ($last_post_time < $thread_time) $last_post_time = $thread_time;
				$count_thrd ++;
				$count_desc += substr_count($f, TXT);
				$count_pics += substr_count($f, IMG);
				if (preg_match(TRD_PLAY, $fn, $m)) {
					if ($m[2] && ($m[2] == 'f' || $m[2] >= TRD_MAX_POSTS)) ++$mod['full'];
				} else
				if (preg_match(TRD_MOD, $fn, $m)) {
					if ($m[6]) ++$mod['stopped'];
					if ($m[7]) ++$mod['deleted'];
				}
			}
			foreach (get_dir_contents($d = DIR_META_R.$r.'/reports/') as $f) if (is_file($path = $d.$f)) {
				$mod['reports'] += substr_count(file_get_contents($path), NL);
			}
			$c = array(
				'threads now'	=> $count_thrd
			,	'threads ever'	=> data_get_thread_count($r)
			,	'threads arch'	=> data_get_archive_count($r)
			,	'last arch'	=> data_get_archive_mtime($r)
			,	'last post'	=> $last_post_time
			,	'pics'		=> $count_pics
			,	'desc'		=> $count_desc
			);
			$save = array(
				'last modified'	=> $last_time_in_room
			,	'counts'	=> $c
			,	'marked'	=> $mod
			);
			file_put_contents($cf,
				$export
				? (
					$export == 'json_encode'
					? $export($save, JSON_NUMERIC_CHECK | JSON_BIGINT_AS_STRING | JSON_PRETTY_PRINT)
					: $export($save)
				) : '<?php return '.var_export($save, true).';?>'
			);
			data_unlock($r);
		}
		if ($mod = array_filter($mod)) {
			if (!GOD) unset($mod['deleted']);
			if (
				GOD
			||	$u_flag['mod']
			||	$u_flag['mod_'.$r]
			) $c['marked'] = $mod;
		}
		if ($t = data_global_announce('all', $r)) $c['anno'] = $t;
		$a[$r] = array_filter($c);
		if ($last < $last_time_in_room) $last = $last_time_in_room;
if (TIME_PARTS) time_check_point('done room '.$r);
	}
	if ($o = trim(ob_get_clean())) data_log_adm('include(post.count) buffer dump: '.$o);
	return $a ? array(
		'last' => $last
	,	'list' => $a
	) : $a;
}

function data_get_visible_threads() {
	global $u_num, $u_flag, $room;
	if (!is_dir($d = DIR_ROOM.$room.'/')) return 0;
	$u_tab = '	'.$u_num.TXT;
	$u_chars = array('ban', 'god', 'mod', 'mod_'.$room, 'nor');
	$threads = array();
	$reports = array();
	$last = 0;
	$files = get_dir_contents($d, 1);
if (TIME_PARTS) time_check_point('done scan, inb4 thread iteration'.NL);
	foreach ($files as $fn) if (
		is_file($f = $d.$fn)
	&&	($f = data_cache($f))
	) {
		$pn = preg_match(TRD_MOD, $fn, $n);
		$pp = preg_match(TRD_PLAY, $fn, $p);
		$frz = ($pn && $n[6]);
		if (
			GOD
	//	||	(MOD && $frz)				//* <- only own or "frozen" for mods
		||	($u_flag['see'] && !($pn && $n[5]))	//* <- any active for seers
		||	(($pp || $frz) && strpos(str_replace(IMG, TXT, $f), $u_tab))
		) {
			$posts = array();
			$last_post_time = 0;
			foreach (explode(NL, $f) as $line) if (strpos($line = trim($line), '	')) {
				$tab = explode('	', $line);
				$t = intval($tab[0]);
				if ($last < $t) $last = $t;
				if ($last_post_time < $t) $last_post_time = $t;
				$u = $tab[1];
				$f = ($u == $u_num?'u':0);
				if (!$f && MOD) {		//* <- mods see other's status as color
					if (!isset($u_cache[$u])) {
						$u_cache[$u] = 0;
						if (is_file($f = DIR_META_U."/$u.flag") && ($f = get_file_lines($f))) {
							foreach ($u_chars as $c) if (in_array($c, $f)) {$u_cache[$u] = $c[0]; break;}
						}
					}
					$f = $u_cache[$u];
				}
				$t = array(
					'flag' => $f
				,	'user' => $u
				,	'time' => $t
				,	'post' => $tab[3]
				);
				if (count($tab) > 4) $t['used'] = $tab[4];
				if (count($tab) > 5) $t['browser'] = $tab[5];
				$posts[] = $t;
			}
			($f = $n[6].$n[7]) || ($f = ($p[2] && ($p[2] == 'f' || $p[2] >= TRD_MAX_POSTS)?'f':''));
			$threads[$tid = "$last_post_time/$n[2]$f"] = $posts;

			if ((MOD || $frz) && is_file($r = DIR_META_R."$room/reports/$n[2].log")) {
				$repl = array();
				foreach (get_file_lines($r) as $line) if (
					trim_bom($line)
				&&	count($tab = explode('	', $line, 4)) > 3
				) {
					$t = intval($tab[0]);
					if ($last < $t) $last = $t;
					$repl
						[$tab[1]-1]	//* <- row (postnum) saved starting with 1; $posts[] - with zero
						[$tab[2]]	//* <- column (left/right)
						[$t]		//* <- time
						= $tab[3];	//* <- content
				}
				$reports[$tid] = $repl;
			}
		}
if (TIME_PARTS) time_check_point("done trd $fn, last = $last");
	}
	return $threads ? array(
		'last' => $last
	,	'threads' => $threads
	,	'reports' => $reports
	) : $threads;
}

function data_check_my_task($aim = 0) {
	global $u_num, $u_flag, $u_task, $u_t_f, $room, $target;
	if ($u_flag['nop']) return '';

	$u_task = (is_file($u_t_f = DIR_META_U."/$u_num.task") ? get_file_lines($u_t_f) : array());
	foreach ($u_task as $k => $line) if (strpos($line, '	')) {
		$a = explode('	', $line, 4);
		if ($a[1] == $room) {
			$target = array(
				'time'	=> $a[0]
			,	'thread'=> ($tt = $a[2])
			,	'post'	=> ($p = $a[3])
			,	'pic'	=> (false === ($i = strrpos($p, '	')))
			,	'task'	=> $i ? substr($p, $i+1) : $p
			);
			unset($u_task[$k]);
			break;
		}
	} else unset($u_task[$k]);

	if ($aim) return $tt;

	if (!is_dir($d = DIR_ROOM.$room.'/')) return 'no_room';
	if (!$tt || !preg_match(TRD_PLAY, $tt, $m)) return 'no_task';
	$a = array(
		'task_owned' => $tt		//* <- retake for new interval
	,	'task_reclaim' => $m[1].$m[6]	//* <- after dropped by others
	);
	foreach ($a as $k => $v) if (is_file($v = $d.$v)) {
		$td = ($target['pic'] ? TARGET_DESC_TIME : TARGET_DRAW_TIME);
		if ($m[4] && $target['time'] && ($td < $m[4] - $target['time'])) $td =  TARGET_LONG_TIME;
		$t = T0+$td;
		$t = "$m[1].u$u_num.t$t$m[6]";
		rename($v, $d.$t);
		data_put($u_t_f, BOM."$target[time]	$room	$t	$target[post]".NL.implode(NL, $u_task));
		return array($k, $td);
	}
	return 'task_let_go';
}

function data_aim($unknown_1st = 0, $skip_list = 0, $dont_change = 0) {
	global $u_num, $u_flag, $u_task, $u_t_f, $room, $target, $file_cache;
	$target = array();
	if ($u_flag['nop'] || !is_dir($d = DIR_ROOM.$room.'/')) return $target;

//* check personal target list
	$tt = data_check_my_task(1);
	if (POST || $dont_change) return $target;

	if (
		!$tt
	||	(
			$target['time'] + TARGET_CHANGE_TIME < T0
		||	(
				is_array($skip_list)
			&&	in_array(intval($target['thread']), $skip_list)
			)
		)
	||	!is_file($d.$tt)
	||	data_get_u_by_file(data_cache($d.$tt), -1) == $u_num
	) {

//* change target, check if not maxed or recently locked by others
		$u_t = '	'.$u_num.TXT;
		$a = array();
		$b = array();
		foreach (get_dir_contents($d) as $f) if (preg_match(TRD_PLAY, $f, $m)) {
			if ($m[3] == $u_num) $u_own[$f] = $m[1].$m[6];		//* <- own current target excluded
			else if (
				!(
					is_array($skip_list)
				&&	in_array($m[1], $skip_list)
				)
			&&	$m[2] != 'f'
			&&	intval($m[2]) < TRD_MAX_POSTS
			&&	intval($m[4]) < T0				//* <- other's target expired
			&&	data_get_u_by_file($dfc = data_cache($d.$f), -1) != $u_num
			) {
				$a[$f] = $m[1];
				if ($unknown_1st && !strpos(str_replace(IMG, TXT, $dfc), $u_t)) $b[$f] = $m[1];
			}
		}
		$c = array(
			'count_free_tasks' => count($a)
		,	'count_free_unknown' => count($b)
		);
		if (!$a || $tt) $a[''] = 0;
		if (!($k = array_rand($b ?: $a)) || !is_file($get = $d.$k)) $target = array($k = '');
		if ($k != $tt) {
			$t = '';
			if ($k) {
				$target = array('time' => T0);

//* get target text to display
				$t = rtrim(data_cache($get));
				$last_post = strrpos($t, TXT) + strlen(TXT);
				$last_pic = strrpos($t, IMG) + strlen(IMG);
				if ($last_post < $last_pic) {
					$target['pic'] = $t =	strpos($p = substr($t, $last_pic), '	');
					$target['task'] = $p =	substr($p, 0, $t);		//* <- only filename
					$t = T0+TARGET_DESC_TIME;
				} else {
					$target['task'] =	substr($t, $last_post);		//* <- only text
					$target['post'] = $p =	substr($t, strrpos($t, NL)+1);	//* <- full last line
					$t = T0+TARGET_DRAW_TIME;
				}

//* rename new target as locked
				$t = $target['thread'] = $a[$k].".u$u_num.t$t.log";
				rename($get, $d.$t);
				$file_cache[$d.$t] = $file_cache[$get];
				$t = T0."	$room	$t	$p";
			}

//* save new target to personal list
			if ($t .= ($t?NL:'').implode(NL, $u_task)) data_put($u_t_f, BOM.$t); else unlink($u_t_f);

//* rename old target as unlocked
			if (is_array($u_own)) foreach ($u_own as $f => $n) rename($d.$f, $d.$n);
		}
		$target = array_merge($target, $c);
	}
}

function data_log_post($t) {
	global $u_num, $room, $target;
	$d = DIR_ROOM.$room.'/';
	$pic = (is_array($t)?'.p1':'');

	if (
		($tt = $target['thread'])
	&&	(
			is_file($f = $d.$tt)		//* <- own target still owned
		||	(
				($tt = preg_replace(TRD_PLAY, '$1$6', $tt))
			&&	is_file($f = $d.$tt)	//* <- own target, taken and dropped by another user in meanwhile
			)
		)
	&&	preg_match(TRD_PLAY, $tt, $m)
	) {

//* update metadata in existing filename
		$p = substr_count(file_get_contents($old = $f), IMG);
		if ($pic) ++$p;
		$f = "$d$m[1].p$p.t".T0.$m[6];
		if ($old != $f) rename($old, $f);
	} else {

//* archive old full threads
		$arch = data_archive_ready_go();

//* create new thread, if not too many
		if (
			!($ptp = ($pic && !$target['pic']))
		&&	($n = data_is_thread_cap())
		) return array(
			'cap' => $n
		,	'arch' => $arch
		);

		if (($n = data_get_thread_count()+1) <= 1) data_set_u_flag($u_num, 'mod_'.$room, 1, 2);
		$f = "$d$n$pic.log";
		if ($pic) $fork = data_log(
			$f
		,	$ptp && $target['post']
			? $target['post']		//* <- late misfire: fork with request copy, if any
			: T0.'	'.$u_num.TXT.(
				$target
				? '<span title="'.htmlspecialchars($target['time'].': '.$target['task']).'">'.NOR.'</span>'
				: NOR
			)
		);
		data_put(0, $n);			//* <- save last created thread number
	}
	$p = ($pic ? IMG.implode('	', $t) : TXT.$t);
	$l = data_log($f, T0."	$u_num$p");

	if (R1 && !$arch) $arch = data_archive_ready_go();

	data_post_refresh();
	return array(
		'post' => $l
	,	'fork' => $target?$fork:0
	,	'arch' => $arch
	);
}

?>
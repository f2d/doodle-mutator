<?php
define(DIR_DATA, 'l/');				//* <- all that's not to scandir goes here
define(DIR_USER, 'u');
define(DIR_DOOM, DIR_DATA.DIR_ROOM);		//* <- room names alone, separate from any reserved filenames
define(DIR_DAUS, DIR_DATA.DIR_USER);
define(TRD_MOD, '~^((\d+)(\..+)?(\.log))(\.(s)top|\.(d)el)?$~i');
define(TRD_PLAY, '~^(\d+)(?:\.p(\d+|f))?(?:\.u(\d+))?(?:\.t(\d+))?(\..+)?(\.log)$~i');
define(BOM, pack('CCC', 239, 187, 191).NL);	//* <- UTF-8 Byte Order Mark
define(TXT, '		');
define(IMG, '	<	');

function data_dir($file_path) {
	if (($dir = strrpos($file_path, '/')) && !is_dir($dir = substr($file_path, 0, $dir))) mkdir($dir, 0755, true);
	return $file_path;
}

function data_cache($file_path) {
global	$file_cache;
	return ($file_cache[$file_path]
	?	$file_cache[$file_path]
	: (	$file_cache[$file_path] = file_get_contents($file_path)
	));
}

function data_put($file_path, $text, $r = '') {
global	$room;
	if (!$file_path || $file_path === 1) $file_path = DIR_DOOM.($r?$r:$room).'/'.($file_path?'arch':'room').'.count';
	return file_put_contents(data_dir($file_path), $text);
}

function data_log($file_path, $line, $n = BOM) {
	return file_put_contents(data_dir($file_path), (is_file($file_path)?NL:$n).$line, FILE_APPEND);
}

function data_post_refresh($r = '') {
global	$room;
	if (is_file($f = DIR_DOOM.($r?$r:$room).'/post.count')) unlink($f);
}

function data_global_announce($a = 0) {
	$room = DIR_ROOM.($rtrim = $_REQUEST['room']).'/';
	$t = '.txt';
	if ($a) return (
	(	is_file($f = DIR_DATA.$a.$t) || ($rtrim
	&& is_file($f = DIR_DATA.$room.$a.$t)
	))?1:0);
	$g = array();
	foreach (array('anno', 'stop') as $a)
	foreach (array('', $room) as $r) if (!$r || $rtrim) {
		if (is_file($f = DIR_DATA.$r.$a.$t) && trim($f = file_get_contents($f))) $g[($r?'room_':'').$a] = $f;
	}
	return $g;
}

function data_lock($path) {
global	$lock;
	if (!($u = ($path[0] == '/')) && !is_dir($d = DIR_ROOM)) return;
	if ($path) {
		if (is_array($path));	//* <- okay, lock all of given
		else //if ($u || is_dir($d.$path))
		$path = array($path);	//* <- single room|user
//		else return;		//* <- nothing to lock yet (breaks posting a pic to yet non-existing room)
	} else {
		$path = array();	//* <- lock all existing rooms (not users)
		foreach (scandir($d) as $r) if (trim($r, '.') && is_dir($d.$r)) $path[] = $r;
	}
	foreach ($path as $r) {
		$r = ($r[0] == '/'?DIR_DAUS:DIR_DOOM).$r;			//* <- "data/lock/user/num.lock" = "l/l/u/0.lock"
		if (!is_file($f = DIR_DATA.$r.'.lock')) data_put($f, '');
		if (!flock($lock[$r] = fopen($f, 'r+'), LOCK_EX)) die('Unable to lock data!');	//* <- acquire an exclusive lock
	}
	return $lock;
}
function data_un1($f) {
global	$lock;
	flock($f, LOCK_UN);		//* <- release the lock
	fclose($f);
	unset($lock[$f]);
}
function data_unlock($r = '') {
global	$lock;
	if (!is_array($lock)) return;

	if ($r) data_un1($lock[($r[0] == '/'?DIR_DAUS:DIR_DOOM).$r]);
	else foreach ($lock as $f) data_un1($f);
//	$lock = array();
}

function data_check_u($u, $reg) {
global	$u_key, $u_num, $u_flag, $usernames, $last_user, $room;
	$d = DIR_DAUS;
	if (is_file($f = "$d.log")) foreach (fln($f) as $line) if (strpos($line, '	')) {
		$a = explode('	', $line);
		if ($u == $a[1]) {
			$u_key = $a[1];
			$u_num = $a[0];
			data_lock($n = '/'.$u_num);
			if ($reg) return $u_num;
			if (is_file($f = "$d$n.flag")) foreach (fln($f) as $g) $u_flag[$g] = $g;
		}
		if (POST) $last_user = $a[0];
		if (!$reg) $usernames[$a[0]] = $a[3];
	}
	return $u_num;
}

function data_log_user($u_key, $u_name) {
global	$last_user;
	$u_num = $last_user+1;
	$d = DIR_DAUS;
	data_lock('/new');
	if (($r = data_log($f = "$d.log", "$u_num	$u_key	".T0.'+'.M0."	$u_name"))
	&& !$last_user) data_put("$d/$u_num.flag", 'god');	//* <- 1st registered = top supervisor
	return $r;
}

function data_collect($f, $uniq) {
	if (!is_file($f)
	|| false === strpos(file_get_contents($f).NL, '	'.$uniq.NL)
	) data_log($f, T0.'+'.M0.'	'.$uniq, '');
}

function data_log_ip() {
global	$u_num;
	if (LOG_IP) data_collect(DIR_DAUS."/$u_num.ip", $_SERVER['REMOTE_ADDR']);
}

function data_log_ref() {
	if (!POST
	&& ($r = $_SERVER['HTTP_REFERER'])
	&& ($r != ($s = "http://$_SERVER[SERVER_NAME]"))
	&& (0 !== strpos($r, $s.'/'))
	) data_collect(DIR_DATA.'ref.log', $r);
}

function data_log_adm($a) {			//* <- keep logs of administrative actions by date
global	$u_num, $room;
	$d = date('Y-m-d', T0);
	$u = (GOD?'g':(MOD?'m':'r'));
	return data_log(DIR_DOOM."$room/$d.log", T0.'+'.M0."	$u$u_num	$a");
}

function data_log_report($r) {			//* <- r = array(t-r-c, reason, thread, row, column)
global	$u_num, $room;
	$u_tab = '	'.$u_num.TXT;
	if (is_dir($d = DIR_ROOM.$room.'/'))
	foreach (scandir($d) as $f) if (trim($f, '.')
	&& preg_match(TRD_MOD, $f, $m) && ($m[2] == $r[2])) {
		if (is_file($f = $d.$f) && strpos(str_replace(IMG, TXT, file_get_contents($f)), $u_tab)) {	//* <- report only visible to you
			if (!$m[5]) rename($f, $f.'.stop');	//* <- freeze thread
			data_log(DIR_DOOM."$room/$m[2].report.txt", T0.'+'.M0."	$r[3]	$r[4]	$r[1]");
			if ($r = data_log_adm("$r[0]	$r[1]")) data_post_refresh();
			return $r;
		}
		break;
	}
	return 0;
}

function data_get_mod_log($t = 0, $mt = 0) {	//* <- Y-m-d|int, 1|0
global	$room;
	if ($t) {
		if (is_file($f = (
strpos($t, '-') ? DIR_DOOM."$room/$t" :
($t-3 ? DIR_DATA.'ref' : DIR_DAUS)
		).'.log')) return ($mt ? filemtime($f) : trim(file_get_contents($f), BOM));
	} else {
		$a = glob(DIR_DOOM.$room.'/*.log');
		$t = array();
		foreach ($a as $f) if (preg_match(PAT_DATE, substr($f, strrpos($f, '/')), $m)) $t[$m[1]][] = $m[4];
		return $t;
	}
}

function data_get_last_post_time($t) {return substr($t, $last_line = strrpos($t, NL)+1, strpos($t, '	', $last_line)-$last_line);}
function data_get_thread_count($r = 0) {
	if (!$r) {global $room; $r = $room;}
	return (is_file($f = DIR_DOOM.$r.'/room.count')						//* <- seems ~2x faster than scandir()
	? file_get_contents($f)
	: count(array_diff(scandir(DIR_ROOM.$r), array('.', '..', trim(DIR_THUMB, '/'))))	//* <- seems ~2x faster than glob()
	);
}
function data_is_thread_cap($r = 0) {
	if (!$r) {global $room; $r = $room;}
	if (is_dir($d = DIR_ROOM.$r)) foreach (scandir($d) as $f) if (trim($f, '.')
		&& preg_match(TRD_MOD, $f, $m) && !$m[7]			//* <- "burnt" not counted
		&& (++$n >= TRD_MAX_COUNT)) return $n;
	return 0;
}

function data_get_thread_by_num($n) {
global	$room, $d, $trd;
	if (($p = $trd[$n]) && is_file($p[0].$p[1])) return $p;
	if ($d || is_dir($d = DIR_ROOM.$room.'/'))
	foreach (scandir($d) as $f) if (trim($f, '.')
	&& preg_match(TRD_MOD, $f, $m) && ($n == $m[2]))
	return ($trd[$n] = array($d,$f,$m));	//* <- dir/path/, filename, [num,etc,ext,.stop]
	return 0;
}

function data_get_u_by_file($f, $line = 0, $get_pic = 0) {
	if (!$line) $f = file_get_contents($f); else
	if ($line > 0) {
		if ($line >= count($f = fln($f))) return 0;
		$f = $f[$line];
	}
	if ($get_pic) {
		if (false === ($start = strrpos($f, IMG))) return 0;
		$start = substr($f, $start+3);
		return substr($start, 0, strpos($start, '	'));
	}
	$l = strrpos($t = str_replace(IMG, TXT, $f), TXT);
	$t = substr($t, 0, $l);
	return substr($t, strrpos($t, '	')+1);
}

function data_get_u_by_post($a) {
global	$d, $u;
	if ($p = $u[$tp = $a[0].'-'.$a[1]]) return $p;
	if (list($d,$f) = data_get_thread_by_num($a[0]))
	return ($u[$tp] = data_get_u_by_file($d.$f, $a[1]));
	return 0;
}

function data_set_u_flag($u, $flag, $on = -1, $harakiri = 0) {
global	$u_num, $u_flag;
	if (is_array($u)) $u = data_get_u_by_post($u);

	if ($on < 0) {
		if ($u && data_lock('/new') && ($ul = fln($f = DIR_DAUS.'.log'))) foreach ($ul as $k => $line) if (intval($line) == $u) {
			$ul[$k] = substr($line, 0, $s = strrpos($line, '	')+1).$flag;
			data_put($f, implode(NL, $ul));
			return $u.': '.substr($line, $s).' -> '.$flag;
		}
		return 'no user with ID '.$u;
	}

	if (!$u || (!(GOD || $harakiri) && ($u == $u_num))) return 0;		//* <- mods cannot ban self
	$n = '/'.$u;
	if (is_file($f = DIR_DAUS.$n.'.flag')) {
		data_lock($n);
		$flags = array();
		foreach (fln($f) as $k) $flags[$k] = $k;
		if (!GOD && ($flags['god'] || $flags['mod'])) return -$u;	//* <- mods cannot ban mods

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
	else return 0;
	return $u;
}

function data_check_user_info($u) {
	$n = DIR_DAUS.($u = "/$u");
	if (data_lock($u))
	foreach (array('flag' => 'Flags','ip' => 'IPs') as $k => $v)
	if (is_file($f = "$n.$k")) $r .= NL."$v: ".NL.file_get_contents($f).NL;
	return $r;
}

function data_get_full_threads() {
global	$room, $usernames;
	if (!$usernames) die('no usernames');			//* <- bug checking
	$threads = array();
	if (is_dir($d = DIR_ROOM.$room.'/'))
	foreach (scandir($d) as $f) if (trim($f, '.')
	&& preg_match(TRD_PLAY, $f, $n)
	&& $n[2] && ($n[2] == 'f' || $n[2] >= TRD_MAX_POSTS)
	&& (R1 || !$n[4] || ($n[4] + TRD_ARCH_TIME < T0))
	&& is_file($f = $d.$f)) {
		$a = array($f,'','');
		foreach (fln($f) as $line) if (strpos($line, '	')) {
			$tab = explode('	', $line);
			$tab[0] = date(TIMESTAMP, $last_time = $tab[0]);
			$tab[1] = $usernames[$tab[1]];
			if ($tab[2]) {
				$p = pic_subpath($tab[3]);
				if (!$a[2]) $a[2] = $p;		//* <- thumb
				$p = ROOTPRFX.(PIC_SUB ? $p : DIR_PICS.$tab[3]);
				if ($b = (strpos($p, ';') ? explode(';', $p, 2) : '')) $p = pic_resized_path($b[0]);
				$p = '<img src="'.$p.'">';
				$tab[3] = ($b ? '<a href="'.$b[0].'">'.$p.'</a>;'.$b[1] : $p);
			}
			unset($tab[2], $tab[5]);
			$a[1] .= NL.implode('	', $tab);	//* <- content
		}
		if (R1 || $n[2] == 'f' || ($last_time + TRD_ARCH_TIME < T0)) $threads[$last_time.$f] = $a;
	}
	if (ksort($threads)) return $threads;
}

function data_archive_ready_go() {
	if ($threads = data_get_full_threads()) {
		require_once(NAMEPRFX.'.arch.php');
		return data_archive_full_threads($threads);
	}
}

function data_del_pic($f) {
	if (is_file($r = pic_resized_path($f))) unlink($r);
	return unlink($f);
}

function data_del_thread($t, $n, $pics = 0) {
	if ($pics && preg_match_all('~(<img src="[^>]*/|'.IMG.')([^/">	]+)[	"]~is', file_get_contents($t), $m)) {
		foreach ($m[2] as $p) if (($f = pic_subpath($p)) && is_file($f) && data_del_pic($f)) ++$c;
	}
	$t = (unlink($t) && (!is_file($r = DIR_DOOM."$room/$n.report.txt") || unlink($r)));
	return ($t && $c?$c:$t);
}

function data_log_mod($a) {			//* <- array(option name, thread, row, column, option num)
global	$u_num, $u_flag, $room, $merge;
	if (!MOD) return 0;
	$ok = 0;
	$q = explode('+', array_shift($a));
	$o = array_shift($q);
	$un = count($q);
	$msg = ($_POST[$msg = "t_$a[0]_$a[1]_$a[2]"]?stripslashes($_POST[$msg]):'');
	if ($a[2] > 1) $a = $a[0];

//* ----	left	----
	if ($o == 'archive') {
		if ((list($d,$f,$m) = data_get_thread_by_num($a[0]))) {
			if ($un > 1) {
				$p = substr_count($t = file_get_contents($d.$f), IMG);
				$t = data_get_last_post_time($t);
				if (rename($d.$f, "$d$m[2].p$p.t$t$m[4]")) $ok = OK;	//* <- wait
			} else {
				if (rename($d.$f, "$d$m[2].pf$m[4]")) $ok = OK;		//* <- ready
				if ($ok && !$un && ($r = data_archive_ready_go())) $ok .= ", $r[0] done, $r[1] gone";
			}
			if ($ok) data_post_refresh();
		}
	} else
	if ($o == 'freeze tr.') {
		if ((list($d,$f,$m) = data_get_thread_by_num($a[0]))	//* <- still so much redundancy
		&& ($f != ($n = $m[1].($un > 1?'.del':($un?'':'.stop'))))
		&& rename($d.$f, $d.$n)
		) {
			$ok = OK;
			if (($un == 1) && is_file($r = DIR_DOOM."$room/$m[2].report.txt")) unlink($r);
			data_post_refresh();
		}
	} else
	if ($o == 'delete thread') {
		if ((list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&& (GOD
			? data_del_thread($d.$f, $m[2], $un)
			: rename($d.$f, $d.$m[1].'.del')
		)) {$ok = OK; data_post_refresh();}
	} else
	if ($o == 'delete post') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			$ok = $a[1];
			$old = fln($f = $d.$f);
			if (count($old) > $a[1]) {
				$ok .= '='.$old[$a[1]];			//* <- save post contents in log, just in case
				unset($old[$a[1]]);
				if (count($old) > 1) {
					data_put($f, $new = implode(NL, $old));
					if (strpos($m[3], 'p')) {$p = substr_count($new, IMG); rename($f, "$d$m[2].p$p$m[4]$m[5]");}
				} else {
					data_del_thread($f, $m[2]);
					$ok .= '	> void';
				}
				data_post_refresh();
			} else $ok = -$ok;
		}
	} else
	if ($o == 'delete pic') {
		if ((list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&& ($fn = data_get_u_by_file($d.$f, $a[1], 1))
		&& (is_file($f = pic_subpath($fn)))
		&& (GOD || ((is_dir($n = DIR_PICS.'del/') || mkdir($n, 0755)) && rename($f, $n.$fn)))
		&& ($un
			? (file_put_contents($f, '') === 0)		//* <- 0-truncate
			: (GOD ? data_del_pic($f) : 1)
		)) $ok = $a[1].'='.$f;
	} else
	if ($o == 'merge thread target') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			if ($un) {
				$ok = $a[1];
				$old = count($f = fln($d.$f));		//* <- source to add
				if ($old-- > $ok) {
					$merge[$m[2]] = implode(NL, array_slice($f, $ok));
					$ok = "+t$m[2] p$ok-$old";
				} else $ok .= ' post not found';
			} else if (is_array($merge) && count($merge)) {
				$n = array_unique(explode(NL, ($old = file_get_contents($f = $d.$f)).NL.implode(NL, $merge)));
				sort($n);
				if ($old != ($new = BOM.trim(implode(NL, $n), BOM))) {
					file_put_contents($f, $new);
					$ok = $m[2].'<-'.implode(',', array_keys($merge));
				} else $ok = 'no change';
				data_post_refresh();
			}
		}
	} else
	if ($o == 'split thread from here') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			$ok = $a[1];
			$old = fln($f = $d.$f);
			if (count($old) > $ok) {
				$p = substr_count($fst = implode(NL, array_slice($old, 0, $ok)), IMG);
				$q = substr_count($lst = BOM.implode(NL, array_slice($old, $ok)), IMG);
				data_put(0, ($ok = data_get_thread_count())+1);
				data_put("$d$ok.p$p.log$m[5]", $fst);		//* <- put 1st half into new thread, un/frozen like old
				data_put($f, $lst);				//* <- put 2nd half into old thread, to keep people's target
				if (preg_match(TRD_PLAY, $m[1], $n)
				&& $n[2] && ($n[2] == 'f' || $n[2] >= TRD_MAX_POSTS)) {
					rename($f, "$d$n[1].p$q$n[6]$m[5]");	//* <- rename full thread to drop pics count
				}
				data_post_refresh();
			} else $ok .= ' post not found';
		}
	} else

//* ----	right	----
	if ($o == 'harakiri'	) $ok = data_set_u_flag($u_num, 'mod_'.$room, 0, 1); else
	if ($o == 'ban'		) $ok = data_set_u_flag($a, 'ban', !$un); else
	if ($o == 'can report'	) $ok = data_set_u_flag($a, 'nor', $un); else
	if (!GOD) return 0; else

//* ----	god right	----
	if ($o == 'gets targets') $ok = data_set_u_flag($a, 'nop', $un); else
	if ($o == 'sees unknown') $ok = data_set_u_flag($a, 'see', !$un); else
	if ($o == 'give mod'	) $ok = data_set_u_flag($a, 'mod_'.$room, !$un); else
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
			? ((data_put($f, $msg) === strlen($msg))?($msg?$msg:'-'):0)
			: (is_file($f) && unlink($f))
		);
	} else

//* ----	god left	----
	if ($o == 'rename room') {
		if (!($msg = trim_room($msg))) return 0;
		if (!is_dir(DIR_ROOM.$room)) $ok = $room.' does not exist';
		else {
			if ($un) {
				if (!is_dir(DIR_ROOM.$msg)) $ok = $msg.' does not exist';
				else
				if ((list($d,$f,$m) = data_get_thread_by_num($a[0]))
				&& data_lock($msg)
				&& (($n = data_get_thread_count($msg)) >= 0)
				&& copy($d.$f, DIR_ROOM."$msg/$n$m[3]$m[4]$m[5]")) {
					$ok = $msg;
					data_put(0, $n+1, $msg);
					data_post_refresh($msg);
				}
			} else
			if (is_dir(DIR_ROOM.$msg)) $ok = $msg.' already exists';
			else {
				data_post_refresh();
				$ok = $room.' -> '.$msg;
				foreach (array('arch', 'room', 'doom') as $f) $ok .=
','.$f.':'.(is_dir($rr = ($r = constant('DIR_'.strtoupper($f))).$room) && rename($rr, $r.$msg));
				$room = $msg;
			}
		}
	} else
	if ($o == 'nuke room') {
		$r = DIR_DOOM.$room;
		$cf = array('room', 'post');
		if ($da = ($un > 1)) $cf[] = 'arch';
		foreach ($cf as $f) if (($f .= '.count') && is_file($n = "$r/$f")) $ok .= ",$f:".unlink($n);
		if ($r = glob($r.'/*.report.txt', GLOB_NOSORT)) foreach ($r as $f) unlink($f);

		function delTree($r) {
			foreach (scandir($r) as $f) if (trim($f, '.')) is_dir($f = "$r/$f") ? delTree($f) : unlink($f);
			return rmdir($r);
		}

		$tc = $ac = $pc = 0;
		if (is_dir($r = DIR_ROOM.$room.'/')) {
			foreach (scandir($r) as $f) if (trim($f, '.'))
			if ($un ? ($pc += data_del_thread($r.$f, 0, 1)) : unlink($r.$f)) $tc++;
			rmdir($r);
		}
		if ($da && is_dir($r = DIR_ARCH.$room.'/')) {
			foreach (scandir($r) as $f) if (is_file($f = $r.$f)) {
				if ($pc += data_del_thread($f, 0, 1)) $ac++;	//* <- with pics!!
			}
			delTree($r);
		}
		foreach (array('thrd', 'arch', 'pics') as $a) if ($c = ${"$a[0]c"}) $ok .= ",$a:$c";
	} else
	if ($o == 'insert post') {
		if (trim($msg) && list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			$ok = $a[1];
			$l = fln($f = $d.$f);
			if (count($l) > ($n = $a[1])) {
				$ok .= '='.$l[$n];
				$tc = substr_count($msg = preg_replace('~[ \v]+~', ' ', $msg), '	');
				if ($un > 1) {	//* <- replace
					$l[$n] = ($tc > 1?'':substr($prfx = str_replace(IMG, TXT, $l[$n]), 0, strrpos($prfx, TXT)).($tc?IMG:TXT));
				} else		//* <- insert
				if (!$un) $l[$n] .= NL.($tc > 1?'':T0.'	'.$u_num.($tc?IMG:TXT));
				$l[$n] .= $msg;	//* <- add

				if (data_put($f, implode(NL, $l))) {
					$ok .= '	=>	'.$msg;
					data_post_refresh();
				}
			} else $ok = -$ok;
		}
	} else
	return 0;
	if ($un) $o .= '+'.end($q);
	if (is_array($a)) $a = implode('-', $a);
	return data_log_adm("$a	$o: $ok");
}

function data_get_visible_rooms() {
	if (!is_dir($dr = DIR_ROOM)) return 0;
	require_once(NAMEPRFX.'.arch.php');
global	$u_flag;
	$a = array(0);
	$sd = array_diff(scandir($dr), array('.', '..'));
if (TIME_PARTS) time_check_point('done scan'.NL);
	foreach ($sd as $r) if ((
		($u_mod = (GOD || $u_flag['mod_'.$r])) || !ROOM_HIDE || ROOM_HIDE != $r[0]
	) && is_dir($d = "$dr$r/")) {
		$lmt = 0;			//* <- last mod time in room
		if (is_file($cf = DIR_DOOM.$r.'/post.count') && (list($lmt, $c, $mod) = fln($cf))) {
			$c = explode(',', $c);
			if ($u_mod && $mod) {
				$mod = explode(',', $mod);
				if (!GOD) $mod[3] = '';
				$c[] = $mod;
			}
		} else {
			$lmt = T0;		//* <- force to now, less problems
			$count_thrd =
			$count_desc =
			$count_pics = $lpt = 0;
			$mod = array('rep' => '', 'frz' => '', 'del' => '', 'full' => '');
			data_lock($r);
			foreach (scandir($d) as $fn) if (trim($fn, '.') && is_file($f = $d.$fn)) {
				$mt = data_get_last_post_time($f = file_get_contents($f));
				if ($lpt < $mt) $lpt = $mt;
				$count_thrd ++;
				$count_desc += substr_count($f, TXT);
				$count_pics += substr_count($f, IMG);
				if (preg_match(TRD_PLAY, $fn, $m)) {
					if ($m[2] && ($m[2] == 'f' || $m[2] >= TRD_MAX_POSTS)) ++$mod['full'];
				} else
				if (preg_match(TRD_MOD, $fn, $m)) {
					if ($m[6]) ++$mod['frz'];
					if ($m[7]) ++$mod['del'];
				}
			}
			if ($d = glob(DIR_DOOM.$r.'/*.report.txt', GLOB_NOSORT)) $mod['rep'] = count($d);
			$c = array($count_thrd	//* <- active
, data_get_thread_count($r)			//* <- ever
, data_get_archive_count($r)			//* <- archived
, data_get_archive_mtime($r), $count_pics, $count_desc, $lpt);
			file_put_contents($cf, $lmt.NL.implode(',', $c).(implode('', $mod)?NL.implode(',', $mod):($mod = '')));
			data_unlock();
			if ($u_mod && $mod) {
				if (!GOD) $mod['del'] = '';
				$c[] = $mod;
			}
		}
		foreach (array('anno', 'stop') as $t) if (is_file($f = DIR_DOOM."$r/$t.txt")) {
			if ($t == 'stop') $c[8][0] = '';
			$c[8]['room_'.$t] = file_get_contents($f);
		}
		$a[$r] = $c;
		if ($a[0] < $lmt) $a[0] = $lmt;
if (TIME_PARTS) time_check_point('done room '.$r);
	}
	return ($a[0]?$a:0);
}

function data_get_visible_threads() {
global	$u_num, $u_flag, $room;
	if (!is_dir($d = DIR_ROOM.$room.'/')) return 0;
	$u_tab = '	'.$u_num.TXT;
	$u_chars = array('ban', 'god', 'mod_'.$room, 'nor');
	$threads = array();
	$reports = array();
	$sd = array_diff(scandir($d), array('.', '..'));
if (TIME_PARTS) time_check_point('done scan'.NL);
	foreach ($sd as $fn) if (is_file($f = $d.$fn) && ($f = data_cache($f))) {
		$pn = preg_match(TRD_MOD, $fn, $n);
		$pp = preg_match(TRD_PLAY, $fn, $p);
		if (GOD
		|| (MOD && ($pn && $n[6]))			//* <- only own or "frozen" for mods
		|| ($u_flag['see'] && !($pn && $n[5]))		//* <- any active for seers
		|| ($pp && strpos(str_replace(IMG, TXT, $f), $u_tab))
		) {
			$posts = array(0);
			foreach (explode(NL, $f) as $line) if (strpos($line = trim($line), '	')) {
				$tab = explode('	', $line);

				if (!($f = (($u = $tab[1]) == $u_num?'u':0)) && MOD) {	//* <- mods see other's status as color
					if (!isset($u_cache[$u])) {
						$u_cache[$u] = 0;
						if (is_file($f = DIR_DAUS."/$u.flag") && ($f = fln($f))) {
							foreach ($u_chars as $c) if (in_array($c, $f)) {$u_cache[$u] = $c[0]; break;}
						}
					}
					$f = $u_cache[$u];
				}
				array_unshift($tab, $f);
				$posts[] = $tab;
			}
			unset($posts[0]);
			($f = $n[6].$n[7]) || ($f = ($p[2] && ($p[2] == 'f' || $p[2] >= TRD_MAX_POSTS)?'f':''));
			$threads[$f .= $n[2]] = $posts;

			if (MOD && is_file($r = DIR_DOOM.$room."/$n[2].report.txt")) {
				$repl = array();
				foreach (fln($r) as $line) if (count($tab = explode('	', $line, 4)) > 3) $repl[$tab[1]][$tab[2]][$tab[0]] = $tab[3];
				$reports[$f] = $repl;
			}
		}
if (TIME_PARTS) time_check_point('done trd '.$fn);
	}
	return array($threads, $reports);
}

function data_check_my_task($status = 0) {
global	$u_num, $u_flag, $u_task, $u_t_f, $room, $target;
	if ($u_flag['nop']) return '';
	$u_task = (is_file($u_t_f = DIR_DAUS."/$u_num.task") ? fln($u_t_f) : array());
	foreach ($u_task as $k => $line) if (strpos($line, '	')) {
		$a = explode('	', $line, 4);
		if ($a[1] == $room) {
			$target['time'] = $a[0];
			$target['thread'] = $tt = $a[2];
			if ((	$target['post'] = $a[3]
			) == (	$target['task'] = substr($line, strrpos($line, '	')+1)
			))	$target['pic'] = 1;
			unset($u_task[$k]);
			break;
		}
	} else unset($u_task[$k]);
	if ($status) {
		if (!is_dir($d = DIR_ROOM.$room.'/')) return -1;	//* <- room deleted or renamed
		if (!$tt || !preg_match(TRD_PLAY, $tt, $m)) return 0;	//* <- empty target
		$a = array(
			1 => $tt					//* <- own target still owned
		,	2 => $m[1].$m[6]				//* <- taken and dropped by others
		);
		foreach ($a as $k => $v) if (is_file($v = $d.$v)) {	//* <- retake for new interval
			$t = T0+($target['pic'] ? TARGET_DESC_TIME : TARGET_DRAW_TIME);
			$t = "$m[1].u$u_num.t$t$m[6]";
			rename($v, $d.$t);
			data_put($u_t_f, BOM."$target[time]	$room	$t	$target[post]".NL.implode(NL, $u_task));
			return $k;
		}
		return -2;						//* <- still taken or fulfilled
	}
	return $tt;
}

function data_aim($unknown_1st = 0, $skip_list = 0) {
global	$u_num, $u_flag, $u_task, $u_t_f, $room, $target, $file_cache;
	$target = array();
	if ($u_flag['nop'] || !is_dir($d = DIR_ROOM.$room.'/')) return;

//* check personal target list
	$tt = data_check_my_task();
	if (POST || GET_Q) return;

	if (!$tt
	|| ($target['time'] + TARGET_CHANGE_TIME < T0 || (is_array($skip_list) && in_array(intval($target['thread']), $skip_list)))
	|| !is_file($d.$tt)
	|| (data_get_u_by_file(data_cache($d.$tt), -1) == $u_num)) {

//* change target, check if not maxed or recently locked by others
		$u_t = '	'.$u_num.TXT;
		$a = array();
		$b = array();
		foreach (scandir($d) as $f) if (trim($f, '.') && preg_match(TRD_PLAY, $f, $m)) {
			if ($m[3] == $u_num) $u_own[$f] = $m[1].$m[6];	//* <- own current target excluded
			else if (!(is_array($skip_list) && in_array($m[1], $skip_list)) && ($m[2] != 'f')
			&& (intval($m[2]) < TRD_MAX_POSTS)
			&& (intval($m[4]) < T0)				//* <- other's target expired
			&& (data_get_u_by_file($dfc = data_cache($d.$f), -1) != $u_num)
			) {
				$a[$f] = $m[1];
				if ($unknown_1st && !strpos(str_replace(IMG, TXT, $dfc), $u_t)) $b[$f] = $m[1];
			}
		}
		if (!$a || $tt) $a[''] = 0;
		if (!($k = array_rand($b?$b:$a)) || !is_file($get = $d.$k)) $target = array($k = '');
		if ($k != $tt) {
			$t = '';
			if ($k) {
				$target = array('time' => T0);

//* get target text to display
				$t = data_cache($get);
				$last_post = strrpos($t, TXT);
				$last_pic = strrpos($t, IMG);
				if ($last_post < $last_pic) {
					$target['pic'] =	strpos($p = substr($t, $last_pic+3), '	');
					$target['task'] = $p =	substr($p, 0, $target['pic']);
					$t = T0+TARGET_DESC_TIME;
				} else {
					$target['task'] =	substr($t, $last_post+2);
					$target['post'] = $p =	substr($t, strrpos($t, NL)+1);
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
	}
}

function data_log_post($t) {
global	$u_num, $room, $target;
	$d = DIR_ROOM.$room.'/';
	$pic = (is_array($t)?'.p1':'');

	if (($tt = $target['thread'])
	&& (is_file($f = $d.$tt)				//* <- own target still owned
	|| (($tt = preg_replace(TRD_PLAY, '$1$6', $tt))
	&&  is_file($f = $d.$tt)				//* <- own target, taken and dropped by another user in meanwhile
	)) && preg_match(TRD_PLAY, $tt, $m)) {

//* update metadata in existing filename
		$p = substr_count(file_get_contents($old = $f), IMG);
		if ($pic) ++$p;
		$f = "$d$m[1].p$p.t".T0.$m[6];
		if ($old != $f) rename($old, $f);
	} else {

//* archive old full threads
		data_archive_ready_go();

//* create new thread, if not too many
		if (!($ptp = ($pic && !$target['pic'])) && ($n = data_is_thread_cap())) return -$n;

		$n = data_get_thread_count();
		$f = "$d$n$pic.log";
		if ($pic) $fork = data_log($f, $ptp && $target['post']
			? $target['post']			//* <- late misfire: fork with request copy, if any
			: T0.'	'.$u_num.TXT.($target?'<span title="'.htmlspecialchars($target['time'].', '.$target['task']).'">&mdash;</span>':'&mdash;')
		);
		data_put(0, $n+1);				//* <- increment last created thread number
	}
	$p = ($pic ? IMG.implode('	', $t) : TXT.$t);
	$l = data_log($f, T0."	$u_num$p");
	if (R1) data_archive_ready_go();
	data_post_refresh();
	return (($target && $fork)?-$l:$l);
}
?>
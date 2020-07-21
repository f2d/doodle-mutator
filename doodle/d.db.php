<?php

//* Constants only for internal use: ------------------------------------------

define('DATA_VERSION', '2018-01-15 18:44');	//* <- change this to autoupdate old data formats

define('DATA_FUNC_EXPORT', 'json_encode');
define('DATA_FUNC_IMPORT', 'json_decode');

define('DATA_COUNT_EXT', '.count');
define('DATA_LOCK_EXT', '.lock');
define('DATA_LOG_EXT', '.log');
define('DATA_STATIC_EXT', '.txt');
define('DATA_HIDDEN_EXT', '_hidden');

define('DATA_SUB_ACT', 'actions/');
define('DATA_SUB_REP', 'reports/');
define('DATA_SUB_TRD', 'threads/');

define('DATA_DIR', 'data/');			//* <- all data not viewable directly by URL
define('DATA_DIR_LOCK', DATA_DIR.'lock_files/');	//* <- lock files, empty and disposable
define('DATA_DIR_ROOM', DATA_DIR.'rooms/');	//* <- keep separated, like rooms/meta/subtype/room_name
define('DATA_DIR_USER', DATA_DIR.'users/');	//* <- per user files
define('DATA_USERLIST', DATA_DIR.'users'.DATA_LOG_EXT);		//* <- user list filename
define('DATA_REF_LIST', DATA_DIR.'reflinks'.DATA_LOG_EXT);	//* <- reflinks list filename

define('DATA_U_ABOUT', 'about');
define('DATA_U_FLAG', 'flag');
define('DATA_U_IP', 'ip');
define('DATA_U_TASK', 'task');
define('DATA_U_TASK_CHANGE', 'change');

define('DATA_LOG_START', BOM.NL);
define('DATA_MARK_TXT', '		');
define('DATA_MARK_IMG', '	<	');

define('DATA_RE_LE', mb_escape_regex(DATA_LOG_EXT));
define('DATA_RE_MI', mb_escape_regex(DATA_MARK_IMG, '~', '\\s'));

define('DATA_PAT_IMG', '~
	(?P<before>
		(?:<a\\s+href|<img\\s+src)="?
	|	'.DATA_RE_MI.'
	)
	(?P<path>[^">\\t]+)
	(?=[">\\t]|$)
~iux');

define('DATA_PAT_TRD_PLAY', '~^
	(?P<id>\d+)
	(?P<etc>
		(?:\.
			(?:
				p(?P<pics>\d+|f)
			|	l(?P<last_t>\d+)
			|	a(?P<last_u>\d+)
			|	t(?P<hold_t>\d+)
			|	u(?P<hold_u>\d+)
			|	.+
			)
		)+
	)?
	(?P<ext>'.DATA_RE_LE.')
$~iux');

define('DATA_PAT_TRD_MOD', '~^
	(?P<active>
		(?P<id>\d+)
		(?P<etc>\..+)?
		(?P<ext>'.DATA_RE_LE.')
	)
	(?P<inactive>
		(?:\.
			(?:
				(?P<stopped>s)top
			|	(?P<deleted>d)el
			)
		)+
	)?
$~iux');

//* Function argument flags: --------------------------------------------------

define('DATA_FLAG_POST_TXT', 1);
define('DATA_FLAG_POST_IMG', 2);
define('DATA_FLAG_POST_ANY', 4);

//* Be careful with this, caching is meant only for viewing: *-----------------

function data_cache($file_path) {
	global $data_cache_file;
	return $data_cache_file[$file_path] ?: (
		$data_cache_file[$file_path] = file_get_contents($file_path)
	);
}

function data_cache_file_rename($from, $to) {
	global $data_cache_file;
	if (rename($from, $to)) $data_cache_file[$to] = $data_cache_file[$from];
}

//* ---------------------------------------------------------------------------

function data_post_refresh($r = '') {
	global $room;
	$d = DATA_DIR_ROOM;
	if ($r === true) {
		foreach (get_dir_rooms($d) as $r) if ($r = data_post_refresh($r)) {
			$report .= ($report?NL:'').$r;
		}
	} else
	if ($r || ($r = $room)) {
		data_lock(LK_ROOM.$r);
		if (is_file($f = "$d$r/".COUNT_POST.DATA_COUNT_EXT)) {
			$r = (unlink($f)?'deleted':'cannot delete');
			return "$r $f";
		}
	}
	return $report;
}

function data_log($file_path, $line, $n = DATA_LOG_START, $report_errors = true) {
	if ($old = is_file($file_path)) $old_size = filesize($file_path);
	$line = ($old_size?NL:$n).$line;
	$written = file_put_contents(mkdir_if_none($file_path), $line, FILE_APPEND);
	if (!$written) {
		if ($old) {
			$log = "Cannot write to existing $file_path (possibly wrong file permissions)";
			if (
				rename($file_path, $old = $file_path.'.old'.T0.'.bak')
			&&	($written = file_put_contents($file_path, ($old_size?file_get_contents($old):'').$line))
			) {
				$del = (unlink($old)?'deleted':'cannot delete');
				$log .= NL."Renamed to $old ($old_size bytes, $del), made new file ($written bytes)";
			}
		} else $log = "Cannot create $file_path (possibly wrong dir permissions, or not enough space)";
	}
	if ($log && $report_errors) data_log_action($log);
	return $written;
}

function data_global_announce($type = 'all', $room_in_list = '') {
	global $u_key, $last_user, $room, $tmp_announce, $data_maintenance;
	if ($d = ($room_in_list ?: $room ?: '')) $d = DATA_DIR_ROOM."$d/";
//* usage 1: check top level flag standing:
	if (array_key_exists($type, $tmp_announce)) {
		$f = $type.DATA_STATIC_EXT;
		if (is_file(DATA_DIR.$f)) return -1;	//* <- global freeze
		if ($d && is_file($d.$f)) return 1;	//* <- room freeze
		return 0;
	}
//* usage 2: get all contents, or last mod.date:
	switch ($type) {
		case 'all': $a = array(); break;
		case 'last': $a = 0; break;
		default: return false;
	}
	$sep = '_';
	foreach ($tmp_announce as $k => $v) {
		if (0 === strpos($k, 'new')) {
			if (is_array($a) && (
				('new_game' === $k && !$last_user && !is_file(DATA_USERLIST))
			||	('new_data' === $k && !$room_in_list && $data_maintenance)
			||	('new_room' === $k && $u_key && $d && !is_dir($d))
			)) $a[$k] = '';
			continue;
		}
		if ($i = mb_strpos_after($k, $sep)) {
			if (!$d) continue;
			$f = $d.mb_substr($k, $i);
		} else {
			if ($room_in_list) continue;
			$f = DATA_DIR.$k;
		}
		if (is_file($f .= DATA_STATIC_EXT)) {
			if (is_array($a)) {
				if ($v = trim_bom(file_get_contents($f))) $a[$k] = $v;
			} else {
				if (($v = filemtime($f)) && $a < $v) $a = $v;
			}
		}
	}
	return $a;
}

function data_lock($k, $ex = true) {
	global $lock;
	$d = DATA_DIR_LOCK;
	$e = DATA_LOCK_EXT;
	$i = 0;
	$l = (
		$ex
		? LOCK_EX		//* <- exclusive, to read/write, waits for EX and SH
		: LOCK_SH		//* <- shared, read-only, waits only for EX release
	);
	if ($k === LK_ROOM) $k = get_dir_rooms(DATA_DIR_ROOM, $k);
	$keys = (array)$k;
	foreach ($keys as $k) if ($k) {
		if (!is_array($lock)) $lock = array();
		if (isset($lock[$k]) && ($v = $lock[$k])) {
			if (flock($v, $l)) ++$i;
			else data_log_action("Unable to change lock $path to ex=$ex type!");
		} else
		if (
			($v = fopen(mkdir_if_none("$d$k$e"), 'a'))
		&&	flock($v, $l)	//* <- acquire the lock
		) {
			$lock[$k] = $v;
			++$i;
		} else {
			$n = data_log_action($m = "Unable to lock $path to ex=$ex type!");
			die("$m $n");
		}
	}
	return $i;
}

function data_unlock($k = '') {
	global $lock;
	if (!is_array($lock)) return;

	if ($k === LK_ROOM) $k = get_dir_rooms(DATA_DIR_ROOM, $k);
	$keys = (array)($k ?: array_keys($lock));
	foreach ($keys as $k) if ($v = $lock[$k]) {
		flock($v, LOCK_UN);	//* <- release the lock
		fclose($v);
		unset($lock[$k]);
	}
}

//* ---------------------------------------------------------------------------

function data_fix($what = '') {
	global $cfg_game_type_dir, $tmp_announce;
	if (!$what) {
		$n = DATA_VERSION;
		$f = DATA_DIR.'version'.DATA_STATIC_EXT;

		data_lock($lk = LK_VERSION, false);
		if (!is_file($f) || file_get_contents($f) !== $n) {
			$v = 'none';
			ignore_user_abort(true);

			data_lock($lk);
			if (!is_file($f) || ($v = file_get_contents($f)) !== $n) {
				time_check_point($v = "version check, $f: $v -> $n");
				$report = data_fix(true) ?: 'no change';
				data_log_action($a = 'automatic data fix', $v.NL.$report);
				file_put_mkdir($f, $n);
				time_check_point("done $a");
			}
			data_lock($lk, false);
		}
		return;
	}
	if ($what === true) $what = '';
	$e = DATA_LOG_EXT;
	$old = 'l';

	data_lock(LK_MOD_ACT);
	if (($d = LK_USERLIST) === ($t = $what ?: $d)) {
		delay_timeout();

		data_lock($t);
		if (is_dir($old)) {
			$data_types = array(DATA_U_ABOUT, DATA_U_FLAG, DATA_U_IP, DATA_U_TASK);
			$d = "$old/u";
			$i = 0;
			if ($files = glob("$d/*$e", GLOB_NOSORT) ?: glob("$d?*$e", GLOB_NOSORT)) {
				natcasesort($files);	//* <- to keep file write dates in ascending order alongside IDs
				foreach ($files as $f) if (
					strlen($n = trim(get_file_name($f), "u$e"))
				&&	intval($n) == $n
				&&	($tasks = get_file_lines($f))
				) {
					$i++;
					$flags = array();
					$ips = array();
					foreach (($csv = explode(',', trim_bom(array_shift($tasks)))) as $flag) {
						if (rtrim($flag, '1234567890.')) $flags[] = $flag;
						else $ips[] = ($ips?'':'old').'	'.$flag;
					}
					$done .= NL."$i	$f";
					foreach ($data_types as $x) {
						$s = $x.'s';
						if ($j = trim(implode(NL, $$s))) {
							$done .= "	$s = ".count($$s);
							file_put_contents("$d/$n.$x", $j);
						}
					}
				}
			}
			if ($i) {
				$done .= NL.($i = "done $i $t -> split lists");
				time_check_point($i);
			}
			foreach ($data_types as $t) {
				$i = 0;
				if ($files = glob("$d/*.$t", GLOB_NOSORT)) {
					natcasesort($files);
					foreach ($files as $f) if (is_file($f)) {
						$i++;
						$n = get_file_name($f);
						$n = mkdir_if_none("$d/$t/$n");
						if (is_file($n)) {	//* <- e.g. new IP log was created
							$x = DATA_LOG_START.trim_bom(file_get_contents($f)).NL.trim_bom(file_get_contents($n));
							if ($r = file_put_contents($n, $x)) {
								$del = (unlink($f)?'':' not');
								$r = "combined $r bytes, old was$del deleted";
							} else $r = 'failed to rewrite existing';
						} else {
							$r = (rename($f, $n)?'OK':'failed to move');
						}
						$done .= NL."$f -> $n = $r";
					}
				}
				if ($i) {
					$done .= NL.($i = "done $i $t -> /$t/");
					time_check_point($i);
				}
			}
		}
		data_unlock($t);

	}
	if (($d = 'logs') === ($t = $what ?: $d)) {
		foreach (get_dir_contents($d = 'l/room/') as $room) {
			delay_timeout();

			data_lock($lk = LK_ROOM.$room);
			foreach (array(
				'reports' => "$d$room/*.report.*"
			,	'actions' => "$d$room/*$e"
			) as $t => $pat) if ($a = glob($pat, GLOB_NOSORT)) {
				natcasesort($a);
				$i = 0;
				$dest = "$d$room/$t/";
				foreach ($a as $f) if (is_file($f)) {
					$i++;
					$n = get_file_name($f);
					if ($t === 'reports') $n = intval($n).$e;
					$n = mkdir_if_none($dest.$n);
					$r = (rename($f, $n)?'OK':'failed to move');
					$done .= NL."$i: $f -> $n = $r";
				}
				if ($i) {
					$done .= NL.($i = "done $i $t in $room");
					time_check_point($i);
				}
			}
			data_unlock($lk);

		}
	}
	if (!$what) {
		if (is_dir($old)) {

		//* consolidate various legacy folders;
		//* leave old lock files alone, manually check anything left and remove them later;
		//* also open old lockfiles are troublesome while in transition to new path system;
		//* either close the whole web folder for maintenance, or pray nothing breaks because visitors are rare enough.

			if (is_dir(mkdir_if_none($new = DATA_DIR))) {
				$i = 0;
				$s = DATA_STATIC_EXT;
				$bak = '.bak.'.T0;
				if ($g = GAME_TYPE_DEFAULT) $g = "$g/";
		//* gather old paths, from => to:
				$a = array(
					"$old/ref$e"	=> DATA_REF_LIST
				,	"$old/u$e"	=> DATA_USERLIST
				,	"$old/u"	=> DATA_DIR_USER
			//	,	"$old/l"	=> DATA_DIR_LOCK
			//	,	"$old/room"	=> DATA_DIR_ROOM
				,	"$old/actions"	=> DATA_DIR.DATA_SUB_ACT
				);
				foreach ($tmp_announce as $k => $v) $a["$old/$k$s"] = DATA_DIR.$k.$s;
				foreach (get_dir_contents($d = $old_meta = "$old/room/") as $room) {
					$a["$d$room"] = DATA_DIR_ROOM."$g$room";
				}
				foreach (get_dir_contents($d = DIR_ROOM) as $room) {
					$a["$d$room"] = DATA_DIR_ROOM."$g$room/".DATA_SUB_TRD;
				}
				foreach (get_dir_contents($d = DIR_ARCH) as $room) {
					$r = "$d$room";
					if ($g) {
						if (!in_array($room, $cfg_game_type_dir)) $a[$r] = "$d$g$room";
					} else {
						if (in_array($room, $cfg_game_type_dir) && is_dir($r.DIR_THUMB)) $a[$r] = "$r$bak";
					}
				}
		//* move them:
				foreach ($a as $f => $n) if (($f !== $n) && (is_file($f) || is_dir($f))) {
					delay_timeout();
					$i++;
					$n = trim($n, '/');
					if (is_file($n) || is_dir($n)) {
						$r = (rename($n, $b = "$n$bak")?'kept for back up':'failed to move out');
						$done .= NL."$i: $n -> $b = $r";
					}
					$r = (rename($f, mkdir_if_none($n))?'OK':'failed to move');
					$done .= NL."$i: $f -> $n = $r";
				}
		//* delete empty leftovers:
				foreach (array($old_meta, $old, DIR_ROOM) as $d) if (is_dir($d) && !get_dir_contents($d)) {
					$i++;
					$r = (rmdir($d)?'OK':'failed');
					$done .= NL."$i: old empty dir $d -> delete $r";
				}
				if ($i) {
					$done .= NL.($i = "done $i changes, $old -> $new");
					time_check_point($i);
				}
			} else die("Fatal error: could not create data folder \"$new\"!".($done ? '<hr>Done: '.nl2br($done) : ''));
		}
	}
	if (($d = 'threads') === ($t = $what ?: $d)) {
		foreach (get_dir_contents($d = DATA_DIR_ROOM) as $room) {
			$i = 0;
			delay_timeout();

			data_lock($lk = LK_ROOM.$room);
			if (is_dir($s = "$d$room/".DATA_SUB_TRD))
			foreach (get_dir_contents($s) as $f) if (
				is_file($path = $s.$f)
			&&	($old = file_get_contents($path))
			&&	($content = trim_bom($old))
			) {
				$i++;
				$lines = mb_split_filter($content, NL);
				foreach ($lines as $k => $v) if (
					false !== ($pic_start = mb_strpos_after($v, DATA_MARK_IMG))
				) {
					delay_timeout();
					$pic_end = mb_strpos($v, '	', $pic_start);
					$pic = mb_substr($v, $pic_start, $pic_end - $pic_start);
					$pic = get_post_pic_field_with_fixed_info($pic);
					$line = mb_substr($v, 0, $pic_start).$pic.mb_substr($v, $pic_end);
					if ($v !== $line) $lines[$k] = $line;
				}
				$new = DATA_LOG_START.implode(NL, $lines);
				if ($new !== $old) {
					$r = (
						(
							file_put_contents($path, $new)
						||	(
								rename($path, $old = $path.'.old'.T0.'.bak')
							&&	file_put_contents($path, $new)
							&&	unlink($old)
							)
						)
						? 'OK'
						: 'failed to write'
					);
					$n = strlen($new);
					$done .= NL."$i: $f -> $n = $r";
				}
			}
			data_unlock($lk);

			if ($i) {
				$done .= NL.($i = "done $i $t in $room");
				time_check_point($i);
			}
		}
	}
	if (($d = 'archive') === ($t = $what ?: $d)) {
		require_once(NAMEPRFX.'.arch.php');
		if ($i = data_archive_rewrite(array(
			'recheck_img' => array(
				'exists' => true
			)
		))) {
			$done .= NL.$i;
			time_check_point($i);
		}
	}
	data_unlock(LK_MOD_ACT);

	return $done;
}

//* ---------------------------------------------------------------------------

function data_get_user_flags($u_num) {
	$r = array();
	if ($u_num) {
		$d = DATA_DIR_USER;
		$e = DATA_U_FLAG;
		$sep = '	';

		data_lock(LK_USER.$u_num, false);
		foreach (get_file_lines("$d$e/$u_num.$e") as $line) {
			$a = mb_split($sep, $line);
			$key = reset($a);
			$value = end($a);
			$r[$key] = $value;
		}
	}
	return $r;
}

function data_check_user($u, $reg = false) {
	global $u_key, $u_num, $u_flag, $usernames, $last_user;
	$sep = '	';

	data_lock($lk = LK_USERLIST, false);
	foreach (get_file_lines(DATA_USERLIST) as $line) if (false !== mb_strpos($line, $sep)) {
		list($i, $k, $t, $name) = mb_split($sep, $line);
		$i = intval($i);
		if ($last_user < $i) $last_user = $i;
		if ($u === $k) {
			$u_key = $k;
			$u_num = $i;
			if ($reg) break;

			$u_flag = data_get_user_flags($u_num);
		}
		if (!$reg) $usernames[$i] = $name;
	}
	data_unlock($lk);

	return $u_num;
}

function data_log_user($u_key, $u_name) {
	global $last_user;
	$u_num = $last_user+1;
	$t = T0.'+'.M0;

	data_lock($lk = LK_USERLIST);
	$r = data_log(DATA_USERLIST, "$u_num	$u_key	$t	$u_name");
	if ($r && !$last_user) data_set_u_flag($u_num, 'god', 1);	//* <- 1st registered = top supervisor
	data_unlock($lk);

	return $r;
}

function data_collect($f, $data, $unique = true) {
	foreach ((array)$data as $v) {
		$v = "	$v";
		if (!$unique || !is_file($f) || false === mb_strpos(data_cache($f).NL, $v.NL)) data_log($f, T0.'+'.M0.$v, '');
	}
}

function data_log_ip() {
	global $u_num;
	$d = DATA_DIR_USER;
	$e = DATA_U_IP;
	data_collect("$d$e/$u_num.$e", $_SERVER['REMOTE_ADDR']);
}

function data_log_ref($r) {
	if ($r) {
		data_lock($lk = LK_REF_LIST);	//* <- so parallel checks won't miss each other with duplicates
		data_collect(DATA_REF_LIST, $r);
		data_unlock($lk);
	}
}

function data_log_action($text, $dump = '') {	//* <- write to logs of administrative actions, filename by date
	global $u_num, $room;
	$f = ($room ? DATA_DIR_ROOM."$room/" : DATA_DIR).DATA_SUB_ACT.date('Y-m-d', T0).DATA_LOG_EXT;
	$t = T0.'+'.M0;
	$u = (GOD?'g':(MOD?'m':'r')).$u_num;
	if ($dump) $text .= ': ['.indent($dump).']';
	return data_log($f, "$t	$u	$text", DATA_LOG_START, false);
}

function data_log_report($a) {			//* <- write to user report logs, filename by thread
	global $u_num, $room;
	$d = ($room ? DATA_DIR_ROOM."$room/" : DATA_DIR);
//* find matching file:
	if ($text = $a['report'])
	foreach (get_dir_contents($t = $d.DATA_SUB_TRD) as $f) if (
		preg_match(DATA_PAT_TRD_MOD, $f, $match)
	&&	strlen($i = $match['id'])
	&&	($i == $a['thread'])
	) {
//* check permission:
		if (
			is_file($f = $t.$f)
		&&	(
				MOD
//* cannot report invisible:
			||	(
					preg_match(DATA_PAT_TRD_PLAY, $match['active'], $m)
				&&	($u = intval($m['hold_u'])) > 0
				&&	$u == $u_num
				)
			||	data_thread_has_posts_by($f, $u_num)
			)
		) {
//* write changes:
			$act = 'report';
			if ($a['freeze'] || $a['stop']) {
				$act = 'freeze: '.(
					$match['inactive']
					? 'done before'
					: (
						rename($f, "$f.stop")
						? 'done'
						: 'failed'
					)
				).", $act";
			}
			$t = T0.'+'.M0;
			$p = $a['post'] ?: $a['row'] ?: 0;
			$s = $a['side'] ?: $a['column'] ?: 0;
			$f = $d.DATA_SUB_REP.$i.DATA_LOG_EXT;
			if (data_log($f, "$t	$p	$s	$text")) {
				data_post_refresh();
				return data_log_action("$a[0]	$act: $text");
			}
		}
		break;
	}
	return 0;
}

function data_get_mod_log_file($f, $mt = false) {	//* <- (full_file_path, 1|0)
	if (is_file($f)) return ($mt ? filemtime($f) : trim_bom(file_get_contents($f)));
}

function data_fix_mod_log_line_tabs($match) {
	global $usernames;
	if (
		($i = $match['UserID'])
	&&	($i = intval($i))
	&&	($name = $usernames[$i])
	) {
		$name = ": $name";
	}
	return "$match[Date]	$match[User]$name	$match[Text]";
}

function data_get_mod_log($t = '', $mt = false) {	//* <- (Y-m-d|key_name, 1|0)
	global $room, $u_num;

//* single list of reflinks:
	if ($t === LK_REF_LIST) {
		$t = data_get_mod_log_file(DATA_REF_LIST, $mt);
		if (!$mt) {
			$t = preg_replace(
				'~(\d+)([^\d\s]\V+)?	(\V+)~u'
			,	'$1	$3'		//* <- arrange data fields
			,	$t
			);
		}
		return htmlspecialchars($t);
	}

//* single list of users:
	if ($t === LK_USERLIST) {
		$t = data_get_mod_log_file(DATA_USERLIST, $mt);
		if (!$mt) {
			foreach (array(
				DATA_U_FLAG => array(
					'func' => 'data_get_user_flags'
				)
			,	DATA_U_ABOUT => array(
					'func' => 'data_get_user_profile'
				,	'exclude' => 'last modified'
				)
			) as $e => $a) {
				foreach (get_dir_contents(DATA_DIR_USER.$e) as $f) if (
					($fe = get_file_ext($f))
				&&	(
						$fe === $e
					||	$fe === $e.DATA_HIDDEN_EXT
					)
				&&	($i = intval(get_file_name($f)))
				&&	($f = $a['func'])
				&&	($f = $f($i))
				&&	($f = array_keys($f))
				&&	($f = trim(implode(', ',
						($x = $a['exclude'])
						? array_diff($f, (array)$x)
						: $f
					)))
				) {
					$t = preg_replace("~^$i	[^	]+~mu", "$0, $fe: [$f]", $t);
				}
			}
			$t = preg_replace(
				'~(\V+)(	\V+)	(\V+)\+\V+(	\V+?)~Uu'
			,	'$3,$1$4$2'		//* <- arrange data fields
			,	$t
			);
		}
		return htmlspecialchars($t);
	}

//* logs by date:
	$d = DATA_DIR_ROOM;
	$s = DATA_SUB_ACT;
	$e = DATA_LOG_EXT;
	if ($room) {
		$rooms = array($room);
	} else {
		$rooms = get_dir_rooms($d);
		$rooms[] = '';
	}
//* for a single day:
	if ($t) {
		$a = ($mt ? 0 : array());
//* for a single timestamp:
		if (false === mb_strpos($t, '-')) $t = date('Y-m-d', $t0 = $t);
//* for selected rooms:
		foreach ($rooms as $r) if (
			($p = ($r ? "$d$r/" : DATA_DIR))
		&&	($v = data_get_mod_log_file("$p$s$t$e", $mt))
		) {
//* a) get contents to show:
			if (!$mt) {
				$k = $r ?: '*';
				$v = "
room = $k".
preg_replace_callback('~
	(?<=\v)
	(?P<Date>\S+)\s+
	(?P<User>[^\s\d]*(?P<UserID>\d*)\S*?)\s+
	(?P<Text>\S+)
~ux', 'data_fix_mod_log_line_tabs',
//preg_replace('~(\v\S+)\s+(\S+)\s+~u', '$1	$2	',	//* <- arrange data fields
preg_replace('~\h+~u', ' ',
preg_replace('~<br[^>]*>(\d+)([^\d\s]\S+)\s~ui', NL.'$1	',	//* <- keep multiline entries atomic
preg_replace('~\v+~u', '<br>',
NL.htmlspecialchars($v)))));

//* check each message author and timestamp:
				if ($t0) {
					$v = mb_split(NL, $v);
					$v = array_filter(array_map(function($line) use ($t0, $u_num) {
						$tab = mb_split('	\D*', $line, 3);
						return (
							intval($tab[0]) == $t0
						&&	intval($tab[1]) == $u_num
						) ? $tab[2] : '';
					}, $v));
					$a = array_merge($a, $v);
				} else {
					$a[$k] = $v;
				}
			} else
//* b) find last mod.time:
			if ($a < $v) $a = $v;
		}
		if (!$mt) {
			if ($t0) return $a;
			ksort($a);
			return implode(NL, $a);
		}
	} else {
//* list of all dates with existing logs:
		$a = array();
		foreach ($rooms as $r)
		foreach (get_dir_contents(($r ? "$d$r/" : DATA_DIR).$s) as $f) {
			if (preg_match(PAT_DATE, $f, $m)) $a[$m['ym']][$m['d']] = $m['d'];
		}
		ksort($a);
		foreach ($a as $k => $v) natsort($a[$k]);
	}
	return $a;
}

function data_put_count($i = 0, $type = COUNT_ROOM, $r = '') {
	if ($r || ($r = $GLOBALS['room'])) {
		$f = DATA_DIR_ROOM."$r/$type".DATA_COUNT_EXT;
		return file_put_mkdir($f, $i);
	}
}

function data_get_count($type = COUNT_ROOM, $r = '', $uncached = false, $read = 'file_get_contents', $count = 'get_dir_top_file_id') {
	if ($r || ($r = $GLOBALS['room']))
//* 1) file_get_contents() seems ~2x faster than count(scandir()) on ~50 files
//* 2) scandir() seems ~2x faster than glob()
	return intval(
		!$uncached && is_file($f = DATA_DIR_ROOM."$r/$type".DATA_COUNT_EXT)
		? $read($f)
		: (
			($d = (
				$type == COUNT_ROOM
				? DATA_DIR_ROOM."$r/".DATA_SUB_TRD
				: get_const("DIR_$type").$r
			)) && is_dir($d)
			? $count($d)
			: 0
		)
	);
}

function data_get_mtime($type = COUNT_ROOM, $r = '', $uncached = false) {
	return data_get_count($type, $r, $uncached, 'filemtime', 'get_dir_top_filemtime');
}

function data_is_thread_full($n) {return $n === 'f' || intval($n) >= TRD_MAX_POSTS;}
function data_is_thread_cap($r = '') {
	if ($r || ($r = $GLOBALS['room'])) {
		foreach (get_dir_contents(DATA_DIR_ROOM."$r/".DATA_SUB_TRD) as $f) if (
			preg_match(DATA_PAT_TRD_MOD, $f, $match)
		&&	!$match['deleted']				//* <- "burnt" not counted
		&&	++$n >= TRD_MAX_PER_ROOM
		) return $n;
	}
	return 0;
}

function data_thread_has_posts_by($t, $u_num, $flags = DATA_FLAG_POST_ANY) {
	$u_tab = "	$u_num".($flags & DATA_FLAG_POST_IMG ? DATA_MARK_IMG : DATA_MARK_TXT);
	$t = data_get_thread_content($t);
	if ($flags & DATA_FLAG_POST_ANY) $t = mb_str_replace(DATA_MARK_IMG, DATA_MARK_TXT, $t);
	return false !== mb_strpos($t, $u_tab);
}

function data_get_last_post_u($t) {return data_get_tab_by_file_line($t, 'user', -1);}
function data_get_last_post_time($t) {
	if (is_array($t)) return intval($t['last_t'] ?: ($t['hold_u'] ? 0 : $t['hold_t']));	//* <- for legacy time mark format
	return intval(mb_substr($t, mb_strrpos_after($t, NL)));
}

function data_is_thread_last_post_pic($t) {
	$t = trim_bom(data_get_thread_content($t));
	$last_txt = mb_strrpos_after($t, DATA_MARK_TXT);
	$last_pic = mb_strrpos_after($t, DATA_MARK_IMG);
	return $last_txt < $last_pic;
}

function data_get_thread_content($t) {return (false === mb_strpos($t, '	') ? data_cache($t) : $t);}
function data_get_thread_pics($t, $full_path = false) {
	$a = array();
	$t = file_get_contents($t);
	if (preg_match(PAT_CONTENT, $t, $match)) $t = $match['content'];
	if (preg_match_all(DATA_PAT_IMG, $t, $match)) {
		$m = array_map($full_path ? 'get_pic_subpath' : 'get_file_name', $match['path']);
		foreach ($m as $path) {
			if (($i = mb_strpos($path, ';')) !== false) {
				$a[] = get_pic_resized_path($path = mb_substr($path, 0, $i));
			}
			$a[] = $path;
		}
		return array_unique($a);
	}
	return $a;
}

function data_get_thread_name_tail($t, $count_pics = true) {
	global $room_type;
	$tt = !!$room_type['lock_taken_task'];
//* locked thread:
	if (is_array($t)) {
		if ($tt) list($hold_t, $hold_u) = $t;
//* unlocked thread:
	} else if ($t) {
		$pics = mb_substr_count($t, DATA_MARK_IMG);
		if ($tt) {
			$last_u = data_get_last_post_u($t);
			$last_t = data_get_last_post_time($t);
		} else $count_pics = false;
//* always mark a full thread:
		if (!$count_pics && $pics < TRD_MAX_POSTS) unset($pics);
	}
	$tail = '';
	foreach (array(
		'p' => 'pics'
	,	'l' => 'last_t'
	,	'a' => 'last_u'
	,	't' => 'hold_t'
	,	'u' => 'hold_u'
	) as $k => $v) if (isset($$v)) $tail .= ".$k${$v}";
	return $tail;
}

function data_put_thread_rename_if_free($f, $t = '', $m = '') {
	if (!file_put_mkdir($f, $t)) return false;
	if (is_array($m)) {
//* if task is free:
		if ($m['pics'] || strpos($m['etc'], 'p') || mb_substr_count($t, DATA_MARK_IMG) >= TRD_MAX_POSTS) {
			$t = data_get_thread_name_tail($t);
		} else
//* if task is taken:
		if ($m['hold_u'] || strpos($m['etc'], 'u')) {
			$t = '';
		} else {
//* if task was taken and dropped:
			$t = data_get_thread_name_tail($t, false);
		}
		if ($t) $n = get_file_dir($f)."/$m[id]$t$m[ext]$m[inactive]";
	}
	if ($n && $n !== $f) rename($f, $n);
	return true;
}

function data_get_thread_by_num($n) {
	global $room, $data_cache_d, $data_cache_t;				//* <- ids/paths/content cached for batch processing
	if (!is_array($data_cache_t)) $data_cache_t = array(); else
	if (($t = $data_cache_t[$n]) && is_file($t[0].$t[1])) return $t;
	if ($data_cache_d || is_dir($data_cache_d = DATA_DIR_ROOM."$room/".DATA_SUB_TRD))
	foreach (get_dir_contents($data_cache_d) as $f) if (
		preg_match(DATA_PAT_TRD_MOD, $f, $match)
	&&	strlen($n == $match['id'])
	) return ($data_cache_t[$n] = array($data_cache_d, $f, $match));	//* <- dir/path/, filename, name parts array(num,etc,ext,.stop)
	return 0;
}

function data_get_tab_by_file_line($f, $tab = 0, $line = 0) {
	if (!$f) return 0;
//* find last in file:
	if (!$line) $f = file_get_contents($f); else
//	if ($line < 0) $f = data_cache($f); else
//* find in given line of file:
	if ($line > 0) {
		if ($line >= count($f = get_file_lines($f))) return 0;
		$f = $f[$line];
	}
	$sep = '	';
	if ($tab === 'file') return (
		false !== ($i = mb_strrpos_after($f, DATA_MARK_IMG))
		? mb_substr($f, $i, mb_strpos($f, $sep, $i)-$i)
		: 0
	);
	if ($tab === 'user') {
		$i = max(mb_strrpos($f, DATA_MARK_IMG), mb_strrpos($f, DATA_MARK_TXT));
		$f = mb_substr($f, 0, $i);
		return mb_substr($f, mb_strrpos_after($f, $sep));
	}
	return 0;
}

function data_get_u_by_post($a) {
	global $data_cache_u;
	if (!is_array($data_cache_u)) $data_cache_u = array(); else
	if ($p = $data_cache_u[$n = "$a[0]-$a[1]"]) return $p;
	if (list($d, $f) = data_get_thread_by_num($a[0]))
	return ($data_cache_u[$n] = data_get_tab_by_file_line($d.$f, 'user', $a[1]));
	return 0;
}

function data_set_u_flag($u, $name, $on = -1, $harakiri = false) {
	global $u_num, $u_flag, $room;
	if (is_array($u)) $u = data_get_u_by_post($u);

//* rename user:
	$sep = '	';
	if ($on < 0) {
		$report = '';
		if ($u) {
			data_lock($lk = LK_USERLIST);
			if ($a = get_file_lines($f = DATA_USERLIST)) {
				foreach ($a as &$line) if (intval($line) == $u) {
					$i = mb_strrpos_after($line, $sep);
					$old = mb_substr($line, $i);
					if ($old === $name) {
						$report = "$u already has this name: $old";
					} else {
						$line = mb_substr($line, 0, $i).$name;
						$s = (file_put_mkdir($f, implode(NL, $a)) ? 'renamed' : 'rename failed');
						$report = "$u $s: $old -> $name";
					}
					break;
				}
				unset($line);
			}
			data_unlock($lk);
		}
		return $report ?: "no user with ID $u";
	}

//* modify user flags:
	if (!$u || !$name) return 0;

	$d = DATA_DIR_USER;
	$e = DATA_U_FLAG;
	$f = "$d$e/$u.$e";
	$replace = ($on && $on !== true && $on !== intval($on) && $on !== $name ? $on : '');
	$add = ($on && !$replace);
	$text = T0.'+'.M0.'	'.($replace ?: $name);
	$flags = data_get_user_flags($u);

	data_lock(LK_USER.$u);
	if ($flags) {
//* check if can:
		if (
			!GOD
		&&	(
				$flags['god']
			||	(
					$on
				&&	$name == 'ban'			//* <- mods cannot ban mods
				&&	($flags['mod'] || $flags["mod_$room"])
				)
			||	(
					!$on
				&&	!$harakiri
				&&	$u == $u_num
				&&	substr($name,0,3) == 'mod'	//* <- mods cannot self-resign
				)
			)
		) return -$u;
//* check if set:
		foreach ($flags as $k => $v) if ($k == $name) {
			if ($add) return $u;		//* <- add, exists
			unset($flags[$k]);		//* <- remove
			++$removed;
		}
//* modify:
		if ($add) data_log($f, $text, '');	//* <- add
		else if (!$removed) return 0;		//* <- remove, did not exist
		else if ($text = data_get_flags_text_from_array($flags).($replace ? NL.$text : '')) file_put_mkdir($f, $text);
		else unlink($f);			//* <- removed all, nothing left
	} else if ($add) file_put_mkdir($f, $text);	//* <- add, new file
	else $u = 0;
	return $u;
}

function data_get_flags_text_from_array($a) {
	$r = array();
	foreach ($a as $k => $v) $r[] = ($k == $v ? $k : "$v	$k");
	return implode(NL, $r);
}

function data_replace_u_flag($f, $from, $to = '') {
	if (!$from || $from === $to) return;
	$d = DATA_DIR_USER;
	$e = DATA_U_FLAG;
	if ($f) {
		$u = intval(get_file_name($f));
		if ($u = data_set_u_flag($u, $from, $to)) {
			$log = NL."$from -> $to: user ID = $u";
		}
	} else
	foreach (get_dir_contents("$d$e") as $f) if (get_file_ext($f) == $e) {
		$log .= data_replace_u_flag($f, $from, $to);
	}
	return $log;
}

function data_prepend_line_time($match) {
	$t = get_time_html($match[1]);
	return "$t	$match[0]";
}

function data_get_user_info($u_num) {
	$r = array();
	$d = DATA_DIR_USER;

	data_lock($lk = LK_USER.$u_num, false);
	foreach (array(
		DATA_U_ABOUT	=> 'About'
	,	DATA_U_FLAG	=> 'Flags'
	,	DATA_U_TASK	=> 'Tasks'
	,	DATA_U_IP	=> 'IPs'
	) as $e => $v) if (
		(
			is_file($f = "$d$e/$u_num.$e")
		||	(
				is_file($f .= DATA_HIDDEN_EXT)
			&&	($v .= ' (hidden)')
			)
		)
	&&	($f = trim_bom(file_get_contents($f)))
	) {
		$r[$v] = preg_replace_callback('~^(\d{10,})\D~m', 'data_prepend_line_time', $f);
	}
	data_unlock($lk);

	return $r;
}

function data_get_user_profile($u_num, $array = true) {
	$d = DATA_DIR_USER;
	$e = DATA_U_ABOUT;
	$f = "$d$e/$u_num.$e";

	if (!$array) return is_file($f);

	data_lock($lk = LK_USER.$u_num, false);
	$a = get_file_lines(is_file($f) ? $f : $f.DATA_HIDDEN_EXT);
	data_unlock($lk);

	$sep = '	';
	$r = array();
	foreach ($a as $line) {
		$tab = mb_split_filter($line, $sep);
		$k = array_shift($tab);
		$k = trim($k, '[]():. =->');
		if ($k == 'email') {
			$v = array('addr' => array_shift($tab));
			foreach ($tab as $i) $v[$i] = $i;
		} else {
			$v = (count($tab) > 1 ? $tab : $tab[0]);
		}
		if ($v && trim_bom("$v")) $r[$k] = $v;
	}
	return $r;
}

function data_save_user_profile($a, $write = true) {
	global $u_num;
	if (is_array($m = $a['email'] ?? '')) $a['email'] = implode('	', $m);
	$t = T0.'+'.M0;
	$new = (
		$a
		? BOM."
last modified:	$t
email:	$a[email]
about:	$a[about]
"
		: ''
	);
	if ($write) {
		$d = DATA_DIR_USER;
		$e = DATA_U_ABOUT;
		$v = "$d$e/$u_num.$e";
		$h = $v.DATA_HIDDEN_EXT;
		$about_files = array($v, $h);

		foreach ($about_files as $f) {
			$old = (
				is_file($f)
				? trim_bom(file_get_contents($f))
				: ''
			);
			if ($old) {
				$old = preg_replace('~(
last modified:	)\V*~u', '$1'.$t, $old);
				break;
			}
		}
		if ($old !== $new) {

			data_lock($lk = LK_USER.$u_num);
			foreach ($about_files as $f) if (is_file($f)) unlink($f);
			if ($new) {
				$f = (
					($a['about'] || ($m && $m['show']))
					? $v
					: $h
				);
				file_put_mkdir($f, $new);
			}
			data_unlock($lk);

		}
	}
	return $new;
}

function data_get_full_threads($get_content = true) {
	global $room, $room_type, $usernames;
	$wait = !!$room_type['arch_wait'];
	$sep = '	';
	$threads = array();
	foreach (get_dir_contents($d = DATA_DIR_ROOM."$room/".DATA_SUB_TRD) as $f) if (
		preg_match(DATA_PAT_TRD_PLAY, $f, $match)
	&&	data_is_thread_full($count = $match['pics'])
	&&	!($wait && ($t = data_get_last_post_time($match)) && ($t + TRD_ARCH_TIME > T0))
	&&	is_file($f = $d.$f)
	) {
		if (!$get_content) return true;
		$last_time = 0;
		$a = array('name' => $f);
		foreach (get_file_lines($f) as $line) if (false !== mb_strpos($line, $sep)) {
			$tab = mb_split($sep, $line);
			if ($tab[0]) {
				if ($last_time < $tab[0]) $last_time = $tab[0];
				$tab[0] = date(CONTENT_DATETIME_FORMAT, $tab[0]);
			}
			if ($tab[1] && ($i = intval($tab[1])) && isset($usernames[$i])) $tab[1] = $usernames[$i];
			if ($tab[2]) {
			//* image markup for scriptless view, autofixed by archive template:
				list($src, $res) = mb_split(';', $tab[3], 2);
				if (!isset($a['thumb'])) $a['thumb'] = get_pic_subpath($src);
				$tab[3] = '<img src="'.get_pic_url($src).'">'.($res ? ";$res" : '');
			}
			unset($tab[2], $tab[5]);
			$a['content'] .= NL.implode($sep, $tab);
		}
		if ($count === 'f' || !$wait || ($last_time + TRD_ARCH_TIME < T0)) $threads["$last_time-$f"] = $a;
	}
	if (!$get_content) return false;
	ksort($threads);
	return $threads;
}

function data_archive_ready_go() {
	if ($threads = data_get_full_threads()) {
		require_once(NAMEPRFX.'.arch.php');
		return data_archive_full_threads($threads);
	}
}

function data_get_post_pic_info($post_content, $check_file = 1) {
	list($pic, $csv) = mb_split(';', $post_content, 2);
	return get_post_pic_info($pic, $csv, $check_file);
}

function data_find_by($terms, $caseless = true, $include_hidden = false) {
	global $r_type, $room, $usernames;
	$results = array();
	if (!$terms) return $results;

if (TIME_PARTS) time_check_point('inb4 active data search prep, terms = '.get_print_or_none($terms));
	$d = DATA_DIR_ROOM;
	$e = DATA_LOG_EXT;
	$elen = -strlen($e);
	$rooms = (array)($room ?: get_dir_rooms($d, '', F_NATSORT | ($include_hidden ? 0 : F_HIDE), $r_type));
	$c = count($rooms);
if (TIME_PARTS) time_check_point("got $c rooms, inb4 search iteration".NL);

	foreach ($rooms as $r) {
		$files = array();

		data_lock($lk = LK_ROOM.$r);
		foreach (get_dir_contents($dr = "$d$r/".DATA_SUB_TRD, F_NATSORT) as $f) if (
			substr($f, $elen) === $e
		&&	is_file($path = "$dr/$f")
		) $files[$path] = intval($f);
if (TIME_PARTS) {$n_found = 0; time_check_point(count($files)." files in $dr");}

		foreach ($files as $path => $i) if (
			$t = trim_bom(file_get_contents($path))
		) {
if (TIME_PARTS) $n_check = '';
			foreach (mb_split_filter($t, NL) as $line) {
				$tab = mb_split('	', $line);
				$post = array_filter(array(
					'date' => intval($tab[0]) ?: '?'
				,	'username' => $usernames[$tab[1]] ?: '?'
				,	'user_id' => $tab[1] ?: '?'
				,	'post' => $tab[3] ?: '?'
				,	'meta' => $tab[4]
				));
				if ($found = is_post_matching($post, $terms, $caseless)) {
					$results[$r]["hide_id_$i"][] = get_post_fields_to_display($post);
if (TIME_PARTS) {++$n_found; $n_check .= "=$n_found: $found";}
				}
			}
if (TIME_PARTS) time_check_point("done $i$n_check");
		} else
if (TIME_PARTS) time_check_point("$i: content not found in $path");

		data_unlock($lk);
	}

	return $results;
}

//* Deletions: ----------------------------------------------------------------

function data_del_pic_file($f, $keep = '') {
	if (!is_file($f)) return false;
	$d = 'unlink';
	return (
		$keep
		? rename($f, $d = $keep.get_file_name($f))
		: $d($f)
	)?$d:'';
}

function data_del_pic($f, $keep = '') {
	global $room;
	if ($keep) mkdir_if_none($keep = DIR_PICS_DEL."$room/");
	foreach (array(get_pic_resized_path($f), $f) as $f) $status = data_del_pic_file($f, $keep);
	return $status;
}

function data_del_thread($f, $del_pics = false) {
	global $room;
	$count = array();
	if (is_file($f)) {
		if ($del_pics && ($files = data_get_thread_pics($f, true))) {
			$k = (
				($to_trash = (1 == $del_pics))
				? 'pics moved to trash'
				: 'pics erased'
			);
			foreach ($files as $path) if (data_del_pic($path, $to_trash)) $count[$k]++;
		}
		if (unlink($f)) {
			$count['files']++;
			$f = DATA_DIR_ROOM."$room/".DATA_SUB_REP.intval(get_file_name($f)).DATA_LOG_EXT;
			if (is_file($f) && unlink($f)) $count['reports']++;
		}
	}
	return $count;
}

function data_del_tree($d, $del_pics = false) {
	$count = array();
	if (is_dir($d)) {
		foreach (get_dir_contents($d) as $sub)
		foreach (data_del_tree("$d/$sub", $del_pics) as $k => $v) {
			$count[$k] += $v;
		}
		if (rmdir($d)) $count['dirs']++;
	} else
	if (is_file($d)) {
		foreach (data_del_thread($d, $del_pics) as $k => $v) {
			$count[$k] += $v;
		}
	}
	return $count;
}

//* Mod actions: --------------------------------------------------------------

function data_mod_action($a) {		//* <- array(option name, thread, row, column, option num)
	global $u_num, $u_flag, $r_type, $room, $merge, $cfg_game_type_dir, $tmp_announce;

	if (!MOD) return 0;

	$q = explode('+', array_shift($a));
	$o = array_shift($q);
	if (NO_MOD && !GOD && $o != 'room announce') return 0;

	$e = DATA_LOG_EXT;
	$ok = 0;
	$un = count($q);
	$msg = $_POST["t_$a[0]_$a[1]_$a[2]"] ?: '';
	if ($a[2] > 1) $a = $a[0];	//* <- from global mod panel, not room/thread/post

//* mod left-side menu --------------------------------------------------------

	if ($o == 'archive') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			if ($un > 1) {
				$t = data_get_thread_name_tail(file_get_contents($d.$f));
				if (rename($d.$f, "$d$m[id]$t$e")) $ok = 'OK';	//* <- put to wait
			} else {
				if (rename($d.$f, "$d$m[id].pf$e")) $ok = 'OK';	//* <- get ready
				if ($ok && !$un && is_array($r = data_archive_ready_go())) {
					foreach ($r as $k => $v) if ($v) $ok .= ", $v $k";
					$return = 'trd_arch';
				}
			}
			if ($ok) data_post_refresh();
		}
	} else
	if (substr($o,0,8) == 'freeze t') {
		if (
			(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&&	$f != ($n = $m['active'].($un > 1?'.del':($un?'':'.stop')))
		&&	rename($d.$f, $d.$n)
		) {
			$ok = 'OK';
			data_post_refresh();
		}
	} else
	if (substr($o,0,8) == 'delete c') {
		if (
			(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&&	is_file($r = DATA_DIR_ROOM."$room/".DATA_SUB_REP."$m[id]$e")
		&&	unlink($r)
		) {
			$ok = 'OK';
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
			: ($bak = rename($d.$f, "$d$m[active].del"))
		) {
			$ok = 'OK'.$bak;
			if ($count) $ok .= NL.'deleted counts: '.get_print_or_none($count);
			data_post_refresh();
		}
	} else
	if (substr($o,0,11) == 'delete post') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			$ok = $i = $a[1];
			$old = get_file_lines($f = $d.$f);
			if (count($old) > $i) {
				$ok .= '='.$old[$i];				//* <- save post contents in log, just in case
				unset($old[$i]);
				if (count($old) > 1) {
					data_put_thread_rename_if_free($f, implode(NL, $old), $m);
				} else {
					data_del_thread($f);
					$ok .= '	> void';
				}
				data_post_refresh();
			} else $ok = -$ok;
		}
	} else
	if (substr($o,0,7) == 'delete ') {					//* <- pic/image
		if (
			(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&&	($fn = data_get_tab_by_file_line($d.$f, 'file', $a[1]))
		&&	is_file($f = get_pic_subpath($fn))
		&&	(
				$un == 1
				? (($d = file_put_contents($f, '')) === 0)	//* <- 0-truncate
				: ($d = data_del_pic($f, !(GOD && $un > 1)))
			)
		) $ok = "$a[1]=$f -> $d";
	} else
	if (substr($o,0,7) == 'merge t') {
		if (list($d,$f,$m) = data_get_thread_by_num($a[0])) {
			if ($un) {
				$ok = $a[1];
				$old = count($f = get_file_lines($d.$f));	//* <- source to add
				if ($old-- > $ok) {
					$merge[$m['id']] = trim_bom(implode(NL, array_slice($f, $ok)));
					$ok = "+t$m[id] p$ok-$old";
				} else $ok .= ' post not found';
			} else
			if (is_array($merge) && count($merge)) {
				$n = trim_bom($old = file_get_contents($f = $d.$f)).NL.implode(NL, $merge);
				$n = array_unique(mb_split_filter($n, NL));
				natsort($n);
				if ($old != ($new = DATA_LOG_START.trim_bom(implode(NL, $n)))) {
					data_put_thread_rename_if_free($f, $new, $m);
					$ok = "$m[id]<-".implode(',', array_keys($merge));
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
				$lst = DATA_LOG_START;
				if ($fst = mb_strpos($old[$ok], DATA_MARK_IMG)) {
					$lst .= mb_substr($old[$ok], 0, $fst)
					.DATA_MARK_TXT
					.NOR
					.'<!-- '.T0.': thread split -->'
					.NL;	//* <- add placeholder if pic first
				}
				$fst = implode(NL, $p);
				$lst .= implode(NL, $q);
				$t = data_get_thread_name_tail($fst);
				data_put_count($ok = data_get_count()+1);
				file_put_mkdir("$d$ok$t$e$m[inactive]", $fst);	//* <- put 1st half into new thread, un/frozen like old
				data_put_thread_rename_if_free($f, $lst, $m);	//* <- put 2nd half into old thread, to keep people's target
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
				&&	in_array($k = mb_strtolower($match[1]), $keys)
				) {
					$v = preg_replace('~\s+~u', ' ', $match[2]);
					if ($k == 're') {
						$k = 'text';
						$v = '<span class="mod">'.$v.'</span>';
					}
					$lsv[$k] = $v;
				}
			//	if (!$lsv) $lsv['text'] = preg_replace('~\s+~u', ' ', $msg);
				if ($lsv) {
					$ok .= NL.'data = '.get_print_or_none($lsv);
					$tab = (
						$un < 2
						? array(T0, $u_num)		//* <- add, insert (own post from now, if not specified)
						: mb_split('	', $old)	//* <- edit, replace (assume old post as valid)
					);

			//* timestamp/ID, accept digits > 0 only, or no change:
					foreach (array('time', 'user') as $i => $k)
					if (($v = $lsv[$k]) && !trim($v, '0123456789')) $tab[$i] = $v;

			//* text, just make a post and be done:
					if ($v = $lsv['text']) {
						$new = "$tab[0]	$tab[1]".DATA_MARK_TXT.format_post_text($v);
					}

			//* file/info, edit parts if post with file, or replace full post if enough values:
					else if (array_filter(array(
						($v = $lsv['file'])
					,	($t = $lsv['meta'])
					,	($b = $lsv['browser'])
					))) {
						$old_mark = $tab[2];
						$img_mark = trim(DATA_MARK_IMG);
						if ($old_mark == $img_mark) {
							if ($v) $tab[3] = $v; else if ($tab[3]) $v = 1;
							if ($t) $tab[4] = $t; else if ($tab[4]) $t = 1;
						} else {
							$tab[2] = $img_mark;
							if ($v) $tab[3] = $v;
							if ($t) $tab[4] = $t; else $tab[4] = '-';
						}
						if ($v && $t) {
							if ($b) $tab[5] = $b;
							$tab[3] = get_post_pic_field_with_fixed_info($tab[3], 2);
							$new = implode('	', $tab);
						}
					} else $new = implode('	', $tab);
				}
		//* save result: ----------------------------------------------
				if (!strlen(trim($new))) $ok .= NL.'! nothing to save';
				else if ($old === $new) $ok .= NL.'! no change';
				else {
					if ($un == 1) $l[$n] = $new.NL.$old;	//* <- add before
					else if (!$un) $l[$n] = $old.NL.$new;	//* <- add after
					else {
						$l[$n] = $new;
						$ok .= NL."old = $old";
					}
					if (data_put_thread_rename_if_free($f, implode(NL, $l), $m)) {
						$ok .= NL."new = $new";
						data_post_refresh();
					} else $ok .= NL.'! save failed';
				}
			} else $ok = -$ok;
		}
	} else

//* mod right-side menu -------------------------------------------------------

	if ($o == 'ban'			) $ok = data_set_u_flag($a, 'ban', !$un); else
	if ($o == 'can report'		) $ok = data_set_u_flag($a, 'nor', $un); else
	if ($o == 'give mod'		) $ok = data_set_u_flag($a, $room? "mod_$room":'mod', !$un); else
	if (substr($o,0,4) == 'hara'	) $ok = data_set_u_flag($u_num, $room?"mod_$room":'mod', 0, 1); else

	if (!GOD && $o != 'room announce') return 0; else

//* god right-side menu -------------------------------------------------------

	if (substr($o,0,3) == 'get'	) $ok = data_set_u_flag($a, 'nop', $un); else
	if (substr($o,0,3) == 'see'	) $ok = data_set_u_flag($a, 'see', !$un); else
	if ($o == 'give god'		) $ok = data_set_u_flag($a, 'god', !$un); else
	if ($o == 'rename') {
		$new = trim_post($msg, USER_NAME_MAX_LENGTH);
		$ok = ($new ? data_set_u_flag($a, $new) : "invalid name: $msg");
	} else

	if ((	($g = (0 === strpos($o, 'global')))
	+	($r = (0 === strpos($o, 'room')))
	) && (	($n = strpos($o, 'announce'))
	+	($z = strpos($o, 'freeze'))
	)) {
		$f = ($r ? DATA_DIR_ROOM."$room/" : DATA_DIR).($n?'anno':'stop').DATA_STATIC_EXT;
		$ok = (
			($n?$msg:!$un)
			? ((file_put_mkdir($f, $msg) === strlen($msg)) ? ($msg ?: '-') : 0)
			: (is_file($f) && unlink($f))
		);
	} else

//* god left-side menu --------------------------------------------------------

	if ($o == 'rename room') {
		if (!($msg = trim_room($msg))) return 0;

		if ($r_type) $msg = "$r_type/$msg";
		if (!$r_type && in_array($msg, $cfg_game_type_dir)) {
			$ok = "name /$msg/ is not available (reserved for room type)";
		} else
		if ($room === $msg) {
			$ok = "source /$room/ = target /$msg/, no change";
		} else
		if (!is_dir($old = DATA_DIR_ROOM.$room)) {
			$ok = "source $old/ does not exist";
		} else
		if ($new = DATA_DIR_ROOM.$msg) {

			data_lock(LK_ROOM.$msg);
	//* copy thread:
			if ($un) {
				if (!is_dir($new)) $ok = "target $new/ does not exist";
				else if (
					(list($d,$f,$m) = data_get_thread_by_num($i = $a[0]))
				&&	($n = data_get_count(COUNT_ROOM, $msg)+1)
				&&	($t = mkdir_if_none("$new/".DATA_SUB_TRD."$n$m[etc]$e$m[inactive]"))
				&&	copy($d.$f, $t)
				) {
					$ok = "$f -> $t";
					if (is_file($r = "$old/".DATA_SUB_REP.$i.$e)) {
						$t = mkdir_if_none("$new/".DATA_SUB_REP.$n.$e);
						if (copy($r, $t)) $ok .= NL."+ $r -> $t";
					}
					data_put_count($n, COUNT_ROOM, $msg);
					data_post_refresh($msg);
				}
			} else
	//* rename room:
			if (is_dir($new)) $ok = "target $new/ already exists";
			else {
				$ok = "/$room/ -> /$msg/";
				foreach (array(DATA_DIR_ROOM, DIR_ARCH) as $f) {
					$old = $f.$room;
					$new = $f.$msg;
					$ok .= ",$f:"
						.(is_dir($new) && rename($new, $new.'.'.T0.'.old') ?'old_bak+':'')
						.(is_dir($old) && rename($old, $new) ?1:0);
				}
				$ok .= data_replace_u_flag(0, "mod_$room", "mod_$msg");
				data_post_refresh($room = $msg);
			}
		}
	} else
	if ($o == 'nuke room') {
		$del_pics = ($un?2:1);
		$k = DATA_DIR_ROOM."$room/";
		$a = array();
		$count = array();

	//* wipe just the active content:
		$a[$k.DATA_SUB_TRD] = $del_pics;

	//* total nuke, including all meta:
		if ($un > 1) {
			$a[$k] = 0;
			$a[DIR_ARCH.$room] = $del_pics;
			$ok .= data_replace_u_flag(0, "mod_$room");
			$room = '';
		}

		foreach ($a as $k => $v) $count[$k] = data_del_tree($k, $v) ?: 'none';
		if ($count) $ok .= NL.'deleted counts: '.get_print_or_none($count);

		global $data_cache_d, $data_cache_t;
		unset($data_cache_d, $data_cache_t);
		clearstatcache(true);	//* <- useless?
	} else

//* no action -----------------------------------------------------------------

	return 0;

	if ($un) $o .= '+'.end($q);
	if (is_array($a)) $a = implode('-', $a);
	$logged = data_log_action("$a	$o: $ok");
	return $return ?: $logged;
}

//* END mod actions. ----------------------------------------------------------

function data_get_visible_images($room = '') {
	$a = array();
	$d = DATA_DIR_ROOM;
	$rooms = ($room ? (array)$room : get_dir_rooms($d));
	foreach ($rooms as $r) if (is_dir($s = "$d$r/".DATA_SUB_TRD)) {
		data_lock($lk = LK_ROOM.$r);
		foreach (get_dir_contents($s) as $f) if (is_file($path = $s.$f)) {
			foreach (data_get_thread_pics($path) as $i) {
				if (false === array_search($i, $a)) $a[] = $i;
			}
		}
		data_unlock($lk);
	}
	return $a;
}

function data_get_visible_rooms($type = '') {
	global $u_flag;
	$b = COUNT_POST;
	$e = DATA_COUNT_EXT;
	$g = DATA_DIR_ROOM;
	$mod_marks = array('stopped', 'deleted');
	foreach (array('export', 'import') as $f) {
		if (($$f = get_const("DATA_FUNC_$f")) && !function_exists($$f)) $$f = 0;
	}
	$last = 0;
	$a = array();
	$c = count($rooms = get_dir_rooms($g, '', F_NATSORT | F_HIDE, $type));
if (TIME_PARTS) time_check_point("done scan: $c rooms, inb4 room iteration".NL);
	foreach ($rooms as $r) if (is_dir($s = ($d = "$g$r/").DATA_SUB_TRD)) {
		ob_start();
		$last_time_in_room = 0;
		$recheck_file = 1;
recheck_file:
		if (
			is_file($cf = "$d$b$e")
		&&	($im = (
				$import
			&&	($t = file_get_contents($cf))
				? (
					$import == 'json_decode'
					? $import($t, true)
					: $import($t)
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
			data_lock($lk = LK_ROOM.$r);

			if ($recheck_file) {
				$recheck_file = 0;
				goto recheck_file;		//* <- to see if updated while waiting for lock
			}

			$last_time_in_room = intval(T0);	//* <- force to now, less problems
			$last_post_time =
			$count_trds =
			$count_desc =
			$count_pics = 0;
			$mod = array();
			foreach (get_dir_contents($s) as $f) if (is_file($path = $s.$f)) {
				$thread_time = data_get_last_post_time($t = file_get_contents($path));
				if ($last_post_time < $thread_time) $last_post_time = $thread_time;
				$count_trds ++;
				$count_desc += mb_substr_count($t, DATA_MARK_TXT);
				$count_pics += mb_substr_count($t, DATA_MARK_IMG);
				if (preg_match(DATA_PAT_TRD_PLAY, $f, $match)) {
					if (data_is_thread_full($match['pics'])) ++$mod['full'];
				} else
				if (preg_match(DATA_PAT_TRD_MOD, $f, $match)) {
					foreach ($mod_marks as $k) if ($match[$k]) ++$mod[$k];
				}
			}
			foreach (get_dir_contents($s = $d.DATA_SUB_REP) as $f) if (is_file($path = $s.$f)) {
				$mod['reports'] += mb_substr_count(file_get_contents($path), NL);
			}
			$c = array(
				'threads now'	=> $count_trds
			,	'threads ever'	=> data_get_count(COUNT_ROOM, $r)
			,	'threads arch'	=> data_get_count(COUNT_ARCH, $r)
			,	'last arch'	=> data_get_mtime(COUNT_ARCH, $r)
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
			data_unlock($lk);
		}
		if ($o = trim_bom(ob_get_clean())) data_log_action("include($cf) buffer dump: $o");
		if ($last < $last_time_in_room) $last = $last_time_in_room;
		if ($mod = array_filter($mod)) {
			if (!GOD) unset($mod['deleted']);
			if (
				NOT_MOD_SEE_ROOM_MARKERS
			||	GOD
			||	$u_flag['mod']
			||	$u_flag["mod_$r"]
			) $c['marked'] = $mod;
		}
		if ($t = data_global_announce('all', $r)) $c['anno'] = $t;
		$a[$r] = array_filter($c);
if (TIME_PARTS) time_check_point("done room $r");
	}
	return $a ? array(
		'last' => $last
	,	'list' => $a
	) : $a;
}

function data_get_visible_threads() {
	global $u_num, $u_flag, $room, $room_type, $data_cache_u;
	$show_unknown = !$room_type['hide_unknown_threads'];
	$sep = '	';
	$d = DATA_DIR_USER;
	$e = DATA_U_FLAG;
	$g = DATA_LOG_EXT;
	$r = DATA_DIR_ROOM."$room/";
	$td = $r.DATA_SUB_TRD;
	$tr = $r.DATA_SUB_REP;
	$u_marks = array('ban', 'god', 'mod', "mod_$room", 'nor');
	$threads = array();
	$reports = array();
	$changes = array();
	$last = 0;

	data_lock($lk = LK_ROOM.$room, false);
	$c = count($files = get_dir_contents($td, F_NATSORT));
if (TIME_PARTS) time_check_point("done scan: $c files in $td, inb4 thread iteration".NL);
	foreach ($files as $fn) if (
		is_file($path = $td.$fn)
	&&	($f = data_cache($path))
	) {
		$adm = preg_match(DATA_PAT_TRD_MOD, $fn, $m_adm);
		$act = preg_match(DATA_PAT_TRD_PLAY, $fn, $m_act);
		if (!$adm && !$act) continue;

		$i = $m_adm['id'];
		$frz = !!$m_adm['stopped'];
		$repf = "$tr$i$g";
		if (
			GOD
		||	(MOD && ($frz || ($act && is_file($repf))))	//* <- frozen/reported for mods
		||	($act && $u_flag['see'])			//* <- any active for seers
		||	(
				($act || ($frz && NOT_MOD_SEE_STOPPED_TRD))
			&&	($show_unknown || data_thread_has_posts_by($f, $u_num))
			)
		) {
			$changes[] = $t = filemtime($path);
			if ($last < $t) $last = $t;
			$last_post_time = 0;
			$posts = array();
			foreach (mb_split_filter($f, NL) as $line) if (false !== mb_strpos($line = trim($line), $sep)) {
				$tab = mb_split($sep, $line);
				$t = intval($tab[0]);
				if ($last < $t) $last = $t;
				if ($last_post_time < $t) $last_post_time = $t;
				$u = $tab[1];
				$f = ($u == $u_num?'u':0);
				if (!$f && MOD) {		//* <- mods see other's status as color
					if (!isset($data_cache_u[$u])) {
						$data_cache_u[$u] = 0;
						if ($flags = data_get_user_flags($u)) {
							foreach ($u_marks as $k) if (isset($flags[$k])) {
								$data_cache_u[$u] = $k[0];
								break;
							}
						}
						data_unlock(LK_USER.$u);
					}
					$f = $data_cache_u[$u];
				}
				$t = array(
					'flag' => $f
				,	'user' => $u
				,	'time' => $t
				,	'post' => $tab[3]
				);
				if ($tab[2]) {
					$t['pic'] = true;
					if (count($tab) > 4) $t['used'] = $tab[4];
					if (count($tab) > 5) $t['browser'] = $tab[5];
				}
				$posts[] = $t;
			}
			$f = $m_adm['deleted'] ?: $m_adm['stopped'] ?: (data_is_thread_full($m_act['pics'])?'f':'');
			$threads[$tid = "$last_post_time/$i$f"] = $posts;

			if ((MOD || $frz || NOT_MOD_SEE_ACTIVE_TRD_REPORTS) && is_file($repf)) {
				$repl = array();
				foreach (get_file_lines($repf) as $line) if (
					trim_bom($line)
				&&	count($tab = mb_split($sep, $line, 4)) > 3
				) {
					$t = intval($tab[0]);
					if ($last < $t) $last = $t;
					$repl
					[$tab[1]-1]	//* <- row (postnum) saved starting with 1; $posts[] - with zero
					[$tab[2]]	//* <- column (left/right)
					[$t]		//* <- time
					= $tab[3];	//* <- content
				}
				if ($repl) $reports[$tid] = $repl;
			}
		}
if (TIME_PARTS) time_check_point("done trd $fn, last = $last");
	}
	data_unlock($lk);

	return $threads ? array(
		'last' => $last
	,	'threads' => $threads
	,	'reports' => $reports
	,	'changes' => $changes
	) : $threads;
}

function data_check_my_task($aim = false) {
	global $u_num, $u_flag, $u_task, $u_t_f, $room, $target;
	if (!$target) $target = array('time' => T0);
	if ($u_flag['nop']) return '';

	data_lock(LK_ROOM.$room);

	$d = DATA_DIR_USER;
	$e = DATA_U_TASK;
	$g = GAME_TYPE_DEFAULT;
	$sep = '	';
	$u_task = get_file_lines($u_t_f = "$d$e/$u_num.$e");
	foreach ($u_task as $k => $line) if (
		false !== mb_strpos($line, $sep)
	&&	(list($t, $r, $f, $p) = mb_split($sep, $line, 4))
	&&	$r && $f && $p
	) {
		if ($g && false === mb_strpos($r, '/')) $r = "$g/$r";
		if ($r == $room) {
			if (!$target || !$target['task']) $target = array(
				'time'	=> $t
			,	'thread'=> $f
			,	'posts'	=> ($f && count($c = mb_split_filter($f)) > 2 ? $c[2] : 0)
			,	'post'	=> $p
			,	'pic'	=> (false === ($i = mb_strrpos_after($p, $sep)))
			,	'task'	=> ($i ? mb_substr($p, $i) : $p)
			);
			unset($u_task[$k]);
		}
	} else unset($u_task[$k]);

	$f = $target['thread'];
	$p = $target['post'];

//* auto check for posting or selecting new task:
	if ($aim) {
		if ($aim === DATA_U_TASK_CHANGE && $f && $p) array_unshift($u_task, "$aim	$room	$f	$p");
		if ($aim === DATA_U_TASK_CHANGE || !intval($aim)) {
			$t = implode(NL, $u_task);
			return file_put_mkdir($u_t_f, DATA_LOG_START.$t);
		}
		if (
			$f
		&&	($own = reset(mb_split_filter($f)))
		&&	preg_match(DATA_PAT_TRD_PLAY, $own, $m)
		) {
			$target['deadline'] = $m['hold_t'];
		}
		return $f;
	}

//* manual check for work in progress:
	$d = DATA_DIR_ROOM."$room/".DATA_SUB_TRD;
	if (!is_dir($d)) return 'no_room';

	if (!(
		$f && $p
	&&	(list($own, $dropped, $c) = mb_split_filter($f))
	&&	preg_match(DATA_PAT_TRD_PLAY, $own, $m)
	)) return 'no_task';

	$e = DATA_LOG_EXT;
	$i = $m['id'];
	$a = array(
		$own     => 'task_owned'	//* <- retake for new interval
	,	$dropped => 'task_reclaim'	//* <- dropped by others
	,	"$i$e"   => 'task_reclaim'	//* <- legacy dropped format, without last post user/time
	);
	foreach ($a as $f => $status) if (is_file($f = $d.$f)) {

//* update time limit to hold:
		$tm = $m['hold_t'];
		$tt = $target['time'];
		$td = ($target['pic'] ? TARGET_DESC_TIME : TARGET_DRAW_TIME);
		if ($tm && $tt && ($td < $tm - $tt)) $td = TARGET_LONG_TIME;
		$t = $target['deadline'] = T0 + $td;

//* rename thread file:
		$t = data_get_thread_name_tail(array($t, $u_num));
		$taken = "$i$t$e";
		rename($f, $d.$taken);

//* update user task list:
		$f = $target['thread'] = "$taken/$dropped/$c";
		array_unshift($u_task, "$tt	$room	$f	$p");
		$t = implode(NL, $u_task);
		file_put_mkdir($u_t_f, DATA_LOG_START.$t);

		return array($status, $td);
	}
	return 'task_let_go';
}

function data_aim($change = false, $dont_change = false, $skip_list = false, $unknown_1st = false) {
	global $u_num, $u_flag, $u_task, $u_t_f, $room, $room_type, $target;
	$d = DATA_DIR_ROOM."$room/".DATA_SUB_TRD;
	$e = DATA_LOG_EXT;
	$target = array();
	$new_target = array('time' => T0);
	if (
		FROZEN_HELL
	||	$room_type['single_active_thread']
	||	$u_flag['nop']
	||	!is_dir($d)
	) return $target ?: $new_target;

//* check personal target list:
	$drop = ($change === ARG_DROP ? 'drop' : false);
	$tt = data_check_my_task($drop ?: true);

	if (POST) return $target;

	if ($tt) list($own, $dropped) = mb_split_filter($tt);
	if (!$change) $change = ($target['time'] === DATA_U_TASK_CHANGE);
	$change_from = ($change ? array($own, $dropped, "$i$e") : array());
	$alt = !!$room_type['alternate_reply_type'];
	$counts = array();
	$u_own = array();
	$free_tasks = array();

//* scan threads; ignore skipped, full or held by others:
	foreach (get_dir_contents($d) as $f) if (
		preg_match(DATA_PAT_TRD_PLAY, $f, $m)
	&&	is_file($path = $d.$f)
	) {
		$i = $m['id'];
		if (
			$m['hold_u'] == $u_num
		||	in_array($f, $change_from)
		) {
			$u_own[$f] = $i;			//* <- own current target excluded
		} else if (
			!(is_array($skip_list) && in_array($i, $skip_list))
		&&	intval($m['hold_t']) < T0		//* <- other's target expired
		&&	!data_is_thread_full($m['pics'])
		&&	($room_type['allow_reply_to_self'] || data_get_last_post_u(data_cache($path)) != $u_num)
		) {
			$type = (data_is_thread_last_post_pic($path) ? ARG_DESC : ARG_DRAW);
			$free_tasks['any'][$f] = $i;
			$free_tasks[$type][$f] = $i;
			if ($unknown_1st && !data_thread_has_posts_by($path, $u_num)) {
				$free_tasks['any_unknown'][$f] = $i;
				$free_tasks[$type.'_unknown'][$f] = $i;
			}
			if (!data_thread_has_posts_by($path, $u_num, DATA_FLAG_POST_IMG)) $free_tasks['any_undrawn'][$f] = $i;
		}
	}

//* invert type for change:
	if ($alt) {
		if ($change === ARG_DESC) $change = ARG_DRAW; else
		if ($change === ARG_DRAW) $change = ARG_DESC; else $change = 0;
	} else $change = 0;

//* throw away irrelevant pools:
	$dont_count = array('undrawn', 'unknown');
	foreach ($free_tasks as $k => $v) {
		$typed = (is_prefix($k, ARG_DESC) || is_prefix($k, ARG_DRAW));

		if (!$alt && $typed) goto dont_count;
		foreach ($dont_count as $x) if (is_postfix($k, $x)) goto dont_count;

		$counts[$k] = count($v);
	dont_count:
		if (
			$change
			? (is_prefix($k, $change) || is_prefix($k, 'any'))
			: $typed
		) unset($free_tasks[$k]);
	}
	$target['count_free_tasks'] = $counts;

	if ($dont_change) return $target;

//* change task if thread is missing or taken, or enough time passed since taking last task:
	$f = $own ?: '';
	if (!(
		$tt
	&&	preg_match(DATA_PAT_TRD_PLAY, $own, $m)
	&&	strlen($i = $m['id'])
	&&	!(is_array($skip_list) && in_array($i, $skip_list))
	&&	($own_exists = (
			is_file($path = $d.$own)
		||	is_file($path = $d.$dropped)
		||	is_file($path = "$d$i$e")
		))
	&&	!($change || $change_from || $drop)
	&&	($t = intval($target['time']))
	&&	(T0 < $t + TARGET_CHANGE_TIME)
	&&	($room_type['allow_reply_to_self'] || data_get_last_post_u(data_cache($path)) != $u_num)
	)) {
//* add empty task to selection, unless current is empty:
		if (!$drop && $tt && !data_is_thread_cap()) $free_tasks['any'][] = '';

//* get random target from top-preferred pool:
		if ($drop || !(
			ksort($free_tasks)
		&&	($fa = end($free_tasks))
		&&	($f = array_rand($fa))
		&&	is_file($path = $d.$f)
		)) {
			$f = '';
		}

//* return own back to counts:
		if ($f != $own && $own_exists && $target['task']) {
			++$counts[$target['pic']?'desc':'draw'];
		}

//* forget the old:
		if (!strlen($f)) {
			$target = $new_target;
		} else
		if ($f != $own) {
			$target = $new_target;
			$i = $fa[$f];
			$t = trim_bom(data_cache($path));
			$b = data_get_thread_name_tail($t, false);
			$c = $target['posts'] = mb_substr_count($t, DATA_MARK_IMG) + mb_substr_count($t, DATA_MARK_TXT);

//* get target text to display:
			if ($room_type['single_thread_task']) {
				if ($p = mb_strpos($t, NL)) $t = mb_substr($t, 0, $p);	//* <- only first post
			}
			$last_txt = mb_strrpos_after($t, DATA_MARK_TXT);
			$last_pic = mb_strrpos_after($t, DATA_MARK_IMG);
			if ($last_txt < $last_pic) {
				$type = 'desc';
				$target['pic' ] = $t = mb_strpos($p = mb_substr($t, $last_pic), '	');
				$target['task'] = $p = mb_substr($p, 0, $t);		//* <- only filename
			} else {
				$type = 'draw';
				$p = mb_strrpos_after($t, NL);
				$p = (false === $p ? $t : mb_substr($t, $p));
				$target['task'] = mb_substr($t, $last_txt);		//* <- only text
				$target['post'] = $p;					//* <- full last line
			}
			if ($counts[$type] > 0) --$counts[$type];
			$target['deadline'] = $t = T0 + get_const('TARGET_'.$type.'_TIME');

//* rename new target as taken (locked):
			$t = data_get_thread_name_tail(array($t, $u_num));
			$taken = "$i$t$e";
			$dropped = "$i$b$e";
			$t = $target['thread'] = "$taken/$dropped/$c";
			data_cache_file_rename($path, $d.$taken);
			array_unshift($u_task, T0."	$room	$t	$p");
		}

		$target['count_free_tasks'] = $counts;
	}

	if ($f != $own || !strlen($f) !== !strlen($tt)) {

//* save new target to personal list:
		if ($t = implode(NL, $u_task)) {
			file_put_mkdir($u_t_f, DATA_LOG_START.$t);
		} else unlink($u_t_f);

//* rename old targets as dropped (unlocked):
		if ($u_own && (!strlen($f) || ($f != $own))) foreach ($u_own as $f => $i) {
			$t = data_get_thread_name_tail(data_cache($f = $d.$f), false);
			data_cache_file_rename($f, "$d$i$t$e");
		}

		$target['changed'] = 1;
	}

	return $target;
}

function data_log_post($post) {
	global $u_num, $room, $room_type, $target;
	$d = DATA_DIR_ROOM."$room/".DATA_SUB_TRD;
	$e = DATA_LOG_EXT;
	$u = T0."	$u_num";
	$result = array();

	if ($pic = is_array($post)) $post[0] = get_post_pic_field_with_fixed_info($post[0]);
	$post_type = ($pic?'image':'text');
	$post = $u.(
		$pic
		? DATA_MARK_IMG.implode('	', $post)
		: DATA_MARK_TXT.$post
	);

	if ($change = (
		$target
	&&	($t = $target['time'])
	&&	$t === DATA_U_TASK_CHANGE
	)) $target = array();

//* thread exists and not full/taken:
	if ((
		$room_type['single_active_thread']
	) ? (
		($i = get_dir_top_file_id($d, $e))		//* <- last not yet full
	&&	is_file($f = "$d$i$e")
	) : (
		!$change
	&&	($tt = $target['thread'])
	&&	(list($own, $dropped) = mb_split_filter($tt))
	&&	preg_match(DATA_PAT_TRD_PLAY, $own, $m)
	&&	strlen($i = $m['id'])
	&&	(!($h = $m['hold_u']) || $h == $u_num)		//* <- not taken by others
	&&	(
			is_file($f = $d.$own)
		||	is_file($f = $d.$dropped)
		||	is_file($f = "$d$i$e")
		)
	&&	(
			$room_type['allow_reply_to_self']
		||	data_get_last_post_u(data_cache($f)) != $u_num
		)
	)) {
		$post_type .= '_reply';
	} else {
		$post_type .= ($target['post']?'_reply':'_op');
		$new = 1;
	}

//* check if post type allowed:
	if (!$room_type["allow_$post_type"]) {
		$result[ARG_DENY."_$post_type"] = 1;
		return $result;
	}

	if ($new) {
//* archive old full threads before creating new:
		if ($a = data_archive_ready_go()) $result['arch'] = $a;

//* check if not too many existing threads:
//* still possible to post over the cap, e.g. if user finished drawing too late:
		if (
			!$pic
		&&	($i = data_is_thread_cap())
		) {
			$result['cap'] = $i;

			if ($a) data_post_refresh();

			return $result;
		}

//* prepend a task copy or placeholder post, if needed:
		if ($fork = $target['post']) {
			$result['fork'] = 1;
		} else
		if ($pic) {
			$fork = (T0 - 1)."	$u_num".DATA_MARK_TXT.NOR;
			if ($target['task']) $fork .= '<!-- '.htmlspecialchars("$target[time]: $target[task]").' -->';
		}
		if ($fork) $post = $fork.NL.$post;

//* create new thread, thread IDs start at 1 (was at 0 before):
		data_put_count($i = data_get_count()+1);
		if ($i <= 1) data_set_u_flag($u_num, "mod_$room", 1);

		$t = data_get_thread_name_tail($post);
		$f = $new = "$d$i$t$e";
	}

//* write the post:
	if ($result['post'] = data_log($f, $post)) {
		if (!$new) {
			$t = file_get_contents($f);
			$t = data_get_thread_name_tail($t);
			if (rename($f, $new = "$d$i$t$e")) $f = $new;
		}

//* archive full threads after each post:
		if (!$room_type['arch_wait'] && !$result['arch'] && ($a = data_archive_ready_go())) {
			$result['arch'] = $a;
		}

		$GLOBALS['data_thread_file_path'] = (is_file($f) ? $f : $a['done']);
		data_check_my_task(DATA_U_TASK_CHANGE);
	}

	data_post_refresh();

	return $result;
}

function data_rename_last_pic($old, $new) {
	global $room;
	if ($new === $old || !strlen($f = $GLOBALS['data_thread_file_path'])) return;
	if (false === mb_strpos($f, DATA_LOG_EXT)) {
		require_once(NAMEPRFX.'.arch.php');
		return data_archive_rename_last_pic($old, $new, $f);
	}
	if (is_file($f)) {
		$t = file_get_contents($f);
		$pos_before = mb_strrpos_after($t, DATA_MARK_IMG);
		$pos_after = mb_strpos($t, '	', $pos_before);
		if (mb_substr($t, $pos_before, $pos_after - $pos_before) === $old) {
			$before = mb_substr($t, 0, $pos_before);
			$after = mb_substr($t, $pos_after);
			return file_put_contents($f, "$before$new$after");
		}
	}
}

?>
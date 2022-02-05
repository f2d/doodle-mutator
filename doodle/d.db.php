<?php

//* Constants only for internal use: ------------------------------------------

define('DATA_VERSION', '2018-01-15 18:44');	//* <- change this to autoupdate old data formats

define('DATA_ANNOUNCE_TYPES', [
	'anno',
	'stop',
	'room_anno',
	'room_stop',
	'new_game',
	'new_room',
	'new_data',
]);

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

define('DATA_DIR', 'data/');					//* <- all data not viewable directly by URL
define('DATA_DIR_LOCK', DATA_DIR.'lock_files/');		//* <- lock files, empty and disposable
define('DATA_DIR_ROOM', DATA_DIR.'rooms/');			//* <- keep separated, like rooms/meta/subtype/room_name
define('DATA_DIR_USER', DATA_DIR.'users/');			//* <- per user files
define('DATA_USERLIST', DATA_DIR.'users'.DATA_LOG_EXT);		//* <- user list filename
define('DATA_REF_LIST', DATA_DIR.'reflinks'.DATA_LOG_EXT);	//* <- reflinks list filename

define('DATA_LIST_PROCESS_AS_LINES', true);
define('DATA_USE_SHARED_MEM', function_exists('shmop_open'));
define('DATA_USERLIST_SHARED_MEM_KEY', 0x646F6F64);		//* <- "dood" in hex
define('DATA_LK_SHARED_MEM_USERLIST', 'data_mem_users');

define('DATA_U_ABOUT', 'about');
define('DATA_U_FLAG', 'flag');
define('DATA_U_IP', 'ip');
define('DATA_U_TASK', 'task');
define('DATA_U_TASK_KEEP', 'keep');
define('DATA_U_TASK_CHANGE', 'change');

define('DATA_LOG_START', BOM.NL);
define('DATA_FIELD_SEPARATOR', "\t");
define('DATA_MARK_TXT', "\t\t");
define('DATA_MARK_IMG', "\t<\t");

define('DATA_RE_LOG_EXT', mb_escape_regex(DATA_LOG_EXT));
define('DATA_RE_MARK_IMG', mb_escape_regex(DATA_MARK_IMG, '~', '\\s'));

define('DATA_PAT_IMG', '~
	(?P<Before>
		(?:<a\\s+href|<img\\s+src)="?
	|	'.DATA_RE_MARK_IMG.'
	)
	(?P<Path>[^">'.DATA_FIELD_SEPARATOR.']+)
	(?=[">'.DATA_FIELD_SEPARATOR.']|$)
~iux');

define('DATA_PAT_TRD_PLAY', '~^
	(?P<ThreadId>\d+)
	(?P<Etc>
		(?:\.
			(?:
				p(?P<PicsCount>\d+|f)
			|	l(?P<LastPostTime>\d+)
			|	a(?P<LastPostUser>\d+)
			|	t(?P<TaskHoldTime>\d+)
			|	u(?P<TaskHoldUser>\d+)
			|	.+
			)
		)+
	)?
	(?P<Ext>'.DATA_RE_LOG_EXT.')
$~iux');

define('DATA_PAT_TRD_MOD', '~^
	(?P<IsActive>
		(?P<ThreadId>\d+)
		(?P<Etc>\..+)?
		(?P<Ext>'.DATA_RE_LOG_EXT.')
	)
	(?P<IsInactive>
		(?:\.
			(?:
				(?P<IsStopped>s)top
			|	(?P<IsDeleted>d)el
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

	if (rename($from, $to)) {
		$data_cache_file[$to] = $data_cache_file[$from];
	}
}

//* ---------------------------------------------------------------------------

function data_fields_to_text_line(...$fields) {
	return implode(DATA_FIELD_SEPARATOR, $fields);
}

function data_post_refresh($r = '') {
	global $room;

	if ($r === true) {
		foreach (get_dir_rooms(DATA_DIR_ROOM) as $r) if ($r = data_post_refresh($r)) {
			$report .= ($report ? NL : '').$r;
		}
	} else
	if ($r || ($r = $room)) {
		data_lock(LK_ROOM.$r);

		if (is_file($file_path = DATA_DIR_ROOM.$r.'/'.COUNT_POST.DATA_COUNT_EXT)) {
			$result = (unlink($file_path) ? 'deleted' : 'cannot delete');

			return "$result $file_path";
		}
	}

	return $report;
}

function data_log($file_path, $line, $params = null) {
	if (is_array($params)) {
		extract($params);
	}

	if ($old = is_file($file_path)) {
		$old_size = filesize($file_path);
	}

	$append = !($overwrite ?? false);

	$text_to_write = (
		$append && $old_size
		? NL
		: ($empty_file_prefix ?? DATA_LOG_START)
	).$line;

	$written = file_put_contents(
		mkdir_if_none($file_path)
	,	$text_to_write
	,	($append ? FILE_APPEND : 0)
	);

	if (!$written) {
		if ($old) {
			$log = "Cannot write to existing $file_path (possibly wrong file permissions)";

			if (
				rename($file_path, $old = $file_path.'.old'.T0.'.bak')
			&&	($written = file_put_contents(
					$file_path
				,	(
						$append && $old_size
						? file_get_contents($old)
						: ''
					).$text_to_write
				))
			) {
				$del_result = (unlink($old) ? 'deleted' : 'cannot delete');
				$log .= NL."Renamed to $old ($old_size bytes, $del_result), made new file ($written bytes)";
			}
		} else {
			$log = "Cannot create $file_path (possibly wrong dir permissions, or not enough space)";
		}
	}

	if ($log && ($report_errors ?? true)) {
		data_log_action($log);
	}

	return $written;
}

function data_global_announce($type = 'all', $room_in_list = '') {
	global $u_key, $last_user, $room, $data_maintenance;

	if ($d = ($room_in_list ?: $room ?: '')) {
		$d = DATA_DIR_ROOM."$d/";
	}

//* usage 1: check top level flag standing:

	if (in_array($type, DATA_ANNOUNCE_TYPES)) {
		$f = $type.DATA_STATIC_EXT;

		if (is_file(DATA_DIR.$f)) {
			return -1;	//* <- global freeze
		}

		if ($d && is_file($d.$f)) {
			return 1;	//* <- room freeze
		}

		return 0;
	}

//* usage 2: get all contents, or last mod.date:

	if ($type === 'all') {
		$result = array();
	} else
	if ($type === 'last') {
		$result = (is_dir(DATA_DIR) ? filemtime(DATA_DIR) : 0);
	} else {
		return false;
	}

	foreach (DATA_ANNOUNCE_TYPES as $check_anno_type) {
		if (0 === strpos($check_anno_type, 'new')) {
			if (
				is_array($result)
			&&	(
					('new_game' === $check_anno_type && !$last_user && !is_file(DATA_USERLIST))
				||	('new_data' === $check_anno_type && !$room_in_list && $data_maintenance)
				||	('new_room' === $check_anno_type && $u_key && $d && !is_dir($d))
				)
			) {
				$result[$check_anno_type] = '';
			}

			continue;
		}
		if ($i = mb_strpos_after($check_anno_type, '_')) {
			if (!$d) {
				continue;
			}

			$f = $d.mb_substr($check_anno_type, $i);
		} else {
			if ($room_in_list) {
				continue;
			}

			$f = DATA_DIR.$check_anno_type;
		}
		if (is_file($f .= DATA_STATIC_EXT)) {
			if (is_array($result)) {
				if ($v = trim_bom(file_get_contents($f))) {
					$result[$check_anno_type] = $v;
				}
			} else {
				if (($v = filemtime($f)) && $result < $v) {
					$result = $v;
				}
			}
		}
	}

	return $result;
}

function data_lock($k, $is_writable = true) {
	global $lock;

	$d = DATA_DIR_LOCK;
	$e = DATA_LOCK_EXT;
	$i = 0;
	$lock_type = (
		$is_writable
		? LOCK_EX		//* <- exclusive, to read/write, waits for EX and SH
		: LOCK_SH		//* <- shared, read-only, waits only for EX release
	);

	if ($k === LK_ROOM) {
		$k = get_dir_rooms(DATA_DIR_ROOM, $k);
	}

	$keys = (array)$k;

	foreach ($keys as $k) if ($k) {
		if (!is_array($lock)) {
			$lock = array();
		}

		if (isset($lock[$k]) && ($v = $lock[$k])) {
			if (flock($v, $lock_type)) {
				++$i;
			} else {
				data_log_action("Unable to change lock $path to ex=$is_writable type!");
			}
		} else
		if (
			($v = fopen(mkdir_if_none("$d$k$e"), 'a'))
		&&	flock($v, $lock_type)	//* <- acquire the lock
		) {
			$lock[$k] = $v;
			++$i;
		} else {
			$n = data_log_action($m = "Unable to lock $path to ex=$is_writable type!");
			die("$m $n");
		}
	}

	return $i;
}

function data_unlock($k = '') {
	global $lock;

	if (!is_array($lock)) {
		return;
	}

	if ($k === LK_ROOM) {
		$k = get_dir_rooms(DATA_DIR_ROOM, $k);
	}

	$keys = (array)($k ?: array_keys($lock));

	foreach ($keys as $k) if ($v = (
		$lock[$k] ?:
		$lock[$k = LK_ROOM.$k]
	)) {
		flock($v, LOCK_UN);	//* <- release the lock
		fclose($v);
		unset($lock[$k]);
	}
}

//* ---------------------------------------------------------------------------

function data_fix($what = '') {
	global $cfg_game_type_dir;

	if (!$what) {
		$n = DATA_VERSION;
		$f = DATA_DIR.'version'.DATA_STATIC_EXT;

		data_lock($lk = LK_VERSION, false);

		if (
			!is_file($f)
		||	file_get_contents($f) !== $n
		) {
			$v = 'none';
			ignore_user_abort(true);

			data_lock($lk);

			if (
				!is_file($f)
			||	($v = file_get_contents($f)) !== $n
			) {
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

	if ($what === true) {
		$what = '';
	}

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
						if (rtrim($flag, '1234567890.')) {
							$flags[] = $flag;
						} else {
							$ips[] = ($ips ? '' : 'old').DATA_FIELD_SEPARATOR.$flag;
						}
					}

					$done .= NL.$i.DATA_FIELD_SEPARATOR.$f;

					foreach ($data_types as $x) {
						$s = $x.'s';
						if ($j = trim(implode(NL, $$s))) {
							$done .= "\t$s = ".count($$s);
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
							$x = (
								DATA_LOG_START
								.trim_bom(file_get_contents($f))
								.NL
								.trim_bom(file_get_contents($n))
							);

							if ($r = file_put_contents($n, $x)) {
								$del = (unlink($f) ? '' : ' not');
								$r = "combined $r bytes, old was$del deleted";
							} else {
								$r = 'failed to rewrite existing';
							}
						} else {
							$r = (rename($f, $n) ? 'OK' : 'failed to move');
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

					if ($t === 'reports') {
						$n = intval($n).$e;
					}

					$n = mkdir_if_none($dest.$n);
					$r = (rename($f, $n) ? 'OK' : 'failed to move');
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

				if ($g = GAME_TYPE_DEFAULT) {
					$g = "$g/";
				}

		//* gather old paths, from => to:

				$a = array(
					"$old/ref$e"	=> DATA_REF_LIST
				,	"$old/u$e"	=> DATA_USERLIST
				,	"$old/u"	=> DATA_DIR_USER
			//	,	"$old/l"	=> DATA_DIR_LOCK
			//	,	"$old/room"	=> DATA_DIR_ROOM
				,	"$old/actions"	=> DATA_DIR.DATA_SUB_ACT
				);

				foreach (DATA_ANNOUNCE_TYPES as $check_anno_type) {
					$a["$old/$check_anno_type$s"] = DATA_DIR.$check_anno_type.$s;
				}

				foreach (get_dir_contents($d = $old_meta = "$old/room/") as $room) {
					$a["$d$room"] = DATA_DIR_ROOM."$g$room";
				}

				foreach (get_dir_contents($d = DIR_ROOM) as $room) {
					$a["$d$room"] = DATA_DIR_ROOM."$g$room/".DATA_SUB_TRD;
				}

				foreach (get_dir_contents($d = DIR_ARCH) as $room) {
					$r = "$d$room";
					if ($g) {
						if (!in_array($room, $cfg_game_type_dir)) {
							$a[$r] = "$d$g$room";
						}
					} else {
						if (in_array($room, $cfg_game_type_dir) && is_dir($r.DIR_THUMB)) {
							$a[$r] = "$r$bak";
						}
					}
				}

		//* move them:

				foreach ($a as $f => $n) if (($f !== $n) && (is_file($f) || is_dir($f))) {
					delay_timeout();
					$i++;
					$n = trim($n, '/');

					if (is_file($n) || is_dir($n)) {
						$r = (
							rename($n, $b = "$n$bak")
							? 'kept for back up'
							: 'failed to move out'
						);
						$done .= NL."$i: $n -> $b = $r";
					}

					$r = (
						rename($f, mkdir_if_none($n))
						? 'OK'
						: 'failed to move'
					);
					$done .= NL."$i: $f -> $n = $r";
				}

		//* delete empty leftovers:

				foreach (array($old_meta, $old, DIR_ROOM) as $d) if (is_dir($d) && !get_dir_contents($d)) {
					$i++;
					$r = (rmdir($d) ? 'OK' : 'failed');
					$done .= NL."$i: old empty dir $d -> delete $r";
				}

				if ($i) {
					$done .= NL.($i = "done $i changes, $old -> $new");
					time_check_point($i);
				}
			} else {
				die(
					"Fatal error: could not create data folder \"$new\"!"
					.(
						$done
						? '<hr>Done: '.nl2br($done)
						: ''
					)
				);
			}
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
					$pic_end = mb_strpos($v, DATA_FIELD_SEPARATOR, $pic_start);
					$pic = mb_substr($v, $pic_start, $pic_end - $pic_start);
					$pic = get_post_pic_field_with_fixed_info($pic, 2);
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
					$done .= NL."$room/$i: $f -> $n = $r";
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

		if ($i = data_archive_rewrite(
			['recheck_img' => ['exists' => true]]
		)) {
			$done .= NL.$i;
			time_check_point($i);
		}
	}
	data_unlock(LK_MOD_ACT);

	return $done;
}

//* ---------------------------------------------------------------------------

function data_import_from_file($file_path) {
	return data_import_from_string($file_path, true);
}

function data_import_from_string($value, $is_file_path = false) {
	if (function_exists($func_name = DATA_FUNC_IMPORT)) {
		if ($is_file_path) {
			$value = file_get_contents($value);
		}

		return (
			$func_name === 'json_decode'
			? $func_name($value, true)
			: $func_name($value)
		);
	}

	if ($is_file_path) {
		return include($value);
	}
}

function data_export_to_file($file_path, $data_obj_to_save) {
	$content = data_export_to_string($data_obj_to_save, true, true);

	return file_put_contents($file_path, $content);
}

function data_export_to_string($value, $allow_file_fallback = false, $human_readable = false) {
	if (function_exists($func_name = DATA_FUNC_EXPORT)) {
		return (
			$func_name === 'json_encode'
			? $func_name($value, JSON_NUMERIC_CHECK | JSON_BIGINT_AS_STRING | ($human_readable ? JSON_PRETTY_PRINT : 0))
			: $func_name($value)
		);
	}

	if ($allow_file_fallback) {
		return '<?php return '.var_export($value, true).';?>';
	}
}

function data_get_null_terminated_string($value) {
	return "$value\0";
}

function data_read_null_terminated_string(&$value, $start_offset = 0) {
	$i = strpos($value, "\0", $start_offset);

	return (
		$i === false
		? $value
		: substr($value, $start_offset, $i)
	);
}

/* Note:
	Reallocating or deleting shared memory segment with single constant key crashes Apache 2.4 worker with PHP 7.4 module on Windows 10.
	Thus, change the key for each reallocation.
	This may cause leftover segments under abandoned keys, leaking memory.
*/

function data_get_cached_userlist_shared_key() {
	return (
		stripos(PHP_OS, 'WIN') === 0	//* <- https://stackoverflow.com/q/5879043/#comment93332238_5879078
		? filemtime(DATA_USERLIST)
		: DATA_USERLIST_SHARED_MEM_KEY
	);
}

/* Note:
	Use "@" prefix to hide this useless PHP warning, given before any segment exists:
	shmop_open(): unable to attach or create shared memory segment 'No error'
*/

function data_get_cached_userlist_handle($mem_size = 0) {
	if (
		!DATA_USE_SHARED_MEM
	||	!function_exists('shmop_open')
	) {
		return;
	}

	return (
		$mem_size > 0
		? @shmop_open(data_get_cached_userlist_shared_key(), 'c', 0660, $mem_size)
		: @shmop_open(data_get_cached_userlist_shared_key(), 'a', 0, 0)
	);
}

function data_get_cached_userlist() {
	if (
		!DATA_USE_SHARED_MEM
	||	!function_exists('shmop_read')
	) {
		return;
	}

	$result = null;
	$mem_content = data_get_cached_userlist_from_shared_memory();

	if (null !== $mem_content) {
		if (false !== $mem_content) {
			$data_content = data_read_null_terminated_string($mem_content);
			$result = data_import_from_string($data_content);
		}

		time_check_point('done shmop_read'.(
			false === $mem_content
			? ', result = false'
			: '('.strlen($mem_content)." bytes), filesize = $result[filesize], filemtime = $result[filemtime]"
		));
	}

	return $result;
}

function data_save_cached_userlist($data_obj) {
	if (
		!DATA_USE_SHARED_MEM
	||	!function_exists('shmop_write')
	||	!function_exists('shmop_delete')
	) {
		return;
	}

	time_check_point("inb4 shmop_delete + shmop_write: filesize = $data_obj[filesize], filemtime = $data_obj[filemtime]");

	$data_content = data_export_to_string($data_obj);
	$mem_content = data_get_null_terminated_string($data_content);
	$mem_size = strlen($mem_content);

	$result = data_clear_cached_userlist();

	if ($result !== null) {
		time_check_point("done shmop_delete() = $result");
	}

	$result = data_write_cached_userlist_to_shared_memory($mem_content, $mem_size);

	if ($result !== null) {
		if (IS_LOCALHOST) file_put_contents(DATA_USERLIST.'_shared_mem_content.txt', $mem_content);

		time_check_point("done shmop_write($mem_size bytes) = $result");
	}

	data_unlock(DATA_LK_SHARED_MEM_USERLIST);

	return $result;
}

function data_get_cached_userlist_from_shared_memory() {
	if (
		!DATA_USE_SHARED_MEM
	||	!function_exists('shmop_read')
	) {
		return;
	}

	$mem_content = null;

	data_lock(DATA_LK_SHARED_MEM_USERLIST, false);

	if ($shared_mem_handle = data_get_cached_userlist_handle()) {
		$mem_content = shmop_read($shared_mem_handle, 0, 0);

//* shmop_close() is deprecated since PHP 8.0 where shmop functions use objects instead of resources:

		if (
			PHP_MAJOR_VERSION < 8
		&&	function_exists('shmop_close')
		) {
			shmop_close($shared_mem_handle);
		}
	}

	data_unlock(DATA_LK_SHARED_MEM_USERLIST);

	return $mem_content;
}

function data_write_cached_userlist_to_shared_memory($mem_content, $mem_size) {
	if (
		!DATA_USE_SHARED_MEM
	||	!function_exists('shmop_write')
	) {
		return;
	}

	$result = null;

	data_lock(DATA_LK_SHARED_MEM_USERLIST, true);

	if ($shared_mem_handle = data_get_cached_userlist_handle($mem_size)) {
		$result = shmop_write($shared_mem_handle, $mem_content, 0);

		if (
			PHP_MAJOR_VERSION < 8
		&&	function_exists('shmop_close')
		) {
			shmop_close($shared_mem_handle);
		}
	}

	return $result;
}

function data_clear_cached_userlist() {
	if (
		!DATA_USE_SHARED_MEM
	||	!function_exists('shmop_delete')
	) {
		return;
	}

	$result = null;

	data_lock(DATA_LK_SHARED_MEM_USERLIST, true);

	if ($shared_mem_handle = data_get_cached_userlist_handle()) {
		$result = shmop_delete($shared_mem_handle);

		if (
			PHP_MAJOR_VERSION < 8
		&&	function_exists('shmop_close')
		) {
			shmop_close($shared_mem_handle);
		}
	}

	return $result;
}

//* ---------------------------------------------------------------------------

function data_check_user($u_key_requested, $reg = false) {
	global $u_key, $u_num, $u_flag, $usernames, $last_user;

	if (!strlen($u_key_requested)) {
		return;
	}

	data_lock($lk = LK_USERLIST, false);

	if (
		DATA_USE_SHARED_MEM
	&&	($cached_userlist = data_get_cached_userlist())
	&&	is_array($cached_userlist)
	&&	filesize(DATA_USERLIST) === $cached_userlist['filesize']
	&&	filemtime(DATA_USERLIST) === $cached_userlist['filemtime']
	) {
		$user_keys = $cached_userlist['keys'] ?: array('');
		$usernames = $cached_userlist['names'] ?: array('');

		$i = array_search($u_key_requested, $user_keys);
		$last_user = array_key_last($user_keys);	//* <- since PHP 7.3.0

		if (false !== $i) {
			$u_key = $user_keys[$i];
			$u_num = intval($i);
		}
	} else {
		if (DATA_USE_SHARED_MEM) {
			$user_keys = array('');
			$usernames = array('');
		}

		foreach (get_file_lines(DATA_USERLIST) as $line) if (false !== mb_strpos($line, DATA_FIELD_SEPARATOR)) {
			list($i, $k, $t, $name) = mb_split(DATA_FIELD_SEPARATOR, $line);
			$i = intval($i);

			if ($last_user < $i) {
				$last_user = $i;
			}

			if ($u_key_requested === $k) {
				$u_key = $k;
				$u_num = $i;

				if ($reg && !DATA_USE_SHARED_MEM) {
					break;
				}
			}

			if (DATA_USE_SHARED_MEM) {
				$user_keys[$i] = $k;
				$usernames[$i] = $name;
			} else
			if (!$reg) {
				$usernames[$i] = $name;
			}
		}

		if (DATA_USE_SHARED_MEM) {
			data_save_cached_userlist(array(
				'filesize' => filesize(DATA_USERLIST)
			,	'filemtime' => filemtime(DATA_USERLIST)
			,	'keys' => $user_keys
			,	'names' => $usernames
			));
		}
	}

	data_unlock($lk);

	if (!$reg) {
		$u_flag = data_get_user_flags($u_num);
	}

	return $u_num;
}

function data_log_user($u_key, $u_name) {
	global $last_user;

	$u_num = $last_user+1;
	$t = T0.'+'.M0;
	$data_to_add = data_fields_to_text_line($u_num, $u_key, $t, $u_name);

	data_lock($lk = LK_USERLIST);
	data_clear_cached_userlist();

	if ($result = data_log(DATA_USERLIST, $data_to_add)) {
		if (!$last_user) {
			data_set_u_flag($u_num, 'god', 1);	//* <- 1st registered = top supervisor
		}
	}

	data_unlock(DATA_LK_SHARED_MEM_USERLIST);
	data_unlock($lk);

	return $result;
}

function data_get_user_flags($u_num) {
	$result = array();

	if ($u_num) {
		$d = DATA_DIR_USER;
		$e = DATA_U_FLAG;

		data_lock(LK_USER.$u_num, false);

		foreach (get_file_lines("$d$e/$u_num.$e") as $line) if (strlen($line = trim_bom($line))) {
			$a = mb_split(DATA_FIELD_SEPARATOR, $line);
			$flag = end($a);
			$time = reset($a);
			$result[$flag] = $time;
		}
	}

	return $result;
}

function data_collect($f, $data, $unique = true) {
	$t = T0.'+'.M0;
	$padded_log_content = null;

	foreach ((array)$data as $v) {
		$v = DATA_FIELD_SEPARATOR.$v;

		if (
			!$unique
		||	!is_file($f)
		||	!strlen(
				$padded_log_content === null
				? ($padded_log_content = data_cache($f).NL)
				: $padded_log_content
			)
		||	false === mb_strpos($padded_log_content, $v.NL)
		) {
			data_log($f, $t.$v, ['empty_file_prefix' => '']);
		}
	}
}

function data_log_ip() {
	global $u_num;

	$d = DATA_DIR_USER;
	$e = DATA_U_IP;
	data_collect("$d$e/$u_num.$e", $_SERVER['REMOTE_ADDR']);
}

function data_log_ref($data) {
	if ($data) {
		data_lock($lk = LK_REF_LIST);	//* <- so parallel checks won't miss each other with duplicates
		data_collect(DATA_REF_LIST, $data);
		data_unlock($lk);
	}
}

function data_log_action($text, $dump = '') {	//* <- write to logs of administrative actions, filename by date
	global $u_num, $room;

	$f = ($room ? DATA_DIR_ROOM."$room/" : DATA_DIR).DATA_SUB_ACT.date('Y-m-d', T0).DATA_LOG_EXT;
	$t = T0.'+'.M0;
	$u = (
		get_const('GOD') ? 'g' : (
		get_const('MOD') ? 'm' : 'r'
	)).$u_num;

	if ($dump) {
		$text .= ': ['.indent($dump).']';
	}

	$data_to_add = data_fields_to_text_line($t, $u, $text);

	return data_log($f, $data_to_add, ['report_errors' => false]);
}

function data_log_report($a) {			//* <- write to user report logs, filename by thread
	global $u_num, $room;

	$d = ($room ? DATA_DIR_ROOM."$room/" : DATA_DIR);

//* find matching file:

	if ($text = $a['report'])
	foreach (get_dir_contents($t = $d.DATA_SUB_TRD) as $f) if (
		preg_match(DATA_PAT_TRD_MOD, $f, $match)
	&&	strlen($i = $match['ThreadId'])
	&&	($i == $a['thread'])
	) {

//* check permission:

		if (
			is_file($f = $t.$f)
		&&	(
				MOD

//* cannot report invisible:

			||	(
					preg_match(DATA_PAT_TRD_PLAY, $match['IsActive'], $active_match)
				&&	($u = intval($active_match['TaskHoldUser'])) > 0
				&&	$u == $u_num
				)
			||	data_thread_has_posts_by($f, $u_num)
			)
		) {

//* write changes:

			$act = 'report';
			if ($a['freeze'] || $a['stop']) {
				$act = 'freeze: '.(
					$match['IsInactive']
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

			$data_to_add = data_fields_to_text_line($t, $p, $s, $text);

			if (data_log($f, $data_to_add)) {
				data_post_refresh();

				return data_log_action($a[0].DATA_FIELD_SEPARATOR."$act: $text");
			}
		}
		break;
	}

	return 0;
}

function data_get_mod_log_file($f, $mt = false) {	//* <- (full_file_path, 1|0)
	if (is_file($f)) {
		if ($mt) {
			return filemtime($f);
		}

		if (DATA_LIST_PROCESS_AS_LINES) {
			$lines = get_file_lines($f);

			log_preg_last_error(false);

			return $lines;
		}

		return trim_bom(file_get_contents($f));
	}
}

function data_fix_mod_log_line_tabs($match) {
	global $usernames;

	if (
		($i = $match['UserId'])
	&&	($i = intval($i))
	&&	($name = $usernames[$i])
	) {
		$name = ": $name";
	} else {
		$name = '';
	}

	return data_fields_to_text_line(
		$match['Date']
	,	$match['User'].$name
	,	$match['Text']
	);
}

function data_get_mod_log($t = '', $mt = false) {	//* <- (Y-m-d|key_name, 1|0)
	global $room, $u_num;

//* single list of reflinks:

	if ($t === LK_REF_LIST) {
		$t = data_get_mod_log_file(DATA_REF_LIST, $mt);

		if (!$mt) {
			$t = str_or_array_replace(
				data_fields_to_text_line('~(\d+)([^\d\s]\V+)?', '(\V+)~u')
			,	data_fields_to_text_line('$1', '$3')	//* <- arrange data fields
			,	$t
			);
		}

		return str_or_array_replace_html_special_chars_to_str($t);
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
					$t = str_or_array_replace(
						data_fields_to_text_line('~^'.$i, '[^', ']+~mu')
					,	"$0, $fe: [$f]"
					,	$t
					);
				}
			}

			$t = str_or_array_replace(
				data_fields_to_text_line('~(\V+)', '(\V+)', '(\V+)\+\V+', '(\V+?)~Uu')
			,	data_fields_to_text_line('$3,$1', '$4', '$2')	//* <- arrange data fields
			,	$t
			);
		}

		return str_or_array_replace_html_special_chars_to_str($t);
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

		foreach ($rooms as $each_room) if (
			($p = ($each_room ? "$d$each_room/" : DATA_DIR))
		&&	($v = data_get_mod_log_file("$p$s$t$e", $mt))
		) {

//* a) get contents to show:

			if (!$mt) {
				$k = $each_room ?: '*';
				$v = trim_bom(str_or_array_replace_html_special_chars_to_str($v));
				$v = "
room = $k".
preg_replace_callback('~
	(?<=^|\v)
	(?P<Date>\d\S*)
	\s+
	(?P<User>
		(?P<UserType>[^\s\d]*)?
		(?P<UserId>\d*)
		(?P<UserEtc>[^\s\d]\S*)?
	)
	\s+
	(?P<Text>\S+)
~ux', 'data_fix_mod_log_line_tabs',
//preg_replace('~(\v\S+)\s+(\S+)\s+~u', data_fields_to_text_line('$1', '$2', ''),	//* <- arrange data fields
preg_replace('~\h+~u', ' ',
preg_replace('~(?:^|<br[^>]*>)(\d+)([^\d\s]\S+)\s~ui', NL.'$1'.DATA_FIELD_SEPARATOR,	//* <- keep multiline entries atomic
preg_replace('~\v+~u', '<br>',
$v
))));

//* check each message author and timestamp:

				if ($t0) {
					$v = mb_split(NL, $v);
					$v = array_filter(array_map(function($line) use ($t0, $u_num) {
						$tab = mb_split(DATA_FIELD_SEPARATOR.'\D*', $line, 3);

						return (
							(
								intval($tab[0]) == $t0
							&&	intval($tab[1]) == $u_num
							)
							? $tab[2]
							: ''
						);
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
			if ($t0) {
				return $a;
			}

			ksort($a);

			return implode(NL, $a);
		}
	} else {

//* list of all dates with existing logs:

		$a = array();
		foreach ($rooms as $each_room)
		foreach (get_dir_contents(($each_room ? "$d$each_room/" : DATA_DIR).$s) as $f) {
			if (preg_match(PAT_DATE, $f, $match)) {
				$a[$match['YearMonth']][$match['Day']] = $match['Day'];
			}
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

function data_get_thread_content($t) {
	return (
		false === mb_strpos($t, DATA_FIELD_SEPARATOR)
		? data_cache($t)
		: $t
	);
}
function data_is_thread_full($n) {
	return (
		$n === 'f'
	||	intval($n) >= TRD_MAX_POSTS
	);
}

function data_is_thread_last_post_pic($t) {
	$t = trim_bom(data_get_thread_content($t));
	$last_txt = mb_strrpos_after($t, DATA_MARK_TXT);
	$last_pic = mb_strrpos_after($t, DATA_MARK_IMG);

	return $last_txt < $last_pic;
}

function data_is_task_final($t, $room_type) {
	$t = data_get_thread_content($t);
	$pics = mb_substr_count($t, DATA_MARK_IMG);

	return (
		$pics + 1 === TRD_MAX_POSTS
	&&	(
			!$room_type['alternate_reply_type']
		||	!data_is_thread_last_post_pic($t)
		)
	);
}

function data_is_thread_cap($r = '', $for_pic_post = false) {
	$limit = ($for_pic_post ? 2 : 1) * TRD_MAX_PER_ROOM;

	if ($r || ($r = $GLOBALS['room'])) {
		foreach (get_dir_contents(DATA_DIR_ROOM."$r/".DATA_SUB_TRD) as $f) if (
			preg_match(DATA_PAT_TRD_MOD, $f, $match)
		&&	!$match['IsDeleted']				//* <- "burnt" not counted
		&&	++$n >= $limit
		) {
			return $n;
		}
	}

	return 0;
}

function data_thread_has_posts_by($t, $u_num, $flags = DATA_FLAG_POST_ANY) {
	$t = data_get_thread_content($t);

	if ($flags & DATA_FLAG_POST_ANY) {
		$t = mb_str_replace(DATA_MARK_IMG, DATA_MARK_TXT, $t);
	}

	$padded_u_num = DATA_FIELD_SEPARATOR.$u_num.(
		$flags & DATA_FLAG_POST_IMG
		? DATA_MARK_IMG
		: DATA_MARK_TXT
	);

	return false !== mb_strpos($t, $padded_u_num);
}

function data_get_last_post_u($t) {
	return data_get_tab_by_file_line($t, 'user', -1);
}

function data_get_last_post_time($t) {
	if (is_array($t)) {
		return intval(
			$t['LastPostTime']
			?: (
				$t['TaskHoldUser']
				? 0
				: $t['TaskHoldTime']	//* <- for legacy time mark format
			)
		);
	}

	return intval(mb_substr($t, mb_strrpos_after($t, NL)));
}


function data_get_thread_pics($t, $full_path = false) {
	$a = array();
	$t = file_get_contents($t);

	if (preg_match(PAT_CONTENT, $t, $match)) {
		$t = $match['Content'];
	}

	if (preg_match_all(DATA_PAT_IMG, $t, $match)) {
		$m = array_map($full_path ? 'get_pic_subpath' : 'get_file_name', $match['Path']);

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
		if ($tt) {
			list($task_hold_time, $task_hold_user) = $t;
		}

//* unlocked thread:

	} else
	if ($t) {
		$pics_count = mb_substr_count($t, DATA_MARK_IMG);

		if ($tt) {
			$last_post_user = data_get_last_post_u($t);
			$last_post_time = data_get_last_post_time($t);
		} else {
			$count_pics = false;
		}

//* always mark a full thread:

		if (
			!$count_pics
		&&	$pics_count < TRD_MAX_POSTS
		) {
			unset($pics_count);
		}
	}

	$tail = '';

	foreach (array(
		'p' => 'pics_count'
	,	'l' => 'last_post_time'
	,	'a' => 'last_post_user'
	,	't' => 'task_hold_time'
	,	'u' => 'task_hold_user'
	) as $k => $v) if (isset($$v)) {
		$tail .= ".$k${$v}";
	}

	return $tail;
}

function data_put_thread_rename_if_free($f, $t = '', $match = '') {
	if (!file_put_mkdir($f, $t)) {
		return false;
	}

	if (is_array($match)) {

//* if task is free:

		if (
			$match['PicsCount']
		||	strpos($match['Etc'], 'p')
		||	mb_substr_count($t, DATA_MARK_IMG) >= TRD_MAX_POSTS
		) {
			$t = data_get_thread_name_tail($t);
		} else

//* if task is taken:

		if (
			$match['TaskHoldUser']
		||	strpos($match['Etc'], 'u')
		) {
			$t = '';
		} else {

//* if task was taken and dropped:

			$t = data_get_thread_name_tail($t, false);
		}

		if ($t) {
			$n = get_file_dir($f)."/$match[ThreadId]$t$match[Ext]$match[IsInactive]";
		}
	}

	if ($n && $n !== $f) {
		rename($f, $n);
	}

	return true;
}

function data_get_thread_by_num($n) {
	global $room, $data_cache_d, $data_cache_t;				//* <- ids/paths/content cached for batch processing

	if (!is_array($data_cache_t)) {
		$data_cache_t = array();
	} else
	if (
		($t = $data_cache_t[$n])
	&&	is_file($t[0].$t[1])
	) {
		return $t;
	}

	if (
		$data_cache_d
	||	is_dir($data_cache_d = DATA_DIR_ROOM."$room/".DATA_SUB_TRD)
	) {
		foreach (get_dir_contents($data_cache_d) as $filename) if (
			preg_match(DATA_PAT_TRD_MOD, $filename, $match)
		&&	strlen($n == $match['ThreadId'])
		) {
			return (
				$data_cache_t[$n] = array(
					$data_cache_d	//* <- dir/path/
				,	$filename
				,	$match		//* <- name parts array(num, etc, ext, .stop)
				)
			);
		}
	}

	return 0;
}

function data_get_tab_by_file_line($f, $tab = 0, $line = 0) {
	if (!$f) {
		return 0;
	}

//* find last in file:

	if (!$line) $f = file_get_contents($f); else
//	if ($line < 0) $f = data_cache($f); else

//* find in given line of file:

	if ($line > 0) {
		if ($line >= count($f = get_file_lines($f))) {
			return 0;
		}

		$f = $f[$line];
	}

	if ($tab === 'file') {
		return (
			false !== ($i = mb_strrpos_after($f, DATA_MARK_IMG))
			? mb_substr($f, $i, mb_strpos($f, DATA_FIELD_SEPARATOR, $i) - $i)
			: 0
		);
	}

	if ($tab === 'user') {
		$i = max(mb_strrpos($f, DATA_MARK_IMG), mb_strrpos($f, DATA_MARK_TXT));
		$f = mb_substr($f, 0, $i);

		return mb_substr($f, mb_strrpos_after($f, DATA_FIELD_SEPARATOR));
	}

	return 0;
}

function data_get_u_by_post($a) {
	global $data_cache_u;

	if (!is_array($data_cache_u)) {
		$data_cache_u = array();
	} else
	if ($p = $data_cache_u[$n = "$a[0]-$a[1]"]) {
		return $p;
	}

	if (list($d, $f) = data_get_thread_by_num($a[0])) {
		return ($data_cache_u[$n] = data_get_tab_by_file_line($d.$f, 'user', $a[1]));
	}

	return 0;
}

function data_set_u_flag($u, $name, $on = -1, $harakiri = false) {
	global $u_num, $u_flag, $room;

	if (is_array($u)) {
		$u = data_get_u_by_post($u);
	}

//* rename user:

	if ($on < 0) {
		$report = '';

		if ($u) {
			data_lock($lk = LK_USERLIST);
			if ($a = get_file_lines($f = DATA_USERLIST)) {
				foreach ($a as &$line) if (intval($line) == $u) {
					$i = mb_strrpos_after($line, DATA_FIELD_SEPARATOR);
					$old = mb_substr($line, $i);

					if ($old === $name) {
						$report = "$u already has this name: $old";
					} else {
						$line = mb_substr($line, 0, $i).$name;
						$content = implode(NL, $a);

						data_clear_cached_userlist();
						$result = file_put_mkdir($f, $content);
						data_unlock(DATA_LK_SHARED_MEM_USERLIST);

						$status = ($result ? 'renamed' : 'rename failed');
						$report = "$u $status: $old -> $name";
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

	if (!$u || !$name) {
		return 0;
	}

	$d = DATA_DIR_USER;
	$e = DATA_U_FLAG;
	$f = "$d$e/$u.$e";

	$replace = (
		$on
	&&	$on !== true
	&&	$on !== intval($on)
	&&	$on !== $name
		? $on
		: ''
	);

	$add = ($on && !$replace);
	$new_data_text = trim_bom(data_fields_to_text_line(T0.'+'.M0, $replace ?: $name));
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
				&&	$name == 'ban'				//* <- mods cannot ban mods
				&&	($flags['mod'] || $flags["mod_$room"])
				)
			||	(
					!$on
				&&	!$harakiri
				&&	$u == $u_num
				&&	substr($name,0,3) == 'mod'		//* <- mods cannot self-resign
				)
			)
		) {
			return -$u;
		}

//* check if set:

		foreach ($flags as $k => $v) if ($k == $name) {
			if ($add) {
				return $u;					//* <- add, exists
			}

			unset($flags[$k]);					//* <- remove
			++$removed;
		}

//* modify:

		if ($add) {
			data_log($f, $new_data_text);				//* <- add
		} else
		if (!$removed) {
			return 0;						//* <- remove, did not exist
		} else
		if (strlen(
			$new_data_text = data_get_flags_text_from_array(
				$flags
			,	($replace ? $new_data_text : '')
			)
		)) {
			data_log($f, $new_data_text, ['overwrite' => true]);
		} else {
			unlink($f);						//* <- removed all, nothing left
		}
	} else
	if ($add) {
		if (strlen($new_data_text)) {
			data_log($f, $new_data_text, ['overwrite' => true]);	//* <- add, new file
		} else {
			unlink($f);
		}
	} else {
		$u = 0;
	}

	return $u;
}

function data_get_flags_text_from_array($flags, ...$new_lines) {
	$lines = array();

	foreach ($flags as $k => $v) {
		$lines[] = (
			$k === $v
			? $k
			: data_fields_to_text_line($v, $k)
		);
	}

	foreach ($new_lines as $line) if (strlen($line)) {
		$lines[] = $line;
	}

	return trim_bom(implode(NL, $lines));
}

function data_replace_u_flag($f, $from, $to = '') {
	if (!$from || $from === $to) {
		return;
	}

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

	return data_fields_to_text_line($t, $match[0]);
}

function data_get_user_info($u_num) {
	$d = DATA_DIR_USER;
	$result = array();

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
		$result[$v] = preg_replace_callback('~^(\d{10,})\D~m', 'data_prepend_line_time', $f);
	}
	data_unlock($lk);

	return $result;
}

function data_get_user_profile($u_num, $array = true) {
	$d = DATA_DIR_USER;
	$e = DATA_U_ABOUT;
	$f = "$d$e/$u_num.$e";

	if (!$array) {
		return is_file($f);
	}

	data_lock($lk = LK_USER.$u_num, false);
	$lines = get_file_lines(is_file($f) ? $f : $f.DATA_HIDDEN_EXT);
	data_unlock($lk);

	$result = array();

	foreach ($lines as $line) {
		$tab = mb_split_filter($line, DATA_FIELD_SEPARATOR);
		$k = array_shift($tab);
		$k = trim($k, '[]():. =->');

		if ($k == 'email') {
			$v = array('addr' => array_shift($tab));
			foreach ($tab as $i) $v[$i] = $i;
		} else {
			$v = (count($tab) > 1 ? $tab : $tab[0]);
		}

		if ($v && trim_bom("$v")) {
			$result[$k] = $v;
		}
	}

	return $result;
}

function data_save_user_profile($a, $write = true) {
	global $u_num;

	$t = T0.'+'.M0;

	if (is_array($a) && $a) {
		if (is_array($m = $a['email'] ?? '')) {
			$a['email'] = data_fields_to_text_line(...$m);
		}

		$new = DATA_LOG_START;

		foreach (array(
			'last modified:' => $t
		,	'email:' => $a['email']
		,	'about:' => $a['about']
		) as $k => $v) {
			$new .= data_fields_to_text_line($k, $v).NL;
		}
	} else {
		$new = '';
	}

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
				$old = preg_replace('~('.NL.'last modified:'.DATA_FIELD_SEPARATOR.')\V*~u', '$1'.$t, $old);

				break;
			}
		}

		if ($old !== $new) {

			data_lock($lk = LK_USER.$u_num);
			foreach ($about_files as $f) if (is_file($f)) {
				unlink($f);
			}

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

	if ($get_content) {
		$threads = array();
	}

	if ($room_type) {
		$wait = !!$room_type['arch_wait'];
	}

	if ($room)
	foreach (get_dir_contents($d = DATA_DIR_ROOM."$room/".DATA_SUB_TRD) as $f) if (
		preg_match(DATA_PAT_TRD_PLAY, $f, $match)
	&&	data_is_thread_full($count = $match['PicsCount'])
	&&	!($wait && ($t = data_get_last_post_time($match)) && ($t + TRD_ARCH_TIME > T0))
	&&	is_file($f = $d.$f)
	) {
		if (!$get_content) {
			return true;
		}

		$last_time = 0;
		$a = array('name' => $f);

		foreach (get_file_lines($f) as $line) if (false !== mb_strpos($line, DATA_FIELD_SEPARATOR)) {
			$tab = mb_split(DATA_FIELD_SEPARATOR, $line);

			if ($tab[0]) {
				if ($last_time < $tab[0]) $last_time = $tab[0];
				$tab[0] = date(CONTENT_DATETIME_FORMAT, $tab[0]);
			}

			if ($tab[1] && ($i = intval($tab[1])) && isset($usernames[$i])) {
				$tab[1] = $usernames[$i];
			}

			if ($tab[2]) {

			//* image markup for scriptless view, autofixed by archive template:

				list($src, $res) = mb_split(';', $tab[3], 2);
				if (!isset($a['thumb'])) $a['thumb'] = get_pic_subpath($src);
				$tab[3] = '<img src="'.get_pic_url($src).'">'.($res ? ";$res" : '');
			}

			unset($tab[2], $tab[5]);

			$a['content'] .= NL.implode(DATA_FIELD_SEPARATOR, $tab);
		}

		if (
			$count === 'f'
		||	!$wait
		||	($last_time + TRD_ARCH_TIME < T0)
		) {
			$threads["$last_time-$f"] = $a;
		}
	}

	if ($get_content) {
		ksort($threads);

		return $threads;
	} else {
		return false;
	}
}

function data_archive_ready_go($reloading = false) {
	global $room, $room_type;

	$result = array();

	if ($room) {
		if ($reloading) {
			data_lock($lk = LK_ROOM.$room);
		}

		if ($threads = data_get_full_threads()) {
			require_once(NAMEPRFX.'.arch.php');

			$result = data_archive_full_threads($threads);

			if ($result && $reloading) {
				data_post_refresh();
			}
		}

		if ($reloading) {
			data_unlock($lk);
		}
	} else {
		foreach (get_dir_rooms(DATA_DIR_ROOM) as $each_room) {
			$room = $each_room;
			$room_type = get_room_type($room);
			$result[$room] = data_archive_ready_go($reloading);
		}

		$room = $room_type = false;
	}

	return $result;
}

function data_get_post_pic_info($post_content, $check_file = 1) {
	list($pic, $csv) = mb_split(';', $post_content, 2);

	return get_post_pic_info($pic, $csv, $check_file);
}

function data_find_by($terms, $caseless = true, $include_hidden = false) {
	global $r_type, $room, $usernames;

	$results = array();

	if (!$terms) {
		return $results;
	}

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
		) {
			$files[$path] = intval($f);
		}

if (TIME_PARTS) {$n_found = 0; time_check_point(count($files)." files in $dr");}

		foreach ($files as $path => $i) if (
			$t = trim_bom(file_get_contents($path))
		) {

if (TIME_PARTS) $n_check = '';

			foreach (mb_split_filter($t, NL) as $line) {
				$tab = mb_split(DATA_FIELD_SEPARATOR, $line);
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

		} else {

if (TIME_PARTS) time_check_point("$i: content not found in $path");

		}

		data_unlock($lk);
	}

	return $results;
}

//* Deletions: ----------------------------------------------------------------

function data_del_pic_file($f, $keep = '') {

	if (!is_file($f)) {
		return false;
	}

	$d = 'unlink';

	return (
		(
			$keep
			? rename($f, $d = $keep.get_file_name($f))
			: $d($f)
		)
		? $d
		: ''
	);
}

function data_del_pic($f, $keep = '') {
	global $room;

	if ($keep) {
		mkdir_if_none($keep = DIR_PICS_DEL."$room/");
	}

	foreach (array(get_pic_resized_path($f), $f) as $f) {
		$status = data_del_pic_file($f, $keep);
	}

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

			foreach ($files as $path) if (data_del_pic($path, $to_trash)) {
				$count[$k]++;
			}
		}

		if (unlink($f)) {
			$count['files']++;
			$f = DATA_DIR_ROOM."$room/".DATA_SUB_REP.intval(get_file_name($f)).DATA_LOG_EXT;

			if (is_file($f) && unlink($f)) {
				$count['reports']++;
			}
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

		if (rmdir($d)) {
			$count['dirs']++;
		}
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
	global $u_num, $u_flag, $r_type, $room, $merge, $cfg_game_type_dir;

	if (!MOD) {
		return 0;
	}

	$q = explode('+', array_shift($a));
	$o = array_shift($q);

	if (NO_MOD && !GOD && $o != 'room announce') {
		return 0;
	}

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
				if (rename($d.$f, "$d$m[ThreadId]$t$e")) $ok = 'OK';	//* <- put to wait
			} else {
				if (rename($d.$f, "$d$m[ThreadId].pf$e")) $ok = 'OK';	//* <- get ready
				if ($ok && !$un && is_array($r = data_archive_ready_go())) {
					foreach ($r as $k => $v) if ($v) {
						$ok .= ", $v $k";
					}

					$return = 'trd_arch';
				}
			}
			if ($ok) data_post_refresh();
		}
	} else
	if (substr($o,0,8) == 'freeze t') {
		if (
			(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&&	$f != ($n = $m['IsActive'].($un > 1 ? '.del' : ($un ? '' : '.stop')))
		&&	rename($d.$f, $d.$n)
		) {
			$ok = 'OK';
			data_post_refresh();
		}
	} else
	if (substr($o,0,8) == 'delete c') {
		if (
			(list($d,$f,$m) = data_get_thread_by_num($a[0]))
		&&	is_file($r = DATA_DIR_ROOM."$room/".DATA_SUB_REP."$m[ThreadId]$e")
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
			: ($bak = rename($d.$f, "$d$m[IsActive].del"))
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
					$merge[$m['ThreadId']] = trim_bom(implode(NL, array_slice($f, $ok)));
					$ok = "+t$m[ThreadId] p$ok-$old";
				} else $ok .= ' post not found';
			} else
			if (is_array($merge) && count($merge)) {
				$n = trim_bom($old = file_get_contents($f = $d.$f)).NL.implode(NL, $merge);
				$n = array_unique(mb_split_filter($n, NL));
				natsort($n);
				if ($old != ($new = DATA_LOG_START.trim_bom(implode(NL, $n)))) {
					data_put_thread_rename_if_free($f, $new, $m);
					$ok = "$m[ThreadId]<-".implode(',', array_keys($merge));
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
				file_put_mkdir("$d$ok$t$e$m[IsInactive]", $fst);//* <- put 1st half into new thread, un/frozen like old
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
				$keys = explode(',', 're,text,file,meta'.(GOD ? ',user,time,browser' : ''));
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

				if ($lsv) {
					$ok .= NL.'data = '.get_print_or_none($lsv);
					$tab = (
						$un < 2
						? array(T0, $u_num)			//* <- add, insert (own post from now, if not specified)
						: mb_split(DATA_FIELD_SEPARATOR, $old)	//* <- edit, replace (assume old post as valid)
					);

			//* timestamp/ID, accept digits > 0 only, or no change:

					foreach (array('time', 'user') as $i => $k)
					if (($v = $lsv[$k]) && !trim($v, '0123456789')) $tab[$i] = $v;

			//* text, just make a post and be done:

					if ($v = $lsv['text']) {
						$new = $tab[0].DATA_FIELD_SEPARATOR.$tab[1].DATA_MARK_TXT.format_post_text($v);
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
							$new = implode(DATA_FIELD_SEPARATOR, $tab);
						}
					} else {
						$new = implode(DATA_FIELD_SEPARATOR, $tab);
					}
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
	if ($o == 'give mod'		) $ok = data_set_u_flag($a, $room ? "mod_$room" : 'mod', !$un); else
	if (substr($o,0,4) == 'hara'	) $ok = data_set_u_flag($u_num, $room ? "mod_$room" : 'mod', 0, 1); else

	if (!GOD && $o != 'room announce') {
		return 0;
	} else

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
		$f = ($r ? DATA_DIR_ROOM."$room/" : DATA_DIR).($n ? 'anno' : 'stop').DATA_STATIC_EXT;
		$ok = (
			($n ? $msg : !$un)
			? ((file_put_mkdir($f, $msg) === strlen($msg)) ? ($msg ?: '-') : 0)
			: (is_file($f) && unlink($f))
		);
	} else

//* god left-side menu --------------------------------------------------------

	if ($o == 'rename room') {
		if (!($msg = trim_room($msg))) {
			return 0;
		}

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
				&&	($t = mkdir_if_none("$new/".DATA_SUB_TRD."$n$m[Etc]$e$m[IsInactive]"))
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
					$ok .= (
						",$f:"
						.(is_dir($new) && rename($new, $new.'.'.T0.'.old') ? 'old_bak+' : '')
						.(is_dir($old) && rename($old, $new) ? 1 : 0)
					);
				}
				$ok .= data_replace_u_flag(0, "mod_$room", "mod_$msg");
				data_post_refresh($room = $msg);
			}
		}
	} else
	if ($o == 'nuke room') {
		$del_pics = ($un ? 2 : 1);
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
	} else {

//* no action -----------------------------------------------------------------

		return 0;
	}

	if ($un) {
		$o .= '+'.end($q);
	}

	if (is_array($a)) {
		$a = implode('-', $a);
	}

	$logged = data_log_action($a.DATA_FIELD_SEPARATOR."$o: $ok");

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
				if (false === array_search($i, $a)) {
					$a[] = $i;
				}
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
	$mod_marks = array(
		'IsStopped' => 'stopped'
	,	'IsDeleted' => 'deleted'
	);
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
		&&	($data_obj = data_import_from_file($cf))
		&&	is_array($data_obj)
		&&	($last_time_in_room = $data_obj['last modified'])
		) {
			$c	= $data_obj['counts'];
			$mod	= $data_obj['marked'];
			$t = data_global_announce('last', $r);

			if ($last_time_in_room < $t) {
				$last_time_in_room = $t;
			}
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

				if ($last_post_time < $thread_time) {
					$last_post_time = $thread_time;
				}

				$count_trds ++;
				$count_desc += mb_substr_count($t, DATA_MARK_TXT);
				$count_pics += mb_substr_count($t, DATA_MARK_IMG);

				if (preg_match(DATA_PAT_TRD_PLAY, $f, $match)) {
					if (data_is_thread_full($match['PicsCount'])) {
						++$mod['full'];
					}
				} else
				if (preg_match(DATA_PAT_TRD_MOD, $f, $match)) {
					foreach ($mod_marks as $match_k => $mod_k) if ($match[$match_k]) {
						++$mod[$mod_k];
					}
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

			$data_obj_to_save = array(
				'last modified'	=> $last_time_in_room
			,	'counts'	=> $c
			,	'marked'	=> $mod
			);

			data_export_to_file($cf, $data_obj_to_save);
			data_unlock($lk);
		}

		if ($o = trim_bom(ob_get_clean())) {
			data_log_action("include($cf) buffer dump: $o");
		}

		if ($last < $last_time_in_room) {
			$last = $last_time_in_room;
		}

		if ($mod = array_filter($mod)) {
			if (!GOD) {
				unset($mod['deleted']);
			}

			if (
				NOT_MOD_SEE_ROOM_MARKERS
			||	GOD
			||	$u_flag['mod']
			||	$u_flag["mod_$r"]
			) {
				$c['marked'] = $mod;
			}
		}

		if ($t = data_global_announce('all', $r)) {
			$c['anno'] = $t;
		}

		$a[$r] = array_filter($c);

if (TIME_PARTS) time_check_point("done room $r");

	}

	return (
		!$a ? $a :
		array(
			'last' => $last
		,	'list' => $a
		)
	);
}

function data_get_visible_threads() {
	global $u_num, $u_flag, $room, $room_type, $data_cache_u;

	$show_unknown = !$room_type['hide_unknown_threads'];
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
		$is_admin_match = preg_match(DATA_PAT_TRD_MOD, $fn, $match);
		$is_active_match = preg_match(DATA_PAT_TRD_PLAY, $fn, $active_match);

		if (
			!$is_admin_match
		&&	!$is_active_match
		) {
			continue;
		}

		$i = $match['ThreadId'];
		$frz = !!$match['IsStopped'];
		$repf = "$tr$i$g";
		if (
			GOD
		||	(MOD && ($frz || ($is_active_match && is_file($repf))))	//* <- frozen/reported for mods
		||	($is_active_match && $u_flag['see'])			//* <- any active for seers
		||	(
				($is_active_match || ($frz && NOT_MOD_SEE_STOPPED_TRD))
			&&	($show_unknown || data_thread_has_posts_by($f, $u_num))
			)
		) {
			$changes[] = $t = filemtime($path);
			if ($last < $t) $last = $t;
			$last_post_time = 0;
			$posts = array();

			foreach (mb_split_filter($f, NL) as $line) if (false !== mb_strpos($line = trim($line), DATA_FIELD_SEPARATOR)) {
				$tab = mb_split(DATA_FIELD_SEPARATOR, $line);
				$t = intval($tab[0]);

				if ($last < $t) {
					$last = $t;
				}

				if ($last_post_time < $t) {
					$last_post_time = $t;
				}

				$u = $tab[1];
				$f = ($u == $u_num ? 'u' : 0);

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

			$f = (
				$match['IsStopped'] ?:
				$match['IsDeleted'] ?:
				(data_is_thread_full($active_match['PicsCount']) ? 'f' : '')
			);

			$threads[$tid = "$last_post_time/$i$f"] = $posts;

			if ((MOD || $frz || NOT_MOD_SEE_ACTIVE_TRD_REPORTS) && is_file($repf)) {
				$repl = array();

				foreach (get_file_lines($repf) as $line) if (
					trim_bom($line)
				&&	count($tab = mb_split(DATA_FIELD_SEPARATOR, $line, 4)) > 3
				) {
					$t = intval($tab[0]);

					if ($last < $t) {
						$last = $t;
					}

					$repl
					[$tab[1]-1]	//* <- row (postnum) saved starting with 1; $posts[] - with zero
					[$tab[2]]	//* <- column (left/right)
					[$t]		//* <- time
					= $tab[3];	//* <- content
				}

				if ($repl) {
					$reports[$tid] = $repl;
				}
			}
		}

if (TIME_PARTS) time_check_point("done trd $fn, last = $last");

	}
	data_unlock($lk);

	sort($changes);

	return (
		!$threads ? $threads :
		array(
			'last' => $last
		,	'threads' => $threads
		,	'reports' => $reports
		,	'changes' => $changes
		)
	);
}

function save_updated_task_list_file(...$new_task_data) {
	global $u_task, $u_t_f;

	$result = 'no context';

	if (strlen($u_t_f) && is_array($u_task)) {

		if (strlen($new_task_line = data_fields_to_text_line(...$new_task_data))) {
			array_unshift($u_task, $new_task_line);
		}

		if (strlen($task_list_text = implode(NL, $u_task))) {
			$result = (
				file_put_mkdir($u_t_f, DATA_LOG_START.$task_list_text)
				? true
				: count($u_task).' tasks left, but cannot save to task file'
			);
		} else {
			$result = (
				!is_file($u_t_f) || unlink($u_t_f)
				? true
				: 'no tasks left, but cannot remove task file'
			);
		}
	}

	if ($result === true) {
		return true;
	} else {
		data_log_action("save_updated_task_list_file: $result");

		return false;
	}
}

function data_check_my_task($aim = false) {
	global $u_num, $u_flag, $u_task, $u_t_f, $room, $target;

	if (!$target) {
		$target = array('time' => T0);
	}

	if ($u_flag['nop']) {
		return '';
	}

	data_lock(LK_ROOM.$room);

	$d = DATA_DIR_USER;
	$e = DATA_U_TASK;
	$g = GAME_TYPE_DEFAULT;
	$u_task = get_file_lines($u_t_f = "$d$e/$u_num.$e");

	foreach ($u_task as $k => $line) if (

//* check if line is a task:

		false !== mb_strpos($line, DATA_FIELD_SEPARATOR)
	&&	(list($t, $r, $thread, $post) = mb_split(DATA_FIELD_SEPARATOR, $line, 4))
	&&	$r && $thread && $post
	) {
		if ($g && false === mb_strpos($r, '/')) $r = "$g/$r";
		if ($r == $room) {

//* found user's task for current room:

			if (!$target || !$target['task']) $target = array(
				'drop'	=> (false !== strpos($t, DATA_U_TASK_CHANGE))
			,	'keep'	=> (false !== strpos($t, DATA_U_TASK_KEEP))
			,	'pic'	=> (false === ($i = mb_strrpos_after($post, DATA_FIELD_SEPARATOR)))
			,	'post'	=> $post
			,	'posts'	=> ($thread && count($posts_count = mb_split_filter($thread)) > 2 ? $posts_count[2] : 0)
			,	'task'	=> ($i ? mb_substr($post, $i) : $post)
			,	'thread'=> $thread
			,	'time'	=> intval($t)
			);

			$old_task_line = $line;
			unset($u_task[$k]);
		}
	} else {

//* remove non-task lines:

		unset($u_task[$k]);
	}

	$thread = $target['thread'];
	$post = $target['post'];
	$start_time = $target['time'];

//* auto check for posting or selecting new task:

	if ($aim) {
		if ($thread && $post) {

	//* keep task:

			if ($aim === ARG_KEEP) {
				if (!$target['keep']) {
					$target['keep'] = true;

					save_updated_task_list_file("$start_time/".DATA_U_TASK_KEEP, $room, $thread, $post);
				}
			}

	//* change task to another, or none:

			if ($aim === DATA_U_TASK_CHANGE) {
				save_updated_task_list_file("$start_time/".DATA_U_TASK_CHANGE, $room, $thread, $post);
			}
		} else {

	//* drop task, get none:

			if (
				$aim === DATA_U_TASK_CHANGE
			||	$aim === ARG_DROP
			) {
				save_updated_task_list_file();

				return;
			}
		}

	//* check current task deadline:

		if (
			$thread
		&&	($own = reset(mb_split_filter($thread)))
		&&	preg_match(DATA_PAT_TRD_PLAY, $own, $m)
		) {
			$target['deadline'] = $m['TaskHoldTime'];
		}

		return $thread;
	}

//* manual check for work in progress:

	$d = DATA_DIR_ROOM."$room/".DATA_SUB_TRD;
	if (!is_dir($d)) {
		return 'no_room';
	}

	if (!(
		$thread && $post
	&&	(list($own, $dropped, $posts_count) = mb_split_filter($thread))
	&&	preg_match(DATA_PAT_TRD_PLAY, $own, $m)
	)) {
		return 'no_task';
	}

	$e = DATA_LOG_EXT;
	$i = $m['ThreadId'];

	foreach (
		array(
			$own     => 'task_owned'	//* <- retake for new interval
		,	$dropped => 'task_reclaim'	//* <- dropped by others
		,	"$i$e"   => 'task_reclaim'	//* <- legacy dropped format, without last post user/time
		) as $f => $status
	) if (is_file($thread = $d.$f)) {

//* update time limit to keep:

		$tm = $m['TaskHoldTime'];
		$td = ($target['pic'] ? TARGET_DESC_TIME : TARGET_DRAW_TIME);
		if ($tm && $start_time && ($td < $tm - $start_time)) {
			$td = TARGET_LONG_TIME;
		}
		$t = $target['deadline'] = T0 + $td;

//* rename thread file:

		$t = data_get_thread_name_tail(array($t, $u_num));
		$taken = "$i$t$e";
		rename($thread, $d.$taken);

//* update user task list:

		if ($target['keep']) {
			$start_time = "$start_time/".DATA_U_TASK_KEEP;
		}

		if ($target['drop']) {
			$start_time = "$start_time/".DATA_U_TASK_CHANGE;
		}

		$thread = $target['thread'] = "$taken/$dropped/$posts_count";

		save_updated_task_list_file($start_time, $room, $thread, $post);

		return array($status, $td);
	}

	return 'task_let_go';
}

function data_use_any_when_only_desc_or_draw($task_lists_by_type) {

	if (
		!$task_lists_by_type
	||	!is_array($task_lists_by_type)
	) {
		return array();
	}

	$task_type_supersets = array(
		ARG_ANY => array(
			ARG_DESC,
			ARG_DRAW,
		),
		ARG_ANY.'_'.ARG_UNKNOWN => array(
			ARG_DESC.'_'.ARG_UNKNOWN,
			ARG_DRAW.'_'.ARG_UNKNOWN,
		),
	);

	$task_lists_as_text = array_map(function($a) { return implode(',', $a); }, $task_lists_by_type);

	foreach ($task_type_supersets as $generic_type => $task_type_subsets) if (
		($generic_tasks = $task_lists_as_text[$generic_type])
	) {
		foreach ($task_type_subsets as $specific_type) if (
			($specific_tasks = $task_lists_as_text[$specific_type])
		&&	($specific_tasks === $generic_tasks)
		) {
			$type_to_keep = (
				CHANGE_TASK_USE_MORE_SPECIFIC_TYPES_OF_IDENTICAL_LISTS
				? $specific_type
				: $generic_type
			);

			$type_to_remove = (
				CHANGE_TASK_USE_MORE_SPECIFIC_TYPES_OF_IDENTICAL_LISTS
				? $generic_type
				: $specific_type
			);

if (TIME_PARTS) time_check_point(
	'unset['.$type_to_remove
	.'], same as ['.$type_to_keep
	.'] = ['.$generic_tasks
	.']'
);

			unset($task_lists_by_type[$type_to_remove]);
		}
	}

if (TIME_PARTS) time_check_point(
	'task_lists_by_type = '.get_print_or_none($task_lists_by_type)
);

	return $task_lists_by_type;
}

function data_aim($task_changing_params = false) {
	global $u_num, $u_flag, $u_opts, $room, $room_type, $target;

	$d = DATA_DIR_ROOM."$room/".DATA_SUB_TRD;
	$e = DATA_LOG_EXT;
	$target = array();
	$new_target = array('time' => T0);

	if (
		FROZEN_HELL
	||	$room_type['single_active_thread']
	||	$u_flag['nop']
	||	!is_dir($d)
	) {
		return $target ?: $new_target;
	}

//* check arguments for task changing:

	if ($task_changing_params) {
		if (is_array($task_changing_params)) {
			extract($task_changing_params);
		} else {
			$what_change = $task_changing_params;
		}
	} else

//* never change for POST, unless directly requested to change:

	if (POST) {
		data_check_my_task(true);

		return $target;
	}

//* check extracted arguments:

	if (!is_array($skip_threads)) {
		$skip_threads = array();
	}

	$keep = ($what_change === ARG_KEEP ? ARG_KEEP : false);
	$drop = ($what_change === ARG_DROP ? ARG_DROP : false);

//* check personal target list:

	$old_target_thread = data_check_my_task($keep ?: $drop ?: true) ?: '';

	if ($old_target_thread) {
		list($own, $dropped) = mb_split_filter($old_target_thread);
	}

//* prevent automatic change if task is marked to keep and not skipped:

	if (
		$own
	&&	preg_match(DATA_PAT_TRD_PLAY, $own, $m)
	&&	($i = $m['ThreadId'])
	&&	in_array($i, $skip_threads)
	) {
		$skip_old_target = true;
		$target['keep'] = false;
		$what_change = ARG_CHANGE;
	}

	$asked_for_different_type = (
		$old_target_thread
	&&	(
			$target['pic']
			? ($what_change_to === ARG_DRAW)
			: ($what_change_to === ARG_DESC)
		)
	);

	$change = (
		(
			$what_change
		||	$target['drop']
		||	$asked_for_different_type
		)
		? ($what_change_to ?: ARG_ANY)
		: false
	);

	if (
		(!$change && $target['keep'])
	// ||	(T0 <= $target['time'] + 1)			//* <- only for testing
	) {
		$dont_change = true;
	}

//* prepare list of threads available for manual change:

	$change_from = (
		$change
	&&	$old_target_thread
		? array($own, $dropped, "$i$e")
		: array()
	);

	$prefer_unknown = !$u_opts['unknown'];
	$separate_final_tasks = !!$u_opts['final_task_notice'];
	$reply_type_must_be_alternate = !!$room_type['alternate_reply_type'];

	$free_task_lists = array();
	$u_own = array();

//* scan threads; ignore skipped, full or held by others:

	foreach (get_dir_contents($d) as $f) if (
		preg_match(DATA_PAT_TRD_PLAY, $f, $match)
	&&	is_file($path = $d.$f)
	) {
		$i = $match['ThreadId'];
		if (
			$match['TaskHoldUser'] == $u_num
		||	in_array($f, $change_from)
		) {
			$u_own[$f] = $i;			//* <- own current target excluded
		} else if (
			!in_array($i, $skip_threads)
		&&	intval($match['TaskHoldTime']) < T0	//* <- other's target expired
		&&	!data_is_thread_full($match['PicsCount'])
		&&	(
				$room_type['allow_reply_to_self']
			||	data_get_last_post_u(data_cache($path)) != $u_num
			)
		) {
			$is_desc_type = data_is_thread_last_post_pic($path);

			$type = (
				$is_desc_type
				? ARG_DESC
				: ARG_DRAW
			);

			$free_task_lists[ARG_ANY][$f] = $i;
			$free_task_lists[$type][$f] = $i;

			if (
				PICK_TASK_FROM_UNDESCRIBED_OR_UNDRAWN_THREADS
			&&	!data_thread_has_posts_by($path, $u_num, (
					$is_desc_type
					? DATA_FLAG_POST_TXT
					: DATA_FLAG_POST_IMG
				))
			) {
				$type_suffix = (
					$is_desc_type
					? ARG_UNDESCRIBED
					: ARG_UNDRAWN
				);

				// $free_task_lists[ARG_ANY.'_'.$type_suffix][$f] = $i;
				$free_task_lists[$type.'_'.$type_suffix][$f] = $i;
			}

			if (
				$prefer_unknown
			&&	!data_thread_has_posts_by($path, $u_num)
			) {
				$free_task_lists[ARG_ANY.'_'.ARG_UNKNOWN][$f] = $i;
				$free_task_lists[$type.'_'.ARG_UNKNOWN][$f] = $i;
			}

			if (
				$separate_final_tasks
			&&	data_is_task_final($path, $room_type)
			) {
				$free_task_lists[ARG_FINAL_TASK][$f] = $i;
			}
		}
	}

//* invert type for change:

	if (
		$reply_type_must_be_alternate
	&&	!$what_change_to
	) {
		if ($change === ARG_DESC) $what_change_to = ARG_DRAW; else
		if ($change === ARG_DRAW) $what_change_to = ARG_DESC;
	}

//* throw away irrelevant pools:

	$dont_count = array(
		ARG_UNDESCRIBED,
		ARG_UNDRAWN,
		// ARG_UNKNOWN,
		// ARG_FINAL_TASK,
	);

	$free_task_counts = array();
	$task_lists_to_pick = array();
	$task_lists_for_manual_change = data_use_any_when_only_desc_or_draw($free_task_lists);

	foreach ($task_lists_for_manual_change as $task_type => $tasks_by_type) {
		if (
			!$reply_type_must_be_alternate
		&&	(
				is_prefix($task_type, ARG_DESC)
			||	is_prefix($task_type, ARG_DRAW)
			)
		) {
			continue;
		}

		foreach ($dont_count as $suffix) if (is_postfix($task_type, $suffix)) {
			continue 2;
		}

		$free_task_counts[$task_type] = count($tasks_by_type);
	}

	foreach ($free_task_lists as $task_type => $tasks_by_type) {
		if (
			$what_change_to
			? (
				is_prefix($task_type, $what_change_to)
			||	is_prefix($task_type, ARG_ANY)
			) : (
				is_prefix($task_type, ARG_DESC)
			||	is_prefix($task_type, ARG_DRAW)
			)
		) {
			$task_lists_to_pick[
				$what_change_to
				? $task_type
				: implode('_', array_reverse(explode('_', $task_type)))
			] = $free_task_lists[$task_type];
		}
	}

	ksort($free_task_counts);
	$target['free_task_counts'] = $free_task_counts;

	$own_exists = (
		is_file($path = $d.$own)
	||	is_file($path = $d.$dropped)
	||	is_file($path = "$d$i$e")
	);

	if ($u_opts['final_task_notice']) {
		$target['is_task_final'] = data_is_task_final($path, $room_type);
	}

if (TIME_PARTS) time_check_point('got free task lists'
	.', counts = '.get_print_or_none($free_task_counts)
	.', all = '.get_print_or_none($free_task_lists)
	.', to pick = '.get_print_or_none($task_lists_to_pick)
);

	if ($dont_change) {
		return $target;
	}

//* change task if thread is missing or taken, or enough time passed since taking last task:

	$new_task = array();
	$f = $own ?: '';

	if (!(
		$own_exists
	&&	$old_target_thread
	&&	!($change || $change_from || $drop)
	&&	(T0 < $target['time'] + TARGET_CHANGE_TIME)
	&&	preg_match(DATA_PAT_TRD_PLAY, $own, $m)
	&&	strlen($i = $m['ThreadId'])
	&&	!in_array($i, $skip_threads)
	&&	($room_type['allow_reply_to_self'] || data_get_last_post_u(data_cache($path)) != $u_num)
	)) {

//* add empty task to selection, unless current is empty:

		if (
			!$drop
		&&	$old_target_thread
		&&	!data_is_thread_cap()
		) {
			$task_lists_to_pick[ARG_ANY][] = '';
		}

//* get random target from top-preferred pool:

		if (
			$drop
		||	!(
				ksort($task_lists_to_pick)
			&&	($preferred_task_list = end($task_lists_to_pick))
			&&	($f = array_rand($preferred_task_list))
			&&	is_file($path = $d.$f)
			)
		) {
			$f = '';
		}

if (TIME_PARTS) time_check_point('used free task lists'
	.', sorted = '.get_print_or_none($task_lists_to_pick)
	.', preferred = '.get_print_or_none($preferred_task_list)
	.', random-picked = '.get_print_or_none($f)
);

		if ($f !== $own) {
			$target_changed = true;
		}

//* return own back to counts:

		if (
			!$skip_old_target
		&&	$target_changed
		&&	$own_exists
		&&	$target['task']
		) {
			foreach ([
				($target['pic'] ? ARG_DESC : ARG_DRAW)
			,	ARG_ANY
			] as $k) {
				++$free_task_counts[$k];
			}
		}

//* forget the old:

		if (!strlen($f)) {
			$target = $new_target;
		} else
		if ($target_changed) {
			$target = $new_target;
			$i = $preferred_task_list[$f];
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
				$type = ARG_DESC;
				$target['pic' ] = $t = mb_strpos($p = mb_substr($t, $last_pic), DATA_FIELD_SEPARATOR);
				$target['task'] = $p = mb_substr($p, 0, $t);		//* <- only filename
			} else {
				$type = ARG_DRAW;
				$p = mb_strrpos_after($t, NL);
				$p = (false === $p ? $t : mb_substr($t, $p));
				$target['task'] = mb_substr($t, $last_txt);		//* <- only text
				$target['post'] = $p;					//* <- full last line
			}

			foreach ([
				$type
			,	ARG_ANY
			] as $k) {
				if ($free_task_counts[$k] > 0) {
					--$free_task_counts[$k];
				}
			}

			$target['deadline'] = $t = T0 + get_const('TARGET_'.$type.'_TIME');

			if ($u_opts['final_task_notice']) {
				$target['is_task_final'] = data_is_task_final($path, $room_type);
			}

//* rename new target as taken (locked):

			$t = data_get_thread_name_tail(array($t, $u_num));
			$taken = "$i$t$e";
			$dropped = "$i$b$e";
			$t = $target['thread'] = "$taken/$dropped/$c";
			data_cache_file_rename($path, $d.$taken);
			$new_task = array(T0, $room, $t, $p);
		}

		$target['free_task_counts'] = $free_task_counts;
	}

//* task changed:

	if (
		$target_changed
	||	(!strlen($f) !== !strlen($old_target_thread))
	) {
		save_updated_task_list_file(...$new_task);

//* rename old targets as dropped (unlocked):

		if (
			$u_own
		&&	(!strlen($f) || $target_changed)
		) foreach ($u_own as $f => $i) {
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
	$u = T0.DATA_FIELD_SEPARATOR.$u_num;
	$result = array();

	$pic = is_array($post);
	$content_type = ($pic ? 'image' : 'text');

	$change = (
		$target
	&&	$target['drop']
	);

	if ($change) {
		$target = array();
	}

//* thread exists and not full/taken:

	if ((
		$room_type['single_active_thread']
	) ? (
		($i = get_dir_top_file_id($d, $e))			//* <- last not yet full
	&&	is_file($f = "$d$i$e")
	) : (
		!$change
	&&	($current_target_thread = $target['thread'])
	&&	(list($own, $dropped) = mb_split_filter($current_target_thread))
	&&	preg_match(DATA_PAT_TRD_PLAY, $own, $match)
	&&	strlen($i = $match['ThreadId'])
	&&	(!($h = $match['TaskHoldUser']) || $h == $u_num)	//* <- not taken by others
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
		$relation = 'reply';
	} else {
		$relation = ($target['post'] ? 'reply' : 'op');
		$new = 1;
	}

	$post_type = $content_type.'_'.$relation;

//* check if post type allowed:

	if (!$room_type["allow_$post_type"]) {
		$result[ARG_DENY."_$post_type"] = 1;

		return $result;
	}

	if ($pic) {
		$post[0] = get_post_pic_field_with_fixed_info($post[0]);
	}

	$post = $u.(
		$pic
		? DATA_MARK_IMG.implode(DATA_FIELD_SEPARATOR, $post)
		: DATA_MARK_TXT.$post
	);

	if ($new) {

//* archive old full threads before creating new:

		if ($a = data_archive_ready_go()) $result['arch'] = $a;

//* check if not too many existing threads:
//* still possible to post over the cap, e.g. if user finished drawing too late:

		if ($i = data_is_thread_cap($room, $pic)) {
			$result['cap'] = $i;

			if ($a) data_post_refresh();

			return $result;
		}

//* prepend a task copy or placeholder post, if needed:

		if ($fork = $target['post']) {
			$result['fork'] = 1;
		} else
		if ($pic) {
			$fork = (T0 - 1).DATA_FIELD_SEPARATOR.$u_num.DATA_MARK_TXT.NOR;

			if ($target['task']) {
				$fork .= '<!-- '.htmlspecialchars("$target[time]: $target[task]").' -->';
			}
		}
		if ($fork) {
			$post = $fork.NL.$post;
		}

//* create new thread, thread IDs start at 1 (was at 0 before):

		data_put_count($i = data_get_count()+1);

		if ($i <= 1) {
			data_set_u_flag($u_num, "mod_$room", 1);
		}

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

		if (
			!$room_type['arch_wait']
		&&	!$result['arch']
		&&	($a = data_archive_ready_go())
		) {
			$result['arch'] = $a;
		}

		$GLOBALS['data_thread_file_path'] = (is_file($f) ? $f : $a['done']);
		data_check_my_task(DATA_U_TASK_CHANGE);
	}

	data_post_refresh();

	return $result;
}

function data_rename_last_pic($old, $new) {
	if (
		!$old
	||	!$new
	||	$new === $old
	||	!is_string($f = $GLOBALS['data_thread_file_path'])
	||	!strlen($f)
	||	!is_file($f)
	) {
		return;
	}

	if (false === mb_strpos($f, DATA_LOG_EXT)) {
		require_once(NAMEPRFX.'.arch.php');

		return data_archive_rename_last_pic($old, $new, $f);
	}

	$t = file_get_contents($f);
	$pos_before = mb_strrpos_after($t, DATA_MARK_IMG);
	$pos_after = mb_strpos($t, DATA_FIELD_SEPARATOR, $pos_before);
	$old_saved = mb_substr($t, $pos_before, $pos_after - $pos_before);

//* verify that last pic filename in thread is the right one:

	if (
		$old_saved === $old
	||	(
			mb_split(';', $old_saved, 2)[0]
		===	mb_split(';', $old, 2)[0]
		)
	) {
		$before = mb_substr($t, 0, $pos_before);
		$after = mb_substr($t, $pos_after);
		$new_to_save = get_post_pic_field_with_fixed_info(
			(
				is_string($new) && strlen($new)
				? $new
				: $old
			), 2
		);

		return (
			$new_to_save
		&&	$new_to_save !== $old_saved
		&&	file_put_contents($f, "$before$new_to_save$after")
		);
	}
}

?>
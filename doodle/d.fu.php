<?php

//* Constants only for internal use: ------------------------------------------

define('HTML_VERSION', '2020-12-19 16:19');	//* <- change this to autoupdate old browser-cached pages
define('HTACCESS_VERSION', '2017-10-27 23:23');	//* <- change this + open index as admin to autoupdate old .htaccess

//* Function argument flags: --------------------------------------------------

define('F_GET_FULL_IF_NONE', 1);
define('F_GET_DIR_PART', 2);
define('F_HIDE', 4);
define('F_NATSORT', 8);

//* ---------------------------------------------------------------------------

function exit_if_not_mod($new_time_int = 0) {
	$new_time_int = intval($new_time_int);
	$new_date_time = gmdate(
		HTTP_MOD_TIME_FORMAT
	,	(
			$new_time_int > 0
			? max($new_time_int, data_global_announce('last'))
			: T0
		)
	);

	$lines_to_hash = [
		$new_date_time
	,	'Refresh any page cached before:'
	,	HTML_VERSION
	,	'Also if user key changed:'
	,	ME_VAL
	,	'Also date-related decorations, etc:'
	,	get_date_class()
	,	$GLOBALS['lang']
	];

	if ($v = $GLOBALS['target']) {
		$lines_to_hash[] = [
			'target:'
		,	"task = $v[task]"
		,	$v['drop'] ? 'drop' : ''
		,	$v['keep'] ? 'keep' : ''
		];
	}

	if ($v = $GLOBALS['visible']['changes']) {
		$lines_to_hash[] = [
			'visible changes:'
		,	$v
		];
	}

	$content_to_hash = get_imploded_non_empty_lines($lines_to_hash);
	$etag_hash = md5($content_to_hash);
	$quoted_etag = '"'.$etag_hash.'"';
	$weak_etag = 'W/"'.$etag_hash.'"';

	if (TIME_PARTS) time_check_point('page refreshed, data = '.get_print_or_none([
		'modified' => $new_date_time
	,	'Etag' => $weak_etag
	,	'hash content' => $content_to_hash
	]));

	header("Etag: $weak_etag");
	header("Last-Modified: $new_date_time");

	if (
		!POST
	&&	!$GLOBALS['target']['changed']
	&&	!$GLOBALS['u_opts']['modtime304']
	&&	isset($_SERVER[$m = 'HTTP_IF_MODIFIED_SINCE'])
	&&	isset($_SERVER[$n = 'HTTP_IF_NONE_MATCH'])
	&&	$_SERVER[$m] === $new_date_time
	&&	(
			$_SERVER[$n] === $etag_hash
		||	$_SERVER[$n] === $quoted_etag
		||	$_SERVER[$n] === $weak_etag
		)
	) {
		header('HTTP/1.0 304 Not Modified');

		exit;
	}
}

function exit_redirect($new_path = '', $comment = 'path fix', $pause_seconds = 0) {
	if (headers_sent()) {
		die('<meta http-equiv="refresh" content="'.abs(int($pause_seconds)).'; url='.$new_path.'">');
	} else {
		header("HTTP/1.1 303 Redirect: $comment");
		header("Location: $new_path");

		exit;
	}
}

function add_cookie_header($name, $value = null) {
	global $qk_expires_now, $qk_expires_later;

	if ($value === null) {
		list($name, $value) = preg_split('~\s*=\s*~u', $name, 2);
	}
	$k = (
		($delete = !strlen($value))
	?	'qk_expires_now'
	:	'qk_expires_later'
	);
	$qk_expires = (isset($$k) ? $$k : ($$k = gmdate(DATE_COOKIE, ($delete ? 0 : T0 + QK_EXPIRES))));

	header("Set-Cookie: $name=$value; expires=$qk_expires; Path=".ROOTPRFX);
}

function rewrite_htaccess($write_to_file = 1) {
	$start_mark = '# 8<-- start mark: '.NAMEPRFX.', version: ';
	$end_mark = '# 8<-- end mark: '.NAMEPRFX.', placed automatically: ';
	$new_mark = $start_mark.ROOTPRFX.' '.HTACCESS_VERSION;

	data_lock($lk = LK_MOD_HTA);
	if (
		!strlen($old = (is_file($f = '.htaccess') ? trim_bom(file_get_contents($f)) : ''))
	||	false === strpos($old, $new_mark)
	||	false === strpos($old, $end_mark)
	) {
		$s = '+([^/]+/+)*';
		$d = '('.implode('|', $GLOBALS['cfg_dir']).')(/+[^/]+)*';
		$dd = mb_escape_regex(get_const('DATA_DIR') ?: get_const('DIR_DATA'));
		$new = $new_mark.' -- Do not touch these marks. Only delete them along with the whole block.
<IfModule rewrite_module>
	RewriteEngine On
	RewriteBase '.ROOTPRFX.($dd?'

# hide data files from browsing:
	RewriteRule ^'.$dd.' . [L,R]':'').'

# expand simple image path:
	RewriteRule ^('.DIR_PICS.'+)(([^/])[^/]+\.([^/])[^/]+)$ $1$4/$3/$2'.'

# file found, skip next 4 lines:
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^.? - [S=4]

# file not found:
	RewriteRule ^('.DIR_PICS.$s.'|'.DIR_ARCH.$s.DIR_THUMB.'+)[^/]+$ '.PIC_404.' [L]
	RewriteRule ^('.DIR_ARCH.$s.')[^/]+$ $1 [L,R]

# virtual folders:
	RewriteRule ^'.$d.'/+[-\d]*$ . [L]'.'
	RewriteRule ^'.$d.'$ $0/ [L,R]'.'

</IfModule>
'.$end_mark.date(CONTENT_DATETIME_FORMAT, T0).' -- Manual changes inside will be lost on the next autoupdate.';
		if ($old) {
			$b_found = (false !== ($i = mb_strpos($old, $start_mark)));
			$before = ($b_found ? trim(mb_substr($old, 0, $i)) : '');

			$a_found = (false !== ($i = mb_strpos($old, $end_mark)));
			$after = ($a_found && false !== ($i = mb_strpos($old, NL, $i)) ? trim(mb_substr($old, $i)) : '');

			if ($b_found || $a_found) $new = (
				(strlen($before) ? $before.NL.NL : '')
			.	$new
			.	(strlen($after) ? NL.NL.$after : '')
			); else $new .= NL.NL.$old;
		} else $old = 'none';

//* workaround for Apache ignoring .htaccess with "\n" (not "\r\n") on Windows:

		$old = preg_replace('~(\r\n|\v)~u', PHP_EOL, $old);
		$new = preg_replace('~(\r\n|\v)~u', PHP_EOL, $new);

		$changed = ($new != $old);
	} else $new = 'no change';
	$report = "---- old version: ----

$old

---- new version: ----

$new";

//* rewrite htaccess if none/changed, when logged in and viewing root folder:

	if ($write_to_file && $changed) {
		$saved = (
			file_put_contents($f, $new)
			? 'Succesfully updated'
			: 'Failed to update'
		);
		data_log_action($report = "$saved $f

$report");
	}
	data_unlock($lk);

	return $report;
}

function time_check_point($comment) {$GLOBALS['tcp'][microtime()][] = $comment;}
function get_print_or_none($a) {return $a ? trim(print_r($a, true)) : 'none';}

//* ---------------------------------------------------------------------------
//* Always use mb_* for text, but simple str* for non-empty checks,
//* encoded URLs and binary content (BOM, images, etc);
//* ---------------------------------------------------------------------------
//* not sure about safety of explode("\n", 'UTF-8 text');
//* not sure about mb_split() vs preg_split('//u');
//* mb_split() takes regex without delimiters, and allows encoding other that UTF-8;
//* ---------------------------------------------------------------------------

function fix_encoding($text) {
	global $fix_encoding_chosen;

	if (mb_check_encoding($text)) {
		$fix_encoding_chosen[] = ENC;

		goto normalize;
	}

	$a = array_filter(explode(',', "$_REQUEST[_charset_],".get_const('ENC_FALLBACK')));

	if (function_exists('iconv')) {
		foreach ($a as $e) if (
			strlen($e = trim($e))
		&&	false !== ($fix = iconv($e, ENC, $text))
		&&	mb_check_encoding($fix)
		) {
			$fix_encoding_chosen[] = $e;
			$text = $fix;

			goto normalize;
		}
	}

	if (false !== ($e = mb_detect_encoding($text, implode(',', $a), true))) {
		$fix_encoding_chosen[] = $e;
		$text = mb_convert_encoding($text, ENC, $e);

		goto normalize;
	}

	return $text;

normalize:
	return function_exists($f = 'normalizer_normalize') ? $f($text) : $text;
}

function log_preg_last_error($none_too = true) {
	if (!TIME_PARTS) {
		return;
	}

	if (($e = preg_last_error()) || $none_too) {

		//* http://php.net/manual/en/pcre.constants.php

		foreach (array(
			'PREG_NO_ERROR' => 'there were no errors. since v5.2.0'
		,	'PREG_INTERNAL_ERROR' => 'there was an internal PCRE error. since v5.2.0'
		,	'PREG_BACKTRACK_LIMIT_ERROR' => 'backtrack limit was exhausted. since v5.2.0'
		,	'PREG_RECURSION_LIMIT_ERROR' => 'recursion limit was exhausted. since v5.2.0'
		,	'PREG_BAD_UTF8_ERROR' => 'the last error was caused by malformed UTF-8 data (only when running a regex in UTF-8 mode). since v5.2.0'
		,	'PREG_BAD_UTF8_OFFSET_ERROR' => 'the offset didn\'t correspond to the begin of a valid UTF-8 code point (only when running a regex in UTF-8 mode). since v5.3.0'
		,	'PREG_JIT_STACKLIMIT_ERROR' => 'the last PCRE function failed due to limited JIT stack space. since v7.0.0'
		) as $k => $v) if (
			defined($k)
		&&	$e == get_const($k)
		) {
			$e = "$e, $k: $v";
			break;
		}

		$v = PCRE_VERSION;
		time_check_point("PCRE ver.$v, preg_last_error = $e, debug_backtrace = ".get_print_or_none(debug_backtrace(0)));
	}
}

function get_const($name) {
	return (
		defined($uppercase_name = mb_strtoupper($name))
		? constant($uppercase_name)
		: ''
	);
}

function get_localized_value($key, ...$keys) {
	if (array_key_exists($key, LOCALIZATION_TEXT)) {
		$value = LOCALIZATION_TEXT[$key];

		foreach ($keys as $key) {
			if (
				is_array($value)
			&&	array_key_exists($key, $value)
			) {
				$value = $value[$key];
			} else {
				return '';
			}
		}

		return $value;
	}

	return '';
}

function get_localized_text_array(...$keys) {
	$value = get_localized_value(...$keys);

	return (
		is_array($value)
		? $value
		: array()
	);
}

function get_localized_or_empty_text(...$keys) {
	return get_imploded_non_empty_lines(get_localized_value(...$keys));
}

function get_localized_text(...$keys) {
	$value = get_localized_or_empty_text(...$keys);

	return (
		strlen($value)
		? $value
		: implode(' => ', $keys)
	);
}

function get_abbr($a, $separator = '_') {
	if (!is_array($a)) {
		$a = mb_split_filter($a, $separator);
	}

	foreach ($a as $word) {
		$r .= mb_substr($word,0,1);
	}

	return $r;
}

function get_imploded_non_empty_lines($a, $separator = NL) {
	if (is_array($a)) {
		$a = array_map('get_imploded_non_empty_lines', $a);
		$a = array_filter($a, 'strlen');

		return implode($separator, $a);
	}

	return trim("$a");
}

function mb_escape_regex($s, $delim = '/', $extend = '') {
	return preg_replace("~[\\\\|\\$delim$extend\\[\\](){}^$.:?*+-]~u", '\\\\$0', $s);
}

function mb_normalize_slash($s) {
	return mb_str_replace('\\', '/', $s);
}

function mb_sanitize_filename_char($match) {
	return $match[0] === '"' ? "'" : '_';
}

function mb_sanitize_filename($s) {
	return preg_replace_callback('~[\\/":?*<>]~u', 'mb_sanitize_filename_char', $s);
//	return strtr($s, array('"' => "'", ':' => '_', '?' => '_', '*' => '_', '<' => '_', '>' => '_'));
}

if (!function_exists($f = 'mb_str_split')) {
	function mb_str_split($s) {
		return preg_split('//u', $s);
	}
}

function mb_split_filter($s, $by = '/', $limit = 0) {
	return preg_split(
		$by === NL
		? '~\v+~u'
		: '/('.mb_escape_regex($by).')+/u'
	, $s, $limit, PREG_SPLIT_NO_EMPTY);
}

function mb_substr_before($where, $what, $offset = 0) {
	return (
		false !== ($pos = mb_strpos ($where, $what, $offset))
		? mb_substr($where, 0, $pos)
		: $where
	);
}

function mb_substr_after ($where, $what, $offset = 0) {
	return mb_substr($where, mb_strrpos_after($where, $what, $offset));
}

function mb_strpos_after ($where, $what, $offset = 0) {
	return (
		false !== ($pos = mb_strpos ($where, $what, $offset))
		? $pos + mb_strlen($what)
		: $pos
	);
}

function mb_strrpos_after($where, $what, $offset = 0) {
	return (
		false !== ($pos = mb_strrpos($where, $what, $offset))
		? $pos + mb_strlen($what)
		: $pos
	);
}

function mb_str_replace($what, $to, $where) {
	return implode($to, preg_split('/('.mb_escape_regex($what).')/u', $where));
}

function mb_str_replace_first($what, $to, $where) {
	return (
		false === ($pos = mb_strpos($where, $what))
		? $where
		: mb_substr($where, 0, $pos).$to.mb_substr($where, $pos + mb_strlen($what))
	);
}

function str_replace_first($what, $to, $where) {
	return (
		false === ($pos = strpos($where, $what))
		? $where
		: substr_replace($where, $to, $pos, strlen($what))
	);
}

function trim_slash_dots($path, $remove_edge_slashes = true) {
	$path = preg_replace('~(^|/)(\.*/+|\.+$)+~u', '$1', $path);
	if ($remove_edge_slashes) $path = trim($path, '/');

	return $path;
}

function trim_bom($str) {
	return trim(str_replace(BOM, '', $str));
}

function trim_post($text, $len = 0) {
	$s = trim(preg_replace('~\s+~u', ' ', fix_encoding($text)));
	if ($len > 0) {
		$s = mb_substr($s, 0, $len);
	}

	return POST ? htmlspecialchars($s) : $s;
}

function trim_room($name, $also_allow_chars = '') {
	$t = mb_escape_regex("$GLOBALS[cfg_room_prefix_chars]$also_allow_chars");
	$x = ROOM_NAME_ALLOWED_CHARS;
	$w = "-\\w$x$t";

	return mb_substr(
		preg_replace("/([^0-9a-z$x])\\1+/u", '$1',
		preg_replace("/[^$w]+/u", '_',
		trim(trim(mb_strtolower($name)), '\\/')
	)), 0, ROOM_NAME_MAX_LENGTH);
}

function encode_URL_parts($path, $raw = false) {
	return implode('/', array_map(
		$raw ? 'rawURLencode' : 'URLencode'
	,	is_array($path) ? $path : mb_split_filter($path)
	));
}

function encode_opt_value($v) {
	if (URLencode($v) === "$v") {
		return $v;
	}

	return B64_PRFX.str_replace(
		array('/', '+')
	,	array('_', '-')
	,	base64_encode($v)
	);
}

function decode_opt_value($v) {
	if (0 !== strpos($v, B64_PRFX)) {
		return $v;
	}

	return base64_decode(str_replace(
		array('_', '-')
	,	array('/', '+')
	,	substr($v, strlen(B64_PRFX))
	));
}

function is_url_equivalent($a, $b) {
	$funcs = array('', 'URLdecode', 'rawURLdecode');

	foreach ($funcs as $f)
	foreach ($funcs as $g) {
		if (
			($f ? $f($a) : $a)
		===	($g ? $g($b) : $b)
		) {
			return true;
		}
	}

	return false;
}

function is_url_external($url) {
	if ($url && ($s = $_SERVER['SERVER_NAME'])) {
		$i = (strpos($url, '://'  ) ?: 0)+3;
		$j = (strpos($url, '/', $i) ?: 0);

		if ($s !== substr($url, $i, $j-$i)) {
			return true;
		}
	}

	return false;
}

function is_deny_arg($k) {return is_prefix($k, ARG_DENY);}
function is_desc_arg($k) {return is_prefix($k, ARG_DESC);}
function is_draw_arg($k) {return is_prefix($k, ARG_DRAW);}
function is_opt_arg($k) {return is_prefix($k, OPT_PRFX);}
function is_tag_attr($t) {return mb_strpos($t, '<') === mb_strpos($t, '>');}	//* <- if only both === false
function is_not_empty($var) {return !empty($var);}
function is_not_dot($path) {return !!trim($path, './\\');}
function is_not_draw_none($var) {return $var !== DRAW_APP_NONE;}
function is_not_hidden($room) {
	global $u_flag;

	return (
		GOD
	||	$u_flag['mod']
	||	$u_flag["mod_$room"]
	||	!get_room_type($room, 'hide_in_room_list')
	);
}

function mkdir_if_none($file_path) {
	if (
		($i = mb_strrpos($file_path, '/'))
	&&	($d = mb_substr($file_path, 0, $i))
	&&	!is_dir($d)
	&&	!is_file($d)
	) {
		mkdir($d, 0755, true);
	}

	return $file_path;
}

function file_put_mkdir($file_path, $content = '') {
	return file_put_contents(mkdir_if_none($file_path), $content);
}

function get_dir_contents($path = '.', $flags = 0) {
	$a = (is_dir($path) ? array_filter(scandir($path), 'is_not_dot') : array());

	if ($a && ($flags & F_HIDE)) {
		$a = array_filter($a, 'is_not_hidden');
	}

	if ($a && ($flags & F_NATSORT)) {
		natcasesort($a);
	}

	return $a;
}

function get_dir_rooms($source_subdir = '', $output_subdir = '', $flags = 0, $type = '') {
	global $cfg_game_type_dir;

	if ($s = $source_subdir ?: '') $s = trim($s, '/.').'/';
	if ($o = $output_subdir ?: '') $o = trim($o, '/.').'/';

	$result = array();
	$types = (array)($type ?: $cfg_game_type_dir);

	foreach ($types as $t) {
		if ($t) $t = "$t/";

		foreach (get_dir_contents("$s$t", $flags) as $r) {
			if (($t || !in_array($r, $cfg_game_type_dir)) && is_dir("$s$t$r")) {
				$result[] = "$o$t$r";
			}
		}
	}

	return $result;
}

function get_file_lines($path) {
	return (
		is_file($path)
		? mb_split_filter(file_get_contents($path), NL)
	//	? mb_split_filter(trim_bom(file_get_contents($path)), NL)	//* <- trim messes up line indexes, don't touch for now
	//	? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
		: array()
	);
}

function get_file_name_no_ext($path, $flags = F_GET_FULL_IF_NONE) {return get_file_dir(get_file_name($path, $flags), $flags, '.');}
function get_file_ext($path, $flags = 0) {return mb_strtolower(get_file_name($path, $flags, '.'));}
function get_file_dir($path, $flags = F_GET_FULL_IF_NONE, $delim = '/') {return get_file_name($path, $flags | F_GET_DIR_PART, $delim);}
function get_file_name($path, $flags = F_GET_FULL_IF_NONE, $delim = '/') {
	return (
		false === ($pos = mb_strrpos($path, $delim))
		? ($flags & F_GET_FULL_IF_NONE ? $path : '')
		: (
			$flags & F_GET_DIR_PART
			? mb_substr($path, 0, $pos)	//* <- last delimiter not included
			: mb_substr($path, $pos + mb_strlen($delim))
		)
	);
}

function get_room_skip_list($add = '', $room = '') {
	$a = array();

	if (
		!$GLOBALS['room_type']['single_active_thread']
	&&	($r = $room ?: $GLOBALS['room'])
	) {
		$room_sep = '//';
		$skip_sep = '/';
		$k = array();
		$e = array(
			encode_URL_parts($r, true)
	//	,	encode_opt_value($r)		//* <- uses base64, but atob() in JS turns utf8 into garbage, not usable
		);

//* find existing list in cookies:

		foreach ($e as $r) {
			$k[] = $q = ME.'-skip-'.md5($r);
			if ($v = $_COOKIE[$q]) {
				$a = array_slice(
					explode(
						$skip_sep
					,	end(explode($room_sep, $v, 2))
					,	TRD_MAX_SKIP_PER_ROOM + 1
					)
				,	0
				,	TRD_MAX_SKIP_PER_ROOM - ($add?1:0)
				);
				break;
			}
		}

		if (strlen($add)) {

//* remove same value:

			if ($a) {
				foreach ($a as $k => $v) if ($v === $add) unset($a[$k]);
			} else {

//* if room name not yet defined, find shortest encoding result:

				foreach ($e as $i => $j) if (strlen($j) < strlen($r)) {
					$q = $k[$i];
					$r = $j;
				}
			}

//* prepend given value + return new cookie:

			array_unshift($a, $add);
			$v = implode($skip_sep, $a);

			return "$q=$r$room_sep$v";
		}
	}

//* if not adding, return the list array:

	return $a;
}

function get_room_name_length($name, $as_arr = false) {
	global $cfg_room_prefixes;

	$found_prefixes = array();

	do {
		$found = 0;

		foreach ($cfg_room_prefixes as $v) if (
			0 === strpos($name, $v)
		&&	!in_array($v, $found_prefixes)
		) {
			$found++;
			$found_prefixes[] = $v;
			$name = substr($name, strlen($v));
		}
	} while (strlen($name) && $found);

	$name_length = mb_strlen($name);

	return (
		$as_arr
		? array($name_length, $name, $found_prefixes)
		: $name_length
	);
}

function get_room_type($room, $type = '') {
	global $cfg_game_type_dir, $cfg_room_types;

	$sub = mb_split_filter($room);
	$name = array_pop($sub);

//* 1) check game types in room subfolders:

	if ($type) {
		if ($v = $cfg_game_type_dir[$type]) {
			return ($v ? in_array($v, $sub) : !$sub);
		}

		$result = 0;
	} else {
		$result = array();

		foreach ((array)($sub ?: GAME_TYPE_DEFAULT) as $v) if (false !== ($k = array_search($v, $cfg_game_type_dir))) {
			$result['game_type'] = $k;
		}
	}

//* 2) check room name:

	list($name_length, $name, $found_prefixes) = get_room_name_length($name, true);

//* if any, match all defined criteria in each rule set:

	foreach ($cfg_room_types as $set_id => $set) if (
		(!isset($set['if_game_type']) || (isset($result['game_type']) && $set['if_game_type'] === $result['game_type']))
	&&	(!($v = $set['if_name_prefix']) || in_array($v, $found_prefixes))
	&&	(!($v = intval($set['if_name_length'	])) || $name_length == $v)
	&&	(!($v = intval($set['if_name_length_min'])) || $name_length >= $v)
	&&	(!($v = intval($set['if_name_length_max'])) || $name_length <= $v)
	) {

//* latest found values for a key overwrite any previous:

		if ($type) {
			if (array_key_exists($type, $set)) {
				$result = $set[$type];
			}
		} else {
			$result['set'][] = $set_id;

			foreach ($set as $k => $v) {
				$result[$k] = $v;
			}
		}
	}

	return $result;
}

function get_dir_top_file_id($d, $e = '') {
	$i = 0;

	foreach (get_dir_contents($d) as $f) if (
		($n = intval($f))
	&&	(!$e || $f === "$n$e")
	&&	$i < $n
	) {
		$i = $n;
	}

	return $i;
}

function get_dir_top_filemtime($d) {
	$t = 0;

	foreach (get_dir_contents($d) as $f) if (
		($m = filemtime("$d/$f"))
	&&	$t < $m
	) {
		$t = $m;
	}

	return $t;
}

function get_pic_url($p) {
	return ROOTPRFX.(
		PIC_SUB
		? get_pic_subpath($p)
		: DIR_PICS.get_file_name($p)
	);
}

function get_pic_normal_path($p) {
	return preg_replace('~^
		(?P<before>.*?[\\\\/])?
		(?P<filename>[^/._-]+)
		(?P<suffix>[_-][^/.]*)?
		(?P<ext>\.[^.,;]+)
		(?P<after>[;,].*)?
	$~ux', '$2$4', $p);
}

function get_pic_resized_path($p) {
	$s = '_res';

	return (
		false === ($i = mb_strrpos($p, '.'))
		? $p.$s
		: mb_substr($p,0,$i).$s.mb_substr($p,$i)
	);
}

function get_pic_subpath($p) {
	$p = get_pic_normal_path(get_file_name($p));
	$i = mb_strpos_after($p, '.');
	$e = mb_substr($p,$i,1);
	$n = mb_substr($p,0,1);
	$p = DIR_PICS."$e/$n/$p";

	return $p;
}

function get_search_ranges($criteria, $caseless = true) {
	$criteria = array_filter($criteria, 'is_not_empty');
	$result = array();

//* convert numeric types to array of ranges and/or comparison operators:

	$signs = array('<','>','-');
	$before = '^(?P<before>\D*?)(?P<minus>-)?';
	$pat_oom = '~'.SUBPAT_OOM_LETTERS.'~iu';
	$patterns = array(
		'(?P<number>\d+)'
			=> array('width', 'height')
	,	'(?P<number>\d+)((?P<float>[,.]\d+)?\s*(?P<oom>'.SUBPAT_OOM_LETTERS.'))?'
			=> array('bytes')
	,	'(?P<csv>\d+(:+\d+)*)'
			=> array('time')
	);

	foreach ($patterns as $pattern => $types)
	foreach ($types as $type) {
		if (strlen($t = $criteria[$type])) {
			$sub_ranges = array();
			$min = false;

			while (preg_match("~$before$pattern~iux", $t, $match)) {
				$t = substr($t, strlen($match[0]));
				$prefix = $match['before'] ?: '';
				$minus = $match['minus'] ?: '';

				if (
					strlen($minus)
				&&	!strlen($prefix)
				&&	false !== $min
				) {
					$prefix = '-';
					$minus = '';
				}

				if (strlen($v = $match['csv'])) {
					$v = get_time_seconds($x = "$minus$v");
				} else {
					$v = intval($match['number']);
					$x = "$minus$v";

					if (
						($oom = $match['oom'])
					&&	preg_match($pat_oom, $oom, $m)
					) {
						$v = (float)"$x$match[float]";
						$x = "$v$oom";
						$i = 0;

						do {
							$v *= 1024;
						} while (
							!$m[++$i]
						&&	$i < 255
						);
					} else {
						$v = intval($x);
					}
				}
				$k = '';

				if (strlen($prefix)) {
					foreach ($signs as $sign) if (false !== mb_strpos($prefix, $sign)) {
						$k = $sign;

						break;
					}
				}

				if ('-' === $k && false !== $min) {
					array_pop($sub_ranges);

					$sub_ranges[] = (
						$min < $v
						? array('min' => $min, 'max' => $v, 'min_arg' => $min_arg, 'max_arg' => $x)
						: array('min' => $v, 'max' => $min, 'min_arg' => $x, 'max_arg' => $min_arg)
					);

					$min = false;
				} else {
					if ($k) {
						$min = false;
					} else {
						$k = '=';
						$min = $v;
						$min_arg = $x;
					}

					$sub_ranges[] = array(
						'operator' => $k
					,	'argument' => $x
					,	'value' => $v
					);
				}
			}

			if ($sub_ranges) {
				$result[$type] = $sub_ranges;
			}
		}

		unset($criteria[$type]);
	}

//* convert remaining text types to line-separated arrays and/or lowercase if needed:

	foreach ($criteria as $type => $value) if (is_arg_type_any_of($type)) {
		$result[$type] = get_search_ranges($value, $caseless);
	} else {
		$value = "$value";

		if ($caseless) {
			$value = mb_strtolower($value);
		}

		if (preg_match('~\v~u', $value)) {
			$value = mb_split_filter($value, NL);
		}

		$result[$type] = $value;
	}

	return $result;
}

function is_arg_type_any_of($type) {
	return (
		$type === ARG_ANY_OF
	||	intval($type) !== 0
	);
}

function is_post_text_matching($post_text, $search_value, $type = false, $caseless = false, $is_regex = false) {
	foreach ((array)$post_text as $t) if (strlen($t)) {
		$t = html_entity_decode($t);

		if ($caseless) {
			$t = mb_strtolower($t);
		}

		if (
			(
				$type === ARG_FULL_NAME
				? $t === $search_value
				: false !== mb_strpos($t, $search_value)
			)
		||	($is_regex && @preg_match($search_value, $t))
		) {
			return true;
		}
	}

	return false;
}

function is_post_matching($post, $criteria, $caseless = true) {
	if (!(
		is_array($post) && $post
	&&	is_array($criteria) && $criteria
	)) {
		return false;
	}

	$is_image_post = isset($post['meta']);

	foreach ($criteria as $type => $value) {
		if (is_arg_type_any_of($type)) {
			foreach ($value as $k => $v) {
				if ($found = is_post_matching($post, array($k => $v), $caseless)) {
					continue 2;
				}
			}

			return false;
		}

		$found = $t = '';

	//* get values from relevant post field:

		if ($type == 'name' || $type == ARG_FULL_NAME) {
			$t = $post['username'];
		} else
		if ($type == 'user_id') {
			$t = $post['user_id'];
		} else
		if ($type == 'post') {
			if ($is_image_post) {
				return false;
			}

			$t = $post['post'];				//* <- text-only post content

			if (false !== mb_strpos($t, '<')) {
				$t = preg_replace('~<[^>]+>~u', '', mb_str_replace('<br>', NL, $t));
			}
		} else
		if (!$is_image_post) {
			return false;
		} else
		if ($type == 'file') {
			$t = mb_split('"', $post['post']);
			$t = array_filter($t, 'is_tag_attr');
			$t = array_map('get_file_name', $t);		//* <- all filenames, original and resized
		} else
		if ($type == 'width' || $type == 'height' || $type == 'bytes') {
			$t = $post['post'];
			$pat = ($type == 'bytes' ? PAT_POST_PIC_BYTES : PAT_POST_PIC_W_X_H);

			if (preg_match($pat, mb_substr_after($t, '>'), $match)) {
				$t = intval($match[$type == 'height'?2:1]);
			} else {
				return false;
			}
		} else
		if ($type == 'time') {
			if (preg_match('~^[\d:-]+~i', $post['meta'], $match)) {
				$t = $match[0];

				if (mb_strrpos($t, '-')) {
					$t1 = $t0 = false;

					foreach (mb_split('-', $t) as $n) if (strlen($n)) {
						if (false === $t0) $t0 = $n;
						$t1 = $n;
					}

					$t = intval(($t1-$t0)/1000);	//* <- msec. from JS
				} else {
					$t = get_time_seconds($t);
				}
			} else {
				return false;
			}
		} else {
			$t = $post['meta'];				//* <- what was used to draw
		}

if (TIME_PARTS && LOCALHOST) time_check_point("$type: $t vs ".get_print_or_none($value));

	//* compare:

		if (is_array($value)) {
			foreach ($value as $cond_type => $cond)
			if (
				!is_array($cond)
			||	array_key_exists(0, $cond)
			) {
				if (
					(is_array($cond) ? count($cond) : strlen($cond))
				&&	($found = is_post_text_matching($t, $cond, $cond_type, $caseless))
				) {
					break;
				}
			} else
			if (
				(
					array_key_exists($k = 'operator', $cond)
				&&	array_key_exists($v = 'value', $cond)
				&&	(
						($cond[$k] == '=' && $t == $cond[$v])
					||	($cond[$k] == '<' && $t < $cond[$v])
					||	($cond[$k] == '>' && $t > $cond[$v])
					)
				) || (
					array_key_exists('min', $cond)
				&&	array_key_exists('max', $cond)
				&&	$t >= $cond['min']
				&&	$t <= $cond['max']
				)
			) {
				if ($type == 'time') {
					$found = "drawn in $t sec.";
				} else {
					$found = "found $t";
				}

				break;
			}
		} else {
			if (!is_array($value)) {
				$is_regex = preg_match(PAT_REGEX_FORMAT, $value);
			}

			$found = is_post_text_matching($t, $value, $type, $caseless, $is_regex);
		}

		if (!$found) {
			return false;
		}
	}

	return $found;
}

function get_post_fields_to_display($post) {
	global $post_data_to_hide;

	if (!isset($post_data_to_hide)) {
		$post_data_to_hide = array(PAT_POST_PIC_BYTES, PAT_POST_PIC_CRC32);
	}

	if (is_array($post)) {
		if (isset($post['meta'])) {
			$old = $post['post'];
		}
	} else {
		$old = $post;
	}

	if (isset($old)) {
		$new = $old;

		foreach ($post_data_to_hide as $pat) {
			$new = preg_replace($pat, '', $new);
		}

		if ($new !== $old) {
			if (is_array($post)) {
				$post['post'] = $new;
			} else {
				$post = $new;
			}
		}
	}

	return $post;
}

function get_post_pic_to_display($p, $return_array = false) {
	$a = get_post_pic_info($p);
	$src = $a['file_name_ext'];

	if ($res = (intval($a['full_res'][0]) > DRAW_PREVIEW_WIDTH)) {
		$alt = get_post_fields_to_display($p);
	} else {
		$alt = $src;
	}

	return (
		$return_array
		? array_map('htmlspecialchars', array(
			'src' => get_pic_url($res ? get_pic_resized_path(get_pic_normal_path($src)) : $src)
		,	'alt' => $alt
		))
		: $alt
	);
}

function get_post_pic_info($p, $csv = '', $check_file = 0) {
	list($filename, $etc) = mb_split(';', $p, 2);

	$a = array(
		'file_name_ext' => ($p = trim(
			$check_file
			? mb_sanitize_filename(get_file_name(mb_normalize_slash($filename)))
			: $filename	//* <- faster for prepared active content
		))
	,	'csv' => trim($csv) ?: trim($etc) ?: ''
	);

	if ($check_file || $csv) {
		$a['rel_path'] = ($p = get_pic_subpath($p));
		$a['full_url'] = get_pic_url($p);
	}

	if ($csv = $a['csv']) {
		if (preg_match(PAT_POST_PIC_W_X_H, $csv, $match_res)) {
			$a['full_res'] = array(
				$match_res['width']
			,	$match_res['height']
			);
		}

		if (preg_match(PAT_POST_PIC_BYTES, $csv, $match_bytes)) {
			$a['full_bytes'] = $b = $match_bytes['bytes'];
			$a['full_bytes_f'] = format_filesize($b);
		}

		if (preg_match(PAT_POST_PIC_CRC32, $csv, $match_crc32)) {
			$a['crc32'] = $match_crc32['crc32'];
		}
	}

	if ($check_file && is_file($p)) {
		if ($check_file > 1 || !$a['full_res']) {
			$a['full_res'] = getImageSize($p);
		}

		if ($check_file > 1 || !$a['full_bytes'] || !$a['full_bytes_f']) {
			$a['full_bytes'] = $b = filesize($p);
			$a['full_bytes_f'] = format_filesize($b);
		}

		if ($check_file > 1 || !$a['crc32']) {
			$a['crc32'] = hash_file(ARCH_DL_HASH_TYPE, $p);
		}
	}

	return $a;
}

function get_post_pic_field_with_fixed_info($p, $check_file = 1) {
	$a = get_post_pic_info($p, '', $check_file);
	$res = $a['full_res'];

	return "$a[file_name_ext];$res[0]*$res[1], $a[full_bytes_f], $a[full_bytes] B, 0x$a[crc32]";
}

function get_archiver_dl_list($caseless = true, $include_hidden = true) {
	global $u_num;

	$user_id = ($_REQUEST['by_user_id'] ? "$u_num" : '');
	$names = ($_REQUEST['by_user_names'] ? (fix_encoding($_REQUEST['names']) ?: '') : '');

	if (!strlen($user_id) && !strlen($names)) {
		return '';
	}

	$criteria = get_search_ranges(
		array(
			'file' => '.'
		,	ARG_ANY_OF => array(
				'user_id' => $user_id
			,	ARG_FULL_NAME => $names
			)
		)
	,	$caseless
	);

	$sources = array(
		'from_arch' => array(
			'require' => NAMEPRFX.'.arch.php'
		,	'data_search_func' => 'data_archive_find_by'
		,	'post_parser_func' => 'data_archive_get_post_pic_info'
		)
	,	'from_room' => array(
			'require' => NAMEPRFX.'.db.php'
		,	'data_search_func' => 'data_find_by'
		,	'post_parser_func' => 'data_get_post_pic_info'
		)
	);

	$naming = fix_encoding($_REQUEST['naming']) ?: '';
	$s = ARCH_DL_NAME_PART_SEPARATOR;
	$first = 0;
	$last = 0;
	$total_size = 0;
	$file_list = array();

	data_lock($lk = LK_ARCH_DL, true);
	foreach ($sources as $source_key => $source) if (isset($_REQUEST[$source_key])) {
		if ($v = $source['require']) require_once($v);
		$f = $source['data_search_func'];
		$found = (
			function_exists($f)
			? $f($criteria, $caseless, $include_hidden)
			: array()
		);

		foreach ($found as $room => $threads) {
			if (get_room_type($room, 'hide_in_room_list')) {
				$room = '[ '.get_localized_text('archiver_hidden_room').' #'.strtoupper(hash(ARCH_DL_HASH_TYPE, $room)).' ]';
			}

			foreach ($threads as $thread => $posts) {
				if (intval($thread) <= 0) {
					$thread = '';
				}

				foreach ($posts as $post) {
					$f = $source['post_parser_func'];
					$a = (function_exists($f) ? $f($post['post']) : array());

					if (
						strlen($crc32 = $a['crc32'] ?: '') < 8	//* <- zip stream will restart partial dl without crc32
					||	!strlen($f = $a['file_name_ext'] ?: '')
					||	!strlen($rel_path = $a['rel_path'] ?: '')
					||	!is_file($rel_path)			//* <- zip stream will abort on file access errors
					) {
						continue;
					}

					$i = get_file_name_no_ext($f);
					$ext = get_file_ext($f);

					$date = date(FILENAME_DATETIME_FORMAT, $time = $post['date']);
					$author = $post['username'];

					if ($name = $naming) {
						foreach (get_localized_text_array('archiver_naming_parts') as $k => $tip) {
							$v = $$k;
							$k = mb_escape_regex(ARG_NAMING_VAR_PREFIX.$k).'(?:\b|(?=_))';
							$pat =	'~[<](?P<before>[^>]*?)'
							.		$k
							.	'(?P<after>[^>]*?)[>]|'
							.		$k
							.	'~u'
							;
							$name = preg_replace_callback(
								$pat
							,	function($match) use ($v) {
									return (
										strlen($v)
										? "$match[before]$v$match[after]"
										: ''
									);
								}
							,	$name
							);
						}
					}

					if (!strlen($name = trim($name))) {
						$name = $i;
					} else if (false === mb_strpos($name, $i)) {
						$name .= $s.$i;
					}

					if (strlen($name = mb_sanitize_filename("$name.$ext"))) {
						if ($time) {
							if (!$first || $first > $time) {
								$first = $time;
							}

							if ($last < $time) {
								$last = $time;
							}
						}

						$total_size += $bytes = $a['full_bytes'];
						$full_path = ROOTPRFX.$rel_path;
						$file_list[$name] = "$crc32 $bytes $full_path $name";
					}
				}
			}
		}
	}

	if ($file_list) {
		ksort($file_list, SORT_NATURAL);
		$content = implode(NL, $file_list);
		$hash = md5($content);
		$count = count($file_list);
		$size = format_filesize($total_size);
		$first = date(FILENAME_DATETIME_FORMAT, $first);
		$last = date(FILENAME_DATETIME_FORMAT, $last);
		$f = "$first$s$last$s$size in $count files$s$hash".ARCH_DL_EXT;
		$f = preg_replace('~\s+~u', '_', $f);

		if (!is_file($p = DIR_ARCH_DL.$f.ARCH_DL_LIST_EXT)) {
			file_put_mkdir($p, $content);
		}
	} else {
		$f = '';
	}
	data_unlock($lk);

	return $f;
}

function get_first_arg($text, $before = '"', $after = '"') {
	if ($i = mb_strpos_after($text, $before)) {
		return (
			false !== ($j = mb_strpos($text, $after, $i))
			? mb_substr($text, $i, $j-$i)
			: mb_substr($text, $i)
		);
	}

	return $text;
}

function get_date_class($t_first = 0, $t_last = 0) {	//* <- use time frame for archive pages; default = current date
	global $cfg_date_class, $date_classes;

	if (!$t_first && !$t_last && $date_classes) {
		return $date_classes;
	}

	if (!$t_first) $t_first = T0;
	if (!$t_last) $t_last = $t_first;

	$date_classes = array();

	foreach ($cfg_date_class as $a) if (is_array($a) && $a[0] && $a[1]) {
		$now = array(
			date($a[1], $t_first)
		,	date($a[1], $t_last)
		);
		$due1 = ($a[2] ?: false);
		$due2 = ($a[3] ?: false);
		$flag = ($a[4] ?: 2);
		$wrap = ($due1 !== false && $due2 !== false && $due1 > $due2);	//* <- wrap around new year, etc
		if (!$flag) $flag = 7;

		for ($i = 0; $i < 2; $i++) if ($flag & (1<<$i)) {
			$check = array(
				($due1 === false || $now[$i] >= $due1)
			,	($due2 === false || $now[$i] <= $due2)
			);
			if ($wrap
			?	($check[0] || $check[1])
			:	($check[0] && $check[1])
			) {
				$date_classes[] = $a[0];

				break;
			}
		}
	}

	return $date_classes;
}

function get_draw_app_name($file_path) {
	$app_name = $file_path;

	if (false !== ($index = mb_strrpos_after($app_name, '/'))) {
		$app_name = mb_substr($app_name, $index);
	}

	if (false !== ($index = mb_strrpos($app_name, '.'))) {
		$app_name = mb_substr($app_name, 0, $index);
	}

	return $app_name;
}

function get_draw_app_label($app_name) {
	return get_localized_text('draw_app', $app_name);
}

function get_draw_app_list($allow_upload = true) {
	global $cfg_draw_app;

	if (
		!$allow_upload
	&&	false !== ($index = array_search(DRAW_APP_NONE, $cfg_draw_app))
	) {
		unset($cfg_draw_app[$index]);
	}

	$draw_app_selection_text = get_localized_text('draw_app_select');
	$draw_app_names = array_map('get_draw_app_name', $cfg_draw_app);

	if (
		!($app_name = $GLOBALS['query'][ARG_DRAW_APP] ?: $GLOBALS['u_draw_app'])
	||	!in_array($app_name, $draw_app_names)
	) {
		$app_name = $draw_app_names[0];
	}

	foreach ($draw_app_names as $index => $name) {
		$draw_app_selection_text .= (
			$index
			? ','
			: ':'
		).NL.(
			$app_name === $name
			? get_draw_app_label($name)
			: (
				'<a href="?'.ARG_DRAW_APP.'='.$name.'">'
			.		get_draw_app_label($name)
			.	'</a>'
			)
		);

		if (
			$app_name !== DRAW_APP_NONE
		&&	$app_name === $name
		) {
			$file_path = $cfg_draw_app[$index];
		}
	}

	$result_parts = array(
		'list' => (
			NL
		.	'<p class="hint" id="draw-app-select">'
		.		indent("$draw_app_selection_text.")
		.	'</p>'
		)
	);

	if ($file_path) {
		$ext = get_file_ext($file_path);

		if (!$ext && ($default_ext = get_const('DRAW_APP_DEFAULT_EXT'))) {
			if (false === mb_strrpos($default_ext, '.')) {
				$default_ext = ".$default_ext";
			}

			$file_path .= $default_ext;
			$ext = get_file_ext($file_path);
		}

		if ($file_path[0] !== '/') {
			$file_path = ROOTPRFX.$file_path;
		}

		$result_parts['name'] = $app_name;
		$result_parts['path'] = $file_path;
		$result_parts['path_prefix'] = rtrim(mb_substr($file_path, 0, -mb_strlen($ext)), '/.');

		if (LINK_TIME) {
			$file_path .= '?'.filemtime($_SERVER['DOCUMENT_ROOT'].$file_path);
		}
	}

	if (
		$file_path
	&&	$ext === 'js'
	) {
		$result_parts['noscript'] = '
<noscript>'.indent('<p class="hint">'.get_localized_text('require_js').'</p>').'</noscript>';
		$result_parts['embed'] = '
<div id="draw-app">'.indent(
	'<script id="'.$app_name.'-vars" src="'.$file_path.'" data-vars="'.get_draw_vars($allow_upload ? DRAW_SEND : '').'"></script>'
).'</div>';
	} else
	if ($allow_upload) {
		$result_parts['embed'] = '
<form method="post" enctype="multipart/form-data">
	<b>
		<b><input type="file" name="pic" required></b>
		<b><input type="submit" value="'.get_localized_text('submit').'"></b>
	</b>
	<input type="hidden" name="t0" value="'.T0.'">
</form>';
	} else {
		return;
	}

	return $result_parts;
}

function get_draw_vars($send = '') {
	global $cfg_draw_vars, $cfg_wh;

	$vars = ($send?"$send;":'').DRAW_REST.
		';keep_prefix='.DRAW_PERSISTENT_PREFIX
	.($GLOBALS['u_opts']['save2common']?'':
		';save_prefix='.DRAW_BACKUPCOPY_PREFIX.';saveprfx='.NAMEPRFX
	).($GLOBALS['query'][ARG_ERROR]?
		';preload_last_save=yes'
	:'');

	foreach ($cfg_draw_vars as $k => $v) {
		if (($i = $GLOBALS["u_$v"]) || ($i = get_const($v))) $vars .= ";$k=$i";
	}

	if (($res = $GLOBALS['query']['draw_res']) && false !== mb_strpos($res, 'x')) {
		$wh = array_map('intval', mb_split('x', $res, 2));
	}

	if ($send) {
		foreach (array('DEFAULT_', 'LIMIT_') as $i => $j)
		foreach ($cfg_wh as $k => $l) {
			$p = mb_strtolower(mb_substr($l,0,1)).($i?'l':'');
			if ((!$i && $wh && ($v = $wh[$k])) || ($v = get_const("DRAW_$j$l"))) $vars .= ";$p=$v";
		}
	}

	return csv2nl($vars);
}

function get_flag_vars($a) {
	global $page, $u_opts;

	$r = '';

	foreach ($a as $k => $v) {
		if (!is_array($v)) {
			$mode = $v;
			if ($k == 'caps') $v = array(
				'atm'
			,	array(
					!$u_opts['capture_altclick']
				,	!$u_opts['capture_textselection']
				,	true
				)
			);
		}
		list($letter, $switch) = $v;
		$v = '';
		foreach ($switch as $i => $on) if ($on) $v .= $letter[$i];
		if ($v) {
			$r .= "
$k = $v";		if ($k == 'caps') {
				$r .= "
caps_around = $mode
caps_width = ".DRAW_PREVIEW_WIDTH;
				$page['js']['capture']++;
			}
		}
	}

	return $r;
}

function get_time_html($t = 0) {
	$separator = 'T';
	$f = date(DATE_ATOM, $uint = ($t ?: T0));
	$i = mb_strpos($f, $separator);
	$d = mb_substr($f, 0, $i);
	$t = mb_substr($f, $i + mb_strlen($separator), 8);

	return '<time datetime="'.$f.'" data-t="'.$uint.'">'.$d.' <small>'.$t.'</small></time>';
}

function get_time_elapsed($t = 0) {
	$t = explode(' ', $t ?: microtime());

	return ($t[1]-T0) + ($t[0]-M0);
}

function get_time_seconds($t) {
	if (mb_strrpos($t, ':') > 0) {
		$sec = 0;

		foreach (explode(':', $t) as $n) {
			$sec = $sec*60 + intval($n);
		}

		return ($t[0] == '-'?-$sec:$sec);
	}

	return floor($t);	//* <- 32-bit signed integer overflow workaround
}

function format_time_units($t) {
	while (is_array($t)) {
		$t = reset($t);
	}

	foreach (get_localized_text_array('time_units') as $k => $v) if ($t >= $k) {
		if ($k) {$rem = $t%$k; $t = floor($t/$k);}
		$i = count($v)-1;
		if ($t < 11 || $t >= 20) {
			$s = $t%10;
			if ($s == 1) $i = 0; else
			if ($s > 1 && $s < 5) $i = 1;
		}
		$r .= ($r?' ':'').$t.' '.$v[$i];
		if ($rem) $t = $rem; else break;
	}

	return $r;
}

function format_filesize($b = 0, $frac = 2) {
	$m = 'BkMGTPEZY';

	if ($i = floor((strlen($b) - 1) / 3)) {
		$b = sprintf("%.{$frac}f", $b/pow(1024, $i));

		return "$b $m[$i]$m[0]";
	}

	return "$b $m[0]";
}

function format_matched_link($a) {
	if ($m = $a['a'] ?: $a['img']) {
		$url = $text = $m;

		if ($a['img']) {
			$text = '<img src="'.$url.'" alt="'.$text.'" class="'
			.(
				($m = ($a['align'] ?: '')[0])
			&&	($m == 'l' || $m == 'r')
				? $m
				: 'center'
			).'">';
		}

		return '<a href="'.$url.'" rel="nofollow">'.$text.'</a>';
	}

	return $a[0];
}

function format_post_text($text, $uncut = '') {
	if (!$uncut) {
		$uncut = $text;
	}

	if (
		is_prefix($uncut, POST_LINE_BREAK)
	&&	is_postfix($uncut, POST_LINE_BREAK)
	&&	mb_substr_count($text = trim($text, $spaced = ' '.POST_LINE_BREAK.' '), $spaced)
	) {
		return'<i class="poem">'
		.	mb_str_replace($spaced, '<br>',
			preg_replace('~\s+('.POST_LINE_BREAK.'\s+){2,}~', '<br><br>',
				trim($text, $spaced)
			))
		.'</i>';
	}

	return $text;
}

function delay_timeout($add_sec = 10) {
	if (function_exists('ini_get') && function_exists('ini_set')) {
		$m = 'max_execution_time';
		ini_set($m, ini_get($m) + max(1, intval($add_sec)));
	}
}

function optimize_pic($filepath) {
	if (
		function_exists('exec')
	&&	($f = $filepath)
	&&	($ext = get_file_ext($f))
	&&	is_file($f)
	) {
		$out_path = $f.($out = '.out');
		$bad_path = $f.($bad = '.bad');
		$bak_path = $f.($bak = '.bak');
		$d = DIRECTORY_SEPARATOR;

		global $cfg_optimize_pics, $cfg_optimize_pics_not_supported;

		foreach ($cfg_optimize_pics as $format => $tool) if ($ext == $format)
		foreach ($tool as $tool_params) {
			list($program, $command, $retries, $copy_suffix) = array_map('mb_normalize_slash', $tool_params);

			$program_name = get_file_name($program);

			if (
				is_file($program_path = $program)
			||	is_file($program_path .= '.exe')
			) {
				$program_path = "./$program_path";
			} else
			if (PIC_OPT_TRY_GLOBAL_EXEC) {
				$program_path = $program_name;
			} else {
				continue;
			}

			$retries = intval($retries);

			retry_this_command:

			if (
				isset($copy_suffix)
			&&	strlen($copy_suffix)
			) {
				$file_path_arg = $f.$copy_suffix;
				copy($f, $file_path_arg);
			} else {
				$file_path_arg = $f;
			}

			$return_code = $size = 0;
			$output = array('');
			$cmd = sprintf($command, $program_path, $file_path_arg);

			if ($d !== '/') {
				$cmd = str_replace('/', $d, $cmd);
			}

			delay_timeout(PIC_OPT_ADD_TIMEOUT);

			data_lock($lk = LK_PIC_OPT);
			$return = exec($cmd, $output, $return_code);
			data_unlock($lk);

			$not_supported = false;

			if ($not_supported_text_parts = $cfg_optimize_pics_not_supported[$format]) {
				foreach ((array)$output as $output_line)
				foreach ($not_supported_text_parts as $not_supported_text_part) {
					if (false !== strpos($output_line, $not_supported_text_part)) {
						$not_supported = true;

						break 2;
					}
				}
			}

			$error_or_not_supported = ($not_supported || $return_code);
			$done = ($error_or_not_supported ? 'error' : 'done');

			if (is_file($out_path)) {
				$size = filesize($f);
				$out_size = filesize($out_path);
				$old = "old $f = $size bytes";
				$new = "new $out_path = $out_size bytes";
				if (
					$out_size
				&&	($out_size < $size)
				&&	!$error_or_not_supported
				&&	unlink($f)
				) {
					$ren = (rename($out_path, $f) ? 'renamed' : 'could not rename');
					$done .= ",\n deleted $old,\n $ren $new -> $f";
				} else
				if (is_file($f)) {
					$done .= ",\n kept $old";
					if (is_file($out_path)) {
						$del = (unlink($out_path) ? 'deleted' : 'not deleted');
						$done .= ",\n $del $new";
					}
				}
			} else
			if (is_file($bak_path) && ($bak_size = filesize($bak_path))) {
				$del = (
					is_file($f)
					? (
						($size = filesize($f))
						? (
							($rest = rename($f, $bad_path))
							? "renamed to *$bad"
							: 'not renamed'
						) : (
							($rest = unlink($f))
							? 'deleted'
							: 'not deleted'
						)
					) : ($rest = 'not found')
				);
				if ($rest) $rest = ".\nRestoring from $bak_path = $bak_size bytes, ".(
					!is_file($f) && rename($bak_path, $f)
					? 'done'
					: 'failed'
				);
				$done = "failed, $f = $size bytes, $del$rest";
				if (!$return_code) $return_code = '0, fallback';
			}

			if (!$error_or_not_supported) {
				return $program_name;
			}

			if (!$not_supported) {
				$o = trim(preg_replace('~\v+~u', NL, implode(NL, (array)$output)));

				if (strlen($o)) {
					if (false !== mb_strpos($o, NL)) {
						$o = NL.'['.indent($o).']';
					}
				} else {
					$o = 'empty';
				}

				data_log_action(
"Optimization $done.
Program name: $program_name
Command line: $cmd
Return code: $return_code
Return text: $return
Shell output: $o"
				);

				if ($retries > 0) {
					--$retries;
					goto retry_this_command;
				}
			}
		}
	}
}

//* https://stackoverflow.com/a/1455610
function get_system_memory_info() {
	if (function_exists($f = 'win32_ps_stat_mem')) {
		return $f();
	}

	$array = array();

	if ($lines = get_file_lines('/proc/meminfo')) {
		foreach ($lines as $line) {
			list($key, $val) = explode(':', $line);
			$array[$key] = trim($val);
		}
	}

	return $array;
}


//* front end templates -------------------------------------------------------

function indent($t, $n = 0) {
	if (
		strlen($t = trim($t))
	&&	($n || false !== mb_strpos($t, NL))
	) {
		$before = str_repeat("\t", $n > 0?$n:1);
/*
//* this regex fails on 1 MB of text with PREG_BACKTRACK_LIMIT_ERROR: backtrack limit was exhausted.
		$t = preg_replace(
			'~(?:^|\v+)(?:(\h*<(pre|textarea)\b.+?)(?=\v+\h*</\2>))?~uis'
		,	NL.$before.'$1'
		,	$t
		).NL;
*/
		$in = false;
		$t = NL.preg_replace_callback(
			'~
				(?<=^|\v)
				\h*
				(?:
					<
					(?P<openTag>pre|textarea)
					\b\V*?
				)?
				(?:
					</
					(?P<closeTag>pre|textarea)
					>
				)?
			~imux'
		,	function($match) use ($before, &$in) {
				if ($in) {
					$add = false;
				} else {
					$add = $before;

					if ($tag = $match['openTag']) {
						$in = $tag;
					}
				}

				if ($in && ($tag = $match['closeTag']) && ($in === $tag)) {
					$in = false;

					if (!$add && !$match['openTag']) {
						$add = $before;
					}
				}

				return $add ? $add.$match[0] : $match[0];
			}
		,	$t
		).NL;
		log_preg_last_error(false);
	}

	return $t;
}

function csv2nl($v, $c = ';', $n = 1) {
	$d = "\s*[$c]+\s*";
	$n = NL.($n > 0 ? str_repeat("\t", $n) : '');

	return $n.implode($c.$n, preg_split("~$d~u", preg_replace("~^$d|$d$~u", '', $v))).$c.NL;
}

function get_template_attr($a = '', $prefix = 'data-') {
	foreach ((array)$a as $k => $v) if (strlen($v)) {
		$line .= ' '.$prefix.$k.'="'.$v.'"';
	}

	return $line ?: '';
}

function get_template_welcome_row($separator, $a, $cfg_k = '') {
	global $cfg_welcome_links;

	$a = (array)$a;

	foreach ($a as $k => $v) if ($v) {
		if ($cfg_k && ($i = $cfg_welcome_links[$cfg_k][$k])) {
			$txt = $v;
			$url = $i;
		} else
		if ($k !== intval($k)) {
			$txt = $k;
			$url = $v;
		} else {
			continue;
		}

		if (
			false === ($i = mb_strpos($txt, '<img'))
		||	false === ($j = mb_strpos_after($txt, '>', $i))
		) {
			continue;
		}

		if (is_array($url)) {
			list($f, $g) = $url;
			$url = (function_exists($f) ? $f($g) : $g);
		}

		$a[$k] = indent(
			mb_substr($txt, 0, $i)
		.NL.	'<a href="'.$url.'" target="_blank">'
		.NL.	'	'.mb_substr($txt, $i, $j-$i)
		.NL.	'</a>'
		.NL.	mb_substr($txt, $j)
		);
	}

	return implode($separator, $a);
}

function get_template_welcome_see_do($c, $u, $see = '', $do = '') {
	$user = NL."<td>	$u[who]	</td>";
	$skip = NL.'<td>	...	</td>';
	$class = '<td class="thread">';
	$td = '<td></td><td></td>';

	return (
		'<tr class="'.$c.' see">'.indent(
			$user.$skip.$class.get_template_welcome_row(
				'</td>'.NL.'<td class="then"></td>'.$class
			,	$see ?: array(
					"$u[desc_see]:"
				,	"$u[pic_see]:"
				,	"$u[desc_see]:"
				)
			,	$see?'see':''
			).'</td>'.$skip.$user
		).NL.'</tr><tr class="'.$c.' do">'.indent(
			$td.NL.$class.get_template_welcome_row(
				'</td><td></td>'.NL.$class
			,	$do ?: array(
					$u['desc_do']
				,	$u['pic_do']
				,	$u['desc_do']
				)
			,	$do?'do':''
			).'</td>'.NL.$td
		).'</tr>'
	);
}

function get_template_welcome_interleave($c = '', $t = '') {
	if ($c) $c = ' class="'.$c.'"';
	if ($t) $t = "	$t	";
	$d = '<td></td>';
	$a = NL.$d.$d;
	$b = NL.'<td class="thread">'.$t.'</td>';

	return "<tr$c>".indent("$a$b$d$b$d$b$a").'</tr>';
}

function get_template_welcome($a) {
	$i = '<br><img src="'.ROOTPRFX.NAMEPRFX.'.demo';
	$e = '.png">';
	$sdo = get_template_welcome_see_do($v = 'other', $a[$v]);
	$sdu = get_template_welcome_see_do($v = 'you', $u = $a[$v]
	,	array(
			"$u[pic_see]:	$i.1a$e"
		,	"$u[desc_see]:	$i.2a$e"
		,	"$u[pic_see]:	$i.3a$e"
		)
	,	array(
			"$u[pic_do]	$i.1b$e"
		,	"$u[desc_do]	$i.2b$e"
		,	"$u[pic_do]	$i.3b$e"
		)
	);

	return (
		(($i = $a['header']) ? "<p>$i</p>".NL : '')
		.'<table>'
		.indent(
			get_template_welcome_interleave('prev', $a['head']).$sdo
		.	get_template_welcome_interleave('prev').$sdu
		.	get_template_welcome_interleave('next').$sdo
		.	get_template_welcome_interleave('next', $a['tail'])
		)
		.'</table>'
		.(($i = $a['footer']) ? NL."<p>$i</p>" : '')
	);
}

function get_template_profile_text($t) {
	$p = strtolower($_SERVER['REQUEST_SCHEME'] ?: 'http');

	return mb_str_replace(RELATIVE_LINK_PREFIX, "$p://$_SERVER[SERVER_NAME]/", $t);
}

function get_template_profile_html($t) {
	$p = 'https?';	//* <- protocols allowed in links
	$s = '"\s<>';	//* <- characters not allowed in links
	$pat = "~
		(?P<a>(?:$p)://[^$s/]/*[^$s]*?)(?=[,.]*(?:[$s]|$))
	|	\[\s*(?P<img>(?:$p)://[^$s/][^$s]*?)(?:\s+(?P<align>left|right|center)|)?\s*\]
	~iux";
	$t = get_template_profile_text($t);
	$t = preg_replace_callback($pat, 'format_matched_link', $t);
	$t = preg_replace('~(<br>)+~u', '$0'.NL, $t);

	return $t;
}

function get_template_form($t) {
	if (is_array($t)) {
		extract($t);
	} else {
		$name = $t;
	}

	if (is_array($a = $select)) {
		$n = '';

		foreach ((array)$a as $k => $v) {
			if (!$name) $name = $k;
			$n .= NL."	$k	$v[select]	$v[placeholder]";
		}

		$select = htmlspecialchars($n, ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5).NL;
	}

	if ($name) {
		foreach (
			explode(',', 'head,hint,placeholder,submit') as $v
		) if (!$$v) {
			$$v = (
				get_localized_or_empty_text($name.($v == 'head'?'':"_$v"))
			?:	get_localized_or_empty_text($v)
			);
		}
	}

	if ($a = $method) $method = ' method="'.$a.'"';
	if ($a = $filter) $attr .= ' id="filter" data-filter="'.$a.'"';
	if ($a = $placeholder ?: ($a ? get_localized_text('filter_placeholder') : '')) $attr .= ' placeholder="'.$a.'"';
	if (!$GLOBALS['u_opts']['focus']) $attr .= ' autofocus';

	if ($name) {
		$name = ' name="'.$name.'"'.$attr.' required';
		if ($min||$max) $name .= (
			$textarea
			? ($min?' minlength="'.$min.'"':'').($max?' maxlength="'.$max.'"':'')
			: ' pattern="\s*(\S\s*){'.($min ?: 0).','.($max ?: '').'}"'
		);
	}

	if ($head) {
		$head = NL
		.'<p>'
		.	indent(false === strpos($head, ':') ? "$head:" : $head)
		.'</p>';
	}

	if ($a = $hint) {
		$hint = '';
		foreach ((array)$a as $k => $v) $hint .= NL
		.'<p class="hint'.($k?" $k":'').'">'
		.	indent(get_template_hint(
				is_array($v)
				? implode(',', array_map(
					function($a) {
						return is_array($a) ? $a[0] : $a;
					}
				,	$v
				))
				: $v
			))
		.'</p>';
	}

	if ($a = $checkbox) {
		if (!is_array($a)) $a = array('label' => $a);
		if ($n = $a['name'] ?: 'check') $n = ' name="'.$n.'"';
		if ($r = $a['required'] ?: '') $r = ' required';
		$checkbox = NL
		.	'<label class="r">'
		.		indent("$a[label]:".NL.'<input type="checkbox"'.$n.$r.'>')
		.	'</label>';
	}

	if (($a = $radiogroup) && is_array($a)) {
		if (($o = $a['options']) && is_array($o)) {
			$n = $a['name'];
		} else {
			$o = $a;
		}
		$radiogroup = '';
		$checked = ' checked';
		$n = ' name="'.($n ?: 'radio').'"';
		foreach ($o as $k => $v) {
			$v = array(
				'<span>'
			.		indent(implode(NL, (array)$v))
			.	'</span>'
			);
		//	foreach ($v as &$t) $t = "<span>$t</span>"; unset($t);
			array_unshift($v, '<input type="radio"'.$n.' value="'.$k.'"'.$checked.'>');
			array_push($v, '&mdash;');
			$radiogroup .= NL
			.	'<label>'
			.		indent(implode(NL, $v))
			.	'</label>';
			$checked = '';
		}
	}

	$submittable = ($submit || $method);

	return $head.NL.(
		$name || $method
		? "<form$method>".indent(
			'<b>'.indent(
				'<b><'.(
					$textarea
					? "textarea$name></textarea"
					: 'input type="text"'.$name
				).(
					$select
					? ' data-select="'.$select.'"'
					: ''
				).'></b>'.(
					$submittable
					? NL.'<b><input type="submit" value="'.($submit ?: get_localized_text('submit')).'"></b>'
					: ''
				)
			).'</b>'
			.$checkbox
			.(
				$radiogroup
				? NL.'<div class="r radiogroup">'.indent($radiogroup).'</div>'
				: ''
			).(

//* about "_charset_":
//* https://www.w3.org/TR/html5/forms.html#naming-form-controls:-the-name-attribute

				$submittable
				? NL.'<input type="hidden" name="_charset_">'.(
					$GLOBALS['u_key']
					? ''
					: NL.'<input type="text" name="pass" value="" placeholder="'.(
						get_localized_or_empty_text('spam_trap')
						?: 'spam'
					).'">'
				)
				: ''
			)
		).'</form>'
		: '<p><b><input type="text"'.$attr.'></b></p>'
	).$hint;
}

function get_template_hint($t) {
	return str_replace(
		'href="+"'
	,	'href="javascript:void this" onClick="toggleClass(this.nextElementSibling,\'hid\')"'
	,	str_replace(
			str_split('{`^}[|]\\')
		,	array('<a href="', '" onClick="', '" target="_blank" rel="noopener', '</a>', '<span class="', '">', '</span>', NL)
		,	nl2br(
				htmlspecialchars(
					preg_replace(
						'~([\\\\])\\v+~u'
					,	'$1'
					,	preg_replace_callback(
							'~\b\d+s\b~u'
						,	'format_time_units'
						,	$t
						)
					)
				)
			,	false
			)
		)
	);
}

function get_template_menu($top_line, $items) {
	if (is_array($items)) {
		$items = array_map('indent', $items);
		$items = implode('</u>'.NL.'<u>', $items);
		$items = "<u>$items</u>";
	}

	return (
		'<u class="menu-head">'.indent(
			$top_line.NL.
			'<u class="menu-top">'.
			'<u class="menu-hid">'.
			'<u class="menu-list">'.indent(
				$items
			).'</u></u></u>'
		).'</u>'
	);
}

function get_template_content($p, $is_static_page = false, $tag = '', $attr = '', $data_listing = '') {
	if ($data_listing) {
		$a = (array)$p;
		$p = '';

		if (is_array($data_listing)) {
			$separator = $data_listing['next-item'] ?? NL;
			$data_listing = $data_listing['key-value'] ?? NL;
		} else {
			$separator = NL;
		}

		foreach ($a as $k => $v) {
			$p .= ($p ? $separator : '').$k.$data_listing.$v;
		}

		$p = "
<pre$attr>$p
</pre>";
	} else
	if (is_array($p)) {
		$a = $p;
		$p = '';

		foreach ($a as $k => $v) if (!$k) {
			$p .= $v;
		} else if ($v) {
			$attr .= " $k=\"$v\"";
		}
	}

	if (strlen($p)) {
		if (GOD && !$is_static_page && ($v = $GLOBALS[$k = 'fix_encoding_chosen'])) {
			$v = implode(',', (array)$v);
			$p = "
$k = $v$p";
		}
		$t = $tag ?: 'pre';
		$p = "
<$t$attr>$p
</$t>
<noscript>".indent(
	'<p class="hint report">'
	.(
		$is_static_page
		? 'JavaScript support required.'
		: get_localized_text('require_js')
	)
	.'</p>'
).'</noscript>';
		$p = (
			'<div class="'.(
				$is_static_page
				? 'thread'
				: 'content" id="content'
			).'">'
			.	indent($p)
			.'</div>'
		);

		return $p;
	}

	return '';
}

function get_template_page($page) {
	global $cfg_langs, $draw_test, $room, $tcp;

	if (!is_array($j = $page['js'] ?: array())) {
		$j = array($j => 1);
	}

	$file_path_prefix = ROOTPRFX.NAMEPRFX;

	$data_listing = $page['data_listing'] ?? $page['listing'] ?? '';
	$link_here = $page['link_here'] ?? $page['link'] ?? '';

	$is_archived_page = !!$j['arch'];
	$is_static_page = !!($data_listing || $is_archived_page);

	$page_class = (
		($v = $page['page_class'] ?? $page['body'])
		? (array)$v
		: array()
	);

	$anno = array();

	if ($page['anno']) {
		foreach (data_global_announce('all') as $k => $v) {
			if (strlen($v)) {
				if (MOD) $v = "<span id=\"$k\">$v</span>";
				$v = ": $v";
				$c = (false !== mb_strpos($k, 'stop')?'cold':'dust');
			} else {
				$c = 'new';
			}

			$anno[$c][] = get_localized_text('announce', $k).$v;
		}
	}

	if (!$is_static_page) {
		$is_linked_file_modtime_needed = LINK_TIME;

		if ($a = $page['report']) {
			$e_class = array(
				'trd_arch' => 'trd-arch'
			,	'trd_miss' => 'trd-miss'
			);

			if (!is_array($a)) {
				$a = mb_split_filter($a, ARG_ERROR_SPLIT);
			}

			foreach ($a as $v) if ($v = trim($v)) {
				$anno[$e_class[$v] ?: 'report'][] = get_localized_text('post_err', $v);
			}
		}

		if (
			($t = $page['mod_act_log'])
		&&	($a = data_get_mod_log($t))
		) {
			$anno['mod_act_log al'] = (array)$a;
		}

		if (FROZEN_HELL) {
			$page_class[] = 'frozen-hell';
		}

		if ($d = get_date_class()) {
			$page_class = array_merge($page_class, $d);
		}
	}

	if ($a = $page[$k = 'welcome']) {
		if (
			is_array($a)
			? ($a = get_template_welcome($a))
			: $a
		) {
			$$k = '<div class="'.$k.'">'.indent($a).'</div>';
		}
	}

	if ($a = $page[$k = 'profile']) {
		$$k = '<div class="'.$k.' al">'.indent($a).'</div>';
	}

	if ($a = $page[$k = 'content']) {
		$attr = get_template_attr($page['data'][$k]);
		$content = get_template_content($a, $is_static_page, '', $attr, $data_listing);
	}

	if ($link_here || !ME_VAL) {
		$k = (array)($GLOBALS['cfg_link_canonical_base'] ?: $GLOBALS['cfg_link_schemes'] ?: '');
		if ($k = $k[0]) {
			if (false === strpos($k, '/')) {
				$k = "$k://$_SERVER[SERVER_NAME]";
			}

			if ($draw_test) {
				$v = ROOTPRFX.'?'.ARG_DRAW_APP.'='.$draw_test;
			} else
			if ($page['signup']) {
				$v = ROOTPRFX;
			} else {
				$v = $link_here ?: $_SERVER['REQUEST_URI'];
			}

			$k = rtrim($k, '/.');
			$v = ltrim($v, '/.');
			$canonical_full_link = "$k/$v";
		}
	}

	$head = array();

	$head['meta'] = array(
		'<meta charset="'.ENC.'">'
	,	($data_listing?'':'<meta name="viewport" content="width=690">')
	);

	$head['links'] = array(
		(
			$data_listing
			? ''
			: '<link rel="stylesheet" type="text/css" href="'
			.	$file_path_prefix.($v = '.css')
			.	($is_linked_file_modtime_needed ? '?'.filemtime(NAMEPRFX.$v) : '')
			.'">'
		)
	,	'<link rel="shortcut icon" type="image/png" href="'.(
			($v = $page['icon'])
			? ($v[0] !== '/' ? ROOTPRFX.$v : $v)
			: $file_path_prefix
		).'.png">'
	,	(
			$canonical_full_link
			? '<link rel="canonical" href="'.$canonical_full_link.'">'
			: ''
		)
	,	(
			$is_archived_page || ME_VAL
			? '<link rel="index" href="//'.$_SERVER['SERVER_NAME'].ROOTPRFX.(
				$is_archived_page || $room
				? '" data-room="'.(
					$is_archived_page
					? ($page['room'] ?: $page['title'])
					: $room
				)
				: ''
			).'">'
			: ''
		)
	);

	$head['title'] = (($v = $page['title']) ? "<title>$v</title>" : '');
	$head['head'] = $page['head'] ?: '';

	if ($a = $page[$k = 'meta']) {
		foreach ($a as $n => $v) if ($v) {
			$head[$k][] = '<meta property="'.$n.'" content="'.$v.'">';
		}
	}

	if ($a = $page[$k = 'links']) {
		foreach ($a as $n => $v) if ($v) {
			$head[$k][] = '<link rel="'.$n.'" href="'.$v.'">';
		}
	}

	if ($a = (array)$anno) {
		ksort($a);
		$i = ($page['anno']?'anno':'r');
		foreach ($a as $k => $v) {
			$block = '';
			if (is_array($v)) foreach ($v as $line) {
				$block .= NL.'<b>'.indent($line).'</b>';
			}
			$anno_lines .= NL.'<p class="'.$i.($k ? " $k" : '').'">'.indent($block ?: $v).'</p>';
		}
		if ($page['anno']) {
			$header .= $anno_lines;
			$anno_lines = '';
		}
	}

	if ($a = $page[$k = 'header']) {
		if (is_array($a)) {
			foreach ($a as $i => &$v) if ($v) {
				$v = ($i ? '<u class="'.$i.'">' : '<u>').indent($v).'</u>';
			}
			unset($v);
			$a = implode(NL, $a);
		}
		$header .= NL.'<p>'.indent($a).'</p>';
	}

	if ($header) {
		$i = '"'.$k.'"';
		$attr = get_template_attr($page['data'][$k]);
		$header = "<$k id=$i class=$i$attr>".indent($header)."</$k>";
	}

	if ($v = $page[$txt = 'textarea']) {
		$$txt = NL.trim(htmlspecialchars($v));
	}

	if ($v = $page[$k = 'task']) {
		$attr = get_template_attr($page['data'][$k]);

		if ($sub = $page['subtask']) {
			$v = '<div class="task">'.indent($v).'</div>'.$sub;
		} else
		if (!$is_static_page) {
			$attr = ' class="task"'.$attr;
		}

		if ($sub = $$txt) {
			$k = ' class="dump" id="dump"';
			$v .= "
<div$k>
	<$txt>$sub
	</$txt>
</div>";
		}
		$task = '<div id="task"'.$attr.'>'.indent($anno_lines.$v).'</div>';
	} else
	if ($v = $$txt) {
		$content .= get_template_content($anno_lines.$v, $is_static_page, $txt);
	}

	if ($v = $page['footer']) {

		if (TIME_PARTS) {
			foreach ($tcp as $t => $comment) {
				$t = get_time_elapsed($t);
				$t_diff = ltrim(sprintf('%.6f', $t - $t_prev), '0.');
				$t = sprintf('%.6f', $t_prev = $t);

				$comment = preg_replace('~\v+~u', '<br> - ', (
					is_array($comment)
					? implode('<br>', $comment)
					: $comment
				));

				$took_list .= (
					NL
					."<tr><td>$t +</td><td>$t_diff:</td><td>$comment</td></tr>"
				);
			}
		}
		if ($took_list) {
			$v .= (
				NL
				.'<table id="took" style="display:none">'
				.	indent($took_list)
				.'</table>'
			);
		}

		$footer = '<footer>'.indent($v).'</footer>';
	}

	if ($j) foreach ($j as $k => $v) {
		$scripts .= (
			NL
			.'<script src="'
			.	$file_path_prefix.($v = ($k ? ".$k" : '').'.js')
			.	($is_linked_file_modtime_needed ? '?'.filemtime(NAMEPRFX.$v) : '')
			.'"></script>'
		);
	}

	$page_class = (
		$page_class
		? ' class="'.get_imploded_non_empty_lines($page_class, ' ').'"'
		: ''
	);

	$after_content = (
		indent($footer, 1)
	.	indent($scripts, 1)
	);

	return (
'<!doctype html>
<html lang="'.($page['lang'] ?: get_const('LANG') ?: $cfg_langs[0] ?: 'en').'">
<head>'
.indent(get_imploded_non_empty_lines($head))
.'</head>
<body'.$page_class.'>'
.indent($header, 1)
.indent($task, 1)
.indent($welcome, 1)
.indent($profile, 1)
.indent($content, 1)
.(
	$after_content && ($k = get_const('TOOK'))
	? str_replace($k, round(get_time_elapsed(), 9), $after_content)
	: $after_content
)
.'</body>
</html>'
	);
}

?>
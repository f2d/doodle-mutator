<?php

//* ---------------------------------------------------------------------------
//* There is no fallback for most part.
//* Omit anything and you'll get constant name as its value, or array errors.
//* ---------------------------------------------------------------------------

$cfg_langs = array($lang = 'en', 'ru');
$cfg_link_schemes = array('http', 'https');
$cfg_link_canon = 'https://www.example.com/';	//* <- main domain address, for search engines
$cfg_dir = array(
//* Format: ID => subfolder
//* real folder with accessible static files:
	'arch' => 'archive'
,	'user' => 'profile'
//* not real, interpreted by script after URL rewrite:
,	'opts' => 'options'
,	'room' => 'room'
//* any other subfolder is unrelated and free to use.
);
$cfg_game_type_dir = array(
//* Format: ID => sub-subfolder
	'1dpd' => ''		//* <- single drawing per description, a.k.a. the "blind phone" game
,	'simd' => 'batch'	//* <- multiple drawings per single desc. (OP) per thread
,	'draw' => 'continue'	//* <- multiple drawings, no text posts
,	'text' => 'text_pile'	//* <- endless text posts, no drawings
,	'1trd' => 'dump'	//* <- single thread per room, no thread locking, random mess
);
define('GAME_TYPE_DEFAULT', reset($cfg_game_type_dir));	//* <- if this or none of types is empty, no-subfolder will not be allowed

define('BOARD_LINK', '/board/');	//* <- prepend ROOTPRFX (the script folder) here for a relative link
define('DIR_ARCH_DL', 'dl/');	//* <- real folder to keep listfiles for zip streaming
define('DIR_PICS', 'i/');		//* <- real folder
define('DIR_PICS_DEL', DIR_PICS.'deleted/');
define('DIR_PICS_ORPHAN', DIR_PICS.'orphan/');
define('DIR_THUMB', 'th/');	//* <- subfolder in each room archive
define('PIC_404', 'err.png');	//* <- filename for htaccess and nginx cfg example

define('DIR_DOTS', false);	//* <- use href="./.." in top menu
define('LINK_TIME', false);	//* <- src=file?modtime to force reload at clients
define('LOG_IP', true);		//* <- for each visit, add IP Address to separate file per user
define('LOG_UA', false);		//* <- for each post with pic, add User-Agent field to the post itself
define('PIC_SUB', false);		//* <- true: img src="/i/p/0/0123.png", false: src="/i/0123.png" and leave it to rewrite rule
define('ARCH_DL_ENABLED', false);	//* <- currently requires nginx build with zip streaming module

define('NOT_MOD_SEE_ACTIVE_TRD_REPORTS', true);
define('NOT_MOD_SEE_STOPPED_TRD', true);
define('NOT_MOD_SEE_ROOM_MARKERS', true);

//* ---------------------------------------------------------------------------

define('ROOM_DEFAULT', 'base');
define('ROOM_NAME_ALLOWED_CHARS', '\x{0400}-\x{04ff}\x{2460}-\x{2468}\x{2605}-\x{2606}');	//* <- beside prefixes, 0-9, A-Z, underscore and minus
define('ROOM_NAME_MIN_LENGTH', 1);
define('ROOM_NAME_MAX_LENGTH', 26);
define('USER_NAME_MIN_LENGTH', 1);
define('USER_NAME_MAX_LENGTH', 26);
define('FIND_MIN_LENGTH', 1);
define('FIND_MAX_LENGTH', 123);
define('REPORT_MIN_LENGTH', 5);
define('REPORT_MAX_LENGTH', 500);
define('DESCRIBE_MIN_LENGTH', 9);
define('DESCRIBE_MAX_LENGTH', 900);

define('DRAW_APP_NONE', 'no');
define('DRAW_APP_DEFAULT_EXT', '.js');
define('PIC_OPT_ADD_TIMEOUT', 30);	//* <- seconds added per each program tried
define('PIC_OPT_TRY_GLOBAL_EXEC', false);
/*
 * Global exec notes:
	If program is not found first in specified or current folder,
	try to rely on OS and environment paths.
	This will spam into mod logs if program is not found by OS,
	so remove or comment out lines of those you don't have installed.
*/

$cfg_draw_app = array('dfc', 'milf', DRAW_APP_NONE);
$cfg_draw_file_types = array('png', 'jpg', 'jpeg');
$cfg_optimize_pics = array(
/*
 * Format:
	'file_ext' => array(
		array(
			'program name or path',
			'command line format string, first %s = program path, 2nd %s = source file path',
			[optional number of additional retries],
		),
	),
 * JPEG notes:
	JpegOptim and JpegTran produce bit-identical file results.
	JpegOptim can run a batch and rewrites the source files by default.
	JpegTran can run only a single file and possibly dumps file content into stdout/console.
	Both remove appended data (e.g. rarjpg) even without any stripping options.
 * PNG notes:
	OptiPng v0.7.6 makes a bit smaller or same files as OxiPng v0.15.1 with defaults, therefore it is tried first.
	OptiPng v0.7.6 rarely fails with code 9, leaving no/empty/part of result file + source backup copy.
	OptiPng v0.7.7 seems to not fail anymore, see: https://sourceforge.net/p/optipng/bugs/66/
	OxiPng with -Z (zopfli) takes forever to finish, but makes the smallest result (not much difference).
	OxiPng with -Z takes too much CPU if thread count is too high (> 1, and especially > 6).
	Both remove appended data (e.g. rarpng), but OptiPng - only with -fix option.
 * Overall notes:
	All of these programs must be installed manually and PHP must be allowed to run them with exec().
	Script will try to run them in given order until OK exit code is met.
 * Links to sources of programs:
	JpegOptim: http://freecode.com/projects/jpegoptim/
	JpegTran: http://jpegclub.org/jpegtran/
	OptiPng: http://optipng.sourceforge.net/
	OxiPng: https://github.com/shssoichiro/oxipng
	PngOptimizer: https://psydk.org/pngoptimizer
*/
	'jpg' => array(
		array('jpegoptim', '"%s" --all-progressive "%s" 2>&1'),
		array('jpegtran', '"%1$s" -progressive -optimize -outfile "%2$s.out" "%2$s" 2>&1'),
	//* remove metadata:
	//	array('jpegoptim', '"%s" --all-progressive --strip-all "%s" 2>&1'),
	//	array('jpegtran', '"%1$s" -progressive -optimize -copy none -outfile "%2$s.out" "%2$s" 2>&1'),
	),
	'png' => array(
		array('optipng', '"%s" -v -i 0 -fix "%s" 2>&1', 1),
		array('oxipng', '"%s" -v -i 0 --fix -t 1 "%s" 2>&1'),
		array('pngoptimizercl', '"%1$s" -stdio < "%2$s" > "%2$s.out" 2>&1'),
	),
);

$cfg_optimize_pics_not_supported = array(
	'png' => array(
		'APNG not supported',
		'APNG is not supported',
		'APNG files are not supported',
		'APNG files are not (yet) supported',
		"Can't reliably reduce APNG",
	),
);

define('DRAW_PREVIEW_WIDTH', 640);
define('DRAW_DEFAULT_WIDTH', 640);
define('DRAW_DEFAULT_HEIGHT', 360);
define('DRAW_LIMIT_WIDTH', '100,1920');
define('DRAW_LIMIT_HEIGHT', '100,1920');
define('DRAW_MAX_FILESIZE', 8388608);	//* <- bytes, 8MiB = 8*1024*1024
define('DRAW_MAX_RECOVERY', 9);
define('DRAW_MAX_UNDO', 99);
define('DRAW_TIME_IDLE', 300);		//* <- seconds, 5min
define('DRAW_PERSISTENT_PREFIX', NAMEPRFX.'Keep');
define('DRAW_BACKUPCOPY_PREFIX', NAMEPRFX.'Save');
define('DRAW_SEND', 'send=layers.json, log.json, pic.png, jpg>1002003; check=checkStatus');
define('DRAW_REST', 'resize_style=body, #task; resize_min_id=header');

define('QK_KEEP_AFTER_LOGOUT', false);
define('QK_EXPIRES', 100200300);	//* <- seconds; renewed with every successful POST, incl.options
define('POST_PIC_WAIT', 5);	//* <- seconds

define('TARGET_AUTOUPDATE_INTERVAL', 3600);//* 1h
define('TARGET_CHANGE_TIME', 600);//* 10min
define('TARGET_DESC_TIME', 1200);	//* 20min
define('TARGET_DRAW_TIME', 7200);	//* 2h
define('TARGET_LONG_TIME', 86400);//* 24h
define('TRD_ARCH_TIME', 86400);	//* 24h
define('TRD_MAX_POSTS', 10);
define('TRD_MAX_PER_ROOM', 30);
define('TRD_MAX_SKIP_PER_ROOM', 5);
define('TRD_PER_PAGE', 30);

define('POST_LINE_BREAK', '/');	//* <- used with spaces around
define('ARCH_DL_NAME_PART_SEPARATOR', ' - ');
define('ARCH_DL_EXT', '.zip');
define('ARCH_DL_LIST_EXT', '.txt');
define('PAGE_EXT', '.htm');
define('THUMB_EXT', '.png');
define('THUMB_MAX_WIDTH', 160);
define('THUMB_MAX_HEIGHT', 90);

define('ENC_FALLBACK', '
	windows-1251
,	windows-1252
,	iso-8859-1
,	Shift_JIS');		//* <- comma-separated list (whitespace is ignored), to convert search requests from weird browsers into ENC
define('NOR', '&mdash;');		//* <- static placeholder for no request, remains in content
define('NB', '&#8203;');//&nbsp;	//* <- dynamic placeholder for empty table fields
define('SUBPAT_OOM_LETTERS', '(?:([kк])|([mм])|([gг])|([tт])|([pп])|([eэе])|([zз])|([yий]))[bб]*');	//* <- orders of magnitude for archive search

define('HINT_REGEX_FORMAT', '/%s/imsu');
define('HINT_REGEX_LINK', 'http://php.net/manual/%s/pcre.pattern.php');

//* ---------------------------------------------------------------------------

$cfg_opts_text = array('draw', 'room');
$cfg_opts_order = array(
	'input' => array_merge(
		array(
			'draw' => ARG_DRAW_APP
		)
	,	$cfg_draw_vars = array(
			'save' => 'draw_max_recovery'
		,	'undo' => 'draw_max_undo'
		,	'idle' => 'draw_time_idle'
		)
	,	array(
			'page' => 'trd_per_page'
		,	'room' => 'room_default'
		)
	)
,	'check' => array(
		'head', 'count', 'times', 'names', 'own', 'unknown', 'active'
	,	'save2common', 'kbox', 'focus', 'modtime304'
	,	'picprogress', 'capture_altclick', 'capture_textselection', 'task_timer'
	)
,	'admin' => array(
		'time_check_points'
	,	'display_php_errors'
	)
);

//* ---------------------------------------------------------------------------

$cfg_room_types = array(
/*
 * Format:
	'set_ID' => array(
 * To match a set:
		'if_game_type'         => 'text'	//* <- match a key from $cfg_game_type_dir
	,	'if_name_prefix'       => 'text'
	,	'if_name_length'       => number
	,	'if_name_length_min'   => number
	,	'if_name_length_max'   => number
 * To redefine:
	,	'name_example'         => 'text'	//* <- for room list
	,	'arch_pages'           => number	//* <- max pages shown
	,	'arch_prune'           => true|false	//* <- delete files outside of shown pages
	,	'arch_wait'            => true|false	//* <- wait before archiving full threads
	,	'mod'                  => true|false	//* <- allow post reports and moderator actions
	,	'hide_in_room_list'    => true|false
	,	'hide_unknown_threads' => true|false
	,	'lock_taken_task'      => true|false
	,	'allow_image_op'       => true|false
	,	'allow_image_reply'    => true|false
	,	'allow_text_op'        => true|false
	,	'allow_text_reply'     => true|false
	,	'allow_reply_to_self'  => true|false
	,	'allow_reply_to_full'  => true|false
	,	'alternate_reply_type' => true|false
	,	'single_active_thread' => true|false
	,	'single_thread_task'   => true|false
	)
 * Overall notes:
	'set_ID' is key for translated info in room list.
	All values are optional, default to 0/false/undefined/infinity.
	All prefixes found in the name are substracted from length check.
	Later matched set values overwrite prior assumptions in given order.
*/
	'default' => array(
		'arch_wait'            => true	//* <- defaults, always match
	,	'allow_image_op'       => true
	,	'allow_image_reply'    => true
	,	'allow_text_op'        => true
	,	'allow_text_reply'     => true
	,	'hide_unknown_threads' => true
	,	'mod'                  => true
	,	'name_example'         => '.test'
	)
,	'single_letter' => array(
		'if_name_length'       => 1
	,	'arch_pages'           => 1
	//,	'arch_prune'           => true	//* <- delete oldest archive files
	,	'arch_wait'            => false	//* <- put full threads in archive right away
	,	'mod'                  => false	//* <- no reports and moderation (auto-assigned mod still can set room announces)
	,	'name_example'         => 'b'
	)
,	'hidden' => array(
		'if_name_prefix'       => '.'	//* <- ".room_name" for normal, ".r" for single-letter
	,	'hide_in_room_list'    => true
	,	'name_example'         => 'test'
	)
,	'game_of_blind_phone' => array(
		'if_game_type'         => '1dpd'
	,	'lock_taken_task'      => true
	,	'alternate_reply_type' => true
	)
,	'game_of_blind_story' => array(
		'if_game_type'         => 'draw'
	,	'allow_text_op'        => false
	,	'allow_text_reply'     => false
	,	'lock_taken_task'      => true
	)
,	'game_of_deaf_story' => array(
		'if_game_type'         => 'text'
	,	'allow_image_op'       => false
	,	'allow_image_reply'    => false
	,	'lock_taken_task'      => true
	)
,	'game_of_multiversion' => array(
		'if_game_type'         => 'simd'
	,	'allow_image_op'       => false
	,	'allow_text_reply'     => false
	,	'allow_reply_to_self'  => true
	,	'allow_reply_to_full'  => true
	,	'single_thread_task'   => true
	)
,	'game_of_random' => array(
		'if_game_type'         => '1trd'
	,	'allow_reply_to_self'  => true
	,	'single_active_thread' => true
	,	'hide_unknown_threads' => false
	)
);

//* ---------------------------------------------------------------------------

$cfg_date_class = array(
/*
 * Format:
	array(CSS class, date() format string, start date, end date, flag)
 * Omit dates:
	start: from the beginning of time
	end: to the end of time
 * Flag:
	1: first source date falls in start-end
	2: last source date falls in start-end
	3: 1 or 2
	omit: 2, so archived content is like it was/would be last seen
 * Example:
	array('cirno-day', 'Y-m-d', '2009-09-09', '2009-09-09', 2),
	array('leap-year', 'nd', 229, 229),
	array('april-fools','nd',401, 401),
	array('cirno-day', 'nd', 909, 909),
*/
	array('new-year', 'nd', 1231, 109),
);

//* ---------------------------------------------------------------------------

$cfg_welcome_links = array(
/*
 * Format:
	'row_ID' => array(
		0 => 'url://full/absolute/path',
		1 => '/site_root_relative/path',
		2 => ROOTPRFX.'game_relative/path',
		3 => array('function_name', 'argument'),
	)
 * Omit or empty value to skip a column.
 * Which columns are actually used is decided by template.
*/
	'see' => array(
	//	0 => array('get_pic_url', 'bc05ecff68a66c0779d02b27c0c713dd.png'),
	//	2 => ROOTPRFX.'archive/base/167.htm',
	)
,	'do' => array(
	//	1 => array('get_pic_url', '85cd15467fc90d1dcd1ae9d3a9b96ea8.png'),
	)
);

//* ---------------------------------------------------------------------------

$cfg_header_links = array(
//	'drawpile' => '/drawpile/'
//,	'index' => '/index.htm'
);

//* ---------------------------------------------------------------------------

define('FOOT_NOTE', '
<a href="'.ROOTPRFX.'?'.ARG_ABOUT.'">%s</a>,
<a href="https://github.com/f2d/doodle-mutator">%s</a>,
<a href="https://github.com/f2d/">%s</a>, 2013-2020,
<a href="'.BOARD_LINK.'">%s</a>%s');
/*
 * Format lang-specific %s:
	0: about site link
	1: site engine link
	2: author link
	3: guest book/forum/board link
	4: plain-text tail
*/

//* ---------------------------------------------------------------------------

?>
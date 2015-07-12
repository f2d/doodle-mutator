<?php

$lang = 'en';
$cfg_langs = array('en', 'ru');
$cfg_draw_app = array('dfc', 'milf');
$cfg_draw_vars = array(
	'save' => 'draw_max_recovery'
,	'undo' => 'draw_max_undo'
);
$cfg_opts_order = array(
	'admin' => array('time_check_points')
,	'check' => array('head', 'count', 'times', 'names', 'own', 'unknown', 'active', 'save2common', 'kbox', 'focus', 'modtime304')
,	'input' => array('draw_app', 'draw_max_recovery', 'draw_max_undo', 'per_page', 'room_home')
);
$cfg_dir = array(
	'arch' => 'archive'
,	'opts' => 'options'
,	'room' => 'room'
);
define(DIR_PICS, 'i/');
define(DIR_THUMB, 'th/');

define(LINK_TIME, false);	//* <- src=file?modtime to force reload at clients
define(LOG_IP, true);		//* <- for each visit, add IP Address to separate file per user
define(LOG_UA, false);		//* <- for each post with pic, add User-Agent field to the post itself
define(PIC_SUB, false);		//* <- true: img src=/i/p/0/0123.png, false: src=/i/0123.png and leave it to rewrite rule
define(R1_DEL, false);		//* <- delete 1-letter-room archive content; otherwise just not show
define(QK_EXPIRES, 100200300);	//* <- seconds; renewed with every successful POST, incl.options

define(ROOM_DEFAULT, 'base');
define(ROOM_HIDE, '.');		//* <- allow ".hidden" rooms, not shown in client-side listings
define(ROOM_DUMP, '');		//* <- TODO: "!dump" rooms, single-desc per trd (OP), or something
define(ROOM_NAME_MIN_LENGTH, 1);
define(ROOM_NAME_MAX_LENGTH, 26);
define(USER_NAME_MIN_LENGTH, 1);
define(USER_NAME_MAX_LENGTH, 26);
define(FIND_MIN_LENGTH, 1);
define(FIND_MAX_LENGTH, 123);
define(REPORT_MIN_LENGTH, 5);
define(REPORT_MAX_LENGTH, 500);
define(DESCRIBE_MIN_LENGTH, 9);
define(DESCRIBE_MAX_LENGTH, 789);

define(DRAW_DEFAULT_APP_EXT, '.js');
define(DRAW_PREVIEW_WIDTH, 640);
define(DRAW_DEFAULT_WIDTH, 640);
define(DRAW_DEFAULT_HEIGHT, 360);
define(DRAW_LIMIT_WIDTH, '100,1920');
define(DRAW_LIMIT_HEIGHT, '100,1280');
define(DRAW_MAX_FILESIZE, 10020030);
define(DRAW_MAX_RECOVERY, 3);
define(DRAW_MAX_UNDO, 99);
define(DRAW_JPG_PREF, 1002003);	//* <- bytes
define(DRAW_REST, ';restyle=body,#task;restmin=document.body.firstElementChild');	//* <- on canvas resize

define(TARGET_CHANGE_TIME, 600);//* 10min
define(TARGET_DESC_TIME, 1200);	//* 20min
define(TARGET_DRAW_TIME, 7200);	//* 2h
define(TARGET_LONG_TIME, 86400);//* 24h
define(TRD_ARCH_TIME, 86400);	//* 24h
define(TRD_MAX_POSTS, 10);
define(TRD_MAX_COUNT, 30);
define(TRD_MAX_SKIP_PER_ROOM, 5);
define(TRD_PER_PAGE, 30);

define(PAGE_EXT, '.htm');
define(THUMB_EXT, '.png');
define(THUMB_MAX_WIDTH, 160);
define(THUMB_MAX_HEIGHT, 90);

define(ENC_FALLBACK, 'windows-1251');	//* <- search requests from weird browsers
define(ENC, 'utf-8');
define(NB, '&nbsp;');
define(NL, "\n");
define(O, 'opt_');
define(OK, 'OK');
define(OQ, 'OK. ');
define(TIMESTAMP, 'Y-m-d H:i:s');
define(PAT_DATE, '~((\d+)-(\d+))-(\d+)~');
define(PAT_CONTENT, '~<pre>(.+\S)\s+</pre>~uis');
define(FOOT_NOTE, '
<a href="https://github.com/f2d/doodle-mutator">%s</a> &mdash;
<a href="/">%s</a>, 2013-2015,
<a href="/board/">%s</a>%s');	//* <- lang specific %s: site engine link, author link, contact board link, plaintext suffix

?>
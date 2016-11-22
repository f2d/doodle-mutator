<?php

$tmp_announce = array(
	'anno' =>	'Global announce'
,	'stop' =>	'Global freeze'
,	'room_anno' =>	'Room announce'
,	'room_stop' =>	'Room freeze'
,	'new_game' =>	'No users exist yet. The first user will get administrator status to access global controls.'
,	'new_room' =>	'The room does not exist yet. The first participant will get room moderator status to access some of the room controls.'
);
$tmp_archive = 'Archive';
$tmp_archive_find = 'Find';
$tmp_archive_find_by = array(
	'post' => array(
		'select'	=> 'text post'
	,	'placeholder'	=> 'Enter part of a description here.'
	)
,	'file' => array(
		'select'	=> 'file name'
	,	'placeholder'	=> 'Enter part of a file name here, e.g.: 0123abcdef.png, jpg, res, etc.'
	)
,	'used' => array(
		'select'	=> 'used to draw'
	,	'placeholder'	=> 'Enter used feature here, e.g.: app name, undo, read file, text, etc.'
	)
,	'time' => array(
		'select'	=> 'drawn in time'
	,	'placeholder'	=> 'Enter time in seconds here, e.g.: > 0, < 10:20:30, 40-50, = 60, etc.'
	)
,	'name' => array(
		'select'	=> 'author name'
	,	'placeholder'	=> 'Enter part of a post author name here.'
	)
);
$tmp_archive_found = 'Search results for';
$tmp_archive_hint = 'Hidden rooms are not shown.';
$tmp_archives = 'Archives';
$tmp_arch_count = 'threads';
$tmp_arch_last = 'last';
$tmp_back = 'Return.';
$tmp_ban = 'Access forbidden.';
$tmp_check_required = 'Final check';
$tmp_describe_hint = 'At least '.DESCRIBE_MIN_LENGTH.' letters.'
.'\\[a|Format options]'
.'\\[hid|'
.'\\	[r poem|'
.'\\		Like this,
		or more than once
		
		for a blank line.
	]
	To write poem blocks, start and end the whole text and each line with a fully spaced slash.
	[cite|/ Like this, / or more than once / / for a blank line. /]
	Result is shown at the right side. →
	Not fully spaced slashes will remain as is, including double [cite|//] slashes.
]';
$tmp_describe_new = 'Describe a picture you would like to see';
$tmp_describe_this = 'Describe what you see on this picture';
$tmp_draw_app = array('JS Flat', 'JS Layers', 'Simply upload your own file');
$tmp_draw_free = 'Draw anything';
$tmp_draw_hint = 'This page shares browser memory with the actual game. May be used to restore, offline edit, save to file, etc.';
$tmp_draw_limit_hint = 'It\'s acceptable here to attach you own drawing, limited in size from %sx%s to %sx%s pixels, up to %s bytes (%s), in a file of any of these types: %s.';
$tmp_draw_test = 'Try out.';
$tmp_draw_this = 'Try to draw';
$tmp_empty = 'Empty';
$tmp_foot_notes = array('Project', 'author', 'message board', ' for contact.');
$tmp_me = 'Name yourself';
$tmp_me_hint = 'Maximum length — '.USER_NAME_MAX_LENGTH.' letters. Also you may enter your old key here.';
$tmp_mod_files = array(
	'arch' =>		'Rewrite arch with newest template.'
,	'img2subdir' =>		'Pics to subfolders.'
,	'users' =>		'Convert old user data.'
,	'logs' =>		'Convert old report logs.'
,	'room_list_reset' =>	'Clear cached post counts for room list.'
,	'hta_check' =>		'Check .htaccess template for Apache2.'
,	'hta_write' =>		'Rewrite .htaccess (automatically done for each admin\'s main page visit).'
,	'nginx' =>		'Nginx config example to apply manually.'
,	'list' =>		'List files in current script folder.'
,	'opcache_reset' =>	'Clear PHP OpCache globally.'
,	'opcache_inval' =>	'Clear PHP OpCache for this folder only.'
,	'opcache_check' =>	'View PHP OpCache stats.'
);
$tmp_mod_pages = array(
	'logs' =>	'Logs'
,	'files' =>	'Files'
,	'users' =>	'Users'
,	'reflinks' =>	'Ref.links'
,	'vars' =>	'Vars'
,	'varsort' =>	'Var.sort'
);
$tmp_mod_panel = 'Mod panel';
$tmp_mod_post_hint = 'Modify this post or thread.';
$tmp_mod_user_hint = 'Modify this user.';
$tmp_mod_user_info = 'Check flags of this user.';
$tmp_no_change = 'No change.';
$tmp_no_play_hint = 'Game playing turned off for you (no taking targets).';
$tmp_not_supported = 'This function is not supported.';
$tmp_options = 'Options. About';
$tmp_options_apply = 'Apply';
$tmp_options_drop = array(
	'out'	=> 'Log out'
,	'save'	=> 'Delete save data'
,	'skip'	=> 'Reset skipped threads'
,	'pref'	=> 'Reset options'
);
$tmp_options_first = 'Press '.$tmp_options_apply.' to continue.';
$tmp_options_flags = 'Status';
$tmp_options_input = array(
	'admin' => array(
		'time_check_points'	=> 'Work time check points in footer'
	)
,	'check' => array(
		'active'	=> 'Autohide visible threads, if more than 1'
	,	'count'		=> 'Show contents count'
	,	'head'		=> 'Show full header'
	,	'names'		=> 'Show poster names'
	,	'times'		=> 'Show post dates'
	,	'own'		=> 'Mark own posts'
	,	'picprogress'	=> 'Show image post processing progress'
	,	'focus'		=> 'Autofocus on text input fields'
	,	'kbox'		=> 'Skip description confirmation'
	,	'modtime304'	=> 'Use browser cache, when the page will have nothing new'
	,	'save2common'	=> 'All draw apps share common save slots'
	,	'unknown'	=> 'Prefer tasks from unknown threads'
	,	'capture_altclick'	=> 'Ctrl/Shift + click: select posts (desc & pic pair), Alt + click: save as image'
	,	'capture_textselection'	=> 'Select text across all posts to capture, use visible button to save as image'
	)
,	'input' => array(
		'draw_app'		=> 'Drawing tool variant'
	,	'draw_max_recovery'	=> 'Drawing autosaved recovery slots'
	,	'draw_max_undo'		=> 'Drawing undo history length'
	,	'draw_time_idle'	=> 'Drawing idle time minimum, in seconds'
	,	'trd_per_page'		=> 'Threads per page'
	,	'room_default'		=> 'Home room (single dot = room list)'
	)
);
$tmp_options_name = 'Your signature';
$tmp_options_qk = 'Your key to login';
$tmp_options_qk_hint = 'Double click to select it all for copying. Use in place of name at login form.';
$tmp_options_time = 'Default time zone';
$tmp_options_time_client = 'Your time zone';
$tmp_options_warning = array('Warning: check your server configuration first!', 'See example.');
$tmp_post_err = array(
	'file_dup'	=> 'File denied: copy already exists.'
,	'file_part'	=> 'File denied: upload not completed, please try to load in draw app and send again.'
,	'file_pic'	=> 'File denied: not image.'
,	'file_put'	=> 'File denied: saving failed.'
,	'file_size'	=> 'File denied: size out of limits.'
,	'no_lock'	=> 'Could not lock data.'
,	'no_path'	=> 'Path not found.'
,	'pic_fill'	=> 'Image denied: same color flood.'
,	'pic_size'	=> 'Image denied: size out of limits.'
,	'text_short'	=> 'Text denied: too short.'
,	'trd_arch'	=> 'Room archive has been updated.'
,	'trd_max'	=> 'Too much threads.'
,	'trd_miss'	=> 'Thread miss, posting into new thread.'
,	'unkn_req'	=> 'Unexpected error: invalid request.'
,	'unkn_res'	=> 'Unexpected error: invalid result.'
);
$tmp_post_ok = array(
	'new_post'	=> 'New post added.'
,	'skip'		=> 'No more tasks from this thread.'
,	'user_opt'	=> 'Options set.'
,	'user_qk'	=> 'Cookie set.'
,	'user_quit'	=> 'Log out.'
,	'user_reg'	=> 'User registered.'
);
$tmp_post_progress = array(
	'starting'	=> 'Please wait while your image is processed'
,	'opt_full'	=> 'Optimizing full image'
,	'opt_res'	=> 'Optimizing low-res image copy'
,	'low_res'	=> 'Resizing down to fit image into page view'
,	'low_bit'	=> 'Restricting low-res image colors to keep file size below full copy'
,	'refresh'	=> 'Finished. Click <a href="%s">here</a> if the page does not change after %s.'
);
$tmp_regex_hint = 'Regular expressions are allowed in format {%s|%s}.';
$tmp_regex_hint_pat = 'search subject';
$tmp_require_js = 'JavaScript support required.';
$tmp_report = 'Report problem';
$tmp_report_freeze = 'Freeze the thread until the issue is resolved';
$tmp_report_hint = REPORT_MIN_LENGTH.'-'.REPORT_MAX_LENGTH.' letters.';
$tmp_report_post_hint = $tmp_report.' in this post.';
$tmp_report_user_hint = $tmp_report.' with this user.';
$tmp_result = 'Result';
$tmp_room = 'Room';
$tmp_room_count_threads = 'threads alive, made, archive';
$tmp_room_count_posts = 'pics, descriptions';
$tmp_room_default = 'Basement';
$tmp_rooms = 'Rooms';
$tmp_rooms_hint =
'[r|\\	Also rooms may be created via address bar, like so: '.$tmp_room_new.'.'.(ROOM_HIDE?'
	Hidden rooms are not shown, start with a dot: '.$tmp_room_new_hide.'.':'').(ROOM_DUMP?'
	Single-thread rooms start with an exclamation: '.$tmp_room_new_dump.' (archived at every '.DUMP_MAX_POSTS.' posts).':'')
.'\\]\\Maximum length — '.ROOM_NAME_MAX_LENGTH.' letters.
Single-letter rooms have single page in archive and no reports or moderation.';
$tmp_filter_placeholder =
$tmp_rooms_placeholder = 'Type here to filter the list.';
$tmp_rooms_submit = 'Enter';
$tmp_rules = array(
	'Rules' => array(
'Parallel turn-based multiplayer drawing game.',
'Enjoy your time, make fun, not trouble.',
'Site does not guarantee keeping everything that anyone may post.'
),	'Mechanics' => array(
'As a task you get random last post, except your own, or a prompt to start a new thread.
You have '.TARGET_DESC_TIME.'s to describe or '.TARGET_DRAW_TIME.'s to draw, after that your task can possibly be taken by other people.
If not yet taken, or already dropped, you can still send your post and hit the target.
Misfired pic makes a new thread with copy of your task, a text post just starts a new one.',
'If your task is empty, you can try to change it anytime, if not — once in '.TARGET_CHANGE_TIME.'s, by entering or refreshing room.
Do not open the same room in multiple tabs, the site keeps only single target per room for you, and it will change.
If, after some time or room actions, you finally decide to perform your task, be sure to check it with the [a|⌈?⌋] button at right. This check is also performed automatically when sending a post.
Note: while any message in a [report|red bar] is displayed at top, or in-room draw app selection is used, refreshing the room in-place (i.e., using the F5 key) will not change the task. Сlicking the room link at the top will drop this effect.',
'Threads stay full at '.TRD_MAX_POSTS.' pics for '.TRD_ARCH_TIME.'s (to let reports and moderation), then go to archive when the next new thread is created.
Single-letter rooms keep only 1 page in archive (no more than '.TRD_PER_PAGE.' threads), have no reports and moderation, and full threads go to archive right away.'
));
$tmp_sending = 'Sending now, please wait...';
$tmp_stop_all = 'Game frozen.';
$tmp_submit = 'Submit';
$tmp_target_status = array(
	'no_room'	=> 'This room is renamed or deleted'
,	'no_task'	=> 'Your task is empty'
,	'task_let_go'	=> 'This task is taken by others'
,	'task_owned'	=> 'This is your task, prolonged'
,	'task_reclaim'	=> 'This task was dropped, now it\'s yours'
);
$tmp_time_limit = 'Time limit';
$tmp_time_units = array(
/*	31536000	=> array('year', 'years')
,	86400		=> array('day', 'days')
,*/	3600	=> array('hour', 'hours')
,	60	=> array('minute', 'minutes')
,	0	=> array('second', 'seconds')
);
$tmp_title = ($tmp_title_var ? 'Mekurage: Endless Strand' : 'Doodle Mutator');
$tmp_took = ', took %s sec.';

?>
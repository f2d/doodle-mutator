<?php

$tmp_announce = array(
	'anno' =>	'Global announce'
,	'stop' =>	'Global freeze'
,	'room_anno' =>	'Room announce'
,	'room_stop' =>	'Room freeze'
,	'new_game' =>	'No users exist yet. The first user will get administrator status to access global controls.'
,	'new_room' =>	'This room does not exist yet. The first participant will get room moderator status to access some of the room controls.'
,	'new_data' =>	'Found data in old format, use mod.panel: files: convert.'
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
,	'bytes' => array(
		'select'	=> 'file size (bytes)'
	,	'placeholder'	=> 'List file size ranges here, e.g.: > 0, < 123kb, 4m-4.5mb, = 67890, etc.'
	)
,	'width' => array(
		'select'	=> 'image width (pixels)'
	,	'placeholder'	=> 'List image width ranges here, e.g.: > 0, < 640, 640-800, = 800, etc.'
	)
,	'height' => array(
		'select'	=> 'image height (pixels)'
	,	'placeholder'	=> 'List image height ranges here, e.g.: > 0, < 360, 360-800, = 800, etc.'
	)
,	'time' => array(
		'select'	=> 'drawn in time (seconds)'
	,	'placeholder'	=> 'List time ranges here, e.g.: > 0, < 10:20:30, 40-50, = 60, etc.'
	)
,	'used' => array(
		'select'	=> 'used to draw'
	,	'placeholder'	=> 'Enter used feature here, e.g.: app name, undo, read file, text, etc.'
	)
,	'name' => array(
		'select'	=> 'author name (part)'
	,	'placeholder'	=> 'Enter part of a post author name here.'
	)
,	ARCH_TERM_NAME => array(
		'select'	=> 'author name (exact)'
	,	'placeholder'	=> 'Enter full author name here.'
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
$tmp_describe_hint = 'At least '.DESCRIBE_MIN_LENGTH.' letters.\\
[a|Format options]\\
[hid|\\
	[r poem|\\
		Like this,
		or more than once

		for a blank line.
	]
	To write poem blocks, start and end the whole text and each line with a fully spaced slash.
	[cite|/ Like this, / or more than once / / for a blank line. /]
	Result is shown at the right side. →
	Not fully spaced slashes will remain as is, including double [cite|//] slashes.
]';
$tmp_describe_free = 'Write anything';
$tmp_describe_new = 'Describe a picture you would like to see';
$tmp_describe_this = 'Describe what you see on this picture';
$tmp_draw_app = array('JS Flat', 'JS Layers', 'Simply upload your own file');
$tmp_draw_app_select = 'Drawing tool variant';
$tmp_draw_free = 'Draw anything';
$tmp_draw_hint = 'This page shares browser memory with the actual game. May be used to restore, offline edit, save to file, etc.';
$tmp_draw_limit_hint = 'It\'s acceptable here to attach you own drawing, limited in size from %sx%s to %sx%s pixels, up to %s bytes (%s), in a file of any of these types: %s.';
$tmp_draw_next = 'Try to draw what happens after this';
$tmp_draw_test = 'Test drawing pad';
$tmp_draw_this = 'Try to draw';
$tmp_empty = 'Empty';
$tmp_foot_notes = array('Project', 'author', 'message board', ' for contact.');
$tmp_link_schemes = array(
	'http'	=> 'Switch to HTTP.'
,	'https'	=> 'Switch to HTTPS.'
);
$tmp_me = 'Name yourself';
$tmp_me_hint = 'Maximum length — '.USER_NAME_MAX_LENGTH.' letters. Also you may enter your old key here.';
$tmp_me_submit = $tmp_rooms_submit = 'Enter';
$tmp_mod_files = array(
	'arch' =>		'Rewrite all archives with newest template.'
,	'arch_404_pix' =>	'Rewrite all archives, replacing not found images with placeholder, or vice versa.'
,	'img2orphan_check' =>	'Find pics not used in any room thread or archive.'
,	'img2orphan' =>		'Move pics not used in any room thread or archive to separate backup folder.'
,	'img2subdir' =>		'Move pics in pic folder root to subfolders.'
,	'users' =>		'Convert old user data to current format.'
,	'logs' =>		'Convert old report logs to current format.'
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
,	LK_USERLIST =>	'Users'
,	LK_REF_LIST =>	'Ref.links'
,	'vars' =>	'Vars'
,	'varsort' =>	'Var.sort'
,	'welcome' =>	'Welcome page'
);
$tmp_mod_panel = 'Mod panel';
$tmp_mod_post_hint = 'Options for this post or thread.';
$tmp_mod_user_hint = 'Options for this user.';
$tmp_mod_user_info = 'Check flags of this user.';
$tmp_no_change = 'No change.';
$tmp_no_play_hint = 'Game playing turned off for you (no taking targets).';
$tmp_not_found = 'Not found.';
$tmp_not_supported = 'This function is not supported.';
$tmp_options = 'Options. About';
$tmp_options_apply = 'Apply';
$tmp_options_area = array(
	'user' => 'Your personal info'
,	'view' => 'Site options'
,	'save' => 'Available save data'
);
$tmp_options_drop = array(
	'out'	=> 'Log out (quit)'
,	'save'	=> 'Delete autosaved drawings from memory'
,	'skip'	=> 'Forget task skipping'
,	'pref'	=> 'Reset options'
);
$tmp_options_first = 'Press %s or <a href="%s">select a room</a> to continue.';
$tmp_options_flags = 'Status';
$tmp_options_input = array(	//* ID => array(setting description, default value (yes), toggled value (no)), or as string 'desc|yes|no'
	'input' => array(
		'draw_app'		=> 'Drawing tool variant'
	,	'draw_max_recovery'	=> 'Drawing autosaved recovery slots'
	,	'draw_max_undo'		=> 'Drawing undo history length'
	,	'draw_time_idle'	=> 'Drawing idle time minimum, in seconds'
	,	'trd_per_page'		=> 'Threads per page'
	,	'room_default'		=> 'Home room (single dot = room list)'
	)
,	'check' => array(
		'head'			=> 'Page top links|full|short'
	,	'count'			=> 'Room contents count|show|hide'
	,	'names'			=> 'Poster names|show|hide'
	,	'times'			=> 'Post dates|show|hide'
	,	'focus'			=> 'Text input fields focus|auto'
	,	'active'		=> 'Visible threads|autohide if more than 1|always show'
	,	'own'			=> 'Own posts|different color|same as others'
	,	'kbox'			=> 'Description posting|no check|require confirmation'
	,	'picprogress'		=> 'Image post processing|show + pause|hide + no pause'
	,	'save2common'		=> 'Draw app save slots|shared for all variants|separate'
	,	'modtime304'		=> 'When a page has no new content|use your browser cache|reload anyway'
	,	'unknown'		=> 'Prefer tasks|from unknown threads first|any available'
	,	'task_timer'		=> 'Task timer while page is open|hide + autoupdate|show + countdown to zero'
	,	'capture_altclick'	=> 'Capture posts (Ctrl/Shift + click: mark posts, Alt + click: save)'
	,	'capture_textselection'	=> 'Capture posts with selected text (with buttons around text)'
	)
,	'admin' => array(
		'time_check_points'	=> 'Work time check points in footer|show|hide'
	)
);
$tmp_options_email = 'E-mail';
$tmp_options_email_hint = 'your@mail.box';
$tmp_options_email_show = 'visible to people';
$tmp_options_name = 'Name';
$tmp_options_profile = 'After applying changes';
$tmp_options_profile_link = 'Go to check your profile page.';
$tmp_options_qk = 'Key to login';
$tmp_options_qk_hint = 'Double click to select it all for copying. Use in place of name at login form.';
$tmp_options_self_intro = 'Tell about yourself, if you wish';
$tmp_options_self_intro_hint = 'Your text here, http://links, [http://pictures], [http://picture left,right,center].';
$tmp_options_time = 'Default time zone';
$tmp_options_time_client = 'Your time zone';
$tmp_options_warning = array('Warning: check your server configuration first!', 'See example.');
$tmp_post_err = array(
	'deny_file_op'		=> 'Room type rules: cannot start a thread with an image.'
,	'deny_file_reply'	=> 'Room type rules: cannot reply with an image.'
,	'deny_text_op'		=> 'Room type rules: cannot start a thread with text.'
,	'deny_text_reply'	=> 'Room type rules: cannot reply with text.'
,	'file_dup'	=> 'File denied: copy already exists.'
,	'file_part'	=> 'File denied: upload not completed, please try to load in draw app and send again.'
,	'file_pic'	=> 'File denied: not image.'
,	'file_put'	=> 'File denied: saving failed.'
,	'file_size'	=> 'File denied: size out of limits.'
,	'no_path'	=> 'Path not found.'
,	'pic_fill'	=> 'Image denied: same color flood.'
,	'pic_size'	=> 'Image denied: size out of limits.'
,	'text_short'	=> 'Text denied: too short.'
,	'trd_arch'	=> 'Room archive has been updated.'
,	'trd_max'	=> 'Too much threads.'
,	'trd_miss'	=> 'Thread miss, posting into new thread.'
,	'trd_n_a'	=> 'Specified thread is not accessible.'
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
,	'program'	=> 'used program'
,	'refresh'	=> 'Finished. Click <a href="%s">here</a> if the page does not change after %s.'
);
$tmp_regex_hint = 'Regular expressions are allowed in format {%s|%s}.';
$tmp_regex_hint_pat = 'search subject';
$tmp_require_js = 'JavaScript support required.';
$tmp_report = 'Report problem';
$tmp_report_freeze = 'Freeze the thread until game-breaking issues are resolved';
$tmp_report_hotfix = 'Hot fix, no need to stop (small errors in words, etc)';
$tmp_report_hint = 'Describe what\'s wrong or what you need. '.REPORT_MIN_LENGTH.'-'.REPORT_MAX_LENGTH.' letters.';
$tmp_report_post_hint = $tmp_report.' in this post.';
$tmp_report_user_hint = $tmp_report.' with this user.';
$tmp_result = 'Result';
$tmp_room = 'Room';
$tmp_room_count_threads = 'threads alive, made, archive';
$tmp_room_count_posts = 'pics, descriptions';
$tmp_room_default = 'Basement';
$tmp_room_thread_cap = 'This room has reached maximum thread count.';
$tmp_room_thread_cap_hint = 'Making new threads is not possible for now, but you can try later.';
$tmp_room_types_select = 'Show';
$tmp_room_types_hint = 'Room types';
$tmp_room_types_name_example = 'example';
$tmp_room_types_names = array(
	'single_letter'	=> 'Single-letter rooms have single page in archive and no reports or moderation'
,	'hidden'	=> 'Hidden rooms are not shown, start with a dot'
);
$tmp_room_types_title = array(
	'all' => 'All'
,	'1dpd' => 'Doodle Mutator'
,	'simd' => 'Doodle Version'
,	'draw' => 'Doodle Story'
,	'1trd' => 'Doodle Dump'
);
$tmp_room_types = array(
	'1dpd' => 'single drawing per description, a.k.a. the "blind phone" game'
,	'simd' => 'multiple drawings under single description (topic) in each thread'
,	'draw' => 'multiple drawings, no text posts, like "continue a story"'
,	'1trd' => 'single active thread per room, no thread locking, random mess'
);
$tmp_rooms = 'Rooms';
$tmp_rooms_hint = 'Maximum length — '.ROOM_NAME_MAX_LENGTH.' letters.';
$tmp_filter_placeholder =
$tmp_rooms_placeholder = 'Type here to filter the list.';
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
If, after some time or room actions, you finally decide to perform your task, but disabled automatic task checking, be sure to check it with the timer button (at right). This check is also performed automatically when sending a post.
Note: while any message in a [report|red bar] is displayed at top, or in-room draw app selection is used, refreshing the room in-place (i.e., using the F5 key) will not change the task. Сlicking the room link at the top will drop this effect.',
'Threads stay full at '.TRD_MAX_POSTS.' pics for '.TRD_ARCH_TIME.'s (to let reports and moderation), then go to archive when the next new thread is created.
Single-letter rooms keep only 1 page in archive (no more than '.TRD_PER_PAGE.' threads), have no reports and moderation, and full threads go to archive right away.'
));
$tmp_sending = 'Sending now, please wait...';
$tmp_spam_trap = 'Leave this empty.';
$tmp_stop_all = 'Game is frozen.';
$tmp_submit = 'Submit';
$tmp_target_status = array(
	'no_room'	=> 'This room is renamed or deleted'
,	'no_task'	=> 'Your task is empty'
,	'task_let_go'	=> 'This task is taken by others'
,	'task_owned'	=> 'This is your task'
,	'task_reclaim'	=> 'This task was dropped, now it\'s yours'
);
$tmp_time_limit = 'Time limit from now';
$tmp_time_units = array(
/*	31536000	=> array('year', 'years')
,	86400		=> array('day', 'days')
,*/	3600	=> array('hour', 'hours')
,	60	=> array('minute', 'minutes')
,	0	=> array('second', 'seconds')
);
$tmp_title = 'Doodle Mutator';
$tmp_took = ', took %s sec.';
$tmp_user = 'User profile';
$tmp_user_about = 'About';
$tmp_user_email = $tmp_options_email;
$tmp_user_name = $tmp_options_name;
$tmp_welcome_parts = array(
	'header'	=> 'How to play:'
,	'footer'	=> 'Come anytime, leave anytime. There is no ending.'
,	'head'	=> 'thread'
,	'tail'	=> 'etc.'
,	'you'	=> array(
		'who'		=> 'you'
	,	'desc_see'	=> 'See a description'
	,	'desc_do'	=> 'draw it'
	,	'pic_see'	=> 'See a picture'
	,	'pic_do'	=> 'describe it'
	)
,	'other'	=> array(
		'who'		=> 'other people'
	,	'desc_see'	=> 'See a description'
	,	'desc_do'	=> 'draw it'
	,	'pic_see'	=> 'See a picture'
	,	'pic_do'	=> 'describe it'
	)
);

?>
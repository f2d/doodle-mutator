<?php

define('LOCALIZATION_TEXT', [
	'about' => 'About'
,	'announce' => [
		'anno' =>	'Global announce'
	,	'stop' =>	'Global freeze'
	,	'room_anno' =>	'Room announce'
	,	'room_stop' =>	'Room freeze'
	,	'new_game' =>	'No users exist yet. The first user will get administrator status to access global controls.'
	,	'new_room' =>	'This room does not exist yet. The first participant will get room moderator status to access some of the room controls.'
	,	'new_data' =>	'Found data in old format, use mod.panel: files: convert.'
	]
,	'archive' => 'Archive'
,	'archive_find' => 'Find'
,	'archive_find_by' => [
		'post' => [
			'select'	=> 'text post'
		,	'placeholder'	=> 'Enter part of a description here.'
		]
	,	'file' => [
			'select'	=> 'file name'
		,	'placeholder'	=> 'Enter part of a file name here, e.g.: 0123abcdef.png, jpg, res, etc.'
		]
	,	'bytes' => [
			'select'	=> 'file size (bytes)'
		,	'placeholder'	=> 'List file size ranges here, e.g.: > 0, < 123kb, 4m-4.5mb, = 67890, etc.'
		]
	,	'width' => [
			'select'	=> 'image width (pixels)'
		,	'placeholder'	=> 'List image width ranges here, e.g.: > 0, < 640, 640-800, = 800, etc.'
		]
	,	'height' => [
			'select'	=> 'image height (pixels)'
		,	'placeholder'	=> 'List image height ranges here, e.g.: > 0, < 360, 360-800, = 800, etc.'
		]
	,	'time' => [
			'select'	=> 'drawn in time (seconds)'
		,	'placeholder'	=> 'List time ranges here, e.g.: > 0, < 10:20:30, 40-50, = 60, etc.'
		]
	,	'used' => [
			'select'	=> 'used to draw'
		,	'placeholder'	=> 'Enter used feature here, e.g.: app name, undo, read file, text, etc.'
		]
	,	'name' => [
			'select'	=> 'author name (part)'
		,	'placeholder'	=> 'Enter part of a post author name here.'
		]
	,	ARG_FULL_NAME => [
			'select'	=> 'author name (exact)'
		,	'placeholder'	=> 'Enter full author name here.'
		]
	]
,	'archive_found' => 'Search results for'
,	'archive_hint' => 'Hidden rooms are not shown.'
,	'archiver_button' => 'Prepare archive'
,	'archiver_by_user_id' => 'By your profile ID (not available in archives)'
,	'archiver_by_user_names' => 'By specfied full author names (one per line)'
,	'archiver_by_user_names_hint' => 'List of full author names (one per line).'
,	'archiver_from_arch' => 'From archived threads'
,	'archiver_from_room' => 'From active (unfinished)'
,	'archiver_hidden_room' => 'hidden room'
,	'archiver_naming' => 'File naming inside archive'
,	'archiver_naming_parts' => [
		'author' => ''
	,	'room' => ''
	,	'thread' => 'number (only in archive)'
	,	'date' => ''
	/*,	'width' => ''
	,	'height' => ''
	,	'bytes' => ''
	,	'fbytes' => 'formatted as K/M/etc.'
	*/,	'i' => 'image ID (mandatory, appended if omitted). Text in <angle brackets> is omitted if any variable inside is empty.'
	]
,	'archives' => 'Archives'
,	'arch_count' => 'threads'
,	'arch_last' => 'last'
,	'back' => 'Return.'
,	'ban' => 'Access forbidden.'
,	'check_required' => 'Final check'
,	'describe_hint' => 'From '.DESCRIBE_MIN_LENGTH.' to '.DESCRIBE_MAX_LENGTH.' letters.\\
	[a|Format options]\\
	[hid|\\
		[r poem|\\
			Like this,
			or more than once

			for a blank line.
		]
		To write poem blocks, start and end the whole description text and each line with a fully spaced slash.
		[cite|'
	.POST_LINE_BREAK.' Like this, '
	.POST_LINE_BREAK.' or more than once '
	.POST_LINE_BREAK.' '
	.POST_LINE_BREAK.' for a blank line. '
	.POST_LINE_BREAK.']
		Result is shown at the right side. →
		Not fully spaced slashes will remain as is, including double [cite|'
	.POST_LINE_BREAK
	.POST_LINE_BREAK.'] slashes.
	]'
,	'describe_free' => 'Write anything'
,	'describe_new' => 'Describe a picture you would like to see'
,	'describe_next' => 'Write what happens after this'
,	'describe_this' => 'Describe what you see on this picture'
,	'draw_app' => [
		'JS Flat'
	,	'JS Layers'
	,	'Simply upload your own file'
	]
,	'draw_app_select' => 'Drawing tool variant'
,	'draw_free' => 'Draw anything'
,	'draw_hint' => 'This page shares browser memory with the actual game. May be used to restore, offline edit, save to file, etc.'
,	'draw_limit_hint' => 'Attach you own drawing, limited in size from %sx%s to %sx%s pixels, up to %s bytes (%s), in a file of any of these types: %s.'
,	'draw_next' => 'Try to draw what happens after this'
,	'draw_test' => 'Test drawing pad'
,	'draw_this' => 'Try to draw'
,	'empty' => 'Empty'
,	'foot_notes' => [
		'About this website'
	,	'project'
	,	'author'
	,	'message board'
	,	' for contact.'
	]
,	'header_links' => [
		'drawpile' => 'Drawpile (online collab.)'
	,	'index' => 'etc.'
	]
,	'header_main_page' => 'Main page'
,	'me' => 'Name yourself'
,	'me_hint' => 'Your nickname, maximum length — '.USER_NAME_MAX_LENGTH.' letters. Also you may enter your old key here.'
,	'me_submit' => 'Enter'
,	'mod_files' => [
		'arch' =>		'Rewrite all archives with newest template.'
	,	'arch_pix_404' =>	'Rewrite all archives, replacing not found images with 404 placeholder, or vice versa.'
	,	'arch_pix_hash' =>	'Rewrite all archives, recalculate file hashes (for zip, not filenames) + 404 placeholder.'
	,	'img2orphan_check' =>	'Find pics not used in any room thread or archive.'
	,	'img2orphan' =>		'Move pics not used in any room thread or archive to separate backup folder.'
	,	'img2subdir' =>		'Move pics in pic folder root to subfolders.'
	,	'users' =>		'Convert old user data to current format.'
	,	'logs' =>		'Convert old report logs to current format.'
	,	'room_list_reset' =>	'Clear cached post counts for room list.'
	,	'hta_check' =>		'Check .htaccess template for Apache2.'
	,	'hta_write' =>		'Rewrite .htaccess (automatically done for each admin\'s main page visit).'
	,	'nginx' =>		'Nginx config example to apply manually.'
	,	'opcache_reset' =>	'Clear PHP OpCache globally.'
	,	'opcache_inval' =>	'Clear PHP OpCache for this folder only.'
	,	'opcache_check' =>	'View PHP OpCache stats.'
	,	'list' =>		'Browse files in current script folder.'
	]
,	'mod_pages' => [
		'logs' =>	'Logs'
	,	'files' =>	'Files'
	,	LK_USERLIST =>	'Users'
	,	LK_REF_LIST =>	'Ref.links'
	,	'vars' =>	'Vars'
	,	'varsort' =>	'Var.sort'
	,	'welcome' =>	'Welcome page'
	]
,	'mod_panel' => 'Mod panel'
,	'mod_post_hint' => 'Options for this post or thread.'
,	'mod_user_hint' => 'Options for this user.'
,	'mod_user_info' => 'Check flags of this user.'
,	'no_change' => 'No change.'
,	'no_play_hint' => 'Game playing turned off for you (no taking targets).'
,	'not_found' => 'Not found.'
,	'not_supported' => 'This function is not supported.'
,	'options' => 'Options'
,	'options_apply' => 'Apply'
,	'options_area' => [
		'user' => 'Your personal info'
	,	'view' => 'Site options'
	,	'arch' => 'Drawings archive download'
	,	'save' => 'Available save data'
	]
,	'options_drop' => [
		'out'	=> 'Log out (quit)'
	,	'save'	=> 'Delete autosaved drawings from memory'
	,	'skip'	=> 'Forget task skipping'
	,	'pref'	=> 'Reset options'
	]
,	'options_first' => 'Press %s or <a href="%s">select a room</a> to continue.'
,	'options_flags' => 'Status'
,	'options_input' => [	//* ID => [setting description, default value (yes), toggled value (no)], or as string 'desc|yes|no'
		'input' => [
			ARG_DRAW_APP		=> 'Drawing tool variant'
		,	'draw_max_recovery'	=> 'Drawing autosaved recovery slots'
		,	'draw_max_undo'		=> 'Drawing undo history length'
		,	'draw_time_idle'	=> 'Drawing idle time minimum, in seconds'
		,	'trd_per_page'		=> 'Threads per page'
		,	'room_default'		=> 'Home room (single dot = room list)'
		]
	,	'check' => [
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
		]
	,	'admin' => [
			'time_check_points'	=> 'Work time check points in footer|show|hide'
		,	'display_php_errors'	=> 'PHP errors|hide|show'
		]
	]
,	'options_email' => 'E-mail'
,	'options_email_hint' => 'your@mail.box'
,	'options_email_show' => 'visible to people'
,	'options_name' => 'Your nickname'
,	'options_profile' => 'After applying changes'
,	'options_profile_link' => 'Go to check your profile page.'
,	'options_qk' => 'Key to login'
,	'options_qk_hint' => 'Double click to select it all for copying. Use in place of name at login form.'
,	'options_self_intro' => 'Tell about yourself, if you wish'
,	'options_self_intro_hint' => 'Your text here, links as http://link, pics as [http://picture], align pics to left, right or center as [http://picture left right center].'
,	'options_time' => 'Default time zone'
,	'options_time_client' => 'Your time zone'
,	'options_warning' => [
		'Warning: check your server configuration first!'
	,	'See example.'
	]
,	'post_err' => [
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
	]
,	'post_ok' => [
		'new_post'	=> 'New post added.'
	,	'skip'		=> 'No more tasks from this thread.'
	,	'user_opt'	=> 'Options set.'
	,	'user_qk'	=> 'Cookie set.'
	,	'user_quit'	=> 'Log out.'
	,	'user_reg'	=> 'User registered.'
	]
,	'post_progress' => [
		'starting'	=> 'Please wait while your image is processed...'
	,	'opt_full'	=> 'Optimizing full image'
	,	'opt_res'	=> 'Optimizing low-res image copy'
	,	'low_res'	=> 'Resizing down to fit image into page view'
	,	'low_bit'	=> 'Restricting low-res image colors to keep file size below full copy'
	,	'used_program'	=> 'used program'
	,	'no_program'	=> 'No suitable program found.'
	,	'refresh'	=> 'Finished. Click <a href="%s">here</a> if the page does not change after %s.'
	]
,	'regex_hint' => 'Regular expressions are allowed in format {%s|%s}.'
,	'regex_hint_pat' => 'search subject'
,	'require_js' => 'JavaScript support required.'
,	'report_freeze' => 'Freeze the thread until game-breaking issues are resolved'
,	'report_hotfix' => 'Hot fix, no need to stop (small errors in words, etc)'
,	'report_hint' => 'Describe what\'s wrong or what you need. '.REPORT_MIN_LENGTH.'-'.REPORT_MAX_LENGTH.' letters.'
,	'report_post_hint' => 'Actions on this post.'
,	'report_user_hint' => 'Actions on this user.'
,	'result' => 'Result'
,	'room' => 'Room'
,	'room_count_threads' => 'threads alive, made, archive'
,	'room_count_posts' => 'pics, descriptions'
,	'room_default' => 'Basement'
,	'room_thread_cap' => 'This room has reached maximum thread count.'
,	'room_thread_cap_hint' => 'Making new threads is not possible for now, but you can try later.'
,	'room_types_select' => 'Show'
,	'room_types_hint' => 'Room types'
,	'room_types_name_example' => 'example'
,	'room_types_names' => [
		'single_letter'	=> 'Single-letter rooms have single page in archive and no reports or moderation'
	,	'hidden'	=> 'Hidden rooms are not shown, start with a dot'
	]
,	'room_types_title' => [
		'all' => 'All'
	,	'1dpd' => 'Doodle Mutator'
	,	'simd' => 'Doodle Version'
	,	'draw' => 'Doodle Continue'
	,	'text' => 'Text Continue'
	,	'1trd' => 'Dump'
	]
,	'room_types' => [
		'1dpd' => 'single drawing per description, a.k.a. the "blind phone" game'
	,	'simd' => 'multiple drawings under single description (topic) in each thread'
	,	'draw' => 'multiple drawings, no text posts, like "continue a story"'
	,	'text' => 'multiple text posts, no drawings, like "continue a story" again'
	,	'1trd' => 'single active thread per room, no thread locking, random mess'
	]
,	'rooms' => 'Rooms'
,	'rooms_hint' => 'Maximum length — '.ROOM_NAME_MAX_LENGTH.' letters.'
,	'rooms_submit' => 'Enter'
,	'rooms_placeholder' => 'Type here to filter the list.'
,	'filter_placeholder' => 'Type here to filter the list.'
,	'rules' => [
		'rules' => [
			'head' => 'Rules'
		,	'list' => [
				'Parallel turn-based multiplayer drawing game.'
			,	'Enjoy your time, make fun, not trouble.'
			,	'Site does not guarantee keeping everything that anyone may post.'
			]
		]
	,	'works' => [
			'head' => 'Mechanics'
		,	'list' => [
				'As a task you get random last post, except your own, or a prompt to start a new thread.
	You have '.TARGET_DESC_TIME.'s to describe or ds to draw, after that your task can possibly be taken by other people.
	If not yet taken, or already dropped, you can still send your post and hit the target.
	Misfired pic makes a new thread with copy of your task, a text post just starts a new one.'
			,	'If your task is empty, you can try to change it anytime, if not — once in '.TARGET_CHANGE_TIME.'s, by entering or refreshing room.
	Do not open the same room in multiple tabs, the site keeps only single target per room for you, and it will change.
	If, after some time or room actions, you finally decide to perform your task, but disabled automatic task checking, be sure to check it with the timer button (at right). This check is also performed automatically when sending a post.
	Note: while any message in a [report|red bar] is displayed at top, or in-room draw app selection is used, refreshing the room in-place (i.e., using the F5 key) will not change the task. Сlicking the room link at the top will drop this effect.'
			,	'Threads stay full at '.TRD_MAX_POSTS.' pics for '.TRD_ARCH_TIME.'s (to let reports and moderation), then go to archive when the next new thread is created.
	Single-letter rooms keep only 1 page in archive (no more than '.TRD_PER_PAGE.' threads), have no reports and moderation, and full threads go to archive right away.'
			]
		]
	,	'data' => [
			'head' => 'Personal data and cookies'
		,	'list' => [
				'No real-life personal data is ever required to use this site.'
			,	'If you attach an optional E-mail address, it will be used only on-demand, e.g. instructions to restore your login.'
			,	'This site stores numerical IP addresses of all registered users and participants, which are never deleted, unless directly asked.
	This technical data may be used for bans and deletions of malusers, and is never given outside, unless required by law.'
			,	'This site uses {https://'.LANG.'.wikipedia.org/wiki/HTTP_cookie|HTTP Cookies} in your browser only for:
	- user login,
	- user settings,
	- remembering skipped tasks.
	Site receives this data automatically with each page request, and it never leaves the site.'
			,	'This site uses {https://'.LANG.'.wikipedia.org/wiki/Web_Storage|Local Storage API} in your browser only for:
	- keeping drawpad settings,
	- keeping drawings to restore,
	- keeping archive subfolder listings for easier navigation options.
	Site does not receive this data, and it never leaves your browser.'
			]
		]
	]
,	'sending' => 'Sending now, please wait...'
,	'spam_trap' => 'Leave this empty.'
,	'stop_all' => 'Game is frozen.'
,	'submit' => 'Submit'
,	'target_status' => [
		'no_room'	=> 'This room is renamed or deleted'
	,	'no_task'	=> 'Your task is empty'
	,	'task_let_go'	=> 'This task is taken by others'
	,	'task_owned'	=> 'This is your task'
	,	'task_reclaim'	=> 'This task was dropped, now it\'s yours'
	]
,	'time_limit' => 'Time limit from now'
,	'time_units' => [
	/*	31536000	=> ['year', 'years']
	,	86400		=> ['day', 'days']
	,*/	3600	=> ['hour', 'hours']
	,	60	=> ['minute', 'minutes']
	,	0	=> ['second', 'seconds']
	]
,	'title' => 'Doodle Mutator'
,	'took' => ', took %s sec.'
,	'user' => 'User profile'
,	'user_about' => 'About'
,	'user_email' => 'E-mail'
,	'user_name' => 'Nickname'
,	'welcome_parts' => [
		'header'	=> 'How to play:'
	,	'footer'	=> 'Come anytime, leave anytime. There is no ending.'
	,	'head'	=> 'thread'
	,	'tail'	=> 'etc.'
	,	'you'	=> [
			'who'		=> 'you'
		,	'desc_see'	=> 'See a description'
		,	'desc_do'	=> 'draw it'
		,	'pic_see'	=> 'See a picture'
		,	'pic_do'	=> 'describe it'
		]
	,	'other'	=> [
			'who'		=> 'other people'
		,	'desc_see'	=> 'See a description'
		,	'desc_do'	=> 'draw it'
		,	'pic_see'	=> 'See a picture'
		,	'pic_do'	=> 'describe it'
		]
	]
]);

?>
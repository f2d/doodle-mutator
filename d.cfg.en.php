<?php
$tmp_announce = array(
'anno'	=>	'Global announce'
,'stop'	=>	'Global freeze'
,'room_anno'	=>	'Room announce'
,'room_stop'	=>	'Room freeze'
);
$tmp_archive = 'Archive';
$tmp_archive_find = 'Find';
$tmp_archive_find_by =
$tmp_archive_found_by = array('description', 'file', 'name');
$tmp_archive_found = 'Search results for';
$tmp_archive_hint = 'Hidden rooms are not shown.';
$tmp_arch_count = 'threads';
$tmp_arch_last = 'last';
$tmp_back = 'Return.';
$tmp_ban = 'Access forbidden.';
$tmp_describe_hint = 'At least '.DESCRIBE_MIN_LENGTH.' letters.';
$tmp_describe_new = 'Describe a picture you would like to see:';
$tmp_describe_this = 'Describe what you see on this picture:';
$tmp_draw_app = array('JS Flat', 'JS Layers');
$tmp_draw_free = 'Draw anything.';
$tmp_draw_hint = 'This page shares browser memory with the actual game. May be used to restore, offline edit, save to file, etc.';
$tmp_draw_test = 'Try out.';
$tmp_draw_this = 'Try to draw:';
$tmp_empty = 'Empty';
$tmp_foot_notes = array('盲ゲ ', 'message board', ' for contact.');
$tmp_mod_files = array(
	'Pics to subfolders.'
,	'Rewrite arch with newest template.'
,	'Convert old user data.'
);
$tmp_mod_pages = array(
1 =>	'Logs'
,	'Files'
,	'Users'
,	'Ref.links'
,	'Vars'
);
$tmp_mod_panel = 'Mod panel';
$tmp_mod_post_hint = 'Modify this post or thread.';
$tmp_mod_user_hint = 'Modify this user.';
$tmp_mod_user_info = 'Check flags of this user.';
$tmp_name_hint = 'Maximum length — '.USER_NAME_MAX_LENGTH.' letters.';
$tmp_name_yourself = 'Name yourself:';
$tmp_no_play_hint = 'Game playing turned off for you (no taking targets).';
$tmp_options = 'Options';
$tmp_options_apply = 'Apply';
$tmp_options_field = array(
	'draw_app'	=> 'Sketcher variant'
,	'draw_max_undo'	=> 'Undo history length in drawing'
,	'per_page'	=> 'Threads per page'
,	'room_home'	=> 'Home room (single dot = room list)'
);
$tmp_options_first = 'Press '.$tmp_options_apply.' to continue.';
$tmp_options_logout = 'Log out';
$tmp_options_name = 'Your signature';
$tmp_options_qk = 'Your key to login later with';
$tmp_options_qk_hint = 'Double click to select it all for copying. Use in place of name at login form.';
$tmp_options_reset = 'Reset options';
$tmp_options_show = array(
	'active'	=> 'Autohide visible threads, if more than 1'
,	'count'	=> 'Show contents count'
,	'head'	=> 'Show full header'
,	'names'	=> 'Show poster names'
,	'kbox'	=> 'Skip description confirmation'
,	'own'	=> 'Mark own posts'
,	'save1forall'	=> 'All draw apps share common save slots'
,	'times'		=> 'Show post dates'
,	'unknown'	=> 'Prefer tasks from unknown threads'
);
$tmp_options_time = 'Time zone';
$tmp_options_turn_off = 'no';
$tmp_options_turn_on = 'yes';
$tmp_options_unskip = 'Reset skipped threads';
$tmp_post_err = array(
	-1	=> 'What.'
,	1	=> 'No result.'
,	'dest'	=> 'Invalid path.'
,	'file_dup'	=> 'File denied: copy already exists.'
,	'file_part'	=> 'File denied: upload not completed, please try to load in draw app and send again.'
,	'file_pic'	=> 'File denied: not image.'
,	'file_put'	=> 'File denied: saving failed.'
,	'file_size'	=> 'File denied: size out of limits.'
,	'pic_fill'	=> 'Image denied: same color flood.'
,	'pic_size'	=> 'Image denied: size out of limits.'
,	'text_short'	=> 'Text denied: too short.'
,	'trd_max'	=> 'Too much threads.'
,	'trd_miss'	=> 'Thread misfire fork.'
);
$tmp_post_ok_file = 'Image sent.';
$tmp_post_ok_goto = '%s Go to <a href="%s">next</a>.';
$tmp_post_ok_skip = 'No more tasks from this thread.';
$tmp_post_ok_text = 'Text sent.';
$tmp_post_ok_user_opt = 'Options set.';
$tmp_post_ok_user_qk = 'Cookie set.';
$tmp_post_ok_user_quit = 'Log out.';
$tmp_post_ok_user_reg = 'User registered.';
$tmp_require_js = 'JavaScript support required.';
$tmp_report = 'Report problem';
$tmp_report_hint = REPORT_MIN_LENGTH.'-'.REPORT_MAX_LENGTH.' letters. Reported thread will become frozen.';
$tmp_report_post_hint = $tmp_report.' in this post.';
$tmp_report_user_hint = $tmp_report.' with this user.';
$tmp_room = 'Room';
$tmp_room_count_threads = 'threads alive, made, archive';
$tmp_room_count_posts = 'pics, descriptions';
$tmp_room_default = 'Basement';
$tmp_rooms = 'Rooms';
$tmp_rooms_hint =
'Maximum length — '.ROOM_NAME_MAX_LENGTH.' letters.[r|\Also rooms may be created via address bar, like so: '.$tmp_room_new.'.'.(ROOM_HIDE?'
Hidden rooms are not shown, start with a dot: '.$tmp_room_new_hide.'.':'').(ROOM_DUMP?'
Single-thread rooms start with an exclamation: '.$tmp_room_new_dump.' (archived at every '.DUMP_MAX_POSTS.' posts).':'').']
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
You have '.(TARGET_DESC_TIME/60).' min. to describe or '.(TARGET_DRAW_TIME/3600).' hours to draw, after that your task can possibly be taken by other people.
If not yet taken, or already dropped, you can still send your post and hit the target.
Misfired text goes nowhere, but pic makes a new thread with copy of your task.',
'If your task is empty, you can try to change it anytime, if not — once in '.(TARGET_CHANGE_TIME/60).' min., by entering or refreshing room.
Do not open the same room in multiple tabs, your aim there is single and will change.
If, after some time or room actions, you finally decide to perform your task, be sure to check it with the [a|⌈?⌋] button at right.',
'Threads stay full at '.TRD_MAX_POSTS.' pics for '.(TRD_ARCH_TIME/3600).' hours (to let reports and moderation), then go to archive when the next new thread is created.
Single-letter rooms keep only 1 page in archive (no more than '.TRD_PER_PAGE.' threads), have no reports and moderation, and full threads go to archive right away.'
));
$tmp_stop_all = 'Game frozen.';
$tmp_submit = 'Submit';
$tmp_target_status = array(
-3 =>	'Thread changed'
, -2 =>	'This task is taken by others'
, -1 =>	'This room is renamed or deleted'
,	'Your task is empty'
,	'This is your task, prolonged'
,	'This task was dropped, now it\'s yours again'
);
$tmp_title = ($tmp_title_var ? 'Doodle Mutator' : 'Mekurage: Endless Strand');
$tmp_took = ', took %s sec.';
?>
﻿<?php

$tmp_announce = array(
	'anno' =>	'Общее объявление'
,	'stop' =>	'Игра заморожена'
,	'room_anno' =>	'Комнатное объявление'
,	'room_stop' =>	'Комната заморожена'
);
$tmp_archive = 'Архив';
$tmp_archive_find = 'Найти';
$tmp_archive_find_by = array(
	'post' => array(
		'select'	=> 'описание'
	,	'found by'	=> 'описанию'
	,	'placeholder'	=> 'Введите часть описания.'
	)
,	'file' => array(
		'select'	=> 'имя файла'
	,	'found by'	=> 'имени файла'
	,	'placeholder'	=> 'Введите часть имени файла, напр.: 0123abcdef.png, jpg, res, и т.д.'
	)
,	'used' => array(
		'select'	=> 'использовано'
	,	'found by'	=> 'использованному'
	,	'placeholder'	=> 'Введите инструмент, напр.: имя рисовалки, undo, read file, text, и т.д.'
	)
,	'time' => array(
		'select'	=> 'время на рисование'
	,	'found by'	=> 'времени на рисование'
	,	'placeholder'	=> 'Введите время в секундах, напр.: > 0, < 10:20:30, 40-50, = 60, и т.д.'
	)
,	'name' => array(
		'select'	=> 'имя автора'
	,	'found by'	=> 'имени автора'
	,	'placeholder'	=> 'Введите часть имени автора постов.'
	)
);
$tmp_archive_found = 'Результаты поиска по';
$tmp_archive_hint = 'Скрытые комнаты не показаны.';
$tmp_archives = 'Архивы';
$tmp_arch_count = 'нитей';
$tmp_arch_last = 'последнее';
$tmp_back = 'Назад.';
$tmp_ban = 'Доступ запрещён.';
$tmp_check_required = 'Дополнительная проверка';
$tmp_describe_hint = 'Не менее '.DESCRIBE_MIN_LENGTH.' букв.'
.'\\{javascript:void this`toggleClass(this.nextElementSibling,\'hid\')|+ Возможности формата.}'
.'\\[hid|'
.'\\	[r poem|'
.'\\		Например так,
		или дважды
		
		для пустой строки.
	]
	Для блоков стихов начните и завершите весь текст и каждую строку косой чертой, полностью отделённой пробелами.
	[a|/ Например так, / или дважды / / для пустой строки. /]
	Результат показан справа. →
	Не отделённые полностью черты останутся как есть, включая [a|//] двойные.
]';
$tmp_describe_new = 'Опишите рисунок, который хотели бы видеть';
$tmp_describe_this = 'Опишите то, что видите на этом рисунке';
$tmp_draw_app = array('JS Плоская', 'JS Слои');
$tmp_draw_free = 'Нарисуйте что-нибудь';
$tmp_draw_hint = 'Этой странице доступно то же, что и в игре. Можно пользоваться для восстановления, оффлайн-правок, сохранения в файл и т.д.';
$tmp_draw_test = 'Испытать.';
$tmp_draw_this = 'Попробуйте нарисовать';
$tmp_empty = 'Пусто';
$tmp_foot_notes = array('Проект', 'автор', 'доска сообщений', ' для связи.');
$tmp_me = 'Назовитесь';
$tmp_me_hint = 'Не более '.USER_NAME_MAX_LENGTH.' букв. Также тут можно ввести свой старый ключ.';
$tmp_mod_files = array(
	'Очистить кэш счётчиков для списка комнат.'
,	'Картинки в подпапки.'
,	'Переписать архив по новым шаблонам.'
,	'Привести старые данные пользователей к новому виду.'
,	'Привести старые журналы действий и сообщений к новому виду.'
,	'Проверить шаблон .htaccess для Apache2.'
,	'Переписать .htaccess (автоматически происходит при каждом посещении админом главной страницы).'
);
$tmp_mod_pages = array(
1 =>	'Журнал'
,	'Файлы'
,	'Пользователи'
,	'Реф.Ссылки'
,	'Переменные'
);
$tmp_mod_panel = 'Мод-панель';
$tmp_mod_post_hint = 'Операции на этот пост и тред.';
$tmp_mod_user_hint = 'Операции на этого пользователя.';
$tmp_mod_user_info = 'Информация на этого пользователя.';
$tmp_no_change = 'Нет изменений.';
$tmp_no_play_hint = 'Ваше участие в игре отключено (не отбирать цели).';
$tmp_options = 'Настройки. О сайте';
$tmp_options_apply = 'Применить';
$tmp_options_drop = array(
	'out'	=> 'Выйти'
,	'save'	=> 'Удалить сохранения'
,	'skip'	=> 'Сбросить пропуски'
,	'pref'	=> 'Сбросить настройки'
);
$tmp_options_first = 'Нажмите '.$tmp_options_apply.' для продолжения.';
$tmp_options_flags = 'Статус';
$tmp_options_input = array(
	'admin' => array(
		'time_check_points'	=> 'Время отработки этапов в конце страницы'
	)
,	'check' => array(
		'active'	=> 'Скрывать видимые нити, если больше 1'
	,	'count'		=> 'Подсчёт содержимого'
	,	'head'		=> 'Показывать заголовок целиком'
	,	'names'		=> 'Показывать имена отправителей'
	,	'times'		=> 'Показывать даты постов'
	,	'own'		=> 'Отмечать собственные посты'
	,	'focus'		=> 'Автофокус на поле ввода текста'
	,	'kbox'		=> 'Отправка описания без подтверждения'
	,	'modtime304'	=> 'Использовать кэш браузера, когда нет нового содержимого страницы'
	,	'save2common'	=> 'Общие сохранения для всех рисовалок'
	,	'unknown'	=> 'Предпочитать задания из неизвестных нитей'
	)
,	'input' => array(
		'draw_app'		=> 'Вариант рисовалки'
	,	'draw_max_recovery'	=> 'Рисование: автосохраняемых копий для восстановления'
	,	'draw_max_undo'		=> 'Рисование: максимум отменяемых шагов'
	,	'draw_time_idle'	=> 'Рисование: минимум времени для пропуска бездействия, секунд'
	,	'trd_per_page'		=> 'Нитей на страницу архива'
	,	'room_default'		=> 'Домашняя комната (одна точка = список комнат)'
	)
);
$tmp_options_name = 'Ваша подпись';
$tmp_options_qk = 'Ваш ключ для входа';
$tmp_options_qk_hint = 'Кликните дважды, чтобы выделить целиком для копирования. Используйте при входе вместо имени.';
$tmp_options_time = 'Общий часовой пояс';
$tmp_options_time_client = 'Ваш часовой пояс';
$tmp_post_err = array(
	'file_dup'	=> 'Файл отклонён: копия уже существует.'
,	'file_part'	=> 'Файл отклонён: неполные данные, попытайтесь загрузить в рисовалке и отправить ещё раз.'
,	'file_pic'	=> 'Файл отклонён: не рисунок.'
,	'file_put'	=> 'Файл отклонён: сохранить не удалось.'
,	'file_size'	=> 'Файл отклонён: размер вне допустимого.'
,	'no_lock'	=> 'Данные не удалось зафиксировать.'
,	'no_path'	=> 'Путь не найден.'
,	'pic_fill'	=> 'Рисунок отклонён: пустая заливка.'
,	'pic_size'	=> 'Рисунок отклонён: размер вне допустимого.'
,	'text_short'	=> 'Текст отклонён: слишком мало.'
,	'trd_arch'	=> 'Архив комнаты пополнен.'
,	'trd_max'	=> 'Слишком много нитей.'
,	'trd_miss'	=> 'Промах: сброс в новую нить.'
,	'unkn_req'	=> 'Неизвестная ошибка: неприемлемый запрос.'
,	'unkn_res'	=> 'Неизвестная ошибка: неприемлемый результат.'
);
$tmp_post_ok = array(
	'new_post'	=> 'Новый пост добавлен.'
,	'skip'		=> 'Эта нить будет пропущена.'
,	'user_opt'	=> 'Настройки приняты.'
,	'user_qk'	=> 'Печеньки приняты.'
,	'user_quit'	=> 'Выход.'
,	'user_reg'	=> 'Пользователь принят.'
);
$tmp_post_refresh = '%s Пройти <a href="%s">вперёд</a>.';
$tmp_require_js = 'Необходима поддержка JavaScript.';
$tmp_report = 'Сообщить о проблеме';
$tmp_report_freeze = 'Заморозить нить до исправления';
$tmp_report_hint = REPORT_MIN_LENGTH.'-'.REPORT_MAX_LENGTH.' букв.';
$tmp_report_post_hint = $tmp_report.' в этом посте.';
$tmp_report_user_hint = $tmp_report.' с этим пользователем.';
$tmp_result = 'Результат';
$tmp_room = 'Комната';
$tmp_room_count_threads = 'нитей живо, было, архив';
$tmp_room_count_posts = 'рисунков, описаний';
$tmp_room_default = 'Основа';
$tmp_rooms = 'Комнаты';
$tmp_rooms_hint =
'[r|\\	Также комнаты можно создавать через адресную строку, например: '.$tmp_room_new.'.'.(ROOM_HIDE?'
	Скрытые комнаты не показаны, начинаются с точки: '.$tmp_room_new_hide.'.':'').(ROOM_DUMP?'
	Одинонитевые комнаты начинаются с восклицания: '.$tmp_room_new_dump.' (архивация по '.DUMP_MAX_POSTS.' постов).':'')
.'\\]\\Не более '.ROOM_NAME_MAX_LENGTH.' букв.
Однобуквенные комнаты — одностраничный архив и нет жалоб и модерации.';
$tmp_filter_placeholder =
$tmp_rooms_placeholder = 'Можно вписать сюда часть имени для отсева списка.';
$tmp_rooms_submit = 'Перейти';
$tmp_rules = array(
	'Правила' => array(
'Игра в рисование в параллелелях по очереди для любого количества человек.',
'Цель — весело провести время. Стремитесь вызвать интерес, не проблемы.',
'Сайт не гарантирует хранение всего, что можно прислать.'
),	'Механика' => array(
'В качестве задания в случайном порядке выдаётся последний пост, кроме своих, или предложение начать новую нить.
На описание даётся '.TARGET_DESC_TIME.'s, на рисунок — '.TARGET_DRAW_TIME.'s, спустя которые ваше задание могут отобрать другие люди.
Если ещё не отобрали, или уже бросили, ваш пост всё ещё попадёт в цель.
В случае промаха рисунок создаёт новую нить с копией задания, а описание — просто начинает новую.',
'Задание можно пытаться менять раз в '.TARGET_CHANGE_TIME.'s (или хоть сразу, если пустое), зайдя или обновив комнату.
Не открывайте одну и ту же комнату в нескольких вкладках, на стороне сайта хранится одна цель на комнату и будет изменена.
Если спустя какое-то время или после действий в комнате всё же решите выполнить задание, проверьте его кнопкой [a|⌈?⌋] справа. Эта проверка выполняется автоматически при отправке поста.
В присутствии сообщения в красной полосе, обновление комнаты на месте (например, клавишей F5) не меняет цель.
Ссылка на комнату в заголовке меняет цель в любом случае.',
'Нити лежат полными при '.TRD_MAX_POSTS.' рисунках ещё '.TRD_ARCH_TIME.'s (для возможности жалоб или правок), после чего идут в архив при создании очередной новой нити.
Однобуквенные комнаты держат в архиве только 1 страницу (не более '.TRD_PER_PAGE.' нитей), лишены жалоб и модерации, а полные нити сразу идут в архив.'
));
$tmp_sending = 'Отправка идёт, подождите...';
$tmp_stop_all = 'Игра заморожена.';
$tmp_submit = 'Отправить';
$tmp_target_status = array(
	'no_room'	=> 'Эта комната переименована или удалена'
,	'no_task'	=> 'Ваше задание пусто'
,	'task_let_go'	=> 'Это задание взято кем-то другим'
,	'task_owned'	=> 'Это ваше задание, продлено'
,	'task_reclaim'	=> 'Это задание было брошено, теперь ваше'
);
$tmp_time_limit = 'Срок';
$tmp_time_units = array(
	3600 =>	array('час', 'часа', 'часов')
,	60 => 	array('минута', 'минуты', 'минут')
,	0 =>	array('секунда', 'секунды', 'секунд')
);
$tmp_title = ($tmp_title_var ? 'Мекураге: Бесконечная нить' : 'Слепой телефон');
$tmp_took = ', ушло %s сек.';

?>
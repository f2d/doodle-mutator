var	LS = window.localStorage || localStorage
,	bnw = bnw || [] //* <- bells and whistles

,	regClassHid = getClassReg('hid')
,	regClassAlt = getClassReg('alt|ok')
,	regClassPost = getClassReg('post')
,	regClassThread = getClassReg('thread')
,	regClassSkipFilter = getClassReg('anno|x3')
,	regCountSkip = /(Time|Layers)$/
,	regCountTail = /\s*:+[\s\d]*(\([^()]*\))?$/
,	regCookieSkip = /^([^=]+?-skip-[0-9a-f]+)=([^\/]+?\/+(?:[^\/]+?\/{2})?)(.*)$/i
,	regLinkProtocol = /^(\w*:)?(\/*)/
,	regTagDiv = /^div$/i
,	regTagDivP = /^(div|p)$/i
,	regTagForm = /^form$/i
,	regTagPre = /^pre$/i
,	regImgTag = /<img [^>]+>/i
,	regImgTitle = /\s+(title="[^"]+)"/i
,	regImgUrl = /(".*\/([^\/"]*)")>/
,	regPostUID = /^(.*?)([@#]+)([^@#]+)$/
,	regTimeDrawn = /^((\d+)-(\d+)(?:[^\d:,=-]+(\d+)-(\d+))?|[\d:]+)(?:=(-?\d+))?,(.*)$/m
,	regTimeBreak = /^\d+(<|>|,|$)/
,	regLineBreak = /^(\r\n|\r|\n)/gm
,	regLEqual = /^=+/
,	regREqual = /=+$/
,	regLNaN = /^\D+/
,	regNaN = /\D+/
,	regNaNa = /\D+/g
,	regSpace = /\s+/g
,	regSpaceHTML = /\s|&(nbsp|#8203|#x200B);?/gi
,	regSplitCookie = /;\s*/g
,	regSplitComma = /,\s*/g
,	regSplitLineBreak = /\r\n|\r|\n/g
,	regSplitWord = /\W+/g
,	regTextAreaBR = /(<|&lt;)br[ /]*(>|&gt;)/gi
,	regTrim = getTrimReg('\\s')
,	regTrimPun = getTrimReg(':,.')
,	regTrimSlash = getTrimReg('\\/')
,	regTrimWord = getTrimReg('\\W')

,	splitSec = 60
,	splitSort = 500
,	TOS = ['object','string']
,	ON = ['on','yes','true']
,	NA = ['&#8203;', '&#x200B;', '\x20\x0B', '\u200B']
,	NB = '&nbsp;'
,	NW = '&#8203;'
,	dropDownArrow = '&#x25BE;'
,	toolTipNewLine = ' \r\n'
,	CS = 'checkStatus'
,	CM = 'checkMistype'
,	requestInProgress = {}, taskTime = {}, flag = {}, inputHints = {}, param = {}
,	count = {
		u: 0
	,	uLast: ''
	,	o: 0
	,	oLast: ''
	,	img: 0
	}
,	userClass = {
		0: 'born'
	,	b: 'burn'
	,	g: 'goo'
	,	m: 'ice'
	,	n: 'null'
	,	u: 'me'
	}
,	reportClass = {
		0: 'rooms'
	,	r: 'report'
	,	s: 'frozen'
	,	d: 'burnt'
	,	f: 'full'
	}
,	rootPath = gn('link').reduce(function(r,e) {
		return e.rel == 'index' && e.href
		? (room = e.getAttribute('data-room'), e.href.replace(/^(\w+:)?\/\/+[^\/]+\/*/, '/'))
		: r;
	}) || '/'
,	room = (
		room
	||	location.pathname.slice(rootPath.length).replace(regTrimSlash, '').split('/').slice(1).join('/')
	||	location.pathname.replace(regTrimSlash, '').split('/').slice(-1)[0]
	||	'room'
	)
,	touch = ('ontouchstart' in document.documentElement)
,	insideOut = (touch?'':'date-out')

,	d = document.body.style
,	maxWidth = [d.maxWidth||'1000px', '690px']

,	la, lang = document.documentElement.lang || 'en'
	;

//* UI translation *-----------------------------------------------------------

//if (LS && !(LS.lang && LS.lang == lang)) LS.lang = lang;	//* <- use user-selectable cookie instead

if (lang == 'ru') la = {
	toggle: ['да', 'нет']
,	room_arch: 'Архив комнаты'
,	room_logs: 'Записи комнаты'
,	arch: 'архив'
,	page: 'Страница'
,	page_limit_hint: 'Архив однобуквенных комнат хранит не больше одной страницы.'
,	search_add: 'Добавить предмет поиска'
,	search_remove: 'Убрать'
,	search_hint: {
		restore: 'Вернуть значение в поле ввода.'
	,	anchor: 'Вечная ссылка на этот пост в результате этого поиска.'
	,	thread: 'Перейти к той нити, в которой был найден этот пост.'
	,	name: 'Искать этого автора.'
	}
,	arch_dl: {
		no: 'Ничего не найдено.'
	,	ready: 'Готово:'
	,	names: 'Имена авторов:'
	,	naming: 'Именование файлов внутри:'
	,	incl: 'Включая файлы:'
	,	dl: 'Скачать архив тут.'
	,	see: 'Посмотреть список файлов внутри.'
	}
,	task: {
		free: {
			desc: 'Писать что угодно'
		,	draw: 'Рисовать что угодно'
		}
	,	drop: {
			desc: 'Описать что-то новое'
		,	draw: 'Рисовать что-то новое'
		}
	,	change: {
			any: 'Любое другое задание'
		,	desc: 'Описать другое'
		,	draw: 'Рисовать другое'
		}
	}
,	draw_test: 'Попробовать'
,	check: 'Нажмите, чтобы проверить и продлить задание.'
,	task_mistype: 'Тип задания сменился, обновите страницу или нажмите сюда.'
,	task_changed: 'Задание было изменено другими действиями за прошедшее время.'
,	send_new_thread: 'Будет создана новая нить.'
,	send_anyway: 'Всё равно отправить?'
,	canceled: 'Отправка отменена'
,	comment: 'сообщение'
,	keep_task: 'Оставить себе это задание'
,	keep_task_hint: [
		'Оставить его до выполнения или ручной смены.'
	,	'Это лишь предотвратит автоматическую смену задания при посещении комнаты.'
	,	'Чтобы не дать другим участникам взять его, всё ещё надо держать вкладку с заданием открытой или открывать её хотя бы раз в день.'
	].join(toolTipNewLine)
,	report: 'Пожаловаться на это задание'
,	skip: 'Пропустить'
,	skip_hint: 'Пропускать задания из этой нити до её завершения.'
,	unskip: 'Сбросить пропуски'
,	unskip_hint: 'Очистить список нитей, пропущенных в этой комнате.'
,	clear: {
		unsave: {ask: 'Удалить данные из памяти браузера:', unit: 'байт'}
	,	unskip: {ask: 'Пропуски будут очищены для этих комнат:', unit: ['нить','нити','нитей']}
	}
,	load: 'Проверка... '
,	fail: 'Ошибка '
,	empty: 'Ничего нет.'
,	hax: '(?)'		//'Неизвестный набор данных.'
,	time: 'Нарисовано за'
,	using: 'с помощью'
,	using_file_upload: 'файл'
,	resized: 'Размер'	//'\nИзображение уменьшено.\nРазмер'
,	resized_hint: 'Кликните для просмотра изображения в полном размере.'
,	task_old_src: 'Изображение, которое было на странице до проверки:'
,	task_new_src: 'Проверка вашего задания в этой комнате сейчас дала:'
,	confirm_again: 'Защита от случайного нажатия: 5 секунд.\nПроверьте, что не ошиблись.'
,	post_menu: {
		arch_room: 'Найти в архиве комнаты'
	,	arch_all: 'Найти во всех архивах'
	,	capture_thread: 'Снимок всей нити'
	,	capture_to_last_pic: 'Снимок по последний рисунок'
	,	capture_to_this_post: 'Снимок по этот пост'
	,	report: 'Сообщить или ответить о проблеме'
	,	user: 'Все данные пользователя'
	,	mod: 'Меню модерации'
	}
,	bottom: {
		close: 'Закрыть.'
	,	hide: 'Скрыть поля.'
	,	narrow: 'Сжать.'
	,	top: 'Наверх.'
	}
,	marks: {
		rooms: 'Комнаты'
	,	report: 'Жалоб'
	,	active: 'Активных нитей'
	,	frozen: 'Замороженные нити'
	,	burnt: 'Выжженные нити'
	,	full: 'Полные нити'
	}
,	hint: {
		show: 'Кликните, чтобы показать/спрятать.'
	,	frozen: 'Замороженная нить'
	,	burnt: 'Выжженная нить'
	,	full: 'Полная нить'
	}
,	count: {
		img: 'Рисунков'
	,	posts: 'постов'
	,	u: 'Своих постов'
	,	o: 'Прочих'
	,	last: 'последний'
	,	lastr: 'последняя'
	,	self: 'Себя'
	,	each: 'Всех'
	,	total: 'Всего'
	}
,	groups: {
		found: 'Комнат'
	,	reports: 'Комнат'
	,	users: 'Групп по дням'
	,	reflinks: 'Групп по доменам'
	}
}; else la = {
	toggle: ['yes', 'no']
,	room_arch: 'Room archive'
,	room_logs: 'Room logs'
,	arch: 'archive'
,	page: 'Page'
,	page_limit_hint: 'Archive of a single-letter room keeps no more than one page.'
,	search_add: 'Add search term'
,	search_remove: 'Remove'
,	search_hint: {
		restore: 'Restore this text into search input field.'
	,	anchor: 'Permanent link to this post in this search result.'
	,	thread: 'Go to the thread, where this post is from.'
	,	name: 'Search this name.'
	}
,	arch_dl: {
		no: 'Nothing found.'
	,	ready: 'Ready:'
	,	names: 'Author names:'
	,	naming: 'File naming inside:'
	,	incl: 'Including files:'
	,	dl: 'Download archive here.'
	,	see: 'See list of files inside.'
	}
,	task: {
		free: {
			draw: 'Draw anything'
		,	desc: 'Write anything'
		}
	,	drop: {
			desc: 'Describe anything new'
		,	draw: 'Draw anything new'
		}
	,	change: {
			any: 'Any other task'
		,	desc: 'Describe other'
		,	draw: 'Draw other'
		}
	}
,	draw_test: 'Try drawing'
,	check: 'Click this to verify and prolong your task.'
,	task_mistype: 'Task type changed, please reload the page or click here.'
,	task_changed: 'Task was changed by some actions in the meantime.'
,	send_new_thread: 'Sending will make a new thread.'
,	send_anyway: 'Send anyway?'
,	canceled: 'Sending canceled'
,	comment: 'message'
,	keep_task: 'Keep this task for yourself'
,	keep_task_hint: [
		'Keep it until done or manually changed.'
	,	'This will prevent automatic task change when opening room.'
	,	'To prevent another participant take it, you still need to keep a tab with this task open, or at least open it once a day.'
	].join(toolTipNewLine)
,	report: 'Report a problem with this task'
,	skip: 'Skip'
,	skip_hint: 'Skip any task from this thread from now on.'
,	unskip: 'Unskip'
,	unskip_hint: 'Clear list of threads, skipped in this room.'
,	clear: {
		unsave: {ask: 'Data to be deleted from browser memory (Local Storage):', unit: 'bytes'}
	,	unskip: {ask: 'Skipping will be cleared for following rooms:', unit: ['thread','threads']}
	}
,	load: 'Checking... '
,	fail: 'Error '
,	empty: 'No data.'
,	hax: '(?)'		//'Unknown data set.'
,	time: 'Drawn in'
,	using: 'using'
,	using_file_upload: 'file'
,	resized: 'Full size'	//'\nShown image is resized.\nFull size'
,	resized_hint: 'Click to view full size image.'
,	task_old_src: 'Image that was on this page before checking:'
,	task_new_src: 'Checked your current task for this room, got this:'
,	confirm_again: 'Accidental click safety: 5 seconds.\nPlease check that you are certain.'
,	post_menu: {
		arch_room: 'Search in room archive'
	,	arch_all: 'Search in all archives'
	,	capture_thread: 'Save screenshot of the thread'
	,	capture_to_last_pic: 'Save screenshot up to last pic'
	,	capture_to_this_post: 'Save screenshot up to this post'
	,	report: 'Report or comment a problem'
	,	user: 'List all data of this user'
	,	mod: 'Mod menu'
	}
,	bottom: {
		close: 'Close.'
	,	hide: 'Hide asides.'
	,	narrow: 'Narrow.'
	,	top: 'Go to top.'
	}
,	marks: {
		rooms: 'Rooms'
	,	report: 'Reports'
	,	active: 'Active threads'
	,	frozen: 'Frozen threads'
	,	burnt: 'Burnt threads'
	,	full: 'Full threads'
	}
,	hint: {
		show: 'Click here to show/hide.'
	,	frozen: 'Frozen thread'
	,	burnt: 'Burnt thread'
	,	full: 'Full thread'
	}
,	count: {
		img: 'Pictures'
	,	posts: 'posts'
	,	u: 'Own posts'
	,	o: 'Others'
	,	last: 'last'
	,	self: 'Self'
	,	each: 'All at once'
	,	total: 'Total'
	}
,	groups: {
		found: 'Rooms'
	,	reports: 'Rooms'
	,	users: 'Groups by day'
	,	reflinks: 'Groups by domain'
	}
};

//* Utility functions *--------------------------------------------------------

function confirmAgainInterval(text, wait, again) {
var	t = +new Date
,	d = orz(wait)
,	a = orz(again)
	;
	if (d <= 0) d = 5000;
	return (
		confirm(a ? la.confirm_again+(a > 1?' ['+a+']':'')+'\n\n'+text : text)
	&&	(
			(+new Date)-t > d
		||	confirmAgainInterval(text, d, a+1)
		)
	);
}

function decodeHTMLSpecialChars(t) {
	return String(t)
	.replace(/&nbsp;/gi, ' ')
	.replace(/&lt;/gi, '<')
	.replace(/&gt;/gi, '>')
	.replace(/&quot;/gi, '"')
	.replace(/&#0*39;/g, "'")
	.replace(/&amp;/gi, '&');
}

function encodeHTMLSpecialChars(t) {
	return String(t)
	.replace(/&/g, '&amp;')
	.replace(/"/g, '&quot;')
	.replace(/'/g, '&#39;')
	.replace(/</g, '&lt;')
	.replace(/>/g, '&gt;');
}

function encodeTagAttr(t) {
	return String(t).replace(/"/g, '&quot;');
}

function escapeRegex(t) {
	return t.replace(/[\\|\/\[\](){}^$.:?*+-]/g, '\\$&');
}

function propNameForIE(n) {
	return n
	.split('-')
	.map(function(v,i) {
		return i > 0
		? v.slice(0,1).toUpperCase() + v.slice(1).toLowerCase()
		: v;
	})
	.join('');
}

function getStyleValue(e, prop) {
var	o;
	if (o = e.currentStyle) return o[propNameForIE(prop)];
	if (o = window.getComputedStyle) return o(e).getPropertyValue(prop);
	return null;
}

function getParentByTagName(e,t) {
var	p = e, t = t.toLowerCase();
	while (e && !(e.tagName && e.tagName.toLowerCase() == t) && (p = e.parentNode)) e = p;
	return e;
}

function getParentBeforeTagName(e,t) {
var	p = e, t = t.toLowerCase();
	while (e && (e = e.parentNode) && !(e.tagName && e.tagName.toLowerCase() == t)) p = e;
	return p;
}

function getParentBeforeClass(e,c) {
var	p = e, r = (c.test?c:getClassReg(c));
	while (e && (e = e.parentNode) && !(e.className && r.test(e.className))) p = e;
	return p;
}

function showProps(o,z /*incl.zero*/) {
var	i,t = '';
	for (i in o) if (z || o[i]) t += '\n'+i+'='+o[i];
	return alert(t), o;
}

function isNotEmpty(t) {return String(t).replace(regSpaceHTML, '').length;}
function getTrimReg(c) {return new RegExp('^['+c+']+|['+c+']+$', 'gi');}
function getClassReg(c) {return new RegExp('(^|\\s)('+c+')($|\\s)', 'i');}
function o0(line, split, value) {
var	a = line.split(split || ','), i,o = {};
	for (i in a) o[a[i]] = value || 0;
	return o;
}

function gc(n,p) {try {return TOS.slice.call((p || document).getElementsByClassName(n) || []);} catch(e) {return [];}}
function gn(n,p) {try {return TOS.slice.call((p || document).getElementsByTagName(n) || []);} catch(e) {return [];}}
function gi(t,p) {return (p = gn('input', p)).length && t ? p.filter(function(e) {return e.type == t;}) : p;}
function id(i) {return document.getElementById(i);}
function cre(e,p,b) {
	e = document.createElement(e);
	if (b) p.insertBefore(e, b); else
	if (p) p.appendChild(e);
	return e;
}

function del(e,p) {
	if (!e) return;
	if (e.map) e.map(del); else
	if (p?p:p = e.parentNode) p.removeChild(e);
	return p;
}

function eventStop(e,i,d) {
	if ((e && e.eventPhase !== null) ? e : (e = window.event)) {
		if (d && e.preventDefault) e.preventDefault();
		if (i && e.stopImmediatePropagation) e.stopImmediatePropagation();
		if (e.stopPropagation) e.stopPropagation();
		if (e.cancelBubble !== null) e.cancelBubble = true;
	}
	return e;
}

function deleteCookie(c) {document.cookie = c+'=; expires='+(new Date(0).toUTCString())+'; Path='+rootPath;}
function getCookie(name) {
	//* https://stackoverflow.com/a/25490531
var	m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
	return m ? m.pop() : '';
}

function getToggleButtonHTML(content, opened) {
	return '<a href="javascript:void this'
	+	'" onClick="toggleHideNext(this)'
	+	'" class="toggle'+(opened?' open':'')
	+	'">'
	+		content.replace(regTrimPun, '')
	+	'</a>';
}

function getTagAttrIfNotEmpty(name, values, delim) {
	if (name) {
	var	a = (values.filter ? values : [values]).filter(function(v) {return !!v;});
		if (a.length) return ' '+name+'="'+encodeTagAttr(a.join(delim || ' '))+'"';
	}
	return '';
}

function getToggleThreadHTML(param) {
var	o = param.open || 0
,	h = param.headerText || 0
,	c = param.contentClass || 0
	;
	if (c.split) c = c.split(' ');
	if (!c.join) c = [c];
	if (!o) c.push('hid');
	return	'<div'+getTagAttrIfNotEmpty('class', param.headerClass)+'>'
	+		(h?h+' ':'')
	+		getToggleButtonHTML(param.buttonText || la.hint.show, o)
	+	'</div>'
	+	'<div'+getTagAttrIfNotEmpty('class', c)+'>'
	+		param.content
	+	'</div>';
}

function getDropdownMenuHTML(head, list, tag, id) {
var	t = tag || 'u'
,	a = '<'+t+' class="'
,	b = '</'+t+'>'
	;
	return	a+'menu-head">'
	+		(head || '')
	+	a+'menu-top">'
	+	a+'menu-hid">'
	+	a+'menu-list"'+getTagAttrIfNotEmpty('id', id || '')+'>'
	+		(list || '')
	+	b+b+b+b;
}

function addDropdownMenuWrapper(a, id) {
var	p = a.parentNode
,	t = cre('u')
	;
	t.innerHTML = getDropdownMenuHTML('', '', '', id);
var	m = t.firstElementChild
,	f = m.firstElementChild
	;
	p.insertBefore(m, a);
	m.insertBefore(a, f);
	del(t);
	return m;
}

function toggleHide(e,d) {e.style.display = (e.style.display != (d?d:d='')?d:'none');}
function toggleHideNext(e) {
	toggleClass(h = e.parentNode.nextElementSibling, 'hid');
	toggleClass(e, 'open', regClassHid.test(h.className)?-1:1);
}

function toggleClass(e,c,keep) {
var	j = orz(keep)
,	k = 'className'
,	old = e[k] || e.getAttribute(k) || ''
,	a = old.split(regSpace)
,	i = a.indexOf(c)
	;
	if (i < 0) {
		if (j >= 0) a.push(c);
	} else {
		if (j <= 0) a.splice(i, 1);
	}
	if (a.length) {
		j = a.join(' ');
		if (old != j) e[k] = j;
	} else if (old) e[k] = '', e.removeAttribute(k);
}

function meta() {toggleClass(id('content') || document.body, 'hide-aside');}
function fit() {
var	e = (id('content') || document.body).style, w = maxWidth;
	e.maxWidth = w[e.maxWidth != w[1]?1:0];
}

function parseLineKeyVal(line) {

	function parseLineTrimVal(v) {
		v = v.replace(regTrim, '');
		if (v.length && v[0] == '"' && v.slice(-1) == '"') return v.slice(1, -1);
		return decodeHTMLSpecialChars(v);
	}

var	i = line.indexOf('=');
	return [
		parseLineTrimVal(line.substr(0,i))
	,	parseLineTrimVal(line.substr(i).replace(regLEqual, ''))
	];
}

function average() {
var	i = arguments.length, total = 0;
	while (i > 0) total += arguments[--i];
	return total;
}

function orz(n) {return parseInt(n||0)||0;}
function leftPad(n, len, pad) {
	n = String(orz(n));
	len = orz(len) || 2;
	pad = String(pad || 0);
	while (n.length < len) n = pad+n;
	return n;
}

function getFormattedTimezoneOffset(t) {
	return (
		(t = (t && t.getTimezoneOffset ? t : new Date()).getTimezoneOffset())
		? (t < 0?(t = -t, '+'):'-')+leftPad(Math.floor(t/60))+':'+leftPad(t%60)
		: 'Z'
	);
}

function getFormattedHMS(msec) {
var	t = orz(msec)
,	a = [0, 0, Math.floor(Math.abs(t)/1000)]
,	i = a.length
	;
	while (--i) {
		if (a[i] >= splitSec) a[i-1] = Math.floor(a[i]/splitSec), a[i] %= splitSec;
		if (a[i] < 10) a[i] = '0'+a[i];
	}
	return (t < 0?'-':'')+a.join(':');
}

function getFTimeIfTime(t, plain) {
	return (
		regTimeBreak.test(t = String(t))
		? getFormattedTime(t, plain)
		: t
	);
}

function getFormattedTime(t, plain, only_ymd, for_filename) {
	if (TOS.indexOf(typeof t) > -1) {
		t = orz(t)*1000;
	}
var	d = (
		t
		? new Date(t+(t > 0 ? 0 : new Date()))
		: new Date()
	)
,	t = (
		('FullYear,Month,Date'+(only_ymd?'':',Hours,Minutes,Seconds'))
		.split(',')
		.map(
			function(v,i) {
				v = d['get'+v]();
				if (i == 1) ++v;
				return leftPad(v);
			}
		)
	)
,	YMD = t.slice(0,3).join('-')
,	HIS = t.slice(3).join(for_filename?'-':':')
	;
	return (
		plain
		? YMD+(for_filename?'_':' ')+HIS
		: (
			'<time datetime="'+YMD+'T'+HIS
		+	getFormattedTimezoneOffset(t)
		+	'" data-t="'+Math.floor(d/1000)
		+	'">'
		+		YMD+' <small>'+HIS+'</small>'
		+	'</time>'
		)
	);
}

function getFormattedNumUnits(num, unit) {
	if (unit.join) {
	var	i = unit.length-1, n = Math.floor(num) % 100;
		if (n < 11 || n >= 20) {
			n %= 10;
			if (n == 1) i = 0; else
			if (n > 1 && n < 5) i = 1;
		}
		unit = unit[i];
	}
	return num+' '+unit;
}

//* Room-specific functions *--------------------------------------------------

function checkMyTask(event, e) {

	function stateChange() {
		if (xhr.readyState == 4) {
		var	ttf = autoUpdateTaskTimer
		,	tti = taskTime.intervalMax
			;
			if (xhr.status == 200) {
			var	k = '\n'
			,	j = xhr.responseText.split(regSplitLineBreak)
			,	i = j.pop()
			,	j = j.join(k)
			,	status = j
					.replace(/<[^>]+>/g, '')
					.replace(regSpace, ' ')
					.replace(regTrim, '')
			,	d = j.match(/\bdeadline=["']*([^"'>\s]*)/i)
			,	sending = j.match(/\bid=["']*([^"'>\s]*)/i)
			,	message = (sending?status:'')
			,	img = i.match(/<img[^>]+\balt=["']*([^"'>\s]+)/i)
			,	task = (img?img[1]:i)
			,	error = 0
			,	eTask = id('task')
			,	eText = id('task-text')
			,	eImg = id('task-img')
				;
				if (eTask) {
					e = eImg || gn('img', eTask)[0];
					if (
				//* 1-a) image on page with text in task, or vice versa:
						(!e == !!img)
				//* 1-b) something on page with nothing in task, or vice versa:
					||	(!task == !!(e || eText))
					) {
						error = 'needs reload';
					} else
					if (!img) {
				//* 2) text in task:
				//* 2-a) text on page:
						if (e = eText || gn('aside', eTask)[1]) {
							if (
								!((j = e.previousElementSibling) && regTagForm.test(j.tagName))
							&&	decodeHTMLSpecialChars(e.innerHTML) != decodeHTMLSpecialChars(task)
							) e.innerHTML = task, error = 'reloaded text';
				//* 2-b) no text on page:
						} else error = 'needs reload';
					} else
				//* 3) image in task:
				//* 3-a) image on page:
					if (e) {
					var	i = e.getAttribute('src')
					,	k = task.indexOf(';')+1
					,	m = (flag.pixr || (flag.pixr = i.split('/').slice(0, flag.p?-3:-1).join('/')+'/'))
					,	t = (k ? task.replace(/(\.[^.\/;]+);.+$/, '_res$1') : task)
					,	j = m + getPicPath(t);
						if (i != j) {
							alert(
								[
									la.task_old_src
								,	i
								,
								,	la.task_new_src
								,	j
								,
								,	task
								].join('\n')
							);

							e.src = j;
							e.alt = task;
							setPicResize(e, k);
							error = 'reloaded image';
						}
				//* 3-b) no image on page:
					} else error = 'needs reload';
				}
				if (f && f.firstElementChild) {
					if (!error || confirm(
						(
							message
							? message+'.\n'+la.send_new_thread
							: la.task_changed
						)+'\n'+la.send_anyway
					)) f.submit();
					else status = la.canceled;
				} else {
				var	now = taskTime.lastCheckTime = +new Date
				,	m = now + taskTime.intervalMin
				,	i = now + tti
				,	n = Math.max(m, i)
					;
					if (d && (d = orz(d[1])) > 0) {
						taskTime.deadline = d *= 1000;
						if (now < d && (d = average(now, d)) < n) n = Math.max(m, d);
						if (i = taskTime.interval) clearInterval(i);
						taskTime.interval = setInterval(ttf, taskTime.intervalCheck);
					}
					taskTime.nextCheckTime = n;
				}
				if (error === 'needs reload') {
					if (eText) e = eText.parentNode; else
					if (e = btn || s) while (e && !regTagDivP.test(e.tagName) && (i = e.parentNode)) e = i;
					if (e) {
						k = (param.task_keep_prefix || '?') + (img || !task?'desc':'draw');
						e = cre('b', e, eText);
						e.id = CM;
						e.className = 'post r';
						e.innerHTML =
							'<b class="date-out l">'
						+		'<b class="report">'
						+			la.task_mistype.replace(/\s+(\S+)$/, ' <a href="'+k+'">$1</a>')
						+		'</b>'
						+	'</b>';
					}
				}
			} else {
				if (ttf && tti) {
				var	now = taskTime.lastCheckTime = +new Date;
					taskTime.nextCheckTime = now + Math.min(tti, taskTime.intervalFail);
				}
				status = la.fail;
				task = xhr.status || 0;
			}
			if (s) {
				s.textContent = status;
				(btn || s).title = new Date()+'\n\n'+task;
				if (btn) toggleClass(btn, 'ready', 1);
			}
			requestInProgress.checking = 0;
			if (s && d && ttf && !tti) ttf();
		} else if (s) s.textContent = la.load + xhr.readyState;
	}

var	nothing = (event ? false : void(0));

	if (requestInProgress.checking) return nothing;

	requestInProgress.checking = 1;

var	d = 'data-id', f = id(CM), s = id(CS);
	if (f) del(f);
	if (e && e.tagName) f = getParentByTagName(e, 'form'); else
	if (s && (f = s.getAttribute(d))) {
		f = id(f), s.removeAttribute(d);
		if (!regTagForm.test(f.tagName)) f = gn('form', f)[0];
	}
	if (event) {
		if (event.preventDefault) event.preventDefault();
		if (f && f.checkValidity && !f.checkValidity()) return requestInProgress.checking = 0, nothing;
	}
	if (s) {
	var	btn = getParentByTagName(s, 'a');
		if (btn) toggleClass(btn, 'ready', -1);
		s.textContent = la.load+0;
	}

var	url = (
		f
		? (param.check_task_post || param.check_task_auto || '--')
		: (param.check_task_keep || param.check_task_manual || '-')
	);

var	xhr = new XMLHttpRequest();
	xhr.onreadystatechange = stateChange;
	xhr.open('GET', url, true);
	xhr.send();

	return nothing;
}

function autoUpdateTaskTimer(event) {
	if (requestInProgress.checking) return;
//* all stamps in milliseconds:
var	t = taskTime.taken
,	d = taskTime.deadline
,	i = taskTime.interval
,	a = taskTime.intervalMax
,	f = (event && (e = event.type) && e === 'focus')
,	e = id(CS)
	;
	if (a || e) {
//* a) automatically check to push deadline away:
		if (a > 0) {
		var	now = +new Date
		,	i = taskTime.intervalMin
		,	m = orz(taskTime.lastCheckTime) + i
		,	n = orz(taskTime.nextCheckTime)
			;
			if (n > 0) {
				if (f && now < m && m < n) {
					taskTime.nextCheckTime = m;
				} else
				if ((f && now >= m) || now >= n) checkMyTask();
			} else {
				if (f && now > Math.min(m, t + a)) {
					n = 1;
				} else {
					n = now + Math.max(i, a);
					if (d > 0) {
						m = (now < d ? average(Math.max(now, t), d) : d);
						n = Math.min(m, n);
					}
				}
				taskTime.nextCheckTime = n;
			}
		} else
//* b) countdown to zero, then stop:
		if (e && d > 0) {
		var	statusMsg = (e.textContent || '').replace(regTrim, '')
		,	now = +new Date
		,	timeLeft = 0
			;
			if (now < d) timeLeft = d - now;
			else if (i) clearInterval(i);
			e.textContent = (
				statusMsg && statusMsg != '?'
				? statusMsg.replace(/((:)\s+.+|\s*[:\d]*)$/, '$2 ')
				: ''
			)+getFormattedHMS(timeLeft);
		}
	} else if (i) clearInterval(i);
}

function skipMyTask(v) {submitPostForm({'skip': v});}
function keepMyTask(v) {submitPostForm({'keep': v});}
function changeMyTask(k,v) {submitPostForm({'change': k, 'change_to': v});}

function submitPostForm() {

	function addInputField(name, value) {
	var	input = cre('input', form);

		input.type = 'hidden';
		input.name = name;
		input.value = value;
	}

var	form = cre('form', document.body)
,	k = arguments.length
	;

	for (var i = 0; i < k; i++) {
	var	arg = arguments[i];

		if (typeof arg === 'object' && arg) {
			for (var key in arg) {
				addInputField(key, arg[key]);
			}
		} else {
			addInputField(i, arg);
		}
	}

	form.setAttribute('method', 'post');
	form.submit();
}

function openReportForm(i) {
	if (i && i.indexOf && i.lastIndexOf('-') > 0) {
	var	k = param.report_to
	,	n = 'Report'
	,	w = 'width=680,height=360'
		;
	} else {
	var	k = param.left_link
	,	n = 'Info'
	,	w = 'width=800,height=600'
		;
	}

	window.open(decodeHTMLSpecialChars(k || '')+i, n, w);
}

function formCleanUp(e) {
	if (e && (e = e.target)) {
	var	a = gn('input', e), i = a.length;
		while (i--) if (
			(e = a[i])
		&&	e.name
		&&	e.name !== '_charset_'
		&&	!e.value
		) e.removeAttribute('name');
	}
}

function getPicSubDir(p) {var s = p.split('.'); return s[1][0]+'/'+s[0][0]+'/';}
function getPicPath(filename, param) {
	return (
		(param ? (param.images || '') : '')
	+	(flag.p ? getPicSubDir(filename) : '')
	+	filename
	);
}

function setPicResize(e,i) {
var	a = e.parentNode, nested = /^a$/i.test(a.tagName);
	if (i) {
		if (!nested) {
			a = cre('a',a,e);
			a.appendChild(e);
			a.className = 'res';
			a.href = 'javascript:void this';
			a.setAttribute('onclick', 'togglePicSize(this.firstElementChild)');
		}
		a.title = la.resized_hint+' '+e.alt.slice(i);
	} else
	if (nested) del(a).appendChild(e);
	e.setAttribute('onload', 'setPicStyle(this)');
}

function setPicStyle(e) {
var	i = e.offsetWidth+16
,	a = e.parentNode
,	e = (a.href?a.parentNode:a).style
,	a = {
		minWidth: Math.max(656,i)
	,	maxWidth: Math.max(1000,i)
	}
,	b = document.body.style
	;
	for (i in a) e[i] = b[i] = a[i]+'px';
}

function togglePicSize(e) {
var	r = '_res';
	e.src = (e.src.indexOf(r) > 0
		? e.src.replace(r, '')
		: e.src.replace(/(\.[^.\/]+$)/, r+'$1')
	);
}

function getNormalizedText(str) {return (str || '').replace(regTrim, '').toLowerCase();}
function filter(event, e) {
	if (event) e = eventStop(event).target;
	if (!e) return;
var	v = getNormalizedText(e.value)
,	k = 'lastFilterValue'
,	i = e[k]
	;
	if (event && (i ? (i === v) : !v)) return;

	e[k] = v;
var	f_type = e.getAttribute('data-filter') || ''
,	f_num = Math.max(0, orz(f_type))
,	va = (!f_num && f_type ? v.split(f_type) : 0)
,	containers = showContent('last')
	;
	for (var c_i = 0, c_len = containers.length; c_i < c_len; c_i++) {
	var	container = e = containers[c_i]
	,	threads = gn('div', e).filter(function(e) {return regClassThread.test(e.className);})
	,	foundThreads = 0
		;
		if (!threads.length) threads = [e];
		for (var t_i = 0, t_len = threads.length; t_i < t_len; t_i++) {
		var	thread = e = threads[t_i]
		,	posts = gn('div', e).filter(function(e) {return regClassPost.test(e.className) && !regClassSkipFilter.test(e.className);})
		,	foundPosts = 0
		,	imgPost = 0
		,	eqAlt = 0
		,	alt = 0
			;
			if (!posts.length) continue;
			for (var p_i = 0, p_len = posts.length; p_i < p_len; p_i++) {
			var	post = e = posts[p_i]
			,	found = 1
			,	exact = 0
			,	a = 0
			,	c = 0
				;
				if (v.length) {
					if (c = e.getAttribute('data-filter-value')) {
						c = getNormalizedText(c);
						if (va) a = c.split(f_type);
					} else
					if (f_num == f_type) {
						while (e && e.firstElementChild == (c = e.lastElementChild)) e = c;
						if (e && c) {
							if (f_num > 1) e = c; else
							if (f_num > 0) e = gn('aside', e).filter(function(p) {return p.parentNode == e;}).slice(-1)[0];
						}
						c = (e ? getNormalizedText((e.firstChild || e).textContent) : '');
					}
					if (a) {
						i = va.length-1;
						if (
							(i > 0 && va[0] !== a[0])
						||	a[1].indexOf(va[i]) < 0
						) found = 0;
					} else if (c && c.indexOf(v) < 0) found = 0;
					if (found) {
						exact = (c === v || (a && a[1] === v));
						eqAlt = (c ? !eqAlt : 0);
					} else c = 0;
				}
				if (found) {
					i = gn('img', post).length;
					if (!foundPosts || !i || (!i == !imgPost)) alt = !alt;
					if (!v === !c) ++foundPosts;
					imgPost = i;
					toggleClass(post, 'alt', (exact ? eqAlt : alt)?-1:1);
					toggleClass(post, 'ok', exact?1:-1);
				} else imgPost = 1;
				post.style.display = (found?'':'none');
			}
			thread.style.display = (foundPosts?(++foundThreads, ''):'none');
		}
		container.style.display = (foundThreads?'':'none');
	}
}

function filterPrefix(p) {
	if (e = id('filter')) {
		eventStop(0,1,1);
	var	e
	,	v = e.value || ''
	,	j = e.getAttribute('data-filter') || '/'
	,	i = v.lastIndexOf(j)
		;
		e.value = p+(i < 0 ? v : v.slice(i+j.length));
		filter(0, e);
	}
}

function showOpen(i,top) {
var	t = id(i) || (showContent(), id(i))
,	d = t.firstElementChild
,	a = function(e) {return !!e.onclick || e.href.slice(0,11) === 'javascript:';}
,	c
	;
	if (d
	&&	(a = gn('a', d).filter(a)[0])
	&&	(d = d.nextElementSibling)
	&&	(((c = d.style.display) && c === 'none') || ((c = d.className) && regClassHid.test(c)))
	) a.click();
	t.scrollIntoView(!!top);
}

//* Options-specific functions *-----------------------------------------------

function sortNum(a,b) {return (a>b?1 : a<b?-1 : 0);}
function getSaves(v,e) {

	function isMatchingAnyPrefix(line, prefixes) {
		if (
			!line
		||	!line.length
		) return false;

		if (
			!prefixes
		||	!prefixes.length
		) return true;

	var	j,k
	,	i = prefixes.length
	,	l = line.length
		;
		while (i--) if (
			(j = prefixes[i])
		&&	(k = j.length)
		&&	l >= k
		&&	(line.substr(0,k) === j)
		) return true;

		return false;
	}

var	room = (e?e.getAttribute('data-room'):0) || ''
,	dptk = (e?e.getAttribute('data-prefixes-to-keep'):0) || ''
,	dptd = (e?e.getAttribute('data-prefixes-to-delete'):0) || ''
,	prefToKeep = dptk.split(regSplitComma)
,	prefToDel = dptd.split(regSplitComma)
,	c = []
,	j = []
,	k = []
,	name
	;
	if (v == 'unskip') {
	var	m,n,q,b = 'base64:'
	,	a = document.cookie.split(regSplitCookie)
	,	i = a.length
	,	g = b.length
		;
		while (i--) if (
			(m = decodeURIComponent(a[i]).match(regCookieSkip))
		&&	(name = m[1])
		&&	(n = m[2].replace(regTrimSlash, ''))
	//	&&	(n.slice(0,g) === b ? (n = atob(n.slice(g))) : true)	//* <- atob() turns utf8 into garbage, not usable
		&&	(!room || room === n)
		// &&	!isMatchingAnyPrefix(name, prefToKeep)
		// &&	isMatchingAnyPrefix(name, prefToDel)
		) {
		var	parts = m[3].split('/').map(orz).sort(sortNum);
			c.push(q = parts.length);
			j.push(n+': '+getFormattedNumUnits(q, la.clear[v].unit) + ' (' + parts.join(', ') + ')');
			k.push(name);
		}
	} else
	if (v == 'unsave' && LS && (i = LS.length)) {
		while (i--) if (
			(name = LS.key(i))
		&&	(name !== 'lang')
		&&	(name[0] !== '/')
		&&	(name.slice(-1)[0] !== '/')
		&&	!isMatchingAnyPrefix(name, prefToKeep)
		&&	isMatchingAnyPrefix(name, prefToDel)
		) {
			c.push(q = LS.getItem(name).length);
			j.push(name+': '+getFormattedNumUnits(q, la.clear[v].unit));
			k.push(name);
		}
	}
	return {
		counts: c.sort()
	,	rows: j.sort()
	,	keys: k.sort()
	};
}

function checkSaves(e) {
	if (e.target) v = (e = e.target).id; else e = id(v = e);
	if (e) {
	var	a = getSaves(v,e)
	,	i = a.keys.length
	,	j = 0
	,	k = 0
		;
		while (i--) {
			j++;
			k += a.counts[i];
		}
		if (v == 'unsave') {
			j = a.keys.filter(function(v) {return !regCountSkip.test(v);}).length;
		}
		e.disabled = !j;
		e.value = e.value.replace(regCountTail, '')+': '+(j ? j+' ('+getFormattedNumUnits(k, la.clear[v].unit)+')' : j);
	}
}

function clearSaves(e) {
	if (e) {
		if (e.preventDefault) e.preventDefault(), e = e.target;
		if (v = e.id) {
		var	v,a = getSaves(v,e), k = a.keys;
			if (!k.length) {
				alert(la.empty);
			} else
			if (confirm(la.clear[v].ask+'\n\n'+a.rows.join('\n'))) {
				for (var i in k) {
					if (v == 'unskip') deleteCookie(k[i]); else
					if (v == 'unsave') LS.removeItem(k[i]);
				}
				if (e.getAttribute('data-room')) del(e), document.location.reload(true);
				else checkSaves(e.id);
			}
		}
	}
	return false;
}

function allowApply(v) {
	if (e = id('apply')) {
		e.disabled = (v < 0);
	} else {
	var	e,a = gc('apply')
	,	i = a.length
	,	j = (v ? 'apply_'+v : '')
	,	k = (v ? param.opt_prefix+j : '')
		;
		while (i--) if (
			(e = a[i])
		&&	(
				!v
			||	((!j && !e.id  ) || e.id   === j)
			||	((!k && !e.name) || e.name === k)
			)
		) {
			e.disabled = false;
			if (n = e.name || e.id) {
			var	o = (n.indexOf(param.opt_prefix) == 0 ? n : param.opt_prefix + n)
			,	n = e.nextElementSibling
				;
				if (
					!n
				||	!n.name
				||	(n.name !== o)
				) {
					n = cre('input', e.parentNode, n);
					n.type = 'hidden';
					n.name = o;
					n.value = 1;
					if (e.name) e.removeAttribute('name');
				}
			}
		}
	}
}

function enableFirstIfNone() {
var	e = event
,	a = arguments
,	i = a.length
	;
	if (e && (e = e.target)) {
	var	form = getParentByTagName(e, 'form');
	}
	while (i-- > 0) if (
		(e = a[i])
	&&	(
			e.tagName
		||	(e = id(e) || (
				form
				? form.elements[e]
				: null
			))
		)
	&&	e.checked
	) return;
	if (e) e.checked = true;
}

function prepareArchiveDownload(btn) {

	function getFormPartHTML(k, v) {
		return (k ? (la.arch_dl[k] || k+':') : '')
		+	'<center>'
		+	(
				v.map
				? '<div class="dl">'
				+	v.map(encodeHTMLSpecialChars).join('<br>')
				+ '</div>'
				: '<b>'
				+	encodeHTMLSpecialChars(v)
				+ '</b>'
			)
		+	'</center>'
		;
	}

	function stateChange() {
		if (xhr.readyState == 4) {
		var	b = getParentBeforeTagName(btn, 'div')
		,	p = b.parentNode
		,	b = b.nextElementSibling
		,	idNo = 'arch-dl-not-found'
		,	tagName = 'label'
		,	a = gn(tagName, p)
		,	i = a.length
		,	fileName
		,	j,e,t,c
			;
			while (i--) if ((e = a[i]) && e.className) e.removeAttribute('class');

			if (xhr.status == 200 && (fileName = xhr.responseText)) {
				c = 'ok';
				i = fileName;
			} else {
				c = 'mod';
				i = idNo;
			}
			if (e = id(i)) {
				if (fileName) e.scrollIntoView();
			//	if (!fileName) p.insertBefore(e, b);
			} else {
				if (fileName) {
					t =	getFormPartHTML('ready', fileName)
					+	((j = formParts.text) ? j.join('') : '')
					+	((j = formParts.bool) ? getFormPartHTML('incl', j) : '')
					+	'<a href="'
					+		(param.arch_dl_path || '')
					+		fileName
					+	'" target="_blank">'
					+		la.arch_dl.dl
					+	'</a>'
					+	'<a href="'
					+		(param.arch_dl_path || '')
					+		fileName
					+		(param.arch_dl_list_ext || '.txt')
					+	'" target="_blank" class="r">'
					+		la.arch_dl.see
					+	'</a>'
					;
				} else {
					t =	'<center>'
					+		la.arch_dl.no
					+	'</center>'
					;
				}
				e = cre(tagName, p, b);
				e.id = i;
				e.innerHTML = t;
			}
			e.className = c;
			if (fileName && (e = id(idNo))) del(e);

			requestInProgress.archiver = 0;
			btn.disabled = false;
		}
	}

var	form, formData, e = eventStop(0,1,1);
	if (
		requestInProgress.archiver
	||	btn.disabled
	||	!(btn || (e && (btn = e.target)))
	||	!(form = getParentByTagName(btn, 'form'))
	) return false;

var	a = form.elements
,	i = a.length
,	formParts = {bool: [], text: []}
,	queryParts = []
,	k,v
	;
	while (i--) if (
		(e = a[i])
	&&	(k = e.name)
	) {
		if (!e.type) v = e.value;
		else if (e.type == 'checkbox' || e.type == 'radio') v = +e.checked;
		else if (e.type == 'submit') v = 1;
		else v = e.value;

		if (v) {
			queryParts.push(
				encodeURIComponent(k)+'='+
				encodeURIComponent(v)
			);
			if (e.type != 'submit') {
				if (v.replace) {
					v = getFormPartHTML(k, v.split(/[\r\n]+/g));
					k = 'text';
				} else {
					if (e = getParentByTagName(e, 'label')) {
						v = e.textContent.replace(/[\s.:;,!?=-]+$/, '').replace(regTrim, '');
					} else v = k;
					k = 'bool';
				}
				formParts[k].push(v);
			}
		}
	}
	if (queryParts) {
	var	url = '.?'+queryParts.join('&');

		for (i in formParts) {
			if (formParts[i][0].sort) {
				formParts[i].map(function(v) {v.sort();});
			} else {
				formParts[i].sort();
			}
		}

		btn.disabled = true;

	var	xhr = new XMLHttpRequest();
		xhr.onreadystatechange = stateChange;
		xhr.open('GET', url, true);
		xhr.send();
	}

	return false;
}

function selectLink(e,aa,no,r,t) {
var	i = e.name+'_link'
,	a = id(i)
,	v = e.value || ''
,	r = (v === no ? '' : r.replace('*', r.indexOf('.') < 0 ? v : v.replace(/\.[^.\/]+$/, '')))
,	t = (r ? (t || la.draw_test) : '')
	;
	if (a) {
		a.href = r, a.textContent = t, allowApply(aa);
	} else {
		e = e.parentNode.nextSibling;
		e = cre('div', e.parentNode, e);
		e.className = insideOut;
		e.innerHTML = '<aside class="l"><a href="'+r+'" id="'+i+'">'+t+'</a></aside>';
	}
}

//* Archive-specific functions *-----------------------------------------------

function addSearchTerm(e, max) {
	if (e && e.length) e = id(e);
var	form = getParentByTagName(e, 'form')
,	row = e = form.firstElementChild
,	n = 1
	;
	while ((e = e.nextElementSibling) && !e.type) ++n;
	if (!max || n < max) {
	var	nextRow = cre(row.tagName, form, e);
		nextRow.innerHTML = row.innerHTML;
		if (e = gn('select', nextRow)[0]) e.onchange();
		if (e = gi('submit', nextRow)[0]) {
			e = e.parentNode;
			e.className = 'rem';
			e.innerHTML =
				'<span>&minus; <a href="javascript:void '+(n+1)+'" onClick="removeSearchTerm(this)">'
			+		la.search_remove
			+	'</a></span>';
		}
	}
}

function removeSearchTerm(e) {
	del(getParentBeforeTagName(e, 'form'));
}

function setSearchType(e) {
var	i = gi('text', getParentBeforeTagName(e, 'form'));
	if (i = i[0]) i.title = i.placeholder = inputHints[i.name = e.value] || '';
}

function restoreSearch(e) {
	if (e && e.target) e = getParentByTagName(e.target, 'a');
	if (!(e && e.tagName)) return;
var	f,i,j,k,s
,	p = e
,	n = e.name || '!'
,	v = decodeHTMLSpecialChars((e.firstElementChild || e).textContent)
	;
	while (!(f = gn('form',p)).length && (p = p.parentNode));
	if (f = f[0]) {
		f = f.firstElementChild;
		while (
			(j = gi('text',f)).length
		&&	(i = j[0])
		&&	(k = gn('select',f)).length
		&&	(s = k[0])
		&&	(s.value != n)
		&&	(f = f.nextElementSibling)
		);
		if (i) {
			i.value = v;
			if (s) s.value = n, s.onchange();
			else i.name = n;
		}
	}
}

function page(p) {

	function pageThumbs(i) {
		if (i > param.start) {
			if (!param.current.range[0]) param.current.range[0] = i;
			param.current.range[1] = i;
			param.current.thumbsHTML +=
				'<a href="'
			+		i+param.page_ext+'" data-index="'
			+		i+'" style="background-image:url(\''+param.images
			+		i+param.image_ext+'\');">'
			+	'</a>';
		}
	}

	k = (param.current ? param.current.order : 0);		//* <- 0: last to 1st, 1: ascend
	if (!p) {
		param.current.order = !k;
		return page(1);
	}
	param.current = {
		page: p
	,	order: k
	,	range: [0,0]
	,	rangeHTML: ''
	,	thumbsHTML: ''
	};
var	i = param.on_page
,	j = param.total
,	k = (p-1)*i
	;
	if (param.current.order) {
		for (i += ++k; k<=j && k<i; k++) pageThumbs(k);	//* <- from k up to k+N
	} else {
		for (j -= k+i; i; i--) pageThumbs(j+i);		//* <- last N threads, count down
	}
	k = ', '+param.current.range.join('-');			//* <- currently shown threads
	document.title = param.title.join(k);
	if (j = id('range')) j.innerHTML = decodeURIComponent(room) + k;
	if (j = id('thumbs')) j.innerHTML = param.current.thumbsHTML;
	if (j = id('pages')) {
		k = Math.ceil(param.total / param.on_page);
		for (i = 0; i<=k; i++) p = (
			i || (param.current.order?'&r':'&l')+'aquo;'
		), param.current.rangeHTML += '\n'+(
			param.current.page == i
			? '<span id="current-page">'+p+'</span>'
			: '<a href="javascript:page('+i+')">'+p+'</a>'
		);
		j.innerHTML = (touch?'':la.page+': ')+param.current.rangeHTML;
		if (i = id('current-page')) (i.nextElementSibling || i.parentNode.firstElementChild).focus();
	}
}

//* compile user content *-----------------------------------------------------

function showContent(sortOrder) {

	function getThreadHTML(threadText, addMarks) {

		function getLineHTML(line, noShrink) {
		var	lineHTML = ''
		,	postAttr = ''
		,	postClickMenu = 0
		,	postHoverMenu = 0
		,	imgRes = 0
		,	alter = 0
			;
			if (line.indexOf('\t') < 0) {
		//* as is:
				if (line[0] == '<') return line;
				if (line[0] == '|') {
		//* announce indent:
					if (line[1] == '|') return (
						getLineHTML(t = '\t\t')
					+	getLineHTML(t+line.slice(2), true)
					+	getLineHTML(t)
					);
		//* 1 line, any cell count, evenly hor.aligned:
					tableRow.push(line.slice(1));
				var	next_i = orz(l_i)+1;
					if (lines.length <= next_i || lines[next_i][0] != '|') {
					var	rowHTML = ''
					,	rowLen = tableRow.length
					,	align = (rowLen > 1 && rowLen < 4) || ''
					,	cellWidth = (align?' width="'+Math.floor(100/rowLen)+'%"':'')
						;
						for (var td_i in tableRow) {
							if (align) align = ' align="'+(
								td_i == 0
								? 'left'
								: (
									td_i == rowLen-1
									? 'right'
									: 'center'
								)
							)+'"';
							rowHTML +=
								'<td'+cellWidth+align+'>'
							+		tableRow[td_i]
							+	'</td>';
						}
						lineHTML +=
							'<table width="100%">'
						+		'<tr>'
						+			rowHTML
						+		'</tr>'
						+	'</table>';
						tableRow = [];
					}
				} else
		//* save variables, show nothing:
				if ((i = line.indexOf('=')) > 0) {
				var	i = parseLineKeyVal(line)
				,	k = i[0]
				,	v = i[1]
					;
					if (k.length && v.length) {
						if (flagVarNames.indexOf(k) < 0) param[k] = v;
						else {
							i = v.length;
							while (i--) k = v[i], flag[k] = k;
						}
					}
					return '';
				}
			} else {
		//* 3 columns, sort of:
			var	t,a,b,c,d,i,j,k,l,m,n
			,	u = ''
			,	userID = ''
			,	userLink = ''
			,	userName = ''
			,	userNameHidden = 0
			,	report = ''
			,	postID = ''
			,	editPostData = ''
			,	marks = ''
			,	tab = line.split('\t')
			,	sep = param.separator
			,	roomCount = (dtp.rooms && sep && tab.length > 2)
				;
				if (dtp.options) {
				var	optionNames = [];
				} else
				if (dtp.rooms) {
				var	roomClasses = []
				,	roomDates = {}
					;
				} else
				if (dtp.found) {
					if (!threadMark && param.room) threadMark = {id: param.room+'/', posts: 0};
					if (threadNum != param.t) {
						threadNum = param.t;
						alter = 1;
					}
				} else
				if (dtp.threads) {
					u = tab.shift(), m = u.match(regPostUID);
					if (m) {
						u = m[1];
						userID = m[3];
						if (m[2].indexOf('@') >= 0) {
							userLink = (param.profiles || '')+userID;
						}
					}
					if (u.indexOf(sep = ',') >= 0) {
						j = u.split(sep);
						u = j.pop();
						t = j.join(sep);
						k = t.match(regNaN);
						t = t.replace(regNaN, '');
						if (k && (k = k[0]) in reportClass) threadMark = {id: t, class: k};
						threadNum = t;
						modEnabled = 1;
					}
					if (flag.m) {
						j = (
							userID
							? {user: userID, time: tab[0]}
							: {}
						);
						if (tab.length > 3) {
							j.file = tab[2];
							j.meta = tab[3];
							if (tab.length > 4) j.browser = tab[4];
						} else j.text = tab[2];
						for (k in j) editPostData += (editPostData?'\n':'')+k+': '+j[k];
					}
				} else
				if (dtp.users) {
					if ((t = tab[0]).indexOf(sep = ',') >= 0) {
						j = t.split(sep);
						u = (j.length > 2 ? j.pop() : '');
						userID = j.pop();
						if (u) selfID = userID; else
						if (selfID === userID) u = 'u';
						tab[0] = j.join(sep);
					}
					if (threadNum = (tab.length > 2?1:0)) {
						if (tab[2].indexOf(', about: [') >= 0) {
							userLink = (param.profiles || '')+userID;
						}
					}
					modEnabled = 1;
				}
				if (roomCount && isNotEmpty(t = tab[2]) && t.indexOf(sep) >= 0) {
					k = t.split(sep);
				//* room name:
					tab[2] = k.shift();
				//* colored counters:
					a = {};
					for (i in k) if (c = reportClass[b = k[i].slice(-1)]) {
						a[b] = '<span class="'+c+'" title="'+la.marks[c]+'">'+orz(k[i])+'</span>';
					} else
				//* user's default room or mod status:
					if ((b = k[i].replace(regTrimWord, '')).length > 0) {
						if (b === 'mod') roomClasses.push('u'); else
						if (b === 'home') roomClasses.push(b);
					}
					for (i in reportClass) if (a[i]) marks += a[i]+sep;
				}
			//* crutch for menus:
				i = 2;
				while (i--) if (
					!isNotEmpty(t = tab[i])
				&&	(
						modEnabled
					||	(i > 0 && userLink)
					)
				) {
					if (i > 0 && NA.indexOf(t) >= 0) userNameHidden = 1;
					tab[i] = dropDownArrow;
				}
			//* left:
				if (tab.length > 0 && isNotEmpty(t = tab[0])) {
					if (dtp.options && t.indexOf(j = '|') > 0) {
						t = (optionNames = t.split(j)).shift().replace(regTrimPun, '')+':';
					} else
					if (dtp.rooms && sep) {
				//* arch link:
						j = '*';
						if (t.indexOf(j) < 0) j = '';

						m = (
							'<a href="'
						+		param.archives
						+		(param.type?param.type+'/':'')
						+		tab[2]
						+		'/'
						+	(
								j
								? '" title="'
								+	encodeTagAttr(la.page_limit_hint)
								: ''
							)
						+	'">'
						);

						n = j+'</a>';

						if (roomCount && t.indexOf(sep) >= 0) {
							k = t.split(sep).map(orz);
				//* last arch date:
							if (k.length > 3 && (i = k[3])) {
								roomDates['a '+(insideOut?'r':'l')] = i = getFTimeIfTime(i);
								if (count.uLast < i) count.uLast = i;
							}
							if (!count.u.length) count.u = [0,0,0];
							for (i in (k = k.slice(0,3))) count.u[i] += k[i];
							if (i = k[2]) k[2] = m+i+n;
							t = k.join(sep);
						} else
				//* date hidden:
						if (tab[2] && isNotEmpty(t)) t = m+t+n;
					} else {
					var	time = t = getFTimeIfTime(t);

						if (dtp.found) t = (
							'<a href="'+(param.room?param.room+'/':'')+param.t+param.page_ext
						+	'" title="'+la.search_hint.thread
						+	'">'
						+		t
						+	'</a>'
						+	(alter?' → '+threadNum:'')
						);
					}
					if (flag.c && (dtp.reflinks || (tab.length > 2 && isNotEmpty(tab[2])))) {
						++count[k = (u == 'u'?u:'o')];
						if (time) {
							if (count[k += 'Last'] < time) count[k] = time;
							if (threadMark) {
								if (!threadMark.s || threadMark.s > time) threadMark.s = time;
								if (!threadMark.t || threadMark.t < time) threadMark.t = time;
								if (dtp.found) ++threadMark.posts;
							}
						}
						if (dtp.users && u == 'u') {
							++count[k = 'o'];
							if (time && count[k += 'Last'] < time) count[k] = time;
						}
					}
					tab[0] = t;
				}
			//* right:
				if (tab.length > 1 && isNotEmpty(t = tab[1])) {
					userName = t;
					if (!regTagPre.test(pre.tagName)) {
						t = encodeHTMLSpecialChars(t);	//* <- fix for textarea source and evil usernames
					}
					if (dtp.found) {
						t =	'<a href="?'
						+		(param.arch_term_name || 'name')
						+		'='
						+		encodeURIComponent(userName)
						+	'" title="'+la.search_hint.name
						+	'">'
						+		t
						+	'</a>';
					} else
					if (dtp.threads || dtp.users) {
						if (userLink)
						t =	'<a href="'+userLink+'">'
						+		t
						+	'</a>';
					} else
					if (dtp.reflinks) {
						try {d = decodeURIComponent(t);} catch (e) {d = t;}
						if (!(m = t.match(regLinkProtocol)) || !m[1]) {
							t = 'http://'+t.replace(regLinkProtocol, '');
						}
						t = '<a href="'+t+'">'+d+'</a>';
					} else
				//* rooms:
					if (dtp.rooms && dtp.archive && t.indexOf(j = '*') >= 0) {
						t = (
							'<span title="'
						+		encodeTagAttr(la.page_limit_hint)
						+	'">'
						+		orz(t)
						+		j
						+	'</span>'
						);
					} else
					if (roomCount && t.indexOf(sep) >= 0) {
						k = t.split(sep).map(orz);
				//* last post date:
						if (k.length > 2) {
							if (i = k[2]) {
								roomDates[insideOut?'l':'r'] = i = getFTimeIfTime(i);
								if (count.oLast < i) count.oLast = i;
							}
							if (!count.o.length) count.o = [0,0];
							for (i in (k = k.slice(0,2))) count.o[i] += k[i];
						}
						t = k.join(sep);
					} else
				//* options:
					if (dtp.options && t[0] != '<' && (i = t.indexOf('=')) >= 0) {
					var	i = parseLineKeyVal(t)
					,	k = i[0]
					,	v = i[1]
					,	sep = sep || ','
					,	sel = param.sep_select || ';'
					,	t = param.apply_change || ''
					,	t = (t?"'"+t+"'":'')
						;
				//* drop-down select:
						if (v.indexOf(sel) >= 0) {
						var	l = v.split(sel)
						,	m = orz(l.shift())
							;
							t = '<select name="'+param.opt_prefix+k
							+(
								l.length > 2
								? '" onChange="selectLink(this,'+t+",'"
								+(l[2] || 'no')+"','"
								+(l[3] || '*')
								+(l.length > 4 ? "','"+l[4] : '')
								+"')"
								: ''
							)+'">';
							k = (l[1] || '').split(sep);
							l = (l[0] || '').split(sep);
							for (i in k) t +=
								'<option value="'+k[i]+'"'
							+		(m == i?' selected':'')
							+	'>'
							+		(l[i]?l:k)[i]
							+	'</option>';
							t += '</select>';
						} else if (
				//* add trigger to activate submit button:
							(t = '" onChange="allowApply('+t+')"'),
				//* text field:
							(v.indexOf(sep) >= 0)
						) {
							l = v.split(sep);
							t = '<input type="text'
							+	'" name="'+param.opt_prefix+k
							+	'" value="'+l[0]
							+	'" placeholder="'+l[1]
							+	t+'>';
						} else {
				//* toggle box:
							t = '['+[0,1].map(
								function(i) {
								var	text = (optionNames[i] || la.toggle[i]).replace(regTrimPun, '');
									return '<label>'
									+		'<input type="radio'
									+			'" name="'+param.opt_prefix+k
									+			'" value="'+i+t
									+			(i == v?' checked':'')
									+		'>\n<b>'+text+'</b>\n'
									+	'</label>';
								}
							).join(sep)+']';
						}
					}
					tab[1] = (marks && !isNotEmpty(t) ? marks.slice(0, -sep.length) : marks+t);
				}
			//* center:
				if (tab.length > 2 && isNotEmpty(t = tab[2])) {
					if (dtp.reports) {
						t = (
							'<div class="log al">'
						+	t.replace(
								/(task:\s*)(\S+)(\s*<br>\s*pic:\s*1)|(Denied\s+file\w*:\s*)(\S+)(\s*<br>)/gi
							,	function(m) {
									return m = arguments, (
										(m[1] || m[4])
									+	'<br><img src="'
									+		getPicPath(m[2] || m[5], param)
									+	'">'
									+	(m[3] || m[6])
									);
								}
							).replace(/(<br>)(\s+)/gi, '$1<i></i>')
						+	'</div>'
						);
					} else
					if (dtp.users) {
						t = userID+'. '+(u == 'u'?t:'<span class="a">'+t+'</span>');
					} else
					if (dtp.rooms) {
						j = (param.type?param.type+'/':''), a = '';
						postAttr += '" data-filter-value="'+(j || '/')+t;
				//* room hidden:
						if (t[0] == '.') roomClasses.push('gloom');
				//* room frozen:
						if (tab.length > 4) a = tab[4], roomClasses.push('frozen');
				//* room announce:
						if (tab.length > 3 && (isNotEmpty(a) || isNotEmpty(a = tab[3]))) {
							a = encodeTagAttr(
								a
								.replace(regTrim, '')
								.replace(regSpace, ' ')
							);
							a =	'" title="'+a
							+	'" data-title="'+a.substr(a.indexOf(': '));
						}
						if (a) roomClasses.unshift('room-title');
						t = '<a href="'
						+	j+t+'/'
						+	a+'"'
						+	getTagAttrIfNotEmpty('class', roomClasses)
						+ '>'
						+	t
						+ '</a>';
					} else
				//* image post:
					if (tab.length > 3 && isNotEmpty(a = tab[3])) {
						imgRes = (t.indexOf(', ') > 0);
						if (flag.c) ++count.img;
						if (a[0] == '?') {
							t =	'<span title="'+t+'">'
							+		a.slice(1)
							+	'</span>';
						} else {
							if (m = a.match(regTimeDrawn)) {
								if (m[2]) {
								var	k = getFormattedHMS(+m[3]-m[2])
								,	i = (m[5] && m[5] != m[4] ? getFormattedHMS(+m[5]-m[4]) : '')
								,	j = m[6]	//* <- sum of active intervals
									;
									m[1] = (
										orz(j) > 0 && (j = getFormattedHMS(j)) != k
										? j+' ('+k+(i?' / '+i:'')+')'
										: k+(i?' ('+i+')':'')
									);
								}
							var	q = m[1]+', '+m[7]
							,	a = la.time+' '+m[1]+(
									m[7].slice(0,6) === 'file: '
									? ', '+la.using_file_upload+': '+m[7].slice(6)
									: ' '+la.using+' '+m[7]
								)
								;
							} else q = a = la.hax+' '+a;
							if (dtp.found) {
							var	sep = '">';
								k = t.split(sep);
								for (i in k) if (l = k[i]) {
									m = l.split(regSpace, 1)[0].substr(1).toLowerCase();
									if (m == 'a') k[i] += '" class="res" target="_blank'; else
									if (m == 'img') k[i] +=
										'" alt="'+l.substr(l.lastIndexOf('/')+1)+', '+a
									+	'" title="'+a;
								}
								t = k.join(sep);
								if (imgRes) {
									j = t.split(l = '>');
									k = j.pop()
										.replace(regTrim, '')
										.replace(regLNaN, '')
										.replace(regNaN, 'x');
									t = j.join(l).replace(
											regImgTitle
										,	' $1, '+k+'. '+la.resized_hint+'"'
										)+l;
									if (isNotEmpty(tab[0])) tab[0] += '<br>'+t
										.replace(regImgTag, k)
										.split(sep)
										.join('" title="'+la.resized_hint+sep);
								}
							} else {
								if (imgRes) {
									j = t.split(';');
									t = j[0].replace(/(\.[^.]+)$/, '_res$1');
									k = j[1].replace(regNaN, 'x');
									if (isNotEmpty(tab[0])) tab[0] +=
										'<span class="'+(u == 'u'?u:'res')
								//	+	'" title="'+k+'. '+la.resized_hint
									+	'">'
									+		('\n'+la.resized).replace(regLineBreak, '<br>')
									+		': '+k
									+	'</span>';
								} else j = '';

								t = (
									'<img src="'
								+		getPicPath(t, param)
								+	'" alt="'+t+', '+q
								+	'" title="'+(j?a+', '+k+'. '+la.resized_hint:a)
								+	'">'
								);

								if (j) t = (
									'<a target="_blank" href="'
								+		getPicPath(j[0], param)
								+	'" class="res'+(u == 'u'?' u':'')+'">'
								+		t
								+	'</a>'
								);
							}
						}
						if (!dtp.found && imgPost) alter = 1;
						imgPost = 1;
					} else {
				//* text post:
						if (dtp.threads) t = ++descNum+'. '+t;
						t = t
							.replace(/\s+(-|&mdash;|—|&ndash;|–|)\s+/gi, '&nbsp;$1 ')
							.replace(/\s+([^<\s]{1,2})\s+/g, ' $1&nbsp;');
						imgPost = 0;
					}
					if (dtp.found) {
					var	i = (param.room?param.room+'/':'')+postNum;
						postAttr += '" id="'+i;
						t =	'<a href="#'+i
						+	'" title="'+la.search_hint.anchor
						+	(imgPost?'" class="image-num':'')
						+	'">'
						+		postNum
						+	'.</a>'
						+	(imgPost?'':' ')
						+	t;
					}
					if (!imgRes) t = '<span class="post-text'+(u == 'u'?' u':'')+'">'+t+'</span>';
					tab[2] = t;
				} else tab[2] = (
					dtp.users
					? '<span class="u">'+la.count.self+'.</span>'
					: (
						dtp.rooms
						? '<br id="total-counts">'
						: '<br>'
					)
				);

				if (roomCount && roomDates) {
				var	k = (insideOut?' class="'+insideOut+'"':'');
					for (i in roomDates) lineHTML +=
						'<div'+k+'>'
					+		'<aside class="'+i+'">'
					+			roomDates[i]
					+		'</aside>'
					+	'</div>';
				}
				if (dtp.threads) {
					postID = 'post-'+threadNum+'-'+postNum;
					postAttr += '" id="'+postID;
				}
			//* left & right wrap:
				i = 2;
				while (i--) if (isNotEmpty(t = tab[i])) {
				var	asideAttr = ''
				,	menuID = threadNum+'-'+postNum+'-'+i
					;
					if (
						dtp.threads
					//||	dtp.found	//* <- TODO later
					||	(dtp.users && i > 0)
					) {
					var	j = param.archives || rootPath+'archive/'
					,	k = param.room || room
					,	m = '?'+(param.arch_term_name || 'name')+'='
					,	a = (
							userNameHidden
							? ''
							: encodeURIComponent(decodeHTMLSpecialChars(userName))
						//	: encodeURIComponent('/^'+escapeRegex(decodeHTMLSpecialChars(userName))+'$/iu')
						)
					,	reportID = (
							dtp.users
							? userID
							: menuID
						)
					,	modMenuID = (
							dtp.users
							? userID+'_'+threadNum+'_3'
							: menuID.replace(regNaNa, '_')
						)
					,	capBtnParts = (
							i == 0 && param.caps_width
							? [
								'javascript:capsPostButtonClick(\''
							+		postID
							+	'\','
							,	')" class="menu-btn-mark capture-mark'
							]
							: null
						)
					,	a = {
							arch_room: (
								i > 0
							&&	(!dtp.users || threadNum)
							&&	a.length > 0
							&&	j.length > 0
							&&	k.length > 0
								? j+k+'/'+m+a
								+ '" class="menu-btn-mark search-mark'
								: ''
							)
						,	arch_all: (
								i > 0
							&&	(!dtp.users || threadNum)
							&&	a.length > 0
							&&	j.length > 0
								? j+m+a
								+ '" class="menu-btn-mark search-mark'
								: ''
							)
						,	capture_thread:       (capBtnParts ? capBtnParts.join(0)       : '')
						,	capture_to_last_pic:  (capBtnParts ? capBtnParts.join(-1)      : '')
						,	capture_to_this_post: (capBtnParts ? capBtnParts.join(postNum) : '')
						,	report: (
								modEnabled && dtp.threads && !flag.n
								? (
									'javascript:openReportForm(\''
								+		reportID
								+	'\')" class="menu-btn-mark warn-mark'
								) : ''
							)
						,	user: (
								modEnabled && dtp.users
								? (
									'javascript:openReportForm(\''
								+		reportID
								+	'\')'
								) : ''
							)
						,	mod: (
								modEnabled && bnw.menu && (!flag.n || flag.g || i > 0)
								? (
									(postClickMenu = 1)
								,	(asideAttr += ' id="m_'+(
										modMenuID
									)+'"')
								,	(asideAttr += ' data-post="'+encodeTagAttr(
										editPostData
									)+'"')
								,	'javascript:menuOpen(\''
								+		modMenuID
								+	'\')" class="menu-btn-mark warn-mark" id="b_'
								+		modMenuID
								) : ''
							)
						}
					,	m = ''
						;
						for (k in a) if (j = a[k]) {
							m +=	'<a href="'+j+'">'
							+		la.post_menu[k]
							+	'</a>';
						}
						if (m) {
							postHoverMenu = 1;
							t =	'<div class="menu-wrap">'
							+		getDropdownMenuHTML(
										'<div class="stub">'
									+		t
									+	'</div>'
									, m, 'div')
							+		'&nbsp;'
							+	'</div>';
						}
					}
					if (dtp.threads) {
						j = '';
						if (b = param[k = reportOnPostTypes[i]]) {
							b = b.split('<br>');
							for (var rep_line_i in b) if (isNotEmpty(c = b[rep_line_i])) {
								d = c.indexOf(':');
								j +=	'<span class="report">'
								+		getFormattedTime(c.slice(0, d))
								+		' '+la.comment+':<br>'
								+		c.slice(d+1)
								+	'</span>';
							}
							param[k] = '';
						}
						if (j) t =
							'<span class="date-out '+'rl'[i]+'">'
						+		j
						+	'</span>'
						+	t;
					}
					c = [];
					if (i > 0) {
						c.push(dtp.reflinks ? 'ref' : 'r');
					}
					if (b = param[hintOnPostTypes[i]]) {
						asideAttr += ' title="'+b+'"';
					}
					tab[i] = '<aside'
					+		getTagAttrIfNotEmpty('class', c)
					+		asideAttr
					+	'>'
					+		t
					+	'</aside>';
				}
				lineHTML += tab.slice(0,3).filter(isNotEmpty).join('');
			}
			if (isNotEmpty(lineHTML)) {
		//* half width:
				if (!noShrink && (dtp.options || dtp.rooms)) lineHTML =
						'<div class="center">'
				+			lineHTML
				+		'</div>';
		//* toggle bg color:
				if (!dtp.found && !imgPost) alter = 1;
				if (alter) alt = !alt;
				lineHTML =
					'<div class="post'
				+		(dtp.found || dtp.threads || dtp.users?' pad':'')
				+		(dtp.users?' al':'')
				+		(postHoverMenu?' hover-menu':'')
				+		(postClickMenu?' click-menu':'')
				+		(modEnabled?' '+userClass[isNaN(u)?u:0]:'')
				+		(alt?' alt':'')
				+		(imgRes?' res':'')
				+		postAttr
				+	'">'
				+		lineHTML
				+	'</div>';
				++postNum;
			}
			return lineHTML;
		}

	var	threadHTML = ''
	,	threadMark = ''
	,	threadNum = ''
	,	threadReport = 0
	,	modEnabled = 0
	,	postNum = 1
	,	descNum = 0
	,	imgPost = 0
	,	alt = 1
	,	tableRow = []
	,	lines = threadText.split(regSplitLineBreak)
	,	line,s,t
		;
		for (var l_i in lines) if (isNotEmpty(line = lines[l_i])) threadHTML += getLineHTML(line);

		if (isNotEmpty(threadHTML)) {
			if (addMarks) threadsMarks.push(threadMark);
			if ((dtp.found || dtp.reports) && (t = param.room)) {
				threadHTML = getToggleThreadHTML(
					{
						content: threadHTML
					,	open: 1
					,	headerClass: 'post alt x3'
					,	headerText:
							'<a href="'
						+	(dtp.reports ? param.rooms : '')
						+	(t === '*' ? param.day_link : t+'/')
						+	'" class="anno dust">'
						+		t
						+	'</a>'
					,	buttonText: (dtp.found ? la.room_arch : la.room_logs)
					}
				);
			} else
			if (dtp.rooms && (t = param.type_title || param.type)) {
				++sectionCount;
				s = param.type || '';
				threadHTML = getToggleThreadHTML(
					{
						content: threadHTML
					,	open: 1
					,	headerClass: 'post alt x3'
					,	headerText:
							'<a href="'
						+	(s?s+'/':'.')
						+	'" class="anno dust">'
						+		t
						+	'</a>'
					,	buttonText: la.marks.rooms
					}
				);
				param.type = param.type_title = null;
			}
			return threadHTML;
		}
		return '';
	}

	if ((t = gc('single-thread'))[0]) return t;

var	flagVarNames = ['flag', 'flags']
,	dontCollapse = ['full', 'rooms']
,	hintOnPostTypes = ['left', 'right']
,	reportOnPostTypes = ['reports_on_post', 'reports_on_user']
,	raws = gn('textarea').concat(gn('pre'))
,	rawr = []
,	rawToDelete = []
,	pre
	;
	for (var r_i in raws) if ((pre = e = raws[r_i]) && (t = e.getAttribute('data-type'))) {

	//* already have generated content:
		if ((p = e.previousElementSibling) && (h = p.threadsHTML)) {
		var	i = p.threadsLastSortIndex || 0
		,	k = p.threadsSortIndexKeys || []
			;
			if (sortOrder === 'last') {
				rawr.push(p);
				i = !!(!p.innerHTML && (p.innerHTML = h[i]));
			} else {
			var	n = 0;
				if (typeof sortOrder !== 'undefined') {
					if ((j = k.indexOf(sortOrder)) >= 0) n = j; else
					if (sortOrder > 0) n = 1; else
					if (sortOrder < 0) n = 2; else h = '';
				}
				if (h) {
					h = (i == n && p.innerHTML?'':h[n]);
					p.threadsLastSortIndex = n;
				}
				i = !!(p.innerHTML = h);
			}
			if (i) {
				bnw.adorn();
				if (i = id('filter')) i.onchange(null, i);
			}
			continue;
		}

	//* generate first:
	var	raw = e.value || e.innerHTML
	,	dtp = o0(t, regSpace, 1)	//* <- split into object properties
	,	sectionCount = 0
	,	selfID = ''
	,	linesToSort = []
	,	threadsHTML = []
	,	threadsMarks = []
	,	threads = []
	,	thread
		;
		if (dtp.users) {
		var	lines = raw.split(regSplitLineBreak)
		,	lastDay = ''
		,	threadsByDay = []
			;
			for (var l_i in lines) {
			var	line = lines[l_i]
			,	i = line.indexOf('\t')
				;
				if (i >= 0) {
				var	t = getFormattedTime(line.slice(0, i), 1, 1);
					if (lastDay != t) {
						lastDay = t;
						threadsByDay.push('\n');
					}
					if (line.lastIndexOf('\t') != i) linesToSort.push(line);
				}
				threadsByDay.push(line);
			}
			raw = threadsByDay.join('\n');
		}
		if (dtp.reflinks) {
		var	lines = raw.split(regSplitLineBreak)
		,	domainNames = []
		,	threadsByDomain = {}
			;
			for (var l_i in lines) {
			var	d = 0;
				if ((i = (line = lines[l_i]).lastIndexOf('\t')+1) > 0) {
				var	m = line.slice(i).match(/^(\w*:\/+)?([^:\/]+)/);
					if (m) d = m[2].replace(/\W+$/, '').split('.').slice(-2).join('.'), linesToSort.push(line);
				}
				if (domainNames.indexOf(d) < 0) domainNames.push(d), threadsByDomain[d] = [];
				threadsByDomain[d].push(line);
			}
			for (i in domainNames.sort()) threads.push(threadsByDomain[domainNames[i]].join('\n'));
		} else {
			threads = raw.split('\n\n');
		}
		for (var t_i in threads) if (isNotEmpty(thread = threads[t_i])) {
		var	t = getThreadHTML(thread, 1);
			if (t) threadsHTML.push(t);
		}

	var	p = e.parentNode
	,	barButtons = {
			close:	'<a href="javascript:showContent(null)">'
			+		la.bottom.close
			+	'</a>'
		,	top:	'<a href="javascript:document.body.firstElementChild.scrollIntoView(false)">'
			+		la.bottom.top
			+	'</a>'
		,	hide:	'<a href="javascript:meta()">'
			+		la.bottom.hide
			+	'</a>'
		,	narrow:	'<a href="javascript:fit()">'
			+		la.bottom.narrow
			+	'</a>'
		}
	,	centerButtons =
			'<span class="center">'
		+		'<span>'
		+			barButtons.hide
		+			NB
		+			barButtons.narrow
		+		'</span>'
		+	'</span>'
	,	afterThreadsBar = (
			'<div class="thread task">'
			+	'<p class="hint">'
			+		centerButtons
			+		'<span class="r">'
			+			barButtons.top
			+		'</span>'
			+		(threadsHTML.length > 1 ? barButtons.close : NW)
			+	'</p>'
			+'</div>'
		);

		if (threadsHTML.length) {
		var	e = cre('div', p, e);
			if (sectionCount) {
				e.className = 'threads combined';
				e.threadsHTML = e.innerHTML = threadsHTML.map(
					function(v) {return '<div class="thread">'+v+'</div>';}
				).join('');
			} else
			if (threadsHTML.length > 1) {
		//* multiple threads, top bar with counters:
			var	o = {
					left: []
				,	right: []
				,	marks: []
				}
			,	a = la.marks.active
			,	j = ''
			,	k = ''
			,	l = la.count
			,	m = ''
			,	n = '<br>'
			,	sep = ', '
			,	splitRanges = {}
				;
				for (i in la.groups) if (dtp[i]) {a = la.groups[i]; break;}
				o.left.push(
					'<a href="javascript:showContent()">'
				+		a+': '+(sectionCount || threadsHTML.length)
				+	'</a>'
				);
				if (flag.c) for (i in count) if (k = count[i]) {
					if (dtp.reflinks || (dtp.users && i[0] != 'u')) {
					var	sign = (l[i]?'+':'-')
					,	neg = (sign === '-')
					,	k_i = sign+'Infinity'
					,	l_i = linesToSort.length
						;
						j = (	l[i] ? l.total :
						(	dtp.reflinks ? (l.lastr || l.last) :
							l.last
						));
						j = '<a href="javascript:showContent(\''+k_i+'\')">'+j+'</a>';
						if (l_i > splitSort) {
						var	l_min = 1
						,	l_max = l_i-1
						,	m = []
							;
							if (neg) {
								for (l_i = l_max-splitSort+1; l_i > l_min; l_i -= splitSort) m.push(l_i);
								if (l_i <= l_min) m.push(l_i);
							} else {
								for (l_i = splitSort; l_i < l_max; l_i += splitSort) m.push(l_i);
								if (l_i >= l_max) m.push(l_i);
							}
							if (m) {
								m = splitRanges[k_i] = m.map(function(v) {
								var	a = Math.max(l_min, neg ? v : v-splitSort+1)
								,	b = Math.min(l_max, neg ? v+splitSort-1 : v)
									;
									return neg ? b+'-'+a : a+'-'+b;
								});
								j = getDropdownMenuHTML(j, m.map(function(v) {
									return	'<a href="javascript:showContent(\''+v+'\')">'
									+		v.replace('-', ' &mdash; ')
									+	'</a>';
								}).join(n));
							}
						}
					} else {
						j = (	!l[i] ? l.last :
						(	dtp.users && i == 'u' ? l.self :
						(	dtp.found && i == 'o' ? l.total+' '+l.posts :
							l[i]
						)));
					}
					k = j+': '+k;
					if (i == 'img') o.left.push(k);
					else if (l[i]) o.right.push(k);
					else o.right[o.right.length-1] += sep+k;
				}
				if (m = threadsMarks) {
					j = {};
					for (i in reportClass) j[i] = [];
					for (i in m) if (a = m[i]) j[a.class || 0].push(a);
					for (i in j) if ((m = j[i]).length) {
						if (i == 0) a = '';
						else {
							m.sort(function(a,b) {return a.t == b.t?0:(a.t > b.t?1:-1);}).reverse();
							a = la.marks[reportClass[i]]+': '+m.length+sep;
						}
						o.marks.push(
							a
						+	m.map(
								function(v) {
								var	i = v.id;
									if (regNaN.test(i)) i = "'"+i+"'";
									return (v.t && !v.class
									?	v.id.replace('/', ': ')
									+	v.posts+' '+l.posts+sep
									+	'<a href="javascript:showOpen('+i+',true)">'
									+		(v.s || '?')
									+	'</a> &mdash; '
									: '')
									+	'<a href="javascript:showOpen('+i+(v.t?'':',true')+')">'
									+		(v.t || v.id)
									+	'</a>';
								}
							).join(a?sep:n)
						);
					}
				}
				for (i in o) o[i] = o[i].join(n);
				if (j = o.marks) o[dtp.found?'right':'left'] += n+j;
				if (j = o.right) o.right = '<span class="r">'+j+'</span>';
				b = cre('div', document.body, getParentBeforeTagName(e, 'body'));
				b.className = 'task';
				b.innerHTML =
					'<p class="hint">'
				+		centerButtons
				+		o.right
				+		o.left
				+	'</p>';
		//* unsorted content:
			var	h = [threadsHTML.map(
					function(v,i) {
						if (i = threadsMarks[i]) {
						var	j = reportClass[i.class || 0]
						,	k = ' '+j+'" id="'+i.id
							;
							if (dontCollapse.indexOf(j) < 0) v = getToggleThreadHTML(
								{
									content: v
								,	headerClass: 'post alt anno'
								,	buttonText: la.hint[j]
								}
							);
						}
						return	'<div class="thread'+(k || '')+'">'
						+		v
						+	'</div>';
					}
				).join('')];
		//* sorted content:
				if ((j = linesToSort) && (l = j.length)) {
				var	aik = new Array(h.length)
				,	r = splitRanges
					;
					['sort','reverse'].map(function(v) {

						function addSortedLinesHTML(lines, key) {
							aik[h.length] = key;
							h.push(
								'<div class="thread">'
							+		getThreadHTML(lines.join('\n'))
							+	'</div>'
							);
						}

					var	s = (v === 'sort')
					,	k = (s?'+':'-')+'Infinity'
						;
						addSortedLinesHTML(j[v](), k);
		//* sorted content parts:
						if (r && (v = r[k])) for (var i = 0, n = v.length; i < n; i++) {
						var	k = v[i]
						,	a,b = k.split('-').map(orz)
							;
							if (s) {
								a = b[0], b = b[1]+1;
							} else {
								a = l-b[0]-1, b = l-b[1];
							}
							addSortedLinesHTML(j.slice(a, b), k);
						}
					});
					e.threadsSortIndexKeys = aik;
				}
				for (i = 0, j = h.length; i < j; i++) if (h[i]) h[i] += afterThreadsBar;
				e.className = 'threads';
				e.threadsHTML = h;
		//* show open threads on page load only if option set:
				if (flag.a) e.innerHTML = h[0];
			} else {
		//* single thread, without counters:
				if (dtp.found || (dtp.threads && !flag.v)) cre('div', p, e.nextElementSibling).outerHTML = afterThreadsBar;
				j = (
					(i = threadsMarks)
				&&	(i = i[0])
				&&	(i = i.class)
				&&	(i = reportClass[i])
					? ' '+i
					: ''
				);
				e.className = 'thread single-thread'+j;
				e.threadsHTML = e.innerHTML = threadsHTML.join('');
			}
			if (dtp.threads) e.className += ' aside-wider';
			for (i in (a = gn('select', e))) if (a[i].onchange) a[i].onchange();
		} else del(e);

		if (dtp.options) {
		var	a = gn('textarea')
		,	i = a.length
			;
			while (i--) if ((e = a[i]) && (v = e.value) && v.length) {
				e.value = v.replace(regTextAreaBR, '\n');
			}
		}

		if (dtp.pages && param && (e = id('task'))) {
		var	t = document.title
		,	h = (e.firstElementChild || e).textContent.replace(regTrimWord, '')
		,	i = t.lastIndexOf(h)+h.length
		,	singlePage = (typeof param.start !== 'undefined')
			;
			param.on_page = (touch && !singlePage ? 20 : orz(param.on_page));
			param.start = orz(param.start);
			param.total = orz(param.total);
			param.title = [
				t.slice(0, i)
			,	t.slice(i)
			];
			if (a = gn('p', e)[0]) a.innerHTML += '\n<span id="range"></span>';
			e.innerHTML += (
				singlePage
				? (
					'<p>'+la.page+': '
				+		'<span title="'
				+			encodeTagAttr(la.page_limit_hint)
				+		'">1*</span>'
				+	'</p>'
				) : (
					param.total > param.on_page
					? '<p id="pages"'+(touch?' class="touch"':'')+'></p>'
					: '<p>'+la.page+': 1</p>'
				)
			)+'<div id="thumbs"></div>'+(
				singlePage
				? '<p class="hint">* '+la.page_limit_hint+'</p>'
				: ''
			);
			page(1);
		}

		if ((e = id('total-counts')) && count.o.length) {
		var	a = '<aside class="a '
		,	b = '<div'+(insideOut?' class="'+insideOut+'"':'')+'>'+a
		,	c = '</aside>'
		,	d = c+'</div>'
		,	j = param.separator || ','
			;
			e.outerHTML =
				b+'l">'+count.oLast+d
			+	b+'r">'+count.uLast+d+'<br>'
			+	a+'l">'+count.u.join(j)+c
			+	a+'r">'+count.o.join(j)+c+e.outerHTML;
		}

		del(gn('noscript', p));

		if (p.lastElementChild) {
			if (touch) toggleClass(p, 'wider', 1);
			rawToDelete.push(pre);
		} else del(p);
	}

	if (rawToDelete.length) window.addEventListener('load', function() {
		rawToDelete.map(function(e) {
			e.textContent = '';	//* <- clean up raw data, but keep element in DOM
		});
	}, false);

	if (rawr.length) return rawr;
}

//* Runtime: top panel, etc *--------------------------------------------------

bnw.adorn = function(i) {
	bnw.map(function(f) {
		f(i);
	});
};

gn('time').map(function(e) {
var	t = e.getAttribute('data-t');
	if (t && t > 0) e.outerHTML = getFormattedTime(t);
});

if (k = id('task')) {
//* room task:

	function addTaskBtn(content, attr, parent) {
	var	i,e = parent || gc('buttons', taskTop)[0];
		if (!e) {
			e = cre('span', taskTop, id('task-text'));
			e.className = 'buttons r';
		}
		e = cre('a', e, e.firstElementChild);
		e.innerHTML = content;
		if (attr) for (i in attr) if (attr[i]) e.setAttribute(i, attr[i]);
		return e;
	}

	function addTaskMenuBtn(content, attr, parentId) {
	var	a,e = id(parentId);
		if (a = e) {
			if (a.href) {
				a.removeAttribute('id');
				addDropdownMenuWrapper(a, parentId);
			}
			cre('br', e = id(parentId), e.firstElementChild);
			addTaskBtn(content, attr, e);
		} else addTaskBtn(content, attr).id = parentId;
	}

var	a = orz(k.getAttribute('data-autoupdate'))
,	d = orz(k.getAttribute('data-deadline'))
,	t = orz(k.getAttribute('data-taken'))
,	p = gn('p',k)[0] || k.firstElementChild || k
,	m = 'task-change-buttons'
,	i,j,k,l,m,n
	;

	if (t && !(a || id('task-img') || id('task-text'))) {
		t = -1;
	}

	while (p && regTagForm.test(p.tagName)) {
		p = p.parentNode;
	}

	if (taskTop = p) {
	var	taskKeepNum = k.getAttribute('data-keep')
	,	taskSkipNum = k.getAttribute('data-skip')
	,	taskUnskipNum = k.getAttribute('data-unskip')
	,	taskReportNum = k.getAttribute('data-report')
		;

		if (taskSkipNum) {
			addTaskMenuBtn(
				'X'
			,	{
					href: 'javascript:skipMyTask(' + taskSkipNum + ')'
				,	title: la.skip_hint
				}
			,	m
			);
		}

		if (taskReportNum) {
			addTaskMenuBtn(
				la.report
			,	{
					href: "javascript:openReportForm('" + taskReportNum + "')"
				}
			,	m
			);
		}

		if (
			taskKeepNum
		&&	ON.indexOf(taskKeepNum) < 0
		) {
		// var	match = location.search.match(/(^|[?&])(draw_app=[^&]+)/i);

			addTaskMenuBtn(
				la.keep_task
			,	{
					// href: '?keep=on'+(match ? '&'+match[2] : '')
					href: 'javascript:keepMyTask(' + taskKeepNum + ')'
				,	title: la.keep_task_hint
				}
			,	m
			);
		}

		for (var changeType in la.task) {
		var	possibleChanges = k.getAttribute('data-' + changeType);

			if (possibleChanges) possibleChanges.split(regSplitWord).map(
				function(newTaskType) {
					addTaskMenuBtn(
						la.task[changeType][newTaskType]
					,	{
							// href: ('?' + (changeType == 'free'?'':changeType+'=') + newTaskType).replace(regREqual, '')
							href: (
								changeType == 'free'
								? '?' + newTaskType
								: "javascript:changeMyTask('" + changeType + "', '" + newTaskType + "')"
							)
						}
					,	m
					);
				}
			);
		}

		if (taskUnskipNum) {
			addTaskBtn(
				la.unskip
			,	{
					href: 'javascript:void ' + orz(taskUnskipNum)
				,	title: la.unskip_hint
				,	id: 'unskip'
				,	'data-room': room
				}
			);
		}

		if (t > 0) {
			addTaskBtn(
				'<span id="'+CS+'">?</span>'
			,	{
					href: 'javascript:checkMyTask()'
				,	title: String(t > 0 ? new Date(t) : new Date) + toolTipNewLine + la.check
				,	class: (a > 0?'auto ':'')+'ready'
				}
			);
		}
	}
	if (
		(i = gi('submit',k)[0])
	&&	(f = getParentByTagName(i, 'form'))
	) {
		if (t) f.setAttribute('onsubmit', 'return checkMyTask(event, this)');
		else f.addEventListener('submit', formCleanUp, false);
	}
	if (t > 0) {
		f = autoUpdateTaskTimer;
		taskTime = {
			taken:		1000*t
		,	deadline:	1000*d
		,	intervalFail:	1000*600
		,	intervalMin:	1000*5
		,	intervalMax:	1000*a
		,	intervalCheck:	(i = 1000)
		,	interval: setInterval(f, i)
		};
		if (a) window.addEventListener('focus', f, false);
		document.addEventListener('DOMContentLoaded', f, false);
	}
//* room task image, set up resize on click:
	if (
		(i = id('task-img') || gn('img',k)[0])
	&&	(a = i.alt)
	&&	(j = a.indexOf(';')+1)
	) {
		i.alt = a
			.replace(';',', ')
			.replace('*','x');
		setPicResize(i,j+1);
	}
//* archive search:
	for (i in (j = gi('text',k))) if (
		(e = j[i])
	&&	(n = (e.getAttribute(m = 'data-select') || e.name).replace(regTrim, ''))
	&&	regSplitLineBreak.test(n)
	) {
		e.removeAttribute(m);
	var	max = 0
	,	o = {}
		;
		for (n in (lines = n.split(regSplitLineBreak))) if (line = lines[n].replace(regTrim, '')) {
		var	a = line.split('\t')
		,	inputName = a.shift().replace(regTrim, '')
		,	optionText = a.shift().replace(regTrim, '')
		,	inputHint = a.join(' ').replace(regTrim, '')
			;
			if (inputName.length && optionText.length) {
				o[inputName] = optionText, ++max;
				if (inputHint.length) inputHints[inputName] = inputHint;
			}
		}
		if (o) {
			e = e.parentNode.nextElementSibling;

			e = cre('select', cre(e.tagName, e.parentNode, e));
			e.setAttribute('onchange', 'setSearchType(this)');
			for (n in o) e.options[e.options.length] = new Option(o[n], n);
			e.onchange();

			if (e = getParentByTagName(e, 'form')) {
				e.id = selectId = 'select-search-type'+(i > 0?'-'+i:'');

				e = cre('p', e.parentNode, e.nextElementSibling);
				e.className = 'hint r';
				e.innerHTML =
					'+ <a href="javascript:void '+max
				+	'" onClick="addSearchTerm(\''+selectId+'\', '+max+')'
				+	'">'
				+		la.search_add
				+	'</a>';

				if ((e = id('research')) && (a = gn('a',e))) {
					document.title += '. '+e.textContent.replace(regTrim, '');
				var	l = (a.length > 1);
					for (n in a) if ((e = a[n]).name) {
						if (l && n > 0) addSearchTerm(selectId);
						e.href = 'javascript:void \''+e.name+'\'';
						e.onclick = restoreSearch;
						e.title = la.search_hint.restore;
						e.click();
					}
				}
			}
		} else e.name = '!';
	}
//* show/hide rules:
	if (i = (j = gn('ul',k).concat(gc('hid',k))).length) {
		while (i--) if (
			(m = (n = j[i]).previousElementSibling)
		&&	!((e = m.firstElementChild) && e.tagName.toLowerCase() === 'a')
		) {
			h = regClassHid.test(n.className);
			m.innerHTML = getToggleButtonHTML(m.innerHTML, !h);
		}
	}
}

showContent();

if (e = id('time-zone')) e.innerHTML = getFormattedTimezoneOffset();
if (e = id('filter')) {
	e.onchange = e.onkeyup = filter;
	if (
		(h = location.hash)
	&&	(i = e.getAttribute('data-filter'))
	&&	i === h.slice(-1)
	) {
		filterPrefix(h.replace(/^#+/, ''));
	}
}

bnw.adorn(1);

for (i in la.clear) if (e = id(i)) {
	e.onclick = clearSaves;
	if (!e.href) e.disabled = true, (e.onmouseover = checkSaves)(i);
}
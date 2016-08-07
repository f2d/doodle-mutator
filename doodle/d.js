var	LS = window.localStorage || localStorage

,	regClassAlt = clr('alt|ok')
,	regClassAnno = clr('anno')
,	regClassPost = clr('post')
,	regClassThread = clr('thread')
,	regTagDiv = /^div$/i
,	regTagDivP = /^(div|p)$/i
,	regTagForm = /^form$/i
,	regTagPre = /^pre$/i
,	regImgTag = /<img [^>]+>/i
,	regImgTitle = /\s+(title="[^"]+)"/i
,	regImgUrl = /(".*\/([^\/"]*)")>/
,	regTimeDrawn = /^((\d+)-(\d+)|[\d:]+)(?:=(\d+))?,(.*)$/m
,	regTimeBreak = /^\d+(<|>|,|$)/
,	regLineBreak = /^(\r\n|\r|\n)/gm
,	regLNaN = /^\D+/
,	regNaN = /\D+/
,	regSpace = /\s+/g
,	regSpaceHTML = /\s|&nbsp;|&#8203;/gi
,	regTrim = /^\s+|\s+$/g
,	regTrimWord = /^\W+|\W+$/g

,	splitSec = 60
,	TOS = ['object','string']
,	NB = '&nbsp;'
,	NW = '&#8203;'
,	CS = 'checkStatus'
,	CM = 'checkMistype'
,	drawQuery = '?draw'
,	checking, flag = {}, param = {}, inputHints = {}
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
,	mm
,	room = location.pathname.split('/').slice(-2)[0] || 'room'
,	rootPath = gn('link').reduce(function(r,e) {
		return e.rel == 'index' && e.href
		? e.href.replace(/^\w+:+\/+[^\/]+/, '')
		: r;
	}) || '/'
,	touch = ('ontouchstart' in document.documentElement)
,	insideOut = (touch?'':'date-out')

,	d = document.body.style
,	maxWidth = [d.maxWidth||'1000px', '690px']

,	la, lang = document.documentElement.lang || 'en'
	;

//* UI translation *-----------------------------------------------------------

if (LS && !(LS.lang && LS.lang == lang)) LS.lang = lang;

if (lang == 'ru') la = {
	toggle: ['да', 'нет']
,	room_arch: 'Архив комнаты'
,	arch: 'архив'
,	page: 'Страница'
,	search_add: 'Добавить предмет поиска'
,	search_remove: 'Убрать'
,	search_hint: {
		restore: 'Вернуть значение в поле ввода.'
	,	anchor: 'Вечная ссылка на этот пост в результате этого поиска.'
	,	thread: 'Перейти к той нити, в которой был найден этот пост.'
	,	name: 'Искать этого автора.'
	}
,	draw_test: 'Попробовать'
,	draw: 'Рисовать'
,	check: 'Нажмите, чтобы проверить и продлить задание.'
,	task_mistype: 'Тип задания сменился, обновите страницу или нажмите сюда.'
,	task_changed: 'Задание было изменено другими действиями за прошедшее время.'
,	send_new_thread: 'Будет создана новая нить.'
,	send_anyway: 'Всё равно отправить?'
,	canceled: 'Отправка отменена'
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
,	hax: '(?)'		//'Неизвестный набор данных.'
,	time: 'Нарисовано за'
,	using: 'с помощью'
,	resized: 'Размер'	//'\nИзображение уменьшено.\nРазмер'
,	resized_hint: 'Кликните для просмотра изображения в полном размере.'
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
	,	frozen: 'Замороженная нить.\n'
	,	burnt: 'Выжженная нить.\n'
	,	full: 'Полная нить.\n'
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
	,	users: 'Групп по дням'
	,	reflinks: 'Групп по доменам'
	}
}; else la = {
	toggle: ['yes', 'no']
,	room_arch: 'Room archive'
,	arch: 'archive'
,	page: 'Page'
,	search_add: 'Add search term'
,	search_remove: 'Remove'
,	search_hint: {
		restore: 'Restore this text into search input field.'
	,	anchor: 'Permanent link to this post in this search result.'
	,	thread: 'Go to the thread, where this post is from.'
	,	name: 'Search this name.'
	}
,	draw_test: 'Try drawing'
,	draw: 'Draw'
,	check: 'Click this to verify and prolong your task.'
,	task_mistype: 'Task type changed, please reload the page or click here.'
,	task_changed: 'Task was changed by some actions in the meantime.'
,	send_new_thread: 'Sending will make a new thread.'
,	send_anyway: 'Send anyway?'
,	canceled: 'Sending canceled'
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
,	hax: '(?)'		//'Unknown data set.'
,	time: 'Drawn in'
,	using: 'using'
,	resized: 'Full size'	//'\nShown image is resized.\nFull size'
,	resized_hint: 'Click to view full size image.'
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
	,	frozen: 'Frozen thread.\n'
	,	burnt: 'Burnt thread.\n'
	,	full: 'Full thread.\n'
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
	,	users: 'Groups by day'
	,	reflinks: 'Groups by domain'
	}
};

//* Utility functions *--------------------------------------------------------

function decodeHTMLSpecialChars(t) {
	return String(t)
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
function getStyleValue(obj, prop) {
var	o;
	if (o = obj.currentStyle) return o[propNameForIE(prop)];
	if (o = window.getComputedStyle) return o(obj).getPropertyValue(prop);
	return null;
}

function getParentByTagName(e, target) {
var	p = e, t = target.toLowerCase();
	while (e.tagName.toLowerCase() != t && (p = e.parentNode)) e = p;
	return e;
}

function getParentBeforeTagName(e, target) {
var	p = e, t = target.toLowerCase();
	while ((e = e.parentNode) && e.tagName.toLowerCase() != t) p = e;
	return p;
}

function getParentBeforeClass(e, c) {
var	p = e, r = clr(c);
	while ((e = e.parentNode) && !r.test(e.className)) p = e;
	return p;
}

function showProps(o,z /*incl.zero*/) {
var	i,t = '';
	for (i in o) if (z || o[i]) t += '\n'+i+'='+o[i];
	return alert(t), o;
}

function clr(c) {return new RegExp('(^|\\s)('+c+')($|\\s)', 'i');}
function o0(line, split, value) {
var	a = line.split(split || ','), i,o = {};
	for (i in a) o[a[i]] = value || 0;
	return o;
}

function gn(n,p) {return TOS.slice.call((p || document).getElementsByTagName(n) || []);}
function gi(t,p) {return (p = gn('input', p)).length && t ? p.filter(function(e) {return e.type == t;}) : p;}
function id(i) {return document.getElementById(i);}
function del(e,p) {
	if (p?p:p = e.parentNode) p.removeChild(e);
	return p;
}

function cre(e,p,b) {
	e = document.createElement(e);
	if (b) p.insertBefore(e, b); else
	if (p) p.appendChild(e);
	return e;
}

function eventStop(e) {
	if (e && e.eventPhase?e:e = window.event) {
		if (e.stopPropagation) e.stopPropagation();
		if (e.cancelBubble != null) e.cancelBubble = true;
	}
	return e;
}

function deleteCookie(c) {document.cookie = c+'=; expires='+(new Date(0).toUTCString())+'; Path='+rootPath;}
function toggleHide(e,d) {e.style.display = (e.style.display != (d?d:d='')?d:'none');}
function toggleClass(e,c,keep) {
var	i = e.className, a = (i?i.split(regSpace):[]), i = a.indexOf(c);
	if (i < 0) {
		if (!(keep < 0)) a.push(c);
	} else {
		if (!(keep > 0)) a.splice(i, 1);
	}
	e.className = a.join(' ');
}

function meta() {toggleClass(id('content') || document.body, 'hide-p');}
function fit() {
var	e = (id('content') || document.body).style, w = maxWidth;
	e.maxWidth = w[e.maxWidth != w[1]?1:0];
}

function orz(n) {return parseInt(n||0)||0;}
function leftPad(n) {n = orz(n); return n > 9 || n < 0?n:'0'+n;}
function notEmpty(t) {return String(t).replace(regSpaceHTML, '').length;}
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

function getFTimeIfTime(t, plain) {return regTimeBreak.test(t = ''+t) ? getFormattedTime(t, plain) : t;}
function getFormattedTime(t, plain, only_ymd) {
	if (TOS.indexOf(typeof t) > -1) t = orz(t)*1000;
var	d = (t ? new Date(t+(t > 0 ? 0 : new Date())) : new Date());
	t = ('FullYear,Month,Date'+(only_ymd?'':',Hours,Minutes,Seconds')).split(',').map(function(v,i) {
		v = d['get'+v]();
		if (i == 1) ++v;
		return leftPad(v);
	});
var	YMD = t.slice(0,3).join('-'), HIS = t.slice(3).join(':');
	return (
		plain
		? YMD+' '+HIS
		: '<time datetime="'+YMD+'T'+HIS
		+	getFormattedTimezoneOffset(t)
		+	'" data-t="'+Math.floor(d/1000)
		+	'">'+YMD+' <small>'+HIS+'</small></time>'
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
	if (checking) return;
	checking = 1;
var	d = 'data-id', f = id(CM), s = id(CS), r = new XMLHttpRequest();
	if (f) del(f);
	if (e && e.tagName) f = getParentByTagName(e, 'form'); else
	if (f = s.getAttribute(d)) {
		f = id(f), s.removeAttribute(d);
		if (!regTagForm.test(f.tagName)) f = gn('form', f)[0];
	}
	if (f && event) event.preventDefault();
	s.textContent = la.load+0;
	r.onreadystatechange = function() {
		if (r.readyState == 4) {
			if (r.status == 200) {
			var	k = '\n'
			,	j = r.responseText.split(k)
			,	i = j.pop()
			,	j = j.join(k)
			,	status = j
					.replace(/<[^>]+>/g, '')
					.replace(regSpace, ' ')
					.replace(regTrim, '')
			,	error = j.match(/\bid=["']*([^"'>\s]*)/i)
			,	message = (error?status:'')
			,	img = i.match(/<img[^>]+\balt=["']*([^"'>\s]+)/i)
			,	task = (img?img[1]:i)
				;
				if (k = id('task')) {
					i = (e = gn('img', k)).length;
					if (!i == !!img) {
						e = s, error = 1;
						while (!regTagDivP.test(e.tagName) && (i = e.parentNode)) e = i;
						e = cre('b', e);
						e.id = CM;
						e.className = 'post r';
						e.innerHTML =
							'<b class="date-out l">'
						+		'<b class="report">'
						+			la.task_mistype.replace(/\s(\S+)$/, ' <a href="'+drawQuery+'">$1</a>')
						+		'</b>'
						+	'</b>';
					} else
					if (!img) {
						if (
							(e = gn('p', k)).length > 1
						&&	!regTagForm.test((e = e[1]).previousElementSibling.tagName)
						&&	decodeHTMLSpecialChars(e.innerHTML) != decodeHTMLSpecialChars(task)
						) e.innerHTML = task, error = 1;
					} else
					if (i) {
					var	e = e[0]
					,	i = e.getAttribute('src')
					,	k = task.indexOf(';')+1
						;
						j =	(flag.pixr || (flag.pixr = i.split('/').slice(0, flag.p?-3:-1).join('/')+'/'))
						+	(flag.p?getPicSubDir(task):'')
						+	(k?task.replace(/(\.[^.\/;]+);.+$/,'_res$1'):task);
						if (i != j) e.src = j, e.alt = task, setPicResize(e, k), error = 1;
					}
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
				}
			} else {
				status = la.fail;
				task = r.status || 0;
			}
			s.textContent = status;
			s.title = new Date()+'\n\n'+task;
			checking = 0;
		} else s.textContent = la.load+r.readyState;
	};
	r.open('GET', f?'--':'-', true);
	r.send();
}

function skipMyTask(v) {
var	f = cre('form', document.body), i = cre('input',f);
	f.setAttribute('method', 'post'), i.type = 'hidden', i.name = 'skip', i.value = v;
	f.submit();
}

function getPicSubDir(p) {var s = p.split('.'); return s[1][0]+'/'+s[0][0]+'/';}
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

function filter(event) {
var	e = eventStop(event).target
,	f = Math.max(0, orz(e.getAttribute('data-filter')))
,	v = (e.value || '').replace(regTrim, '').toLowerCase()
,	k = 'lastFilterValue'
,	i = e[k]
	;
	if (i && i == v) return;
	e[k] = v;
var	containers = showContent('last')
	;
	for (var c_i = 0, c_len = containers.length; c_i < c_len; c_i++) {
	var	container = e = containers[c_i]
	,	threads = gn('div', e).filter(function(e) {return regClassThread.test(e.className);})
	,	foundThreads = 0
		;
		if (!threads.length) threads = [e];
		for (var t_i = 0, t_len = threads.length; t_i < t_len; t_i++) {
		var	thread = e = threads[t_i]
		,	posts = gn('div', e).filter(function(e) {return regClassPost.test(e.className) && !regClassAnno.test(e.className);})
		,	foundPosts = 0
		,	eqAlt = 0
		,	alt = 0
			;
			if (!posts.length) continue;
			for (var p_i = 0, p_len = posts.length; p_i < p_len; p_i++) {
			var	post = e = posts[p_i]
			,	found = 1
			,	c = 0
				;
				if (v.length) {
					while (e && e.firstElementChild == (c = e.lastElementChild)) e = c;
					if (e && c) {
						if (f === 2) e = c; else
						if (f === 1) e = gn('p', e).filter(function(p) {return p.parentNode == e;}).slice(-1)[0];
					}
					c = '';
					if (e
					&&	(c = (e.firstChild || e).textContent)
					&&	(c = c.replace(regTrim, '')).length
					&&	(c = c.toLowerCase()).indexOf(v) < 0
					) found = 0;
					else eqAlt = ((c = (c === v)) ? !eqAlt : 0);
				}
				if (found) {
					alt = !alt;
					toggleClass(post, 'alt', (c ? eqAlt : alt)?-1:1);
					toggleClass(post, 'ok', c?1:-1);
				}
				post.style.display = (found?(++foundPosts, ''):'none');
			}
			thread.style.display = (foundPosts?(++foundThreads, ''):'none');
		}
		container.style.display = (foundThreads?'':'none');
	}
}

function showOpen(i,top) {
var	t = id(i) || (showContent(), id(i)), d = t.firstElementChild;
	if (d && (i = d.firstElementChild) && i.href) d.nextElementSibling.style.display = '';
	t.scrollIntoView(!!top);
}

//* Options-specific functions *-----------------------------------------------

function getSaves(v,e) {
var	keep = (e?e.getAttribute('data-keep'):0) || ''
,	room = (e?e.getAttribute('data-room'):0) || ''
,	j = []
,	k = []
,	l = keep.length
	;
	if (v == 'unskip') {
	var	m,a = document.cookie.split(/;\s*/), i = a.length, r = /^([0-9a-z]+-skip-[0-9a-f]+)=([^\/]+)\/(.*)$/i;
		while (i--) if (
			(m = a[i].match(r))
		&&	(m[2] = decodeURIComponent(m[2]))
		&&	(!keep || keep !== m[1].substr(0,l))
		&&	(!room || room === m[2])
		) {
			k.push(m[1]);
			j.push(m[2]+': '+getFormattedNumUnits(m[3].split('/').length, la.clear[v].unit));
		}
	} else
	if (v == 'unsave' && LS && (i = LS.length)) {
		while (i--) if (
			(m = LS.key(i))
		&&	(m !== 'lang')
		&&	(!keep || keep !== m.substr(0,l))
		) {
			k.push(m);
			j.push(m+': '+getFormattedNumUnits(LS.getItem(m).length, la.clear[v].unit));
		}
	}
	return {rows: j.sort(), keys: k.sort()};
}

function checkSaves(e) {
	if (e.target) var v = (e = e.target).id; else e = id(v = e);
	if (e) e.disabled = !(getSaves(v,e).keys.length);
}

function clearSaves(e) {
	if (e) {
		if (e.preventDefault) e.preventDefault(), e = e.target;
		if (v = e.id) {
		var	v,a = getSaves(v,e), k = a.keys;
			if (k.length && confirm(la.clear[v].ask+'\n\n'+a.rows.join('\n'))) {
				for (i in k) {
					if (v == 'unskip') deleteCookie(k[i]); else
					if (v == 'unsave') LS.removeItem(k[i]);
				}
				if (e.getAttribute('data-room')) del(e), document.location.reload(true);
				else e.disabled = true;
			}
		}
	}
	return false;
}

function allowApply(n) {
var	e = id('apply');
	if (e) e.disabled = (n < 0);
}

function selectLink(e,r,t) {
var	i = e.name+'_link', a = id(i), r = r.replace('*', r.indexOf('.') < 0 ? e.value : e.value.replace(/\.[^.\/]+$/, ''));
	if (a) a.href = r, allowApply();
	else {
		e = e.parentNode.nextSibling;
		e = cre('div', e.parentNode, e);
		e.className = insideOut;
		e.innerHTML = '<p class="l"><a href="'+r+'" id="'+i+'">'+(t || la.draw_test)+'</a></p>';
	}
}

//* Archive-specific functions *-----------------------------------------------

function addSearchTerm(e, max) {
	if (e && e.length) e = id(e);
var	form = getParentByTagName(e, 'form')
,	row = e = form.firstElementChild
,	n = 1
	;
	while (e = e.nextElementSibling) ++n;
	if (!max || n < max) {
		n = cre(row.tagName, form);
		n.innerHTML = row.innerHTML;
		if (e = gn('select', n)[0]) e.onchange();
		if (e = gi('submit', n)[0]) {
			e = e.parentNode;
			e.className = 'rem';
			e.innerHTML =
				'<span>&minus; <a href="javascript:void this" onClick="removeSearchTerm(this)">'
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
	if (i = i[0]) i.placeholder = inputHints[i.name = e.value] || '';
}

function restoreSearch(e) {
	if (e && e.target) e = e.target;
	if (!(e && e.tagName)) return;
var	f,i,j,k,s,p = e, n = e.name || '!';
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
			i.value = decodeHTMLSpecialChars((e.firstElementChild || e).textContent);
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
	if (j = id('range')) j.innerHTML = room+k;
	if (j = id('thumbs')) j.innerHTML = param.current.thumbsHTML;
	if (j = id('pages')) {
		k = Math.ceil(param.total / param.on_page);
		for (i = 0; i<=k; i++) p = (
			i || (param.current.order?'&r':'&l')+'aquo;'
		), param.current.rangeHTML += '\n'+(
			param.current.page == i
			? p
			: '<a href="javascript:page('+i+')">'+p+'</a>'
		);
		j.innerHTML = (touch?'':la.page+': ')+param.current.rangeHTML;
	}
}

//* compile user content *-----------------------------------------------------

function showContent(sortOrder) {

	function getThreadHTML(threadText, addMarks, addToSort) {

		function getLineHTML(line) {
		var	lineHTML = ''
		,	postAttr = ''
		,	postMenu = 0
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
					+	getLineHTML(t+line.slice(2))
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
						+	'</table>'
						tableRow = [];
					}
				} else
		//* save variables, show nothing:
				if ((i = line.indexOf('=')) > 0) {
				var	k = line.substr(0,i).replace(regTrim, '')
				,	v = line.substr(i+1).replace(regTrim, '')
					;
					if (k.length && v.length) {
						if (v[0] == '"' && v.slice(-1) == '"') v = v.slice(1, -1);
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
			var	t,a,b,c,d,i,j,k
			,	u = ''
			,	userID = ''
			,	report = ''
			,	editPostData = ''
			,	marks = ''
			,	tab = line.split('\t')
			,	sep = param.separator
			,	roomDates = {}
			,	roomCount = (dtp.rooms && sep && tab.length > 2)
				;
				if (dtp.found) {
					if (!threadMark && param.room) threadMark = {id: param.room+'/', posts: 0};
					if (threadNum != param.t) {
						threadNum = param.t;
						alter = 1;
					}
				} else
				if (dtp.threads) {
					u = tab.shift();
					if (u.indexOf(sep = '#') >= 0) {
						j = u.split(sep);
						userID = j.pop();
						u = j.join(sep);
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
						j = userID ? {
							user: userID
						,	time: tab[0]
						} : {};
						;
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
					threadNum = (tab.length > 2?1:0);
					modEnabled = 1;
				} else
				if (roomCount && notEmpty(t = tab[2]) && t.indexOf(sep) >= 0) {
					k = t.split(sep);
			//* room name:
					tab[2] = k.shift();
			//* colored counters:
					a = {};
					for (i in k) if (c = reportClass[b = k[i].slice(-1)]) {
						a[b] = '<span class="'+c+'" title="'+la[c]+'">'+orz(k[i])+'</span>';
					}
					for (i in reportClass) if (a[i]) marks += a[i]+sep;
				}
			//* left:
				if (tab.length > 0 && notEmpty(t = tab[0])) {
					if (dtp.rooms && sep) {
						if (roomCount && t.indexOf(sep) >= 0) {
							k = t.split(sep).map(orz);
				//* last arch date:
							if (k.length > 3) {
								if (i = k[3]) {
									roomDates['a '+(insideOut?'r':'l')] = i = getFTimeIfTime(i);
									if (count.uLast < i) count.uLast = i;
								}
								if (!count.u.length) count.u = [0,0,0];
								for (i in (k = k.slice(0,3))) count.u[i] += k[i];
								if (i = k[2]) k[2] = '<a href="'+param.archives+tab[2]+'/">'+i+'</a>';
							}
							t = k.join(sep);
						} else if (tab[2] && notEmpty(t)) {
							t = '<a href="'+param.archives+tab[2]+'/">'+t+'</a>';
						}
					} else {
					var	time = t = getFTimeIfTime(t);
						if (dtp.found) t =
							'<a href="'+(param.room?param.room+'/':'')+param.t+param.page_ext
						+	'" title="'+la.search_hint.thread
						+	'">'
						+		t
						+	'</a>'
						+	(alter?' → '+threadNum:'');
					}
					if (flag.c && (dtp.reflinks || (tab.length > 2 && notEmpty(tab[2])))) {
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
				if (tab.length > 1 && notEmpty(t = tab[1])) {
					if (!regTagPre.test(e.tagName)) {
						t = encodeHTMLSpecialChars(t);	//* <- fix for textarea source and evil usernames
					}
					if (dtp.found) {
						t =	'<a href="?name='+encodeURIComponent(decodeHTMLSpecialChars(t))
						+	'" title="'+la.search_hint.name
						+	'">'
						+		t
						+	'</a>';
					} else
					if (dtp.reflinks) {
						try {d = decodeURIComponent(t);} catch (e) {d = t;}
						t = '<a href="'+t+'">'+d+'</a>';
					} else
				//* rooms:
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
					if (dtp.options && t[0] != '<' && t.indexOf('=') > 0) {
					var	k = t.split('=')
					,	sep = ','
						;
						if (k.length > 2 && k[1].length > 0) k = [k[0], k.slice(1).join('=')];
				//* text field:
						if (k.length > 2) {
							t = '<input type="text'
							+	'" name="'+optPrefix+k[0]
							+	'" value="'+k[2]
							+	'" onChange="allowApply()">';
						} else
				//* drop-down select:
						if (k[1].indexOf(sep) > 0) {
						var	l = k[1].split(';')
						,	m = orz(l.shift())
							;
							t = '<select name="'+optPrefix+k[0]
							+(
								l.length > 2
								? '" onChange="selectLink(this,\''
								+(l[2] || '*')
								+(
									l.length > 3
									? "','"+l[3]
									: ''
								)+"')"
								: ''
							)+'">';
							k = (l[0] || '').split(sep);
							l = (l[1] || '').split(sep);
							for (i in k) t +=
								'<option value="'+k[i]+'"'
							+		(m == i?' selected':'')
							+	'>'
							+		(l[i]?l:k)[i]
							+	'</option>';
							t += '</select>';
						} else {
				//* toggle box:
							t = '['+[0,1].map(
								function(v) {
									return '<label>'
									+		'<input type="radio'
									+			'" name="'+optPrefix+k[0]
									+			'" value="'+v
									+			'" onChange="allowApply()"'
									+			(k[1] == v?' checked':'')
									+		'>\n<b>'+la.toggle[v]+'</b>\n'
									+	'</label>';
								}
							).join(sep)+']';
						}
					}
					tab[1] = (marks && !notEmpty(t) ? marks.slice(0, -sep.length) : marks+t);
				}
			//* center:
				if (tab.length > 2 && notEmpty(t = tab[2])) {
					if (dtp.reports) {
						t = '<div class="log al">'
						+	t
							.replace(/(task:\s*)(\S+)(\s*<br>\s*pic:\s*1)/gi
							,	'$1<img src="'
							+	(param.images || '')
							+	'$2">$3'
							)
							.replace(/(<br>)(\s+)/gi, '$1<i></i>')
						+ '</div>';
					} else
					if (dtp.users) {
						t = userID+'. '+(u == 'u'?t:'<span class="a">'+t+'</span>');
					} else
					if (dtp.rooms) {
					var	k = (t[0] == '.'?'gloom':'')
					,	a = ''
						;
				//* room frozen:
						if (tab.length > 4) a = tab[4], k = 'frozen';
				//* room announce:
						if (tab.length > 3 && (notEmpty(a) || notEmpty(a = tab[3]))) {
							a = encodeHTMLSpecialChars(
								a
								.replace(regTrim, '')
								.replace(regSpace, ' ')
							);
							a =	'" title="'+a
							+	'" data-title="'+a.substr(a.indexOf(': '));
						}
						t = '<a href="'+t+'/'+(a||k?'" class="room-title'+(k?' '+k:'')+a:'')+'">'+t+'</a>';
					} else
				//* image post:
					if (tab.length > 3 && notEmpty(a = tab[3])) {
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
								,	j = m[4]	//* <- sum of active intervals
									;
									m[1] = (
										j && (j = getFormattedHMS(j)) != k
										? j+' ('+k+')'
										: k
									);
								}
							var	q = m[1]+', '+m[5]
							,	a = la.time+' '+m[1]+' '+la.using+' '+m[5]
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
									if (notEmpty(tab[0])) tab[0] += '<br>'+t
										.replace(regImgTag, k)
										.split(sep)
										.join('" title="'+la.resized_hint+sep);
								}
							} else {
								if (imgRes) {
									j = t.split(';');
									t = j[0].replace(/(\.[^.]+)$/, '_res$1');
									k = j[1].replace(regNaN, 'x');
									if (notEmpty(tab[0])) tab[0] +=
										'<span class="'+(u == 'u'?u:'res')
								//	+	'" title="'+k+'. '+la.resized_hint
									+	'">'
									+		('\n'+la.resized).replace(regLineBreak, '<br>')
									+		': '+k
									+	'</span>';
								} else j = '';
								t = '<img src="'
								+	(param.images || '')
								+	(flag.p?getPicSubDir(t):'')
								+	t
								+	'" alt="'+t+', '+q
								+	'" title="'+(j?a+', '+k+'. '+la.resized_hint:a)
								+	'">';
								if (j) t =
									'<a target="_blank" href="'+(param.images || '')
								+	(flag.p?getPicSubDir(t):'')
								+	j[0]
								+	'" class="res'+(u == 'u'?' u':'')+'">'
								+		t
								+	'</a>';
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
					if (!imgRes && u == 'u') t = '<span class="u">'+t+'</span>';
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

				if (roomDates) {
				var	k = (insideOut?' class="'+insideOut+'"':'');
					for (i in roomDates) lineHTML +=
						'<div'+k+'>'
					+		'<p class="'+i+'">'
					+			roomDates[i]
					+		'</p>'
					+	'</div>';
				}
				i = 2;
				while (i--) if (notEmpty(t = tab[i])) {
					if (dtp.threads) {
						j = '';
						if (b = param[k = reportOnPostTypes[i]]) {
							b = b.split('<br>');
							for (var rep_line_i in b) if (notEmpty(c = b[rep_line_i])) {
								d = c.indexOf(':');
								j +=	'<span class="report">'
								+		getFormattedTime(c.slice(0, d))
								+		c.slice(d)
								+	'</span>';
							}
							param[k] = '';
						}
						if (j) t +=
							'<span class="date-out '+'rl'[i]+'">'
						+		j
						+	'</span>';
					}
				var	m = (i > 0 ? ' class="'+(dtp.reflinks?'ref':'r')+'"' : '');
					if (modEnabled) {
					var	postID = threadNum+'-'+postNum+'-'+i;
						if (mm && (i > 0 || !dtp.users)) {
							postMenu = 1;
							m += ' id="m_'+(
								dtp.users
								? userID+'_'+threadNum+'_3'
								: postID.replace(/\D+/g, '_')
							)+'"';
							if (i == 0 && editPostData) m += ' data-post="'+encodeHTMLSpecialChars(
								editPostData
							)+'"';
						} else {
							m += ' onClick="window.open(\''+(
								dtp.users
								? '3-'+userID+'\',\'Info\',\'width=400,height=400'
								: postID+'\',\'Report\',\'width=656,height=300'
							)+'\')"';
						}
					}
					if (b = param[hintOnPostTypes[i]]) {
						m += ' title="'+b+'"';
					}
					tab[i] = '<p'+m+'>'
					+		t
					+	'</p>';
				}
				lineHTML += tab.slice(0,3).filter(notEmpty).join('');
			}
			if (notEmpty(lineHTML)) {
				if (addToSort && (!dtp.users || threadNum > 0)) linesToSort.push(line);
		//* half width:
				if (dtp.options || dtp.rooms) lineHTML =
						'<div class="center">'
				+			lineHTML
				+		'</div>';
		//* toggle bg color:
				if (!dtp.found && !imgPost) alter = 1;
				if (alter) alt = !alt;
				lineHTML =
					'<div class="post'
				+		(dtp.found || dtp.threads?' p':'')
				+		(dtp.users?' al p':'')
				+		(postMenu?' menu':'')
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
	,	lines = threadText.split('\n')
	,	line
		;
		for (var l_i in lines) if (notEmpty(line = lines[l_i])) threadHTML += getLineHTML(line);

		if (notEmpty(threadHTML)) {
			if (addMarks) threadsMarks.push(threadMark);
			if (dtp.found && (m = param.room)) {
				threadHTML =
					'<div class="post alt x3">'
				+		'<b class="anno dust">'
				+			la.room_arch+': '
				+			'<a href="'+m+'/">'+m+'</a>'
				+		'</b>'
				+	'</div>'
				+threadHTML;
			}
			return threadHTML;
		}
		return '';
	}

var	flagVarNames = ['flag', 'flags']
,	dontCollapse = ['full', 'rooms']
,	hintOnPostTypes = ['left', 'right']
,	reportOnPostTypes = ['reports_on_post', 'reports_on_user']
,	optPrefix = 'opt_'
,	raws = gn('textarea').concat(gn('pre'))
,	rawr = []
	;
	for (var r_i in raws) if ((e = raws[r_i]) && (t = e.getAttribute('data-type'))) {

		if ((p = e.previousElementSibling) && (h = p.threadsHTML)) {
		var	i = p.threadsLastSortIndex || 0;
			if (sortOrder === 'last') {
				if (!p.innerHTML && (p.innerHTML = h[i]) && mm) mm();
				rawr.push(p);
			} else {
			var	n = 0;
				if (typeof sortOrder !== 'undefined') {
					if (sortOrder > 0) n = 1; else
					if (sortOrder < 0) n = 2; else h = '';
				}
				if (h) {
					h = (i == n && p.innerHTML?'':h[n]);
					p.threadsLastSortIndex = n;
				}
				if ((p.innerHTML = h) && mm) mm();
			}
			continue;
		}

	var	raw = e.value || e.innerHTML
	,	dtp = o0(t, regSpace, 1)	//* <- split into object properties
	,	selfID = ''
	,	linesToSort = []
	,	threadsHTML = []
	,	threadsMarks = []
	,	threads = []
	,	thread
		;
		if (dtp.users) {
		var	lines = raw.split('\n')
		,	line
		,	lastDay = ''
		,	threadsByDay = []
			;
			for (var l_i in lines) {
				if ((i = (line = lines[l_i]).indexOf('\t')) >= 0) {
				var	t = getFormattedTime(line.slice(0, i), 1, 1);
					if (lastDay != t) {
						lastDay = t;
						threadsByDay.push('\n');
					}
				}
				threadsByDay.push(line);
			}
			raw = threadsByDay.join('\n');
		}
		if (dtp.reflinks) {
		var	lines = raw.split('\n')
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
		for (var t_i in threads) if (notEmpty(thread = threads[t_i])) {
		var	t = getThreadHTML(thread, 1, dtp.users);
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
			if (threadsHTML.length > 1) {
		//* top bar:
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
				;
				for (i in la.groups) if (dtp[i]) {a = la.groups[i]; break;}
				o.left.push(
					'<a href="javascript:showContent()">'
				+		a+': '+threadsHTML.length
				+	'</a>'
				);
				if (flag.c) for (i in count) if (k = count[i]) {
					k = (
						dtp.reflinks || (dtp.users && i[0] != 'u')
						? '<a href="javascript:showContent('+(l[i]?1:-1)+')">'+(
							l[i]
							? l.total
							: (l.lastr || l.last)
						)+'</a>'
						: (
							l[i] ? (
								dtp.users && i == 'u' ? l.self : (
								dtp.found && i == 'o' ? l.total+' '+l.posts : l[i]
							)) : l.last
						)
					)+': '+k;
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
				b = cre('div', p, e);
				b.className = 'thread task';
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
							if (dontCollapse.indexOf(j) < 0) v =
								'<div class="post alt anno">'
							+		'<a href="javascript:void this'
							+		'" onClick="toggleHide(this.parentNode.nextElementSibling)'
							+		'">'
							+			la.hint[j]+la.hint.show
							+		'</a>'
							+	'</div>'
							+	'<div style="display:none">'
							+		v
							+	'</div>';
						}
						return	'<div class="thread'+(k || '')+'">'
						+		v
						+	'</div>';
					}
				).join('')];
		//* sorted content:
				if (linesToSort.length) for (i in (j = ['sort','reverse'])) {
					h.push(
						'<div class="thread">'
					+		getThreadHTML(linesToSort[j[i]]().join('\n'))
					+	'</div>'
					);
				}
				for (i = 0, j = h.length; i < j; i++) if (h[i]) h[i] += afterThreadsBar;
				e.className = 'multi-thread';
				e.threadsHTML = h;
		//* show open threads on page load only if option set:
				if (flag.a) e.innerHTML = h[0];
			} else {
				if (dtp.found || dtp.threads) cre('div', p, e.nextElementSibling).outerHTML = afterThreadsBar;
				e.className = 'thread';
				e.threadsHTML = e.innerHTML = threadsHTML.join('');
			}
			for (i in (a = gn('select', e))) if (a[i].onchange) a[i].onchange();
		} else del(e);

		if (dtp.pages && param && (e = id('task'))) {
		var	t = document.title
		,	h = (e.firstElementChild || e).textContent.replace(regTrimWord, '')
		,	i = t.lastIndexOf(h)+h.length
			;
			param.on_page = (touch && !param.start ? 20 : orz(param.on_page));
			param.start = orz(param.start);
			param.total = orz(param.total);
			param.title = [
				t.slice(0, i)
			,	t.slice(i)
			];
			if (a = gn('p', e)[0]) a.innerHTML += '\n<span id="range"></span>';
			e.innerHTML += (
				!param.start && param.total > param.on_page
				? '<p id="pages"></p>'
				: '<p>'+la.page+': 1</p>'
			)+'<div id="thumbs"></div>';
			page(1);
		}

		if ((e = id('total-counts')) && count.o.length) {
		var	a = '<p class="a '
		,	b = '<div'+(insideOut?' class="'+insideOut+'"':'')+'>'+a
		,	c = '</p>'
		,	d = c+'</div>'
		,	j = param.separator || ','
			;
			e.outerHTML =
				b+'l">'+count.oLast+d
			+	b+'r">'+count.uLast+d+'<br>'
			+	a+'l">'+count.u.join(j)+c
			+	a+'r">'+count.o.join(j)+c+e.outerHTML;
		}

		for (i in (a = gn('noscript', p))) del(a[i]);

		if (p.lastElementChild) {
			if (touch) toggleClass(p, 'wider', 1);
		} else del(p);
	}
	if (rawr.length) return rawr;
}

//* Runtime: top panel, etc *--------------------------------------------------

for (i in (a = gn('time'))) if ((e = a[i]) && (t = e.getAttribute('data-t')) && t > 0) e.outerHTML = getFormattedTime(t);
if (e = id('filter')) e.onchange = e.onkeyup = filter;

if (k = id('task')) {
//* room task:
	if (taskTop = gn('p',k)[0]) {
		if (j = k.getAttribute('data-skip')) {
			taskTop.innerHTML += '<a class="r'
			+	'" href="javascript:skipMyTask('+j+')'
			+	'" title="'+la.skip_hint
			+	'">「X」</a>'
		}
		if (j = k.getAttribute('data-unskip')) {
			j = j.split(/\D+/, 1)[0];
			taskTop.innerHTML += '<a class="r'
			+	'" href="javascript:void '+j
			+	'" data-room="'+room
			+	'" id="unskip'
			+	'" title="'+la.unskip_hint
			+	'">「'+la.unskip+'」</a>';
		}
	}
	if ((j = k.getAttribute('data-t')) !== null) {
		if (
			(j = orz(j))
		&&	(i = gi('submit',k)[0])
		&&	(f = getParentByTagName(i, 'form'))
		) {
			f.setAttribute('onsubmit', 'checkMyTask(event, this)');
		}
		if (taskTop) {
			taskTop.innerHTML += '<a class="r" href="'+(
				j
				? 'javascript:checkMyTask()" title="'+new Date(j*1000)+'\n\n'+la.check+'">「<span id="'+CS+'">?</span>'
				: drawQuery+'">「'+la.draw
			)+'」</a>';
		}
		if (
			(i = gn('img',k)[0])
		&&	(j = i.alt.indexOf(';')+1)
		) {
			i.alt = i.alt
				.replace(';',', ')
				.replace('*','x');
			setPicResize(i,j+1);
		}
	}
//* archive search:
	for (i in (j = gi('text',k))) if (
		(e = j[i])
	&&	(n = (e.getAttribute('data-select') || e.name).replace(regTrim, ''))
	&&	n.indexOf('\n') >= 0
	) {
	var	max = 0
	,	o = {}
		;
		for (n in (lines = n.split('\n'))) if (line = lines[n].replace(regTrim, '')) {
		var	a = line.split('\t')
		,	inputName =	a.shift().replace(regTrim, '')
		,	optionText =	a.shift().replace(regTrim, '')
		,	inputHint =	a.join(' ').replace(regTrim, '')
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
					l = (a.length > 1);
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
	if (i = (j = gn('ul',k)).length) {
		n = (m = gn('b')).length, h = 1;
		while (n--) if (regClassAnno.test(m[n].className)) {h = 0; break;}
		while (i--) if (m = j[i].previousElementSibling) {
			m.innerHTML =
				'<a href="javascript:void this'
			+	'" onClick="toggleHide(this.parentNode.nextElementSibling)'
			+	'">'
			+		m.innerHTML
			+	'</a>';
			if (h) toggleHide(j[i]), allowApply(-1);
		}
	}
}

showContent();

if (e = id('time-zone')) e.innerHTML = getFormattedTimezoneOffset();
if (mm) mm(1);
for (i in la.clear) if (e = id(i)) {
	e.onclick = clearSaves;
	if (!e.href) e.disabled = true, (e.onmouseover = checkSaves)(i);
}
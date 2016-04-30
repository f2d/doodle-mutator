var	LS = window.localStorage || localStorage
,	h = gn('header')[0], i,j,k,l = location.href
,	rootPath = (h?gn('a',h)[0].href.replace(/^\w+:\/+[^\/]+\/+/, '/'):'/')
,	AN = /\banno\b/i, PT = /\bpost\b/i, DP = /^(div|p)$/i, FM = /^form$/i
,	TU = /^\d+(<|>|$)/
,	WS = /^\s+|\s+$/g
,	NL = /^(\r\n|\r|\n)/g
,	count = {u:0, uLast:'', o:0, oLast:'', img:0}
,	u_bar = {0:'born', b:'burn', g:'goo', m:'ice', n:'null', u:'me'}
,	m,n,mm,mt = {frozen:[], burnt:[], full:[]}
,	AF = mt.full.filter
,	filter, checking, CS = 'checkStatus', CM = 'checkMistype', data = {}, flag = {}
,	inout = (('ontouchstart' in document.documentElement)?'':'date-out')
,	la, lang = document.documentElement.lang || 'en';

if (lang == 'ru') la = {
	arch: 'архив'
,	draw: 'Рисовать'
,	checked: 'Подтверждение'
,	check: 'Нажмите, чтобы проверить и продлить задание.'
,	task_mistype: 'Тип задания сменился, обновите страницу или нажмите сюда.'
,	task_changed: 'Задание было изменено другими действиями за прошедшее время.'
,	send_new_thread: 'Будет создана новая нить.'
,	send_anyway: 'Всё равно отправить?'
,	canceled: 'Отправка отменена'
,	close: 'Закрыть'
,	top: 'Наверх'
,	skip: 'Пропустить'
,	skip_hint: 'Пропускать задания из этой нити до её завершения.'
,	clear: {
		unsave: {ask: 'Удалить данные из памяти браузера:', unit: 'байт'}
	,	unskip: {ask: 'Пропуски будут очищены для этих комнат:', unit: 'нитей'}
	}
,	load: 'Проверка... '
,	fail: 'Ошибка '
,	hax: '(?)'		//'Неизвестный набор данных.'
,	time: 'Нарисовано за'
,	using: 'с помощью'
,	resized: 'Размер'	//'\nИзображение уменьшено.\nРазмер'
,	groups: 'Групп'
,	report: 'Жалоб'
,	active: 'Активных нитей'
,	frozen: 'Замороженные нити'
,	burnt: 'Выжженные нити'
,	full: 'Полные нити'
,	hint: {	show: 'Кликните, чтобы показать/спрятать.'
	,	frozen: 'Замороженная нить.\n'
	,	burnt: 'Выжженная нить.\n'
	,	full: 'Полная нить.\n'
},	count: {img: 'Рисунков'
	,	u: 'Своих постов'
	,	o: 'Прочих'
	,	last: 'последний'
	,	lastr: 'последняя'
	,	self: 'Себя'
	,	each: 'Всех'
	,	total: 'Всего'
}}; else la = {
	arch: 'archive'
,	draw: 'Draw'
,	checked: 'Confirm'
,	check: 'Click this to verify and prolong your task.'
,	task_mistype: 'Task type changed, please reload the page or click here.'
,	task_changed: 'Task was changed by some actions in the meantime.'
,	send_new_thread: 'Sending will make a new thread.'
,	send_anyway: 'Send anyway?'
,	canceled: 'Sending canceled'
,	close: 'Close'
,	top: 'Top'
,	skip: 'Skip'
,	skip_hint: 'Skip any task from this thread from now on.'
,	clear: {
		unsave: {ask: 'Data to be deleted from browser memory (Local Storage):', unit: 'bytes'}
	,	unskip: {ask: 'Skipping will be cleared for following rooms:', unit: 'threads'}
	}
,	load: 'Checking... '
,	fail: 'Error '
,	hax: '(?)'		//'Unknown data set.'
,	time: 'Drawn in'
,	using: 'using'
,	resized: 'Full size'	//'\nShown image is resized.\nFull size'
,	groups: 'Groups'
,	report: 'Reports'
,	active: 'Active threads'
,	frozen: 'Frozen threads'
,	burnt: 'Burnt threads'
,	full: 'Full threads'
,	hint: {	show: 'Click here to show/hide.'
	,	frozen: 'Frozen thread.\n'
	,	burnt: 'Burnt thread.\n'
	,	full: 'Full thread.\n'
},	count: {img: 'Pictures'
	,	u: 'Own posts'
	,	o: 'Others'
	,	last: 'last'
	,	self: 'Self'
	,	each: 'All at once'
	,	total: 'Total'
}};

function decodeHTMLSpecialChars(t) {
	return String(t)
	.replace(/&lt;/gi, '<')
	.replace(/&gt;/gi, '>')
	.replace(/&quot;/gi, '"')
	.replace(/&#0*39;/g, "'")
	.replace(/&amp;/gi, '&');
}

function propNameForIE(n) {return n.split('-').map(function(v,i) {return i > 0 ? v.slice(0,1).toUpperCase()+v.slice(1).toLowerCase() : v;}).join('');}
function getStyleValue(obj, prop) {
var	o;
	if (o = obj.currentStyle) return o[propNameForIE(prop)];
	if (o = window.getComputedStyle) return o(obj).getPropertyValue(prop);
	return null;
}

function showProps(o,z /*incl.zero*/) {var i,t=''; for(i in o)if(z||o[i])t+='\n'+i+'='+o[i]; alert(t); return o;}
function gn(n,p) {return (p||document).getElementsByTagName(n);}
function gi(t,p) {return (p = gn('input',p)) && t ? AF.call(p, function(e) {return e.type == t;}) : p;}
function id(i) {return document.getElementById(i);}
function cre(e,p,b) {
	e = document.createElement(e);
	if (b) p.insertBefore(e, b); else
	if (p) p.appendChild(e);
	return e;
}

function deleteCookie(c) {document.cookie = c+'=; expires=Thu, 01 Jan 1970 00:00:01 GMT; Path='+rootPath;}
function toggleHide(e,d) {e.style.display = (e.style.display != (d?d:d='')?d:'none');}
function getPicSubDir(p) {var s = p.split('.'); return s[1][0]+'/'+s[0][0]+'/';}
function unixTimeToStamp(t,u) {
	if (['object','string'].indexOf(typeof t) > -1) t = parseInt(t)*1000;
var	d = (t ? new Date(t+(t > 0 ? 0 : new Date())) : new Date());
	t = ['FullYear','Month','Date','Hours','Minutes','Seconds'];
	u = 'get'+(u?'UTC':'');
	for (i in t) if ((t[i] = d[u+t[i]]()+(i==1?1:0)) < 10) t[i] = '0'+t[i];
	return t.slice(0,3).join('-')+' '+t.slice(3).join(':');
}

function getSaves(v,e) {
var	j = [], k = [], keep = (e?e.getAttribute('data-keep'):0) || '', l = keep.length;
	if (v == 'unsave') {
	var	m = 'string';
		for (i in LS) if (
			(typeof (a = LS[i]) === m)
		&&	(!keep || keep !== i.substr(0,l))
		) {
			k.push(i);
			j.push(i+': '+a.length+' '+la.clear[v].unit);
		}
	} else
	if (v == 'unskip') {
	var	a = document.cookie.split(/;\s*/), i = a.length, r = /^([0-9a-z]+-skip-[0-9a-f]+)=([^\/]+)\/(.*)$/i;
		while (i--) if (
			(m = a[i].match(r))
		&&	(!keep || keep !== m[1].substr(0,l))
		) {
			k.push(m[1]);
			j.push(decodeURIComponent(m[2])+': '+m[3].split('/').length+' '+la.clear[v].unit);
		}
	}
	return {rows: j, keys: k};
}

function checkSaves(e) {
	if (e.target) var v = (e = e.target).id; else e = id(v = e);
	if (e) e.disabled = !(getSaves(v,e).keys.length);
}

function clearSaves(e) {
	if (e.preventDefault) e.preventDefault();
	if (e = e.target) v = e.id;
var	v,a = getSaves(v,e), k = a.keys;
	if (!v) alert(la.fail+': '+v); else
	if (k.length && confirm(la.clear[v].ask+'\n\n'+a.rows.join('\n'))) {
		for (i in k) {
			if (v == 'unskip') deleteCookie(k[i]); else
			if (v == 'unsave') LS.removeItem(k[i]);
		}
		if (e) e.disabled = true;
	}
	return false;
}

function checkMyTask(event, e) {
	if (checking) return;
	checking = 1;
var	d = 'data-id', f = id(CM), s = id(CS), r = new XMLHttpRequest();
	if (f) f.parentNode.removeChild(f);
	if (f = e) while (f && !FM.test(f.tagName)) f = f.parentNode; else
	if (f = s.getAttribute(d)) {
		f = id(f), s.removeAttribute(d);
		if (!FM.test(f.tagName)) f = ((f = gn('form', f)) && f.length ? f[0] : 0);
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
					.replace(/\s+/, ' ')
					.replace(WS, '')
			,	error = j.match(/\bid=["']*([^"'>\s]*)/i)
			,	message = (error?status:'')
			,	img = i.match(/<img[^>]+\balt=["']*([^"'>\s]+)/i)
			,	task = (img?img[1]:decodeHTMLSpecialChars(i));
				if (k = id('task')) {
					i = (e = gn('img', k)).length;
					if (!i == !!img) {
						e = s, error = 1;
						while (!DP.test(e.tagName) && (i = e.parentNode)) e = i;
						e = cre('b', e);
						e.id = CM;
						e.className = 'post r';
						e.innerHTML =
							'<b class="date-out l">'
						+		'<b class="report">'
						+			la.task_mistype.replace(/\s(\S+)$/, ' <a href="?">$1</a>')
						+		'</b>'
						+	'</b>';
					} else
					if (!img) {
						if (
							(e = gn('p', k)).length > 1
						&&	!FM.test((e = e[1]).previousElementSibling.tagName)
						&&	e.textContent != task
						) e.textContent = task, error = 1;
					} else
					if (i) {
						e = e[0];
						k = task.indexOf(';')+1;
						j =	(flag.pixr || (flag.pixr = e.src.split('/').slice(0, flag.p?-3:-1).join('/')+'/'))
						+	(flag.p?getPicSubDir(task):'')
						+	(k?task.replace(/(\.[^.\/;]+);.+$/,'_res$1'):task);
						if (e.getAttribute('src') != j) e.src = j, e.alt = task, setPicResize(e, k), error = 1;
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

function setPicResize(e,i) {
var	a = e.parentNode, nested = /^a$/i.test(a.tagName);
	if (i) {
		if (!nested) {
			a = cre('a',a,e);
			a.appendChild(e);
			a.className = 'res';
			a.href = 'javascript:;';
			a.setAttribute('onclick', 'togglePicSize(this.firstElementChild)');
		}
		a.title = e.alt.slice(i);
	} else if (nested) {
		r = a.parentNode;
		r.appendChild(e);
		r.removeChild(a);
	}
	e.setAttribute('onload', 'setPicStyle(this)');
}

function setPicStyle(e) {
var	i = e.offsetWidth+16, b = document.body.style, a = e.parentNode, e = (a.href?a.parentNode:a).style
,	a = {minWidth: Math.max(656,i), maxWidth: Math.max(1000,i)};
	for (i in a) e[i] = b[i] = a[i]+'px';
}

function togglePicSize(e) {
var	r = '_res';
	e.src = (e.src.indexOf(r) > 0
		? e.src.replace(r, '')
		: e.src.replace(/(\.[^.\/]+$)/, r+'$1')
	);
}

function filterList(event) {
var	e = event.target, v = (e.value||'').replace(WS, '').toLowerCase(), k = 'lastFilterValue';
	if (!filter || (e[k] && e[k] == v)) return;
	if (c = id('tower')) {
		if (v.length && !c.innerHTML.length) showContent();
	} else c = id('filter');
	if (!c) return;
	e[k] = v;
var	c,d = gn('div',c), i,j,l = d.length, o = [], p,alt;
	for (i = 0; i < l; i++) if (PT.test((e = d[i]).className)) {
		if (o.indexOf(p = e.parentNode) < 0) o.push(p);
		if (e == p.firstElementChild) alt = 1;
		if (!(k = gn('p',e)).length) k = e.textContent;
		else if (filter == 1 && k.length > 1) k = k[1].textContent;
		else {
			j = k.length-1, j = k[j > 1?1:j], k = '';
			while (j = j.nextSibling) if (!DP.test(j.tagName)) k += j.textContent;
		}
		if (j = (!v || !(k = k.replace(WS, '').toLowerCase()) || k.indexOf(v) >= 0)) {
			alt = (alt?'':' alt');
			e.className = e.className.replace(/\s(alt|ok)\b/i, '')+(k == v?' ok':alt);
		}
		e.style.display = (j?'':'none');
	}
	for (i in o) {
		d = 'none', e = (p = o[i]).firstElementChild;
		do {if (e.style.display != d) {d = ''; break;}} while (e = e.nextElementSibling);
		p.style.display = d;
	}
}

function allowApply(n) {
	apply.disabled = (n < 0);
}

function selectLink(e,r,t) {
var	i = e.name+'_link', a = id(i), r = r.replace('*', r.indexOf('.') < 0 ? e.value : e.value.replace(/\.[^.\/]+$/, ''));
	if (a) a.href = r;
	else {
		e = e.parentNode.nextSibling;
		e = cre('div', e.parentNode, e);
		e.className = inout;
		e.innerHTML = '<p class="l"><a href="'+r+'" id="'+i+'">'+t+'</a></p>';
	}
}

function showOpen(i) {
var	t = id(i) || (showContent(), id(i)), d = t.firstElementChild;
	if (/^a$/i.test(d.firstElementChild.tagName)) d.nextElementSibling.style.display = '';
	t.scrollIntoView(false);
}

function showContent(pre) {
	s = data.sort;
	if (pre && pre.innerHTML) data.ph = pre.innerHTML.replace(WS, ''); else data.sort = pre;
	if (h = id(o = 'tower')) {
		i = h.innerHTML.length, h.innerHTML = '';
		if (i && pre == s) return;
	}

var	i,j,k,l,m,n = '\n', o,p = data.ph, opt = 'opt_', q,s = ' ', t = '	'
,	a = p.split(n+n), b = n+t, c = b+t, d = c+t, e = d+t
,	f = p.split(n,1)[0], g = ':|*', h,hell, recl = ['report'];

	for (i in mt) recl.push(i);

	if (f == 'rep') flag[f] = 1, f = p.split(n,2)[1]; else	//* <- image src root, temporary crutch inb4 overhaul
	if (f == 'ref') {
		flag[f] = flag.c = 1;
		if (isNaN(pre)) {
			a = p.split(n), j = {}, k = [];
			for (i in a) if (a[i].indexOf(t) > 0 && (m = a[i].match(/\s([^:\/]*[:\/]+)?([^\/]+)/))) {
				m = m[2].replace(/\W+$/, '').split('.').slice(-2).join('.');
				if (k.indexOf(m) < 0) k.push(m), j[m] = [];
				j[m].push(a[i]);
			}
			a = [];
			k.sort();
			for (i in k) a.push(j[k[i]].join(n));
		} else if (pre < 0) a = p.split(n), a.reverse(), a = [a.join(n)];
	} else
	for (i in g) if (f.indexOf(g[i]) > -1) {
		l = (f = f.split(g = g[i])).length;
		if (g == '*') count.u = [0,0,0], count.o = [0,0];
		if (g == ':') flag.pixr = f[2];
		if (l > 3) for (i in f[3]) flag[f[3][i]] = 1;
		if (flag.u) {
			l = a.pop().split(n), j = [];
			while (l.length) {
				j.push(l.shift());
				if (!l.length || j.length > 9) a.push(a.length+','+j.join(n).replace(/^\d+,/, '')), j = [];
			}
		}
		break;
	}
	flag.hell = {burnt: !!mm, frozen: !mm/*, full: !flag.a*/};

	function getThread(txt, preCount) {
	var	line = txt.split(n), output = '', placeholder = '<!--?-->'
	,	img = (flag.u?0:1), alt = 1, tr = []
	,	desc_num = (g == ':'?1:0), post_num = 0, thread_num = 0, i,j,k,l,m,mark;
		hell = 0;
		for (i in line) if (line[i].indexOf(t) > 0) {
		var	tab = line[i].split(t), u = (tab.length > 3?tab.shift():''), post = '<br>', res = 0;
			if (u.indexOf(',') > -1) {
				u = u.split(','), thread_num = u.shift(), u = u[0], j = thread_num[0];
				if (isNaN(j)) {
					hell = {
						class: (j = recl[k = 'asdf'.indexOf(j)])
					,	id: (thread_num = thread_num.slice(1))
					};
					mt[j].push(mark = {i:thread_num});
				}
			}
			if (preCount) {
				++count[u == 'u'?u:u = 'o'];
				if (tab.length > 3) ++count.img;
				if (TU.test(post = tab[0])) post = unixTimeToStamp(post);
				if (count[u += 'Last'] < post) count[u] = post;
				if (mark && (!mark.t || mark.t < post)) mark.t = post;
			} else {
				++post_num;
				if (tab.length > 3) {
					if (tab[3][0] == '?') {
						post =
e+'<span title="'+tab[2]+'">'+tab[3].slice(1)+'</span>';
					} else {
						if (m = tab[3].match(/^((\d+)-(\d+)|[\d:]+),(.*)$/m)) {
							if (m[2]) {
								k = [0, 0, Math.floor((+m[3]-m[2])/1000)], l = 3;
								while (--l) {
									if (k[l] > 59) k[l-1] = Math.floor(k[l]/60), k[l] %= 60;
									if (k[l] < 10) k[l] = '0'+k[l];
								}
								m[1] = k.join(':');
							}
							q = m[1]+', '+m[4], m = la.time+s+m[1]+s+la.using+s+m[4];
						} else q = m = la.hax+s+tab[3];
						if (tab[2].indexOf(j = ';') > 0) {
							j = tab[2].split(j);
							tab[2] = j[0].replace(/(\.[^.]+)$/, '_res$1'), k = j[res = 1].replace('*', 'x');
							tab[0] +=
'<span class="a" title="'+k+'">'+(n+la.resized).replace(NL, '<br />')+': '+k+'</span>';
						} else j = '';
						post =
e+'<img src="'+f[2]+(flag.p?getPicSubDir(tab[2]):'')+tab[2]+'" alt="'+tab[2]+', '+q+'" title="'+m+'">';
						if (j) post =
e+'<a target="_blank" href="'+f[2]+(flag.p?getPicSubDir(tab[2]):'')+j[0]+'">'+post+
e+'</a>';
					}
					if (img) alt = (alt?'':' alt');
					img = 1;
				} else {
					if (g == '|' && ((k = tab[1])[0] != '<') && (k.indexOf('=') > 0)) {
						k = k.split('=');
						if (k.length > 2) {
							tab[1] = '<input type="text" name="'+opt+k[0]+'" value="'+k[2]+'" onChange="allowApply()">';
						} else
						if (k[1].indexOf(g) > 0) {
							l = k[1].split(':'), m = parseInt(l.shift());
						var	opts = opt+k[0]+(l.length > 3?'" onChange="allowApply(); selectLink(this,\''+l[2]+'\',\''+l[3]+'\')':'')+'">';
							k = l[0].split(g);
							l = l[1].split(g);
							for (j in k) opts +=
e+'<option value="'+k[j]+'"'+(m == j?' selected':'')+'>'+(l[j]?l:k)[j]+'</option>';
							tab[1] =
e+'<select name="'+opts+
e+'</select>';
						} else {
							tab[1] = '['+[0,1].map(function(v) {
								return '<label>'
								+	'<input type="radio" name="'+opt+k[0]+'" value="'+v+'" onChange="allowApply()"'
								+	(k[1] == v?' checked':'')
								+	'>\n<b>'+f[v]+'</b>\n'
								+ '</label>';
							}).join('|')+']';
						}
					} else
					if (tab.length > 2) {
					var	da = {}, dd = '', an = '/';						//* room list:
						if (g == '*') {
							m = (tab[2][0] == '.'?'gloom':'');				//* <- room hidden
							if (l = tab[2].indexOf('/')+1) {
								k = tab[2].slice(l).replace(/"/g, '&quot;');
								if (k[0] == '/') k = k.slice(1), m = 'frozen-hell';	//* <- room frozen/announce
								m += '" title="'+k+'" data-title="'+k.substr(k.indexOf(': '));
								tab[2] = tab[2].slice(0,l-1);
							}
							if (m) an += '" class="room-title'+(m[0] != '"'?' '+m:m);
							if ((m = tab[1].split(f[1])).length > 2) {
								da[inout?'l':'r'] = m[2];				//* <- last post date
								if (count.oLast < m[2]) count.oLast = m[2];
								for (k in (m = m.slice(0,2))) count.o[k] += parseInt(m[k]);
								tab[1] = m.join(f[1]);
							}
							if ((m = tab[2].split(f[1])).length > 1) {
								tab[2] = m.shift();					//* <- room name
								l = '';
								for (k in m) if ((m[k] = m[k].replace(WS, '')).length && m[k] != 0) {
									l += '<span class="'+recl[k]+'" title="'+la[recl[k]]+'">'+m[k]+'</span>'+f[1];
								}
								tab[1] = l+tab[1];					//* <- colored counters
							}
							if ((m = tab[0].split(f[1])).length > 1) {
								for (k in m.slice(0,3)) count.u[k] += parseInt(m[k]);
								da[k = 'a '+(inout?'r':'l')] = '';			//* <- arch date?
							}
							if (m.length > 2) {
								if (m[3]) {
									da[k] = m[3];
									if (count.uLast < m[3]) count.uLast = m[3];	//* <- arch date
								}
								m[2] = '<a href="'+f[0]+tab[2]+'/">'+m[2]+'</a>';	//* <- arch count
								tab[0] = m.slice(0,3).join(f[1]);
							} else if (g == m[0]) {
								tab[0] = '<a href="'+f[0]+tab[2]+'/">'+la.arch+'</a>';	//* <- link with no count
							}
							if (/^[0,\s]*$/.test(tab[0]+tab[1])) {
								tab[0] = tab[1] = '';
								if (f[2]) tab[2] = '<span class="a">'+tab[2]+'</span>';
							} else
							if (f[2] && tab[2].replace(WS, '').length) tab[2] = '<a href="'+f[2]+tab[2]+an+'">'+tab[2]+'</a>';
						}
						for (k in da) if (da[k]) dd +=
e+'<div'+(inout?' class="'+inout+'"':'')+'><p class="'+k+'">'+da[k]+'</p></div>';
						post =
dd+e+(desc_num && img?desc_num++ +'. ':'')+tab[2];
					} else if (g == '*') post = placeholder;
					else if (flag.ref) {
						try {m = decodeURIComponent(k = tab[1]);} catch (e) {m = k;}
						tab[1] = '<a href="'+k+'">'+m+'</a>';
					}
					alt = (alt?'':' alt');
					img = 0;
				}
				if (u == 'u') post = '<span class="u">'+post+'</span>';
				else if (flag.u) post = post.replace(' ', ' <span class="a">')+'</span>';
				else if (flag.rep) post =
					'<div class="log al">'
				+		post
						.replace(/(task:\s*)(\S+)(\s*<br>\s*pic:\s*1)/gi, '$1<img src="'+f+'$2">$3')
						.replace(/(<br>)(\s+)/gi, '$1<i></i>')
				+	'</div>';
				q = (flag.u?tab[2].slice(0, tab[2].indexOf('.')).replace(WS, ''):0);
				m = (thread_num?thread_num+'-'+post_num+'-':'');
				k = 2;
				while (k--) if (tab[k].replace(WS, '').length || (flag.u && (tab[k] += la.count[k > 0?'self':'each']))) {
				var	r = '';
					if (tab[k].indexOf(l = '<br>') > 0) {
						l = tab[k].split(l);
						tab[k] = l.shift();
						for (j in l) r +=
e+'	<span class="'+recl[0]+'">'+unixTimeToStamp(l[j].slice(0, i = l[j].indexOf(':')))+l[j].slice(i)+'</span>';
					}
					if (!k && TU.test(tab[k])) j = tab[k].split('<'), j[0] = unixTimeToStamp(j[0]), tab[k] = j.join('<');
					if (r) tab[k] +=
e+'<span class="date-out '+'rl'[k]+'">'+r+
e+'</span>';
					l = '', post =
e+'<p'+(desc_num && f[k]?l+' title="'+f[k]+(m?(mm&&(k||!q)
?'" id="m_'+(q?q+'_'+thread_num+'_3':(m+k).replace(/-/g, '_'))
:'" onClick="window.open(\''+(q?'3-'+q+'\',\'Info\',\'width=400,height=400':m+k+'\',\'Report\',\'width=656,height=267')+'\')'
):'')+'"':'')+(k?(flag.ref?' class="e"':' class="r"'):'')+'>'+tab[k]+'</p>'+post;
				}
				if (!(desc_num||flag.ref||flag.rep)) post =
e+'<div class="center">'+post+
e+'</div>';
				output +=
d+'<div class="post'+(desc_num?' p '+u_bar[isNaN(u)?u:0]:'')+alt+(res?' res':'')+'">'+post+
d+'</div>';
			}
		} else if (!preCount) {
			l = line[i][0];
			if (l == '<') output += n+line[i]; else
			if (l == '|') {
				tr.push(line[i].slice(1));
				if (!line[j = parseInt(i) +1] || line[j][0] != '|') {
					j = tr.length, m = (j > 1 && j < 4);
					output +=
					'<div class="post'+(alt = (alt?'':' alt'))+'">'
					+	'<div class="center">'
					+		'<table width="100%">'
					+			'<tr>', l =
									'<td'+(m?' width="'+Math.floor(100/j)+'%"':'');
					for (k in tr) output +=
					l+(
						m
						? ' align="'+(
							k == 0?'left':(k == j-1?'right':'center')
						)+'">'
						: '>'
					)+tr[k]
					+				'</td>';
					output +=		'</tr>'
					+		'</table>'
					+	'</div>'
					+ '</div>';
					tr = [];
				}
			}
		}

		if (g == '*' && count.o.length) {
			i = e+'	<p class="a ';
			j = e+'	<div'+(inout?' class="'+inout+'"':'')+'>'+i;
			k = (l = '</p>')+'</div>';
			output = output.replace(placeholder,
j+'l">'+count.oLast+k+
j+'r">'+count.uLast+k+'<br>'+
i+'l">'+count.u.join(f[1])+l+
i+'r">'+count.o.join(f[1])+l+'<br>');
		}
		if (hell && flag.hell[j = hell.class]) output =
d+'<div class="post alt anno"><a href="javascript:;" onClick="toggleHide(this.parentNode.nextElementSibling)">'+la.hint[j]+la.hint.show+'</a>'+
d+'</div>'+
d+'<div style="display:none">'+output+
d+'</div>';
		return output;
	}

	if (h) {
		p = '';
		for (i in a) o = getThread(a[i]), p +=
c+'<div class="thread'+(flag.u?' al':'')+(hell?' '+hell.class+'" id="'+hell.id:'')+'">'+o+
c+'</div>';
		h.innerHTML = (p?p+
c+'<div class="thread task">'+
d+'<p class="hint">'+
e+'<a href="javascript:showContent()">'+la.close+'</a><span class="r">'+
e+'<a href="javascript:document.body.firstElementChild.scrollIntoView(false)">'+la.top+'</a></span>'+
d+'</p>'+
c+'</div>'+b:p);
		if (p&&mm) mm();
	} else if (pre && (p = pre.parentNode)) {
		if (a.length > 1) {
			k = h = m = '', l = la.count;
			if (flag.c) {
				for (i in a) getThread(a[i], 1);
				for (i in count) if (count[i]) {
					k = (flag.ref
						? '<a href="javascript:showContent('+(l[i]?1:-1)+')">'+(l[i]
							? l.total
							: (l.lastr || l.last)
						)+'</a>'
						: (l[i]
							? (flag.u && i == 'u' ? l.self : l[i])
							: l.last
					))+': '+count[i];
					if (i == 'img') m += '<br>'+k;
					else h += (l[i]?(h?'<br>':''):', ')+k;
				}
				k = (h?e+'<span class="r">'+h+'</span>'+(m?e+m:(h.indexOf('<br>') > 0?'<br>&nbsp;':'')):'');
			}
			for (i in mt) if ((j = mt[i]).length) k += '<br>'+la[i]+': '+j.length+','+j.map(function(v,i) {
				return e+'<a href="javascript:showOpen('+v.i+')">'+v.t.replace(' ', ' <small>')+'</small></a>';
			}).join(',');
			p.className += ' task';
			p.innerHTML =
d+'<p class="hint"><a href="javascript:showContent()">'+(flag.u||flag.ref?la.groups:la.active)+': '+a.length+'</a>'+k+'</p>'+b;
			cre('div', p.parentNode, p.nextSibling).id = o;
			if (flag.a) showContent();
		} else {
			p.innerHTML = getThread(a[0]);
			if (hell) p.className += ' '+hell.class;
			if (mm) mm();
		}
	}

	if (g == '|') {
		for (h in (i = gn('select'))) if (i[h].onchange) i[h].onchange();
	} else
	if (g == '*') {
		d = gn('div'), i = d.length;
		while (i--) if (PT.test((c = d[i]).className) && (p = gn('p',c)).length > 1) {

			function w(e) {
			var	sum = e.offsetWidth, i,a = ['border-left-width', 'padding-left', 'padding-right', 'border-right-width'];
				for (i in a) if (getStyleValue(e, a[i].replace('width', 'style')) != 'none') sum -= parseInt(j = getStyleValue(e, a[i]));
				return sum;
			}

			a = (c = c.firstElementChild).lastElementChild.offsetWidth, e = w(p[0]), f = w(p[1]);
			if (a+e+f < c.offsetWidth) {
				if (e < f) p[0].style.width = f+'px'; else
				if (e > f) p[1].style.width = e+'px';
			}
		}
	}
	if (inout && g != ':') {
	var	s = 'style', h = gn('header')[0], e = gn(s, h);
		(e.length ? e[0] : cre(s,h)).innerHTML = '.post .center {max-width: 500px;}';
	}
}

if ((i = gn('pre')).length) showContent(i[0]);
if (k = id('task')) {
	if ((filter = k.getAttribute('data-filter')) !== null && (i = gi()).length) {
		j = k.nextSibling;
		while (!j.tagName) j = j.nextSibling;
		j.id = 'filter', i = i[0], i.onchange = i.onkeyup = filterList;
	}
	if (flag.k && (i = gn('form',k))) {
		i = cre('label', i[0]);
		i.className = 'r';
		i.innerHTML = la.checked+': <input type="checkbox" name="check" required>';
	}
	if (j = k.getAttribute('data-t')) {
		f = 0;
		if ((i = gn('p',k)).length) i[0].innerHTML +=
			(
				(j = j.split('-')).length > 1 && j[1]
				? '<a class="r" href="javascript:skipMyTask('+j[1]+')" title="'+la.skip_hint+'">「X」</a>'
				:''
			)+'<a class="r" href="'+(
				(f = j[0] && (j = parseInt(j[0])))
				? 'javascript:checkMyTask()" title="'+new Date(j*1000)+'\n\n'+la.check+'">「<span id="'+CS+'">?</span>'
				: '?draw">「'+la.draw
			)+'」</a>';
		if (f && (i = gi('submit',k)).length) {
			f = i[0];
			while (f && !FM.test(f.tagName)) f = f.parentNode;
			if (f) f.setAttribute('onsubmit', 'checkMyTask(event, this)');
		}
		if ((i = gn('img',k)).length && (i = i[0]) && (j = i.alt.indexOf(';')+1)) i.alt = i.alt
			.replace(';',', ')
			.replace('*','x'), setPicResize(i,j);
	}
	if (i = (j = gn('ul',k)).length) {
		n = (m = gn('b')).length, k = 1;
		while (n--) if (AN.test(m[n].className)) {k = 0; break;}
		while (i--) if (m = j[i].previousElementSibling) {
			m.innerHTML = '<a href="javascript:;" onClick="toggleHide(this.parentNode.nextElementSibling)">'+m.innerHTML+'</a>';
			if (k) toggleHide(j[i]), allowApply(-1);
		}
	}
}
if (k = id('tabs')) {

	function a(r,t) {return '<a href="'+r+(r == l?'" class="at':'')+'">'+t+'</a>';}

	h = '', l = l.split('/').slice(-1)[0], n = k.textContent.replace(WS, '').split('|');
	for (i in n) h += (h?'\n|	':'')+a(+i+1, n[i]);
	k.innerHTML = '[	'+h+'	]';	//* <- category tabs
var	p = k.parentNode, c,d,e,r = /^\d+-\d+-\d+(,\d+)*/, w = /\s.*$/;
	while ((m = p.lastElementChild) && !((j = m.lastElementChild) && j.id) && r.test(j = m.innerHTML.replace(WS, ''))) {
		j = j.split('-'), y = 'year'+(f = j[0]), n = j.pop().split(','), j = j.join('-'), h = j+':';
		for (i in n) h += '\n'+a(j+'-'+n[i].replace(w, ''), n[i]);
		m.innerHTML = h;		//* <- row: month, column: day
		if (!d || d.id != y) {
			(d = cre('div',c?c:c = cre('div',p,k.nextElementSibling))).id = y;
			if (d.previousElementSibling) toggleHide(d), cre('div',c,d).innerHTML = '<p><a href="javascript:toggleHide('+y+')">'+f+'</a></p>';
		}
		d.appendChild(m);
	}
}
for (i in (j = ['unsave', 'unskip'])) if (e = id(k = j[i])) {
	e.onclick = clearSaves, (e.onmouseover = checkSaves)(k);
}
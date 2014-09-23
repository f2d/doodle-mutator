var	h = gn('header')[0], i = gi(), j, k = id('task'), l = location.href, m, n
,	filtered, filter = null
,	rootPath = (h?gn('a',h)[0].href.replace(/^\w+:\/+[^\/]+\/+/, '/'):'/')
,	AN = /\banno\b/i
,	TU = /^\d+(<|>|$)/
,	WS = /^\s+|\s+$/g
,	NL = /^(\r\n|\r|\n)/g
,	count = {u:0, uLast:'', o:0, oLast:'', img:0}
,	u_bar = {0:'born', b:'burn', g:'goo', m:'ice', n:'null', u:'me'}
,	mm, mt = {frozen:[], burnt:[], full:[]}
,	checking, cs = 'checkStatus', flag = {}
,	inout = (('ontouchstart' in document.documentElement)?'':'date-out')
,	la, lang = document.documentElement.lang || 'en';

if (lang == 'ru') la = {
	arch: 'архив'
,	draw: 'Рисовать'
,	checked: 'Подтверждение'
,	check: 'Нажмите, чтобы проверить и продлить задание.'
,	close: 'Закрыть'
,	top: 'Наверх'
,	skip: 'Пропустить'
,	skip_hint: 'Пропускать задания из этой нити до её завершения.'
,	unskip: 'Список пропущенных заданий будет очищен для этих комнат:'
,	mistype: 'Тип задания сменился, обновите страницу.'
,	load: 'Проверка... '
,	fail: 'Ошибка '
,	hax: '(?)'		//'Нестандартный набор данных.'
,	time: 'Нарисовано за'
,	using: 'с помощью'
,	resized: 'Размер'	//'\nИзображение уменьшено.\nРазмер'
,	report: 'Жалоб'
,	active: 'Активных нитей'
,	frozen: 'Замороженные нити'
,	frozen_hint: 'Замороженная нить. Кликните, чтобы показать/спрятать.'
,	burnt: 'Выжженные нити'
,	full: 'Полные нити'
,	count: {img: 'Рисунков'
	,	u: 'Своих постов'
	,	o: 'Прочих'
	,	last: 'последний'
	,	self: 'Себя'
	,	each: 'Всех'
}}; else la = {
	arch: 'archive'
,	draw: 'Draw'
,	checked: 'Confirm'
,	check: 'Click this to verify and prolong your task.'
,	close: 'Close'
,	top: 'Top'
,	skip: 'Skip'
,	skip_hint: 'Skip any task from this thread from now on.'
,	unskip: 'Skipped tasks will be cleared for following rooms:'
,	mistype: 'Task type changed, please reload the page.'
,	load: 'Checking... '
,	fail: 'Error '
,	hax: '(?)'		//'Nonstandart data set.'
,	time: 'Drawn in'
,	using: 'using'
,	resized: 'Full size'	//'\nShown image is resized.\nFull size'
,	report: 'Reports'
,	active: 'Active threads'
,	frozen: 'Frozen threads'
,	frozen_hint: 'Frozen thread. Click here to show/hide.'
,	burnt: 'Burnt threads'
,	full: 'Full threads'
,	count: {img: 'Pictures'
	,	u: 'Own posts'
	,	o: 'Others'
	,	last: 'last'
	,	self: 'Self'
	,	each: 'All at once'
}};

if (((i.length && i[0].type == 'text') || (i = gn('textarea')).length) && (i = i0 = i[0])) {
	i.focus();
	if (i.onkeyup) (i.onchange = i.onkeyup)();
}

if ((i = gn('pre')).length) showContent(i[0]);

if (k) {
	if ((filter = k.getAttribute('data-filter')) !== null) {
		j = k.nextSibling;
		while (!j.tagName) j = j.nextSibling;
		j.id = 'filter';
	}
	if (j = k.getAttribute('data-t')) {
		if ((i = gn('p',k)).length) i[0].innerHTML +=
			(((j = j.split('-')).length > 1 && j[1])
				? '<a class="r" href="-'+j[1]+'" title="'+la.skip_hint+'">「X」</a>'
				:''
			)+'<a class="r" href="'+
			((j[0] && (j = parseInt(j[0])))
				? 'javascript:checkMyTask()" title="'+new Date(j*1000)+'\r\n'+la.check+'">「<span id="'+cs+'">?</span>'
				: '?">「'+la.draw
			)+'」</a>';
		if ((i = gn('img',k)).length && (i = i[0]) && (j = i.alt.indexOf(';')+1)) i.alt = i.alt
			.replace(';',', ')
			.replace('*','x'), setPicResize(i,j);
	}
	if (flag.k) k.lastElementChild.innerHTML +=
		'<span class="r">'+la.checked+': <input type="checkbox" id="ok" onChange="'+i0.getAttribute('onkeyup')+'"></span>';
	if (i = (j = gn('ul',k)).length) {
		n = (m = gn('b')).length;
		while (n--) if (AN.test(m[n].className)) {n = 1; break;}
		while (i--) if (m = j[i].previousElementSibling) {
			m.innerHTML = '<a href="javascript:;" onclick="toggleHide(this.parentNode.nextElementSibling)">'+m.innerHTML+'</a>';
			if (n !== 1) toggleHide(j[i]);
		}
	}
}

function gn(n,d) {return (d?d:document).getElementsByTagName(n);}
function gi(d) {return gn('input',d);}
function id(i) {return document.getElementById(i);}
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

function unskip() {
var	a = document.cookie.split(/;\s*/), i = a.length, j = [], k = [], m, r = /^([0-9a-z]+-skip-[0-9a-f]+)=([^\/]+)\//i;
	while (i--) if (m = a[i].match(r)) k.push(m[1]), j.push(decodeURIComponent(m[2]));
	if (k.length && confirm(la.unskip+'\n\n'+j.join('\n'))) for (i in k) deleteCookie(k[i]);
}

function checkMyTask() {
	if (checking) return;
	checking = 1;
var	s = id(cs), r = new XMLHttpRequest(), i, j, k, e, t;
	s.textContent = la.load+0;
	r.onreadystatechange = function() {
		if (r.readyState == 4) {
			if (r.status == 200) {
				t = (j = r.responseText.split('\n'))[2];
				s.textContent = j[0].replace(/<[^>]+>/g, '');
				j = !j[1];			//* <- 0:describe, 1:draw task
				if (k = id('task')) {
					i = (e = gn('img', k)).length;
					if (!i != j) s.innerHTML +=
'<b class="post">'+
'<b class="date-out l">'+
'<b class="report">'+la.mistype+'</b></b></b>';
					if (j) {
						if ((e = gn('p', k)).length && (e = e[1]).innerHTML != t) e.innerHTML = t;
					} else if (i) {
						e = e[0];
						k = t.indexOf(';')+1;
						if (!flag.pixr) flag.pixr = e.src.split('/').slice(0, flag.p?-3:-1).join('/')+'/';
						j = flag.pixr+(flag.p?getPicSubDir(t):'')+(k?t:t.replace(/(\.[^.\/;]+);.+$/,'_res$1'));
						if (e.src != j) e.src = j, e.alt = t, setPicResize(e, k);
					}
				}
			} else {
				s.textContent = la.fail;
				t = r.status || 0;
			}
			s.title = new Date()+'\r\n'+t;
			checking = 0;
		} else s.textContent = la.load+r.readyState;
	};
	r.open('GET', '-', true);
	r.send();
}

function setPicResize(e,i) {
	e.title = (i?e.alt.slice(i):'');
	e.style.cursor = (i?'move':'auto');
	e.setAttribute('onclick', i?'togglePicSize(this)':'');
	e.setAttribute('onload', 'setPicStyle(this)');
}

function setPicStyle(e) {
var	i = e.offsetWidth+16, a = {minWidth: Math.max(656,i), maxWidth: Math.max(1000,i)}, b = document.body.style, e = e.parentNode.style;
	for (i in a) e[i] = b[i] = a[i]+'px';
}

function togglePicSize(e) {
var	r = '_res';
	e.src = (e.src.indexOf(r) > 0
		? e.src.replace(r, '')
		: e.src.replace(/(\.[^.\/]+$)/, r+'$1')
	);
}

function submitLimit(l,m) {
	function filterList() {
	var	c = id('tower') || id('filter');
		if (!c || !filter || filtered == (v = v.toLowerCase())) return;
		filtered = v;
	var	d = gn('div',c), e, i, j, k, l = d.length, p = /\bpost\b/i, t = /^(div|p)$/i, alt;
		for (i = 0; i < l; i++) if (p.test((e = d[i]).className)) {
			if (e == e.parentNode.firstElementChild) alt = 1;
			if (!(k = gn('p',e)).length) k = e.textContent;
			else if (filter == 1 && k.length > 1) k = k[1].textContent;
			else {
				j = k.length-1, j = k[j > 1?1:j], k = '';
				while (j = j.nextSibling) if (!t.test(j.tagName)) k += j.textContent;
			}
			if (j = (!v || !(k = k.replace(WS, '').toLowerCase()) || k.indexOf(v) >= 0)) {
				alt = (alt?'':' alt');
				e.className = e.className.replace(/\s(alt|ok)\b/i, '')+(k == v?' ok':alt);
			}
			e.style.display = (j?'':'none');
		}
	}
	function r(e) {
		k = ((k && !k.checked)
			? true
			: (!(v = e.value) || (v = v.replace(WS, '')).length < l || (m && m < v.length))
		), filterList();
		for (t in i) if (i[t].type == 'submit') return i[t].disabled = k;
	}
var	i = gi(), k = id('ok'), t = id('task'), v;
	if (m&&(t = gn('textarea',t)).length) return r(t[0]);
	for (t in i) if (i[t].type == 'text') return r(i[t]);
}

function toggleOpt(e) {
var	p = e.parentNode, i = gi(p)[0], v = 0, s = gn('span',p);
	if (e == i) v = (parseInt(i.value)?1:0);
	else i.value = v = (e == s[1]?1:0);
	s[v].innerHTML = '<b>'+s[v].textContent+'</b>';
	s[1-v].innerHTML = '<a href="javascript:void(\''+i.name+'=o'+(v?'n':'ff')+'\')" onClick="toggleOpt(this.parentNode)">'+s[1-v].textContent+'</a>';
}

function selectLink(e,r,t) {
var	i = e.name+'_link', a = id(i), r = r.replace('*', r.indexOf('.') < 0 ? e.value : e.value.replace(/\.[^.\/]+$/, ''));
	if (a) a.href = r;
	else	e = e.parentNode.nextSibling
	, (	e = e.parentNode.insertBefore(document.createElement('div'), e)).className = inout
	,	e.innerHTML = '<p class="l"><a href="'+r+'" id="'+i+'">'+t+'</a></p>';
}

function showProps(o, z /*incl.zero*/) {var i,t=''; for(i in o)if(z||o[i])t+='\n'+i+'='+o[i]; alert(t); return o;}
function showContent(pre) {
	if (pre) window.ph = pre.innerHTML;
var	i, j, k, l, m, n = '\n', o = 'tower', p = window.ph, opt = 'opt_', q, s = ' ', t = '	'
,	a = p.split(n+n), b = n+t, c = b+t, d = c+t, e = d+t
,	f = p.split(n,1)[0], g = ':|*', h = id(o), hell, recl = ['report'];

	for (i in mt) recl.push(i);

	if (f == 'ref') {
		flag.ref = flag.a = flag.c = 1;
		a = p.split(n), j = {}, k = [];
		for (i in a) if (a[i].indexOf(t) > 0 && (m = a[i].match(/\s([^:\/]*[:\/]+)?([^\/]+)/))) {
			m = m[2].replace(/\W+$/, '').split('.').slice(-2).join('.');
			if (k.indexOf(m) < 0) k.push(m), j[m] = [];
			j[m].push(a[i]);
		}
		a = [];
		k.sort();
		for (i in k) a.push(j[k[i]].join(n));
	} else
	for (i in g) if (f.indexOf(g[i]) > -1) {
		l = (f = f.split(g = g[i])).length;
		if (g == '*') count.u = [0,0,0], count.o = [0,0];
		if (g == ':') flag.pixr = f[2];
		if (l > 3) for (i in f[3]) flag[f[3][i]] = 1;
		break;
	}

	function getThread(txt, preCount) {
	var	line = txt.split(n), output = '', placeholder = '<!--?-->'
	,	img = (flag.u?0:1), alt = 1, tr = []
	,	desc_num = (g == ':'?1:0), post_num = 0, thread_num = 0, i, j, k, l, m;
		hell = 0;
		for (i in line) if (line[i].indexOf(t) > 0) {
		var	tab = line[i].split(t), u = (tab.length > 3?tab.shift():''), post = '<br>', res = 0;
			if (u.indexOf(',') > -1) {
				u = u.split(','), thread_num = u.shift(), u = u[0], j = thread_num[0];
				if (isNaN(j)) {
					hell = recl[k = 'asdf'.indexOf(j)]+'" id="'+(thread_num = thread_num.slice(1));
					if (preCount) mt[recl[k]].push(thread_num);
				}
			}
			if (preCount) {
				if (tab.length > 3) ++count.img;
				if (TU.test(post = tab[0])) post = unixTimeToStamp(post);
				++count[u == 'u'?u:u = 'o'];
				if (count[u += 'Last'] < post) count[u] = post;
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
							j = tab[2].split(j), res = 1;
							tab[0] +=
'<span class="a">'+(n+la.resized).replace(NL, '<br />')+': '+j[1].replace('*', 'x')+'</span>';
							tab[2] = j[0].replace(/(\.[^.]+)$/, '_res$1');
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
							tab[1] = '<input type="text" name="'+opt+k[0]+'" value="'+k[2]+'">';
						} else
						if (k[1].indexOf(g) > 0) {
							l = k[1].split(':'), m = parseInt(l.shift());
						var	opts = opt+k[0]+(l.length > 3?'" onChange="selectLink(this,\''+l[2]+'\',\''+l[3]+'\')':'')+'">';
							k = l[0].split(g);
							l = l[1].split(g);
							for (j in k) opts +=
e+'<option value="'+k[j]+'"'+(m == j?' selected':'')+'>'+(l[j]?l:k)[j]+'</option>';
							tab[1] =
e+'<select name="'+opts+
e+'</select>';
						} else {
							tab[1] =
' <span> [ <span>'+f[0]+
'</span> | <span>'+f[1]+
'</span> ]<input type="hidden" name="'+opt+k[0]+'" value="'+k[1]+'"></span>';
						}
					} else
					if (tab.length > 2) {
					var	da = {}, dd = '', an = '/';						//* room list:
						if (g == '*') {
							if (l = tab[2].indexOf('/')+1) {
								k = tab[2].slice(l).replace(/"/g, '&quot;');
								m = 'room';
								if (k[0] == '/') k = k.slice(1); else m += ' mod';	//* <- room frozen/announce
								an += '" class="'+m+'" title="'+k;
								tab[2] = tab[2].slice(0,l-1);
							}
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
				if (u == 'u') post = '<span class="u">'+post+'</span>'; else
				if (flag.u) post = post.replace(' ', ' <span class="a">')+'</span>';
			var	q = (flag.u?tab[2].slice(0, tab[2].indexOf('.')).replace(WS, ''):0);
				m = (thread_num?thread_num+'-'+post_num+'-':'');
				k = 2;
				while (k--) if (tab[k].replace(WS, '').length || (flag.u && (tab[k] += la.count[k?'self':'each']))) {
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
				if (!(desc_num||flag.ref)) post =
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
					output +=
d+'<div class="post'+(alt = (alt?'':' alt'))+'">'+
e+'<div class="center">'+
e+'	<table width="100%"><tr>';
					l =
e+'		<td width="'+Math.floor(100/(j = tr.length))+'%"';
					for (k in tr) output += l+(j > 1 && j < 4?' align="'+(
						k == 0	?'left':(
						k == j-1?'right':'center')
					)+'">':'>')+tr[k]+'</td>';
					tr = [];
					output +=
e+'	</tr></table>'+
e+'</div>'+
d+'</div>';
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
		if (!mm&&hell&&(j = hell.split('"'))[0] == 'frozen') {
			output =
d+'<div class="post alt anno"><a href="javascript:;" onclick="toggleHide(this.parentNode.nextElementSibling)">'+la.frozen_hint+'</a>'+
d+'</div>'+
d+'<div style="display:none">'+output+
d+'</div>';
		}
		return output;
	}

	if (h) {
		if (h.innerHTML == (p = '')) for (i in a) {
			o = getThread(a[i]);
			p +=
c+'<div class="thread'+(flag.u?' al':'')+(hell?' '+hell:'')+'">'+o+
c+'</div>';
		}
		h.innerHTML = (p?p+
b+'<div class="thread task">'+
c+'<p class="hint">'+
d+'<a href="javascript:showContent()">'+la.close+'</a><span class="r">'+
d+'<a href="javascript:document.body.firstElementChild.scrollIntoView(false);void(0);">'+la.top+'</a></span>'+
c+'</p>'+
b+'</div>':p);
		if (p&&mm) mm();
	} else if (pre) {
	var	p = pre.parentNode;
		if (a.length > 1) {
			k = h = m = '', l = la.count;
			if (flag.c) {
				for (i in a) getThread(a[i], 1);
				for (i in count) if (count[i]) {
					k = (l[i]?(i == 'u' && flag.u?l.self:l[i]):l.last)+': '+count[i];
					if (i == 'img') m += '<br>'+k;
					else h += (l[i]?(h?'<br>':''):', ')+k;
				}
				k = (h?e+'<span class="r">'+h+'</span>'+(m?e+m:(h.indexOf('<br>') > 0?'<br>&nbsp;':'')):'');
			}
			for (i in mt) if (mt[i].length) {
				k += ', '+la[i]+':';
				for (j in mt[i]) k +=
e+'<a href="#'+mt[i][j]+'"># '+mt[i][j]+'</a>';
			}
			p.className += ' task';
			p.innerHTML =
d+'<p class="hint"><a href="javascript:showContent()">'+la.active+': '+a.length+'</a>'+k+'</p>'+b;
			p.parentNode.insertBefore(h = document.createElement('div'), p.nextSibling);
			h.id = o;
			if (flag.a) showContent();
		} else {
			p.innerHTML = getThread(a[0]);
			if (hell) p.className += ' '+hell;
			if (mm) mm();
		}
	}

	if (g == '|') {
		for (h in (i = gi())) if (i[h].name && i[h].name.slice(0,4) == opt && i[h].type == 'hidden') toggleOpt(i[h]);
		for (h in (i = gn('select'))) if (i[h].onchange) i[h].onchange();
	}
	if (g != ':' && inout) {
	var	s = 'style', h = gn('header')[0], e = gn(s, h);
		(e.length ? e[0] : h.appendChild(document.createElement(s))).innerHTML = '.post .center {max-width: 500px;}';
	}
}
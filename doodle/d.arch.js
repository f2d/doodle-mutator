var	LS = window.localStorage || localStorage
,	bnw = bnw || []

,	param = {
		caps: 'at'
	,	caps_around: 3
	,	caps_width: 640
	}

,	regClassPost = getClassReg('post')
,	regClassThread = getClassReg('thread')
,	regHash = /([;,]\s*)?0x[0-9a-f]{8}$/i
,	regBytes = /([;,]\s*)?\d+\s*B$/i
,	regNaN = /\D+/
,	regSpace = /\s+/g
,	regTrim = /^\s+|\s+$/g
,	regTimeBreak = /^\d+(<|>|,|$)/

,	splitSec = 60
,	TOS = ['object','string']

,	touch = ('ontouchstart' in document.documentElement)
,	d = document.body.style
,	w = [d.maxWidth||'1000px', '690px']

,	room = (
		document.title
	||	location.pathname.split('/').slice(-2)[0]
	||	'room'
	).replace(regTrim, '')
,	timeRange

,	lang = getCookie('lang') || (LS ? LS.lang : '') || document.documentElement.lang || 'en'
	;

//* Utility functions *--------------------------------------------------------

function gc(n,p) {try {return TOS.slice.call((p || document).getElementsByClassName(n) || []);} catch(e) {return [];}}
function gn(n,p) {try {return TOS.slice.call((p || document).getElementsByTagName(n) || []);} catch(e) {return [];}}
function id(i) {return document.getElementById(i);}
function fit() {v.content = 'width='+(d.maxWidth = w[d.maxWidth != w[1]?1:0]).replace(regNaN,'');}
function meta() {toggleClass(document.body, 'hide-aside');}
function getClassReg(c) {return new RegExp('(^|\\s)('+c+')($|\\s)', 'i');}
function toggleClass(e,c,keep) {
var	k = 'className'
,	old = e[k]
,	a = (old ? old.split(regSpace) : [])
,	i = a.indexOf(c)
	;
	if (i < 0) {
		if (!(keep < 0)) a.push(c);
	} else {
		if (!(keep > 0)) a.splice(i, 1);
	}
	if (a.length) e[k] = a.join(' ');
	else if (old) e[k] = '', e.removeAttribute(k);
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

function getParentBeforeClass(e,c) {
var	p = e, r = (c.test?c:getClassReg(c));
	while ((e = e.parentNode) && !(e.className && r.test(e.className))) p = e;
	return p;
}

function cre(e,p,b) {
	e = document.createElement(e);
	if (b) p.insertBefore(e, b); else
	if (p) p.appendChild(e);
	return e;
}

function del(e,p) {
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

function getCookie(name) {
	//* https://stackoverflow.com/a/25490531
var	m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
	return m ? m.pop() : '';
}

function orz(n) {return parseInt(n||0)||0;}
function leftPad(n) {n = orz(n); return n > 9 || n < 0?n:'0'+n;}
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
var	YMD = t.slice(0,3).join('-')
,	HIS = t.slice(3).join(':')
	;
	return (
		plain
		? YMD+' '+HIS
		: '<time datetime="'+YMD+'T'+HIS
		+	getFormattedTimezoneOffset(t)
		+	'" data-t="'+Math.floor(d/1000)
		+	'">'+YMD+' <small>'+HIS+'</small></time>'
	);
}

//* Specific functions *-------------------------------------------------------

function showArch(p) {
	if (id('task1')) return;

var	h,i,j,k,l,m,t = '\t', threadHTML = '', alt = 1, img = 1, num = 1

,	regSiteName = /^(\w+:+)?\/\/([^\/]+)\/+/
,	regLNaN = /^\D+/
,	regImgTag = /<img [^>]+>/i
,	regImgTitle = /\s+(title="[^"]+)"/i
,	regImgUrl = /(".*\/([^\/"]*)")>/
,	regTimeDrawn = /^((\d+)-(\d+)(?:[^\d:,=-]+(\d+)-(\d+))?|[\d:]+)(?:=(-?\d+))?,(.*)$/m

,	line = p.innerHTML.split('\n')
	;
	for (i in line) if (line[i].indexOf(t) > 0) {
	var	tab = line[i].split(t)
	,	post = '<br>'
	,	m = getFTimeIfTime(tab[0], 1)
	,	res_link = 0
	,	res = 0
		;
		tab[0] = getFTimeIfTime(tab[0]);
		if (!timeRange) timeRange = [m,m];
		else {
			if (timeRange[0] > m) timeRange[0] = m;
			if (timeRange[1] < m) timeRange[1] = m;
		}
		if (tab.length > 3) {
	//* deleted file placeholder:
			if (tab[3][0] == '?') {
				m = tab[2].match(regImgUrl);
				post = '<span title="'+(m?m[1]:tab[2])+'">'
				+		tab[3].slice(1)
				+	'</span>';
			} else {
	//* image, meta, link to full size:
				if (m = tab[3].match(regTimeDrawn)) {
					if (m[2]) {
					var	k = getFormattedHMS(+m[3]-m[2])
					,	i = (m[5] && m[5] != m[4] ? getFormattedHMS(+m[5]-m[4]) : '')
					,	j = m[6]
						;
						m[1] = (
							orz(j) > 0 && (j = getFormattedHMS(j)) != k
							? j+' ('+k+(i?' / '+i:'')+')'
							: k+(i?' ('+i+')':'')
						);
					}
					tab[3] = m[1]+', '+m[7];
				}
				post = tab[2];
				res = (post.indexOf(', ') > 0);
				j = '">';
				k = post.split(j);
				for (i in k) if (l = k[i]) {
					m = l.split(regSpace, 1)[0].substr(1).toLowerCase();
					if (m == 'a') k[i] += '" class="res" target="_blank', res_link = 1; else
					if (m == 'img') k[i] += '" alt="'+l.substr(l.lastIndexOf('/')+1)+', '+tab[3]+'" title="'+tab[3];
				}
				post = k.join(j);
				if (res_link) {
					j = post.split(l = '>');
					k = j.pop()
						.replace(regTrim, '')
						.replace(regHash, '')
						.replace(regBytes, '')
						.replace(regLNaN, '')
						.replace(regNaN, 'x');
					post = j.join(l).replace(regImgTitle, ' $1, '+k+'"')+l;
					tab[0] += '<br>'+post.replace(regImgTag, k);
					tab[1] += '<br>&nbsp;';
				} else
				if (res) {
					post = post.substr(0, post.lastIndexOf('>')+1);
				}
			}
			if (img) alt = (alt?'':' alt');
			img = 1;
		} else {
	//* number + text post:
			if (tab.length > 2) post = ((num && img?num++ +'. ':'')+tab[2])
				.replace(/\s+(-|&mdash;|—|&ndash;|–|)\s+/gi, '&nbsp;$1 ')
				.replace(/\s+([^<\s]{1,2})\s+/g, ' $1&nbsp;');
			alt = (alt?'':' alt');
			img = 0;
		}
		k = 2;
		while (k--) post =
			'<aside'+(k?' class="r"':'')+'>'
		+		tab[k]
		+	'</aside>'+post;

		threadHTML += '<div class="post pad'
		+	(num?' p':'')
		+	(res?' res':'')
		+	alt
		+	'">'
		+		post
		+	'</div>';
	}
	if (threadHTML) {
	var	thisPage
	,	nextPage
	,	prevPage
	,	rootPath
	,	lastDir
	,	pageExt = '.htm'
	,	a = gn('link')
	,	i = a.length
	,	d = document.body
	,	pre = p
		;
		window.addEventListener('load', function() {del(pre);}, false);
		if (k = id('task')) {
			thisPage = orz(k.textContent);
			while (e = k.lastChild) k.removeChild(e);
		} else {
			k = cre('div', d, d.firstChild);
		}
		while (i--) if ((l = a[i]) && (h = l.getAttribute('href'))) {
			h = h.replace(regSiteName, '/');
			if (l.rel == 'index') rootPath = h; else			//* <- explicit link given
			if (l.rel == 'next') nextPage = h; else
			if (l.rel == 'prev') prevPage = h; else
			if ((l = h.lastIndexOf('/')) >= 0) lastDir = h.substr(0,l);	//* <- or guess
		}
		if (!thisPage && nextPage) thisPage = orz(nextPage)-1;
		if (thisPage) {
			if (thisPage <= 1) prevPage = '.'; else
			if (!prevPage) prevPage = (thisPage-1) + pageExt;
			if (!nextPage) nextPage = (thisPage+1) + pageExt;
		}
		document.title = room.replace(/\/+/g, ' - ')+' - '+thisPage+(timeRange?', '+timeRange.join(' - '):'');
	//* header, links up:
		a = {
			'&#8662;': rootPath || lastDir || '../..'			//* <- or a hand-wave
		,	'&#8679;': '.'
		,	'&#8596;': 'javascript:fit()'
		,	'&#9636;': 'javascript:meta()'
		}, h = '';
		for (i in a) h += '<u><a href="'+a[i]+'">'+i+'</a></u>'
		e = gn(i = 'header')[0] || cre(i, d, d.firstChild);
		e.className = i+' a'+(touch?' touch':'');
		e.innerHTML = '<u>'+h+'</u>';
	//* top/bottom bar, links to prev/next:
		h =	'<a href="'+prevPage+'" class="al">&#8678; '+(orz(prevPage) || '')+'</a>'
		+	'<a href="'+nextPage+'" class="ar">'+orz(nextPage)+' &#8680;</a>'
		+	'<p>'+orz(thisPage)+'</p>';
		p = p.parentNode, a = [k, p], i = a.length;
		while (i--) {
			e = cre('div', e = a[i], e.firstChild);
			e.parentNode.id = 'task'+(orz(i) || '');
			e.className = 'task next-prev';
			e.innerHTML = h;
		}
	//* posts content:
		e = cre('div', document.body, p);
		e.className = e.id = 'content';
		e = cre('div', e);
		e.className = 'thread';
		e.innerHTML = threadHTML;
		bnw.map(function(f) {f(1);});
	}
}

//* Runtime *------------------------------------------------------------------

if (a = gn('meta')) for (i in a) if ((e = a[i]) && e.name == 'viewport') {v = e; break;}
if (a = gn('pre')) for (i in a) if (e = a[i]) showArch(e);
if (touch) fit(), meta();	//* <- compact view and big buttons for touch screen
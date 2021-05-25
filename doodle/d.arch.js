var	LS = window.localStorage || localStorage
,	bnw = bnw || []

,	param = {
		caps: 'atm'
	,	caps_around: 3
	,	caps_width: 640
	,	arch_term_name: 'fullname'
	,	archives: '../'
	,	page_ext: '.htm'
	}

,	regClassPost = getClassReg('post')
,	regClassThread = getClassReg('thread')
,	regHash = /([;,]\s*)?0x[0-9a-f]{8}$/i
,	regBytes = /([;,]\s*)?\d+\s*B$/i
,	regNaN = /\D+/
,	regSpace = /\s+/g
,	regSpaceHTML = /\s|&(nbsp|#8203|#x200B);?/gi
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

,	thisPage
,	timeRange

,	la
,	langs = ['en', 'ru']
,	lang = getActiveLang() || 'en';

//* UI translation *-----------------------------------------------------------

if (lang == 'ru') la = {
	post_menu_hint: {
		post: 'Действия с этим постом.'
	,	user: 'Действия с этим пользователем.'
	}
,	post_menu: {
		arch_room: 'Найти в архиве комнаты'
	,	arch_all: 'Найти во всех архивах'
	,	capture_thread: 'Снимок всей нити'
	,	capture_to_last_pic: 'Снимок по последний рисунок'
	,	capture_to_this_post: 'Снимок по этот пост'
	,	title_:'Actions on this post'
	}
}; else la = {
	post_menu_hint: {
		post: 'Actions on this post.'
	,	user: 'Actions on this user.'
	}
,	post_menu: {
		arch_room: 'Search in room archive'
	,	arch_all: 'Search in all archives'
	,	capture_thread: 'Save screenshot of the thread'
	,	capture_to_last_pic: 'Save screenshot up to last pic'
	,	capture_to_this_post: 'Save screenshot up to this post'
	}
};

//* Utility functions *--------------------------------------------------------

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

function gc(n,p) {try {return TOS.slice.call((p || document).getElementsByClassName(n) || []);} catch(e) {return [];}}
function gn(n,p) {try {return TOS.slice.call((p || document).getElementsByTagName(n) || []);} catch(e) {return [];}}
function id(i) {return document.getElementById(i);}
function fit() {v.content = 'width='+(d.maxWidth = w[d.maxWidth != w[1]?1:0]).replace(regNaN,'');}
function meta() {toggleClass(document.body, 'hide-aside');}
function isNotEmpty(t) {return String(t).replace(regSpaceHTML, '').length;}
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

function getActiveLang() {
	return (
		getCookie('lang')
	||	(LS ? LS.lang : null)
	||	(navigator.languages ? navigator.languages.reduce(function(r,v) { return r || (langs.indexOf(v) < 0 ? r : v); }, null) : null)
	||	navigator.language
	||	document.documentElement.lang
	);
}

function getCookie(name) {
	//* https://stackoverflow.com/a/25490531
var	m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
	return m ? m.pop() : '';
}

function getDropdownMenuHTML(head, list, tag) {
var	t = tag || 'div'
,	a = '<'+t+' class="'
,	b = '</'+t+'>'
	;
	return	a+'menu-head">'
	+		(head || '')
	+	a+'menu-top">'
	+	a+'menu-hid">'
	+	a+'menu-list">'
	+		(list || '')
	+	b+b+b+b;
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

function getFTimeIfTime(t, plain) {
	return (
		regTimeBreak.test(t = ''+t)
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

//* Specific functions *-------------------------------------------------------

function showArch(pre) {
	if (id('task1')) return;

var	h,i,j,k,l,m,t

,	regSiteName = /^(\w+:+)?\/\/([^\/]+)\/+/
,	regLNaN = /^\D+/
,	regImgTag = /<img [^>]+>/i
,	regImgTitle = /\s+(title="[^"]+)"/i
,	regImgUrl = /(".*\/([^\/"]*)")>/
,	regTimeDrawn = /^((\d+)-(\d+)(?:[^\d:,=-]+(\d+)-(\d+))?|[\d:]+)(?:=(-?\d+))?,(.*)$/m

,	nextPage
,	prevPage
,	rootPath
,	lastDir
,	taskBar = id('task')
,	d = document.body
,	a = gn('link')
,	i = a.length
	;

	if (taskBar) {
		thisPage = orz(taskBar.textContent);
		while (e = taskBar.lastChild) taskBar.removeChild(e);
	} else {
		taskBar = cre('div', d, d.firstChild);
	}
	for (var i in a) if ((l = a[i]) && (h = l.getAttribute('href'))) {
		h = h.replace(regSiteName, '/');
		if (l.rel == 'index') rootPath = h; else			//* <- explicit link given
		if (l.rel == 'next') nextPage = h; else
		if (l.rel == 'prev') prevPage = h; else
		if ((l = h.lastIndexOf('/')) >= 0) lastDir = h.substr(0,l);	//* <- or guess
	}
	if (!thisPage && nextPage) thisPage = orz(nextPage)-1;
	if (thisPage) {
		if (thisPage <= 1) prevPage = '.'; else
		if (!prevPage) prevPage = (thisPage-1) + param.page_ext;
		if (!nextPage) nextPage = (thisPage+1) + param.page_ext;
	}

var	threadHTML = ''
,	alt = 1
,	img = 1
,	descNum = 1
,	postNum = 1

,	line
,	lines = pre.innerHTML.split('\n')
,	tabDelimiter = '\t'
	;

	for (var l_i in lines) if (
		(line = lines[l_i])
	&&	line.indexOf(tabDelimiter) > 0
	) {
	var	tab = line.split(tabDelimiter)
	,	post = '<br>'
	,	m = getFTimeIfTime(tab[0], 1)
	,	res_link = 0
	,	res = 0
	,	userName = tab[1]
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

			if (tab.length > 2) {
				t = tab[2];

				if (descNum && img) {
					t = (descNum++)+'. '+t;
				}

				post = (
					t
					.replace(/\s+(-|&mdash;|—|&ndash;|–|)\s+/gi, '&nbsp;$1 ')
					.replace(/\s+([^<\s]{1,2})\s+/g, ' $1&nbsp;')
				);
			}
			alt = (alt?'':' alt');
			img = 0;
		}

//* side info:

	var	asides = []
	,	i = 2
	,	postHoverMenu = 0
	,	postID = 'post-'+postNum
	,	archPath = param.archives || rootPath+'archive/'
	,	roomName = param.room || room
	,	userName = encodeURIComponent(decodeHTMLSpecialChars(userName))
		;
		while (i--) {
			t = tab[i];

//* side menu:

		var	nameQuery = (
				i > 0 && userName.length > 0
				? '?'+(param.arch_term_name || 'name')+'='+userName
				: null
			)
		,	capBtnParts = (
				i == 0 && param.caps_width > 0
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
					nameQuery
					? archPath + roomName + '/' + nameQuery
					+ '" class="menu-btn-mark search-mark" rel="nofollow'
					: ''
				)
			,	arch_all: (
					nameQuery
					? archPath + nameQuery
					+ '" class="menu-btn-mark search-mark" rel="nofollow'
					: ''
				)
			,	capture_thread:       (capBtnParts ? capBtnParts.join(0)       : '')
			,	capture_to_last_pic:  (capBtnParts ? capBtnParts.join(-1)      : '')
			,	capture_to_this_post: (capBtnParts ? capBtnParts.join(postNum) : '')
			}
		,	m = ''
			;

			for (k in a) if (j = a[k]) {
				m += (
					'<a href="'+j+'">'
				+		la.post_menu[k]
				+	'</a>'
				);
			}

//* side menu container:

			if (m) {
				postHoverMenu = 1;
				t = (
					'<div class="menu-wrap">'
				+		getDropdownMenuHTML(
							'<div class="stub">'
						+		t
						+	'</div>'
						, m, 'div')
				+		'&nbsp;'
				+	'</div>'
				);
			}

//* side info container:

			asides.push(
				'<aside'
			+	(i?' class="r"':'')
			+	(m?' title="'+(la.post_menu_hint[i?'user':'post'])+'"':'')
			+	'>'
			+		t
			+	'</aside>'
			);
		}

//* post container:

		threadHTML += (
			'<div class="post pad'
		+	(descNum?' p':'')
		+	(res?' res':'')
		+	(postHoverMenu?' hover-menu':'')
		+	alt
		+	'" id="'+postID
		+	'">'
		+		asides.join('')
		+		post
		+	'</div>'
		);

		++postNum;
	}

//* thread container:

	if (threadHTML) {
		window.addEventListener('load', function() {del(pre);}, false);
		document.title = room.replace(/\/+/g, ' - ')+' - '+thisPage+(timeRange?', '+timeRange.join(' - '):'');

//* header, links up:

		a = {
			'&#8662;': rootPath || lastDir || '../..'
		,	'&#8679;': '.'
		,	'&#8596;': 'javascript:fit()'
		,	'&#9636;': 'javascript:meta()'
		}, h = '';
		for (i in a) {
			h += '<u><a href="'+a[i]+'">'+i+'</a></u>';
		}
		e = gn(i = 'header')[0] || cre(i, d, d.firstChild);
		e.className = i+' a'+(touch?' touch':'');
		e.innerHTML = '<u>'+h+'</u>';

//* top/bottom bar, links to prev/next:

		h = (
			'<a href="'+prevPage+'" class="al">&#8678; '+(orz(prevPage) || '')+'</a>'
		+	'<a href="'+nextPage+'" class="ar">'+orz(nextPage)+' &#8680;</a>'
		+	'<p>'+orz(thisPage)+'</p>'
		);

	var	p = pre.parentNode
	,	a = [taskBar, p]
	,	i = a.length
		;
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

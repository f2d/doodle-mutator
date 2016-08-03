var	regLNaN = /^\D+/
,	regNaN = /\D+/
,	regSpace = /\s+/g
,	regTrim = /^\s+|\s+$/g
,	regTimeBreak = /^\d+(<|>|,|$)/
,	regImgTitle = /\s+(title="[^"]+)"/i

,	split_sec = 60
,	TOS = ['object','string']

,	touch = ('ontouchstart' in document.documentElement)
,	d = document.body.style
,	w = [d.maxWidth||'1000px', '690px']

,	room = (
		document.title
	||	location.pathname.split('/').slice(-2)[0]
	||	'room'
	).replace(regTrim, '')
,	timeRange;

//* Runtime *------------------------------------------------------------------

if (a = gn('meta')) for (i in a) if ((e = a[i]) && e.name == 'viewport') {v = e; break;}
if (a = gn('pre')) for (i in a) if (e = a[i]) showArch(e);
if (touch) fit(), meta();	//* <- compact view and big buttons for touch screen

//* Utility functions *--------------------------------------------------------

function gn(n,p) {return w.slice.call((p || document).getElementsByTagName(n) || []);}
function id(i) {return document.getElementById(i);}
function fit() {v.content = 'width='+(d.maxWidth = w[d.maxWidth != w[1]?1:0]).replace(regNaN,'');}
function meta() {toggleClass(document.body, 'hide-p');}
function toggleClass(e,c) {
var	i = e.className, a = (i?i.split(regSpace):[]), i = a.indexOf(c);
	if (i < 0) a.push(c); else a.splice(i, 1);
	e.className = a.join(' ');
}

function cre(e,p,b) {
	e = document.createElement(e);
	if (b) p.insertBefore(e, b); else
	if (p) p.appendChild(e);
	return e;
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

//* Specific functions *-------------------------------------------------------

function showArch(p) {
var	h,i,j,k,l,m,t = '\t', threadHTML = '', alt = 1, img = 1, num = 1

,	regImgTag = /<img [^>]+>/i
,	regImgUrl = /(".*\/([^\/"]*)")>/
,	regTimeDrawn = /^((\d+)-(\d+)|[\d:]+),(.*)$/m

,	line = p.innerHTML.split('\n');

	for (i in line) if (line[i].indexOf(t) > 0) {
	var	tab = line[i].split(t), post = '<br>', m = getFTimeIfTime(tab[0], 1), res = 0;
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
						j = +m[3]-m[2], k = [0, 0, Math.floor(Math.abs(j)/1000)], l = k.length;
						while (--l) {
							if (k[l] >= split_sec) k[l-1] = Math.floor(k[l]/split_sec), k[l] %= split_sec;
							if (k[l] < 10) k[l] = '0'+k[l];
						}
						m[1] = (j < 0?'-':'')+k.join(':');
					}
					tab[3] = m[1]+', '+m[4];
				}
				post = tab[2];
				res = (post.indexOf(', ') > 0);
				j = '">';
				k = post.split(j);
				for (i in k) if (l = k[i]) {
					m = l.split(regSpace, 1)[0].substr(1).toLowerCase();
					if (m == 'a') k[i] += '" class="res" target="_blank'; else
					if (m == 'img') k[i] += '" alt="'+l.substr(l.lastIndexOf('/')+1)+', '+tab[3]+'" title="'+tab[3];
				}
				post = k.join(j);
				if (res) {
					j = post.split(l = '>');
					k = j.pop()
						.replace(regTrim, '')
						.replace(regLNaN, '')
						.replace(regNaN, 'x');
					post = j.join(l).replace(regImgTitle, ' $1, '+k+'"')+l;
					tab[0] += '<br>'+post.replace(regImgTag, k);
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
			'<p'+(k?' class="r"':'')+'>'
		+		tab[k]
		+	'</p>'+post;

		threadHTML += '<div class="post'
		+	(num?' p'+(res?' res':''):'')
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
		;
		if (k = id('task')) {
			thisPage = orz(k.textContent);
			while (e = k.lastChild) k.removeChild(e);
		} else {
			k = cre('div', d, d.firstChild);
			k.id = 'task';
		}
		while (i--) if ((l = a[i]) && (h = l.getAttribute('href'))) {
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
		document.title = room+' - '+thisPage+(timeRange?', '+timeRange.join(' - '):'');
	//* header, links up:
		a = {
			'&#8662;': rootPath || lastDir || '../..'			//* <- or a hand-wave
		,	'&#8679;': '.'
		,	'&#8596;': 'javascript:fit()'
		,	'&#9636;': 'javascript:meta()'
		}, h = '';
		for (i in a) h += '<u><a href="'+a[i]+'">'+i+'</a></u>'
		e = gn('header')[0] || cre('header', d, d.firstChild);
		e.className = 'a'+(touch?' touch':'');
		e.innerHTML = '<u>'+h+'</u>';
	//* top/bottom bar, links to prev/next:
		h =	'<p class="arr">'
		+		'<a href="'+prevPage+'">&#8678; '+(orz(prevPage) || '')+'</a>'
		+		'<a href="'+nextPage+'" class="r">'+orz(nextPage)+' &#8680;</a>'
		+	'</p>';
		p = p.parentNode, a = [k, p], i = a.length;
		while (i--) {
			e = cre('div', e = a[i], e.firstChild);
			e.className = 'task';
			e.innerHTML = h;
		}
	//* posts content:
		e = cre('div', document.body, p);
		e.className = 'thread';
		e.innerHTML = threadHTML;
	}
}
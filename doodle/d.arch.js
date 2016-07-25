var	regNaN = /\D+/
,	regSpace = /\s+/g
,	regTrim = /^\s+|\s+$/g

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

//* Specific functions *-------------------------------------------------------

function showArch(p) {
var	h,i,j,k,l,m,t = '\t', thread = '', alt = 1, img = 1, num = 1, split_sec = 60

,	regImgTag = /<img [^>]+>/i
,	regImgUrl = /(".*\/([^\/"]*)")>/
,	regTimeDrawn = /^((\d+)-(\d+)|[\d:]+),(.*)$/m

,	line = p.innerHTML.split('\n');

	for (i in line) if (line[i].indexOf(t) > 0) {
	var	tab = line[i].split(t), post = '<br>', m = tab[0];
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
				j = '">';
				k = post.split(j);
				for (i in k) if (l = k[i]) {
					m = l.split(regSpace, 1)[0].substr(1).toLowerCase();
					if (m == 'a') k[i] += '" class="res" target="_blank'; else
					if (m == 'img') k[i] += '" alt="'+l.substr(l.lastIndexOf('/')+1)+', '+tab[3]+'" title="'+tab[3];
				}
				post = k.join(j);
				if (post.indexOf(k = '>;') > 0) {
					j = post.split(k);
					l = j.shift();
					k = j.join(k).replace(regTrim, '').replace(regNaN, 'x');
					post = l.replace(/\s+(title="[^"]+)"/i, ' $1, '+k+'"')+'>';
					tab[0] += '<br>'+post.replace(regImgTag, k);
				} else l = 0;
			}
			if (img) alt = (alt?'':' alt');
			img = 1;
		} else {
	//* number + text post:
			if (tab.length > 2) post = (num && img?num++ +'. ':'')+tab[2];
			alt = (alt?'':' alt');
			img = 0;
		}
		k = 2;
		while (k--) post =
			'<p'+(k?' class="r"':'')+'>'
		+		tab[k]
		+	'</p>'+post;

		thread += '<div class="post'
		+	(num?' p'+(l?' res':''):'')
		+	alt
		+	'">'
		+		post
		+	'</div>';
	}
	if (thread) {
		j = '', (p = p.parentNode).innerHTML = thread;
	//* header, links up:
		if ((k = gn('header')).length) j = (k = k[0]).innerHTML;		//* <- legacy
		else {
			i = (k = gn('link')).length;
			while (i--) if ((l = k[i]) && (h = l.getAttribute('href'))) {
				if (l.rel == 'index') {j = h; break;} else		//* <- explicit link given
				if ((m = h.lastIndexOf('/')) >= 0) j = h.substr(0,m);	//* <- or guess
			}
			k = cre('header', m = document.body, m.firstChild);
		}

		l = '', k.className = 'a'+(touch?' touch':'');
		for (i in (j = {
			'&#8662;': j || '../..'						//* <- or a hand-wave
		,	'&#8679;': '.'
		,	'&#8596;': 'javascript:fit()'
		,	'&#9636;': 'javascript:meta()'
		})) l += '<u><a href="'+j[i]+'">'+i+'</a></u>'
		k.innerHTML = '<u>'+l+'</u>';

		i = parseInt((k = id('task')).textContent);
		document.title = room+' - '+i+(timeRange?', '+timeRange.join(' - '):''), j = (i--)+1;
	//* top/bottom bar, links to prev/next:
		m = cre('div', p.parentNode, p.nextElementSibling);
		if (h = p.className) m.className = h;
		m.innerHTML = k.innerHTML =
			'<div class="task">'
		+		'<p class="arr">'
		+			'<a href="'+(i?i+'.htm':'.')+'">&#8678; '+(i?i:'')+'</a>'
		+			'<u class="r">'
		+				'<a href="'+j+'.htm">'+j+' &#8680;</a>'
		+			'</u>'
		+		'</p>'
		+	'</div>';
	}
}
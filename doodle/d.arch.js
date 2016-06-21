var	m = gn('meta'), p = gn('pre'), wsp = /^\s+|\s+$/g
,	d = document.body.style, w = [d.maxWidth||'1000px', '690px'], touch = ('ontouchstart' in document.documentElement)
,	v = location.href.match(/\/([^\/]+)\/[^\/]*$/), lastDir = (v ? v[1] : document.title), laa, lang = document.documentElement.lang || 'en';
if (lang == 'ru') laa = {
	page: 'Страница'
}; else laa = {
	page: 'Page'
};
for (i in m) if (m[i].name == 'viewport') {v = m[i]; break;}
if (p.length) showArch(p[0]);
if (touch) fit(), meta(' {font-size: 56pt; line-height: 56pt; text-decoration: none;}');	//* <- big buttons for touch screen

function gn(n,d) {return (d?d:document).getElementsByTagName(n);}
function id(i) {return document.getElementById(i);}
function fit() {v.content = 'width='+(d.maxWidth = w[d.maxWidth != w[1]?1:0]).replace(/\D+$/,'');}
function meta(b) {
var	s = 'style', h = gn('header')[0], e = gn(s, h);
	if (e.length) {
		e[1].innerHTML = (e[1].innerHTML?'':'.post p {display: none;}');
		if (b) e[0].innerHTML = 'header a, .task a'+b;
	} else 	if (b) h.appendChild(document.createElement(s)).innerHTML = '#pages'+b;
}
function showArch(p) {
var	i, j, k, l, m, n = '\n', s = ' ', t = '	', thread = '', thread_num, alt = 1, img = 1, num = 1
,	line = p.innerHTML.split(n), a = /^[\d\s]/.test(p.textContent), b = n+t, c = b+t, d = c+t, e = d+t, t0, t1;
	for (i in line) if (!a && line[i][0] == t) thread_num = line[i].slice(1), alt = (alt?'':' alt');
	else if (line[i].indexOf(t) > 0) {
	var	tab = line[i].split(t), post = '<br>';
		if (thread_num) tab[0] = '<a href="'+thread_num+'.htm">'+tab[0]+'</a>';
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
					tab[3] = m[1]+', '+m[4];
				}
				post =
e+tab[2].replace('<a ', '<a class="u" target="_blank" ').replace(/(".*\/([^\/"]*)")>/, '$1 alt="$2, '+tab[3]+'" title="'+tab[3]+'">');
			}
			if (a && img) alt = (alt?'':' alt');
			img = 1;
		} else {
			if (tab.length > 2) post =
e+((a && num && img)?num++ +'. ':'')+tab[2];
			if (a) alt = (alt?'':' alt');
			img = 0;
		}
		if (!t0) t0 = tab[0]; t1 = tab[0];
		if (post.indexOf(k = '>;') > 0) {
			j = post.split(k);
			l = j.shift();
			k = j.join(k).replace('*', 'x');
			post = l.replace(/( title="[^"]+)"/i, '$1, '+k+'"')+'>';
			tab[0] +=
'<br>'+post.replace(/<img [^>]+>/i, k);
		} else l = 0;
		if (!a) post = e+'<a href="#N">N.</a> '.replace(/N/g, num)+post;
		k = 2; while (k--) post =
e+'<p'+(k?' class="r"':'')+'>'+tab[k]+'</p>'+post;
		if (!num) post =
e+'<div class="center">'+post+
e+'</div>';
		thread +=
d+'<div class="post'+(num?' p'+(l?' res':''):'')+alt+(a?'':'" id="'+num++)+'">'+post+
d+'</div>';
	}
	p = p.parentNode;
	if (thread) p.innerHTML = thread;
	if (thread && a) {
		if ((k = gn(l = 'header')).length) i = (k = k[0]).innerHTML;	//* <- link to /d/ root given
		else i = '../..', (m = document.body).insertBefore(k = document.createElement(l), m.firstChild);	//* <- or guess
		k.className = 'a';
		k.innerHTML =
c+'<u><u>'+
d+'<style></style>'+
d+'<style></style>'+
d+'<a href="'+i+'">&#8662;</a>'+
c+'</u><u>'+
d+'<a href=".">&#8679;</a>'+
c+'</u><u>'+
d+'<a href="javascript:fit()">&#8596;</a>'+
c+'</u><u>'+
d+'<a href="javascript:meta()">&#9636;</a>'+
c+'</u></u>';
		i = parseInt((k = id('task')).textContent);
		k.innerHTML =
c+'<div class="task">'+
d+'<p class="arr">'+
e+'<a href="'+(i-1)+'.htm">&#8678; '+(i-1)+'</a><u class="r">'+
e+'<a href="'+(i+1)+'.htm">'+(i+1)+' &#8680;</a></u>'+
d+'</p>'+
c+'</div>';
		document.title = lastDir+' - '+i+', '+t0+' - '+t1;
		document.write(
b+'<div class="thread">'+k.innerHTML+
b+'</div>'/*+
b+'<footer>'+
c+'<p class="r hint"><abbr title="CCC, copycat conscious">&copy;</abbr> 2013, iidev</p>'+
b+'</footer>'*/);
	} else
	if ((k = line[0].split('|')).length > 2) {
		if (!thread) p.parentNode.removeChild(p);
		t0 = document.title;
		g = {	th: k[0]
		,	max: parseInt(k[1])
		,	pp: (touch && !k[3])? 20: parseInt(k[2])	//* <- global var: image prefix, max count, per page, etc
		,	min: k[3]? parseInt(k[3]): 0
		,	p: t0.slice(0, l = t0.lastIndexOf(l = (k = id('task')).textContent.replace(wsp, ''))+l.length)
		,	t: t0.slice(l)					//* <- "Archive <room>. Etc" title parts
		};
		j = (l = location.href.replace(/#.*$/, '')).indexOf('?');
		if (j > 0) {
			l = l.slice(j+1);
			j = l.indexOf('=');
		} else k.innerHTML += (!g.min && g.max > g.pp ?
d+'<p id="pages">'+
d+'</p>' : '<p>'+laa.page+': 1</p>');
		p = gn('p', k);
		p[0].innerHTML +=
'<span id="range"></span>';
		if (j < 0) k.innerHTML +=
c+'<div id="thumbs">'+
c+'</div>', page(1);
		if ((i = gn('input', k)).length) {
			if (j > 0 && p.length > 2) document.title += '. '+p[2].textContent;
			g.f = {i:i = i[0], p:p = p[1], n:i.name.split(','), t:p.textContent.split(',')};
			findBy(j > 0 ? l.slice(0, j) : 0);
			if (j = id('r')) {
				j.href='javascript:resetSearch()';
				window.addEventListener('DOMContentLoaded', resetSearch, false);
			}
		}
	}
}

function resetSearch() {
var	a = id('r'), i = gn('input');
	if (a && i) {
		(i = i[0]).value = a.textContent;
		if (a = i.onkeyup) a();
	}
}

function page(p) {
	if (!p) {g.order = (g.order?0:1); return page(1);}	//* <- 0: last to 1st, 1: ascend
	g.page = p;
	g.pt = g.tt = '';
	g.fst = g.last = 0;
var	i = g.pp, j = g.max, k = (g.page-1)*i;

	function th(i) {
		if (i>g.min) (g.fst?0:g.fst = i), g.last = i, g.tt +=
'<a class="op a" href="'+
i+'.htm"><b><b><img src="'+g.th+
i+'.png" alt="'+
i+'"></b></b></a>';						//* <- b: table; for vertical-align
	}
	if (g.order) for (i += ++k; k<=j && k<i; k++) th(k);	//* <- from k up to k+pp
	else for (j -= k+i; i; i--) th(j+i);			//* <- last pp threads, count down

	document.title = g.p+(k = ', '+g.fst+'-'+g.last)+g.t;	//* <- currently shown threads
	if (j = id('range')) j.innerHTML = ': '+lastDir+k;
	if (j = id('thumbs')) j.innerHTML = g.tt;
	if (j = id('pages')) {
		k = Math.ceil(g.max/g.pp);
		for (i = 0; i<=k; i++) p = (i?i:(g.order?'&r':'&l')+'aquo;'), g.pt +=
'\n'+(g.page==i?p:'<a href="javascript:page('+i+')">'+p+'</a>');
		j.innerHTML = (touch?'':laa.page+': ')+g.pt;
	}
}

function findBy(f) {
var	j = [];
	if (g.f.n.indexOf(f) < 0) f = g.f.n[0];
	for (i in g.f.n) j.push(f == g.f.n[i] ? g.f.t[i] : '<a href="javascript:findBy(\''+g.f.n[i]+'\')">'+g.f.t[i]+'</a>');
	g.f.p.innerHTML = j.join(', ');
	g.f.i.name = f;
}
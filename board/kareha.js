//* -------- functions: --------

function id(i) {return document.getElementById(i);}
function is_ie() {return document.all && !document.opera;}
function gn(n,p) {return (p || document).getElementsByTagName(n);}
function show(i) {
var	style = i.style || id(i).style
,	n = 'none'
	;
	style.display = (style.display!=n?n:'');
}

function cre(e,p,b) {
	e = document.createElement(e);
	if (b) p.insertBefore(e, b); else
	if (p) p.appendChild(e);
	return e;
}

function reply_insert(text,thread) {
var	i = id('show_postform')
,	t = id('postform'+thread).comment
	;
	if (i && i.style.display != 'none') gn('a',i)[0].click();
	if (t) {
		if (t.createTextRange && (i = t.caretPos)) {// IE
			i.text = (i.text.charAt(i.text.length-1) == ' ' ? text+' ' : text);
		} else
		if (t.setSelectionRange) {// Firefox
		var	start = t.selectionStart
		,	end = t.selectionEnd
			;
			t.value = t.value.substr(0,start)+text+t.value.substr(end);
			t.setSelectionRange(start+text.length,start+text.length);
		} else {
			t.value += text+' ';
		}
		if ('activeElement' in document) i = document.activeElement; else
		if ('querySelector' in document) i = document.querySelector(':focus'); else i = null;
		if (t !== i) {
			document.body.firstElementChild.scrollIntoView(false);
			t.focus();
		}
	}
}

function insert_reply(text,link) {
	if (document.body.className == 'mainpage') document.location = link+hash+text;
	else reply_insert(text,'');
}

function size_field(i,rows) {
	id(i).comment.setAttribute('rows',rows);
}

function delete_post(thread,post,file) {
	if (confirm('Are you sure you want to delete reply '+post+'?')) {
	var	fileonly = false
	,	script = document.forms[0].action
	,	password = document.forms[0].password.value
		;
		if (file) fileonly = confirm('Leave the reply text and delete the only file?');

		document.location = script
		+	'?task=delete'
		+	'&delete='+thread+','+post
		+	'&password='+password
		+	'&fileonly='+(fileonly?1:0);
	}
}

function preview_post(formId,thread) {
var	form = id(formId)
,	preview = id('preview'+thread)
	;

	if (!form || !preview) return;

	preview.style.display = '';
	preview.innerHTML = '<em>Loading...</em>';

var	text = 'task=preview'
	+	'&comment='+encodeURIComponent(form.comment.value)
	+	'&markup='+encodeURIComponent(form.markup.value)
	;
	if (thread) text += '&thread='+thread;

var	x = get_xmlhttp();
	x.open('POST', self);
	x.onreadystatechange = function() {
		if (x.readyState == 4) preview.innerHTML = x.responseText;
	}
	if (is_ie() || x.setRequestHeader) x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	x.send(text);
}

function get_xmlhttp() {
var	x = null;
	if (typeof ActiveXObject !== 'undefined') {
		try {
			x = new ActiveXObject('Msxml2.XMLHTTP');
		} catch(e) {
			try {
				x = new ActiveXObject('Microsoft.XMLHTTP');
			} catch(f) {
				x = null;
			}
		}
	}
	if (!x && typeof XMLHttpRequest !== 'undefined') x = new XMLHttpRequest();
	return x;
}

function set_new_inputs(i) {
	if (i) i = id(i);
	if (!i || !i.link) return;
var	e;
	if ((e = i.field_a) && !e.value) e.value = get_cookie('name');
	if ((e = i.field_b) && !e.value) e.value = get_cookie('link');
	if ((e = i.password) && !e.value) e.value = get_password('password');
	if (e = i.save_useragent) e.checked = !!get_cookie('save_useragent');
	if (e = i.markup) {
		if ((i = i.comment) && !i.value) e.value = get_cookie('markup');
		select_markup(e);
	}
}

function set_delpass(i) {
	with(id(i)) password.value = get_cookie('password');
}

function make_password() {
var	chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
,	pass = ''
,	i = 8
,	j = chars.length
	;
	while (i--) pass += chars[Math.floor(Math.random()*j)];
	return pass;
}

function get_password(name) {
var	pass = get_cookie(name);
	if (pass) return pass;
	return make_password();
}

function select_markup(s) {
	if (m = window.markup_descriptions) {
	var	m,e = s;
		while (e = e.nextSibling) if (e.nodeName.toLowerCase() == 'small') break;
		if (e) e.innerHTML = m[s.value];
	}
}

function get_cookie(name) {
	with(document.cookie) {
	var	regexp = new RegExp('(^|;\\s+)'+name+'=(.*?)(;|$)')
	,	hit = regexp.exec(document.cookie)
		;
		if (hit && hit.length > 2) return unescape(hit[2]);
		else return '';
	}
};

function set_cookie(name,value,days) {
var	x = '';
	if (days) {
	var	date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		x = '; expires='+date.toGMTString();
	}
	document.cookie = name+'='+value+x+'; path=/';
}

function rt(e) {
	return e ? {
		r: e.getAttribute('rel')
	,	t: e.getAttribute('title')
	} : {};
}

function set_stylesheet(styletitle) {
var	a = gn('link'), i = a.length, r, found = false;
	while (i--) if ((r = rt(a[i])).t && r.r.indexOf('style') >= 0) {
		a[i].disabled = true; // IE needs this to work. IE needs to die.
		if (styletitle == r.t) a[i].disabled = !(found = true);
	}
	if (!found) set_preferred_stylesheet();
}

function set_preferred_stylesheet() {
var	a = gn('link'), i = a.length, r;
	while (i--) if ((r = rt(a[i])).t && r.r.indexOf('style') >= 0) a[i].disabled = (r.r.indexOf('alt') >= 0);
}

function get_active_stylesheet() {
var	a = gn('link'), i = a.length, r;
	while (i--) if (!a[i].disabled && (r = rt(a[i])).t && r.r.indexOf('style') >= 0) return r.t;
}

function get_preferred_stylesheet() {
var	a = gn('link'), i = a.length, r;
	while (i--) if ((r = rt(a[i])).t && r.r.indexOf('style') >= 0 && r.r.indexOf('alt') < 0) return r.t;
	return null;
}

//* -------- runtime: --------

window.onunload = function(e) {
	if (style_cookie) set_cookie(style_cookie,get_active_stylesheet(),365);
}

window.onload = function(e) {
var	a = gn('p')
,	i = a.length
,	h = location.hash
,	r = /\babbrev\b/i
,	t,b,c
	;
	while (i--) if (
		(d = a[i])
	&&	(c = d.className) && r.test(c)
	&&	(t = gn('td', d.previousElementSibling))
	&&	(c = t.length)
	) {
		t[c-1].appendChild(d);
	}
	if (id('de-pform')) return;
	if (!id('postform') && (i = gn('hr')) && (i = i[1])) {
		i.previousElementSibling.innerHTML = postform_fallback;
	}
var	i = gn('select')
,	a = {
		postform: set_new_inputs
	,	delform: set_delpass
	}
,	d = document.body
	;
	if (d.getAttribute('style')) d.setAttribute('style', '');
	if (i.length) i[0].value = get_active_stylesheet();
	for (i in a) if (id(i)) a[i](i);

	if (i = id('postform')) {
		if (
			!i.comment.value
		&&	h && h.slice(0, c = hash.length) == hash
		&&	(c = h.slice(c))
		) {
			try {
				reply_insert(unescape(c),'');
			} catch(e) {
				reply_insert(c,'');
			}
		} else c = 0;
		if (t = id('index-form-header') || id('reply-form-header')) {
		var	a = ' [<a href="javascript:show(\'postform\'),show(\'show_postform\');">'
		,	b = '</a>]'
			;
			d = cre('span',t);
			d.id = 'show_postform';
			d.innerHTML = a+(t.id[0] == 'r'?'Write a reply':'Start a new thread')+b;
			show(h || c?d:i);

			d = cre('div',gn('tr',i)[0].lastElementChild);
			d.className = 'postform-close';
			d.innerHTML = a+'x'+b;
		}
	}
}

if (style_cookie) set_stylesheet(get_cookie(style_cookie)||get_preferred_stylesheet());

var	captcha_key = make_password()
,	hash = '#i'
,	i = id('postform')
,	postform_fallback = (i?i.innerHTML:'')
	|| (
		'<table><tr><td><ul>'+
		'<li>EN: If post form is not found here, try to disable your extensions/userscripts for this site.</li>'+
		'<li>RU: Если нет формы отправки поста, отключите убравшие её расширения (например Куклоскрипт).</li>'+
		'</ul></td></tr></table>'
	);

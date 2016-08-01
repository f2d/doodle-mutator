//* -------- functions: --------

function id(i) {return document.getElementById(i);}
function is_ie() {return document.all && !document.opera;}
function gn(n,p) {return (p||document).getElementsByTagName(n);}
function show(i) {
var	style = i.style || id(i).style, n = 'none';
	style.display = (style.display!=n?n:'');
}

function cre(e,p,b) {
	e = document.createElement(e);
	if (b) p.insertBefore(e, b); else
	if (p) p.appendChild(e);
	return e;
}

function insert(text,thread) {
var	i = id('show_postform'), t = id('postform'+thread).comment;
	if (i && i.style.display != 'none') gn('a',i)[0].click();
	if (t) {
		if (t.createTextRange && t.caretPos) {// IE
		var	caretPos = t.caretPos;
			caretPos.text = caretPos.text.charAt(caretPos.text.length-1) == ' '?text+' ':text;
		} else
		if (t.setSelectionRange) {// Firefox
		var	start = t.selectionStart, end = t.selectionEnd;
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

function w_insert(text,link) {
	if (document.body.className == 'mainpage') document.location = link+hash+text;
	else insert(text,'');
}

function size_field(i,rows) {
	id(i).comment.setAttribute('rows',rows);
}

function delete_post(thread,post,file) {
	if (confirm('Are you sure you want to delete reply '+post+'?')) {
	var	fileonly = false, script = document.forms[0].action, password = document.forms[0].password.value;

		if (file) fileonly = confirm('Leave the reply text and delete the only file?');

		document.location = script
		+	'?task=delete'
		+	'&delete='+thread+','+post
		+	'&password='+password
		+	'&fileonly='+(fileonly?'1':'0');
	}
}

function preview_post(formId,thread) {
var	form = id(formId), preview = id('preview'+thread);

	if (!form||!preview) return;

	preview.style.display = '';
	preview.innerHTML = '<em>Loading...</em>';

var	text;
	text = 'task=preview';
	text += '&comment='+encodeURIComponent(form.comment.value);
	text += '&markup='+encodeURIComponent(form.markup.value);
	if (thread) text += '&thread='+thread;

var	xmlhttp = get_xmlhttp();
	xmlhttp.open('POST',self);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) preview.innerHTML = xmlhttp.responseText;
	}
	if (is_ie()||xmlhttp.setRequestHeader) xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xmlhttp.send(text);
}

function get_xmlhttp() {
var	xmlhttp;
	try {
		xmlhttp = new ActiveXObject('Msxml2.XMLHTTP');
	} catch(e) {
		try { xmlhttp = new ActiveXObject('Microsoft.XMLHTTP'); }
		catch(f) { xmlhttp = null; }
	}

	if (!xmlhttp && typeof XMLHttpRequest!='undefined') xmlhttp = new XMLHttpRequest();

	return xmlhttp;
}

function set_new_inputs(i) {
var	el = id(i);
	if (!el||!el.link) return;

	if (!el.field_a.value) el.field_a.value = get_cookie('name');
	if (!el.field_b.value) el.field_b.value = get_cookie('link');
	if (!el.password.value) el.password.value = get_password('password');
	if (el.markup && !el.comment.value) el.markup.value = get_cookie('markup');
	select_markup(el.markup);
}

function set_delpass(i) {
	with(id(i)) password.value = get_cookie('password');
}

function make_password() {
var	i = 8, r, pass = '', chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	while (i--) pass += chars.substring(r = Math.floor(Math.random()*chars.length), r+1);
	return pass;
}

function get_password(name) {
var	pass = get_cookie(name);
	if (pass) return pass;
	return make_password();
}

function select_markup(sel) {
	if (!window.markup_descriptions) return;

var	el = sel;
	while (el = el.nextSibling) if (el.nodeName.toLowerCase() == 'small') break;

	if (el) el.innerHTML = markup_descriptions[sel.value];
}

function get_cookie(name) {
	with(document.cookie) {
	var	regexp = new RegExp('(^|;\\s+)'+name+'=(.*?)(;|$)'), hit = regexp.exec(document.cookie);
		if (hit && hit.length > 2) return unescape(hit[2]);
		else return '';
	}
};

function set_cookie(name,value,days) {
	if (days) {
	var	date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
	var	expires = '; expires='+date.toGMTString();
	}
	else expires = '';
	document.cookie = name+'='+value+expires+'; path=/';
}

function rt(e) {return {r:e.getAttribute('rel'), t:e.getAttribute('title')};}

function set_stylesheet(styletitle) {
var	a = gn('link'), i = a.length, r, found = false;
	while (i--) if ((r = rt(a[i])).t && r.r.indexOf('style')>=0) {
		a[i].disabled = true; // IE needs this to work. IE needs to die.
		if (styletitle == r.t) a[i].disabled = !(found = true);
	}
	if (!found) set_preferred_stylesheet();
}

function set_preferred_stylesheet() {
var	a = gn('link'), i = a.length, r;
	while (i--) if ((r = rt(a[i])).t && r.r.indexOf('style')>=0) a[i].disabled = (r.r.indexOf('alt')>=0);
}

function get_active_stylesheet() {
var	a = gn('link'), i = a.length, r;
	while (i--) if (!a[i].disabled && (r = rt(a[i])).t && r.r.indexOf('style')>=0) return r.t;
}

function get_preferred_stylesheet() {
var	a = gn('link'), i = a.length, r;
	while (i--) if ((r = rt(a[i])).t && r.r.indexOf('style')>=0 && r.r.indexOf('alt')<0) return r.t;
	return null;
}

//* -------- runtime: --------

window.onunload = function(e) {
	if (style_cookie) set_cookie(style_cookie,get_active_stylesheet(),365);
}

window.onload = function(e) {
var	a = gn('p'), i = a.length, r = /\babbrev\b/i, t,b,c,d;
	while (i--) if ((d = a[i]).className && r.test(d.className)) {
		t = gn('td', d.previousElementSibling);
		t[t.length-1].appendChild(d);
	}
	if (!id('postform')) {
		gn('hr')[1].previousElementSibling.innerHTML = postform_fallback;
	}
	i = gn('select'), a = {postform: set_new_inputs, delform: set_delpass}, d = document.body;
	if (d.getAttribute('style')) d.setAttribute('style', '');
	if (i.length) i[0].value = get_active_stylesheet();
	for (i in a) if (id(i)) a[i](i);

	if (i = id('postform')) {
		if (!i.comment.value && (a = location.hash) && a.slice(0, c = hash.length) == hash) {
			c = a.slice(c);
			try {
				insert(unescape(c),'');
			} catch(e) {
				insert(c,'');
			}
		} else c = 0;
		if (t = id('index-form-header') || id('reply-form-header')) {
			if (!c) show(i);
			a = ' [<a href="javascript:show(postform),show(show_postform);">', b = '</a>]';

			d = cre('span',t);
			d.id = 'show_postform';
			d.innerHTML = a+(t.id[0] == 'r'?'Write a reply':'Start a new thread')+b;
			if (c) show(d);

			d = cre('div',gn('tr',i)[0].lastElementChild);
			d.style.cssFloat = 'right';
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

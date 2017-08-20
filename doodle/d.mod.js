var	bnw = bnw || [];

bnw.push(bnw.menu = function menuInit(i) {
	if (i) {
	var	k = id('tabs')
	,	d = param.day_link || ''
		;
		if (d && !k && (k = id('task'))) k = k.firstElementChild;
	}
	if (k) {
	var	a = function (i,t,d) {return '<a href="'+(d || '')+i+(i == day?(at = y, '" class="at'):'')+'">'+t+'</a>';}
	,	h = ''
	,	n = k.textContent.replace(regTrim, '').split('|')
	,	day = k.getAttribute('data-day') || location.search.split('=').slice(-1)[0] || location.pathname.split('/').slice(-1)[0]
	,	prefix = d || k.getAttribute('data-var') || ''
		;
//* task category tabs:
		if (n.length > 1) {
			for (i in n) h += (h?'\n|\t':'')+a(+i+1, n[i]);
			k.innerHTML = '[\t'+h+'\t]';
		}
	var	p = k.parentNode
	,	r = /^\d+-\d+-\d+(,\d+)*/
	,	w = /\s.*$/
		;
		while (
			(m = p.lastElementChild)
		&&	!((j = m.lastElementChild) && j.id)
		&&	r.test(j = m.innerHTML.replace(regTrim, ''))
		) {
//* logs, row = month, column = day:
		var	at,c
		,	j = j.split('-')
		,	f = j[0]
		,	y = 'year'+f
		,	n = j.pop().split(',')
		,	j = j.join('-')
		,	h = function (v) {return a(j+'-'+v.replace(w, ''), v, prefix);}
			;
			m.innerHTML = j+': '+n.map(h).join(' ');
			if (!d || d.id != y) {
				(d = cre('div',c?c:c = cre('div',p,k.nextElementSibling))).id = y;
				if (d.previousElementSibling) {
					d.className = 'hid';
					cre('p',c,d).innerHTML = getToggleButtonHTML(f);
				}
			}
			d.appendChild(m);
		}
		if (at && (d = id(at)) && (d = d.previousElementSibling) && (d = gn('a',d)[0])) d.click();
	}
//* god|mod:
	if (flag && (flag.g || flag.m)) {
	var	a = gn('p')
	,	i = a.length
		;
		while (i--) if ((p = a[i]).id && p.id.slice(0,2) == 'm_') {
			menuOpenOnClick(p);
			if (flag.v) p.click();
			if ((p = getParentBeforeClass(p, 'content')) && !regTagForm.test(p.tagName)) {
			var	n = p.nextElementSibling
			,	d = +new Date()
			,	f = cre('form', p.parentNode, p)
				;
				f.method = 'post';
				f.innerHTML = '<input type="hidden" name="mod" value="'+d+'">';
				f.appendChild(p);
				if (n && regTagPre.test(n.tagName)) f.appendChild(n);
			}
		}
	}
});

function menuOpenOnClick(p) {
	p.setAttribute('onclick', 'menuOpen(this)');
}

function menuOpen(p) {
var	n = 'menu_'+p.id
,	m = id(n)
	;
	if (!m) {
		p.removeAttribute('onclick');
	var	leftSide = (n.slice(-1) == 0)
	,	d = p.parentNode
	,	g = flag.g
	,	u = flag.u
	,	v = flag.v
	,	ngm = (!g && flag.n)
	,	la
		;
		if (lang == 'ru') la = {
			tip: (
				leftSide ? [
					'Применение заданных операций, по пунктам снизу вверх, разом со всей комнаты.'
				,	'Удалить пост и файл - 2 разных пункта.'
				,	'Удаление файла без стирания переместит его в подпапку для мусора.'
				,	'На 1 строке сработает только 1 пункт.'
				,	'Рекомендуется заморозка и действия по шагам.'
				,	'Также рекомендуется вмешиваться пореже.'
				] : [
					'Легенда окраски пользователей:'
				,	'Тёплый: обычный,'
				,	'Красный: запрет доступа (бан),'
				,	'Серый: запрет сообщения,'
				,	'Лёд: модератор комнаты,'
				,	'Пурпур: супермодератор,'
				,	'Зелёный: ваш.'
				,	'Модератор не может менять статус супермодератора, банить модераторов или снимать с себя полномочия.'
				]
			)
		,	go: 'пуск'
		,	r: (g?'Добавить сообщение':'Сообщить о проблеме')
		,	t: 'Ваш текст тут.'
		,	i: 'Новый текст поста / имя / объявление.'
		,	x: 'Закрыть и забыть это меню.'
		,	z: 'Закрыть и забыть все меню на странице.'
		,	o: (
				leftSide ? (
					ngm?'':(
					(v?'':'в архив+готово+нет|замороз.нить+отм.'+(g?'+скрыть':'')+'|удалить сообщения||уд.нить'
				+	(g?'+и файлы+и стереть файлы с диска':'')+'|удалить пост (но не файл)|уд.файл+обнулить'
				+	(g?'+стереть с диска':'')+'|доб.пост+перед+изменить||слить нити сюда+отсюда вниз|разделить нить отсюда вниз'+(g?'|':''))
				+	(g?'уд.комн.+и стер.файлы+и архив и метаданные|переимен.комн.'+(v?'':'+копир.нить в комнату'):'')
				)) : (
					ngm?'комнатное объявление':(
					(v?'':'закрыть доступ+открыть|может жаловаться+нет|'
				+	(g?'получает цели+нет|видит неизв.+нет|':'')+'дать модератора+снять|'
				+	(g?'дать супермод.+снять|переименовать||':''))
				+	(g?'общее объявление'+(u?'':'|комнатное объявление|замороз. комнату+отм.')+'|заморозить всё+отм.'
					:'||комнатное объявление')
				))
			)
		}; else la = {
			tip: (
				leftSide ? [
					'Apply changes on entire room (options go last to first).'
				,	'Preferably avoid batch submits, use freeze.'
				,	'Deleting post with file requires 2 checkboxes.'
				,	'Deleting pic without erasing will move it to trash subfolder.'
				,	'Only 1 checkbox per line will work.'
				,	'Also, do not ruin the game modifying too much.'
				] : [
					'Userbar color legend:'
				,	'Warm: default,'
				,	'Red: no access (ban),'
				,	'Gray: no reports,'
				,	'Ice: room mod,'
				,	'Purple: super mod,'
				,	'Green: you.'
				,	'Moderator cannot change status of a supermoderator, ban other moderators or self-resign.'
				]
			)
		,	go: 'go'
		,	r: (g?'Add a comment':'Report a problem')
		,	t: 'Your text here.'
		,	i: 'New post content / name / announcement.'
		,	x: 'Close and forget this menu.'
		,	z: 'Close and forget all menus on the page.'
		};
	var	o = (
			leftSide ? (
				ngm?'':(
				(v?'':'archive+ready+wait|freeze trd.+warm up'+(g?'+hide':'')+'|delete comments||delete thread'
			+	(g?'+& pics+& erase pics from disk':'')+'|delete post (but not pic)|delete pic+nullify'
			+	(g?'+erase from disk':'')+'|add post+before+edit||merge trd. target+source down from here|split thread down from here'+(g?'|':''))
			+	(g?'nuke room+& er.pics+& archive and metadata|rename room'+(v?'':'+copy trd. to room'):'')
			)) : (
				ngm?'room announce':(
				(v?'':'ban+lift|can report+not|'
			+	(g?'gets targets+not|sees unknown+not|':'')+'give mod+take|'
			+	(g?'give god+take|rename||':''))
			+	(g?'global announce'+(u?'':'|room announce|room freeze+warm up')+'|global freeze+warm up'
				:'||room announce')
			))
		).split('|')
//* warning message dialogs:
	,	check = {
//* for each functionality type:
			confirm: {
//* get first phrase ID, where button fits any criteria from array:
				erase_trd: [/\berase pics\b/]
			,	erase_pic: [/\bnullify\b/, /\berase\b/]
			,	wipe_all: [/^.+archive\b/]
			,	wipe_active: [/^nuke room/]
			,	rename: ['rename room']
			}
		,	textarea: {
				require: [/^add post/, /^rename/]
			,	enable: [/ announce$/, / freeze$/]
			}
		}
	,	a = (la.o?la.o.split('|'):o)
	,	m = ''
		;

		function getCheckList(buttonID) {
		var	criteria, j,k,r = {};
		type:	for (j in check)
		phrase:	for (k in check[j]) {
			var	a = check[j][k]
			,	i = a.length
				;
		fit:		while (i--) if (
					(criteria = a[i]).test
					? criteria.test(buttonID)
					: (criteria === buttonID)
				) {
					r[j] = k;
					break phrase;
				}
			}
			return r;
		}

		function joinCheckList(a, before, between, after) {
		var	pad = [
				before || '" data-'
			,	between || '="'
			,	after || ''
			]
		,	i,r = ''
			;
			for (i in a) r += pad[0]+i+pad[1]+a[i]+pad[2];
			return r;
		}

		for (i in a) if (a[i]) {
		var	b = a[i].split('+')
		,	v = o[i].split('+')
		,	b0 = b.shift()
		,	v0 = v.shift()
		,	v1 = v0
		,	k = getCheckList(v0+'+')
		,	l = '" value="'
		,	c = '<input type="checkbox" onChange="menuRowCheck(this)" name="'
			+	p.id.replace('m', 'm'+i)
			;
			m += '<div class="row">';
			for (j in b) {
				v1 += '+'+v[j];
			var	checkList = getCheckList(v1) || k;
				b[j] = '</label><label title="'+b[j]+joinCheckList(checkList, '\r\n - ', ': ')+'">'
				+	(leftSide?'':b[j])
				+	c
				+	joinCheckList(checkList)
				+	l+v1
				+	'">'
				+	(leftSide?b[j]:'');
			}
			checkList = getCheckList(v0) || k;
			m += '<label title="'+b0+joinCheckList(checkList, '\r\n - ', ': ')+'">'
			+(b0
				?	(leftSide?'':b0)
				+	c
				+	joinCheckList(checkList)
				+	l+v0
				+	'">'
				+	(leftSide?b0:'')
				:''
			)+b.join('')+'</label>';
			m += '</div>';
		} else {
			m += '</div><div class="block">';
		}
	var	b = '<input type="button" value="'
	,	c = '" onClick="eventStop(event); menuClose('
	,	i = '" title="'
	,	j = p.id.split('_').slice(1).join('-')
	,	t = la.tip.join('\r\n')
	,	i = '<div title="'+t+'">'
		+	(ngm || u || v?'':
			'<div class="block">'
		+		'[ <a href="javascript:void window.open(\''+j+'\',\'Report\',\'width=656,height=280\')">'+la.r+'</a> ]'
		+	'</div>'
			)
		+	'<div class="block">'+m+'</div>'
		+'</div>'
		+'<textarea name="'+p.id.replace('m', 't')+i+la.i+'" placeholder="'+la.t+'"></textarea>'
		+'<div>'
		+	'<input type="submit" value="'+la.go+i+t+'">&ensp;'
		+	b+'x'+i+la.x+c+'this)">'
		+	b+'&gt;&lt;'+i+la.z+c+')">'
		+'</div>'
		;
		m = cre('div', p);
		m.className = 'mod-menu';
		m.id = n;
		m.innerHTML = i;
		menuRowCheck(m);
	}
}

function menuRowCheck(target) {
var	e = target
,	k = e.checked
	;
	while (!(d = e).id && (e = e.parentNode));
var	opening = (d == target);
	if (k) {
		e = target;
		if (!e.getAttribute('data-textarea')) k = 0;
		do {if (e = e.parentNode) a = e;} while (!regTagDivP.test(e.tagName));
		a = gi('checkbox', a), i = a.length;
		while (i--) if ((e = a[i]) != target) e.checked = 0;
	} else if (opening) k = 1;
var	count = {checked: 0, text: 0, req: 0}
,	a = gi('checkbox', d)
,	i = a.length
,	j = []
,	la
	;
	if (lang == 'ru') la = {
		wipe_active: 'Всё активное содержимое комнаты будет уничтожено, восстановление невозможно.'
	,	wipe_all: 'Комната будет уничтожена, восстановление невозможно.'
	,	erase_pic: 'Выбранные рисунки будут уничтожены, восстановление невозможно.'
	,	erase_trd: 'В нитях, выбранных к удалению, все рисунки будут уничтожены, восстановление невозможно.'
	,	rename: 'Комната сменит адрес, возможны конфликты данных.'
	,	sure: 'Вы уверены?'
	}; else la = {
		wipe_active: 'All active content of the room will be deleted, this cannot be reverted.'
	,	wipe_all: 'The room will be deleted, this cannot be reverted.'
	,	erase_pic: 'Selected images will be deleted, this cannot be reverted.'
	,	erase_trd: 'Images will be deleted from threads, selected for deletion, this cannot be reverted.'
	,	rename: 'The room will be renamed, this can possibly cause conflicts in data.'
	,	sure: 'Are you sure?'
	};
	while (i--) if ((e = a[i]) && e.checked) {
		count.checked++;
		if (t = e.getAttribute('data-confirm')) j.push(la[t]);
		if (t = e.getAttribute('data-textarea')) {
			if (t == 'enable') count.text++;
			if (t == 'require') count.req++;
		}
	}
	if ((a = gi('submit', d)).length && (i = a[0])) i.disabled = !count.checked; else i = 0;
	if ((a = gn('textarea', d)).length && (e = a[0])) {
	var	d = d.parentNode
	,	t = count.text || count.req
	,	focus = (k && t)
		;
		e.required = !!count.req;
		e.style.display = (t?'':'none');
		if (focus) {
		var	v = target.value;
			if (i && v) {
			var	leftSide = (target.name.slice(-1) == 0);
				if (v.indexOf('add post') == 0) {
					e.value = (
						v.indexOf('edit') < 0
						? ''
						: d.getAttribute('data-post')
					) || (
						(i = 'Re: ')+(
							e.value
						&&	(v = e.value.replace(regTrim, ''))
						&&	(
								v.indexOf(': ') < 0
							||	v.substr(0, i.length).toLowerCase() == i.toLowerCase()
							)
						&&	(v = v
								.replace(regSpace, ' ')
								.replace(new RegExp('^'+i+'*', 'i'), '')
							)
							? v
							: (lang == 'ru' ? 'Текст ответа тут.' : 'Reply text here.')
						)
					);
				} else
				if (v.indexOf('rename') == 0) {
					e.value = (
						leftSide
						? location.pathname.split('/').slice(-2)[0]
						: d.firstChild.textContent
					).replace(regTrim, '')
					|| (
						leftSide
						? (lang == 'ru' ? 'Имя комнаты назначения тут.' : 'Target room name here.')
						: (lang == 'ru' ? 'Новое имя пользователя тут.' : 'New user name here.')
					);
				} else
				if ((i = ['announce', 'freeze'].indexOf(v.split(' ').slice(-1)[0])) >= 0) {
					e.value = (
						(v = id(v) || id((v.indexOf('room') == 0?'room_':'') + ['anno', 'stop'][i]))
						? v.textContent
							.replace(regTrim, '')
							.replace(regSpace, ' ')
						: (lang == 'ru' ? 'Текст сообщения тут.' : 'Announce text here.')
					);
				}
			}
			e.focus();
		}
	}
	while (!regTagForm.test(e.tagName) && (e = e.parentNode));
	if (e) {
		if (j.length) j.push(la.sure), j = j.join('\n');
		else j = '';
		e.onsubmit = (j ? (function() { return confirm(j); }) : null);
	}
}

function menuClose(e) {
	if (e && e.tagName) {
	var	m = e.parentNode;
		while (!m.id) m = m.parentNode;
	var	p = m.parentNode;
		p.removeChild(m);
		menuOpenOnClick(p);
	} else {
	var	a = gi(), i = a.length;
		while (i--) if ((e = a[i]) && e.value == 'x') menuClose(e);
	}
}
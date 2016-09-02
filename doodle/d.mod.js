mm = function menuInit(i) {
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
}

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
		,	r: 'Сообщить о проблеме'
		,	t: 'Ваш текст тут.'
		,	i: 'Новый текст поста / имя / объявление.'
		,	x: 'Закрыть и забыть это меню.'
		,	z: 'Закрыть и забыть все меню на странице.'
		,	o: (
				leftSide ? (
					(flag.v?'':'в архив+готово+нет|замороз.нить+отм.'+(g?'+скрыть':'')+'|удалить сообщения||удалить нить'
				+	(g?'+файлы+стереть с диска':'')+'|удалить пост (но не файл)|уд.файл+обнулить'
				+	(g?'+стереть с диска':'')+'|доб.пост+перед+изменить||слить сюда+отсюда|разделить нить отсюда')
				+	(g?'|уд.комн.+файлов+архива|переназ.комн.'+(flag.v?'':'+коп.нить в'):'')
				) : (
					(flag.v?'':'закрыть доступ+открыть|может жаловаться+нет|'
				+	(g?'получает цели+нет|видит неизв.+нет|':'')+'дать модератора+снять|'
				+	(g?'дать супермод.+снять|переименовать||':''))
				+	(g?'общее объявление'+(flag.u?'':'|комнатное объявление|замороз. комнату+отм.')+'|заморозить всё+отм.'
					:'||комнатное объявление')
				)
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
		,	r: 'Report a problem'
		,	t: 'Your text here.'
		,	i: 'New post content / name / announcement.'
		,	x: 'Close and forget this menu.'
		,	z: 'Close and forget all menus on the page.'
		};
	var	o = (
			leftSide ? (
				(flag.v?'':'archive+ready+wait|freeze tr.+warm up'+(g?'+hide':'')+'|delete comments||delete thread'
			+	(g?'+pics+erase from disk':'')+'|delete post (but not pic)|delete pic+nullify'
			+	(g?'+erase from disk':'')+'|add post+before+edit||merge thread target+source|split thread from here')
			+	(g?'|nuke room+pics+arch|rename room'+(flag.v?'':'+copy trd to'):'')
			) : (
				(flag.v?'':'ban+lift|can report+not|'
			+	(g?'gets targets+not|sees unknown+not|':'')+'give mod+take|'
			+	(g?'give god+take|rename||':''))
			+	(g?'global announce'+(flag.u?'':'|room announce|room freeze+warm up')+'|global freeze+warm up'
				:'||room announce')
			)
		).split('|')
	,	check = {
			confirm: {
				erase: ['nuke room+']
			,	rename: ['rename room']
			}
		,	text: {
				require: ['add post+', 'rename', 'rename room+']
			,	enable: ['room announce', 'global announce', 'room freeze', 'global freeze']
			}
		}
	,	a = (la.o?la.o.split('|'):o)
	,	b,b0,v,v0,v1,c,i,j,n = '';

		function checkFeature(f) {
		var	i,j,r = '';
			for (i in check)
			for (j in check[i]) if (check[i][j].indexOf(f) >= 0) {r += '" data-'+i+'="'+j; break;}
			return r;
		}

		for (i in a) if (a[i]) {
			v1 =
			v0 = (v = o[i].split('+')).shift();
			b0 = (b = a[i].split('+')).shift();

			c = '<input type="checkbox" onChange="menuRowCheck(this)" name="'
			+	p.id.replace('m', 'm'+i)
			+	checkFeature(v0+'+')
			+	'" value="';

			n += '<div class="row">';
			for (j in b) b[j] = '</label><label title="'+b[j]+'">'
				+	(leftSide?'':b[j])
				+	c+(v1 += '+'+v[j])
				+	checkFeature(v[j])
				+	'">'
				+	(leftSide?b[j]:'');

			n += '<label title="'+b0+'">'
			+(b0
				?	(leftSide?'':b0)
				+	c+v0
				+	checkFeature(v0)
				+	'">'
				+	(leftSide?b0:'')
				:''
			)+b.join('')+'</label>';
			n += '</div>';
		} else {
			n += '</div><div class="block">';
		}
		b = '<input type="button" value="';
		c = '" onClick="eventStop(event); menuClose(';
		i = '" title="';
		v = la.tip.join('\r\n');

		i = '<div title="'+v+'">'
		+(g
			?	''
			:	'<div class="block">'
			+		'<a href="javascript:void('+(j = p.id.split('_').slice(1).join('-'))
			+		')" onClick="window.open(\''+j+'\',\'Report\',\'width=656,height=267\')">'+la.r+'</a>'
			+	'</div>'
		)
		+	'<div class="block">'+n+'</div>'
		+'</div>'
		+'<textarea name="'+p.id.replace('m', 't')+i+la.i+'" placeholder="'+la.t+'"></textarea>'
		+'<div>'
		+	'<input type="submit" value="'+la.go+i+v+'">&ensp;'
		+	b+'x'+i+la.x+c+'this)">'
		+	b+'&gt;&lt;'+i+la.z+c+')">'
		+'</div>';

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
		if (!e.getAttribute('data-text')) k = 0;
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
		erase: 'Комната будет уничтожена, восстановление невозможно.'
	,	rename: 'Комната сменит адрес, возможны конфликты.'
	,	sure: 'Вы уверены?'
	}; else la = {
		erase: 'The room will be deleted, this cannot be reverted.'
	,	rename: 'The room will be renamed, this can cause conflicts.'
	,	sure: 'Are you sure?'
	};
	while (i--) if ((e = a[i]) && e.checked) {
		count.checked++;
		if (t = e.getAttribute('data-confirm')) j.push(la[t]);
		if (t = e.getAttribute('data-text')) {
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
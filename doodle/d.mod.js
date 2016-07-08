mm = function menuInit() {
	if (!(flag || flag.g || flag.m)) return;	//* <- god|mod
var	a = gn('p'), i = a.length, p = id('tower');
	if (!p && (p = id('task'))) while ((p = p.nextSibling) && !DIV.test(p.tagName));
	if (p && !FORM.test(p.parentNode.tagName)) {
	var	f = document.createElement('form');
		p.parentNode.insertBefore(f, p);
		f.method = 'post';
		f.innerHTML = '<input type="hidden" name="mod" value="'+(+new Date())+'">';
		f.appendChild(p);
	}
	while (i--) if ((p = a[i]).id && p.id.slice(0,2) == 'm_') menuOpenOnClick(p);
}

function menuOpenOnClick(p) {
	p.setAttribute('onclick', 'menuOpen(this)');
}

function menuOpen(p) {
var	n = 'menu_'+p.id, m = id(n);
	if (!m) {
		p.removeAttribute('onclick');
		m = document.createElement('div'), m.className = 'mod-menu', m.id = n;
	var	leftSide = (n.slice(-1) == 0), d = p.parentNode, g = flag.g, la;
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
		,	i: 'Новый текст поста / имя комнаты.'
		,	e: 'Вставить пост для редактирования.'
		,	a: 'Вставить шаблон ответа.'
		,	x: 'Закрыть и забыть это меню.'
		,	z: 'Закрыть и забыть все меню на странице.'
		,	o: (
				leftSide ? (
					(flag.v?'':'в архив+готово+нет|замороз.нить+отм.+сжечь||удалить нить'
				+	(g?'+файлы+стереть с диска':'')+'|удалить пост (но не файл)|доб.пост+перед+изменить|уд.файл+обнулить'
				+	(g?'+стереть с диска':'')+'||слить сюда+отсюда|разделить нить отсюда')
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
		,	i: 'New post content / room name.'
		,	e: 'Insert post content to edit.'
		,	a: 'Insert response template.'
		,	x: 'Close and forget this menu.'
		,	z: 'Close and forget all menus on the page.'
		};
	var	o = (
			leftSide ? (
				(flag.v?'':'archive+ready+wait|freeze tr.+warm up+burn||delete thread'
			+	(g?'+pics+erase from disk':'')+'|delete post (but not pic)|add post+before+edit|delete pic+nullify'
			+	(g?'+erase from disk':'')+'||merge thread target+source|split thread from here')
			+	(g?'|nuke room+pics+arch|rename room'+(flag.v?'':'+copy trd to'):'')
			) : (
				(flag.v?'':'ban+lift|can report+not|'
			+	(g?'gets targets+not|sees unknown+not|':'')+'give mod+take|'
			+	(g?'give god+take|rename||':''))
			+	(g?'global announce'+(flag.u?'':'|room announce|room freeze+warm up')+'|global freeze+warm up'
				:'||room announce')
			)
		).split('|')
	,	textRequired = ['add post', 'rename', 'rename room', 'room announce', 'global announce']
	,	textEnabled = ['room freeze', 'global freeze']
	,	a = (la.o?la.o.split('|'):o)
	,	b,b0,v,v0,v1,c,i,j,n = '';
		for (i in a) if (a[i]) {
			v1 =
			v0 = (v = o[i].split('+')).shift();
			b0 = (b = a[i].split('+')).shift();

			c = '<input type="checkbox" onChange="menuRowCheck(this)" name="'
			+	p.id.replace('m', 'm'+i)
			+	(textRequired.indexOf(v0) < 0 ? '' : '" data-require="text')
			+	'" value="';

			n += '<span>';
			for (j in b) b[j] = '</label><label title="'+b[j]+'">'
				+	(leftSide?'':b[j])
				+	c+(v1 += '+'+v[j])
				+	(textEnabled.indexOf(v[j]) < 0 ? '' : '" data-enable="text')
				+	'">'
				+	(leftSide?b[j]:'');

			n += (n?'<br>':'')+'<label title="'+b0+'">'
			+(b0
				?	(leftSide?'':b0)
				+	c+v0
				+	(textEnabled.indexOf(v0) < 0 ? '' : '" data-enable="text')
				+	'">'
				+	(leftSide?b0:'')
				:''
			)+b.join('')+'</label>';
			n += '</span>';
		} else {
			n += '<br>';
		}
		b = '<input type="button" value="';
		a = '" onClick="menuAddText(this)">';
		c = '" onClick="eventStop(event); menuClose(';
		i = '" title="';
		v = la.tip.join('\r\n');

		m.innerHTML = '<div title="'+v+'">'+n+'</div>'
		+	'<textarea name="'+p.id.replace('m', 't')+i+la.i+'" placeholder="'+la.t+'"></textarea>'
		+(g
			?	'<br>'
			:	'<u><a href="javascript:void('+(j = p.id.split('_').slice(1).join('-'))
			+	')" onClick="window.open(\''+j+'\',\'Report\',\'width=656,height=267\')">'+la.r+'</a></u>'
		)
		+	'<input type="submit" value="'+la.go+i+v+'">&ensp;'
		+(leftSide
			?	b+'?'+i+la.e+a
			+	b+'+'+i+la.a+a+'&ensp;'
			:	''
		)
		+	b+'x'+i+la.x+c+'this)">'
		+	b+'&gt;&lt;'+i+la.z+c+')">';

		p.appendChild(m);
		menuRowCheck(p.lastElementChild);
	}
}

function menuRowCheck(target) {
var	d,e = target;
	while (!(d = e).id && (e = e.parentNode));
	if ((e = target).checked) {
		do {if (e = e.parentNode) a = e;} while (e.firstElementChild == e.lastElementChild);
		a = gi('checkbox', a), i = a.length;
		while (i--) if ((e = a[i]) != target) e.checked = 0;
	}
var	a = gi('checkbox', d), i = a.length, c = 0, n = 0, t = 0;
	while (i--) if ((e = a[i]) && e.checked) {
		++c;
		if (e.getAttribute('data-enable') == 'text') ++n;
		if (e.getAttribute('data-require') == 'text') ++t;
	}
	i = 0;
	if ((a = gi('submit', d)).length && (i = a[0])) i.disabled = !c;
	if ((a = gn('textarea', d)).length && (e = a[0])) {
		n = t||n;
		if (i) while ((i = i.nextElementSibling) && i.value != 'x') i.disabled = !n;
		e.required = !!t;
		e.style.display = (n?'':'none');
		menuFixHeight(d.parentNode);
	}
}

function menuAddText(e) {
	if (e.value != '+') {
	var	p = e;
		while (!(p.id && p.id.slice(0,2) == 'm_') && (p = p.parentNode));
		if (p) p = p.getAttribute('data-post');
	}
	while (e = e.previousElementSibling) if (e.name) return e.value = p || (
		'Re: '+(
			e.value && (p = e.value.replace(TRIM, ''))
			? p.replace(/^Re:\s*/i, '').replace(SPACE, ' ')
			: (lang == 'ru' ? 'Ваш текст ответа.' : 'Reply text here.')
		)
	);
}

function menuFixHeight(p) {
var	d = p.parentNode, p = gn('p',d), i = p.length, m = 0;
	while (i--) m = Math.max(m, p[i].offsetHeight);
	d.style.minHeight = m+'px';
}

function menuClose(e) {
	if (e && e.tagName) {
	var	m = e.parentNode, p = m.parentNode;
		p.removeChild(m);
		menuFixHeight(p);
		menuOpenOnClick(p);
	} else {
	var	a = gi(), i = a.length;
		while (i--) if ((e = a[i]) && e.value == 'x') menuClose(e);
	}
}
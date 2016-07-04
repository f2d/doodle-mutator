mm = function menuInit() {
	if (!(flag || flag.g || flag.m)) return;	//* <- god|mod
var	a = gn('p'), i = a.length, p = id('tower'), f;
	if (!p && (p = id('task'))) while ((p = p.nextSibling) && !DIV.test(p.tagName));
	if (p && !FORM.test(p.parentNode.tagName)) {
		p.parentNode.insertBefore(f = document.createElement('form'), p);
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
var	n = p.id+'_menu', m = id(n);
	if (!m) {
		p.removeAttribute('onclick');
		m = document.createElement('div');
		m.id = n;
	var	c = (n.split('_')[3] == 0), d = p.parentNode, g = flag.g, la;
		if (lang == 'ru') la = {
			tip: (
				c ? [
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
				]
			)
		,	go: 'пуск'
		,	r: 'Сообщить о проблеме'
		,	i: 'Новый текст поста / имя комнаты.'
		,	e: 'Вставить пост для редактирования.'
		,	a: 'Вставить шаблон ответа.'
		,	x: 'Закрыть и забыть это меню.'
		,	z: 'Закрыть и забыть все меню на странице.'
		,	o: (c
?(flag.v?'':'в архив+готово+нет|замороз.нить+отм.+сжечь||удалить нить'
+(g?'+файлы+стереть с диска':'')+'|удалить пост (но не файл)|удалить файл+обнулить'+(g?'+стереть с диска':'')+'|'
+(g?'доб.пост+допис.+заменить|':'')+'|слить сюда+отсюда|разделить нить отсюда')
+(g?'|снос комн.+файлов+архива|переназв.комн.'+(flag.v?'':'+копир.нить в'):'')

:(flag.v?'':'закрыть доступ+открыть|может жаловаться+нет|'
+(g?'получает цели+нет|видит неизв.+нет|':'')+'дать модератора+снять|'
+(g?'дать супермод.+снять|переименовать||':''))
+(g?'общее объявление'+(flag.u?'':'|комнатное объявление|замороз. комнату+отм.')+'|заморозить всё+отм.'
:'||комнатное объявление')
			)
		}; else la = {
			tip: (
				c ? [
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
				]
			)
		,	go: 'go'
		,	r: 'Report a problem'
		,	i: 'New post content / room name.'
		,	e: 'Insert post content to edit.'
		,	a: 'Insert response template.'
		,	x: 'Close and forget this menu.'
		,	z: 'Close and forget all menus on the page.'
		};
	var	o = (c
?(flag.v?'':'archive+ready+wait|freeze tr.+warm up+burn||delete thread'
+(g?'+pics+erase from disk':'')+'|delete post (but not pic)|delete pic+nullify'+(g?'+erase from disk':'')+'|'
+(g?'insert post+add+replace|':'')+'|merge thread target+source|split thread from here')
+(g?'|nuke room+pics+arch|rename room'+(flag.v?'':'+copy trd to'):'')

:(flag.v?'':'ban+lift|can report+not|'
+(g?'gets targets+not|sees unknown+not|':'')+'give mod+take|'
+(g?'give god+take|rename||':''))
+(g?'global announce'+(flag.u?'':'|room announce|room freeze+warm up')+'|global freeze+warm up'
:'||room announce')
		).split('|')
	,	a = (la.o?la.o.split('|'):o)
	,	b,b0,iv,v,v0,v1,n = '';
		for (i in a) if (a[i]) {
			v1 =
			v0 = (v = o[i].split('+')).shift();
			b0 = (b = a[i].split('+')).shift();
			iv = '<input type="checkbox" onChange="menuRowCheck(this)" name="'+p.id.replace('m', 'm'+i)+'" value="';
			for (j in b) b[j] =
 (c?'':b[j])+iv+(v1 += '+'+v[j])+'">'
+(c?b[j]:'');
			n += (b0
?(c?'':b0)+iv+v0+'">'
+(c?b0:''):'')+b.join('')+'<br>';
		} else n += '<br>';
		m.innerHTML = n+(g||!c?
'<textarea name="'+p.id.replace('m', 't')+'" title="'+la.i+'"></textarea>'
:'')+(g?'<br>':
'<u><a href="javascript:void('+(j = p.id.split('_').slice(1).join('-')
)+')" onClick="window.open(\''+j+'\',\'Report\',\'width=656,height=267\')">'+la.r+'</a></u>'
)+
'<input type="submit" value="'+la.go+'" title="'+(m.title = la.tip.join('\r\n'))+'">&ensp;'+(c && g?
'<input type="button" value="^" title="'+la.e+'" onClick="menuAddText(this)">'+
'<input type="button" value="v" title="'+la.a+'" onClick="menuAddText(this)">&ensp;':'')+
'<input type="button" value="x" title="'+la.x+'" onClick="menuClose()">'+
'<input type="button" value="&gt;&lt;" title="'+la.z+'" onClick="menuCloseAll()">';
		p.appendChild(m);
		menuFixHeight(p);
	}
}

function menuRowCheck(i) {

	function menuIter(which) {
	var	t,e = i;
		while ((e = e[which+'ElementSibling']) && (!(t = e.tagName) || !BR.test(t))) if (e != i && INPUT.test(t)) e.checked = 0;
	}

	if (i.checked) menuIter('previous'), menuIter('next');
}

function menuAddText(e) {
var	t = (e.value == 'v');
	while (e = e.previousElementSibling) if (e.name) return e.value = (
		t
		? '<span class="mod">\n'+(e.value?e.value:'Re:')+'\n</span>'
		: 'TODO'
	);
}

function menuFixHeight(p) {
var	d = p.parentNode, p = gn('p',d), i = p.length, m = 0;
	while (i--) m = Math.max(m, p[i].offsetHeight);
	d.style.minHeight = m+'px';
}

function menuClose(e) {
	if (e = eventStop(e)) e = e.target; else return;
var	m = e.parentNode, p = m.parentNode;
	p.removeChild(m);
	menuFixHeight(p);
	menuOpenOnClick(p);
}

function menuCloseAll(e) {
	eventStop(e);
var	a = gi(), i = a.length, o = 'menuClose()';
	while (i--) if ((e = a[i]) && e.getAttribute('onclick') == o) e.click();
}
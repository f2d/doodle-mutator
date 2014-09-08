var	mPat = /^m_(\d+_)+menu$/i
,	divPat = /^div$/i
,	formPat = /^form$/i;

function mm() {
	if (!(flag || flag.g || flag.m)) return;	//* <- god|mod
var	a = gn('p'), i = a.length, p = id('tower'), f;
	if (!p && (p = id('task'))) while ((p = p.nextSibling) && !divPat.test(p.tagName));
	if (p && !formPat.test(p.parentNode.tagName)) {
		p.parentNode.insertBefore(f = document.createElement('form'), p);
		f.method = 'post';
		f.innerHTML = '<input type="hidden" name="mod" value="'+(+new Date())+'">';
		f.appendChild(p);
	}
	while (i--) if ((p = a[i]).id && p.id.slice(0,2) == 'm_') p.setAttribute('onclick', 'mDropDown(this)');
}

function mDropAll(p) {
var	a = gn('div'), i = a.length, p;
	while (i--) if ((p = a[i]).id && mPat.test(p.id)) mDrop(p.parentNode);
}

function mDropDown(p) {
var	n = p.id+'_menu', m = id(n);
	if (!m) {
		m = document.createElement('div');
		m.onclick = function(e) {e.stopPropagation();};
		m.id = n;
	var	c = (n.split('_')[3] == 0), d = p.parentNode, g = flag.g, la;
		if (lang == 'ru') la = {
			tip: (c
?'Пункты пойдут снизу вверх, разом со всей комнаты.|\
Удалить пост и файл - 2 разных пункта.|\
На 1 строке сработает только 1 пункт.|\
Рекомендуется заморозка и действия по шагам.|\
Также рекомендуется вмешиваться пореже.'

:'Легенда окраски пользователей:|\
Тёплый: обычный,|\
Красный: запрет доступа (бан),|\
Серый: запрет сообщения,|\
Лёд: модератор комнаты,|\
Пурпур: супермодератор,|\
Зелёный: ваш.')
		,	go: 'пуск'
		,	r: 'Сообщить о проблеме'
		,	i: 'Новый текст поста / имя комнаты.'
		,	v: 'Вставить шаблон ответа.'
		,	x: 'Сбросить это.'
		,	z: 'Сбросить всё.'
		,	o: (c
?(flag.v?'':'в архив+готово+нет|замороз.нить+отм.+сжечь||удалить нить'
+(g?'+файлы':'')+'|удалить пост|удалить файл+обнулить|'
+(g?'доб.пост+допис.+заменить|':'')+'|слить сюда+отсюда|разделить нить отсюда')
+(g?'|снос комн.+файлов+архива|переназв.комн.'+(flag.v?'':'+копир.нить в'):'')

:(flag.v?'':'закрыть доступ+открыть|может жаловаться+нет|')
+(g?(flag.v?'':'получает цели+нет|видит неизв.+нет|дать модератора+снять|дать супермод.+снять|переименовать||')
+'общее объявление'+(flag.u?'':'|комнатное объявление|замороз. комнату+отм.')+'|заморозить всё+отм.'
:'|харакири (отказ)'))
		}; else la = {
			tip: (c
?'Apply changes on entire room.|\
Options go last to first, avoid batch submits, use freeze.|\
Deleting post with file requires 2 checkboxes.|\
Only 1 checkbox per line will work.|\
Also, do not ruin the game modifying too much.'

:'Userbar color legend:|\
Warm: default,|\
Red: no access (ban),|\
Gray: no reports,|\
Ice: room mod,|\
Purple: super mod,|\
Green: you.')
		,	go: 'go'
		,	r: 'Report a problem'
		,	i: 'New post content / room name.'
		,	v: 'Insert response template.'
		,	x: 'Drop this box.'
		,	z: 'Drop all.'
		};
	var	o = (c
?(flag.v?'':'archive+ready+wait|freeze tr.+warm up+burn||delete thread'
+(g?'+pics':'')+'|delete post|delete pic+nullify|'
+(g?'insert post+add+replace|':'')+'|merge thread target+source|split thread from here')
+(g?'|nuke room+pics+arch|rename room'+(flag.v?'':'+copy trd to'):'')

:(flag.v?'':'ban+lift|can report+not|')
+(g?(flag.v?'':'gets targets+not|sees unknown+not|give mod+take|give god+take|rename||')
+'global announce'+(flag.u?'':'|room announce|room freeze+warm up')+'|global freeze+warm up'
:'|harakiri (retire)')).split('|'), a = (la.o?la.o.split('|'):o), b, b0, iv, v, v0, v1;
		n = '';
		for (i in a) if (a[i]) {
			b0 = (b = a[i].split('+')).shift(), v1 =
			v0 = (v = o[i].split('+')).shift();
			iv = '<input type="checkbox" name="'+p.id.replace('m', 'm'+i)+'" value="';
			for (j in b) b[j] =
 (c?'':b[j])+iv+(v1 += '+'+v[j])+'">'
+(c?b[j]:'');
			n += (b0
?(c?'':b0)+iv+v0+'">'
+(c?b0:''):'')+b.join('')+'<br>';
		} else n += '<br>';
		m.title = la.tip.replace(/\|/g, '\r\n');
		m.innerHTML = n+(g?
'<textarea name="'+p.id.replace('m', 't')+'" title="'+la.i+'"></textarea>':
'<br><a target="_blank" href="'+p.id.split('_').slice(1).join('-')+'">'+la.r+'</a><br>')+'<br>'+
'<input type="submit" value="'+la.go+'">&emsp;'+(g?
'<input type="button" value="^" title="'+la.v+'" onClick="mSpan(this)">&emsp;':'')+
'<input type="button" value="x" title="'+la.x+'" onClick="mDrop('+p.id+')">&emsp;'+
'<input type="button" value="&gt;&lt;" title="'+la.z+'" onClick="mDropAll()">';
		p.appendChild(m);
		mFit(p);
	}
}

function mDrop(p) {
var	i = p.id+'_menu', m = id(i);
	if (m) m.parentNode.removeChild(m), mFit(p);
}

function mFit(p) {
var	d = p.parentNode, p = gn('p',d), i = p.length, m = 0, h;
	while (i--) if (m < (h = p[i].offsetHeight)) m = h;
	d.style.minHeight = m+'px';
}

function mSpan(e) {
var	p = e.parentNode, a = gn('textarea',p), t = a.length, i = t || (a = gi(p)).length;
	while (i--) if ((e = a[i]) && (t || e.type == 'text')) return e.value = '<span class="mod">\n'+(e.value?e.value:'What?')+'\n</span>';
}
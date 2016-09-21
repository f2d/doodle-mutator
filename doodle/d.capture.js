var	bnw = bnw || []
,	capsModes = {}
,	capsLastAnchor
,	capsLastPost
,	capsLastText
,	capsL = 'mouseup'
,	capsR = 'contextmenu'
,	capsBtn = 'caps-btn'
,	capsCanvas = 'caps-canvas'
,	capsWidth = document.body.offsetWidth
,	regClassCapsBtn
	;

bnw.push(bnw.caps = function capsInit(i) {
	if (i && param) {
		if ((a = param.caps) && (i = a.length)) while (i--) capsModes[a[i]] = 1;
		if ((a = param.caps_width) && (i = orz(a)) > 0) capsWidth = a;
	}
	if (!capsModes) return;

var	e = id('content') || document.body
,	n = ' \r\n'
,	f,t = ''
	;
	if (capsModes.a) {
		if (i) {
			reAddEventListener(e, capsL, capsPostClick);
			reAddEventListener(e, capsR, capsPostClick);
		}
		if (lang == 'ru') a = [
			'Alt + Shift +		клик: сохранить снимок всей нити'
		,	'Alt + Ctrl +		клик: снимок нити до последнего рисунка включительно'
		,	'Alt +			клик: снимок кликнутых/выбранных постов'
		,	'Ctrl + левый/правый	клик: выбрать/отменить'
		,	'Ctrl + Shift +		клик: выбрать всю нить'
		,	'Shift +		клик: выбрать до сюда'
		]; else a = [
			'Alt + Shift +		click: capture thread'
		,	'Alt + Ctrl +		click: capture thread up to last picture included'
		,	'Alt +			click: capture clicked/selected posts'
		,	'Ctrl + left/right	click: select/deselect'
		,	'Ctrl + Shift +		click: select thread'
		,	'Shift +		click: select range'
		];
		t += a.join(','+n).replace(/\t+/g, ' ')+'.';
		capsLastPost = 0;
	}
	if (capsModes.t) {
		if (i) {
			regClassCapsBtn = getClassReg(capsBtn);
			reAddEventListener(e, capsL, capsTextSelect);
		}
		if (lang == 'ru') t += (t?n+'Или в':'В')+'ыделить текст нужных постов и появится кнопка сохранения.';
		else t += (t?n+'Or s':'S')+'elect text across posts to capture, then a save button appears.';
	}
var	a = gc('multi-thread',e)
,	i = n = a.length
	;
	while (i--) a[i].title = t;

var	a = gc('post',e)
,	i = a.length
	;
	while (i--) if (
		(e = a[i])
	&&	(f = e.firstElementChild)
	&&	!(f.href || f.onclick)
	) {
		if (capsModes.a) capsLastPost = e;
		if (!n) e.title = t;
	}
});

function capsPostClick(e) {
	if (!e || !(
			e.which === 1
		||	e.type === capsR
		) || !(
			e.altKey
		||	e.ctrlKey
		||	(e.shiftKey
			&&	(
					e.type === capsR
				||	!capsLastText
				)//* ugh; shift+rmb still fails if ff49, but works in o11, Chrome, etc.
			)
		)
	) return e.which !== 1;
	if (e && (p = e.target) && (p = getCapsParentPost(p))) {
		eventStop(e,1,1);
	var	j,p,n = (e.which === 1?0:-1);
//* capture snapshot of all selected posts:
		if (e.altKey) {
		var	a = gc('post selected', id('content'))
		,	i = a.length
			;
			if (e.ctrlKey || e.shiftKey) {
				a = getCapsGroup(p, e.shiftKey?1:-1), j = a.length;
			} else {
				if (a.indexOf(p) < 0) a = getCapsGroup(p), j = a.length;
			}
			capsSave(a);
		} else
//* toggle selection of one clicked post:
		if (e.ctrlKey) {
		var	a = getCapsGroup(capsLastPost = p, j = (e.shiftKey?1:0))
		,	i = a.length
			;
			while (i--) toggleClass(a[i], 'selected', n || j);
		} else
//* select all posts between clicked and last clicked:
		if (e.shiftKey) {
		var	a = getCapsGroupRange(capsLastPost, p)
		,	i = a.length
			;
			capsLastPost = p;
			while (i--) toggleClass(a[i], 'selected', n || 1);
		}
	}
	return false;
}

function capsTextSelect(e) {
	if (!e
	||	e.altKey
	||	e.ctrlKey
	//||	e.shiftKey
	||	e.which !== 1
	) return false;
	if ((p = e.target) && (p = getCapsParentPost(p))) {
		eventStop(e,1,1);
	var	r,p
	,	e = null
	,	f = null
		;
		if (r = getCapsSelection()) {
			f = r.anchorNode || r.startContainer;
			e = r.focusNode || r.endContainer;
		}
		if (f && e) {
			f = getParentBeforeClass(f, regClassPost).parentNode || capsLastAnchor;
			e = getParentBeforeClass(e, regClassPost).parentNode;
		var	a = getCapsGroupRange(f,e)
		,	i = a.length
			;
			if (f) capsLastAnchor = f;	//* <- this is for ff49 moving the anchorNode somewhere outside the posts
		}
		if (i) {
			capsTextButtons(a[0], a[i-1], i);
		} else capsTextButtons();
	}
	return false;
}

function capsTextButtons(f,e,n) {
var	la,a = {
		top: f || e
	,	bottom: e || f
	};
	if (lang == 'ru') la = {
		top: 'Начало ряда'
	,	bottom: 'Конец ряда'
	,	hint: 'Сохранить снимок'
	}; else la = {
		top: 'Selection start'
	,	bottom: 'Selection end'
	,	hint: 'Save screenshot'
	};
	for (var i in a) {
		e = id(f = capsBtn+'-'+i);
		if (a[i]) {
			if (!e) {
				e = cre('div');
				e.className = capsBtn;
				e.id = f;
				e.innerHTML =
					'<div>'
				+		'<b>'+la[i]+'</b>'
				+		'<button>'+la.hint+'</button>'
				+	'</div>';
				gn('button',e)[0].onclick = capsSave;
			}
			if (i === 'top') {
				f = a[i].firstChild;
				if (e !== f) a[i].insertBefore(e,f);
			} else {
				if (f = gn('b',e)[0]) (f.lastElementChild || cre('span', f)).textContent = ', '+n;
				a[i].appendChild(e);
			}
		} else if (e) del(e);
	}
}

function getCapsParentPost(e) {
	while (!regClassPost.test(e.className)) if (
		e.id
	||	e.href
	||	e.onclick
	||	!(e = e.parentNode)
	) return null;
	return e;
}

function getCapsGroup(e, wholeThread) {
var	a = [];
	if (e && e.tagName) {
	var	f = e
	,	p = e
		;
		while ((p = p.previousElementSibling) && (wholeThread || !gn('img',p).length)) f = p;
		do {
			a.push(f);
			if (!wholeThread && gn('img',f).length) break;
		} while (f = f.nextElementSibling);
		if (wholeThread < 0) while ((e = a.length-1) > 0 && !gn('img', a[e]).length) a.length = e;
	}
	return a;
}

function getCapsGroupRange(f,e) {
var	r = []
,	a = gc('post', id('content'))
,	b = getCapsGroup(f)
,	c = getCapsGroup(e)
,	i = b.length-1
,	k = c.length-1
,	k = Math.max(a.indexOf(b[i]), a.indexOf(c[k]))
,	i = Math.min(a.indexOf(b[0]), a.indexOf(c[0]))
	;
	for (; i <= k; i++) r.push(a[i]);
	return r;
}

function getCapsSelection() {
var	r = capsLastText = 0;
	if (
		window.getSelection
	&&	(r = window.getSelection())
	&&	(capsLastText = (''+r).length)
	) return r;
	if (
		(r = document.selection) && r.createRange
	&&	(r = r.createRange()) && r.text
	&&	(capsLastText = r.text.length)
	) return r;
	return null;
}

function capsSave(posts) {

	function getWrappedTextLines(text, maxWidth) {

		function addLine(t, w) {
			if (blockWidth < w) blockWidth = w;
			lines.push({text: t, width: w});
		}

		//* source: http://sourcoder.blogspot.ru/2012/12/text-wrapping-in-html-canvas.html
		function wrapLine(text) {
		var	t = text.replace(regTrim, '').replace(regSpace, ' ')
		,	w = ctx.measureText(t).width
			;
			if (w < maxWidth) {
				return addLine(t, w);
			}
		var	words = t.split(' ')
		,	line = ''
			;
			while (words.length > 0) {
				while (ctx.measureText(words[0]).width >= maxWidth) {
					t = words[0];
					words[0] = t.slice(0, -1);
					if (words.length > 1) {
						words[1] = t.slice(-1) + words[1];
					} else {
						words.push(t.slice(-1));
					}
				}
				t = ctx.measureText(line + words[0]).width;
				if (t < maxWidth) {
					w = t;
					line += words.shift() + ' ';
				} else {
					addLine(line, w);
					line = '';
				}
			}
			if (line.length > 0) addLine(line, w);
		}

	var	lines = [];
		blockWidth = 0;

		if (text.join) {
			for (var i = 0, k = text.length; i < k; i++) wrapLine(text[i]);
		} else wrapLine(text);

		return lines;
	}

	function getNewBlock(e, color) {
		return {
			color: (e ? e.color || getStyleValue(e, 'background-color') : 0) || color || 'white'
		,	height: pad
		};
	}

	function addBlock(b) {
		totalHeight += b.height;
		blocks.push(b);
	}

	function saveDL(dataURI) {
//* sources:
//* https://github.com/SthephanShinkufag/Dollchan-Extension-Tools/blob/25a5f53349057b1216cdcbdc38a92c8991a9622d/src/Dollchan_Extension_Tools.es6.user.js#L1283
//* https://gist.github.com/borismus/1032746#gistcomment-1493026
//* https://developers.google.com/web/updates/2012/06/Don-t-Build-Blobs-Construct-Them
		size = dataURI.length;
		if ('download' in gn('a')[0]) {
		var	u = window.URL || window.webkitURL
		,	a = cre('a', document.body)
			;
			if (u && u.createObjectURL) {
			var	type = dataURI.split(';', 1)[0].split(':', 2)[1]
			,	data = dataURI.slice(dataURI.indexOf(',')+1)
			,	data = Uint8Array.from(TOS.map.call(atob(data), function(v) {return v.charCodeAt(0);}))
			,	blob = window.URL.createObjectURL(new Blob([data], {'type': type}))
			,	size = data.length
				;
				a.href = ''+blob;
			} else a.href = ''+dataURI;
;			a.download = getFormattedTime(0,1,0,1)+(room?'_'+room:'')+'.png';
			a.click();
			setTimeout(function() {
				if (blob) u.revokeObjectURL(blob);
				del(a);
			}, 12345);
		} else window.open(dataURI, '_blank');
		return size;
	}

var	la;
	if (lang == 'ru') la = {
		no_posts: 'Не выбран ни один пост для захвата.'
	,	no_image_size: 'Ошибка: программа-браузер не может создать полотно необходимого размера. Выберите меньше постов.'
	,	no_image_data: 'Ошибка: программа-браузер не может создать данные для файла изображения.'
	,	save_size: 'Размер содержимого файла'
	,	image_data: 'Содержимое изображения'
	,	image_res: 'Размер изображения'
	,	pixels: ' пикселей'
	,	bytes: ' байт'
	}; else la = {
		no_posts: 'No posts selected to capture.'
	,	no_image_size: 'Error: the browser program failed to create a canvas of required size. Try selecting less posts.'
	,	no_image_data: 'Error: the browser program failed to create resulting image file content.'
	,	save_size: 'File content size'
	,	image_data: 'Image content'
	,	image_res: 'Image size'
	,	pixels: ' pixels'
	,	bytes: ' bytes'
	};

	if (
		!(typeof posts === 'object' && posts.join)
	&&	(f = id(capsBtn+'-top'))
	&&	(e = id(capsBtn+'-bottom'))
	) {
	var	f = getParentBeforeClass(f, regClassPost).parentNode
	,	e = getParentBeforeClass(e, regClassPost).parentNode
	,	posts = getCapsGroupRange(f,e)
		;
	}
	if (posts && posts.length) {
		if (e = id('content')) toggleClass(e, 'saving', 1);
	var	e = posts[0]
	,	blocks = []
	,	cnv = id(capsCanvas) || cre('canvas')
	,	ctx = cnv.getContext('2d')
	,	w = orz(capsWidth) || 1234
	,	h = orz(getStyleValue(e, 'line-height')) || w
	,	pad = orz(getStyleValue(e, 'padding-bottom'))
	,	color = getStyleValue(e, 'color') || 'black'
	,	x = getStyleValue(e, 'font-size') || '20px'
	,	y = getStyleValue(e, 'font-family') || 'sans-serif'
	,	z = orz(x)
	,	font = x+' '+y
	,	ifont = 'italic '+font
	,	maxTextWidth = w - pad*2
	,	blockWidth = 0
	,	totalHeight = 0
		;
	//* just in case:
		cnv.id = capsCanvas;
		cnv.width = cnv.height = 1;

		for (var p_i = 0, p_k = posts.length; p_i < p_k; p_i++) {
		var	e = posts[p_i]
		,	p = getNewBlock(e)
		,	img = gn('img',e)[0]
		,	t = getParentBeforeClass(e, regClassThread).parentNode
			;
	//* bar between threads:
			if (prevThread !== t) {
				if (prevThread) addBlock(getNewBlock(t, 'black'));
			var	prevThread = t;
			}
	//* image post:
			if (img) {
				if (img.width || img.height) {
					p.height += img.height;
					p.img = img;
				}
			} else {
	//* text post:
				e = e.firstChild;
				do {
					if (
						!((t = e.tagName) && t.toLowerCase() === 'p')
					&&	!((t = e.className) && regClassCapsBtn.test(t))
					&&	(t = e.textContent) && t.length
					&&	(t = t.replace(regTrim, '')).length
					) break;
				} while (e = e.nextSibling);
				if (!e) continue;

				ctx.font = font;
	//* italic/poem block:
				if (i = gn('i',e)[0]) {
				var	f = e.firstChild.textContent.replace(/^\s+/, '').replace(regSpace, ' ')
				,	t = decodeHTMLSpecialChars(i.innerHTML).split(/<br[^>]*>/gi)
				,	i = ctx.measureText(f).width
					;
					ctx.font = ifont;
				var	lines = getWrappedTextLines(t, maxTextWidth - i);
				} else {
	//* normal text block:
					lines = getWrappedTextLines(t, maxTextWidth);
				}
				for (var l_i = 0, l_k = lines.length; l_i < l_k; l_i++) {
				var	line = getNewBlock(p);
					line.height = h;
					line.text = lines[l_i];
					if (i) {
						line.text.width = blockWidth;
						line.indent = i;
						if (l_i == 0) line.index = f;
					}
					addBlock(line);
				}
			}
			addBlock(p);
		}
		if (e = id('content')) toggleClass(e, 'saving', -1);
	//* prepare the canvas to draw the image:
		if (totalHeight > 0) {
			cnv.width = w;
			cnv.height = totalHeight;
		}
		if (cnv.height == totalHeight) {
	//* draw bg and images:
			for (var b_i = y = 0, b_k = blocks.length; b_i < b_k; b_i++) {
			var	b = blocks[b_i];
				ctx.fillStyle = b.color;
				ctx.fillRect(0,y, w,b.height);
				if (e = b.img) {
					x = Math.round((w - e.width)/2);
					ctx.drawImage(e, x,y);
				}
				y += b.height;
			}
	//* draw text, parts of letters may hang over neighbour blocks:
			for (var b_i = 0, y = z, b_k = blocks.length; b_i < b_k; b_i++) {
			var	b = blocks[b_i];
				if (e = b.text) {
					ctx.font = font;
					ctx.fillStyle = color;
					ctx.textBaseline = 'alphabetic';
					i = b.indent || 0;
					x = Math.round((w - e.width + i)/2);
					if (i) {
						if (i = b.index) {
							ctx.textAlign = 'right';
							ctx.fillText(i, x,y);
						}
						ctx.font = ifont;
					}
					ctx.textAlign = 'left';
					ctx.fillText(e.text, x,y);
				}
				y += b.height;
			}
			e = x = y = z = 0;
			try {
				x = cnv.toDataURL();
				y = x.length;
				z = saveDL(x);
			} catch(err) {
				e = err;
			}
			if (e || !z) {
				alert(
					la.no_image_data
				+	'\n\n'+la.image_res+': '+cnv.width+' x '+cnv.height+la.pixels+', '+(z?Math.round(y*3/4):y)+la.bytes
				+	(y ?	'\n'+la.image_data+': '+x.slice(0,42)+(y > 42?'(...)':'')
					+	'\n'+la.save_size+': '+z+la.bytes
					: '')
				+	(e ?	'\nError code: '+e.code
					+	'\nError name: '+e.name
					+	'\nError text: '+(e.message || 'none')
					: '')
				);
			}
		} else {
			alert(
				la.no_image_size
			+	'\n'+la.image_res+': '+w+' x '+totalHeight+la.pixels
			);
		}
	//* trying to free memory sooner:
		cnv.width = cnv.height = 1;
	} else {
		alert(la.no_posts);
	}
	return false;
}
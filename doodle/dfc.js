var dfc = new function () {

var	NS = 'dfc'	//* <- namespace prefix, change here and above; by the way, tabs align to 8 spaces
,	INFO_VERSION = 'v0.9.53'
,	INFO_DATE = '2013-04-01 — 2015-07-14'
,	INFO_ABBR = 'Dumb Flat Canvas'
,	A0 = 'transparent', IJ = 'image/jpeg', BOTH_PANELS_HEIGHT = 640, NUF = 100
,	CR = 'CanvasRecovery', CT = 'Time', DRAW_PIXEL_OFFSET = 0.5, CANVAS_BORDER = 1

,	TOOLS_REF = [
		{blur: 0, opacity: 1.00, width:  1, color: '0, 0, 0'}		//* <- draw
	,	{blur: 0, opacity: 1.00, width: 20, color: '255, 255, 255'}	//* <- back
	]
,	tools = [{}, {}], tool = tools[0]
,	BOW = ['blur', 'opacity', 'width']
,	BOWL = 'BOW'
,	RANGE = [
		{min: 0   , max: NUF, step: 1}
	,	{min: 0.01, max: 1  , step: 0.01}
	,	{min: 1   , max: NUF, step: 1}
	]

,	flushCursor = false, neverFlushCursor = true
,	mode = this.mode = {
		debug:	false
	,	shape:	false	//* <- straight line	/ fill area	/ copy
	,	step:	false	//* <- curve line	/ erase area	/ rect pan
	,	resize:	false
	,	lowQ:	false
	,	brushView:	false
	,	limitFPS:	false
	,	autoSave:	true
	}
,	modes = []
,	modeL = 'DLURQVFA'
,	shapeHotKey = 'NPRTYQM'
,	select = {
		imgSizes: {width:640, height:360}
	,	imgLimits: {width:[32,640], height:[32,800]}
	,	lineCaps: {lineCap:0, lineJoin:0}
	,	shapeFlags: [1,10, 2,2,2,66, 4]
/* shape flags (sum parts):
	1 = path, mode L: step 1 line, L+U: step 2 curve
	2 = fig., mode L: fill, U: outline
	4 = move, mode L: copy, U: step 2 rect
	8 = path, closed polygon
	64 = step 2
*/
	,	options: {
			shape	: ['line', 'freehand poly', 'rectangle', 'circle', 'ellipse', 'speech balloon', 'move']
		,	lineCap	: ['round', 'butt', 'square']
		,	lineJoin: ['round', 'bevel', 'miter']
		,	palette	: ['history', 'auto', 'legacy', 'Touhou', 'gradient']
		}
	}
,	PALETTE_COL_COUNT = 16	//* <- used if no '\n' found
,	palette = [
		(LS = window.localStorage || localStorage) && (i = LS.historyPalette) ? JSON.parse(i) : ['#f']
/* palette field format:
	'\t' = title
	'\n' = new row + optional title
	'\r' = special cases
	'#f00' = hex color field
	anything else = title + label
*/
	,	[	'#f', '#d', '#a', '#8', '#5', '#2', '#0',				'#a00', '#740', '#470', '#0a0', '#074', '#047', '#00a', '#407', '#704'
		, '\n',	'#7f0000', '#007f00', '#00007f', '#ff007f', '#7fff00', '#007fff', '#3', '#e11', '#b81', '#8b1', '#1e1', '#1b8', '#18b', '#11e', '#81b', '#b18'
		, '\n',	'#ff0000', '#00ff00', '#0000ff', '#ff7f00', '#00ff7f', '#7f00ff', '#6', '#f77', '#db7', '#bd7', '#7f7', '#7db', '#7bd', '#77f', '#b7d', '#d7b'
		, '\n',	'#ff7f7f', '#7fff7f', '#7f7fff', '#ffff00', '#00ffff', '#ff00ff', '#9', '#faa', '#eca', '#cea', '#afa', '#aec', '#ace', '#aaf', '#cae', '#eac'
		, '\n',	'#ffbebe', '#beffbe', '#bebeff', '#ffff7f', '#7fffff', '#ff7fff', '#c', '#fcc', '#fdc', '#dfc', '#cfc', '#cfd', '#cdf', '#ccf', '#dcf', '#fcd'
		]
	,	[ '\tWin7'
		,	'#0', '#7f7f7f', '#880015', '#ed1c24', '#ff7f27', '#fff200', '#22b14c', '#00a2e8', '#3f48cc', '#a349a4'
		, '\n',	'#f', '#c3c3c3', '#b97a57', '#ffaec9', '#ffc90e', '#efe4b0', '#b5e61d', '#99d9ea', '#7092be', '#c8bfe7'
		, '\nClassic', '#000000', '#000080', '#008000', '#008080', '#800000', '#800080', '#808000', '#c0c0c0', '\tCGA', '#0', '#00a', '#0a0', '#0aa', '#a00', '#a0a', '#aa0', '#a'
		, '\nClassic', '#808080', '#0000ff', '#00ff00', '#00ffff', '#ff0000', '#ff00ff', '#ffff00', '#ffffff', '\tCGA', '#5', '#55f', '#5f5', '#5ff', '#f55', '#f5f', '#ff5', '#f'
		, '\nGrayScale', '#f', '#e', '#d', '#c', '#b', '#a', '#9', '#8', '#7', '#6', '#5', '#4', '#3', '#2', '#1', '#0'
		, '\nPaint.NET'
		,	'#000000', '#404040', '#ff0000', '#ff6a00', '#ffd800', '#b6ff00', '#4cff00', '#00ff21'
		,	'#00ff90', '#00ffff', '#0094ff', '#0026ff', '#4800ff', '#b200ff', '#ff00dc', '#ff006e'
		, '\n',	'#ffffff', '#808080', '#7f0000', '#7f3300', '#7f6a00', '#5b7f00', '#267f00', '#007f0e'
		,	'#007f46', '#007f7f', '#004a7f', '#00137f', '#21007f', '#57007f', '#7f006e', '#7f0037'
		, '\n',	'#a0a0a0', '#303030', '#ff7f7f', '#ffb27f', '#ffe97f', '#daff7f', '#a5ff7f', '#7fff8e'
		,	'#7fffc5', '#7fffff', '#7fc9ff', '#3f647f', '#a17fff', '#d67fff', '#ff7fed', '#ff7fb6'
		, '\n',	'#c0c0c0', '#606060', '#7f3f3f', '#7f593f', '#7f743f', '#6d7f3f', '#527f3f', '#3f7f47'
		,	'#3f7f62', '#3f7f7f', '#3f647f', '#3f497f', '#503f7f', '#6b3f7f', '#7f3f76', '#7f3f5b'
		, '\nApple II', '#000000', '#7e3952', '#524689', '#df4ef2', '#1e6952', '#919191', '#35a6f2', '#c9bff9', '\tMSX', '#0', '#0', '#3eb849', '#74d07d', '#5955e0', '#8076f1', '#b95e51', '#65dbef'
		, '\nApple II', '#525d0d', '#df7a19', '#919191', '#efb5c9', '#35cc19', '#c9d297', '#a2dcc9', '#ffffff', '\tMSX', '#db6559', '#ff897d', '#ccc35e', '#ded087', '#3aa241', '#b766b5', '#c', '#f'
		, '\nIBM PC/XT CGA', '#000000', '#0000b6', '#00b600', '#00b6b6', '#b60000', '#b600b6', '#b66700', '#b6b6b6', '\tC-64', '#000000', '#ffffff', '#984a43', '#79c1c7', '#9b51a5', '#67ae5b', '#52429d', '#c9d683'
		, '\nIBM PC/XT CGA', '#676767', '#6767ff', '#67ff67', '#67ffff', '#ff6767', '#ff67ff', '#ffff67', '#ffffff', '\tC-64', '#9b6639', '#695400', '#c37b74', '#626262', '#898989', '#a3e599', '#897bcd', '#adadad'
		, '\nZX Spectrum', '#0', '#0000ca', '#ca0000', '#ca00ca', '#00ca00', '#00caca', '#caca00', '#cacaca', '\tVIC-20', '#000000', '#ffffff', '#782922', '#87d6dd', '#aa5fb6', '#55a049', '#40318d', '#bfce72'
		, '\nZX Spectrum', '#0', '#0000ff', '#ff0000', '#ff00ff', '#00ff00', '#00ffff', '#ffff00', '#ffffff', '\tVIC-20', '#aa7449', '#eab489', '#b86962', '#c7ffff', '#ea9ff6', '#94e089', '#8071cc', '#ffffb2'
		]
	,	[	'all'	, '#0', '#f', '#fcefe2'
		, '\n', 'Reimu'	, '#fa5946', '#e5ff41', '', '', ''	//* <- mid-row pad to align columns
		,	'Marisa', '#fff87d', '#a864a8'
		, '\n', 'Cirno'	, '#1760f3', '#97ddfd', '#fd3727', '#00d4ae', ''
		,	'Alice'	, '#8787f7', '#fafab0', '#fabad2', '#f2dcc6', '#8'
		, '\n', 'Sakuya', '#59428b', '#bcbccc', '#fe3030', '#00c2c6', '#585456'
		,	'Remilia','#cf052f', '#cbc9fd', '#f22c42', '#f2dcc6', '#464646'
		, '\n', 'Chen'	, '#fa5946', '#6b473b', '#339886', '#464646', '#ffdb4f'
		,	'Ran'	, '#393c90', '#ffff6e', '#c096c0'
		, '\n', 'Yukari', '#c096c0', '#ffff6e', '#fa0000', '#464646', ''
		,	'Reisen', '#dcc3ff', '#2e228c', '#e94b6d'
		, '\n', 'etc'
		]
	,	'\rg'
	]

,	noTransformByProp = /^Opera.* Version\D*11\.\d+$/i.test(navigator.userAgent)
,	noShadowBlurCurve = /^Opera.* Version\D*12\.\d+$/i.test(navigator.userAgent)
,	regHex = /^#?[0-9a-f]{6}$/i
,	regHex3 = /^#?([0-9a-f])([0-9a-f])([0-9a-f])$/i
,	reg255 = /^([0-9]{1,3}),\s*([0-9]{1,3}),\s*([0-9]{1,3})$/
,	reg255split = /,\s*/
,	regTipBrackets = /[ ]*\([^)]+\)$/
,	regFunc = /\{[^.]+\.([^(]+)\(/
,	regLimit = /^(\d+)\D+(\d+)$/

,	self = this, container, canvas, c2d, cnvHid, c2s, lang, DL, LS, i, outside = this.o = {}
,	fps = 0, ticks = 0, timer = 0, lastUsedSaveSlot = 0
,	interval = {fps:0, timer:0, save:0}, text = {debug:0, timer:0, undo:0}, used = {}, cue = {upd:{}}

,	draw = {o:{}, cur:{}, prev:{}
	,	refresh:0, time: [0,0]
	,	line: {started:0, back:0, preview:0}
	,	history: {pos:0, last:0
		,	cur: function() {return this.data[this.pos];}
		,	act: function(i) {
				lastUsedSaveSlot = 0;
			var	t = this, s = isNaN(i), z = t.data.length - 1;
				if (i && !s) {
					if (i < 0 && t.pos > 0) --t.pos; else
					if (i > 0 && t.pos < z && t.pos < t.last) ++t.pos; else return 0;
					t.reversable = '';
					draw.screen();
					cue.autoSave = 1;
					used.history = 'Undo';
				} else {
					if (s) {
						if (!i || !i.length) {
							t.reversable = '';
						} else
						if (i.slice && i.slice(0,3) == 'res') {
							if (t.reversable == i) --t.pos;
							else t.reversable = i;
							for (i in select.imgSizes) cnvHid[i] = canvas[i] = orz(id('img-'+i).value);
							draw.screen(1);
						} else {
							if (t.reversable == i) return 0;
							else t.reversable = i, draw.screen();
						}

						if (i !== 0) {
							if (t.pos < z) t.last = ++t.pos;
							else for (i = 0; i < z; i++) t.data[i] = t.data[i+1];
						}
					}
					t.data[t.pos] = c2d.getImageData(0,0, canvas.width, canvas.height);
				}
				return text.undo.textContent = t.pos+' / '+t.last+' / '+z, 1;
			}
		}
	,	screen: function(res) {
			clearFill(canvas);

		var	d = this.history.cur();
			if (!d) return;

		var	s = select.imgSizes, i;
			if (res && mode.resize) {
				for (i in s) cnvHid[i] = d[i], canvas[i] = orz(id('img-'+i).value);
				clearFill(canvas);
				c2s.putImageData(d, 0,0);
				c2d.drawImage(cnvHid, 0,0, d.width, d.height, 0,0, canvas.width, canvas.height);
				for (i in s) cnvHid[i] = canvas[i];
			} else {
				if (!res) {
				var	dif = 0, e = document.activeElement, unfocused = !(e.id && s[getLastWord(e.id)]);
					for (i in s) {
						if (canvas[i] != d[i]) cnvHid[i] = canvas[i] = d[i];
						if (unfocused && (e = id('img-'+i)).value != d[i]) e.value = d[i], ++dif;
					}
					if (dif) updateDimension();
				}
				c2d.putImageData(d, 0,0);
			}
		}
	};

function clearFill(c) {
var	d = c.c2d || (c.c2d = c.getContext('2d'));
	d.fillStyle = 'white';
	d.fillRect(0,0, c.width, c.height);
	return d;
}

function historyAct(i) {if (draw.history.act(i)) updateDebugScreen(), updateHistoryButtons();}
function fpsCount() {fps = ticks; ticks = 0;}

function dist(x,y) {return Math.sqrt(x*x + y*y)};
function ang_btw(x,y) {return cut_period(y-x);}
function cut_period(x,y,z) {
	if (isNaN(y)) y = -Math.PI;
	if (isNaN(z)) z = Math.PI;
	return (x < y ? x-y+z : (x > z ? x+y-z : x));
}
function orz(n) {return parseInt(n||0)||0;}
function nl2br(s) {return s.replace(/[\r\n]+/g, '<br>');}
function repeat(t,n) {return new Array(n+1).join(t);}
function replaceAll(t,s,j) {return t.split(s).join(j);}
function replaceAdd(t,s,a) {return replaceAll(t,s,s+a);}
function getLastWord(s,i) {return s.substr(s.lastIndexOf(i||'-')+1);}
function getFormattedNum(i) {
var	r,s = ''+i ,f = 'toLocaleString'
	return (i = orz(i))[f] && s != (r = i[f]()) ? r : s.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1 ');
}

function id(id) {return document.getElementById(NS+(id?'-'+id:''));}
function setId(e,id) {return e.id = NS+'-'+id;}
function setClass(e,c) {return e.className = NS+'-'+replaceAll(c,' ',' '+NS+'-');}
function setEvent(e,onWhat,func) {return e.setAttribute(onWhat, NS+'.'+func);}
function setContent(e,c) {
var	a = ['class','id','onChange','onClick'];
	for (i in a) c = replaceAdd(c, ' '+a[i]+'="', NS+(a[i][0]=='o'?'.':'-'));
	return e.innerHTML = c;
}
function toggleView(e) {e = id(e); return e.style.display = (e.style.display?'':'none');}
function showInfo() {
	if (id('colors').style.display == id('info').style.display) return;
	toggleView('colors');
	setClass(id('buttonH'), toggleView('info') ? 'button' : 'button-active');
}
function showProps(target, flags, spaces) {
/* flags:
	1 = return, don't alert
	2 = skip if value evaluates to false
	4 = only keys
	8 = only values
*/
var	k,v,j = ' ', output = '';
	for (k in target) if (
		(v = target[k]) || !(flags & 2)
	) output +=
		(output?'\n':'')
	+	((flags & 8)?'':k)
	+	((flags &12)?'':' = ')
	+	((flags & 4)?'':(spaces && v?(v+j).split(j, spaces).join(j):v));
	return (flags & 1) ? output : alert(output);
}
this.show = showProps;
this.unsave = function(i) {if (saveClear(i,1)) updateDebugScreen(i);}
this.whatSaved = function(i) {
var	d = getSaveLSDict(i);
	return {
		'supported'	: '[\n'+showProps(CR[i],1)+'\n]'
	,	'found'		: '[\n'+showProps(d.dict,5)+'\n]'
	,	'total bytes'	: getFormattedNum(d.sum)
	};
}

function updatePalette() {
var	pt = id('palette-table'), c = select.palette.value, p = palette[c];
	if (LS) LS.lastPalette = c;
	while (pt.childNodes.length) pt.removeChild(pt.lastChild);

	if (p[0] == '\r') {
		c = p[1];
		if (c == 'g') {
		var	ctx = (c = document.createElement('canvas')).getContext('2d')
		,	hues = [[255,  0,  0]
			,	[255,255,  0]
			,	[  0,255,  0]
			,	[  0,255,255]
			,	[  0,  0,255]
			,	[255,  0,255]
			]
		,	bw = [	[  0,  0,  0]
			,	[127,127,127]
			,	[255,255,255]
			]
		,	l = hues.length, f = 'return false;', r = RANGE[0];

			function linearBlend(from, to, frac, max) {
				if (frac <= 0) return from;
				if (frac >= max) return to;
			var	i = to.length, j = frac/max, k = 1-j, r = [];
				while (i--) r[i] = Math.round(from[i]*k + to[i]*j);
				return r;
			}
			c.width = 300, c.height = 133, c.sat = r.max, clearFill(c);

			(c.updateSat = function (sat) {
			var	x = c.width, y = c.height, y2 = Math.floor(y/2), h, i, j, k = c.width/l
			,	d = c.c2d.createImageData(x, y);
				while (x--) {
					h = linearBlend(hues[y = Math.floor(x/k)], hues[(y+1)%l], x%k, k);
					if (!isNaN(sat)) h = linearBlend(bw[1], h, sat, c.sat);
					y = c.height;
					while (y--) {
						j = linearBlend(h, bw[y < y2?0:2], Math.abs(y-y2), y2);
						i = (x+y*c.width)*4;
						d.data[i  ] = j[0];
						d.data[i+1] = j[1];
						d.data[i+2] = j[2];
						d.data[i+3] = 255;
					}
				}
				c.c2d.putImageData(d, 0,0);
			})();

			c.setAttribute('onscroll', f);
			c.setAttribute('oncontextmenu', f);
			c.addEventListener('mousedown', function (event) {pickColor(0, c, event || window.event);}, false);
			setId(c, 'gradient');
			setContent(pt, getSlider('S', -1));
			setSlider('S'), updateSliders(id('rangeS'));
		} else c = 'TODO';
		pt.appendChild(c.tagName ? c : document.createTextNode(c));
	} else {
	var	tbl = document.createElement('table'), tr, td, fill, t = '', colCount = 0, autoRows = true;
		for (i in p) if (p[i][0] == '\n') {autoRows = false; break;}
		for (i in p) {
			c = p[i];
			if (c[0] == '\n' || !colCount) {
				colCount = PALETTE_COL_COUNT;
				if (tr) tbl.appendChild(tr);
				(tr = document.createElement('tr')).textContent = '\n';
			}
			if (c[0] == '\t') t = c.slice(1); else		//* <- title, no text field
			if (c[0] == '\n') {
				if (c.length > 1) t = c.slice(1);	//* <- new line, title optional
			} else {
				(td = document.createElement('td')).textContent = '\n';
				if (c.length > 1 && c[0] == '#') {
				var	v = (c.length < 7 ? (c.length < 4 ?
						parseInt((c += repeat(c[1], 5))[1], 16)*3 : (
						parseInt(c[1], 16)+
						parseInt(c[2], 16)+
						parseInt(c[3], 16)))*17 : (
						parseInt(c.substr(1,2), 16)+
						parseInt(c.substr(3,2), 16)+
						parseInt(c.substr(5,2), 16)));
					setClass(fill = document.createElement('div'), v > 380 ? 'palettine' : 'paletdark');
					setEvent(fill, 'onclick', 'updateColor("'+c+'",0);');
					setEvent(fill, 'oncontextmenu', 'updateColor("'+c+'",1); return false;');
					fill.title = c+(t?(' ('+t+')'):'');
					td.style.backgroundColor = c;
					td.appendChild(fill);		//* <- color field
				} else if (c) {
					td.textContent = t = c;		//* <- title + text field
					setClass(td, 't');
				}
				tr.appendChild(td);			//* <- otherwise - empty spacer
				if (autoRows) --colCount;
			}
		}
		tbl.appendChild(tr);
		pt.appendChild(tbl);
	}
}

function getOffsetXY(e) {
var	x = 0, y = 0;
	while (e) x += e.offsetLeft, y += e.offsetTop, e = e.offsetParent;
	return {x:x, y:y};
}

function updateDebugScreen(lsid, refresh) {
	if (lsid) {
		if (refresh && !((i = text.debug.innerHTML) && i.length > 0)) return;
	var	i = s = 0, j = '', k = !CR[0], n = CR.length;
		if (k) for (k = ''; ++i < n;) {
			d = (i == 1 && refresh == 1)
		,	a = 'r'+i
		,	k += '{.whatSaved('+i+');'+(CR[i].keepSavedInOldFormat
				?'<span style="background-color:#ace">'+a+'</span>'
				:a)+'}'
		,	r = LS[CR[i].R]
		,	t = LS[CR[i].T]
		,	s += getSaveLSDict(i,0,1)
		,	j +=
			(j?'<br>':'<hr>')
		+	'Save'+i+'<a href="javascript:'+NS+'.unsave('+i+')">.del</a>, time: '
		+	(i == lsid || d
				?'<span style="background-color:#'
		+			(['f44','5ae','5ea','feb'][d?2:orz(refresh)]||'aaa')
		+		'">'+t+'</span>'
				:t)
		+	(t?' = '+unixDateToHMS(+t.split('-')[1],0,1):'')
		+	(r?', pic size: '+getFormattedNum(r.length)
		+		' [<a href="javascript:'+NS+'.savePic(0,'+i+')">save</a>'
		+		', <a href="javascript:'+NS+'.savePic(3,'+i+')">load</a>]':'')
		+	(d			?' ← saved':
			(i == lastUsedSaveSlot	?' ← last used':
			(i == lsid		?' ← shifted up to':'')));
		}
		text.debug.innerHTML = '<hr>'
		+	replaceAll(
			replaceAll(
			replaceAll(
			replaceAll(
				'{,0,1;props}'
		+		'{.o;outside}'
		+		'{.mode;mode}'
		+		'LStorage: '+(k?k+n+', total bytes = '+getFormattedNum(s):CT+', '+CR)
		+		', Save file as: '+(DL || 'new tab')
			, '{', '<a href="javascript:|.show(|')
			, ';', ')">.')
			, '}', '</a>,\n')
		+		(outside.read?'':', F6=read: <textarea id="|-read">/9.png</textarea>')
			, '|', NS)
		+	j+'<hr>'
		+	nl2br(getSendMeta(draw.screen()))+'<hr>';
	} else
	if (mode.debug) {
	var	r = '</td></tr>\n<tr><td>', d = '</td><td>', a = draw.turn, s = draw.step, t = draw.time, i = isMouseIn();
		text.debug.innerHTML =
			'<table><tr><td>'
		+	draw.refresh+d+'1st='+t[0]	+d+'last='+t[1]		+d+'fps='+fps
		+	r+'Relative'+d+'x='+draw.o.x	+d+'y='+draw.o.y	+d+i+(i?',rgb='+pickColor(1):'')
		+	r+'DrawOfst'+d+'x='+draw.cur.x	+d+'y='+draw.cur.y	+d+'btn='+draw.btn+',active='+draw.active
		+	r+'Previous'+d+'x='+draw.prev.x	+d+'y='+draw.prev.y	+d+'chain='+mode.click
		+	(s?''
		+	r+'StpStart'+d+'x='+s.prev.x	+d+'y='+s.prev.y
		+	r+'Step_End'+d+'x='+s.cur.x	+d+'y='+s.cur.y
			:'')
		+	'</td></tr></table>'
		+	showProps(tool,1)+(a?'<br>turn: '+showProps(a,3):'');
		++ticks;
	}
}

function updateViewport(delta) {
var	i,p = ['-moz-','-webkit-','-o-',''], s = '', t = '';
	if (isNaN(delta)) draw.angle = 0, draw.pan = 0, draw.zoom = 1, t = 'none';
	else
	if (draw.turn.pan) {
		draw.pan = {};
		for (i in draw.o) draw.pan[i] = draw.o[i] - draw.turn.origin[i] + draw.turn.prev[i];
	} else
	if (draw.turn.zoom) {
		i = draw.turn.prev * (draw.turn.origin + delta) / draw.turn.origin;
		if (i > 4) i = 4; else
		if (i < .25) i = .25;
		draw.zoom = i;
	} else {
		draw.angle = draw.turn.prev + delta;
		if (draw.a360 = Math.floor(draw.angle*180/Math.PI)%360) draw.aRad = draw.a360/180*Math.PI;
		else draw.angle = draw.a360 = draw.aRad = 0;
	}
	if (draw.pan) t += ' translate('+draw.pan.x+'px,'+draw.pan.y+'px)';
	if (draw.angle) t += ' rotate('+draw.a360+'deg)';
	if (draw.zoom != 1) t += ' scale('+(draw.zoom)+')';

	if (noTransformByProp) {
		if (t.indexOf('(') > 0) for (i in p) s += p[i]+'transform:'+t+';';
		canvas.setAttribute('style', s);
	} else {
		for (i in p) canvas.style[p[i]+'transform'] = t;
	}
	updateDebugScreen();
}

function updatePosition(event) {
var	i = select.shapeFlags[select.shape.value], r = getOffsetXY(draw.field)
,	o = (
	!	((i & 2) && mode.shape && !mode.step)
	&&	((i & 4) || ((draw.active ? c2d.lineWidth : tool.width) % 2))
	? DRAW_PIXEL_OFFSET
	: 0) - CANVAS_BORDER;

	draw.o.x = event.pageX - r.x;
	draw.o.y = event.pageY - r.y;

	if (draw.pan && !(draw.turn && draw.turn.pan)) for (i in draw.o) draw.o[i] -= draw.pan[i];
	if (!draw.turn && (draw.angle || draw.zoom != 1)) {
		r = getCursorRad(2, draw.o.x, draw.o.y);
		if (draw.angle) r.a -= draw.aRad;
		if (draw.zoom != 1) r.d /= draw.zoom;
		draw.o.x = Math.cos(r.a)*r.d + canvas.width/2;
		draw.o.y = Math.sin(r.a)*r.d + canvas.height/2;
		o = 0;
	}
	for (i in draw.o) draw.cur[i] = o + draw.o[i];
}

function getCursorRad(r, x, y) {
	if (draw.turn.pan) return {x: draw.o.x, y: draw.o.y};
	x = (isNaN(x) ? draw.cur.x : x) - canvas.width/2;
	y = (isNaN(y) ? draw.cur.y : y) - canvas.height/2;
	return (r
	? {	a:Math.atan2(y, x)
	,	d:dist(y, x)
	}
	: (draw.turn.zoom
		? dist(y, x)
		: Math.atan2(y, x)
	));
}

function drawCursor() {
	c2d.beginPath();
	c2d.lineWidth = 1;
	if (mode.brushView) {
		c2d.fillStyle = 'rgba('+tool.color+', '+tool.opacity+')';
		c2d.shadowColor = ((c2d.shadowBlur = tool.blur) ? 'rgb('+tool.color+')' : A0);
	} else {
		c2d.strokeStyle = 'rgb(123,123,123)';
		c2d.shadowColor = A0;
		c2d.shadowBlur = 0;
	}
	c2d.arc(draw.cur.x, draw.cur.y, tool.width/2, 0, 7/*Math.PI*2*/, false);
	c2d.closePath();
	mode.brushView ? c2d.fill() : c2d.stroke();
	if (!neverFlushCursor) flushCursor = true;
}

function drawStart(event) {
	if (!isMouseIn()) return false;
	canvas.focus();
	event.preventDefault();
	event.stopPropagation();
	event.cancelBubble = true;

	if (draw.btn && (draw.btn != event.which)) return drawEnd();
	if (mode.click) return ++mode.click, drawEnd(event);
	if (event.altKey) draw.turn = {prev: draw.zoom, zoom: 1}; else
	if (event.ctrlKey) draw.turn = {prev: draw.angle, angle: 1}; else
	if (event.shiftKey) draw.turn = {prev: draw.pan ? {x: draw.pan.x, y: draw.pan.y} : {x:0,y:0}, pan: 1};

	updatePosition(event);
	if (draw.turn) return draw.turn.origin = getCursorRad();

var	sf = select.shapeFlags[select.shape.value];
	if (draw.step) {
		if ((mode.step && ((mode.shape && (sf & 1)) || (sf & 4))) || (sf & 64)) {
			for (i in draw.o) draw.prev[i] = draw.cur[i];
			return draw.step.done = 1;
		} else draw.step = 0;
	}
//	if (event.shiftKey) mode.click = 1;
	if ((draw.btn = event.which) != 1 && draw.btn != 3) pickColor();
	else {
		draw.active = 1;
		if (!draw.time[0]) draw.time[0] = draw.time[1] = +new Date;
		if (!interval.timer) {
			interval.timer = setInterval(timeElapsed, 1000);
			interval.save = setInterval(autoSave, 60000);
		}
	var	i = (event.which == 1 ? 1 : 0), t = tools[1-i]
	,	pf = ((sf & 8) && (mode.shape || !mode.step))
	,	fig = ((sf & 2) && (mode.shape || pf));
		for (i in (t = {
			lineWidth: (((sf & 4) || (pf && !mode.step))?1:t.width)
		,	fillStyle: (fig ? 'rgba('+(mode.step?tools[i]:t).color+', '+t.opacity+')' : A0)
		,	strokeStyle: (fig && !(mode.step || pf) ? A0 : 'rgba('+t.color+', '+((sf & 4)?(draw.step?0.33:0.66):t.opacity)+')')
		,	shadowColor: (t.blur ? 'rgb('+t.color+')' : A0)
		,	shadowBlur: t.blur
		})) c2s[i] = c2d[i] = t[i];
		updatePosition(event);
		for (i in draw.o) draw.prev[i] = draw.cur[i];
		for (i in draw.line) draw.line[i] = false;
		for (i in select.lineCaps) c2s[i] = c2d[i] = select.options[i][select[i].value];
		c2d.beginPath();
		c2d.moveTo(draw.cur.x, draw.cur.y);
	}
}

function drawMove(event) {
	if (mode.click == 1 && !event.shiftKey) return mode.click = 0, drawEnd(event);

	updatePosition(event);
	if (draw.turn) return updateViewport(draw.turn.pan?1:draw.turn.delta = getCursorRad() - draw.turn.origin);

var	redraw = true, s = select.shape.value, sf = select.shapeFlags[s]
,	newLine = (draw.active && !((mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8)));

	if (mode.click) mode.click = 1;
	if (newLine) {
		if (draw.line.preview) {
			drawShape(c2d, s);
		} else
		if (draw.line.back = mode.step) {
			if (noShadowBlurCurve) c2d.shadowColor = A0, c2d.shadowBlur = 0;
			if (draw.line.started) c2d.quadraticCurveTo(
				draw.prev.x
			,	draw.prev.y
			,	(draw.cur.x + draw.prev.x)/2
			,	(draw.cur.y + draw.prev.y)/2
			);
		} else {
			c2d.lineTo(draw.cur.x, draw.cur.y);
		}
		draw.line.preview =	!(draw.line.started = true);
	} else
	if (draw.line.back) {
		c2d.lineTo(draw.prev.x, draw.prev.y);
		draw.line.back =	!(draw.line.started = true);
	}
	if (mode.limitFPS) {
	var	t = +new Date;
		if (t-draw.refresh > 30) draw.refresh = t; else redraw = false;		//* <- put "> 1000/N" to redraw maximum N FPS
	}
	if (redraw) {
		if ((flushCursor || neverFlushCursor) && !(mode.lowQ && draw.active)) draw.screen();
		if (draw.active) {
			if ((mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8)) {
				draw.line.preview = true;
				c2s.clearRect(0,0, canvas.width, canvas.height);
				c2s.beginPath();
				drawShape(c2s, (mode.step && (sf & 4) && (!draw.step || !draw.step.done))?2:s);
				c2s.stroke();
				c2d.drawImage(cnvHid, 0,0);				//* <- draw 2nd canvas overlay with sole shape
			}
			if (draw.line.started) c2d.stroke();
		} else if (neverFlushCursor && !mode.lowQ && isMouseIn()) drawCursor();
		updateDebugScreen();
	}
	if (newLine) for (i in draw.o) draw.prev[i] = draw.cur[i];
}

function drawEnd(event) {
	if (!event || draw.turn) return draw.active = draw.step = draw.btn = draw.turn = 0;
	if (mode.click == 1 && event.shiftKey) return drawMove(event);
	if (draw.active) {
	var	s = select.shape.value, sf = select.shapeFlags[s], m = ((mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8));
		if (!draw.step && ((mode.step && ((mode.shape && (sf & 1)) || (sf & 4))) || (sf & 64))) {
			draw.step = {prev:{x:draw.prev.x, y:draw.prev.y}, cur:{x:draw.cur.x, y:draw.cur.y}};	//* <- normal straight line as base
			return;
		}
		draw.time[1] = +new Date;
		draw.screen();
		c2d.fillStyle = c2s.fillStyle;
		if (sf & 8) {
			c2d.closePath();
			if (mode.shape || !mode.step) c2d.fill();
			used.poly = 'Poly';
		} else
		if ((sf & 64) || (m && draw.line.preview)) {
			drawShape(c2d, s);
			if (!(sf & 4)) used.shape = 'Shape';
		} else
		if (m || draw.line.back || !draw.line.started) {//* <- draw 1 pixel on short click, regardless of mode or browser
			c2d.lineTo(draw.cur.x, draw.cur.y + (draw.cur.y == draw.prev.y ? 0.01 : 0));
		}
		if (sf & 4) used.move = 'Move'; else
		if (!(sf & 8) || mode.step) c2d.stroke();
		historyAct();
		draw.active = draw.step = draw.btn = 0;
		if (cue.autoSave < 0) autoSave(); else cue.autoSave = 1;
		if (mode.click && event.shiftKey) return mode.click = 0, drawStart(event);
	}
	updateDebugScreen();
}

function drawShape(ctx, i) {
var	s = draw.step, v = draw.prev, r = draw.cur;
	switch (parseInt(i)) {
	//* rect
		case 2:	if (s) {
		//* show pan source area
				ctx.strokeRect(s.prev.x, s.prev.y, s.cur.x-s.prev.x, s.cur.y-s.prev.y);
			} else {
				if (ctx.fillStyle != A0)
				ctx.fillRect(v.x, v.y, r.x-v.x, r.y-v.y);
				ctx.strokeRect(v.x, v.y, r.x-v.x, r.y-v.y);
			}
		break;
	//* circle
		case 3:
		var	xCenter = (v.x+r.x)/2
		,	yCenter = (v.y+r.y)/2
		,	radius = Math.max(1, dist(r.x-xCenter, r.y-yCenter));

			ctx.moveTo(xCenter + radius, yCenter);
			ctx.arc(xCenter, yCenter, radius, 0, 7, false);	//* <- won't work without last "false" in Opera 11, okay

			if (ctx.fillStyle != A0) ctx.fill();
		break;
	//* ellipse
		case 4:
		case 5:	if (s) p = v, q = r, v = s.prev, r = s.cur;
		var	xCenter = (v.x+r.x)/2
		,	yCenter = (v.y+r.y)/2
		,	xRadius = Math.max(1, Math.abs(r.x-xCenter))
		,	yRadius = Math.max(1, Math.abs(r.y-yCenter))
		,	radius = Math.max(xRadius, yRadius), a = 1, b = 1, q,p,p2 = Math.PI*2;

			if (s && s.done) {
				xCenter += q.x-p.x;
				yCenter += q.y-p.y;
			}
			ctx.save();
			if (xRadius < yRadius) xCenter /= a = xRadius/yRadius, ctx.scale(a, b); else
			if (xRadius > yRadius) yCenter /= b = yRadius/xRadius, ctx.scale(a, b);

			if (s) {
		//* speech balloon
			var	x = q.x/a - xCenter
			,	y = q.y/b - yCenter
			,	a1 = Math.min(p2*(tool.width+1)/radius, Math.PI/18)
			,	a2 = Math.atan2(y, x)
			,	r2 = dist(x, y);

				ctx.moveTo(xCenter + Math.cos(a2)*r2, yCenter + Math.sin(a2)*r2);
				ctx.arc(xCenter, yCenter, radius, a2+a1, a2-a1+p2, false);
				ctx.closePath();
			} else {
				ctx.moveTo(xCenter + xRadius/a, yCenter);
				ctx.arc(xCenter, yCenter, radius, 0, p2, false);
			}
			ctx.restore();

			if (ctx.fillStyle != A0) ctx.fill();
		break;
	//* pan
		case 6:	if (v.x != r.x
			|| (v.y != r.y)) moveScreen(r.x-v.x, r.y-v.y);
		break;
	//* line
		default:if (s) {
			var	d = r, old = {}, t = {lineWidth: 1, shadowBlur: 0, shadowColor: A0, strokeStyle: 'rgba(123,123,123,0.5)'};
				for (i in t) old[i] = c2s[i], c2s[i] = t[i];
				c2s.beginPath();
				if (s.prev.x != v.x || s.prev.y != v.y) {
					c2s.moveTo(d.x, d.y), d = v;
					c2s.lineTo(d.x, d.y);
				}
				c2s.moveTo(s.cur.x, s.cur.y);
				c2s.lineTo(s.prev.x, s.prev.y);
				c2s.stroke();
				for (i in t) c2s[i] = old[i];
				c2s.beginPath();
		//* curve
				ctx.moveTo(s.prev.x, s.prev.y);
				ctx.bezierCurveTo(s.cur.x, s.cur.y, d.x, d.y, r.x, r.y);
			} else {
		//* straight
				ctx.moveTo(v.x, v.y);
				ctx.lineTo(r.x, r.y);
			}
	}
	ctx.moveTo(r.x, r.y);
}

function moveScreen(x, y) {
var	d = draw.history.cur(), p = draw.step, n = !mode.shape;
	c2d.fillStyle = 'rgb(' + tools[1].color + ')';
	if (p) {
		for (i in {min:0,max:0}) p[i] = {
			x:Math[i](p.cur.x, p.prev.x)
		,	y:Math[i](p.cur.y, p.prev.y)
		};
		p.max.x -= p.min.x;
		p.max.y -= p.min.y;
		if (n) c2d.fillRect(p.min.x, p.min.y, p.max.x, p.max.y);
		c2d.putImageData(d, x, y, p.min.x, p.min.y, p.max.x, p.max.y);
	} else {
		if (n) c2d.fillRect(0,0, canvas.width, canvas.height);
		c2d.putImageData(d, x, y);
	}
}

function fillCheck() {
var	d = draw.history.cur(), i = d.data.length;
	while (--i) if (d.data[i] != d.data[i%4]) return 0;	//* <- drawn content confirmed
	return 1;			//* <- fill flood confirmed
}

function fillScreen(i) {
	if (isNaN(i)) {
		used.wipe = 'Wipe';
		c2d.clearRect(0,0, canvas.width, canvas.height);
	} else
	if (i < 0) {
		historyAct('flip');	//* <- only push history 1 time for all consecutive attempts with same label
	var	d = draw.history.cur();
		if (i == -1) {
			used.inv = 'Invert';
			i = d.data.length;
			while (i--) if (i%4 != 3) d.data[i] = 255 - d.data[i];	//* <- modify current history point, no push
		} else {
		var	hw = d.width, hh = d.height, w = canvas.width, h = canvas.height
		,	hr = (i == -2), j, k, l = (hr?w:h)/2, m, n, x, y;
			if (hr) used.flip_h = 'Hor.Flip';
			else	used.flip_v = 'Ver.Flip';
			x = canvas.width; while (x--) if ((!hr || x >= l) && x < hw) {
			y = canvas.height; while (y--) if ((hr || y >= l) && y < hh) {
				m = (hr?w-x-1:x);
				n = (hr?y:h-y-1);
				i = (x+y*hw)*(k = 4);
				j = (m+n*hw)*k;
				while (k--) {
					m = d.data[i+k];
					n = d.data[j+k];
					d.data[i+k] = n;
					d.data[j+k] = m;
				}
			}}
		}
		c2d.putImageData(d, 0,0);
		return;
	} else {
		used.fill = 'Fill';
		c2d.fillStyle = 'rgb(' + tools[i].color + ')';
		c2d.fillRect(0,0, canvas.width, canvas.height);
	}
	cue.autoSave = 0;
	historyAct();
}

function pickColor(keep, c, event) {
	if (c) {
//* from gradient palette:
	var	d = getOffsetXY(c)
	,	x = event.pageX - CANVAS_BORDER - d.x
	,	y = event.pageY - CANVAS_BORDER - d.y;
		if (y < 0 || y >= c.height) {
			c = (y < 0?'0':'f');
			d = 0;
		} else {
			d = c.c2d.getImageData(0,0, c.width, c.height);
			if (x < 0) x = 0; else
			if (x >= c.width) x = c.width;
			c = (x + y*c.width)*4;
		}
	} else {
//* from drawing container:
		c = (Math.floor(draw.o.x) + Math.floor(draw.o.y)*canvas.width)*4;
		d = draw.history.cur();
	}
	if (d) {
		d = d.data;
		c = (d[c]*65536 + d[c+1]*256 + d[c+2]).toString(16);
		while (c.length < 6) c = '0'+c;
		c = '#'+c;
	}
	return keep ? c : updateColor(c, (!event || event.which != 3)?0:1);
}

function updateColor(value, toolIndex) {
var	t = tools[toolIndex || 0]
,	c = id('color-text')
,	v = value || c.value;
	if (reg255.test(v)) {
	var	a = (t.color = v).split(reg255split);
		v = '#';
		for (i in a) v += ((a[i] = parseInt(a[i]).toString(16)).length == 1) ? '0'+a[i] : a[i];
	} else {
		if (v[0] != '#') v = '#'+v;
		if (v.length == 2) v += repeat(v[1], 5);
		if (regHex3.test(v)) v = v.replace(regHex3, '#$1$1$2$2$3$3');
		if (!regHex.test(v)) return c.style.backgroundColor = 'red';
		if (value != '') t.color =
			parseInt(v.substr(1,2), 16)+', '+
			parseInt(v.substr(3,2), 16)+', '+
			parseInt(v.substr(5,2), 16);
	}
	if (t == tool) c.value = v, c.style.backgroundColor = '';

//* put on top of history palette:
var	p = palette[0], found = p.length, i;
	for (i = 0; i < found; i++) if (p[i] == v) found = i;
	if (found) {
		i = Math.min(found+1, PALETTE_COL_COUNT*9);		//* <- history length limit
		while (i--) p[i] = p[i-1];
		p[0] = v;
		if (0 == select.palette.value) updatePalette();
		if (LS) LS.historyPalette = JSON.stringify(p);
	}

//* update buttons:
	c = 0;
var	a = t.color.split(reg255split), e = id((t == tool) ? 'colorF' : 'colorB');
	for (i in a) c += parseInt(a[i]);
	e.style.color = (c > 380 ? '#000' : '#fff');			//* <- inverted font color
	e.style.background = 'rgb(' + t.color + ')';
	return v;
}

function getSlider(b,z) {
var	i = BOWL.indexOf(b), j
,	r = RANGE[i > 0?i:0]
,	s = '<span id="slider'+b+'"><input type="range" id="range'+b+'" onChange="updateSliders(this)';
	for (j in r) s += '" '+j+'="'+r[j];
	return s+'" value="'+(z > 0?r.min:r.max)+'"></span>	'+(i < 0?lang.sat:lang.tool[b])+(z?'<br>':'');
}

function setSlider(b) {
var	r = 'range', s = 'slider', t = 'text', y = 'type', e = id(r+b);
	if (e[y] != r) {
		e.setAttribute(y, t);
	} else {
		e = document.createElement('input');
		setId(e, (e[y] = t)+b);
		setEvent(e, 'onchange', 'updateSliders(this)');
		id(s+b).appendChild(e);
	}
	r = RANGE[Math.max(BOWL.indexOf(b), 0)], e.title = r.min+' - '+r.max;
}

function updateSlider(i,e) {
var	k = (e?i:BOWL[i])
,	s = id('range'+k)
,	t = id('text'+k) || s
,	r = RANGE[e?0:i]
,	f = (r.step < 1)
,	v = (e?parseFloat(e.value):tool[i = BOW[i]]);
	if (isNaN(v)) v = 1;
	if (f && v > r.max && v.toString().indexOf('.') < 0) v = '0.'+v;
	if (v < r.min) v = r.min; else
	if (v > r.max) v = r.max;
	if (f) v = parseFloat(v).toFixed(2);
	if (e && (e = id('gradient'))) {
		e.updateSat(v);
	} else tool[i] = v;
	s.value = t.value = v;
	return false;
}

function updateSliders(s) {
	if (s && s.id) {
	var	prop = s.id[s.id.length-1], i = BOWL.indexOf(prop);
		if (i < 0) return updateSlider(prop, s);
		return tool[BOW[i]] = parseFloat(s.value), updateSlider(i);
	}
	if (s) updateSlider(s);
	else for (i in BOW) updateSlider(i);

	if (draw.o.length) {
		drawEnd();
		s = tool.width+4;
		c2d.putImageData(draw.history.cur(), 0,0, draw.o.x - s/2, draw.o.y - s/2, s, s);
		drawCursor();
	}
	return false;
}

function updateShape(s) {
	if (!isNaN(s)) select.shape.value = s, s = 0;
	s = select.shapeFlags[(s?s:s=select.shape).value];
	setClass(id('bottom'), (s & 1?'b c':(s & 4?'a b':'a c')));
	return false;
}

function updateHistoryButtons() {
var	a = {R:draw.history.last,U:0}, b = 'button', d = b+'-disabled', e;
	for (i in a) setClass(id(b+i), draw.history.pos == a[i] ?d:b);
	cue.upd = {J:1,P:1};
}

function updateSaveFileSize(e) {
var	i = e.id.slice(-1);
	if (cue.upd[i]) cue.upd[i] = 0,
	e.title = e.title.replace(regTipBrackets, '')+' ('+(canvas.toDataURL({J:IJ,P:''}[i]).length / 1300).toFixed(0)+' KB)';
}

function updateResize(e) {
	mode.resize = !!e.checked;
}

function updateDimension(e) {
	if (e) {
		i = getLastWord(e.id), c = canvas[i], v = orz(e.value);
		cnvHid[i] = canvas[i] = e.value = v = (
			v < (b = select.imgLimits[i][0]) ? b : (
			v > (b = select.imgLimits[i][1]) ? b : v)
		);
		historyAct(mode.resize?'rescale':'resize');
	}
	if (!e || i == 'height') {
	var	b = id('buttonH')
	,	c = id('colors').style
	,	i = id('info').style;
		if (canvas.height < BOTH_PANELS_HEIGHT) {
		var	a = (b.className.indexOf('active') >= 0), v = 'none';
			c.display = (a?v:'');
			i.display = (a?'':v);
		} else {
			c.display = i.display = '';
			setClass(b, 'button-active');
		}
	}
	container.style.minWidth = (v = canvas.width+id('right').offsetWidth+14)+'px';
	if (a = outside.restyle) {
		v += 24;
		if (!(c = id(i = 'restyle'))) setId(container.parentNode.insertBefore(c = document.createElement('style'), container), i);
		if ((b = outside.restmin) && (b = eval(b).offsetWidth) > v) v = b;
		c.innerHTML = a+'{max-width:'+v+'px;}';
	}
}

function toolTweak(prop, value) {
var	i = BOWL.indexOf(prop);
	if (i < 0) return alert(lang.bad_id+'\nNo '+prop+' in '+BOWL), false;
var	b = BOW[i];
	if (value > 0) tool[b] = value;
	else {
	var	v = new Number(tool[b]), s = RANGE[i].step;
		tool[b] = (value ? v-s : v+s);
	}
	return updateSliders(i);
}

function toolSwap(back) {
var	t,i = orz(back);
	if (i > TOOLS_REF.length || i < 0) {
		for (t in TOOLS_REF)
		for (i in TOOLS_REF[t]) tools[t][i] = TOOLS_REF[t][i];
		for (i in select.lineCaps) select[i].value = 0;
		if (mode.shape) toggleMode(1);
		if (mode.step) toggleMode(2);
		updateShape(0);
		tool.width = Math.abs(back);
	} else
	if (i) {
		back = TOOLS_REF[i-1];
		for (i in back) tool[i] = back[i];
	} else {
		back = tools[0];
		tool = tools[0] = tools[1];
		tools[1] = back;
	}
	updateColor(tool.color);
	updateColor(0,1);
	updateSliders();
}

function toggleMode(i, keep) {
	if (i < 0 || i >= modes.length) return alert(lang.bad_id+'\nNo '+i+' in '+modes.length), false;
var	n = modes[i], v = mode[n], e = id('check'+modeL[i]);
	if (!keep) v = mode[n] = !v;
	if (e) setClass(e, v ? 'button-active' : 'button');
	if (n == 'debug') {
		text.debug.textContent = '';
		interval.fps ? clearInterval(interval.fps) : (interval.fps = setInterval(fpsCount, 1000));
	}
	return false;
}

function unixDateToHMS(t,u,y) {
var	d = t ? new Date(t+(t >0?0:new Date())) : new Date(), t = ['Hours','Minutes','Seconds'], u = 'get'+(u?'UTC':'');
	if (y) t = ['FullYear','Month','Date'].concat(t);
	for (i in t) if ((t[i] = d[u+t[i]]()+(y && i == 1?1:0)) < 10) t[i] = '0'+t[i];
	d = '-', u = (y > 1?d:':');
	return y ? t[0]+d+t[1]+d+t[2]+(y > 1?'_':' ')+t[3]+u+t[4]+u+t[5] : t.join(u);
}

function timeElapsed() {text.timer.textContent = unixDateToHMS(timer += 1000, 1);}
function autoSave() {if (mode.autoSave && cue.autoSave && !(cue.autoSave = (draw.active?-1:0))) savePic(2,-1);}

function getSendMeta(sz) {
var	d = draw.time, i, j = [], u = [], t = outside.t0;
	for (i in d) u[i] = parseInt(d[i]) || (i > 0?+new Date:t);
	for (i in used) j.push(used[i].replace(/[\r\n]+/g, ', '));
	return 't0: '	+Math.floor(t/1000)
	+'\ntime: '	+u.join('-')
	+'\napp: '	+NS+' '+INFO_VERSION
	+'\npixels: '	+canvas.width+'x'+canvas.height
	+'\nbytes: '	+(sz?sz:
		'png = '	+ canvas.toDataURL().length
		+', jpg = '	+ canvas.toDataURL(IJ).length
	)
	+(j.length
	?'\nused: '	+j.join(', ')
	:'');
}

function getSaveLSKeys(i) {
	if (i > 0 && (r = CR[i])) {
	var	r = r.R, n = r.length, i = LS.length, j = [], k;
		while(i--) if ((k = LS.key(i)) && (k == r || (!orz(k[n]) && k.substr(0,n) == r))) j.push(k);
		return j.sort();
	}
	return [];
}

function getSaveLSDict(i, swap, sum) {
var	j = getSaveLSKeys(i), k,m = 0, n = CR[i].R.length, d = {};
	if (j.length > 0) for (i in j) k = j[i], m += (sum?LS[k]:d[swap ? CR[swap].R+k.substr(n) : k] = LS[k]).length;
	return sum?m:{dict: d, sum: m};
}

function saveClear(i, warn) {
var	j = getSaveLSKeys(i);
	if (j.length > 0 && (!warn || confirm('Confirm deleting LS keys:\n\n'+j.join('\n')))) {
		for (i in j) LS.removeItem(j[i]);
		return j.length;
	}
	return 0;
}

function saveShiftUp(i) {
	if (i > 0 && i < CR.length-1) {
	var	n = i+1, d = getSaveLSDict(i, n), m = d.sum, d = d.dict;
		saveClear(i), saveClear(n);	//* <- have to care about LS size limit
		for (i in d) LS[i] = d[i];
	}
	return m || 0;
}

function saveShiftUpTo(i, swap) {
var	d = getSaveLSDict(i, swap = orz(swap)), m = d.sum, d = d.dict;
	while (i-- > swap) m += saveShiftUp(i);	//* <- destroys top slot old content
	if (swap) for (i in d) LS[i] = d[i];	//* <- carefully copy all fields, even if unaware what other app versions have saved there
	return m || 0;				//* <- max size proven to be allowed
}

function saveDL(content, suffix) {
	if (DL) {
		container.appendChild(a = document.createElement('a'));
		a.href = content, a[DL] = unixDateToHMS(0,0,2)+suffix;
		a.click();
		setTimeout(function() {container.removeChild(a);}, 5678);
	} else window.open(content, '_blank');
}

function confirmShowTime(la, s) {
	if (s) {
	var	a = s.split('-'), i,t,n = ' \r\n', r = la.join(n);
		for (i = 0; i < 2; i++) t = +a[i], r += n+(t ? unixDateToHMS(t,0,1) : '-');
	} else r = la[0];
	return confirm(r);
}

function savePic(dest, lsid) {
var	a = (lsid < 0), b = 'button', c,d,e,i,j,t = (lsid > 0);
	draw.screen();

	switch (dest) {
//* save to file
	case 0:
	case 1: saveDL(
			c = (t
				? LS[CR[lsid].R]
				: canvas.toDataURL(dest?IJ:'')
			)
		,	'_'+(t
				? LS[CR[lsid].T].split('-', 2)
				: draw.time
			).join('-')+(dest?'.jpg':'.png')
		);
		break;
//* save to memory
	case 2:
		c = canvas.toDataURL();
		if (!c || fillCheck())	return a || alert(lang.no.drawn	), c;
		if (!LS)		return a || alert(lang.no.LS	), c;
		if (LS[CR[1].R] === c)	return a || alert(lang.no.change), c;

		i = 1, j = CR.length;
		while (++i < j) if (LS[CR[i].R] === c) {
			saveShiftUpTo(i,1), updateDebugScreen(i,1);
			return a || alert(lang.found_swap), c;
		}

		if (lsid || confirmShowTime(lang.confirm.save, LS[CR[1].T])) {
			t = draw.time.join('-')+(used.read?'-'+used.read:'');
			d = saveShiftUpTo(i = j-1);
			while (--j) try {
				LS[CR[1].R] = c;
				LS[CR[1].T] = t;
				break;
			} catch(e) {
				if (c.length + t.length > d) return alert(lang.no.space+'\n'+lang.err_code+': '+e.code+', '+e.message), c;
				saveClear(1), saveClear(j);	//* <- probably maxed out allowed LS capacity, try to clean up from oldest slots first
			}
			setClass(id(b+'L'), b);
			id('saveTime').textContent = unixDateToHMS();
			cue.autoSave = lastUsedSaveSlot = 0, updateDebugScreen(i,1);
		}
		break;
//* load from memory
	case 3:
		if (!LS) return alert(lang.no.LS);

		function seekSavePos(i) {
			if (!(i > 0 && i < j)) i = 1;
			do if (e = LS[CR[i].T]) {
				d = LS[CR[i].R], t = e;
				if (t && d && d != c) return i;
			} while (++i < j);
			return 0;
		}

		c = canvas.toDataURL(), j = CR.length, i = seekSavePos(lsid || lastUsedSaveSlot+1) || seekSavePos(), lastUsedSaveSlot = 0;
		setClass(id(b+'L'), b+(t?'':'-disabled'));

		if (!t) return;
		if (!i) return alert(lang.no.change);

		if (lsid || confirmShowTime(lang.confirm.load, t)) {
			t = t.split('-');
			if (t.length > 2) used.read = 'Read File: '+t.slice(2).join('-').replace(/^[^:]+:\s+/, '');
			draw.time = t.slice(0,2), a = id('saveTime'), a.textContent = unixDateToHMS(+t[1]), a.title = new Date(+t[1]);
			readPic(d,i);
			used.LS = 'Local Storage';
		}
		break;
//* load file
	case 4:	
		if ((a = lsid) || ((outside.read || (outside.read = id('read'))) && (a = outside.read.value))) {
			used.read = 'Read File: '+readPic(a);
		}
		break;
//* send to server
	default:
		if (dest)		alert(lang.bad_id+'\n\nid='+dest+'\nautosave='+a); else
		if (!outside.send)	alert(lang.no.form); else
		if (fillCheck())	alert(lang.no.drawn); else {
			a = select.imgLimits, c = 'send';
			for (i in a) if (canvas[i] < a[i][0] || canvas[i] > a[i][1]) c = 'size';
		}
		if (c && confirm(lang.confirm[c])) {
			if (!outside.send.tagName) {
				setId(e = document.createElement('form'), 'send');
				e.setAttribute('method', (outside.send.length && outside.send.toLowerCase() == 'get')?'get':'post');
				container.appendChild(outside.send = e);
			}
		var	pngData = savePic(2, 1), jpgData, a = {txt:0,pic:0}, f = outside.send;
			for (i in a) if (!(a[i] = id(i))) {
				setId(e = a[i] = document.createElement('input'), e.name = i);
				e.type = 'hidden';
				f.appendChild(e);
			}
			e = pngData.length;
			d = (((i = outside.jp || outside.jpg_pref)
				&& (e > i)
				&& (((c = canvas.width * canvas.height
				) <= (d = select.imgSizes.width * select.imgSizes.height
				))
				|| (e > (i *= c/d)))
				&& (e > (t = (jpgData = canvas.toDataURL(IJ)).length))
			) ? jpgData : pngData);
			if (mode.debug) alert('png limit = '+i+'\npng = '+e+'\njpg = '+t);
			a.pic.value = d;
			a.txt.value = getSendMeta(d.length);
			f.encoding = f.enctype = 'multipart/form-data';
			f.submit();
		}
	}
	if (!lsid) return c;
}

function readPic(s,ls) {
	if (!s || s == 0 || (!s.data && !s.length)) return;
	if (!s.data) s = {data: s, name: (0 === s.indexOf('data:') ? s.split(',', 1) : s)};
var	d = draw.time, e = new Image(), t = +new Date, i;
	for (i in d) if (!d[i]) d[i] = t;
	e.setAttribute('onclick', 'this.parentNode.removeChild(this); return false');
	e.onload = function () {
		try {
			c2s.drawImage(e, 0,0);
			c2s.getImageData(0,0, 1,1);	//* <- disposable test of data source safety

			if (mode.resize) {
				clearFill(canvas);
				c2d.drawImage(e, 0,0, e.width, e.height, 0,0, canvas.width, canvas.height);
			} else {
				for (i in select.imgSizes) id('img-'+i).value = cnvHid[i] = canvas[i] = e[i];
				updateDimension();
				clearFill(canvas);
				c2d.drawImage(e, 0,0);
			}
			historyAct();
			cue.autoSave = 0;
			if (lastUsedSaveSlot = ls) updateDebugScreen(ls,3);
		} catch(i) {
			alert(lang.err_code+': '+i.code+', '+i.message);
			c2s = clearFill(cnvHid = document.createElement('canvas'));
			for (i in select.imgSizes) cnvHid[i] = canvas[i];
		} finally {
			if (d = e.parentNode) d.removeChild(e);
		}
	}
	draw.field.appendChild(e);
	return e.src = s.data, s.name;
}

function dragOver(event) {
	event.stopPropagation();
	event.preventDefault();

var	d = event.dataTransfer.files, e = d && d.length;
	event.dataTransfer.dropEffect = e?'copy':'move';
}

function drop(event) {
	event.stopPropagation();
	event.preventDefault();

var	d = event.dataTransfer.files, i = (d?d.length:0), f, r;
	if (!window.FileReader || !i) return;
	while (i--)
	if ((f = d[i]).type.match('image.*')) {
		(r = new FileReader()).onload = (function(f) {
			return function(e) {
				savePic(4, {
					name: f.name
				,	data: e.target.result
				});
			};
		})(f);
		r.readAsDataURL(f);
		return;
	}
	alert(lang.no.files);
}

function isMouseIn() {return (draw.o.x >= 0 && draw.o.y >= 0 && draw.o.x < canvas.width && draw.o.y < canvas.height);}
function browserHotKeyPrevent(event) {
	return ((!draw.active && isMouseIn()) || (event.keyCode == 27))
	? ((event.returnValue = false) || event.preventDefault() || true)
	: false;
}

function hotKeys(event) {
	if (browserHotKeyPrevent(event)) {
	var	s = String.fromCharCode(event.keyCode), i = shapeHotKey.indexOf(s);
		if (i >= 0) return updateShape(i);
		if (BOWL.indexOf(s) >= 0) return toolTweak(s, event.altKey?-1:0);

		function c(s) {return s.charCodeAt(0);}

		n = event.keyCode - c('0');
		if ((n?n:n=10) > 0 && n < 11) {
			k = [event.altKey, event.ctrlKey, 1];
			for (i in k) if (k[i]) return toolTweak(BOWL[i], RANGE[i].step < 1 ? n/10 : (n>5 ? (n-5)*10 : n));
		} else
		switch (event.keyCode) {
			case 27:	drawEnd();	break;	//* Esc
			case 36: updateViewport();	break;	//* Home

			case c('Z'):	historyAct(-1);	break;
			case c('X'):	historyAct(1);	break;
			case c('C'):	pickColor();	break;
			case c('F'):	fillScreen(0);	break;
			case c('D'):	fillScreen(1);	break;
			case c('I'):	fillScreen(-1);	break;
			case c('H'):	fillScreen(-2);	break;
			case c('V'):	fillScreen(-3);	break;
			case c('S'):	toolSwap();	break;
			case c('A'):	toolSwap(1);	break;
			case c('E'):	toolSwap(2);	break;
			case c('G'):	toolSwap(3);	break;

			case 8:
if (text.debug.innerHTML.length)	toggleMode(0);	break;	//* 8=bksp, 45=Ins, 42=106=[Num *]
			case c('L'):	toggleMode(1);	break;
			case c('U'):	toggleMode(2);	break;
			case 114:	toggleMode(4);	break;

			case 112:	showInfo();	break;
			case 120:	savePic(0);	break;
			case 118:	savePic(1);	break;
			case 113:	savePic(2);	break;
			case 115:	savePic(3);	break;
			case 117:	savePic(4);	break;
			case 119:	savePic();	break;

			case 42:
			case 106:updateDebugScreen(-1);	break;

			default: if (mode.debug) text.debug.innerHTML += '\n'+s+'='+event.keyCode;
		}
	}
	return false;
}

function hotWheel(event) {
	if (browserHotKeyPrevent(event)) {
	var	d = event.deltaY || event.detail || event.wheelDelta
	,	b = event.altKey?'B':(event.ctrlKey?'O':'W');
		toolTweak(b, d < 0?0:-1);
		if (mode.debug) text.debug.innerHTML += ' d='+d;
	}
	return false;
}




function init() {
	if (isTest()) document.title += ': '+NS+' '+INFO_VERSION;
var	a,b,c = 'canvas', d = '<div id="', e,f,g,h,i,j,k,n = '\n', o = outside, r = '</td><td class="r">', s = '&nbsp;', t = '" title="';

	if (e = id(c)) while (e = e.parentNode) if (e.id == NS) {
		e.parentNode.removeChild(e);	//* <- remove self duplicate, for archived pages saved in "current state"
		break;
	}

	setContent(container = id().firstElementChild
	,	d+'load">'
	+		'<div>'			//* <- transform offset fix for o11
	+			'<'+c+' id="'+c+'" tabindex="0">'+lang.no.canvas+'</'+c+'>'
	+		'</div>'
	+	'</div>'
	+	d+'right"></div>'
	+	d+'bottom"></div>'
	+	d+'debug"></div>'
	);
	if (!(canvas = id(c)).getContext) return;

	cnvHid = document.createElement(c);

	for (i in select.imgSizes) {
		cnvHid[i] = canvas[i] = (o[a = i[0]]?o[a]:o[a] = (o[i]?o[i]:select.imgSizes[i]));
		if ((o[b = a+'l'] || o[b = i+'Limit']) && (f = o[b].match(regLimit))) select.imgLimits[i] = [orz(f[1]), orz(f[2])];
	}
	c2s = clearFill(cnvHid);
	c2d = clearFill(canvas);

	for (i in {onscroll:0, oncontextmenu:0}) canvas.setAttribute(i, 'return false;');
	for (i in (a = {
		dragover:	dragOver
	,	drop:		drop
	,	mousedown:	drawStart
	,	mousemove:	drawMove
	,	mouseup:	drawEnd
	,	keypress:	browserHotKeyPrevent
	,	keydown:	hotKeys
	,	mousewheel:	e = hotWheel
	,	wheel:		e
	,	scroll:		e
	})) document.addEventListener(i, a[i], false);	//* <- using "document" to prevent negative clipping.
		//* still fails to catch events outside of document block height less than of browser window.

	b = d+'colors">'+d+'sliders">', i = BOW.length, r = '</td><td class="r">', a = ': '+r+'	';

	while (i--) b += getSlider(BOWL[i], i);

	b +=	'</div><table width="100%"><tr><td>'
	+	lang.shape	+a+'<select id="shape" onChange="updateShape(this)"></select>';

	for (i in select.lineCaps) b += r+'<select id="'+i+t+(select.lineCaps[i] || i)+'"></select>';

	setContent(id('right'),
		b+'</td></tr><tr><td>'
	+	lang.hex	+a+'<input type="text" value="#000" id="color-text" onChange="updateColor()'+t
	+	lang.hex_hint+'">'+r
	+	lang.palette	+a+'<select id="palette" onChange="updatePalette()"></select></td></tr></table>'
	+	d+'palette-table"></div></div>'
	+	d+'info"></div>'
	);

	a = '<a href="javascript:void(0);" onClick="', b = '">', c = '</abbr>', d = '';

	for (i in select.imgSizes) d +=
		lang.size[i]+': '
	+	'<input type="text" value="'+o[i[0]]+'" id="img-'+i+'" onChange="updateDimension(this)'+t
	+	lang.size_hint
	+	select.imgLimits[i].join(lang.range_hint)+'"> ';

	b = '<abbr title="', f = '<span class="rf">';

	g = '<b class="L">'
	+	'<b class="T"></b>'
	+	'<i></i>'
	+	'<b class="B"></b>'
	+ '</b>';

	setContent(id('info'),
//* top of 2 angle brackets:
		'<p class="L-open">'
	+		lang.info_top
	+	'</p>'
	+	'<p>'
	+		lang.info.join('<br>').replace(/\{([^};]+);([^}]+)}/g, a+'$1()">$2</a>')
	+		': '+f+b+(new Date())+'" id="saveTime">'
	+		lang.info_no_save+'</abbr>.</span>'
	+		'<br>'+a+'toggleView(\'timer\')'+t
	+		lang.hide_hint+'">'
	+		lang.info_time+'</a>: '+f+'<span id="timer">'+lang.info_no_yet+'</span>.</span>\n'
	+		lang.info_undo+': '	+f+'<span id="undo">'+lang.info_no_yet+'</span>.</span><br>'
	+		lang.info_drop
	+	'</p>'
//* bottom of 2 angle brackets:
	+	'<p class="L-close">'
	+		b
	+		NS.toUpperCase()
	+		', '+INFO_ABBR
	+		', '+lang.info_pad
	+		', '+INFO_DATE
	+		'">'+INFO_VERSION+'</abbr>.'
	+	'</p>'
	+	'<div>'
	+		d+'<label id="fit" title="'
	+		lang.resize_hint+'">'
//* 4 angle brackets:
	+			g
	+			'<input type="checkbox" onChange="updateResize(this)"'+(mode.resize?' checked':'')+'>'
	+			g.replace('L', 'R')
	+		'</label>'
	+	'</div>'
	);

	for (i in BOW) setSlider(BOWL[i]);
	for (i in text) text[i] = id(i);
	draw.field = id('load');
	draw.history.data = new Array(o.undo+1);

	a = 'historyAct(', b = 'button', c = 'color', d = 'toggleMode(', e = 'savePic(', f = 'fillScreen(', g = 'toolSwap(', k = 'check'
,	a = [
//* subtitle, hotkey, pictogram, function, id
		['undo'	,'Z'	,'&#x2190;'	,a+'-1)',b+'U']
	,	['redo'	,'X'	,'&#x2192;'	,a+'1)'	,b+'R']
	, 0
	,	['fill'	,'F'	,s		,f+'0)'	,c+'F']
	,	['swap'	,'S'	,'&#X21C4;'	,'toolSwap()']
	,	['erase','D'	,s		,f+'1)'	,c+'B']
	, 0
	,	['invert','I'	,'&#x25D0;'	,f+'-1)']
	,	['flip_h','H'	,'&#x2194;'	,f+'-2)']
	,	['flip_v','V'	,'&#x2195;'	,f+'-3)']
	, 0
	,	['pencil','A'	,'i'		,g+'1)']
	,	['eraser','E'	,'&#x25CB;'	,g+'2)']
	,	['reset' ,'G'	,'&#x25CE;'	,g+'3)']
	, 0
	,	['line|area|copy'	,'L'	,'&ndash;|&#x25A0;|&#x25A4;'	,d+'1)'	,k+'L']
	,	['curve|outline|rect'	,'U'	,'~|&#x25A1;|&#x25AD;'		,d+'2)'	,k+'U']
	,	['cursor'		,'F3'	,'&#x25CF;'			,d+'4)'	,k+'V']
	, 0
	,	['png'	,'F9'	,'P'	,e+'0)'	,b+'P']
	,	['jpeg'	,'F7'	,'J'	,e+'1)'	,b+'J']
	,	['save'	,'F2'	,'!'	,e+'2)'	,b+'S']
	,	['load'	,'F4'	,'?'	,e+'3)'	,b+'L']
	, !o.read || 0 == o.read?1:['read'	,'F6'	,'&#x21E7;'	,e+'4)']
	, !o.send || 0 == o.send?1:['done'	,'F8'	,'&#x21B5;'	,e+')']
	, 0
	,	['info'	,'F1'	,'?'	,'showInfo()'	,b+'H']
	]
,	f = id('bottom'), d = '<div class="button-', c = '</div>';

	function btnContent(e, subt, pict) {
	var	t = lang.b[subt];
		return setContent(e, d+'key">'+k[1]+c+pict+d+'subtitle"><br>'+(t.t?t.sub:subt)+c), e.title = t.t||t, e;
	}

	for (i in a) if (1 !== (k = a[i])) {
		if (k) {
			e = document.createElement(b);

			if (k[0].indexOf('|') > 0) {
				g = k[0].split('|');
				h = k[2].split('|');
				for (j in g) setClass(e.appendChild(btnContent(document.createElement('div'), g[j], h[j])), 'abc'[j]);
			} else btnContent(e, k[0], k[2]);

			setClass(e, b);
			setEvent(e, 'onclick', k[3]);
			if (k.length > 4) setId(e, k[4]);
			f.appendChild(e);
		} else f.innerHTML += s;
	}
	if (canvas.height < BOTH_PANELS_HEIGHT) toggleView('info');
	else setClass(id(b+'H'), b+'-active');

	for (i in mode) if (mode[modes[j = modes.length] = i]) toggleMode(j,1);
	for (i in (a = {S:0, L:CT})) if (!LS || ((k = a[i]) && !LS[k])) setClass(id(b+i), b+'-disabled');
	for (i in (a = 'JP')) if (e = id(b+a[i])) setEvent(e, 'onmouseover', 'updateSaveFileSize(this)');

	d = ['onchange', 'onclick', 'onmouseover'];
	for (i in (a = [b, 'input', 'select', 'span', 'a']))
	for (c in (b = container.getElementsByTagName(a[i])))
	for (e in d) if ((f = b[c][d[e]]) && !self[f = (''+f).match(regFunc)[1]]) self[f] = eval(f);

	d = 'download', DL = (d in b[0]?d:'');
	d = {	lineCap: ['<->', '|-|', '[-]']
	,	lineJoin: ['-x-', '\\_/', 'V']
	};
	a = select.options, c = select.translated || a, f = (LS && (e = LS.lastPalette) && palette[e]?e:1);
	for (b in a) {
		e = select[b] = id(b);
		for (i in a[b]) (
			e.options[e.options.length] = new Option(c[b][i]+(b == 'shape'?' ['+shapeHotKey[i]+']':(b in d?' '+d[b][i]:'')), i)
		).selected = (b == 'palette'?(i == f):!i);
	}

//* safe palette constructor, step recomended to be: 1, 3, 5, 15, 17, 51, 85, 255
	function generatePalette(p, step, slice) {
		p = palette[p];
		if (!p) return;
	var	letters = [0,0,0], l = p.length;
		if (l) p[l] = '\t', p[l+1] = '\n';
		while (letters[0] <= 255) {
			p[l = p.length] = '#';
			for (var i = 0; i < 3; i++) {
			var	s = letters[i].toString(16);
				if (s.length == 1) s = '0'+s;
				p[l] += s;
			}
			letters[2] += step;
			if (letters[2] > 255) letters[2] = 0, letters[1] += step;
			if (letters[1] > 255) letters[1] = 0, letters[0] += step;
			if ((letters[1] == 0 || (letters[1] == step * slice)) && letters[2] == 0) {
				p[l+1] = '\n';
			}
		}
	}

	toolSwap(3);
	generatePalette(1, 85, 0);
	updatePalette();
	updateSliders();
	updateViewport();
	historyAct(0);
}; //* <- END init()




function isTest() {
	if (!CR[0]) return !o.send;
var	o = outside
,	f = o.send = id('send')
,	r = o.read = id('read')
,	v = id('vars')
,	a = [v,f,r], e,i,j,k;

/* ext.config syntax:
	a) varname; var2=;		// no sign => value 1; no value => ''
	b) warname=two=3=last_val;	// all vars => same value (rightmost part)
*/
	for (i in a) if ((e = a[i]) && (e = (e.getAttribute('data-vars') || e.name))) {
		for (i in (a = e
			.replace(/\s*=\s*/g, '=')
			.replace(/[\s;]+=*/g, ';')
			.split(';')
		)) if ((e = a[i]).length) {
			if ((e = e.split('=')).length > 1) {
				k = e.pop();
				for (j in e) o[e[j]] = k;
			} else o[e[0]] = 1;
		}
		break;			//* <- read vars batch in the first found attribute only; no care about the rest
	}

	if (LS) {
		i = o.save = Math.max(orz(o.save), 3)
	,	j = (o.saveprfx || NS)+CR
	,	CR = [];

		do CR[i] = (
			LS[k = (i == 1?j:j.slice(0,-1)+i)]
			? {R:k, T:k+CT, keepSavedInOldFormat:true}
			: {R:(k = j+i), T:k+CT}
		); while (--i);

		CT = CR[1].T;
	} else o.save = 0;

	o.t0 = (o.t0 > 0 ? o.t0+'000' : +new Date)
,	i = ' \r\n'
,	j = shapeHotKey.split('').join(k = ', ');

	if ((o.undo = orz(o.undo)) < 3) o.undo = 123;
	if (!o.lang) o.lang = document.documentElement.lang || 'en';

	if (o.lang == 'ru') {
		r = ' браузера (содержит очередь из '+o.save+' позиций максимум).'
	,	lang = {
			bad_id:		'Ошибка: действие не найдено.'
		,	err_code:	'Код ошибки'
		,	found_swap:	'Рисунок был в запасе, теперь сдвинут на первое место.'
		,	confirm: {
				send:	'Отправить рисунок в сеть?'
			,	size:	'Размеры полотна вне допустимых пределов. Отправить всё равно?'
			,	save: [
					'Сохранить рисунок в память браузера?'
				,	'Заменить старую копию, изменённую:'
				]
			,	load: [
					'Вернуть рисунок из памяти браузера?'
				,	'Восстановить копию, изменённую:'
				]
			}
		,	no: {
				LS:	'Локальное Хранилище (память браузера) недоступно.'
			,	space:	'Ошибка сохранения, нет места.'
			,	files:	'Среди файлов не найдено изображений.'
			,	form:	'Назначение недоступно.'
			,	change:	'Нет изменений.'
			,	canvas:	'Ваша программа не поддерживает HTML5-полотно.'
			,	drawn:	'Полотно пусто.'
			}
		,	tool: {
				B:	'Тень'
			,	O:	'Непрозр.'
			,	W:	'Толщина'
			}
		,	shape:		'Форма'
		,	palette:	'Палитра'
		,	sat:		'Насыщ.'
		,	hex:		'Цвет'
		,	hex_hint:	'Формат ввода — #a, #f90, #ff9900, или 0,123,255'
		,	hide_hint:	'Кликните, чтобы спрятать или показать.'
		,	info_top:	'Управление (указатель над полотном):'
		,	info: [
				'C'+k+'средний клик = подобрать цвет с рисунка.'
			,	j+' = выбор формы.'
		//	,	'Shift + клик = цепочка форм, Esc = {drawEnd;отмена}.'
			,	'Ctrl + тяга = поворот полотна, Home = {updateViewport;сброс}.'
			,	'Alt + тяга = масштаб, Shift + т. = сдвиг рамки.'
			,	'1-10'		+k+'колесо мыши'+k+'(Alt +) W = толщина кисти.'
			,	'Ctrl + (1-10'	+k+'колесо)'	+k+'(Alt +) O = прозрачность.'
			,	'Alt + (1-10'	+k+'колесо)'	+k+'(Alt +) B = размытие тени.'
			,	'Автосохранение раз в минуту'
			]
		,	info_no_save:	'ещё не было'
		,	info_no_yet:	'ещё нет'
		,	info_time:	'Времени прошло'
		,	info_undo:	'Шаги'
		,	info_pad:	'доска для набросков'
		,	info_drop:	'Можно перетащить сюда файлы с диска.'
		,	size: {
				width:	'Ширина'
			,	height:	'Высота'
			}
		,	size_hint:	'Число от '
		,	range_hint:	' до '
		,	resize_hint:	'Отметить, чтобы содержимое полотна растягивалось с изменением размера или при загрузке файлов.'
		,	b: {
				undo:	{sub:'назад',	t:'Отменить последнее действие.'}
			,	redo:	{sub:'вперёд',	t:'Отменить последнюю отмену.'}
			,	fill:	{sub:'залить',	t:'Залить полотно основным цветом.'}
			,	erase:	{sub:'стереть',	t:'Залить полотно запасным цветом.'}
			,	invert:	{sub:'инверт.',	t:'Обратить цвета полотна.'}
			,	flip_h:	{sub:'отразить',t:'Отразить полотно слева направо.'}
			,	flip_v:	{sub:'перевер.',t:'Перевернуть полотно вверх дном.'}
			,	pencil:	{sub:'каранд.',	t:'Инструмент — тонкий простой карандаш.'}
			,	eraser:	{sub:'стёрка',	t:'Инструмент — толстый белый карандаш.'}
			,	swap:	{sub:'смена',	t:'Поменять инструменты местами.'}
			,	reset:	{sub:'сброс',	t:'Сбросить инструменты к начальным.'}
			,	line:	{sub:'прямая',	t:'Прямая линия 1 зажатием.'}
			,	curve:	{sub:'кривая',	t:'Сглаживать углы пути / кривая линия 2 зажатиями.'}
			,	area:	{sub:'закрас.',	t:'Закрашивать площадь геометрических фигур.'}
			,	outline:{sub:'контур',	t:'Рисовать контур геометрических фигур.'}
			,	copy:	{sub:'копия',	t:'Оставить старую копию.'}
			,	rect:	{sub:'прямоуг.',t:'Сдвиг прямоугольником.'}
			,	cursor:	{sub:'указат.',	t:'Показывать кисть на указателе.'}
			,	rough:	{sub:'п.штрих',	t:'Уменьшить нагрузку, пропуская перерисовку штриха.'}
			,	fps:	{sub:'п.кадры',	t:'Уменьшить нагрузку, пропуская кадры.'}
			,	png:	{sub:'сохр.png',t:'Сохранить рисунок в PNG файл.'}
			,	jpeg:	{sub:'сохр.jpg',t:'Сохранить рисунок в JPEG файл.'}
			,	save:	{sub:'сохран.',	t:'Сохранить рисунок в память'+r}
			,	load:	{sub:'загруз.',	t:'Вернуть рисунок из памяти'+r
				+	i+'Может не сработать в некоторых браузерах, если не настроить автоматическую загрузку и показ изображений.'}
			,	read:	{sub:'зг.файл',	t:'Прочитать локальный файл.'
				+	i+'Может не сработать вообще, особенно при запуске самой рисовалки не с диска.'
				+	i+'Вместо этого рекомендуется перетаскивать файлы из других программ.'}
			,	done:	{sub:'готово',	t:'Завершить и отправить рисунок в сеть.'}
			,	info:	{sub:'помощь',	t:'Показать или скрыть информацию.'}
			}
		}
	,	select.lineCaps = {
			lineCap:	'Концы линий'
		,	lineJoin:	'Сгибы линий'
		}
	,	select.translated = {
			shape	: ['линия', 'замкнутая линия', 'прямоугольник', 'круг', 'овал', 'овал для речи', 'сдвиг']
		,	lineCap	: ['круг', 'срез', 'квадрат']
		,	lineJoin: ['круг', 'срез', 'угол']
		,	palette	: ['история', 'авто', 'разное', 'Тохо', 'градиент']
		};
	} else {
		r = ' your browser memory (keeps a maximum of '+o.save+' slots in a queue).'
	,	lang = {
			bad_id:		'Invalid action: case not found.'
		,	err_code:	'Error code'
		,	found_swap:	'Found same image still saved, swapped it to first slot.'
		,	confirm: {
				send:	'Send image to server?'
			,	size:	'Canvas size is outside of limits. Send anyway?'
			,	save: [
					'Save image to your browser memory?'
				,	'Replace saved copy edited at:'
				]
			,	load: [
					'Restore image from your browser memory?'
				,	'Load copy edited at:'
				]
			}
		,	no: {
				LS:	'Local Storage (browser memory) not supported.'
			,	space:	'Saving failed, not enough space.'
			,	files:	'No image files found.'
			,	form:	'Destination unavailable.'
			,	change:	'Nothing changed.'
			,	canvas:	'Your browser does not support HTML5 canvas.'
			,	drawn:	'Canvas is empty.'
			}
		,	tool: {
				B:	'Shadow'
			,	O:	'Opacity'
			,	W:	'Width'
			}
		,	shape:		'Shape'
		,	palette:	'Palette'
		,	sat:		'Saturat.'
		,	hex:		'Color'
		,	hex_hint:	'Valid formats — #a, #f90, #ff9900, or 0,123,255'
		,	hide_hint:	'Click to show/hide.'
		,	info_top:	'Hot keys (mouse over image only):'
		,	info: [
				'C'+k+'Mouse Mid = pick color from image.'
			,	j+' = select shape.'
		//	,	'Shift + click = chain shapes, Esc = {drawEnd;cancel}.'
			,	'Ctrl + drag = rotate canvas, Home = {updateViewport;reset}.'
			,	'Alt + d. = zoom, Shift + d. = move canvas frame.'
			,	'1-10'		+k+'Mouse Wheel'+k+'(Alt +) W = brush width.'
			,	'Ctrl + (1-10'	+k+'Wheel)'	+k+'(Alt +) O = brush opacity.'
			,	'Alt + (1-10'	+k+'Wheel)'	+k+'(Alt +) B = brush shadow blur.'
			,	'Autosave every minute, last saved'
			]
		,	info_no_save:	'not yet'
		,	info_no_yet:	'no yet'
		,	info_time:	'Time elapsed'
		,	info_undo:	'Steps'
		,	info_pad:	'sketch pad'
		,	info_drop:	'You can drag files from disk and drop here.'
		,	size: {
				width:	'Width'
			,	height:	'Height'
			}
		,	size_hint:	'Number from '
		,	range_hint:	' to '
		,	resize_hint:	'Check to resize canvas content to fit on size changes or when loading files.'
		,	b: {
				undo:	'Revert last change.'
			,	redo:	'Redo next reverted change.'
			,	fill:	'Fill image with main color.'
			,	erase:	'Fill image with back color.'
			,	invert:	'Invert image colors.'
			,	flip_h:	{sub:'flip hor.',t:'Flip image horizontally.'}
			,	flip_v:	{sub:'flip ver.',t:'Flip image vertically.'}
			,	pencil:	'Set tool to sharp black.'
			,	eraser:	'Set tool to large white.'
			,	swap:	'Swap your tools.'
			,	reset:	'Reset both tools.'
			,	line:	'Draw straight line with 1 drag.'
			,	curve:	'Smooth path corners / draw single curve with 2 drags.'
			,	area:	'Fill geometric shapes.'
			,	outline:'Draw outline of geometric shapes.'
			,	copy:	'Keep old copy.'
			,	rect:	'Move rectangle.'
			,	cursor:	'Brush preview on cursor.'
			,	rough:	'Skip draw cleanup while drawing to use less CPU.'
			,	fps:	'Limit FPS when drawing to use less CPU.'
			,	png:	'Save image as PNG file.'
			,	jpeg:	'Save image as JPEG file.'
			,	save:	'Save image copy to'+r
			,	load:	'Load image copy from'+r
				+	i+'May not work in some browsers until set to load and show new images automatically.'
			,	read:	'Load image from your local file.'
				+	i+'May not work at all, especially if sketcher itself is not started from disk.'
				+	i+'Instead, it is recommended to drag and drop files from another program.'
			,	done:	'Finish and send image to server.'
			,	info:	'Show/hide information.'
			}
		};
	}
	return !o.send;
} //* <- END isTest()




document.write(
	replaceAll(
'<div id="|">'
+'<div>Loading |...</div>'
+	replaceAdd('<style>\
#| .|-L-close {padding-bottom: 22px; border-bottom: 1px solid #000; border-right: 1px solid #000;}\
#| .|-L-open {padding-top: 22px; border-top: 1px solid #000; border-left: 1px solid #000;}\
#| .|-a .|-a,\
#| .|-b .|-b,\
#| .|-c .|-c {display: none;}\
#| .|-button {background-color: #ddd;}\
#| .|-button-active {background-color: #ace;}\
#| .|-button-active:hover {background-color: #bef;}\
#| .|-button-disabled {color: #888; cursor: default;}\
#| .|-button-key, #| .|-button-subtitle {vertical-align: top; height: 10px; font-size: 9px; margin: 0; padding: 0;}\
#| .|-button-key, #|-debug {text-align: left;}\
#| .|-button-subtitle {line-height: 6px; margin: 0 -3px;}\
#| .|-button:hover {background-color: #eee;}\
#| .|-paletdark, #| .|-palettine {border: 2px solid transparent; height: 15px; width: 15px; cursor: pointer;}\
#| .|-paletdark:hover {border-color: #fff;}\
#| .|-palettine:hover {border-color: #000;}\
#| .|-r {text-align: right;}\
#| .|-red {background-color: #f77;}\
#| a {color: #888;}\
#| a:hover {color: #000;}\
#| abbr {border-bottom: 1px dotted #111;}\
#| canvas {border: '+CANVAS_BORDER+'px solid #ddd; margin: 0; cursor:\
	url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAGElEQVR42mNgYGCYUFdXN4EBRPz//38CADX3CDIkWWD7AAAAAElFTkSuQmCC\'),\
	auto;}\
#| canvas:hover {border-color: #aaa;}\
#| hr {border: none; border-top: 1px solid #aaa; margin: 8px 0;}\
#| input[type="range"] {width: 156px; height: 16px; margin: 0; padding: 0;}\
#| input[type="text"] {width: 48px;}\
#| select, #| #|-color-text {width: 78px;}\
#| textarea {min-width: 80px; min-height: 16px; height: 16px; vertical-align: top;}\
#| {text-align: center; padding: 12px; background-color: #f8f8f8;}\
#|, #| input, #| select {font-family: "Arial"; font-size: 19px; line-height: normal;}\
#|-bottom > button {border: 1px solid #000; width: 38px; height: 38px; margin: 2px; padding: 2px; font-size: 15px; line-height: 7px; text-align: center; vertical-align: top; cursor: pointer;}\
#|-bottom > button, #|-load canvas {box-shadow: 3px 3px rgba(0,0,0, 0.1);}\
#|-bottom {margin: 10px 0 -2px;}\
#|-debug td {width: 234px;}\
#|-fit > b * {display: block; height: 12px; width: 6px;}\
#|-fit > b b.|-B {height: 6px; border-bottom: 2px solid #aaa; vertical-align: bottom;}\
#|-fit > b b.|-T {height: 6px; border-top: 2px solid #aaa; vertical-align: top;}\
#|-fit > b.|-L b {border-left: 2px solid #aaa;}\
#|-fit > b.|-R b {border-right: 2px solid #aaa;}\
#|-fit input {margin: 0; padding: 0;}\
#|-fit, #|-fit > b, #|-fit input {display: inline-block; height: 28px; vertical-align: top;}\
#|-fit:hover > b b {border-color: #000; border-width: 1px; padding: 1px 1px 0 0;}\
#|-info p {padding-left: 22px; line-height: 22px; margin: 0;}\
#|-info p, #|-palette-table table {color: #000; font-size: small;}\
#|-load canvas {vertical-align: bottom;}\
#|-load img {position: absolute; top: 1px; left: 1px; margin: 0;}\
#|-load, #|-load canvas {position: relative; display: inline-block;}\
#|-palette-table .|-t {padding: 0 4px;}\
#|-palette-table table {margin: 0;}\
#|-palette-table tr td {margin: 0; padding: 0; height: 16px;}\
#|-palette-table {overflow-y: auto; max-height: 178px; margin: 0 0 12px 0;}\
#|-right span > input[type="text"] {margin: 2px;}\
#|-right table {border-collapse: collapse;}\
#|-right table, #|-info > div {margin-top: 7px;}\
#|-right td {padding: 0 2px; height: 32px;}\
#|-right {color: #888; width: 321px; margin: 0; margin-left: 12px; text-align: left; display: inline-block; vertical-align: top; overflow: hidden;}\
</style>'
	, '}', '\n')
	, '|', NS)
+'</div>'
);

document.addEventListener('DOMContentLoaded', init, false);
};
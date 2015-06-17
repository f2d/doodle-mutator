var dfc = new function () {

var	NS = 'dfc'	//* <- namespace prefix, change here and above; by the way, tabs align to 8 spaces
,	INFO_VERSION = 'v0.9.49'
,	INFO_DATE = '2013-04-01 — 2015-06-18'
,	INFO_ABBR = 'Dumb Flat Canvas'
,	A0 = 'transparent', IJ = 'image/jpeg', BOTH_PANELS_HEIGHT = 640
,	CR = 'CanvasRecover', CT = 'Time', DL, DRAW_PIXEL_OFFSET = -0.5
,	LS = window.localStorage || localStorage

,	TOOLS_REF = [
		{blur: 0, opacity: 1.00, width:  1, color: '0, 0, 0'}		//* <- draw
	,	{blur: 0, opacity: 1.00, width: 20, color: '255, 255, 255'}	//* <- back
	], tools = [{}, {}], tool = tools[0], BOW = ['blur', 'opacity', 'width'], BOWL = 'BOW'
,	RANGE = [
		{min: 0   , max: 100, step: 1}
	,	{min: 0.01, max: 1  , step: 0.01}
	,	{min: 1   , max: 100, step: 1}
	]

,	flushCursor = false, neverFlushCursor = true
,	mode = {debug:	false
	,	shape:	false	//* <- straight line	/ fill area	/ copy
	,	step:	false	//* <- curve line	/ erase area	/ rect pan
	,	lowQ:	false
	,	brushView:	false
	,	limitFPS:	false
	,	autoSave:	true
	}, modes = [], modeL = 'DLUQVFA'
,	used = {}, cue = {upd:{}}
,	select = {
		imgRes: {width:640, height:360}
	,	imgLimits: {width:[64,640], height:[64,800]}
	,	lineCaps: {lineCap:0, lineJoin:0}
	,	shapeFlags: [1,10, 2,2,2,66, 4]
	,	options: {
			shape	: ['line', 'freehand poly', 'rectangle', 'circle', 'ellipse', 'speech balloon', 'move']
		,	lineCap	: ['round', 'butt', 'square']
		,	lineJoin: ['round', 'bevel', 'miter']
		,	palette	: ['history', 'auto', 'legacy', 'Touhou', 'gradient']
	}}
,	PALETTE_COL_COUNT = 16	//* <- used if no '\n' found
,	palette = [(LS && LS.historyPalette) ? JSON.parse(LS.historyPalette) : ['#f']
//* '\t' = title, '\n' = line break + optional title, '\r' = special cases, '#f00' = hex color field, anything else = title + plaintext spacer
	, [	'#f', '#d', '#a', '#8', '#5', '#2', '#0',				'#a00', '#740', '#470', '#0a0', '#074', '#047', '#00a', '#407', '#704'
	, '\n',	'#7f0000', '#007f00', '#00007f', '#ff007f', '#7fff00', '#007fff', '#3', '#e11', '#b81', '#8b1', '#1e1', '#1b8', '#18b', '#11e', '#81b', '#b18'
	, '\n',	'#ff0000', '#00ff00', '#0000ff', '#ff7f00', '#00ff7f', '#7f00ff', '#6', '#f77', '#db7', '#bd7', '#7f7', '#7db', '#7bd', '#77f', '#b7d', '#d7b'
	, '\n',	'#ff7f7f', '#7fff7f', '#7f7fff', '#ffff00', '#00ffff', '#ff00ff', '#9', '#faa', '#eca', '#cea', '#afa', '#aec', '#ace', '#aaf', '#cae', '#eac'
	, '\n',	'#ffbebe', '#beffbe', '#bebeff', '#ffff7f', '#7fffff', '#ff7fff', '#c', '#fcc', '#fdc', '#dfc', '#cfc', '#cfd', '#cdf', '#ccf', '#dcf', '#fcd'
	], ['\tWin7'
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
	], [	'all'	, '#0', '#f', '#fcefe2'
	, '\n', 'Reimu'	, '#fa5946', '#e5ff41', '', '', ''
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
	], '\rg', '\rl']

,	regHex = /^#?[0-9a-f]{6}$/i
,	regHex3 = /^#?([0-9a-f])([0-9a-f])([0-9a-f])$/i
,	reg255 = /^([0-9]{1,3}),\s*([0-9]{1,3}),\s*([0-9]{1,3})$/
,	reg255split = /,\s*/
,	regTipBrackets = /[ ]*\([^)]+\)$/
,	regFunc = /\{[^.]+\.([^(]+)\(/
,	regLimit = /^(\d+)\D+(\d+)$/

,	self = this, container, canvas, c2d, canvasShape, c2s, lang, outside = this.o = {}
,	fps = 0, ticks = 0, timer = 0
,	interval = {fps: 0, timer: 0, save: 0}, text = {debug:0, timer:0}

,	draw = {o:{}, cur:{}, prev:{}
	,	refresh:0, time: [0, 0]
	,	line: {started:0, back:0, preview:0}
	,	history: {pos:0, last:0
		,	cur: function() {return this.data[this.pos];}
		,	act: function(i) {
			var	t = this, d = t.data.length - 1;
				if (i) {
					if (i < 0 && t.pos > 0) --t.pos; else
					if (i > 0 && t.pos < d && t.pos < t.last) ++t.pos; else return 0;
					draw.screen();
					cue.autoSave = 1;
					used.history = 'Undo';
				} else {
					if (i !== false) t.reversable = 0;
					else if (t.reversable) return 0;
					else t.reversable = 1, draw.screen();
					if (i !== 0) {if (t.pos < d) t.last = ++t.pos; else for (i = 0; i < d; i++) t.data[i] = t.data[i+1];}
					t.data[t.pos] = c2d.getImageData(0, 0, canvas.width, canvas.height);
				}
				return 1;
			}
		}
	,	screen: function() {
			c2d.fillStyle = 'white';
			c2d.fillRect(0, 0, canvas.width, canvas.height);
			c2d.putImageData(this.history.cur(), 0, 0);
		}
	};

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
function repeat(t,n) {return new Array(n+1).join(t);}
function replaceAll(t,s,j) {return t.split(s).join(j);}
function replaceAdd(t,s,a) {return replaceAll(t,s,s+a);}

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
		],	bw = [	[  0,  0,  0]
			,	[127,127,127]
			,	[255,255,255]
		],	l = hues.length, f = 'return false;';

			function linearBlend(from, to, frac, max) {
				if (frac <= 0) return from;
				if (frac >= max) return to;
			var	i = to.length, j = frac/max, k = 1-j, r = [];
				while (i--) r[i] = Math.round(from[i]*k + to[i]*j);
				return r;
			}
			c.width = 300, c.height = 133, c.sat = 100, c.ctx = ctx;

			(c.updateSat = function (sat) {
			var	x = c.width, y = c.height, y2 = Math.floor(y/2), h, i, j, k = c.width/l
			,	d = ctx.createImageData(x, y);
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
				ctx.putImageData(d, 0, 0);
			})();

			c.setAttribute('onscroll', f);
			c.setAttribute('oncontextmenu', f);
			c.addEventListener('mousedown', function (event) {pickColor(0, c, event || window.event);}, false);
			setId(c, 'gradient');
			setContent(pt, '<span id="sliderS">'
+'<input onChange="updateSliders(this)" type="range" id="rangeS" value="'+c.sat+'" min="0" max="'+c.sat+'" step="1">'
+'<input onChange="updateSliders(this)" type="text" id="textS" value="'+c.sat+'"></span> '+lang.sat+'<br>');
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

function updateDebugScreen() {
	if (!mode.debug) return;
	ticks ++;
var	t = '</td><td>', r = '</td></tr>	<tr><td>', a, b = 'turn: ', c = draw.step, i;
	if (a = draw.turn) for (i in a) b += i+'='+a[i]+'; ';
	text.debug.innerHTML = '<table><tr><td>'
+draw.refresh+t+'1st='+draw.time[0]+t+'last='+draw.time[1]+t+'fps='+fps
+r+'Relative'+t+'x='+draw.o.x+t+'y='+draw.o.y+(isMouseIn()?t+'rgb='+pickColor(1):'')
+r+'DrawOfst'+t+'x='+draw.cur.x+t+'y='+draw.cur.y+t+'btn='+draw.btn
+r+'Previous'+t+'x='+draw.prev.x+t+'y='+draw.prev.y+t+'chain='+mode.click+(c?''
+r+'StpStart'+t+'x='+c.prev.x+t+'y='+c.prev.y
+r+'Step_End'+t+'x='+c.cur.x+t+'y='+c.cur.y:'')+'</td></tr></table>'+b;
}

function updateViewport(delta) {
var	i, t = '', p = ['-moz-','-webkit-','-o-',''];
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
	if (draw.pan) t += 'translate('+draw.pan.x+'px,'+draw.pan.y+'px)';
	if (draw.angle) t += 'rotate('+draw.a360+'deg)';
	if (draw.zoom != 1) t += 'scale('+(draw.zoom)+')';
	for (i in p) canvas.style[p[i]+'transform'] = t;	//* <- not working in opera11.10, though should be possible
	updateDebugScreen();
}

function updatePosition(event) {
var	i = select.shapeFlags[select.shape.value], o = (
	!  ((i & 2) && mode.shape && !mode.step)
	&& ((i & 4) || ((draw.active?c2d.lineWidth:tool.width) % 2))
	? DRAW_PIXEL_OFFSET : 0);	//* <- maybe not a 100% fix yet

	draw.o.x = event.pageX - draw.field.offsetLeft;
	draw.o.y = event.pageY - draw.field.offsetTop;
	if (draw.pan && !(draw.turn && draw.turn.pan)) for (i in draw.o) draw.o[i] -= draw.pan[i];
	if (!draw.turn && (draw.angle || draw.zoom != 1)) {
	var	r = getCursorRad(2, draw.o.x, draw.o.y);
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
	,	d:dist(y, x)	//* <- looks stupid, will do for now
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
			if (draw.line.started) c2d.quadraticCurveTo(draw.prev.x, draw.prev.y, (draw.cur.x + draw.prev.x)/2, (draw.cur.y + draw.prev.y)/2);
		} else c2d.lineTo(draw.cur.x, draw.cur.y);
		draw.line.preview =	!(draw.line.started = true);
	} else if (draw.line.back) {
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
				c2s.clearRect(0, 0, canvas.width, canvas.height);
				c2s.beginPath();
				drawShape(c2s, (mode.step && (sf & 4) && (!draw.step || !draw.step.done))?2:s);
				c2s.stroke();
				c2d.drawImage(c2s.canvas, 0, 0);			//* <- draw 2nd canvas overlay with sole shape
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
		} else if (m || draw.line.back || !draw.line.started) {//* <- draw 1 pixel on short click, regardless of mode or browser
			c2d.lineTo(draw.cur.x, draw.cur.y + (draw.cur.y == draw.prev.y ? 0.01 : 0));
		}
		if (sf & 4) used.move = 'Move'; else if (!(sf & 8) || mode.step) c2d.stroke();
		historyAct();
		draw.active = draw.step = draw.btn = 0;
		if (cue.autoSave < 0) autoSave(); else cue.autoSave = 1;
		if (mode.click && event.shiftKey) return mode.click = 0, drawStart(event);
	}
	updateDebugScreen();
}

function drawShape(ctx, i, clear) {
var	s = draw.step, v = draw.prev, r = draw.cur;
	switch (parseInt(i)) {
	//* rect
		case 2:	if (s) {
			//* show pan source area
				ctx.strokeRect(s.prev.x, s.prev.y, s.cur.x-s.prev.x, s.cur.y-s.prev.y);
			} else
			if (clear) {
				c2d.clearRect(v.x, v.y, r.x-v.x, r.y-v.y);
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
			ctx.arc(xCenter, yCenter, radius, 0, 7);

			if (clear) insideClear(c2d);
			else if (ctx.fillStyle != A0) ctx.fill();
		break;
	//* ellipse
		case 4:
		case 5:	if (s) x = r.x, y = r.y, v = s.prev, r = s.cur;
		var	xCenter = (v.x+r.x)/2
		,	yCenter = (v.y+r.y)/2
		,	xRadius = Math.max(1, Math.abs(r.x-xCenter))
		,	yRadius = Math.max(1, Math.abs(r.y-yCenter)), a = 1, b = 1;

			ctx.save();
			if (s) {
				if (xRadius < yRadius) xCenter /= a = xRadius/yRadius, ctx.scale(a, b); else
				if (xRadius > yRadius) yCenter /= b = yRadius/xRadius, ctx.scale(a, b);

			var	x = x/a - xCenter
			,	y = y/b - yCenter
			,	r1 = Math.max(xRadius, yRadius)
			,	r2 = dist(x, y)
			,	a3 = Math.min(Math.PI*2*(tool.width+1)/r1, Math.PI/18)
			,	a2 = Math.atan2(y, x)
			,	a1 = a2-a3;

				ctx.moveTo(xCenter + Math.cos(a1)*r1, yCenter + Math.sin(a1)*r1);
				ctx.lineTo(xCenter + Math.cos(a2)*r2, yCenter + Math.sin(a2)*r2);
				ctx.arc(xCenter, yCenter, r1, a2+a3, a1+Math.PI*2);
			} else {
				if (xRadius < yRadius) ctx.scale(a = xRadius/yRadius, 1), xCenter /= a; else
				if (xRadius > yRadius) ctx.scale(1, b = yRadius/xRadius), yCenter /= b;

				ctx.moveTo(xCenter + xRadius/a, yCenter);
				ctx.arc(xCenter, yCenter, Math.max(xRadius, yRadius), 0, 7);
			}
			ctx.restore();

			if (clear) insideClear(c2d);
			else if (ctx.fillStyle != A0) ctx.fill();
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
		//* straigth
				ctx.moveTo(v.x, v.y);
				ctx.lineTo(r.x, r.y);
			}
	}
	ctx.moveTo(r.x, r.y);
}

function insideClear(c) {
	c.save();
	c.clip();
	c.clearRect(0, 0, canvas.width, canvas.height);
	c.restore();
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
		if (n) c2d.fillRect(0, 0, canvas.width, canvas.height);
		c2d.putImageData(d, x, y);
	}
}

function fillCheck() {
var	d = draw.history.cur(), i = d.data.length;
	while (--i) if (d.data[i] != d.data[i%4]) return 0; return 1;		//* <- fill flood confirmed
}

function fillScreen(i) {
	if (isNaN(i)) {
		used.wipe = 'Wipe';
		c2d.clearRect(0, 0, canvas.width, canvas.height);
	} else
	if (i < 0) {
		historyAct(false);
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
		c2d.putImageData(d, 0, 0);
		return;
	} else {
		used.fill = 'Fill';
		c2d.fillStyle = 'rgb(' + tools[i].color + ')';
		c2d.fillRect(0, 0, canvas.width, canvas.height);
	}
	cue.autoSave = 0;
	historyAct();
}

function pickColor(keep, c, event) {
	if (c) {
	var	d = c.ctx.getImageData(0, 0, c.width, c.height);
		c = (event.pageX - c.offsetLeft
		+   (event.pageY - c.offsetTop)*c.width)*4;
	} else {
		d = draw.history.cur();
		c = (Math.floor(draw.o.x) + Math.floor(draw.o.y)*canvas.width)*4;
	}
	c = (d.data[c]*65536 + d.data[c+1]*256 + d.data[c+2]).toString(16);
	while (c.length < 6) c = '0'+c; c = '#'+c;
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

function updateSlider(i,e) {
var	k = (e?i:BOWL[i])
,	s = id('range'+k)
,	t = id('text'+k) || s
,	r = (e?s:RANGE[i])
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
}

function updateSliders(s) {
	if (s && s.id) {
	var	prop = s.id[s.id.length-1];
		for (i in BOW) if (prop == BOWL[i]) {
			tool[BOW[i]] = parseFloat(s.value);
			return updateSlider(i);
		}
		return updateSlider(prop, s);
	} else {
		if (s) updateSlider(s); else
		for (i in BOW) updateSlider(i);
		if (draw.o.length) {
			drawEnd();
			s = tool.width+4;
			c2d.putImageData(draw.history.cur(), 0, 0, draw.o.x - s/2, draw.o.y - s/2, s, s);
			drawCursor();
		}
	}
}

function updateShape(s) {
	if (!isNaN(s)) select.shape.value = s, s = 0;
	s = select.shapeFlags[(s?s:s=select.shape).value];
	setClass(id('bottom'), (s & 1?'b c':(s & 4?'a b':'a c')));
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

function updateDim(i) {
	if (i) {
	var	a = id('img-'+i), b, c = canvas[i], v = orz(a.value);
		canvasShape[i] = canvas[i] = a.value = v = (
			v < (b = select.imgLimits[i][0]) ? b : (
			v > (b = select.imgLimits[i][1]) ? b : v)
		);
		if (v > c) {
			draw.screen();
			historyAct(0);
		}
	}
	if (!i || i[0] == 'h') {
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
		if ((b = outside.restmin) && ((b = eval(b).offsetWidth) > v)) v = b;
		c.innerHTML = a+'{max-width:'+v+'px;}';
	}
}

function toolTweak(prop, value) {
	for (i in BOW) if (prop == BOWL[i]) {
	var	b = BOW[i];
		if (value > 0) tool[b] = value;
		else {
		var	v = new Number(tool[b]), s = RANGE[i].step;
			tool[b] = (value ? v-s : v+s);
		}
		return updateSliders(i);
	}
}

function toolSwap(back) {
	if (back == 3) {
		for (t in TOOLS_REF)
		for (i in TOOLS_REF[t]) tools[t][i] = TOOLS_REF[t][i];
		for (i in select.lineCaps) select[i].value = 0;
		if (mode.shape) toggleMode(1);
		updateShape(0);
		tool.width = 3; //* <- arbitrary default
	} else
	if (back) {
		back = TOOLS_REF[back-1];
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
	if (i >= 0 && i < modes.length) {
	var	n = modes[i], v = mode[n];
		if (!keep) v = mode[n] = !v;
		if (e = id('check'+modeL[i])) setClass(e, v ? 'button-active' : 'button');
		if (n == 'debug') {
			text.debug.textContent = '';
			interval.fps ? clearInterval(interval.fps) : (interval.fps = setInterval(fpsCount, 1000));
		}
	} else alert(lang.bad_id);
}

function unixDateToHMS(t,u,y) {
var	d = t ? new Date(t+(t >0?0:new Date())) : new Date(), t = ['Hours','Minutes','Seconds'], u = 'get'+(u?'UTC':'');
	if (y) t = ['FullYear','Month','Date'].concat(t);
	for (i in t) if ((t[i] = d[u+t[i]]()+(y && i == 1?1:0)) < 10) t[i] = '0'+t[i];
	d = '-', u = (y > 1?d:':');
	return y ? t[0]+d+t[1]+d+t[2]+(y > 1?'_':' ')+t[3]+u+t[4]+u+t[5] : t.join(u);
}

function timeElapsed() {text.timer.textContent = unixDateToHMS(timer += 1000, 1);}
function autoSave() {if (mode.autoSave && cue.autoSave && !(cue.autoSave = (draw.active?-1:0))) sendPic(2,true);}

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

function saveDL(content, suffix) {
	if (DL) {
		container.appendChild(a = document.createElement('a'));
		a.href = content, a[DL] = unixDateToHMS(0,0,2)+'_'+draw.time.join('-')+suffix;
		a.click();
		setTimeout(function() {container.removeChild(a);}, 5678);
	} else window.open(content, '_blank');
}

function sendPic(dest, auto) {
var	a = auto || false, c, d, e, i, t;
	draw.screen();

	function confirmShowTime(la, s) {
		if (s) {
		var	a = s.split('-'), i,t,n = ' \r\n', r = la.join(n);
			for (i = 0; i < 2; i++) t = +a[i], r += n+(t ? unixDateToHMS(t,0,1) : '-');
		} else r = la[0]
		return confirm(r);
	}

	switch (dest) {
	case 0:
	case 1: saveDL(c = canvas.toDataURL(dest?IJ:''), dest?'.jpg':'.png');
		break;
//* save
	case 2:
		if (fillCheck()) return a?c:alert(lang.no.drawn);
		c = canvas.toDataURL();
		if (!LS) return a?c:alert(lang.no.LS);
		d = LS[CR[1].R];
		if (d == c) return a?c:alert(lang.no.change);
		t = LS[CR[1].T], e = LS[CR[2].R] || 0;
		if (!a && e == c) {
			LS[CR[1].R] = e;
			LS[CR[1].T] = LS[CR[2].T];
			LS[CR[2].R] = d;
			LS[CR[2].T] = t;
			alert(lang.found_swap);
		} else
		if (a || confirmShowTime(lang.confirm.save, t)) {
			function rem(a) {var r = 'RT', i = r.length; while (i--) LS.removeItem(CR[a][r[i]]);}
			try {
				if (e) rem(2);
				if (t) {
					rem(1);
					LS[CR[2].R] = d;
					LS[CR[2].T] = t;
				}
				LS[CR[1].R] = c;
				LS[CR[1].T] = draw.time.join('-')+(used.read?'-'+used.read:'');
			} catch(e) {
				rem(1), rem(2);
				try {
					LS[CR[1].R] = d;
					LS[CR[1].T] = t;
				} catch(i) {rem(1); e.message += '\n'+i.message;}
				return alert(lang.no.space+'\nError code: '+e.code+', '+e.message), c;
			}
			id('saveTime').textContent = unixDateToHMS();
			setClass(id('buttonL'), 'button');
			cue.autoSave = 0;
		}
		break;
//* load
	case 3:
		if (!LS) return alert(lang.no.LS);
		t = LS[CR[1].T];
		if (!t) return;
		d = LS[CR[1].R];
		if (d == (c = canvas.toDataURL())) {
			if ((!(t = LS[CR[2].T]) || ((d = LS[CR[2].R]) == c))) return alert(lang.no.change);
		}
		if (confirmShowTime(lang.confirm.load, t)) {
			t = t.split('-');
			if (t.length > 2) used.read = 'Read File: '+t.slice(2).join('-').replace(/^[^:]+:\s+/, '');
			draw.time = t.slice(0,2), a = id('saveTime'), a.textContent = unixDateToHMS(+t[1]), a.title = new Date(+t[1]);
			readPic(d);
			used.LS = 'Local Storage';
		}
		break;
	case 4:	
		if (a || ((outside.read || (outside.read = id('read'))) && (a = outside.read.value))) {
	//		draw.time = [0, 0];
			used.read = 'Read File: '+readPic(a);
		}
		break;
//* send
	default:
		if (dest) alert(lang.bad_id+'\n\nid='+dest+'\na='+auto); else
		if (!outside.send) alert(lang.no.form); else
		if (fillCheck()) alert(lang.no.drawn); else {
			a = select.imgLimits, c = 'send';
			for (i in a) if (canvas[i] < a[i][0] || canvas[i] > a[i][1]) c = 'size';
		}
		if (c && confirm(lang.confirm[c])) {
			if (!outside.send.tagName) {
				setId(e = document.createElement('form'), 'send');
				e.setAttribute('method', (outside.send.length && outside.send.toLowerCase() == 'get')?'get':'post');
				container.appendChild(outside.send = e);
			}
		var	pngData = sendPic(2, 1), jpgData, a = {txt:0,pic:0}, f = outside.send;
			for (i in a) if (!(a[i] = id(i))) {
				setId(e = a[i] = document.createElement('input'), e.name = i);
				e.type = 'hidden';
				f.appendChild(e);
			}
			e = pngData.length;
			d = (((i = outside.jp || outside.jpg_pref)
				&& (e > i)
				&& (((c = canvas.width * canvas.height
				) <= (d = select.imgRes.width * select.imgRes.height
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
	return c;
}

function readPic(s) {
	if (!s || s == 0 || (!s.data && !s.length)) return;
	if (!s.data) s = {data: s, name: (0 === s.indexOf('data:') ? s.split(',', 1) : s)};
var	d = draw.time, e = new Image(), t = +new Date, i;
	for (i in d) if (!d[i]) d[i] = t;
	e.setAttribute('onclick', 'this.parentNode.removeChild(this); return false');
	e.onload = function () {
		for (i in select.imgRes) id('img-'+i).value = canvasShape[i] = canvas[i] = e[i];
		updateDim();
		c2d.drawImage(e,0,0);
		historyAct();
		cue.autoSave = 0;
		if (d = e.parentNode) d.removeChild(e);
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
				sendPic(4, {
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
		function c(s) {return s.charCodeAt(0);}
	var	n = event.keyCode - c('0');
		if ((n?n:n=10) > 0 && n < 11) {
		var	k = [event.altKey, event.ctrlKey, 1], i;
			for (i in k) if (k[i]) return toolTweak(BOWL[i], RANGE[i].step < 1 ? n/10 : (n>5 ? (n-5)*10 : n));
		} else
		switch (event.keyCode) {
			case 27:	drawEnd();	break;	//* Esc
			case 36:updateViewport();	break;	//* Home

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
			case 120:	sendPic(0);	break;
			case 118:	sendPic(1);	break;
			case 113:	sendPic(2);	break;
			case 115:	sendPic(3);	break;
			case 117:	sendPic(4);	break;
			case 119:	sendPic();	break;

			case c('N'):	updateShape(0);	break;
			case c('P'):	updateShape(1);	break;
			case c('R'):	updateShape(2);	break;
			case c('T'):	updateShape(3);	break;
			case c('Y'):	updateShape(4);	break;
			case c('Q'):	updateShape(5);	break;
			case c('M'):	updateShape(6);	break;

			case c('B'):
			case c('O'):
			case c('W'): toolTweak(String.fromCharCode(event.keyCode), event.altKey?-1:0); break;

			case 42:
			case 106:
				for (i = 1, k = ''; i < 3; i++) k +=
'<br>Save'+i+'.time: '+LS[CR[i].T]+(LS[CR[i].R]?', size: '+LS[CR[i].R].length:'');
				text.debug.innerHTML = getSendMeta()+'<br>'+replaceAll(
"\n<a href=\"javascript:var s=' ',t='';for(i in |)t+='\\n'+i+' = '+(|[i]+s).split(s,1);alert(t);\">|.props</a>"+
"\n<a href=\"javascript:var t='',o=|.o;for(i in o)t+='\\n'+i+' = '+o[i];alert(t);\">|.outside</a>"+
(outside.read?'':'<br>\nF6=read: <textarea id="|-read" value="/9.png"></textarea>'), '|', NS)+CR+','+CT+k; break;

			default: if (mode.debug) text.debug.innerHTML += '\n'+String.fromCharCode(event.keyCode)+'='+event.keyCode;
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




this.init = function() {
	if (isTest()) document.title += ': '+NS+' '+INFO_VERSION;
var	a, b, c = 'canvas', d = '<div id="', e = '"></div>', f, g, h, i, j, k, n = '\n	', o = outside, p, s = '&nbsp;';
	setContent(container = id(),
n+d+'load"><'+c+' id="'+c+'" tabindex="0">'+lang.no.canvas+'</'+c+'></div>'+
//n+
d+'right'+e+n+d+'bottom'+e+n+d+'debug'+e+'\n');

	if (!(canvas = id(c)).getContext) return;
	canvasShape = document.createElement(c);
	for (i in select.imgRes) {
		canvasShape[i] = canvas[i] = (o[a = i[0]]?o[a]:o[a] = (o[i]?o[i]:select.imgRes[i]));
		if ((o[b = a+'l'] || o[b = i+'Limit']) && (f = o[b].match(regLimit))) select.imgLimits[i] = [orz(f[1]), orz(f[2])];
	}

	c2s = canvasShape.getContext('2d');
	c2d = canvas.getContext('2d');
	c2d.fillStyle = 'white';
	c2d.fillRect(0, 0, o.w, o.h);

	document.addEventListener('dragover'	, dragOver	, f = false);
	document.addEventListener('drop'	, drop		, f);
	document.addEventListener('mousedown'	, drawStart	, f);
	document.addEventListener('mousemove'	, drawMove	, f);	//* <- using 'document' to prevent negative clipping
	document.addEventListener('mouseup'	, drawEnd	, f);
	document.addEventListener('keypress'	, browserHotKeyPrevent, f);
	document.addEventListener('keydown'	, hotKeys	, f);
	document.addEventListener('mousewheel'	, e = hotWheel	, f);
	document.addEventListener('wheel'	, e, f);
	document.addEventListener('scroll'	, e, f);
	canvas.setAttribute('onscroll'		, f = 'return false;');
	canvas.setAttribute('oncontextmenu'	, f);

	c = '</td><td class="r">', a = ': '+c+'	', e = n+'	', f = e+'	', b = e+d+'colors">'+d+'sliders">', i = BOW.length;
	while (i--) {
		b += f+'<span id="slider'+BOWL[i]+'"><input type="range" id="range'+BOWL[i]+'" onChange="updateSliders(this)';
		for (j in RANGE[i]) b += '" '+j+'="'+RANGE[i][j];
		b += '"></span>	'+lang.tool[BOWL[i]]+(i?'<br>':'');
	}

	b += e+'</div>'+e+'<table width="100%"><tr><td>'
+f+lang.shape	+a+'<select id="shape" onChange="updateShape(this)"></select>';
	for (i in select.lineCaps) b += c+'<select id="'+i+'" title="'+(select.lineCaps[i] || i)+'"></select>';
	setContent(id('right'), b+'	</td></tr><tr><td>'
+f+lang.hex	+a+'<input type="text" value="#000" id="color-text" onChange="updateColor()" title="'+lang.hex_hint+'">	'+c
+f+lang.palette	+a+'<select id="palette" onChange="updatePalette()"></select>	</td></tr></table>'
+f+d+'palette-table"></div>'+e+'</div>'+e+d+'info">'+e+'</div>'+n);

	a = '<a href="javascript:void(0);" onClick="', b = '">', c = '</abbr>', d = '';
	for (i in select.imgRes) d += (d?' x ':'')
+'<input type="text" value="'+o[i[0]]+'" id="img-'+i+'" onChange="updateDim(\''+i+'\')" title="'+lang.size_hint+select.imgLimits[i].join(lang.range_hint)+'">';

	b = '<abbr title="', h = '<span class="rf">', g = h+s+b
+NS.toUpperCase()+', '+INFO_ABBR+', '+lang.info_pad+', '+INFO_DATE+'">'+INFO_VERSION+'</abbr>.</span>';

	setContent(id('info'), f+replaceAll(
'<p class="L-open">'+lang.info_top+'</p>\
|<p>	'+lang.info.join('|<br>	').replace(/\{([^};]+);([^}]+)}/g, a+'$1()">$2</a>')
+':	'+h+b+(new Date())+'" id="saveTime">'+lang.info_no_save+'</abbr>.</span>\
|<br>	'+a+'toggleView(\'timer\')" title="'+lang.hide_hint+'">'+lang.info_time+'</a>'
+':	'+h+'<span id="timer">'+lang.info_no_time+'</span>.</span>\
|</p>	<p class="L-close"> ', '|', f)
+lang.info_drop+g+'</p>'
+f+'<div>'+lang.size+':	'+d
+f+'</div>'+e);

	if (c = (canvas.height < BOTH_PANELS_HEIGHT)) toggleView('info');
	for (i in text) text[i] = id(i);
	draw.field = id('load');
	draw.history.data = new Array(o.undo);

	a = 'historyAct(', b = 'button', d = 'toggleMode(', e = 'sendPic(', f = 'fillScreen(';
	a = [
//* subtitle, hotkey, pictogram, function, id
	['undo'	,'Z'	,'&#x2190;'	,a+'-1)',b+'U'
],	['redo'	,'X'	,'&#x2192;'	,a+'1)'	,b+'R'
],
0,	['fill'	,'F'	,s		,f+'0)'	,(a='color')+'F'
],	['swap'	,'S'	,'&#X21C4;'	,'toolSwap()'
],	['erase','D'	,s		,f+'1)'	,a+'B'
],
0,	['invert','I'	,'&#x25D0;'	,f+'-1)'
],	['flip_h','H'	,'&#x2194;'	,f+'-2)'
],	['flip_v','V'	,'&#x2195;'	,f+'-3)'
],
0,	['pencil','A'	,'i'		,(a='toolSwap(')+'1)'
],	['eraser','E'	,'&#x25CB;'	,a+'2)'
],	['reset' ,'G'	,'&#x25CE;'	,a+'3)'
],
0,	['line|area|copy','L'	,'&ndash;|&#x25A0;|&#x25A4;'	,d+'1)'	,(a='check')+'L'
],	['curve|outline|rect','U'	,'~|&#x25A1;|&#x25AD;'	,d+'2)'	,a+'U'
],	['cursor','F3'	,'&#x25CF;'	,d+'4)'	,a+'V'
],
0,	['png'	,'F9'	,'P'	,e+'0)'	,b+'P'
],	['jpeg'	,'F7'	,'J'	,e+'1)'	,b+'J'
],	['save'	,'F2'	,'!'	,e+'2)'
],	['load'	,'F4'	,'?'	,e+'3)'	,b+'L'
],!o.read || 0 == o.read?1:
	['read'	,'F6'	,'&#x21E7;'	,e+'4)'
],!o.send || 0 == o.send?1:
	['done'	,'F8'	,'&#x21B5;'	,e+')'
],c?0:1
,!c?1:	['info'	,'F1'	,'?'	,'showInfo()'	,b+'H'
]], f = id('bottom'), d = '<div class="button-', c = '</div>';

	function btnContent(e, subt, pict) {
	var	t = lang.b[subt];
		e.title = (t.t?t.t:t);
		setContent(e, d+'key">'+k[1]+c+pict+d+'subtitle"><br>'+(t.t?t.sub:subt)+c);
		return e;
	}

	for (i in a) if (1 !== (k = a[i])) {
		if (k) {
			e = document.createElement(b);

			if (k[0].indexOf('|') > 0) {
				s = k[0].split('|');
				p = k[2].split('|');
				for (j in s) setClass(e.appendChild(btnContent(document.createElement('div'), s[j], p[j])), 'abc'[j]);
			} else btnContent(e, k[0], k[2]);

			setClass(e, b);
			setEvent(e, 'onclick', k[3]);
			if (k.length > 4) setId(e, k[4]);
			f.appendChild(e);
		} else f.innerHTML += '&nbsp;';
	}
	for (name in mode) if (mode[modes[i = modes.length] = name]) toggleMode(i,1);

	if (!LS || !LS[CT]) setClass(id('buttonL'), 'button-disabled');

	i = (a = 'JP').length;
	while (i--) if (b = id('button'+a[i])) setEvent(b, 'onmouseover', 'updateSaveFileSize(this)');

	a = 'range', b = 'text', d = (id(a+'W').type == a);
	for (i in BOW) if (d) {
		e = document.createElement('input');
		setId(e, (e.type = b)+BOWL[i]);
		setEvent(e, 'onchange', 'updateSliders(this)');
		id('slider'+BOWL[i]).appendChild(e);
	} else 	id(a+BOWL[i]).type = b;

	for (i in (a = ['input', 'select', 'span', 'button', 'a']))
	for (c in (b = container.getElementsByTagName(a[i])))
	for (e in (d = ['onchange', 'onclick', 'onmouseover'])) if ((f = b[c][d[e]]) && !self[f = (''+f).match(regFunc)[1]]) self[f] = eval(f);

	d = 'download', DL = (d in b[0]?d:'');
	a = select.options, c = select.translated || a, f = (LS && LS.lastPalette && palette[LS.lastPalette]) ? LS.lastPalette : 1;
	for (b in a) {
		e = select[b] = id(b);
		for (i in a[b]) (
			e.options[e.options.length] = new Option(c[b][i], i)
		).selected = (b == 'palette'?(i == f):!i);
	}

//* safe palette constructor, step recomended to be: 1, 3, 5, 15, 17, 51, 85, 255
  function generatePalette(p, step, slice) {
	if (!(p = palette[p])) return;
var	letters = [0, 0, 0], l = p.length;
	if (l) {p[l] = '\t'; p[l+1] = '\n';}
	while (letters[0] <= 255) {
		p[l = p.length] = '#';
		for (var i = 0; i < 3; i++) {
		var	s = letters[i].toString(16);
			if (s.length == 1) s = '0'+s;
			p[l] += s;
		}
		letters[2] += step;
		if (letters[2] > 255) {letters[2] = 0; letters[1] += step;}
		if (letters[1] > 255) {letters[1] = 0; letters[0] += step;}
		if ((letters[1] == 0 || (letters[1] == step * slice)) && letters[2] == 0)
			p[l+1] = '\n';
	}
  }

	generatePalette(1, 85, 0);
	toolSwap(3);
	updatePalette();
	updateSliders();
	updateViewport();
	historyAct(0);
}; //* END this.init()




function isTest() {
	if (CR[0] !== 'C') return !o.send;
var	o = outside, v = id('vars'), e, i, j, k
,	f = o.send = id('send')
,	r = o.read = id('read'), a = [v,f,r];
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
/*	a) varname; var2=;		//noequal=1, empty=0
	b) warname=two=3=last_val;	//samevalue, rightmost
*/		}
		break;	//* <- no care about the rest
	}
	k = 'y2', i = k.length, j = (o.saveprfx?o.saveprfx:NS)+CR, CR = [];
	while (i) CR[i--] = {R:e = j+k[i], T:e+CT};
	CT = CR[1].T;
	o.t0 = o.t0 > 0 ? o.t0+'000' : +new Date;
	if ((o.undo = orz(o.undo)) < 3) o.undo = 123;
	if (!o.lang) o.lang = document.documentElement.lang || 'en';
	if (o.lang == 'ru')
select.lineCaps = {lineCap: 'край', lineJoin: 'сгиб'}
, select.translated = {
	shape	: ['линия', 'замкнутая линия', 'прямоугольник', 'круг', 'овал', 'овал: речь', 'сдвиг']
,	lineCap	: ['круг <->', 'срез |-|', 'квадрат [-]']
,	lineJoin: ['круг -x-', 'срез \\_/', 'угол V']
,	palette	: ['история', 'авто', 'разное', 'Тохо', 'градиент']
}, lang = {
	bad_id:		'Ошибка выбора.'
,	confirm: {
		send:	'Отправить рисунок в сеть?'
	,	size:	'Превышен размер полотна. Отправить всё равно?'
	,	save: [	'Сохранить рисунок в память браузера?', 'Заменить старую копию, изменённую:']
	,	load: [	'Вернуть рисунок из памяти браузера?', 'Восстановить копию, изменённую:']
},	found_swap:	'Рисунок был в запасе, поменялись местами.'
,	no: {
		LS:	'Локальное Хранилище (память браузера) недоступно.'
	,	space:	'Ошибка сохранения, нет места.'
	,	files:	'Среди файлов не найдено изображений.'
	,	form:	'Назначение недоступно.'
	,	change:	'Нет изменений.'
	,	canvas:	'Ваша программа не поддерживает HTML5-полотно.'
	,	drawn:	'Полотно пусто.'
}, tool: {	B:	'Тень'
	,	O:	'Непрозр.'
	,	W:	'Толщина'
},	shape:		'Форма'
,	palette:	'Палитра'
,	sat:		'Насыщ.'
,	hex:		'Цвет'
,	hex_hint:	'Формат ввода — #a, #f90, #ff9900, или 0,123,255'
,	hide_hint:	'Кликните, чтобы спрятать или показать.'
,	info_top:	'Управление (указатель над полотном):'
,	info: [	'C / средний клик = подобрать цвет с рисунка'
	,	'N / P / R / T / Y / Q / M = выбор формы'
//	,	'Shift + клик = цепочка форм, Esc = {drawEnd;отмена}'
	,	'Ctrl + тяга = поворот полотна, Home = {updateViewport;сброс}'
	,	'Alt + тяга = масштаб, Shift + т. = сдвиг рамки'
	,	'1-10		/ колесо мыши	/ (Alt +) W = толщина кисти'
	,	'Ctrl + 1-10	/ колесо	/ (Alt +) O = прозрачность'
	,	'Alt + 1-10	/ колесо	/ (Alt +) B = размытие тени'
	,	'Автосохранение раз в минуту'
],	info_no_save:	'ещё не было'
,	info_no_time:	'ещё нет'
,	info_time:	'Времени прошло'
,	info_pad:	'доска для набросков'
,	info_drop:	'Можно перетащить сюда файлы с диска.'
,	size:		'Размер полотна'
,	size_hint:	'Число от '
,	range_hint:	' до '
, b: {	undo:	{sub:'назад',	t:'Отменить последнее действие.'
},	redo:	{sub:'вперёд',	t:'Отменить последнюю отмену.'
},	fill:	{sub:'залить',	t:'Залить полотно основным цветом.'
},	erase:	{sub:'стереть',	t:'Залить полотно запасным цветом.'
},	invert:	{sub:'инверт.',	t:'Обратить цвета полотна.'
},	flip_h:	{sub:'отразить',t:'Отразить полотно слева направо.'
},	flip_v:	{sub:'перевер.',t:'Перевернуть полотно вверх дном.'
},	pencil:	{sub:'каранд.',	t:'Инструмент — тонкий простой карандаш.'
},	eraser:	{sub:'стёрка',	t:'Инструмент — толстый белый карандаш.'
},	swap:	{sub:'смена',	t:'Поменять инструменты местами.'
},	reset:	{sub:'сброс',	t:'Сбросить инструменты к начальным.'
},	line:	{sub:'прямая',	t:'Прямая линия 1 зажатием.'
},	curve:	{sub:'кривая',	t:'Сглаживать углы пути / кривая линия 2 зажатиями.'
},	area:	{sub:'закрас.',	t:'Закрашивать площадь геометрических фигур.'
},	outline:{sub:'контур',	t:'Рисовать контур геометрических фигур.'
},	copy:	{sub:'копия',	t:'Оставить старую копию.'
},	rect:	{sub:'прямоуг.',t:'Сдвиг прямоугольником.'
},	cursor:	{sub:'указат.',	t:'Показывать кисть на указателе.'
},	rough:	{sub:'п.штрих',	t:'Уменьшить нагрузку, пропуская перерисовку штриха.'
},	fps:	{sub:'п.кадры',	t:'Уменьшить нагрузку, пропуская кадры.'
},	png:	{sub:'сохр.png',t:'Сохранить рисунок в PNG файл.'
},	jpeg:	{sub:'сохр.jpg',t:'Сохранить рисунок в JPEG файл.'
},	save:	{sub:'сохран.',	t:'Сохранить рисунок в память браузера, 2 последние позиции по очереди.'
},	load:	{sub:'загруз.',	t:'Вернуть рисунок из памяти браузера, 2 последние позиции по очереди. \r\n\
Может не сработать в некоторых браузерах, если не настроить автоматическую загрузку и показ изображений.'
},	read:	{sub:'зг.файл',	t:'Прочитать локальный файл. \r\n\
Может не сработать вообще, особенно при запуске самой рисовалки не с диска. \r\n\
Вместо этого рекомендуется перетаскивать файлы из других программ.'
},	done:	{sub:'готово',	t:'Завершить и отправить рисунок в сеть.'
},	info:	{sub:'помощь',	t:'Показать или скрыть информацию.'
}}};
else lang = {
	bad_id:		'Invalid case.'
,	confirm: {
		send:	'Send image to server?'
	,	size:	'Canvas size exceeds limit. Send anyway?'
	,	save: [	'Save image to your browser memory?', 'Replace saved copy edited at:']
	,	load: [	'Restore image from your browser memory?', 'Load copy edited at:']
},	found_swap:	'Found image at slot 2, swapped slots.'
,	no: {
		LS:	'Local Storage (browser memory) not supported.'
	,	space:	'Saving failed, not enough space.'
	,	files:	'No image files found.'
	,	form:	'Destination unavailable.'
	,	change:	'Nothing changed.'
	,	canvas:	'Your browser does not support HTML5 canvas.'
	,	drawn:	'Canvas is empty.'
}, tool: {	B:	'Shadow'
	,	O:	'Opacity'
	,	W:	'Width'
},	shape:		'Shape'
,	palette:	'Palette'
,	sat:		'Saturat.'
,	hex:		'Color'
,	hex_hint:	'Valid formats — #a, #f90, #ff9900, or 0,123,255'
,	hide_hint:	'Click to show/hide.'
,	info_top:	'Hot keys (mouse over image only):'
,	info: [	'C / Mouse Mid = pick color from image'
	,	'N / P / R / T / Y / Q / M = select shape'
//	,	'Shift + click = chain shapes, Esc = {drawEnd;cancel}'
	,	'Ctrl + drag = rotate canvas, Home = {updateViewport;reset}'
	,	'Alt + d. = zoom, Shift + d. = move canvas frame'
	,	'1-10		/ Mouse Wheel /	(Alt +) W = brush width'
	,	'Ctrl + 1-10	/ Wheel /	(Alt +) O = brush opacity'
	,	'Alt + 1-10	/ Wheel /	(Alt +) B = brush shadow blur'
	,	'Autosave every minute, last saved'
],	info_no_save:	'not yet'
,	info_no_time:	'no yet'
,	info_time:	'Time elapsed'
,	info_pad:	'sketch pad'
,	info_drop:	'You can drag files from disk and drop here.'
,	size:		'Image size'
,	size_hint:	'Number from '
,	range_hint:	' to '
, b: {	undo:	'Revert last change.'
,	redo:	'Redo next reverted change.'
,	fill:	'Fill image with main color.'
,	erase:	'Fill image with back color.'
,	invert:	'Invert image colors.'
,	flip_h:	{sub:'flip hor.',t:'Flip image horizontally.'
},	flip_v:	{sub:'flip ver.',t:'Flip image vertically.'
},	pencil:	'Set tool to sharp black.'
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
,	save:	'Save image copy to your browser memory, two slots in a queue.'
,	load:	'Load image copy from your browser memory, two slots in a queue. \r\n\
May not work in some browsers until set to load and show new images automatically.'
,	read:	'Load image from your local file. \r\n\
May not work at all, especially if sketcher itself is not started from disk. \r\n\
Instead, it is recommended to drag and drop files from another program.'
,	done:	'Finish and send image to server.'
,	info:	'Show/hide information.'
}};
	return !o.send;
}




document.addEventListener('DOMContentLoaded', this.init, false);
document.write(replaceAll(replaceAdd('\n<style>\
#| .|-L-close {padding-bottom: 24px; border-bottom: 1px solid #000; border-right: 1px solid #000;}\
#| .|-L-open {padding-top: 24px; border-top: 1px solid #000; border-left: 1px solid #000;}\
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
#| a {color: #888;}\
#| a:hover {color: #000;}\
#| abbr {border-bottom: 1px dotted #111;}\
#| canvas {border: 1px solid #ddd; margin: 0; vertical-align: bottom; cursor:\
	url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAGElEQVR42mNgYGCYUFdXN4EBRPz//38CADX3CDIkWWD7AAAAAElFTkSuQmCC\'),\
	auto;}\
#| canvas:hover {border-color: #aaa;}\
#| input[type="range"] {width: 156px; height: 16px; margin: 0; padding: 0;}\
#| input[type="text"] {width: 48px;}\
#| select, #| #|-color-text {width: 78px;}\
#| {text-align: center; padding: 12px; background-color: #f8f8f8;}\
#|, #| input, #| select {font-family: "Arial"; font-size: 14pt; line-height: normal;}\
#|-bottom > button {border: 1px solid #000; width: 38px; height: 38px; margin: 2px; padding: 2px; font-size: 15px; line-height: 7px; text-align: center; cursor: pointer;}\
#|-bottom > button, #|-load canvas {box-shadow: 3px 3px rgba(0,0,0, 0.1);}\
#|-bottom {margin: 10px 0 -2px;}\
#|-debug td {width: 234px;}\
#|-info p {padding-left: 22px; line-height: 22px; margin: 0;}\
#|-info p, #|-palette-table table {color: #000; font-size: small;}\
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
#|-right {color: #888; width: 321px; margin: 0; margin-left: 12px; text-align: left; display: inline-block; vertical-align: top; overflow-x: hidden;}\
#| .|-a .|-a,\
#| .|-b .|-b,\
#| .|-c .|-c {display: none;}\
</style>', '}', '\n'), '|', NS)+'\n<div id="'+NS+'">Loading '+NS+'...</div>\n');
};
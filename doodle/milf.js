//* Global wrapper *-----------------------------------------------------------

var milf = new function () {
var	NS = 'milf'	//* <- namespace prefix, change here and above; BTW, tabs align to 8 spaces

//* Configuration *------------------------------------------------------------

,	INFO_VERSION = 'v1.16'	//* needs complete rewrite, long ago
,	INFO_DATE = '2014-07-16 — 2019-03-17'
,	INFO_ABBR = 'Multi-Layer Fork of DFC'
,	A0 = 'transparent', IJ = 'image/jpeg', SO = 'source-over', DO = 'destination-out'
,	CR = 'CanvasRecover', CT = 'Time', CL = 'Layers', DL
,	LS = this.LS = window.localStorage || localStorage
,	DRAW_PIXEL_OFFSET = 0.5, CANVAS_BORDER = 1
,	DRAW_HELPER = {lineWidth: 1, shadowBlur: 0, shadowColor: A0, strokeStyle: 'rgba(123,123,123,0.5)', globalCompositeOperation: SO}

,	mode = {debug:	false	//* <- safe to define here
	,	shape:	false	//* <- safe to define here;	straight line	/ fill area	/ copy
	,	step:	true	//* <- safe to define here;	curve		/ outline	/ part
	,	lowQ:	false
	,	erase:	false
	,	brushView:	false	//* <- safe to define here
	,	limitFPS:	false
	,	autoSave:	true
	,	globalHistory:	false
	}, modes = [], modeL = 'DLUQEVFAG'

,	RANGE = {
		B: {min: 0   , max: 100, step: 1}
	,	O: {min: 0.01, max: 1  , step: 0.01}
	,	W: {min: 1   , max: 100, step: 1}
	}, BOW = ['grid', 'blur', 'opacity', 'width'], BOWL = 'GBOW'

,	TOOLS_REF = [
		{grid: 1, blur: 0, opacity: 1.00, width:  1, clip: SO, color: '0,0,0'}		//* <- draw
	,	{grid: 0, blur: 0, opacity: 1.00, width: 20, clip: DO, color: '255,255,255'}	//* <- back
	], tools = [{}, {}], tool = tools[0]

,	select = {
		imgRes: {width:640, height:360}
	,	imgLimits: {width:[64,32767], height:[64,32767]}
	,	lineCaps: {lineCap:0, lineJoin:0}
	,	shapeFig: [1,1,6,2,3,4,5]
	,	shapeFlags: [1,10,66,2,2,2,66,20,28,50]
/* flags (sum parts, be careful; such a mess, for now):
1 = path, mode: step 1 line, step 2 curve
2 = area, mode: outline, fill, erase
4 = move, mode: copy, step 1 area
8 = path, closed polygon
16 = hide lineStyle
32 = show textStyle; click once to draw
64 = step 2 for all modes; click same point twice -> not 1 pixel drawn
*/
	,	clipBorder: ['', '#123', '#5ea']//, '#5ae', '#ff0', '#f40']
	,	options: {
			shape	: ['line', 'freehand poly', 'regular poly', 'rectangle', 'circle', 'ellipse', 'radiance', 'pan'/*, 'lasso', 'text'*/]
		,	lineCap	: ['round', 'butt', 'square']
		,	lineJoin: ['round', 'bevel', 'miter']
		,	filter	: ['scale', 'integral']
		,	font	: ['normal', 'compact', 'monospace']
		,	compose	: [SO, 'destination-over', 'source-atop', 'destination-atop', 'lighter', 'xor', DO]
		,	palette	: ['history', 'auto', 'legacy', 'Touhou', 'gradient', 'wheel']
	}}

,	PALETTE_COL_COUNT = 16	//* <- used if no '\n' found, for example - unformatted history
,	palette = [['#f']
//* '\t' = title, '\n' = line break + optional title, '\r' = special cases, '#f00' = hex color field, anything else = title + plaintext spacer
	, [	'#f', '#d', '#a', '#8', '#5', '#2', '#0',				'#a00', '#740', '#470', '#0a0', '#074', '#047', '#00a', '#407', '#704'
	, '\n',	'#7f0000', '#007f00', '#00007f', '#ff007f', '#7fff00', '#007fff', '#3', '#e11', '#b81', '#8b1', '#1e1', '#1b8', '#18b', '#11e', '#81b', '#b18'
	, '\n',	'#ff0000', '#00ff00', '#0000ff', '#ff7f00', '#00ff7f', '#7f00ff', '#6', '#f77', '#db7', '#bd7', '#7f7', '#7db', '#7bd', '#77f', '#b7d', '#d7b'
	, '\n',	'#ff7f7f', '#7fff7f', '#7f7fff', '#ffff00', '#00ffff', '#ff00ff', '#9', '#faa', '#eca', '#cea', '#afa', '#aec', '#ace', '#aaf', '#cae', '#eac'
	, '\n',	'#ffbebe', '#beffbe', '#bebeff', '#ffff7f', '#7fffff', '#ff7fff', '#c', '#fcc', '#fdc', '#dfc', '#cfc', '#cfd', '#cdf', '#ccf', '#dcf', '#fcd'

	], ['\tWin7'
	,	'#0', '#7f7f7f', '#880015', '#ed1c24', '#ff7f27', '#fff200', '#22b14c', '#00a2e8', '#3f48cc', '#a349a4'
	, '\n',	'#f', '#c3c3c3', '#b97a57', '#ffaec9', '#ffc90e', '#efe4b0', '#b5e61d', '#99d9ea', '#7092be', '#c8bfe7'
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
	, '\nClassic', '#000000', '#000080', '#008000', '#008080', '#800000', '#800080', '#808000', '#c0c0c0', '\tApple II', '#000000', '#7e3952', '#524689', '#df4ef2', '#1e6952', '#919191', '#35a6f2', '#c9bff9'
	, '\nClassic', '#808080', '#0000ff', '#00ff00', '#00ffff', '#ff0000', '#ff00ff', '#ffff00', '#ffffff', '\tApple II', '#525d0d', '#df7a19', '#919191', '#efb5c9', '#35cc19', '#c9d297', '#a2dcc9', '#ffffff'
	, '\nCGA', '#0', '#00a', '#0a0', '#0aa', '#a00', '#a0a', '#aa0', '#a', '\tMSX', '#0', '#0', '#3eb849', '#74d07d', '#5955e0', '#8076f1', '#b95e51', '#65dbef'
	, '\nCGA', '#5', '#55f', '#5f5', '#5ff', '#f55', '#f5f', '#ff5', '#f', '\tMSX', '#db6559', '#ff897d', '#ccc35e', '#ded087', '#3aa241', '#b766b5', '#c', '#f'
	, '\nIBM PC/XT CGA', '#000000', '#0000b6', '#00b600', '#00b6b6', '#b60000', '#b600b6', '#b66700', '#b6b6b6', '\tC-64', '#000000', '#ffffff', '#984a43', '#79c1c7', '#9b51a5', '#67ae5b', '#52429d', '#c9d683'
	, '\nIBM PC/XT CGA', '#676767', '#6767ff', '#67ff67', '#67ffff', '#ff6767', '#ff67ff', '#ffff67', '#ffffff', '\tC-64', '#9b6639', '#695400', '#c37b74', '#626262', '#898989', '#a3e599', '#897bcd', '#adadad'
	, '\nZX Spectrum', '#0', '#0000ca', '#00ca00', '#00caca', '#ca0000', '#ca00ca', '#caca00', '#cacaca', '\tVIC-20', '#000000', '#ffffff', '#782922', '#87d6dd', '#aa5fb6', '#55a049', '#40318d', '#bfce72'
	, '\nZX Spectrum', '#0', '#0000ff', '#00ff00', '#00ffff', '#ff0000', '#ff00ff', '#ffff00', '#ffffff', '\tVIC-20', '#aa7449', '#eab489', '#b86962', '#c7ffff', '#ea9ff6', '#94e089', '#8071cc', '#ffffb2'

	], [	'all'	, '#0', '#f', '#fcefe2'
	, '\n', 'Reimu'	, '#fa5946', '#e5ff41', '', '', ''		//* <- mid-row spacers to align columns
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

	], '\rg', '\rw']

//* Set up (don't change) *----------------------------------------------------

,	noShadowBlurCurve = /^Opera.* Version\D*12\.\d+$/i.test(navigator.userAgent)	//* <- broken forever, sadly
,	noBorderRadius = /^Opera.* Version\D*1\d\.\d+$/i.test(navigator.userAgent)
,	abc = 'abc'.split('')
,	regLastNum = /^.*\D(\d+)$/
,	regHex = /^#*[0-9a-f]{6}$/i
,	regHex3 = /^#*([0-9a-f])([0-9a-f])([0-9a-f])$/i
,	reg255 = /^([0-9]{1,3}),\s*([0-9]{1,3}),\s*([0-9]{1,3})$/
,	reg255split = /,\s*/
,	regTipBrackets = /[ ]*\([^)]+\)$/
,	regFunc = /\{[^.]+\.([^(]+)\(/
,	regLimit = /^(\d+)\D+(\d+)$/

,	t0 = +new Date
,	self = this, outside = this.o = {}, lang, container
,	fps = 0, ticks = 0, timer = 0, loading = 0
,	interval = o0('fps,timer,save'), cue = {upd:{}}, hue
,	text = o0('debug,timer')
,	cnv = o0('view,draw,lower,current,upper,filter,temp'), ctx = {}
,	count = o0('layers,strokes,erases,undo'), used = {}, used_shape = {}

,	draw = {m:{}, o:{}, cur:{}, prev:{}
	,	refresh: 0
	,	line: o0('started,back,preview')
	,	time: {
			activeSum: 0
		,	all: [0,0]
		,	act: function(i) {
			var	a = this.all, t = +new Date, i = (i?t0:t);
				if (!a[0]) {
					a[0] = this.activeStart = i;
					a[1] = t;
				} else {
					if (!this.activeStart) this.activeStart = i; else
					if (t-a[1] > this.idle) {
						this.activeSum = this.sum(t);
						this.activeStart = t;
					}
				}
				return t;
			}
		,	sum: function(t) {
				return (
					this.activeStart
					? (this.all[1] || t || +new Date) - this.activeStart
					: 0
				) + this.activeSum;
			}
		}
	,	history: {layer:0, layers:[{show:1, color:'#f'}]
		,	cur: function(t) {
				return ((t || (t = this.layer)) && (t = this.layers[t]) ? t.data[t.pos] : 0);
			}
		,	act: function(i) {
				if (!this.layer) return 0;
			var	t = this.layers[this.layer], d = outside.undo;
				if (i < -9) i = -i;
				if (i && i < 9) {
			//* -1: Read: Back
					if (i < 0 && t.pos > 0) --t.pos, ++count.undo; else
			//* 1: Read: Forward
					if (i > 0 && t.pos < d && t.pos < t.last) ++t.pos, --count.undo; else return 0;
					draw.view(1);
					cue.autoSave = 1;
				} else {
			//* 0 or timestamp: Write
				var	dt = i || (+new Date);
					if (i !== false) t.reversable = 0;
					else if (t.reversable) return 0;
					else t.reversable = 1, draw.view();
					if (i !== 0) {
						if (t.pos < d) t.data.length = t.last = ++t.pos;
						else for (i = 0; i < d; i++) t.data[i] = t.data[i+1];
					}
					(t.data[t.pos] = ctx.draw.getImageData(0, 0, cnv.view.width, cnv.view.height)).date = dt;
				}
				return 1;
			}
		}
	,	preload: function(i) {
		var	y = this.history, c = y.layer, d = y.layers, a = (
				i ? {	lower: [1,c-1]
				,	draw: [c,c]
				,	upper: [c+1,d.length-1]
				} : {	draw: [c,c]
				}
			), j, k, l, t, s = this.shift || 0, z;
			for (i in ctx) ctx[i].globalCompositeOperation = SO;
			for (i in a) {
				ctx[i].clearRect(0, 0, cnv.view.width, cnv.view.height);
				for (j = a[i][0], l = a[i][1]; j <= l; j++) if (j && (t = d[j]).show && (k = y.cur(j))) {
					if (j == c) ctx[i].putImageData(k, 0, 0);
					else {
						if (t.clip && !(k = getClippedImageData(k, j))) continue;
						ctx.temp.clearRect(0, 0, cnv.view.width, cnv.view.height);
						ctx.temp.putImageData(k, 0, 0);
						if (t.blur) filterCanvas(t.blur, t.filter);
						ctx[i].globalAlpha = t.alpha/RANGE.A.max;
						ctx[i].drawImage(cnv.temp, z = (s?s*(j-c):0), z);
					}
				}
				ctx[i].globalAlpha = 1;
			}
		}
	,	view: function(i) {
			if (i) draw.preload(i === 2);
		var	y = this.history, c = y.layer, d = y.layers, a = ['lower', 'draw', 'upper'], j, k, l;
			if (d[0].show) {
				ctx.view.fillStyle = d[0].color;
				ctx.view.fillRect(0, 0, cnv.view.width, cnv.view.height);
			} else ctx.view.clearRect(0, 0, cnv.view.width, cnv.view.height);
			for (i in a) {
				ctx[j = a[i]].globalCompositeOperation = SO;
				if (c && i == 1) {
					l = d[c];
					if (l.clip) {
						k = ctx[j].getImageData(0, 0, cnv.view.width, cnv.view.height), l = getClippedImageData(k);
						if (!l) continue;
						ctx[j].putImageData(l, 0, 0);
					}
					if (l.blur) filterCanvas(l.blur, l.filter, j);
					ctx.view.globalAlpha = d[c].alpha/RANGE.A.max;
				} else ctx.view.globalAlpha = 1;
				ctx.view.drawImage(cnv[j], 0, 0);
			}
		}
	};

function historyAct(i) {
var	y = draw.history, c = y.layer, d = y.layers, z = d.length, x;
//* global history timeline:
	if (!c) {
		if (i === 1) c = +new Date; else
		if (i === -1); else return;
		function wiz(callback) {
			z = d.length;
			while (--z) {
				x = d[z];
				if (i < 0 && x.pos == 0) continue;
				if (i > 0 && x.pos == x.last) continue;
				x = x.data[x.pos+(i > 0?i:0)], callback(x?x.date:0);
			}
		}
		wiz(function(x) {if ((i < 0)?(c < x):(c > x)) c = x;});
		wiz(function(x) {if (c == x) y.layer = z, historyAct(i);});
		y.layer = 0, updateLayers(1);
	} else
//* separate history per layer:
	if (y.act(i)) {
		if ((x = d[0].max) && (x == z)) {
//* after loading saved layers stack, reorder as saved:
			c = [d[0]], delete d[0].max;
			while (--z) x = d[z], c[x.z] = x, delete x.z;
			y.layers = c, selectLayer(0,1);
		}
		updateDebugScreen(), updateLayers(!x), updateHistoryButtons();
	}
}

function updateHistoryButtons() {
var	y = draw.history, c = y.layer, y = c?[0,y.layers[c]]:y.layers, a = 'UR', b = 'button', d = b+'-disabled', i, j, k;
	for (i in a) {
		k = d;
		for (j in y) if (j > 0 && y[j].pos != (i > 0?y[j].last:0)) {k = b; break;}
		setClass(id(b+a[i]), k);
	}
	cue.upd = {J:1,P:1};
}

//* Layers manipulation *------------------------------------------------------

function newLayer(load) {
	if (draw.active) drawEnd();
var	y = draw.history, c = y.layer, d = y.layers, z, u;
	if (!isNaN(load) && !c) return;
	if (load === -1) {
//* merge down
		if (c < 2) return;
		if (z = y.cur()) {
			d[c].show = 0, u = y.cur(--c);
			u ?	ctx.draw.putImageData(u, 0, 0)
			:	ctx.draw.clearRect(0, 0, cnv.view.width, cnv.view.height);
			ctx.temp.putImageData(z, 0, 0);
			ctx.draw.globalAlpha = d[y.layer--].alpha/RANGE.A.max;
			ctx.draw.drawImage(cnv.temp, 0, 0);
			ctx.draw.globalAlpha = 1;
			historyAct();
			cue.autoSave = 1;
		}
		return updateLayers(2);
	}
//* new
var	x = {name: lang.layer.prefix+'_'+(++count.layers), data:[]};
	for (z in NEW_LAYER) x[z] = NEW_LAYER[z];
	if (load === 1) {
//* copy
		draw.preload();
		x.data[0] = ctx.draw.getImageData(0, 0, cnv.view.width, cnv.view.height);
		x.name = d[c].name+' (2)';
	} else
//* saved
	if (load) for (z in load) x[z] = load[z];
//* clean otherwise
	d[y.layer = d.length] = x;
	if (!x.z) moveLayer(++c), updateLayers(0,1);
}

function moveLayer(to) {
var	y = draw.history, c = y.layer, d = y.layers, u, x, z = d.length-1;
	if (!c) return 0;
//* input:
	if (isNaN(to)) u = to, to = z;
	else if (to < 0) to = c-1;
	else if (to == 0) to = c+1;
//* clip:
	if (to < 1) to = 1; else
	if (to > z) to = z;
//* go:
var	i = 0, j = (c > to?-1:1);
	while (c != to) x = d[c], d[c] = d[c+j], d[c += j] = x, i += j;
//* delete:
	if (u) y.layers.pop(), c = y.layer-1;

	return selectLayer(c,1,1), i;
}

function selectLayer(i, ui_rewrite, scroll) {
var	y = draw.history, x = y.layer, z = y.layers.length-1;
//* input:
	if (isNaN(i)	) x = z;
	else if (i >= 0	) x = i;
	else if (i == -1) --x;
	else if (i < 0	) ++x;
//* clip:
	if (x > z) x = z; else
	if (x < 0) x = 0;
//* go:
var	u = (y.layer != x || ui_rewrite);
	if (u) y.layer = x, updateLayers(!ui_rewrite, scroll);
	if (x) {
	var	a = {A:'alpha',R:'blur'}, x = y.layers[x];
		for (i in a) {
			z = id('slider'+i).lastElementChild, z.value = x[a[i]] || 0;
			if (u) updateSliders(z);
		}
		a = ['compose','filter'];
		for (i in a) if (z = id(a[i])) z.value = x[i] || 0;
	}
//* show/hide:
	z = id('layers');
	while (z = z.nextSibling) z.style.display = (x?'':'none');
}

function tweakLayer(e,i,t) {
	if (draw.active) drawEnd();
var	y = draw.history, d = y.layers;
	if (isNaN(i) || i < 0) i = y.layer;
	if (!t) return (isNaN(t)
		? (e.id
			? (d[i][t?'compose':'filter'] = e.value)
			: (d[i].show = e.checked?1:0))
		: (e.parentNode.style.backgroundColor = select.clipBorder[d[i].clip = (d[i].clip?d[i].clip+1:2) % select.clipBorder.length])
	), draw.view(2);

var	v = d[i][['name','blur','alpha'][--t]] = (typeof e === 'object' ? e.value : e);
	if (t && i
	&& (e = id('layer'+i))
	&& (e = e.lastElementChild.lastElementChild.previousSibling)
	&& (t === 2 || (e = e.previousSibling))
	&& (e.textContent != v)) {
		e.textContent = v, draw.view(2);	//* <- additionally, redraws on slider mousemove, even without call here
	}
}

function updateLayers(ui_tweak, scroll) {
var	a, b = 'button', j, k, l = 'layer', d, e = id('layers')
,	y = draw.history, c = y.layer, z = y.layers, i = z.length
,	h = z[0].color = hex2fix(z[0].color), hi = isRgbDark(hex2rgb(h))?'#fff':'#000';

	function getHistPos(a) {return /*((d = a.data[a.pos])?d.date:null)+'-'+*/a.pos+'/'+a.last;}

	if (ui_tweak && id(l+0) && z[1]) {
//* DOM fix:
		j = e.getElementsByTagName('p'), k = j.length;
		while (k--) if (a = z[parseInt(j[k].id.match(regLastNum)[1])]) {
			j[k].className = j[k].className.replace(b+'-active', b);
			i = j[k].getElementsByTagName('input');
			i[0].parentNode.style.backgroundColor = select.clipBorder[a.clip] || '';
			if (ui_tweak === 2 && !i[0].checked != !a.show) i[0].checked = !!a.show;
			if (i.length > 1) {
				if (i[1].value != a.name) i[1].value = a.name;
				i = j[k].firstElementChild.getElementsByTagName('i');

				function updateTab(k, v) {if (i[k].textContent != v) i[k].textContent = v;}

				updateTab(2, a.blur || '');
				updateTab(3, a.alpha);
				updateTab(4, getHistPos(a));
			} else {
				i = j[k].getElementsByTagName('button')[0], d = i.style;
				d.backgroundColor = i.textContent = h;
				d.color = hi;
			}
		}
		if (i = id(l+c)) i.className = i.className.replace(b, b+'-active');
	} else {
//* HTML reset, slower, resets scroll:
		j = (i > 1?'<hr><div class="slide">':''), k = '<i title="';
		while (i--) {
			a = z[i];
			j += (j?(i?'':'</div><hr>'):'<hr>')
			+'<p class="'+b+(i == c?'-active':'')
			+'" onClick="selectLayer('+i+')" id="'+l+i+'"><i>'+k+lang.layer.hint.check
			+(a.clip?'" style="background-color:#5ea':'')
			+'"><input type="checkbox" onChange="'	+'tweakLayer(this,'+i+')"'+(a.show?' checked':'')
			+(i?' onContextMenu="'			+'tweakLayer(this,'+i+',0);return false"':'')+'></i><i>'
			+(i?	'<input type="text" onChange="'	+'tweakLayer(this,'+i+',1)" value="'
				+a.name+'" title="'+lang.layer.hint.name+'"></i>'
				+k+lang.layer.hint.blur+'">'+(a.blur || '')+'</i>'
				+k+lang.layer.hint.alpha+'">'+a.alpha+'</i>'
				+k+lang.layer.hint.undo+'">'+getHistPos(a)
			:	'<button class="rf" title="'+lang.layer.hint.bg+'" style="background-color:'
				+h+'; color: '+hi+';" onClick="setBG(0);return false" onContextMenu="setBG(1);return false">'
				+h+'</button>'+lang.layer.bg
			)+'</i></i></p>';
		}
		clearContent(e), setContent(e, j);
	}
	j = z.length-1;
	if (c && j > 9) {
		i = c+5, a = true;
		if (i > j) i = j; else
		if (i < 10) i = 1, a = false;
		if (i = id(l+i)) i.scrollIntoView(a);	//* <- param: none/true=alignWithTop, false=Bottom
	}
	i = j, j = {U:i,T:i, D:1,B:1,M:1, C:0,E:0}, d = b+'-disabled';
	for (i in j) setClass(id(l+i), (!c || c == j[i]) ?d:b);
	updateHistoryButtons(), draw.view(2);
}

function setBG(i) {
	draw.history.layers[0].color = rgb2hex(tools[i].color), updateLayers(1);
}

//* Layer data postprocessing *------------------------------------------------

function getClippedImageData(d, i) {
var	y = draw.history, l = y.layers, c = i || y.layer, a;
	while (l[--c].clip);
	if (!c || !l[c].show || !(a = l[c].alpha) || !(c = y.cur(c))) return 0;
	y = ctx.temp.createImageData(cnv.view.width, cnv.view.height), i = y.data.length, a /= RANGE.A.max*255;
	while (i--) y.data[i] = (i%4 == 3 ? Math.floor(c.data[i]*d.data[i]*a) : d.data[i]);
	return y;
}

function filterCanvas(r, f, i) {
	if (isNaN(r) || r < 1) return;

var	c = cnv[i?i:i = 'temp'], x = ctx[i];
	if (f == 1) {
//* fastest for opera12, okay for others
		iiBlurCanvasRGBA(i, 0, 0, c.width, c.height, r, 0);
	} else {
//* dummy rescale as a fallback:
		ctx.filter.clearRect(0, 0, c.width, c.height);
		ctx.filter.drawImage(c, 0, 0, c.width/r, c.height/r);
		x.clearRect(0, 0, c.width, c.height);
		x.drawImage(cnv.filter, 0, 0, c.width/r, c.height/r, 0, 0, c.width, c.height);
	}
}

function iiBlurCanvasRGBA(c,x0,y0,w,h, radius, iterations) {
/*
http://www.quasimondo.com/IntegralImageForCanvas
Integral Image v0.4, modified (failed as it was)
Copyright (c) 2011 Mario Klingemann
http://opensource.org/licenses/MIT
*/
	function iiGetRGBA(c,x0,y0,w,h) {
	var	p = iiGetPixelsRGBA(c,x0,y0,w,h);
		if (!p) return null;
	var	ii = iiCalculateRGBA(p.pixels,w,h);
		ii.context = p.context;
		ii.imageData = p.imageData;
		return ii;
	}

	function iiGetPixelsRGBA(c,x0,y0,w,h) {
	var	p = {	top_x:	x0
		,	top_y:	y0
		,	width:	w
		,	height:	h
		,	canvas:	cnv[c]	//document.getElementById( id );
		,	context:ctx[c]	//result.canvas.getContext("2d");
		};
		try {
			p.imageData = p.context.getImageData(x0,y0,w,h);
		} catch(e) {
	//		throw new Error("unable to access image data: " + e);
			return null;
		}
		p.pixels = p.imageData.data;
		return p;
	}

	function iiBlurRGBA(ii, radius) {
	var	x,y,dx1,dx2,dy,dy1,dy2,idx1,idx2,idx3,idx4,area,pa,i,j,k
	,	width = ii.width, height = ii.height, pixels = ii.pixels
	,	iw = width + 1, i1 = 0, i2 = 0, L = 'rgb', a = ii.a, t = '';
		for (y = 0; y < height; y++) {
			dy1 = (y < radius ? -y : -radius);
			dy2 = (y >= height-radius ? height-y : radius);
			dy = dy2-dy1;
			dy1 *= iw;
			dy2 *= iw;
			for (x = 0; x < width; x++) {
				dx1 = (x < radius ? -x : -radius);
				dx2 = (x >= width-radius ? width-x : radius);
				area = 1/((dx2-dx1)*dy);
				dx1 += i1;
				dx2 += i1;
				idx1 = dx1+dy1;
				idx2 = dx2+dy2;
				idx3 = dx1+dy2;
				idx4 = dx2+dy1;
				pa = ((a[idx1]+a[idx2]-a[idx3]-a[idx4])*area) || 0;
				if (pa > 0) {
					k = 255/pa;
					for (i in L) j = ii[L[i]], pixels[i2++] = ((j[idx1]+j[idx2]-j[idx3]-j[idx4])*area*k) || 0;
					pixels[i2++] = pa;
				} else {
					pixels[i2++] = pixels[i2++] = pixels[i2++] = pixels[i2++] = 0;
				}
				i1++;
			}
			i1++;
		}
	}

	function iiCalculateRGBA(pixels, width, height) {
	var	r = [], g = [], b = [], a = [], i = 0, j = 0;
		for (y = 0; y < height; y++) {
		var	rsum = pixels[i++]
		,	gsum = pixels[i++]
		,	bsum = pixels[i++]
		,	asum = pixels[i++];
			for (x = 0; x < width; x++) {
				r[j]	= rsum;
				g[j]	= gsum;
				b[j]	= bsum;
				a[j++]	= asum;
				rsum += pixels[i++];
				gsum += pixels[i++];
				bsum += pixels[i++];
				asum += pixels[i++];
			}
			r[j]	= rsum;
			g[j]	= gsum;
			b[j]	= bsum;
			a[j++]	= asum;
			i -= 4;
		}
	var	w1 = width+1, h1 = w1*(height+1), j1 = w1, j2 = 0;
		while (j1 < h1) {
			r[j1] += r[j2];
			g[j1] += g[j2];
			b[j1] += b[j2];
			a[j1] += a[j2];
			j1++, j2++;
		}
		return { r:r, g:g, b:b, a:a, width:width, height:height, pixels:pixels };
	}

var	ii = iiGetRGBA(c,0,0,w,h);
	if (!ii) return null;
var	c = ii.context, d = ii.imageData, p = ii.pixels;
	iiBlurRGBA(ii, radius);
	while (--iterations > 0)
	iiBlurRGBA(iiCalculateRGBA(p,w,h), radius);
	return c.putImageData(d,x0,y0);
}

//* Strokes and shapes *-------------------------------------------------------

function drawCursor() {
var	c = ctx[mode.brushView?'draw':'view'], g = tool.grid;
	if (g > 1) {
	var	m, n = Math.floor(tool.width/g), o = draw.o, v = (n < 1?(n = 1):n)*g, p = DRAW_PIXEL_OFFSET, w = v+p, v = v-p;
		for (i in DRAW_HELPER) c[i] = DRAW_HELPER[i];
		c.beginPath();
		for (i = -n; i <= n; i++) if (i) {
			m = i*g+p;
			c.moveTo(o.x+m, o.y-v);
			c.lineTo(o.x+m, o.y+w);
			c.moveTo(o.x-v, o.y+m);
			c.lineTo(o.x+w, o.y+m);
		} else {
			c.moveTo(o.x+p, 0), c.lineTo(o.x+p, cnv.draw.height);
			c.moveTo(0, o.y+p), c.lineTo(cnv.draw.width, o.y+p);
		}
		c.stroke();
	}
	if (mode.brushView) {
		c.fillStyle = 'rgba('+tool.color+', '+tool.opacity+')';
		c.shadowColor = (c.shadowBlur = tool.blur) ? 'rgb('+tool.color+')' : A0;
		c.globalCompositeOperation = tool.clip;
	} else {
		c.strokeStyle = 'rgb(123,123,123)';
		c.shadowColor = A0;
		c.shadowBlur = 0;
		c.lineWidth = 1;
	}
	c.beginPath();
	c.arc(draw.cur.x, draw.cur.y, tool.width/2, 0, 7);
	mode.brushView ? c.fill() : c.stroke();
	c.globalCompositeOperation = SO;
}

function drawStart(event) {
	try {
		showProps(event,1,1);	//* <- check if permission denied to read some property
	} catch (err) {
		return;		//* <- against FireFox catching clicks on page scrollbar
	}

	if (!draw.step || (draw.target && draw.target !== event.target)) drawEnd(event);
	if (isMouseIn() <= 0) return false;

	draw.target = event.target;
//	cnv.view.focus();
	eventStop(event).preventDefault();

//* Special actions:
	if (draw.btn && (draw.btn != event.which)) return drawEnd();
	if (mode.click) return ++mode.click, drawEnd(event);
	if (event.altKey) draw.turn = {prev: draw.zoom, zoom: 1}; else
	if (event.ctrlKey) draw.turn = {prev: draw.angle, angle: 1}; else
	if (event.shiftKey) draw.turn = {prev: draw.pan ? {x: draw.pan.x, y: draw.pan.y} : {x:0,y:0}, pan: 1};
	if (mode.debug && draw.turn && !draw.turn.pan) {
		for (i in DRAW_HELPER) ctx.view[i] = DRAW_HELPER[i];
		ctx.view.beginPath();
		ctx.view.moveTo(draw.o.x, draw.o.y);
		ctx.view.lineTo(draw.cur.x, draw.cur.y);
		ctx.view.lineTo(cnv.view.width/2, cnv.view.height/2);
		ctx.view.stroke();
	}
	updatePosition(event);
	if (draw.turn) return draw.turn.origin = getCursorRad();

//* Drawing on cnv.draw:
var	y = draw.history, i = y.layer, s = select.shape.value, fig = select.shapeFig[s], sf = select.shapeFlags[s];
	if ((i || fig) && !(i && y.layers[i].show)) return false;

	if (draw.step) {
		if (
			(mode.step && (
				(mode.shape && (sf & 1))	//* <- line+curve
				|| !fig				//* <- move+area
			))
			|| (sf & 64)				//* <- any mode
		) {
			for (i in draw.o) draw.prev[i] = draw.cur[i];
			return draw.step.done = 1;
		} else draw.step = 0;
	}
//	if (event.shiftKey) mode.click = 1;	//* <- draw line/form chains, meh, forget for now

	if ((draw.btn = event.which) != 1 && draw.btn != 3) return pickColor(), drawEnd();

//* start drawing:

	draw.active = draw.time.act(), y = {draw:0, temp:0};
	if (!interval.timer) {
		interval.timer = setInterval(timeElapsed, 1000);
		interval.save = setInterval(autoSave, 60000);
	}
var	i = (event.which == 1)?1:0, j, t = tools[1-i]
,	b = (fig ? t.blur : 0)
,	pf = ((sf & 8) && (mode.shape || !mode.step))
,	sh = ((sf & 2) && (mode.shape || pf));
	draw.clip = t.clip;
	for (i in (t = mode.erase ? DRAW_HELPER : {
		lineWidth: ((!fig || (pf && !mode.step))?1:t.width)
	,	fillStyle: (sh ? 'rgba('+(mode.step?tools[i]:t).color+', '+t.opacity+')' : A0)
	,	strokeStyle: (sh && !(mode.step || pf) ? A0 : 'rgba('+t.color+', '+(fig?t.opacity:(draw.step?0.33:0.66))+')')
	,	shadowColor: (b ? 'rgb('+t.color+')' : A0)
	,	shadowBlur: b
	})) for (j in y) ctx[j][i] = t[i];
	updatePosition(event);		//* <- update pixel offset based on tool width && draw.active
	for (i in draw.o) draw.prev[i] = draw.cur[i];
	for (i in draw.line) draw.line[i] = false;
	for (i in select.lineCaps) {
		t = select.options[i][select[i].value];
		for (j in y) ctx[j][i] = t;
	}
	if (sf & 32) return drawEnd(event);
	ctx.draw.beginPath();
	ctx.draw.moveTo(draw.cur.x, draw.cur.y);
}

function drawMove(event) {
	if (mode.click == 1 && !event.shiftKey) return mode.click = 0, drawEnd(event);

	updatePosition(event);
	if (draw.turn) return updateViewport(draw.turn.pan?1:draw.turn.delta = getCursorRad() - draw.turn.origin);

var	s = select.shape.value, fig = select.shapeFig[s], sf = select.shapeFlags[s]
,	redraw = true, i
,	newLine = (draw.active && !((mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8)));

	if (mode.click) mode.click = 1;
	if (newLine) {
		if (draw.line.preview) {
			drawShape(ctx.draw, s);
		} else
		if (draw.line.back = mode.step) {
			if (noShadowBlurCurve) ctx.draw.shadowColor = A0, ctx.draw.shadowBlur = 0;
			if (draw.line.started) ctx.draw.quadraticCurveTo(draw.prev.x, draw.prev.y, (draw.cur.x + draw.prev.x)/2, (draw.cur.y + draw.prev.y)/2);
		} else ctx.draw.lineTo(draw.cur.x, draw.cur.y);
		draw.line.preview =	!(draw.line.started = true);
	} else if (draw.line.back) {
		ctx.draw.lineTo(draw.prev.x, draw.prev.y);
		draw.line.back =	!(draw.line.started = true);
	}
	if (mode.limitFPS) {
	var	t = +new Date;
		if (t-draw.refresh > 30) draw.refresh = t; else redraw = false;		//* <- put "> 1000/N" to redraw maximum N FPS
	}
	if (redraw && ((i = isMouseIn()) > 0 || draw.active)) {
		redraw = 0;
		if (i || (draw.active && !mode.lowQ)) draw.preload(), ++redraw;
		if (draw.active) {
			if (fig) ctx.draw.globalCompositeOperation = draw.clip;
			if ((mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8)) {
				draw.line.preview = true;
				if (mode.erase && (sf & 2)) {
					ctx.draw.beginPath();
					drawShape(ctx.draw, s, 1), ++redraw;		//* <- erase shape area
				} else {
					ctx.temp.clearRect(0, 0, cnv.view.width, cnv.view.height);
					ctx.temp.beginPath();
					drawShape(ctx.temp, s);
					ctx.temp.stroke();
					ctx.draw.drawImage(cnv.temp, 0, 0), ++redraw;
				}
			} else
			if (draw.line.started) ctx.draw.stroke(), ++redraw;
		} else if (i && mode.brushView && !mode.lowQ) drawCursor(), ++redraw;
		updateDebugScreen();
		if (redraw) {
			draw.view();
			if (i && !(draw.active || mode.brushView || mode.lowQ)) drawCursor();
		}
	}
	if (newLine) for (i in draw.o) draw.prev[i] = draw.cur[i];
}

function drawEnd(event) {
	if (!event || draw.turn) return draw.active = draw.step = draw.btn = draw.turn = 0, draw.view(1);
	if (mode.click == 1 && event.shiftKey) return drawMove(event);
	if (draw.active) {
		if (draw.target != cnv.view) return;
	var	s = select.shape.value, fig = select.shapeFig[s], sf = select.shapeFlags[s]
	,	m = ((mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8));
	//* 2pt line, base for 4pt curve:
		if (!draw.step && (
			(mode.step && (
				(mode.shape && (sf & 1))	//* <- line+curve
				|| (sf & 4)			//* <- move+area
			))
			|| (sf & 64)				//* <- any mode
		)) {
			draw.step = {
				prev:{x:draw.prev.x, y:draw.prev.y}
			,	cur:{x:draw.cur.x, y:draw.cur.y}
			};
			return;
		}
		for (i in DRAW_HELPER) ctx.temp[i] = DRAW_HELPER[i];
		draw.time.all[1] = +new Date;
		draw.preload();
		if (mode.erase) {
			if (sf & 8) {
				ctx.draw.closePath();
				ctx.draw.save();
				ctx.draw.clip();
				ctx.draw.clearRect(0, 0, cnv.view.width, cnv.view.height);
				ctx.draw.restore();
			} else drawShape(ctx.draw, s, 1);
			++count.erases;
		} else {
			ctx.draw.fillStyle = ctx.temp.fillStyle;
			if (i = (fig ? ((ctx.draw.globalCompositeOperation = draw.clip) == DO) : 0)) ++count.erases;
			if (sf & 8) {
				ctx.draw.closePath();
				if (mode.shape || !mode.step) ctx.draw.fill();
				used.poly = 'Poly';
			} else
			if ((sf & 64) || (m && draw.line.preview)) {
				drawShape(ctx.draw, s);
				if (fig) ++used_shape[select.options.shape[s]];//used.shape = 'Shape';
			} else
			if (m || draw.line.back || !draw.line.started) {//* <- draw 1 pixel on short click, regardless of mode or browser
				ctx.draw.lineTo(draw.cur.x, draw.cur.y + (draw.cur.y == draw.prev.y ? 0.01 : 0));
			}
			if (!fig) used.move = 'Move';
			else if (!(sf & 8) || mode.step) {
				ctx.draw.stroke();
				if (!i) ++count.strokes;
			}
		}
		ctx.draw.globalCompositeOperation = SO;
		historyAct();
		draw.active = draw.step = draw.btn = 0;
		if (cue.autoSave < 0) autoSave(); else cue.autoSave = 1;
		if (mode.click && event.shiftKey) return mode.click = 0, drawStart(event);
	}
	draw.target = 0;
	updateDebugScreen();
}

function drawShape(c, i, clear) {
var	cd = ctx.draw, v = draw.prev, r = draw.cur
,	ct = ctx.temp, s = draw.step
,	fig = select.shapeFig[i] || (mode.step && !(s && s.done)?2:0);

	function circle(r, c, a, b) {
		(c?c:c = ct).moveTo(
		(a?a:a = x)+r,
		(b?b:b = y));
		c.arc(a, b, r, 0, 7);
	}

	switch (fig) {
	//* pan
		case 0:	if (v.x != r.x
			|| (v.y != r.y)) moveScreen(r.x-v.x, r.y-v.y, c != ct);
		break;
	//* line
		case 1:	if (s) {
			var	old = propSwap(ct, DRAW_HELPER), d = r;

				ct.moveTo(s.cur.x, s.cur.y);		//* <- control point 1 phantom
				ct.lineTo(s.prev.x, s.prev.y);
				if (s.done) {
					ct.moveTo(d.x, d.y), d = v;	//* <- control point 2 phantom
					ct.lineTo(d.x, d.y);
				}

				propSwap(ct, old, 0);
		//* curve
				c.moveTo(s.prev.x, s.prev.y);
				c.bezierCurveTo(s.cur.x, s.cur.y, d.x, d.y, r.x, r.y);
			} else {
		//* straight
				c.moveTo(v.x, v.y);
				c.lineTo(r.x, r.y);
			}
		break;
	//* rect
		case 2:	if (s) {
			//* show pan source area
				c.strokeRect(s.prev.x, s.prev.y, s.cur.x-s.prev.x, s.cur.y-s.prev.y);
			} else if (clear) cd.clearRect(v.x, v.y, r.x-v.x, r.y-v.y);
			else {
				if (c.fillStyle != A0)
				c.fillRect(v.x, v.y, r.x-v.x, r.y-v.y);
				c.strokeRect(v.x, v.y, r.x-v.x, r.y-v.y);
			}
		break;
	//* circle
		case 3:
		var	xCenter = (v.x+r.x)/2
		,	yCenter = (v.y+r.y)/2
		,	radius = dist(r.x-xCenter, r.y-yCenter);
			if (radius > 0) {
				circle(radius, c, xCenter, yCenter);
				if (clear) insideClear(cd);
				else if (c.fillStyle != A0) c.fill();
			}
		break;
	//* ellipse
		case 4:
		var	xCenter = (v.x+r.x)/2
		,	yCenter = (v.y+r.y)/2
		,	xRadius = Math.abs(r.x-xCenter)
		,	yRadius = Math.abs(r.y-yCenter), x = 1, y = 1;
			if (xRadius > 0 && yRadius > 0) {
				if (c.ellipse) {
					c.moveTo(xCenter+xRadius, yCenter);
					c.ellipse(xCenter, yCenter, xRadius, yRadius, 0, 0, 7);
				} else {
					c.save();
					if (xRadius > yRadius) c.scale(x, y = yRadius/xRadius); else
					if (xRadius < yRadius) c.scale(x = xRadius/yRadius, y);
					c.moveTo((xCenter+xRadius)/x, yCenter/y);
					c.arc(xCenter/x, yCenter/y, Math.max(xRadius, yRadius), 0, 7);
					c.restore();
				}
				if (clear) insideClear(cd);
				else if (c.fillStyle != A0) c.fill();
			}
		break;
	//* polar figures
		case 5:
		case 6:	if (s) {
			var	x = s.prev.x, y = s.prev.y, GEAR = (fig == 5)
			,	r1 =		dist(s.cur.x-x, s.cur.y-y)	//* <- 1st radius (at releasing 1st click)
			,	r2 =		dist(r.x-x, r.y-y)		//* <- always current (2nd or 3rd)
			,	r3 = (s.done ?	dist(v.x-x, v.y-y) : r2);	//* <- 2nd or current (2nd click down)

				function r2far() {
					return dist(
						Math.max(cnv.draw.width-x, x)
					,	Math.max(cnv.draw.height-y, y)	//* <- "infinite" ray length = to farthest image corner + outline width
					)+tool.width;
				}

				if (GEAR) {
					if (r3 < r1+1) r3 = r2far()*10;	//* <- for later out-of-border moves
					if (r2 > r3) r2 = 0;
				} else	if (r1 < 1) r1 = r2far();	//* <- just as a 1-click default; infinity is not needed here
				if (r3 > 1) {
				var	a1 =		Math.atan2(s.cur.y-y, s.cur.x-x)
				,	a2 =		Math.atan2(r.y-y, r.x-x)
				,	a3 = (s.done ?	Math.atan2(v.y-y, v.x-x) : a2)
				,	a = ang_btw(a1, a3)
				,	b = Math.abs(a), d = Math.PI, h = d/2
				,	w = Math.ceil(tool.width+2)				//* <- minimum ray width, pixels
				,	i = Math.floor(d/Math.max(Math.abs(b-h), w/h/r3))	//* <- ray count to fit angular width
			//	,	i = Math.ceil(b/d*360)					//* <- ray count by angle fraction (max 360 rays here)
				,	R2G1 = ((!GEAR || s.done) && r2 > 1 && r2 > r1)
				,	R1G3 = (r1 > r3)
				,	r4 = (GEAR && R2G1?r2:r1)
				,	j = (GEAR || !R1G3)?2:3					//* <- minimum ray count
				;	d /= i = Math.max(i, j);
				}
				old = propSwap(ct, DRAW_HELPER);

				if (!s.done) {
				var	rm = Math.max(r2-4, 4)
				,	rn = rm+8
				,	am = a1+(b > h?Math.PI:0)
				,	an = ((a < 0) != (b > h)?-h:h)
				,	k = Math.min(90, Math.floor(rm/3))
				;	function r2line(a) {
						c.moveTo(x + Math.cos(a)*rm, y + Math.sin(a)*rm);
						c.lineTo(x + Math.cos(a)*rn, y + Math.sin(a)*rn);
					}
					while (++j < k) r2line(am + an*(1-2/j));	//* <- possible ray count marks phantom
					r2line(am + an);
					r2line(am);					//* <- 90 degree marks
				}

				if (GEAR) {
			//* radiance, cog wheel
					if (r3 > 1 && R2G1) circle(r1);			//* <- base circle phantom

					propSwap(ct, old, 0);

					if (r3 > 1) {
						if (!R2G1) h = a2-d, c.moveTo(x + Math.cos(h)*r3, y + Math.sin(h)*r3);	//* <- start linked
						while (i--) {
							a = a2 + d*i*2, b = a-d, h = (R2G1?b:a+d);
							if (R2G1) c.moveTo(x + Math.cos(h)*r3, y + Math.sin(h)*r3);	//* <- start isolated
							c.arc(x, y, r4, h, a,!R2G1);
							c.arc(x, y, r3, a, b, true);		//* <- arc adds lineTo itself by standard, wanted or not
						}
					} else if (	r1 > 1 && r2 < r1) circle(r1, c);	//* <- base circle, no cogs
					if (s.done &&	r2 > 1 && r2 < r1) circle(r2, c);	//* <- hole inside
				} else {
			//* regular polygon, star
					if (R1G3) {
				//* fixed spike length + autoconnect peaks
						R2G1 = 0;
						if (s.done) {
							if (i > 4) {
								a = Math.PI/i;
								j = Math.floor((i-3)/2)+1;	//* <- max peak connection variants
								while (--j) {
									b = h = r1*Math.cos(a*(j+1))/Math.cos(a*j);
									if (r2 > r1) h += r1;
									circle(h);		//* <- snap levels phantom
									if (!R2G1 && r2 < h) R2G1 = 1, r4 = b;
								}
							}
						} else {
							j = d*2, h = Math.PI*2, a = h+(h+a2)%j;
							while ((a -= j) >= 0) {
								ct.moveTo(x, y);
								ct.lineTo(x + Math.cos(a)*r1, y + Math.sin(a)*r1);	//* <- ray count phantom
							}
						}
						r2 = r1;
					} else if (r2 != r1)
				//* variable spike length
					circle(r2);
					circle(r1);				//* <- base radius phantom

					propSwap(ct, old, 0);

					c.moveTo(x + Math.cos(a2)*r2, y + Math.sin(a2)*r2);
					while (i--) {
						a = a2 + d*i*2, b = a+d;
						if (!R1G3 || R2G1)
						c.lineTo(x + Math.cos(b)*r4, y + Math.sin(b)*r4);
						c.lineTo(x + Math.cos(a)*r2, y + Math.sin(a)*r2);
					}
				}
			} else circle(dist(r.x-v.x, r.y-v.y), c, v.x, v.y);	//* <- base radius, 1st step

			if (clear) insideClear(cd);
			else if (c.fillStyle != A0) c.fill('evenodd');
		break;
	}
	c.moveTo(r.x, r.y);
}

function insideClear(c) {
	c.save();
	c.clip();
	c.clearRect(0, 0, cnv.view.width, cnv.view.height);
	c.restore();
}

function propSwap(a, b, c, r) {
	if ((!r && !c && c !== null ? (r = 'stroke') : r) && a[r]) a[r]();
	r = {};
	for (i in b) r[i] = a[i], a[i] = b[i];
	if ((c?c:c = 'beginPath') && a[c]) a[c]();
	return r;
}

//* One-click all-screen manipulation *----------------------------------------

function moveScreen(dx, dy, fin) {
var	y = draw.history, l = y.layers, z = y.layer, p = draw.step, n = !mode.shape, v = cnv.view;
	if (z) {
		if (d = y.cur()) (c = ctx.draw).putImageData(d, 0, 0);
		else return;
	} else {
		if (fin) {
			y.layer = y.layers.length, t = +new Date;
			while (--y.layer) moveScreen(dx, dy), historyAct(t);
			return updateLayers();
		} else {
			draw.preload(1);
		var	c = ctx.upper, d = c.getImageData(0, 0, v.width, v.height);
		}
	}
	ctx.temp.clearRect(0, 0, v.width, v.height);
	if (p) {
		for (i in {min:0,max:0}) p[i] = {
			x:Math[i](p.cur.x, p.prev.x)
		,	y:Math[i](p.cur.y, p.prev.y)
		};
		p.max.x -= p.min.x;
		p.max.y -= p.min.y;
		if (n) c.clearRect(p.min.x, p.min.y, p.max.x, p.max.y);
		ctx.temp.putImageData(d, dx, dy, p.min.x, p.min.y, p.max.x, p.max.y);
	} else {
		if (n) c.clearRect(0, 0, v.width, v.height);
		ctx.temp.putImageData(d, dx, dy);
	}
	c.drawImage(cnv.temp, 0, 0);
}

function fillCheck(c) {
	if (c && !(c = draw.history.cur())) return [0,0,0,255];
var	d = (c?c:ctx.view.getImageData(0, 0, cnv.view.width, cnv.view.height)).data, i = d.length, r = [];
	while (i--)
	if (i < 4) r[i] = d[i]; else
	if (d[i] != d[i%4]) return 0;
//* fill flood confirmed, return its color:
	return r;
}

function fillScreen(i,t) {
	y = draw.history, l = y.layers, z = y.layer;
	if (!z) {
		if (isNaN(i)) return false;
		if (i < 0) {
			if (i == -1) l[0].color = hex2inv(l[0].color);
			y.layer = l.length, t = +new Date;
			while (--y.layer) fillScreen(i,t);
		} else l[0].color = rgb2hex(tools[i].color);
		return updateLayers();
	}
	if (isNaN(i) || i > 0) {
		used.wipe = 'Wipe';
		ctx.draw.clearRect(0, 0, cnv.view.width, cnv.view.height);
	} else
	if (!i) {
		used.fill = 'Fill';
		ctx.draw.globalCompositeOperation = (id('lineStyle').style.display == 'none' ? SO : tool.clip);
		ctx.draw.fillStyle = 'rgb(' + tools[i].color + ')';
		ctx.draw.fillRect(0, 0, cnv.view.width, cnv.view.height);
		ctx.draw.globalCompositeOperation = SO;
	} else {
		draw.preload(), historyAct(-t || false), d = y.cur(), z = l[z];
		if (!d) return;
		if (i == -1) {
			used.inv = 'Invert';
			i = d.data.length;
			while (i--) if (i%4 != 3) d.data[i] = 255 - d.data[i];	//* <- modify current history point, no push
		} else {
		var	hw = d.width, hh = d.height, w = cnv.view.width, h = cnv.view.height
		,	hr = (i == -2), j, k, l = (hr?w:h)/2, m, n, x, y, z, d;
			if (hr) used.flip_h = 'Hor.Flip';
			else	used.flip_v = 'Ver.Flip';
			x = cnv.view.width; while (x--) if ((!hr || x >= l) && x < hw) {
			y = cnv.view.height; while (y--) if ((hr || y >= l) && y < hh) {
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
		ctx.draw.putImageData(z.data[z.pos] = d, 0, 0);
		return draw.view(1);
	}
	cue.autoSave = 0;
	historyAct(t);
}

function pickColor(event, e, keep) {
	if (e && e.ctx) c = e; else
	if (event) {
		if (e && e.length) d = e; else
		if (event === 1) keep = 1; else
		if (event.ctx) c = event; else
		if ((e = event.target) && e.ctx) c = e;
	}
//* from gradient palette:
	if (c) {
		eventStop(event);
	var	d = getOffsetXY(c)
	,	x = event.pageX - CANVAS_BORDER - d.x
	,	y = event.pageY - CANVAS_BORDER - d.y
	,	w = c.width
	,	h = c.height
		;
		if (x < 0) x = 0; else if (x >= w) x = w-1;
		if (y < 0) y = 0; else if (y >= h) y = h-1;
		d = c.ctx.getImageData(x,y, 1,1).data;
		c = 0;
	} else
//* from drawing container:
	if (!d) {
	var	c = 0
	,	x = Math.floor(draw.o.x)
	,	y = Math.floor(draw.o.y)
		;
//* current layer:
		if (d = draw.history.cur()) c = (x + y*cnv.view.width)*4;
//* whole image:
		else draw.view(1), d = ctx.view.getImageData(x,y, 1,1);
		d = d.data;
	}
	if (d) {
		c = rgb2hex(d, c);
		if ((e = keep) && e.tagName) {
			e.style.backgroundColor = c;
			e.rgbArray = hue = c = d;
		}
		return keep ? c : updateColor(c, event);
	}
}

//* Color conversions *--------------------------------------------------------

function hex2fix(v) {
	v = '#'+trim(v, '#');
	if (v.length == 2) v += repeat(v[1], 5); else
	if (regHex3.test(v)) v = v.replace(regHex3, '#$1$1$2$2$3$3');
	return regHex.test(v) ? v.toLowerCase() : false;
}

function hex2inv(v) {
	if (v = hex2fix(v)) {
	var	a = '0123456789abcdef', i = '', j = v.length, k, l = a.length;
		while (--j) {k = l; while (k--) if (v[j] == a[k]) {i = a[l-k-1]+i; break;}}
		return '#'+i;
	}
	return false;
}

function hex2rgb(v) {
	if (!regHex.test(v)) return '0,0,0';
	v = trim(v, '#');
	return parseInt(v.substr(0,2), 16)
	+', '+ parseInt(v.substr(2,2), 16)
	+', '+ parseInt(v.substr(4,2), 16);
}

function rgb2hex(v, i) {
	if (v && !v.split) {
		if (k = v.length) {
		var	c = 0, i = orz(i), k = Math.min(k, i+3);
			for (; i < k; i++) c = c*256 + v[i];
			c = c.toString(16);
		} else c = '';
		while (c.length < 6) c = '0'+c;
		return '#'+c;
	}
	if (!reg255.test(v)) return false;
	v = v.split(reg255split);
var	h = '#', i, j;
	for (i in v) h += ((j = Math.min(255, parseInt(v[i])).toString(16)).length == 1) ? '0'+j : j;
	return h;
}

function isRgbDark(v) {
var	a = v.split(reg255split), v = 0, i;
	for (i in a) v += parseInt(a[i]);
	return v < 380;
}

function setToolHue(redraw) {
var	a = tool.color.split(reg255split).map(orz)
,	i = a.length
	;
	while (--i) if (a[i] != a[0]) {
	var	i = a.length
	,	j = Math.min.apply(null, a)
	,	k = Math.max.apply(null, a)-j
		;
		while (i--) a[i] -= j;
		if (k < 255) {
			i = a.length;
			j = 255/k;
			while (i--) if (a[i] > 0) a[i] = Math.floor(a[i]*j);
		}
		i = a.length;
		while (i--) if (!hue || a[i] != hue[i]) {
			hue = a;
			if (redraw && (i = id('color-wheel-box'))) i.redrawBoxGradient(hue);
			return;
		}
		return;
	}
}

//* Layout changes *-----------------------------------------------------------

function updateColor(value, i) {
var	t = tools[(!i || (isNaN(i) && i.which != 3))?0:1]
,	c = id('color-text')
,	v = value || c.value;

//* check format:
	if (i = rgb2hex(v)) {
		t.color = v, v = i;
	} else
	if (v = hex2fix(v)) {
		if (value != '') t.color = hex2rgb(v);
	} else return c.style.backgroundColor = 'red';

	if (t == tool) c.value = v, c.style.backgroundColor = '', setToolHue(1);

//* put on top of history palette:
var	p = palette[0], found = p.length;
	for (i = 0; i < found; i++) if (p[i] == v) found = i;
	if (found) {
		i = Math.min(found+1, PALETTE_COL_COUNT*9);		//* <- history length limit
		while (i--) p[i] = p[i-1];
		p[0] = v;
		if (0 == select.palette.value) updatePalette();
		if (LS) LS.historyPalette = JSON.stringify(p);
	}

//* update buttons:
	i = id(t == tool?'colorF':'colorB');
	i.style.color = isRgbDark(t.color)?'#fff':'#000';		//* <- inverted font color
	i.style.background = 'rgb(' + t.color + ')';
	return v;
}

function updatePalette() {

	function drawGradient(cnv, fun, etc) {
	var	ctx = cnv.ctx = cnv.getContext('2d')
	,	w = cnv.width
	,	h = cnv.height
	,	d = ctx.createImageData(w,h)
	,	b,x,y = h
		;
		while (y--) {
			x = w;
			while (x--) {
				b = fun(x,y, w,h, etc);
				i = (x + y*w)*4;
				d.data[i  ] = b[0];
				d.data[i+1] = b[1];
				d.data[i+2] = b[2];
				d.data[i+3] = 255;
			}
		}
		ctx.putImageData(d, 0,0);
	}

	function linearBlend(from, to, frac, max) {
		if (frac <= 0) return from;
		if (frac >= max) return to;
	var	i = to.length
	,	j = frac/max
	,	k = 1-j
	,	r = []
		;
		while (i--) r[i] = Math.round(from[i]*k + to[i]*j);
		return r;
	}

	function getBoxGradientPixel(x,y, w,h, hue) {
		return linearBlend(
			linearBlend(hue, gray[2], x,w)
		,	linearBlend(gray[0], gray[1], x,w)
		,	y,h
		);
	}

	function getRainbowWheelGradientPixel(x,y, w,h) {
	var	m = Math.PI*2
	,	a = Math.atan2(y - h/2, x - w/2) + m/3
	,	i = 0
	,	d = m/l
		;
		if (a < 0) a += m;
		while (a > d) a -= d, ++i;
		return linearBlend(rgb[i], rgb[++i < l?i:0], a,d);
	}

	function getRainbowBoxGradientPixel(x,y, w,h, sat) {
	var	i = 0
	,	d = w/l
		;
		while (x > d) x -= d, ++i;
	var	c = linearBlend(rgb[i], rgb[++i < l?i:0], x,d);
		if (d = sat[1]) c = linearBlend(gray[1], c, sat[0], d);
		if (y == y2) return c;
		return linearBlend(c, gray[y < y2?0:2], Math.abs(y-y2), y2);
	}

	function pickHue(event) {
		if (event.type === 'mousemove' && (!draw.target || draw.target != event.target)) return;
		eventStop(event).preventDefault();
		if (!draw.target) draw.target = id('color-wheel-round');
	var	hue = pickColor(event, draw.target, id('color-wheel-hue'));
		drawGradient(id('color-wheel-box'), getBoxGradientPixel, hue);
	}

	function pickCorner(event) {
		pickColor(event, event.target.rgbArray);
	}

	function redrawBoxGradient(hue) {
	var	e = id('color-wheel-hue');
		e.style.backgroundColor = rgb2hex(hue);
		e.rgbArray = hue;
		drawGradient(id('color-wheel-box'), getBoxGradientPixel, hue);
	}

var	pt = id('colors')
,	c = select.palette.value
,	p = palette[c]
	;
	if (LS) LS.lastPalette = c;
	clearContent(pt);

	if (p[0] == '\r') {
	var	c = p[1]
	,	rgb = [
			[255,  0,  0]
		,	[255,255,  0]
		,	[  0,255,  0]
		,	[  0,255,255]
		,	[  0,  0,255]
		,	[255,  0,255]
		]
	,	gray = [
			[  0,  0,  0]
		,	[127,127,127]
		,	[255,255,255]
		]
	,	l = rgb.length
	,	f = 'return false;'
		;
		if (c == 'g') {
			setContent(pt, getSlider('S')+'<br>');
			setSlider('S');
			setId(c = cre('canvas', pt), 'gradient');
		var	x = c.width = 300
		,	y = c.height = 133
		,	y2 = Math.floor(y/2)
			;

			(c.updateSat = function (sat) {
				drawGradient(c, getRainbowBoxGradientPixel, [sat, isNaN(sat) ? 0 : RANGE.S.max]);
			})();

			c.setAttribute('onscroll', f);
			c.setAttribute('oncontextmenu', f);
			c.onmousedown = pickColor;
		} else
		if (c == 'w') {
		var	border = CANVAS_BORDER
		,	pad = 0
		,	outerDiam = 304 - (pad + border)*2
		,	innerDiam = Math.floor((outerDiam - border*2) * 0.75)
		,	innerBoxWidth = Math.floor((w = innerDiam - border*2) / Math.sqrt(2))
		,	p = 'color-wheel'
		,	b = setId(cre('div'	, clearContent(pt)), p)
		,	c = setId(cre('canvas'	, b), p+'-round')
		,	d = setId(cre('div'	, b), p+'-inner')
		,	e = setId(cre('canvas'	, d), p+'-box')
		,	w = Math.round(w/2)
			;
			b.style.width = b.style.height = outerDiam+'px';
			d.style.width = d.style.height = innerDiam+'px';
			d.style.top = d.style.left = (Math.floor((outerDiam - innerDiam)/2 - border) + pad)+'px';
			e.style.top = e.style.left = (Math.floor((innerDiam - innerBoxWidth)/2 - border))+'px';
			e.width = e.height = innerBoxWidth;
			c.width = c.height = outerDiam - border*2;

			setToolHue();
			if (!hue) hue = rgb[0];

			drawGradient(c, getRainbowWheelGradientPixel);
			drawGradient(e, getBoxGradientPixel, hue);

			b.onmousemove =
			c.onmousedown = pickHue;
			d.onmousedown = pickCorner;
			e.onmousedown = pickColor;
			e.redrawBoxGradient = redrawBoxGradient;

		var	a = [b,c,d,e]
		,	i = a.length
			;
			while (i--) {
				q = a[i];
				q.setAttribute('onscroll', f);
				q.setAttribute('oncontextmenu', f);
			}
		var	a = [
				['bottom', 'left']
			,	['bottom', 'right']
			,	['top', 'right']
			,	['top', 'left']
			]
		,	i = a.length
			;
			while (i--) {
			var	q = a[i]
			,	b = cre('div', d, e)
			,	k = q.length
				;
				for (var j = 0; j < k; j++) b.style[q[j]] = 0;
				if (i < gray.length) q = gray[i];
				else q = hue, setId(b, p+'-hue');
				b.style.backgroundColor = rgb2hex(b.rgbArray = q);
				b.style.width = b.style.height = w+'px';
			}
		} else c = 'TODO';
		if (!c.tagName) setContent(pt, c);
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
					c = hex2fix(c) || '#000';
					setClass(fill = document.createElement('div'), isRgbDark(hex2rgb(c)) ? 'paletdark' : 'palettine');
					setEvent(fill, 'onclick', 'updateColor("'+c+'",0);');
					setEvent(fill, 'oncontextmenu', 'updateColor("'+c+'",1); return false;');
					fill.title = c+(t?(' ('+t+')'):'');
					td.style.backgroundColor = c;
					td.appendChild(fill);		//* <- color field
				} else if (c) {
					td.textContent = t = c;		//* <- title + text field
					setClass(td, 'text');
				}
				tr.appendChild(td);			//* <- otherwise - empty spacer
				if (autoRows) --colCount;
			}
		}
		tbl.appendChild(tr);
		pt.appendChild(tbl);
	}
}

//* safe palette constructor, step recomended to be: 1, 3, 5, 15, 17, 51, 85, 255

function generatePalette(p, step, slice) {
	if (!(p = palette[p])) return;
var	letters = [0, 0, 0], l = p.length;
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
		if ((letters[1] == 0 || (letters[1] == step * slice)) && letters[2] == 0)
			p[l+1] = '\n';
	}
}

function getSlider(b,z) {
var	i, g = RANGE[b], c = '<i id="slider'+b+'"><input type="range" id="range'+b+'" onChange="updateSliders(this)';
	for (i in g) c += '" '+i+'="'+g[i];
	return c+'" value="'+(z?g.min:g.max)+'"><span> '+lang.tool[b]+'</span></i>';
}

function setSlider(b) {
var	r = 'range', s = 'slider', t = 'text', c = id(r+b), d,e;
	if (c.type != r) c.type = t;
	else {
		setClass(d = document.createElement('i'), s);
		d.textContent = (e = (r = id(s+b)).lastElementChild).textContent;
		r.removeChild(e);
		r.insertBefore(d, r.firstElementChild);

		d = document.createElement('input');
		setId(d, (d.type = t)+b);
		setEvent(d, 'onchange', 'updateSliders(this)');
		d.value = c.value;
		r.appendChild(d);
	}
}

function updateSlider(i,e) {
var	k = e?i:BOWL[i]
,	s = id('range'+k)
,	t = id('text'+k) || s
,	r = e?s:RANGE[k]
,	v = e?parseFloat(e.value):tool[i = BOW[i]];
	if (v < r.min) v = r.min; else
	if (v > r.max) v = r.max;
	if (r.step < 1) v = parseFloat(v).toFixed(2);
	if (e) {
		if (i == 'A') tweakLayer(v, draw.history.layer, 3); else
		if (i == 'R') tweakLayer(v, draw.history.layer, 2); else
		if (i == 'T') draw.shift = v, draw.view(2); else
		if (i == 'S') (e = id('gradient')) ? e.updateSat(v) : alert('sat: '+v);
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
	}
	if (s) updateSlider(s); else
	for (i in BOW) updateSlider(i);
}

function updateShape(s) {
	if (!isNaN(s)) select.shape.value = s, s = 0;
	s = select.shapeFlags[(s?s:s=select.shape).value];
var	a = id('warn'), b = a.firstElementChild, c = [], f = {lineStyle:16, textStyle:32}, i;
	for (i in f) id(i).style.display = ((!(s & f[i]) == !(f[i] < 32))?'none':'');
	for (i in abc) if (!(s & (1<<i))) c.push(abc[i]);
	setClass(a, (mode.erase = !(mode.shape || mode.step || !(s & 2)))?'red':'');
	setClass(container, s = c.join(' '));
	do {
		c = b.firstElementChild;
		do {
			if (s.indexOf(c.className.slice(NS.length+1)) < 0) {b.title = c.title; break;}
		} while (c = c.nextSibling);
	} while (b = b.nextSibling);	//* <- because <button><div title></button> won't show tooltip
}

function updateSaveFileSize(e) {
var	i = e.id.slice(-1);
	if (cue.upd[i]) cue.upd[i] = 0,
	e.title = e.title.replace(regTipBrackets, '')+' ('+(cnv.view.toDataURL({J:IJ,P:''}[i]).length / 1300).toFixed(0)+' KB)';
}

function updateDim(i) {
	if (i) {
	var	a = id('img-'+i), b, c = cnv.view[i], j, v = parseInt(a.value) || 0;
		a.value = v = (
			v < (b = select.imgLimits[i][0]) ? b : (
			v > (b = select.imgLimits[i][1]) ? b : v)
		);
		for (j in cnv) cnv[j][i] = v;
		if (v > c) //historyAct(0),
			draw.view(2);
	}
	c = container.style, b = 'minWidth', a = (v = cnv.view.width)+'px';
	if (c[b] != a) {
		c[b] = a;
		if (a = outside[i = 'resize_style']) {
			v += 24;
			if ((e = outside.resize_min_id) && (e = document.getElementById(e)) && (e = e.offsetWidth) && e > v) v = e;
			c = id(i) || setId(cre('style', id()), i);
			c.innerHTML = a+'{max-width:'+v+'px;}';
		}
	}
}

function updateEraser() {
var	a = select.affect, i = a.value, a = (i == a.length-1), b = 'button', e = id(b+'E');
	tool.clip = select.options.affect[i];
	if (e) setClass(e, b+(a?'-active':''));
	return a;
}

function toggleMode(i, v) {
	if (isNaN(i)) i = modes.indexOf[n = i];
	if (i < 0 || i >= modes.length) return alert(lang.bad_id+'\n\nid='+i+'\nk='+v+','+n+'\nlen='+modes.length+'\n'+modes.join('\n'));
	else var e, n = modes[i];

	v = mode[n] = (isNaN(v) ? !mode[n] : v > 0);

	if (e = id('check'+modeL[i])) {
		setClass(e, 'button'+(v?'-active':''));
		if (e.parentNode.id == NS+'-warn') updateShape();
	}
	if (n == 'debug') {
		text.debug.textContent = '';
		interval.fps ? clearInterval(interval.fps) : (interval.fps = setInterval(fpsCount, 1000));
	}
}

function toolTweak(prop, value) {
	for (i in BOW) if (prop == BOWL[i]) {
	var	b = BOW[i];
		if (value > 0) tool[b] = value;
		else {
		var	v = new Number(tool[b]), s = RANGE[prop].step;
			tool[b] = value ? v-s : v+s;
		}
		return updateSliders(i);
	}
}

function toolSwap(t, k) {
var	i, j, a = select.affect;

//* exchange working sets
	if (isNaN(t)) {
		t = tools[0];
		tool = tools[0] = tools[1];
		tools[1] = t;
	} else

//* restore all defaults
	if (t > TOOLS_REF.length) {
		for (j in TOOLS_REF)
		for (i in TOOLS_REF[j]) tools[j][i] = TOOLS_REF[j][i];
		for (i in select.lineCaps) select[i].value = 0;
		toolSwap(-1, k);
		tool.width = t;
	} else

//* restore front set to one of defaults + line shape
	if (t > 0) {
		for (i in (t = TOOLS_REF[t-1])) tool[i] = t[i];
		updateShape(0);
	} else

//* drop switches, set shape
	if (t) {
		if (!k) for (i in {shape:0, step:0}) if (mode[i]) toggleMode(modes.indexOf[i]);
		return updateShape(-t-1);

//* toggle eraser mode
	} else {
		return a.value = (a.value > 0?0:a.length-1), updateEraser();
	}
	i = select.options.affect.indexOf(tool.clip), a.value = (i < 0?0:i);
	updateColor(tool.color);
	updateColor(0,1);
	updateEraser();
	updateSliders();
}

//* Save, load, send picture *-------------------------------------------------

function autoSave() {if (mode.autoSave && cue.autoSave && !(cue.autoSave = (draw.active?-1:0))) sendPic(2,true);}
function fpsCount() {fps = ticks; ticks = 0;}
function timeElapsed() {text.timer.textContent = unixDateToHMS(timer += 1000, 1);}

function unixDateToHMS(t,u,y) {
var	d = t ? new Date(t+(t >0?0:new Date())) : new Date(), t = ['Hours','Minutes','Seconds'], u = 'get'+(u?'UTC':'');
	if (y) t = ['FullYear','Month','Date'].concat(t);
	for (i in t) if ((t[i] = d[u+t[i]]()+(y && i == 1?1:0)) < 10) t[i] = '0'+t[i];
	d = '-', u = (y > 1?d:':');
	return y ? t[0]+d+t[1]+d+t[2]+(y > 1?'_':' ')+t[3]+u+t[4]+u+t[5] : t.join(u);
}

function getSendMeta(sz) {
var	a = ['clip', 'mask', 'lighter', 'xor']
,	b = ['resize', 'integral']
,	i,j = ', ', k,m = [], n = [], u = [], s = []
,	t = +new Date;
	for (i in count) if ((k = count[i]) > 1 || (i != 'layers' && k > 0)) u.push(k+' '+(k > 1?i:i.replace(/s+$/i, '')));
	for (i in used_shape) s.push(i);
	for (i in used) u.push(used[i]);
	draw.history.layers.map(function(v) {
		if (v.clip > 0 && m.indexOf(k = a[v.clip    - 2]) < 0) m.push(k);
		if (v.blur > 0 && n.indexOf(k = b[v.filter || 0]) < 0) n.push(k);
	});
	for (i in (a = {Shape:s, Composition:m, Filter:n})) if (a[i].length) u.push(i+': '+a[i].join(j));
	a = [
		'open_time: '+t0+'-'+t
	,	'draw_time: '+draw.time.all.join('-')
	,	'active_time: '+draw.time.sum()
	,	'app: '+NS+' '+INFO_VERSION
	,	'pixels: '+cnv.view.width+'x'+cnv.view.height
	,	'bytes: '+(
			sz || [
				'png = '+ cnv.view.toDataURL().length
			,	'jpg = '+ cnv.view.toDataURL(IJ).length
			].join(j)
		)
	];
	if (u.length) a.push('used: '+u.join(j));
	return a.join('\n');
}

function getSaveLayers(c) {
var	skip = ['pos', 'last', 'reversable', 'filtered', 'data']
,	a = [], i,t,d = '-'
,	b = {
		time: (
			(t = draw.time).all.join(d)
		+	(t.activeStart ? '='+t.sum() : '')
		+	(used.read ? d+used.read : '')
		)
	};
	if (c) b.meta = getSendMeta();
	draw.history.layers.map(function(v,k) {
		c = {};
		for (i in v) if (skip.indexOf(i) < 0 && (!k || (i in NEW_LAYER ? (v[i] != NEW_LAYER[i]) : v[i]))) c[i] = v[i];
		if (v[i = 'data']) {
			if (d = v[i][v.pos]) {
				ctx.temp.putImageData(d, 0, 0);
				c[i] = cnv.temp.toDataURL();
			} else return;
		}
		a.push(c);
	});
	return b.layers = a, b;	//* <- object, needs JSON.stringify(b)
}

function readSavedLayers(b) {
	if (!b.time || !b.layers) return false;
	for (i in count) count[i] = 0;
var	d = draw.history
,	dt = draw.time
,	i,j = '-'
,	a = b.time.split(j)
,	t = dt.all = a.slice(0,2).map(orz)
	;
	dt.activeStart = dt.activeSum = 0;
	if (a.length > 2) used.read = a.slice(2).join(j);
	if (a[1].indexOf('=') >= 0) dt.activeSum = orz(a[1].split('=', 2)[1]);
	if (!dt.activeSum) dt.activeSum = t[1]-t[0];
	t = t[1];
	a = id('saveTime');
	a.title = new Date(t);
	a.textContent = unixDateToHMS(t,0,1).split(' ',2)[1];
	a = b.layers, i = j = a[d.layer = 0].max = a.length, d.layers = [a[0]];
	while (--i) d = a[i], d.z = i, readPic(d);
	return j;
}

function getSaveFileName(j) {
	return unixDateToHMS(0,0,2)+'_'+draw.time.all.join('-')+(j || '.json');
}

function saveDL(data, suffix) {

	function dataToURI(data) {
		return (data.slice(0,5) == prefix ? data : prefix+type+','+encodeURIComponent(data));
	}

	function dataToBlob(data) {
		if (u && u.createObjectURL) {
			if (data.slice(0, k = prefix.length) == prefix) {
			var	i = data.indexOf(',')
			,	meta = data.slice(k,i)
			,	data = data.slice(i+1)
			,	k = meta.indexOf(';')
				;
				if (k < 0) type = meta, data = decodeURIComponent(data);
				else {
					type = meta.slice(0,k);
					if (meta.slice(k+1) == 'base64') data = atob(data);
				}
			}
			data = Uint8Array.from(modes.map.call(data, function(v) {return v.charCodeAt(0);}));
			return u.createObjectURL(new Blob([data], {'type': type}));
		}
	}

var	u = window.URL || window.webkitURL
,	data = ''+data
,	prefix = 'data:'
,	type = 'text/plain'
	;
	if (DL) {
		try {
		var	a = cre('a', container)
		,	blob = dataToBlob(data)
			;
			a.href = ''+(blob || dataToURI(data));
			a[DL] = getSaveFileName(suffix);
			a.click();
			setTimeout(function() {
				if (blob) u.revokeObjectURL(blob);
				a.parentNode.removeChild(a);
			}, 12345);
		} catch(e) {
			alert('Error code: '+e.code+', '+e.message+'\n\n'+lang.copy_to_save+':\n\n'+data);
		}
	} else window.open(dataToURI(data), '_blank');
}

function sendPic(dest, auto) {
var	a = auto || false, b,c,d,e,f,i,j,k,l,t,v = cnv.view;
	draw.view(1);

	function getTimeToShow(s) {
		if (!s) return '-';
	var	a = s.split('-', 2).map(orz), i,t,r = '';
		for (i = 0; i < 2; i++) r += ' \r\n'+((t = a[i]) ? unixDateToHMS(t,0,1) : '-');
		return r;
	}

	switch (dest) {
	case 0:
	case 1:	saveDL(c = cnv.view.toDataURL(dest?IJ:''), dest?'.jpg':'.png');
		break;
//* save project
	case 2:
		if (fillCheck()) return a?c:alert(lang.flood);
		c = cnv.view.toDataURL();
		if (!LS) return a?c:alert(lang.no_LS);
		d = LS[CR[1].R];
		if (d == c) return a?c:alert(lang.no_change);
		t = LS[CR[1].T], e = LS[CR[2].R] || 0, l = LS[CL];
		if (!a && e == c) {
			LS[CR[1].R] = e;
			LS[CR[1].T] = LS[CR[2].T];
			LS[CR[1].L] = LS[CR[2].L];
			LS[CR[2].R] = d;
			LS[CR[2].T] = t;
			LS[CR[2].L] = l;
			alert(lang.found_swap);
		} else
		if (a || confirm(lang.confirm.save + getTimeToShow(t))) {
			function rem(a) {var r = 'RTL', i = r.length; while (i--) LS.removeItem(CR[a][r[i]]);}
			try {
				if (e) rem(2);
				if (t) {
					rem(1);
					LS[CR[2].R] = d;
					LS[CR[2].T] = t;
					LS[CR[2].L] = l;
				}
				b = getSaveLayers();
				LS[CR[1].R] = c;
				LS[CR[1].T] = b.time;
				LS[CR[1].L] = JSON.stringify(b);
			} catch(e) {
				rem(1), rem(2);
				try {
					LS[CR[1].R] = d;
					LS[CR[1].T] = t;
					LS[CR[1].L] = l;
				} catch(i) {rem(1); e.message += '\n'+i.message;}
				return alert(lang.no_space+'\nError code: '+e.code+', '+e.message), c;
			}
			id('saveTime').textContent = unixDateToHMS();
			cue.autoSave = 0, a = 'AL', d = 'button';
			for (i in a) if (e = id(d+a[i])) setClass(e, d);
		}
		break;
//* loading
	case 3:
	case 4:
		if (!LS) return alert(lang.no_LS);
		t = LS[CR[1].T];
		if (!t) return;
		d = LS[CR[1].R], i = CR[1].L;
		if ((d == (c = cnv.view.toDataURL()))
		|| ((a = draw.history.layer) && draw.history.layers[a].name == unixDateToHMS(+t.split('-')[1],0,1))
		) {
			if ((!(t = LS[CR[2].T]) || ((d = LS[CR[2].R]) == c))) return alert(lang.no_change);
			i = CR[2].L;
		}
//* load flat image to new layer
		if (dest == 4) {
		var	dt = draw.time, a = t.split('-'), t = a.slice(0,2).map(orz), i = t[1], c = unixDateToHMS(i,0,1);
			if (a.length > 2) used.read = a.slice(2).join('-');
			if (dt.all[0] > t[0]) dt.all[0] = t[0];
			if (dt.all[1] < t[1]) {
				dt.all[1] = t[1];
				a = id('saveTime');
				a.title = new Date(i);
				a.textContent = c.split(' ',2)[1];
			}
			draw.time.act(1);
			readPic({name:c, data:d});
			used.LS = 'Local Storage';
		} else
//* load project
		if (!LS[i] || (b = JSON.parse(LS[i])).time != t) alert(lang.no_layers); else
		if (confirm(lang.confirm.load + getTimeToShow(t))) {
			draw.time.act(1);
			used = {LS:'Local Storage'}, readSavedLayers(b);
		}
		break;
	case 5:
	case 6:
		if (a || ((outside.read || (outside.read = id('read'))) && (a = outside.read.value))) {
	//		draw.time = [0, 0];
			if (dest == 5) a = readPic(a);
			else {
				try {
					if (
						(b = a.data)
					&&	(i = b.indexOf('{')) >= 0
					&&	(j = b.lastIndexOf('}')) >= 0
					&&	(b = b.slice(i, j+1))
					&&	readSavedLayers(JSON.parse(b))
					) {
						a = a.name;
					} else if (confirm(lang.bad_data+' \r\n'+lang.confirm.reprint)) {
						b = JSON.stringify(b, null, '\t');
						saveDL(b);
					}
				} catch(e) {
					alert(lang.bad_data), a = '';
				}
			}
			if (a.length) {
				draw.time.act(1);
				used.read = 'Read File: '+a;
			}
		}
		break;
//* save project text as file
	case 7:
		b = JSON.stringify(getSaveLayers(1), null, '\t');
		try {
		var	bb = new BlobBuilder();
			bb.append(b);
		var	blob = bb.getBlob('text/plain');
			saveAs(blob, getSaveFileName());
		} catch(e) {
			saveDL(b);
		}
		break;
//* send
	default:
		if (dest) alert(lang.bad_id+'\n\nid='+dest+'\na='+auto); else
		if (!outside.send) alert(lang.no_form); else
		if (fillCheck()) alert(lang.flood); else {
			a = select.imgLimits, c = 'send';
			for (i in a) if (v[i] < a[i][0] || v[i] > a[i][1]) c = 'size';
		}
		if (c && confirm(lang.confirm[c])) {
			if ((f = outside.send) && f.tagName) clearContent(f);
			else {
				setId(e = cre('form', container), 'send');
				if (!f.length || f.toLowerCase() != 'get') e.setAttribute('method', 'post');
				outside.send = f = e;
			}
		var	pngData = sendPic(2,-1), jpgData, a = {txt:0,pic:0};
			for (i in a) if (!(a[i] = id(i))) {
				setId(e = a[i] = cre('input', f), e.name = i).type = 'hidden';
			}
			e = pngData.length, d = select.imgRes;
			c = v.width * v.height;
			d = d.width * d.height;
			d = ((
				(i = outside.jpg)
			&&	e > i
			&&	((c <= d) || (e > (i *= c/d)))
			&&	e > (t = (jpgData = v.toDataURL(IJ)).length)
			) ? jpgData : pngData);
			if (mode.debug) alert('png limit = '+i+'\npng = '+e+'\njpg = '+t);
			a.pic.value = d;
			a.txt.value = getSendMeta(d.length);
			f.encoding = f.enctype = 'multipart/form-data';
			if ((i = outside.check) && (e = document.getElementById(i))) e.setAttribute('data-id', f.id), e.click();
			else f.submit();
		}
	}
	return c;
}

function readPic(s) {
	if (!s || s == 0 || (!s.data && !s.length)) return;
	if (!s.data) s = {data: s, name: (0 === s.indexOf('data:') ? s.split(',', 1) : s)};
var	e = new Image(), i = 'lcd', lcd = id(i);
	if (!lcd) setId(lcd = document.createElement('div'), i), container.parentNode.insertBefore(lcd, container);
	setRemove(e);

	function setLCD() {
		lcd.textContent = lang.loading+': '+loading;
		lcd.style.lineHeight = cnv.view.height+'px';
	}

	e.onload = function () {
		delete s.data;
	var	d,t = 1;
		for (i in select.imgRes) if (s.z || cnv.view[i] < e[i]) {
			id('img-'+i).value = e[i];
			for (d in cnv) cnv[d][i] = e[i];
			if (outside[i[0]+'l']) updateDim(i), t = 0;
		}
		if (t) updateDim();
		i = draw.history, j = i.layer;
		if (j > 0 && !i.layers[j].last && !i.cur()) tweakLayer(s.name,j,1); else newLayer(s);
		ctx.draw.clearRect(0, 0, cnv.view.width, cnv.view.height);
		ctx.draw.drawImage(e, 0, 0);
		historyAct(s.z ? draw.time.all[1] : null);
		cue.autoSave = 0;
		if (d = e.parentNode) d.removeChild(e);
		if (--loading < 1) loading = 0, container.style.visibility = lcd.textContent = '';
		else setLCD();
	}

	if (!(mode.debug || text.debug.innerHTML) && ++loading > 1) container.style.visibility = 'hidden', setLCD();
	draw.container.appendChild(e);
	return e.src = s.data, s.name;
}

//* Hot keys *-----------------------------------------------------------------

function mouseClickBarrier(event) {
	event.stopPropagation();
	event.cancelBubble = true;
	return false;
}

function browserHotKeyPrevent(event) {
	return ((!draw.active && isMouseIn() > 0) || (event.keyCode == 27))
	? ((event.returnValue = false) || event.preventDefault() || true)
	: false;
}

function hotWheel(event) {
	if (browserHotKeyPrevent(event)) {
	var	d = event.deltaY || event.detail || event.wheelDelta;
		toolTweak(
			event.shiftKey	?'G':(
			event.altKey	?'B':(
			event.ctrlKey	?'O':'W')), d < 0?0:-1);
		if (mode.debug) text.debug.innerHTML += ' d='+d;
	}
	return false;
}

function hotKeys(event) {
	if (!loading && browserHotKeyPrevent(event)) {
		function c(s) {return s.charCodeAt(0);}	//* <- only 1st letter is a hotkey
	var	n = event.keyCode - c('0');
		if ((n?n:n=10) > 0 && n < 11) {
		var	i, k = [event.shiftKey, event.altKey, event.ctrlKey, 1];
			for (i in k) if (k[i]) return toolTweak(k = BOWL[i], RANGE[k].step < 1 ? n/10 : (n>5 ? (n-5)*10 : n));
		} else
		if (event.altKey)
		switch (event.keyCode) {
			case 38:	moveLayer(0);	break;
			case 40:	moveLayer(-1);	break;
			case 37:	moveLayer();	break;
			case 39:	moveLayer(1);	break;

			case c('E'):	moveLayer('del');break;
			case c('L-new'):newLayer();	break;
			case c('Copy'):	newLayer(1);	break;
			case c('Merge'):newLayer(-1);	break;

			case c('A-def'):toolSwap(3);	break;
			case c('SCurs'):toggleMode(5);	break;
		//	case c('GlobalHistory'):toggleMode(8);	break;

			case c('Grid'):
			case c('Blur'):
			case c('Opacity'):
			case c('Width'):toolTweak(String.fromCharCode(event.keyCode), -1);
		} else
		if (event.shiftKey)
		switch (event.keyCode) {
			case 38:	selectLayer(-2,0,1);break;
			case 40:	selectLayer(-1,0,1);break;
			case 37:	selectLayer('top',0,1);break;
			case 39:	selectLayer(0,0,1);
		} else
		switch (event.keyCode) {
			case 27:	drawEnd();	break;	//* Esc
			case 36: updateViewport();	break;	//* Home
			case 8:
if (text.debug.innerHTML.length)	toggleMode(0);	break;	//* 45=Ins, 42=106=Num *, 8=bksp
			case c('L/shp'):toggleMode(1);	break;
			case c('U/stp'):toggleMode(2);	break;

			case 112:	resetAside();	break;	//* F1
			case 120:	sendPic(0);	break;	//* F9
		//	case 118:	sendPic(1);	break;	//* jpeg
			case 113:	sendPic(2);	break;
			case 114:	sendPic(3);	break;
			case 115:	sendPic(4);	break;
			case 117:	sendPic(5);	break;
			case 118:	sendPic(7);	break;
			case 119:	sendPic();	break;

			case c('ZUndo'):historyAct(-1);	break;
			case c('XRedo'):historyAct(1);	break;
			case c('CPick'):pickColor();	break;
			case c('Fill'):	fillScreen(0);	break;
			case c('Del'):	fillScreen(1);	break;
			case c('Invrt'):fillScreen(-1);	break;
			case c('Hflip'):fillScreen(-2);	break;
			case c('Vflip'):fillScreen(-3);	break;
			case c('Swap'):	toolSwap();	break;
			case c('Erase'):toolSwap(0);	break;
			case c('A-pen'):toolSwap(1);	break;
			case c('K-wht'):toolSwap(2);	break;

			case c('QLine'):updateShape(0);	break;
			case c('Poly'):	updateShape(1);	break;
			case c('TestRPoly'):updateShape(2);	break;
			case c('Rectg'):updateShape(3);	break;
		//	case c('Circl'):updateShape(4);	break;
		//	case c('Elips'):updateShape(5);	break;
			case c('YRadi'):updateShape(6);	break;
			case c('Move'):	updateShape(7);	break;
		//	case c('Lasso'):updateShape(8);	break;
		//	case c('Text'):	updateShape(9);	break;

			case c('Grid'):
			case c('Blur'):
			case c('Opacity'):
			case c('Width'):toolTweak(String.fromCharCode(event.keyCode), 0); break;

			case 106: case 42:
				for (i = 1, k = ''; i < 3; i++) k += '<br>Save'+i+'.time: '+LS[CR[i].T]
+(LS[CR[i].R]?', pic size: '+LS[CR[i].R].length:'')
+(LS[CR[i].L]?', layers sum: <a href="javascript:alert('+NS+'.LS[\''+CR[i].L+'\'])">'+LS[CR[i].L].length+'</a>':'');
				draw.view(1);
				text.debug.innerHTML = replaceAll(
"\n<a href=\"javascript:var s=' ',t='';for(i in |)t+='\\n'+i+' = '+(|[i]+s).split(s,1);alert(t);\">self.props</a>"+
"\n<a href=\"javascript:var t='',o=|.o;for(i in o)t+='\\n'+i+' = '+o[i];alert(t);\">self.outside</a>"+
(outside.read?'':'<br>\nF6=read: <textarea id="|-read" value="/9.png"></textarea>'), '|', NS)
+', '+CT+', '+CL+': '+(CR.length || CR)+(loading?', loading: '+loading:'')+k+'<hr>'+getSendMeta().replace(/[\r\n]+/g, '<br>');
			break;

			default: if (mode.debug) text.debug.innerHTML += '\n'+String.fromCharCode(event.keyCode)+'='+event.keyCode;
		}
	}
	return false;
}

//* Positioning *--------------------------------------------------------------

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
	for (i in p) cnv.view.style[p[i]+'transform'] = t;	//* <- not working in opera11.10, though should be possible
	updateDebugScreen();
}

function updateDebugScreen() {
	if (!mode.debug) return;
	ticks ++;
var	t = '</td><td>', r = '</td></tr>	<tr><td>', a = draw.turn, b = 'turn: ', c = draw.step, i;
//	if (a) for (i in a) b += i+'='+a[i]+'; ';
	i = isMouseIn();
	text.debug.innerHTML = '<table><tr><td>'
+draw.refresh+t+'1st='+draw.time.all.join(d+'last=')+t+'fps='+fps
+r+'Relative'+t+'x='+draw.o.x+t+'y='+draw.o.y+''+t+i+(i?',rgb='+pickColor(1):'')
+r+'DrawOfst'+t+'x='+draw.cur.x+t+'y='+draw.cur.y+t+'btn='+draw.btn+',active='+draw.active
+r+'Previous'+t+'x='+draw.prev.x+t+'y='+draw.prev.y+t+'chain='+mode.click+(c?''
+r+'StpStart'+t+'x='+c.prev.x+t+'y='+c.prev.y+',step1done='+c.done
+r+'Step_End'+t+'x='+c.cur.x+t+'y='+c.cur.y:'')+'</td></tr></table>'+showProps(tool,1,1)+(a?b+showProps(a,1):'');
}

function updatePosition(event) {
var	i = select.shape.value, g = tool.grid, o = (
		(!mode.step && mode.shape && (select.shapeFlags[i] & 2))
	||	(select.shapeFig[i] && !((draw.active ? ctx.draw.lineWidth : tool.width) % 2))
	? 0 : DRAW_PIXEL_OFFSET);	//* <- maybe not a 100% fix yet

	draw.o.x = (draw.m.x = event.pageX) - CANVAS_BORDER - draw.container.offsetLeft;
	draw.o.y = (draw.m.y = event.pageY) - CANVAS_BORDER - draw.container.offsetTop;
	if (draw.pan && !(draw.turn && draw.turn.pan)) for (i in draw.o) draw.o[i] -= draw.pan[i];
	if (!draw.turn && (draw.angle || draw.zoom != 1)) {
	var	r = getCursorRad(2, draw.o.x, draw.o.y);
		if (draw.angle) r.a -= draw.aRad;
		if (draw.zoom != 1) r.d /= draw.zoom;
		draw.o.x = Math.cos(r.a)*r.d + cnv.view.width/2;
		draw.o.y = Math.sin(r.a)*r.d + cnv.view.height/2;
		o = 0;
	}
	for (i in draw.o) {
		if (g > 0) draw.o[i] = Math.round(draw.o[i]/g)*g;
		draw.cur[i] = o + draw.o[i];
	}
}

function getCursorRad(r, x, y) {
	if (draw.turn.pan) return {x: draw.o.x, y: draw.o.y};
	x = (isNaN(x) ? draw.cur.x : x) - cnv.view.width/2;
	y = (isNaN(y) ? draw.cur.y : y) - cnv.view.height/2;
	return (r
	? {	a:Math.atan2(y, x)
	,	d:dist(y, x)	//* <- looks stupid, will do for now
	}
	: (draw.turn.zoom
		? dist(y, x)
		: Math.atan2(y, x)
	));
}

function isMouseIn() {
	if ('x' in draw.m) {
	var	a = container.getElementsByTagName('aside'), i = a.length, e, x, y;
		while (i--) if ((e = a[i]).id) {
			x = parseInt(e.style.left) || 0;
			y = parseInt(e.style.top) || 0;
			if (draw.m.x >= x && draw.m.y >= y && draw.m.x < x+e.offsetWidth && draw.m.y < y+e.offsetHeight) return -1;
		}
	}
	return (draw.o.x >= 0 && draw.o.y >= 0 && draw.o.x <= cnv.view.width && draw.o.y <= cnv.view.height)?1:0;
}

function getOffsetXY(e) {
var	x = 0, y = 0;
	while (e) x += e.offsetLeft, y += e.offsetTop, e = e.offsetParent;
	return {x:x, y:y};
}

function putInView(e,x,y) {
	if (isNaN(x)) {y = getOffsetXY(e); x = y.x; y = y.y;}
var	x0 = document.body.scrollLeft || document.documentElement.scrollLeft || 0
,	y0 = document.body.scrollTop || document.documentElement.scrollTop || 0;
	if (x < x0) x = x0; else if (x > (x0 += window.innerWidth - e.offsetWidth)) x = x0;
	if (y < y0) y = y0; else if (y > (y0 += window.innerHeight - e.offsetHeight)) y = y0;
	e.style.left = x+'px';
	e.style.top  = y+'px';
	return e;
}

function putOnTop(e) {
var	a = document.getElementsByTagName(e.tagName), i = a.length, zTop = 0, z;
	while (i--) if (zTop < (z = parseInt(a[i].style.zIndex))) zTop = z;
	e.style.zIndex = zTop+1;
	return e;
}

//* Drag and drop *------------------------------------------------------------

function dragStart(event) {
	event.stopPropagation();

var	e = event.target;
	while (!e.id) e = e.parentNode;
var	c = getOffsetXY(putOnTop(e));
	event.dataTransfer.setData('text/plain', e.id
	+','+	(event.pageX - parseInt(c.x))
	+','+	(event.pageY - parseInt(c.y))
	);
}

function dragMove(event) {
var	d = event.dataTransfer.getData('text/plain'), e;
	return (d
	&& (d.indexOf(NS) === 0)
	&& (d = d.split(',')).length === 3
	&& (e = document.getElementById(d[0]))
	)
	? putInView(e, event.pageX - parseInt(d[1]), event.pageY - parseInt(d[2]))
	: false;
}

function dragOver(event) {
	eventStop(event).preventDefault();

var	d = event.dataTransfer.files, e = d && d.length;
	event.dataTransfer.dropEffect = e?'copy':'move';
	if (!e) dragMove(event);
}

function drop(event) {
	eventStop(event).preventDefault();

//* Move windows: you can actually drop simple text strings like "NS-info,0,0"
	if (dragMove(event));
	else
//* Load images: from http://www.html5rocks.com/en/tutorials/file/dndfiles/
	if (window.FileReader) {
		function rr(pic) {
			if (!!((f = d[i]).type && m.test(f.type)) !== !!pic) return;
			(r = new FileReader()).onload = (function(f) {
				return function(e) {
					sendPic(pic?5:6, {
						name: f.name
					,	data: e.target.result
					});
				};
			})(f);
			r[pic?'readAsDataURL':'readAsText'](f);
			return ++k;
		}

	var	d = event.dataTransfer.files, f, i = (d?d.length:0), j = i, k = 0, r, m = /^image/i;
		while (i--) if (rr()) return;
		i = j;
		while (i--) rr(1);
		if (j && !k) alert(lang.no_files);
	}
}

//* Autoplace windows around canvas *------------------------------------------

function resetAside() {
var	margin = 2, h = 256//Math.max(cnv.view.height, select.imgRes.height)
,	off = getOffsetXY(draw.container), x = off.x + cnv.view.offsetWidth + margin, y = 0, z
,	a = container.getElementsByTagName('aside'), i = a.length, e;
	while (i--) if ((e = a[i]).id) {
		e.style.display = '';
		if (z) x = off.x - e.offsetWidth - margin;
		putInView(e, x, y+off.y);
		y += e.offsetHeight + margin;
		if (!z && y > h) y = 0, z = 1;
	}
}

//* Initialization *-----------------------------------------------------------

function init() {
	if (isTest()) document.title += ': '+NS+' '+INFO_VERSION;
var	a = {B:'GRST',W:'A'},b,c = 'canvas',d,e,f,g,h,i,j,k,l,m,n, o = outside, style = '', s = '&nbsp;';
	for (i in a)
	for (j in a[i]) RANGE[a[i][j]] = RANGE[i];
	NEW_LAYER = {show:1, alpha: RANGE.A.max, pos: 0, last: 0};

	a = '\n\
<div id="load"><'+c+' id="'+c+'" tabindex="0">'+lang.no_canvas+'</'+c+'></div>\n\
<div id="debug"></div>';
	for (i in lang.windows) a += '\n<aside id="'+i+'"></aside>';

	setContent(container = id(), a), e = id(c);
	if (!e.getContext) return;

	for (i in cnv) ctx[i] = (cnv[i] = (i == 'view'?e:document.createElement(c))).getContext('2d');
	for (i in select.imgRes) {
		b = o[a = i[0]]?o[a]:(o[a] = o[i]?o[i]:select.imgRes[i]);
		for (j in cnv) cnv[j][i] = b;
		if ((o[b = c = a+'l']
		|| (o[c] = o[b = i+'Limit']))
		&& (f = o[b].match(regLimit))) select.imgLimits[i] = [parseInt(f[1]), parseInt(f[2])];
	}

var	wnd = container.getElementsByTagName('aside'), wit = wnd.length;
	while (wit--) if ((e = wnd[wit]).id) {
		style += '\n#'+e.id+' header i::before {content:"'+lang.windows[k = reId(e)]+'";}';
		a = '<a href="javascript:;" onClick="', l = '" title="';
		c = '<header draggable="true">'+a+'toggleView(this.parentNode.parentNode)">[ x ]</a><i>:</i></header>\n';

//* Add content as text *------------------------------------------------------

		if (k == 'color') {
			c += '<div class="selects">'+lang.hex+': <input type="text" id="color-text" onChange="updateColor()'+l+lang.hex_hint+'"> '
			+lang.palette+': <select id="palette" onChange="updatePalette()"></select>'
			+'</div><div id="colors"></div>';
		} else

		if (k == 'info') {
			d = '';
			for (i in select.imgRes) d += (d?' x ':'')
+'<input type="text" value="'+o[i[0]]+'" id="img-'+i+'" onChange="updateDim(\''+i+'\')'+l+lang.size_hint+select.imgLimits[i]+'">';

			b = '<abbr title="', f = '<i class="rf">', g = f+s+b
+NS.toUpperCase()+', '+INFO_ABBR+', '+lang.info_pad+', '+INFO_DATE
//+', '+lang.lang[0]+': '+lang.lang[1]
+'">'+INFO_VERSION+'</abbr>.</i>';

			c += lang.size
+':	'+d+'<hr><p>'+lang.info.join('<br>')
				.replace(/-<br>/gi, '</p><hr><p>')
				.replace(/\{([^=};]+)(?:=([^=};]+))?;([^}]+)}/g, a+'$1($2)">$3</a>')
				.replace(/\[([^\];]+);([^\]]+)]/g, '<span id="$1">$2</span>')
+':	'+f+b+(new Date())+'" id="saveTime">'+lang.info_no_save+'</abbr>.</i>'
+'<br>	'+a+'toggleView(\'timer\')'+l+lang.show_hint+'">'+lang.info_time+'</a>'
+':	'+f+'<span id="timer">'+lang.info_no_time+'</span>.</i><br>'+lang.info_drop+g+'</p>';
		} else

		if (k == 'layer') {
			c += '<div id="'+k+'s"></div><div id="filters"><hr><div id="'+k+'-sliders"></div>'
+'<select id="filter" onChange="tweakLayer(this)'+l+lang.filter+'"></select><br>'
+'<!--select id="compose" onChange="tweakLayer(this,-1,1)'+l+lang.compose+'"></select--></div>';
		} else

		if (k == 'tool') {
			d = '<select id="', b = '"></select>', j = select.lineCaps;
			c += '<div class="selects"><div class="rf">'+d+'shape" onChange="updateShape(this)'+l+lang.shape+b+'<div id="lineStyle">';
			for (i in j) c += d+i+l+(j[i] || i)+b+'<br>';
			c += d+'affect" onChange="updateEraser()'+l+lang.compose+b+'</div><div id="textStyle">'
+d+'font'+l+lang.font+b+'<br><textarea placeholder="'+lang.text_hint+'"></textarea></div></div><div id="'+k+'-sliders">';
			i = BOW.length;
			while (i--) c += getSlider(BOWL[i]);
			c += '</div></div>';
		} else c += 'TODO: '+k;

		setContent(e, c);

//* Add DOM + events *---------------------------------------------------------

		function btnArray(a,bf) {
		var	b = 'button', c = document.createElement('div'), d,i,j,k;

			function btnContent(e, a) {
			var	t = lang.b[a[0]], d = '<div class="'+b+'-', c = '</div>';
				e.title = t.t?t.t:t;
				setContent(e, d+'key">'+a[1]+c+a[2]+d+'subtitle"><br>'+(t.t?t.sub:a[0])+c);
				return e;
			}

			for (i in a) if (isNaN(k = a[i])) {
				d = document.createElement(b);

				if (k[0].indexOf('|') > 0) {
				var	subt = k[0].split('|')
				,	pict = k[2].split('|');
					for (j in subt) setClass(d.appendChild(btnContent(
						document.createElement('div'), [subt[j], k[1], pict[j]]
					)), 'abc'[j]);
				} else btnContent(d, k);

				setClass(d, b);
				if (k.length > 3) setEvent(d, 'onclick', k[3]);
				if (k.length > 4) setId(d, k[4]);
				c.appendChild(d);
			} else if (k != -1) c.innerHTML += k < 0?'<hr>':(k?repeat(s,k):'<br>');
			setClass(c, b+'s');
			return bf ? e.insertBefore(c, bf) : e.appendChild(c);
		}

		a = 'Alt+', b = 'button', c = 'color', d = 'sendPic(', f = 'fillScreen(', g = 'toggleMode(', h = 'check', j = 'toolSwap(';
		if (k == 'info') {
			btnArray([
//* subtitle, hotkey, pictogram, function, id
-9,	['png'	,'F9'	,'P'		,d+'0)'	,b+'P'
],	['jpeg'	,s	,'J'		,d+'1)'	,b+'J'
],	['json'	,'F7'	,'&#x25A4;'	,d+'7)'
],1,	['save'	,'F2'	,'!'		,d+'2)'
],	['load'	,'F3'	,'?'		,d+'3)'	,b+'L'
],	['loadd','F4'	,'+'		,d+'4)'	,b+'A'
],
1,!o.read || 0 == o.read?-1:
	['read'	,'F6'	,'&#x21E7;'	,d+'5)'
],!o.send || 0 == o.send?-1:
	['done'	,'F8'	,'&#x21B5;'	,d+')'
]]);
		} else
		if (k == 'layer') {
			m = id(k+'-sliders'), d = 'ART', n = '';
			i = d.length; while (i--) n += getSlider(d[i], i == 2);
			setClass(m, 'rf ri');
			setContent(m, n);
			i = d.length; while (i--) setSlider(d[i]);

			d = 'moveLayer(', h = 'historyAct(', l = 'layer', n = 'newLayer(';
			btnArray([
//* subtitle, hotkey, pictogram, function, id
	['new'	,a+'L','&#x25A1;'	,n+')'
],	['delete',a+'E','&times;'	,d+'"del")',l+'E'
],
1,	['up'	,a+'U','&#x25B2;'	,d+'0)'	,l+'U'
],	['down'	,a+'I','&#x25BC;'	,d+'-1)',l+'D'
],
1,	['fill'	,'F'	,s		,f+'0)'	,c+'F'
],	['swap'	,'S'	,'&#X21C4;'	,j+')'
],	['erase','D'	,s		,f+'1)'	,c+'B'
],
0,	['copy'	,a+'C'	,'&#x25EB;'	,n+'1)'	,l+'C'
],	['merge',a+'M'	,'&#x25E7;'	,n+'-1)',l+'M'
],
1,	['top'	,a+'T','&#x2191;'	,d+')'	,l+'T'
],	['bottom',a+'Y','&#x2193;'	,d+'1)'	,l+'B'
],
1,	['invert','I'	,'&#x25D0;'	,f+'-1)'
],	['flip_h','H'	,'&#x2194;'	,f+'-2)',l+'H'
],	['flip_v','V'	,'&#x2195;'	,f+'-3)',l+'V'
],
0,	['undo'	,'Z'	,'&#x2190;'	,h+'-1)',b+'U'
],	['redo'	,'X'	,'&#x2192;'	,h+'1)'	,b+'R'
//],1,	['global',a+'G','&#x25A4;'	,g+'7)' ,h+'G'
]], e.firstElementChild.nextSibling).appendChild(m = id('sliderT'));
			setClass(m, 'rf ri');
		} else
		if (k == 'tool') {
			btnArray([
//* subtitle, hotkey, pictogram, function, id
-9,	['pencil','A'	,'i'		,j+'1)'
],	['chalk' ,'K'	,'&#x25CB;'	,j+'2)'
],	['reset' ,a+'A'	,'&#x25CE;'	,j+'3)'
],
1,	['eraser','E'	,'&#x25CC;'	,j+'0)'	,b+'E'
],	['cursor',a+'S'	,'&#x25CF;'	,g+'5)'	,h+'V'
],
1,	['line|area|copy'	,'L'	,'&ndash;|&#x25A0;|&#x25EB;'	,g+'1)'	,h+'L'
],	['curve|outline|rect'	,'U'	,'~|&#x25A1;|&#x25AF;'	,g+'2)'	,h+'U'
]]);
			i = id(h+'L');
			setId(i.parentNode.insertBefore(d = document.createElement('div'), i), 'warn');
			for (i in (a = 'LU')) d.appendChild(id(h+a[i]));
			for (i in BOW) setSlider(BOWL[i]);
		}

		e.addEventListener('mousedown', mouseClickBarrier, false);
		e.firstElementChild.addEventListener('dragstart', dragStart, false);
	}
	newLayer();

//* Global events, etc *-------------------------------------------------------


	draw.container = id('load'), b = 'button', i = (a = 'JP').length, h = /^header$/i;
	while (i--) if (e = id(b+a[i])) setEvent(e, 'onmouseover', 'updateSaveFileSize(this)');

	for (i in mode) modes.push(i);
	for (i in text) text[i] = id(i);
	for (i in (a = {L:CL, A:CT})) if (!LS || !LS[a[i]]) setClass(id(b+i), b+'-disabled');

	for (i in (a = [b, 'input', 'select', 'p', 'a']))
	for (c in (b = container.getElementsByTagName(a[i])))
	for (e in (d = ['onchange', 'onclick', 'onmouseover']))
	if (
		(f = b[c][d[e]])
	&&	(m = (''+f).match(regFunc))
	) {
		if (!self[f = m[1]]) self[f] = eval(f);
		if (f == 'toggleView' && !(m = b[c]).title) m.title = lang[h.test(m.parentNode.tagName)?'hide_hint':'show_hint'];
	}

	if (LS && (i = LS.historyPalette)) palette[0] = JSON.parse(i);

	d = 'download', DL = (d in b[0]?d:'');
	a = select.options, c = select.translated || a, f = (LS && (i = LS.lastPalette) && palette[i]?i:1);
	a.affect = a.compose, c.affect = c.compose;
	for (b in a) if (e = select[b] = id(b))
	for (i in a[b]) (
		e.options[e.options.length] = new Option((c[b]?c:a)[b][i], i)
	).selected = (b == 'palette'?(i == f):!i);

	for (i in modes) if (mode[modes[i]]) toggleMode(i, 1);		//* <- only after select lists are defined

//* listen on all page to prevent dead zones:
//* still fails to catch events outside of document block height less than of browser window.
	e = window;	//document.body;
	for (i in {onscroll:0, oncontextmenu:0}) cnv.view.setAttribute(i, 'return false;');
	for (i in (a = {
		dragover:	dragOver
	,	drop:		drop
	,	mousedown:	drawStart
	,	mousemove:	drawMove
	,	mouseup:	drawEnd
	,	keypress:	browserHotKeyPrevent
	,	keydown:	hotKeys
	,	mousewheel:	f = hotWheel
	,	wheel:		f
	,	scroll:		f
	})) e.addEventListener(i, a[i], false);

//* Get ready to work *--------------------------------------------------------

	toggleView('hotkeys');
	id('style').innerHTML += style;

	generatePalette(1, 85, 0);
	toolSwap(3, 1);	//* <- arbitrary default, also not changing switches
	updatePalette();
	updateViewport();
	resetAside();
}

//* External config *----------------------------------------------------------

function isTest() {

	function getNumClamped(i,n) {return Math.min(Math.max(orz(i) || n, 3), Number.MAX_SAFE_INTEGER || 100200300);}

	if (CR[0] !== 'C') return !o.send;

var	o = outside
,	f = o.send = id('send')
,	r = o.read = id('read')
,	v = id('vars')
,	a = [v,f,r]
,	s = ';'
,	regVarSep = /\s*[;\r\n\f]+\s*/g
,	regVarName = /^([^=]+)\s*=\s*/
,	e,i,j,k;

/* ext.config syntax:
	a) varname; var2=;		// no sign => value 1; no value => ''
	b) warname=two=3=last_val;	// all vars => same value (rightmost part)
*/
	for (i in a) if ((e = a[i]) && (e = (e.getAttribute('data-vars') || e.name))) {
		a = e.replace(regVarSep, s).split(s);
		for (i in a) if ((e = a[i].replace(regVarName, '$1=')).length) {
			if ((e = e.split('=')).length > 1) {
				k = e.pop();
				for (j in e) o[e[j]] = k;
			} else o[e[0]] = k = 1;
			if (e[0].substr(0,2) == 'jp') o.jpg = k;
		}
		break;			//* <- read vars batch in the first found attribute only; no care about the rest
	}

	k = 'y2', i = k.length, j = (o.saveprfx?o.saveprfx:NS)+CR, CR = [];
	while (i) CR[i--] = {R:e = j+k[i], T:e+CT, L:e+CL};
	CT = CR[1].T, CL = CR[1].L;

	o.undo = draw.history.max = getNumClamped(o.undo, 99);
	o.idle = draw.time.idle = getNumClamped(o.idle, 60)*1000;

	if (!o.lang) o.lang = document.documentElement.lang || 'en';

//* translation: Russian *-----------------------------------------------------

	if (o.lang == 'ru')
select.lineCaps = {lineCap: 'Концы линий', lineJoin: 'Сгибы линий'}
, select.translated = {
	shape	: ['линия', 'замкнутая линия', 'многоугольник', 'прямоугольник', 'круг', 'овал', 'излучение', 'сдвиг', 'сдвиг лассо', 'текст']
,	lineCap	: ['круг <->', 'срез |-|', 'квадрат [-]']
,	lineJoin: ['круг -x-', 'срез \\_/', 'угол V']
,	filter	: ['масштаб', 'интеграл']
,	compose	: ['поверх', 'под', 'в пределах', 'предел (маска)', 'свет', 'исключение', 'стёрка']
,	palette	: ['история', 'авто', 'разное', 'Тохо', 'градиент', 'круг']
}, lang = {
	lang: ['язык', 'Русский']
,	bad_data:	'Неправильный формат данных.'
,	bad_id:		'Ошибка выбора.'
,	flood:		'Полотно пусто.'
,	confirm: {
		send:	'Отправить рисунок в сеть?'
	,	size:	'Превышен размер полотна. Отправить всё равно?'
	,	save:	'Сохранить слои в память браузера? \r\nПерезаписать копию, изменённую: '
	,	load:	'Вернуть слои из памяти браузера? \r\nВосстановить копию, изменённую: '
	,	reprint:'Пересохранить с переносами строк и отступами?'
},	copy_to_save:	'Откройте новый текстовый файл, скопируйте в него всё ниже этой линии'
,	found_swap:	'Рисунок был в запасе, поменялись местами.'
,	loading:	'Ожидание загрузки изображений'
,	no_LS:		'Локальное Хранилище (память браузера) недоступно.'
,	no_space:	'Ошибка сохранения, нет места.'
,	no_files:	'Среди файлов не найдено изображений.'
,	no_layers:	'Позиция в памяти не содержит слоёв.'
,	no_form:	'Назначение недоступно.'
,	no_change:	'Нет изменений.'
,	no_canvas:	'Ваша программа не поддерживает HTML5-полотно.'
,	windows: {
		layer:	'Слои'
	,	info:	'Данные'
	,	color:	'Цвет'
	,	tool:	'Инструмент'
},	layer: {prefix:	'Слой'
	,	bg:	'Цвет фона и общие операции'
	,	hint: {
			bg:	'Сменить цвет фона: левым кликом на цвет основного инструмента, правым — запасного.'
		,	check:	'Видимость слоя. Правым кликом переключается режим группы ограниченного наложения.'
		,	name:	'Название слоя, меняйте на что-то осмысленное, чтобы не перепутать.'
		,	blur:	'Радиус размытия слоя.'
		,	alpha:	'Непрозрачность слоя при наложении.'
		,	undo:	'История отмен слоя.'
}},	tool: {	G:	'Шаг сетки'
	,	B:	'Тень'
	,	O:	'Непрозрачность'
	,	W:	'Толщина'
	,	T:	'Расслоение'
	,	R:	'Размытие'
	,	S:	'Насыщенность'
},	shape:		'Форма'
,	font:		'Шрифт'
,	text_hint:	'Ваш текст тут.'
,	filter:		'Фильтр'
,	compose:	'Режим наложения'
,	palette:	'Палитра'
,	hex:		'Код'
,	hex_hint:	'Формат ввода — #a, #f90, #ff9900, или 0,123,255'
,	hide_hint:	'Кликните, чтобы спрятать.'
,	show_hint:	'Кликните, чтобы спрятать или показать.'
,	info: [	'{toggleView=\'hotkeys\';Управление}: [hotkeys;(указатель над полотном)'
	,
	,	'Esc = сбросить незавершённое действие.'
	,	'C / средний клик = подобрать цвет с рисунка.'
	,
	,	'Выбор формы инструмента:'
	,	'Q = линия, прямая, кривая.'
	,	'P = лассо, замкнутая линия.'
	,	'R = прямоугольник.'
//	,	'T = печатный текст.'
	,	'Y = лучи солнца, шестерёнка.'
	,	'M = сдвиг, копия.'
	,
	,	'1-10 / колесо мыши	/ (Alt +) W = толщина кисти.'
	,	'Ctrl	+ 1-10 / колесо / (Alt +) O = прозрачность.'
	,	'Alt	+ 1-10 / колесо / (Alt +) B = размытие тени.'
	,	'Shift	+ 1-10 / колесо / (Alt +) G = шаг сетки.'
	,
	,	'Shift	+ стрелки = выбирать слой.'
	,	'Alt	+ стрелки = двигать слой по списку.'
	,
	,	'Ctrl	+ тяга = поворот полотна, Home = {updateViewport;сброс}.'
	,	'Alt	+ тяга = масштаб, Shift + т. = сдвиг рамки.'
	,
	,	']F1 = {resetAside;вернуть} панельки по местам.-'
	,	'Автосохранение раз в минуту'
],	info_no_save:	'ещё не было'
,	info_no_time:	'ещё нет'
,	info_time:	'Времени прошло'
,	info_pad:	'стопка для набросков'
,	info_drop:	'Можно перетащить сюда файлы с диска.'
,	size:		'Размер полотна'
,	size_hint:	'Число между '
,	b: {	undo:	{sub:'назад',	t:'Отменить последнее действие.'
	},	redo:	{sub:'вперёд',	t:'Отменить последнюю отмену.'
	},	fill:	{sub:'залить',	t:'Залить слой основным цветом.'
	},	erase:	{sub:'стереть',	t:'Залить слой прозрачностью, или фон — запасным цветом.'
	},	invert:	{sub:'инверт.',	t:'Обратить цвета полотна.'
	},	flip_h:	{sub:'отразить',t:'Отразить полотно слева направо.'
	},	flip_v:	{sub:'перевер.',t:'Перевернуть полотно вверх дном.'
	},	pencil:	{sub:'каранд.',	t:'Инструмент — тонкий простой карандаш.'
	},	chalk:	{sub:'мел',	t:'Инструмент — толстый белый карандаш.'
	},	eraser:	{sub:'стёрка',	t:'Переключить режим инструмента на стёрку / обратно на обычный.'
	},	swap:	{sub:'смена',	t:'Поменять инструменты местами.'
	},	reset:	{sub:'сброс',	t:'Сбросить инструменты к начальным.'
	},	line:	{sub:'прямая',	t:'Прямая линия 1 зажатием.'
	},	curve:	{sub:'кривая',	t:'Сглаживать углы пути / кривая линия 2 зажатиями.'
	},	area:	{sub:'закрас.',	t:'Закрашивать площадь геометрических фигур. \r\nНе обводить + не закрашивать = удалить площадь.'
	},	outline:{sub:'контур',	t:'Рисовать контур геометрических фигур. \r\nНе обводить + не закрашивать = удалить площадь.'
	},	copy:	{sub:'копия',	t:'Оставить старую копию.'
	},	rect:	{sub:'прямоуг.',t:'Сдвиг прямоугольником.'
	},	cursor:	{sub:'указат.',	t:'Показывать кисть на указателе.'
	},	rough:	{sub:'п.штрих',	t:'Уменьшить нагрузку, пропуская перерисовку штриха.'
	},	fps:	{sub:'п.кадры',	t:'Уменьшить нагрузку, пропуская кадры.'
	},	png:	{sub:'сохр.png',t:'Сохранить рисунок в PNG файл.'
	},	jpeg:	{sub:'сохр.jpg',t:'Сохранить рисунок в JPEG файл.'
	},	json:	{sub:'сох.json',t:'Сохранить слои в JSON файл.'
	},	save:	{sub:'сохран.',	t:'Сохранить слои в память браузера, 2 позиции по очереди.'
	},	load:	{sub:'загруз.',	t:'Вернуть слои из памяти браузера, 2 позиции по очереди. \r\n\
Может не сработать в некоторых браузерах, если не настроить автоматическую загрузку и показ изображений.'
	},	loadd:	{sub:'загр.доб',t:'Добавить рисунок из памяти браузера на новый слой, 2 позиции по очереди. \r\n\
Может не сработать в некоторых браузерах, если не настроить автоматическую загрузку и показ изображений.'
	},	read:	{sub:'заг.файл',t:'Прочитать локальный файл на новый слой. \r\n\
Может не сработать вообще, особенно при запуске самой рисовалки не с диска. \r\n\
Вместо этого рекомендуется перетаскивать файлы из других программ.'
	},	done:	{sub:'готово',	t:'Завершить и отправить рисунок в сеть.'

	},	'new':	{sub:'новый',	t:'Создать новый слой.'
	},	copy:	{sub:'копия',	t:'Создать копию слоя.'
	},	merge:	{sub:'слить',	t:'Скопировать содержимое слоя вниз.'
	},	'delete':{sub:'удалить',t:'Удалить слой вместе с его историей отмен.'
	},	up:	{sub:'выше',	t:'Поднять слой выше.'
	},	down:	{sub:'ниже',	t:'Опустить слой ниже.'
	},	top:	{sub:'верх',	t:'Поднять слой на самый верх.'
	},	bottom:	{sub:'низ',	t:'Опустить слой в самый низ.'
	},	global:	{sub:'глобал.',	t:'Общая история отмен, вместо отдельной для выбранного слоя. Тот же эффект на "глобальном" слое.'
}}};

//* translation: English *-----------------------------------------------------

else o.lang = 'en'
, select.translated = {
	compose	: ['above', 'under', 'clip', 'mask', 'light', 'exclude', 'eraser']
}, lang = {
	lang: ['language', 'English']
,	bad_data:	'Invalid data format.'
,	bad_id:		'Invalid case.'
,	flood:		'Canvas is empty.'
,	confirm: {
		send:	'Send image to server?'
	,	size:	'Canvas size exceeds limit. Send anyway?'
	,	save:	'Save layers to your browser memory? \r\nOverwrite the copy edited at:'
	,	load:	'Restore layers from your browser memory? \r\nOverwrite the copy edited at:'
	,	reprint:'Resave with line breaks and indents?'
},	copy_to_save:	'Open new text file, copy and paste to it after this line'
,	found_swap:	'Found image at slot 2, swapped slots.'
,	loading:	'Waitind for images to load'
,	no_LS:		'Local Storage (browser memory) not supported.'
,	no_space:	'Saving failed, not enough space.'
,	no_layers:	'Save position has no layer data.'
,	no_files:	'No image files found.'
,	no_form:	'Destination unavailable.'
,	no_change:	'Nothing changed.'
,	no_canvas:	'Your browser does not support HTML5 canvas.'
,	windows: {
		layer:	'Layers'
	,	info:	'Info'
	,	color:	'Color'
	,	tool:	'Tool'
},	layer: {prefix:	'Layer'
	,	bg:	'Background color, global actions'
	,	hint: {
			bg:	'Change bg color: left click to front tool color, right — to back.'
		,	check:	'Layer visibility. Right click to toggle clipping group mode.'
		,	name:	'Layer entry name, change to something meaningful.'
		,	blur:	'Layer blur radius.'
		,	alpha:	'Layer opacity ratio.'
		,	undo:	'Layer undo history.'
}},	tool: {	G:	'Grid step'
	,	B:	'Shadow'
	,	O:	'Opacity'
	,	W:	'Width'
	,	T:	'Separation'
	,	R:	'Blur'
	,	S:	'Saturation'
},	shape:		'Shape'
,	font:		'Font'
,	text_hint:	'Your text here.'
,	filter:		'Filter'
,	compose:	'Composition mode'
,	palette:	'Palette'
,	hex:		'Code'
,	hex_hint:	'Valid formats — #a, #f90, #ff9900, or 0,123,255'
,	hide_hint:	'Click to hide.'
,	show_hint:	'Click to show/hide.'
,	info: [	'{toggleView=\'hotkeys\';Hot keys}: [hotkeys;(mouse over image only)'
	,
	,	'Esc = cancel current unfinished change.'
	,	'C / mouse mid = pick color from image.'
	,
	,	'Select tool shape:'
	,	'Q = line, straight, curve.'
	,	'P = lasso, freehand polygon.'
	,	'R = rectangle.'
//	,	'T = print text.'
	,	'Y = sun rays, gear wheel.'
	,	'M = move, copy.'
	,
	,	'1-10 / mouse wheel	/ (Alt +) W = brush width.'
	,	'Ctrl	+ 1-10 / wheel	/ (Alt +) O = brush opacity.'
	,	'Alt	+ 1-10 / wheel	/ (Alt +) B = brush shadow blur.'
	,	'Shift	+ 1-10 / wheel	/ (Alt +) O = grid step.'
	,
	,	'Shift	+ arrows = select layer.'
	,	'Alt	+ arrows = move layer on the list.'
	,
	,	'Ctrl	+ drag = rotate canvas, Home = {updateViewport;reset}.'
	,	'Alt	+ drag = zoom, Shift + d. = move canvas.'
	,
	,	']F1 = {resetAside;reset} floating panels.-'
	,	'Autosave every minute, last saved'
],	info_no_save:	'not yet'
,	info_no_time:	'no yet'
,	info_time:	'Time elapsed'
,	info_pad:	'sketch stack'
,	info_drop:	'You can drag files from disk and drop here.'
,	size:		'Image size'
,	size_hint:	'Number between '
,	b: {	undo:	'Revert last change.'
	,	redo:	'Redo next reverted change.'
	,	fill:	'Fill layer with main color.'
	,	erase:	'Fill layer with transparency, or background with back color.'
	,	invert:	'Invert image colors.'
	,	flip_h:	{sub:'flip hor.',t:'Flip image horizontally.'
	},	flip_v:	{sub:'flip ver.',t:'Flip image vertically.'
	},	pencil:	'Set tool to sharp black.'
	,	chalk:	'Set tool to large white.'
	,	eraser:	'Set tool mode to eraser / back to normal.'
	,	swap:	'Swap your tools.'
	,	reset:	'Reset both tools.'
	,	line:	'Draw straight line with 1 drag.'
	,	curve:	'Smooth path corners / draw single curve with 2 drags.'
	,	area:	'Fill geometric shapes. \r\nNo outline + no fill = erase area.'
	,	outline:'Draw outline of geometric shapes. \r\nNo outline + no fill = erase area.'
	,	copy:	'Keep old copy.'
	,	rect:	'Move rectangle.'
	,	cursor:	'Brush preview on cursor.'
	,	rough:	'Skip draw cleanup while drawing to use less CPU.'
	,	fps:	'Limit FPS when drawing to use less CPU.'
	,	png:	'Save image as PNG file.'
	,	jpeg:	'Save image as JPEG file.'
	,	json:	'Save layers as JSON file.'
	,	save:	'Save layers copy to your browser memory, 2 slots in a queue.'
	,	load:	'Load layers copy from your browser memory, 2 slots in a queue. \r\n\
May not work in some browsers until set to load and show new images automatically.'
	,	loadd:	'Load image copy from your browser memory to a new layer, 2 slots in a queue. \r\n\
May not work in some browsers until set to load and show new images automatically.'
	,	read:	'Load image from your local file to a new layer. \r\n\
May not work at all, especially if sketcher itself is not started from disk. \r\n\
Instead, it is recommended to drag and drop files from another program.'
	,	done:	'Finish and send image to server.'

	,	'new':	'Add a new layer.'
	,	copy:	'Add a copy of the current layer.'
	,	merge:	'Copy layer contents to the lower layer.'
	,	'delete':'Delete layer. Its undo history will be lost.'
	,	up:	'Move layer one step up.'
	,	down:	'Move layer one step down.'
	,	top:	'Move layer to the top.'
	,	bottom:	'Move layer to the bottom.'
	,	global:	'Global history, instead of layer\'s own only. Same effect on the "global" layer.'
}};
	lang.tool.A = lang.tool.O;
	return !o.send;
} //* <- END external config

//* Embed CSS and container *--------------------------------------------------

document.write(replaceAll(replaceAdd('\n<style id="|-style">\
#| #|-color-text {width: 78px;}\
#| .|-button {background-color: #ddd;}\
#| .|-button-active {background-color: #ace;}\
#| .|-button-active:hover {background-color: #bef;}\
#| .|-button-disabled {color: #888; cursor: default;}\
#| .|-button-key, #| .|-button-subtitle {vertical-align: top; height: 10px; font-size: 9px; margin: 0; padding: 0;}\
#| .|-button-key, #|-debug {text-align: left;}\
#| .|-button-subtitle {line-height: 6px; margin: 0 -3px;}\
#| .|-button:hover {background-color: #eee;}\
#| .|-buttons button {font-size: 15px;}\
#| .|-buttons {line-height: 0;}'+/* <- fix for Chrome */'\
#| .|-paletdark, #| .|-palettine {border: 2px solid transparent; height: 15px; width: 15px; cursor: pointer;}\
#| .|-paletdark:hover {border-color: #fff;}\
#| .|-palettine:hover {border-color: #000;}\
#| .|-rf {display: block; float: right;}\
#| .|-ri {display: inline-block; text-align: right;}\
#| .|-selects .|-rf, #| aside select {width: 86px; margin: 0;}\
#| .|-selects, #|-layers {min-width: 304px; white-space: nowrap;}\
#| .|-slider {display: block; width: 156px; height: 1px; font-size: small; line-height: normal;}\
#| a {color: #888;}\
#| a:hover {color: #000;}\
#| abbr {border-bottom: 1px dotted #111;}\
#| aside button {border: 1px solid #000; width: 38px; height: 38px; margin: 2px; padding: 2px; line-height: 7px; text-align: center; cursor: pointer;}\
#| aside header > a {display: block; float: right; text-decoration: none; color: #eee; margin: 0;}\
#| aside header {display: block; cursor: move; padding: 2px 2px 4px 2px; margin-bottom: 2px; background-color: #ace; overflow: hidden;}\
#| aside header:hover {background-color: #5ea6ed;}\
#| aside i {font-style: normal;}\
#| aside input[type="range"] {width: 156px; height: 16px; margin: 0; padding: 0;}\
#| aside input[type="text"] {margin: 2px; width: 48px;}\
#| aside p {line-height: 22px; margin: 0.5em;}\
#| aside p, #|-info hr, #|-layers hr, #|-colors table {font-size: small;}\
#| aside {position: absolute; z-index: 100; left: 0; top: 0; text-align: left; padding: 2px; border: 2px solid #888; background-color: rgba(234,234,234,0.90);}\
#| aside, #|-load canvas {box-shadow: 3px 3px rgba(0,0,0, 0.1);}\
#| aside:hover {background-color: #eaeaea;}\
#| canvas {border: '+CANVAS_BORDER+'px solid #ddd; margin: 0; vertical-align: bottom; cursor:\
	url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAGElEQVR42mNgYGCYUFdXN4EBRPz//38CADX3CDIkWWD7AAAAAElFTkSuQmCC\'),\
	auto;}\
#| canvas:hover, #| canvas:hover + #|-color-wheel-inner, #|-color-wheel-inner div:hover {border-color: #aaa;}\
#| hr {border: 1px solid #aaa; border-top: none;}\
#| {text-align: center; padding: 12px; background-color: #f8f8f8;}\
#|, #| button, #| input, #| select, #|-lcd {color: #111; font-family: "Arial"; font-size: 19px; line-height: normal;}\
#|-color-wheel * {position: absolute;}\
#|-color-wheel {position: relative; margin: 0 auto; padding: 0;}\
#|-color-wheel, #|-color-wheel-inner, #|-color-wheel-inner div {border: '+CANVAS_BORDER+'px solid #ddd; overflow: hidden; background-color: white;}'
+(noBorderRadius?'':'\
#|-color-wheel-inner div {cursor: pointer; border-radius: 25%;}\
#|-color-wheel-inner, #|-color-wheel-mark, #|-color-wheel-round {border-radius: 50%;}'
)+'\
#|-colors .|-text {padding: 0 4px;}\
#|-colors table {margin: 2px 0 0 0; border-collapse: collapse;}\
#|-colors td {margin: 0; padding: 0; height: 16px;}\
#|-debug td {width: 234px;}\
#|-filters {height: 66px;}\
#|-layers .|-slide p:first-child {margin-top: 0;}\
#|-layers .|-slide p:last-child {margin-bottom: 0;}\
#|-layers .|-slide {max-height: 314px; overflow-y: auto;}\
#|-layers > p .|-rf {width: 64px; height: 18px; margin-right: -2px; font-size: small; font-family: monospace;}'+/* <- bg color box */'\
#|-layers p > i > i {display: table-cell; padding: 0 4px; white-space: nowrap; overflow: hidden;}\
#|-layers p > i > i:nth-child(1) {width: 14px;}\
#|-layers p > i > i:nth-child(3) {width: 21px; text-align: right;}\
#|-layers p > i > i:nth-child(4) {width: 33px; text-align: right;}\
#|-layers p > i > i:nth-child(4):after {content: "%";}\
#|-layers p > i > i:nth-child(5) {width: 1px;}\
#|-layers p > i {display: table; width: 100%;}\
#|-layers p i input[type="checkbox"] {width: 13px; height: 13px; margin: 0; padding: 0; vertical-align: middle;}\
#|-layers p i input[type="text"] {width: 100%; font-size: small; height: 16px; padding: 1px; border: 1px solid #aaa; margin: 0 -3px;}\
#|-layers p {border: 2px solid #ddd;}\
#|-layers p.|-button input[type="text"] {background-color: #f5f5f5; border-color: #ddd;}\
#|-layers {max-width: 304px;}\
#|-lcd {text-align: center; vertical-align: middle; font-size: 40px; color: #aaa; background-color: #f5f5f5;}\
#|-load img {position: absolute; top: '+CANVAS_BORDER+'px; left: '+CANVAS_BORDER+'px; margin: 0;}\
#|-load, #|-load canvas {position: relative; display: inline-block; z-index: 99;}\
#|-textStyle textarea {width: 80px; height: 68px;}\
#|-warn {background-color: #bbb; display: inline-block;}\
#|-warn.|-red {background-color: #f77;}'
+abc.map(function(i) {return '.|-'+i+' .|-'+i;}).join(', ')+' {display: none;}\
</style>', '}', '\n'), '|', NS)+'\n<div id="'+NS+'">Loading '+NS+'...</div>\n');

//* Generic funcs *------------------------------------------------------------

function repeat(t,n) {return new Array(n+1).join(t);}
function replaceAll(t,s,j) {return t.split(s).join(j);}
function replaceAdd(t,s,a) {return replaceAll(t,s,s+a);}

//* http://www.webtoolkit.info/javascript-trim.html
function ltrim(str, chars) {return str.replace(new RegExp('^['+(chars || '\\s')+']+', 'g'), '');}
function rtrim(str, chars) {return str.replace(new RegExp('['+(chars || '\\s')+']+$', 'g'), '');}
function trim(str, chars) {return ltrim(rtrim(str, chars), chars);}

function cre(e,p,b) {
	e = document.createElement(e);
	if (b) p.insertBefore(e, b); else
	if (p) p.appendChild(e);
	return e;
}
function orz(n) {return parseInt(n||0)||0;}
function id(i) {return document.getElementById(NS+(i?'-'+i:''));}
function reId(e) {return e.id.slice(NS.length+1);}
function setId(e,id) {return e.id = NS+'-'+id, e;}
function setClass(e,c) {return e.className = c?replaceAdd(' '+c,' ',NS+'-').trim():'', e;}
function setEvent(e,onWhat,func) {return e.setAttribute(onWhat, NS+'.'+func), e;}
function setContent(e,c) {
var	a = ['class','id','onChange','onClick','onContextMenu'];
	for (i in a) c = replaceAdd(c, ' '+a[i]+'="', NS+(a[i][0]=='o'?'.':'-'));
	return e.innerHTML = c, e;
}
function setRemove(e,o) {return e.setAttribute(o?o:'onclick', 'this.parentNode.removeChild(this); return false'), e;}
function clearContent(e) {while (e.childNodes.length) e.removeChild(e.lastChild); return e;}	//* <- works without a blink, unlike e.innerHTML = '';
function toggleView(e) {if (!e.tagName) e = id(e); return e.style.display = e.style.display?'':'none';}
function dist(x,y) {return Math.sqrt(x*x + y*y)};
function cut_period(x,y,z) {if (!y) y = -Math.PI; if (!z) z = Math.PI; return (x < y ? x-y+z : (x > z ? x+y-z : x));}
function ang_btw(x,y) {return cut_period(y-x);}
function o0(line,delim) {var a=line.split(delim||','),i,o={}; for(i in a) o[a[i]]=0; return o;}
function showProps(o,r,z /*incl.zero*/) {var i,t=''; for(i in o)if(z||o[i])t+='\n'+i+'='+o[i]; if(r)return t; alert(t); return o;}

function eventStop(e) {
	if (e && e.eventPhase?e:e = window.event) {
		if (e.stopPropagation) e.stopPropagation();
		if (e.cancelBubble != null) e.cancelBubble = true;
	}
	return e;
}

//* To get started *-----------------------------------------------------------

document.addEventListener('DOMContentLoaded', init, false);

}; //* <- END global wrapper
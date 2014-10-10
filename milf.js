//* Global wrapper *-----------------------------------------------------------

var milf = new function () {
var	NS = 'milf'	//* <- namespace prefix, change here and above; BTW, tabs align to 8 spaces

//* Configuration *------------------------------------------------------------

,	INFO_VERSION = 'v1.12'
,	INFO_DATE = '2014-07-16 — 2014-10-11'
,	INFO_ABBR = 'Multi-Layer Fork of DFC'
,	A0 = 'transparent', IJ = 'image/jpeg', SO = 'source-over', DO = 'destination-out'
,	CR = 'CanvasRecover', CT = 'Time', CL = 'Layers'
,	LS = this.LS = window.localStorage || localStorage
,	DRAW_PIXEL_OFFSET = -0.5
,	DRAW_HELPER = {lineWidth: 1, shadowBlur: 0, shadowColor: A0, strokeStyle: 'rgba(123,123,123,0.5)', globalCompositeOperation: SO}

,	mode = {debug:	false
	,	shape:	false	//* <- straight line	/ fill area	/ copy
	,	step:	false	//* <- curve		/ outline	/ part
	,	lowQ:	false
	,	erase:	false
	,	brushView:	false
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
	,	imgLimits: {width:[64,640], height:[64,800]}
	,	lineCaps: {lineCap:0, lineJoin:0}
	,	shapeFlags: [1,10,2,2,2,4]
	,	clipBorder: ['', '#123', '#5ea']//, '#5ae', '#ff0', '#f40']
	,	options: {
			shape	: ['line', 'poly', 'rectangle', 'circle', 'ellipse', 'pan']
		,	lineCap	: ['round', 'butt', 'square']
		,	lineJoin: ['round', 'bevel', 'miter']
		,	filter	: ['scale', 'integral']
		,	compose	: [SO, 'destination-over', 'source-atop', 'destination-atop', 'lighter', 'xor', DO]
		,	palette	: ['history', 'auto', 'legacy', 'Touhou', 'gradient']
	}}

,	PALETTE_COL_COUNT = 16	//* <- used if no '\n' found, for example - unformatted history
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

	], '\rg']

//* Set up (don't change) *----------------------------------------------------

,	o12 = /^Opera.* Version\D*12\.\d+$/i.test(navigator.userAgent)	//* <- broken forever, sadly
,	abc = 'abc'.split('')
,	regLastNum = /^.*\D(\d+)$/
,	regHex = /^#*[0-9a-f]{6}$/i
,	regHex3 = /^#*([0-9a-f])([0-9a-f])([0-9a-f])$/i
,	reg255 = /^([0-9]{1,3}),\s*([0-9]{1,3}),\s*([0-9]{1,3})$/
,	reg255split = /,\s*/
,	regTipBrackets = /[ ]*\([^)]+\)$/
,	regFunc = /\{[^.]+\.([^(]+)\(/
,	regLimit = /^(\d+)\D+(\d+)$/

,	self = this, outside = this.o = {}, lang, container
,	fps = 0, ticks = 0, timer = 0
,	interval = {fps:0, timer:0, save:0}, text = {debug:0, timer:0}
,	ctx = {}, cnv = {view:0, draw:0, lower:0, current:0, upper:0, filter:0, temp:0}
,	used = {}, cue = {upd:{}}, count = {layers:0, strokes:0, erases:0, undo:0}

,	draw = {m:{}, o:{}, cur:{}, prev:{}
	,	refresh:0, time: [0, 0]
	,	line: {started:0, back:0, preview:0}
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
						if (t.pos < d) {
							t.last = ++t.pos;
							for (i = t.last+1; i < d; i++) delete t.data[i];
						} else for (i = 0; i < d; i++) t.data[i] = t.data[i+1];
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
var	x = {show:1, name: lang.layer.prefix+'_'+(++count.layers), alpha: RANGE.A.max, pos:0, last:0, data:[]};
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
var	c = ctx[mode.brushView?'draw':'view'], d = tool.grid;
	if (d > 1) {
	var	m, n = Math.floor(tool.width / d), o = draw.o, v = (n < 1 ? (n = 1) : n)*d, w = v+DRAW_PIXEL_OFFSET, v = v-DRAW_PIXEL_OFFSET;
		for (i in DRAW_HELPER) c[i] = DRAW_HELPER[i];
		c.beginPath();
		for (i = -n; i <= n; i++) {
			m = i*d+DRAW_PIXEL_OFFSET;
			c.moveTo(o.x+m, o.y-v);
			c.lineTo(o.x+m, o.y+w);
			c.moveTo(o.x-v, o.y+m);
			c.lineTo(o.x+w, o.y+m);
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
	c.arc(draw.cur.x, draw.cur.y, tool.width/2, 0, 7/*Math.PI*2*/, false);
	mode.brushView ? c.fill() : c.stroke();
	c.globalCompositeOperation = SO;
}

function drawStart(event) {
	draw.target = event.target;
	if (isMouseIn() <= 0) return false;

	cnv.view.focus();
	event.preventDefault();
	event.stopPropagation();
	event.cancelBubble = true;

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
var	y = draw.history, i = y.layer, sf = select.shapeFlags[select.shape.value];
	if (!(i || (sf & 4)) || !y.layers[i].show) return false;

	if (draw.step) {
		if (mode.step && ((mode.shape && (sf & 1)) || (sf & 4))) {
			for (i in draw.o) draw.prev[i] = draw.cur[i];
			return draw.step.done = 1;
		} else draw.step = 0;
	}
//	if (event.shiftKey) mode.click = 1;	//* <- draw line/form chains, badly, forget for now

	if ((draw.btn = event.which) != 1 && draw.btn != 3) pickColor();
	else {
		draw.active = 1, y = {draw:0, temp:0};
		if (!draw.time[0]) draw.time[0] = draw.time[1] = +new Date;
		if (!interval.timer) {
			interval.timer = setInterval(timeElapsed, 1000);
			interval.save = setInterval(autoSave, 60000);
		}
	var	i = (event.which == 1)?1:0, j, t = tools[1-i]
	,	b = ((sf & 4) ? 0 : t.blur)
	,	pf = ((sf & 8) && (mode.shape || !mode.step))
	,	fig = ((sf & 2) && (mode.shape || pf));
		draw.clip = t.clip;
		for (i in (t = mode.erase ? DRAW_HELPER : {
			lineWidth: (((sf & 4) || (pf && !mode.step))?1:t.width)
		,	fillStyle: (fig ? 'rgba('+(mode.step?tools[i]:t).color+', '+t.opacity+')' : A0)
		,	strokeStyle: (fig && !(mode.step || pf) ? A0 : 'rgba('+t.color+', '+((sf & 4)?(draw.step?0.33:0.66):t.opacity)+')')
		,	shadowColor: (b ? 'rgb('+t.color+')' : A0)
		,	shadowBlur: b
		})) for (j in y) ctx[j][i] = t[i];
		updatePosition(event);
		for (i in draw.o) draw.prev[i] = draw.cur[i];
		for (i in draw.line) draw.line[i] = false;
		for (i in select.lineCaps) {
			t = select.options[i][select[i].value];
			for (j in y) ctx[j][i] = t;
		}
		ctx.draw.beginPath();
		ctx.draw.moveTo(draw.cur.x, draw.cur.y);
	}
}

function drawMove(event) {
	if (mode.click == 1 && !event.shiftKey) return mode.click = 0, drawEnd(event);

	updatePosition(event);
	if (draw.turn) return updateViewport(draw.turn.pan?1:draw.turn.delta = getCursorRad() - draw.turn.origin);

var	redraw = true, s = select.shape.value, sf = select.shapeFlags[s], i
,	newLine = (draw.active && !((mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8)));

	if (mode.click) mode.click = 1;
	if (newLine) {
		if (draw.line.preview) {
			drawShape(ctx.draw, s);
		} else
		if (draw.line.back = mode.step) {
			if (o12) ctx.draw.shadowColor = A0, ctx.draw.shadowBlur = 0;	//* <- shadow, once used with CurveTo + stroke(), totally breaks for given cnv.view in Opera 12
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
			if (!(sf & 4)) ctx.draw.globalCompositeOperation = draw.clip;
			if ((mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8)) {
				draw.line.preview = true;
				if (mode.erase && (sf & 2)) {
					ctx.draw.beginPath();
					drawShape(ctx.draw, s, 1), ++redraw;		//* <- erase shape area
				} else {
					ctx.temp.clearRect(0, 0, cnv.view.width, cnv.view.height);
					ctx.temp.beginPath();
					drawShape(ctx.temp, (mode.step && (sf & 4) && (!draw.step || !draw.step.done))?2:s);
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
	if (!event || draw.turn) return draw.active = draw.step = draw.btn = draw.turn = 0;
	if (mode.click == 1 && event.shiftKey) return drawMove(event);
	if (draw.active) {
		if (draw.target != cnv.view) return;
		draw.target = 0;
	var	s = select.shape.value, sf = select.shapeFlags[s], m = ((mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8));
	//* 2pt line, base for 4pt curve:
		if (!draw.step && mode.step && ((mode.shape && (sf & 1)) || (sf & 4))) {
			draw.step = {
				prev:{x:draw.prev.x, y:draw.prev.y}
			,	cur:{x:draw.cur.x, y:draw.cur.y}
			};
			return;
		}
		for (i in DRAW_HELPER) ctx.temp[i] = DRAW_HELPER[i];
		draw.time[1] = +new Date;
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
			if (i = (sf & 4) ? 0 : ((ctx.draw.globalCompositeOperation = draw.clip) == DO)) ++count.erases;
			if (sf & 8) {
				ctx.draw.closePath();
				if (mode.shape || !mode.step) ctx.draw.fill();
				used.poly = 'Poly';
			} else
			if (m && draw.line.preview) {
				drawShape(ctx.draw, s);
				if (!(sf & 4)) used.shape = 'Shape';
			} else
			if (m || draw.line.back || !draw.line.started) {//* <- draw 1 pixel on short click, regardless of mode or browser
				ctx.draw.lineTo(draw.cur.x, draw.cur.y + (draw.cur.y == draw.prev.y ? 0.01 : 0));
			}
			if (sf & 4) used.move = 'Move';
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
	updateDebugScreen();
}

function drawShape(c, i, clear) {
var	s = draw.step, r = draw.cur, v = draw.prev;
	switch (parseInt(i)) {
	//* rect
		case 2:	if (s) {
			//* show pan source area
				c.strokeRect(s.prev.x, s.prev.y, s.cur.x-s.prev.x, s.cur.y-s.prev.y);
			} else if (clear) ctx.draw.clearRect(v.x, v.y, r.x-v.x, r.y-v.y);
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
		,	radius = Math.sqrt(Math.pow(r.x-xCenter, 2) + Math.pow(r.y-yCenter, 2));
			c.moveTo(xCenter+radius, yCenter);
			c.arc(xCenter, yCenter, radius, 0, 7, false);
			if (clear) {
				ctx.draw.save();
				ctx.draw.clip();
				ctx.draw.clearRect(0, 0, cnv.view.width, cnv.view.height);
				ctx.draw.restore();
			} else if (c.fillStyle != A0) c.fill();
			c.moveTo(r.x, r.y);
			break;
	//* ellipse
		case 4:
		var	xCenter = (v.x+r.x)/2
		,	yCenter = (v.y+r.y)/2
		,	xRadius = Math.abs(r.x-xCenter)
		,	yRadius = Math.abs(r.y-yCenter), qx = 1, qy = 1;
			if (xRadius > 0 && yRadius > 0) {
				c.save();
				if (xRadius > yRadius) c.scale(1, qy = yRadius/xRadius); else
				if (xRadius < yRadius) c.scale(qx = xRadius/yRadius, 1);
				c.moveTo((xCenter+xRadius)/qx, yCenter/qy);
				c.arc(xCenter/qx, yCenter/qy, Math.max(xRadius, yRadius), 0, 7, false);
				c.restore();
				if (clear) {
					ctx.draw.save();
					ctx.draw.clip();
					ctx.draw.clearRect(0, 0, cnv.view.width, cnv.view.height);
					ctx.draw.restore();
				} else if (c.fillStyle != A0) c.fill();
			}
			c.moveTo(r.x, r.y);
			break;
	//* pan
		case 5:	if (v.x != r.x
			|| (v.y != r.y)) moveScreen(r.x-v.x, r.y-v.y, c != ctx.temp);
			break;
	//* line
		default:if (s) {
			var	d = r, old = propSwap(ctx.temp, DRAW_HELPER);
				ctx.temp.beginPath();
				if (s.prev.x != v.x || s.prev.y != v.y) {
					ctx.temp.moveTo(d.x, d.y), d = v;
					ctx.temp.lineTo(d.x, d.y);
				}
				ctx.temp.moveTo(s.cur.x, s.cur.y);
				ctx.temp.lineTo(s.prev.x, s.prev.y);
				ctx.temp.stroke();
				propSwap(ctx.temp, old);
				ctx.temp.beginPath();
		//* curve
				c.moveTo(s.prev.x, s.prev.y);
				c.bezierCurveTo(s.cur.x, s.cur.y, d.x, d.y, r.x, r.y);
			} else {
		//* straight
				c.moveTo(v.x, v.y);
				c.lineTo(r.x, r.y);
			}
	}
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
		ctx.draw.fillStyle = 'rgb(' + tools[i].color + ')';
		ctx.draw.fillRect(0, 0, cnv.view.width, cnv.view.height);
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

function pickColor(keep, c, event) {
	if (c) {
//* gradient palette:
	var	d = c.ctx.getImageData(0, 0, c.width, c.height), o = getOffsetXY(c);
		c = (event.pageX - o.x
		+   (event.pageY - o.y)*c.width)*4;
	} else {
		c = (Math.floor(draw.o.x) + Math.floor(draw.o.y)*cnv.view.width)*4;
//* current layer:
		d = draw.history.cur();
//* whole image:
		if (!d) draw.view(1), d = ctx.view.getImageData(0, 0, cnv.view.width, cnv.view.height);
	}
	d = d.data, c = (d[c]*65536 + d[c+1]*256 + d[c+2]).toString(16);
	while (c.length < 6) c = '0'+c; c = '#'+c;
	return keep ? c : updateColor(c, event);
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

function rgb2hex(v) {
	if (!reg255.test(v)) return false;
	v = v.split(reg255split);
var	h = '#', i, j;
	for (i in v) h += ((j = parseInt(v[i]).toString(16)).length == 1) ? '0'+j : j;
	return h;
}

function isRgbDark(v) {
var	a = v.split(reg255split), v = 0, i;
	for (i in a) v += parseInt(a[i]);
	return v < 380;
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

	if (t == tool) c.value = v, c.style.backgroundColor = '';

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
var	pt = id('colors'), c = select.palette.value, p = palette[c];
	if (LS) LS.lastPalette = c;
	clearContent(pt);

	if (p[0] == '\r') {
		c = p[1];
		if (c == 'g') {
			c = document.createElement('canvas'), c.ctx = c.getContext('2d'), c.width = 300, c.height = 133;
		var	hues = [[255,  0,  0]
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

			(c.updateSat = function (sat) {
			var	x = c.width, y = c.height, y2 = Math.floor(y/2), h, i, j, k = Math.ceil(c.width/l)
			,	d = c.ctx.createImageData(x, y);
				while (x--) {
					h = linearBlend(hues[y = Math.floor(x/k)], hues[(y+1)%l], x%k, k);
					if (!isNaN(sat)) h = linearBlend(bw[1], h, sat, RANGE.S.max);
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
				c.ctx.putImageData(d, 0, 0);
			})();

			c.setAttribute('onscroll', f);
			c.setAttribute('oncontextmenu', f);
			c.addEventListener('mousedown', function (event) {pickColor(0, c, event || window.event);}, false);
			setId(c, 'gradient');
			setContent(pt, getSlider('S')+'<br>');
			setSlider('S');
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
var	a = id('warn'), b = a.firstElementChild, c = [], i;
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
	container.style.minWidth = (v = cnv.view.width)+'px';
	if (a = outside.restyle) {
		v += 24;
		if (!(c = id(i = 'restyle'))) setId(container.parentNode.insertBefore(c = document.createElement('style'), container), i);
		if ((b = outside.restmin) && ((b = eval(b).offsetWidth) > v)) v = b;
		c.innerHTML = a+'{max-width:'+v+'px;}';
	}
}

function updateEraser() {
var	a = select.affect, i = a.value, a = (i == a.length-1), b = 'button', e = id(b+'E');
	tool.clip = select.options.affect[i];
	if (e) setClass(e, b+(a?'-active':''));
	return a;
}

function toggleMode(i, keep) {
	if (i >= 0 && i < modes.length) {
	var	n = modes[i], v = mode[n], e;
		if (!keep) v = mode[n] = !v;
		if (e = id('check'+modeL[i])) {
			setClass(e, 'button'+(v?'-active':''));
			if (e.parentNode.id == NS+'-warn') updateShape();
		}
		if (n == 'debug') {
			text.debug.textContent = '';
			interval.fps ? clearInterval(interval.fps) : (interval.fps = setInterval(fpsCount, 1000));
		}
	} else alert(lang.bad_id);
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

function toolSwap(t) {
var	i, j, k = select.affect;

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
		toolSwap(-1);
		tool.width = t;
	} else

//* restore front set to one of defaults + line shape
	if (t > 0) {
		for (i in (t = TOOLS_REF[t-1])) tool[i] = t[i];
		updateShape(0);
	} else

//* drop switches, set shape
	if (t) {
		if (mode.shape) toggleMode(1);
		if (mode.step) toggleMode(2);
		return updateShape(-t-1);

//* toggle eraser mode
	} else {
		return k.value = (k.value > 0?0:k.length-1), updateEraser();
	}
	i = select.options.affect.indexOf(tool.clip), k.value = (i < 0?0:i);
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
	return y ? t[0]+'-'+t[1]+'-'+t[2]+' '+t[3]+':'+t[4]+':'+t[5] : t.join(':');
}

function getSendMeta(sz) {
var	a = ['clip', 'mask', 'lighter', 'xor']
,	b = ['resize', 'integral']
,	d = draw.time, h = draw.history.layers, i, j = [], k, l, m = [], n = [], u = [], t = outside.t0;
	for (i in d) u[i] = parseInt(d[i]) || (i > 0?+new Date:t);
	for (i in count) if ((k = count[i]) > 1 || (i != 'layers' && k > 0)) j.push(k+' '+(k > 1?i:i.replace(/s+$/i, '')));
	for (i in used) j.push(used[i]);
	for (i in h) {
		l = h[i];
		if (l.clip > 0 && m.indexOf(k = a[l.clip    - 2]) < 0) m.push(k);
		if (l.blur > 0 && m.indexOf(k = b[l.filter || 0]) < 0) n.push(k);
	}
	if (m.length) j.push('Composition: '+m.join(', '));
	if (n.length) j.push('Filter: '+n.join(', '));
//	return Math.floor(t/1000)+','+u.join('-')+','+NS+' '+INFO_VERSION + (j.length?' (used '+j.join(', ')+')':'');
	return 't0: '	+Math.floor(t/1000)
	+'\ntime: '	+u.join('-')
	+'\napp: '	+NS+' '+INFO_VERSION
	+(j.length
	?'\nused: '	+j.join(', '):'')
	+'\nlength: '	+(sz?sz:
		'png = '	+ cnv.view.toDataURL().length
		+', jpg = '	+ cnv.view.toDataURL(IJ).length
	);
}

function getSaveLayers(k) {
var	a = draw.history.layers
,	e = ['pos', 'last', 'reversable', 'filtered', 'data'], d, f, i, j = '-'
,	b = {time: draw.time.join(j)+(used.read?j+used.read:'')};
	if (k) b.meta = getSendMeta();
	b.layers = [];
	for (i in a) {
		d = {}, j = a[i];
		for (k in j) if (e.indexOf(k) < 0) d[k] = j[k] || 0;
		if (j[k = 'data']) {
			if (f = j[k][j.pos]) {
				ctx.temp.putImageData(f, 0, 0);
				d[k] = cnv.temp.toDataURL();
			} else continue;
		}
		b.layers.push(d);
	}
	return b;	//* <- object, needs JSON.stringify(b)
}

function readSavedLayers(b) {
	if (!b.time || !b.layers) return false;
	for (i in count) count[i] = 0;
var	a = id('saveTime'), d = draw.history, j = '-', i = b.time.split(j);
	if (i.length > 2) used.read = i.slice(2).join(j);
	draw.time = i.slice(0,2);
	a.title = new Date(i = +i[1]);
	a.textContent = unixDateToHMS(i,0,1).split(' ',2)[1];
	a = b.layers, i = j = a[0].max = a.length, d.layers = [a[d.layer = 0]];
	while (--i) d = a[i], d.z = i, readPic(d);
	return j;
}

function sendPic(dest, auto) {
var	a = auto || false, b, c, d, e, f, i, j, k, l, t;
	draw.view(1);
	switch (dest) {
	case 0:
	case 1:	window.open(c = cnv.view.toDataURL(dest?IJ:''), '_blank');
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
		if (a || confirm(lang.confirm_save)) {
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
			t = t.split('-'), c = unixDateToHMS(i = +t[1],0,1);
			if (t.length > 2) used.read = t.slice(2).join('-');
			if (draw.time[0] < parseInt(t[0])) draw.time[0] = t[0];
			if (draw.time[1] > parseInt(t[1])) {
				draw.time[1] = t[1];
				a = id('saveTime');
				a.title = new Date(i);
				a.textContent = c.split(' ',2)[1];
			}
			readPic({name:c, data:d});
			used.LS = 'Local Storage';
		} else
//* load project
		if (!LS[i] || (b = JSON.parse(LS[i])).time != t) alert(lang.no_layers); else
		if (confirm(lang.confirm_load)) used = {LS:'Local Storage'}, readSavedLayers(b);
		break;
	case 5:
	case 6:
		if (a || ((outside.read || (outside.read = id('read'))) && (a = outside.read.value))) {
	//		draw.time = [0, 0];
			if (dest == 5) a = readPic(a);
			else {
				try {
					readSavedLayers(JSON.parse(a.data)), a = a.name;
				} catch(e) {
					alert(lang.bad_data), a = '';
				}
			}
			if (a.length) used.read = 'Read File: '+a;
		}
		break;
//* save project text as file
	case 7:
		b = JSON.stringify(getSaveLayers(1), null, '\t');
		try {
		var	bb = new BlobBuilder();
			bb.append(b);
		var	blob = bb.getBlob('text/plain');
			saveAs(blob, draw.time.join('-')+'.json');
		} catch(e) {
			try {
				window.open('data:text/plain,'+encodeURIComponent(b), '_blank');
			} catch(d) {
				alert(lang.copy_to_save+':\n\n'+b);
			}
		}
		break;
//* send
	default:
		if (dest) alert(lang.bad_id); else
		if (!outside.send) alert(lang.no_form); else
		if (fillCheck()) alert(lang.flood); else
		if (confirm(lang.confirm_send)) {
			if (!outside.send.tagName) {
				setId(e = document.createElement('form'), 'send');
				e.setAttribute('method', (outside.send.length && outside.send.toLowerCase() == 'get')?'get':'post');
				container.appendChild(outside.send = e);
			}
		var	pngData = sendPic(2, 1), jpgData, a = {txt:0,pic:0};
			for (i in a) if (!(a[i] = id(i))) {
				setId(e = a[i] = document.createElement('input'), e.name = i);
				e.type = 'hidden';
				outside.send.appendChild(e);
			}
			e = pngData.length;
			d = (((i = outside.jp || outside.jpg_pref)
				&& (e > i)
				&& (((c = cnv.view.width * cnv.view.height
				) <= (d = select.imgRes.width * select.imgRes.height
				))
				|| (e > (i *= c/d)))
				&& (e > (t = (jpgData = cnv.view.toDataURL(IJ)).length))
			) ? jpgData : pngData);
			a.pic.value = d;
			a.txt.value = getSendMeta(d.length);
			if (mode.debug) alert('png limit = '+i+'\npng = '+e+'\njpg = '+t);
			outside.send.submit();
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
		delete s.data;
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
		historyAct(s.z ? draw.time[1] : null);
		cue.autoSave = 0;
		if (d = e.parentNode) d.removeChild(e);
	}
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
	if (browserHotKeyPrevent(event)) {
		function c(s) {return s.charCodeAt(0);}
	var	n = event.keyCode - c('0');
		if ((n?n:n=10) > 0 && n < 11) {
		var	k = [event.shiftKey, event.altKey, event.ctrlKey, 1];
			for (i in k) if (k[i]) return toolTweak(k = BOWL[i], RANGE[k].step < 1 ? n/10 : (n>5 ? (n-5)*10 : n));
		} else
		if (event.altKey)
		switch (event.keyCode) {
			case c('L'):	newLayer();	break;
			case c('C'):	newLayer(1);	break;
			case c('M'):	newLayer(-1);	break;
			case c('E'):	moveLayer('del');break;

		case 38:case c('U'):	moveLayer(0);	break;
		case 40:case c('I'):	moveLayer(-1);	break;
		case 37:case c('T'):	moveLayer();	break;
		case 39:case c('Y'):	moveLayer(1);	break;

			case c('A'):	toolSwap(3);	break;
			case c('S'):	toggleMode(5);	break;
		//	case c('G'):	toggleMode(8);	break;

			case c('G'):
			case c('B'):
			case c('O'):
			case c('W'):	toolTweak(String.fromCharCode(event.keyCode), -1);
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
			case c('Z'):	historyAct(-1);	break;
			case c('X'):	historyAct(1);	break;
			case c('C'):	pickColor();	break;
			case c('F'):	fillScreen(0);	break;
			case c('D'):	fillScreen(1);	break;
			case c('I'):	fillScreen(-1);	break;
			case c('H'):	fillScreen(-2);	break;
			case c('V'):	fillScreen(-3);	break;
			case c('S'):	toolSwap();	break;
			case c('E'):	toolSwap(0);	break;
			case c('A'):	toolSwap(1);	break;
			case c('K'):	toolSwap(2);	break;
		//	case c('G'):	toolSwap(3);	break;

			case 8:
if (text.debug.innerHTML.length)	toggleMode(0);	break;	//* 45=Ins, 42=106=Num *, 8=bksp
			case c('L'):	toggleMode(1);	break;
			case c('U'):	toggleMode(2);	break;

			case 112:	resetAside();	break;	//* F1
			case 120:	sendPic(0);	break;	//* F9
		//	case 118:	sendPic(1);	break;
			case 113:	sendPic(2);	break;
			case 114:	sendPic(3);	break;
			case 115:	sendPic(4);	break;
			case 117:	sendPic(5);	break;
			case 118:	sendPic(7);	break;
			case 119:	sendPic();	break;

			case c('Q'):	updateShape(0);	break;
			case c('P'):	updateShape(1);	break;
			case c('R'):	updateShape(2);	break;
			case c('T'):	updateShape(3);	break;
			case c('Y'):	updateShape(4);	break;
			case c('M'):	updateShape(5);	break;

			case c('G'):
			case c('B'):
			case c('O'):
			case c('W'):	toolTweak(String.fromCharCode(event.keyCode), 0); break;

			case 106: case 42:
				for (i = 1, k = ''; i < 3; i++) k += '<br>Save'+i+'.time: '+LS[CR[i].T]
+(LS[CR[i].R]?', pic size: '+LS[CR[i].R].length:'')
+(LS[CR[i].L]?', layers sum: <a href="javascript:'+i+'">'+LS[CR[i].L].length+'</a>':'');
				(a = text.debug).innerHTML = getSendMeta()+'<br>'+replaceAll(
"\n<a href=\"javascript:var s=' ',t='';for(i in |)t+='\\n'+i+' = '+(|[i]+s).split(s,1);alert(t);\">self.props</a>"+
"\n<a href=\"javascript:var t='',o=|.o;for(i in o)t+='\\n'+i+' = '+o[i];alert(t);\">self.outside</a>"+
(outside.read?'':'<br>\nF6=read: <textarea id="|-read" value="/9.png"></textarea>'), '|', NS)+CR+','+CT+','+CL+k;
			var	a = a.getElementsByTagName('a'), i = a.length, m = /void\((\w+)\)/i, n = /\b(\d+)$/;
				while (i--) if ((k = a[i].href) && (k = k.match(n))) a[i].href ='javascript:alert('+NS+'.LS[\''+CR[k[1]].L+'\'])';
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
		draw.a360 = Math.floor(draw.angle*180/Math.PI)%360;
		draw.arad = draw.a360/180*Math.PI;
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
var	t = '</td><td>', r = '</td></tr>	<tr><td>', a, b = 'turn: ', c = draw.step, i;
	if (a = draw.turn) for (i in a) b += i+'='+a[i]+'; ';
	i = isMouseIn();
	text.debug.innerHTML = '<table><tr><td>'
+draw.refresh+t+'1st='+draw.time[0]+t+'last='+draw.time[1]+t+'fps='+fps
+r+'Relative'+t+'x='+draw.o.x+t+'y='+draw.o.y+''+t+i+(i?',rgb='+pickColor(1):'')
+r+'DrawOfst'+t+'x='+draw.cur.x+t+'y='+draw.cur.y+t+'btn='+draw.btn
+r+'Previous'+t+'x='+draw.prev.x+t+'y='+draw.prev.y+t+'chain='+mode.click+(c?''
+r+'StpStart'+t+'x='+c.prev.x+t+'y='+c.prev.y
+r+'Step_End'+t+'x='+c.cur.x+t+'y='+c.cur.y:'')+'</td></tr></table>'+b;
}

function updatePosition(event) {
var	i = select.shapeFlags[select.shape.value], d = tool.grid, o = (
	!  ((i & 2) && mode.shape && !mode.step)
	&& ((i & 4) || ((draw.active?ctx.draw.lineWidth:tool.width) % 2))
	? DRAW_PIXEL_OFFSET : 0);	//* <- maybe not a 100% fix yet

	draw.o.x = (draw.m.x = event.pageX) - draw.container.offsetLeft;
	draw.o.y = (draw.m.y = event.pageY) - draw.container.offsetTop;
	if (draw.pan && !(draw.turn && draw.turn.pan)) for (i in draw.o) draw.o[i] -= draw.pan[i];
	if (!draw.turn && (draw.angle || draw.zoom != 1)) {
	var	r = getCursorRad(2, draw.o.x, draw.o.y);
		if (draw.angle) r.a -= draw.arad;
		if (draw.zoom != 1) r.d /= draw.zoom;
		draw.o.x = Math.cos(r.a)*r.d + cnv.view.width/2;
		draw.o.y = Math.sin(r.a)*r.d + cnv.view.height/2;
		o = 0;
	}
	for (i in draw.o) {
		if (d > 0) draw.o[i] = Math.round(draw.o[i]/d)*d;
		draw.cur[i] = o + draw.o[i];
	}
}

function getCursorRad(r, x, y) {
	if (draw.turn.pan) return {x: draw.o.x, y: draw.o.y};
	x = (isNaN(x) ? draw.cur.x : x) - cnv.view.width/2;
	y = (isNaN(y) ? draw.cur.y : y) - cnv.view.height/2;
	return (r
	? {	a:Math.atan2(y, x)
	,	d:Math.sqrt(x*x+y*y)	//* <- looks stupid, will do for now
	}
	: (draw.turn.zoom
		? Math.sqrt(x*x+y*y)
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
	event.stopPropagation();
	event.preventDefault();

var	d = event.dataTransfer.files, e = d && d.length;
	event.dataTransfer.dropEffect = e?'copy':'move';
	if (!e) dragMove(event);
}

function drop(event) {
	event.stopPropagation();
	event.preventDefault();

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
			d = '<select id="', b = '"></select><br>', j = select.lineCaps;
			c += '<div class="selects"><div class="rf">'+d+'shape" onChange="updateShape(this)'+l+lang.shape+b;
			for (i in j) c += d+i+l+(j[i] || i)+b;
			c += d+'affect" onChange="updateEraser()'+l+lang.compose+b+'</div><div id="'+k+'-sliders">';
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
	cnv.view.setAttribute('onscroll'		, f = 'return false;');
	cnv.view.setAttribute('oncontextmenu'	, f);

	for (name in mode) if (mode[modes[i = modes.length] = name]) toggleMode(i,1);
	for (i in text) text[i] = id(i);

	draw.container = id('load'), b = 'button', i = (a = 'JP').length, h = /^header$/i;
	while (i--) if (e = id(b+a[i])) setEvent(e, 'onmouseover', 'updateSaveFileSize(this)');

	for (i in (a = {L:CL, A:CT})) if (!LS || !LS[a[i]]) setClass(id(b+i), b+'-disabled');

	for (i in (a = ['a', 'input', 'select', 'p', b]))
	for (c in (b = container.getElementsByTagName(a[i])))
	for (e in (d = ['onchange', 'onclick', 'onmouseover']))
	if ((f = b[c][d[e]])
	&& (m = (''+f).match(regFunc))) {
		if (!self[f = m[1]]) self[f] = eval(f);
		if (f == 'toggleView' && !(m = b[c]).title) m.title = lang[h.test(m.parentNode.tagName)?'hide_hint':'show_hint'];
	}

	generatePalette(1, 85, 0);
	a = select.options, c = select.translated || a, f = (LS && LS.lastPalette && palette[LS.lastPalette]) ? LS.lastPalette : 1;
	a.affect = a.compose, c.affect = c.compose;
	for (b in a) if (e = select[b] = id(b))
	for (i in a[b]) (
		e.options[e.options.length] = new Option((c[b]?c:a)[b][i], i)
	).selected = (b == 'palette'?(i == f):!i);

//* Get ready to work *--------------------------------------------------------

	toggleView('hotkeys');
	id('style').innerHTML += style;

	toolSwap(3);
	updatePalette();
	updateViewport();
	resetAside();
}

//* External config *----------------------------------------------------------

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
//*	a) varname; var2=;		//noequal=1, empty=0
//*	b) warname=two=3=last_val;	//samevalue, rightmost
		}
		break;	//* <- no care about the rest
	}
	k = 'y2', i = k.length, j = (o.saveprfx?o.saveprfx:NS)+CR, CR = [];
	while (i) CR[i--] = {R:e = j+k[i], T:e+CT, L:e+CL};
	CT = CR[1].T, CL = CR[1].L;
	o.t0 = o.t0 > 0 ? o.t0+'000' : +new Date;
	if (!o.undo || isNaN(o.undo) || o.undo < 3) o.undo = 123; else o.undo = parseInt(o.undo);
	if (!o.lang) o.lang = document.documentElement.lang || 'en';

//* translation: Russian *-----------------------------------------------------

	if (o.lang == 'ru')
select.lineCaps = {lineCap: 'Концы линий', lineJoin: 'Сгибы линий'}
, select.translated = {
	shape	: ['линия', 'многоугольник', 'прямоугольник', 'круг', 'овал', 'сдвиг']
,	lineCap	: ['круг <->', 'срез |-|', 'квадрат [-]']
,	lineJoin: ['круг -x-', 'срез \\_/', 'угол V']
,	filter	: ['масштаб', 'интеграл']
,	compose	: ['поверх', 'под', 'в пределах', 'предел (маска)', 'свет', 'исключение', 'стёрка']
,	palette	: ['история', 'авто', 'разное', 'Тохо', 'градиент']
}, lang = {
	lang: ['язык', 'Русский']
,	bad_data:	'Неправильный формат данных.'
,	bad_id:		'Ошибка выбора.'
,	flood:		'Полотно пусто.'
,	confirm_send:	'Отправить рисунок в сеть?'
,	confirm_save:	'Сохранить слои в память браузера?'
,	confirm_load:	'Вернуть слои из памяти браузера?'
,	copy_to_save:	'Откройте новый текстовый файл, скопируйте в него всё ниже этой линии'
,	found_swap:	'Рисунок был в запасе, поменялись местами.'
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
,	filter:		'Фильтр'
,	compose:	'Режим наложения'
,	palette:	'Палитра'
,	hex:		'Код'
,	hex_hint:	'Формат ввода — #a, #f90, #ff9900, или 0,123,255'
,	hide_hint:	'Кликните, чтобы спрятать.'
,	show_hint:	'Кликните, чтобы спрятать или показать.'
,	info: [	'{toggleView=\'hotkeys\';Управление}: [hotkeys;(указатель над полотном)'
	,,	'C / средний клик = подобрать цвет с рисунка.'
	,	'Q / P / R / T / Y / M = выбор формы.'
	,,	'1-10 / колесо мыши	/ (Alt +) W = толщина кисти.'
	,	'Ctrl	+ 1-10 / колесо / (Alt +) O = прозрачность.'
	,	'Alt	+ 1-10 / колесо / (Alt +) B = размытие тени.'
	,	'Shift	+ 1-10 / колесо / (Alt +) G = шаг сетки.'
	,,	'Shift	+ стрелки = выбирать слой.'
	,	'Alt	+ стрелки = двигать слой по списку.'
	,,	'Ctrl	+ тяга = поворот полотна, Home = {updateViewport;сброс}.'
	,	'Alt	+ тяга = масштаб, Shift + т. = сдвиг рамки.'
	,,	']F1 = {resetAside;вернуть} панельки по местам.-'
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

	},	new:	{sub:'новый',	t:'Создать новый слой.'
	},	copy:	{sub:'копия',	t:'Создать копию слоя.'
	},	merge:	{sub:'слить',	t:'Скопировать содержимое слоя вниз.'
	},	delete:	{sub:'удалить',	t:'Удалить слой вместе с его историей отмен.'
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
,	confirm_send:	'Send image to server?'
,	confirm_save:	'Save layers to your browser memory?'
,	confirm_load:	'Restore layers from your browser memory?'
,	copy_to_save:	'Open new text file, copy and paste to it after this line'
,	found_swap:	'Found image at slot 2, swapped slots.'
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
,	filter:		'Filter'
,	compose:	'Composition mode'
,	palette:	'Palette'
,	hex:		'Code'
,	hex_hint:	'Valid formats — #a, #f90, #ff9900, or 0,123,255'
,	hide_hint:	'Click to hide.'
,	show_hint:	'Click to show/hide.'
,	info: [	'{toggleView=\'hotkeys\';Hot keys}: [hotkeys;(mouse over image only)'
	,,	'C / mouse mid = pick color from image.'
	,	'Q / P / R / T / Y / M = select shape.'
	,,	'1-10 / mouse wheel	/ (Alt +) W = brush width.'
	,	'Ctrl	+ 1-10 / wheel	/ (Alt +) O = brush opacity.'
	,	'Alt	+ 1-10 / wheel	/ (Alt +) B = brush shadow blur.'
	,	'Shift	+ 1-10 / wheel	/ (Alt +) O = grid step.'
	,,	'Shift	+ arrows = select layer.'
	,	'Alt	+ arrows = move layer on the list.'
	,,	'Ctrl	+ drag = rotate canvas, Home = {updateViewport;reset}.'
	,	'Alt	+ drag = zoom, Shift + d. = move canvas.'
	,,	']F1 = {resetAside;reset} floating panels.-'
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

	,	new:	'Add a new layer.'
	,	copy:	'Add a copy of the current layer.'
	,	merge:	'Copy layer contents to the lower layer.'
	,	delete:	'Delete layer. Its undo history will be lost.'
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
#| aside {position: absolute; left: 0; top: 0; text-align: left; padding: 2px; border: 2px solid #888; background-color: rgba(234,234,234,0.90);}\
#| canvas {border: 1px solid #ddd; margin: 0; vertical-align: bottom; cursor:\
	url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAGElEQVR42mNgYGCYUFdXN4EBRPz//38CADX3CDIkWWD7AAAAAElFTkSuQmCC\'),\
	auto;}\
#| canvas:hover {border-color: #aaa;}\
#| hr {border: 1px solid #aaa; border-top: none;}\
#| {text-align: center; padding: 12px; background-color: #f8f8f8;}\
#|, #| button, #| input, #| select {color: #111; font-family: "Arial"; font-size: 19px; line-height: normal;}\
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
#|-load img {position: absolute; top: 1px; left: 1px; margin: 0;}\
#|-load, #|-load canvas {position: relative; display: inline-block;}\
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

function id(i) {return document.getElementById(NS+(i?'-'+i:''));}
function reId(e) {return e.id.slice(NS.length+1);}
function setId(e,id) {return e.id = NS+'-'+id;}
function setClass(e,c) {return e.className = c?replaceAdd(' '+c,' ',NS+'-').trim():'';}
function setEvent(e,onWhat,func) {return e.setAttribute(onWhat, NS+'.'+func);}
function setContent(e,c) {
var	a = ['class','id','onChange','onClick','onContextMenu'];
	for (i in a) c = replaceAdd(c, ' '+a[i]+'="', NS+(a[i][0]=='o'?'.':'-'));
	return e.innerHTML = c;
}
function clearContent(e) {while (e.childNodes.length) e.removeChild(e.lastChild);}	//* <- works without a blink, unlike e.innerHTML = '';
function toggleView(e) {if (!e.tagName) e = id(e); return e.style.display = e.style.display?'':'none';}
function propSwap(a, b) {
var	r = {};
	for (i in b) r[i] = a[i], a[i] = b[i];
	return r;
}

//* To get started *-----------------------------------------------------------

document.addEventListener('DOMContentLoaded', init, false);

}; //* <- END global wrapper
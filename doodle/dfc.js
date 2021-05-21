
//* Global wrapper; set namespace at the end *---------------------------------

(function(NS) { if (!window[NS]) window[NS] = null, window[NS] = new function() {

//* Configuration *------------------------------------------------------------

var	INFO_VERSION = 'v0.9.88'
,	INFO_DATE = '2013-04-01 — 2021-05-21'
,	INFO_ABBR = 'Dumb Flat Canvas'

,	CR = 'CanvasRecovery'
,	CT = 'Time'

,	DEFAULT_FONT = '18px sans-serif'
,	DRAW_PIXEL_OFFSET = 0.5
,	CANVAS_BORDER = 1
,	TAIL_WIDTH = 18
,	RANGE_MAX = 100
,	BOTH_PANELS_HEIGHT = 640
,	DRAW_HELPER = {
		lineWidth   : 1
	,	shadowBlur  : 0
	,	shadowColor : 'transparent'
	,	fillStyle   : 'transparent'
	,	strokeStyle : 'rgba(123, 123, 123, 0.5)'
	}

,	DEFAULT_TOOL_WIDTH = 2
,	ROUGH_LINE_DIV = 0.8	//* <- 640/800
,	ROUGH_LINE_SHIFT = 1
,	ROUGH_LINE_WIDTH_FRAC = 0.05
,	TOOLS_REF = [
		{blur : 0, opacity : 1.00, width :  1, color : '0, 0, 0'}	//* <- draw
	,	{blur : 0, opacity : 1.00, width : 20, color : '255, 255, 255'}	//* <- back
	]
,	tools = [{}, {}]
,	tool = tools[0]
,	selectedSlider = 'W'
,	SLIDER_LETTERS = 'BOW'
,	SLIDER_NAMES = ['blur', 'opacity', 'width']
,	RANGE = [
		{min : 0   , max : RANGE_MAX, step : 1}
	,	{min : 0.01, max : 1        , step : 0.01}
	,	{min : 1   , max : RANGE_MAX, step : 1}
	]

,	flushCursor = false
,	neverFlushCursor = true
,	mode = this.mode = {
		debug : false
	,	shape : false	//* <- straight line	/ fill area	/ copy
	,	step  : false	//* <- curve line	/ erase area	/ rect pan
	,	scale : false
	,	lowQ  : false
	,	roughLine : false
	,	brushView : false
	,	limitFPS  : false
	,	autoSave  : true
	}
,	MODE_NAMES = []
,	MODE_LETTERS = 'DLUSQRVFA'
,	SHAPE_HOTKEYS = 'NJR....M'
,	select = {
		imgSizes : {
			width  : 640
		,	height : 360
		}
	,	imgLimits : {
			width  : [32, 32767]
		,	height : [32, 32767]
		}
	,	lineCaps : {
			lineCap  : 0
		,	lineJoin : 0
		}
/* shape flags (sum parts):
	1 = path, mode L: step 1 line, L+U: step 2 curve
	2 = fig., mode L: fill, U: outline
	4 = move, mode L: copy, U: step 2 rect
	8 = path, closed polygon
	16 = cursor crosshair
	32 = print text
	64 = step 2
	128 = tool width not used
*/
	,	shapeFlags : [1,10, 50,34,50, 114,114, 148]
	,	shapeClass : '01111112'
	,	shapeModel : '//[oOO{<'
	,	textStylePreset : [
			''
		,	DEFAULT_FONT
		,	'sans-serif'
		,	'serif'
		,	'monospace'
		,	'cursive'
		,	'fantasy'
		,	'Modern'
		,	'Impact, "Arial Narrow"'
		,	'Anime Ace, Comic Sans'
		,	'"Century Gothic"'
		,	'Script'
		]
	,	options : {
			resize : {
				'top left'	: 'TL crop/pad anchor: top left'
			,	'top center'	: 'TC crop/pad anchor: top'
			,	'top right'	: 'TR crop/pad anchor: top right'
			,	'middle left'	: 'ML crop/pad anchor: middle left'
			,	'middle center'	: 'MC crop/pad anchor: center'
			,	'middle right'	: 'MR crop/pad anchor: middle right'
			,	'bottom left'	: 'BL crop/pad anchor: bottom left'
			,	'bottom center'	: 'BC crop/pad anchor: bottom'
			,	'bottom right'	: 'BR crop/pad anchor: bottom right'
			,	'auto crop'	: 'Auto crop all outer space'
			,	'scaleKeepW'	: 'W scale: keep ratio, load: keep width'
			,	'scaleKeepH'	: 'H scale: keep ratio, load: keep height'
			,	'scaleDeform'	: 'D scale: keep other, load: keep both'
			}
		,	shape : [
				'line'
			,	'freehand poly'
			,	'rectangle'
			,	'circle'
			,	'ellipse'
			,	'speech balloon'
			,	'speech box'
			,	'move'
			]
		,	lineCap : [
				'round'
			,	'butt'
			,	'square'
			]
		,	lineJoin : [
				'round'
			,	'bevel'
			,	'miter'
			]
		,	textStyle : [
				'...'
			,	'default font'
			,	'sans-serif'
			,	'serif'
			,	'monospace'
			,	'cursive'
			,	'fantasy'
			,	'modern'
			,	'narrow'
			,	'comic'
			,	'gothic'
			,	'script'
			]
		,	palette	: [
				'history'
			,	'auto'
			,	'legacy'
			,	'Touhou'
			,	'gradient'
			,	'wheel'
			]
		}
	}
,	PALETTE_COL_COUNT = 16	//* <- used if no '\n' found
,	palette = [
/* palette field format:
	'\t' = title
	'\n' = new row + optional title
	'\r' = special cases
	'#f00' = hex color field
	anything else = title + label
*/
		['#f']
	,	[	'#f', '#d', '#a', '#8', '#5', '#2', '#0',				'#a00', '#740', '#470', '#0a0', '#074', '#047', '#00a', '#407', '#704'
		, '\n',	'#7f0000', '#007f00', '#00007f', '#ff007f', '#7fff00', '#007fff', '#3', '#e11', '#b81', '#8b1', '#1e1', '#1b8', '#18b', '#11e', '#81b', '#b18'
		, '\n',	'#ff0000', '#00ff00', '#0000ff', '#ff7f00', '#00ff7f', '#7f00ff', '#6', '#f77', '#db7', '#bd7', '#7f7', '#7db', '#7bd', '#77f', '#b7d', '#d7b'
		, '\n',	'#ff7f7f', '#7fff7f', '#7f7fff', '#ffff00', '#00ffff', '#ff00ff', '#9', '#faa', '#eca', '#cea', '#afa', '#aec', '#ace', '#aaf', '#cae', '#eac'
		, '\n',	'#ffbebe', '#beffbe', '#bebeff', '#ffff7f', '#7fffff', '#ff7fff', '#c', '#fcc', '#fdc', '#dfc', '#cfc', '#cfd', '#cdf', '#ccf', '#dcf', '#fcd'
		]
	,	[ '\nMS Paint, Win7 version', '#0', '#7f7f7f', '#880015', '#ed1c24', '#ff7f27', '#fff200', '#22b14c', '#00a2e8', '#3f48cc', '#a349a4'
		, '\tBurichan', '#0f0c5d', '\tFutaba, TEGAKI', '#800000', '\tGurochan', '#d9af9e', '#af0a0f', '#285', '#0f0c5d'
		, '\nMS Paint, Win7 version', '#f', '#c3c3c3', '#b97a57', '#ffaec9', '#ffc90e', '#efe4b0', '#b5e61d', '#99d9ea', '#7092be', '#c8bfe7'
		, '\tBurichan', '#d6daf0', '\tFutaba, TEGAKI', '#f0e0d6', '\tGurochan', '#eddad2', '#ca927b', '#792', '#34345c'
		, '\tPixiv/illust.49093780'
		, '\n',	'#000000', '#271b8f', '#0000ab', '#47009f', '#8f0077', '#ab0013', '#a70000', '#7f0b00', '#432f00', '#004700', '#005100', '#003f17', '#1b3f5f'
		, '\n',	'#757575', '#0073ef', '#233bef', '#8300f3', '#bf00bf', '#e7005b', '#db2b00', '#cb4f0f', '#8b7300', '#009700', '#00ab00', '#00933b', '#00838b'
		, '\n',	'#bcbcbc', '#3fbfff', '#5f97ff', '#a78bfd', '#f77bff', '#ff77b7', '#ff7763', '#ff9b3b', '#f3bf3f', '#83d313', '#4fdf4b', '#58f898', '#00ebdb'
		, '\n',	'#ffffff', '#abe7ff', '#c7d7ff', '#d7cbff', '#ffc7ff', '#ffc7db', '#ffbfb3', '#ffdbab', '#ffe7a3', '#e3ffa3', '#abf3bf', '#b3ffcf', '#9ffff3'
		, '\nGrayScale', '#f', '#e', '#d', '#c', '#b', '#a', '#9', '#8', '#7', '#6', '#5', '#4', '#3', '#2', '#1', '#0'
		, '\nPaint.NET'
		,	'#a0a0a0', '#303030', '#ff7f7f', '#ffb27f', '#ffe97f', '#daff7f', '#a5ff7f', '#7fff8e'
		,	'#7fffc5', '#7fffff', '#7fc9ff', '#3f647f', '#a17fff', '#d67fff', '#ff7fed', '#ff7fb6'
		, '\n',	'#c0c0c0', '#606060', '#7f3f3f', '#7f593f', '#7f743f', '#6d7f3f', '#527f3f', '#3f7f47'
		,	'#3f7f62', '#3f7f7f', '#3f647f', '#3f497f', '#503f7f', '#6b3f7f', '#7f3f76', '#7f3f5b'
		, '\n',	'#000000', '#404040', '#ff0000', '#ff6a00', '#ffd800', '#b6ff00', '#4cff00', '#00ff21'
		,	'#00ff90', '#00ffff', '#0094ff', '#0026ff', '#4800ff', '#b200ff', '#ff00dc', '#ff006e'
		, '\n',	'#ffffff', '#808080', '#7f0000', '#7f3300', '#7f6a00', '#5b7f00', '#267f00', '#007f0e'
		,	'#007f46', '#007f7f', '#004a7f', '#00137f', '#21007f', '#57007f', '#7f006e', '#7f0037'
		, '\nClassic', '#000000', '#000080', '#008000', '#008080', '#800000', '#800080', '#808000', '#c0c0c0', '\tApple II', '#000000', '#7e3952', '#524689', '#df4ef2', '#1e6952', '#919191', '#35a6f2', '#c9bff9'
		, '\nClassic', '#808080', '#0000ff', '#00ff00', '#00ffff', '#ff0000', '#ff00ff', '#ffff00', '#ffffff', '\tApple II', '#525d0d', '#df7a19', '#919191', '#efb5c9', '#35cc19', '#c9d297', '#a2dcc9', '#ffffff'
		, '\nCGA', '#0', '#00a', '#0a0', '#0aa', '#a00', '#a0a', '#aa0', '#a', '\tMSX', '#0', '#0', '#3eb849', '#74d07d', '#5955e0', '#8076f1', '#b95e51', '#65dbef'
		, '\nCGA', '#5', '#55f', '#5f5', '#5ff', '#f55', '#f5f', '#ff5', '#f', '\tMSX', '#db6559', '#ff897d', '#ccc35e', '#ded087', '#3aa241', '#b766b5', '#c', '#f'
		, '\nIBM PC/XT CGA', '#000000', '#0000b6', '#00b600', '#00b6b6', '#b60000', '#b600b6', '#b66700', '#b6b6b6', '\tC-64', '#000000', '#ffffff', '#984a43', '#79c1c7', '#9b51a5', '#67ae5b', '#52429d', '#c9d683'
		, '\nIBM PC/XT CGA', '#676767', '#6767ff', '#67ff67', '#67ffff', '#ff6767', '#ff67ff', '#ffff67', '#ffffff', '\tC-64', '#9b6639', '#695400', '#c37b74', '#626262', '#898989', '#a3e599', '#897bcd', '#adadad'
		, '\nZX Spectrum', '#0', '#0000ca', '#00ca00', '#00caca', '#ca0000', '#ca00ca', '#caca00', '#cacaca', '\tVIC-20', '#000000', '#ffffff', '#782922', '#87d6dd', '#aa5fb6', '#55a049', '#40318d', '#bfce72'
		, '\nZX Spectrum', '#0', '#0000ff', '#00ff00', '#00ffff', '#ff0000', '#ff00ff', '#ffff00', '#ffffff', '\tVIC-20', '#aa7449', '#eab489', '#b86962', '#c7ffff', '#ea9ff6', '#94e089', '#8071cc', '#ffffb2'
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
	,	'\rw'
	]

//* Set up (don't change) *----------------------------------------------------

,	CUSTOM_CURSOR_DOT = false
,	noTransformByProp = /^Opera.* Version\D*11\.\d+$/i.test(navigator.userAgent)
,	noShadowBlurCurve = /^Opera.* Version\D*12\.\d+$/i.test(navigator.userAgent)
,	noBorderRadius	= noTransformByProp || noShadowBlurCurve
,	regA0		= /^[rgba(]+[\d,\s]+[.0)]+$/i
,	regCommaSpace	= /\s*(,)[\s,]*/g
,	regCommaSplit	= /,\s*/
,	regHex		= /^#?[0-9a-f]{6}$/i
,	regHex3		= /^#?([0-9a-f])([0-9a-f])([0-9a-f])$/i
,	regInt3		= /^([0-9]{1,3}),\s*([0-9]{1,3}),\s*([0-9]{1,3})$/
,	regInsideFunc	= /\{[^.]+\.([^(]+)\(/
,	regLimit	= /^(\d+)\D+(\d+)$/
,	regSpace	= /\s+/g
,	regVertSpace	= /\r\n|[\f\n\r\v\u2028\u2029]/g
,	regTailBrackets	= /[ ]*\([^)]+\)$/
,	regTextSize	= /^(\d+)([a-z]+)?$/i
,	regTrim		= /^\s+|\s+$/g
,	regHotKey	= /^[A-Z\d]$/

,	TITLE_LINE_BREAK = ' \r\n'
,	A0 = 'transparent'
,	IJ = 'image/jpeg'
,	FILL_RULE = 'evenodd'
,	MODE_LABELS = 'abc'.split('')
,	CSS_PREFIXES = ['-moz-', '-webkit-', '-o-', '']

,	t0 = +new Date
,	self = this
,	outside = this.o = {}
,	container, canvas, c2d, cnvHid, c2s, hue, lang, i,DL,HP,LP
,	LS = window.localStorage || localStorage
,	isPointerEventSupported = !!window.PointerEvent
,	isPointerEventCoalesced = isPointerEventSupported && !!PointerEvent.prototype.getCoalescedEvents
,	fps = 0
,	ticks = 0
,	timer = 0
,	lastUsedSaveSlot = 0
,	interval = {
		fps   : 0
	,	timer : 0
	,	save  : 0
	}
,	text = {
		debug : 0
	,	timer : 0
	,	undo  : 0
	}
,	used = {}
,	cue = {upd : {}}
,	postingInProgress

,	draw = {
		o    : {}
	,	cur  : {}
	,	prev : {}
	,	refresh : 0
	,	line : {
			started : 0
		,	back    : 0
		,	preview : 0
		}
	,	time : {
			activeSum : 0
		,	all : [0,0]
		,	act : function(i) {
			var	a = this.all
			,	t = +new Date
			,	i = (i ? t0 : t)
				;

				if (!a[0]) {
					a[0] = this.activeStart = i;
					a[1] = t;
				} else {
					if (!this.activeStart) {
						this.activeStart = i;
					} else
					if (t-a[1] > this.idle) {
						this.activeSum = this.sum(t);
						this.activeStart = t;
					}
				}

				return t;
			}
		,	sum : function(t) {
				return (
					this.activeStart
					? (this.all[1] || t || +new Date) - this.activeStart
					: 0
				) + this.activeSum;
			}
		}
	,	history : {
			pos  : 0
		,	last : 0
		,	max  : 0
		,	data : []
		,	cur : function() { return this.data[this.pos]; }
		,	act : function(i) {
			var	t = this
			,	s = isNaN(i)
			,	z = this.max
			,	dt = draw.time
				;

				lastUsedSaveSlot = 0;

				if (i && !s) {
					if (i < 0 && t.pos > 0) {
						--t.pos;
					} else
					if (i > 0 && t.pos < z && t.pos < t.last) {
						++t.pos;
					} else {
						return 0;
					}

					if ((d = t.data[t.pos]) && d.timeRest && (a = d.time)) for (i in a) {
						dt[i] = a[i];
					}

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
							i = select.resize.value;
							if (t.reversable == i) --t.pos;
							else t.reversable = i;
							for (i in select.imgSizes) cnvHid[i] = canvas[i] = orz(getElemById('img-'+i).value);
							draw.screen(1);
						} else {
							if (t.reversable == i) {
								return 0;
							} else {
								t.reversable = i;
								draw.screen();
							}
						}

						if (i !== 0) {
							if (t.pos < z) {
								t.last = ++t.pos;
							} else {
								for (i = 0; i < z; i++) t.data[i] = t.data[i+1];
							}
						}
					}

				var	a = ['all', 'activeStart', 'activeSum']
				,	b
				,	c = {}
				,	d = c2d.getImageData(0,0, canvas.width, canvas.height)
					;

					for (i in a) {
						b = dt[k = a[i]];
						c[k] = (b ? (b.join ? b.slice(0) : b) : 0);
					}

					d.time = c;
					t.data[t.data.length = t.pos] = d;
				}

				text.undo.textContent = t.pos+' / '+t.last+' / '+z;

				return 1;
			}
		,	storeTime : function() {
			var	d = this.cur();
				if (d) d.timeRest = 1;
			}
		}
	,	screen : function(res) {
			clearFill(canvas);

		var	d = this.history.cur();
			if (!d) {
				return;
			}

		var	c = canvas
		,	s = select.imgSizes
		,	i
			;

			if (res && mode.scale) {
				for (i in s) {
					cnvHid[i] = d[i];
					c[i] = orz(getElemById('img-'+i).value);
				}

				c2s.putImageData(d, 0,0);
				clearFill(c).drawImage(cnvHid, 0,0, d.width, d.height, 0,0, c.width, c.height);

				for (i in s) {
					cnvHid[i] = c[i];
				}
			} else {
				if (res) {
				var	a = select.resize.value.split(' ')
				,	w = c.width
				,	h = c.height
				,	i = d.width
				,	j = d.height
				,	y = (a.indexOf('top') < 0 ? h-j : 0)
				,	x = (a.indexOf('left') < 0 ? w-i : 0)
					;

					if (a.indexOf('middle') >= 0) y = Math.floor(y/2);
					if (a.indexOf('center') >= 0) x = Math.floor(x/2);
				} else {
				var	dif = x = y = 0
				,	e = document.activeElement
				,	unfocused = !(e.id && s[getLastWord(e.id)])
					;

					for (i in s) {
						if (c[i] != d[i]) {
							cnvHid[i] = c[i] = d[i];
						}

						if (unfocused && (e = getElemById('img-'+i)).value != d[i]) {
							e.value = d[i];
							++dif;
						}
					}

					if (dif) updateDimension();
				}
				c2d.putImageData(d, x,y);
			}
		}
	};

function historyAct(i) {
	if (draw.history.act(i)) {
		updateDebugScreen();
		updateHistoryButtons();
	}
}

this.show = showProps;
this.unsave = function(i) { if (saveClear(i,1)) updateDebugScreen(i); }
this.whatSaved = function(i) {
var	d = getSaveLSDict(i);

	return {
		'supported'	: '[\n'+showProps(CR[i],1)+'\n]'
	,	'found'		: '[\n'+showProps(d.dict,5)+'\n]'
	,	'total bytes'	: getFormattedNum(d.sum)
	};
}

//* Generic helper functions *-------------------------------------------------

function eventStop(evt) {
	if (evt && evt.eventPhase ? evt : (evt = window.event)) {
		if (evt.stopPropagation) evt.stopPropagation();
		if (evt.cancelBubble !== null) evt.cancelBubble = true;
		if (evt.stopImmediatePropagation !== null) evt.stopImmediatePropagation = true;
	}

	return evt;
}

function getEventPointsDist(a,b) {
	return (
		Math.abs(a.x - b.x)
	+	Math.abs(a.y - b.y)
		// dist(a.x - b.x, a.y - b.y)
	);
}

function sortEventPoints(a,b) {
	return (
		a.timeStamp > b.timeStamp ? 1 :
		a.timeStamp < b.timeStamp ? -1 :
		a.pointerId > b.pointerId ? 1 :
		a.pointerId < b.pointerId ? -1 :
		a.distFromPrevTime > b.distFromPrevTime ? 1 :
		a.distFromPrevTime < b.distFromPrevTime ? -1 :
		0
	);
}

function isTransparent(s) {return (s == A0 || regA0.test(s));}

function dist(x,y) {return Math.sqrt(x*x + y*y)};
function angleTo(x,y) {return cutPeriod(y-x);}
function cutPeriod(x,y,z) {
	if (isNaN(y)) y = -Math.PI;
	if (isNaN(z)) z = Math.PI;
	return (x < y ? x-y+z : (x > z ? x+y-z : x));
}

//* Source: http://www.webtoolkit.info/javascript-trim.html
function ltrim(str, chars) {return str.replace(new RegExp('^['+(chars || '\\s')+']+', 'g'), '');}
function rtrim(str, chars) {return str.replace(new RegExp('['+(chars || '\\s')+']+$', 'g'), '');}
function trim(str, chars) {return ltrim(rtrim(str, chars), chars);}

function orz(n) {return parseInt(n||0)||0;}
function nl2br(s) {return s.replace(/[\r\n]+/g, '<br>');}
function repeat(t,n) {return new Array(n+1).join(t);}
function replaceAll(t,s,j) {return t.split(s).join(j);}
function replaceAdd(t,s,a) {return replaceAll(t,s,s+a);}
function getSpaceReduced(t) {return t.replace(regTrim, '').replace(regSpace, ' ');}
function getLastWord(t,i) {return t.substr(t.lastIndexOf(i||'-')+1);}
function getFormattedNum(i) {
var	r
,	s = ''+i
,	f = 'toLocaleString'
	;

	return (
		(i = orz(i))[f] && s != (r = i[f]())
		? r
		: s.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1 ')
	);
}

function getTextOffsetX(ctx, align, lines) {
	if (align == 'center') {
		return 0;
	}

var	i,w,x = 0;

	for (i in lines) if (x < (w = ctx.measureText(lines[i]).width)) {
		x = w;
	}

	return (align == 'left' ? -x : x)/2;
}

function getTextOffsetXY(f,c,a,t) {
	c.font = f;

	return {
		x : getTextOffsetX(c,a,t)
	,	y : c.measureText('M').width
	};
}

function cre(e,p,b) {
	e = document.createElement(e);
	if (b) p.insertBefore(e, b); else
	if (p) p.appendChild(e);

	return e;
}

function getElemById(i) {return document.getElementById(i ? NS+'-'+i : NS);}
function setId(e,i) {if (e) e.id = NS+'-'+i; return e;}
function setEvent(e,onWhat,func) {if (e) e.setAttribute(onWhat, NS+'.'+func); return e;}
function setClickRemove(e,o) {if (e) e.setAttribute(o || 'onclick', 'this.parentNode.removeChild(this); return false'); return e;}
function setClass(e,c) {
	if (e) {
		if (c && (c = replaceAdd(' '+getSpaceReduced(c), ' ', NS+'-').trim())) {
			e.className = c;
		} else if (e.getAttribute(c = 'class')) {
			e.removeAttribute(c);
		}
	}

	return e;
}

function clearContent(e) {while (e.lastChild) e.removeChild(e.lastChild); return e;}	//* <- works without a blink, unlike e.innerHTML = '';
function setContent(e,c) {
var	a = ['class','id','onChange','onClick'];

	for (i in a) {
		c = replaceAdd(c, ' '+a[i]+'="', NS+(a[i][0]=='o' ? '.' : '-'));
	}

	return e.innerHTML = c;
}

function toggleView(e) {
	e = getElemById(e);

	return e.style.display = (e.style.display ? '' : 'none');
}

function showProps(target, flags, spaces) {
/* flags:
	1 = return, don't alert
	2 = skip if value evaluates to false
	4 = only keys
	8 = only values
*/
var	k,v,j = ' '
,	output = ''
	;

	for (k in target) if (
		(v = target[k])
	||	!(flags & 2)
	) {
		output += (
			(output ? '\n' : '')
		+	((flags & 8) ? '' : k)
		+	((flags &12) ? '' : ' = ')
		+	((flags & 4) ? '' : (spaces && v ? (v+j).split(j, spaces).join(j) : v))
		);
	}

	return (
		(flags & 1)
		? output
		: alert(output)
	);
}

//* Strokes and shapes *-------------------------------------------------------

function drawCursor() {
var	sf = draw.shapeFlags;

	if (sf & 16) {
		for (i in DRAW_HELPER) c2d[i] = DRAW_HELPER[i];

	var	i,o = draw.o
	,	p = DRAW_PIXEL_OFFSET
		;

		c2d.beginPath();
		c2d.moveTo(o.x+p, 0), c2d.lineTo(o.x+p, canvas.height);
		c2d.moveTo(0, o.y+p), c2d.lineTo(canvas.width, o.y+p);
		c2d.stroke();
	}

	if (!(sf & 128)) {
		if (mode.brushView) {
			c2d.fillStyle = 'rgba('+tool.color+', '+tool.opacity+')';
			c2d.shadowColor = ((c2d.shadowBlur = tool.blur) ? 'rgb('+tool.color+')' : A0);
		} else {
			for (i in DRAW_HELPER) c2d[i] = DRAW_HELPER[i];
		}

		c2d.beginPath();
		c2d.arc(draw.cur.x, draw.cur.y, tool.width/2, 0, 7, false);
		c2d.closePath();
		mode.brushView ? c2d.fill() : c2d.stroke();
	}

	if (!neverFlushCursor) flushCursor = true;
}

function drawStart(evt) {
	draw.evt = evt = evt || window.event;

	// if (!evt || draw.active) {	//* <- breaks line+curve 2nd point
	if (!evt) {
		return;
	}

	try {
		showProps(evt,1,1);	//* <- check if permission denied to read some property
	} catch (error) {
		return;			//* <- against FireFox catching clicks on page scrollbar
	}

	if (!draw.step || (draw.target && draw.target !== evt.target)) {
		drawEnd(evt);
	}

	if (!isMouseIn()) {
		return;
	}

	eventStop(evt).preventDefault();
	draw.target = evt.target;
//	canvas.focus();

	if (draw.btn && (draw.btn != evt.which)) {
		return drawEnd();
	}

	if (mode.click) {
		++mode.click;

		return drawEnd(evt);
	}

var	s = draw.shape = select.shape.value
,	sf = draw.shapeFlags = select.shapeFlags[s]
	;

	if (
		(evt.altKey || evt.ctrlKey || evt.shiftKey)
	&&	(draw.step && mode.step && mode.shape && (sf & 1))	//* <- line+curve
	); else
	if (evt.altKey)   draw.turn = {prev : draw.zoom,  zoom  : 1}; else
	if (evt.ctrlKey)  draw.turn = {prev : draw.angle, angle : 1}; else
	if (evt.shiftKey) draw.turn = {
		prev : (
			draw.pan
			? {x : draw.pan.x, y : draw.pan.y}
			: {x : 0, y : 0}
		)
	,	pan : 1
	};

	updatePosition(evt);

	if (draw.turn) {
		return draw.turn.origin = getCursorRad();
	}

	if (draw.step) {
		if (
			(mode.step && (
				(mode.shape && (sf & 1))	//* <- line+curve
				|| (sf & 4)			//* <- move+area
			)) || (sf & 64)				//* <- any mode
		) {
			for (i in draw.o) draw.prev[i] = draw.cur[i];

			draw.step.swap = evt.altKey || evt.ctrlKey || evt.shiftKey;
			draw.step.done = 1;

			return;
		} else {
			draw.step = 0;
		}
	}

//	if (evt.shiftKey) mode.click = 1;

	if ((draw.btn = evt.which) != 1 && draw.btn != 3) {
		pickColor();

		return drawEnd(evt);
	}

//* start drawing:

	draw.active = draw.time.act();

	if (!interval.timer) {
		interval.timer = setInterval(timeElapsed, 1000);
		interval.save  = setInterval(autoSave, 60000);
	}

var	i = (evt.which == 1 ? 1 : 0)
,	t = tools[1-i]
,	pf  = ((sf & 8) && (mode.shape || !mode.step))
,	fig = ((sf & 2) && (mode.shape || pf))
	;

	draw.fig = (mode.click == 1 || mode.shape || !(sf & 1)) && !(sf & 8);
	draw.points = null;

	t = (sf & 4 ? DRAW_HELPER : {
		lineWidth : (pf && !mode.step ? 1 : t.width + (mode.roughLine ? ROUGH_LINE_WIDTH_FRAC : 0))
	,	fillStyle : (fig ? 'rgba('+(mode.step ? tools[i] : t).color+', '+t.opacity+')' : A0)
	,	strokeStyle : (fig && !(mode.step || pf) ? A0 : 'rgba('+t.color+', '+t.opacity+')')
	,	shadowColor : (t.blur ? 'rgb('+t.color+')' : A0)
	,	shadowBlur : t.blur
	});

	for (i in t) c2s[i] = c2d[i] = t[i];

	updatePosition(evt);

	if (
		isPointerEventCoalesced
	&&	!draw.fig
	&&	(
			evt.pointerId
		// ||	evt.timeStamp
		)
	) {
		draw.points = [{
			x : draw.cur.x
		,	y : draw.cur.y
		,	pointerId : 0
		,	timeStamp : 0	//* <- keep this point first in sorting
		}];
	}

	for (i in draw.o) draw.prev[i] = draw.cur[i];
	for (i in draw.line) draw.line[i] = false;
	for (i in select.lineCaps) c2s[i] = c2d[i] = select.options[i][select[i].value];

	if (
		(sf & 32)
	&&	(t = getElemById('text-content').value).replace(regTrim, '').length
	) {
	var	i = getElemById('text-font')
	,	f = c2d.font = DEFAULT_FONT
	,	s = c2d.strokeStyle
	,	a = draw.textAlign || 'center'
	,	t = t.split(regVertSpace)
		;

		if (k = checkTextStyle(i, 1)) {
			f = i.value;
		} else {
		var	j = i.value.replace(regCommaSpace, '$1 ').split(' ')
		,	k = []
		,	l = ''
		,	m
			;

			for (i in j) {
				if (m = j[i].match(regTextSize)) {
					l = m[1] + (m[2] || 'px')+' ';
				} else {
					k.push(j[i]);
				}
			}

			f = l+k.join(' ');
			k = (l && checkTextStyle(f, 1));
		}

		if (isTransparent(s)) {
			i = (draw.btn == 1 ? 1 : 0);
			s = 'rgba('+tools[i].color+', '+tools[1-i].opacity+')';
		}

		draw.text = {
			font : f
		,	style : s
		,	align : a
		,	lines : t
		,	offset : (k ? getTextOffsetXY(f,c2d,a,t) : {x : 0, y : 0})
		};
	} else {
		draw.text = 0;
	}

	if ((sf & 32) && !(sf & 2)) {
		return drawEnd(evt);
	}

	c2d.beginPath();
	c2d.moveTo(draw.cur.x, draw.cur.y);
}

function drawMove(evt) {
	draw.evt = evt = evt || window.event;

	if (evt) {
		if (mode.click == 1 && !evt.shiftKey) {
			mode.click = 0;

			return drawEnd(evt);
		}

		if (
			evt.type.indexOf('mouse') === 0
		||	evt.type.indexOf('pointer') === 0
		) {
			updatePosition(evt);
		}
	}

	if (draw.turn) {
		return updateViewport(draw.turn.pan ? 1 : draw.turn.delta = getCursorRad() - draw.turn.origin);
	}

var	redraw = true
,	s = draw.shape
,	sf = draw.shapeFlags
,	newLine = (draw.active && !draw.fig)
,	points
	;

	if (mode.click) {
		mode.click = 1;
	}

	if (newLine) {
		if (draw.line.preview) {
			drawShape(c2d, s);
		} else
		if (draw.line.back = mode.step) {
			if (noShadowBlurCurve) {
				c2d.shadowColor = A0;
				c2d.shadowBlur = 0;
			}

			if (draw.line.started) {
				if (points = draw.points) {
					points.isCurve = true;
					points.push({
						x : draw.cur.x
					,	y : draw.cur.y
					,	pointerId : evt.pointerId
					,	timeStamp : evt.timeStamp
					});
				} else {
					c2d.quadraticCurveTo(
						draw.prev.x
					,	draw.prev.y
					,	(draw.cur.x + draw.prev.x)/2
					,	(draw.cur.y + draw.prev.y)/2
					);
				}
			}
		} else {
			if (points = draw.points) {
				points.push({
					x : draw.cur.x
				,	y : draw.cur.y
				,	pointerId : evt.pointerId
				,	timeStamp : evt.timeStamp
				});
			} else {
				c2d.lineTo(draw.cur.x, draw.cur.y);
			}
		}

		draw.line.preview = false;
		draw.line.started = true;
	} else
	if (draw.line.back) {
		c2d.lineTo(draw.prev.x, draw.prev.y);

		draw.line.back = false;
		draw.line.started = true;
	}

	if (mode.limitFPS) {
	var	t = +new Date;
		if (t-draw.refresh > 30) draw.refresh = t; else redraw = false;		//* <- use 1000/N to redraw maximum N FPS
	}

	if (redraw) {

//* Restore the view before current unfinished action:

		if ((flushCursor || neverFlushCursor) && !(mode.lowQ && draw.active)) {
			draw.screen();
		}

//* Redraw current active shape:

		if (draw.active) {
			if (draw.fig) {
				draw.line.preview = true;
				c2s.clearRect(0,0, canvas.width, canvas.height);
				c2s.beginPath();
				drawShape(c2s, (mode.step && (sf & 4) && (!draw.step || !draw.step.done) ? -1 : s));
				c2s.stroke();
				c2d.drawImage(cnvHid, 0,0);				//* <- draw 2nd canvas overlay with sole shape
			}

			if (draw.line.started) {

//* Fix for out-of-order pen points in Firefox:

				if (points) {
					points.sort(sortEventPoints);

					function setEventPointDist(point) {
						if (typeof point.distFromPrevTime === 'undefined') {
							point.distFromPrevTime = getEventPointsDist(refPoint, point);

							isResortingNeeded = true;
						}
					}

					for (
					var	i = 2
					,	k = points.length
					,	isResortingNeeded = false
					,	refPoint = points[0]
					,	prevPoint
					,	nextPoint = points[1]
						; i < k
					&&	(prevPoint = nextPoint)
					&&	(nextPoint = points[i])
						; ++i
					) if (
						prevPoint.timeStamp === nextPoint.timeStamp
					&&	prevPoint.pointerId === nextPoint.pointerId
					) {
						setEventPointDist(prevPoint);
						setEventPointDist(nextPoint);
					} else {
						refPoint = prevPoint;
					}

					if (isResortingNeeded) {
						points.sort(sortEventPoints);

						// console.log('isResortingNeeded:', isResortingNeeded, points);
					}

					c2d.beginPath();
					c2d.moveTo(points[0].x, points[0].y);

					if (points.isCurve) {
						for (
						var	i = 2
						,	k = points.length
						,	prevPoint
						,	nextPoint = points[1]
							; i < k
						&&	(prevPoint = nextPoint)
						&&	(nextPoint = points[i])
							; ++i
						) {
							c2d.quadraticCurveTo(
								prevPoint.x
							,	prevPoint.y
							,	(nextPoint.x + prevPoint.x)/2
							,	(nextPoint.y + prevPoint.y)/2
							);
						}
					} else {
						for (
						var	i = 1
						,	k = points.length
						,	nextPoint
							; i < k
						&&	(nextPoint = points[i])
							; ++i
						) {
							c2d.lineTo(
								nextPoint.x
							,	nextPoint.y
							);
						}
					}
				}

//* Redraw the plotted stroke:

				c2d.stroke();
			}
		} else if (neverFlushCursor && !mode.lowQ && isMouseIn()) {
			drawCursor();
		}

		updateDebugScreen();
	}

	if (newLine) {
		for (i in draw.o) draw.prev[i] = draw.cur[i];
	}
}

function drawEnd(evt) {
	draw.evt = evt = evt || window.event;
	draw.target = 0;

	if (!evt || draw.turn) {
		draw.active = draw.btn = draw.step = draw.turn = 0;

		return draw.screen();
	}

	if (evt.which && evt.which != 1 && draw.btn != 3) {
		draw.active = draw.btn = draw.step = draw.turn = draw.text = 0;

		return;
	}

	if (mode.click == 1 && evt.shiftKey) {
		return drawMove(evt);
	}

	if (draw.active) {
	var	c = c2d
	,	s = draw.shape
	,	sf = draw.shapeFlags
		;

	//* normal straight 2pt line, base for 4pt curve:

		if (!draw.step && (
			(mode.step && (
				(mode.shape && (sf & 1))	//* <- line+curve
				|| (sf & 4)			//* <- move+area
			)) || (sf & 64)				//* <- any mode
		)) {
			draw.step = {
				prev : {
					x : draw.prev.x
				,	y : draw.prev.y
				}
			,	cur : {
					x : draw.cur.x
				,	y : draw.cur.y
				}
			};

			return;
		}

		draw.time.all[1] = +new Date;
		draw.screen();
		c2d.fillStyle = c2s.fillStyle;

		if (draw.text) {
			c = c2s;
			c.beginPath();
			c.clearRect(0,0, canvas.width, canvas.height);
			drawShape(c, s);

			used.shape = 'Shape';
			used.text = 'Text';
		} else
		if (sf & 8) {
			c.closePath();

			if (mode.shape || !mode.step) {
				c.fill(FILL_RULE);
			}

			used.poly = 'Poly';
		} else
		if ((sf & 64) || (draw.fig && draw.line.preview)) {
			c.beginPath();
			drawShape(c, s);

			if (!(sf & 4)) {
				used.shape = 'Shape';
			}
		} else
		if (draw.fig || draw.line.back || !draw.line.started) {//* <- draw 1 pixel on short click, regardless of mode or browser
			c.lineTo(draw.cur.x, draw.cur.y + (draw.cur.y == draw.prev.y ? 0.01 : 0));
		}

		if (sf & 4) {
			used.move = 'Move';
		} else
		if (!(sf & 8) || mode.step) {
			c.stroke();
		}

		if (draw.text) {
			c2d.drawImage(cnvHid, 0,0);
		}

		historyAct();
		draw.active = draw.btn = draw.step = draw.text = 0;

		if (cue.autoSave < 0) {
			autoSave();
		} else {
			cue.autoSave = 1;
		}

		if (mode.click && evt.shiftKey) {
			mode.click = 0;

			return drawStart(evt);
		}
	}

	updateDebugScreen();
}

function drawShape(ctx, i) {
var	evt = draw.evt
,	s = draw.step
,	v = draw.prev
,	r = draw.cur
,	AREA = 0
	;

	switch (select.shapeModel[i] || '[') {
	//* pan
		case '<':
		case '>':
			if (
				(v.x != r.x)
			||	(v.y != r.y)
			) {
				moveScreen(r.x-v.x, r.y-v.y);
			}
		break;
	//* rect
		case '[':
			if (s) {
		//* show pan source area
				v = s.prev;
				r = s.cur;
			} else {
				if (draw.text) {
				var	x = (v.x+r.x)/2
				,	y = (v.y+r.y)/2
				,	h = Math.abs(v.y-r.y)
					;
				}

				AREA = 1;
			}

			ctx.moveTo(v.x, v.y);
			ctx.lineTo(r.x, v.y);
			ctx.lineTo(r.x, r.y);
			ctx.lineTo(v.x, r.y);
			ctx.closePath();
		break;
	//* circle
		case 'o':
		var	x = xCenter = (v.x+r.x)/2
		,	y = yCenter = (v.y+r.y)/2
		,	radius = Math.max(1, dist(r.x-xCenter, r.y-yCenter))
		,	h = radius*2
			;

			ctx.moveTo(xCenter + radius, yCenter);
			ctx.arc(xCenter, yCenter, radius, 0, 7, false);
			ctx.closePath();
			AREA = 1;
		break;
	//* ellipse
		case 'O':
			if (s) {
				p = v;
				q = r;
				v = s.prev;
				r = s.cur;
			}

		var	xCenter = (v.x+r.x)/2
		,	yCenter = (v.y+r.y)/2
		,	xRadius = Math.max(1, Math.abs(r.x-xCenter))
		,	yRadius = Math.max(1, Math.abs(r.y-yCenter))
		,	radius = Math.max(xRadius, yRadius)
		,	a = 1
		,	b = 1
		,	p2 = Math.PI*2
		,	h = yRadius*2
		,	q,p
			;

			if (s && s.done) {
				xCenter += q.x-p.x;
				yCenter += q.y-p.y;
			}
			x = xCenter;
			y = yCenter;

			ctx.save();

			if (xRadius < yRadius) {
				xCenter /= a = xRadius/yRadius;
				ctx.scale(a, b);
			} else
			if (xRadius > yRadius) {
				yCenter /= b = yRadius/xRadius;
				ctx.scale(a, b);
			}

			if (s) {
		//* speech balloon
			var	a = q.x/a
			,	b = q.y/b
			,	a1 = (Math.min(radius/8, TAIL_WIDTH) + tool.width)/8/radius*p2
			,	a2 = Math.atan2(b-yCenter, a-xCenter)
				;

				ctx.moveTo(a, b);
				ctx.arc(xCenter, yCenter, radius, a1+a2, p2-a1+a2, false);
			} else {
				ctx.moveTo(xCenter + xRadius/a, yCenter);
				ctx.arc(xCenter, yCenter, radius, 0, p2, false);
			}

			ctx.closePath();
			ctx.restore();
			AREA = 1;
		break;
	//* speech box
		case '{':
			if (s) {
				p = v;
				q = r;
				v = s.prev;
				r = s.cur;
			}

		var	dx = Math.max(1, Math.abs(r.x-v.x))	//* <- box width
		,	dy = Math.max(1, Math.abs(r.y-v.y))	//* <- box height
		,	d = Math.floor(Math.min(dx, dy)/8)
		,	r1 = Math.max(1, Math.min(64, d))	//* <- border radius
		,	x0 = Math.min(r.x, v.x)
		,	y0 = Math.min(r.y, v.y)
		,	x1 = Math.max(r.x, v.x)
		,	y1 = Math.max(r.y, v.y)
			;

			function drawSpeechFig(xCenter, yCenter, roundRadius, xRadius, yRadius, sideCount, xTail, yTail) {
				if (!(sideCount > 0)) sideCount = 4;
				if (!(xRadius > 0)) xRadius = 1;
				if (!(yRadius > 0) || (sideCount % 2)) yRadius = xRadius;
				if (!(roundRadius > 0)) {
					roundRadius = 0;
				} else {
					roundRadius = Math.min(roundRadius, xRadius/2, yRadius/2);
				}

			var	i = sideCount
			,	a = Math.PI
			,	b = a/i
			,	q = b*2
			,	f = b-Math.atan2(yRadius, xRadius)	//* <- fluctuating pendulum angle shift
			,	d = dist(yRadius, xRadius)
			,	r = dist(roundRadius, roundRadius)
			,	aFirst = 0
			,	aLast = 0
				;

				if (isNaN(xTail) || isNaN(yTail)) {
					ctx.moveTo(xCenter - xRadius, yCenter);
				} else {
				var	w = Math.min(r1, TAIL_WIDTH/2) + tool.width
				,	x = xTail-xCenter
				,	y = yTail-yCenter
				,	t = Math.atan2(y, x)
					;

					while (i-- && a-b+f > t) {
						a -= q;
						f = -f;
					}

				var	TAIL = (t > a ? 1 : -1)
				,	j = !(i % 2)
				,	rc = (j ? yRadius : xRadius)
				,	rd = (j ? xRadius : yRadius)
				,	rs = rd - roundRadius

				,	at = angleTo(a, t)
				,	ta = Math.tan(at)
				,	tc = ta*rc
				,	te = tc+w

				,	ac = Math.cos(a)
				,	as = Math.sin(a)
				,	ar = b/roundRadius
				,	i = sideCount
					;

					if (TAIL > 0 && Math.abs(te) > rs) {
						--i;
					}

					ctx.moveTo(xTail, yTail);
					drawTailPart(tc-w);
				}

				function drawTailPart(td, end) {
				var	ab = Math.abs(td);

					if (ab > rs) {
						ab = (ab-rs)*ar;

						if (TAIL > 0) {
							if (end) aLast = ab;
							else {
								aFirst = q-ab;
								a += q;
								f = -f;
								++i;
							}
						} else {
							if (end) aLast = q-ab;
							else aFirst = ab;
						}

						if (end) drawNextArc();
					} else {
						if (j) {
							td = -td;
						}

						ctx.lineTo(
							xCenter + ac*rc + as*td
						,	yCenter + as*rc + ac*td
						);
					}
				}

				function drawNextArc() {
				var	a0 = a
				,	a1 = a -= b
				,	a2 = a -= b
				,	a3 = a1-(f = -f)
					;

					ctx.arc(
						xCenter + Math.cos(a3)*d - Math.cos(a1)*r
					,	yCenter + Math.sin(a3)*d - Math.sin(a1)*r
					,	roundRadius
					,	a0-aFirst
					,	a2+aLast
					,	true
					);

					aFirst = aLast = 0;

					return --i;
				}

				while (drawNextArc());

				if (TAIL) {
					drawTailPart(te, 1);
				}

				ctx.closePath();
			}

			if (s && s.done) {
				d = q.x-p.x;
				x0 += d;
				x1 += d;

				d = q.y-p.y;
				y0 += d;
				y1 += d;
			}

		var	x = (x0+x1)/2
		,	y = (y0+y1)/2
		,	h = dy
			;

			s
			? drawSpeechFig(x, y, r1, dx/2, dy/2, 4, q.x, q.y)
			: drawSpeechFig(x, y, r1, dx/2, dy/2);

			AREA = 1;
		break;
	//* line
		default:
			if (s) {
			var	d = r
			,	w = s.prev
			,	u = s.cur
			,	old = {}
			,	t = DRAW_HELPER
				;

		//* curve helpers

				for (i in t) {
					old[i] = c2s[i];
					c2s[i] = t[i];
				}

				c2s.beginPath();

		//* control point 1 phantom

				c2s.moveTo(u.x, u.y);
				c2s.lineTo(w.x, w.y);

		//* control point 2 phantom

				if (w.x != v.x || w.y != v.y) {
					c2s.moveTo(d.x, d.y), d = v;
					c2s.lineTo(d.x, d.y);
				}

				c2s.stroke();

				for (i in t) {
					c2s[i] = old[i];
				}

				c2s.beginPath();
		//* curve
			var	swapKey = (evt && (evt.altKey || evt.ctrlKey || evt.shiftKey))
			,	swapPoint1 = (s.done ? s.swap : swapKey)
			,	swapPoint2 = (s.done ? !swapKey : swapKey)
			,	linePoint1 = (swapPoint1 ? u : w)
			,	ctrlPoint1 = (swapPoint1 ? w : u)
			,	ctrlPoint2 = (swapPoint2 ? r : d)
			,	linePoint2 = (swapPoint2 ? d : r)
				;

				ctx.moveTo(linePoint1.x, linePoint1.y);
				ctx.bezierCurveTo(
					ctrlPoint1.x, ctrlPoint1.y
				,	ctrlPoint2.x, ctrlPoint2.y
				,	linePoint2.x, linePoint2.y
				);
			} else {
		//* straight
				ctx.moveTo(v.x, v.y);
				ctx.lineTo(r.x, r.y);
			}
	}

	if (AREA && !isTransparent(ctx.fillStyle)) {
		ctx.fill(FILL_RULE);
	}

	if (draw.text) {
	var	d = draw.text
	,	t = d.lines
	,	o = d.offset
	,	f = d.font
		;

		if (!o.y) {
			f = Math.round(h/(t.length*2-1))+'px '+f;
			o = getTextOffsetXY(f, ctx, d.align, t);
		}

		h = o.y*2;
		x += o.x;
		y -= h/2*(t.length-1);
		ctx.save();
		ctx.clip();
		ctx.font = f;
		ctx.fillStyle = d.style;
		ctx.textAlign = d.align;
		ctx.textBaseline = 'middle';

		for (i in t) {
			ctx.fillText(t[i], Math.round(x), Math.round(y));
			y += h;
		}

		ctx.restore();
	}
	ctx.moveTo(r.x, r.y);
}

//* One-click all-screen manipulation *----------------------------------------

function moveScreen(x, y) {
var	d = draw.history.cur()
,	p = draw.step
,	notCopy = !mode.shape
	;

	c2d.fillStyle = 'rgb(' + tools[1].color + ')';

	if (p) {
		for (i in {min : 0, max : 0}) {
			p[i] = {
				x : Math[i](p.cur.x, p.prev.x)
			,	y : Math[i](p.cur.y, p.prev.y)
			};
		}

		p.max.x -= p.min.x;
		p.max.y -= p.min.y;

		if (notCopy) {
			c2d.fillRect(p.min.x, p.min.y, p.max.x, p.max.y);
		}

		c2d.putImageData(d, x,y, p.min.x, p.min.y, p.max.x, p.max.y);
	} else {
		if (notCopy) {
			c2d.fillRect(0,0, canvas.width, canvas.height);
		}

		c2d.putImageData(d, x,y);
	}
}

function fillCheck() {
var	c = draw.history.cur()
,	d = c.data
,	i = d.length
	;

	while (--i) if (d[i] != d[i%4]) {
		return 0;	//* <- drawn content confirmed
	}

	return 1;		//* <- fill flood confirmed
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

			while (i--) if (i%4 != 3) {
				d.data[i] = 255 - d.data[i];	//* <- modify current history point, no push
			}
		} else {
		var	hw = d.width
		,	hh = d.height
		,	w = canvas.width
		,	h = canvas.height
		,	hr = (i == -2)
		,	l = (hr ? w : h)/2
		,	j,k,m,n,x,y
			;

			if (hr) used.flip_h = 'Hor.Flip';
			else	used.flip_v = 'Ver.Flip';

			x = canvas.width; while (x--) if ((!hr || x >= l) && x < hw) {
			y = canvas.height; while (y--) if ((hr || y >= l) && y < hh) {
				m = (hr ? w-x-1 : x);
				n = (hr ? y : h-y-1);
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

function clearFill(c) {
var	d = c.ctx || (c.ctx = c.getContext('2d'));
	d.fillStyle = 'white';
	d.fillRect(0,0, c.width, c.height);

	return d;
}

function pickColor(evt, e, keep) {
	// evt = evt || window.event;	//* <- breaks getting data without cursor when picking color from canvas

	if (e && e.ctx) c = e; else
	if (evt) {
		if (e && e.length) d = e; else
		if (evt === 1) keep = 1; else
		if (evt.ctx) c = evt; else
		if ((e = evt.target) && e.ctx) c = e;
	}

//* from gradient palette:

	if (c) {
		eventStop(evt);

		if (noBorderRadius) {
		var	d = evt
		,	e = d.target
		,	x = orz(d.x)
		,	y = orz(d.y)
			;

			do {
				x -= CANVAS_BORDER + orz(e.style.left);	//* <- left margins fix for o11-12
				y -= CANVAS_BORDER + orz(e.style.top);
				e = e.parentNode;
			} while (e && e.style.left);
		} else {
			d = getOffsetXY(c);
			x = evt.pageX - CANVAS_BORDER - d.x;
			y = evt.pageY - CANVAS_BORDER - d.y;
		}

	var	w = c.width
	,	h = c.height
		;

		if (x < 0) x = 0; else if (x >= w) x = w-1;
		if (y < 0) y = 0; else if (y >= h) y = h-1;

		d = c.ctx.getImageData(x,y, 1,1).data;
		c = 0;
	} else

//* from drawing container:

	if (!d) {
		c = (Math.floor(draw.o.x) + Math.floor(draw.o.y)*canvas.width)*4;
		d = draw.history.cur().data;
	}

	if (d) {
	var	c = rgb2hex(d, c);

		if ((e = keep) && e.tagName) {
			e.style.backgroundColor = c;
			e.rgbArray = hue = c = d;
		}

		return (
			keep
			? c
			: updateColor(c, (!evt || evt.which != 3) ? 0 : 1)
		);
	}
}

//* Color conversions *--------------------------------------------------------

function rgb2hex(a, i) {
	if (a && (k = a.length)) {
	var	c = 0
	,	i = orz(i)
	,	k = Math.min(k, i+3)
		;

		for (; i < k; i++) {
			c = c*256 + a[i];
		}

		c = c.toString(16);
	} else {
		c = '';
	}

	while (c.length < 6) {
		c = '0'+c;
	}

	return '#'+c;
}

function hex2fix(v) {
	v = '#'+trim(v, '#');

	if (v.length == 2) {
		v += repeat(v[1], 5);
	} else
	if (regHex3.test(v)) {
		v = v.replace(regHex3, '#$1$1$2$2$3$3');
	}

	return (
		regHex.test(v)
		? v.toLowerCase()
		: false
	);
}

//* Layout changes *-----------------------------------------------------------

function showInfo() {
	if (
		getElemById('colors').style.display
	==	getElemById('info').style.display
	) {
		return;
	}

	toggleView('colors');
	setClass(getElemById('buttonH'), toggleView('info') ? 'button' : 'button-active');
}

function fpsCount() {
	fps = ticks;
	ticks = 0;
}

function setToolHue(redraw) {
var	a = tool.color.split(regCommaSplit).map(orz)
,	i = a.length
	;

	while (--i) if (a[i] != a[0]) {
	var	i = a.length
	,	j = Math.min.apply(null, a)
	,	k = Math.max.apply(null, a)-j
		;

		while (i--) {
			a[i] -= j;
		}

		if (k < 255) {
			i = a.length;
			j = 255/k;

			while (i--) if (a[i] > 0) {
				a[i] = Math.floor(a[i]*j);
			}
		}

		i = a.length;

		while (i--) if (!hue || a[i] != hue[i]) {
			hue = a;

			if (
				redraw
			&&	(i = getElemById('color-wheel-box'))
			) {
				i.redrawBoxGradient(hue);
			}

			return;
		}

		return;
	}
}

function updateColor(value, i) {
var	t = tools[i || 0]
,	c = getElemById('color-text')
,	v = value || c.value
	;

	if (regInt3.test(v)) {
	var	a = (t.color = v).split(regCommaSplit);
		v = '#';

		for (i in a) {
			v += (
				((a[i] = Math.min(255, parseInt(a[i])).toString(16)).length == 1)
				? '0'+a[i]
				: a[i]
			);
		}
	} else
	if (v = hex2fix(v)) {
		if (value != '') {
			t.color = (
				parseInt(v.substr(1,2), 16)
			+', '+	parseInt(v.substr(3,2), 16)
			+', '+	parseInt(v.substr(5,2), 16)
			);
		}
	} else {
		return c.style.backgroundColor = 'red';
	}

	if (t == tool) {
		c.value = v;
		c.style.backgroundColor = '';
		setToolHue(1);
	}

//* put on top of history palette:

var	p = palette[i = c = 0]
,	k = p.length
	;

//* remove duplicates, count changes:

	while (1 < --k) if (hex2fix(p[k]) == v) {
		p.splice(k, 1);
		++c;
	}

//* insert new value:

	if (p.indexOf(v) < 0) {
		p.unshift(v);
		k = PALETTE_COL_COUNT*9;
		++c;

		if (p.length > k) {
			p.length = k;
		}
	}

	if (c) {
		if (i == select.palette.value) updatePalette();
		if (LS) LS[HP] = JSON.stringify(p);
	}

//* update buttons:

var	c = 0
,	a = t.color.split(regCommaSplit)
,	e = getElemById((t == tool) ? 'colorF' : 'colorB')
	;

	for (i in a) {
		c += parseInt(a[i]);
	}

//* inverted font color:

	e.style.color = (c > 380 ? '#000' : '#fff');
	e.style.background = 'rgb(' + t.color + ')';

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

		while (i--) {
			r[i] = Math.round(from[i]*k + to[i]*j);
		}

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

		if (a < 0) {
			a += m;
		}

		while (a > d) {
			a -= d;
			++i;
		}

		return linearBlend(
			rgb[i]
		,	rgb[++i < l ? i : 0]
		,	a,d
		);
	}

	function getRainbowBoxGradientPixel(x,y, w,h, sat) {
	var	i = 0
	,	d = w/l
		;

		while (x > d) {
			x -= d;
			++i;
		}

	var	c = linearBlend(
			rgb[i]
		,	rgb[++i < l ? i : 0]
		,	x,d
		);

		if (d = sat[1]) {
			c = linearBlend(gray[1], c, sat[0], d);
		}

		if (y == y2) {
			return c;
		}

		return linearBlend(
			c
		,	gray[y < y2 ? 0 : 2]
		,	Math.abs(y-y2)
		,	y2
		);
	}

	function pickHue(evt) {
		evt = evt || window.event;

		if (
			(
				evt.type === 'mousemove'
			||	evt.type === 'pointermove'
			)
		&&	(!draw.target || draw.target !== evt.target)
		) {
			return;
		}

		eventStop(evt).preventDefault();

	var	hue = pickColor(
			evt
		,	draw.target || (draw.target = getElemById('color-wheel-round'))
		,	getElemById('color-wheel-hue')
		);

		drawGradient(getElemById('color-wheel-box'), getBoxGradientPixel, hue);
	}

	function pickCorner(evt) {
		evt = evt || window.event;

		pickColor(evt, evt.target.rgbArray);
	}

	function redrawBoxGradient(hue) {
	var	e = getElemById('color-wheel-hue');
		e.style.backgroundColor = rgb2hex(hue);
		e.rgbArray = hue;
		drawGradient(getElemById('color-wheel-box'), getBoxGradientPixel, hue);
	}

var	pt = getElemById('palette-table')
,	c = select.palette.value
,	p = palette[c]
	;

	if (LS) {
		LS[LP] = c;
	}

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
			setContent(pt, getSliderHTML('S', -1));
			setSliderProps('S'), updateSliders(getElemById('rangeS'));
			setId(c = cre('canvas', pt), 'gradient');

		var	x = c.width = 300
		,	y = c.height = 133
		,	y2 = Math.floor(y/2)
			;

			(c.updateSat = function (sat) {
				drawGradient(c, getRainbowBoxGradientPixel, [sat, isNaN(sat) ? 0 : RANGE[0].max]);
			})();

			c.setAttribute('onscroll', f);
			c.setAttribute('oncontextmenu', f);
			c.addEventListener((isPointerEventSupported ? 'pointerdown' : 'mousedown'), pickColor, true);
		} else
		if (c == 'w') {
		var	border = CANVAS_BORDER
		,	pad = 0
		,	p = (pad + border)*2
		,	outerWidth = 300 - p
		,	outerHeight = 178 - p
		,	outerDiam = Math.min(outerWidth, outerHeight)
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

			if (!hue) {
				hue = rgb[0];
			}

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

				for (var j = 0; j < k; j++) {
					b.style[q[j]] = 0;
				}

				if (i < gray.length) {
					q = gray[i];
				} else {
					q = hue;
					setId(b, p+'-hue');
				}

				b.style.backgroundColor = rgb2hex(b.rgbArray = q);
				b.style.width = b.style.height = w+'px';
			}
		} else {
			c = 'TODO';
		}

		if (!c.tagName) {
			setContent(pt, c);
		}
	} else {
	var	tbl = cre('table', clearContent(pt))
	,	tr, td, fill
	,	t = ''
	,	colCount = 0
	,	autoRows = true
		;

		for (i in p) if (p[i][0] == '\n') {
			autoRows = false;

			break;
		}

		for (i in p) {
			c = p[i];
			if (c[0] == '\n' || !colCount) {
				colCount = PALETTE_COL_COUNT;
				tr = cre('tr', tbl);
				tr.textContent = '\n';
			}

			if (c[0] == '\t') {
				t = c.slice(1);					//* <- title, no text field
			} else
			if (c[0] == '\n') {
				if (c.length > 1) t = c.slice(1);		//* <- new line, title optional
			} else {
				td = cre('td', tr);
				td.textContent = '\n';				//* <- if none else - empty spacer

				if (
					c.length > 1
				&&	c[0] == '#'
				) {
				var	v = (
						c.length < 7
						? (
							c.length < 4
							? parseInt((c += repeat(c[1], 5))[1], 16)*3
							: (
								parseInt(c[1], 16)
							+	parseInt(c[2], 16)
							+	parseInt(c[3], 16)
							)
						)*17
						: (
							parseInt(c.substr(1,2), 16)
						+	parseInt(c.substr(3,2), 16)
						+	parseInt(c.substr(5,2), 16)
						)
					);

					setClass(fill = cre('div', td), v > 380 ? 'palettine' : 'paletdark');
					setEvent(fill, 'onclick', 'updateColor("'+c+'",0);');
					setEvent(fill, 'oncontextmenu', 'updateColor("'+c+'",1); return false;');
					fill.title = c+(t ? (' ('+t+')') : '');
					td.style.backgroundColor = c;		//* <- color field
				} else
				if (c) {
					setClass(td, 't').textContent = t = c;	//* <- title + text field
				}

				if (autoRows) {
					--colCount;
				}
			}
		}
	}
}

//* safe palette constructor, step recomended to be: 1, 3, 5, 15, 17, 51, 85, 255
function generatePalette(p, step, slice) {
	p = palette[p];

	if (!p) {
		return;
	}

var	letters = [0,0,0];
var	len = p.length;

	if (len) {
		p[len] = '\t';
		p[len+1] = '\n';
	}

	while (letters[0] <= 255) {
		p[len = p.length] = '#';

		for (var i = 0; i < 3; i++) {
		var	hex = letters[i].toString(16);

			if (hex.length == 1) {
				hex = '0'+hex;
			}

			p[len] += hex;
		}

		letters[2] += step;

		if (letters[2] > 255) {
			letters[2] = 0;
			letters[1] += step;
		}

		if (letters[1] > 255) {
			letters[1] = 0;
			letters[0] += step;
		}

		if (
			(
				letters[1] == 0
			||	(letters[1] == step * slice)
			)
		&&	letters[2] == 0
		) {
			p[len+1] = '\n';
		}
	}
}

function getSliderHTML(b,z) {
var	i = SLIDER_LETTERS.indexOf(b)
,	j
,	r = RANGE[i > 0 ? i : 0]
,	k = (i < 0 ? '' : ' ['+SLIDER_LETTERS[i]+']')
,	s = '';

	for (j in r) {
		s += '" '+j+'="'+r[j];
	}

	return (
		'<div class="slider">'
	+		'<div id="slider'+b+'">'
	+			'<div class="slider-range">'
	+				'<input type="range" id="range'+b
	+				'" onChange="updateSliders(this)'+s
	+				'" value="'
	+				(z > 0 ? r.min : r.max)
	+				'">'
	+			'</div>'
	+		'</div>'
	+		'<label>'
	+			'<div class="slider-title">'
	+				(i < 0 ? lang.sat : lang.tool[b])
	+			'</div>'
	+			k
	+		'</label>'
	+	'</div>'
	);
}

function setSliderProps(b) {
var	r = 'range'
,	s = 'slider'
,	t = 'text'
,	y = 'type'
,	e = getElemById(r+b)
	;

	if (e[y] != r) {
		e.setAttribute(y, t);
	} else {
		setId(e = cre('input', getElemById(s+b)), (e[y] = t)+b);
		setEvent(e, 'onchange', 'updateSliders(this)');
	}

	r = RANGE[Math.max(SLIDER_LETTERS.indexOf(b), 0)];
	e.title = r.min+' - '+r.max;
}

function updateSlider(i,e) {
var	k = (e ? i : SLIDER_LETTERS[i])
,	s = getElemById('range'+k)
,	t = getElemById('text'+k) || s
,	r = RANGE[e ? 0 : i]
,	f = (r.step < 1)
,	v = (e ? parseFloat(e.value) : tool[i = SLIDER_NAMES[i]])
	;

	if (isNaN(v)) {
		v = 1;
	}

	if (
		f
	&&	v > r.max
	&&	v.toString().indexOf('.') < 0
	) {
		v = '0.'+v;
	}

	if (v < r.min) v = r.min; else
	if (v > r.max) v = r.max;

	if (f) {
		v = parseFloat(v).toFixed(2);
	}

	if (e && (e = getElemById('gradient'))) {
		e.updateSat(v);
	} else {
		tool[i] = v;
	}


	s.value = t.value = v;
	return false;
}

function updateSliders(s) {
	if (s && s.id) {
	var	prop = s.id[s.id.length-1]
	,	i = SLIDER_LETTERS.indexOf(prop)
		;

		if (i < 0) {
			return updateSlider(prop, s);
		}

		tool[SLIDER_NAMES[i]] = parseFloat(s.value);

		return updateSlider(i);
	}

	if (s) {
		updateSlider(s);
	} else {
		for (i in SLIDER_NAMES) updateSlider(i);
	}

	if (draw.o.length) {
		drawEnd();
		s = tool.width+4;
		c2d.putImageData(draw.history.cur(), 0,0, draw.o.x - s/2, draw.o.y - s/2, s, s);
		drawCursor();
	}

	return false;
}

function updateShape(s) {
	if (!isNaN(s)) {
		select.shape.value = s;
		s = 0;
	}

var	c = select.shapeClass[s = orz((s||select.shape).value)]
,	sf = select.shapeFlags[s]
,	i,j = []
	;

	if (
		!draw.active
	&&	!draw.step
	) {
		draw.shape = s;
		draw.shapeFlags = sf;
	}

	for (i in MODE_LABELS) if (c != i) {
		j.push(MODE_LABELS[i]);
	}

	setClass(getElemById('bottom'), j.join(' '));
	setClass(getElemById('texts'), ((sf & 32) ? 'texts' : 'sliders'));

	for (i in {L : 0, U : 1}) if (j = getElemById('check'+i)) {
		s = j.firstElementChild;

		do {
			if (s.className.substr(-1) == MODE_LABELS[c]) {
				j.title = s.title;

				break;
			}
		} while (s = s.nextElementSibling);
	}

	return false;
}

function updateTextAlign(e) {
	draw.textAlign = getLastWord(e.id);
}

function updateTextStyle(e) {
var	i = orz(e.value);

	if (i) {
		getElemById('text-font').value = select.textStylePreset[i];
		e.value = 0;
	}
}

function checkTextStyle(e, test) {
var	v = (e.value ? (e.value = getSpaceReduced(e.value)) : e);

	if (test) {
		c2d.font = v;
		return (c2d.font === v);
	}

	return false;
}

function updateHistoryButtons() {
var	a = {R : draw.history.last, U : 0}
,	b = 'button'
,	d = b+'-disabled'
,	e
	;

	for (i in a) {
		setClass(getElemById(b+i), (draw.history.pos == a[i] ? d : b));
	}

	cue.upd = {J : 1, P : 1};
}

function updateSaveFileSize(e) {
var	i = e.id.slice(-1);

	if (cue.upd[i]) {
		cue.upd[i] = 0;

		e.title = (
			e.title.replace(regTailBrackets, '')
		+	' ('
		+	(canvas.toDataURL({J : IJ, P : ''}[i]).length / 1300).toFixed(0)
		+	' KB)'
		);
	}
}

function updateResize(e) {
	if (e.value == 'auto crop') {
		if (d = draw.history.cur()) {
		var	c = c2d.canvas
		,	d,a = d.data
		,	i = a.length
		,	j = i-4
		,	k,z,x,y = 0
		,	w = d.width
		,	h = 0
		,	b = w*4
			;

//* from bottom right:
		up:	while (--i) if (a[i] != a[i%4 + j]) {
				h = Math.floor(i/b) + 1;
//* from top left:
			down:	for (i = 0; i < j; i++) if (a[i] != a[i%4]) {
					h -= y = Math.floor(i/b);

					break down;
				}
//* from y to y+h:
				w = b;
			rtl:	while (--w)
				for (i = h; i--;) if (a[k = w + (y+i)*b] != a[k%4 + j]) {
					w = Math.floor(w/4) + 1;

					break rtl;
				}

			ltr:	for (x = 0; x < b; x++)
				for (i = h; i--;) if (a[k = x + (y+i)*b] != a[k%4]) {
					w -= x = Math.floor(x/4);

					break ltr;
				}
//* draw cut box:
				if (x || y || w != c.width || h != c.height) {
					c2d.save();
					for (i in DRAW_HELPER) c2d[i] = DRAW_HELPER[i];
					i = DRAW_PIXEL_OFFSET;
					c2d.translate(i,i);
					c2d.beginPath();
					c2d.moveTo(x, 0), c2d.lineTo(x, c.height), z = x+w;
					c2d.moveTo(z, 0), c2d.lineTo(z, c.height);
					c2d.moveTo(0, y), c2d.lineTo(c.width, y), z = y+h;
					c2d.moveTo(0, z), c2d.lineTo(c.width, z);
					c2d.stroke();
					c2d.restore();
//* save:
					if (confirm('x = '+x+', y = '+y+'\nw = '+w+', h = '+h)) {
						c2s.putImageData(d, 0,0);
						d = c2s.getImageData(x,y, w,h);
						updateDimension(w,h);
						c2d.putImageData(d, 0,0);
						historyAct();
					}
				} else {
					alert(lang.no.change);
				}

				break up;
			}
		}
		if (!h) alert(lang.no.drawn);
		e.value = mode.scalePrev || 'top left';
	} else mode.scalePrev = e.value;
	mode.scale = (e.value.indexOf(' ') < 0 ? e.value.substr(-1) : '');
}

function updateDimension(e,h) {
var	c = canvas;

	if (e && e.id) {
	var	i = mode.scale
	,	a = (i == 'W' || i == 'H' ? c.width/c.height : 0)
	,	i = getLastWord(e.id)
	,	v = orz(e.value)
		;

		cnvHid[i] = c[i] = e.value = v = (
			v < (b = select.imgLimits[i][0]) ? b : (
			v > (b = select.imgLimits[i][1]) ? b : v)
		);

		if (a) {
			if (i == 'width') {
				a = v/a;
				b = 'height';
			} else {
				a *= v;
				b = 'width';
			}

			cnvHid[b] = c[b] = getElemById('img-'+b).value = Math.max(1, Math.round(a));
		}

		historyAct(mode.scale ? 'rescale' : 'resize');
	} else
	if (e || h) {
		if (h) {
			e = {
				width : e
			,	height : h
			};
		}

		for (i in select.imgSizes) {
			getElemById('img-'+i).value = cnvHid[i] = c[i] = orz(e[i]);
		}
	}

	if (!v || i == 'height') {
	var	b = getElemById('buttonH')
	,	c = getElemById('colors').style
	,	i = getElemById('info').style
		;

		if (canvas.height < BOTH_PANELS_HEIGHT) {
		var	a = (b.className.indexOf('active') >= 0)
		,	v = 'none'
			;

			c.display = (a ? v : '');
			i.display = (a ? '' : v);
		} else {
			c.display = i.display = '';
			setClass(b, 'button-active');
		}
	}

	c = container.style;
	b = 'minWidth';
	a = (v = canvas.width+getElemById('right').offsetWidth+14)+'px';

	if (c[b] != a) {
		c[b] = a;

		if (a = outside[i = 'resize_style']) {
			v += 24;

			if (
				(e = outside.resize_min_id)
			&&	(e = getElemById(e))
			&&	(e = e.offsetWidth)
			&&	e > v
			) {
				v = e;
			}

			c = getElemById(i) || setId(cre('style', getElemById()), i);
			c.innerHTML = a+'{max-width:'+v+'px;}';
		}
	}
}

function toolTweak(prop, value) {
var	i = SLIDER_LETTERS.indexOf(prop);

	if (i < 0) {
		return alert(lang.bad_id+'\nNo '+prop+' in '+SLIDER_LETTERS), false;
	}

var	b = SLIDER_NAMES[i];

	if (
		value === Infinity
	||	value === -Infinity
	) {
	var	v = new Number(tool[b]), s = RANGE[i].step;
		tool[b] = (value < 0 ? v-s : v+s);
	} else {
		tool[b] = value;
	}

	return updateSliders(i);
}

function toolSwap(back) {
var	t,i = orz(back);

	if (i > TOOLS_REF.length || i < 0) {
		for (t in TOOLS_REF)
		for (i in TOOLS_REF[t]) {
			tools[t][i] = TOOLS_REF[t][i];
		}

		for (i in select.lineCaps) {
			select[i].value = 0;
		}

		if (mode.shape) toggleMode(1);
		if (mode.step) toggleMode(2);

		updateShape(0);
		tool.width = DEFAULT_TOOL_WIDTH;
	} else
	if (i) {
		back = TOOLS_REF[i-1];
		for (i in back) {
			tool[i] = back[i];
		}
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
	if (isNaN(i)) {
		i = MODE_LETTERS.indexOf(i);
	}

	if (i < 0 || i >= MODE_NAMES.length) {
		alert(
			lang.bad_id
		+	'\nNo '+i
		+	' in '+MODE_NAMES.length
		);
	} else {
	var	n = MODE_NAMES[i], v = mode[n];

		if (n == 'debug' && text.debug.textContent.length) {
			text.debug.textContent = '';

			interval.fps
			? clearInterval(interval.fps)
			: (interval.fps = setInterval(fpsCount, 1000));
		}

		if (!keep) {
			v = mode[n] = !v;
		}

		setClass(getElemById('check'+MODE_LETTERS[i]), v ? 'button-active' : 'button');
	}

	return false;
}

//* Positioning *--------------------------------------------------------------

function updateViewport(delta) {
var	i
,	s = ''
,	t = ''
,	target = draw.container
	;

	if (isNaN(delta)) {
		draw.angle = 0;
		draw.pan = 0;
		draw.zoom = 1;
		t = 'none';
	} else
	if (draw.turn.pan) {
		draw.pan = {};

		for (i in draw.o) {
			draw.pan[i] = draw.o[i] - draw.turn.origin[i] + draw.turn.prev[i];
		}
	} else
	if (draw.turn.zoom) {
		i = draw.turn.prev * (draw.turn.origin + delta) / draw.turn.origin;

		if (i > 4) i = 4; else
		if (i < .25) i = .25;

		draw.zoom = i;
	} else {
		draw.angle = draw.turn.prev + delta;

		if (draw.a360 = Math.floor(draw.angle*180/Math.PI)%360) {
			draw.aRad = draw.a360/180*Math.PI;
		} else {
			draw.angle = draw.a360 = draw.aRad = 0;
		}
	}

	if (draw.pan) t += ' translate('+draw.pan.x+'px,'+draw.pan.y+'px)';
	if (draw.angle) t += ' rotate('+draw.a360+'deg)';
	if (draw.zoom != 1) t += ' scale('+(draw.zoom)+')';

	if (noTransformByProp) {
		if (t.indexOf('(') > 0) {
			for (i in CSS_PREFIXES) {
				s += CSS_PREFIXES[i]+'transform:'+t+';';
			}
		}

		target.setAttribute('style', s);
	} else {
		for (i in CSS_PREFIXES) {
			target.style[CSS_PREFIXES[i]+'transform'] = t;
		}
	}

	updateDebugScreen();
}

function updateDebugScreen(lsid, refresh) {
	if (lsid) {
		if (
			refresh
		&&	!(
				(i = text.debug.innerHTML)
			&&	(i.length > 0)
			)
		) {
			return;
		}

	var	i = s = 0
	,	j = ''
	,	k = !CR[0]
	,	n = CR.length
	,	f = '<a href="javascript:void(|.'
	,	g = '))">'
	,	p = f+'savePic('
		;

		if (k) for (k = ''; ++i < n;) {
			d = (i == 1 && refresh == 1);
			a = 'r'+i;

			k += (
				'{.whatSaved('+i+');'
			+	(
					CR[i].keepSavedInOldFormat
					? '<span style="background-color:#ace">'+a+'</span>'
					: a
				)
			+	'}'
			);

			r = LS[CR[i].R];
			t = LS[CR[i].T];
			s += getSaveLSDict(i,0,1);

			j += (
				(j ? '<br>' : '')
			+	'Save '+i+' ['+f+'unsave('+i+g+'delete</a>] time: '
			+	(i == lsid || d
					?'<span style="background-color:#'
			+			(['f44','5ae','5ea','feb'][d ? 2 : orz(refresh)]||'aaa')
			+		'">'+t+'</span>'
					:t)
			+	(t ? ' = '+unixDateToHMS(orz(t.split('-')[1]),0,1) : '')
			+	(r ? ', pic size: '+getFormattedNum(r.length)
			+		' ['+p+'0,'+i+g+'save</a>'
			+		', '+p+'3,'+i+g+'load</a>]' : '')
			+	(d			? ' ← saved' :
				(i == lastUsedSaveSlot	? ' ← last used' :
				(i == lsid		? ' ← shifted up to' : '')))
			);
		}

		text.debug.innerHTML = (
			'<hr>[<a href="javascript:;" onClick="this.parentNode.innerHTML=\'\'; return false;"> x </a>], '
		+	replaceAll(
			replaceAll(
			replaceAll(
			replaceAll(
				'{,0,1;props}'
		+		'{.o;outside}'
		+		'{.mode;mode}'
		+		'LStorage: '+(k ? k+'sum = '+getFormattedNum(s)+' bytes' : CR)
		+		', Save file as: '+(DL || 'new tab')
			, '{', f+'show(|')
			, ';', g)
			, '}', '</a>,\n')
		+		(outside.read ? '' : ', F6=read: <textarea id="|-read">/9.png</textarea>')
		+		'<hr>'+nl2br(getSendMeta(draw.screen()))
		+		'<hr>'+j
		+		'<hr>'
			, '|', NS)
		);
	} else
	if (mode.debug) {
	var	r = '</td></tr>\n<tr><td>'
	,	d = '</td><td>'
	,	a = draw.turn
	,	s = draw.step
	,	t = draw.time
	,	i = isMouseIn()
		;

		text.debug.innerHTML = (
			'<table><tr><td>'
		+	draw.refresh+d+'1st = '+t.all.join(d+'last = ')+d+'fps = '+fps
		+	r+'Not idle'+d+'last start = '+t.activeStart+d+'sum = '+t.sum()+d
		+	r+'Relative'+d+'x = '+draw.o.x   +d+'y = '+draw.o.y   +d+i+(i ? ',rgb = '+pickColor(1) : '')
		+	r+'DrawOfst'+d+'x = '+draw.cur.x +d+'y = '+draw.cur.y +d+'btn = '+draw.btn+', active = '+draw.active
		+	r+'Previous'+d+'x = '+draw.prev.x+d+'y = '+draw.prev.y+d+'chain = '+mode.click
		+ (!s ? '' :
			r+'StpStart'+d+'x = '+s.prev.x+d+'y = '+s.prev.y
		+	r+'Step_End'+d+'x = '+s.cur.x +d+'y = '+s.cur.y
		)
		+	'</td></tr></table>'
		+	showProps(tool,1)
		+	(a ? '<br>turn: '+showProps(a,3) : '')
		);

		++ticks;
	}
}

function updatePosition(evt) {
var	sf = draw.shapeFlags
,	isHandDrawnLine = ((sf & 1) && !mode.shape)
,	isFillOnlyFigure = ((sf & 2) && mode.shape && !mode.step)
,	isMoveToolOrEvenWidthLine = ((sf & 4) || ((draw.active ? c2d.lineWidth : tool.width) % 2))
,	toolOffset = (
		isMoveToolOrEvenWidthLine && !isFillOnlyFigure
		? DRAW_PIXEL_OFFSET
		: 0
	)
,	containerOffset = getOffsetXY(draw.container)
,	i;

	evt = evt || window.event;
	draw.o.x = evt.pageX - CANVAS_BORDER - containerOffset.x;
	draw.o.y = evt.pageY - CANVAS_BORDER - containerOffset.y;

	if (draw.pan && !(draw.turn && draw.turn.pan)) for (i in draw.o) {
		draw.o[i] -= draw.pan[i];
	}

	if (!draw.turn && (draw.angle || draw.zoom != 1)) {
	var	cursorOffset = getCursorRad(2, draw.o.x, draw.o.y);

		if (draw.angle    ) cursorOffset.a -= draw.aRad;
		if (draw.zoom != 1) cursorOffset.d /= draw.zoom;

		draw.o.x = Math.cos(cursorOffset.a) * cursorOffset.d + canvas.width/2;
		draw.o.y = Math.sin(cursorOffset.a) * cursorOffset.d + canvas.height/2;

		toolOffset = 0;
	}

	for (i in draw.o) {
	var	drawingCoordinate = toolOffset + draw.o[i];

		draw.cur[i] = (
			isHandDrawnLine && mode.roughLine
			? getRoughCoordinate(drawingCoordinate)
			: drawingCoordinate
		);
	}
}

function getRoughCoordinate(v) {
	return (
		Math.round(
			(v + ROUGH_LINE_SHIFT)
			/ ROUGH_LINE_DIV
		) * ROUGH_LINE_DIV
		- ROUGH_LINE_SHIFT
	);
}

function getCursorRad(r, x, y) {
	if (draw.turn.pan) {
		return {
			x : draw.o.x
		,	y : draw.o.y
		};
	}

	x = (isNaN(x) ? draw.cur.x : x) - canvas.width/2;
	y = (isNaN(y) ? draw.cur.y : y) - canvas.height/2;

	return (
		r
		? {
			a : Math.atan2(y, x)
		,	d : dist(y, x)
		}
		: draw.turn.zoom
		? dist(y, x)
		: Math.atan2(y, x)
	);
}

function getOffsetXY(e) {
var	x = 0
,	y = 0
	;

	while (e) {
		x += e.offsetLeft;
		y += e.offsetTop;
		e = e.offsetParent;
	}

	return {x : x, y : y};
}

function isMouseIn() {
	return (
		draw.o.x >= 0
	&&	draw.o.y >= 0
	&&	draw.o.x < canvas.width
	&&	draw.o.y < canvas.height
	);
}

//* Save, load, send picture *-------------------------------------------------

function autoSave() {
	if (
		mode.autoSave
	&&	cue.autoSave
	&&	!(cue.autoSave = (draw.active ? -1 : 0))
	) {
		savePic(2,-1);
	}
}

function timeElapsed() {
	text.timer.textContent = unixDateToHMS(timer += 1000, 1);
}

function unixDateToHMS(t,u,y) {
var	d = (
		t
		? new Date(t+(t > 0 ? 0 : new Date()))
		: new Date()
	)
,	t = ['Hours','Minutes','Seconds']
,	u = 'get'+(u ? 'UTC' : '')
	;

	if (y) {
		t = ['FullYear','Month','Date'].concat(t);
	}

	for (i in t) if ((t[i] = d[u+t[i]]()+(y && i == 1 ? 1 : 0)) < 10) {
		t[i] = '0'+t[i];
	}

	d = '-';
	u = (y > 1 ? d : ':');

	return (
		y
		? t[0]+d+t[1]+d+t[2]+(y > 1 ? '_' : ' ')+t[3]+u+t[4]+u+t[5]
		: t.join(u)
	);
}

function getSendMeta(sz) {
var	i,j = ', '
,	t = +new Date
,	u = []
,	a = [
		'open_time: '+t0+'-'+t
	,	'draw_time: '+draw.time.all.join('-')
	,	'active_time: '+draw.time.sum()
	,	'app: '+NS+' '+INFO_VERSION
	,	'pixels: '+canvas.width+'x'+canvas.height
	,	'bytes: '+(
			sz || [
				'png = '+ canvas.toDataURL().length
			,	'jpg = '+ canvas.toDataURL(IJ).length
			].join(j)
		)
	];

	for (i in used) {
		u.push(used[i].replace(/[\r\n]+/g, j));
	}

	if (u.length) {
		a.push('used: '+u.join(j));
	}

	return a.join('\n');
}

function getSaveLSKeys(i) {
	if (
		i > 0
	&&	(r = CR[i])
	) {
	var	r = r.R
	,	n = r.length
	,	i = LS.length
	,	j = []
	,	k
		;

		while(i--) if (
			(k = LS.key(i))
		&&	(k == r || (!orz(k[n]) && k.substr(0,n) == r))
		) {
			j.push(k);
		}

		return j.sort();
	}

	return [];
}

function getSaveLSDict(i, swap, sum) {
var	j = getSaveLSKeys(i)
,	k,l,m = 0
,	n = (sum ? 0 : CR[i].R.length)
,	d = {}
	;

	if (j.length > 0) for (i in j) {
		l = LS[k = j[i]];
		m += (sum ? l : d[swap ? CR[swap].R+k.substr(n) : k] = l).length;
	}

	return (
		sum ? m : {
			dict : d
		,	sum : m
		}
	);
}

function saveClear(i, warn) {
var	j = getSaveLSKeys(i);

	if (
		j.length > 0
	&&	(!warn || confirm('Confirm deleting LS keys:\n\n'+j.join('\n')))
	) {
		for (i in j) {
			LS.removeItem(j[i]);
		}

		return j.length;
	}

	return 0;
}

function saveShiftUp(i) {
	if (
		i > 0
	&&	i < CR.length-1
	) {
	var	n = i+1
	,	d = getSaveLSDict(i
	,	n)
	,	m = d.sum
	,	d = d.dict
		;

		if (!m) {
			return 0;
		}

		saveClear(i);
		saveClear(n);	//* <- have to care about LS size limit

		for (i in d) {
			LS[i] = d[i];
		}
	}

	return m || 0;
}

function saveShiftUpTo(i, swap) {
var	d = getSaveLSDict(i
,	swap = orz(swap))
,	m = d.sum
,	d = d.dict
	;

	if (!swap) {
	var	j = 1;

		while (getSaveLSDict(j,0,1) > 0) {
			j++;		//* <- condense free slots
		}

		if (i > j) {
			i = j;
		}
	}

	while (i-- > swap) {
		m += saveShiftUp(i);	//* <- destroys top slot old content
	}

	if (swap) for (i in d) {
		LS[i] = d[i];		//* <- carefully copy all fields, even if unaware what other app versions have saved there
	}

	return m || 0;			//* <- max size proven to be allowed
}

function saveDL(dataURI, suffix) {
	if (DL) {
	var	URL = window.URL || window.webkitURL
	,	a = cre('a', getElemById())
		;

		if (URL && URL.createObjectURL) {
		var	type = dataURI.split(';', 1)[0].split(':', 2)[1]
		,	data = dataURI.slice(dataURI.indexOf(',')+1)
		,	data = Uint8Array.from(MODE_NAMES.map.call(atob(data), function(v) { return v.charCodeAt(0); }))
		,	blob = URL.createObjectURL(new Blob([data], {'type' : type}))
			;

			a.href = ''+blob;
		} else {
			a.href = ''+dataURI;
		}

		a[DL] = unixDateToHMS(0,0,2)+suffix;
		a.click();

		setTimeout(function() {
			if (blob) URL.revokeObjectURL(blob);
			a.parentNode.removeChild(a);
		}, 12345);
	} else {
		window.open(dataURI, '_blank');
	}
}

function confirmShowTime(la, s) {
	if (s) {
	var	a = s.split('-', 2).map(orz)
	,	k = a.length
	,	r = la.join(TITLE_LINE_BREAK)
	,	t,i
		;

		for (i = 0; i < k; i++) {
			r += TITLE_LINE_BREAK+(
				(t = a[i])
				? unixDateToHMS(t,0,1)
				: '-'
			);
		}
	} else {
		r = la[0];
	}

	return confirm(r);
}

function savePic(dest, lsid) {
var	t = (lsid > 0)
,	a = (lsid < 0)
,	b = 'button'
,	c,d,e,i,j
	;

	draw.screen();

	switch (dest) {

//* save to file:

	case 0:
	case 1:
		saveDL(
			c = (
				t
				? LS[CR[lsid].R]
				: canvas.toDataURL(dest ? IJ : '')
			)
		,	'_'+(
				t
				? LS[CR[lsid].T].split('-', 2)
				: draw.time.all
			).join('-')+(dest ? '.jpg' : '.png')
		);

		break;

//* save to memory:

	case 2:
		c = canvas.toDataURL();

		if (!c || fillCheck())	return a ? c : alert(lang.no.drawn);
		if (!LS)		return a ? c : alert(lang.no.LS);
		if (LS[CR[1].R] === c)	return a ? c : alert(lang.no.change);

		i = 1;
		j = CR.length;

		while (++i < j) if (LS[CR[i].R] === c) {
			saveShiftUpTo(i,1);
			updateDebugScreen(i,1);

			return a ? c : alert(lang.found_swap);
		}

		if (
			lsid
		||	confirmShowTime(lang.confirm.save, LS[CR[1].T])
		) {
			t = (
				(dt = draw.time).all.join('-')
			+	(dt.activeStart ? '='+dt.sum() : '')
			+	(used.read ? '-'+used.read : '')
			);

			d = saveShiftUpTo(i = j-1);

			while (--j) try {
				LS[CR[1].R] = c;
				LS[CR[1].T] = t;

				break;

			} catch (error) {
				if (c.length + t.length > d) {
					return a ? c : alert(lang.no.space+'\n'+lang.err_code+': '+error.code+', '+error.message);
				}

				saveClear(1);
				saveClear(j);	//* <- probably maxed out allowed LS capacity, try to clean up from oldest slots first
			}

			setClass(getElemById(b+'L'), b);
			getElemById('saveTime').textContent = unixDateToHMS();
			cue.autoSave = lastUsedSaveSlot = 0;
			updateDebugScreen(i,1);
		}

		break;

//* load from memory:

	case 3:
		if (!LS) return alert(lang.no.LS);

		function seekSavePos(i) {
			if (!(i > 0 && i < j)) {
				i = 1;
			}

			do if (e = LS[CR[i].T]) {
				d = LS[CR[i].R];
				t = e;

				if (t && d && d != c) {
					return i;
				}
			} while (++i < j);

			return 0;
		}

		c = canvas.toDataURL();
		j = CR.length;
		i = seekSavePos(lsid || lastUsedSaveSlot+1) || seekSavePos();
		lastUsedSaveSlot = 0;

		setClass(getElemById(b+'L'), b+(t ? '' : '-disabled'));

		if (!t) return;
		if (!i) return alert(lang.no.change);

		if (lsid || confirmShowTime(lang.confirm.load, t)) {
			draw.history.storeTime();

		var	dt = draw.time
		,	a = t.split('-')
		,	t = dt.all = a.slice(0,2).map(orz)
			;

			dt.activeStart = dt.activeSum = 0;

			if (a.length > 2) {
				used.read = 'Read File: '+a.slice(2).join('-').replace(/^[^:]+:\s+/, '');
			}

			if (a[1].indexOf('=') >= 0) {
				dt.activeSum = orz(a[1].split('=', 2)[1]);
			}

			if (!dt.activeSum) {
				dt.activeSum = t[1]-t[0];
			}

			t = t[1];
			a = getElemById('saveTime');
			a.textContent = unixDateToHMS(t);
			a.title = new Date(t);

			readPic(d,i);
			used.LS = 'Local Storage';
		}

		break;

//* load file:

	case 4:
		if (
			(a = lsid)
		||	(
				(outside.read || (outside.read = getElemById('read')))
			&&	(a = outside.read.value)
			)
		) {
			draw.time.act(1);
			used.read = 'Read File: '+readPic(a);
		}

		break;

//* send to server:

	default:
		if (dest)		alert(lang.bad_id+'\n\nid='+dest+'\nautosave='+a); else
		if (!outside.send)	alert(lang.no.form); else
		if (fillCheck())	alert(lang.no.drawn); else {
		var	confirmationText = lang.confirm.send;
			a = select.imgLimits;

			for (i in a) if (
				canvas[i] < a[i][0]
			||	canvas[i] > a[i][1]
			) {
				j = a.width.length;
				confirmationText = lang.confirm.size.map(
					function(v,i) {
						return v+(i < j ? a.width[i]+'x'+a.height[i] : '');
					}
				).join('');

				break;
			}
		}

		if (confirmationText) {
		var	pngData = savePic(2,-1)
		,	jpgData
			;

			e = pngData.length;
			c = canvas;
			d = select.imgSizes;
			c = c.width * c.height;
			d = d.width * d.height;

		var	isSendingAsJpg = (
				(i = outside.jpg)
			&&	e > i
			&&	((c <= d) || (e > (i *= c/d)))
			&&	e > (t = (jpgData = c.toDataURL(IJ)).length)
			);

		var	dataInBase64 = (
				isSendingAsJpg
				? jpgData
				: pngData
			);

			if (mode.debug) {
				alert(['png limit = '+i, 'png = '+e, 'jpg = '+t].join('\n'));
			}

			function sendAfterConfirmation(data) {

				if ((f = outside.send) && f.tagName) {
					clearContent(f);
				} else {
					setId(e = cre('form', container), 'send');

					if (!f.length || f.toLowerCase() != 'get') {
						e.setAttribute('method', 'post');
					}

					outside.send = f = e;
				}

				a = {txt : 0, pic : 0};

				for (i in a) if (!(a[i] = getElemById(i))) {
					e = a[i] = cre('input', f);
					e.name = i;
					e.hidden = true;
					e.type = 'hidden';
					e.style.display = 'none';
					setId(e, i);
				}

			var	dataSize, dataType, dataEnc, fileExt;

				function setDataVars(data) {
					if (data.type) {
						dataSize = data.size;
						dataType = data.type;
						dataEnc = 'blob';
					} else {
					var	dataEncParts = data.split(',', 1)[0].split(';');
						dataSize = data.length;
						dataType = dataEncParts[0].split(':')[1];
						dataEnc = dataEncParts[1];
					}

					fileExt = dataType.split('/')[1];

					return data;
				}

				if (data.type) {
					setDataVars(data);

				var	fileName = +new Date + '.' + fileExt;
				var	file = new File([data], fileName, {type : dataType});

					a.pic.type = 'file';
					a.pic.value = null;
					a.pic.files = createFileList(file);
				}

//* Fallback to base64 for old Firefox 56:

				if (!(
					data.type
				&&	a.pic.files
				&&	a.pic.files.length > 0
				)) {
					a.pic.type = 'hidden';
					a.pic.value = setDataVars(
						data.type
						? dataInBase64
						: data
					);
				}

				a.txt.type = 'hidden';
				a.txt.value = getSendMeta(dataSize);

				f.encoding = f.enctype = 'multipart/form-data';

				confirmationText = (
					canvas.width + 'x' + canvas.height
				+	', '
				+	dataSize + ' ' + lang.bytes
				+	', '
				+	dataEnc
				+	', '
				+	fileExt.toUpperCase()
				+	'\n\n'
				+	confirmationText
				);

				if (confirm(confirmationText)) {
					try {
						postingInProgress = true;

						if (
							(i = outside.check)
						&&	(e = getElemById(i))
						) {
							e.setAttribute('data-id', f.id);
							e.click();
						} else {
							f.submit();
						}

					} catch (error) {
						console.error(error);

						postingInProgress = false;
					}
				}
			}

//* Use less traffic in modern browsers with blob uploaded as file:

			if (canvas.toBlob) {
				try {
					canvas.toBlob(
						sendAfterConfirmation
					,	isSendingAsJpg ? IJ : ''
					);

					return c;

				} catch (error) {
					console.error(error);
				}
			}

//* Fallback to base64 for old Opera 11-12:

			sendAfterConfirmation(dataInBase64);
		}
	}

	return c;
}

function readPic(s,ls) {
	if (!s || s == 0 || (!s.data && !s.length)) {
		return;
	}

	if (!s.data) {
		s = {
			data : s
		,	name : (0 === s.indexOf('data:') ? s.split(',', 1) : s)
		};
	}

var	e = new Image();

	e.onload = function () {
		try {

	//* throw-away test of data source safety:

			d = cre('canvas');
			d.width = d.height = 1;

			d = d.getContext('2d');
			d.drawImage(e, 0,0, 1,1);
			d.getImageData(0,0, 1,1);

	//* actual work:

		var	d = canvas
		,	i = mode.scale
			;

			if (i) {
				if ((i == 'W' || i == 'H') && (d.width != e.width || d.height != e.height)) {
				var	j = e.width/e.height
				,	j = {
						width  : (i == 'W' ? d.width  : Math.max(1, Math.round(d.height*j)))
					,	height : (i == 'H' ? d.height : Math.max(1, Math.round(d.width/j)))
					};

					updateDimension(j);
				}

				clearFill(d).drawImage(e, 0,0, e.width, e.height, 0,0, d.width, d.height);
			} else {
				updateDimension(e);
				clearFill(d).drawImage(e, 0,0);
			}

			historyAct();
			draw.history.storeTime();
			cue.autoSave = 0;

			if (lastUsedSaveSlot = ls) {
				updateDebugScreen(ls,3);
			}
		} catch (error) {
			alert(lang.err_code+': '+error.code+', '+error.message);
		} finally {
			if (d = e.parentNode) {
				d.removeChild(e);
			}
		}
	}

	draw.container.appendChild(setClickRemove(e));
	e.src = s.data;

	return s.name;
}

//* The only way to change input[type=file] value is with a other FileList instance,
//* and this is currently the only way to construct a new FileList.
//* Source: https://stackoverflow.com/a/50169790

function createFileList(a) {
	a = Array.prototype.slice.call(Array.isArray(a) ? a : arguments);
	for (var c, b = c = a.length, d = !0; b-- && d;) d = a[b] instanceof File;
	if (!d) throw new TypeError('expected argument to FileList is File or array of File objects');
	for (b = (new ClipboardEvent('')).clipboardData || new DataTransfer; c--;) b.items.add(a[c]);
	return b.files;
}

//* Hot keys *-----------------------------------------------------------------

function addEventListeners(e, funcByEventName) {
	for (var i in funcByEventName) {
		try {
			e.addEventListener(i, funcByEventName[i], { capture : true, passive : false });
		} catch (error) {
			console.error(error);

			e.addEventListener(i, funcByEventName[i], true);
		}
	}
}

function prevent(evt) {
	evt = evt || window.event;
	evt.preventDefault();
}

function browserHotKeyPrevent(evt) {
	evt = evt || window.event;

	if (
		isMouseIn()
	||	evt.keyCode == 27
	) {
		evt = eventStop(evt);
		evt.returnValue = false;
		evt.preventDefault();

		return evt;
	}
}

function stopScroll(evt) {
	browserHotKeyPrevent(evt);

	return false;
}

function hotWheel(evt) {
	if (evt = browserHotKeyPrevent(evt)) {
	var	delta = evt.deltaY || evt.detail || evt.wheelDelta;

		toolTweak(selectedSlider, delta < 0 ? Infinity : -Infinity);
		drawMove(evt);

		if (mode.debug) {
			text.debug.innerHTML += '<br>' + [
				'slider = ' + selectedSlider
			,	'type = ' + evt.type
			,	'delta = ' + delta
			].join(',\n');
		}
	}

	return false;
}

function hotKeys(evt) {

	function getKeyCode(s) { return s.charCodeAt(0); }	//* <- alphanumeric hotkey code from first letter

	if (evt = browserHotKeyPrevent(evt)) {
		if (
			!draw.active
		&&	evt.type === 'keydown'
		) {
		var	i,k,n,s = String.fromCharCode(evt.keyCode);

			if (
				!evt.altKey
			&&	!evt.ctrlKey
			&&	!evt.shiftKey
			) {

//* Select tool shape:

				if ((k = SHAPE_HOTKEYS.indexOf(s)) >= 0) {
					updateShape(k);

					return drawMove(evt);
				}

//* Select tool slider, to update using number keys:

				if ((k = SLIDER_LETTERS.indexOf(s)) >= 0) {
					selectedSlider = s;

					for (i in SLIDER_LETTERS) {
						setClass(getElemById('slider' + SLIDER_LETTERS[i]).nextElementSibling, (i == k ? 'active' : ''));
					}

					return drawMove(evt);
				}

//* Update selected tool slider:

			var	keyMinus = (
					evt.keyCode == 173	//* 173=[-]
				||	evt.keyCode == 109	//* 109=[Num -]
				);

			var	keyPlus = (
					evt.keyCode == 61	//* 173=[=]
				||	evt.keyCode == 107	//* 109=[Num +]
				);

				if (keyMinus || keyPlus)  {
					toolTweak(selectedSlider, keyPlus ? Infinity : -Infinity);

					return drawMove(evt);
				}

			var	keyNumber = evt.keyCode - getKeyCode('0');

				if (
					keyNumber >= 0
				&&	keyNumber <= 10
				) {
					k = SLIDER_LETTERS.indexOf(selectedSlider);

					n = (
						!keyNumber && RANGE[k].min > 0
						? 10
						: keyNumber
					);

					n = (
						RANGE[k].step < 1
						? n / 10
						: n > 5
						? (n - 5) * 10
						: n
					);

					toolTweak(selectedSlider, n);

					return drawMove(evt);
				}
			}

			if (evt.altKey)
			switch (evt.keyCode) {
				case getKeyCode('All-default') :	toolSwap(3);		return;
				case getKeyCode('Show-brush-cursor') :	toggleMode('V');	return drawMove(evt);
			} else
			switch (evt.keyCode) {
				case 27 :	drawEnd();		return;	//* 27=Esc
				case 36 :	updateViewport();	return;	//* 36=Home

				case 112 :	showInfo();	return;	//* 112=F1
				case 120 :	savePic(0);	return;	//* 120=F9
				case 118 :	savePic(1);	return;	//* 118=F7
				case 113 :	savePic(2);	return;	//* 113=F2
				case 115 :	savePic(3);	return;	//* 115=F5
				case 117 :	savePic(4);	return;	//* 117=F6
				case 119 :	savePic();	return;	//* 119=F8

				case 8 :				toggleMode('D');	return;	//* 8=Bksp, 45=Ins
				case getKeyCode('Line-straight') :	toggleMode('L');	return;
				case getKeyCode('U-line-curve') :	toggleMode('U');	return;
				case getKeyCode('G-rough-line') :	toggleMode('R');	return;

				case getKeyCode('Z-Undo') :	historyAct(-1);	return;
				case getKeyCode('X-Redo') :	historyAct(1);	return;

				case getKeyCode('Color-pick') :	pickColor();	return;

				case getKeyCode('Swap-tools') :	toolSwap();	return;
				case getKeyCode('A-pencil') :	toolSwap(1);	return;
				case getKeyCode('Eraser') :	toolSwap(2);	return;
				// case getKeyCode('Get-all-default'):	toolSwap(3);	return;

				case getKeyCode('Fill-canvas') :	fillScreen(0);	return;
				case getKeyCode('D-clear-canvas') :	fillScreen(1);	return;
				case getKeyCode('Invert-canvas') :	fillScreen(-1);	return;
				case getKeyCode('Hor-flip-canvas') :	fillScreen(-2);	return;
				case getKeyCode('Ver-flip-canvas') :	fillScreen(-3);	return;

				case 42 :
				case 106 :	updateDebugScreen(-1);	return;	//* 42=106=[Num *]
			}
		}

		if (
			draw.active
		&&	(
				evt.type === 'keydown'
			||	evt.type === 'keyup'
			)
		) {

//* Cancel drawing:

			if (evt.keyCode == 27) {	//* 27=Esc
				return drawEnd();
			}

//* Swap control/end points of curved line:

			if (
				(
					evt.keyCode == 16	//* 16=Shift
				||	evt.keyCode == 17	//* 17=Ctrl
				||	evt.keyCode == 18	//* 18=Alt
				)
			&&	draw.step
			&&	mode.step
			&&	mode.shape
			&&	(draw.shapeFlags & 1)	//* <- line+curve in progress
			) {
				return drawMove(evt);
			}
		}

//* Show debug info for unused keys:

		if (mode.debug) text.debug.innerHTML += '<br>' + [
			'type = ' + evt.type
		,	'key = ' + evt.key
		,	'code = ' + evt.code
		,	'keyCode = ' + evt.keyCode
		,	'which = ' + evt.which
		,	'altKey = ' + evt.altKey
		,	'ctrlKey = ' + evt.ctrlKey
		,	'shiftKey = ' + evt.shiftKey
		].join(',\n');
	}

	return false;
}

//* Drag and drop *------------------------------------------------------------

function dragOver(evt) {
	evt = eventStop(evt);
	evt.preventDefault();

var	d = evt.dataTransfer.files
,	e = d && d.length
	;

	evt.dataTransfer.dropEffect = e ? 'copy' : 'move';
}

function drop(evt) {
	evt = eventStop(evt);
	evt.preventDefault();

var	d = evt.dataTransfer.files
,	i = (d ? d.length : 0)
,	f,r
	;

	if (!window.FileReader || !i) {
		return;
	}

	while (i--) if ((f = d[i]).type.match('image.*')) {
	var	r = new FileReader();

		r.onload = (function(f) {
			return function(e) {
				savePic(4, {
					name : f.name
				,	data : e.target.result
				});
			};
		})(f);

		r.readAsDataURL(f);

		return;
	}

	alert(lang.no.files);
}

//* Check before closing page *------------------------------------------------

function beforeUnload(evt) {
	if (
		!postingInProgress
	&&	!fillCheck()
	) {

//* Note: given message text won't be used in modern browsers.
//* https://habr.com/ru/post/141793/

	var	message = lang.confirm.close;

		if (evt = evt || window.event) {
			evt.returnValue = message;
		}

		return message;
	}
}

//* Initialization *-----------------------------------------------------------

function init() {
	if (isTest()) {
		document.title += ': '+NS+' '+INFO_VERSION;
	}

var	a,b,c = 'canvas'
,	d = '<div id="'
,	e,f,g,h,i,j,k
,	n = '\n'
,	o = outside
,	r = '</td><td class="r">'
,	s = '&nbsp;'
,	t = '" title="'
	;

	if (e = getElemById(c)) while (e = e.parentNode) if (e.id == NS) {
		e.parentNode.removeChild(e);	//* <- remove self duplicate, for archived pages saved in "current state"

		break;
	}

	setContent(
		container = getElemById().firstElementChild
	,	d+'draw">'			//* <- transform offset fix for o11
	+		d+'load">'
	+			'<'+c+' id="'+c+'" tabindex="0">'+lang.no.canvas+'</'+c+'>'
	+		'</div>'
	+	'</div>'
	+	d+'right"></div>'
	+	d+'bottom"></div>'
	+	d+'debug"></div>'
	);

	if (!(canvas = getElemById(c)).getContext) {
		return;
	}

	cnvHid = cre(c);

	for (i in select.imgSizes) {
		cnvHid[i] = canvas[i] = (o[a = i[0]] || (o[a] = o[i] || select.imgSizes[i]));

		if (
			(o[b = a+'l'] || o[b = i+'Limit'])
		&&	(f = o[b].match(regLimit))
		) {
			select.imgLimits[i] = [
				orz(f[1])
			,	orz(f[2])
			];
		}
	}

	c2s = clearFill(cnvHid);
	c2d = clearFill(canvas);

	a = {
		left : '←</label>'
	,	center : '<label>→'
	,	right : '</label>'
	}

	b = '<label>';
	k = 'text-align';

	for (i in a) {
		b += '<input type="radio" name="'+k+'" id="'+k+'-'+i+'" onChange="updateTextAlign(this)">'+a[i];
	}

	b = (
		d+'colors">'
	+	d+'texts">'
	+		d+'text">'
	+			'<textarea id="text-font'+t+lang.text_font_hint+'" onChange="checkTextStyle(this)">'+DEFAULT_FONT+'</textarea>'
	+			'<div id="'+k+'-wrap">'
	+				'<span id="'+k+'-group'+t+lang.text_align_hint+'">'+b+'</span>'
	+				'<select id="textStyle'+t+lang.text_font_set_hint+'" onChange="updateTextStyle(this)"></select>'
	+			'</div>'
	+			'<textarea id="text-content'+t+lang.text_hint+'" placeholder="'+lang.text_placeholder+'"></textarea>'
	+		'</div>'
	+		d+'sliders">'
	);

	i = SLIDER_NAMES.length;
	j = '<td class="l">';
	k = '<span>';
	r = '</td><td class="r">';
	a = ': '+r+'	';
	c = ':</span>	';

	while (i--) {
		b += getSliderHTML(SLIDER_LETTERS[i], i);
	}

	b += (
			'</div>'
	+	'</div><br>'
	+	'<table id="selects"><tr><td>'
	+	lang.shape	+a+'<select id="shape" onChange="updateShape(this)"></select>'
	);

	for (i in select.lineCaps) {
		b += r+'<select id="'+i+t+(select.lineCaps[i] || i)+'"></select>';
	}

	setContent(
		getElemById('right')
	,	b+'</td></tr><tr><td>'
	+	lang.hex	+a+'<input type="text" value="#000" id="color-text" onChange="updateColor()'+t
	+	lang.hex_hint+'">'+r
	+	lang.palette	+a+'<select id="palette" onChange="updatePalette()"></select></td></tr></table>'
	+	d+'palette-table"></div>'
	+ '</div>'
	+ d+'info"></div>'
	);

	a = '<a href="javascript:void(0);" onClick="';
	b = '">';
	c = '</abbr>';
	d = '';

	for (i in select.imgSizes) {
		d += (
			(d ? ' x ' : lang.size+': ')
		+	'<input type="text" value="'+o[i[0]]+'" id="img-'+i+'" onChange="updateDimension(this)'+t
		+	lang.size_hint
		+	select.imgLimits[i].join(lang.range_hint)+'">'
		);
	}

	b = '<abbr title="';
	f = '<span class="rf">';

	setContent(
		getElemById('info')

//* top of 2 info brackets:

	,	'<p class="L-open">'
	+		lang.info_top
	+	'</p>'
	+	'<p>'
	+		lang.info
				.join('<br>')
				.replace(/<br><br>/gi, '</p><p>')
				.replace(/\{([^};]+);([^}]+)}/g, a+'$1()">$2</a>')
	+		': '+f+b+(new Date())+'" id="saveTime">'
	+		lang.info_no_save+'</abbr>.</span>'
	+		'<br>'+a+'toggleView(\'timer\')'+t
	+		lang.hide_hint+'">'
	+		lang.info_time+'</a>: '	+f+'<span id="timer">'+lang.info_no_yet+'</span>.</span>\n'
	+		lang.info_undo+': '	+f+'<span id="undo">'+lang.info_no_yet+'</span>.</span><br>'
	+		lang.info_drop
	+	'</p>'

//* bottom of 2 info brackets:

	+	'<p class="L-close">'
	+		b
	+		NS.toUpperCase()
	+		', '+INFO_ABBR
	+		', '+lang.info_pad
	+		', '+INFO_DATE
	+		'">'+INFO_VERSION+'</abbr>.'
	+	'</p>'
	+	'<div>'
	+		'<select id="resize" onChange="updateResize(this)'+t
	+		lang.resize_hint+'"></select>\n'
	+		d
	+	'</div>'
	);

	for (i in SLIDER_NAMES) setSliderProps(SLIDER_LETTERS[i]);
	for (i in text) text[i] = getElemById(i);

	draw.container = getElemById('load');

	a = 'historyAct(';
	b = 'button';
	c = 'color';
	d = 'toggleMode(';
	e = 'savePic(';
	f = 'fillScreen(';
	i = 'toolSwap(';
	k = 'check';
	a = [

//* subtitle, hotkey, pictogram, function, id:

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
	,	['pencil','A'		,'i'		,i+'1)']
	,	['eraser','E'		,'&#x25CB;'	,i+'2)']
	,	['reset' ,'Alt+A'	,'&#x25CE;'	,i+'3)']
	, 0
	,	['line|area|copy'	,'L'	,'&ndash;|&#x25A0;|&#x25EB;'	,d+'"L")'	,k+'L']
	,	['curve|outline|rect'	,'U'	,'~|&#x25A1;|&#x25AF;'		,d+'"U")'	,k+'U']
	,	['rough'		,'G'	,'&#x25CD;'			,d+'"R")'	,k+'R']
	,	['cursor'		,'Alt+S','&#x25CF;'			,d+'"V")'	,k+'V']
	, 0
	,	['png'	,'F9'	,'&#x25EA;'	,e+'0)'	,b+'P']
	,	['jpeg'	,'F7'	,'&#x25A9;'	,e+'1)'	,b+'J']
	,	['save'	,'F2'	,'!'		,e+'2)'	,b+'S']
	,	['load'	,'F4'	,'?'		,e+'3)'	,b+'L']
	, !o.read || 0 == o.read ? 1 : ['read'	,'F6'	,'&#x2324;'	,e+'4)']
	, !o.send || 0 == o.send ? 1 : ['done'	,'F8'	,'&#x2713;'	,e+')']
	, 0
	,	['info'	,'F1'	,'?'	,'showInfo()'	,b+'H']
	];

	d = '<div class="button-';
	c = '</div>';

	setClass(f = getElemById('bottom'), MODE_LABELS.join(' '));

	function btnContent(e, a) {
	var	t = lang.b[a[0]] || a[0];

		setContent(e, d+'key">'+a[1]+c+a[2]+d+'subtitle"><br>'+(t.t ? t.sub : a[0])+c);
		e.title = t.t || t;

		return e;
	}

	for (i in a) if (1 !== (k = a[i])) {
		if (k) {
			setClass(e = cre(b,f), b);

			if (k[0].indexOf('|') > 0) {
			var	subt = k[0].split('|')
			,	pict = k[2].split('|')
				;

				for (j in subt) {
					setClass(
						btnContent(
							cre('div', e)
						,	[subt[j]
						,	k[1]
						,	pict[j]]
						)
					,	MODE_LABELS[j]
					);
				}
			} else {
				btnContent(e, k);
			}

			if (k.length > 3) setEvent(e, 'onclick', k[3]);
			if (k.length > 4) setId(e, k[4]);
		} else {
			f.innerHTML += s;
		}
	}

	if (canvas.height < BOTH_PANELS_HEIGHT) {
		toggleView('info');
	} else {
		setClass(getElemById(b+'H'), b+'-active');
	}

	for (i in mode) if (mode[MODE_NAMES[j = MODE_NAMES.length] = i]) {
		toggleMode(j,1);
	}

	for (i in (a = {S : 0, L : CT})) if (!LS || ((k = a[i]) && !LS[k])) {
		setClass(getElemById(b+i), b+'-disabled');
	}

	for (i in (a = 'JP')) if (e = getElemById(b+a[i])) {
		setEvent(e, 'onmouseover', 'updateSaveFileSize(this)');
	}

	a = [b, 'input', 'textarea', 'select', 'span', 'a'];
	d = ['onchange', 'onclick', 'onmouseover'];

	for (i in a) if (b = container.getElementsByTagName(a[i]))
	for (c in b)
	for (e in d) if (
		(f = b[c][d[e]])
	&&	!self[f = (''+f).match(regInsideFunc)[1]]
	) {
		self[f] = eval(f);
	}

	d = 'download';
	DL = (d in b[0] ? d : '');

	d = {
		lineCap : ['<->', '|-|', '[-]']
	,	lineJoin : ['-x-', '\\_/', 'V']
	};

	a = select.options;
	c = select.translated || a;
	f = (LS && (e = LS.getItem(LP)) !== null && palette[e] ? e : 1);

	for (b in a) if (e = select[b] = getElemById(b))
	for (i in a[b]) (
		e.options[e.options.length] = new Option(c[b][i]+(
			b == 'shape' && regHotKey.test(k = SHAPE_HOTKEYS[i])
			? ' ['+k+']'
			: (b in d)
			? ' '+d[b][i]
			: ''
		), i)
	).selected = (
		b == 'palette'
		? (i == f)
		: !i
	);

	generatePalette(1, 85, 0);
	getElemById('text-align-center').click();
	setClass(getElemById('slider' + selectedSlider).nextElementSibling, 'active');
	toolSwap(3);
	updatePalette();
	updateSliders();
	updateViewport();
	historyAct(0);

//* listen on all page to prevent dead zones:
//* still fails to catch events outside of document block height less than of browser window.

	addEventListeners(
		window
	,	{
			'beforeunload' : beforeUnload
		,	'dragover' :	dragOver
		,	'drop' :	drop
		,	'keypress' :	k = hotKeys
		,	'keydown' :	k
		,	'keyup' :	k
		,	'mousewheel' :	f = hotWheel
		,	'wheel' :	f
		,	'scroll' :	f
		}
	);

	addEventListeners(
		window
	,	(
			isPointerEventSupported
			? {
				'pointerdown' :	drawStart
			,	'pointermove' :	drawMove
			,	'pointerup' :	drawEnd
			}
			: {
				'mousedown' :	drawStart
			,	'mousemove' :	drawMove
			,	'mouseup' :	drawEnd
			}
		)
	);

	addEventListeners(
		canvas
	,	{
			'scroll' :	f = stopScroll	//* <- against FireFox always scrolling on mousewheel
		,	'contextmenu' :	f
		,	'touchstart' :	k = prevent
		,	'touchmove' :	k
		}
	);

}; //* <- END init()

//* External config *----------------------------------------------------------

function isTest() {

	function getOldFormat(i,j) { return (i == 1 ? j : j.slice(0,-1)+i); }
	function getNumClamped(i,n) { return Math.min(Math.max(orz(i) || n, 3), Number.MAX_SAFE_INTEGER || 100200300); }

	if (!CR[0]) {
		return !o.send;
	}

var	o = outside
,	f = o.send = getElemById('send')
,	r = o.read = getElemById('read')
,	v = getElemById('vars')
,	a = [v,f,r]
,	s = ';'
,	regVarSep = /\s*[;\r\n\f]+\s*/g
,	regVarName = /^([^=]+)\s*=\s*/
,	e,i,j,k
	;

/* ext.config syntax:
	a) varname; var2=;		// no sign => value 1; no value => ''
	b) warname=two=3=last_val;	// all vars => same value (rightmost part)
*/
	for (i in a) if (
		(e = a[i])
	&&	(e = (e.getAttribute('data-vars') || e.name))
	) {
		a = e.replace(regVarSep, s).split(s);

		for (i in a) if ((e = a[i].replace(regVarName, '$1=')).length) {
			if ((e = e.split('=')).length > 1) {
				k = e.pop();

				for (j in e) {
					o[e[j]] = k;
				}
			} else {
				o[e[0]] = k = 1;
			}

			if (e[0].substr(0,2) == 'jp') {
				o.jpg = k;
			}
		}

		break;			//* <- read vars batch in the first found attribute only; no care about the rest
	}

	if (LS) {
		i = (o.keep_prefix || o.keepprfx || NS);

		if (j = LS[HP = i+'HistoryPalette'] || LS.historyPalette) {
			palette[0] = JSON.parse(j);
		}

		if (
			null === LS.getItem(LP = i+'LastPalette')
		&&	null !== LS.getItem(k = 'lastPalette')
		) {
			LS[LP] = LS[k];
		}

		i = o.save = getNumClamped(o.save, 9)
	,	j = (o.save_prefix || o.saveprfx || NS)+CR
	,	f = (o.saveprfx ? o.saveprfx+CR : '')
	,	CR = []
		;

		do {
			v = (
			(f && (
				null !== LS.getItem(k = getOldFormat(i,f))
			||	null !== LS.getItem(k = f+i)
			))
			||	null !== LS.getItem(k = getOldFormat(i,j))
			||	(k = j+i, 0)
			);

			a = {
				R : k
			,	T : k+CT
			};

			if (v) {
				a.keepSavedInOldFormat = true;
			}

			CR[i] = a;
		} while (--i);

		CT = CR[1].T;
	} else {
		o.save = 0;
		CR = 'none';
	}

	o.undo = draw.history.max = getNumClamped(o.undo, 99);
	o.idle = draw.time.idle = getNumClamped(o.idle, 60)*1000;

	j = SHAPE_HOTKEYS.split('').join(k = ', ');

	if (!o.lang) {
		o.lang = document.documentElement.lang || 'en';
	}

	if (o.lang == 'ru') {
		r = ' браузера (содержит очередь из '+o.save+' позиций максимум).';

		lang = {
			bad_id : 	'Ошибка: действие не найдено.'
		,	err_code :	'Код ошибки'
		,	found_swap :	'Рисунок был в запасе, теперь сдвинут на первое место.'
		,	bytes :		'байт'
		,	confirm : {
				send :	'Отправить рисунок в сеть?'
			,	close :	'Покинуть эту страницу и выбросить открытый рисунок?'
			,	size : [
					'Размеры полотна вне допустимых пределов, от '
				,	' до '
				,	'. Отправить всё равно?'
				]
			,	save : [
					'Сохранить рисунок в память браузера?'
				,	'Заменить старую копию, изменённую:'
				]
			,	load : [
					'Вернуть рисунок из памяти браузера?'
				,	'Восстановить копию, изменённую:'
				]
			}
		,	no : {
				LS	: 'Локальное Хранилище (память браузера) недоступно.'
			,	space	: 'Ошибка сохранения, нет места.'
			,	files	: 'Среди файлов не найдено изображений.'
			,	form	: 'Назначение недоступно.'
			,	change	: 'Нет изменений.'
			,	canvas	: 'Ваша программа не поддерживает HTML5-полотно.'
			,	drawn	: 'Полотно пусто.'
			}
		,	tool : {
				B :	'Тень'
			,	O :	'Непрозр.'
			,	W :	'Толщина'
			}
		,	shape :		'Форма'
		,	palette :	'Палитра'
		,	sat :		'Насыщ.'
		,	hex :		'Цвет'
		,	hex_hint :	'Формат ввода — #a, #f90, #ff9900, или 0,123,255'
		,	hide_hint :	'Кликните, чтобы спрятать или показать.'
		,	text_hint :	'Рисовать в фигурах текст, заданный здесь.'
		+TITLE_LINE_BREAK+	'Поле можно растягивать за уголок, если ваш браузер позволяет.'
		,	text_align_hint :	'Выравнивать текст по краю или середине.'
		,	text_font_hint :	'Шрифт, стиль и высота строки печатаемого текста.'
		+TITLE_LINE_BREAK+		'Если размер не указан, он подбирается автоматически.'
		,	text_font_set_hint :	'Некоторые заданные варианты стилей.'
		+TITLE_LINE_BREAK+		'Какие-то могут не сработать, если в вашей системе не найдётся такого шрифта.'
		,	text_placeholder :	'Ваш текст тут.'
		,	info_top :	'Управление (когда указатель над полотном):'
		,	info : [
				'[C], средний клик мыши = взять цвет с рисунка.'
			,	'[1-10], [+/&minus;], колесо мыши = параметры кисти.'
			,	'[Esc] = {drawEnd;сбросить незавершённое действие.}'
			,	''
			,	'Тянуть левой кнопкой мыши, зажимая кнопку:'
			,	'[Alt] = масштаб, [Ctrl] = поворот, [Shift] = сдвиг.'
			,	'[Home] = {updateViewport;сброс положения полотна.}'
			,	''
			,	'Автосохранение раз в минуту'
			]
		,	info_no_save :	'ещё не было'
		,	info_no_yet :	'ещё нет'
		,	info_time :	'Времени прошло'
		,	info_undo :	'Шаги'
		,	info_pad :	'доска для набросков'
		,	info_drop :	'Можно перетащить сюда файлы с диска.'
		,	size :		'Размер'
		,	size_hint :	'Число от '
		,	range_hint :	' до '
		,	resize_hint :	'Как умещать или растягивать содержимое полотна при изменении размера или загрузке файлов.'
		+TITLE_LINE_BREAK+	'Без растягивания файл просто перезаписывает всё полотно и его размер.'
		,	b : {
				undo	: {sub : 'назад',	t : 'Отменить последнее действие.'}
			,	redo	: {sub : 'вперёд',	t : 'Отменить последнюю отмену.'}
			,	fill	: {sub : 'залить',	t : 'Залить полотно основным цветом.'}
			,	erase	: {sub : 'стереть',	t : 'Залить полотно запасным цветом.'}
			,	invert	: {sub : 'инверт.',	t : 'Обратить цвета полотна.'}
			,	flip_h	: {sub : 'отразить',	t : 'Отразить полотно слева направо.'}
			,	flip_v	: {sub : 'перевер.',	t : 'Перевернуть полотно вверх дном.'}
			,	pencil	: {sub : 'каранд.',	t : 'Инструмент — тонкий простой карандаш.'}
			,	eraser	: {sub : 'стёрка',	t : 'Инструмент — толстый белый карандаш.'}
			,	swap	: {sub : 'смена',	t : 'Поменять инструменты местами.'}
			,	reset	: {sub : 'сброс',	t : 'Сбросить инструменты к начальным.'}
			,	line	: {sub : 'прямая',	t : 'Прямая линия (1 клик-зажатие).'}
			,	curve	: {sub : 'сгиб',	t : 'Сглаживать углы линии.'
				+TITLE_LINE_BREAK+	'Вместе с включением "прямой" — одна ровная кривая линия (2 клик-зажатия подряд).'
				+TITLE_LINE_BREAK+	'Зажатие кнопки Alt, Ctrl или Shift меняет местами концевую и контрольную точку линии.'
				}
			,	area	: {sub : 'закрас.',	t : 'Закрашивать площадь геометрических фигур.'}
			,	outline	: {sub : 'контур',	t : 'Рисовать контур геометрических фигур.'}
			,	copy	: {sub : 'копия',	t : 'Оставить старую копию при сдвиге.'}
			,	rect	: {sub : 'прямоуг.',	t : 'Сдвиг прямоугольником.'}
			,	cursor	: {sub : 'указат.',	t : 'Показывать кисть на указателе.'}
			,	rough	: {sub : 'черновик',	t : 'Рисовать немного сбитые грубоватые штрихи.'}
			// ,	rough	: {sub : 'п.штрих',	t : 'Уменьшить нагрузку, пропуская перерисовку штриха.'}
			,	fps	: {sub : 'п.кадры',	t : 'Уменьшить нагрузку, пропуская кадры.'}
			,	png	: {sub : 'сохр.png',	t : 'Сохранить рисунок в PNG файл — плоская картинка, полная чёткость.'}
			,	jpeg	: {sub : 'сохр.jpg',	t : 'Сохранить рисунок в JPEG файл — плоская картинка,'
				+	'может меньше весить, но хуже качество.'
				}
			,	save	: {sub : 'сохран.',	t : 'Сохранить рисунок в память'+r}
			,	load	: {sub : 'загруз.',	t : 'Вернуть рисунок из памяти'+r
				+TITLE_LINE_BREAK+	'Может не сработать в некоторых браузерах,'
				+			'если не настроить автоматическую загрузку и показ изображений.'
				}
			,	read	: {sub : 'зг.файл',	t : 'Прочитать локальный файл.'
				+TITLE_LINE_BREAK+	'Может не сработать вообще, особенно при запуске самой рисовалки не с диска.'
				+TITLE_LINE_BREAK+	'Вместо этого рекомендуется перетаскивать файлы из других программ.'
				}
			,	done	: {sub : 'готово',	t : 'Завершить и отправить рисунок в сеть.'}
			,	info	: {sub : 'помощь',	t : 'Показать или скрыть информацию.'}
			}
		};

		select.lineCaps = {
			lineCap :	'Концы линий'
		,	lineJoin :	'Сгибы линий'
		};

		select.translated = {
			resize : {
				'top left'	: 'ЛВ доп./обрез.: держать за верх слева'
			,	'top center'	: 'СВ доп./обрез.: держать за верх'
			,	'top right'	: 'ПВ доп./обрез.: держать за верх справа'
			,	'middle left'	: 'ЛЦ доп./обрез.: держать за центр слева'
			,	'middle center'	: 'СЦ доп./обрез.: держать за центр'
			,	'middle right'	: 'ПЦ доп./обрез.: держать за центр справа'
			,	'bottom left'	: 'ЛН доп./обрез.: держать за низ слева'
			,	'bottom center'	: 'СН доп./обрез.: держать за низ'
			,	'bottom right'	: 'ПН доп./обрез.: держать за низ справа'
			,	'auto crop'	: 'Обрезать пустоту по краям'
			,	'scaleKeepW'	: 'Ш масштаб: держ.отношение, загрузка: держ.ширину'
			,	'scaleKeepH'	: 'В масштаб: держ.отношение, загрузка: держ.высоту'
			,	'scaleDeform'	: 'Д масштаб: оставить другую сторону, загрузка: обе'
			}
		,	shape : ['линия', 'замкнутая линия', 'прямоугольник', 'круг', 'овал', 'овал для речи', 'коробка для речи', 'сдвиг']
		,	lineCap : ['круг', 'срез', 'квадрат']
		,	lineJoin : ['круг', 'срез', 'угол']
		,	textStyle : ['...', 'шрифт по умолчанию', 'без засечек', 'с засечками', 'моноширинный', 'курсив', 'фантастика', 'модерн', 'узкий', 'комикс', 'готика', 'рукопись']
		,	palette	: ['история', 'авто', 'разное', 'Тохо', 'градиент', 'круг']
		};
	} else {
		r = ' your browser memory (keeps a maximum of '+o.save+' slots in a queue).';

		lang = {
			bad_id :	'Invalid action: case not found.'
		,	err_code :	'Error code'
		,	found_swap :	'Found same image still saved, swapped it to first slot.'
		,	bytes :		'bytes'
		,	confirm : {
				send :	'Send image to server?'
			,	close :	'Leave this page and discard the drawing?'
			,	size : [
					'Canvas size is outside of limits, from '
				,	' to '
				,	'. Send anyway?'
				]
			,	save : [
					'Save image to your browser memory?'
				,	'Replace saved copy edited at:'
				]
			,	load : [
					'Restore image from your browser memory?'
				,	'Load copy edited at:'
				]
			}
		,	no : {
				LS	: 'Local Storage (browser memory) not supported.'
			,	space	: 'Saving failed, not enough space.'
			,	files	: 'No image files found.'
			,	form	: 'Destination unavailable.'
			,	change	: 'Nothing changed.'
			,	canvas	: 'Your browser does not support HTML5 canvas.'
			,	drawn	: 'Canvas is empty.'
			}
		,	tool : {
				B :	'Shadow'
			,	O :	'Opacity'
			,	W :	'Width'
			}
		,	shape :		'Shape'
		,	palette :	'Palette'
		,	sat :		'Saturat.'
		,	hex :		'Color'
		,	hex_hint :	'Valid formats — #a, #f90, #ff9900, or 0,123,255'
		,	hide_hint :	'Click to show/hide.'
		,	text_hint :		'Enter text here to print inside figures.'
		+TITLE_LINE_BREAK+		'Field is resizable by dragging its corner, if your browser supports.'
		,	text_align_hint :	'Align printed text to either side or centered.'
		,	text_font_hint :	'Printed text font style.'
		,	text_font_set_hint :	'Various style presets, some of which may not work if your system has no matching fonts installed.'
		,	text_placeholder :	'Your text here.'
		,	info_top :	'Hot keys (when cursor is on canvas):'
		,	info : [
				'[C], middle mouse click = pick color from canvas.'
			,	'[1-10], [+/&minus;], mouse wheel = brush settings.'
			,	'[Esc] = {drawEnd;cancel unfinished action.}'
			,	''
			,	'Drag with left mouse click, while holding key:'
			,	'[Alt] = zoom, [Ctrl] = rotate, [Shift] = move.'
			,	'[Home] = {updateViewport;reset canvas view.}'
			,	''
			,	'Autosave every minute, last saved'
			]
		,	info_no_save :	'not yet'
		,	info_no_yet :	'no yet'
		,	info_time :	'Time elapsed'
		,	info_undo :	'Steps'
		,	info_pad :	'sketch pad'
		,	info_drop :	'You can drag files from disk and drop here.'
		,	size : 		'Size'
		,	size_hint :	'Number from '
		,	range_hint :	' to '
		,	resize_hint :	'How to fit/resize canvas content when changing size or loading files.'
		+TITLE_LINE_BREAK+	'Without rescaling loaded file just overwrites the whole canvas and its size.'
		,	b : {
				undo	: 'Revert last change.'
			,	redo	: 'Redo next reverted change.'
			,	fill	: 'Fill image with main color.'
			,	erase	: 'Fill image with back color.'
			,	invert	: 'Invert image colors.'
			,	flip_h	: {sub : 'flip hor.', t : 'Flip image horizontally.'}
			,	flip_v	: {sub : 'flip ver.', t : 'Flip image vertically.'}
			,	pencil	: 'Set tool to sharp black.'
			,	eraser	: 'Set tool to large white.'
			,	swap	: 'Swap your tools.'
			,	reset	: 'Reset both tools.'
			,	line	: 'Draw straight line (1 click-drag).'
			,	curve	: 'Draw lines with smooth corners.'
			+TITLE_LINE_BREAK+	'With "straight" enabled — draw single curve (2 click-drags).'
			+TITLE_LINE_BREAK+	'Holding Alt, Ctrl or Shift key swaps line end and control point.'
			,	area	: 'Fill geometric shapes.'
			,	outline	: 'Draw outline of geometric shapes.'
			,	copy	: 'Keep old copy.'
			,	rect	: 'Move rectangle.'
			,	cursor	: 'Brush preview on cursor.'
			,	rough	: 'Make slightly rough hand-drawn lines.'
			// ,	rough	: 'Skip draw cleanup while drawing to use less CPU.'
			,	fps	: 'Limit FPS when drawing to use less CPU.'
			,	png	: 'Save image as PNG file — flat picture, top quality.'
			,	jpeg	: 'Save image as JPEG file — flat picture, maybe less filesize, but poor quality.'
			,	save	: 'Save image copy to'+r
			,	load	: 'Load image copy from'+r
			+TITLE_LINE_BREAK+	'May not work in some browsers until set to load and show new images automatically.'
			,	read	: 'Load image from your local file.'
			+TITLE_LINE_BREAK+	'May not work at all, especially if sketcher itself is not started from disk.'
			+TITLE_LINE_BREAK+	'Instead, it is recommended to drag and drop files from another program.'
			,	done	: 'Finish and send image to server.'
			,	info	: 'Show/hide information.'
			}
		};
	}

	return !o.send;
} //* <- END isTest()

//* Embed CSS and container *--------------------------------------------------

function initUIcontainer() {
var	CURSOR_DOT = (
		CUSTOM_CURSOR_DOT
		? (
			'url("data:image/png;base64,'
		+	'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAGElEQVR42mNgYGCYUFdXN4EBRPz//38CADX3CDIkWWD7AAAAAElFTkSuQmCC'
		+	'"), auto'
		)
		: 'default'
	);

var	content = replaceAll(
		'<div>Loading |...</div>'
	+ [
		'<style>'
	,	'#| .|-L-close {padding-bottom: 22px; margin-bottom: 0.25em; border-top: none;}'
	,	'#| .|-L-open {padding-top: 22px; margin-top: 0; border-bottom: none;}'
	,	'#| .|-active {background-color: #ace; color: #fff;}'
	,	'#| .|-button {background-color: #ddd;}'
	,	'#| .|-button-active {background-color: #ace;}'
	,	'#| .|-button-active:hover {background-color: #bef;}'
	,	'#| .|-button-disabled {color: #888; cursor: default;}'
	,	'#| .|-button-key, #| .|-button-subtitle {vertical-align: top; height: 10px; font-size: 9px; margin: 0; padding: 0;}'
	,	'#| .|-button-key, #|-debug {text-align: left;}'
	,	'#| .|-button-subtitle {line-height: 6px; margin: 0 -3px;}'
	,	'#| .|-button:hover {background-color: #eee;}'
	,	'#| .|-inline {display: inline-block;}'
	,	'#| .|-paletdark, #| .|-palettine {border: 2px solid transparent; width: 15px; height: 15px; cursor: pointer;}'
	,	'#| .|-paletdark:hover {border-color: #fff;}'
	,	'#| .|-palettine:hover {border-color: #000;}'
	,	'#| .|-r {text-align: right;}'
	,	'#| .|-red {background-color: #f77;}'
	,	'#| .|-slider *:not(input), #|-text-align-group {display: inline-block;}'
	,	'#| .|-slider input[type="text"] {margin: 2px 0;}'
	,	'#| .|-slider label {padding: 1px 4px;}'
	,	'#| .|-slider, #| .|-slider *:not(.|-slider-range) {vertical-align: middle;}'
	,	'#| .|-slider-range {width: 154px; height: 22px; margin-right: 4px;}'
	,	'#| .|-slider-title {max-width: 90px; overflow: hidden; text-overflow: ellipsis;}'
	,	'#| .|-sliders #|-text, #| .|-texts .|-slider-range, #| .|-texts input[type="range"] {display: none;}'
	,	'#| .|-texts input[type="text"] {margin-left: 2px;}'
	,	'#| a {color: #888;}'
	,	'#| a:hover {color: #000;}'
	// ,	'#| a[href="javascript:;"], #| a[href^="javascript:void"] {text-decoration: none;}'
	,	'#| abbr {text-decoration-line: underline; text-decoration-style: dotted;}'
	,	'#| canvas {border: '+CANVAS_BORDER+'px solid #ddd; margin: 0; cursor: '+CURSOR_DOT+';}'
	,	'#| canvas:hover, #| canvas:hover + #|-color-wheel-inner, #|-color-wheel-inner div:hover {border-color: #aaa;}'
	,	'#| hr {border: none; border-top: 1px solid #aaa; margin: 8px 0;}'
	,	'#| input[type="range"] {width: 100%; height: 100%; margin: 1px; padding: 0; vertical-align: top;}'
	,	'#| input[type="text"] {width: 40px; height: 22px;}'
	,	'#| select optgroup option {margin: 0;}'
	,	'#| select optgroup {margin-top: 1em 0;}'
	,	'#| select {width: 78px; height: 28px;}'
	,	'#| textarea {min-width: 80px; min-height: 16px; height: 16px; vertical-align: top;}'
	,	'#| {white-space: nowrap; text-align: center; padding: 12px; background-color: #f8f8f8;}'
	,	'#|, #| input, #| select {font-family: "Arial"; font-size: 19px; line-height: normal;}'
	,	'#|-bottom > button {border: 1px solid #000; width: 38px; height: 38px; margin: 2px; padding: 2px; font-size: 15px; line-height: 7px; text-align: center; vertical-align: top; overflow: hidden; cursor: pointer;}'
	,	'#|-bottom {margin: 10px 0 -2px 0;}'
	,	'#|-bottom, #|-debug {white-space: normal;}'
	,	'#|-color-wheel * {position: absolute;}'
	,	'#|-color-wheel {position: relative; margin: 0 auto; padding: 0;}'
	,	'#|-color-wheel, #|-color-wheel-inner, #|-color-wheel-inner div {border: '+CANVAS_BORDER+'px solid #ddd; overflow: hidden; background-color: white;}'
	,	(noBorderRadius	? '' :
		'#|-color-wheel-inner div {cursor: pointer; border-radius: 25%;}'
	,	'#|-color-wheel-inner, #|-color-wheel-mark, #|-color-wheel-round {border-radius: 50%;}'
		)
	,	'#|-debug td {min-width: 234px;}'
	,	'#|-draw canvas {vertical-align: bottom;}'
	,	'#|-draw canvas, #|-bottom > button {box-shadow: 3px 3px rgba(0,0,0, 0.1);}'
	,	'#|-draw canvas, #|-draw {position: relative; display: inline-block; z-index: 99;}'
	,	'#|-info p {border: 1px solid #777; padding-left: 22px; line-height: 20px;}'
	,	'#|-info p, #|-palette-table table {color: #000; font-size: small;}'
	,	'#|-info p:not(.|-L-open):not(.|-L-close) {border-color: transparent;}'
	,	'#|-load img {position: absolute; top: '+CANVAS_BORDER+'px; left: '+CANVAS_BORDER+'px; margin: 0;}'
	,	'#|-palette-table .|-t {padding: 0 4px;}'
	,	'#|-palette-table table {margin: 0;}'
	,	'#|-palette-table tr td {margin: 0; padding: 0; height: 16px;}'
	,	'#|-palette-table {overflow-y: auto; max-height: 190px; margin: 4px 0;}'
	// ,	'#|-right span > input[type="text"] {margin: 2px;}'
	,	'#|-right table {border-collapse: collapse;}'
	// ,	'#|-right table, #|-info > div {margin-top: 7px;}'
	,	'#|-right td {padding: 0 2px; height: 32px;}'
	,	'#|-right {color: #888; width: 321px; margin: 0; margin-left: 12px; text-align: left; display: inline-block; vertical-align: top; overflow: hidden;}'
	,	'#|-selects #|-color-text {width: 78px;}'
	,	'#|-selects td {min-width: 64px;}'
	,	'#|-selects {width: 100%;}'
	,	'#|-sliders {max-width: 100px;}'
	,	'#|-text #|-text-font {max-height: 22px; min-height: 22px; height: 22px;}'
	,	'#|-text select {margin: 2px; width: 51px; height: 28px;}'
	,	'#|-text textarea {margin: 2px; width: 146px; min-width: 146px; max-width: 311px; max-height: 356px; min-height: 22px; height: 22px;}'
	,	'#|-texts > * {float: left;}'
	,	'#|>a, #| form {display: none;}'
	,	MODE_LABELS.map(function(i) {return '.|-'+i+' .|-'+i;}).join(', ')+' {display: none;}'
	,	'</style>'
	].join('\n')
	, '|'
	, NS
	);

var	container = getElemById();

	if (container) {
		container.innerHTML = content;
	} else {
		document.write(
				'<div id="'+NS+'">'
			+		content
			+	'</div>'
		);
	}
} //* <- END initUIcontainer()

//* To get started *-----------------------------------------------------------

initUIcontainer();

document.addEventListener('DOMContentLoaded', init, false);

}; })('dfc');	//* <- END global wrapper; set namespace here

/*
	Two Factor Authentication - phpBB extension
	Author:    Salvo Cortesiano
	Copyright: (c) 2026-08-11 20:00 CEST Salvo Cortesiano
	Forum:     https://netshadows.de/ombra/
	License:   GNU General Public License, version 2 (GPL-2.0)

	Small QR encoder (byte mode) used to draw the enrolment code in the browser.
	The secret is never sent to an online QR service, which would defeat the
	purpose of a second factor.

	Ported from a reference implementation checked by decoding the generated
	codes, so the output is known to be readable by real scanners.
*/
(function (global) {
	'use strict';

	/* Galois field ---------------------------------------------------- */
	var EXP = new Array(512);
	var LOG = new Array(256);

	(function () {
		var x = 1;
		for (var i = 0; i < 255; i++) {
			EXP[i] = x;
			LOG[x] = i;
			x <<= 1;
			if (x & 0x100) { x ^= 0x11D; }
		}
		for (var j = 255; j < 512; j++) { EXP[j] = EXP[j - 255]; }
	}());

	function gfMul(a, b) {
		if (a === 0 || b === 0) { return 0; }
		return EXP[LOG[a] + LOG[b]];
	}

	function rsGenerator(n) {
		var poly = [1];
		for (var i = 0; i < n; i++) {
			var next = new Array(poly.length + 1);
			for (var k = 0; k < next.length; k++) { next[k] = 0; }
			for (var j = 0; j < poly.length; j++) {
				next[j] ^= gfMul(poly[j], 1);
				next[j + 1] ^= gfMul(poly[j], EXP[i]);
			}
			poly = next;
		}
		return poly;
	}

	function rsEncode(data, n) {
		var gen = rsGenerator(n);
		var res = data.slice();
		var i, j;
		for (i = 0; i < n; i++) { res.push(0); }
		for (i = 0; i < data.length; i++) {
			var coef = res[i];
			if (coef) {
				for (j = 1; j < gen.length; j++) { res[i + j] ^= gfMul(gen[j], coef); }
			}
		}
		return res.slice(data.length);
	}

	/* Tables ---------------------------------------------------------- */
	var EC_TABLE = {
		L: {1:[7,1,19,0,0],2:[10,1,34,0,0],3:[15,1,55,0,0],4:[20,1,80,0,0],5:[26,1,108,0,0],
			6:[18,2,68,0,0],7:[20,2,78,0,0],8:[24,2,97,0,0],9:[30,2,116,0,0],10:[18,2,68,2,69],
			11:[20,4,81,0,0],12:[24,2,92,2,93],13:[26,4,107,0,0],14:[30,3,115,1,116],
			15:[22,5,87,1,88],16:[24,5,98,1,99],17:[28,1,107,5,108],18:[30,5,120,1,121],
			19:[28,3,113,4,114],20:[28,3,107,5,108]},
		M: {1:[10,1,16,0,0],2:[16,1,28,0,0],3:[26,1,44,0,0],4:[18,2,32,0,0],5:[24,2,43,0,0],
			6:[16,4,27,0,0],7:[18,4,31,0,0],8:[22,2,38,2,39],9:[22,3,36,2,37],10:[26,4,43,1,44],
			11:[30,1,50,4,51],12:[22,6,39,2,40],13:[22,8,33,1,34],14:[24,4,40,5,41],
			15:[24,5,41,5,42],16:[28,7,45,3,46],17:[28,10,46,1,47],18:[26,9,43,4,44],
			19:[26,3,44,11,45],20:[26,3,41,13,42]}
	};

	var ALIGN_POS = {1:[],2:[6,18],3:[6,22],4:[6,26],5:[6,30],6:[6,34],7:[6,22,38],8:[6,24,42],
		9:[6,26,46],10:[6,28,50],11:[6,30,54],12:[6,32,58],13:[6,34,62],14:[6,26,46,66],
		15:[6,26,48,70],16:[6,26,50,74],17:[6,30,54,78],18:[6,30,56,82],19:[6,30,58,86],
		20:[6,34,62,90]};

	var REMAINDER = {1:0,2:7,3:7,4:7,5:7,6:7,7:0,8:0,9:0,10:0,11:0,12:0,13:0,
		14:3,15:3,16:3,17:3,18:3,19:3,20:3};

	function dataCapacity(version, ec) {
		var t = EC_TABLE[ec][version];
		return t[1] * t[2] + t[3] * t[4];
	}

	function chooseVersion(length, ec) {
		for (var v = 1; v <= 20; v++) {
			var cci = (v < 10) ? 8 : 16;
			if (dataCapacity(v, ec) * 8 >= 4 + cci + length * 8) { return v; }
		}
		return 0;
	}

	function toBytes(text) {
		var out = [];
		var encoded = unescape(encodeURIComponent(text));
		for (var i = 0; i < encoded.length; i++) { out.push(encoded.charCodeAt(i) & 0xFF); }
		return out;
	}

	function pad(value, width) {
		var s = value.toString(2);
		while (s.length < width) { s = '0' + s; }
		return s;
	}

	function makeCodewords(data, version, ec) {
		var cci = (version < 10) ? 8 : 16;
		var bits = '0100' + pad(data.length, cci);
		var i;

		for (i = 0; i < data.length; i++) { bits += pad(data[i], 8); }

		var cap = dataCapacity(version, ec) * 8;
		var term = Math.min(4, cap - bits.length);

		for (i = 0; i < term; i++) { bits += '0'; }
		while (bits.length % 8) { bits += '0'; }

		var cw = [];
		for (i = 0; i < bits.length; i += 8) { cw.push(parseInt(bits.substr(i, 8), 2)); }

		var padBytes = [0xEC, 0x11];
		var k = 0;
		while (cw.length < dataCapacity(version, ec)) { cw.push(padBytes[k++ % 2]); }

		return cw;
	}

	function interleave(cw, version, ec) {
		var t = EC_TABLE[ec][version];
		var ecLen = t[0], b1 = t[1], d1 = t[2], b2 = t[3], d2 = t[4];
		var blocks = [], ecBlocks = [], pos = 0, i, j;

		for (i = 0; i < b1; i++) { blocks.push(cw.slice(pos, pos + d1)); pos += d1; }
		for (i = 0; i < b2; i++) { blocks.push(cw.slice(pos, pos + d2)); pos += d2; }
		for (i = 0; i < blocks.length; i++) { ecBlocks.push(rsEncode(blocks[i], ecLen)); }

		var maxLen = 0;
		for (i = 0; i < blocks.length; i++) { maxLen = Math.max(maxLen, blocks[i].length); }

		var out = [];
		for (i = 0; i < maxLen; i++) {
			for (j = 0; j < blocks.length; j++) {
				if (i < blocks[j].length) { out.push(blocks[j][i]); }
			}
		}
		for (i = 0; i < ecLen; i++) {
			for (j = 0; j < ecBlocks.length; j++) { out.push(ecBlocks[j][i]); }
		}
		return out;
	}

	/* Matrix ---------------------------------------------------------- */
	function placeFinder(m, r, c) {
		for (var i = -1; i < 8; i++) {
			for (var j = -1; j < 8; j++) {
				var rr = r + i, cc = c + j;
				if (rr < 0 || cc < 0 || rr >= m.length || cc >= m.length) { continue; }
				var inside = ((i >= 0 && i <= 6) && (j === 0 || j === 6))
					|| ((j >= 0 && j <= 6) && (i === 0 || i === 6))
					|| (i >= 2 && i <= 4 && j >= 2 && j <= 4);
				m[rr][cc] = inside ? 1 : 0;
			}
		}
	}

	function buildMatrix(version, ec, bits) {
		var size = version * 4 + 17;
		var m = [], i, j, r, c;

		for (i = 0; i < size; i++) {
			m.push([]);
			for (j = 0; j < size; j++) { m[i].push(null); }
		}

		placeFinder(m, 0, 0);
		placeFinder(m, 0, size - 7);
		placeFinder(m, size - 7, 0);

		for (i = 8; i < size - 8; i++) {
			var v = (i % 2 === 0) ? 1 : 0;
			if (m[6][i] === null) { m[6][i] = v; }
			if (m[i][6] === null) { m[i][6] = v; }
		}

		var coords = ALIGN_POS[version];
		for (var a = 0; a < coords.length; a++) {
			for (var b = 0; b < coords.length; b++) {
				r = coords[a];
				c = coords[b];
				if ((r === 6 && c === 6) || (r === 6 && c === size - 7) || (r === size - 7 && c === 6)) { continue; }
				for (i = -2; i <= 2; i++) {
					for (j = -2; j <= 2; j++) {
						m[r + i][c + j] = (Math.max(Math.abs(i), Math.abs(j)) !== 1) ? 1 : 0;
					}
				}
			}
		}

		m[size - 8][8] = 1;

		for (i = 0; i < 9; i++) {
			if (m[8][i] === null) { m[8][i] = 0; }
			if (m[i][8] === null) { m[i][8] = 0; }
		}
		for (i = size - 8; i < size; i++) {
			if (m[8][i] === null) { m[8][i] = 0; }
			if (m[i][8] === null) { m[i][8] = 0; }
		}

		if (version >= 7) {
			for (i = 0; i < 6; i++) {
				for (j = 0; j < 3; j++) {
					m[size - 11 + j][i] = 0;
					m[i][size - 11 + j] = 0;
				}
			}
		}

		var reserved = [];
		for (i = 0; i < size; i++) {
			reserved.push([]);
			for (j = 0; j < size; j++) { reserved[i].push(m[i][j] !== null); }
		}

		var idx = 0, upward = true, col = size - 1;
		while (col > 0) {
			if (col === 6) { col -= 1; }
			for (var step = 0; step < size; step++) {
				r = upward ? (size - 1 - step) : step;
				for (var k = 0; k < 2; k++) {
					c = col - k;
					if (!reserved[r][c]) {
						m[r][c] = (idx < bits.length) ? parseInt(bits.charAt(idx), 10) : 0;
						idx++;
					}
				}
			}
			upward = !upward;
			col -= 2;
		}

		return { matrix: m, reserved: reserved };
	}

	var MASKS = [
		function (r, c) { return (r + c) % 2 === 0; },
		function (r) { return r % 2 === 0; },
		function (r, c) { return c % 3 === 0; },
		function (r, c) { return (r + c) % 3 === 0; },
		function (r, c) { return (Math.floor(r / 2) + Math.floor(c / 3)) % 2 === 0; },
		function (r, c) { return (r * c) % 2 + (r * c) % 3 === 0; },
		function (r, c) { return ((r * c) % 2 + (r * c) % 3) % 2 === 0; },
		function (r, c) { return ((r + c) % 2 + (r * c) % 3) % 2 === 0; }
	];

	var FORMAT_EC = { L: 1, M: 0, Q: 3, H: 2 };

	function formatBits(ec, mask) {
		var data = (FORMAT_EC[ec] << 3) | mask;
		var v = data << 10;
		var g = 0x537;

		for (var i = 4; i >= 0; i--) {
			if (v & (1 << (i + 10))) { v ^= g << i; }
		}
		return ((data << 10) | v) ^ 0x5412;
	}

	function versionBits(version) {
		var v = version << 12;
		var g = 0x1F25;

		for (var i = 5; i >= 0; i--) {
			if (v & (1 << (i + 12))) { v ^= g << i; }
		}
		return (version << 12) | v;
	}

	function penalty(m) {
		var size = m.length, score = 0, r, c, run, last, i;

		for (r = 0; r < size; r++) {
			run = 0; last = null;
			for (c = 0; c < size; c++) {
				if (m[r][c] === last) { run++; }
				else { if (run >= 5) { score += 3 + (run - 5); } run = 1; last = m[r][c]; }
			}
			if (run >= 5) { score += 3 + (run - 5); }
		}
		for (c = 0; c < size; c++) {
			run = 0; last = null;
			for (r = 0; r < size; r++) {
				if (m[r][c] === last) { run++; }
				else { if (run >= 5) { score += 3 + (run - 5); } run = 1; last = m[r][c]; }
			}
			if (run >= 5) { score += 3 + (run - 5); }
		}
		for (r = 0; r < size - 1; r++) {
			for (c = 0; c < size - 1; c++) {
				if (m[r][c] === m[r][c + 1] && m[r][c] === m[r + 1][c] && m[r][c] === m[r + 1][c + 1]) { score += 3; }
			}
		}

		var p1 = [1,0,1,1,1,0,1,0,0,0,0], p2 = [0,0,0,0,1,0,1,1,1,0,1];

		function matches(seq, pat) {
			for (var k = 0; k < 11; k++) { if (seq[k] !== pat[k]) { return false; } }
			return true;
		}

		for (r = 0; r < size; r++) {
			for (c = 0; c + 10 < size; c++) {
				var seqH = [];
				for (i = 0; i < 11; i++) { seqH.push(m[r][c + i]); }
				if (matches(seqH, p1) || matches(seqH, p2)) { score += 40; }
			}
		}
		for (c = 0; c < size; c++) {
			for (r = 0; r + 10 < size; r++) {
				var seqV = [];
				for (i = 0; i < 11; i++) { seqV.push(m[r + i][c]); }
				if (matches(seqV, p1) || matches(seqV, p2)) { score += 40; }
			}
		}

		var dark = 0;
		for (r = 0; r < size; r++) {
			for (c = 0; c < size; c++) { dark += m[r][c]; }
		}
		var total = size * size;
		score += Math.floor(Math.abs(Math.floor(dark * 100 / total) - 50) / 5) * 10;

		return score;
	}

	function applyFormat(m, ec, mask) {
		var size = m.length;
		var bits = formatBits(ec, mask);

		for (var i = 0; i < 15; i++) {
			var b = (bits >> (14 - i)) & 1;

			if (i < 6) { m[8][i] = b; }
			else if (i === 6) { m[8][7] = b; }
			else if (i === 7) { m[8][8] = b; }
			else if (i === 8) { m[7][8] = b; }
			else { m[14 - i][8] = b; }

			if (i < 7) { m[size - 1 - i][8] = b; }
			else { m[8][size - 15 + i] = b; }
		}
	}

	function applyVersion(m, version) {
		if (version < 7) { return; }

		var size = m.length;
		var bits = versionBits(version);

		for (var i = 0; i < 18; i++) {
			var b = (bits >> i) & 1;
			var r = Math.floor(i / 3), c = i % 3;
			m[size - 11 + c][r] = b;
			m[r][size - 11 + c] = b;
		}
	}

	/**
	 * Build the module matrix for a piece of text.
	 *
	 * @return array of arrays of 0/1, or null when the text is too long
	 */
	function encode(text, ec) {
		ec = ec || 'M';

		var data = toBytes(text);
		var version = chooseVersion(data.length, ec);

		if (!version) { return null; }

		var cw = makeCodewords(data, version, ec);
		var finalCw = interleave(cw, version, ec);
		var bits = '', i, r, c;

		for (i = 0; i < finalCw.length; i++) { bits += pad(finalCw[i], 8); }
		for (i = 0; i < REMAINDER[version]; i++) { bits += '0'; }

		var built = buildMatrix(version, ec, bits);
		var best = null, bestScore = null;

		for (var mask = 0; mask < 8; mask++) {
			var m = [];
			for (r = 0; r < built.matrix.length; r++) { m.push(built.matrix[r].slice()); }

			for (r = 0; r < m.length; r++) {
				for (c = 0; c < m.length; c++) {
					if (!built.reserved[r][c] && MASKS[mask](r, c)) { m[r][c] ^= 1; }
				}
			}

			applyFormat(m, ec, mask);
			applyVersion(m, version);

			var s = penalty(m);
			if (bestScore === null || s < bestScore) { best = m; bestScore = s; }
		}

		return best;
	}

	/**
	 * Draw the code into an element.
	 */
	function render(element, text, options) {
		options = options || {};

		var ec = options.ec || 'M';
		var quiet = (options.quiet === undefined) ? 4 : options.quiet;
		var target = options.size || 240;
		var matrix = encode(text, ec);

		if (!matrix) { return false; }

		var n = matrix.length;
		var scale = Math.max(2, Math.floor(target / (n + quiet * 2)));
		var side = (n + quiet * 2) * scale;

		var canvas = document.createElement('canvas');
		canvas.width = side;
		canvas.height = side;
		canvas.setAttribute('role', 'img');

		var ctx = canvas.getContext('2d');
		ctx.fillStyle = '#ffffff';
		ctx.fillRect(0, 0, side, side);
		ctx.fillStyle = '#000000';

		for (var r = 0; r < n; r++) {
			for (var c = 0; c < n; c++) {
				if (matrix[r][c]) {
					ctx.fillRect((c + quiet) * scale, (r + quiet) * scale, scale, scale);
				}
			}
		}

		element.innerHTML = '';
		element.appendChild(canvas);

		return true;
	}

	global.agmQr = { encode: encode, render: render };
	global.agmQrRender = function (element, text, options) { return render(element, text, options); };

	if (typeof module !== 'undefined' && module.exports) {
		module.exports = { encode: encode };
	}
}(typeof window !== 'undefined' ? window : this));

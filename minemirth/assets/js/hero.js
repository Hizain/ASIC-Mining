/*!
 * Mine Mirth — hero canvases.
 *  - [data-mm-sphere]    rotating hash-network sphere with pulses and arcs
 *  - [data-mm-particles] drifting particle field
 * Both pause off-screen / in background tabs and respect reduced motion.
 */
(function () {
	'use strict';

	var MM = window.MineMirth || {};
	var cfg = window.MM_CONFIG || {};
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var density = (cfg.fx && cfg.fx.density) || 1;

	function hexToRgb(hex, fallback) {
		var m = String(hex || '').trim().match(/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i);
		if (!m) { return fallback; }
		var h = m[1];
		if (h.length === 3) { h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2]; }
		var n = parseInt(h, 16);
		return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
	}
	function palette() {
		var cs = getComputedStyle(document.documentElement);
		return {
			primary: hexToRgb(cs.getPropertyValue('--mm-primary'), [53, 168, 246]),
			accent: hexToRgb(cs.getPropertyValue('--mm-accent'), [37, 99, 235]),
			hi: hexToRgb(cs.getPropertyValue('--mm-accent-2'), [94, 225, 255]),
			dark: document.documentElement.getAttribute('data-theme') === 'dark'
		};
	}
	function rgba(c, a) { return 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + a.toFixed(3) + ')'; }

	/**
	 * Shared lifecycle: sizing, visibility pause, theme refresh.
	 */
	function Runner(canvas, setup, draw) {
		var ctx = canvas.getContext('2d');
		var state = { w: 0, h: 0, dpr: 1, visible: true, running: false, t0: performance.now(), colors: palette() };
		var raf = 0;

		function resize() {
			var rect = canvas.getBoundingClientRect();
			state.dpr = Math.min(window.devicePixelRatio || 1, 2);
			state.w = Math.max(1, rect.width);
			state.h = Math.max(1, rect.height);
			canvas.width = Math.round(state.w * state.dpr);
			canvas.height = Math.round(state.h * state.dpr);
			ctx.setTransform(state.dpr, 0, 0, state.dpr, 0, 0);
			setup(state);
			if (reduce || !state.running) { draw(ctx, state, 0); }
		}
		function frame(now) {
			if (!state.running) { return; }
			draw(ctx, state, now - state.t0);
			raf = requestAnimationFrame(frame);
		}
		function start() {
			if (state.running || reduce) { return; }
			state.running = true;
			raf = requestAnimationFrame(frame);
		}
		function stop() {
			state.running = false;
			cancelAnimationFrame(raf);
		}

		resize();
		if (window.ResizeObserver) {
			new ResizeObserver(function () { resize(); }).observe(canvas);
		} else {
			window.addEventListener('resize', resize);
		}
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				state.visible = entries[0].isIntersecting;
				if (state.visible && !document.hidden) { start(); } else { stop(); }
			}, { threshold: 0 }).observe(canvas);
		} else {
			start();
		}
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) { stop(); } else if (state.visible) { start(); }
		});
		document.addEventListener('mm:themechange', function () {
			state.colors = palette();
			if (reduce || !state.running) { draw(ctx, state, 0); }
		});
		return state;
	}

	/* ------------------------------------------------------------------
	 * Hash-network sphere
	 * ---------------------------------------------------------------- */
	function sphere(canvas) {
		var count = Math.round(360 * Math.max(.3, density));
		var pts = [];
		var edges = [];
		var pulses = [];
		var arcs = [];
		var lastPulse = 0, lastArc = 0;
		var pointer = { x: 0, y: 0, tx: 0, ty: 0 };

		// Fibonacci sphere.
		var golden = Math.PI * (3 - Math.sqrt(5));
		for (var i = 0; i < count; i++) {
			var y = 1 - (i / (count - 1)) * 2;
			var r = Math.sqrt(1 - y * y);
			var th = golden * i;
			pts.push({ x: Math.cos(th) * r, y: y, z: Math.sin(th) * r, seed: Math.random() });
		}
		// Connect each point to its 3 nearest neighbours.
		var seen = {};
		for (var a = 0; a < count; a++) {
			var best = [];
			for (var b = 0; b < count; b++) {
				if (a === b) { continue; }
				var dx = pts[a].x - pts[b].x, dy = pts[a].y - pts[b].y, dz = pts[a].z - pts[b].z;
				var d = dx * dx + dy * dy + dz * dz;
				if (best.length < 3) { best.push([d, b]); best.sort(function (p, q) { return p[0] - q[0]; }); }
				else if (d < best[2][0]) { best[2] = [d, b]; best.sort(function (p, q) { return p[0] - q[0]; }); }
			}
			best.forEach(function (pair) {
				var key = a < pair[1] ? a + '-' + pair[1] : pair[1] + '-' + a;
				if (!seen[key]) { seen[key] = 1; edges.push([a, pair[1]]); }
			});
		}

		var zone = canvas.closest('.mm-hero') || canvas;
		if (MM.finePointer) {
			zone.addEventListener('pointermove', function (e) {
				var r = zone.getBoundingClientRect();
				pointer.tx = ((e.clientX - r.left) / r.width - 0.5) * 0.9;
				pointer.ty = ((e.clientY - r.top) / r.height - 0.5) * 0.6;
			});
			zone.addEventListener('pointerleave', function () { pointer.tx = 0; pointer.ty = 0; });
		}

		var proj = new Array(count);

		function slerp(p, q, t) {
			var dot = Math.max(-1, Math.min(1, p.x * q.x + p.y * q.y + p.z * q.z));
			var om = Math.acos(dot);
			if (om < 1e-4) { return { x: p.x, y: p.y, z: p.z }; }
			var so = Math.sin(om);
			var s1 = Math.sin((1 - t) * om) / so, s2 = Math.sin(t * om) / so;
			return { x: p.x * s1 + q.x * s2, y: p.y * s1 + q.y * s2, z: p.z * s1 + q.z * s2 };
		}

		Runner(canvas, function () {}, function (ctx, s, time) {
			var w = s.w, h = s.h, c = s.colors;
			var R = Math.min(w, h) * 0.40;
			var cx = w / 2, cy = h / 2;
			var f = R * 2.6;
			pointer.x += (pointer.tx - pointer.x) * 0.05;
			pointer.y += (pointer.ty - pointer.y) * 0.05;
			var ay = time * 0.00012 + pointer.x;
			var ax = -0.38 + pointer.y;
			var sy = Math.sin(ay), cyy = Math.cos(ay), sx = Math.sin(ax), cxx = Math.cos(ax);

			ctx.clearRect(0, 0, w, h);

			for (var i = 0; i < count; i++) {
				var p = pts[i];
				var x1 = p.x * cyy - p.z * sy;
				var z1 = p.x * sy + p.z * cyy;
				var y2 = p.y * cxx - z1 * sx;
				var z2 = p.y * sx + z1 * cxx;
				var k = f / (f + z2 * R);
				proj[i] = { x: cx + x1 * R * k, y: cy + y2 * R * k, z: z2, k: k };
			}

			// Edges.
			ctx.lineWidth = 0.8;
			for (var e = 0; e < edges.length; e++) {
				var A = proj[edges[e][0]], B = proj[edges[e][1]];
				var depth = (A.z + B.z) / 2; // -1 (front) .. 1 (back)
				var alpha = (c.dark ? 0.28 : 0.22) * (0.25 + (1 - depth) * 0.5);
				ctx.strokeStyle = rgba(c.primary, alpha);
				ctx.beginPath();
				ctx.moveTo(A.x, A.y);
				ctx.lineTo(B.x, B.y);
				ctx.stroke();
			}

			// Points.
			for (var j = 0; j < count; j++) {
				var P = proj[j];
				var front = (1 - P.z) / 2; // 0 back .. 1 front
				var twinkle = 0.7 + 0.3 * Math.sin(time * 0.002 + pts[j].seed * 20);
				var size = (0.6 + front * 1.6) * P.k;
				ctx.fillStyle = front > 0.62 ? rgba(c.hi, (0.35 + front * 0.6) * twinkle) : rgba(c.primary, 0.15 + front * 0.5);
				ctx.beginPath();
				ctx.arc(P.x, P.y, size, 0, Math.PI * 2);
				ctx.fill();
			}

			if (reduce) { return; }

			// Pulses travelling along edges.
			if (time - lastPulse > 90 && pulses.length < 26) {
				lastPulse = time;
				pulses.push({ e: (Math.random() * edges.length) | 0, t: 0, v: 0.012 + Math.random() * 0.02 });
			}
			for (var q = pulses.length - 1; q >= 0; q--) {
				var pu = pulses[q];
				pu.t += pu.v;
				if (pu.t >= 1) { pulses.splice(q, 1); continue; }
				var E = edges[pu.e], PA = proj[E[0]], PB = proj[E[1]];
				if ((PA.z + PB.z) / 2 > 0.2) { continue; }
				var px = PA.x + (PB.x - PA.x) * pu.t, py = PA.y + (PB.y - PA.y) * pu.t;
				var g = ctx.createRadialGradient(px, py, 0, px, py, 7);
				g.addColorStop(0, rgba(c.hi, 0.95));
				g.addColorStop(1, rgba(c.hi, 0));
				ctx.fillStyle = g;
				ctx.beginPath();
				ctx.arc(px, py, 7, 0, Math.PI * 2);
				ctx.fill();
			}

			// Transaction arcs.
			if (time - lastArc > 1400 && arcs.length < 4) {
				lastArc = time;
				var i1 = (Math.random() * count) | 0, i2 = (Math.random() * count) | 0;
				if (i1 !== i2) { arcs.push({ a: pts[i1], b: pts[i2], t: 0 }); }
			}
			for (var r = arcs.length - 1; r >= 0; r--) {
				var arc = arcs[r];
				arc.t += 0.012;
				if (arc.t > 1.6) { arcs.splice(r, 1); continue; }
				var head = Math.min(1, arc.t);
				var tail = Math.max(0, arc.t - 0.6);
				var fade = arc.t > 1 ? 1 - (arc.t - 1) / 0.6 : 1;
				ctx.beginPath();
				var steps = 28, started = false, hx = 0, hy = 0, hz = 0;
				for (var st = 0; st <= steps; st++) {
					var tt = tail + (head - tail) * (st / steps);
					var sp = slerp(arc.a, arc.b, tt);
					var lift = 1 + Math.sin(Math.PI * tt) * 0.28;
					var X = sp.x * lift, Y = sp.y * lift, Z = sp.z * lift;
					var x1b = X * cyy - Z * sy;
					var z1b = X * sy + Z * cyy;
					var y2b = Y * cxx - z1b * sx;
					var z2b = Y * sx + z1b * cxx;
					var kb = f / (f + z2b * R);
					var sxp = cx + x1b * R * kb, syp = cy + y2b * R * kb;
					if (!started) { ctx.moveTo(sxp, syp); started = true; } else { ctx.lineTo(sxp, syp); }
					hx = sxp; hy = syp; hz = z2b;
				}
				ctx.strokeStyle = rgba(c.hi, 0.55 * fade);
				ctx.lineWidth = 1.4;
				ctx.stroke();
				if (arc.t <= 1 && hz < 0.6) {
					ctx.fillStyle = rgba(c.hi, 0.95);
					ctx.beginPath();
					ctx.arc(hx, hy, 2.6, 0, Math.PI * 2);
					ctx.fill();
				}
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Particle field
	 * ---------------------------------------------------------------- */
	function particles(canvas) {
		var nodes = [];
		var mouse = { x: -9999, y: -9999 };
		var zone = canvas.closest('.mm-hero') || canvas;
		zone.addEventListener('pointermove', function (e) {
			var r = canvas.getBoundingClientRect();
			mouse.x = e.clientX - r.left;
			mouse.y = e.clientY - r.top;
		});
		zone.addEventListener('pointerleave', function () { mouse.x = mouse.y = -9999; });

		Runner(canvas, function (s) {
			var n = Math.min(140, Math.round((s.w * s.h) / 14000 * density));
			nodes = [];
			for (var i = 0; i < n; i++) {
				nodes.push({ x: Math.random() * s.w, y: Math.random() * s.h, vx: (Math.random() - 0.5) * 0.25, vy: (Math.random() - 0.5) * 0.25, r: Math.random() * 1.6 + 0.4 });
			}
		}, function (ctx, s) {
			var c = s.colors;
			ctx.clearRect(0, 0, s.w, s.h);
			var maxD = 120;
			for (var i = 0; i < nodes.length; i++) {
				var a = nodes[i];
				if (!reduce) {
					a.x += a.vx; a.y += a.vy;
					if (a.x < 0 || a.x > s.w) { a.vx *= -1; }
					if (a.y < 0 || a.y > s.h) { a.vy *= -1; }
					var mx = a.x - mouse.x, my = a.y - mouse.y, md = Math.sqrt(mx * mx + my * my);
					if (md < 110) { a.x += mx / md * 1.2; a.y += my / md * 1.2; }
				}
				for (var j = i + 1; j < nodes.length; j++) {
					var b = nodes[j];
					var dx = a.x - b.x, dy = a.y - b.y;
					var d = dx * dx + dy * dy;
					if (d < maxD * maxD) {
						ctx.strokeStyle = rgba(c.primary, (1 - Math.sqrt(d) / maxD) * (c.dark ? 0.32 : 0.22));
						ctx.lineWidth = 0.7;
						ctx.beginPath();
						ctx.moveTo(a.x, a.y);
						ctx.lineTo(b.x, b.y);
						ctx.stroke();
					}
				}
				ctx.fillStyle = rgba(c.hi, c.dark ? 0.8 : 0.6);
				ctx.beginPath();
				ctx.arc(a.x, a.y, a.r, 0, Math.PI * 2);
				ctx.fill();
			}
		});
	}

	function init(ctx) {
		ctx = ctx || document;
		Array.prototype.forEach.call(ctx.querySelectorAll('[data-mm-sphere]'), function (c) {
			if (c.dataset.mmBound) { return; }
			c.dataset.mmBound = '1';
			sphere(c);
		});
		Array.prototype.forEach.call(ctx.querySelectorAll('[data-mm-particles]'), function (c) {
			if (c.dataset.mmBound) { return; }
			c.dataset.mmBound = '1';
			particles(c);
		});
	}

	if (MM.on) { MM.on('init', init); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(); }); } else { init(); }
})();

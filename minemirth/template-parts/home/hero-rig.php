<?php
/**
 * Animated isometric ASIC miner (pure SVG + CSS, no images).
 *
 * Faces are drawn in unit-square space and mapped onto the box with
 * matrix() transforms, so the fans spin in correct perspective.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mm-rig" aria-hidden="true">
	<svg class="mm-rig__svg" viewBox="0 0 560 480" xmlns="http://www.w3.org/2000/svg">
		<defs>
			<linearGradient id="mmRigTop" x1="0" y1="0" x2="1" y2="1">
				<stop offset="0" class="mm-rig__stop-top-a"/>
				<stop offset="1" class="mm-rig__stop-top-b"/>
			</linearGradient>
			<linearGradient id="mmRigLeft" x1="0" y1="0" x2="0" y2="1">
				<stop offset="0" class="mm-rig__stop-left-a"/>
				<stop offset="1" class="mm-rig__stop-left-b"/>
			</linearGradient>
			<linearGradient id="mmRigRight" x1="0" y1="0" x2="1" y2="1">
				<stop offset="0" class="mm-rig__stop-right-a"/>
				<stop offset="1" class="mm-rig__stop-right-b"/>
			</linearGradient>
			<radialGradient id="mmRigGlow" cx="0.5" cy="0.5" r="0.5">
				<stop offset="0" class="mm-rig__stop-glow-a"/>
				<stop offset="1" class="mm-rig__stop-glow-b"/>
			</radialGradient>
			<linearGradient id="mmRigStream" x1="0" y1="1" x2="1" y2="0">
				<stop offset="0" class="mm-rig__stop-stream-a"/>
				<stop offset="1" class="mm-rig__stop-stream-b"/>
			</linearGradient>
		</defs>

		<!-- floor glow + shadow -->
		<ellipse class="mm-rig__glow" cx="270" cy="392" rx="210" ry="58" fill="url(#mmRigGlow)"/>
		<ellipse class="mm-rig__shadow" cx="270" cy="384" rx="150" ry="30"/>

		<!-- hash stream to the chain of blocks -->
		<path class="mm-rig__stream" d="M400 214 C 440 170, 450 120, 470 82" stroke="url(#mmRigStream)"/>
		<path class="mm-rig__stream mm-rig__stream--2" d="M380 236 C 430 210, 480 170, 512 150" stroke="url(#mmRigStream)"/>

		<g class="mm-rig__float">
			<!-- top face: heatsink fins -->
			<polygon class="mm-rig__face mm-rig__face--top" points="110,170 300,96 470,166 280,240" fill="url(#mmRigTop)"/>
			<g transform="matrix(170 70 190 -74 110 170)">
				<g class="mm-rig__fins">
					<line x1="0.06" y1="0.12" x2="0.94" y2="0.12" vector-effect="non-scaling-stroke"/>
					<line x1="0.06" y1="0.24" x2="0.94" y2="0.24" vector-effect="non-scaling-stroke"/>
					<line x1="0.06" y1="0.36" x2="0.94" y2="0.36" vector-effect="non-scaling-stroke"/>
					<line x1="0.06" y1="0.48" x2="0.94" y2="0.48" vector-effect="non-scaling-stroke"/>
					<line x1="0.06" y1="0.60" x2="0.94" y2="0.60" vector-effect="non-scaling-stroke"/>
					<line x1="0.06" y1="0.72" x2="0.94" y2="0.72" vector-effect="non-scaling-stroke"/>
					<line x1="0.06" y1="0.84" x2="0.94" y2="0.84" vector-effect="non-scaling-stroke"/>
				</g>
			</g>

			<!-- left (front) face with fan -->
			<polygon class="mm-rig__face mm-rig__face--left" points="110,170 280,240 280,392 110,322" fill="url(#mmRigLeft)"/>
			<g transform="matrix(170 70 0 152 110 170)">
				<rect class="mm-rig__bezel" x="0.08" y="0.1" width="0.84" height="0.8" rx="0.08" vector-effect="non-scaling-stroke"/>
				<circle class="mm-rig__fan-ring" cx="0.5" cy="0.5" r="0.34" vector-effect="non-scaling-stroke"/>
				<g class="mm-rig__fan">
					<path class="mm-rig__blade" d="M0.5 0.5 C0.52 0.3,0.66 0.24,0.72 0.3 C0.66 0.38,0.58 0.44,0.5 0.5Z"/>
					<path class="mm-rig__blade" d="M0.5 0.5 C0.7 0.52,0.76 0.66,0.7 0.72 C0.62 0.66,0.56 0.58,0.5 0.5Z"/>
					<path class="mm-rig__blade" d="M0.5 0.5 C0.48 0.7,0.34 0.76,0.28 0.7 C0.34 0.62,0.42 0.56,0.5 0.5Z"/>
					<path class="mm-rig__blade" d="M0.5 0.5 C0.3 0.48,0.24 0.34,0.3 0.28 C0.38 0.34,0.44 0.42,0.5 0.5Z"/>
				</g>
				<circle class="mm-rig__hub" cx="0.5" cy="0.5" r="0.06"/>
			</g>

			<!-- right (side) face: status panel -->
			<polygon class="mm-rig__face mm-rig__face--right" points="280,240 470,166 470,318 280,392" fill="url(#mmRigRight)"/>
			<g transform="matrix(190 -74 0 152 280 240)">
				<rect class="mm-rig__panel" x="0.08" y="0.14" width="0.56" height="0.3" rx="0.04" vector-effect="non-scaling-stroke"/>
				<rect class="mm-rig__bar mm-rig__bar--1" x="0.12" y="0.22" width="0.36" height="0.03"/>
				<rect class="mm-rig__bar mm-rig__bar--2" x="0.12" y="0.29" width="0.26" height="0.03"/>
				<rect class="mm-rig__bar mm-rig__bar--3" x="0.12" y="0.36" width="0.44" height="0.03"/>
				<circle class="mm-rig__led mm-rig__led--ok" cx="0.78" cy="0.2" r="0.025"/>
				<circle class="mm-rig__led mm-rig__led--hash" cx="0.86" cy="0.2" r="0.025"/>
				<rect class="mm-rig__port" x="0.72" y="0.3" width="0.16" height="0.12" rx="0.015" vector-effect="non-scaling-stroke"/>
				<g class="mm-rig__vents">
					<rect x="0.08" y="0.56" width="0.84" height="0.035" rx="0.017"/>
					<rect x="0.08" y="0.64" width="0.84" height="0.035" rx="0.017"/>
					<rect x="0.08" y="0.72" width="0.84" height="0.035" rx="0.017"/>
					<rect x="0.08" y="0.80" width="0.84" height="0.035" rx="0.017"/>
				</g>
			</g>

			<!-- edge highlights -->
			<polyline class="mm-rig__edge" points="110,170 280,240 470,166"/>
			<line class="mm-rig__edge" x1="280" y1="240" x2="280" y2="392"/>
		</g>

		<!-- chain of mined blocks -->
		<g class="mm-rig__chain">
			<g class="mm-rig__block mm-rig__block--1" transform="translate(452 44)">
				<polygon class="mm-rig__cube-top" points="0,14 24,4 48,14 24,24"/>
				<polygon class="mm-rig__cube-left" points="0,14 24,24 24,52 0,42"/>
				<polygon class="mm-rig__cube-right" points="24,24 48,14 48,42 24,52"/>
			</g>
			<g class="mm-rig__block mm-rig__block--2" transform="translate(498 118)">
				<polygon class="mm-rig__cube-top" points="0,14 24,4 48,14 24,24"/>
				<polygon class="mm-rig__cube-left" points="0,14 24,24 24,52 0,42"/>
				<polygon class="mm-rig__cube-right" points="24,24 48,14 48,42 24,52"/>
			</g>
			<path class="mm-rig__link" d="M488 96 L508 122"/>
		</g>

		<!-- floating hashes -->
		<g class="mm-rig__hashes">
			<text x="356" y="120">0000…a3f9</text>
			<text x="150" y="132">nonce 0x7f3a</text>
			<text x="380" y="70">sha256d</text>
		</g>
	</svg>
</div>

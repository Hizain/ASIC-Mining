<?php
/**
 * Reference list of ASIC miner models for the profitability dashboard: manufacturer, model,
 * release month, rated hashrate and power, algorithm and cooling, from the manufacturers' published
 * specifications. Products in your shop with mining specs are added automatically and matched to
 * these models; add or change models with the "mm_miner_models" filter.
 *
 * Fields: b (brand), m (model), h (hashrate with unit), w (watts), a (algorithm key), c (cooling:
 * air|hydro|immersion), r (release, YYYY-MM).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

function mm_miner_models(): array {
	$models = array(
		// SHA-256 (Bitcoin).
		array( 'b' => 'Bitmain', 'm' => 'Antminer S23 Hyd 3U', 'h' => '1.16 PH', 'w' => 11020, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2026-01' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S23e Hyd 2U', 'h' => '865 TH', 'w' => 8650, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2026-04' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S23 XP Hyd', 'h' => '600 TH', 'w' => 5340, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2026-11' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S23 Hyd', 'h' => '580 TH', 'w' => 5510, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2026-01' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S21 XP+ Hyd', 'h' => '500 TH', 'w' => 5500, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2025-07' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S21 XP Hyd', 'h' => '473 TH', 'w' => 5676, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2024-10' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S21 Hyd', 'h' => '335 TH', 'w' => 5360, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2024-02' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S21 XP', 'h' => '270 TH', 'w' => 3645, 'a' => 'sha256', 'c' => 'air', 'r' => '2024-11' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S21 Pro', 'h' => '234 TH', 'w' => 3510, 'a' => 'sha256', 'c' => 'air', 'r' => '2024-07' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S21+', 'h' => '216 TH', 'w' => 3564, 'a' => 'sha256', 'c' => 'air', 'r' => '2025-01' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S21', 'h' => '200 TH', 'w' => 3500, 'a' => 'sha256', 'c' => 'air', 'r' => '2024-02' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer T21', 'h' => '190 TH', 'w' => 3610, 'a' => 'sha256', 'c' => 'air', 'r' => '2024-03' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S19 XP', 'h' => '140 TH', 'w' => 3010, 'a' => 'sha256', 'c' => 'air', 'r' => '2022-07' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S19k Pro', 'h' => '120 TH', 'w' => 2760, 'a' => 'sha256', 'c' => 'air', 'r' => '2023-04' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer S19j Pro', 'h' => '104 TH', 'w' => 3068, 'a' => 'sha256', 'c' => 'air', 'r' => '2021-07' ),
		array( 'b' => 'Bitdeer', 'm' => 'SealMiner A4 Ultra Hydro', 'h' => '886 TH', 'w' => 8372, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2026-05' ),
		array( 'b' => 'Bitdeer', 'm' => 'SealMiner A4 Pro Hydro', 'h' => '680 TH', 'w' => 7412, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2026-05' ),
		array( 'b' => 'Auradine', 'm' => 'Teraflux AH3880', 'h' => '600 TH', 'w' => 8700, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2024-09' ),
		array( 'b' => 'MicroBT', 'm' => 'Whatsminer M63S', 'h' => '390 TH', 'w' => 7215, 'a' => 'sha256', 'c' => 'hydro', 'r' => '2023-10' ),
		array( 'b' => 'MicroBT', 'm' => 'Whatsminer M66S', 'h' => '298 TH', 'w' => 5513, 'a' => 'sha256', 'c' => 'immersion', 'r' => '2023-10' ),
		array( 'b' => 'MicroBT', 'm' => 'Whatsminer M60S', 'h' => '186 TH', 'w' => 3441, 'a' => 'sha256', 'c' => 'air', 'r' => '2023-10' ),
		array( 'b' => 'MicroBT', 'm' => 'Whatsminer M60', 'h' => '172 TH', 'w' => 3422, 'a' => 'sha256', 'c' => 'air', 'r' => '2023-10' ),
		array( 'b' => 'MicroBT', 'm' => 'Whatsminer M50S', 'h' => '126 TH', 'w' => 3276, 'a' => 'sha256', 'c' => 'air', 'r' => '2022-07' ),
		array( 'b' => 'MicroBT', 'm' => 'Whatsminer M30S++', 'h' => '112 TH', 'w' => 3472, 'a' => 'sha256', 'c' => 'air', 'r' => '2020-10' ),
		array( 'b' => 'Canaan', 'm' => 'Avalon A1566', 'h' => '185 TH', 'w' => 3420, 'a' => 'sha256', 'c' => 'air', 'r' => '2024-10' ),
		array( 'b' => 'Canaan', 'm' => 'Avalon A1466', 'h' => '150 TH', 'w' => 3230, 'a' => 'sha256', 'c' => 'air', 'r' => '2023-10' ),
		array( 'b' => 'Canaan', 'm' => 'Avalon A1366', 'h' => '130 TH', 'w' => 3250, 'a' => 'sha256', 'c' => 'air', 'r' => '2022-11' ),
		array( 'b' => 'Canaan', 'm' => 'Avalon Q', 'h' => '90 TH', 'w' => 1674, 'a' => 'sha256', 'c' => 'air', 'r' => '2025-03' ),
		array( 'b' => 'Canaan', 'm' => 'Avalon Nano 3S', 'h' => '6 TH', 'w' => 140, 'a' => 'sha256', 'c' => 'air', 'r' => '2025-02' ),
		array( 'b' => 'Canaan', 'm' => 'Avalon Nano 3', 'h' => '4 TH', 'w' => 140, 'a' => 'sha256', 'c' => 'air', 'r' => '2024-06' ),
		array( 'b' => 'Bitaxe', 'm' => 'Bitaxe Gamma 601', 'h' => '1.2 TH', 'w' => 17, 'a' => 'sha256', 'c' => 'air', 'r' => '2024-07' ),

		// Scrypt (Litecoin + Dogecoin).
		array( 'b' => 'Bitdeer', 'm' => 'SealMiner DL1 Hydro', 'h' => '52.5 GH', 'w' => 7823, 'a' => 'scrypt', 'c' => 'hydro', 'r' => '2026-06' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer L9', 'h' => '16 GH', 'w' => 3360, 'a' => 'scrypt', 'c' => 'air', 'r' => '2024-02' ),
		array( 'b' => 'Elphapex', 'm' => 'DG1+', 'h' => '14.4 GH', 'w' => 3950, 'a' => 'scrypt', 'c' => 'air', 'r' => '2024-06' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer L7', 'h' => '9.5 GH', 'w' => 3425, 'a' => 'scrypt', 'c' => 'air', 'r' => '2021-11' ),

		// kHeavyHash (Kaspa).
		array( 'b' => 'Bitmain', 'm' => 'Antminer KS5 Pro', 'h' => '21 TH', 'w' => 3150, 'a' => 'kheavyhash', 'c' => 'air', 'r' => '2024-04' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer KS5', 'h' => '20 TH', 'w' => 3000, 'a' => 'kheavyhash', 'c' => 'air', 'r' => '2024-04' ),
		array( 'b' => 'IceRiver', 'm' => 'KS5L', 'h' => '12 TH', 'w' => 3400, 'a' => 'kheavyhash', 'c' => 'air', 'r' => '2024-03' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer KS3', 'h' => '9.4 TH', 'w' => 3500, 'a' => 'kheavyhash', 'c' => 'air', 'r' => '2023-08' ),
		array( 'b' => 'IceRiver', 'm' => 'KS3M', 'h' => '6 TH', 'w' => 3400, 'a' => 'kheavyhash', 'c' => 'air', 'r' => '2023-08' ),
		array( 'b' => 'IceRiver', 'm' => 'KS0 Ultra', 'h' => '400 GH', 'w' => 100, 'a' => 'kheavyhash', 'c' => 'air', 'r' => '2024-05' ),

		// Blake3 (Alephium).
		array( 'b' => 'Bitmain', 'm' => 'Antminer AL1 Pro', 'h' => '16.6 TH', 'w' => 3730, 'a' => 'blake3', 'c' => 'air', 'r' => '2024-09' ),
		array( 'b' => 'Goldshell', 'm' => 'AL-BOX', 'h' => '360 GH', 'w' => 180, 'a' => 'blake3', 'c' => 'air', 'r' => '2023-12' ),

		// Equihash (Zcash).
		array( 'b' => 'Bitmain', 'm' => 'Antminer Z15 Pro', 'h' => '840 kSol', 'w' => 2780, 'a' => 'equihash', 'c' => 'air', 'r' => '2023-06' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer Z15K (565k)', 'h' => '565 kSol', 'w' => 2483, 'a' => 'equihash', 'c' => 'air', 'r' => '2026-09' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer Z15K (525k)', 'h' => '525 kSol', 'w' => 2483, 'a' => 'equihash', 'c' => 'air', 'r' => '2026-08' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer Z15', 'h' => '420 kSol', 'w' => 1510, 'a' => 'equihash', 'c' => 'air', 'r' => '2020-06' ),

		// Etchash (Ethereum Classic).
		array( 'b' => 'Bitmain', 'm' => 'Antminer E9 Pro', 'h' => '3.68 GH', 'w' => 2200, 'a' => 'etchash', 'c' => 'air', 'r' => '2023-06' ),
		array( 'b' => 'Jasminer', 'm' => 'X16-Q', 'h' => '1.95 GH', 'w' => 620, 'a' => 'etchash', 'c' => 'air', 'r' => '2023-03' ),

		// X11 (Dash), RandomX (Monero), Eaglesong (Nervos).
		array( 'b' => 'Bitmain', 'm' => 'Antminer D9', 'h' => '1770 GH', 'w' => 2839, 'a' => 'x11', 'c' => 'air', 'r' => '2024-02' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer X9', 'h' => '1 MH', 'w' => 2472, 'a' => 'randomx', 'c' => 'air', 'r' => '2026-09' ),
		array( 'b' => 'Bitmain', 'm' => 'Antminer K7', 'h' => '63.5 TH', 'w' => 3080, 'a' => 'eaglesong', 'c' => 'air', 'r' => '2023-04' ),
	);
	/**
	 * Add, remove or change the reference miner models on the profitability dashboard.
	 *
	 * @param array $models Rows with b, m, h, w, a, c, r.
	 */
	return (array) apply_filters( 'mm_miner_models', $models );
}

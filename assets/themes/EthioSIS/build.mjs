/**
 * EthioSIS theme CSS builder
 *
 * Concatenates the theme's source stylesheets into the two files the app
 * actually loads (`stylesheet.css` and `stylesheet_wkhtmltopdf.css`), using
 * the same file order as `Gruntfile.js` -> cssmin.
 *
 * Why this exists: the shipped stylesheets are generated concatenations.
 * `grunt` needs `node_modules` which is not committed, so a zero-dependency
 * Node script keeps the generated files reproducible on any machine.
 *
 * Usage (from the repository root):
 *   node assets/themes/EthioSIS/build.mjs
 *
 * It is idempotent: running it on unchanged sources rewrites the same bytes.
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const here = dirname(fileURLToPath(import.meta.url));
const css = (name) => join(here, 'css', name);

/**
 * Bundle -> source files, mirroring Gruntfile.js.
 * Each entry is [ outputFile, [ inputFiles… ] ].
 */
const bundles = [
	[
		join(here, 'stylesheet.css'),
		[
			css('calendar-blue.css'),
			css('checkbox-radio.css'),
			css('colorbox.css'),
			css('legacy/colors.css'),
			css('legacy/font.css'),
			css('legacy/icons.css'),
			css('legacy/stylesheet.css'),
			css('legacy/zresponsive.css'),
			css('legacy/rtl.css'),
			css('icons-svg.css'),
			css('theme.css'),
		],
	],
	[
		join(here, 'stylesheet_wkhtmltopdf.css'),
		[
			css('legacy/colors.css'),
			css('legacy/font.css'),
			css('legacy/icons.css'),
			css('legacy/stylesheet.css'),
			css('legacy/rtl.css'),
			css('legacy/wkhtmltopdf.css'),
			css('theme.css'),
		],
	],
];

const header = '/* EthioSIS - generated concatenation. Edit files in css/ then re-run the build. */';

for (const [out, files] of bundles) {
	const parts = files.map((file) => {
		// Marker uses the path relative to the theme dir, with the original
		// Windows separator, to match the existing generated output.
		const rel = file.slice(here.length + 1).replace(/\//g, '\\');
		const body = readFileSync(file, 'utf8').replace(/\r\n/g, '\n');
		return `/* ===== ${rel} ===== */\n${body}`;
	});

	writeFileSync(out, [header, ...parts].join('\n'));
	console.log(`${out} <- ${files.length} files`);
}

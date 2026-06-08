const BLOCK_PREFIX = 'tebuto-online-terminbuchung/block/';

/**
 * @param {string[]} files
 * @returns {string[]}
 */
function toBlockRelativePaths(files) {
	return files.map((file) => {
		const normalized = file.replace(/\\/g, '/');
		const prefixIndex = normalized.indexOf(BLOCK_PREFIX);
		if (prefixIndex === -1) {
			return file;
		}

		return normalized.slice(prefixIndex + BLOCK_PREFIX.length);
	});
}

/**
 * @param {string} npmScript
 * @param {string[]} files
 * @returns {string}
 */
function blockNpmScript(npmScript, files) {
	const relativeFiles = toBlockRelativePaths(files).join(' ');
	return `npm --prefix tebuto-online-terminbuchung/block run ${npmScript} -- ${relativeFiles}`;
}

/** @type {import('lint-staged').Config} */
module.exports = {
	'tebuto-online-terminbuchung/block/src/**/*.{js,jsx,ts,tsx}': (
		files
	) => [
		blockNpmScript('format', files),
		blockNpmScript('lint:js', files),
	],
};

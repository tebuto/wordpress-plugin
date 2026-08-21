export function getTebutoData() {
	return window.tebutoData || {}
}

export function getPresets() {
	return getTebutoData().presets || []
}

/**
 * @param {'booking'|'seminars'} [variant='booking']
 * @returns {Record<string, unknown>}
 */
export function getDefaults(variant = 'booking') {
	const data = getTebutoData()
	const shared = data.defaults || {}
	if (variant === 'seminars') {
		return { ...shared, ...data.seminarsDefaults }
	}
	return shared
}

export function getConnectUrl() {
	const d = getTebutoData()
	return d.connectUrl || d.reconnectUrl || '/wp-admin/admin.php?page=tebuto-main'
}

export function getTebutoData() {
	return window.tebutoData || {}
}

export function getPresets() {
	return getTebutoData().presets || []
}

export function getDefaults() {
	return getTebutoData().defaults || {}
}

export function getConnectUrl() {
	const d = getTebutoData()
	return d.connectUrl || d.reconnectUrl || '/wp-admin/admin.php?page=tebuto-main'
}

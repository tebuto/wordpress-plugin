export function getTebutoData() {
	const data = window.tebutoData || {}
	const flag = (value) => value === true || value === 1 || value === '1' || value === 'true'
	// wp_localize_script stringifies top-level scalar values, including booleans.
	return {
		...data,
		seminarsFeatureEnabled: flag(data.seminarsFeatureEnabled),
		hasManagedUsers: flag(data.hasManagedUsers),
		isManagingUser: flag(data.isManagingUser)
	}
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

import { usePage } from '@inertiajs/vue3';

export function usePermission() {
	const { auth } = usePage().props;

	/**
	 * Check if user has a specific role
	 * @param {string|string[]} name - Role name(s) to check
	 * @returns {boolean}
	 */
	const hasRole = (name) => {
		if (typeof name === 'string') {
			return auth.user.roles.includes(name);
		}
		// If array, check if user has any of the roles
		return name.some((role) => auth.user.roles.includes(role));
	};

	/**
	 * Check if user has all of the specified roles
	 * @param {string[]} roles - Array of role names
	 * @returns {boolean}
	 */
	const hasAllRoles = (roles) => {
		return roles.every((role) => auth.user.roles.includes(role));
	};

	/**
	 * Check if user has a specific permission
	 * @param {string|string[]} name - Permission name(s) to check
	 * @returns {boolean}
	 */
	const hasPermission = (name) => {
		// Debug logging
		if (name === 'jpm.view') {
			console.log('hasPermission DEBUG for jpm.view:');
			console.log('  permissions array:', auth.user.permissions);
			console.log('  array as JSON:', JSON.stringify(auth.user.permissions));
			console.log('  array length:', auth.user.permissions.length);
			console.log('  array contents one by one:');
			auth.user.permissions.forEach((perm, idx) => {
				console.log(`    [${idx}]: "${perm}" (length: ${perm.length})`);
			});
			console.log('  looking for: "jpm.view" (length: 8)');
			console.log('  includes result:', auth.user.permissions.includes(name));
		}

		if (typeof name === 'string') {
			return auth.user.permissions.includes(name);
		}
		// If array, check if user has any of the permissions
		return name.some((permission) => auth.user.permissions.includes(permission));
	};

	/**
	 * Check if user has all of the specified permissions
	 * @param {string[]} permissions - Array of permission names
	 * @returns {boolean}
	 */
	const hasAllPermissions = (permissions) => {
		return permissions.every((permission) => auth.user.permissions.includes(permission));
	};

	/**
	 * Check if user has access to a specific page (based on role-page assignment)
	 * @param {string|string[]} page - Page name(s) to check
	 * @returns {boolean}
	 */
	const hasPageAccess = (page) => {
		if (typeof page === 'string') {
			return auth.user.pages?.includes(page) ?? false;
		}
		// If array, check if user has access to any of the pages
		return page.some((p) => auth.user.pages?.includes(p) ?? false);
	};

	/**
	 * Check if user has access to all of the specified pages
	 * @param {string[]} pages - Array of page names
	 * @returns {boolean}
	 */
	const hasAllPageAccess = (pages) => {
		return pages.every((page) => auth.user.pages?.includes(page) ?? false);
	};

	/**
	 * Check if user is administrator
	 * @returns {boolean}
	 */
	const isAdmin = () => {
		return auth.user.roles.includes('administrator');
	};

	/**
	 * Check if user has either of the specified roles or permissions
	 * @param {Object} options - { roles?: string[], permissions?: string[] }
	 * @returns {boolean}
	 */
	const can = (options) => {
		const { roles = [], permissions = [] } = options;

		const hasAnyRole = roles.length === 0 || roles.some((role) => auth.user.roles.includes(role));
		const hasAnyPermission =
			permissions.length === 0 || permissions.some((perm) => auth.user.permissions.includes(perm));

		return hasAnyRole && hasAnyPermission;
	};

	return {
		hasRole,
		hasAllRoles,
		hasPermission,
		hasAllPermissions,
		hasPageAccess,
		hasAllPageAccess,
		isAdmin,
		can,
	};
}

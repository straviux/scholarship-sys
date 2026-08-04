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
		if (!auth.user || !auth.user.permissions) {
			return false;
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
		isAdmin,
		can,
	};
}

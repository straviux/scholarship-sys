import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
	const page = usePage();

	const user = computed(() => page.props.auth?.user);
	const userRole = computed(() => {
		const u = user.value;
		if (!u) return null;

		// Handle different role structures
		if (u.roles && Array.isArray(u.roles) && u.roles.length > 0) {
			return u.roles[0].name || u.roles[0];
		}
		if (u.role && typeof u.role === 'string') {
			return u.role;
		}
		if (u.role && u.role.name) {
			return u.role.name;
		}
		return null;
	});

	const permissions = computed(() => page.props.permissions || []);

	/**
	 * Check if user has a specific permission
	 * @param {string} permission - Permission name (e.g., 'applicants.edit')
	 * @returns {boolean}
	 */
	const can = (permission) => {
		// Administrators can do everything
		if (userRole.value === 'administrator') {
			return true;
		}

		// Check if permission exists in user's permissions array
		return permissions.value.includes(permission);
	};

	/**
	 * Check if user has any of the specified permissions
	 * @param {string[]} permissionList - Array of permission names
	 * @returns {boolean}
	 */
	const canAny = (permissionList) => {
		return permissionList.some((permission) => can(permission));
	};

	/**
	 * Check if user has all of the specified permissions
	 * @param {string[]} permissionList - Array of permission names
	 * @returns {boolean}
	 */
	const canAll = (permissionList) => {
		return permissionList.every((permission) => can(permission));
	};

	/**
	 * Check if user has a specific role
	 * @param {string} role - Role name
	 * @returns {boolean}
	 */
	const isRole = (role) => {
		return userRole.value === role;
	};

	/**
	 * Check if user has any of the specified roles
	 * @param {string[]} roles - Array of role names
	 * @returns {boolean}
	 */
	const hasAnyRole = (roles) => {
		return roles.includes(userRole.value);
	};

	return {
		can,
		canAny,
		canAll,
		isRole,
		hasAnyRole,
		userRole,
		permissions,
	};
}

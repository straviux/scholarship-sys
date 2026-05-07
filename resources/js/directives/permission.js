import { usePage } from '@inertiajs/vue3';

export default {
	mounted(el, binding) {
		const page = usePage();
		const permissions = page.props.permissions || [];
		const user = page.props.auth?.user;

		// Get user role
		let userRole = null;
		if (user) {
			if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
				userRole = user.roles[0].name || user.roles[0];
			} else if (user.role && typeof user.role === 'string') {
				userRole = user.role;
			} else if (user.role && user.role.name) {
				userRole = user.role.name;
			}
		}

		// Administrator has all permissions
		if (userRole === 'administrator') {
			return;
		}

		const permission = binding.value;

		// Check if user has the permission
		if (!permissions.includes(permission)) {
			// Hide the element
			el.style.display = 'none';
			// Also remove from DOM to prevent interaction
			el.remove();
		}
	},
};

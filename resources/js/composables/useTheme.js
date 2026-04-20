import { ref, computed } from 'vue';

// ── Singleton state (shared across all components) ──
const theme = ref(
	typeof localStorage !== 'undefined' ? localStorage.getItem('theme') || 'system' : 'system',
);

// navDark: whether sidebar/topnav should appear dark
// - 'system' (default hybrid): nav is always dark, content stays light
// - 'dark' (full dark): everything dark
// - 'light': never dark
const navDark = computed(() => theme.value === 'system' || theme.value === 'dark');

function applyTheme(value) {
	if (typeof document === 'undefined') return;

	// Only 'dark' sets the global .dark class (full dark mode)
	// 'system' = hybrid: dark nav + light content — no global .dark
	// 'light' = all light — no global .dark
	document.documentElement.classList.toggle('dark', value === 'dark');
}

if (typeof window !== 'undefined') {
	// Apply on module load (prevents flash)
	applyTheme(theme.value);
}

export function useTheme() {
	function setTheme(value) {
		theme.value = value;
		localStorage.setItem('theme', value);
		applyTheme(value);
	}

	function cycleTheme() {
		const modes = ['system', 'light', 'dark'];
		const idx = modes.indexOf(theme.value);
		setTheme(modes[(idx + 1) % modes.length]);
	}

	function getThemeIcon() {
		if (theme.value === 'dark') return 'moon';
		if (theme.value === 'light') return 'sun';
		return 'desktop';
	}

	function getThemeLabel() {
		if (theme.value === 'dark') return 'Dark';
		if (theme.value === 'light') return 'Light';
		return 'System';
	}

	return { theme, navDark, setTheme, cycleTheme, getThemeIcon, getThemeLabel };
}

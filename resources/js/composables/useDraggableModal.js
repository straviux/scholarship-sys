import { ref, computed, onBeforeUnmount } from 'vue';

export function useDraggableModal() {
	const dragOffset = ref({ x: 0, y: 0 });
	const dragStart = ref(null);

	const dragStyle = computed(() => ({
		transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
	}));

	function onDragStart(e) {
		if (e.target.closest('button')) return;
		dragStart.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y };
		document.addEventListener('pointermove', onDragMove);
		document.addEventListener('pointerup', onDragEnd);
	}

	function onDragMove(e) {
		if (!dragStart.value) return;
		dragOffset.value = { x: e.clientX - dragStart.value.x, y: e.clientY - dragStart.value.y };
	}

	function onDragEnd() {
		dragStart.value = null;
		document.removeEventListener('pointermove', onDragMove);
		document.removeEventListener('pointerup', onDragEnd);
	}

	function resetDrag() {
		dragOffset.value = { x: 0, y: 0 };
	}

	onBeforeUnmount(() => {
		document.removeEventListener('pointermove', onDragMove);
		document.removeEventListener('pointerup', onDragEnd);
	});

	return { dragStyle, onDragStart, resetDrag };
}

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { onMounted, ref, watch, computed, onBeforeUnmount } from "vue";
import CourseModal from "@/Pages/Course/Modal/CourseModal.vue";
import axios from 'axios';
import { usePermission } from '@/composable/permissions';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    courses: Object,
    scholarshipPrograms: Object,
});

const { hasPermission } = usePermission();

// Local reactive copy of courses
const coursesList = ref([...props.courses]);

const scholarshipProgramsOptions = ref([]);
const programOptions = ref([]);

// Modal state
const showCourseModal = ref(false);
const editingCourse = ref(null);

// Delete confirmation
const showDeleteModal = ref(false);
const selectedCourse = ref(null);
const deleting = ref(false);

// Search and pagination
const globalFilter = ref('');
const programFilter = ref(null);
const first = ref(0);
const rows = ref(10);
const filters = ref({
    global: { value: null, matchMode: 'contains' },
    program: { value: null, matchMode: 'equals' }
});

watch(globalFilter, (newValue) => {
    filters.value.global.value = newValue;
});

watch(programFilter, (newValue) => {
    filters.value.program.value = newValue;
});

const rebuildProgramOptions = () => {
    const uniquePrograms = [...new Set(coursesList.value.map(c => c.program))].filter(Boolean);
    programOptions.value = [
        { label: 'All Programs', value: null },
        ...uniquePrograms.map(p => ({ label: p, value: p }))
    ];
};

onMounted(() => {
    scholarshipProgramsOptions.value = props.scholarshipPrograms.map(program => ({
        value: program.id,
        label: program.name
    }));
    rebuildProgramOptions();
});

const openCreate = () => {
    editingCourse.value = null;
    showCourseModal.value = true;
};

const openEdit = (course) => {
    editingCourse.value = course;
    showCourseModal.value = true;
};

// Handle save from modal (create or update)
const onCourseSaved = (course) => {
    const idx = coursesList.value.findIndex(c => c.id === course.id);
    if (idx >= 0) {
        coursesList.value[idx] = course;
    } else {
        coursesList.value.push(course);
    }
    rebuildProgramOptions();
};

const confirmDeleteCourse = (course) => {
    selectedCourse.value = course;
    showDeleteModal.value = true;
    deleteDragOffset.value = { x: 0, y: 0 };
};

const deleteCourse = async () => {
    if (!selectedCourse.value || deleting.value) return;
    deleting.value = true;

    try {
        await axios.delete(route('courses.destroy', selectedCourse.value.id));
        coursesList.value = coursesList.value.filter(c => c.id !== selectedCourse.value.id);
        rebuildProgramOptions();
        toast.success('Course deleted successfully', { position: toast.POSITION.TOP_RIGHT });
    } catch {
        toast.error('Failed to delete course', { position: toast.POSITION.TOP_RIGHT });
    } finally {
        deleting.value = false;
        showDeleteModal.value = false;
        selectedCourse.value = null;
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedCourse.value = null;
};

/* ── Delete modal drag ── */
const deleteDragOffset = ref({ x: 0, y: 0 });
const deleteDragStart = ref(null);
const deleteModalStyle = computed(() => ({
    width: '460px',
    transform: `translate(${deleteDragOffset.value.x}px, ${deleteDragOffset.value.y}px)`,
}));

function onDeleteDragStart(e) {
    if (e.target.closest('button, a')) return;
    deleteDragStart.value = { x: e.clientX - deleteDragOffset.value.x, y: e.clientY - deleteDragOffset.value.y };
    document.addEventListener('pointermove', onDeleteDragMove);
    document.addEventListener('pointerup', onDeleteDragEnd);
}
function onDeleteDragMove(e) {
    if (!deleteDragStart.value) return;
    deleteDragOffset.value = { x: e.clientX - deleteDragStart.value.x, y: e.clientY - deleteDragStart.value.y };
}
function onDeleteDragEnd() {
    deleteDragStart.value = null;
    document.removeEventListener('pointermove', onDeleteDragMove);
    document.removeEventListener('pointerup', onDeleteDragEnd);
}
onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDeleteDragMove);
    document.removeEventListener('pointerup', onDeleteDragEnd);
});
</script>

<template>

    <Head title="Courses" />

    <AdminLayout>
        <template #header>Manage Course</template>

        <div class="max-w-8xl mx-auto py-4">
            <!-- Header Panel -->
            <Panel>
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-book text-xl"></i>
                        <span class="font-semibold text-lg">Course Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center" v-if="hasPermission('courses.manage')">
                    <div class="text-gray-600">
                        Manage courses and their scholarship programs
                    </div>
                    <Button label="New Course" icon="pi pi-plus" severity="success" raised @click="openCreate" />
                </div>
            </Panel>

            <!-- Filters Section -->
            <div class="mt-6">
                <div class="flex gap-4 items-center mb-4">
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="globalFilter" placeholder="Search courses..." class="w-full" />
                        </IconField>
                    </div>
                    <div class="min-w-48">
                        <Select v-model="programFilter" :options="programOptions" optionLabel="label"
                            optionValue="value" placeholder="Filter by Program" class="w-full" />
                    </div>
                </div>
            </div>

            <!-- Courses DataTable -->
            <div class="mt-6">
                <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="coursesList" stripedRows
                    showGridlines responsiveLayout="scroll" :emptyMessage="'No data to be displayed'"
                    :globalFilterFields="['name', 'shortname', 'field_of_study', 'program', 'remarks']"
                    v-model:filters="filters" paginator :rows="rows" v-model:first="first"
                    :rowsPerPageOptions="[5, 10, 20, 50]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">Courses</h3>
                            <Tag :value="`${coursesList.length} courses`" severity="info" />
                        </div>
                    </template>

                    <Column field="id" header="#" style="width: 50px">
                        <template #body="slotProps">
                            <div class="text-center font-mono text-sm text-gray-500">
                                {{ first + slotProps.index + 1 }}
                            </div>
                        </template>
                    </Column>

                    <Column field="name" header="Course" sortable>
                        <template #body="slotProps">
                            <div class="font-semibold text-gray-800">{{ slotProps.data.name }}</div>
                            <div class="text-sm text-gray-500 font-bold">[{{ slotProps.data.shortname }}]</div>
                        </template>
                    </Column>

                    <Column field="field_of_study" header="Field of Study" sortable>
                        <template #body="slotProps">
                            <span class="text-gray-700">{{ slotProps.data.field_of_study || '-' }}</span>
                        </template>
                    </Column>

                    <Column field="program" header="Program" sortable>
                        <template #body="slotProps">
                            <span class="font-medium text-gray-700">{{ slotProps.data.program }}</span>
                        </template>
                    </Column>

                    <Column field="remarks" header="Remarks">
                        <template #body="slotProps">
                            <div class="text-gray-600 max-w-xs truncate" v-html="slotProps.data.remarks || '-'"></div>
                        </template>
                    </Column>

                    <Column field="is_active" header="Status" style="width: 120px">
                        <template #body="slotProps">
                            <Chip :label="slotProps.data.is_active ? 'Active' : 'Inactive'"
                                :severity="slotProps.data.is_active ? 'success' : 'secondary'" />
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 160px">
                        <template #body="slotProps">
                            <div class="flex gap-2 justify-center" v-if="hasPermission('courses.manage')">
                                <Button icon="pi pi-pen-to-square" severity="info" size="small" rounded outlined
                                    v-tooltip.top="'Edit Course'" @click="openEdit(slotProps.data)" />
                                <Button v-if="hasPermission('courses.delete')" icon="pi pi-trash" severity="danger"
                                    size="small" rounded outlined v-tooltip.top="'Delete Course'"
                                    @click="confirmDeleteCourse(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- iOS Delete Confirmation Dialog -->
        <Dialog :visible="showDeleteModal" modal @update:visible="val => !val && closeDeleteModal()"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="deleteModalStyle">
                    <!-- Nav Bar -->
                    <div class="ios-nav-bar" @pointerdown="onDeleteDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="closeDeleteModal">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Confirm Deletion</span>
                        <button class="ios-nav-btn ios-nav-action ios-nav-destructive" @click="deleteCourse"
                            :disabled="deleting">
                            {{ deleting ? 'Deleting...' : 'Delete' }}
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="ios-body" v-if="selectedCourse">
                        <!-- Warning -->
                        <div class="ios-section">
                            <div class="ios-card">
                                <div class="ios-row" style="padding: 12px 16px; gap: 12px;">
                                    <i class="pi pi-exclamation-triangle"
                                        style="font-size: 24px; color: #FF3B30; flex-shrink: 0;"></i>
                                    <div>
                                        <div
                                            style="font-size: 15px; font-weight: 600; color: #000; margin-bottom: 4px;">
                                            Permanently delete this course?
                                        </div>
                                        <div style="font-size: 13px; color: #8E8E93; line-height: 1.4;">
                                            This action cannot be undone. All associated data will be removed.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Course Info -->
                        <div class="ios-section">
                            <div class="ios-section-label">Course</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <span class="ios-row-label">Name</span>
                                    <span style="font-size: 14px; color: #FF3B30; font-weight: 600;">
                                        {{ selectedCourse.name }}
                                    </span>
                                </div>
                                <div class="ios-row">
                                    <span class="ios-row-label">Shortname</span>
                                    <span style="font-size: 13px; color: #8E8E93;">{{ selectedCourse.shortname }}</span>
                                </div>
                                <div class="ios-row ios-row-last">
                                    <span class="ios-row-label">Program</span>
                                    <span style="font-size: 13px; color: #8E8E93;">{{ selectedCourse.program }}</span>
                                </div>
                            </div>
                        </div>

                        <div style="height: 20px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Course Create/Edit Modal -->
        <CourseModal v-model:visible="showCourseModal" :course="editingCourse"
            :scholarshipProgramsOptions="scholarshipProgramsOptions" @saved="onCourseSaved" />
    </AdminLayout>
</template>

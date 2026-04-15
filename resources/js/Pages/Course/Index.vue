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

        <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <i class="pi pi-book text-blue-600 text-[2rem] short:text-[1.5rem]"></i>
                    <div>
                        <h1 class="text-2xl short:text-xl font-bold text-gray-700">Courses</h1>
                        <p class="text-sm text-gray-600">Manage courses and scholarship programs</p>
                    </div>
                </div>
            </template>
            <template #end>
                <Button v-if="hasPermission('courses.manage')" icon="pi pi-plus" label="Add Course" severity="success"
                    raised rounded size="small" @click="openCreate" />
            </template>
        </Toolbar>

        <div class="py-2">

            <!-- Search & Filters -->
            <div class="flex gap-3 items-center mb-4">
                <IconField iconPosition="left" class="flex-1 max-w-sm">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="globalFilter" placeholder="Search courses..." class="w-full" />
                </IconField>
                <Select v-model="programFilter" :options="programOptions" optionLabel="label" optionValue="value"
                    placeholder="All Programs" class="min-w-[180px]" />
                <Tag :value="`${coursesList.length} course${coursesList.length !== 1 ? 's' : ''}`"
                    severity="secondary" />
            </div>

            <!-- Courses DataTable -->
            <DataTable :value="coursesList" stripedRows showGridlines scrollable
                :globalFilterFields="['name', 'shortname', 'field_of_study', 'program', 'remarks']"
                v-model:filters="filters" paginator :rows="rows" v-model:first="first"
                :rowsPerPageOptions="[10, 25, 50]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                :currentPageReportTemplate="'{first} - {last} of {totalRecords}'">

                <Column field="name" header="Course" sortable>
                    <template #body="{ data }">
                        <div class="font-semibold text-gray-800 text-sm">{{ data.name }}</div>
                        <div class="text-[11px] text-[#8e8e93] font-mono mt-0.5" v-if="data.shortname">
                            [{{ data.shortname }}]
                        </div>
                    </template>
                </Column>

                <Column field="field_of_study" header="Field of Study" sortable>
                    <template #body="{ data }">
                        <span class="text-sm text-gray-700">{{ data.field_of_study || '\u2014' }}</span>
                    </template>
                </Column>

                <Column field="program" header="Program" sortable>
                    <template #body="{ data }">
                        <span class="text-sm font-medium text-gray-700">{{ data.program }}</span>
                    </template>
                </Column>

                <Column field="remarks" header="Remarks">
                    <template #body="{ data }">
                        <div class="text-sm text-gray-600 max-w-xs truncate" v-safe-html="data.remarks || '\u2014'">
                        </div>
                    </template>
                </Column>

                <Column field="is_active" header="Status" style="width: 100px">
                    <template #body="{ data }">
                        <span
                            class="text-[11px] font-semibold px-[9px] py-[3px] rounded-[20px] inline-block whitespace-nowrap"
                            :style="data.is_active
                                ? 'background: #d1f5e0; color: #187a3c;'
                                : 'background: #fee2e2; color: #991b1b;'">
                            {{ data.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </template>
                </Column>

                <Column header="Actions" style="width: 100px">
                    <template #body="{ data }">
                        <div class="flex gap-1.5 justify-center" v-if="hasPermission('courses.manage')">
                            <Button icon="pi pi-pencil" severity="info" size="small" rounded outlined
                                v-tooltip.top="'Edit'" @click="openEdit(data)" />
                            <Button v-if="hasPermission('courses.delete')" icon="pi pi-trash" severity="danger"
                                size="small" rounded outlined v-tooltip.top="'Delete'"
                                @click="confirmDeleteCourse(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
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

<style scoped>
:deep(.p-datatable) {
    border-radius: 1.5rem;
    overflow: hidden;
    border: 1px solid var(--p-datatable-border-color);
}

:deep(.p-datatable-table-container) {
    border-radius: 0;
    overflow: hidden;
}

:deep(.p-datatable thead tr:first-child th:first-child) {
    border-left: none;
}

:deep(.p-datatable thead tr:first-child th:last-child) {
    border-right: none;
}

:deep(.p-datatable thead tr:first-child th) {
    border-top: none;
}

:deep(.p-datatable tbody tr:last-child td) {
    border-bottom: none;
}

:deep(.p-datatable tbody tr:last-child td:first-child) {
    border-left: none;
}

:deep(.p-datatable tbody tr:last-child td:last-child) {
    border-right: none;
}

:deep(.p-paginator) {
    border: none;
    border-top: 1px solid var(--p-datatable-border-color);
}

:deep(.p-iconfield .p-inputtext) {
    border-radius: 1rem;
}

:deep(.p-select) {
    border-radius: 1rem;
}
</style>

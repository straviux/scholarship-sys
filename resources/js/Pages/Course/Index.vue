<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { onMounted, ref, watch } from "vue";
import { useStorage } from '@vueuse/core';
import CourseModal from "@/Pages/Course/Modal/CourseModal.vue";
import { router } from '@inertiajs/vue3';
import { usePermission } from '@/composable/permissions';

const props = defineProps({
    action: String,
    courses: Object,
    course: Object,
    scholarshipPrograms: Object,
});

const { hasPermission } = usePermission();

const scholarshipProgramsOptions = ref([]);
const programOptions = ref([]);

// Delete confirmation modal
const showConfirmDeleteModal = ref(false);
const selectedCourse = ref(null);

// Search and pagination
const globalFilter = ref('');
const programFilter = ref(null);
const first = ref(0);
const rows = ref(10);
const filters = ref({
    global: { value: null, matchMode: 'contains' },
    program: { value: null, matchMode: 'equals' }
});

// Watch for changes in globalFilter and update filters
watch(globalFilter, (newValue) => {
    filters.value.global.value = newValue;
});

// Watch for changes in programFilter and update filters
watch(programFilter, (newValue) => {
    filters.value.program.value = newValue;
});

onMounted(() => {
    // console.log(props.courses);
    scholarshipProgramsOptions.value = props.scholarshipPrograms.map(program => ({
        value: program.id,
        label: program.name
    }));

    // Create unique program options for filtering
    const uniquePrograms = [...new Set(props.courses.map(course => course.program))].filter(Boolean);
    programOptions.value = [
        { label: 'All Programs', value: null },
        ...uniquePrograms.map(program => ({ label: program, value: program }))
    ];
    // console.log(props.courses);
});

const editCourse = (courseId) => {
    // Navigate to the edit course page
    router.get(route("courses.index", {
        id: courseId,
        action: 'edit'
    }))
};

const confirmDeleteCourse = (course) => {
    selectedCourse.value = course;
    showConfirmDeleteModal.value = true;
};

const deleteCourse = () => {
    if (selectedCourse.value) {
        router.delete(route('courses.destroy', selectedCourse.value.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                showConfirmDeleteModal.value = false;
                selectedCourse.value = null;
            },
            onError: () => {
                showConfirmDeleteModal.value = false;
                selectedCourse.value = null;
            }
        });
    }
};

const closeDeleteModal = () => {
    showConfirmDeleteModal.value = false;
    selectedCourse.value = null;
};

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
                    <Button label="New Course" icon="pi pi-plus" severity="success" raised
                        @click="router.get(route('courses.index', { action: 'create' }))" />
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
                <DataTable :value="courses" stripedRows showGridlines responsiveLayout="scroll"
                    :emptyMessage="'No data to be displayed'"
                    :globalFilterFields="['name', 'shortname', 'program', 'remarks']" v-model:filters="filters"
                    paginator :rows="rows" v-model:first="first" :rowsPerPageOptions="[5, 10, 20, 50]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">Courses</h3>
                            <Tag :value="`${courses.length} courses`" severity="info" />
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

                    <Column field="program" header="Program" sortable>
                        <template #body="slotProps">
                            <span class="font-medium text-gray-700">{{ slotProps.data.program }}</span>
                        </template>
                    </Column>

                    <Column field="remarks" header="Remarks">
                        <template #body="slotProps">
                            <span class="text-gray-600">{{ slotProps.data.remarks || '-' }}</span>
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
                                    v-tooltip.top="'Edit Course'" @click="editCourse(slotProps.data.id)" />
                                <Button v-if="hasPermission('courses.delete')" icon="pi pi-trash" severity="danger"
                                    size="small" rounded outlined v-tooltip.top="'Delete Course'"
                                    @click="confirmDeleteCourse(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showConfirmDeleteModal" :style="{ width: '450px' }" header="Confirm Deletion"
            :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
                <div>
                    <p class="text-lg font-semibold text-gray-800 mb-2">
                        Are you sure you want to delete this course?
                    </p>
                    <div class="bg-gray-100 p-3 rounded border-l-4 border-red-500" v-if="selectedCourse">
                        <div class="font-semibold text-red-700">{{ selectedCourse.name }}</div>
                        <div class="text-sm text-gray-600">{{ selectedCourse.shortname }}</div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeDeleteModal" outlined />
                <Button label="Delete Course" severity="danger" @click="deleteCourse" />
            </template>
        </Dialog>

        <!-- CREATE SCHOLARSHIP PROGRAM MODAL -->
        <CourseModal v-if="props.action == 'create' || props.action == 'edit'" :action="props.action"
            :course="props.course" :scholarshipProgramsOptions="scholarshipProgramsOptions" />
    </AdminLayout>
</template>

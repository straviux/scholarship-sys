<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import logger from '@/utils/logger';

const toast = useToast(); const emit = defineEmits(['close', 'scholar-selected']);

// Props for edit mode
const props = defineProps({
    mode: {
        type: String,
        default: 'create', // 'create' or 'edit'
        validator: v => ['create', 'edit'].includes(v)
    },
    voucherId: {
        type: [Number, String],
        default: null
    },
    initialData: {
        type: Object,
        default: null
    },
    visible: {
        type: Boolean,
        default: false
    }
});

const step = ref(1);
const isOpen = ref(props.visible);
const selectedScholars = ref([]);
const searchQuery = ref('');
const selectAll = ref(false);
const scholars = ref([]);
const loading = ref(false);
const error = ref('');
const responsibilityCenters = ref([]);
const selectedRCParticulars = ref([]);

// Form data for all sections
const voucherData = reactive({
    voucher_number: '',
    scholars: [],
    obligations: {
        payee_type: 'scholar', // scholar or school
        payee_id: '',
        payee_name: '',
        payee_address: '',
        responsibility_center: '',
        account_code: '',
        particulars_name: '',
        particulars_description: '', // Single text field for rich content
        amount: '',
        obr_type: '' // REGULAR, FINANCIAL ASSISTANCE, or REIMBURSEMENT
    },
    disbursements: {
        type: 'disbursements', // disbursements or payroll
        explanation: '',
        los_course: '', // Optional course name for Letter of Support
        course: '', // Optional course for new List of Scholars step
        academic_year: '', // Optional academic year
        semester: '' // Optional semester
    },
    summary: {
        notes: '',
        transaction_status: 'pending' // pending, suspended, completed
    }
});

// Available schools for payee selection
const schools = ref([
    { id: 'school_1', name: 'Main School' },
    { id: 'school_2', name: 'Secondary School' }
]);

// Fetch scholars from API (now includes scholarship data)
const fetchScholars = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await axios.get('/api/scholars');
        const data = Array.isArray(response.data) ? response.data : (response.data.data || []);

        if (!data || data.length === 0) {
            logger.warn('No scholars found in response');
            scholars.value = [];
            return;
        }

        // Store scholars with scholarship data included
        scholars.value = data.map(s => ({
            ...s,
            selected: false,
            latestScholarship: {
                academic_year: s.academic_year,
                term: s.term
            }
        }));
        logger.info(`Successfully loaded ${scholars.value.length} scholars with scholarship data`);
    } catch (err) {
        error.value = `Failed to fetch scholars: ${err.message}`;
        logger.error('Error fetching scholars:', err);
        scholars.value = [];
    } finally {
        loading.value = false;
    }
};

// Filtered scholars based on search
const filteredScholars = ref([]);
const searchLoading = ref(false);
let searchTimeout = null;

// Async search function
const performSearch = async () => {
    if (!searchQuery.value.trim()) {
        // When search is cleared, show empty list (search-only mode)
        filteredScholars.value = [];
        logger.info('Search cleared, no results displayed');
        return;
    }

    searchLoading.value = true;
    try {
        const searchTerm = searchQuery.value.toLowerCase();
        logger.info(`Searching for: ${searchTerm}`);

        // Local search from already loaded scholars (API doesn't support search parameter)
        const localResults = scholars.value.filter(s =>
            (s.first_name?.toLowerCase() || '').includes(searchTerm) ||
            (s.last_name?.toLowerCase() || '').includes(searchTerm) ||
            (s.middle_name?.toLowerCase() || '').includes(searchTerm) ||
            (s.email?.toLowerCase() || '').includes(searchTerm)
        );

        filteredScholars.value = localResults;
        logger.info(`Local search found ${localResults.length} results`);

    } catch (err) {
        logger.error('Search error:', err);
        filteredScholars.value = [];
    } finally {
        searchLoading.value = false;
    }
};

// Watch search query for changes with manual debounce
watch(
    () => searchQuery.value,
    () => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        searchTimeout = setTimeout(() => {
            performSearch();
        }, 300);
    }
);

// Note: Scholars are only displayed when user searches (search-only mode)

// Toggle select all
const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
    filteredScholars.value.forEach(s => {
        s.selected = selectAll.value;
    });
    updateSelectedCount();
};

// Count selected scholars
const selectedCount = computed(() => {
    return scholars.value.filter(s => s.selected).length;
});

// Format year level by appending "YEAR" if it matches pattern like "1st", "2nd", "3rd", "4th"
const formatYearLevel = (yearLevel) => {
    if (!yearLevel || yearLevel === '---') {
        return yearLevel;
    }
    // Handle cases where year_level might have spaces or be in different format
    const trimmedLevel = String(yearLevel).trim().toLowerCase();

    // Check if it's a year level format (1st, 2nd, 3rd, 4th, etc.)
    if (/^\d+(st|nd|rd|th)$/.test(trimmedLevel)) {
        // Return with proper case and YEAR appended
        const properCase = String(yearLevel).trim().charAt(0).toUpperCase() + String(yearLevel).trim().slice(1).toLowerCase();
        return `${properCase} YEAR`;
    }

    // If it's just a number, append the ordinal suffix and YEAR
    if (/^\d+$/.test(trimmedLevel)) {
        const num = parseInt(trimmedLevel);
        let suffix = 'th';
        if (num % 10 === 1 && num % 100 !== 11) suffix = 'st';
        else if (num % 10 === 2 && num % 100 !== 12) suffix = 'nd';
        else if (num % 10 === 3 && num % 100 !== 13) suffix = 'rd';
        return `${num}${suffix} YEAR`;
    }

    return yearLevel;
};

// Get selected scholar details
const getSelectedScholarsList = () => {
    return scholars.value.filter(s => s.selected);
};

// Update selected count and voucherData
const updateSelectedCount = () => {
    selectedScholars.value = scholars.value.filter(s => s.selected);
    voucherData.scholars = selectedScholars.value;
};

// Get step title
const getStepTitle = () => {
    const prefix = props.mode === 'edit' ? 'Edit' : 'Create';
    const titles = [
        `${prefix} - Select Scholars`,
        `${prefix} - Obligation Request`,
        `${prefix} - Disbursements/Payroll`,
        `${prefix} - List of Scholars`,
        `Review & ${props.mode === 'edit' ? 'Update' : 'Create'}`
    ];
    return titles[step.value - 1];
};

// Move to next step with validation
const nextStep = () => {
    if (step.value === 1 && selectedCount.value === 0) {
        error.value = 'Please select at least one scholar';
        return;
    }
    error.value = '';
    step.value++;
};

// Move to previous step
const previousStep = () => {
    if (step.value > 1) {
        step.value--;
        error.value = '';
    }
};

// Load initial data for edit mode
const loadEditData = async () => {
    if (props.mode !== 'edit' || !props.initialData) return;

    try {
        // Populate voucherData with initial data
        const data = props.initialData;

        // Set voucher number
        voucherData.voucher_number = data.voucher_number || data.number || '';

        // Set obligations
        voucherData.obligations.payee_type = data.payee_type || 'scholar';
        voucherData.obligations.payee_id = data.payee_id || '';
        voucherData.obligations.payee_name = data.payee_name || '';

        // For school payees, if payee_id is empty but payee_name exists, use payee_name as payee_id
        if (voucherData.obligations.payee_type === 'school' && !voucherData.obligations.payee_id && voucherData.obligations.payee_name) {
            voucherData.obligations.payee_id = voucherData.obligations.payee_name;
        }

        voucherData.obligations.payee_address = data.payee_address || '';
        voucherData.obligations.responsibility_center = data.responsibility_center || '';
        voucherData.obligations.account_code = data.account_code || '';
        voucherData.obligations.particulars_name = data.particulars_name || '';
        voucherData.obligations.particulars_description = data.particulars_description || '';
        voucherData.obligations.amount = data.amount || '';
        voucherData.obligations.obr_type = data.obr_type || '';

        // Set disbursements
        voucherData.disbursements.type = data.voucher_type || 'disbursements';
        voucherData.disbursements.explanation = data.explanation || '';
        voucherData.disbursements.los_course = data.los_course || '';
        voucherData.disbursements.course = data.course || '';
        voucherData.disbursements.academic_year = data.academic_year || '';
        voucherData.disbursements.semester = data.semester || '';

        // Set summary notes
        voucherData.summary.notes = data.notes || '';
        voucherData.summary.transaction_status = data.transaction_status || 'pending';

        // Mark scholars as selected based on scholar_ids or scholars relationship
        await nextTick();
        let scholarIds = [];

        // Try multiple sources for scholar IDs
        if (data.scholar_ids && Array.isArray(data.scholar_ids)) {
            scholarIds = data.scholar_ids;
        } else if (data.scholars && Array.isArray(data.scholars)) {
            // If data contains scholars array with objects that have profile_id
            scholarIds = data.scholars.map(s => typeof s === 'object' ? s.profile_id : s);
        }

        logger.info('Scholar IDs for edit:', scholarIds);

        if (scholarIds && scholarIds.length > 0) {
            scholars.value.forEach(s => {
                s.selected = scholarIds.some(sid =>
                    (typeof sid === 'object' ? sid.profile_id : sid) === s.profile_id
                );
            });
            updateSelectedCount();

            // In edit mode, populate filteredScholars with the selected scholars for display
            filteredScholars.value = scholars.value.filter(s => s.selected);
            logger.info(`Selected ${selectedScholars.value.length} scholars in edit mode`);
        }

        logger.info('Edit data loaded successfully');
    } catch (err) {
        logger.error('Error loading edit data:', err);
        error.value = 'Failed to load voucher data';
    }
};

// Handle submit (Final step)
const handleSubmit = async () => {
    loading.value = true;
    error.value = '';

    try {
        // Build payee_name based on payee_type
        let payeeName = voucherData.obligations.payee_name;

        if (!payeeName) {
            if (voucherData.obligations.payee_type === 'scholar' && voucherData.obligations.payee_id) {
                const selectedScholar = voucherData.scholars.find(s => s.profile_id === voucherData.obligations.payee_id);
                payeeName = selectedScholar ? `${selectedScholar.first_name} ${selectedScholar.last_name}` : '';
                // Add & CO. if multiple scholars
                if (voucherData.scholars.length > 1) {
                    payeeName += ' & CO.';
                }
            } else if (voucherData.obligations.payee_type === 'school') {
                // For school, payee_id contains the school name/identifier
                payeeName = voucherData.obligations.payee_id;
            }
        }

        const payload = {
            voucher_type: voucherData.disbursements.type,
            explanation: voucherData.disbursements.explanation,
            los_course: voucherData.disbursements.los_course,
            course: voucherData.disbursements.course,
            academic_year: voucherData.disbursements.academic_year,
            semester: voucherData.disbursements.semester,
            payee_type: voucherData.obligations.payee_type,
            payee_id: voucherData.obligations.payee_id,
            payee_name: payeeName,
            payee_address: voucherData.obligations.payee_address,
            responsibility_center: voucherData.obligations.responsibility_center,
            account_code: voucherData.obligations.account_code,
            particulars_name: voucherData.obligations.particulars_name,
            particulars_description: voucherData.obligations.particulars_description,
            amount: parseFloat(voucherData.obligations.amount) || 0,
            obr_type: voucherData.obligations.obr_type,
            notes: voucherData.summary.notes,
            transaction_status: voucherData.summary.transaction_status
        };

        // For scholar_ids, send as JSON string if it's an array, otherwise as array/object
        const selectedScholars = voucherData.scholars.filter(s => s.selected);
        if (selectedScholars.length > 0) {
            payload.scholar_ids = selectedScholars.map(s => s.profile_id);
        } else {
            payload.scholar_ids = [];
        }

        logger.info(`Submitting ${props.mode} voucher payload:`, payload);

        // Use PUT for edit, POST for create
        const url = props.mode === 'edit' ? `/api/vouchers/${props.voucherId}` : '/api/vouchers';
        const method = props.mode === 'edit' ? 'put' : 'post';
        const response = await axios({
            method,
            url,
            data: payload
        });

        // Handle different response structures
        const voucherId = response.data?.id || response.data?.data?.id;
        if (voucherId) {
            logger.info(`Voucher ${props.mode === 'edit' ? 'updated' : 'saved'} successfully:`, voucherId);
            toast.add({
                severity: 'success',
                summary: props.mode === 'edit' ? 'Voucher Updated' : 'Voucher Created',
                detail: `Voucher has been ${props.mode === 'edit' ? 'updated' : 'created'} successfully`,
                life: 3000
            });
            emit('scholar-selected', voucherData.scholars, voucherData.disbursements.type);
            closeWizard();
        } else {
            logger.error('Response from server:', response.data);
            error.value = `Failed to ${props.mode === 'edit' ? 'update' : 'save'} voucher. Please try again.`;
            logger.error('Invalid response from server - no ID returned');
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.value,
                life: 5000
            });
        }
    } catch (err) {
        const errorMessage = err.response?.data?.errors ?
            Object.entries(err.response.data.errors)
                .map(([key, msgs]) => `${key}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
                .join('; ')
            : (err.response?.data?.message || err.message);
        error.value = `Error ${props.mode === 'edit' ? 'updating' : 'saving'} voucher: ${errorMessage}`;
        logger.error(`Error ${props.mode === 'edit' ? 'updating' : 'saving'} voucher:`, err.response?.data);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.value,
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};

// Fetch responsibility centers and particulars
const fetchResponsibilityCentersAndParticulars = async () => {
    try {
        const response = await axios.get('/api/responsibility-centers');
        const data = Array.isArray(response.data) ? response.data : (response.data.data || []);
        responsibilityCenters.value = data;
        logger.info('Responsibility centers loaded:', responsibilityCenters.value.length);
    } catch (err) {
        logger.error('Failed to fetch responsibility centers:', err);
    }
};

// Get currently selected RC
const selectedRC = computed(() => {
    if (!voucherData.obligations.responsibility_center) return null;
    return responsibilityCenters.value.find(rc => rc.code === voucherData.obligations.responsibility_center);
});

// Get particulars for selected RC (memoized via computed)
const currentParticulars = computed(() => {
    return selectedRC.value?.particulars || [];
});

// Get selected particular (memoized via computed)
const selectedParticular = computed(() => {
    if (!voucherData.obligations.account_code) return null;
    return currentParticulars.value.find(p => p.account_code === voucherData.obligations.account_code);
});

// Watch for los_course changes and auto-fill course field if empty
watch(
    () => voucherData.disbursements.los_course,
    (newValue) => {
        if (newValue && !voucherData.disbursements.course) {
            voucherData.disbursements.course = newValue;
        }
    }
);

// Get selected scholar for payee
const selectedPayeeScholar = computed(() => {
    if (voucherData.obligations.payee_type !== 'scholar' || !voucherData.obligations.payee_id) {
        return null;
    }
    return voucherData.scholars.find(s => s.profile_id === voucherData.obligations.payee_id);
});

// Get payee display name
const getPayeeDisplay = () => {
    if (voucherData.obligations.payee_type === 'scholar') {
        if (selectedPayeeScholar.value) {
            const name = `${selectedPayeeScholar.value.first_name} ${selectedPayeeScholar.value.last_name}`;
            return voucherData.scholars.length > 1 ? `${name} & CO.` : name;
        }
        return '---';
    } else {
        // For school, just return the entered school name
        return voucherData.obligations.payee_id || '---';
    }
};

// Calculate total amount (per head * number of scholars)
const totalAmount = computed(() => {
    const perHeadAmount = parseFloat(voucherData.obligations.amount || 0);
    const scholarsCount = voucherData.scholars.length;
    return perHeadAmount * scholarsCount;
});

// Format amount as currency
const formatCurrency = (value) => {
    const amount = parseFloat(value || 0);
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
};

// Get RC display name
const getRCDisplay = () => {
    return selectedRC.value ? `${selectedRC.value.code}` : 'N/A';
};

// Get selected particular name
const getSelectedParticularName = () => {
    return selectedParticular.value?.name || 'N/A';
};

// Close wizard
// Reset voucherData to initial state
const resetVoucherData = () => {
    voucherData.voucher_number = '';
    voucherData.scholars = [];
    voucherData.obligations = {
        payee_type: 'scholar',
        payee_id: '',
        payee_name: '',
        payee_address: '',
        responsibility_center: '',
        account_code: '',
        particulars_name: '',
        particulars_description: '',
        amount: '',
        obr_type: ''
    };
    voucherData.disbursements = {
        type: 'disbursements',
        explanation: '',
        los_course: '',
        course: '',
        academic_year: '',
        semester: ''
    };
    voucherData.summary = {
        notes: '',
        transaction_status: 'pending'
    };
    // Deselect all scholars
    scholars.value.forEach(s => s.selected = false);
    selectedScholars.value = [];
    selectAll.value = false;
    filteredScholars.value = [];
    searchQuery.value = '';
    step.value = 1;
    error.value = '';
};

// Close wizard
const closeWizard = () => {
    isOpen.value = false;
    step.value = 1;
    resetVoucherData();
    emit('close');
};

// Sync visible prop with isOpen
watch(() => props.visible, (newVal) => {
    isOpen.value = newVal;
});

// Watch for changes to initialData in edit mode
watch(() => props.initialData, async (newData) => {
    if (props.mode === 'edit' && newData) {
        await loadEditData();
    }
});

// Quill toolbar configuration with text alignment
const quillToolbar = [
    [{ align: [] }],
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link']
];

onMounted(async () => {
    // Set up CSRF token for axios
    const page = usePage();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || page.props.csrf_token;
    if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    }

    // Fetch scholars first and wait for it to complete
    await fetchScholars();
    await fetchResponsibilityCentersAndParticulars();

    // Load edit data if in edit mode, otherwise reset for create mode
    if (props.mode === 'edit') {
        await loadEditData();
    } else {
        resetVoucherData();
    }

    // Open dialog immediately after initialization
    isOpen.value = true;
});
</script>

<template>
    <Dialog v-model:visible="isOpen" :modal="true" :draggable="true" :closable="true" :header="getStepTitle()"
        position="center" :style="{ width: '90%', maxWidth: step == 2 ? '1100px' : '640px', zIndex: 1100 }"
        @hide="closeWizard" class="p-dialog-scrollable">
        <div class="space-y-6">
            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Step {{ step }} of 5</span>
                    <span>{{ Math.round((step / 5) * 100) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        :style="{ width: (step / 5) * 100 + '%' }"></div>
                </div>
            </div>

            <!-- Step 1: Scholar Selection -->
            <div v-if="step === 1" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-3">
                        Select Scholars
                    </label>

                    <div class="space-y-3">
                        <!-- Info Banner -->
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-900"><i class="pi pi-info-circle mr-2"></i>Only active scholars
                                are displayed</p>
                        </div>

                        <!-- Search Input -->
                        <div class="relative">
                            <input v-model="searchQuery" type="text" placeholder="Search by name or email..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            <div v-if="searchLoading" class="absolute right-3 top-2.5">
                                <i class="pi pi-spin pi-spinner text-blue-600"></i>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-8">
                            <i class="pi pi-spin pi-spinner text-3xl text-blue-600"></i>
                            <p class="mt-2 text-gray-600">Loading scholars...</p>
                        </div>

                        <!-- Error Message -->
                        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                            {{ error }}
                        </div>

                        <!-- Select All Checkbox -->
                        <div v-if="!loading" class="flex items-center">
                            <input id="select-all" v-model="selectAll" type="checkbox"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                @change="toggleSelectAll" />
                            <label for="select-all" class="ml-3 text-sm font-medium text-gray-900">
                                Select All ({{ filteredScholars.length }} found)
                            </label>
                        </div>

                        <!-- Scholar List -->
                        <div v-if="!loading"
                            class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-4">
                            <div v-if="filteredScholars.length === 0" class="text-center py-8 text-gray-500">
                                <p v-if="scholars.length === 0">No scholars available</p>
                                <p v-else>No scholars match your search</p>
                            </div>
                            <div v-for="scholar in filteredScholars" :key="scholar.profile_id"
                                class="flex items-center hover:bg-gray-50 p-2 rounded">
                                <input :id="`scholar-${scholar.profile_id}`" v-model="scholar.selected" type="checkbox"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                    @change="updateSelectedCount" />
                                <label :for="`scholar-${scholar.profile_id}`"
                                    class="ml-3 text-sm text-gray-700 flex-1 cursor-pointer">
                                    <div class="font-medium">{{ scholar.first_name }} {{ scholar.middle_name }} {{
                                        scholar.last_name }}</div>
                                    <div class="text-gray-500 text-xs">
                                        <span v-if="scholar.year_level" class="uppercase">{{
                                            formatYearLevel(scholar.year_level) }}</span>
                                        <span v-else class="text-red-500">---</span>
                                        {{ scholar.course ? ' | ' + scholar.course : '' }}
                                        {{ scholar.school ? ' | ' + scholar.school : '' }}
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Selected Count -->
                        <div class="text-sm text-gray-600">
                            {{ selectedCount }} scholar(s) selected
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Obligations -->
            <div v-if="step === 2" class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                    <p v-if="props.mode === 'edit'" class="text-sm text-blue-900"><i
                            class="pi pi-info-circle mr-2"></i>Voucher Number: <span class="font-semibold">{{
                                voucherData.voucher_number }}</span></p>
                    <p v-else class="text-sm text-blue-900"><i class="pi pi-info-circle mr-2"></i>A new voucher number
                        will be generated upon saving</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                    <!-- OBR Type and Payee Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OBR Type</label>
                        <select v-model="voucherData.obligations.obr_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                            <option value="">Select OBR Type</option>
                            <option value="REGULAR">REGULAR</option>
                            <option value="FINANCIAL ASSISTANCE">FINANCIAL ASSISTANCE</option>
                            <option value="REIMBURSEMENT">REIMBURSEMENT</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payee Type</label>
                        <select v-model="voucherData.obligations.payee_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                            <option value="scholar">Scholar</option>
                            <option value="school">School</option>
                        </select>
                    </div>
                </div>

                <!-- Payee and Payee Address (wider section) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                    <!-- Payee Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payee</label>
                        <!-- Scholar Dropdown -->
                        <select v-if="voucherData.obligations.payee_type === 'scholar'"
                            v-model="voucherData.obligations.payee_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                            <option value="">Select Payee</option>
                            <option v-for="scholar in voucherData.scholars" :key="scholar.profile_id"
                                :value="scholar.profile_id">
                                {{ scholar.first_name }} {{ scholar.last_name }}{{ voucherData.scholars.length > 1 ?
                                    ` &
                                CO.` : '' }}
                            </option>
                        </select>
                        <!-- School Input -->
                        <input v-else v-model="voucherData.obligations.payee_id" type="text"
                            placeholder="Enter school name..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                    </div>

                    <!-- Payee Address -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payee Address</label>
                        <input v-model="voucherData.obligations.payee_address" type="text"
                            placeholder="Enter payee address..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:items-start">

                    <!-- Column 2: Responsibility Center, Particulars, Account Code, Amount -->
                    <div class="space-y-4">
                        <!-- Responsibility Center -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Responsibility Center</label>
                            <select v-model="voucherData.obligations.responsibility_center"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                                <option value="">Select Responsibility Center</option>
                                <option v-for="rc in responsibilityCenters" :key="rc.id" :value="rc.code">
                                    {{ rc.code }}
                                </option>
                            </select>
                        </div>

                        <!-- Particular Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Particulars</label>
                            <select v-model="voucherData.obligations.account_code"
                                :disabled="!voucherData.obligations.responsibility_center"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed cursor-pointer">
                                <option value="">Select Particular</option>
                                <option v-for="particular in currentParticulars" :key="particular.id"
                                    :value="particular.account_code">
                                    {{ particular.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Account Code Display -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Code</label>
                            <input :value="voucherData.obligations.account_code || '---'" type="text" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed" />
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-gray-600 font-medium">₱</span>
                                <input v-model="voucherData.obligations.amount" type="number" placeholder="0.00"
                                    step="0.01"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Academic Year, Term -->
                    <div class="space-y-4">
                        <!-- Academic Year -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year (Optional)</label>
                            <AcademicYearSelect v-model="voucherData.disbursements.academic_year" />
                        </div>

                        <!-- Term -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Term (Optional)</label>
                            <TermSelect v-model="voucherData.disbursements.semester" />
                        </div>
                    </div>

                    <!-- Column 3: Particulars Description (Quill Editor) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Particulars (Descriptions)</label>
                        <div class="border border-gray-300 rounded-lg overflow-hidden h-full">
                            <QuillEditor v-model:content="voucherData.obligations.particulars_description"
                                :toolbar="quillToolbar" content-type="html" theme="snow"
                                placeholder="Enter particulars description..." style="height: 300px" />
                        </div>
                    </div>

                </div>


            </div>

            <!-- Step 3: Explanation -->
            <div v-if="step === 3" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Column 1: Disbursement Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-3">
                        Disbursement Type
                    </label>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input id="disbursements" v-model="voucherData.disbursements.type" type="radio"
                                value="disbursements"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500" />
                            <label for="disbursements" class="ml-3 text-sm font-medium text-gray-900">
                                Disbursement Voucher
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input id="payroll" v-model="voucherData.disbursements.type" type="radio" value="payroll"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500" />
                            <label for="payroll" class="ml-3 text-sm font-medium text-gray-900">
                                Payroll
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Column 2-3: Explanation -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-900 mb-2">
                        Explanation
                    </label>
                    <div class="border border-gray-300 rounded-lg overflow-hidden">
                        <QuillEditor v-model:content="voucherData.disbursements.explanation" :toolbar="quillToolbar"
                            content-type="html" theme="snow" placeholder="Enter explanation..." style="height: 200px" />
                    </div>
                </div>
            </div>

            <!-- Step 4: List of Scholars -->
            <div v-if="step === 4" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-3">
                        Selected Scholars ({{ voucherData.scholars.length }})
                    </label>
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mb-4 max-h-48 overflow-y-auto">
                        <ul class="text-sm text-blue-800 space-y-2">
                            <li v-for="scholar in voucherData.scholars" :key="scholar.profile_id"
                                class="flex items-center">
                                <i class="pi pi-check text-green-600 mr-2 text-xs"></i>
                                {{ scholar.first_name }} {{ scholar.last_name }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Course -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Course (Optional)</label>
                        <input v-model="voucherData.disbursements.course" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="e.g., BS Information Technology" />
                    </div>
                </div>
            </div>

            <!-- Step 5: Review & Create -->
            <div v-if="step === 5" class="space-y-4">
                <!-- Obligations Summary -->
                <div v-if="voucherData.obligations.payee_id" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="font-medium text-gray-900 mb-3">Obligation Details</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Payee:</span>
                            <span class="font-medium text-gray-900">{{ getPayeeDisplay() }}</span>
                        </div>
                        <div v-if="voucherData.obligations.payee_address" class="flex justify-between">
                            <span class="text-gray-600 text-xs">Address:</span>
                            <p class="font-medium text-gray-900 text-sm">{{ voucherData.obligations.payee_address }}</p>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Particulars:</span>
                            <span class="font-medium text-gray-900">{{ getSelectedParticularName() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Resp. Center:</span>
                            <span class="font-medium text-gray-900">{{ getRCDisplay() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Account Code:</span>
                            <span class="font-medium text-gray-900">{{ voucherData.obligations.account_code }}</span>
                        </div>
                        <div v-if="voucherData.obligations.particulars_description"
                            class="pt-2 border-t border-gray-300">
                            <p class="text-gray-600 text-xs mb-2">Particulars (Description):</p>
                            <div class="text-gray-700 text-sm" v-html="voucherData.obligations.particulars_description">
                            </div>
                        </div>
                        <div class="pt-2 border-t border-gray-300">
                            <p class="text-gray-600 text-xs mb-2">Scholars Breakdown:</p>
                            <ol class="text-gray-700 text-sm space-y-1 ml-4 list-decimal">
                                <li v-for="(scholar, i) in voucherData.scholars" :key="scholar.profile_id"
                                    class="flex justify-between">
                                    <span>{{ scholar.first_name }} {{ scholar.last_name }}</span>
                                    <span class="font-semibold">{{ formatCurrency(voucherData.obligations.amount)
                                    }}</span>
                                </li>
                            </ol>
                            <div v-if="voucherData.scholars.length > 1"
                                class="flex justify-between mt-2 pt-2 border-t border-gray-300 text-gray-900 font-bold">
                                <span>Total Amount ({{ voucherData.scholars.length }} scholars):</span>
                                <span class="text-blue-600 text-lg">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Summary -->
                <div v-if="voucherData.summary.notes" class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <h4 class="font-medium text-yellow-900 mb-2">Notes</h4>
                    <p class="text-sm text-yellow-800">{{ voucherData.summary.notes }}</p>
                </div>

                <!-- Disbursements Summary -->
                <div v-if="voucherData.disbursements.explanation"
                    class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h4 class="font-medium text-blue-900 mb-2">{{ voucherData.disbursements.type === 'disbursements' ?
                        'Disbursements' : 'Payroll' }} Explanation</h4>
                    <p class="text-sm text-blue-800" v-html="voucherData.disbursements.explanation"></p>
                </div>

                <!-- List of Scholars Summary -->
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                    <h4 class="font-medium text-purple-900 mb-2">List of Scholars</h4>
                    <div class="text-sm text-purple-800 space-y-2">
                        <div v-if="voucherData.disbursements.course">
                            <span class="text-gray-600">Course:</span> {{ voucherData.disbursements.course }}
                        </div>
                        <div v-if="voucherData.disbursements.academic_year">
                            <span class="text-gray-600">Academic Year:</span>
                            {{ voucherData.disbursements.academic_year }}
                        </div>
                        <div v-if="voucherData.disbursements.semester">
                            <span class="text-gray-600">Term:</span>
                            {{ voucherData.disbursements.semester }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <template #footer>
            <div class="flex justify-between gap-2 pt-4 border-t">
                <button v-if="step > 1" @click="previousStep"
                    class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer">
                    Previous
                </button>
                <div v-else></div>

                <div class="space-x-3">
                    <button @click="closeWizard"
                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button v-if="step < 5" @click="nextStep" :disabled="step === 1 && selectedCount === 0"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                        Next
                    </button>
                    <button v-if="step === 5" @click="handleSubmit" :disabled="loading"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                        <span v-if="loading" class="flex items-center gap-2">
                            <i class="pi pi-spinner pi-spin"></i>
                            Saving...
                        </span>
                        <span v-else>{{ props.mode === 'edit' ? 'Update Voucher' : 'Create Voucher' }}</span>
                    </button>
                </div>
            </div>
        </template>
    </Dialog>

    <!-- Obligation Preview Drawer -->
    <Drawer :visible="isOpen && step !== 5" :header="step === 1 ? 'Selected Scholars' : 'Obligation Preview'"
        :modal="false" position="right" :closable="false" :dismissableMask="false"
        :style="{ width: '400px', zIndex: 1000 }">
        <div class="space-y-4">
            <!-- Step 1: Selected Scholars -->
            <div v-if="step === 1" class="space-y-3">
                <div v-if="selectedCount === 0" class="text-center py-8 text-gray-500">
                    <p class="text-sm">No scholars selected yet</p>
                </div>
                <div v-else class="space-y-2">
                    <div v-for="scholar in scholars.filter(s => s.selected)" :key="scholar.profile_id"
                        class="flex items-center justify-between bg-green-50 p-3 rounded border border-green-200">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">{{ scholar.first_name }} {{ scholar.last_name
                            }}
                            </div>
                            <div class="text-xs text-gray-500">
                                <span v-if="scholar.year_level" class="uppercase">{{ formatYearLevel(scholar.year_level)
                                }}</span>
                                <span v-else class="text-red-500">---</span>
                                {{ scholar.course ? ' | ' + scholar.course : '' }}
                            </div>
                        </div>
                        <button
                            @click="() => { const s = scholars.find(x => x.profile_id === scholar.profile_id); if (s) s.selected = false; updateSelectedCount(); }"
                            class="ml-2 text-red-500 hover:text-red-700 text-sm">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2-5: Obligation Preview -->
            <div v-else class="space-y-3 text-sm">
                <div class="flex justify-between pb-3 border-b border-gray-200">
                    <span class="text-gray-600">Payee:</span>
                    <span class="font-medium text-gray-900">{{ getPayeeDisplay() }}</span>
                </div>
                <div v-if="voucherData.obligations.payee_address"
                    class="flex justify-between pb-3 border-b border-gray-200">
                    <span class="text-gray-600 text-xs uppercase">Address:</span>
                    <p class="font-medium text-gray-900 text-sm mt-1">{{ voucherData.obligations.payee_address }}</p>
                </div>
                <div class="flex justify-between pb-3 border-b border-gray-200">
                    <span class="text-gray-600">Resp. Center:</span>
                    <span class="font-medium text-gray-900">{{ getRCDisplay() }}</span>
                </div>
                <div class="flex justify-between pb-3 border-b border-gray-200">
                    <span class="text-gray-600">Particulars:</span>
                    <span class="font-medium text-gray-900">{{ voucherData.obligations.particulars_name || '---'
                        }}</span>
                </div>

                <div class="flex justify-between pb-3 border-b border-gray-200">
                    <span class="text-gray-600">Account Code:</span>
                    <span class="font-medium text-gray-900">{{ voucherData.obligations.account_code || '---' }}</span>
                </div>
                <div class="py-3 border-b border-gray-200">
                    <p class="text-gray-700 font-medium mb-3 uppercase text-xs">List of Scholars</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-gray-700 text-xs">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="text-left pb-2">Scholar Name</th>
                                    <th class="text-left pb-2 px-2">Academic Year</th>
                                    <th class="text-left pb-2 px-2">Term</th>
                                    <th class="text-right pb-2">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(scholar, i) in voucherData.scholars" :key="scholar.profile_id"
                                    class="border-b border-gray-200">
                                    <td class="py-2">{{ scholar.first_name }} {{ scholar.last_name }}</td>
                                    <td class="py-2 px-2">{{ voucherData.disbursements.academic_year || '---' }}</td>
                                    <td class="py-2 px-2">{{ voucherData.disbursements.semester || '---' }}</td>
                                    <td class="py-2 text-right font-semibold">{{
                                        formatCurrency(voucherData.obligations.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-between pt-3">
                    <span class="text-gray-600 font-medium">Total Amount:</span>
                    <span class="font-bold text-gray-700 text-lg">{{ formatCurrency(totalAmount) }}</span>
                </div>
                <div v-if="voucherData.obligations.particulars_description" class="py-3 border-t border-gray-200">
                    <p class="text-gray-700 font-medium mb-2 text-xs uppercase">Particulars Description</p>
                    <div class="text-gray-700 text-sm" v-html="voucherData.obligations.particulars_description"></div>
                </div>
            </div>
        </div>
    </Drawer>
</template>

<style scoped>
/* Ensure proper dialog styling */
:deep(.p-dialog) {
    border-radius: 0.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    z-index: 1100 !important;
}

:deep(.p-dialog-mask) {
    z-index: 1100 !important;
}

:deep(.p-dialog-header) {
    background: linear-gradient(to right, #2563eb, #1d4ed8);
    color: white;
    font-weight: 600;
}

:deep(.p-dialog-content) {
    padding: 1.5rem;
}

:deep(.p-dialog-footer) {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
}

/* Quill text alignment styles for v-html preview */
:deep(.ql-align-center) {
    text-align: center;
}

:deep(.ql-align-right) {
    text-align: right;
}

:deep(.ql-align-justify) {
    text-align: justify;
}

:deep(p.ql-align-center),
:deep(div.ql-align-center) {
    text-align: center;
}

:deep(p.ql-align-right),
:deep(div.ql-align-right) {
    text-align: right;
}

:deep(p.ql-align-justify),
:deep(div.ql-align-justify) {
    text-align: justify;
}
</style>

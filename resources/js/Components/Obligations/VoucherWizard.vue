<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import logger from '@/utils/logger';
import { quickAnimateFrom } from '@/composables/useGSAPAnimation';
import { shouldAnimate } from '@/composables/useAnimationDefaults';

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
const wizardContentRef = ref(null);
const selectedScholars = ref([]);
const searchQuery = ref('');
const selectAll = ref(false);
const scholars = ref([]);
const loading = ref(false);
const error = ref('');
const responsibilityCenters = ref([]);
const selectedRCParticulars = ref([]);

// Apply-to-all scholarship amount functionality
const applyToAllChecked = ref(false);
const applyToAllAmount = ref('');

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
        year_level: '', // Optional year level override
        academic_year: '', // Optional academic year
        semester: '', // Optional semester
        school: '', // School name
        grant_provision: '' // Grant provision
    },
    summary: {
        notes: '',
        transaction_status: 'On Process'
    }
});

const transactionStatusOptions = [
    'No OBR', 'LOA', 'Irregular', 'Transferred', 'Claimed', 'Paid', 'On Process', 'Denied'
];

const grantProvisionOptions = ['Matriculation', 'Related Learning Experience', 'Tuition', 'Related Learning Experience and Tuition', 'Affiliation', 'Related Learning Experience & Affiliation'];

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

const formatScholarFullName = (scholar) => {
    if (!scholar) return '';
    return [scholar.first_name, scholar.middle_name, scholar.last_name, scholar.extension_name].filter(Boolean).join(' ');
};

// Get selected scholar details
const getSelectedScholarsList = () => {
    return scholars.value.filter(s => s.selected);
};

const normalizeId = (value) => (value === null || value === undefined ? '' : String(value));
const toUpper = (obj, key) => { if (obj[key]) obj[key] = obj[key].toUpperCase(); };
const idsEqual = (a, b) => normalizeId(a) !== '' && normalizeId(a) === normalizeId(b);

// Format scholar name with middle initial for REIMBURSEMENT OBR type
const formatScholarNameForPayee = (scholar, useMiddleInitial = false) => {
    if (!scholar) return '';

    let parts = [scholar.first_name];

    if (useMiddleInitial && scholar.middle_name) {
        // Use middle initial (first letter + period)
        const initial = scholar.middle_name.charAt(0).toUpperCase();
        parts.push(initial + '.');
    } else if (scholar.middle_name) {
        // Use full middle name
        parts.push(scholar.middle_name);
    }

    if (scholar.last_name) parts.push(scholar.last_name);
    if (scholar.extension_name) parts.push(scholar.extension_name);

    return parts.filter(Boolean).join(' ');
};

const payeeOptions = computed(() => {
    return voucherData.scholars.map((s) => ({
        ...s,
        payeeValue: normalizeId(s.profile_id)
    }));
});

// Update selected count and voucherData
const updateSelectedCount = () => {
    selectedScholars.value = scholars.value.filter(s => s.selected);
    // Initialize individual amounts if not already set
    voucherData.scholars = selectedScholars.value.map(s => ({
        ...s,
        individualAmount: s.individualAmount || parseFloat(voucherData.obligations.amount) || 0
    }));

    // Keep payee_id valid against current selected scholars
    if (voucherData.obligations.payee_type === 'scholar') {
        const hasCurrentPayee = voucherData.scholars.some(s => idsEqual(s.profile_id, voucherData.obligations.payee_id));
        if (!hasCurrentPayee) {
            voucherData.obligations.payee_id = voucherData.scholars[0] ? normalizeId(voucherData.scholars[0].profile_id) : '';
        }
    }

    // Pre-populate additional info fields from first selected scholar (only if they don't already have values)
    if (voucherData.scholars.length > 0) {
        const firstScholar = voucherData.scholars[0];
        // Only populate if fields are empty to avoid overwriting user input
        if (!voucherData.disbursements.course && firstScholar.course) {
            voucherData.disbursements.course = firstScholar.course;
        }
        if (!voucherData.disbursements.year_level && firstScholar.year_level) {
            voucherData.disbursements.year_level = firstScholar.year_level;
        }
        if (!voucherData.disbursements.school && firstScholar.school) {
            voucherData.disbursements.school = firstScholar.school;
        }
        if (!voucherData.disbursements.semester && firstScholar.term) {
            voucherData.disbursements.semester = firstScholar.term;
        }
        if (!voucherData.disbursements.academic_year && firstScholar.academic_year) {
            voucherData.disbursements.academic_year = firstScholar.academic_year;
        }
        logger.info('Pre-populated additional info from first scholar:', {
            course: voucherData.disbursements.course,
            year_level: voucherData.disbursements.year_level,
            school: voucherData.disbursements.school,
            semester: voucherData.disbursements.semester,
            academic_year: voucherData.disbursements.academic_year
        });
    }
};

// Get step title
const getStepTitle = () => {
    const prefix = props.mode === 'edit' ? 'Edit' : 'Create';
    const titles = [
        `${prefix} - Select Scholars`,
        `${prefix} - Obligation & Scholars`,
        `${prefix} - Additional Info`,
        `${prefix} - Particulars & Explanation`,
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
        voucherData.obligations.payee_id = data.payee_type === 'scholar' ? normalizeId(data.payee_id) : (data.payee_id || '');
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
        voucherData.disbursements.year_level = data.year_level || '';
        voucherData.disbursements.school = data.school || '';
        voucherData.disbursements.grant_provision = data.grant_provision || '';

        // Set summary notes
        voucherData.summary.notes = data.notes || '';
        voucherData.summary.transaction_status = data.transaction_status || 'On Process';

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

            // Set individual amounts from loaded data if available
            scholarIds.forEach(sid => {
                const scholarId = typeof sid === 'object' ? sid.profile_id : sid;
                const scholarData = voucherData.scholars.find(s => s.profile_id === scholarId);
                if (scholarData && typeof sid === 'object' && sid.amount) {
                    scholarData.individualAmount = parseFloat(sid.amount);
                }
            });

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
        const useMiddleInitial = voucherData.obligations.obr_type === 'REIMBURSEMENT';

        if (voucherData.obligations.payee_type === 'scholar') {
            const selectedScholar = voucherData.scholars.find(s => idsEqual(s.profile_id, voucherData.obligations.payee_id));
            if (selectedScholar) {
                payeeName = formatScholarNameForPayee(selectedScholar, useMiddleInitial);
            } else {
                payeeName = '';
            }
            // Always append & CO. when this is a group of scholars
            if (payeeName && voucherData.scholars.length > 1) {
                payeeName += ' & CO.';
            }
        } else if (voucherData.obligations.payee_type === 'school') {
            // For school, payee_id contains the school name/identifier
            payeeName = voucherData.obligations.payee_id;
        }

        const selectedScholars = voucherData.scholars.filter(s => s.selected);
        const computedScholarTotal = selectedScholars.reduce((sum, scholar) => {
            return sum + (parseFloat(scholar.individualAmount) || 0);
        }, 0);
        const fallbackAmount = parseFloat(voucherData.obligations.amount) || 0;

        const payload = {
            voucher_type: voucherData.disbursements.type,
            explanation: voucherData.disbursements.explanation,
            los_course: voucherData.disbursements.los_course,
            course: voucherData.disbursements.course,
            academic_year: voucherData.disbursements.academic_year,
            semester: voucherData.disbursements.semester,
            year_level: voucherData.disbursements.year_level,
            school: voucherData.disbursements.school,
            grant_provision: voucherData.disbursements.grant_provision,
            payee_type: voucherData.obligations.payee_type,
            payee_id: voucherData.obligations.payee_id,
            payee_name: payeeName,
            payee_address: voucherData.obligations.payee_address,
            responsibility_center: voucherData.obligations.responsibility_center,
            account_code: voucherData.obligations.account_code,
            particulars_name: voucherData.obligations.particulars_name,
            particulars_description: voucherData.obligations.particulars_description,
            amount: selectedScholars.length > 0 ? computedScholarTotal : fallbackAmount,
            obr_type: voucherData.obligations.obr_type,
            notes: voucherData.summary.notes,
            transaction_status: voucherData.summary.transaction_status
        };

        // For scholar_ids, include individual amounts for each scholar
        if (selectedScholars.length > 0) {
            payload.scholar_ids = selectedScholars.map(s => ({
                profile_id: s.profile_id,
                name: formatScholarNameForPayee(s, useMiddleInitial),
                amount: parseFloat(s.individualAmount) || 0
            }));
        } else {
            payload.scholar_ids = [];
        }

        logger.info(`Submitting ${props.mode} voucher payload:`, payload);

        // Use PUT for edit, POST for create
        const url = props.mode === 'edit' ? `/api/fund-transactions/${props.voucherId}` : '/api/fund-transactions';
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

const autoGeneratedParticularsDescription = ref('');
const autoGeneratedExplanation = ref('');

const toUpperOrFallback = (value, fallback = '---') => {
    const text = String(value ?? '').trim();
    return (text || fallback).toUpperCase();
};

const joinNonEmpty = (...parts) => {
    return parts
        .map(part => String(part ?? '').trim())
        .filter(Boolean)
        .join(' ')
        .replace(/\s+/g, ' ')
        .trim();
};

// Get selected scholar for payee (must be defined before primaryParticularScholar)
const selectedPayeeScholar = computed(() => {
    if (voucherData.obligations.payee_type !== 'scholar' || !voucherData.obligations.payee_id) {
        return null;
    }
    return voucherData.scholars.find(s => idsEqual(s.profile_id, voucherData.obligations.payee_id));
});

const primaryParticularScholar = computed(() => {
    return selectedPayeeScholar.value || voucherData.scholars[0] || null;
});

const particularsCourse = computed(() => {
    // Priority: Additional Info (Step 3) → Scholar data
    const course = voucherData.disbursements.course?.trim() || voucherData.disbursements.los_course?.trim();
    if (course) {
        console.log('Course Priority: Using additional info -', course);
        return course;
    }
    const scholarCourse = primaryParticularScholar.value?.course || '';
    if (scholarCourse) {
        console.log('Course Priority: Fallback to scholar -', scholarCourse);
    }
    return scholarCourse;
});

const particularsYearLevel = computed(() => {
    // Priority: Additional Info (Step 3) → Scholar data
    const yearLevel = voucherData.disbursements.year_level?.trim();
    if (yearLevel) {
        console.log('Year Level Priority: Using additional info -', yearLevel);
        return formatYearLevel(yearLevel);
    }
    const scholarYearLevel = primaryParticularScholar.value?.year_level || '';
    if (scholarYearLevel) {
        console.log('Year Level Priority: Fallback to scholar -', scholarYearLevel);
    }
    return formatYearLevel(scholarYearLevel);
});

const particularsSchool = computed(() => {
    // Priority: Additional Info (Step 3) → Payee school → Scholar data
    if (voucherData.disbursements.school?.trim()) {
        console.log('School Priority: Using additional info -', voucherData.disbursements.school);
        return voucherData.disbursements.school;
    }
    if (voucherData.obligations.payee_type === 'school') {
        const payeeSchool = voucherData.obligations.payee_id || voucherData.obligations.payee_name || '';
        if (payeeSchool) {
            console.log('School Priority: Using payee school -', payeeSchool);
            return payeeSchool;
        }
    }
    const scholarSchool = primaryParticularScholar.value?.school || '';
    if (scholarSchool) {
        console.log('School Priority: Fallback to scholar -', scholarSchool);
    }
    return scholarSchool;
});

const particularsSemester = computed(() => {
    // Priority: Additional Info (Step 3) → Scholar data
    if (voucherData.disbursements.semester?.trim()) {
        console.log('Semester Priority: Using additional info -', voucherData.disbursements.semester);
        return voucherData.disbursements.semester;
    }
    const scholarSemester = primaryParticularScholar.value?.term || '';
    if (scholarSemester) {
        console.log('Semester Priority: Fallback to scholar -', scholarSemester);
    }
    return scholarSemester;
});

const particularsAcademicYear = computed(() => {
    // Priority: Additional Info (Step 3) → Scholar data
    if (voucherData.disbursements.academic_year?.trim()) {
        console.log('Academic Year Priority: Using additional info -', voucherData.disbursements.academic_year);
        return voucherData.disbursements.academic_year;
    }
    const scholarAcademicYear = primaryParticularScholar.value?.academic_year || '';
    if (scholarAcademicYear) {
        console.log('Academic Year Priority: Fallback to scholar -', scholarAcademicYear);
    }
    return scholarAcademicYear;
});

const particularsGrantProvision = computed(() => {
    const grant = (voucherData.disbursements.grant_provision || '').trim();
    const particular = (voucherData.obligations.particulars_name || '').trim();
    console.log('Grant Provision Priority:', { grant, particular, result: grant || particular || '' });
    // Explicitly prioritize grant_provision first
    if (grant) return grant;
    if (particular) return particular;
    return '';
});

const scholarsParticularSeed = computed(() => {
    return voucherData.scholars
        .map(s => joinNonEmpty(
            normalizeId(s.profile_id),
            s.year_level,
            s.course,
            s.school,
            s.academic_year,
            s.term
        ))
        .join('|');
});

const buildParticularsDescriptionByObrType = () => {
    const obrType = (voucherData.obligations.obr_type || '').toUpperCase();
    if (!obrType) return '';

    if (obrType === 'REIMBURSEMENT') {
        const yearLevel = toUpperOrFallback(particularsYearLevel.value);
        const course = toUpperOrFallback(particularsCourse.value);
        const termAcadYear = toUpperOrFallback(joinNonEmpty(particularsSemester.value, particularsAcademicYear.value));
        const schoolLine = toUpperOrFallback(particularsSchool.value);
        console.log('REIMBURSEMENT Description Build:', { yearLevel, course, termAcadYear, schoolLine, disbYear: voucherData.disbursements.year_level, disbCourse: voucherData.disbursements.course });
        return `<p>(REIMBURSEMENT FOR ${yearLevel}, ${course} STUDENT, ${termAcadYear})</p><p>(${schoolLine})</p>`;
    }

    if (obrType === 'REGULAR') {
        const detailsLine = joinNonEmpty(
            `EDUCATIONAL ASSISTANCE FOR ${toUpperOrFallback(particularsCourse.value)} STUDENT,`,
            toUpperOrFallback(particularsSemester.value),
            toUpperOrFallback(particularsAcademicYear.value)
        );
        const grantLine = toUpperOrFallback(particularsGrantProvision.value);
        return `<p>(${detailsLine})</p><p>(${grantLine})</p>`;
    }

    if (obrType === 'FINANCIAL ASSISTANCE') {
        const lineOne = joinNonEmpty(
            `FINANCIAL ASSISTANCE FOR ${toUpperOrFallback(particularsYearLevel.value)}`,
            toUpperOrFallback(particularsAcademicYear.value)
        );
        const lineTwo = toUpperOrFallback(particularsCourse.value);
        const lineThree = toUpperOrFallback(particularsSchool.value);
        return `<p>(${lineOne})</p><p>(${lineTwo})</p><p>(${lineThree})</p>`;
    }

    return '';
};

const buildExplanationByObrType = () => {
    const disbursementType = (voucherData.disbursements.type || '').toLowerCase();
    const obrType = (voucherData.obligations.obr_type || '').toUpperCase();
    const course = toUpperOrFallback(particularsCourse.value);
    const school = toUpperOrFallback(particularsSchool.value);

    if (disbursementType === 'payroll') {
        return `<p>We acknowledge receipt of the sum shown opposite our names for EDUCATIONAL FINANCIAL ASSISTANCE FOR ${school} ${course} SCHOLARS</p>`;
    }

    if (!obrType) return '';

    const grantProvision = toUpperOrFallback(particularsGrantProvision.value);
    const yearLevel = toUpperOrFallback(particularsYearLevel.value);
    const semesterAcademicYear = toUpperOrFallback(joinNonEmpty(particularsSemester.value, particularsAcademicYear.value));

    if (obrType === 'REIMBURSEMENT') {
        return `<p>To obligate the payment for the REIMBURSEMENT OF ${grantProvision} OF ${course} STUDENT, ${yearLevel}, ${semesterAcademicYear} at ${school} as per supporting papers hereto attached in the amount of...</p>`;
    }

    if (obrType === 'REGULAR') {
        return `<p>To obligate the payment for the ${grantProvision} OF THE ${course} STUDENT, ${yearLevel}, ${semesterAcademicYear} as per supporting papers hereto attached in the amount of...</p>`;
    }

    if (obrType === 'FINANCIAL ASSISTANCE') {
        return `<p>To obligate the payment for the FINANCIAL ASSISTANCE OF THE ${course} as per supporting papers hereto attached in the amount of...</p>`;
    }

    return '';
};

// Watch for los_course changes and auto-fill course field if empty
watch(
    () => voucherData.disbursements.los_course,
    (newValue) => {
        if (newValue && !voucherData.disbursements.course) {
            voucherData.disbursements.course = newValue;
        }
    }
);

watch(
    () => selectedParticular.value?.name,
    (newName) => {
        if (newName) {
            voucherData.obligations.particulars_name = newName;
        }
    }
);

watch(
    [
        () => voucherData.obligations.obr_type,
        () => voucherData.disbursements.course,
        () => voucherData.disbursements.los_course,
        () => voucherData.disbursements.semester,
        () => voucherData.disbursements.academic_year,
        () => voucherData.disbursements.year_level,
        () => voucherData.disbursements.school,
        () => voucherData.obligations.payee_type,
        () => voucherData.obligations.payee_id,
        () => voucherData.obligations.payee_name,
        () => voucherData.obligations.particulars_name,
        () => particularsGrantProvision.value,
        () => particularsCourse.value,
        () => particularsSchool.value,
        () => particularsYearLevel.value,
        () => particularsSemester.value,
        () => particularsAcademicYear.value,
        () => scholarsParticularSeed.value
    ],
    () => {
        const generated = buildParticularsDescriptionByObrType();
        if (!generated) {
            autoGeneratedParticularsDescription.value = '';
            return;
        }

        const currentDescription = (voucherData.obligations.particulars_description || '').trim();
        if (!currentDescription || currentDescription === autoGeneratedParticularsDescription.value) {
            voucherData.obligations.particulars_description = generated;
        }
        autoGeneratedParticularsDescription.value = generated;
    }
);

watch(
    [
        () => voucherData.obligations.obr_type,
        () => voucherData.disbursements.course,
        () => voucherData.disbursements.los_course,
        () => voucherData.disbursements.semester,
        () => voucherData.disbursements.academic_year,
        () => voucherData.disbursements.year_level,
        () => voucherData.disbursements.school,
        () => voucherData.obligations.payee_type,
        () => voucherData.obligations.payee_id,
        () => voucherData.obligations.payee_name,
        () => voucherData.obligations.particulars_name,
        () => particularsGrantProvision.value,
        () => particularsCourse.value,
        () => particularsSchool.value,
        () => particularsYearLevel.value,
        () => particularsSemester.value,
        () => particularsAcademicYear.value,
        () => scholarsParticularSeed.value,
        () => voucherData.disbursements.type
    ],
    () => {
        const generated = buildExplanationByObrType();
        if (!generated) {
            autoGeneratedExplanation.value = '';
            return;
        }

        const currentExplanation = (voucherData.disbursements.explanation || '').trim();
        if (!currentExplanation || currentExplanation === autoGeneratedExplanation.value) {
            voucherData.disbursements.explanation = generated;
        }
        autoGeneratedExplanation.value = generated;
    }
);

watch(
    () => voucherData.obligations.payee_type,
    (newType, oldType) => {
        if (newType === 'school') {
            const matchesSelectedScholar = voucherData.scholars.some(s => idsEqual(s.profile_id, voucherData.obligations.payee_id));
            if (oldType === 'scholar' && matchesSelectedScholar) {
                voucherData.obligations.payee_id = '';
            }
            return;
        }

        const hasCurrentPayee = voucherData.scholars.some(s => idsEqual(s.profile_id, voucherData.obligations.payee_id));
        if (!hasCurrentPayee) {
            voucherData.obligations.payee_id = voucherData.scholars[0] ? normalizeId(voucherData.scholars[0].profile_id) : '';
        }
    }
);

// Watch grant_provision specifically to force rebuild explanation
watch(
    () => voucherData.disbursements.grant_provision,
    () => {
        const generated = buildExplanationByObrType();
        if (generated) {
            // Force rebuild explanation when grant_provision changes
            voucherData.disbursements.explanation = generated;
            autoGeneratedExplanation.value = generated;
        }
    }
);

// Watch additional info fields to force rebuild explanation and particulars description
watch(
    [
        () => voucherData.disbursements.school,
        () => voucherData.disbursements.year_level,
        () => voucherData.disbursements.course,
        () => voucherData.disbursements.semester,
        () => voucherData.disbursements.academic_year
    ],
    () => {
        console.log('Step 3 Additional Info Watch Triggered:', {
            course: voucherData.disbursements.course,
            year_level: voucherData.disbursements.year_level,
            school: voucherData.disbursements.school,
            semester: voucherData.disbursements.semester,
            academic_year: voucherData.disbursements.academic_year
        });

        // Rebuild explanation
        const explanation = buildExplanationByObrType();
        if (explanation) {
            voucherData.disbursements.explanation = explanation;
            autoGeneratedExplanation.value = explanation;
        }

        // Rebuild particulars description
        const description = buildParticularsDescriptionByObrType();
        if (description) {
            voucherData.obligations.particulars_description = description;
            autoGeneratedParticularsDescription.value = description;
        }
    }
);



// Get payee display name
const getPayeeDisplay = () => {
    if (voucherData.obligations.payee_type === 'scholar') {
        if (selectedPayeeScholar.value) {
            const useMiddleInitial = voucherData.obligations.obr_type === 'REIMBURSEMENT';
            const name = formatScholarNameForPayee(selectedPayeeScholar.value, useMiddleInitial);
            return voucherData.scholars.length > 1 ? `${name} & CO.` : name;
        }
        return '---';
    } else {
        // For school, just return the entered school name
        return voucherData.obligations.payee_id || '---';
    }
};

// Calculate total amount (sum of all individual amounts)
const totalAmount = computed(() => {
    return voucherData.scholars.reduce((sum, scholar) => {
        return sum + (parseFloat(scholar.individualAmount) || 0);
    }, 0);
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

// Apply the same amount to all scholars
const applyAmountToAll = () => {
    const amount = parseFloat(applyToAllAmount.value) || 0;
    if (amount > 0 && voucherData.scholars.length > 0) {
        voucherData.scholars.forEach(scholar => {
            scholar.individualAmount = amount;
        });
    }
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
        year_level: '',
        academic_year: '',
        semester: ''
    };
    voucherData.summary = {
        notes: '',
        transaction_status: 'On Process'
    };
    autoGeneratedParticularsDescription.value = '';
    autoGeneratedExplanation.value = '';
    // Deselect all scholars
    scholars.value.forEach(s => s.selected = false);
    selectedScholars.value = [];
    selectAll.value = false;
    filteredScholars.value = [];
    searchQuery.value = '';
    step.value = 1;
    error.value = '';

    // Reset apply-to-all values
    applyToAllChecked.value = false;
    applyToAllAmount.value = '';
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

// GSAP: step transition â€” slide in direction of travel
watch(step, (newStep, oldStep) => {
    if (!shouldAnimate()) return;
    const dir = newStep > oldStep ? 1 : -1;
    nextTick(() => {
        const el = wizardContentRef.value;
        if (!el) return;
        quickAnimateFrom(el, { x: dir * 28, opacity: 0, duration: 0.26, clearProps: 'transform,opacity' });
    });
});


// â”€â”€â”€ Drag logic â”€â”€â”€
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);

const wizardModalStyle = computed(() => ({
    width: '90vw',
    maxWidth: step.value === 1 ? '900px' : step.value === 2 ? '1040px' : step.value === 4 ? '1200px' : '640px',
    height: 'auto',
    maxHeight: 'calc(100vh - 2rem)',
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

onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
});
</script>

<template>
    <Dialog v-model:visible="isOpen" :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @hide="closeWizard">
        <template #container>
            <div class="ios-modal" :style="wizardModalStyle">
                <!-- iOS Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button v-if="step === 1" class="ios-nav-btn ios-nav-cancel" @click="closeWizard">
                        <i class="pi pi-times"></i>
                    </button>
                    <button v-else class="ios-nav-btn ios-nav-cancel" @click="previousStep">
                        <i class="pi pi-chevron-left" style="font-size: 13px;"></i>
                    </button>
                    <span class="ios-nav-title">{{ getStepTitle() }}</span>
                    <button v-if="step < 5" class="ios-nav-btn ios-nav-action" @click="nextStep"
                        :disabled="step === 1 && selectedCount === 0">
                        <i class="pi pi-arrow-right" style="font-size: 13px;"></i>
                    </button>
                    <button v-else class="ios-nav-btn ios-nav-action" @click="handleSubmit" :disabled="loading">
                        <i v-if="loading" class="pi pi-spin pi-spinner" style="font-size: 12px; margin-right: 3px;"></i>
                        <i v-else-if="props.mode === 'edit'" class="pi pi-save" style="font-size: 13px;"></i>
                        <i v-else class="pi pi-check" style="font-size: 13px;"></i>
                    </button>
                </div>
                <div ref="wizardContentRef" class="ios-body">
                    <div class="space-y-6" style="padding-top: 12px; padding-bottom: 24px;">
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
                        <div v-if="step === 1" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <!-- Left: Search & Select -->
                            <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 space-y-3">
                                <label class="block text-sm font-medium text-gray-900">Select Scholars</label>

                                <!-- Info Banner -->
                                <div class="p-3 bg-blue-50 border border-blue-200 rounded-2xl">
                                    <p class="text-sm text-blue-900"><i class="pi pi-info-circle mr-2"></i>Only active
                                        scholars are displayed</p>
                                </div>

                                <!-- Search Input -->
                                <div class="relative">
                                    <InputText v-model="searchQuery" type="text" placeholder="Search by name..."
                                        class="w-full" />
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
                                <div v-if="error"
                                    class="p-3 bg-red-50 border border-red-200 rounded-2xl text-red-700 text-sm">
                                    {{ error }}
                                </div>

                                <!-- Select All Checkbox -->
                                <div v-if="!loading" class="flex items-center">
                                    <Checkbox id="select-all" v-model="selectAll" :binary="true"
                                        @change="toggleSelectAll" />
                                    <label for="select-all" class="ml-3 text-sm font-medium text-gray-900">
                                        Select All ({{ filteredScholars.length }} found)
                                    </label>
                                </div>

                                <!-- Scholar List -->
                                <div v-if="!loading" class="space-y-1 max-h-[420px] overflow-y-auto">
                                    <div v-if="filteredScholars.length === 0" class="text-center py-8 text-gray-500">
                                        <p v-if="scholars.length === 0">No scholars available</p>
                                        <p v-else>No scholars match your search</p>
                                    </div>
                                    <div v-for="scholar in filteredScholars" :key="scholar.profile_id"
                                        class="flex items-center hover:bg-gray-50 p-2 rounded-xl">
                                        <Checkbox :inputId="`scholar-${scholar.profile_id}`" v-model="scholar.selected"
                                            :binary="true" @change="updateSelectedCount" />
                                        <label :for="`scholar-${scholar.profile_id}`"
                                            class="ml-3 text-sm text-gray-700 flex-1 cursor-pointer">
                                            <div class="font-medium">{{ formatScholarFullName(scholar) }}</div>
                                            <div class="text-gray-500 text-xs">
                                                <span v-if="scholar.year_level" class="uppercase">{{
                                                    formatYearLevel(scholar.year_level) }}</span>
                                                <span v-else class="text-red-400">—</span>
                                                {{ scholar.course ? ' | ' + scholar.course : '' }}
                                                {{ scholar.school ? ' | ' + scholar.school : '' }}
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Selected Scholars -->
                            <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 flex flex-col">
                                <label class="block text-sm font-medium text-gray-900 mb-3">
                                    Selected Scholars
                                    <span class="ml-2 text-xs font-normal text-gray-500">({{ selectedCount }})</span>
                                </label>

                                <div v-if="selectedCount === 0"
                                    class="flex-1 flex items-center justify-center text-center py-8 text-gray-400">
                                    <div>
                                        <i class="pi pi-users text-3xl mb-2 block text-gray-300"></i>
                                        <p class="text-sm">No scholars selected yet</p>
                                    </div>
                                </div>

                                <div v-else class="space-y-2 max-h-[460px] overflow-y-auto">
                                    <div v-for="scholar in scholars.filter(s => s.selected)" :key="scholar.profile_id"
                                        class="flex items-center justify-between bg-green-50 border border-green-200 p-2.5 rounded-xl">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900 truncate">{{
                                                formatScholarFullName(scholar) }}</div>
                                            <div class="text-xs text-gray-500">
                                                <span v-if="scholar.year_level" class="uppercase">{{
                                                    formatYearLevel(scholar.year_level) }}</span>
                                                <span v-else class="text-red-400">—</span>
                                                {{ scholar.course ? ' | ' + scholar.course : '' }}
                                            </div>
                                        </div>
                                        <button
                                            @click="() => { const s = scholars.find(x => x.profile_id === scholar.profile_id); if (s) { s.selected = false; updateSelectedCount(); } }"
                                            class="ml-2 shrink-0 text-red-400 hover:text-red-600 text-xs p-1 rounded-lg hover:bg-red-50 transition-colors">
                                            <i class="pi pi-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Obligations -->
                        <div v-if="step === 2" class="space-y-4 text-gray-800">

                            <!-- OBR Type + Transaction Status -->
                            <div
                                class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4 bg-white border border-[#e5e5ea] rounded-2xl p-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">OBR Type</label>
                                    <Select v-model="voucherData.obligations.obr_type"
                                        :options="[{ value: 'REGULAR', label: 'REGULAR' }, { value: 'FINANCIAL ASSISTANCE', label: 'FINANCIAL ASSISTANCE' }, { value: 'REIMBURSEMENT', label: 'REIMBURSEMENT' }]"
                                        optionLabel="label" optionValue="value" placeholder="Select OBR Type"
                                        class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction
                                        Status</label>
                                    <Select v-model="voucherData.summary.transaction_status"
                                        :options="transactionStatusOptions" placeholder="Select status"
                                        class="w-full" />
                                </div>
                            </div>

                            <!-- Payee Type, Payee, and Payee Address in one row -->
                            <div
                                class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4 bg-white border border-[#e5e5ea] rounded-2xl p-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payee Type</label>
                                    <Select v-model="voucherData.obligations.payee_type"
                                        :options="[{ value: 'scholar', label: 'Scholar' }, { value: 'school', label: 'School' }]"
                                        optionLabel="label" optionValue="value" class="w-full" />
                                </div>

                                <!-- Payee Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payee</label>
                                    <!-- Scholar Dropdown -->
                                    <Select v-if="voucherData.obligations.payee_type === 'scholar'"
                                        v-model="voucherData.obligations.payee_id" :options="payeeOptions"
                                        optionValue="payeeValue" placeholder="Select Payee" class="w-full">
                                        <template #option="{ option }">
                                            {{ formatScholarFullName(option) }}{{ payeeOptions.length > 1 ? ` & CO.` :
                                                '' }}
                                        </template>
                                        <template #value="{ value, placeholder }">
                                            <template v-if="value != null && value !== ''">
                                                {{formatScholarFullName(payeeOptions.find(s => idsEqual(s.payeeValue,
                                                    value)))}}{{ payeeOptions.length > 1 ? ` & CO.` : '' }}
                                            </template>
                                            <span v-else>{{ placeholder }}</span>
                                        </template>
                                    </Select>
                                    <!-- School Input -->
                                    <InputText v-else v-model="voucherData.obligations.payee_id" type="text"
                                        placeholder="Enter school name..." class="w-full"
                                        @input="toUpper(voucherData.obligations, 'payee_id')"
                                        style="text-transform:uppercase" />
                                </div>

                                <!-- Payee Address -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payee Address</label>
                                    <InputText v-model="voucherData.obligations.payee_address" type="text"
                                        placeholder="Enter payee address..." class="w-full"
                                        @input="toUpper(voucherData.obligations, 'payee_address')"
                                        style="text-transform:uppercase" />
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:items-start bg-white border border-[#e5e5ea] rounded-2xl p-4">

                                <!-- Column 2: Responsibility Center, Particulars, Account Code, Amount -->
                                <div class="space-y-4">
                                    <!-- Responsibility Center -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Responsibility
                                            Center</label>
                                        <Select v-model="voucherData.obligations.responsibility_center"
                                            :options="responsibilityCenters" optionLabel="code" optionValue="code"
                                            placeholder="Select Responsibility Center" class="w-full" />
                                    </div>

                                    <!-- Particular Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Particulars</label>
                                        <Select v-model="voucherData.obligations.account_code"
                                            :disabled="!voucherData.obligations.responsibility_center"
                                            :options="currentParticulars" optionLabel="name" optionValue="account_code"
                                            placeholder="Select Particular" class="w-full" />
                                    </div>

                                    <!-- Account Code Display -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Code</label>
                                        <InputText :value="voucherData.obligations.account_code || '---'" type="text"
                                            readonly class="w-full" />
                                    </div>



                                </div>

                                <div class="space-y-4">
                                    <div class="bg-white border border-[#e5e5ea] rounded-2xl p-3">
                                        <div class="flex items-center justify-between gap-3 mb-2">
                                            <label class="text-xs font-medium text-gray-900">Selected Scholars ({{
                                                voucherData.scholars.length
                                            }})</label>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <Checkbox id="applyToAll" v-model="applyToAllChecked" :binary="true" />
                                                <label for="applyToAll"
                                                    class="text-xs font-medium text-gray-900 cursor-pointer whitespace-nowrap">
                                                    Apply Same Amount
                                                </label>
                                            </div>
                                        </div>
                                        <div v-if="applyToAllChecked" class="flex items-center gap-2 mb-2">
                                            <span class="text-gray-600 text-xs">PHP</span>
                                            <InputText v-model="applyToAllAmount" type="number" placeholder="0.00"
                                                step="0.01" class="flex-1 text-sm" />
                                            <Button label="Apply" severity="success" @click="applyAmountToAll" />
                                        </div>
                                        <div class="text-xs text-blue-800 space-y-1">
                                            <div v-for="scholar in voucherData.scholars" :key="scholar.profile_id"
                                                class="flex items-center justify-between px-2.5 py-1 bg-white rounded-xl border border-[#e5e5ea]">
                                                <div class="flex items-center flex-1">
                                                    <i class="pi pi-check text-green-600 mr-3 text-xs"></i>
                                                    <span class="font-medium text-sm">{{ formatScholarFullName(scholar)
                                                        }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-gray-600 text-xs">PHP</span>
                                                    <InputText v-model.number="scholar.individualAmount" type="number"
                                                        placeholder="0.00" step="0.01" class="w-28 text-sm" />
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="voucherData.scholars.length > 1"
                                            class="mt-3 pt-3 border-t border-blue-200 flex justify-between items-center">
                                            <span class="font-semibold text-sm text-blue-900">Total Amount:</span>
                                            <span class="text-base font-bold text-blue-600">{{
                                                formatCurrency(totalAmount)
                                                }}</span>
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- Step 3: Additional Info (Optional Fields) -->
                        <div v-if="step === 3" class="space-y-4 text-gray-800">
                            <!-- Info Banner -->
                            <div class="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-2xl px-4 py-3">
                                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <p class="text-sm text-blue-700">
                                    <span class="font-semibold">For document generation only.</span>
                                    The information entered here is used solely to generate the voucher document and
                                    will <span class="font-semibold">not</span> be saved to the scholar's profile.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Academic
                                            Year</label>
                                        <AcademicYearSelect v-model="voucherData.disbursements.academic_year" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                                        <TermSelect v-model="voucherData.disbursements.semester" />
                                    </div>
                                </div>
                                <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                                        <InputText v-model="voucherData.disbursements.course" type="text" class="w-full"
                                            placeholder="e.g., Medical Laboratory Science"
                                            @input="toUpper(voucherData.disbursements, 'course')"
                                            style="text-transform:uppercase" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                                        <InputText v-model="voucherData.disbursements.year_level" type="text"
                                            class="w-full" placeholder="e.g., 1st, 2nd"
                                            @input="toUpper(voucherData.disbursements, 'year_level')"
                                            style="text-transform:uppercase" />
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">School</label>
                                    <InputText v-model="voucherData.disbursements.school" type="text" class="w-full"
                                        placeholder="e.g., University of the Philippines"
                                        @input="toUpper(voucherData.disbursements, 'school')"
                                        style="text-transform:uppercase" />
                                </div>
                                <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Grant Provision</label>
                                    <Select v-model="voucherData.disbursements.grant_provision"
                                        :options="grantProvisionOptions" placeholder="Select grant provision"
                                        class="w-full" showClear />
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Particulars & Explanation -->
                        <div v-if="step === 4" class="space-y-4 text-gray-800">
                            <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4">
                                <label class="block text-sm font-medium text-gray-900 mb-3">Disbursement Type</label>
                                <div class="flex gap-3">
                                    <button type="button" @click="voucherData.disbursements.type = 'disbursements'"
                                        :class="[
                                            'flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all duration-200 text-left',
                                            voucherData.disbursements.type === 'disbursements'
                                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                                : 'border-[#e5e5ea] bg-gray-50 text-gray-600 hover:border-gray-300 hover:bg-gray-100'
                                        ]">
                                        <span :class="[
                                            'flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all duration-200',
                                            voucherData.disbursements.type === 'disbursements'
                                                ? 'border-indigo-500 bg-indigo-500'
                                                : 'border-gray-300 bg-white'
                                        ]">
                                            <svg v-if="voucherData.disbursements.type === 'disbursements'"
                                                class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span class="text-sm font-medium">Disbursement Voucher</span>
                                    </button>
                                    <button type="button" @click="voucherData.disbursements.type = 'payroll'" :class="[
                                        'flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all duration-200 text-left',
                                        voucherData.disbursements.type === 'payroll'
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                            : 'border-[#e5e5ea] bg-gray-50 text-gray-600 hover:border-gray-300 hover:bg-gray-100'
                                    ]">
                                        <span :class="[
                                            'flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all duration-200',
                                            voucherData.disbursements.type === 'payroll'
                                                ? 'border-indigo-500 bg-indigo-500'
                                                : 'border-gray-300 bg-white'
                                        ]">
                                            <svg v-if="voucherData.disbursements.type === 'payroll'"
                                                class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span class="text-sm font-medium">Payroll</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Particulars
                                        (Descriptions)</label>
                                    <div class="border border-gray-300 rounded-2xl overflow-hidden bg-white h-full">
                                        <QuillEditor v-model:content="voucherData.obligations.particulars_description"
                                            :toolbar="quillToolbar" content-type="html" theme="snow"
                                            placeholder="Enter particulars description..." style="height: 420px" />
                                    </div>
                                </div>

                                <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4">
                                    <label class="block text-sm font-medium text-gray-900 mb-2">Explanation</label>
                                    <div class="border border-gray-300 rounded-2xl overflow-hidden bg-white">
                                        <QuillEditor v-model:content="voucherData.disbursements.explanation"
                                            :toolbar="quillToolbar" content-type="html" theme="snow"
                                            placeholder="Enter explanation..." style="height: 420px" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Review & Create -->
                        <div v-if="step === 5" class="space-y-4">
                            <!-- Obligations Summary -->
                            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                                <h4 class="font-medium text-gray-900 mb-3">Obligation Details</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">OBR Type:</span>
                                        <span class="font-medium text-gray-900">{{ voucherData.obligations.obr_type ||
                                            '---' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payee Type:</span>
                                        <span class="font-medium text-gray-900">{{ voucherData.obligations.payee_type ||
                                            '---' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payee:</span>
                                        <span class="font-medium text-gray-900">{{ getPayeeDisplay() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 text-xs">Address:</span>
                                        <p class="font-medium text-gray-900 text-sm">{{
                                            voucherData.obligations.payee_address || '---' }}</p>
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
                                        <span class="font-medium text-gray-900">{{ voucherData.obligations.account_code
                                            || '---' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Disbursement Type:</span>
                                        <span class="font-medium text-gray-900">{{ voucherData.disbursements.type ||
                                            '---' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Course:</span>
                                        <span class="font-medium text-gray-900">{{ voucherData.disbursements.course ||
                                            '---' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Year Level:</span>
                                        <span class="font-medium text-gray-900">{{ voucherData.disbursements.year_level
                                            ||
                                            '---' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Academic Year:</span>
                                        <span class="font-medium text-gray-900">{{
                                            voucherData.disbursements.academic_year ||
                                            '---' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Term:</span>
                                        <span class="font-medium text-gray-900">{{ voucherData.disbursements.semester ||
                                            '---' }}</span>
                                    </div>
                                    <div v-if="voucherData.obligations.particulars_description"
                                        class="pt-2 border-t border-gray-300">
                                        <p class="text-gray-600 text-xs mb-2">Particulars (Description):</p>
                                        <div class="text-gray-700 text-sm"
                                            v-html="voucherData.obligations.particulars_description">
                                        </div>
                                    </div>
                                    <!-- Disbursements Summary -->
                                    <div v-if="voucherData.disbursements.explanation"
                                        class="pt-2 border-t border-gray-300">
                                        <p class="text-gray-600 text-xs mb-2">{{ voucherData.disbursements.type ===
                                            'disbursements' ?
                                            'Disbursements' : 'Payroll' }} Explanation</p>
                                        <div class="text-gray-700 text-sm"
                                            v-html="voucherData.disbursements.explanation">
                                        </div>
                                    </div>
                                    <div class="pt-2 border-t border-gray-300">
                                        <p class="text-gray-600 text-xs mb-2">Scholars Breakdown:</p>
                                        <ol class="text-gray-700 text-sm space-y-1 ml-4 list-decimal">
                                            <li v-for="(scholar, i) in voucherData.scholars" :key="scholar.profile_id"
                                                class="flex justify-between">
                                                <span>{{ formatScholarFullName(scholar) }}</span>
                                                <span class="font-semibold">{{ formatCurrency(scholar.individualAmount
                                                    || 0)
                                                    }}</span>
                                            </li>
                                        </ol>
                                        <div
                                            class="flex justify-between mt-2 pt-2 border-t border-gray-300 text-gray-900 font-bold">
                                            <span>Total Amount ({{ voucherData.scholars.length }} scholar{{
                                                voucherData.scholars.length > 1 ? 's' : '' }}):</span>
                                            <span class="text-blue-600 text-lg">{{ formatCurrency(totalAmount) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes Summary -->
                            <div v-if="voucherData.summary.notes"
                                class="bg-yellow-50 p-4 rounded-2xl border border-yellow-200">
                                <h4 class="font-medium text-yellow-900 mb-2">Notes</h4>
                                <p class="text-sm text-yellow-800">{{ voucherData.summary.notes }}</p>
                            </div>

                        </div><!-- end Step 5 -->

                    </div><!-- end space-y-6 -->
                </div><!-- end ios-body / wizardContentRef -->
            </div><!-- end ios-modal -->
        </template>
    </Dialog>
</template>

<style scoped>
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

/* ── Compact inputs & selects ── */
:deep(.p-inputtext) {
    font-size: 13px;
    padding: 6px 10px;
    min-height: unset;
}

:deep(.p-select) {
    font-size: 13px;
    min-height: unset;
}

:deep(.p-select-label) {
    font-size: 13px;
    padding: 6px 10px;
}

:deep(.p-select-dropdown) {
    width: 2rem;
}

:deep(.p-radiobutton .p-radiobutton-box) {
    width: 16px;
    height: 16px;
}

:deep(.p-checkbox .p-checkbox-box) {
    width: 16px;
    height: 16px;
}

:deep(.p-button:not(.p-button-icon-only)) {
    font-size: 13px;
    padding: 6px 12px;
}
</style>

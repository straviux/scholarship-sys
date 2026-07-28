// Shared color + icon mapping for OBR transaction statuses.
// Used by the vouchers table/details badges, the "Change Status" submenu, and the status picker.
const STATUS_META = {
    'No OBR': {
        badge: 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
        text: 'text-gray-600 dark:text-gray-400',
        icon: 'file-warning',
    },
    'LOA': {
        badge: 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200',
        text: 'text-blue-600 dark:text-blue-400',
        icon: 'send',
    },
    'Irregular': {
        badge: 'bg-orange-100 dark:bg-orange-900/50 text-orange-800 dark:text-orange-200',
        text: 'text-orange-600 dark:text-orange-400',
        icon: 'exclamation-triangle',
    },
    'Transferred': {
        badge: 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200',
        text: 'text-purple-600 dark:text-purple-400',
        icon: 'arrow-right',
    },
    'Claimed': {
        badge: 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200',
        text: 'text-indigo-600 dark:text-indigo-400',
        icon: 'hand-coins',
    },
    'Paid': {
        badge: 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200',
        text: 'text-green-600 dark:text-green-400',
        icon: 'check-circle',
    },
    'On Process': {
        badge: 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200',
        text: 'text-yellow-600 dark:text-yellow-400',
        icon: 'clock',
    },
    'Denied': {
        badge: 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200',
        text: 'text-red-600 dark:text-red-400',
        icon: 'times-circle',
    },
    'Replacement': {
        badge: 'bg-teal-100 dark:bg-teal-900/50 text-teal-800 dark:text-teal-200',
        text: 'text-teal-600 dark:text-teal-400',
        icon: 'refresh',
    },
    'Cancelled': {
        badge: 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300',
        text: 'text-slate-600 dark:text-slate-400',
        icon: 'ban',
    },
};

const DEFAULT_META = {
    badge: 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
    text: 'text-gray-600 dark:text-gray-400',
    icon: 'circle',
};

export const getStatusBadgeClass = (status) => (STATUS_META[status] || DEFAULT_META).badge;
export const getStatusTextClass = (status) => (STATUS_META[status] || DEFAULT_META).text;
export const getStatusIcon = (status) => (STATUS_META[status] || DEFAULT_META).icon;

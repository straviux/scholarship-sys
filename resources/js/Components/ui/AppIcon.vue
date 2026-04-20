<template>
    <component :is="resolvedIcon" v-bind="attrs" :size="resolvedSize" :stroke-width="strokeWidth"
        :class="computedClass" />
</template>

<script setup>
import { computed, useAttrs } from 'vue';
import * as LucideIcons from 'lucide-vue-next';
import {
    AlertCircle,
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    ArrowLeftRight,
    AtSign,
    BarChart2,
    BarChart3,
    Bell,
    Bookmark,
    BookMarked,
    BookOpen,
    Building2,
    Calendar,
    CalendarPlus,
    CheckCircle,
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    ChevronFirst,
    ChevronLast,
    Circle,
    CloudUpload,
    Settings,
    MessageSquare,
    MessageSquareMore,
    Copy,
    CreditCard,
    Database,
    DollarSign,
    Download,
    EllipsisVertical,
    Mail,
    ExternalLink,
    Eye,
    EyeOff,
    Smile,
    File,
    FileDown,
    FileEdit,
    FileSpreadsheet,
    FileType,
    FileText,
    Filter,
    Flag,
    FolderOpen,
    GraduationCap,
    Heart,
    History,
    Home,
    IdCard,
    Image,
    Inbox,
    Info,
    Key,
    Layers,
    LineChart,
    Link2Off,
    List,
    Loader2,
    Lock,
    Map,
    MapPin,
    Menu,
    Minus,
    Banknote,
    Moon,
    Pencil,
    SquarePen,
    Phone,
    PieChart,
    Pause,
    Ban,
    Plus,
    PlusCircle,
    Printer,
    QrCode,
    HelpCircle,
    RefreshCw,
    Save,
    Search,
    Shield,
    LogIn,
    LogOut,
    Sparkles,
    Star,
    Sun,
    Monitor,
    Table,
    Tag,
    Tags,
    X,
    XCircle,
    Trash2,
    Undo2,
    Unlink,
    Upload,
    User,
    UserCog,
    UserPlus,
    Users,
    Wallet,
    Maximize2,
    Minimize2,
    Wrench,
    Coins,
    BookmarkCheck,
    Laptop,
    Palette,
    ThumbsUp,
    Megaphone,
    Paperclip,
    Camera,
    Clock,
    BadgeCheck,
    Expand,
    Hash,
    Highlighter,
    Columns,
    ArrowUpNarrowWide,
    Globe,
    Smartphone,
    ShieldCheck,
    SlidersHorizontal,
    Award,
    Code,
    Folder,
    Link,
    GripVertical,
    // Navigation — extended
    ChevronsLeft,
    ChevronsRight,
    ArrowUpDown,
    TrendingUp,
    TrendingDown,
    ArrowBigUp,
    ArrowBigDown,
    Send,
    SendHorizontal,
    // Actions — extended
    PencilLine,
    Eraser,
    Scissors,
    Clipboard,
    ClipboardList,
    ClipboardCheck,
    ClipboardPen,
    // Status / Shape
    CircleCheck,
    CircleX,
    CirclePlus,
    CircleMinus,
    SquareCheck,
    SquareX,
    SquarePlus,
    SquareMinus,
    ListChecks,
    ListTodo,
    ListOrdered,
    // Data / Layout
    Receipt,
    ReceiptText,
    Table2,
    Grid2X2,
    LayoutDashboard,
    LayoutGrid,
    LayoutList,
    LayoutTemplate,
    Layers2,
    Activity,
    Zap,
    ZapOff,
    // People / Relations
    Contact,
    Handshake,
    HandCoins,
    HandHeart,
    // Education / Institution
    School,
    University,
    Landmark,
    BookCopy,
    BookUser,
    BookOpenCheck,
    NotepadText,
    NotebookPen,
    // Business / Goals
    Briefcase,
    BriefcaseBusiness,
    Package,
    Target,
    Trophy,
    Medal,
    Crown,
    // File variants
    FileCheck,
    FileWarning,
    FileImage,
    FileCode,
    FileSignature,
    FolderPlus,
    FolderClosed,
    FolderCheck,
    // Calendar / Time
    CalendarCheck,
    CalendarX,
    CalendarClock,
    CalendarDays,
    CalendarRange,
    AlarmClock,
    AlarmCheck,
    // Tech / Dev
    Terminal,
    Code2,
    Server,
    Network,
    Share2,
    Cpu,
    HardDrive,
    Wifi,
    Bug,
    // Misc
    MapPinned,
    Globe2,
    Ribbon,
    Power,
    ShieldAlert,
    ShieldX,
    Construction,
    Headphones,
    Radio,
    Tv,
} from 'lucide-vue-next';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: null },
    strokeWidth: { type: Number, default: 1.75 },
});

const attrs = useAttrs();

// Maps pi icon names (without "pi-" prefix) → Lucide component
const iconMap = {
    // Navigation / Arrows
    'angle-double-left': ChevronFirst,
    'angle-double-right': ChevronLast,
    'angle-left': ChevronLeft,
    'angle-right': ChevronRight,
    'arrow-left': ArrowLeft,
    'arrow-right': ArrowRight,
    'arrow-right-arrow-left': ArrowLeftRight,
    'chevron-down': ChevronDown,
    'chevron-left': ChevronLeft,
    'chevron-right': ChevronRight,
    'chevron-up': ChevronUp,
    'external-link': ExternalLink,

    // Actions / CRUD
    'plus': Plus,
    'plus-circle': PlusCircle,
    'minus': Minus,
    'times': X,
    'trash': Trash2,
    'pencil': Pencil,
    'pen-to-square': SquarePen,
    'file-edit': FileEdit,
    'save': Save,
    'copy': Copy,
    'upload': Upload,
    'cloud-upload': CloudUpload,
    'download': Download,
    'undo': Undo2,
    'refresh': RefreshCw,
    'sync': RefreshCw,
    'print': Printer,
    'unlink': Unlink,
    'filter': Filter,
    'filter-fill': Filter,
    'search': Search,

    // File types
    'file': File,
    'file-arrow-down': FileDown,
    'file-excel': FileSpreadsheet,
    'file-pdf': FileType,
    'file-word': FileText,
    'image': Image,
    'folder-open': FolderOpen,

    // Status / Feedback
    'check': Check,
    'check-circle': CheckCircle,
    'times-circle': XCircle,
    'exclamation-circle': AlertCircle,
    'exclamation-triangle': AlertTriangle,
    'info-circle': Info,
    'question-circle': HelpCircle,
    'question': HelpCircle,
    'spinner': Loader2,  // special: also gets animate-spin

    // User / Auth
    'user': User,
    'user-edit': UserCog,
    'user-plus': UserPlus,
    'users': Users,
    'sign-in': LogIn,
    'sign-out': LogOut,
    'lock': Lock,
    'key': Key,
    'shield': Shield,
    'id-card': IdCard,

    // Communication
    'bell': Bell,
    'envelope': Mail,
    'at': AtSign,
    'comment': MessageSquare,
    'message-square': MessageSquare,
    'comments': MessageSquareMore,
    'message-square-more': MessageSquareMore,
    'phone': Phone,

    // Content / Media
    'bookmark': Bookmark,
    'bookmark-fill': BookmarkCheck,
    'book': BookOpen,
    'book-open': BookOpen,
    'tag': Tag,
    'tags': Tags,
    'star': Star,
    'star-fill': Star,
    'heart': Heart,
    'face-smile': Smile,
    'flag': Flag,
    'flag-fill': Flag,
    'circle': Circle,
    'circle-fill': Circle,

    // Charts / Data
    'chart-bar': BarChart3,
    'bar-chart-3': BarChart3,
    'chart-line': LineChart,
    'chart-pie': PieChart,
    'pause': Pause,
    'ban': Ban,
    'database': Database,
    'table': Table,
    'history': History,

    // Finance
    'dollar': DollarSign,
    'money-bill': Banknote,
    'wallet': Wallet,
    'credit-card': CreditCard,
    'coins': Coins,

    // UI / System
    'home': Home,
    'cog': Settings,
    'settings': Settings,
    'wrench': Wrench,
    'calendar': Calendar,
    'calendar-plus': CalendarPlus,
    'building': Building2,
    'building-2': Building2,
    'graduation-cap': GraduationCap,
    'map': Map,
    'map-marker': MapPin,
    'map-pin': MapPin,
    'bars': Menu,
    'eye': Eye,
    'eye-slash': EyeOff,
    'inbox': Inbox,
    'qrcode': QrCode,
    'ellipsis-v': EllipsisVertical,
    'window-maximize': Maximize2,
    'window-minimize': Minimize2,
    'help-circle': HelpCircle,

    // Theme
    'moon': Moon,
    'sun': Sun,
    'desktop': Monitor,
    'laptop': Laptop,

    // Misc
    'list': List,
    'palette': Palette,
    'thumbs-up': ThumbsUp,
    'megaphone': Megaphone,
    'paperclip': Paperclip,
    'camera': Camera,
    'clock': Clock,
    'verified': BadgeCheck,
    'expand': Expand,
    'hashtag': Hash,
    'highlight': Highlighter,
    'objects-column': Columns,
    'sort-numeric-up': ArrowUpNarrowWide,
    'globe': Globe,
    'mobile': Smartphone,
    'shield-check': ShieldCheck,
    'sliders-h': SlidersHorizontal,
    'sliders-horizontal': SlidersHorizontal,
    'award': Award,
    'code': Code,
    'folder': Folder,
    'link': Link,
    'grip-vertical': GripVertical,
    'file-text': FileText,
    'share-2': Share2,

    // Navigation — extended
    'chevrons-left': ChevronsLeft,
    'chevrons-right': ChevronsRight,
    'double-left': ChevronsLeft,
    'double-right': ChevronsRight,
    'arrow-up-down': ArrowUpDown,
    'sort': ArrowUpDown,
    'trending-up': TrendingUp,
    'trending-down': TrendingDown,
    'arrow-big-up': ArrowBigUp,
    'arrow-big-down': ArrowBigDown,
    'send': Send,
    'send-horizontal': SendHorizontal,

    // Actions — extended
    'pencil-line': PencilLine,
    'eraser': Eraser,
    'scissors': Scissors,
    'clipboard': Clipboard,
    'clipboard-list': ClipboardList,
    'clipboard-check': ClipboardCheck,
    'clipboard-pen': ClipboardPen,

    // Status / Shape — extended
    'circle-check': CircleCheck,
    'circle-x': CircleX,
    'circle-plus': CirclePlus,
    'circle-minus': CircleMinus,
    'square-check': SquareCheck,
    'square-x': SquareX,
    'square-plus': SquarePlus,
    'square-minus': SquareMinus,
    'list-checks': ListChecks,
    'list-todo': ListTodo,
    'list-ordered': ListOrdered,

    // Data / Layout — extended
    'receipt': Receipt,
    'receipt-text': ReceiptText,
    'table-2': Table2,
    'grid': Grid2X2,
    'layout-dashboard': LayoutDashboard,
    'layout-grid': LayoutGrid,
    'layout-list': LayoutList,
    'layout-template': LayoutTemplate,
    'layers': Layers,
    'layers-2': Layers2,
    'activity': Activity,
    'zap': Zap,
    'zap-off': ZapOff,
    'bar-chart': BarChart2,
    'bar-chart-2': BarChart2,
    'sparkles': Sparkles,

    // People / Relations
    'contact': Contact,
    'handshake': Handshake,
    'hand-coins': HandCoins,
    'hand-heart': HandHeart,
    'link-off': Link2Off,

    // Education / Institution
    'school': School,
    'university': University,
    'landmark': Landmark,
    'book-copy': BookCopy,
    'book-marked': BookMarked,
    'book-user': BookUser,
    'book-check': BookOpenCheck,
    'notepad': NotepadText,
    'notebook-pen': NotebookPen,

    // Business / Goals
    'briefcase': Briefcase,
    'briefcase-business': BriefcaseBusiness,
    'package': Package,
    'target': Target,
    'trophy': Trophy,
    'medal': Medal,
    'crown': Crown,

    // File variants — extended
    'file-check': FileCheck,
    'file-warning': FileWarning,
    'file-image': FileImage,
    'file-code': FileCode,
    'file-signature': FileSignature,
    'folder-plus': FolderPlus,
    'folder-closed': FolderClosed,
    'folder-check': FolderCheck,

    // Calendar / Time — extended
    'calendar-check': CalendarCheck,
    'calendar-x': CalendarX,
    'calendar-clock': CalendarClock,
    'calendar-days': CalendarDays,
    'calendar-range': CalendarRange,
    'alarm': AlarmClock,
    'alarm-check': AlarmCheck,

    // Tech / Dev
    'terminal': Terminal,
    'code-2': Code2,
    'server': Server,
    'network': Network,
    'share': Share2,
    'cpu': Cpu,
    'hard-drive': HardDrive,
    'wifi': Wifi,
    'bug': Bug,

    // Misc — extended
    'map-pinned': MapPinned,
    'globe-2': Globe2,
    'ribbon': Ribbon,
    'power': Power,
    'shield-alert': ShieldAlert,
    'shield-x': ShieldX,
    'construction': Construction,
    'headphones': Headphones,
    'radio': Radio,
    'tv': Tv,
};

const extractIconName = (rawName) => {
    if (typeof rawName !== 'string') {
        return '';
    }

    const tokens = rawName
        .trim()
        .split(/\s+/)
        .map((token) => token.replace(/^pi-/, ''))
        .filter((token) => token && token !== 'pi' && token !== 'fw');

    return tokens[tokens.length - 1] || '';
};

const toPascalCase = (iconName) => {
    return iconName
        .split('-')
        .filter(Boolean)
        .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
        .join('');
};

const normalizedIconName = computed(() => extractIconName(props.name));

const resolvedSize = computed(() => props.size ?? '1em');

const resolvedIcon = computed(() => {
    const iconName = normalizedIconName.value;
    const icon = iconMap[iconName] || LucideIcons[toPascalCase(iconName)];

    if (!icon) {
        if (import.meta.env.DEV) {
            console.warn(`[AppIcon] Unknown icon name: "${props.name}". Falling back to HelpCircle.`);
        }
        return HelpCircle;
    }
    return icon;
});

const computedClass = computed(() => {
    const base = attrs.class || '';
    if (normalizedIconName.value === 'spinner') {
        return base ? `${base} animate-spin` : 'animate-spin';
    }
    return base || undefined;
});
</script>

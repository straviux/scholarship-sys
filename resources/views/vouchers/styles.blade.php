<!-- Document Styles - Print Safe -->
<style>
    /* ============================================
       PRINT DOCUMENT BASE STYLES
       ============================================ */

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        border-collapse: collapse;
    }

    body {
        font-family: 'Arial', sans-serif;
        line-height: 1.6;
        color: #000;
        background: #fff;
        padding: 0;
    }

    @page {
        margin: 6mm 6mm;
    }

    @media print {
        body {
            background: white;
            padding: 0;
        }

        .no-print {
            display: none !important;
        }

        .container {
            box-shadow: none;
            max-width: 100%;
            page-break-inside: avoid;
        }
    }

    /* ============================================
       TEXT & FONT CLASSES
       ============================================ */

    /* Font Sizes */
    .text-xs {
        font-size: 9px;
    }

    .text-sm {
        font-size: 10px;
    }

    .text-base {
        font-size: 11px;
    }

    .text-md {
        font-size: 12px;
    }

    .text-lg {
        font-size: 13px;
    }

    .text-xl {
        font-size: 14px;
    }

    .text-2xl {
        font-size: 16px;
    }

    .text-3xl {
        font-size: 18px;
    }

    /* Font Weights */
    .font-normal {
        font-weight: 400;
    }

    .font-medium {
        font-weight: 500;
    }

    .font-semibold {
        font-weight: 600;
    }

    .font-bold {
        font-weight: 700;
    }

    .font-extrabold {
        font-weight: 800;
    }

    /* Text Alignment */
    .text-left {
        text-align: left !important;
    }

    .text-center {
        text-align: center !important;
    }

    .text-right {
        text-align: right !important;
    }

    .text-justify {
        text-align: justify !important;
    }

    /* Vertical Alignment */
    .align-top {
        vertical-align: top;
    }

    .align-middle {
        vertical-align: middle;
    }

    .align-bottom {
        vertical-align: bottom;
    }

    /* Line Heights */
    .leading-tight {
        line-height: 1.2;
    }

    .leading-normal {
        line-height: 1.5;
    }

    .leading-relaxed {
        line-height: 1.75;
    }

    /* Letter Spacing */
    .tracking-tighter {
        letter-spacing: -1px;
    }

    .tracking-tight {
        letter-spacing: -0.5px;
    }

    .tracking-normal {
        letter-spacing: 0;
    }

    .tracking-wide {
        letter-spacing: 1px;
    }

    .tracking-wider {
        letter-spacing: 2px;
    }

    .tracking-widest {
        letter-spacing: 3px;
    }

    .tracking-expanded {
        letter-spacing: 4px;
    }

    /* Text Transform */
    .uppercase {
        text-transform: uppercase;
    }

    .lowercase {
        text-transform: lowercase;
    }

    .capitalize {
        text-transform: capitalize;
    }

    /* Text Decoration */
    .underline {
        text-decoration: underline;
    }

    .line-through {
        text-decoration: line-through;
    }

    .overline {
        text-decoration: overline;
    }

    .no-underline {
        text-decoration: none;
    }

    .italic {
        font-style: italic;
    }

    .not-italic {
        font-style: normal;
    }

    .text-strike {
        text-decoration: line-through;
    }

    /* Text Decoration Color & Style */
    .underline-dotted {
        text-decoration: underline dotted;
    }

    .underline-dashed {
        text-decoration: underline dashed;
    }

    .underline-wavy {
        text-decoration: underline wavy;
    }

    /* Text Colors */
    .text-black {
        color: #000;
    }

    .text-white {
        color: #fff;
    }

    .text-gray {
        color: #666;
    }

    .text-gray-light {
        color: #999;
    }

    .text-gray-dark {
        color: #333;
    }

    .text-red {
        color: #dc2626;
    }

    .text-red-light {
        color: #ef4444;
    }

    .text-red-dark {
        color: #7f1d1d;
    }

    .text-blue {
        color: #2563eb;
    }

    .text-blue-light {
        color: #60a5fa;
    }

    .text-blue-dark {
        color: #1e40af;
    }

    .text-green {
        color: #16a34a;
    }

    .text-green-light {
        color: #4ade80;
    }

    .text-green-dark {
        color: #15803d;
    }

    .text-yellow {
        color: #eab308;
    }

    .text-yellow-dark {
        color: #a16207;
    }

    .text-orange {
        color: #ea580c;
    }

    .text-orange-dark {
        color: #7c2d12;
    }

    /* Background Colors */
    .bg-black {
        background-color: #000;
    }

    .bg-white {
        background-color: #fff;
    }

    .bg-gray {
        background-color: #f3f4f6;
    }

    .bg-gray-light {
        background-color: #f9fafb;
    }

    .bg-gray-dark {
        background-color: #e5e7eb;
    }

    .bg-red {
        background-color: #fecaca;
    }

    .bg-red-light {
        background-color: #fee2e2;
    }

    .bg-red-dark {
        background-color: #fca5a5;
    }

    .bg-blue {
        background-color: #bfdbfe;
    }

    .bg-blue-light {
        background-color: #dbeafe;
    }

    .bg-blue-dark {
        background-color: #93c5fd;
    }

    .bg-green {
        background-color: #bbf7d0;
    }

    .bg-green-light {
        background-color: #dcfce7;
    }

    .bg-green-dark {
        background-color: #86efac;
    }

    .bg-yellow {
        background-color: #fef3c7;
    }

    .bg-yellow-dark {
        background-color: #fde68a;
    }

    .bg-orange {
        background-color: #fed7aa;
    }

    .bg-orange-dark {
        background-color: #fbddd2;
    }

    /* ============================================
       PARAGRAPH & TEXT BLOCK STYLES
       ============================================ */

    p {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100%;
        word-wrap: break-word;
        white-space: normal;
    }

    /* Quill Editor Alignment */
    p.ql-align-center {
        text-align: center;
    }

    p.ql-align-left {
        text-align: left;
    }

    p.ql-align-right {
        text-align: right;
    }

    /* ============================================
       ROW & COLUMN GRID SYSTEM
       ============================================ */

    /* Base Row Container */
    .row {
        display: flex;
        justify-content: flex-start;
        align-items: stretch;
        padding: 0;
        font-size: 12px;
        width: 100%;
    }

    .row.border-bottom {
        border-bottom: 1px solid #333;
    }

    .row.no-border {
        border: none;
    }

    .row.border-top {
        border-top: 1px solid #333;
    }

    .row.header {
        background-color: #f5f5f5;
        font-weight: bold;
    }

    .row.footer {
        background-color: #f0f0f0;
        font-weight: bold;
    }

    /* Column Base Styles */
    .col {
        display: flex;
        align-items: center;
        overflow: hidden;
        word-break: break-word;
    }

    .col.border-right {
        border-right: 1px solid #333 !important;
    }

    .col.no-border {
        border: none;
    }

    /* Flexible Width Columns */
    .col-auto {
        flex: 0 0 auto;
        min-width: 40px;
    }

    .col-fit {
        flex: 0 1 auto;
    }

    .col-grow {
        flex: 1 1 auto;
    }

    /* Fixed Width Columns (in percentage) */
    .col-1 {
        flex: 0 0 8.333%;
    }

    .col-2 {
        flex: 0 0 16.666%;
    }

    .col-3 {
        flex: 0 0 25%;
    }

    .col-4 {
        flex: 0 0 33.333%;
    }

    .col-5 {
        flex: 0 0 41.666%;
    }

    .col-6 {
        flex: 0 0 50%;
    }

    .col-7 {
        flex: 0 0 58.333%;
    }

    .col-8 {
        flex: 0 0 66.666%;
    }

    .col-9 {
        flex: 0 0 75%;
    }

    .col-10 {
        flex: 0 0 83.333%;
    }

    .col-11 {
        flex: 0 0 91.666%;
    }

    .col-12 {
        flex: 0 0 100%;
        width: 100%;
    }

    /* Fixed Width Columns (in pixels) */
    .col-60 {
        flex: 0 0 60px;
        min-width: 60px;
    }

    .col-80 {
        flex: 0 0 80px;
        min-width: 80px;
    }

    .col-100 {
        flex: 0 0 100px;
        min-width: 100px;
    }

    .col-120 {
        flex: 0 0 120px;
        min-width: 120px;
    }

    .col-140 {
        flex: 0 0 140px;
        min-width: 140px;
    }

    .col-160 {
        flex: 0 0 160px;
        min-width: 160px;
    }

    .col-180 {
        flex: 0 0 180px;
        min-width: 180px;
    }

    .col-200 {
        flex: 0 0 200px;
        min-width: 200px;
    }

    .col-300 {
        flex: 0 0 300px;
        min-width: 300px;
    }

    .col-360 {
        flex: 0 0 360px;
        min-width: 360px;
    }

    .col-400 {
        flex: 0 0 400px;
        min-width: 400px;
    }

    /* Column Content Alignment */
    .col-left {
        justify-content: flex-start;
    }

    .col-center {
        justify-content: center;
        text-align: center;
    }

    .col-right {
        justify-content: flex-end;
        text-align: right;
    }

    .col-between {
        justify-content: space-between;
    }

    /* Column Vertical Alignment */
    .col-top {
        align-items: flex-start;
    }

    .col-vcenter {
        align-items: center;
    }

    .col-bottom {
        align-items: flex-end;
    }

    /* Column Direction */
    .col-row {
        flex-direction: row;
    }

    .col-col {
        flex-direction: column;
        align-items: flex-start;
    }

    /* Column Span (Colspan equivalent) */
    .col-span-2 {
        flex: 0 0 calc(16.666% * 2);
    }

    .col-span-3 {
        flex: 0 0 calc(25% * 2);
    }

    .col-span-4 {
        flex: 0 0 calc(33.333% * 2);
    }

    .col-span-6 {
        flex: 0 0 50%;
    }

    /* Column Padding */
    .col-p-0 {
        padding: 0 !important;
    }

    .col-p-1 {
        padding: 2px !important;
    }

    .col-p-2 {
        padding: 4px !important;
    }

    .col-p-3 {
        padding: 6px !important;
    }

    .col-p-4 {
        padding: 8px !important;
    }

    .col-px-1 {
        padding: 0 2px !important;
    }

    .col-px-2 {
        padding: 0 4px !important;
    }

    .col-px-3 {
        padding: 0 6px !important;
    }

    .col-py-1 {
        padding: 2px 0 !important;
    }

    .col-py-2 {
        padding: 4px 0 !important;
    }

    .col-py-3 {
        padding: 6px 0 !important;
    }

    /* ============================================
       BORDERS & LINES
       ============================================ */

    .border-full {
        border: 1px solid #333;
    }

    .border-top {
        border-top: 1px solid #333;
    }

    .border-bottom {
        border-bottom: 1px solid #333;
    }

    .border-left {
        border-left: 1px solid #333;
    }

    .border-right {
        border-right: 1px solid #333 !important;
    }

    .border-none {
        border: none !important;
    }

    .border-dotted {
        border-bottom: 1px dotted #333;
    }

    /* ============================================
       PADDING UTILITIES (General)
       ============================================ */

    /* Padding - All Sides */
    .p-0 {
        padding: 0 !important;
    }

    .p-1 {
        padding: 4px !important;
    }

    .p-2 {
        padding: 8px !important;
    }

    .p-3 {
        padding: 12px !important;
    }

    .p-4 {
        padding: 16px !important;
    }

    .p-5 {
        padding: 20px !important;
    }

    .p-6 {
        padding: 24px !important;
    }

    /* Padding - Horizontal (left & right) */
    .px-0 {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .px-1 {
        padding-left: 4px !important;
        padding-right: 4px !important;
    }

    .px-2 {
        padding-left: 8px !important;
        padding-right: 8px !important;
    }

    .px-3 {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }

    .px-4 {
        padding-left: 16px !important;
        padding-right: 16px !important;
    }

    /* Padding - Vertical (top & bottom) */
    .py-0 {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    .py-1 {
        padding-top: 4px !important;
        padding-bottom: 4px !important;
    }

    .py-2 {
        padding-top: 8px !important;
        padding-bottom: 8px !important;
    }

    .py-3 {
        padding-top: 12px !important;
        padding-bottom: 12px !important;
    }

    .py-4 {
        padding-top: 16px !important;
        padding-bottom: 16px !important;
    }

    /* Padding - Individual Sides */
    .pt-1 {
        padding-top: 4px !important;
    }

    .pt-2 {
        padding-top: 8px !important;
    }

    .pt-3 {
        padding-top: 12px !important;
    }

    .pt-4 {
        padding-top: 16px !important;
    }

    .pb-1 {
        padding-bottom: 4px !important;
    }

    .pb-2 {
        padding-bottom: 8px !important;
    }

    .pb-3 {
        padding-bottom: 12px !important;
    }

    .pb-4 {
        padding-bottom: 16px !important;
    }

    .pl-1 {
        padding-left: 4px !important;
    }

    .pl-2 {
        padding-left: 8px !important;
    }

    .pl-3 {
        padding-left: 12px !important;
    }

    .pl-4 {
        padding-left: 16px !important;
    }

    .pr-1 {
        padding-right: 4px !important;
    }

    .pr-2 {
        padding-right: 8px !important;
    }

    .pr-3 {
        padding-right: 12px !important;
    }

    .pr-4 {
        padding-right: 16px !important;
    }

    /* ============================================
       SPACING UTILITIES
       ============================================ */

    .m-0 {
        margin: 0 !important;
    }

    .m-1 {
        margin: 4px !important;
    }

    .m-2 {
        margin: 8px !important;
    }

    .m-3 {
        margin: 12px !important;
    }

    .m-4 {
        margin: 16px !important;
    }

    .mx-auto {
        margin-left: auto !important;
        margin-right: auto !important;
    }

    /* ============================================
        CONTAINER STYLES
       ============================================ */

    .container {
        max-width: 100%;
        margin: 0 auto;
        background: #fff;
        padding: 0;
        display: flex;
        flex-direction: column;
    }




    /* ============================================
        HEADER STYLES
       ============================================ */

    .header {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid #333;
        padding: 8px 0;
    }

    .header-logo {
        position: absolute;
        left: 2%;
        width: 48px;
        height: 48px;
    }

    .header-logo img {
        width: 100%;
        height: auto;
    }

    .header-text {
        color: #000;
        text-align: center;
        flex: 1;
        font-size: 13px;
    }

    .header-text p {
        margin: 0;
        padding: 0;
        line-height: 1.2;
    }

    .header-text p.main {
        font-size: 13px;
        font-weight: 600;
    }

    .header-text p.sub {
        font-size: 12px;
    }

    /* ============================================
       TABLE STYLES
       ============================================ */

    table {
        width: 100%;
        font-size: 11px;
        border-collapse: collapse;
    }

    table th,
    table td {
        padding: 2px;
        text-align: left;
        /* border-collapse: collapse; */
        border: 1px solid #333;

    }

    table th {
        padding: 12px 4px !important;
        text-align: center;
    }


    /* ============================================
       UTILITY CLASSES
       ============================================ */

    .flex-center {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .flex-between {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .flex-col {
        display: flex;
        flex-direction: column;
    }

    .flex-row {
        display: flex;
        flex-direction: row;
    }

    .gap-1 {
        gap: 4px;
    }

    .gap-2 {
        gap: 8px;
    }

    .gap-3 {
        gap: 12px;
    }

    .gap-4 {
        gap: 16px;
    }

    .hidden {
        display: none !important;
    }

    .invisible {
        visibility: hidden !important;
    }

    .break {
        page-break-after: always;
    }

    .no-break {
        page-break-inside: avoid;
    }

    .min-h-screen {
        min-height: 100vh;
    }

    .flex-1 {
        flex: 1;
    }

    .flex-grow {
        flex-grow: 1;
    }
</style>
import { getPdfCss } from '@/Pages/FundTransactions/Pdf/pdf-styles.js';
import { pagedjsPolyfillScript } from '@/utils/pagedjsPolyfill';

export const interviewedApplicantsPdfFooterCss = `
@page {
  margin: 4mm 5mm 12mm 5mm;
  @bottom-right {
    content: "Page " counter(page) " of " counter(pages);
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7pt;
    color: #666;
  }
}

@page:first {
  margin-top: 1mm;
}

body { margin: 0; padding: 0; }

/* Screen "page by page" preview look — the vendored paged.polyfill.min.js is
   the pagination engine only; it ships without Paged.js's companion
   interface.css, so without this every page renders flush against the next
   with no visual separation (one continuous white area instead of distinct
   sheets on a grey backdrop). Reset to the actual print layout in @media print. */
.pagedjs_pages {
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: #e5e5ea;
  padding: 16px 0;
}
.pagedjs_page {
  background-color: #fff;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
  margin-bottom: 16px;
}
.pagedjs_page:last-child {
  margin-bottom: 0;
}
@media print {
  .pagedjs_pages { background: none; padding: 0; }
  .pagedjs_page { box-shadow: none; margin-bottom: 0; }
}

table { -fs-table-paginate: paginate; }
thead { display: table-header-group; }
tfoot { display: table-footer-group; }
tbody { page-break-inside: auto; break-inside: auto; }
tr, td, th { page-break-inside: avoid; break-inside: avoid-page; }
`;

export function buildInterviewedApplicantsPdfDoc(
    bodyHtml,
    title = 'Document',
    paperSize = 'a4-landscape',
    autoPrint = false,
) {
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${title}</title>
  <style>
    body { visibility: hidden; margin: 0; padding: 0; }
    ${getPdfCss(paperSize)}
    ${interviewedApplicantsPdfFooterCss}
  </style>
  <script>${pagedjsPolyfillScript}<\/script>
  <script>
    (function () {
      function repeatSplitTableHeaders() {
        try {
          var splitTables = document.querySelectorAll('table[data-split-from]');

          splitTables.forEach(function (table) {
            var ref = table.getAttribute('data-ref');

            if (!ref) {
              return;
            }

            var originals = document.querySelectorAll('table[data-ref="' + ref + '"]:not([data-split-from])');
            var original = originals[0];

            if (!original) {
              return;
            }

            if (!table.querySelector(':scope > colgroup')) {
              var originalColgroups = original.querySelectorAll(':scope > colgroup');

              originalColgroups.forEach(function (colgroup) {
                table.insertBefore(colgroup.cloneNode(true), table.firstChild);
              });
            }

            if (table.querySelector(':scope > thead')) {
              return;
            }

            var originalHead = original.querySelector(':scope > thead');

            if (!originalHead) {
              return;
            }

            table.insertBefore(originalHead.cloneNode(true), table.firstChild);
          });
        } catch (error) {
          console.warn('Interviewed Applicants header repeat failed', error);
        }
      }

      function finalizeRender() {
        repeatSplitTableHeaders();

        var pages = document.querySelector('.pagedjs_pages');
        var height = pages ? pages.scrollHeight + 48 : document.documentElement.scrollHeight;

        if (window.parent && typeof window.parent.postMessage === 'function') {
          window.parent.postMessage({ type: 'pagedjs:rendered', height: height }, '*');
        }

        document.body.style.visibility = 'visible';

        if (document.body.getAttribute('data-auto-print') === '1') {
          window.print();
        }
      }

      if (window.PagedPolyfill && typeof window.PagedPolyfill.on === 'function') {
        window.PagedPolyfill.on('rendered', finalizeRender);
      } else {
        window.addEventListener('load', function () {
          setTimeout(finalizeRender, 100);
        });
      }
    })();
  <\/script>
</head>
<body${autoPrint ? ' data-auto-print="1"' : ''}>${bodyHtml}</body>
</html>`;
}
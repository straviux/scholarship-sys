import { getPdfCss } from '@/Pages/FundTransactions/Pdf/pdf-styles.js';
import { pagedjsPolyfillScript } from '@/utils/pagedjsPolyfill';

export const interviewedApplicantsPdfFooterCss = `
@page {
  margin: 6mm 5mm 12mm 5mm;
  @bottom-right {
    content: "Page " counter(page) " of " counter(pages);
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7pt;
    color: #666;
  }
}

table { -fs-table-paginate: paginate; }
thead { display: table-header-group; }
tfoot { display: table-footer-group; }
tbody, tr, td, th { page-break-inside: auto; break-inside: auto; }
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
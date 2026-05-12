import moment from 'moment';

import { renderVueTemplate, usePdfPrint } from '@/composables/usePdfPrint';
import RecommendationListTemplate from './Pdf/RecommendationListTemplate.vue';

function resolvePaperSize(recommendationList) {
    const paperSize = recommendationList?.paper_size || 'A4';
    const orientation = recommendationList?.orientation || 'landscape';

    const map = {
        A4: {
            portrait: 'a4',
            landscape: 'a4-landscape',
        },
        Letter: {
            portrait: 'letter',
            landscape: 'letter-landscape',
        },
        Legal: {
            portrait: 'long',
            landscape: 'landscape',
        },
    };

    return map[paperSize]?.[orientation] || 'a4-landscape';
}

export function printRecommendationList({ recommendationList }) {
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        return false;
    }

    const title = recommendationList?.report_title || 'RECOMMENDATION LIST FOR APPROVAL';
    const generatedAt = moment().format('MMMM D, YYYY h:mm A');
    const bodyHtml = renderVueTemplate(RecommendationListTemplate, {
        records: recommendationList?.records || [],
        today: moment().format('MMMM D, YYYY'),
        preparedBy: recommendationList?.prepared_by || '',
        preparedByPosition: recommendationList?.prepared_by_position || '',
        preparedByOffice: recommendationList?.prepared_by_office || '',
        approvedBy: recommendationList?.approved_by || '',
        approvedByPosition: recommendationList?.approved_by_position || '',
        budgetAllocation: recommendationList?.budget_allocation || null,
        reportTitle: title,
    });

    const { buildHtmlDoc } = usePdfPrint();
    const paperSize = resolvePaperSize(recommendationList);

    printWindow.document.write(buildHtmlDoc(bodyHtml, title, paperSize, '', {
        generatedAt,
        showPageNumbers: true,
    }));
    printWindow.document.close();
    printWindow.onload = () => {
        printWindow.focus();
        printWindow.print();
    };

    setTimeout(() => {
        if (printWindow && !printWindow.closed) {
            printWindow.focus();
            printWindow.print();
        }
    }, 800);

    return true;
}
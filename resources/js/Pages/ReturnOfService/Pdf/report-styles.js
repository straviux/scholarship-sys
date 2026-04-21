export const returnOfServiceReportCss = `
.ros-report {
    font-family: Arial, sans-serif;
    color: #0f172a;
    font-size: 12px;
    line-height: 1.5;
}

.ros-report h1,
.ros-report h2,
.ros-report h3,
.ros-report p {
    margin: 0;
}

.ros-report__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #cbd5e1;
}

.ros-report__eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-size: 10px;
    color: #64748b;
    margin-bottom: 6px;
}

.ros-report__title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.ros-report__subtitle,
.ros-report__meta {
    color: #475569;
    font-size: 11px;
}

.ros-report__summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.ros-report__summary-card {
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 12px 14px;
    background: #f8fafc;
}

.ros-report__summary-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    margin-bottom: 4px;
}

.ros-report__summary-value {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
}

.ros-report__batch {
    margin-bottom: 22px;
    page-break-inside: avoid;
}

.ros-report__batch-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 10px;
}

.ros-report__batch-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 3px;
}

.ros-report__batch-meta {
    color: #475569;
    font-size: 11px;
}

.ros-report__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 68px;
    padding: 6px 10px;
    border-radius: 999px;
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: 700;
    font-size: 11px;
}

.ros-report__description {
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    color: #334155;
    margin-bottom: 12px;
}

.ros-report__table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.ros-report__table th,
.ros-report__table td {
    border: 1px solid #cbd5e1;
    padding: 7px 8px;
    vertical-align: top;
    word-wrap: break-word;
}

.ros-report__table th {
    background: #e2e8f0;
    text-align: left;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #334155;
}

.ros-report__empty {
    text-align: center;
    color: #64748b;
    padding: 16px 0;
}

.ros-report__status {
    font-weight: 600;
}
`;

# System Status Report Documentation

## Recent Fixes and Updates

### SQL Error Resolutions

- **Fixed Relationship Method Error**: Corrected `scholarshipProfile()` to `profile()` in ScholarshipRecord model relationship
- **Fixed Column Name Error**: Updated `scholarship_record_id` to `record_id` in orphaned requirements query
- **Fixed GROUP BY Clause Issues**: Added missing columns to GROUP BY clauses in all JOIN queries:
  - `by_program` query: Added `scholarship_programs.shortname` to GROUP BY
  - `by_school` query: Added `schools.name` to GROUP BY (removed non-existent `municipality`)
  - `by_course` query: Added `courses.name` to GROUP BY
  - `getProgramEffectiveness` query: Added `shortname` and `name` to GROUP BY
- **Fixed ORDER BY Direction Error**: Corrected invalid `orderBy('year', 'month')` to proper `orderByRaw('YEAR(date_filed) ASC, MONTH(date_filed) ASC')`
- **Fixed Raw SQL Functions**: Updated `groupBy` to use `groupByRaw` for SQL functions in monthly trends query
- **Fixed User Roles Query**: Replaced non-existent `is_admin` column with Spatie Permission role-based queries
  - Updated `getUserRolesDistribution()` to use `User::role()` method for counting administrators and moderators
- **Frontend Updates**: Removed references to non-existent `school_municipality` field
- **Verified All Queries**: All data integrity and relationship queries now work correctly

### Verified Data Integrity Metrics

- Records without programs: Available
- Records without courses: Available
- Records without schools: Available
- Profiles without records: Available
- Records without profiles: Available
- Orphaned requirements: Available

## Overview

The System Status Report is a comprehensive administrative tool that provides insights into the scholarship management system's health, performance, and data integrity. This feature is exclusively available to users with administrator privileges.

## Access Control

- **Authorization**: Only users with the "administrator" role can access this feature
- **Route Protection**: Protected by both middleware and Gate policies
- **Navigation**: Available in the Administrator section of the sidebar

## Report Sections

### 1. Executive Summary

- Total scholarship records and profiles
- Application status breakdown (pending, approved, rejected)
- Overall approval rate
- System health status
- Total users, programs, schools, and courses

### 2. Data Integrity Analysis

- Records without assigned programs
- Records without assigned courses
- Records without assigned schools
- Orphaned profiles and requirements
- Invalid date ranges
- Duplicate applications

### 3. Application Status Distribution

- Visual charts showing status breakdown
- Applications by program
- Monthly trends
- Processing time analytics

### 4. Performance Metrics

- Average processing time in days
- Application volume comparison (current vs previous month)
- Peak application days
- System usage statistics

### 5. Geographic Distribution

- Top municipalities by application count
- Top schools by application count
- Regional distribution analysis

### 6. Academic Analysis

- Applications by course with approval rates
- Applications by year level
- Program effectiveness metrics

### 7. System Health Monitoring

- Database connection status
- Cache system status
- Storage usage metrics (public and private)
- Recent system activity

### 8. User Activity Tracking

- Total registered users
- Active users today
- New users this month
- Inactive user count
- User role distribution

## Features

### Interactive Dashboard

- Real-time data visualization using Chart.js
- Responsive design with PrimeVue components
- Color-coded health indicators
- Drill-down capabilities for detailed analysis

### Export Capabilities

- JSON export for data analysis
- Downloadable reports with timestamps
- Structured data format for external processing

### Real-time Updates

- Refresh functionality to get latest data
- Live status indicators
- Dynamic chart updates

## Technical Implementation

### Backend Controller

**File**: `app/Http/Controllers/SystemReportController.php`

- Comprehensive data analysis methods
- Optimized database queries
- Error handling and validation
- JSON export functionality

### Frontend Component

**File**: `resources/js/Pages/Admin/SystemReport/Index.vue`

- Vue 3 composition API
- PrimeVue UI components
- Chart.js integration for data visualization
- Responsive grid layouts

### Authorization

**File**: `app/Providers/AppServiceProvider.php`

- Gate definition for 'admin' access
- Integration with Spatie Permission package
- Role-based access control

### Routes

**File**: `routes/web.php`

- Protected administrator routes
- JSON export endpoint
- Middleware protection

## Usage Instructions

### Accessing the Report

1. Log in as an administrator
2. Navigate to the Administrator section in the sidebar
3. Click on "System Report"
4. The comprehensive report will load automatically

### Refreshing Data

- Click the "Refresh" button to get the latest system data
- All metrics and charts will update with current information

### Exporting Data

- Click "Export JSON" to download the complete report data
- File includes timestamp for version tracking
- Data can be used for external analysis or archival

## Data Sources

### Database Tables

- `scholarship_records` - Application data
- `scholarship_profiles` - Applicant profiles
- `scholarship_programs` - Program information
- `schools` - Educational institutions
- `courses` - Academic programs
- `users` - System users
- `scholarship_record_requirements` - Application requirements

### Calculated Metrics

- Approval rates and percentages
- Processing time averages
- Geographic distributions
- Trend analysis over time

## Security Considerations

### Access Control

- Multiple layers of authorization (middleware + gates)
- Role-based permissions using Spatie Permission
- Secure route protection

### Data Privacy

- Aggregated data only (no personal information exposed)
- Statistical analysis without individual identification
- Secure export functionality

## Performance Optimization

### Database Queries

- Optimized joins and aggregations
- Efficient counting and grouping
- Limited result sets for large datasets

### Frontend Performance

- Lazy loading of chart components
- Efficient data structures
- Responsive design for various screen sizes

## Maintenance Notes

### Regular Monitoring

- Review data integrity issues regularly
- Monitor system health indicators
- Track performance trends over time

### Data Cleanup

- Address orphaned records
- Resolve missing data associations
- Maintain data consistency

### Capacity Planning

- Monitor storage usage trends
- Track application volume growth
- Plan for system scaling needs

## Future Enhancements

### Potential Additions

- Email alerts for critical issues
- Scheduled automated reports
- Historical trend analysis
- Advanced filtering options
- Custom date range selection
- Additional export formats (PDF, Excel)

### Integration Opportunities

- External monitoring systems
- Business intelligence tools
- Automated backup status
- Log analysis integration

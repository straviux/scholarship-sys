# Waiting List Queue Order Fix - Multiple Queue Numbers

## Date: October 17, 2025

## Issue

The Q# (queue number) in the waiting list report was not accurate. The requirement is for **separate queue numbers** for:

1. **Program Queue (P-Q#):** Per program, ordered by date filed
2. **School Queue (S-Q#):** Per school, ordered by date filed
3. **Course Queue (C-Q#):** Per course, ordered by date filed

## Changes Made

### 1. Updated Sorting Logic (`waiting_list_report.blade.php` lines 266-277)

**Before:**

```php
// Sort profiles by program, school, course, and date filed (oldest to newest)
return [$programName, $schoolName, $courseName, $dateFiled, $createdAt];
```

**After:**

```php
// Sort profiles by course, then by date filed (oldest to newest)
// Primary sort: Course → Date Filed → Created At
return [$courseName, $dateFiled, $createdAt, $programName, $schoolName];
```

**Explanation:**

- Primary sort is now **Course** first (instead of Program → School → Course)
- Secondary sort is **Date Filed** (oldest to newest)
- Tertiary sort is **Created At** (for records with same date filed)
- This ensures the table displays records grouped by course, with oldest applications first

### 2. Added Three Separate Queue Number Trackers (lines 279-288)

**New Code:**

```php
// Initialize queue number counters (Q#) for program, school, and course
// These track position within each group based on date filed order
$programQueueNumbers = [];
$schoolQueueNumbers = [];
$courseQueueNumbers = [];
```

**Explanation:**

- Three separate arrays to track queue positions
- Each array maintains independent counters per program/school/course
- Increments happen in the order records are processed (sorted by course → date filed)

### 3. Implemented Queue Number Calculation Logic (lines 315-340)

**New Code:**

```php
// Calculate Queue Numbers (Q#) per Program, School, and Course
// These represent the position in the waiting list for each grouping

// Q# for Program (per program by date filed)
if (!isset($programQueueNumbers[$programName])) {
    $programQueueNumbers[$programName] = 0;
}
$programQueueNumbers[$programName]++;
$programQNum = $programQueueNumbers[$programName];

// Q# for School (per school by date filed)
if (!isset($schoolQueueNumbers[$schoolName])) {
    $schoolQueueNumbers[$schoolName] = 0;
}
$schoolQueueNumbers[$schoolName]++;
$schoolQNum = $schoolQueueNumbers[$schoolName];

// Q# for Course (per course by date filed)
if (!isset($courseQueueNumbers[$courseName])) {
    $courseQueueNumbers[$courseName] = 0;
}
$courseQueueNumbers[$courseName]++;
$courseQNum = $courseQueueNumbers[$courseName];
```

**Explanation:**

- Each queue counter is incremented independently
- Since records are sorted by course → date filed, the increments happen in chronological order
- Each program/school/course starts at Q#1 and increments sequentially

### 4. Updated Display Format (line 355)

**Before:**

```php
P: #{{ $programSeqNum }} | S: #{{ $schoolSeqNum }} | C: #{{ $courseSeqNum }} | D: #{{ $dateIndex }}
```

**After:**

```php
P-Q#{{ $programQNum }} | S-Q#{{ $schoolQNum }} | C-Q#{{ $courseQNum }} | Filed: {{ $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('m/d/Y') : 'N/A' }}
```

**Explanation:**

- Changed from generic sequences (P:, S:, C:) to explicit queue numbers (P-Q#, S-Q#, C-Q#)
- Removed D: (date index) and replaced with readable "Filed: mm/dd/yyyy" format
- Makes it clear these are queue positions, not just sequence numbers

## Result

### Display Format

Each record now displays:

```
Name: Last, First
P-Q#1 | S-Q#1 | C-Q#1 | Filed: 01/15/2025
```

Where:

- **P-Q#:** Program Queue Number (position in waiting list for this program, by date filed)
- **S-Q#:** School Queue Number (position in waiting list for this school, by date filed)
- **C-Q#:** Course Queue Number (position in waiting list for this course, by date filed)
- **Filed:** Date the application was filed

### Table Sorting

The table displays records in this order:

1. **Course** (alphabetically)
2. **Date Filed** (oldest to newest within each course)
3. **Created At** (if multiple records have same date filed)

### Example Scenario

**Program: CHED**

- Application filed 01/15/2025 → **P-Q#1**
- Application filed 01/20/2025 → **P-Q#2**
- Application filed 01/25/2025 → **P-Q#3**

**School: ABC University**

- Application filed 01/10/2025 → **S-Q#1**
- Application filed 01/18/2025 → **S-Q#2**
- Application filed 01/22/2025 → **S-Q#3**

**Course: BSIT**

- Application filed 01/15/2025 → **C-Q#1**
- Application filed 01/16/2025 → **C-Q#2**
- Application filed 01/20/2025 → **C-Q#3**

**Course: BSCS** (starts over)

- Application filed 01/10/2025 → **C-Q#1**
- Application filed 01/18/2025 → **C-Q#2**

### Complete Example Record

```
John Doe (CHED, ABC University, BSIT)
Filed: 01/15/2025
→ P-Q#5 (5th in CHED program queue)
→ S-Q#8 (8th in ABC University queue)
→ C-Q#2 (2nd in BSIT course queue)

Display:
P-Q#5 | S-Q#8 | C-Q#2 | Filed: 01/15/2025
```

## Testing

To verify the fix:

1. Generate a waiting list PDF report
2. Check that records are grouped by **course**
3. Within each course, verify dates are sorted **oldest to newest**
4. Verify each queue counter (P-Q#, S-Q#, C-Q#):
   - Starts at #1 for each new program/school/course
   - Increments based on date filed order
   - Is independent of the other queue counters
5. Check that the same applicant can have different queue positions for program, school, and course

## Files Modified

- `resources/views/waiting_list_report.blade.php`

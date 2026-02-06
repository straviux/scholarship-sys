# Print-Safe CSS Classes Reference Guide

## How to Use Standard Classes in Templates

### 1. Text & Font Classes

**Font Sizes** (Print-safe)
- `.text-xs` — 9px (tiny labels, footnotes)
- `.text-sm` — 10px (small text)
- `.text-base` — 11px (default body text)
- `.text-md` — 12px (normal)
- `.text-lg` — 13px (medium)
- `.text-xl` — 14px (main headings)
- `.text-2xl` — 16px (large headings)
- `.text-3xl` — 18px (page titles)

**Font Weights**
- `.font-normal` — 400 (regular)
- `.font-medium` — 500 (slightly bold)
- `.font-semibold` — 600 (semi-bold)
- `.font-bold` — 700 (bold)
- `.font-extrabold` — 800 (very bold)

**Text Alignment**
```html
<div class="text-left">Left aligned text</div>
<div class="text-center">Centered text</div>
<div class="text-right">Right aligned text</div>
<div class="text-justify">Justified text</div>
```

### 2. Row & Column System

**Basic Row with Columns**
```html
<!-- Row with auto-sizing columns -->
<div class="row">
    <div class="col col-center text-xl font-bold">HEADER</div>
</div>

<!-- Row with header styling -->
<div class="row header">
    <div class="col col-120">Column 1</div>
    <div class="col col-grow">Column 2</div>
    <div class="col col-80">Column 3</div>
</div>
```

**Column Width Options**

*Percentage-based (for responsive spacing):*
- `.col-1` through `.col-12` — width divided by 12

*Pixel-based (for fixed widths):*
- `.col-60`, `.col-80`, `.col-100`, `.col-120`, `.col-140`, `.col-160`, `.col-180`, `.col-200`

*Flexible sizing:*
- `.col-auto` — minimal width, no growth
- `.col-fit` — fit content
- `.col-grow` — take available space (flex: 1)

### 3. Column Alignment

**Horizontal Alignment**
```html
<div class="col col-left">Text left</div>
<div class="col col-center text-center">Text center</div>
<div class="col col-right text-right">Text right</div>
```

**Vertical Alignment**
```html
<div class="col col-top">Top aligned</div>
<div class="col col-vcenter">Vertically centered</div>
<div class="col col-bottom">Bottom aligned</div>
```

### 4. Column Padding & Spacing

```html
<div class="col col-p-0">No padding</div>
<div class="col col-p-1">2px padding</div>
<div class="col col-p-2">4px padding</div>
<div class="col col-p-3">6px padding (default)</div>
<div class="col col-p-4">8px padding</div>

<!-- Horizontal padding only -->
<div class="col col-px-2">4px left/right</div>

<!-- Vertical padding only -->
<div class="col col-py-2">4px top/bottom</div>
```

### 5. Border Control

```html
<!-- Remove borders -->
<div class="row no-border">No bottom border</div>
<div class="col no-border">No right border</div>

<!-- Add borders -->
<div class="row border-top">Add top border</div>
<div class="col border-right">Add right border</div>

<!-- Full border -->
<div class="border-full">Complete border</div>

<!-- Special -->
<div class="border-dotted">Dotted bottom border</div>
```

### 6. Row Special Classes

```html
<!-- Header row with gray background -->
<div class="row header">
    <div class="col col-160">Name</div>
    <div class="col col-grow">Description</div>
</div>

<!-- Footer row with gray background -->
<div class="row footer">
    <div class="col col-120">Total</div>
    <div class="col col-grow">Amount</div>
</div>
```

### 7. Practical Example: Disbursement Voucher Row

```html
<!-- OLD WAY (from DV template) -->
<div class="obr-info-row">
    <div class="label">Item</div>
    <div class="number">001</div>
    <div class="column_1">Name</div>
    <div class="column_5" style="flex-direction: row;">
        <div class="fpp">100</div>
        <div class="account-code">5000</div>
        <div class="amount">₱1,000.00</div>
    </div>
</div>

<!-- NEW WAY (using standard classes) -->
<div class="row text-xl">
    <div class="col col-60 col-center font-bold">Item</div>
    <div class="col col-100 col-center">001</div>
    <div class="col col-120">Name</div>
    <div class="col col-60 col-center">100</div>
    <div class="col col-60 col-center">5000</div>
    <div class="col col-grow col-right">₱1,000.00</div>
</div>
```

## Integration Steps

### In Blade Templates

**Option 1: Include styles file**
```html
<!-- At top of template -->
@include('vouchers.styles')

<!-- Then use classes in your HTML -->
<div class="row header">
    ...
</div>
```

**Option 2: Link externally (if converted to CSS file)**
```html
<link rel="stylesheet" href="/css/vouchers/styles.css">
```

### Migrate Existing DV Classes

Replace these old classes:
- `.obr-info-row` → `.row`
- `.label` → `.col col-center font-bold`
- `.number` → `.col col-{width} col-center`
- `.column_1` → `.col col-120`
- `.column_2` → `.col col-grow col-col` (if needs flex-direction: column)
- `.column_3` → `.col col-80`
- `.column_4` → `.col col-80`
- `.column_5` → `.col col-160 col-row` (or `.col col-grow` for flexible)
- `.fpp` → `.col col-60 col-center`
- `.account-code` → `.col col-60 col-center`
- `.amount` → `.col col-grow col-right`

### Print-Safe Features

✓ **No box-shadow** — All styles work in print
✓ **Proper borders** — Use 1px solid #333
✓ **Text clarity** — Tested font sizes 9px-18px
✓ **Margins** — 6mm page margins defined
✓ **Page breaks** — Use `.break` for page breaks, `.no-break` to prevent

## Examples for Common Patterns

### Payroll Template Header
```html
<div class="row header text-xl font-bold">
    <div class="col col-120">Employee Name</div>
    <div class="col col-100">Position</div>
    <div class="col col-100">Salary</div>
    <div class="col col-100">Deductions</div>
    <div class="col col-grow">Net Pay</div>
</div>
```

### Data Row with Amounts
```html
<div class="row text-base">
    <div class="col col-120">John Doe</div>
    <div class="col col-100">Teacher</div>
    <div class="col col-100 col-right">50,000</div>
    <div class="col col-100 col-right">5,000</div>
    <div class="col col-grow col-right font-semibold">45,000</div>
</div>
```

### Multi-line Address Block
```html
<div class="row">
    <div class="col col-120 font-bold">Address</div>
    <div class="col col-grow col-col">
        <p>123 Main Street</p>
        <p>City, State 12345</p>
        <p>Country</p>
    </div>
</div>
```

## Tips for Best Results

1. **Always use `.text-xl` or similar** for consistent font sizes
2. **Combine column width with alignment**: `.col col-100 col-center text-center`
3. **Use `.col-grow`** for flexible columns that fill remaining space
4. **Group related columns** using `.col col-row` for sub-columns
5. **Test print output** by using browser's Print Preview
6. **Avoid mixing old and new classes** — fully migrate one template at a time

---

**Last Updated:** Document created for comprehensive CSS class standardization  
**Applies to:** All Blade templates for OBR, DV, Payroll, and LOS documents

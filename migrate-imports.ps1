# Component Import Path Migration Script
# This script helps update import paths after component reorganization

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Component Import Path Migration Tool" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$workspacePath = "c:\Users\Administrator\Desktop\SCHOLARSHIP PROGRAM\scholarship-sys"
$searchPath = "$workspacePath\resources\js"

# Define old -> new path mappings
$pathMappings = @(
    # Field Components
    @{ Old = "@/Components/PersonalInformationFields.vue"; New = "@/Components/forms/fields/PersonalInformationFields.vue" },
    @{ Old = "@/Components/FamilyInformationFields.vue"; New = "@/Components/forms/fields/FamilyInformationFields.vue" },
    @{ Old = "@/Components/AcademicInformationFields.vue"; New = "@/Components/forms/fields/AcademicInformationFields.vue" },
    
    # Modal Components
    @{ Old = "@/Components/ApplicantFormModal.vue"; New = "@/Components/modals/ApplicantFormModal.vue" },
    @{ Old = "@/Components/ScholarFormModal.vue"; New = "@/Components/modals/ScholarFormModal.vue" },
    @{ Old = "@/Components/AddApplicantModal.vue"; New = "@/Components/modals/AddApplicantModal.vue" },
    @{ Old = "@/Components/AddExistingModal.vue"; New = "@/Components/modals/AddExistingModal.vue" },
    @{ Old = "@/Components/Modal.vue"; New = "@/Components/modals/Modal.vue" },
    
    # Button Components
    @{ Old = "@/Components/PrimaryButton.vue"; New = "@/Components/ui/buttons/PrimaryButton.vue" },
    @{ Old = "@/Components/SecondaryButton.vue"; New = "@/Components/ui/buttons/SecondaryButton.vue" },
    @{ Old = "@/Components/DangerButton.vue"; New = "@/Components/ui/buttons/DangerButton.vue" },
    
    # Input Components
    @{ Old = "@/Components/TextInput.vue"; New = "@/Components/ui/inputs/TextInput.vue" },
    @{ Old = "@/Components/TextArea.vue"; New = "@/Components/ui/inputs/TextArea.vue" },
    @{ Old = "@/Components/DateInput.vue"; New = "@/Components/ui/inputs/DateInput.vue" },
    @{ Old = "@/Components/Checkbox.vue"; New = "@/Components/ui/inputs/Checkbox.vue" },
    @{ Old = "@/Components/InputLabel.vue"; New = "@/Components/ui/inputs/InputLabel.vue" },
    @{ Old = "@/Components/InputError.vue"; New = "@/Components/ui/inputs/InputError.vue" },
    
    # Navigation Components
    @{ Old = "@/Components/NavLink.vue"; New = "@/Components/ui/navigation/NavLink.vue" },
    @{ Old = "@/Components/ResponsiveNavLink.vue"; New = "@/Components/ui/navigation/ResponsiveNavLink.vue" },
    @{ Old = "@/Components/SidebarLink.vue"; New = "@/Components/ui/navigation/SidebarLink.vue" },
    @{ Old = "@/Components/Dropdown.vue"; New = "@/Components/ui/navigation/Dropdown.vue" },
    @{ Old = "@/Components/DropdownLink.vue"; New = "@/Components/ui/navigation/DropdownLink.vue" },
    @{ Old = "@/Components/NotificationDropdown.vue"; New = "@/Components/ui/navigation/NotificationDropdown.vue" },
    
    # Table Components
    @{ Old = "@/Components/Table.vue"; New = "@/Components/ui/table/Table.vue" },
    @{ Old = "@/Components/TableRow.vue"; New = "@/Components/ui/table/TableRow.vue" },
    @{ Old = "@/Components/TableHeaderCell.vue"; New = "@/Components/ui/table/TableHeaderCell.vue" },
    @{ Old = "@/Components/TableDataCell.vue"; New = "@/Components/ui/table/TableDataCell.vue" },
    @{ Old = "@/Components/Pagination.vue"; New = "@/Components/ui/table/Pagination.vue" }
)

Write-Host "Searching for files with old import paths..." -ForegroundColor Yellow
Write-Host ""

$filesFound = 0
$updatesCount = 0

# Find all .vue and .js files
$files = Get-ChildItem -Path $searchPath -Include *.vue, *.js -Recurse

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $originalContent = $content
    $fileUpdated = $false
    
    foreach ($mapping in $pathMappings) {
        if ($content -match [regex]::Escape($mapping.Old)) {
            $content = $content -replace [regex]::Escape($mapping.Old), $mapping.New
            $fileUpdated = $true
        }
    }
    
    if ($fileUpdated) {
        $filesFound++
        Write-Host "Update: " -ForegroundColor Green -NoNewline
        Write-Host $file.FullName.Replace($workspacePath, "~")
        
        # Count how many replacements were made
        $count = 0
        foreach ($mapping in $pathMappings) {
            $replaceCount = ([regex]::Matches($originalContent, [regex]::Escape($mapping.Old))).Count
            if ($replaceCount -gt 0) {
                $count += $replaceCount
                $updatesCount += $replaceCount
            }
        }
        Write-Host "  -> $count import path(s) updated" -ForegroundColor Cyan
        
        Set-Content -Path $file.FullName -Value $content -NoNewline
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Migration Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Files updated: $filesFound" -ForegroundColor Yellow
Write-Host "Total imports updated: $updatesCount" -ForegroundColor Yellow
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Run npm run build to verify compilation" -ForegroundColor White
Write-Host "2. Test your application thoroughly" -ForegroundColor White
Write-Host "3. Check browser console for any errors" -ForegroundColor White
Write-Host ""

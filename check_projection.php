use App\Models\ScholarshipRecord;
use App\Services\ScholarshipExpenseProjectionService;

$terms = ["1ST TRIMESTER", "2ND TRIMESTER", "3RD TRIMESTER", "1ST SEMESTER"];
$yearLevel = "4TH";
$academicYear = "2026-2027";
$program = "MED";

$service = new ScholarshipExpenseProjectionService();

foreach ($terms as $term) {
    $record = [
        "program" => $program,
        "course" => "GENERAL MEDICINE", // Providing a likely course or dummy string if not critical
        "term" => $term,
        "year_level" => $yearLevel,
        "academic_year" => $academicYear
    ];
    
    $result = $service->projectForRecord($record);
    
    echo "Term: $term\n";
    echo "Grant Amount: " . ($result["grant_amount"] ?? "N/A") . "\n";
    echo "Projected Total Expense: " . ($result["projected_total_expense"] ?? "0") . "\n";
    if (isset($result["projection_status"]) && $result["projection_status"] !== "configured") {
        echo "Status: " . $result["projection_status"] . " (" . ($result["projection_note"] ?? "") . ")\n";
    }
    echo "--------------------------\n";
}

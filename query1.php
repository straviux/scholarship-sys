echo "Profiles with municipality: " . App\Models\ScholarshipProfile::whereNotNull("municipality")->where("municipality", "!=", "")->count() . PHP_EOL;
echo "Total profiles: " . App\Models\ScholarshipProfile::count() . PHP_EOL;
$samples = App\Models\ScholarshipProfile::select("profile_id", "first_name", "last_name", "municipality")->limit(10)->get();
foreach($samples as $s) {
    echo $s->first_name . " " . $s->last_name . " -> " . ($s->municipality ?? "NULL") . PHP_EOL;
}

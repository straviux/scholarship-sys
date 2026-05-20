$names = ["HEMPLO","LOGRONIO","PALAO","RODRIGUEZ","ROSAURO"];
foreach($names as $n) {
    $p = App\Models\ScholarshipProfile::where("last_name", "like", "%" . $n . "%")->select("first_name", "last_name", "municipality")->first();
    echo $n . " -> " . ($p ? ($p->first_name . " " . $p->last_name . " / " . ($p->municipality ?? "NULL")) : "NOT FOUND") . PHP_EOL;
}

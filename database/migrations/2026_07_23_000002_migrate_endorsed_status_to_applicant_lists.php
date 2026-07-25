<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The old "endorse" workflow marked records with unified_status = 'endorsed'.
     * That workflow is replaced by the applicant tracking lists, so move those
     * records into the shared 'endorsed' list and return the record itself to
     * 'pending' (the status it held before being endorsed).
     */
    public function up(): void
    {
        $records = DB::table('scholarship_records')
            ->where('unified_status', 'endorsed')
            ->get(['id', 'profile_id', 'updated_at']);

        if ($records->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($records as $record) {
            if (!$record->profile_id) {
                continue;
            }

            $exists = DB::table('applicant_list_entries')
                ->where('profile_id', $record->profile_id)
                ->where('list_type', 'endorsed')
                ->exists();

            if (!$exists) {
                DB::table('applicant_list_entries')->insert([
                    'profile_id' => $record->profile_id,
                    'list_type' => 'endorsed',
                    'user_id' => null,
                    'added_by' => null,
                    'note' => 'Migrated from unified_status = endorsed',
                    'created_at' => $record->updated_at ?? $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('scholarship_records')
            ->where('unified_status', 'endorsed')
            ->update(['unified_status' => 'pending']);
    }

    /**
     * Restore the previous status for the entries this migration created.
     */
    public function down(): void
    {
        $profileIds = DB::table('applicant_list_entries')
            ->where('list_type', 'endorsed')
            ->where('note', 'Migrated from unified_status = endorsed')
            ->pluck('profile_id');

        if ($profileIds->isNotEmpty()) {
            DB::table('scholarship_records')
                ->whereIn('profile_id', $profileIds)
                ->where('unified_status', 'pending')
                ->update(['unified_status' => 'endorsed']);

            DB::table('applicant_list_entries')
                ->where('list_type', 'endorsed')
                ->where('note', 'Migrated from unified_status = endorsed')
                ->delete();
        }
    }
};

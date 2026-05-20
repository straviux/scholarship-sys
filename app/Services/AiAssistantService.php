<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Disbursement;
use App\Models\FundTransaction;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipRecord;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;
    private string $provider;

    public function __construct()
    {
        $this->provider = (string) config('services.ai.provider', 'groq');
        $providerCfg    = config('services.ai.' . $this->provider, []);

        $this->apiKey  = (string) ($providerCfg['key'] ?? '');
        $this->model   = (string) ($providerCfg['model'] ?? '');
        $this->baseUrl = rtrim((string) ($providerCfg['base_url'] ?? 'https://api.groq.com/openai/v1'), '/');

        // Fallback: if groq has no key but legacy openai.key is set, use openai
        if ($this->apiKey === '' && $this->provider === 'groq') {
            $legacy = (string) config('services.openai.key', '');
            if ($legacy !== '') {
                $this->provider = 'openai';
                $this->apiKey   = $legacy;
                $this->model    = (string) config('services.openai.model', 'gpt-4o-mini');
                $this->baseUrl  = 'https://api.openai.com/v1';
            }
        }
    }

    /**
     * Process a chat exchange. $messages is an array of [{role, content}, ...]
     * Returns a plain-text assistant reply.
     */
    public function chat(array $messages): string
    {
        if (empty($this->apiKey)) {
            return "⚠️ AI service is not configured. Please set `GROQ_API_KEY` (or `OPENAI_API_KEY`) in the `.env` file.";
        }

        $apiMessages = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt()]],
            $messages
        );

        // Loop to allow multiple tool-call rounds (max 3 rounds for safety)
        for ($round = 0; $round < 3; $round++) {
            $response = $this->callOpenAI($apiMessages, $this->getToolDefinitions());

            if (! $response) {
                return 'Unable to reach the AI service. Please check your API key or try again later.';
            }

            $message = $response['choices'][0]['message'] ?? null;
            if (! $message) {
                return 'No response received from AI.';
            }

            // If tool calls are present, execute them and loop
            if (! empty($message['tool_calls'])) {
                $apiMessages[] = $message;

                foreach ($message['tool_calls'] as $toolCall) {
                    $name = $toolCall['function']['name'] ?? '';
                    $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];
                    $result = $this->executeTool($name, $args);

                    $apiMessages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content'      => json_encode($result, JSON_UNESCAPED_UNICODE),
                    ];
                }
                continue;
            }

            return (string) ($message['content'] ?? 'No response.');
        }

        return 'The assistant could not complete the request after multiple steps. Please try rephrasing your question.';
    }

    // ────────────────────────────────────────────────────────────────────
    // OpenAI HTTP call
    // ────────────────────────────────────────────────────────────────────

    private function callOpenAI(array $messages, array $tools = []): ?array
    {
        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.3,
            'max_tokens'  => 1500,
        ];

        if (! empty($tools)) {
            $payload['tools']       = $tools;
            $payload['tool_choice'] = 'auto';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(45)->post($this->baseUrl . '/chat/completions', $payload);

            if ($response->failed()) {
                Log::error('AI API error [' . $this->provider . ']', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('AI HTTP exception [' . $this->provider . ']: ' . $e->getMessage());
            return null;
        }
    }

    private function buildSystemPrompt(): string
    {
        $now = Carbon::now()->format('F j, Y');

        return <<<PROMPT
You are **YAKAP Scholar AI**, the data assistant for the **Akbay sa Mag-Aaral Yaman ng Kinabukasan (AMYK)** Scholarship Management System of the Provincial Government of Palawan. Today is {$now}.

You help administrators and program managers by:
- Answering questions about scholars, applications, fund transactions, and disbursements
- Generating data summaries and counts
- Providing program-level and geographic insights

Rules:
- ALWAYS use the provided tools to fetch real numbers. Never invent counts or amounts.
- Format peso amounts with ₱ prefix and thousand separators (e.g. ₱1,250,000.00).
- Use professional, concise language. Present structured data as markdown tables or bullet lists when helpful.
- If the question is unrelated to the scholarship system, politely decline.
- If a tool returns no data, say so clearly instead of guessing.
PROMPT;
    }

    // ────────────────────────────────────────────────────────────────────
    // Tool definitions (OpenAI function-calling schema)
    // ────────────────────────────────────────────────────────────────────

    private function getToolDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_dashboard_summary',
                    'description' => 'Get overall system statistics: total profiles, scholarship records by status, programs, schools, and applications this month/year.',
                    'parameters'  => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_scholars_by_program',
                    'description' => 'Get scholar/application counts grouped by scholarship program. Optionally filter by academic year or unified status.',
                    'parameters'  => [
                        'type' => 'object',
                        'properties' => [
                            'academic_year' => ['type' => 'string', 'description' => 'e.g. 2025-2026'],
                            'status'        => ['type' => 'string', 'enum' => ['pending', 'approved', 'active', 'denied', 'inactive', 'graduated', 'dropped']],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_scholars_by_municipality',
                    'description' => 'Get the distribution of scholarship profiles by municipality (top 20).',
                    'parameters'  => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_fund_transactions_summary',
                    'description' => 'Get fund transaction (OBR/voucher) totals. Optional filters by fiscal year, scholarship program, or OBR type.',
                    'parameters'  => [
                        'type' => 'object',
                        'properties' => [
                            'fiscal_year'            => ['type' => 'string', 'description' => 'e.g. 2025'],
                            'scholarship_program_id' => ['type' => 'integer'],
                            'obr_type'               => ['type' => 'string'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_pending_applications',
                    'description' => 'List the most recent pending scholarship applications (applicant name, municipality, program, AY, date filed).',
                    'parameters'  => [
                        'type' => 'object',
                        'properties' => [
                            'limit' => ['type' => 'integer', 'description' => 'Default 10, max 30'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_disbursements_summary',
                    'description' => 'Get disbursement totals. Optional filters by academic year or semester.',
                    'parameters'  => [
                        'type' => 'object',
                        'properties' => [
                            'academic_year' => ['type' => 'string'],
                            'semester'      => ['type' => 'string'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_recent_activity',
                    'description' => 'Show recent activity log entries (latest actions performed by users in the system).',
                    'parameters'  => [
                        'type' => 'object',
                        'properties' => [
                            'limit' => ['type' => 'integer', 'description' => 'Default 10, max 20'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'search_scholar_by_name',
                    'description' => 'Search for a scholar profile by partial first or last name. Returns up to 10 matches with municipality and active record count.',
                    'parameters'  => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Name fragment, min 2 chars'],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'list_programs',
                    'description' => 'List all scholarship programs with their id, name, and short name. Use this when the user references a program by name and you need its id.',
                    'parameters'  => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
        ];
    }

    // ────────────────────────────────────────────────────────────────────
    // Tool dispatcher
    // ────────────────────────────────────────────────────────────────────

    private function executeTool(string $name, array $args): array
    {
        try {
            return match ($name) {
                'get_dashboard_summary'         => $this->toolDashboardSummary(),
                'get_scholars_by_program'       => $this->toolScholarsByProgram($args),
                'get_scholars_by_municipality'  => $this->toolScholarsByMunicipality(),
                'get_fund_transactions_summary' => $this->toolFundTransactionsSummary($args),
                'get_pending_applications'      => $this->toolPendingApplications($args),
                'get_disbursements_summary'     => $this->toolDisbursementsSummary($args),
                'get_recent_activity'           => $this->toolRecentActivity($args),
                'search_scholar_by_name'        => $this->toolSearchScholar($args),
                'list_programs'                 => $this->toolListPrograms(),
                default                         => ['error' => 'Unknown tool: ' . $name],
            };
        } catch (\Throwable $e) {
            Log::error("AI tool [{$name}] failed: " . $e->getMessage());
            return ['error' => 'Tool execution failed: ' . $e->getMessage()];
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // Tool implementations
    // ────────────────────────────────────────────────────────────────────

    private function toolDashboardSummary(): array
    {
        $statusCounts = ScholarshipRecord::select('unified_status', DB::raw('COUNT(*) as count'))
            ->groupBy('unified_status')
            ->pluck('count', 'unified_status')
            ->toArray();

        return [
            'total_profiles'           => ScholarshipProfile::count(),
            'total_scholarship_records' => ScholarshipRecord::count(),
            'records_by_status'        => $statusCounts,
            'total_programs'           => ScholarshipProgram::count(),
            'total_schools'            => School::count(),
            'applications_this_month'  => ScholarshipRecord::whereMonth('date_filed', now()->month)
                ->whereYear('date_filed', now()->year)->count(),
            'applications_this_year'   => ScholarshipRecord::whereYear('date_filed', now()->year)->count(),
        ];
    }

    private function toolScholarsByProgram(array $args): array
    {
        $query = ScholarshipRecord::leftJoin('scholarship_programs', 'scholarship_records.program_id', '=', 'scholarship_programs.id')
            ->select(
                DB::raw('COALESCE(scholarship_programs.name, "No Program") as program_name'),
                DB::raw('COALESCE(scholarship_programs.shortname, "N/A") as program_shortname'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN unified_status IN ("approved","active") THEN 1 ELSE 0 END) as approved_or_active'),
                DB::raw('SUM(CASE WHEN unified_status = "pending" THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN unified_status = "denied" THEN 1 ELSE 0 END) as denied')
            )
            ->groupBy('scholarship_records.program_id', 'scholarship_programs.name', 'scholarship_programs.shortname');

        if (! empty($args['academic_year'])) {
            $query->where('scholarship_records.academic_year', $args['academic_year']);
        }
        if (! empty($args['status'])) {
            $query->where('scholarship_records.unified_status', $args['status']);
        }

        return $query->orderByDesc('total')->limit(20)->get()->toArray();
    }

    private function toolScholarsByMunicipality(): array
    {
        return ScholarshipProfile::select('municipality', DB::raw('COUNT(*) as count'))
            ->whereNotNull('municipality')
            ->where('municipality', '!=', '')
            ->groupBy('municipality')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->toArray();
    }

    private function toolFundTransactionsSummary(array $args): array
    {
        $query = FundTransaction::query();

        if (! empty($args['fiscal_year'])) {
            $query->where('fiscal_year', $args['fiscal_year']);
        }
        if (! empty($args['scholarship_program_id'])) {
            $query->where('scholarship_program_id', (int) $args['scholarship_program_id']);
        }
        if (! empty($args['obr_type'])) {
            $query->where('obr_type', $args['obr_type']);
        }

        $filtered = [
            'total_amount' => (float) $query->sum('amount'),
            'total_count'  => (clone $query)->count(),
        ];

        return [
            'filtered'       => $filtered,
            'by_obr_type'    => FundTransaction::select('obr_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
                ->whereNotNull('obr_type')
                ->groupBy('obr_type')
                ->orderByDesc('total_amount')
                ->limit(10)
                ->get()->toArray(),
            'by_fiscal_year' => FundTransaction::select('fiscal_year', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
                ->whereNotNull('fiscal_year')
                ->groupBy('fiscal_year')
                ->orderByDesc('fiscal_year')
                ->limit(5)
                ->get()->toArray(),
            'by_status'      => FundTransaction::select('transaction_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
                ->groupBy('transaction_status')
                ->get()->toArray(),
        ];
    }

    private function toolPendingApplications(array $args): array
    {
        $limit = min(max((int) ($args['limit'] ?? 10), 1), 30);

        // Load programs map (program_id is a direct column on scholarship_records)
        $programs = ScholarshipProgram::pluck('shortname', 'id')->toArray();

        $records = ScholarshipRecord::with(['profile:profile_id,first_name,last_name,municipality'])
            ->where('unified_status', 'pending')
            ->orderByDesc('date_filed')
            ->limit($limit)
            ->get(['id', 'profile_id', 'program_id', 'academic_year', 'date_filed', 'unified_status'])
            ->map(fn($r) => [
                'name'          => $r->profile ? trim($r->profile->last_name . ', ' . $r->profile->first_name) : 'N/A',
                'municipality'  => $r->profile?->municipality ?? 'N/A',
                'program'       => $programs[$r->program_id] ?? 'N/A',
                'academic_year' => $r->academic_year ?? 'N/A',
                'date_filed'    => $r->date_filed?->format('M d, Y') ?? 'N/A',
            ])->toArray();

        return [
            'total_pending' => ScholarshipRecord::where('unified_status', 'pending')->count(),
            'records'       => $records,
        ];
    }

    private function toolDisbursementsSummary(array $args): array
    {
        $query = Disbursement::query();

        if (! empty($args['academic_year'])) {
            $query->where('academic_year', $args['academic_year']);
        }
        if (! empty($args['semester'])) {
            $query->where('semester', $args['semester']);
        }

        return [
            'filtered' => [
                'total_amount' => (float) $query->sum('amount'),
                'total_count'  => (clone $query)->count(),
            ],
            'by_academic_year' => Disbursement::select('academic_year', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->whereNotNull('academic_year')
                ->groupBy('academic_year')
                ->orderByDesc('academic_year')
                ->limit(5)
                ->get()->toArray(),
            'by_semester' => Disbursement::select('semester', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->whereNotNull('semester')
                ->groupBy('semester')
                ->get()->toArray(),
        ];
    }

    private function toolRecentActivity(array $args): array
    {
        $limit = min(max((int) ($args['limit'] ?? 10), 1), 20);

        $logs = ActivityLog::with('user:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($l) => [
                'action'        => $l->action ?? $l->activity_type ?? 'N/A',
                'activity_type' => $l->activity_type ?? null,
                'description'   => $l->description ?? '',
                'user'          => $l->user?->name ?? 'System',
                'created_at'    => $l->created_at?->format('M d, Y h:i A'),
            ])->toArray();

        return ['logs' => $logs];
    }

    private function toolSearchScholar(array $args): array
    {
        $q = trim((string) ($args['query'] ?? ''));
        if (strlen($q) < 2) {
            return ['error' => 'Search query must be at least 2 characters.'];
        }

        $profiles = ScholarshipProfile::where(function ($w) use ($q) {
            $w->where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhere('middle_name', 'like', "%{$q}%");
        })
            ->select('profile_id', 'first_name', 'last_name', 'middle_name', 'municipality', 'is_active')
            ->withCount(['scholarshipGrant as records_count'])
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'profile_id'   => $p->profile_id,
                'name'         => trim($p->last_name . ', ' . $p->first_name . ' ' . ($p->middle_name ?? '')),
                'municipality' => $p->municipality ?? 'N/A',
                'records'      => $p->records_count ?? 0,
                'is_active'    => (bool) $p->is_active,
            ])->toArray();

        return [
            'matches'      => $profiles,
            'total_found'  => count($profiles),
        ];
    }

    private function toolListPrograms(): array
    {
        return ScholarshipProgram::select('id', 'name', 'shortname', 'is_active')
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}

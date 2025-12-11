<?php

namespace App\Jobs;

use App\Services\JasperReportService;
use App\Services\JasperReportDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

/**
 * GenerateJasperReport Job
 * 
 * Async job for generating JasperReports.
 * Allows long-running reports to be processed in the background without blocking user requests.
 */
class GenerateJasperReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out
     * 
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * The number of times the job may be attempted
     * 
     * @var int
     */
    public $tries = 3;

    /**
     * Report configuration
     */
    protected $config;

    /**
     * User who triggered the report
     */
    protected $userId;

    /**
     * Report callback URL (optional)
     */
    protected $callbackUrl;

    /**
     * Create a new job instance
     * 
     * @param array $config Report configuration
     * @param int $userId User ID who triggered the report
     * @param string|null $callbackUrl Optional callback URL for completion notification
     */
    public function __construct(array $config, int $userId, ?string $callbackUrl = null)
    {
        $this->config = $config;
        $this->userId = $userId;
        $this->callbackUrl = $callbackUrl;

        // Set queue configuration
        $this->onQueue(config('jasperreports.queue.queue', 'default'));
        $this->tries = config('jasperreports.queue.tries', 3);
        $this->timeout = config('jasperreports.queue.timeout', 300);
    }

    /**
     * Execute the job
     * 
     * @param JasperReportService $reportService
     * @param JasperReportDataService $dataService
     * @return void
     * @throws \Exception
     */
    public function handle(JasperReportService $reportService, JasperReportDataService $dataService): void
    {
        try {
            Log::info('JasperReport Job Started', [
                'template' => $this->config['template'] ?? 'unknown',
                'user_id' => $this->userId,
            ]);

            // Set up the report service
            $report = $reportService->template($this->config['template'] ?? 'waiting_list');

            // Add all parameters
            if (isset($this->config['parameters'])) {
                foreach ($this->config['parameters'] as $key => $value) {
                    $report->parameter($key, $value);
                }
            }

            // Handle data export if needed
            if (isset($this->config['export_data']) && $this->config['export_data']) {
                $data = $this->retrieveData($dataService);
                $report->parameter('data', $data);
            }

            // Set format
            if (isset($this->config['format'])) {
                $report->format($this->config['format']);
            }

            // Generate the report
            $outputPath = $report->generate();

            Log::info('JasperReport Generated Successfully', [
                'template' => $this->config['template'],
                'output' => $outputPath,
                'file_size' => filesize($outputPath),
                'user_id' => $this->userId,
            ]);

            // Store output path for later retrieval
            $this->storeReportMetadata($outputPath);

            // Call callback if provided
            if ($this->callbackUrl) {
                $this->notifyCompletion($outputPath);
            }
        } catch (\Exception $e) {
            Log::error('JasperReport Generation Failed', [
                'template' => $this->config['template'] ?? 'unknown',
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
                'trace' => $e->getTraceAsString(),
            ]);

            // Rethrow to trigger retries
            throw $e;
        }
    }

    /**
     * Handle job failure
     * 
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception): void
    {
        Log::critical('JasperReport Job Failed After Retries', [
            'template' => $this->config['template'] ?? 'unknown',
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
        ]);

        // Optionally: Send notification to user that report generation failed
        // Notification::route('mail', User::find($this->userId)->email)
        //     ->notify(new ReportGenerationFailed($this->config));
    }

    /**
     * Retrieve data for the report
     * 
     * @param JasperReportDataService $dataService
     * @return array
     */
    protected function retrieveData(JasperReportDataService $dataService): array
    {
        $filters = $this->config['filters'] ?? [];
        $reportType = $this->config['type'] ?? 'profiles';

        return match ($reportType) {
            'profiles' => $dataService->getScholarshipProfiles($filters)->toArray(),
            'records' => $dataService->getScholarshipRecords($filters)->toArray(),
            'disbursements' => $dataService->getDisbursements($filters)->toArray(),
            'statistics' => $dataService->getStatistics($filters, $this->config['group_by'] ?? 'program'),
            default => [],
        };
    }

    /**
     * Store report metadata for retrieval
     * 
     * @param string $outputPath
     * @return void
     */
    protected function storeReportMetadata(string $outputPath): void
    {
        $metadata = [
            'path' => $outputPath,
            'template' => $this->config['template'] ?? 'unknown',
            'format' => $this->config['format'] ?? 'pdf',
            'user_id' => $this->userId,
            'generated_at' => now()->toIso8601String(),
            'file_size' => filesize($outputPath),
        ];

        $filename = 'report_' . $this->userId . '_' . now()->timestamp . '.json';
        Storage::disk('local')->put('jasper-reports/' . $filename, json_encode($metadata, JSON_PRETTY_PRINT));
    }

    /**
     * Notify completion via callback URL
     * 
     * @param string $outputPath
     * @return void
     */
    protected function notifyCompletion(string $outputPath): void
    {
        try {
            $client = new \GuzzleHttp\Client();

            $client->post($this->callbackUrl, [
                'json' => [
                    'status' => 'completed',
                    'template' => $this->config['template'],
                    'output' => basename($outputPath),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to notify report completion', [
                'callback_url' => $this->callbackUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

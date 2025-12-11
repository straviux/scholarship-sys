<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Exception;
use RuntimeException;
use InvalidArgumentException;

/**
 * JasperReportService
 * 
 * Core service for generating reports using JasperReports.
 * Handles template compilation, data binding, and report execution.
 */
class JasperReportService
{
    /**
     * Logger instance
     */
    protected $logger;

    /**
     * Data service instance
     */
    protected $dataService;

    /**
     * Report template name
     */
    protected $templateName;

    /**
     * Report parameters
     */
    protected $parameters = [];

    /**
     * Output format
     */
    protected $format = 'pdf';

    /**
     * Output file path
     */
    protected $outputPath;

    public function __construct(JasperReportDataService $dataService)
    {
        $this->logger = Log::channel(config('jasperreports.logging.channel'));
        $this->dataService = $dataService;

        // Ensure output directory exists
        $this->ensureDirectoryExists(config('jasperreports.output.path'));
        $this->ensureDirectoryExists(config('jasperreports.templates.compiled_path'));
    }

    /**
     * Set the template to use
     * 
     * @param string $templateName
     * @return self
     */
    public function template(string $templateName): self
    {
        $this->templateName = $templateName;
        return $this;
    }

    /**
     * Add a parameter to the report
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function parameter(string $key, $value): self
    {
        $this->parameters[$key] = $value;
        return $this;
    }

    /**
     * Add multiple parameters
     * 
     * @param array $parameters
     * @return self
     */
    public function parameters(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }

    /**
     * Set output format
     * 
     * @param string $format
     * @return self
     */
    public function format(string $format): self
    {
        $supported = config('jasperreports.output.formats', ['pdf']);

        if (!in_array($format, $supported)) {
            throw new InvalidArgumentException("Unsupported format: {$format}");
        }

        $this->format = $format;
        return $this;
    }

    /**
     * Generate the report
     * 
     * @return string (output file path)
     * @throws RuntimeException
     */
    public function generate(): string
    {
        if (!$this->templateName) {
            throw new RuntimeException("Template not set. Call template() first.");
        }

        if (!config('jasperreports.java.enabled')) {
            throw new RuntimeException("JasperReports is not enabled. Set JASPER_ENABLED=true in .env");
        }

        try {
            // Get template path
            $templatePath = $this->getTemplatePath();

            // Compile template if not already compiled
            $compiledTemplatePath = $this->compileTemplate($templatePath);

            // Generate output filename
            $this->outputPath = $this->generateOutputPath();

            // Build command
            $command = $this->buildCommand($compiledTemplatePath);

            // Execute command
            $this->executeCommand($command);

            // For JAR execution, JasperStarter adds the extension automatically
            $starterPath = $this->getJasperStarterPath();
            $isJar = strtolower(pathinfo($starterPath, PATHINFO_EXTENSION)) === 'jar';

            if ($isJar) {
                // JAR version adds the extension, so check for that
                $actualPath = $this->outputPath;
                if (!file_exists($actualPath)) {
                    throw new RuntimeException("Report generation failed: Output file not created at {$actualPath}");
                }
                // The outputPath already has the extension, so it's correct
            } else {
                // Executable version doesn't add extension if included in output path
                if (!file_exists($this->outputPath)) {
                    throw new RuntimeException("Report generation failed: Output file not created");
                }
            }

            // Log successful generation
            $this->logger->info('Report generated successfully', [
                'template' => $this->templateName,
                'format' => $this->format,
                'output' => $this->outputPath,
                'file_size' => filesize($this->outputPath),
            ]);

            return $this->outputPath;
        } catch (Exception $e) {
            $this->logger->error('Report generation failed', [
                'template' => $this->templateName,
                'error' => $e->getMessage(),
                'parameters' => $this->sanitizeParameters($this->parameters),
            ]);

            throw new RuntimeException("Failed to generate report: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Generate report and return as file download
     * 
     * @return array (stream context for download)
     */
    public function download(): array
    {
        $path = $this->generate();

        return [
            'path' => $path,
            'name' => basename($path),
            'mime' => $this->getMimeType(),
        ];
    }

    /**
     * Get the template file path
     * 
     * @return string
     * @throws RuntimeException
     */
    protected function getTemplatePath(): string
    {
        $templates = config('jasperreports.templates.reports', []);

        if (!isset($templates[$this->templateName])) {
            throw new RuntimeException("Template not configured: {$this->templateName}");
        }

        $templateFile = config('jasperreports.templates.base_path') . '/' . $templates[$this->templateName];

        if (!file_exists($templateFile)) {
            throw new RuntimeException("Template file not found: {$templateFile}");
        }

        return $templateFile;
    }

    /**
     * Compile JRXML template to Jasper format
     * 
     * @param string $templatePath
     * @return string (path to compiled template)
     */
    protected function compileTemplate(string $templatePath): string
    {
        $compiledPath = config('jasperreports.templates.compiled_path') . '/'
            . Str::slug($this->templateName) . '.jasper';

        // Return if already compiled and caching is enabled
        if (file_exists($compiledPath) && config('jasperreports.performance.cache_templates')) {
            return $compiledPath;
        }

        // Use jasperstarter to compile
        $starterPath = $this->getJasperStarterPath();

        // Determine if we're using JAR or executable
        $isJar = strtolower(pathinfo($starterPath, PATHINFO_EXTENSION)) === 'jar';

        if ($isJar) {
            // Use java -jar approach (different syntax for JAR version)
            $javaPath = 'java'; // Java should be in PATH from environment setup
            $command = sprintf(
                '%s -jar "%s" compile "%s" -o "%s"',
                $javaPath,
                $starterPath,
                $templatePath,
                $compiledPath
            );
        } else {
            // Use executable directly (old syntax)
            $command = sprintf(
                '%s cp -t jrxml -f jasper "%s" "%s"',
                $starterPath,
                $templatePath,
                $compiledPath
            );
        }

        $this->logger->debug("Compiling template with command: {$command}");

        // Set up environment with Java path
        $env = [];
        if ($javaHome = config('jasperreports.java.path')) {
            $env['JAVA_HOME'] = $javaHome;
            // On Windows, add to PATH
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $env['PATH'] = $javaHome . '\bin;' . (getenv('PATH') ?: '');
            } else {
                $env['PATH'] = $javaHome . '/bin:' . (getenv('PATH') ?: '');
            }
        }

        $result = Process::env($env)->run($command);

        if ($result->exitCode() !== 0) {
            $error = $result->errorOutput() ?: $result->output();
            throw new RuntimeException("Template compilation failed: {$error} (Exit code: {$result->exitCode()})");
        }

        $this->logger->debug("Template compiled", [
            'source' => $templatePath,
            'compiled' => $compiledPath,
        ]);

        return $compiledPath;
    }

    /**
     * Build the jasperstarter command
     * 
     * @param string $compiledTemplatePath
     * @return string
     */
    protected function buildCommand(string $compiledTemplatePath): string
    {
        $dataFile = $this->createDataFile();

        $starterPath = $this->getJasperStarterPath();
        $isJar = strtolower(pathinfo($starterPath, PATHINFO_EXTENSION)) === 'jar';

        if ($isJar) {
            // Use java -jar approach (different syntax for JAR version)
            $javaPath = 'java'; // Java should be in PATH from environment setup

            // For JAR version, remove file extension since JasperStarter adds it
            $outputPathWithoutExt = preg_replace('/\.' . preg_quote($this->format) . '$/', '', $this->outputPath);

            // Build Java memory options (goes before -jar for JAR execution)
            $javaOpts = '';
            if ($memory = config('jasperreports.java.max_memory')) {
                $javaOpts = sprintf('-Xmx%s', $memory);
            }

            $command = sprintf(
                '%s %s -jar "%s" process "%s" -f %s -o "%s"',
                $javaPath,
                $javaOpts,
                $starterPath,
                $compiledTemplatePath,
                $this->format,
                $outputPathWithoutExt
            );
        } else {
            // Use executable directly (old syntax)
            $command = sprintf(
                '%s process "%s" -t %s -o "%s"',
                $starterPath,
                $compiledTemplatePath,
                $this->format,
                $this->outputPath
            );

            // Add Java memory settings (goes after command for executable)
            if ($memory = config('jasperreports.java.max_memory')) {
                $command .= sprintf(' -J-Xmx%s', $memory);
            }
        }

        // Add data source
        if ($this->shouldUseJsonData()) {
            $command .= sprintf(' --data-file "%s" --data-source-type json', $dataFile);
        }

        // Add parameters
        foreach ($this->parameters as $key => $value) {
            $command .= sprintf(' -P %s="%s"', $key, escapeshellarg((string)$value));
        }

        return $command;
    }

    /**
     * Execute the report generation command
     * 
     * @param string $command
     * @throws RuntimeException
     */
    protected function executeCommand(string $command): void
    {
        $timeout = config('jasperreports.java.timeout', 60);

        // Set up environment with Java path
        $env = [];
        if ($javaHome = config('jasperreports.java.path')) {
            $env['JAVA_HOME'] = $javaHome;
            // On Windows, add to PATH
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $env['PATH'] = $javaHome . '\bin;' . (getenv('PATH') ?: '');
            } else {
                $env['PATH'] = $javaHome . '/bin:' . (getenv('PATH') ?: '');
            }
        }

        $result = Process::timeout($timeout)->env($env)->run($command);

        if ($result->exitCode() !== 0) {
            $error = $result->errorOutput() ?: $result->output();
            throw new RuntimeException("Command execution failed: {$error} (Exit code: {$result->exitCode()})");
        }

        $this->logger->debug("Report command executed", [
            'command' => $this->sanitizeCommand($command),
            'exit_code' => $result->exitCode(),
        ]);
    }

    /**
     * Create data file for JSON data source
     * 
     * @return string (file path)
     */
    protected function createDataFile(): string
    {
        // If parameters contain data, export it
        if (isset($this->parameters['data'])) {
            $data = $this->parameters['data'];
            $filename = Str::slug($this->templateName) . '_' . now()->timestamp . '.json';
            $path = config('jasperreports.output.path') . '/' . $filename;

            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

            return $path;
        }

        // Return empty data file
        $filename = 'empty_' . now()->timestamp . '.json';
        $path = config('jasperreports.output.path') . '/' . $filename;

        file_put_contents($path, json_encode(['data' => []], JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * Check if using JSON data source
     * 
     * @return bool
     */
    protected function shouldUseJsonData(): bool
    {
        return config('jasperreports.datasource.type') === 'json'
            && isset($this->parameters['data']);
    }

    /**
     * Generate output file path
     * 
     * @return string
     */
    protected function generateOutputPath(): string
    {
        $filename = Str::slug($this->templateName) . '_'
            . now()->timestamp . '_' . Str::random(8)
            . '.' . $this->format;

        return config('jasperreports.output.path') . '/' . $filename;
    }

    /**
     * Get JasperStarter executable path
     * 
     * @return string
     * @throws RuntimeException
     */
    protected function getJasperStarterPath(): string
    {
        $bin = config('jasperreports.jasperstarter.bin');

        // On Windows, prefer using JAR directly to avoid .exe compatibility issues
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $jarPath = 'C:\jasperstarter\lib\jasperstarter.jar';
            if (file_exists($jarPath)) {
                $this->logger->debug("Using JasperStarter JAR for Windows compatibility: {$jarPath}");
                return $jarPath;
            }
        }

        if ($bin && file_exists($bin)) {
            $this->logger->debug("Using JasperStarter from configured path: {$bin}");
            return $bin;
        }

        $path = config('jasperreports.jasperstarter.path');

        // Try to find in PATH (use where on Windows, which on Linux)
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $whereCmd = $isWindows ? 'where' : 'which';

        $result = Process::run("{$whereCmd} {$path}");
        if ($result->exitCode() === 0 && $output = $result->output()) {
            $found = trim($output);
            $this->logger->debug("Found JasperStarter in PATH: {$found}");
            return $found;
        }

        // Try default Windows locations
        if ($isWindows) {
            $defaultPaths = [
                'C:\jasperstarter\bin\jasperstarter.exe',
                'C:\Program Files (x86)\JasperStarter\bin\jasperstarter.exe',
                'C:\Program Files\JasperStarter\bin\jasperstarter.exe',
            ];

            foreach ($defaultPaths as $defaultPath) {
                if (file_exists($defaultPath)) {
                    $this->logger->debug("Found JasperStarter at default Windows location: {$defaultPath}");
                    return $defaultPath;
                }
            }
        }

        throw new RuntimeException("JasperStarter not found at: {$path}. Please configure JASPERSTARTER_BIN in .env");
    }

    /**
     * Ensure directory exists
     * 
     * @param string $path
     * @return void
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Get MIME type for format
     * 
     * @return string
     */
    protected function getMimeType(): string
    {
        return match ($this->format) {
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'html' => 'text/html',
            default => 'application/octet-stream',
        };
    }

    /**
     * Sanitize command for logging (remove sensitive data)
     * 
     * @param string $command
     * @return string
     */
    protected function sanitizeCommand(string $command): string
    {
        // Remove password and sensitive parameters
        return preg_replace('/-P[^\s]+="[^"]*password[^"]*"/', '-P***password***', $command);
    }

    /**
     * Sanitize parameters for logging
     * 
     * @param array $parameters
     * @return array
     */
    protected function sanitizeParameters(array $parameters): array
    {
        $sanitized = [];

        foreach ($parameters as $key => $value) {
            if (in_array(strtolower($key), ['password', 'secret', 'token', 'api_key'])) {
                $sanitized[$key] = '***REDACTED***';
            } else {
                $sanitized[$key] = is_array($value) ? 'Array[' . count($value) . ']' : substr((string)$value, 0, 100);
            }
        }

        return $sanitized;
    }
}

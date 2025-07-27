<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use App\Models\EmailTemplate;

class SettingsController extends Controller
{
    /**
     * Display the admin settings page.
     */
    public function index()
    {
        $settings = [];
        if (Storage::disk('local')->exists('settings.json')) {
            $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
        }
        $googleAnalyticsCode = $settings['google_analytics_code'] ?? '';
        $premiumProductSpots = $settings['premium_product_spots'] ?? 6;
        $productPublishTime = $settings['product_publish_time'] ?? '07:00';
        return view('admin.settings.index', compact('googleAnalyticsCode', 'premiumProductSpots', 'productPublishTime'));
    }

    public function emailTemplates()
    {
        $template = \App\Models\EmailTemplate::where('name', 'product_approved')->first();
        return view('admin.settings.email_templates', compact('template'));
    }

    public function storeEmailTemplates(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_html' => 'boolean',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'reply_to_email' => 'nullable|email|max:255',
        ]);

        $template = \App\Models\EmailTemplate::where('name', 'product_approved')->first();
        $template->update($request->all());

        return back()->with('success', 'Email template updated successfully.');
    }

    public function storeAnalyticsCode(Request $request)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'google_analytics_code' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if (!empty($value) && (!str_contains($value, '<script') || !str_contains($value, '</script>'))) {
                    $fail('The '.$attribute.' must be a valid script tag.');
                }
            }],
        ]);

        $settings = [];
        if (Storage::disk('local')->exists('settings.json')) {
            $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
        }

        $settings['google_analytics_code'] = $request->input('google_analytics_code', '');
        
        try {
            Storage::disk('local')->put('settings.json', json_encode($settings, JSON_PRETTY_PRINT));
            Log::info('Google Analytics code updated by user: ' . Auth::id());
            return back()->with('success', 'Google Analytics code saved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to save Google Analytics code: ' . $e->getMessage());
            return back()->with('error', 'Failed to save Google Analytics code. Please check logs.');
        }
    }

    public function storePremiumProductSpots(Request $request)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'premium_product_spots' => 'required|integer|min:0',
        ]);

        $settings = [];
        if (Storage::disk('local')->exists('settings.json')) {
            $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
        }

        $settings['premium_product_spots'] = $request->input('premium_product_spots');
        
        try {
            Storage::disk('local')->put('settings.json', json_encode($settings, JSON_PRETTY_PRINT));
            Log::info('Premium product spots updated by user: ' . Auth::id());
            return back()->with('success', 'Premium product spots saved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to save premium product spots: ' . $e->getMessage());
            return back()->with('error', 'Failed to save premium product spots. Please check logs.');
        }
    }

    public function storePublishTime(Request $request)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'product_publish_time' => 'required|date_format:H:i',
        ]);

        $settingsPath = 'settings.json';
        $currentSettings = [];

        if (Storage::disk('local')->exists($settingsPath)) {
            $currentSettings = json_decode(Storage::disk('local')->get($settingsPath), true);
        }

        // Ensure default structure if file was just created or empty
        $defaultSettings = [
            'google_analytics_code' => '',
            'premium_product_spots' => 6,
            'product_publish_time' => '07:00',
        ];

        $settingsToSave = array_merge($defaultSettings, $currentSettings);
        $settingsToSave['product_publish_time'] = $request->input('product_publish_time');
        
        try {
            Storage::disk('local')->put($settingsPath, json_encode($settingsToSave, JSON_PRETTY_PRINT));
            Log::info('Product publish time updated by user: ' . Auth::id());
            return back()->with('success', 'Product publish time saved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to save product publish time: ' . $e->getMessage());
            return back()->with('error', 'Failed to save product publish time. Please check logs.');
        }
    }

    /**
     * Handle the database export request.
     */
    public function exportDatabase()
    {
        // Ensure only admins can access this. Middleware should also protect the route.
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $dbName = DB::connection()->getDatabaseName();
            $dbUser = DB::connection()->getConfig('username');
            $dbPassword = DB::connection()->getConfig('password');
            $dbHost = DB::connection()->getConfig('host');
            $dbPort = DB::connection()->getConfig('port');
            $dbDriver = DB::connection()->getConfig('driver');

            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup-{$dbName}-{$timestamp}.sql";
            // Path relative to the storage disk's root (e.g., storage/app/ if disk is 'local')
            $storageDiskRelativePath = 'temp_backups';
            $fullStorageDiskPathWithFile = $storageDiskRelativePath . '/' . $filename;


            // Ensure temp directory exists on the 'local' disk (storage/app/temp_backups)
            if (!Storage::disk('local')->exists($storageDiskRelativePath)) {
                Storage::disk('local')->makeDirectory($storageDiskRelativePath);
            }

            $command = [];
            $envVars = [];

            if ($dbDriver === 'mysql') {
                $mysqldumpPath = env('MYSQLDUMP_PATH', 'mysqldump');
                $command = [
                    $mysqldumpPath,
                    "--host={$dbHost}",
                    "--port={$dbPort}",
                    "--user={$dbUser}",
                    $dbName,
                ];
                if ($dbPassword) { // Only add password if it's set
                    $envVars['MYSQL_PWD'] = $dbPassword;
                }
            } elseif ($dbDriver === 'pgsql') {
                $command = [
                    'pg_dump',
                    "--host={$dbHost}",
                    "--port={$dbPort}",
                    "--username={$dbUser}",
                    $dbName,
                ];
                $envVars['PGPASSWORD'] = $dbPassword;
            } elseif ($dbDriver === 'sqlite') {
                $sqlite3Path = env('SQLITE3_PATH', 'sqlite3');
                $command = [$sqlite3Path, $dbName, '.dump'];
            } else {
                Log::error("Database export failed: Unsupported database driver '{$dbDriver}'.");
                return back()->with('error', 'Database export failed: Unsupported database driver.');
            }
            
            // The $command array was for an older approach and is not used by Process for output redirection.
            // The $processCommand array is correctly built below for Process.
            // Lines for adding '>' and $fullPath to $command are removed.

            // For Windows, command might need to be `cmd /C "actual command > file"`
            // For simplicity, assuming Unix-like environment for direct redirection.
            // A more robust way is to pipe output within Process if direct redirection is problematic.
            // However, Process component handles command arrays well. Let's try direct.
            // If direct redirection `>` in command array fails, we'll pipe output.

            // Reconstruct command for Process if it includes redirection
            // Process component does not handle shell redirection (>) directly in the array.
            // We need to either pipe the output or use shell_exec (less safe) or write to file via Process output.

            // Let's use Process and get output, then write to file.
            if ($dbDriver === 'mysql') {
                $mysqldumpPath = env('MYSQLDUMP_PATH', 'mysqldump');
                $processCommand = [
                    $mysqldumpPath,
                    "--host={$dbHost}",
                    "--port={$dbPort}",
                    "--user={$dbUser}",
                ];
                // It's generally safer to use MYSQL_PWD for passwords with mysqldump via Process
                // rather than --password=... directly in the command for some versions/setups.
                // The $envVars['MYSQL_PWD'] = $dbPassword; approach is good.
                // If $dbPassword is null/empty, MYSQL_PWD should not be set or mysqldump might prompt.
                // The original code for $envVars was correct.
                $processCommand[] = $dbName;

            } elseif ($dbDriver === 'pgsql') {
                $pgdumpPath = env('PGDUMP_PATH', 'pg_dump');
                $processCommand = [
                    $pgdumpPath,
                    "--host={$dbHost}",
                    "--port={$dbPort}",
                    "--username={$dbUser}",
                    $dbName,
                ];
                 // PGPASSWORD is set via environment variable for pg_dump
            } elseif ($dbDriver === 'sqlite') {
                $sqlite3Path = env('SQLITE3_PATH', 'sqlite3');
                $processCommand = [$sqlite3Path, $dbName, '.dump'];
            } else {
                // This case was already handled above, but as a safeguard for $processCommand
                throw new \Exception("Unsupported database driver for Process command: {$dbDriver}");
            }

            $process = new Process($processCommand, null, $envVars);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error("Database export failed (mysqldump/pg_dump/sqlite3): " . $process->getErrorOutput());
                throw new ProcessFailedException($process);
            }
            
            // Write the successful output to the file on the 'local' disk
            Storage::disk('local')->put($fullStorageDiskPathWithFile, $process->getOutput());
            
            // Log success *after* file is written
            Log::info("Database export process successful for user: " . Auth::id() . ". File: {$filename} stored at {$fullStorageDiskPathWithFile}");

            $headers = [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            // Get the absolute path for response()->download() using the disk's path method
            $absolutePathToFile = Storage::disk('local')->path($fullStorageDiskPathWithFile);

            if (Storage::disk('local')->exists($fullStorageDiskPathWithFile)) {
                 return response()->download($absolutePathToFile, $filename, $headers)->deleteFileAfterSend(true);
            } else {
                Log::error("Database export failed: File not found for download at disk path {$fullStorageDiskPathWithFile} (absolute: {$absolutePathToFile})");
                return back()->with('error', 'Database export failed: Backup file could not be created or found for download.');
            }

        } catch (\Exception $e) {
            Log::error("Database export failed for user: " . Auth::id() . ". Error: " . $e->getMessage());
            return back()->with('error', 'Database export failed. Please check the logs. Error: ' . $e->getMessage());
        }
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'recipient_email' => 'required|email',
        ]);

        $recipientEmail = $request->input('recipient_email');

        try {
            \Illuminate\Support\Facades\Mail::raw('This is a test email from your application.', function ($message) use ($recipientEmail) {
                $message->to($recipientEmail)
                        ->subject('Test Email');
            });

            return response()->json(['message' => 'Test email sent successfully to ' . $recipientEmail]);
        } catch (\Exception $e) {
            Log::error('Test email failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send test email. Please check your mail configuration and logs. Error: ' . $e->getMessage()], 500);
        }
    }
}
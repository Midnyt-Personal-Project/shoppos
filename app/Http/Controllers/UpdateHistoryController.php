<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Event, Log};
use App\Models\UpdateHistory;
use Exception;
use Native\Desktop\Events\AutoUpdater\{CheckingForUpdate, Error, UpdateAvailable, UpdateDownloaded, UpdateNotAvailable};
use Native\Desktop\Facades\AutoUpdater;

class UpdateHistoryController extends Controller
{
    public function index()
    {
        $history = UpdateHistory::orderBy('created_at', 'desc')->take(20)->get();
        return view('about.updates', compact('history'));
    }

    public function check(Request $request)
    {
        //dd('Update check initiated');
        try {
            $currentVersion = config('nativephp.version', '1.0.4');

            // Register event listeners before triggering
            $this->registerUpdateEventListeners($currentVersion);

            // Trigger the NativePHP auto-updater
            AutoUpdater::checkForUpdates();

            // Record initiation (will be updated later by events)
            UpdateHistory::create([
                'version_checked' => $currentVersion,
                'status'          => 'checking',
                'message'         => 'Update check initiated.',
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Update check started. Results will appear in the history table.',
                'current_version' => $currentVersion,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to initiate update check', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            UpdateHistory::create([
                'version_checked' => config('nativephp.version', '1.0.4'),
                'status'          => 'error',
                'message'         => 'Failed to start update check: ' . $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
        
    }

    private function registerUpdateEventListeners($currentVersion)
    {
        // When the check begins
        Event::listen(CheckingForUpdate::class, function () use ($currentVersion) {
            Log::info('Auto-updater is checking for updates', ['current_version' => $currentVersion]);
        });

        log::info('Registered auto-updater event listeners', ['current_version' => $currentVersion]);

        // When an update is available
        Event::listen(UpdateAvailable::class, function ($event) use ($currentVersion) {
            $newVersion = $event->version ?? 'unknown';
            Log::info('Update available', [
                'current' => $currentVersion,
                'new'     => $newVersion,
            ]);
            UpdateHistory::create([
                'version_checked' => $currentVersion,
                'new_version'     => $newVersion,
                'status'          => 'update-available',
                'message'         => "New version {$newVersion} is available.",
            ]);
        });

        log::info('Auto-updater event listeners registered successfully', ['current_version' => $currentVersion]);

        // When no update is available
        Event::listen(UpdateNotAvailable::class, function () use ($currentVersion) {
            Log::info('No update available', ['current_version' => $currentVersion]);
            UpdateHistory::create([
                'version_checked' => $currentVersion,
                'status'          => 'up-to-date',
                'message'         => 'No updates found.',
            ]);
        });

        log::info('Auto-updater event listeners for update availability registered', ['current_version' => $currentVersion]);
        // When an update has been downloaded
        Event::listen(UpdateDownloaded::class, function () use ($currentVersion) {
            Log::info('Update downloaded, ready to install', ['current_version' => $currentVersion]);
            UpdateHistory::create([
                'version_checked' => $currentVersion,
                'status'          => 'downloaded',
                'message'         => 'Update downloaded. Restart the app to install.',
            ]);
        });

            log::info('Auto-updater event listeners for update downloaded registered', ['current_version' => $currentVersion]);

        // When an error occurs
        Event::listen(Error::class, function ($event) use ($currentVersion) {
            $errorMessage = $event->error ?? $event->message ?? 'Unknown error';
            Log::error('Update check error', [
                'current_version' => $currentVersion,
                'error'           => $errorMessage,
            ]);
            UpdateHistory::create([
                'version_checked' => $currentVersion,
                'status'          => 'error',
                'message'         => 'Update check error: ' . $errorMessage,
            ]);
        });

        log::info('Auto-updater event listeners for errors registered', ['current_version' => $currentVersion]);


       // dd('Update check initiated, event listeners done');
    }
}
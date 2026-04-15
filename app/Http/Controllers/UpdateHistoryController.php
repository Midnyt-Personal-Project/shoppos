<?php

namespace App\Http\Controllers;

use App\Models\UpdateHistory;
use Illuminate\Http\Request;
use Native\Desktop\Facades\AutoUpdater;
use Exception;

class UpdateHistoryController extends Controller
{
    public function index()
    {
        $history = UpdateHistory::orderBy('created_at', 'desc')->take(20)->get();
        return view('about.updates', compact('history'));
    }

    public function check(Request $request)
    {
        try {
            // Trigger the NativePHP auto‑updater
            AutoUpdater::checkForUpdates();

            // After check, you need to get the result.
            // AutoUpdater doesn't return a value directly; it fires events.
            // For simplicity, we assume the check is queued and we'll log later.
            // Better: listen to NativePHP events and store result.
            // Here we simulate a manual check result (replace with actual logic).

            $currentVersion = config('app.version', '1.0.3');
            $latestVersion = $this->getLatestVersionFromServer(); // implement this

            $status = ($latestVersion > $currentVersion) ? 'update-available' : 'up-to-date';

            UpdateHistory::create([
                'version_checked' => $currentVersion,
                'new_version'     => $status === 'update-available' ? $latestVersion : null,
                'status'          => $status,
                'message'         => $status === 'update-available' ? "New version {$latestVersion} is available." : "No updates found.",
            ]);

            return response()->json([
                'success' => true,
                'status'  => $status,
                'current' => $currentVersion,
                'latest'  => $latestVersion ?? null,
            ]);

        } catch (Exception $e) {
            UpdateHistory::create([
                'version_checked' => config('app.version', '1.0.3'),
                'status'          => 'error',
                'message'         => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getLatestVersionFromServer()
    {
        // Call your update server API to get the latest version.
        // Example:
        // $response = Http::get('https://updates.omnipos.com/latest');
        // return $response->json('version');

        // Placeholder:
        return '1.0.3';
    }
}

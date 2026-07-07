<?php

namespace App\Http\Controllers\API;

use App\Jobs\CreateBackup;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    private $disk;
    private $backupName;

    public function __construct()
    {
        $this->disk = Storage::disk('local');
        $this->backupName = config('backup.backup.name');
    }

    public function createBackup(Request $request)
    {
        $request->validate([
            'flag' => 'required|string|in:full,only-database,only-files',
        ]);
        try {
            $flag = $request->input('flag');
            // if ($flag === 'only-database') {
            //     Artisan::call('backup:run', ['--only-db' => true]);
            // }
            // if ($flag === 'only-files') {
            //     Artisan::call('backup:run', ['--only-files' => true]);
            // }
            // if ($flag === 'full') {
            //     Artisan::call('backup:run');
            // }
            // return response([
            //     'message' => 'Backup process completed.'
            // ], 202);
            CreateBackup::dispatch($flag);
            return response([
                'message' => 'Backup process started.'
            ], 202);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getBackups()
    {
        try {
            $files = $this->disk->files($this->backupName);
            $backups = collect($files)->map(function ($file) {
                return [
                    'name' => basename($file),
                    'size' => $this->disk->size($file),
                    'date' => $this->disk->lastModified($file),
                    'size_human' => $this->formatBytes($this->disk->size($file)),
                    'date_human' => date('Y-m-d H:i:s', $this->disk->lastModified($file)),
                ];
            })->sortByDesc('date')->values();

            return response([
                'backups' => $backups,
                'total' => $backups->count()
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function downloadBackup($filename)
    {
        try {
            $path = $this->backupName . '/' . $filename;
            if (!$this->disk->exists($path)) {
                return response([
                    'message' => 'Backup file not found.'
                ], 404);
            }
            return $this->disk->download($path);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteBackup($filename)
    {
        try {
            $path = $this->backupName . '/' . $filename;
            if (!$this->disk->exists($path)) {
                return response([
                    'message' => 'Backup file not found.'
                ], 404);
            }
            $this->disk->delete($path);
            return response([
                'message' => 'Backup deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

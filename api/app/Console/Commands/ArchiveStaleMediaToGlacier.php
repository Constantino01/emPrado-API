<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Media\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ArchiveStaleMediaToGlacier extends Command
{
    /**
     * The name and signature of the console command.
     * Use this in the terminal: php artisan media:archive-stale
     */
    protected $signature = 'media:archive-stale';

    /**
     * The console command description.
     */
    protected $description = 'Move media files untouched for over a year to AWS Glacier to save costs.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 365 days ago
        $thresholdDate = now()->subDays(365);
        
        $this->info("Looking for media not accessed since {$thresholdDate->toDateString()}...");

        // Use chunk to prevent RAM exhaustion on large datasets
        Media::where('glacier_status', 'standard')
            ->where('last_accessed_at', '<', $thresholdDate)
            ->chunk(100, function ($mediaFiles) {
                foreach ($mediaFiles as $media) {
                    $this->archiveToGlacier($media);
                }
            });

        $this->info("Archiving process completed.");
    }

    /**
     * Perform the S3 Copy operation to change the Storage Class.
     */
    private function archiveToGlacier(Media $media)
    {
        try {
            $client = Storage::disk($media->disk)->getClient();
            $bucket = config("filesystems.disks.{$media->disk}.bucket");

            // AWS S3 allows changing storage class by copying the object over itself
            $client->copyObject([
                'Bucket'            => $bucket,
                'Key'               => $media->path,
                'CopySource'        => "{$bucket}/{$media->path}",
                'StorageClass'      => 'GLACIER', // GLACIER or DEEP_ARCHIVE
                'MetadataDirective' => 'COPY'     // Keep original metadata
            ]);

            // Update DB status to prevent re-processing
            $media->update(['glacier_status' => 'archived']);
            
            $this->info("Archived: {$media->original_name} (ID: {$media->id})");
            
        } catch (Exception $e) {
            $this->error("Failed to archive Media ID {$media->id}: " . $e->getMessage());
            Log::error("Glacier Archiving Failed for Media ID {$media->id}", ['error' => $e->getMessage()]);
        }
    }
}
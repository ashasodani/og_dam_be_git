<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

class VideoStreamController extends Controller
{
    public function stream(Request $request, $filename)
    {
        try {
            // Get the S3 disk
            $disk = Storage::disk('s3');

            // Check if file exists
            if (!$disk->exists($filename)) {
                abort(404, 'Video not found');
            }
                    // Get file size and MIME type
        $fileSize = $disk->size($filename);
        $mimeType = $this->getMimeType($filename);
        
        // Get the range header
        $range = $request->header('Range');
        
        if (!$range) {
            // No range requested, stream entire file
            return $this->streamEntireFile($disk, $filename, $fileSize, $mimeType);
        }
        
        // Parse range header
        $ranges = $this->parseRangeHeader($range, $fileSize);
        
        if (empty($ranges)) {
            return response('Invalid range', 416)
                ->header('Content-Range', "bytes */{$fileSize}");
        }
        
        // Use first range (multi-range not commonly supported for video)
        $start = $ranges[0]['start'];
        $end = $ranges[0]['end'];
        $length = $end - $start + 1;
        
        // Stream the requested range
        return $this->streamRange($disk, $filename, $start, $end, $length, $fileSize, $mimeType);
        
    } catch (\Exception $e) {
        Log::error('Video streaming error: ' . $e->getMessage());
        abort(500, 'Error streaming video');
    }
}

private function streamEntireFile($disk, $filename, $fileSize, $mimeType)
{
    $stream = $disk->readStream($filename);
    
    return response()->stream(function() use ($stream) {
        while (!feof($stream)) {
            echo fread($stream, 8192); // Read in 8KB chunks
            flush();
        }
        fclose($stream);
    }, 200, [
        'Content-Type' => $mimeType,
        'Content-Length' => $fileSize,
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'no-cache',
    ]);
}

private function streamRange($disk, $filename, $start, $end, $length, $fileSize, $mimeType)
{
    // Get S3 client for range requests
    $s3Client = $disk->getAdapter()->getClient();
    $bucket = config('filesystems.disks.s3.bucket');
    
    // Use S3 GetObject with Range parameter
    $result = $s3Client->getObject([
        'Bucket' => $bucket,
        'Key' => $filename,
        'Range' => "bytes={$start}-{$end}"
    ]);
    
    $stream = $result['Body'];
    
    return response()->stream(function() use ($stream) {
        while (!$stream->eof()) {
            echo $stream->read(8192); // Read in 8KB chunks
            flush();
        }
    }, 206, [
        'Content-Type' => $mimeType,
        'Content-Length' => $length,
        'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'no-cache',
    ]);
}

private function parseRangeHeader($range, $fileSize)
{
    $ranges = [];
    
    if (preg_match('/bytes=(.+)/', $range, $matches)) {
        $rangeSpecs = explode(',', $matches[1]);
        
        foreach ($rangeSpecs as $rangeSpec) {
            $rangeSpec = trim($rangeSpec);
            
            if (strpos($rangeSpec, '-') !== false) {
                list($start, $end) = explode('-', $rangeSpec, 2);
                
                $start = $start === '' ? 0 : intval($start);
                $end = $end === '' ? $fileSize - 1 : intval($end);
                
                // Validate range
                if ($start >= 0 && $end < $fileSize && $start <= $end) {
                    $ranges[] = ['start' => $start, 'end' => $end];
                }
            }
        }
    }
    
    return $ranges;
}

private function getMimeType($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv',
        'flv' => 'video/x-flv',
        'mkv' => 'video/x-matroska',
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}
}
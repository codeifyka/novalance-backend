<?php

namespace App\Http\Controllers;
use App\Traits\files;
use Illuminate\Http\Request;

class FilesUploadController extends Controller
{
    use files;

    public function uploadServicesImages(Request $request)
    {
        $files = $request->allFiles();
        if (empty($files)) {
            return response()->json([
                "data" => [],
            ]);
        }

        $names = [];

        // Flatten any nested file structures (e.g. file1, file2 or files[])
        $flattenedFiles = [];
        array_walk_recursive($files, function ($item) use (&$flattenedFiles) {
            if ($item instanceof \Illuminate\Http\UploadedFile) {
                $flattenedFiles[] = $item;
            }
        });

        foreach ($flattenedFiles as $file) {
            if (!$file->isValid()) {
                return response()->json([
                    "error" => "File upload failed: " . $file->getErrorMessage() . " (" . $file->getClientOriginalName() . ")",
                ], 422);
            }

            $uniqueFilename = $file->store('services_images');
            $names[] = $uniqueFilename;
        }

        return response()->json([
            "data" => $names,
        ]);
    }

    public function uploadJobPostFiles(Request $request)
    {
        $files = $request->allFiles();
        $names = [];

        foreach($files as $file){
            $file_name = $this->files($file,'illustrative_files');
            array_push($names, $file_name);
        }

        return response()->json([
            "data" => $names,
        ]);
    }
}

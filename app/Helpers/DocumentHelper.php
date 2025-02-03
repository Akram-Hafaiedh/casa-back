<?php

namespace App\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class DocumentHelper
{
    /**
     * Save documents to a specified path and associate them with a model.
     *
     * @param array $documents Array of uploaded files.
     * @param string $path Storage path for the documents.
     * @param string $type Type of the document (e.g., 'insurance', 'accounting', 'tax').
     * @param Model $model The model to associate the documents with.
     * @param string $clientId The client ID associated with the documents.
     * @return void
     */
    public static function saveDocuments(array $documents, string $path, string $type, Model $model, int $clientId): void
    {
        if (empty($documents)) {
            return;
        }
        foreach ($documents as $document) {
            try {
                if (!$document->isValid()) {
                    throw new Exception("Invalid document upload.");
                }
                $documentPath = $document->store($path);
                $documentName = "{$type}_document_" . now()->format('Y-m-d_H-i-s');

                
                $model->documents()->create([
                    'name' => $documentName,
                    'path' => $documentPath,
                    'type' => $type,
                    'client_id' => $clientId,
                ]);
                Log::info("Document saved for model: {$model->id}, client: {$clientId}, path: {$documentPath}");
            } catch (Exception $e) {
                Log::error("Failed to save document: " . $e->getMessage());
                throw $e;
            }
        }
    }
    public static function formatSize(int $size): string {
        $sizeInBytes = $size;
        $sizeInKB = round($size / 1024, 2);
        $sizeInMB = round($size / 1024 / 1024, 2);
        $sizeInGB = round($size / 1024 / 1024 / 1024, 2);
    
        if ($sizeInGB > 1) {
            return $sizeInGB . ' GB';
        } elseif ($sizeInMB > 1) {
            return $sizeInMB . ' MB';
        } elseif ($sizeInKB > 1) {
            return $sizeInKB . ' KB';
        } else {
            return $sizeInBytes . ' bytes';
        }
    }
}

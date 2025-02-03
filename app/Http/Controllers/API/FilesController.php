<?php

namespace App\Http\Controllers\API;

use App\Helpers\DocumentHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    public function index(Request $request) 
    {

        $fullSize = 0;
        $totalFiles = 0;

        $userCategories = ['copy_id', 'contract', 'payrolls'];
        $userDocumentsArray = [];
        $userDirectories = Storage::directories('user_documents');
        if(!empty($userDirectories)){            
            foreach ($userDirectories as $userDirectory) {
                $userIdAndName = basename($userDirectory);
                [$userId, $userName] = explode('-', $userIdAndName, 2);
                foreach ($userCategories as $category) {
                    $categoryPath = "{$userDirectory}/{$category}";
                    if (Storage::exists($categoryPath)) {
                        $documents = Storage::allFiles($categoryPath);
                        foreach ($documents as $document) {
                            $size = Storage::size($document);
                            $fullSize += $size;
                            array_push($userDocumentsArray, [
                                'user_id' => $userId,
                                'user_name' => $userName,
                                'file' => asset(Storage::url($document)),
                                'file_name'=> basename($document),
                                'size' => $size,
                                'formattedSize' => DocumentHelper::formatSize($size),
                                'modified' => \DateTime::createFromFormat("U", Storage::lastModified($document))->format('Y-m-d H:m'),
                                'category' => $category
                            ]);
                            $totalFiles++;
                        }
                    }
                }
            }
        }

        $projectDirectories = Storage::directories('project_documents');
        $projectDocumentsArray = [];

        foreach ($projectDirectories as $projectDirectory) {
            $projectId = basename($projectDirectory);
            $documents = Storage::allFiles($projectDirectory);
            foreach ($documents as $document){
                $size = Storage::size($document);
                $fullSize += $size;
                array_push($projectDocumentsArray, [
                    'category' => 'project_details',
                    'projectId' => $projectId,
                    'file' => asset(Storage::url($document)),
                    'file_name'=> basename($document),
                    'size' => $size,
                    'formattedSize' => DocumentHelper::formatSize($size),
                    'modified' => \DateTime::createFromFormat("U", Storage::lastModified($document))->format('Y-m-d H:m'),
                ]);
                $totalFiles++;
            }
        }

        
        $customerCategories = ['taxes', 'accountings'];
        $customerDocumentsArray = [];
        $customerDirectories = Storage::directories('customer_documents');

        foreach ($customerDirectories as $customerDirectory) {
            $customerId = basename($customerDirectory);
            foreach ($customerCategories as $category) {
                $categoryPath = "{$customerDirectory}/{$category}";
                if (Storage::exists($categoryPath)) {
                    $documents = Storage::allFiles($categoryPath);
                    foreach ($documents as $document) {
                        $size = Storage::size($document);
                        $fullSize += $size;
                        array_push($customerDocumentsArray, [
                            'customer_id' => $customerId,
                            'file' => asset(Storage::url($document)),
                            'file_name'=> basename($document),
                            'category' => $category,
                            'file_name'=> basename($document),
                            'size' => $size,
                            'formattedSize' => DocumentHelper::formatSize($size),
                            'modified' => \DateTime::createFromFormat("U", Storage::lastModified($document))->format('Y-m-d H:m'),
                        ]);
                        $totalFiles++;

                    }
                }
            }
        }
    
        return response()->json([
            'data' => [
                'user_documents' => $userDocumentsArray,
                'project_documents' => $projectDocumentsArray,
                'customer_documents' => $customerDocumentsArray,
                'size' => $fullSize,
                'formattedSize' => DocumentHelper::formatSize($fullSize),
                'totalFiles' => $totalFiles,
            ],
            'status' => ['code' => 200, 'message' => 'Files retrieved successfully.']
        ]);
    }

    public function getCustomerDocuments($customerId) {
        $fullSize = 0;
        $customerDocumentsArray = [];
        $customerDirectory = "customer_documents/{$customerId}";
        $documents = Storage::allFiles($customerDirectory);
        foreach ($documents as $document) {
            $size = Storage::size($document);
            $fullSize += $size;
            array_push($customerDocumentsArray, [
                'customer_id' => $customerId,
                'file' => asset(Storage::url($document)),
                'size' => $size,
                'modified' => \DateTime::createFromFormat("U", Storage::lastModified($document))->format('Y-m-d H:m'),
            ]);
        }

        return response()->json([
            'data' => [
                'customer_documents' => $customerDocumentsArray,
                'size' => $fullSize,
            ],
            'status' => ['code' => 200, 'message' => 'Files retrieved successfully.']
        ]);
    }

    public function getUserDocuments($userId) {
        $categories = ['copy_id', 'contract', 'payrolls'];
        $fullSize = 0;
        $userDocumentsArray = [];
        $userDirectory = "user_documents/{$userId}";

        if (Storage::exists($userDirectory)) {
            foreach ($categories as $category) {
                $categoryPath = "{$userDirectory}/{$category}";
                if (Storage::exists($categoryPath)) {
                    $documents = Storage::allFiles($categoryPath);
                    foreach ($documents as $document) {
                        $size = Storage::size($document);
                        $fullSize += $size;
                        array_push($userDocumentsArray, [
                            'user_id' => $userId,
                            'file' => asset(Storage::url($document)),
                            'file_name'=> basename($document),
                            'size' => $size,
                            'modified' => \DateTime::createFromFormat("U", Storage::lastModified($document))->format('Y-m-d H:m'),
                            'category' => $category
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'data' => [
                'user_documents' => $userDocumentsArray,
                'size' => $fullSize,
            ],
            'status' => ['code' => 200, 'message' => 'Files retrieved successfully.']
        ]);
    }

    public function getProjectDocuments($projectId) {
        $fullSize = 0;
        $projectDocumentsArray = [];
        $projectDirectory = "project_documents/{$projectId}";
        $documents = Storage::allFiles($projectDirectory);
        foreach ($documents as $document) {
            $size = Storage::size($document);
            $fullSize += $size;
            array_push($projectDocumentsArray, [
                'project_id' => $projectId,
                'file' => asset(Storage::url($document)),
                'size' => $size,
                'modified' => \DateTime::createFromFormat("U", Storage::lastModified($document))->format('Y-m-d H:m'),
            ]);
        }

        return response()->json([
            'project_documents' => $projectDocumentsArray,
            'size' => $fullSize,
        ]);
    }

    public function getAuthenicatedUserDocuments() {
        $user = Auth::user();
        $fullSize = 0;
        $userDocumentsArray = [];
        $userDirectory = "user_documents/{$user->id}";
        $categories = ['copy_id', 'contract', 'payrolls'];

        foreach ($categories as $category) {
            $categoryPath = "{$userDirectory}/{$category}";
            if (Storage::exists($categoryPath)) {
                $documents = Storage::allFiles($categoryPath);
                foreach ($documents as $document) {
                    $size = Storage::size($document);
                    $fullSize += $size;
                    array_push($userDocumentsArray, [
                        'user_id' => $user->id,
                        'file' => asset(Storage::url($document)),
                        'size' => $size,
                        'modified' => \DateTime::createFromFormat("U", Storage::lastModified($document))->format('Y-m-d H:m'),
                        'category' => $category
                    ]);
                }
            }
        }

        return response()->json([
            'user_documents' => $userDocumentsArray,
            'size' => $fullSize,
        ]);
    }

    public function downloadCustomerDocuments($customerId, $documentName) {
        return Storage::download("customer_documents/{$customerId}/{$documentName}");
    }

    public function downloadUserFile($userId, $category, $documentName) {
        return Storage::download("user_documents/{$category}/{$documentName}");
    }

    public function downloadProjectFile($documentName) {
        return Storage::download("project_documents/{$documentName}");
    }

}


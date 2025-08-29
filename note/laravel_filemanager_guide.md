# Cấu hình Laravel FileManager với Laravel 11+ Inertia Vue 3 Sanctum

## Bước 1: Cài đặt Laravel FileManager

### Terminal
```bash
composer require unisharp/laravel-filemanager
php artisan vendor:publish --tag=lfm_config
php artisan vendor:publish --tag=lfm_public
```

## Bước 2: Cấu hình FileManager

### config/lfm.php
Cập nhật cấu hình chính (dòng ~15):
```php
'use_package_routes' => true,

// Dòng ~25: URL prefix
'url_prefix' => env('LFM_URL_PREFIX', 'filemanager'),

// Dòng ~35: Middlewares
'middlewares' => ['web', 'auth:sanctum', 'permission:manage files'],

// Dòng ~50: Disk configuration
'disk' => env('LFM_DISK', 'public'),

// Dòng ~60: Rename file/folder
'rename_file' => true,
'rename_duplicates' => true,

// Dòng ~80: File categories
'categories' => [
    'file' => [
        'folder_name'  => 'files',
        'startup_view' => 'grid',
        'max_size'     => 50000, // 50MB
        'thumb_folder_name' => 'thumbs',
        'thumb' => false
    ],
    
    'image' => [
        'folder_name'  => 'photos',
        'startup_view' => 'grid',
        'max_size'     => 50000, // 50MB
        'thumb_folder_name' => 'thumbs',
        'thumb' => true
    ]
],

// Dòng ~120: Valid image extensions
'images' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],

// Dòng ~130: Valid file extensions
'file_type_array' => [
    'pdf'  => 'Adobe Acrobat',
    'doc'  => 'Microsoft Word',
    'docx' => 'Microsoft Word',
    'xls'  => 'Microsoft Excel',
    'xlsx' => 'Microsoft Excel',
    'zip'  => 'Archive',
    'rar'  => 'Archive',
    'mp4'  => 'Video',
    'mov'  => 'Video',
    'avi'  => 'Video',
],

// Dòng ~160: Upload validation
'upload_validation' => [
    'image' => 'required|image|mimes:jpeg,jpg,png,gif,svg,webp|max:' . (50 * 1024),
    'file'  => 'required|mimes:pdf,doc,docx,xls,xlsx,zip,rar,mp4,mov,avi|max:' . (50 * 1024),
],

// Dòng ~180: Thumb settings
'thumb_img_width'  => 200,
'thumb_img_height' => 200,
```

## Bước 3: Cấu hình Environment

### .env
Thêm cấu hình FileManager:
```env
# FileManager settings
LFM_URL_PREFIX=filemanager
LFM_DISK=public
LFM_BASE_DIRECTORY=

# Image quality
LFM_SHOULD_CREATE_THUMBNAILS=true
LFM_THUMB_IMG_WIDTH=200
LFM_THUMB_IMG_HEIGHT=200
```

## Bước 4: Thêm Permission cho FileManager

### database/seeders/RolePermissionSeeder.php
Cập nhật permissions (dòng ~25):
```php
$permissions = [
    // User permissions
    'view users',
    'create users', 
    'edit users',
    'delete users',
    
    // Role permissions
    'view roles',
    'create roles',
    'edit roles',
    'delete roles',
    
    // Permission permissions
    'view permissions',
    'assign permissions',
    
    // Dashboard permissions
    'view dashboard',
    'view admin dashboard',
    
    // FileManager permissions - THÊM MỚI
    'manage files',
    'upload files',
    'delete files',
    'rename files',
    'create folders',
];
```

Cập nhật role assignments (dòng ~60):
```php
// Admin permissions - thêm file management
$adminPermissions = [
    'view users', 'create users', 'edit users',
    'view roles', 'view permissions',
    'view dashboard', 'view admin dashboard',
    'manage files', 'upload files', 'delete files', // THÊM MỚI
    'rename files', 'create folders',
];
```

## Bước 5: Tạo FileManager Controller

### app/Http/Controllers/FileManagerController.php
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class FileManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'permission:manage files']);
    }

    // Hiển thị FileManager trong Inertia
    public function index()
    {
        return Inertia::render('FileManager/Index', [
            'config' => [
                'baseUrl' => config('app.url') . '/' . config('lfm.url_prefix'),
                'disk' => config('lfm.disk'),
                'categories' => config('lfm.categories'),
                'maxFileSize' => config('lfm.categories.file.max_size'),
            ]
        ]);
    }

    // API endpoint để lấy danh sách files
    public function files(Request $request)
    {
        $type = $request->get('type', 'image');
        $path = $request->get('path', '');
        
        $disk = Storage::disk(config('lfm.disk'));
        $basePath = config("lfm.categories.{$type}.folder_name") . '/' . $path;
        
        $files = [];
        $folders = [];
        
        if ($disk->exists($basePath)) {
            $items = $disk->listContents($basePath, false);
            
            foreach ($items as $item) {
                if ($item['type'] === 'dir') {
                    $folders[] = [
                        'name' => basename($item['path']),
                        'path' => str_replace(config("lfm.categories.{$type}.folder_name") . '/', '', $item['path']),
                        'type' => 'folder',
                        'modified' => $item['lastModified'] ?? null,
                    ];
                } else {
                    $files[] = [
                        'name' => basename($item['path']),
                        'path' => $item['path'],
                        'url' => $disk->url($item['path']),
                        'size' => $item['fileSize'] ?? 0,
                        'type' => 'file',
                        'extension' => pathinfo($item['path'], PATHINFO_EXTENSION),
                        'modified' => $item['lastModified'] ?? null,
                        'is_image' => in_array(strtolower(pathinfo($item['path'], PATHINFO_EXTENSION)), 
                                     config('lfm.images')),
                    ];
                }
            }
        }
        
        return response()->json([
            'folders' => $folders,
            'files' => $files,
            'current_path' => $path,
        ]);
    }

    // Upload file
    public function upload(Request $request)
    {
        $type = $request->get('type', 'image');
        $path = $request->get('path', '');
        
        $request->validate([
            'file' => config("lfm.upload_validation.{$type}"),
        ]);
        
        $file = $request->file('file');
        $basePath = config("lfm.categories.{$type}.folder_name") . '/' . $path;
        
        // Check if file exists and rename if needed
        $filename = $file->getClientOriginalName();
        if (config('lfm.rename_duplicates')) {
            $counter = 1;
            $originalName = pathinfo($filename, PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            
            while (Storage::disk(config('lfm.disk'))->exists($basePath . '/' . $filename)) {
                $filename = $originalName . '_' . $counter . '.' . $extension;
                $counter++;
            }
        }
        
        $filePath = $file->storeAs($basePath, $filename, config('lfm.disk'));
        
        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'file' => [
                'name' => $filename,
                'path' => $filePath,
                'url' => Storage::disk(config('lfm.disk'))->url($filePath),
                'size' => $file->getSize(),
            ]
        ]);
    }

    // Tạo folder
    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\-_\s]+$/',
            'type' => 'required|in:image,file',
            'path' => 'string',
        ]);
        
        $type = $request->get('type');
        $path = $request->get('path', '');
        $folderName = $request->get('name');
        
        $basePath = config("lfm.categories.{$type}.folder_name") . '/' . $path;
        $fullPath = $basePath . '/' . $folderName;
        
        $disk = Storage::disk(config('lfm.disk'));
        
        if ($disk->exists($fullPath)) {
            return response()->json(['error' => 'Folder already exists'], 400);
        }
        
        $disk->makeDirectory($fullPath);
        
        return response()->json([
            'success' => true,
            'message' => 'Folder created successfully',
            'folder' => [
                'name' => $folderName,
                'path' => trim($path . '/' . $folderName, '/'),
            ]
        ]);
    }

    // Xóa file/folder
    public function delete(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.path' => 'required|string',
            'items.*.type' => 'required|in:file,folder',
        ]);
        
        $disk = Storage::disk(config('lfm.disk'));
        $deleted = [];
        $errors = [];
        
        foreach ($request->get('items') as $item) {
            try {
                if ($item['type'] === 'folder') {
                    $disk->deleteDirectory($item['path']);
                } else {
                    $disk->delete($item['path']);
                }
                $deleted[] = $item['path'];
            } catch (\Exception $e) {
                $errors[] = [
                    'path' => $item['path'],
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return response()->json([
            'success' => count($errors) === 0,
            'message' => count($deleted) . ' items deleted successfully',
            'deleted' => $deleted,
            'errors' => $errors,
        ]);
    }

    // Rename file/folder
    public function rename(Request $request)
    {
        $request->validate([
            'old_path' => 'required|string',
            'new_name' => 'required|string|max:255',
            'type' => 'required|in:file,folder',
        ]);
        
        $disk = Storage::disk(config('lfm.disk'));
        $oldPath = $request->get('old_path');
        $newName = $request->get('new_name');
        $type = $request->get('type');
        
        $directory = dirname($oldPath);
        $newPath = $directory . '/' . $newName;
        
        if ($disk->exists($newPath)) {
            return response()->json(['error' => 'File/folder with this name already exists'], 400);
        }
        
        try {
            $disk->move($oldPath, $newPath);
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' renamed successfully',
                'old_path' => $oldPath,
                'new_path' => $newPath,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

## Bước 6: API Controller cho FileManager

### app/Http/Controllers/Api/FileManagerController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'permission:manage files,api']);
    }

    // Lấy danh sách files cho API
    public function index(Request $request)
    {
        $type = $request->get('type', 'image');
        $path = $request->get('path', '');
        
        $disk = Storage::disk(config('lfm.disk'));
        $basePath = config("lfm.categories.{$type}.folder_name") . '/' . $path;
        
        $files = [];
        $folders = [];
        
        if ($disk->exists($basePath)) {
            $items = $disk->listContents($basePath, false);
            
            foreach ($items as $item) {
                if ($item['type'] === 'dir') {
                    $folders[] = [
                        'name' => basename($item['path']),
                        'path' => str_replace(config("lfm.categories.{$type}.folder_name") . '/', '', $item['path']),
                        'type' => 'folder',
                        'modified' => isset($item['lastModified']) ? date('Y-m-d H:i:s', $item['lastModified']) : null,
                    ];
                } else {
                    $files[] = [
                        'name' => basename($item['path']),
                        'path' => $item['path'],
                        'url' => $disk->url($item['path']),
                        'size' => $this->formatBytes($item['fileSize'] ?? 0),
                        'size_bytes' => $item['fileSize'] ?? 0,
                        'type' => 'file',
                        'extension' => strtolower(pathinfo($item['path'], PATHINFO_EXTENSION)),
                        'modified' => isset($item['lastModified']) ? date('Y-m-d H:i:s', $item['lastModified']) : null,
                        'is_image' => in_array(strtolower(pathinfo($item['path'], PATHINFO_EXTENSION)), 
                                     config('lfm.images')),
                        'thumbnail' => $this->getThumbnailUrl($item['path'], $type),
                    ];
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'folders' => $folders,
                'files' => $files,
                'current_path' => $path,
                'breadcrumbs' => $this->getBreadcrumbs($path),
            ],
            'meta' => [
                'total_files' => count($files),
                'total_folders' => count($folders),
                'disk_usage' => $this->getDiskUsage(),
            ]
        ]);
    }

    // Upload multiple files
    public function upload(Request $request)
    {
        $type = $request->get('type', 'image');
        $path = $request->get('path', '');
        
        $request->validate([
            'files' => 'required|array',
            'files.*' => config("lfm.upload_validation.{$type}"),
        ]);
        
        $basePath = config("lfm.categories.{$type}.folder_name") . '/' . $path;
        $uploaded = [];
        $errors = [];
        
        foreach ($request->file('files') as $file) {
            try {
                $filename = $file->getClientOriginalName();
                
                // Rename duplicates if enabled
                if (config('lfm.rename_duplicates')) {
                    $counter = 1;
                    $originalName = pathinfo($filename, PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    
                    while (Storage::disk(config('lfm.disk'))->exists($basePath . '/' . $filename)) {
                        $filename = $originalName . '_' . $counter . '.' . $extension;
                        $counter++;
                    }
                }
                
                $filePath = $file->storeAs($basePath, $filename, config('lfm.disk'));
                
                $uploaded[] = [
                    'name' => $filename,
                    'path' => $filePath,
                    'url' => Storage::disk(config('lfm.disk'))->url($filePath),
                    'size' => $this->formatBytes($file->getSize()),
                    'size_bytes' => $file->getSize(),
                ];
                
            } catch (\Exception $e) {
                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return response()->json([
            'success' => count($errors) === 0,
            'message' => count($uploaded) . ' files uploaded successfully',
            'data' => [
                'uploaded' => $uploaded,
                'errors' => $errors,
            ]
        ]);
    }

    // Tìm kiếm files
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'required|in:image,file',
        ]);
        
        $query = $request->get('query');
        $type = $request->get('type');
        $disk = Storage::disk(config('lfm.disk'));
        $basePath = config("lfm.categories.{$type}.folder_name");
        
        $results = [];
        
        if ($disk->exists($basePath)) {
            $allFiles = $disk->allFiles($basePath);
            
            foreach ($allFiles as $filePath) {
                $fileName = basename($filePath);
                
                if (stripos($fileName, $query) !== false) {
                    $results[] = [
                        'name' => $fileName,
                        'path' => $filePath,
                        'url' => $disk->url($filePath),
                        'size' => $this->formatBytes($disk->size($filePath)),
                        'folder' => dirname(str_replace($basePath . '/', '', $filePath)),
                        'is_image' => in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), 
                                     config('lfm.images')),
                    ];
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'query' => $query,
                'total' => count($results),
            ]
        ]);
    }

    // Helper methods
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function getThumbnailUrl($filePath, $type)
    {
        if ($type === 'image') {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($extension, config('lfm.images'))) {
                return Storage::disk(config('lfm.disk'))->url($filePath);
            }
        }
        
        return null;
    }

    private function getBreadcrumbs($path)
    {
        $breadcrumbs = [['name' => 'Root', 'path' => '']];
        
        if (!empty($path)) {
            $segments = explode('/', $path);
            $currentPath = '';
            
            foreach ($segments as $segment) {
                $currentPath .= ($currentPath ? '/' : '') . $segment;
                $breadcrumbs[] = [
                    'name' => $segment,
                    'path' => $currentPath,
                ];
            }
        }
        
        return $breadcrumbs;
    }

    private function getDiskUsage()
    {
        $disk = Storage::disk(config('lfm.disk'));
        $totalSize = 0;
        
        try {
            $allFiles = $disk->allFiles();
            
            foreach ($allFiles as $file) {
                $totalSize += $disk->size($file);
            }
        } catch (\Exception $e) {
            // Handle error silently
        }
        
        return $this->formatBytes($totalSize);
    }
}
```

## Bước 7: Cập nhật Routes

### routes/web.php
Thêm FileManager routes (dòng ~30):
```php
// FileManager routes
Route::middleware(['auth:sanctum', 'permission:manage files'])->group(function () {
    Route::get('/filemanager', [FileManagerController::class, 'index'])->name('filemanager.index');
    Route::get('/filemanager/files', [FileManagerController::class, 'files'])->name('filemanager.files');
    Route::post('/filemanager/upload', [FileManagerController::class, 'upload'])->name('filemanager.upload');
    Route::post('/filemanager/folder', [FileManagerController::class, 'createFolder'])->name('filemanager.folder');
    Route::delete('/filemanager/delete', [FileManagerController::class, 'delete'])->name('filemanager.delete');
    Route::patch('/filemanager/rename', [FileManagerController::class, 'rename'])->name('filemanager.rename');
});

// Integrate với standard FileManager routes
Route::group(['prefix' => 'filemanager', 'middleware' => ['web', 'auth:sanctum', 'permission:manage files']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});
```

### routes/api.php
Thêm API routes (dòng ~25):
```php
// FileManager API routes
Route::middleware(['auth:sanctum', 'permission:manage files,api'])->prefix('filemanager')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\FileManagerController::class, 'index']);
    Route::post('/upload', [\App\Http\Controllers\Api\FileManagerController::class, 'upload']);
    Route::get('/search', [\App\Http\Controllers\Api\FileManagerController::class, 'search']);
    
    Route::post('/folder', [FileManagerController::class, 'createFolder']);
    Route::delete('/delete', [FileManagerController::class, 'delete']);
    Route::patch('/rename', [FileManagerController::class, 'rename']);
});
```

## Bước 8: Cấu hình TinyMCE Integration (Optional)

### resources/js/Components/TinyMCEEditor.vue
Tạo component TinyMCE:
```vue
<template>
  <div>
    <textarea :id="editorId" v-model="content"></textarea>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  modelValue: String,
  height: {
    type: Number,
    default: 400
  }
})

const emit = defineEmits(['update:modelValue'])

const editorId = ref(`tinymce-${Date.now()}`)
const content = ref(props.modelValue || '')

watch(content, (newContent) => {
  emit('update:modelValue', newContent)
})

onMounted(() => {
  // TinyMCE initialization code would go here
  // với FileManager integration
})
</script>
```

## Bước 9: Middleware cho File Access Control

### app/Http/Middleware/FileAccessMiddleware.php
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if user has permission to access files
        if (!Auth::guard('sanctum')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('sanctum')->user();
        
        // Check file access permissions based on request path
        $path = $request->path();
        
        if (str_contains($path, 'photos') && !$user->can('manage files')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        if (str_contains($path, 'files') && !$user->can('manage files')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        return $next($request);
    }
}
```

## Bước 10: Cập nhật Inertia Middleware

### app/Http/Middleware/HandleInertiaRequests.php
Thêm FileManager permissions (dòng ~45):
```php
'can' => $user ? [
    'viewUsers' => $user->can('view users'),
    'createUsers' => $user->can('create users'),
    'editUsers' => $user->can('edit users'),
    'deleteUsers' => $user->can('delete users'),
    'viewAdminDashboard' => $user->can('view admin dashboard'),
    
    // FileManager permissions
    'manageFiles' => $user->can('manage files'),
    'uploadFiles' => $user->can('upload files'),
    'deleteFiles' => $user->can('delete files'),
    'renameFiles' => $user->can('rename files'),
    'createFolders' => $user->can('create folders'),
] : [],
```

## Bước 11: Helper Service cho File Operations

### app/Services/FileManagerService.php
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileManagerService
{
    protected $disk;
    
    public function __construct()
    {
        $this->disk = Storage::disk(config('lfm.disk'));
    }

    public function getFileInfo($path): ?array
    {
        if (!$this->disk->exists($path)) {
            return null;
        }

        $size = $this->disk->size($path);
        $lastModified = $this->disk->lastModified($path);
        
        return [
            'name' => basename($path),
            'path' => $path,
            'url' => $this->disk->url($path),
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'last_modified' => date('Y-m-d H:i:s', $lastModified),
            'extension' => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            'is_image' => $this->isImage($path),
        ];
    }

    public function copyFile($sourcePath, $destinationPath): bool
    {
        try {
            return $this->disk->copy($sourcePath, $destinationPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function moveFile($sourcePath, $destinationPath): bool
    {
        try {
            return $this->disk->move($sourcePath, $destinationPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isImage($path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, config('lfm.images'));
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function getFolderSize($path): int
    {
        $totalSize = 0;
        $files = $this->disk->allFiles($path);
        
        foreach ($files as $file) {
            $totalSize += $this->disk->size($file);
        }
        
        return $totalSize;
    }
}
```

## Bước 12: Chạy Migration và Setup

### Terminal
```bash
# Chạy migrations
php artisan migrate

# Seed permissions
php artisan db:seed --class=RolePermissionSeeder

# Tạo storage directories
php artisan storage:link

# Clear cache
php artisan config:cache
php artisan route:cache

# Tạo thư mục FileManager
mkdir -p storage/app/public/{photos,files}
mkdir -p storage/app/public/photos/thumbs
mkdir -p storage/app/public/files/thumbs
```

## Bước 13: Test FileManager

### Web Access:
- Truy cập `/filemanager` để mở FileManager interface
- Test upload, create folder, rename, delete operations
- Kiểm tra permissions với các role khác nhau

### API Access:
- GET `/api/filemanager?type=image&path=` để lấy danh sách files
- POST `/api/filemanager/upload` để upload files
- GET `/api/filemanager/search?query=test&type=image` để tìm kiếm

## Lưu ý quan trọng:
1. FileManager được bảo vệ bởi Sanctum authentication
2. Permissions được kiểm tra cho cả web và API routes
3. File uploads có validation và size limits
4. Tự động rename duplicates nếu được cấu hình
5. Support cả image và file categories
6. API responses bao gồm metadata và breadcrumbs
7. Integration sẵn sàng với TinyMCE và CKEditor
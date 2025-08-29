# Cấu hình Laravel Permission với Laravel 11+ Inertia Vue 3 Sanctum

## Bước 1: Cài đặt Laravel Permission

### Terminal
```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

## Bước 2: Cấu hình User Model

### app/Models/User.php
Thêm trait và relationships (dòng ~12):
```php
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
    ];

    // Dòng ~40: Thêm guard name cho API
    protected $guard_name = ['web', 'api'];
}
```

## Bước 3: Cấu hình Permission Config

### config/permission.php
Cập nhật guards (dòng ~25):
```php
'guards' => [
    'web',
    'api', // Thêm api guard
],

// Dòng ~85: Cache configuration
'cache' => [
    'expiration_time' => \DateInterval::createFromDateString('24 hours'),
    'key' => 'spatie.permission.cache',
    'store' => 'default',
],
```

## Bước 4: Tạo Seeder cho Roles và Permissions

### database/seeders/RolePermissionSeeder.php
Tạo seeder mới:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Tạo permissions
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
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            Permission::create([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }

        // Tạo roles và assign permissions
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminApiRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'api']);
        
        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $adminApiRole = Role::create(['name' => 'Admin', 'guard_name' => 'api']);
        
        $userRole = Role::create(['name' => 'User', 'guard_name' => 'web']);
        $userApiRole = Role::create(['name' => 'User', 'guard_name' => 'api']);

        // Super Admin có tất cả permissions
        $superAdminRole->givePermissionTo(Permission::where('guard_name', 'web')->get());
        $superAdminApiRole->givePermissionTo(Permission::where('guard_name', 'api')->get());

        // Admin permissions
        $adminPermissions = [
            'view users', 'create users', 'edit users',
            'view roles', 'view permissions',
            'view dashboard', 'view admin dashboard'
        ];
        $adminRole->givePermissionTo(Permission::whereIn('name', $adminPermissions)->where('guard_name', 'web')->get());
        $adminApiRole->givePermissionTo(Permission::whereIn('name', $adminPermissions)->where('guard_name', 'api')->get());

        // User permissions
        $userPermissions = ['view dashboard'];
        $userRole->givePermissionTo(Permission::whereIn('name', $userPermissions)->where('guard_name', 'web')->get());
        $userApiRole->givePermissionTo(Permission::whereIn('name', $userPermissions)->where('guard_name', 'api')->get());

        // Tạo Super Admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
        ]);
        $superAdmin->assignRole('Super Admin');
    }
}
```

## Bước 5: Cập nhật DatabaseSeeder

### database/seeders/DatabaseSeeder.php
Thêm seeder (dòng ~15):
```php
public function run(): void
{
    $this->call([
        RolePermissionSeeder::class,
    ]);
}
```

## Bước 6: Tạo Permission Middleware

### app/Http/Middleware/PermissionMiddleware.php
Tạo middleware mới:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission, $guard = null)
    {
        $authGuard = Auth::guard($guard);
        
        if ($authGuard->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $permissions = is_array($permission)
            ? $permission
            : explode('|', $permission);

        foreach ($permissions as $permission) {
            if ($authGuard->user()->can($permission)) {
                return $next($request);
            }
        }

        throw UnauthorizedException::forPermissions($permissions);
    }
}
```

### app/Http/Middleware/RoleMiddleware.php
Tạo middleware cho role:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role, $guard = null)
    {
        $authGuard = Auth::guard($guard);
        
        if ($authGuard->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $roles = is_array($role)
            ? $role
            : explode('|', $role);

        if (! $authGuard->user()->hasAnyRole($roles)) {
            throw UnauthorizedException::forRoles($roles);
        }

        return $next($request);
    }
}
```

## Bước 7: Đăng ký Middleware

### bootstrap/app.php (Laravel 11)
Thêm middleware aliases (dòng ~20):
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
        \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
    ]);
    
    // Đăng ký middleware aliases
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
    ]);
})
```

## Bước 8: Cập nhật Controllers

### app/Http/Controllers/Auth/LoginController.php
Cập nhật method store để load roles (dòng ~30):
```php
public function store(Request $request)
{
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    if (Auth::attempt($request->only('username', 'password'), $request->boolean('remember'))) {
        $request->session()->regenerate();
        
        // Load user với roles và permissions
        $user = Auth::user()->load('roles.permissions');
        
        return redirect()->intended(route('dashboard'));
    }

    throw ValidationException::withMessages([
        'username' => __('auth.failed'),
    ]);
}
```

### app/Http/Controllers/Api/AuthController.php
Cập nhật API login (dòng ~25):
```php
public function login(Request $request)
{
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = User::where('username', $request->username)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'username' => ['The provided credentials are incorrect.'],
        ]);
    }

    // Load roles và permissions
    $user->load('roles.permissions');
    
    // Tạo token với abilities dựa trên permissions
    $abilities = $user->getAllPermissions()->pluck('name')->toArray();
    $token = $user->createToken('api-token', $abilities)->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()->pluck('name'),
    ]);
}

// Cập nhật user method
public function user(Request $request)
{
    $user = $request->user()->load('roles.permissions');
    
    return response()->json([
        'user' => $user,
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()->pluck('name'),
    ]);
}
```

## Bước 9: Tạo Controllers cho User Management

### app/Http/Controllers/UserController.php
```php
<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view users')->only(['index', 'show']);
        $this->middleware('permission:create users')->only(['create', 'store']);
        $this->middleware('permission:edit users')->only(['edit', 'update']);
        $this->middleware('permission:delete users')->only(['destroy']);
    }

    public function index()
    {
        $users = User::with('roles')->paginate(10);
        
        return Inertia::render('Users/Index', [
            'users' => $users,
            'can' => [
                'create_user' => auth()->user()->can('create users'),
                'edit_user' => auth()->user()->can('edit users'),
                'delete_user' => auth()->user()->can('delete users'),
            ]
        ]);
    }

    public function create()
    {
        $roles = Role::where('guard_name', 'web')->get();
        
        return Inertia::render('Users/Create', [
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'array|exists:roles,name'
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        if ($request->roles) {
            $user->assignRole($request->roles);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::where('guard_name', 'web')->get();
        $userRoles = $user->getRoleNames()->toArray();
        
        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $userRoles,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'array|exists:roles,name'
        ]);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
        ]);

        $user->syncRoles($request->roles ?? []);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Không cho phép xóa chính mình
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
```

## Bước 10: API Controllers cho Permission

### app/Http/Controllers/Api/UserController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:view users,api')->only(['index', 'show']);
        $this->middleware('permission:create users,api')->only(['store']);
        $this->middleware('permission:edit users,api')->only(['update']);
        $this->middleware('permission:delete users,api')->only(['destroy']);
    }

    public function index()
    {
        $users = User::with('roles')->get();
        
        return response()->json([
            'users' => $users,
            'meta' => [
                'can_create' => auth()->user()->can('create users'),
                'can_edit' => auth()->user()->can('edit users'),
                'can_delete' => auth()->user()->can('delete users'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'roles' => 'array|exists:roles,name'
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        if ($request->roles) {
            $user->assignRole($request->roles);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('roles')
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load('roles.permissions')
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'roles' => 'array|exists:roles,name'
        ]);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
        ]);

        $user->syncRoles($request->roles ?? []);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles')
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'You cannot delete yourself'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
```

## Bước 11: Cập nhật Routes

### routes/web.php
Thêm protected routes (dòng ~25):
```php
// Routes cần authentication và permissions
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard')->middleware('permission:view dashboard');
    
    // Admin Dashboard
    Route::get('/admin/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard')->middleware('permission:view admin dashboard');
    
    // User Management Routes
    Route::resource('users', UserController::class)
        ->middleware('permission:view users');
    
    // Routes chỉ cho Super Admin
    Route::middleware(['role:Super Admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/roles', function () {
            return Inertia::render('Admin/Roles/Index');
        })->name('roles.index');
    });
    
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
```

### routes/api.php
Cập nhật API routes (dòng ~15):
```php
// API Routes cần authentication và permissions
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User Management API
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);
    
    // Admin only routes
    Route::middleware(['role:Super Admin|Admin,api'])->prefix('admin')->group(function () {
        Route::get('/roles', function () {
            return response()->json(\Spatie\Permission\Models\Role::with('permissions')->get());
        });
        
        Route::get('/permissions', function () {
            return response()->json(\Spatie\Permission\Models\Permission::all());
        });
    });
    
    // Protected data endpoint với permission
    Route::get('/protected-data', function () {
        return response()->json(['message' => 'This is protected data']);
    })->middleware('permission:view dashboard,api');
});
```

## Bước 12: Cập nhật Inertia Middleware

### app/Http/Middleware/HandleInertiaRequests.php
Thêm permissions vào shared data (dòng ~30):
```php
public function share(Request $request): array
{
    $user = $request->user();
    
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ] : null,
        ],
        'can' => $user ? [
            'viewUsers' => $user->can('view users'),
            'createUsers' => $user->can('create users'),
            'editUsers' => $user->can('edit users'),
            'deleteUsers' => $user->can('delete users'),
            'viewAdminDashboard' => $user->can('view admin dashboard'),
        ] : [],
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error' => fn () => $request->session()->get('error'),
        ],
    ]);
}
```

## Bước 13: Exception Handler

### app/Exceptions/Handler.php
Thêm handling cho UnauthorizedException (dòng ~25):
```php
use Spatie\Permission\Exceptions\UnauthorizedException;
use Illuminate\Http\Request;

public function register(): void
{
    $this->renderable(function (UnauthorizedException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $e->getMessage()
            ], 403);
        }
        
        return redirect()->route('dashboard')
            ->with('error', 'You do not have permission to perform this action.');
    });
}
```

## Bước 14: Chạy Migration và Seed

### Terminal
```bash
# Chạy migration
php artisan migrate

# Chạy seeder
php artisan db:seed --class=RolePermissionSeeder

# Hoặc chạy tất cả seeders
php artisan db:seed

# Clear cache
php artisan cache:clear
php artisan config:cache
```

## Bước 15: Helper Methods (Optional)

### app/Http/Controllers/Controller.php
Thêm helper methods (dòng ~15):
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    // Helper method để check permission
    protected function checkPermission($permission, $guard = null)
    {
        if (!auth()->guard($guard)->user()->can($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
    
    // Helper method để check role
    protected function checkRole($role, $guard = null)
    {
        if (!auth()->guard($guard)->user()->hasRole($role)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
```

## Test Permission System

### Web Testing:
1. Login với superadmin/password
2. Truy cập `/users` để xem danh sách users
3. Truy cập `/admin/dashboard` để test admin permissions

### API Testing:
1. POST `/api/login` với superadmin credentials
2. Sử dụng token để truy cập `/api/users`
3. Test các endpoints khác với token

## Lưu ý quan trọng:
1. Permissions được tạo cho cả `web` và `api` guards
2. API tokens được tạo với abilities dựa trên user permissions
3. Middleware tự động check guard phù hợp (web/api)
4. Cache permissions để tối ưu hiệu suất
5. Exception handling cho Unauthorized access
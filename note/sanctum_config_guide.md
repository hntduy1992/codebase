# Cấu hình Laravel Sanctum cho Laravel 11+ Inertia Vue 3

## Bước 1: Cài đặt Sanctum

### Terminal
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

## Bước 2: Cấu hình Kernel Middleware

### app/Http/Kernel.php
Thêm vào `api` middleware group (dòng ~43):
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

## Bước 3: Cấu hình Sanctum

### config/sanctum.php
Cập nhật các dòng quan trọng:
```php
// Dòng 17: Domains được phép sử dụng session
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    Sanctum::currentApplicationUrlWithPort(),
    env('FRONTEND_URL') ? ','.parse_url(env('FRONTEND_URL'), PHP_URL_HOST) : ''
))),

// Dòng 46: Guard cho Sanctum
'guard' => ['web'],

// Dòng 56: Token expiration (null = không hết hạn)
'expiration' => null,
```

## Bước 4: Cấu hình CORS

### config/cors.php
```php
// Dòng 19: Paths áp dụng CORS
'paths' => ['api/*', 'sanctum/csrf-cookie'],

// Dòng 29: Cho phép credentials
'supports_credentials' => true,
```

## Bước 5: Cấu hình Environment

### .env
Thêm các dòng sau:
```env
# Dòng cuối file
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
FRONTEND_URL=http://localhost:3000
SESSION_DOMAIN=localhost
SESSION_DRIVER=cookie
```

## Bước 6: Cập nhật User Model

### app/Models/User.php
Thêm trait (dòng ~12):
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    // Dòng ~20: Thêm username vào fillable
    protected $fillable = [
        'name',
        'email',
        'username', // Thêm dòng này
        'password',
    ];
}
```

## Bước 7: Migration User Table

### database/migrations/xxxx_create_users_table.php
Thêm cột username (dòng ~18):
```php
public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('username')->unique(); // Thêm dòng này
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
}
```

## Bước 8: Cấu hình Auth Guard

### config/auth.php
Cập nhật guards (dòng ~40):
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    
    'api' => [
        'driver' => 'sanctum', // Đổi từ 'token' thành 'sanctum'
        'provider' => 'users',
        'hash' => false,
    ],
],
```

## Bước 9: Authentication Controller

### app/Http/Controllers/Auth/LoginController.php
Tạo controller mới:
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    // Hiển thị form login
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    // Xử lý login
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Attempt login với username
        if (Auth::attempt($request->only('username', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate(); // Bảo mật session
            
            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'username' => __('auth.failed'),
        ]);
    }

    // Logout
    public function destroy(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}
```

## Bước 10: API Authentication Controller

### app/Http/Controllers/Api/AuthController.php
Tạo controller cho API:
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // API Login - trả về token
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

        // Tạo token cho API
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    // API Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logged out successfully']);
    }

    // API User info
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
```

## Bước 11: Web Routes

### routes/web.php
```php
<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Trang chủ
Route::get('/', function () {
    return Inertia::render('Welcome');
});

// Routes không cần authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Routes cần authentication cho web
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
```

## Bước 12: API Routes

### routes/api.php
```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// API Routes không cần authentication
Route::post('/login', [AuthController::class, 'login']);

// API Routes cần authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Các API routes khác của bạn
    Route::get('/protected-data', function () {
        return response()->json(['message' => 'This is protected data']);
    });
});

// CSRF Cookie endpoint cho SPA
Route::get('/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});
```

## Bước 13: Inertia Middleware

### app/Http/Middleware/HandleInertiaRequests.php
Cập nhật method share (dòng ~30):
```php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'username' => $request->user()->username,
                'email' => $request->user()->email,
            ] : null,
        ],
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error' => fn () => $request->session()->get('error'),
        ],
    ]);
}
```

## Bước 14: Bootstrap Sanctum

### bootstrap/app.php (Laravel 11)
Đảm bảo có middleware (dòng ~15):
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        
        // Sanctum middleware đã được tự động load
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

## Bước 15: Chạy Migration và Test

### Terminal
```bash
# Chạy migration
php artisan migrate

# Tạo user test (optional)
php artisan tinker
User::create(['name' => 'Test User', 'username' => 'testuser', 'email' => 'test@example.com', 'password' => bcrypt('password')]);

# Khởi động server
php artisan serve
```

## Test Authentication

### Web Authentication:
- Truy cập `/login` để đăng nhập web
- Sau khi login chuyển đến `/dashboard`

### API Authentication:
- POST `/api/login` với username/password để lấy token
- Sử dụng `Bearer {token}` header cho các API calls
- GET `/api/user` để lấy thông tin user

## Lưu ý quan trọng:
1. Đảm bảo FRONTEND_URL trong .env khớp với URL frontend
2. Web routes sử dụng session, API routes sử dụng token
3. CSRF cookie tự động được xử lý bởi Sanctum
4. Remember logout cả session và token khi cần thiết
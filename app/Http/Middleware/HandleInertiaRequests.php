<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use App\Services\CorporateReportSettingsService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $unreadCount = $user ? Notification::where('is_read', false)->count() : 0;
        $recentNotifications = $user ? Notification::latest()->limit(5)->get() : [];

        $company = app(CorporateReportSettingsService::class)->forInertia();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                    'avatar_url' => $user->avatar_url,
                    'phone' => $user->phone,
                ] : null,
            ],
            'company' => $company,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'notifications' => [
                'unread_count' => $unreadCount,
                'recent' => $recentNotifications,
            ],
        ];
    }
}

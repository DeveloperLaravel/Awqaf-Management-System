<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AttendanceChart;
use App\Filament\Widgets\CenterOverview;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Actions\Action;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Http\Middleware\EnsureCenterIsApproved;
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
->plugins([
    FilamentShieldPlugin::make()
      ->localizePermissionLabels()
        ->navigationGroup('إدارة النظام')

        ->navigationLabel('الأدوار والصلاحيات')
        ->navigationSort(90)
         
])
            ->brandName('منظومة إدارة مراكز التحفيظ')

            ->defaultThemeMode(ThemeMode::Light)
            ->themeSwitcher()
            ->darkMode(true)

            ->viteTheme(
                'resources/css/filament/admin/theme.css'
            )
              ->userMenuItems([
          'logout' => fn (Action $action) => $action
            ->label('تسجيل الخروج')
            ->icon('heroicon-o-arrow-right-start-on-rectangle')
            ->color('danger'),
    ])
            ->colors([
                'primary' => Color::hex('#173B32'),
                'gray' => Color::Slate,
                'success' => Color::hex('#246653'),
                'warning' => Color::hex('#C7A45B'),
            ])

            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources',
            )

            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages',
            )

           ->pages([
    \App\Filament\Pages\Dashboard::class,
])

   ->widgets([
    // الإحصائيات الرئيسية
    \App\Filament\Widgets\CenterOverview::class,

    // إحصائيات الطلاب والمعلمين والحلقات
    \App\Filament\Widgets\StudentsOverview::class,
    \App\Filament\Widgets\TeachersOverview::class,
    \App\Filament\Widgets\CirclesOverview::class,

    // الرسوم البيانية
    \App\Filament\Widgets\AttendanceChart::class,
    \App\Filament\Widgets\MemorizationChart::class,
    \App\Filament\Widgets\PaymentsOverview::class,

    // الجداول
    \App\Filament\Widgets\RecentAttendance::class,
    \App\Filament\Widgets\RecentStudents::class,
    \App\Filament\Widgets\PendingCenters::class,
])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            ->authMiddleware([
                Authenticate::class,
                EnsureCenterIsApproved::class,

            ]);
    }
}

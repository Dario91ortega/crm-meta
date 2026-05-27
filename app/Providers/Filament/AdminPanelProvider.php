<?php

namespace App\Providers\Filament;

use App\Models\User;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->topNavigation()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('google')
                            ->label('Continue with Google')
                            ->icon('heroicon-o-globe-alt')
                            ->color(Color::Gray)
                            ->outlined()
                            ->stateless(true),
                    ])
                    ->registration(true)
                    ->createUserUsing(function (string $provider, SocialiteUserContract $oauthUser, FilamentSocialitePlugin $plugin): User {
                        $fullName = trim((string) ($oauthUser->getName() ?? $oauthUser->getNickname() ?? ''));
                        [$firstName, $lastName] = self::splitName($fullName);

                        return User::create([
                            'name' => $fullName !== '' ? $fullName : ($oauthUser->getEmail() ?? 'User'),
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => $oauthUser->getEmail(),
                            'email_verified_at' => now(),
                            'avatar' => $oauthUser->getAvatar(),
                            'password' => Hash::make(Str::random(40)),
                            'is_active' => true,
                            'agency_id' => null,    // pending — a manager must assign one
                            'approved_at' => null,  // pending — a manager must approve
                        ]);
                    }),
            ]);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected static function splitName(string $fullName): array
    {
        if ($fullName === '') {
            return [null, null];
        }

        $parts = preg_split('/\s+/', $fullName, 2) ?: [];

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
        ];
    }
}

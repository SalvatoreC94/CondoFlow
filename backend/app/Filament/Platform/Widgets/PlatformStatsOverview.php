<?php

namespace App\Filament\Platform\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Administrator;
use App\Models\Condominium;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $administratorsCount = Administrator::count();
        $activeCount = Administrator::where('subscription_status', SubscriptionStatus::Active)->count();
        $trialCount = Administrator::where('subscription_status', SubscriptionStatus::Trial)->count();
        $expiringSoonCount = Administrator::whereBetween('subscription_ends_at', [now(), now()->addDays(7)])->count();

        return [
            Stat::make('Amministratori', $administratorsCount)
                ->description("{$activeCount} attivi · {$trialCount} in prova")
                ->color('primary'),
            Stat::make('Condomini gestiti', Condominium::count())
                ->description('Su tutta la piattaforma')
                ->color('success'),
            Stat::make('Unità sotto gestione', Condominium::sum('total_units'))
                ->description('Somma delle unità dichiarate per condominio')
                ->color('gray'),
            Stat::make('Abbonamenti in scadenza', $expiringSoonCount)
                ->description('Nei prossimi 7 giorni')
                ->color($expiringSoonCount > 0 ? 'warning' : 'success'),
        ];
    }
}

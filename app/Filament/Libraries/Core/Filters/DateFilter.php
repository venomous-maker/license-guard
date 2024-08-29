<?php

namespace App\Filament\Libraries\Core\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Tables;
use Filament\Forms;
class DateFilter
{
    public static function fromTo(): Tables\Filters\Filter
    {
        $filter = Tables\Filters\Filter::make('created_at')
            ->form([
                Forms\Components\DatePicker::make('created_from')
                    ->placeholder(fn ($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                Forms\Components\DatePicker::make('created_until')
                    ->placeholder(fn ($state): string => now()->format('M d, Y')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['created_from'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                    )
                    ->when(
                        $data['created_until'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['created_from'] ?? null) {
                    $indicators['created_from'] = 'from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                }
                if ($data['created_until'] ?? null) {
                    $indicators['created_until'] = 'until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                }

                return $indicators;
            });
        return $filter;
    }
}

<?php

namespace App\Filament\User\Resources\LicenseResource\Pages;

use App\Filament\User\Resources\LicenseResource;
use App\Libraries\Core;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Services\MpesaService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditLicense extends EditRecord
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Action::make('payment')
                    ->label('Make Payment')
                    ->visible(fn () => $this->record && $this->record->active != 1)
                    ->url(fn () => $this->record ? route('checkout', $this->record->id) : '#')
                    ->openUrlInNewTab(),

                Action::make('mpesa_payment')
                    ->form([
                        TextInput::make('phone_number')->required(),
                        TextInput::make('amount')->required()->default(fn () => $this->record ? $this->record->type->amount : null),
                    ])
                    ->action(function (array $data) {
                        (new MpesaService())->makeStkPayment($data);
                    }),

                Action::make('confirm_payment')
                    ->form([
                        TextInput::make('phone_number')->required(),
                        TextInput::make('amount')->required()->default(fn () => $this->record ? $this->record->type->amount : null)
                            ->readOnly(fn () => $this->record && $this->record->type->amount !== null),
                    ])
                    ->action(function (array $data) {
                        $transaction = MpesaTransaction::query()
                            ->where('phone_number', $data['phone_number'])
                            ->where('transaction_amount', $data['amount'])
                            ->whereBetween('transaction_date', [Carbon::parse(now())->startOfDay(), Carbon::parse(now())->endOfMonth()])
                            ->first();

                        if ($transaction && $this->record) {
                            $user = auth()->user();
                            $licenseType = $this->record->type;

                            if (!$licenseType) {
                                Notification::make()
                                    ->danger()
                                    ->title('License Type Not Found')
                                    ->body('The license type could not be found.')
                                    ->send();

                                return;
                            }

                            $this->record->update([
                                'active' => 1,
                                'expiry_date' => Core::licenceDuration($licenseType->duration),
                            ]);

                            Payment::create([
                                'user_id' => $user->id,
                                'license_id' => $this->record->id,
                                'trx_ref' => Str::uuid(),
                                'gateway' => 'Flutterwave',
                                'amount' => $data['amount'],
                                'info' => 'Payment Successful for ' . $licenseType->name . ' Activation/Renewal'
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Transaction Confirmed')
                                ->body('Your transaction has been confirmed.')
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Transaction Confirmation Failed')
                                ->body('Your transaction confirmation failed.')
                                ->send();
                        }
                    }),

                Actions\DeleteAction::make(),
            ]),
        ];
    }
}

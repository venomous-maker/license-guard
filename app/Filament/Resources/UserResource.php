<?php

namespace App\Filament\Resources;

use App\Filament\Libraries\Core\Filters\DateFilter;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('email')->required()->email(),
                TextInput::make('password')->required()->password()->visibleOn('create'),
                Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')->label('Profile Picture'),
                TextColumn::make('name')->label('Name')->searchable()->sortable()->copyable(),
                TextColumn::make('email')->copyable()->label('Email')
                ->sortable()->searchable(),
                TextColumn::make('created_at')->label('Registered at')->sortable()->searchable()->copyable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
                Tables\Filters\TrashedFilter::make(),
                DateFilter::fromTo()
                ,
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (User $record) => auth()->user()->can('edit-user', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function boot()
    {
        parent::boot();

        // Ensure the role is set correctly when creating or editing users
        static::saved(function (User $user) {
            $roles = request()->input('roles', []);
            $user->syncRoles($roles);
        });
    }
}

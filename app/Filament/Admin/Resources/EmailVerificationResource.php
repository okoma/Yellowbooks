<?php
// ============================================
// 6. EmailVerificationResource.php (READ-ONLY)
// Purpose: Track email verification tokens
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EmailVerificationResource\Pages;
use App\Models\EmailVerification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class EmailVerificationResource extends Resource
{
    protected static ?string $model = EmailVerification::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $navigationLabel = 'Email Verifications';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')->searchable()->url(fn ($r) => $r->user ? route('filament.admin.resources.users.view', $r->user) : null),
            Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('type')->badge(),
            Tables\Columns\IconColumn::make('is_used')->boolean()->label('Used'),
            Tables\Columns\TextColumn::make('expires_at')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->since(),
        ])->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\TernaryFilter::make('is_used'),
            Tables\Filters\Filter::make('expired')->query(fn ($q) => $q->where('expires_at', '<', now())),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEmailVerifications::route('/')];
    }

    public static function canCreate(): bool { return false; }
}

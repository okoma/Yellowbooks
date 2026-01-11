<?php
// ============================================
// 7. VerificationAttemptResource (READ-ONLY)
// Purpose: Audit trail for verification attempts
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VerificationAttemptResource\Pages;
use App\Models\VerificationAttempt;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class VerificationAttemptResource extends Resource
{
    protected static ?string $model = VerificationAttempt::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Verification Attempts';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('verification.business.business_name')->label('Business')->searchable()->limit(30),
            Tables\Columns\TextColumn::make('verification_type')->badge(),
            Tables\Columns\TextColumn::make('status')->badge()->colors(['success' => 'success', 'danger' => 'failed', 'warning' => 'pending']),
            Tables\Columns\TextColumn::make('details')->limit(50),
            Tables\Columns\TextColumn::make('ip_address')->copyable()->toggleable(),
            Tables\Columns\TextColumn::make('attempted_at')->dateTime()->sortable()->since(),
        ])->defaultSort('attempted_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListVerificationAttempts::route('/')];
    }

    public static function canCreate(): bool { return false; }
}

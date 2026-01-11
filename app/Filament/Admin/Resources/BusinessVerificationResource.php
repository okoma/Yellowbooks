<?php

// ============================================
// app/Filament/Admin/Resources/BusinessVerificationResource.php
// Location: app/Filament/Admin/Resources/BusinessVerificationResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Multi-step business verification (CAC, Location, Email, Website) with documents
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessVerificationResource\Pages;
use App\Models\BusinessVerification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class BusinessVerificationResource extends Resource
{
    protected static ?string $model = BusinessVerification::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Business Verifications';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                // ===== STEP 1: Basic Information =====
                Forms\Components\Wizard\Step::make('Basic Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Select::make('business_id')
                                    ->relationship('business', 'business_name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn ($context) => $context !== 'create'),
                                
                                Forms\Components\Select::make('business_claim_id')
                                    ->label('Related Claim')
                                    ->relationship('claim', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                                        "Claim #{$record->id} - {$record->user->name}"
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Link to approved business claim (optional)'),
                                
                                Forms\Components\Select::make('submitted_by')
                                    ->label('Submitted By')
                                    ->relationship('submitter', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => auth()->id())
                                    ->disabled(fn ($context) => $context !== 'create'),
                            ])
                            ->columns(1),
                    ]),

                // ===== STEP 2: CAC Verification =====
                Forms\Components\Wizard\Step::make('CAC Verification')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Section::make('CAC Registration')
                            ->description('Corporate Affairs Commission registration details')
                            ->schema([
                                Forms\Components\TextInput::make('cac_number')
                                    ->label('CAC Registration Number')
                                    ->maxLength(255)
                                    ->helperText('Company registration number'),
                                
                                Forms\Components\FileUpload::make('cac_document')
                                    ->label('CAC Certificate/Document')
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(10240)
                                    ->directory('verification-cac')
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('Upload CAC certificate or incorporation document (Max: 10MB)'),
                                
                                Forms\Components\Toggle::make('cac_verified')
                                    ->label('CAC Verified')
                                    ->helperText('Mark as verified after reviewing documents'),
                                
                                Forms\Components\Textarea::make('cac_notes')
                                    ->label('Admin Notes')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('Internal notes about CAC verification'),
                            ])
                            ->columns(2),
                    ]),

                // ===== STEP 3: Location Verification =====
                Forms\Components\Wizard\Step::make('Location Verification')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Section::make('Physical Office Location')
                            ->description('Verify business physical location with photo proof')
                            ->schema([
                                Forms\Components\Textarea::make('office_address')
                                    ->label('Office Address')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->helperText('Full physical office address')
                                    ->columnSpanFull(),
                                
                                Forms\Components\FileUpload::make('office_photo')
                                    ->label('Office Photo')
                                    ->image()
                                    ->maxSize(5120)
                                    ->directory('verification-office')
                                    ->imageEditor()
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('Photo of office building/storefront (Max: 5MB)'),
                                
                                Forms\Components\TextInput::make('office_latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->helperText('GPS coordinates'),
                                
                                Forms\Components\TextInput::make('office_longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->helperText('GPS coordinates'),
                                
                                Forms\Components\Toggle::make('location_verified')
                                    ->label('Location Verified')
                                    ->helperText('Mark as verified after reviewing location proof'),
                                
                                Forms\Components\Textarea::make('location_notes')
                                    ->label('Admin Notes')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('Internal notes about location verification')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),

                // ===== STEP 4: Email Verification =====
                Forms\Components\Wizard\Step::make('Email Verification')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Forms\Components\Section::make('Business Email')
                            ->description('Verify official business email address')
                            ->schema([
                                Forms\Components\TextInput::make('business_email')
                                    ->label('Business Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->helperText('Official business email address'),
                                
                                Forms\Components\TextInput::make('email_verification_token')
                                    ->label('Verification Token')
                                    ->disabled()
                                    ->helperText('Auto-generated verification token'),
                                
                                Forms\Components\Toggle::make('email_verified')
                                    ->label('Email Verified')
                                    ->helperText('Mark as verified after email confirmation'),
                                
                                Forms\Components\DateTimePicker::make('email_verified_at')
                                    ->label('Verified At')
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => $get('email_verified')),
                                
                                Forms\Components\Textarea::make('email_notes')
                                    ->label('Admin Notes')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('Internal notes about email verification')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),

                // ===== STEP 5: Website Verification =====
                Forms\Components\Wizard\Step::make('Website Verification')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        Forms\Components\Section::make('Website Ownership')
                            ->description('Verify website ownership via meta tag')
                            ->schema([
                                Forms\Components\TextInput::make('website_url')
                                    ->label('Website URL')
                                    ->url()
                                    ->maxLength(500)
                                    ->prefix('https://')
                                    ->helperText('Business website URL'),
                                
                                Forms\Components\TextInput::make('meta_tag_code')
                                    ->label('Verification Meta Tag Code')
                                    ->maxLength(500)
                                    ->helperText('Unique code to place in website <head>'),
                                
                                Forms\Components\Toggle::make('website_verified')
                                    ->label('Website Verified')
                                    ->helperText('Mark as verified after meta tag is found'),
                                
                                Forms\Components\DateTimePicker::make('website_verified_at')
                                    ->label('Verified At')
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => $get('website_verified')),
                                
                                Forms\Components\Textarea::make('website_notes')
                                    ->label('Admin Notes')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('Internal notes about website verification')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),

                // ===== STEP 6: Additional Documents =====
                Forms\Components\Wizard\Step::make('Additional Documents')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        Forms\Components\Section::make('Supporting Documents')
                            ->description('Upload utility bills, tax certificates, or other proof documents')
                            ->schema([
                                Forms\Components\FileUpload::make('additional_documents')
                                    ->label('Additional Documents')
                                    ->multiple()
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(10240)
                                    ->maxFiles(10)
                                    ->directory('verification-additional')
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('Upload utility bills, tax certificates, etc. (Max: 10 files, 10MB each)')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // ===== STEP 7: Review & Status =====
                Forms\Components\Wizard\Step::make('Review & Status')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\Section::make('Verification Status')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending Review',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                        'requires_resubmission' => 'Requires Resubmission',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->native(false)
                                    ->live(),
                                
                                Forms\Components\TextInput::make('verification_score')
                                    ->label('Verification Score')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('/ 100')
                                    ->disabled()
                                    ->helperText('Auto-calculated: CAC(40) + Location(30) + Email(20) + Website(10)'),
                                
                                Forms\Components\Select::make('verified_by')
                                    ->label('Verified By')
                                    ->relationship('verifier', 'name')
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => in_array($get('status'), ['approved', 'rejected'])),
                                
                                Forms\Components\Textarea::make('rejection_reason')
                                    ->label('Rejection Reason')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->visible(fn (Forms\Get $get) => $get('status') === 'rejected')
                                    ->required(fn (Forms\Get $get) => $get('status') === 'rejected')
                                    ->columnSpanFull(),
                                
                                Forms\Components\Textarea::make('admin_feedback')
                                    ->label('Admin Feedback')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('Feedback for business owner (visible to them)')
                                    ->columnSpanFull(),
                                
                                Forms\Components\TextInput::make('resubmission_count')
                                    ->label('Resubmission Count')
                                    ->numeric()
                                    ->disabled()
                                    ->default(0),
                            ])
                            ->columns(2),
                    ]),
            ])
            ->columnSpanFull()
            ->skippable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => "Score: {$record->verification_score}/100"),
                
                Tables\Columns\TextColumn::make('submitter.name')
                    ->label('Submitted By')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('cac_verified')
                    ->boolean()
                    ->label('CAC')
                    ->tooltip('CAC Verified'),
                
                Tables\Columns\IconColumn::make('location_verified')
                    ->boolean()
                    ->label('Location')
                    ->tooltip('Location Verified'),
                
                Tables\Columns\IconColumn::make('email_verified')
                    ->boolean()
                    ->label('Email')
                    ->tooltip('Email Verified'),
                
                Tables\Columns\IconColumn::make('website_verified')
                    ->boolean()
                    ->label('Website')
                    ->tooltip('Website Verified'),
                
                Tables\Columns\TextColumn::make('verification_score')
                    ->label('Score')
                    ->sortable()
                    ->suffix('/100')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 90 => 'success',
                        $state >= 70 => 'info',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'requires_resubmission',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y')),
                
                Tables\Columns\TextColumn::make('resubmission_count')
                    ->label('Resubmissions')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Deleted At'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'requires_resubmission' => 'Requires Resubmission',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('cac_verified')
                    ->label('CAC Verified'),
                
                Tables\Filters\TernaryFilter::make('location_verified')
                    ->label('Location Verified'),
                
                Tables\Filters\TernaryFilter::make('email_verified')
                    ->label('Email Verified'),
                
                Tables\Filters\TernaryFilter::make('website_verified')
                    ->label('Website Verified'),
                
                Tables\Filters\Filter::make('verification_score')
                    ->form([
                        Forms\Components\TextInput::make('score_from')
                            ->numeric()
                            ->label('Score from'),
                        Forms\Components\TextInput::make('score_to')
                            ->numeric()
                            ->label('Score to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['score_from'], fn ($q, $val) => $q->where('verification_score', '>=', $val))
                            ->when($data['score_to'], fn ($q, $val) => $q->where('verification_score', '<=', $val));
                    }),
                
                TrashedFilter::make()->label('Deleted Verifications'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('calculate_score')
                        ->label('Calculate Score')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->action(function (BusinessVerification $record) {
                            $record->calculateScore();
                            
                            Notification::make()
                                ->success()
                                ->title('Score Updated')
                                ->body("Verification score: {$record->verification_score}/100")
                                ->send();
                        }),
                    
                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (BusinessVerification $record) {
                            $record->approve(auth()->id());
                            
                            Notification::make()
                                ->success()
                                ->title('Verification Approved')
                                ->body("Business has been verified successfully.")
                                ->send();
                        })
                        ->visible(fn (BusinessVerification $record) => $record->status !== 'approved'),
                    
                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->required()
                                ->label('Reason for Rejection')
                                ->rows(3),
                            Forms\Components\Textarea::make('admin_feedback')
                                ->label('Feedback for Business Owner')
                                ->rows(3),
                        ])
                        ->action(function (BusinessVerification $record, array $data) {
                            $record->reject(auth()->id(), $data['rejection_reason'], $data['admin_feedback'] ?? null);
                            
                            Notification::make()
                                ->danger()
                                ->title('Verification Rejected')
                                ->send();
                        })
                        ->visible(fn (BusinessVerification $record) => $record->status !== 'rejected'),
                    
                    Tables\Actions\Action::make('request_resubmission')
                        ->label('Request Resubmission')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Textarea::make('admin_feedback')
                                ->required()
                                ->label('What needs to be fixed?')
                                ->rows(3),
                        ])
                        ->action(function (BusinessVerification $record, array $data) {
                            $record->requestResubmission(auth()->id(), $data['admin_feedback']);
                            
                            Notification::make()
                                ->warning()
                                ->title('Resubmission Requested')
                                ->send();
                        }),
                    
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Business Verification')
                        ->modalDescription('This will soft delete the verification. It will be hidden but can be restored later.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Verification deleted')
                                ->body('The business verification has been soft deleted.')
                        ),
                    
                    Tables\Actions\RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Verification restored')
                                ->body('The business verification has been restored.')
                        ),
                    
                    Tables\Actions\ForceDeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Verification')
                        ->modalDescription('Are you sure? This will permanently delete the verification and cannot be undone.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Verification permanently deleted')
                                ->body('The business verification has been permanently removed from the database.')
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Verifications deleted')
                                ->body('The selected verifications have been soft deleted.')
                        ),
                    
                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Verifications restored')
                                ->body('The selected verifications have been restored.')
                        ),
                    
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Verifications')
                        ->modalDescription('This will permanently delete the selected verifications. This action cannot be undone.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Verifications permanently deleted')
                                ->body('The selected verifications have been permanently removed.')
                        ),
                    
                    Tables\Actions\BulkAction::make('calculate_scores')
                        ->label('Calculate Scores')
                        ->icon('heroicon-o-calculator')
                        ->action(fn ($records) => $records->each->calculateScore()),
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
            'index' => Pages\ListBusinessVerifications::route('/'),
            'create' => Pages\CreateBusinessVerification::route('/create'),
            'edit' => Pages\EditBusinessVerification::route('/{record}/edit'),
            'view' => Pages\ViewBusinessVerification::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::whereIn('status', ['pending', 'requires_resubmission'])->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::whereIn('status', ['pending', 'requires_resubmission'])->count();
        return $pendingCount > 0 ? 'warning' : null;
    }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}
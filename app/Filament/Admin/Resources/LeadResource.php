<?php
// ============================================
// app/Filament/Admin/Resources/LeadResource.php
// Location: app/Filament/Admin/Resources/LeadResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: System-wide lead/inquiry management - supports BOTH standalone businesses AND branches
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LeadResource\Pages;
use App\Models\Lead;
use App\Models\Business;
use App\Models\BusinessBranch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Leads';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Lead Information')
                ->schema([
                    // Lead Type Selection
                    Forms\Components\Select::make('lead_type')
                        ->label('Lead For')
                        ->options([
                            'branch' => 'Business Branch',
                            'business' => 'Business(Main)',
                        ])
                        ->required()
                        ->default('branch')
                        ->live()
                        ->disabled(fn ($context) => $context !== 'create')
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('business_id', null);
                            $set('business_branch_id', null);
                        })
                        ->helperText('Select whether this lead is for a specific branch or a standalone business'),
                    
                    // For Branch Leads
                    Forms\Components\Select::make('business_branch_id')
                        ->label('Business Branch')
                        ->relationship('branch', 'branch_name')
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => 
                            "{$record->branch_title}"
                        )
                        ->disabled(fn ($context) => $context !== 'create')
                        ->required(fn (Forms\Get $get) => $get('lead_type') === 'branch')
                        ->visible(fn (Forms\Get $get) => $get('lead_type') === 'branch')
                        ->helperText('Select the specific branch this lead is for'),
                    
                    // For Standalone Business Leads
                    Forms\Components\Select::make('business_id')
                        ->label('Business')
                        ->relationship('business', 'business_name')
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create')
                        ->required(fn (Forms\Get $get) => $get('lead_type') === 'business')
                        ->visible(fn (Forms\Get $get) => $get('lead_type') === 'business')
                        ->helperText('Select the standalone business this lead is for'),
                    
                    Forms\Components\Select::make('user_id')
                        ->label('User (if logged in)')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('Leave empty for guest inquiries'),
                    
                    Forms\Components\TextInput::make('client_name')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Customer name'),
                    
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20),
                    
                    Forms\Components\TextInput::make('whatsapp')
                        ->tel()
                        ->maxLength(20)
                        ->prefix('+234'),
                    
                    Forms\Components\TextInput::make('lead_button_text')
                        ->label('Lead Type/Button')
                        ->maxLength(255)
                        ->helperText('e.g., "Book Now", "Get Quote", "Contact Us"'),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Custom Fields')
                ->description('Additional information provided by customer')
                ->schema([
                    Forms\Components\KeyValue::make('custom_fields')
                        ->label('Additional Information')
                        ->helperText('Custom form fields and their values')
                        ->columnSpanFull(),
                ])
                ->collapsible(),
            
            Forms\Components\Section::make('Status & Response')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'new' => 'New',
                            'contacted' => 'Contacted',
                            'qualified' => 'Qualified',
                            'converted' => 'Converted',
                            'lost' => 'Lost',
                        ])
                        ->required()
                        ->default('new')
                        ->native(false)
                        ->live(),
                    
                    Forms\Components\Toggle::make('is_replied')
                        ->label('Replied')
                        ->live(),
                    
                    Forms\Components\Textarea::make('reply_message')
                        ->label('Reply to Customer')
                        ->rows(3)
                        ->maxLength(1000)
                        ->visible(fn (Forms\Get $get) => $get('is_replied'))
                        ->required(fn (Forms\Get $get) => $get('is_replied'))
                        ->columnSpanFull(),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->maxLength(2000)
                        ->helperText('Private notes (not visible to customer)')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Lead Type Badge
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($record) => 
                        $record->isBranchLead() ? 'Branch' : 'Business'
                    )
                    ->badge()
                    ->color(fn ($record) => 
                        $record->isBranchLead() ? 'info' : 'success'
                    )
                    ->sortable(false),
                
                // Business Name with Branch Description
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $business = $record->getParentBusiness();
                        return $business?->business_name ?? 'N/A';
                    })
                    ->description(fn ($record) => 
                        $record->isBranchLead() 
                            ? $record->branch?->branch_name 
                            : 'Standalone Business'
                    ),
                
                Tables\Columns\TextColumn::make('client_name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->email ?: $record->phone),
                
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('lead_button_text')
                    ->label('Button')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'new',
                        'info' => 'contacted',
                        'primary' => 'qualified',
                        'success' => 'converted',
                        'danger' => 'lost',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_replied')
                    ->boolean()
                    ->label('Replied')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('custom_fields')
                    ->label('Info')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'None';
                        $count = count($state);
                        return "{$count} field(s)";
                    })
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y h:i A')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Filter by Lead Type (Branch or Business)
                Tables\Filters\Filter::make('lead_type')
                    ->label('Lead Type')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'branch' => 'Branch Leads',
                                'business' => 'Business Leads',
                            ])
                            ->placeholder('All leads'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['type'] === 'branch', fn ($q) => $q->whereNotNull('business_branch_id'))
                            ->when($data['type'] === 'business', fn ($q) => $q->whereNotNull('business_id')->whereNull('business_branch_id'));
                    }),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'converted' => 'Converted',
                        'lost' => 'Lost',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_replied')
                    ->label('Reply Status')
                    ->placeholder('All leads')
                    ->trueLabel('Replied')
                    ->falseLabel('Not replied'),
                
                Tables\Filters\SelectFilter::make('lead_button_text')
                    ->label('Lead Button Type')
                    ->options(function () {
                        return Lead::query()
                            ->distinct()
                            ->pluck('lead_button_text', 'lead_button_text')
                            ->filter()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('received_from')
                            ->label('Received from'),
                        Forms\Components\DatePicker::make('received_until')
                            ->label('Received until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['received_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['received_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->today()),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->thisWeek()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('contact')
                        ->icon('heroicon-o-phone')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Mark as Contacted')
                        ->modalDescription('Change lead status to "Contacted"?')
                        ->action(function (Lead $record) {
                            $record->markAsContacted();
                            
                            Notification::make()
                                ->success()
                                ->title('Lead Status Updated')
                                ->body('Lead marked as contacted.')
                                ->send();
                        })
                        ->visible(fn (Lead $record) => $record->isNew()),
                    
                    Tables\Actions\Action::make('qualify')
                        ->icon('heroicon-o-check-badge')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function (Lead $record) {
                            $record->markAsQualified();
                            
                            Notification::make()
                                ->success()
                                ->title('Lead Qualified')
                                ->send();
                        })
                        ->visible(fn (Lead $record) => $record->isNew() || $record->isContacted()),
                    
                    Tables\Actions\Action::make('convert')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark as Converted')
                        ->modalDescription('This lead successfully converted to a customer?')
                        ->action(function (Lead $record) {
                            $record->markAsConverted();
                            
                            Notification::make()
                                ->success()
                                ->title('Lead Converted! 🎉')
                                ->body('Lead successfully converted.')
                                ->send();
                        })
                        ->visible(fn (Lead $record) => $record->isContacted() || $record->isQualified()),
                    
                    Tables\Actions\Action::make('mark_lost')
                        ->label('Mark Lost')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Lead $record) {
                            $record->markAsLost();
                            
                            Notification::make()
                                ->warning()
                                ->title('Lead Marked as Lost')
                                ->send();
                        })
                        ->visible(fn (Lead $record) => !$record->isLost() && !$record->isConverted()),
                    
                    Tables\Actions\Action::make('send_reply')
                        ->label('Send Reply')
                        ->icon('heroicon-o-chat-bubble-left')
                        ->color('info')
                        ->form([
                            Forms\Components\Textarea::make('reply_message')
                                ->required()
                                ->label('Reply Message')
                                ->rows(4)
                                ->helperText('This message will be sent to the customer'),
                        ])
                        ->action(function (Lead $record, array $data) {
                            $record->markAsReplied($data['reply_message']);
                            
                            // TODO: Send email/SMS to customer
                            
                            Notification::make()
                                ->success()
                                ->title('Reply Sent')
                                ->send();
                        })
                        ->visible(fn (Lead $record) => !$record->isReplied()),
                    
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_contacted')
                        ->label('Mark as Contacted')
                        ->icon('heroicon-o-phone')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->markAsContacted();
                            
                            Notification::make()
                                ->success()
                                ->title('Leads Updated')
                                ->body(count($records) . ' leads marked as contacted.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('mark_qualified')
                        ->label('Mark as Qualified')
                        ->icon('heroicon-o-check-badge')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->markAsQualified();
                            
                            Notification::make()
                                ->success()
                                ->title('Leads Qualified')
                                ->body(count($records) . ' leads marked as qualified.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('mark_converted')
                        ->label('Mark as Converted')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->markAsConverted();
                            
                            Notification::make()
                                ->success()
                                ->title('Leads Converted! 🎉')
                                ->body(count($records) . ' leads marked as converted.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            // TODO: Implement CSV export
                            Notification::make()
                                ->info()
                                ->title('Export Started')
                                ->body('CSV export will be ready shortly.')
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Lead Details')
                    ->schema([
                        // Lead Type Badge
                        Components\TextEntry::make('type')
                            ->label('Lead Type')
                            ->formatStateUsing(fn ($record) => 
                                $record->isBranchLead() ? 'Business Branch' : 'Standalone Business'
                            )
                            ->badge()
                            ->color(fn ($record) => 
                                $record->isBranchLead() ? 'info' : 'success'
                            ),
                        
                        // Parent Business
                        Components\TextEntry::make('business_name')
                            ->label('Business')
                            ->getStateUsing(function ($record) {
                                $business = $record->getParentBusiness();
                                return $business?->business_name ?? 'N/A';
                            })
                            ->url(function ($record) {
                                $business = $record->getParentBusiness();
                                return $business 
                                    ? route('filament.admin.resources.businesses.view', $business)
                                    : null;
                            })
                            ->color('primary'),
                        
                        // Branch (if applicable)
                        Components\TextEntry::make('branch.branch_name')
                            ->label('Branch')
                            ->url(fn ($record) => 
                                $record->branch 
                                    ? route('filament.admin.resources.business-branches.view', $record->branch)
                                    : null
                            )
                            ->color('primary')
                            ->visible(fn ($record) => $record->isBranchLead()),
                        
                        Components\TextEntry::make('lead_button_text')
                            ->label('Lead Button')
                            ->badge(),
                        
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'new' => 'warning',
                                'contacted' => 'info',
                                'qualified' => 'primary',
                                'converted' => 'success',
                                'lost' => 'danger',
                            }),
                    ])
                    ->columns(2),
                
                Components\Section::make('Customer Information')
                    ->schema([
                        Components\TextEntry::make('client_name')
                            ->label('Name'),
                        
                        Components\TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->url(fn ($record) => $record->getEmailLink()),
                        
                        Components\TextEntry::make('phone')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->url(fn ($record) => $record->getPhoneLink()),
                        
                        Components\TextEntry::make('whatsapp')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->url(fn ($record) => $record->getWhatsAppLink())
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->whatsapp),
                        
                        Components\TextEntry::make('user.name')
                            ->label('Registered User')
                            ->url(fn ($record) => $record->user ? route('filament.admin.resources.users.view', $record->user) : null)
                            ->visible(fn ($record) => $record->user),
                    ])
                    ->columns(3),
                
                Components\Section::make('Additional Information')
                    ->schema([
                        Components\KeyValueEntry::make('custom_fields')
                            ->label('Custom Fields')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->custom_fields)
                    ->collapsible(),
                
                Components\Section::make('Response')
                    ->schema([
                        Components\IconEntry::make('is_replied')
                            ->boolean()
                            ->label('Replied'),
                        
                        Components\TextEntry::make('replied_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->replied_at),
                        
                        Components\TextEntry::make('reply_message')
                            ->label('Reply')
                            ->visible(fn ($record) => $record->reply_message)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->is_replied)
                    ->collapsible(),
                
                Components\Section::make('Internal Notes')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->visible(fn ($record) => $record->notes)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->notes)
                    ->collapsible(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Received At')
                            ->description(fn ($record) => $record->getTimeSinceCreated()),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Last Updated'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
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
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
            'view' => Pages\ViewLead::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $newCount = static::getModel()::new()->count();
        return $newCount > 0 ? (string) $newCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $newCount = static::getModel()::new()->count();
        return $newCount > 0 ? 'warning' : null;
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['business', 'branch.business', 'user']);
    }
}
<?php
// ============================================
// app/Filament/Business/Resources/LeadResource.php
// Centralized lead management across all businesses
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\LeadResource\Pages;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    
    protected static ?string $navigationLabel = 'My Leads';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lead Information')
                    ->schema([
                        Forms\Components\Select::make('business_id')
                            ->label('Business')
                            ->relationship(
                                'business',
                                'business_name',
                                fn($query) => $query->where('user_id', Auth::id())
                            )
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('business_branch_id')
                            ->label('Branch')
                            ->relationship('branch', 'branch_title')
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('client_name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        
                        Forms\Components\TextInput::make('whatsapp')
                            ->tel()
                            ->maxLength(20),
                        
                        Forms\Components\TextInput::make('lead_button_text')
                            ->label('Inquiry Type')
                            ->maxLength(100),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Custom Fields')
                    ->schema([
                        Forms\Components\KeyValue::make('custom_fields')
                            ->label('Additional Information'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
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
                            ->default('new'),
                        
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        
                        Forms\Components\Toggle::make('is_replied')
                            ->label('Replied')
                            ->live(),
                        
                        Forms\Components\Textarea::make('reply_message')
                            ->rows(4)
                            ->maxLength(1000)
                            ->visible(fn (Forms\Get $get) => $get('is_replied'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->url(fn ($record) => $record->business ? 
                        route('filament.business.resources.businesses.view', $record->business) : null),
                
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->getStateUsing(fn ($record) => $record->branch?->branch_title ?? $record->business?->business_name)
                    ->searchable(['branch.branch_title', 'business.business_name']),
                
                Tables\Columns\TextColumn::make('client_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-o-envelope')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('whatsapp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->copyable()
                    ->url(fn ($record) => $record->whatsapp ? 
                        'https://wa.me/' . preg_replace('/[^0-9]/', '', $record->whatsapp) : null)
                    ->openUrlInNewTab(),
                
                Tables\Columns\TextColumn::make('lead_button_text')
                    ->label('Type')
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'new',
                        'info' => 'contacted',
                        'primary' => 'qualified',
                        'success' => 'converted',
                        'danger' => 'lost',
                    ]),
                
                Tables\Columns\IconColumn::make('is_replied')
                    ->boolean()
                    ->label('Replied'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->label('Received'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'business_name')
                    ->searchable()
                    ->preload(),
                
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
                    ->label('Replied'),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('reply')
                        ->label('Send Reply')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->form([
                            Forms\Components\Textarea::make('reply_message')
                                ->required()
                                ->rows(5),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'is_replied' => true,
                                'reply_message' => $data['reply_message'],
                                'replied_at' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('Reply sent successfully')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_contacted')
                        ->label('Mark as Contacted')
                        ->icon('heroicon-o-phone')
                        ->action(fn ($records) => $records->each->update(['status' => 'contacted'])),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where(function($q) {
            $businesses = Auth::user()->businesses()->pluck('id');
            $branches = Auth::user()->businesses()->with('branches')->get()
                ->flatMap(fn($b) => $b->branches->pluck('id'));
            
            $q->whereIn('business_id', $businesses)
              ->orWhereIn('business_branch_id', $branches);
        })->where('status', 'new')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
    
    public static function getEloquentQuery(): Builder
    {
        $businesses = Auth::user()->businesses()->pluck('id');
        $branches = Auth::user()->businesses()->with('branches')->get()
            ->flatMap(fn($b) => $b->branches->pluck('id'));
        
        return parent::getEloquentQuery()
            ->where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            });
    }
}
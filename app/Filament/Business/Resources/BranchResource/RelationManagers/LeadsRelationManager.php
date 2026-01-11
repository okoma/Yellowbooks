<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/RelationManagers/LeadsRelationManager.php
// Manage customer leads/inquiries
// ============================================

namespace App\Filament\Business\Resources\BranchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class LeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'leads';
    
    protected static ?string $title = 'Customer Leads';
    
    protected static ?string $icon = 'heroicon-o-user-plus';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lead Information')
                    ->schema([
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
                            ->maxLength(100)
                            ->helperText('e.g., "Book Now", "Get Quote"'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Custom Fields')
                    ->schema([
                        Forms\Components\KeyValue::make('custom_fields')
                            ->label('Additional Information')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),
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
                            ->helperText('Internal notes (not visible to customer)')
                            ->columnSpanFull(),
                        
                        Forms\Components\Toggle::make('is_replied')
                            ->label('Replied to Customer')
                            ->live(),
                        
                        Forms\Components\Textarea::make('reply_message')
                            ->label('Reply Message')
                            ->rows(4)
                            ->maxLength(1000)
                            ->visible(fn (Forms\Get $get) => $get('is_replied'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('client_name')
            ->defaultSort('created_at', 'desc')
            ->columns([
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
                    ->url(fn ($record) => $record->whatsapp ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $record->whatsapp) : null)
                    ->openUrlInNewTab(),
                
                Tables\Columns\TextColumn::make('lead_button_text')
                    ->label('Type')
                    ->badge()
                    ->color('info'),
                
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'converted' => 'Converted',
                        'lost' => 'Lost',
                    ]),
                
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Lead Manually'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('contact')
                        ->label('Mark as Contacted')
                        ->icon('heroicon-o-phone')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => 'contacted']);
                            
                            Notification::make()
                                ->title('Lead marked as contacted')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->status === 'new'),
                    
                    Tables\Actions\Action::make('convert')
                        ->label('Mark as Converted')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => 'converted']);
                            
                            Notification::make()
                                ->title('Lead converted! 🎉')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => in_array($record->status, ['contacted', 'qualified'])),
                    
                    Tables\Actions\Action::make('reply')
                        ->label('Send Reply')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->form([
                            Forms\Components\Textarea::make('reply_message')
                                ->label('Your Reply')
                                ->required()
                                ->rows(5)
                                ->maxLength(1000),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'is_replied' => true,
                                'reply_message' => $data['reply_message'],
                                'replied_at' => now(),
                            ]);
                            
                            // TODO: Send actual email/notification to customer
                            
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
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'contacted'])),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
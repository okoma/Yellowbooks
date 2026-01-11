<?php
// ============================================
// UNIVERSAL LeadsRelationManager
// Use EXACT SAME FILE for both:
// - app/Filament/Admin/Resources/BusinessResource/RelationManagers/LeadsRelationManager.php (NEW)
// - app/Filament/Admin/Resources/BusinessBranchResource/RelationManagers/LeadsRelationManager.php (EXISTING)
// ============================================

namespace App\Filament\Admin\Resources\BusinessResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'leads';
    protected static ?string $title = 'Customer Leads';
    protected static ?string $icon = 'heroicon-o-user-plus';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Lead Information')
                ->schema([
                    Forms\Components\TextInput::make('client_name')
                        ->required()
                        ->maxLength(255),
                    
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
                        ->label('Lead Type')
                        ->maxLength(255)
                        ->helperText('e.g., "Book Now", "Get Quote", "Contact Us"'),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Custom Fields')
                ->schema([
                    Forms\Components\KeyValue::make('custom_fields')
                        ->label('Additional Information')
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
                        ->default('new')
                        ->required()
                        ->native(false),
                    
                    Forms\Components\Toggle::make('is_replied')
                        ->label('Replied'),
                    
                    Forms\Components\Textarea::make('reply_message')
                        ->label('Reply')
                        ->rows(3)
                        ->maxLength(1000)
                        ->visible(fn (Forms\Get $get) => $get('is_replied'))
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('client_name')
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('lead_button_text')
                    ->label('Type')
                    ->badge()
                    ->color('info')
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
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_replied')
                    ->boolean()
                    ->label('Replied')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
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
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('received_from')
                            ->label('Received from'),
                        Forms\Components\DatePicker::make('received_until')
                            ->label('Received until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['received_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['received_until'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Lead'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('mark_contacted')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->action(fn ($record) => $record->update(['status' => 'contacted']))
                    ->visible(fn ($record) => $record->status === 'new'),
                
                Tables\Actions\Action::make('mark_converted')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['status' => 'converted']))
                    ->visible(fn ($record) => in_array($record->status, ['new', 'contacted', 'qualified'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_contacted')
                        ->label('Mark as Contacted')
                        ->icon('heroicon-o-phone')
                        ->color('info')
                        ->action(fn ($records) => $records->each->update(['status' => 'contacted'])),
                    
                    Tables\Actions\BulkAction::make('mark_converted')
                        ->label('Mark as Converted')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'converted'])),
                ]),
            ])
            ->emptyStateHeading('No leads yet')
            ->emptyStateDescription('Customer inquiries and leads will appear here.')
            ->emptyStateIcon('heroicon-o-user-plus');
    }
}
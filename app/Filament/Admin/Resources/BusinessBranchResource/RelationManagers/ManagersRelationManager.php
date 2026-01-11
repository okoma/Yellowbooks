<?php

// ============================================
// app/Filament/Admin/Resources/BusinessBranchResource/RelationManagers/ManagersRelationManager.php
// Location: app/Filament/Admin/Resources/BusinessBranchResource/RelationManagers/ManagersRelationManager.php
// Panel: Admin Panel
// Access: Admins, Moderators
// Purpose: Manage branch managers and their permissions
// ============================================

namespace App\Filament\Admin\Resources\BusinessBranchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ManagersRelationManager extends RelationManager
{
    protected static string $relationship = 'managers';
    protected static ?string $title = 'Branch Managers';
    protected static ?string $icon = 'heroicon-o-user-circle';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Manager Information')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Select a user to assign as manager'),
                    
                    Forms\Components\TextInput::make('position')
                        ->required()
                        ->default('Branch Manager')
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('employee_id')
                        ->maxLength(100),
                    
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20),
                    
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('whatsapp')
                        ->tel()
                        ->maxLength(20)
                        ->prefix('+234'),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Permissions')
                ->description('Control what this manager can do')
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->options([
                            'can_edit_branch' => 'Edit Branch Details',
                            'can_manage_products' => 'Manage Products',
                            'can_respond_to_reviews' => 'Respond to Reviews',
                            'can_view_leads' => 'View Leads',
                            'can_respond_to_leads' => 'Respond to Leads',
                            'can_view_analytics' => 'View Analytics',
                            'can_access_financials' => 'Access Financial Data',
                            'can_manage_staff' => 'Manage Staff',
                        ])
                        ->columns(2)
                        ->gridDirection('row')
                        ->columnSpanFull(),
                ]),
            
            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Manager can access the branch'),
                    
                    Forms\Components\Toggle::make('is_primary')
                        ->label('Primary Manager')
                        ->helperText('Main manager for this branch'),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Manager')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('position')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('permissions')
                    ->label('Permissions')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'None';
                        $count = count(array_filter($state));
                        return "{$count} permission(s)";
                    })
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\IconColumn::make('is_primary')
                    ->boolean()
                    ->label('Primary')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Assigned')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Manager'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['assigned_by'] = auth()->id();
                        $data['assigned_at'] = now();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('make_primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(fn ($record) => $record->makePrimary())
                    ->visible(fn ($record) => !$record->is_primary)
                    ->requiresConfirmation(),
                
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn ($record) => $record->is_active ? $record->deactivate() : $record->activate()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->activate()),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->deactivate()),
                ]),
            ]);
    }
}
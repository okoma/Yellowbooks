<?php
// ============================================
// app/Filament/Business/Resources/BranchResource/RelationManagers/ManagersRelationManager.php
// Assign and manage branch managers with permissions
// ============================================

namespace App\Filament\Business\Resources\BranchResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ManagersRelationManager extends RelationManager
{
    protected static string $relationship = 'managers';
    
    protected static ?string $title = 'Branch Managers';
    
    protected static ?string $icon = 'heroicon-o-user-group';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Manager Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Select User')
                            ->searchable()
                            ->preload()
                            ->options(User::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->helperText('Choose an existing user to assign as manager'),
                        
                        Forms\Components\TextInput::make('position')
                            ->default('Branch Manager')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('employee_id')
                            ->maxLength(50),
                        
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primary Manager')
                            ->helperText('Only one manager can be primary per branch'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('whatsapp')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Permissions')
                    ->description('What can this manager do?')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->options([
                                'can_edit_branch' => 'Edit Branch Information',
                                'can_manage_products' => 'Manage Products/Services',
                                'can_view_leads' => 'View Customer Leads',
                                'can_respond_to_leads' => 'Respond to Leads',
                                'can_respond_to_reviews' => 'Respond to Reviews',
                                'can_view_analytics' => 'View Analytics & Statistics',
                                'can_access_financials' => 'Access Financial Data',
                                'can_manage_staff' => 'Manage Other Staff',
                            ])
                            ->columns(2)
                            ->gridDirection('row')
                            ->default([
                                'can_view_leads' => true,
                                'can_respond_to_leads' => true,
                                'can_respond_to_reviews' => true,
                            ]),
                    ]),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->defaultSort('is_primary', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Manager')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('position')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_primary')
                    ->boolean()
                    ->label('Primary'),
                
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->getStateUsing(fn ($record) => count(array_filter($record->permissions ?? [])))
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                
                Tables\Columns\TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->label('Assigned'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only'),
                
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Manager'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Assign Manager')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['assigned_by'] = Auth::id();
                        $data['assigned_at'] = now();
                        return $data;
                    })
                    ->after(function ($record) {
                        // Update user's manager status
                        $record->user->updateManagerStatus();
                        
                        Notification::make()
                            ->title('Manager assigned successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('make_primary')
                        ->label('Make Primary')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->makePrimary();
                            
                            Notification::make()
                                ->title('Primary manager updated')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => !$record->is_primary),
                    
                    Tables\Actions\Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->deactivate();
                            $record->user->updateManagerStatus();
                            
                            Notification::make()
                                ->title('Manager deactivated')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->is_active),
                    
                    Tables\Actions\Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->activate();
                            $record->user->updateManagerStatus();
                            
                            Notification::make()
                                ->title('Manager activated')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => !$record->is_active),
                    
                    Tables\Actions\DeleteAction::make()
                        ->after(function ($record) {
                            $record->user->updateManagerStatus();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->activate();
                                $record->user->updateManagerStatus();
                            });
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->deactivate();
                                $record->user->updateManagerStatus();
                            });
                        }),
                ]),
            ]);
    }
}
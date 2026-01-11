<?php
// ============================================
// OfficialsRelationManager
// For BusinessResource or BusinessBranchResource
// ============================================

namespace App\Filament\Admin\Resources\BusinessResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OfficialsRelationManager extends RelationManager
{
    protected static string $relationship = 'officials';
    protected static ?string $title = 'Team Members';
    protected static ?string $icon = 'heroicon-o-user-group';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Personal Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->label('Full Name'),
                    
                    Forms\Components\TextInput::make('position')
                        ->required()
                        ->maxLength(255)
                        ->label('Job Title')
                        ->helperText('e.g., CEO, General Manager, Head of Sales'),
                    
                    Forms\Components\FileUpload::make('photo')
                        ->image()
                        ->directory('team-members')
                        ->maxSize(2048)
                        ->imageEditor()
                        ->helperText('Team member photo'),
                    
                    Forms\Components\TextInput::make('order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Display order (lower numbers appear first)'),
                    
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Hide inactive team members from public display')
                        ->inline(false),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Social Media Accounts')
                ->description('Connect social media profiles for this team member')
                ->schema([
                    Forms\Components\KeyValue::make('social_accounts')
                        ->label('Social Links')
                        ->keyLabel('Platform')
                        ->valueLabel('URL')
                        ->helperText('Add social media profiles (e.g., LinkedIn, Twitter, Instagram)')
                        ->addActionLabel('Add Social Link')
                        ->reorderable()
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn($query) => $query->with(['business', 'businessBranch']))
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('position')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->social_accounts ? count($record->social_accounts) . ' social link(s)' : 'No social links'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('social_accounts')
                    ->label('Social Media')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'None';
                        $platforms = array_keys($state);
                        return implode(', ', array_map('ucfirst', $platforms));
                    })
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All team members')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Team Member'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No team members yet')
            ->emptyStateDescription('Add key people like CEO, managers, or team leaders.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->reorderable('order');
    }
}
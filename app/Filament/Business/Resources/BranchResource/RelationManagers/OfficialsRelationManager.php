<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/RelationManagers/OfficialsRelationManager.php
// Manage business team members/officials
// ============================================

namespace App\Filament\Business\Resources\BranchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OfficialsRelationManager extends RelationManager
{
    protected static string $relationship = 'officials';
    
    protected static ?string $title = 'Team Members';
    
    protected static ?string $icon = 'heroicon-o-users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Official Information')
                    ->schema([
                        Forms\Components\FileUpload::make('photo')
                            ->image()
                            ->directory('official-photos')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->avatar()
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('position')
                            ->required()
                            ->maxLength(255)
                            ->helperText('e.g., CEO, Manager, Chef, etc.'),
                        
                        Forms\Components\TextInput::make('order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Show/hide on public profile'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Social Media Accounts')
                    ->description('Add social media profiles for this team member')
                    ->schema([
                        Forms\Components\Repeater::make('social_accounts')
                            ->schema([
                                Forms\Components\Select::make('platform')
                                    ->options([
                                        'linkedin' => 'LinkedIn',
                                        'twitter' => 'Twitter (X)',
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                        'youtube' => 'YouTube',
                                        'tiktok' => 'TikTok',
                                        'github' => 'GitHub',
                                        'website' => 'Personal Website',
                                    ])
                                    ->required(),
                                
                                Forms\Components\TextInput::make('url')
                                    ->url()
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Social Account')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['platform'] ?? null),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('order', 'asc')
            ->reorderable('order')
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
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('social_accounts_count')
                    ->label('Social Links')
                    ->getStateUsing(fn ($record) => $record->social_accounts ? count($record->social_accounts) : 0)
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->label('Order'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only'),
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
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ]);
    }
}
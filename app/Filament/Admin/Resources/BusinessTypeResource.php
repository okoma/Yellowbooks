<?php
// ============================================
// 2. BusinessTypeResource.php
// Location: app/Filament/Admin/Resources/BusinessTypeResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Manage business types (Restaurant, Hotel, Hospital, etc.)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessTypeResource\Pages;
use App\Models\BusinessType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BusinessTypeResource extends Resource
{
    protected static ?string $model = BusinessType::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Business Types';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                            $set('slug', Str::slug($state))
                        )
                        ->helperText('e.g., Restaurant, Hotel, Hospital'),
                    
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    
                    Forms\Components\TextInput::make('icon')
                        ->maxLength(100)
                        ->helperText('Icon class (e.g., heroicon-o-building-office)'),
                    
                    Forms\Components\TextInput::make('order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Display order'),
                    
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Lead Form Configuration')
                ->description('Configure lead form fields for this business type')
                ->schema([
                    Forms\Components\KeyValue::make('lead_form_fields')
                        ->label('Custom Form Fields')
                        ->helperText('Additional fields for lead forms (e.g., {"check_in_date": "date", "number_of_guests": "number"})')
                        ->columnSpanFull(),
                    
                    Forms\Components\KeyValue::make('lead_button_options')
                        ->label('Lead Button Options')
                        ->helperText('Available lead buttons (e.g., {"book_now": "Book Now", "get_quote": "Get Quote"})')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description),
                
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('categories_count')
                    ->counts('categories')
                    ->label('Categories')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('businesses_count')
                    ->label('Businesses')
                    ->getStateUsing(function ($record) {
                        return $record->categories->sum(fn ($cat) => $cat->businesses->count());
                    })
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
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
            ->reorderable('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessTypes::route('/'),
            'create' => Pages\CreateBusinessType::route('/create'),
            'edit' => Pages\EditBusinessType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}

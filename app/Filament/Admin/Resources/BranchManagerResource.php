<?php
// ============================================
// 1. BranchManagerResource.php (System-wide)
// Location: app/Filament/Admin/Resources/BranchManagerResource.php
// Panel: Admin Panel - Access: Admins
// Purpose: System-wide branch manager management
// ============================================
namespace App\Filament\Admin\Resources;
use App\Filament\Admin\Resources\BranchManagerResource\Pages;
use App\Models\BranchManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class BranchManagerResource extends Resource
{
    protected static ?string $model = BranchManager::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Branch Managers';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Manager Assignment')->schema([
                Forms\Components\Select::make('business_branch_id')
                    ->label('Branch')
                    ->relationship(
                        name: 'branch',
                        titleAttribute: 'branch_title',
                        modifyQueryUsing: fn ($query) => $query->with('business')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->business->business_name} - {$record->branch_title}")
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('user_id')->relationship('user', 'name')->required()->searchable()->preload(),
                Forms\Components\TextInput::make('position')->default('Branch Manager')->required()->maxLength(255),
                Forms\Components\TextInput::make('employee_id')->maxLength(100),
            ])->columns(2),
            Forms\Components\Section::make('Contact')->schema([
                Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
                Forms\Components\TextInput::make('email')->email()->maxLength(255),
                Forms\Components\TextInput::make('whatsapp')->tel()->maxLength(20)->prefix('+234'),
            ])->columns(3),
            Forms\Components\Section::make('Permissions')->schema([
                Forms\Components\CheckboxList::make('permissions')->options([
                    'can_edit_branch' => 'Edit Branch Details',
                    'can_manage_products' => 'Manage Products',
                    'can_respond_to_reviews' => 'Respond to Reviews',
                    'can_view_leads' => 'View Leads',
                    'can_respond_to_leads' => 'Respond to Leads',
                    'can_view_analytics' => 'View Analytics',
                    'can_access_financials' => 'Access Financials',
                    'can_manage_staff' => 'Manage Staff',
                ])->columns(2)->gridDirection('row')->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
                Forms\Components\Toggle::make('is_primary')->label('Primary Manager'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')->searchable()->sortable()->url(fn ($r) => route('filament.admin.resources.users.view', $r->user)),
            Tables\Columns\TextColumn::make('branch.business.business_name')->label('Business')->searchable()->limit(30),
            Tables\Columns\TextColumn::make('branch.branch_title')->label('Branch')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('position')->searchable(),
            Tables\Columns\TextColumn::make('permissions')->formatStateUsing(fn ($s) => $s ? count(array_filter($s)) . ' perms' : '0')->badge()->color('info'),
            Tables\Columns\IconColumn::make('is_primary')->boolean()->label('Primary'),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active')->sortable(),
            Tables\Columns\TextColumn::make('assigned_at')->dateTime()->sortable()->toggleable(),
        ])->defaultSort('created_at', 'desc')->filters([
            Tables\Filters\TernaryFilter::make('is_active'),
            Tables\Filters\TernaryFilter::make('is_primary'),
        ])->actions([
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')->label(fn ($r) => $r->is_active ? 'Deactivate' : 'Activate')->icon(fn ($r) => $r->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')->color(fn ($r) => $r->is_active ? 'danger' : 'success')->action(fn ($r) => $r->is_active ? $r->deactivate() : $r->activate()),
                Tables\Actions\DeleteAction::make(),
            ]),
        ])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
    public static function getPages(): array { return ['index' => Pages\ListBranchManagers::route('/'), 'create' => Pages\CreateBranchManager::route('/create'), 'edit' => Pages\EditBranchManager::route('/{record}/edit'), 'view' => Pages\ViewBranchManager::route('/{record}')]; }
}
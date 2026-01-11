<?php
// ============================================
// 2. ManagerInvitationResource.php
// Location: app/Filament/Admin/Resources/ManagerInvitationResource.php
// Panel: Admin Panel - Access: Admins, Moderators
// ============================================
namespace App\Filament\Admin\Resources;
use App\Filament\Admin\Resources\ManagerInvitationResource\Pages;
use App\Models\ManagerInvitation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ManagerInvitationResource extends Resource
{
    protected static ?string $model = ManagerInvitation::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Manager Invitations';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('business_branch_id')->relationship('branch', 'branch_title')->required()->searchable()->preload(),
                Forms\Components\Select::make('invited_by')->relationship('inviter', 'name')->disabled(),
                Forms\Components\TextInput::make('email')->email()->required()->maxLength(255),
                Forms\Components\TextInput::make('position')->default('Branch Manager')->required()->maxLength(255),
                Forms\Components\Select::make('status')->options(['pending' => 'Pending', 'accepted' => 'Accepted', 'declined' => 'Declined', 'expired' => 'Expired'])->required()->native(false),
                Forms\Components\DateTimePicker::make('expires_at')->required()->native(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('branch.business.business_name')->label('Business')->searchable()->limit(20),
            Tables\Columns\TextColumn::make('branch.branch_title')->label('Branch')->searchable(),
            Tables\Columns\TextColumn::make('position'),
            Tables\Columns\TextColumn::make('inviter.name')->label('Invited By')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge()->colors(['warning' => 'pending', 'success' => 'accepted', 'danger' => 'declined', 'gray' => 'expired'])->formatStateUsing(fn ($s) => ucfirst($s)),
            Tables\Columns\TextColumn::make('expires_at')->dateTime()->sortable()->since(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('created_at', 'desc')->filters([
            Tables\Filters\SelectFilter::make('status')->options(['pending' => 'Pending', 'accepted' => 'Accepted', 'declined' => 'Declined', 'expired' => 'Expired'])->multiple(),
        ])->actions([
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resend')->icon('heroicon-o-arrow-path')->color('info')->action(fn ($r) => $r->resend())->visible(fn ($r) => $r->status === 'pending'),
                Tables\Actions\DeleteAction::make(),
            ]),
        ])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
    public static function getPages(): array { return ['index' => Pages\ListManagerInvitations::route('/'), 'create' => Pages\CreateManagerInvitation::route('/create'), 'edit' => Pages\EditManagerInvitation::route('/{record}/edit'), 'view' => Pages\ViewManagerInvitation::route('/{record}')]; }
    public static function getNavigationBadge(): ?string { $pending = static::getModel()::where('status', 'pending')->where('expires_at', '>', now())->count(); return $pending > 0 ? (string) $pending : null; }
}

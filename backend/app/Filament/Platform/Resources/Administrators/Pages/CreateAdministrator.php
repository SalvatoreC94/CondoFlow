<?php

namespace App\Filament\Platform\Resources\Administrators\Pages;

use App\Enums\UserStatus;
use App\Filament\Platform\Resources\Administrators\AdministratorResource;
use App\Notifications\UserInvited;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateAdministrator extends CreateRecord
{
    protected static string $resource = AdministratorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = UserStatus::Invited;
        $data['invitation_token'] = Str::random(48);
        $data['invitation_expires_at'] = now()->addDays(7);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->notify(new UserInvited($this->record->invitation_token));
    }
}

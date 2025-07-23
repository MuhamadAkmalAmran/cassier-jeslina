<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;

class EditProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.edit-profile';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Field Nama
                $this->getNameFormComponent(),

                // Field Email
                $this->getEmailFormComponent(),

                // Field Password
                $this->getPasswordFormComponent(),

                // Field Konfirmasi Password
                $this->getPasswordConfirmationFormComponent(),

                // PERHATIKAN: Kita TIDAK menyertakan field 'role' di sini.
            ]);
    }
}

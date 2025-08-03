<?php

namespace App\Filament\Pages;

// Gunakan base class dari Filament untuk halaman Edit Profile

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    /**
     * Override method form untuk mendefinisikan field kita sendiri.
     * Ini memastikan field 'role' tidak akan pernah muncul.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    /**
     * Override untuk komponen password utama agar selalu kosong.
     */
    protected function getPasswordFormComponent(): Component
    {
        $component = parent::getPasswordFormComponent();

        $component->afterStateHydrated(function (TextInput $component) {
            $component->state(null);
        });

        return $component;
    }

    /**
     * Override untuk komponen konfirmasi password untuk memastikan ia selalu tampil.
     * Kita tidak perlu mengubah perilakunya, cukup panggil dan kembalikan.
     */
    protected function getPasswordConfirmationFormComponent(): Component
    {
        $component = parent::getPasswordConfirmationFormComponent();
        $component->visible(true);
        // Cukup panggil method asli dari parent class dan kembalikan hasilnya.
        return $component;
    }
}

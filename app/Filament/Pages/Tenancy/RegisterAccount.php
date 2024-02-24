<?php

namespace App\Filament\Pages\Tenancy;
 
use App\Models\Account;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;
 
class RegisterAccount extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register Your Account(Company)';
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                // ...
            ]);
    }
    
    protected function handleRegistration(array $data): Account
    {
        $account = Account::create($data);
        
        $account->members()->attach(auth()->user());
        
        return $account;
    }
}
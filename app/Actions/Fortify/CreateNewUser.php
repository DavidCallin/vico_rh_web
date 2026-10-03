<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'login' => $this->uniqueLogin($input['email'], $input['name']),
            'status' => 'active',
        ]);
    }

    /**
     * La colonne users.login est obligatoire et unique, alors que le
     * formulaire Fortify ne la demande pas.
     */
    private function uniqueLogin(string $email, string $name): string
    {
        $base = Str::slug(Str::before($email, '@'));

        if ($base === '') {
            $base = Str::slug($name);
        }

        if ($base === '') {
            $base = 'user';
        }

        $base = Str::limit($base, 40, '');
        $login = $base;
        $suffix = 1;

        while (User::query()->where('login', $login)->exists()) {
            $login = $base.$suffix;
            $suffix++;
        }

        return $login;
    }
}

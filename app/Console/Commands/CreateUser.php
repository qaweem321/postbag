<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'postbag:create-user {email} {--name=} {--password= : Omit to be asked}';

    protected $description = 'Create a dashboard user';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $password = (string) ($this->option('password') ?: $this->secret('Password (at least 10 characters)'));
        $validator = Validator::make(compact('email', 'password'), [
            'email' => ['required', 'email:rfc', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $this->option('name') ?: strstr($email, '@', true),
            'email' => $email,
            'password' => $password,
        ]);
        $this->info("Created {$email}. Log in at ".url('/login'));

        return self::SUCCESS;
    }
}

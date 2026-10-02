<?php

namespace App\Console\Commands;

use App\Actions\Access\SyncPermissions;
use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('user:create-admin {--name= : Display name} {--email= : E-mail address} {--password= : Password (asked for when omitted)}')]
#[Description('Create a verified user with the admin role, or promote an existing one')]
class CreateAdminCommand extends Command
{
    public function handle(SyncPermissions $syncPermissions): int
    {
        $syncPermissions->handle();

        $email = $this->option('email') ?: text('E-mail', required: true);
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $input = [
                'name' => $this->option('name') ?: text('Name', required: true),
                'email' => $email,
                'password' => $this->option('password') ?: password('Password', required: true),
            ];

            $validator = Validator::make($input, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', Password::default()],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->components->error($error);
                }

                return self::FAILURE;
            }

            $user = new User($input);
            $user->email_verified_at = now();
            $user->save();
        }

        $user->assignRole(SystemRole::Admin->value);

        $this->components->info("{$user->email} is an admin now.");

        return self::SUCCESS;
    }
}

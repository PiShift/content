<?php

namespace PiShift\Content\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Creates the first admin account of a site (or resets a user's password), on the user
 * model of the admin guard.
 *
 * @internal Not part of the public API; may change in any release.
 */
class UserCommand extends Command
{
    protected $signature = 'content:user {email : Signs in with this address} {--name= : Display name (defaults to the part before @)} {--password= : Skips the prompt; avoid on shared machines, it stays in the shell history}';

    protected $description = 'Create an admin user, or set the password of an existing one';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = $this->option('password') ?? $this->secret('Password (at least 8 characters)');

        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $guard = config('content.admin.guard', 'web');
        /** @var class-string<Model> $model */
        $model = config('auth.providers.'.config("auth.guards.{$guard}.provider").'.model');

        $user = $model::query()->firstOrNew(['email' => $email]);
        $created = ! $user->exists;

        $user->forceFill([
            'name' => $this->option('name') ?? ($user->getAttribute('name') ?: strstr($email, '@', true)),
            'password' => Hash::make($password),
        ])->save();

        $this->components->info($created ? "User {$email} created." : "Password of {$email} updated.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class FixUserDataTypes extends Command
{
    protected $signature = 'users:fix-data-types';
    protected $description = 'Fix user data types in database';

    public function handle()
    {
        $this->info('Fixing user data types...');

        $users = User::all();
        $fixed = 0;

        foreach ($users as $user) {
            $updates = [];

            // Fix login_count
            if (!is_numeric($user->login_count)) {
                $updates['login_count'] = 0;
            } else {
                $updates['login_count'] = (int) $user->login_count;
            }

            // Fix otp_attempts
            if (!is_numeric($user->otp_attempts)) {
                $updates['otp_attempts'] = 0;
            } else {
                $updates['otp_attempts'] = (int) $user->otp_attempts;
            }

            // Fix otp_resend_count
            if (!is_numeric($user->otp_resend_count)) {
                $updates['otp_resend_count'] = 0;
            } else {
                $updates['otp_resend_count'] = (int) $user->otp_resend_count;
            }

            if (!empty($updates)) {
                $user->update($updates);
                $fixed++;
            }
        }

        $this->info("Fixed {$fixed} user records.");
        return 0;
    }
}

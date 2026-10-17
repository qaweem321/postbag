<?php

namespace App\Console\Commands;

use App\Models\MailingList;
use App\Models\User;
use Illuminate\Console\Command;

/** Used by `make wordpress` to connect the demo WordPress site. Local environments only. */
class DemoToken extends Command
{
    protected $signature = 'postbag:demo-token {--list : Print the first list\'s ID instead}';

    protected $description = 'Print an API token (or a list ID) for the demo WordPress site';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Only available in the local environment.');

            return self::FAILURE;
        }
        if ($this->option('list')) {
            $this->line((string) MailingList::query()->orderBy('id')->value('uuid'));

            return self::SUCCESS;
        }
        $user = User::query()->orderBy('id')->firstOrFail();
        $this->line($user->createToken('Demo WordPress site', ['read', 'write'])->plainTextToken);

        return self::SUCCESS;
    }
}

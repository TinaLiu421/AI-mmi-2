<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\TokenService;

/**
 * One-time command: credit existing active subscribers with token equivalents
 * of what they originally paid, so they keep value after the system migration.
 *
 * Conversion rates (old price → token credit):
 *   free        →    0 tokens
 *   all_ai      →   60 tokens  ($12 ÷ $0.20)
 *   hybrid      →  495 tokens  ($99 ÷ $0.20)
 *   premium     → 1900 tokens  (full plan cost, honour the access they bought)
 *   vip         → 4900 tokens  (full plan cost)
 *   application →    0 tokens  (education plan, not migrated)
 *
 * Safety: only credits members who haven't already received a conversion credit
 * (checked via token_transactions type = 'earn_admin_grant' with notes LIKE 'sub_conversion:%')
 */
class ConvertSubscribersToTokens extends Command
{
    protected $signature   = 'tokens:convert-subscribers {--dry-run : Preview changes without writing to DB}';
    protected $description = 'Credit existing active subscribers with token equivalents of their paid plan';

    // How many tokens each plan code is worth
    private const PLAN_TOKEN_CREDIT = [
        'all_ai'  => 60,
        'hybrid'  => 495,
        'premium' => 1900,
        'vip'     => 4900,
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $svc    = new TokenService();

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be written.');
        }

        // Get all active subscriptions for migration-domain plans that have a token credit value
        $planCodes = array_keys(self::PLAN_TOKEN_CREDIT);
        $subs = DB::table('subscriptions as s')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->join('member as m', 'm.id', '=', 's.member_id')
            ->where('s.status', 'active')
            ->where(function ($q) {
                $q->whereNull('s.ends_at')->orWhere('s.ends_at', '>', now());
            })
            ->whereIn('p.code', $planCodes)
            ->select('s.id as sub_id', 's.member_id', 'p.code as plan_code', 'm.email', 's.ends_at')
            ->orderBy('s.member_id')
            ->get();

        if ($subs->isEmpty()) {
            $this->info('No active subscribers found to convert.');
            return 0;
        }

        $this->info("Found {$subs->count()} active subscription(s) to process:");
        $this->table(
            ['Sub ID', 'Member ID', 'Email', 'Plan', 'Ends At'],
            $subs->map(fn($r) => [$r->sub_id, $r->member_id, $r->email, $r->plan_code, $r->ends_at ?? 'no expiry'])
        );

        if (!$dryRun && !$this->confirm('Proceed with crediting tokens?', true)) {
            $this->info('Aborted.');
            return 0;
        }

        // Deduplicate by member + plan (a member might have multiple active rows)
        $seen = [];
        $credited = 0;

        foreach ($subs as $sub) {
            $key = "{$sub->member_id}_{$sub->plan_code}";
            if (isset($seen[$key])) {
                $this->line("  Skipping duplicate sub #{$sub->sub_id} for member #{$sub->member_id} ({$sub->plan_code}) — already processed in this run.");
                continue;
            }
            $seen[$key] = true;

            // Check if already converted (idempotent guard)
            $alreadyConverted = DB::table('token_transactions')
                ->where('member_id', $sub->member_id)
                ->where('type', 'earn_admin_grant')
                ->where('notes', 'like', "sub_conversion:{$sub->plan_code}%")
                ->exists();

            if ($alreadyConverted) {
                $this->line("  Member #{$sub->member_id} ({$sub->email}) already converted for {$sub->plan_code} — skipping.");
                continue;
            }

            $tokens = self::PLAN_TOKEN_CREDIT[$sub->plan_code] ?? 0;
            $this->line("  → Member #{$sub->member_id} ({$sub->email}): +{$tokens} tokens for {$sub->plan_code}");

            if (!$dryRun) {
                $svc->earn(
                    (int) $sub->member_id,
                    $tokens,
                    TokenService::EARN_ADMIN_GRANT,
                    'subscriptions',
                    (int) $sub->sub_id,
                    "sub_conversion:{$sub->plan_code} — token equivalent of existing paid plan"
                );
            }

            $credited++;
        }

        $this->info($dryRun
            ? "DRY RUN complete. Would have credited {$credited} member(s)."
            : "Done. Credited {$credited} member(s) with tokens.");

        return 0;
    }
}

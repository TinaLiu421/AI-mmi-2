<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

/**
 * Spotlight_Queue — manages paid spotlight slots.
 *
 * Table: app_spotlight_queue (prefix applied by Laravel)
 * Statuses: pending_payment | queued | active | expired | cancelled
 */
class Spotlight_Queue extends BaseModel
{
    protected $_table = 'spotlight_queue';
    protected $_posts_table = 'member_posts';

    const PRICE_ID = 'price_1TJ9PuKcbpMSEKkQvT455ckz';
    const SLOT_LIMIT = 3;
    const SLOT_DAYS  = 7;

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    // ------------------------------------------------------------------ //
    // Read helpers
    // ------------------------------------------------------------------ //

    /** Count posts currently live in spotlight. */
    public function getActiveCount(): int
    {
        return (int) DB::table($this->_table)
            ->where('status', 'active')
            ->where('scheduled_end', '>', now())
            ->count();
    }

    /** IDs of posts currently in active spotlight (still on the clock). */
    public function getActivePostIds(): array
    {
        return DB::table($this->_table)
            ->where('status', 'active')
            ->where('scheduled_end', '>', now())
            ->pluck('posts_id')
            ->map(fn($v) => (int)$v)
            ->toArray();
    }

    /**
     * All active + queued entries for a given member, newest first.
     * Returns array of plain arrays.
     */
    public function getQueuedForMember(int $member_id): array
    {
        $t  = $this->_table;       // spotlight_queue  → prefixed by Laravel
        $pt = $this->_posts_table; // member_posts     → prefixed by Laravel
        $pfx = $this->_db_prefix;  // e.g. 'app_'

        $rows = DB::table($t)
            ->join($pt, $pt . '.id', '=', $t . '.posts_id')
            ->whereIn($t . '.status', ['active', 'queued', 'pending_payment'])
            ->where($t . '.member_id', $member_id)
            ->orderByRaw("FIELD(`{$pfx}{$t}`.`status`,'active','queued','pending_payment')")
            ->orderBy($t . '.paid_at', 'asc')
            ->select([
                $t . '.id',
                $t . '.posts_id',
                $t . '.status',
                $t . '.queue_position',
                $t . '.scheduled_start',
                $t . '.scheduled_end',
                $t . '.paid_at',
                $t . '.activated_at',
                $pt . '.title',
                $pt . '.photo',
                $pt . '.youtube_url',
            ])
            ->get();

        return array_map(fn($r) => (array)$r, $rows->toArray());
    }

    /**
     * Get all queue entries across all members, ordered by payment time.
     * (Earlier payment → higher priority.)
     */
    public function getQueue(): array
    {
        return DB::table($this->_table)
            ->where('status', 'queued')
            ->orderBy('paid_at', 'asc')
            ->get()
            ->map(fn($r) => (array)$r)
            ->toArray();
    }

    /**
     * Preview: when would $qty new slots open up?
     * Returns array like [['start'=>timestamp,'end'=>timestamp], ...]
     */
    public function getSchedulePreview(int $qty): array
    {
        // Active entries ordered by soonest expiry
        $active = DB::table($this->_table)
            ->where('status', 'active')
            ->where('scheduled_end', '>', now())
            ->orderBy('scheduled_end', 'asc')
            ->pluck('scheduled_end')
            ->toArray();

        // Build a timeline of when slots become available
        // Available slots = SLOT_LIMIT − active_count
        $slot_times = []; // unix timestamps when a slot opens

        $now_ts = time();

        // Fill array with expiry times of active slots
        $slot_pool = array_map('strtotime', $active);
        // Sort ascending (soonest first)
        sort($slot_pool);

        // For each queued entry (in order), the slot they will take opens when
        // the earliest currently-free + earliest future slot becomes free.
        // Simple model: we "allocate" starting from free slots.
        $free_slots = max(0, self::SLOT_LIMIT - count($active));
        $upcoming_openings = array_values($slot_pool); // expiry times of active slots

        // Combine with queued scheduled_ends (they don't exist yet, so ignore)
        // We only simulate from free_slots + upcoming_openings
        $timeline = [];
        // First $free_slots slots are available immediately (now)
        for ($i = 0; $i < $free_slots; $i++) {
            $timeline[] = $now_ts;
        }
        // Then each active slot frees up at its scheduled_end
        foreach ($upcoming_openings as $expiry_ts) {
            $timeline[] = max($expiry_ts, $now_ts);
        }
        // Then any queued-ahead slots (they block our $qty slots by their 7-day term)
        $queued_count = count(DB::table($this->_table)
            ->where('status', 'queued')
            ->orderBy('paid_at', 'asc')
            ->get()
            ->toArray());

        // For each queued ahead, extend timeline by SLOT_DAYS after their predicted start
        // Recompute from scratch for clarity
        $full_timeline = array_slice($timeline, 0, self::SLOT_LIMIT + $queued_count + $qty + 10);
        while (count($full_timeline) < self::SLOT_LIMIT + $queued_count + $qty + 1) {
            // If more needed, assume last expiry + SLOT_DAYS
            $last = !empty($full_timeline) ? end($full_timeline) : $now_ts;
            $full_timeline[] = $last + self::SLOT_DAYS * 86400;
        }

        $result = [];
        for ($i = 0; $i < $qty; $i++) {
            $idx = $queued_count + $i;
            $start = isset($full_timeline[$idx]) ? (int)$full_timeline[$idx] : $now_ts;
            $end   = $start + self::SLOT_DAYS * 86400;
            $result[] = ['start' => $start, 'end' => $end];
        }

        return $result;
    }

    // ------------------------------------------------------------------ //
    // Lifecycle
    // ------------------------------------------------------------------ //

    /**
     * Mark entries whose scheduled_end has passed as expired, and clear
     * featured_until on the corresponding post.
     */
    public function expireActive(): void
    {
        $expired = DB::table($this->_table)
            ->where('status', 'active')
            ->where('scheduled_end', '<=', now())
            ->get();

        foreach ($expired as $entry) {
            DB::table($this->_table)
                ->where('id', $entry->id)
                ->update([
                    'status'     => 'expired',
                    'expired_at' => now(),
                    'updated_at' => now(),
                ]);

            // Clear featured_until on the post
            DB::table($this->_posts_table)
                ->where('id', $entry->posts_id)
                ->update(['featured_until' => null]);
        }
    }

    /**
     * Activate the next queued entries to fill open slots.
     */
    public function activateNext(): void
    {
        $active_count = $this->getActiveCount();
        $open_slots   = self::SLOT_LIMIT - $active_count;

        if ($open_slots <= 0) {
            return;
        }

        $next_entries = DB::table($this->_table)
            ->where('status', 'queued')
            ->orderBy('paid_at', 'asc')
            ->limit($open_slots)
            ->get();

        foreach ($next_entries as $entry) {
            $start = now();
            $end   = now()->copy()->addDays(self::SLOT_DAYS);

            DB::table($this->_table)
                ->where('id', $entry->id)
                ->update([
                    'status'         => 'active',
                    'activated_at'   => $start,
                    'scheduled_start'=> $start,
                    'scheduled_end'  => $end,
                    'updated_at'     => now(),
                ]);

            // Set featured_until on the post
            DB::table($this->_posts_table)
                ->where('id', $entry->posts_id)
                ->update(['featured_until' => $end->toDateTimeString()]);
        }

        // Renumber remaining queued entries now that some were promoted
        if (count($next_entries) > 0) {
            $this->reassignPositions();
        }
    }

    /**
     * Called by webhook on successful payment.
     * Moves pending_payment entries to queued, records paid_at, assigns position.
     * Then tries to immediately activate any newly queued entries into open slots.
     *
     * @param int    $member_id
     * @param array  $post_ids       Array of posts_id integers
     * @param string $session_id     Stripe checkout session id
     * @param string $paid_at_str    Timestamp string (webhook event created)
     */
    public function onPaymentReceived(int $member_id, array $post_ids, string $session_id, string $paid_at_str): void
    {
        // idempotency: skip if session already processed
        $already = DB::table($this->_table)
            ->where('stripe_session_id', $session_id)
            ->where('status', '!=', 'pending_payment')
            ->exists();

        if ($already) {
            return;
        }

        $paid_at = $paid_at_str;

        foreach ($post_ids as $posts_id) {
            $posts_id = (int)$posts_id;
            if ($posts_id <= 0) continue;

            // Find the pending_payment record (created at checkout initiation)
            $entry = DB::table($this->_table)
                ->where('member_id', $member_id)
                ->where('posts_id', $posts_id)
                ->where('stripe_session_id', $session_id)
                ->where('status', 'pending_payment')
                ->first();

            if ($entry) {
                DB::table($this->_table)
                    ->where('id', $entry->id)
                    ->update([
                        'status'     => 'queued',
                        'paid_at'    => $paid_at,
                        'updated_at' => now(),
                    ]);
            }
        }

        // Assign queue positions (re-number all queued by paid_at)
        $this->reassignPositions();

        // Immediately activate if slots are free
        $this->activateNext();
    }

    /** Renumber queue_position for all queued entries by paid_at order. */
    public function reassignPositions(): void
    {
        $queued = DB::table($this->_table)
            ->where('status', 'queued')
            ->orderBy('paid_at', 'asc')
            ->orderBy('id', 'asc')
            ->select('id')
            ->get();

        $pos = 1;
        foreach ($queued as $q) {
            DB::table($this->_table)
                ->where('id', $q->id)
                ->update(['queue_position' => $pos++, 'updated_at' => now()]);
        }
    }

    /**
     * Create a pending_payment entry (called at checkout initiation).
     * Returns the inserted id.
     */
    public function createPending(int $member_id, int $posts_id, string $session_id): int
    {
        return (int) DB::table($this->_table)->insertGetId([
            'member_id'        => $member_id,
            'posts_id'         => $posts_id,
            'stripe_session_id'=> $session_id,
            'status'           => 'pending_payment',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Check whether a given post is already in an active, queued, or pending
     * spotlight entry. Includes pending_payment so duplicate checkouts via
     * direct POST are blocked at the controller level.
     */
    public function isAlreadySpotlighted(int $posts_id): bool
    {
        return DB::table($this->_table)
            ->where('posts_id', $posts_id)
            ->whereIn('status', ['active', 'queued', 'pending_payment'])
            ->exists();
    }

    /**
     * Cancel a single pending_payment entry that belongs to $member_id.
     * Returns true if a row was actually updated.
     */
    public function cancelPending(int $member_id, int $sq_id): bool
    {
        return (bool) DB::table($this->_table)
            ->where('id', $sq_id)
            ->where('member_id', $member_id)
            ->where('status', 'pending_payment')
            ->update(['status' => 'cancelled', 'updated_at' => now()]);
    }

    /**
     * Fetch a single pending_payment entry that belongs to $member_id.
     * Returns the row as an array, or null.
     */
    public function getPendingEntry(int $member_id, int $sq_id): ?array
    {
        $row = DB::table($this->_table)
            ->where('id', $sq_id)
            ->where('member_id', $member_id)
            ->where('status', 'pending_payment')
            ->first();
        return $row ? (array)$row : null;
    }

    /**
     * Admin overview: all active + queued + pending entries across all members,
     * joined with member alias and post title.
     */
    public function getAdminOverview(): array
    {
        $t  = $this->_table;
        $pt = $this->_posts_table;
        $mt = 'member';

        $rows = DB::table($t)
            ->join($pt, $pt . '.id', '=', $t . '.posts_id')
            ->join($mt, $mt . '.id', '=', $t . '.member_id')
            ->whereIn($t . '.status', ['active', 'queued', 'pending_payment'])
            ->orderByRaw("FIELD(`{$this->_db_prefix}{$t}`.`status`,'active','queued','pending_payment')")
            ->orderBy($t . '.paid_at', 'asc')
            ->select([
                $t . '.id',
                $t . '.member_id',
                $t . '.posts_id',
                $t . '.status',
                $t . '.queue_position',
                $t . '.scheduled_start',
                $t . '.scheduled_end',
                $t . '.paid_at',
                $t . '.activated_at',
                $pt . '.title as post_title',
                $mt . '.alias_name as member_name',
                $mt . '.email as member_email',
            ])
            ->get();

        return array_map(fn($r) => (array)$r, $rows->toArray());
    }

    /**
     * Admin force-cancel any active/queued/pending entry by ID.
     */
    public function adminCancel(int $sq_id): bool
    {
        $entry = DB::table($this->_table)->where('id', $sq_id)->first();
        if (!$entry) return false;

        DB::table($this->_table)
            ->where('id', $sq_id)
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        // If it was active, clear featured_until on the post
        if ($entry->status === 'active') {
            DB::table($this->_posts_table)
                ->where('id', $entry->posts_id)
                ->update(['featured_until' => null]);
            // Activate next in queue
            $this->activateNext();
        }

        $this->reassignPositions();
        return true;
    }
}

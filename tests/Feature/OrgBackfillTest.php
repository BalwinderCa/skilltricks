<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\AI\DocumentContextService;
use Database\Migrations\BackfillRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrgBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    /** Re-run the backfill against users created after migrations ran. */
    private function runBackfill(): void
    {
        (new BackfillRunner)->run();
    }

    /**
     * users.chat_role_categories stores the category id — that is what the
     * profile form submits — so fixtures must seed the id, not the name.
     */
    private function roleId(string $name): string
    {
        return (string) DB::table('chat_role_categories')->where('name', $name)->value('id');
    }

    public function test_a_complete_profile_is_calibrated_without_an_interview(): void
    {
        $user = User::factory()->create([
            'email' => 'anoop@acme.com',
            'user_type' => 'customer',
            'phone' => '+15550001',
            'company_name' => 'Acme Corporation',
            'company_address' => '1 Acme Way',
            'number_employess' => '1000-10000',
            'chat_role_categories' => $this->roleId('C-Suite'),
            'company_category' => 'Software',
            'about_company' => 'Real estate technology.',
        ]);

        $this->runBackfill();
        $user = $user->fresh();

        $this->assertNotNull($user->organization_id);
        $this->assertSame('acme.com', Organization::find($user->organization_id)->domain);
        $this->assertSame(50, (int) $user->hierarchy_rank);

        $active = $user->organization->activeContext;
        $this->assertNotNull($active);
        $this->assertStringContainsString('Real estate technology', $active->profile['scale'].' '.implode(' ', $active->profile['summary_bullets']));
        $this->assertNull($active->transcript, 'A backfilled row is marked by a null transcript.');

        // This profile is rendered into every prompt the org sends, and the row
        // is append-only — a raw category id here could never be corrected.
        $this->assertSame('C-Suite', $active->profile['role']);
        $this->assertStringContainsString('C-Suite', app(DocumentContextService::class)->orgContextBlock($user));
    }

    public function test_running_the_backfill_twice_changes_nothing(): void
    {
        // It runs unattended on deploy and may run again on the next one.
        $user = User::factory()->create([
            'email' => 'anoop@acme.com',
            'user_type' => 'customer',
            'phone' => '+15550001',
            'company_name' => 'Acme Corporation',
            'company_address' => '1 Acme Way',
            'number_employess' => '1000-10000',
            'chat_role_categories' => $this->roleId('C-Suite'),
            'company_category' => 'Software',
            'about_company' => 'Real estate technology.',
        ]);

        $this->runBackfill();
        $this->runBackfill();

        $this->assertDatabaseCount('org_context_versions', 1);
        $this->assertSame(1, Organization::where('domain', 'acme.com')->count());
        $this->assertSame(50, (int) $user->fresh()->hierarchy_rank);
    }

    public function test_an_unmatched_role_falls_to_the_rank_floor(): void
    {
        $user = User::factory()->create([
            'email' => 'someone@acme.com',
            'user_type' => 'customer',
            'phone' => '+15550002',
            'company_name' => 'Acme',
            'company_address' => '1 Acme Way',
            'number_employess' => '0-10',
            'chat_role_categories' => 'Chief Vibes Officer', // matches no seeded id or name
            'company_category' => 'Software',
            'about_company' => 'Things.',
        ]);

        $this->runBackfill();

        $this->assertSame(10, (int) $user->fresh()->hierarchy_rank);
    }

    public function test_an_incomplete_profile_is_left_for_the_interview(): void
    {
        $user = User::factory()->create([
            'email' => 'newbie@acme.com',
            'user_type' => 'customer',
            'company_name' => 'Acme',
            'about_company' => null,
        ]);

        $this->runBackfill();
        $user = $user->fresh();

        // Membership is assigned; rank is not — so the gate routes them to the interview.
        $this->assertNotNull($user->organization_id);
        $this->assertNull($user->hierarchy_rank);
    }

    public function test_the_earliest_registrant_owns_the_organization(): void
    {
        $first = User::factory()->create(['email' => 'first@acme.com', 'user_type' => 'customer']);
        $second = User::factory()->create(['email' => 'second@acme.com', 'user_type' => 'customer']);

        $this->runBackfill();

        $org = Organization::where('domain', 'acme.com')->first();
        $this->assertSame($first->id, (int) $org->owner_user_id);
        $this->assertNotSame($second->id, (int) $org->owner_user_id);
    }
}

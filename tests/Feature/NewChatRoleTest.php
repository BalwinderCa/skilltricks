<?php

namespace Tests\Feature;

use App\Models\ChatCategory;
use App\Models\ChatRoleCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The New Chat page's role, after the profile picker that used to set it was
 * replaced by Department (9fdf538) and left users.chat_role_categories with no
 * writer at all.
 */
class NewChatRoleTest extends TestCase
{
    use RefreshDatabase;

    private string $cSuite;

    private string $vicePresident;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        // 2026_08_22_000003 already seeds the six rungs; look them up rather than
        // hardcoding ids, since the ladder is data and an install can reshape it.
        $this->cSuite = (string) ChatRoleCategory::where('rank', 50)->value('id');
        $this->vicePresident = (string) ChatRoleCategory::where('rank', 40)->value('id');

        // role_name holds the role's id, as a string.
        ChatCategory::insert([
            ['name' => 'Leadership and Governance', 'role_name' => $this->cSuite, 'status' => 1],
            ['name' => 'Strategic Initiatives', 'role_name' => $this->vicePresident, 'status' => 1],
            ['name' => 'Retired Category', 'role_name' => $this->cSuite, 'status' => 0],
        ]);
    }

    private function customer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'customer',
            'email_verified_at' => now(),
        ], $attributes));
    }

    public function test_role_is_resolved_from_hierarchy_rank(): void
    {
        $user = $this->customer(['hierarchy_rank' => 50, 'chat_role_categories' => null]);

        $response = $this->actingAs($user)->get(route('newchat.index'));

        $response->assertOk();
        $response->assertSee('Leadership and Governance');
        $response->assertDontSee('Strategic Initiatives');
    }

    public function test_inactive_categories_are_excluded(): void
    {
        $user = $this->customer(['hierarchy_rank' => 50]);

        $this->actingAs($user)->get(route('newchat.index'))->assertDontSee('Retired Category');
    }

    public function test_legacy_column_is_used_when_no_rank_is_set(): void
    {
        $user = $this->customer(['hierarchy_rank' => null, 'chat_role_categories' => $this->vicePresident]);

        $response = $this->actingAs($user)->get(route('newchat.index'));

        $response->assertOk();
        $response->assertSee('Strategic Initiatives');
    }

    public function test_rank_wins_over_a_stale_legacy_column(): void
    {
        // The roster keeps hierarchy_rank current; nothing keeps the old column so.
        $user = $this->customer(['hierarchy_rank' => 50, 'chat_role_categories' => $this->vicePresident]);

        $response = $this->actingAs($user)->get(route('newchat.index'));

        $response->assertSee('Leadership and Governance');
        $response->assertDontSee('Strategic Initiatives');
    }

    /** The regression: this was a TypeError, not a page. */
    public function test_user_with_no_rank_and_no_legacy_value_still_gets_the_page(): void
    {
        $user = $this->customer(['hierarchy_rank' => null, 'chat_role_categories' => null]);

        $response = $this->actingAs($user)->get(route('newchat.index'));

        $response->assertOk();
        // Both rungs offered, because the user has to pick one.
        $response->assertSee('C-Suite');
        $response->assertSee('Vice President');
        // ...and no category belongs to a role nobody has chosen yet.
        $response->assertDontSee('Leadership and Governance');
    }

    public function test_the_role_select_locks_only_when_a_role_is_known(): void
    {
        $locked = $this->customer(['hierarchy_rank' => 50]);
        $this->actingAs($locked)->get(route('newchat.index'))->assertSee('pointer-events: none', false);

        $open = $this->customer(['hierarchy_rank' => null, 'chat_role_categories' => null]);
        $this->actingAs($open)->get(route('newchat.index'))->assertDontSee('pointer-events: none', false);
    }

    public function test_categories_endpoint_returns_active_categories_for_a_role(): void
    {
        $user = $this->customer(['hierarchy_rank' => 50]);

        $response = $this->actingAs($user)->get(route('getcategories.index', ['role_id' => $this->cSuite]));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Leadership and Governance']);
    }

    public function test_categories_endpoint_returns_empty_for_a_missing_role(): void
    {
        $user = $this->customer(['hierarchy_rank' => 50]);

        $this->actingAs($user)->get(route('getcategories.index'))->assertOk()->assertExactJson([]);
    }
}

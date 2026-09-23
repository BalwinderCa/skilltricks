<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\Organization;
use App\Models\SearchUserChat;
use App\Models\StrategyResource;
use App\Models\StrategyResourceChange;
use App\Models\User;
use App\Services\AI\AiProviderService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Publish gate (Features spec, phase 1): a strategy stays Draft until an
 * org-chart leader commits resources and publishes it.
 */
class PublishGateTest extends TestCase
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

    /**
     * An organization with an owner, a department head, a manager with one
     * report, and a plain member; departments Sales and Engineering.
     *
     * @return array{org: Organization, owner: User, head: User, manager: User, report: User, member: User, sales: Department, eng: Department}
     */
    private function world(): array
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $make = fn (string $email, array $extra = []) => User::factory()->create(array_merge([
            'email' => $email, 'user_type' => 'customer', 'organization_id' => $org->id,
        ], $extra));

        $owner = $make('owner@acme.com');
        $org->forceFill(['owner_user_id' => $owner->id])->save();

        $head = $make('head@acme.com');
        $manager = $make('manager@acme.com');
        $report = $make('report@acme.com', ['manager_id' => $manager->id]);
        $member = $make('member@acme.com');

        $sales = Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E', 'head_user_id' => $head->id]);
        $eng = Department::create(['organization_id' => $org->id, 'name' => 'Engineering', 'color' => '#3B82F6']);

        return compact('org', 'owner', 'head', 'manager', 'report', 'member', 'sales', 'eng');
    }

    /** A finished strategy: pathway, scenario, brief and two role goals. */
    private function finishedChat(User $author): SearchUserChat
    {
        $chat = SearchUserChat::create([
            'user_id' => $author->id, 'status1' => 0,
            'selected_strategy' => 'Product-led security upsell',
            'selected_scenario' => 'Realistic Positive',
            'leadership_brief' => 'Convert mid-market accounts to enterprise tiers.',
        ]);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'VP of Sales', 'recommended_action' => 'Create an enterprise upgrade motion']);
        ExpectedState::create(['search_user_chat_id' => $chat->id, 'role' => 'VP of Product', 'recommended_action' => 'Ship SOC2 controls']);

        return $chat;
    }

    /** Bind an AI provider that answers every generate() with $text. */
    private function fakeAi(string $text, int $status = 200): void
    {
        $ai = Mockery::mock(AiProviderService::class);
        $ai->shouldReceive('providerLabel')->andReturn('Fake');
        $ai->shouldReceive('generate')->andReturn(new ClientResponse(new PsrResponse($status, [], json_encode([
            'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
        ]))));
        $ai->shouldReceive('extractText')->andReturn($text);
        $ai->shouldReceive('extractUsage')->andReturn([]);
        $ai->shouldReceive('recordChatTokens')->andReturn(0);
        $ai->shouldReceive('parseJson')->andReturnUsing(fn ($t) => json_decode((string) $t, true));
        $this->instance(AiProviderService::class, $ai);
    }

    public function test_a_new_strategy_starts_as_draft(): void
    {
        $w = $this->world();
        $chat = SearchUserChat::create(['user_id' => $w['owner']->id, 'status1' => 0]);

        $this->assertSame('draft', $chat->fresh()->status);
        $this->assertFalse($chat->fresh()->isPublished());
    }

    public function test_backfill_copies_the_authors_organization_onto_existing_strategies(): void
    {
        $w = $this->world();
        $chat = SearchUserChat::create(['user_id' => $w['member']->id, 'status1' => 0]);
        DB::table('search_user_chat')->where('id', $chat->id)->update(['organization_id' => null]);

        $migration = require database_path('migrations/2026_09_23_000000_add_publish_gate.php');
        $migration->backfill();

        $this->assertSame($w['org']->id, (int) $chat->fresh()->organization_id);
        $this->assertSame('draft', $chat->fresh()->status);
    }

    public function test_resources_and_their_changes_hang_off_the_strategy(): void
    {
        $w = $this->world();
        $chat = $this->finishedChat($w['owner']);
        $row = $chat->resources()->create([
            'department_id' => $w['sales']->id, 'department_name' => 'Sales',
            'budget' => 50000, 'fte' => 1.5, 'ai_suggestion' => ['budget' => 40000],
        ]);
        $row->changes()->create(['user_id' => $w['owner']->id, 'field' => 'budget', 'old_value' => '40000.00', 'new_value' => '50000.00']);

        $this->assertSame(['budget' => 40000], $chat->resources()->first()->ai_suggestion);
        $this->assertSame(1, StrategyResourceChange::count());

        $chat->delete();
        $this->assertSame(0, StrategyResource::count());
    }
}

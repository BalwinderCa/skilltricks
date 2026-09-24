<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\MyGoals;
use App\Services\OrganizationService;
use Database\Seeders\FeaturesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** The local demo data for the Features spec phases is usable end to end. */
class FeaturesDemoSeederTest extends TestCase
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

    public function test_the_demo_is_ready_to_click_through(): void
    {
        $this->seed(FeaturesDemoSeeder::class);

        $ceo = User::where('email', 'ceo@demo.test')->firstOrFail();
        $rep = User::where('email', 'rep@demo.test')->firstOrFail();
        $orgs = app(OrganizationService::class);

        $this->assertTrue($orgs->isLeader($ceo));
        $this->assertTrue($orgs->isLeader(User::where('email', 'sales.head@demo.test')->firstOrFail()));
        $this->assertFalse($orgs->isLeader($rep));
        $this->assertSame(1, SearchUserChat::where('user_id', $ceo->id)->where('status', 'published')->count());
        $this->assertSame(1, SearchUserChat::where('user_id', $ceo->id)->where('status', 'draft')->count());
        $this->assertCount(1, app(MyGoals::class)->for($rep));

        $this->assertTrue(Hash::check('password', $rep->password));
        $this->assertFalse((bool) $rep->must_change_password);
        $this->actingAs($rep)->get('/dashboard')->assertOk()->assertSee('Your goals')->assertSee('Where to begin', false);
        $this->actingAs($ceo)->get(route('strategies.index'))->assertOk()->assertSee('Expand enterprise revenue');
    }

    public function test_running_it_twice_does_not_duplicate(): void
    {
        $this->seed(FeaturesDemoSeeder::class);
        $this->seed(FeaturesDemoSeeder::class);

        $this->assertSame(1, Organization::where('domain', 'demo.test')->count());
        $this->assertSame(1, User::where('email', 'rep@demo.test')->count());
    }
}

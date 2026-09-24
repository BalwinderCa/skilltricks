<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\GoalResponse;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local demo data for the Features spec phases (publish gate → executive view):
 *   php artisan db:seed --class=FeaturesDemoSeeder
 *
 * Logins (password "password"): ceo@demo.test (owner, author), sales.head@demo.test
 * (Sales department head), rep@demo.test (reports to the Sales head),
 * pm@demo.test (Product). Never runs outside local/testing.
 */
class FeaturesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->error('FeaturesDemoSeeder only runs in local or testing.');

            return;
        }
        if (Organization::where('domain', 'demo.test')->exists()) {
            $this->command?->warn('Demo Co already exists; nothing to do.');

            return;
        }

        $org = Organization::create(['domain' => 'demo.test', 'name' => 'Demo Co']);
        app(OrganizationService::class)->seedDefaultRoles($org);
        $salesRole = OrgRole::create(['organization_id' => $org->id, 'name' => 'Sales']);
        $productRole = OrgRole::create(['organization_id' => $org->id, 'name' => 'Product']);
        $salesDept = Department::create(['organization_id' => $org->id, 'name' => 'Sales', 'color' => '#22C55E']);
        $productDept = Department::create(['organization_id' => $org->id, 'name' => 'Product', 'color' => '#3B82F6']);

        $make = fn (string $name, string $email, ?OrgRole $role, ?Department $dept, ?User $manager) => User::create([
            'name' => $name, 'email' => $email, 'password' => Hash::make('password'),
            'email_verified_at' => now(), 'user_type' => 'customer', 'must_change_password' => false,
            'organization_id' => $org->id, 'org_role_id' => $role?->id,
            'department_id' => $dept?->id, 'manager_id' => $manager?->id,
        ]);
        $ceo = $make('Casey CEO', 'ceo@demo.test', null, null, null);
        $org->forceFill(['owner_user_id' => $ceo->id])->save();
        $head = $make('Sam Sales-Head', 'sales.head@demo.test', $salesRole, $salesDept, $ceo);
        $rep = $make('Riley Rep', 'rep@demo.test', $salesRole, $salesDept, $head);
        $make('Pat Product', 'pm@demo.test', $productRole, $productDept, $ceo);
        $salesDept->forceFill(['head_user_id' => $head->id])->save();

        // Published: goals linked, resources committed, Sales has starting points,
        // and the rep has chosen "Act on it" so the card offers Where to begin.
        $published = $this->strategy($ceo, 'Expand enterprise revenue 30% over two quarters');
        [$salesGoal] = $this->goals($published, $salesRole, $productRole);
        $salesGoal->update(['starting_options' => [
            'Audit the 20 largest mid-market accounts for upgrade fit',
            'Draft the enterprise upgrade contract with Legal',
            'Book a pricing review with Finance this week',
        ]]);
        $published->resources()->create(['department_id' => $salesDept->id, 'department_name' => 'Sales', 'budget' => 50000, 'fte' => 2, 'tools' => 'CRM seats, CPQ', 'notes' => 'Moved from Q3 brand budget']);
        $published->resources()->create(['department_id' => $productDept->id, 'department_name' => 'Product', 'budget' => 120000, 'fte' => 4, 'tools' => 'SOC2 tooling']);
        $published->forceFill(['status' => 'published', 'published_by' => $ceo->id, 'published_at' => now(), 'organization_id' => $org->id])->save();
        GoalResponse::create(['expected_state_id' => $salesGoal->id, 'user_id' => $rep->id, 'decision' => 'act_on_it', 'decided_at' => now()]);

        // Draft: finished wizard, nothing committed yet — try the Publish card on it.
        $draft = $this->strategy($ceo, 'Cut operational delays by 10% this year');
        $this->goals($draft, $salesRole, $productRole);
        $draft->forceFill(['organization_id' => $org->id])->save();

        $this->command?->info('Demo Co seeded. Log in as ceo@demo.test / password (or sales.head@, rep@, pm@demo.test).');
    }

    private function strategy(User $author, string $question): SearchUserChat
    {
        $chat = SearchUserChat::create([
            'user_id' => $author->id, 'status1' => 1, 'status2' => 1,
            'selected_strategy' => 'Product-led security and compliance upsell',
            'selected_scenario' => 'Realistic Positive',
            'leadership_brief' => "# Leadership Alignment Brief\n\n{$question}. Focus on converting mid-market accounts to enterprise tiers.",
        ]);
        SearchUserChatData::create(['search_user_chat_id' => $chat->id, 'user_id' => $author->id, 'search' => $question, 'response' => 'Demo strategy.']);

        return $chat;
    }

    /** @return array{0: ExpectedState, 1: ExpectedState} */
    private function goals(SearchUserChat $chat, OrgRole $sales, OrgRole $product): array
    {
        $productGoal = ExpectedState::create([
            'search_user_chat_id' => $chat->id, 'role' => 'Product', 'org_role_id' => $product->id,
            'recommended_action' => 'Ship SOC2 Type II controls and enterprise role-based access by Q3.',
            'success_metric' => 'SOC2 audit passed', 'target_date' => now()->addMonths(3)->toDateString(),
        ]);
        $salesGoal = ExpectedState::create([
            'search_user_chat_id' => $chat->id, 'role' => 'Sales', 'org_role_id' => $sales->id,
            'recommended_action' => 'Launch a zero-friction enterprise upgrade motion for mid-market clients.',
            'success_metric' => 'Upgrades signed', 'target_value' => '25', 'target_date' => now()->addMonths(5)->toDateString(),
            'depends_on_id' => $productGoal->id,
        ]);

        return [$salesGoal, $productGoal];
    }
}

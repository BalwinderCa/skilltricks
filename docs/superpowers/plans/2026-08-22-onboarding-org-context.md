# Dynamic AI Onboarding & Org Context Calibration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the eight-field profile gate with a 3-turn LLM interview that produces a versioned, rank-ordered organizational context baseline, and feed that baseline into every StrategiStudio prompt.

**Architecture:** Organizations are resolved from verified email domains. Each confirmed interview appends an immutable row to `org_context_versions`; the highest-ranked row becomes the organization's active baseline via a pointer on `organizations.active_context_id`. A single new method, `DocumentContextService::orgContextBlock()`, injects that baseline into all prompt-building paths.

**Tech Stack:** Laravel 12, PHP 8.2, MySQL (staging) / SQLite `:memory:` (tests), PHPUnit, PHPStan (baselined), Pint, `App\Services\AI\AiProviderService` (Vertex AI).

**Spec:** `docs/superpowers/specs/2026-08-22-onboarding-org-context-design.md`

## Global Constraints

- PHP 8.2. Laravel 12. No new Composer dependencies.
- All migrations use the codebase's guarded style: wrap `Schema::create` in `if (! Schema::hasTable(...))` and `Schema::table` column adds in `if (! Schema::hasColumn(...))`. Every migration needs a working `down()`.
- Tests run on SQLite `:memory:`. Feature tests extend `Tests\TestCase`, use `RefreshDatabase`, and follow the `setUp()` shape in `tests/Feature/ExpectedStateTest.php`.
- Run the full suite with `vendor/bin/phpunit`. A single test: `vendor/bin/phpunit --filter test_name`.
- CI gates on `vendor/bin/phpstan analyse --no-progress --memory-limit=2G` (baselined — only *new* issues fail), `vendor/bin/phpunit tests/Feature/Security/`, then `vendor/bin/phpunit`.
- Format before committing: `vendor/bin/pint`.
- `users.user_type` is `'customer'` for end users; admins are `'admin'`. The gate applies to customers only.
- Rank ladder — these six integers are the ONLY valid ranks anywhere in this feature: `10` (individual contributor), `20` (manager), `30` (director), `40` (VP), `50` (C-Suite), `60` (Board).
- Turn 1's seed question is fixed copy, used verbatim:
  `To help SkillTricks anchor its intelligence in your daily reality: What is your current role, and what specific team or area of the organization do you directly drive or influence?`
- Deployment is staging-only; pushing to `main` autodeploys and runs migrations. Do not push until the whole plan is green.

---

## File Structure

| Path | Responsibility |
|---|---|
| `database/migrations/2026_08_22_000000_create_organizations_table.php` | organizations table |
| `database/migrations/2026_08_22_000001_create_org_context_versions_table.php` | append-only context history |
| `database/migrations/2026_08_22_000002_add_org_columns_to_users_table.php` | `organization_id`, `hierarchy_rank` |
| `database/migrations/2026_08_22_000003_add_rank_to_chat_role_categories_table.php` | rank column + ladder seed |
| `database/migrations/2026_08_22_000005_backfill_organizations_from_users.php` | data-only backfill |
| `app/Models/Organization.php` | org identity, active-context pointer, members |
| `app/Models/OrgContextVersion.php` | one immutable context declaration |
| `app/Services/OrganizationService.php` | domain resolution + the cascade rule. No LLM, no HTTP. |
| `app/Services/AI/OnboardingAgentService.php` | interview prompts and turn orchestration. LLM only, no persistence. |
| `app/Http/Controllers/Backend/OnboardingController.php` | HTTP surface: session state, validation, confirm |
| `config/organizations.php` | free-domain list |
| `resources/views/backend/pages/onboarding.blade.php` | interview UI |
| `resources/views/backend/pages/partials/org-members.blade.php` | owner-only rank override |

`OrganizationService` and `OnboardingAgentService` are split deliberately: the cascade rule is pure database logic that must be unit-testable without touching an LLM, and the agent is LLM logic with no database writes. The controller is the only place they meet.

---

### Task 1: Organizations and append-only context versions

**Files:**
- Create: `database/migrations/2026_08_22_000000_create_organizations_table.php`
- Create: `database/migrations/2026_08_22_000001_create_org_context_versions_table.php`
- Create: `app/Models/Organization.php`
- Create: `app/Models/OrgContextVersion.php`
- Test: `tests/Feature/OrgContextVersionTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `Organization` with `$fillable = ['domain', 'name', 'owner_user_id', 'active_context_id']`, relations `activeContext(): BelongsTo`, `versions(): HasMany`, `members(): HasMany`.
  - `OrgContextVersion` with `$fillable = ['organization_id', 'user_id', 'rank', 'profile', 'transcript']`, casts `profile` and `transcript` to `array`, `rank` to `integer`. Updating or deleting an instance throws `\RuntimeException`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OrgContextVersionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrgContextVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgContextVersionTest extends TestCase
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

    public function test_a_context_version_stores_its_profile_and_transcript(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        $version = OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 30,
            'profile' => ['role' => 'Director of Ops', 'rank' => 30, 'frictions' => ['handoffs']],
            'transcript' => [['question' => 'What is your role?', 'answer' => 'Director of Ops']],
        ]);

        $this->assertSame(30, $version->rank);
        $this->assertSame('Director of Ops', $version->profile['role']);
        $this->assertSame('handoffs', $version->profile['frictions'][0]);
        $this->assertSame('Director of Ops', $version->transcript[0]['answer']);
        $this->assertSame($org->id, $version->organization->id);
    }

    public function test_a_context_version_cannot_be_updated(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com']);
        $version = OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 30,
            'profile' => ['role' => 'Director of Ops'],
        ]);

        $this->expectException(\RuntimeException::class);
        $version->update(['rank' => 60]);
    }

    public function test_a_context_version_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com']);
        $version = OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 30,
            'profile' => ['role' => 'Director of Ops'],
        ]);

        $this->expectException(\RuntimeException::class);
        $version->delete();
    }

    public function test_an_organization_points_at_its_active_context(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $org = Organization::create(['domain' => 'acme.com']);
        $version = OrgContextVersion::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'rank' => 50,
            'profile' => ['role' => 'CEO'],
        ]);

        $org->update(['active_context_id' => $version->id]);

        $this->assertSame($version->id, $org->fresh()->activeContext->id);
        $this->assertCount(1, $org->fresh()->versions);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OrgContextVersionTest`
Expected: FAIL — `Class "App\Models\Organization" not found`.

- [ ] **Step 3: Write the organizations migration**

Create `database/migrations/2026_08_22_000000_create_organizations_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('organizations')) {
            Schema::create('organizations', function (Blueprint $table) {
                $table->id();
                $table->string('domain')->unique();
                $table->string('name')->nullable();
                $table->unsignedBigInteger('owner_user_id')->nullable();
                // No FK: this points at org_context_versions, which is created after
                // this table. A constraint here would be circular at migrate time.
                $table->unsignedBigInteger('active_context_id')->nullable();
                $table->timestamps();

                $table->index('owner_user_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('organizations');
    }
};
```

- [ ] **Step 4: Write the org_context_versions migration**

Create `database/migrations/2026_08_22_000001_create_org_context_versions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('org_context_versions')) {
            Schema::create('org_context_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->unsignedBigInteger('user_id');
                $table->integer('rank');
                $table->json('profile');
                // Null marks a backfilled row: derived from an existing profile
                // rather than produced by an interview.
                $table->json('transcript')->nullable();
                // Append-only: rows are never updated, so there is no updated_at.
                $table->timestamp('created_at')->nullable();

                $table->index(['organization_id', 'rank']);
                $table->index('user_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('org_context_versions');
    }
};
```

- [ ] **Step 5: Write the Organization model**

Create `app/Models/Organization.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'domain',
        'name',
        'owner_user_id',
        'active_context_id',
    ];

    public function activeContext(): BelongsTo
    {
        return $this->belongsTo(OrgContextVersion::class, 'active_context_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(OrgContextVersion::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
```

- [ ] **Step 6: Write the OrgContextVersion model**

Create `app/Models/OrgContextVersion.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgContextVersion extends Model
{
    /**
     * Append-only. The brief's "Full Input Persistence" rule says lower-level
     * responses are never hard-deleted, so mutation is blocked at the model
     * rather than left to convention.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'user_id',
        'rank',
        'profile',
        'transcript',
    ];

    protected $casts = [
        'profile' => 'array',
        'transcript' => 'array',
        'rank' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('org_context_versions is append-only; a context version cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('org_context_versions is append-only; a context version cannot be deleted.');
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OrgContextVersionTest`
Expected: PASS, 4 tests.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint app/Models/Organization.php app/Models/OrgContextVersion.php
git add database/migrations/2026_08_22_00000{0,1}_*.php app/Models/Organization.php app/Models/OrgContextVersion.php tests/Feature/OrgContextVersionTest.php
git commit -m "feat(org): add organizations and append-only org_context_versions"
```

---

### Task 2: User membership columns and the rank ladder

**Files:**
- Create: `database/migrations/2026_08_22_000002_add_org_columns_to_users_table.php`
- Create: `database/migrations/2026_08_22_000003_add_rank_to_chat_role_categories_table.php`
- Create: `database/migrations/2026_08_22_000004_add_profile_columns_to_users_table.php`
- Modify: `app/Models/User.php` (add to `$fillable`, add `organization()` relation)
- Test: `tests/Feature/OrgMembershipTest.php`

**Interfaces:**
- Consumes: `Organization` from Task 1.
- Produces: `User::organization(): BelongsTo`, `users.organization_id` (nullable), `users.hierarchy_rank` (nullable int), `chat_role_categories.rank` (nullable int, seeded).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OrgMembershipTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrgMembershipTest extends TestCase
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

    public function test_a_user_belongs_to_an_organization(): void
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $user = User::factory()->create([
            'user_type' => 'customer',
            'organization_id' => $org->id,
            'hierarchy_rank' => 40,
        ]);

        $this->assertSame($org->id, $user->organization->id);
        $this->assertSame(40, (int) $user->fresh()->hierarchy_rank);
        $this->assertCount(1, $org->fresh()->members);
    }

    public function test_a_new_user_starts_uncalibrated(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);

        $this->assertNull($user->organization_id);
        $this->assertNull($user->hierarchy_rank);
    }

    public function test_the_rank_ladder_is_seeded_onto_chat_role_categories(): void
    {
        $ranks = DB::table('chat_role_categories')->pluck('rank', 'name');

        $this->assertSame(50, (int) $ranks['C-Suite']);
        $this->assertSame(60, (int) $ranks['Board']);
        $this->assertSame(10, (int) $ranks['Individual Contributor']);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OrgMembershipTest`
Expected: FAIL — SQLite reports no such column `organization_id`.

- [ ] **Step 3: Write the users columns migration**

Create `database/migrations/2026_08_22_000002_add_org_columns_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->index('organization_id');
            });
        }

        if (! Schema::hasColumn('users', 'hierarchy_rank')) {
            Schema::table('users', function (Blueprint $table) {
                // Null means "not yet calibrated" — this is what the dashboard gate reads.
                $table->integer('hierarchy_rank')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'hierarchy_rank')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('hierarchy_rank');
            });
        }

        if (Schema::hasColumn('users', 'organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }
    }
};
```

- [ ] **Step 4: Create the missing profile columns**

`users.company`, `company_name`, `company_address`, `number_employess`,
`chat_role_categories`, `company_category`, and `about_company` are read and
written throughout the app (`User::$fillable`, `DashboardController::updateProfile()`,
`profile.blade.php`) but **no migration ever creates them** — they exist only on
the live database, added out of band. A freshly migrated database therefore lacks
them, which breaks CI and makes Task 11's backfill unrunnable.

Create `database/migrations/2026_08_22_000004_add_profile_columns_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repairs drift between the migrations and the live database. These columns
     * are used all over the app but were added out of band, so a fresh database
     * never had them. Every add is guarded, making this a no-op wherever they
     * already exist.
     */
    private const COLUMNS = [
        'company',
        'company_name',
        'company_address',
        'number_employess',
        'chat_role_categories',
        'company_category',
    ];

    public function up()
    {
        foreach (self::COLUMNS as $column) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->string($column)->nullable();
                });
            }
        }

        if (! Schema::hasColumn('users', 'about_company')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('about_company')->nullable();
            });
        }
    }

    public function down()
    {
        // Intentionally empty. These columns predate this migration on every
        // real database and hold live user data; dropping them on rollback
        // would destroy it.
    }
};
```

Add this test to `tests/Feature/OrgMembershipTest.php`:

```php
    public function test_the_profile_columns_exist_on_a_freshly_migrated_database(): void
    {
        foreach ([
            'company', 'company_name', 'company_address', 'number_employess',
            'chat_role_categories', 'company_category', 'about_company',
        ] as $column) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Schema::hasColumn('users', $column),
                "users.{$column} is missing — Task 11's backfill reads it."
            );
        }
    }
```

- [ ] **Step 5: Write the rank ladder migration**

Create `database/migrations/2026_08_22_000003_add_rank_to_chat_role_categories_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The six bands the onboarding agent maps a stated role onto. Seeded onto the
     * existing chat_role_categories table so the platform keeps one notion of
     * "role" rather than growing a second, competing one.
     */
    private const LADDER = [
        'Individual Contributor' => 10,
        'Manager' => 20,
        'Director' => 30,
        'Vice President' => 40,
        'C-Suite' => 50,
        'Board' => 60,
    ];

    public function up()
    {
        if (! Schema::hasColumn('chat_role_categories', 'rank')) {
            Schema::table('chat_role_categories', function (Blueprint $table) {
                $table->integer('rank')->nullable();
            });
        }

        foreach (self::LADDER as $name => $rank) {
            $exists = DB::table('chat_role_categories')->where('name', $name)->exists();

            if ($exists) {
                DB::table('chat_role_categories')->where('name', $name)->update(['rank' => $rank]);

                continue;
            }

            DB::table('chat_role_categories')->insert([
                'name' => $name,
                'rank' => $rank,
                'status' => 1,
                'created_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Deliberately does NOT delete rows. up() cannot distinguish, on rollback,
        // a category it inserted from one that already existed, and destroying a
        // pre-existing row is far worse than leaving six harmless standard ones.
        // Dropping the column removes everything this migration actually added.
        if (Schema::hasColumn('chat_role_categories', 'rank')) {
            Schema::table('chat_role_categories', function (Blueprint $table) {
                $table->dropColumn('rank');
            });
        }
    }
};
```

Note: the `chat_role_categories` table's updated-at column is named `update_at` (a pre-existing typo preserved by the model). The inserts above set only `created_at`, so the typo is not touched.

- [ ] **Step 6: Add the fields and relation to the User model**

In `app/Models/User.php`, add `'organization_id'` and `'hierarchy_rank'` to `$fillable` immediately after `'company'`:

```php
        'company',
        'organization_id',
        'hierarchy_rank',
        'company_name',
```

Add the `BelongsTo` import alongside the existing imports:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
```

And add this relation next to the existing `role()` method:

```php
    # organization
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OrgMembershipTest`
Expected: PASS, 3 tests.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint app/Models/User.php
git add database/migrations/2026_08_22_00000{2,3,4}_*.php app/Models/User.php tests/Feature/OrgMembershipTest.php
git commit -m "feat(org): add user membership columns and seed the rank ladder"
```

---

### Task 3: Resolve an organization from an email domain

**Files:**
- Create: `config/organizations.php`
- Create: `app/Services/OrganizationService.php`
- Test: `tests/Unit/OrganizationServiceTest.php`

**Interfaces:**
- Consumes: `Organization` from Task 1.
- Produces: `OrganizationService::resolveForEmail(string $email): Organization`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/OrganizationServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        $this->service = app(OrganizationService::class);
    }

    public function test_two_users_on_the_same_corporate_domain_share_an_organization(): void
    {
        $first = $this->service->resolveForEmail('anoop@acme.com');
        $second = $this->service->resolveForEmail('raghu@acme.com');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('acme.com', $first->domain);
        $this->assertSame(1, Organization::count());
    }

    public function test_domains_are_normalised_for_case_and_whitespace(): void
    {
        $first = $this->service->resolveForEmail('anoop@acme.com');
        $second = $this->service->resolveForEmail('  Raghu@ACME.CoM  ');

        $this->assertSame($first->id, $second->id);
    }

    public function test_free_domain_users_are_isolated_from_each_other(): void
    {
        $first = $this->service->resolveForEmail('alice@gmail.com');
        $second = $this->service->resolveForEmail('bob@gmail.com');

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('alice@gmail.com', $first->domain);
        $this->assertSame('bob@gmail.com', $second->domain);
    }

    public function test_malformed_addresses_do_not_share_an_organization(): void
    {
        // Every one of these previously keyed to domain '' and collided into a
        // single shared org — the cross-tenant merge this boundary must prevent.
        $a = $this->service->resolveForEmail('alice@');
        $b = $this->service->resolveForEmail('bob@');
        $c = $this->service->resolveForEmail('@');

        $this->assertNotSame($a->id, $b->id);
        $this->assertNotSame($b->id, $c->id);
        $this->assertNotSame($a->id, $c->id);
        $this->assertSame(3, Organization::count());
    }

    public function test_an_empty_address_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->resolveForEmail('   ');
    }

    public function test_a_trailing_dot_does_not_split_an_organization(): void
    {
        $withDot = $this->service->resolveForEmail('anoop@acme.com.');
        $without = $this->service->resolveForEmail('raghu@acme.com');

        $this->assertSame($withDot->id, $without->id);
    }

    public function test_a_corporate_domain_is_not_treated_as_free(): void
    {
        $org = $this->service->resolveForEmail('someone@notgmail.com');

        $this->assertSame('notgmail.com', $org->domain);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OrganizationServiceTest`
Expected: FAIL — `Target class [App\Services\OrganizationService] does not exist`.

- [ ] **Step 3: Write the free-domain config**

Create `config/organizations.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Free email domains
    |--------------------------------------------------------------------------
    |
    | A shared consumer domain is not an organization. Users on these domains
    | are keyed to their full email address instead, so each gets a private
    | single-seat organization rather than all gmail.com users landing in one
    | shared "org" and overwriting each other's strategic context.
    |
    */
    'free_domains' => [
        'gmail.com',
        'googlemail.com',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'yahoo.com',
        'yahoo.co.uk',
        'icloud.com',
        'me.com',
        'aol.com',
        'proton.me',
        'protonmail.com',
        'gmx.com',
        'mail.com',
        'yandex.com',
        'zoho.com',
        'qq.com',
        '163.com',
    ],
];
```

- [ ] **Step 4: Write the resolver**

Create `app/Services/OrganizationService.php`:

```php
<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Database\QueryException;

class OrganizationService
{
    /**
     * Find or create the organization an email address belongs to.
     *
     * A shared email domain is the trust boundary: you cannot join an
     * organization whose email you cannot receive. Free consumer domains are
     * keyed to the full address so their users stay isolated.
     */
    public function resolveForEmail(string $email): Organization
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            // Refuse rather than bucket. An empty key would become a shared
            // organization that every other empty input joins — the exact
            // cross-tenant merge this method exists to prevent.
            throw new \InvalidArgumentException('Cannot resolve an organization from an empty email address.');
        }

        $at = strrpos($email, '@');
        // Trailing DNS root dot: acme.com. and acme.com are the same domain.
        $domain = $at === false ? '' : rtrim(substr($email, $at + 1), '.');

        if ($domain === '') {
            // No '@' at all, or nothing after it ('alice@'). Not addressable, so
            // not verifiable: key on the whole string so each such address gets
            // its own singleton org instead of sharing one.
            return $this->firstOrCreateDomain($email);
        }

        $isFree = in_array($domain, config('organizations.free_domains', []), true);

        return $this->firstOrCreateDomain($isFree ? $email : $domain);
    }

    /**
     * firstOrCreate is read-then-write, so two people registering on the same
     * brand-new domain at once can both pass the SELECT and one hits the unique
     * index. This runs inside registration's transaction, where a raw
     * "Integrity constraint violation" would be flashed straight at the user.
     * The loser simply re-reads the row the winner just created.
     */
    private function firstOrCreateDomain(string $domain): Organization
    {
        try {
            return Organization::firstOrCreate(['domain' => $domain]);
        } catch (QueryException $e) {
            $existing = Organization::where('domain', $domain)->first();

            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OrganizationServiceTest`
Expected: PASS, 4 tests.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint app/Services/OrganizationService.php config/organizations.php
git add config/organizations.php app/Services/OrganizationService.php tests/Unit/OrganizationServiceTest.php
git commit -m "feat(org): resolve organizations from verified email domains"
```

---

### Task 4: The cascade rule

This is the heart of the feature: §3 of the brief — full persistence, upward review, top-down active overwrite.

**Files:**
- Modify: `app/Services/OrganizationService.php`
- Test: `tests/Feature/OrgCascadeTest.php`

**Interfaces:**
- Consumes: `OrganizationService::resolveForEmail()` from Task 3, models from Task 1.
- Produces: `OrganizationService::recordContext(Organization $org, User $user, int $rank, array $profile, ?array $transcript = null): OrgContextVersion` and `OrganizationService::VALID_RANKS` (an `int[]` const).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OrgCascadeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgCascadeTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        $this->service = app(OrganizationService::class);
    }

    private function member(Organization $org, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'user_type' => 'customer',
            'organization_id' => $org->id,
        ]);
    }

    public function test_the_first_context_becomes_active(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $analyst = $this->member($org, 'analyst@acme.com');

        $version = $this->service->recordContext($org, $analyst, 10, ['role' => 'Business Analyst']);

        $this->assertSame($version->id, $org->fresh()->active_context_id);
    }

    public function test_a_higher_rank_overwrites_the_active_baseline(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $analyst = $this->member($org, 'analyst@acme.com');
        $ceo = $this->member($org, 'ceo@acme.com');

        $draft = $this->service->recordContext($org, $analyst, 10, ['role' => 'Business Analyst']);
        $executive = $this->service->recordContext($org, $ceo, 50, ['role' => 'CEO']);

        $this->assertSame($executive->id, $org->fresh()->active_context_id);
        $this->assertSame('CEO', $org->fresh()->activeContext->profile['role']);

        // The superseded draft is still readable — nothing is hard-deleted.
        $this->assertDatabaseHas('org_context_versions', ['id' => $draft->id, 'rank' => 10]);
        $this->assertCount(2, $org->fresh()->versions);
    }

    public function test_a_lower_rank_does_not_overwrite_the_active_baseline(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $ceo = $this->member($org, 'ceo@acme.com');
        $analyst = $this->member($org, 'analyst@acme.com');

        $executive = $this->service->recordContext($org, $ceo, 50, ['role' => 'CEO']);
        $junior = $this->service->recordContext($org, $analyst, 10, ['role' => 'Business Analyst']);

        $this->assertSame($executive->id, $org->fresh()->active_context_id);
        $this->assertNotSame($junior->id, $org->fresh()->active_context_id);
    }

    public function test_a_lower_rank_still_persists_its_own_input(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $ceo = $this->member($org, 'ceo@acme.com');
        $analyst = $this->member($org, 'analyst@acme.com');

        $this->service->recordContext($org, $ceo, 50, ['role' => 'CEO']);
        $junior = $this->service->recordContext($org, $analyst, 10, ['role' => 'Business Analyst']);

        // "Never lose data": the input is stored even though it does not govern.
        $this->assertDatabaseHas('org_context_versions', [
            'id' => $junior->id,
            'user_id' => $analyst->id,
            'rank' => 10,
        ]);
    }

    public function test_an_equal_rank_refreshes_the_active_baseline(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $first = $this->member($org, 'vp1@acme.com');
        $second = $this->member($org, 'vp2@acme.com');

        $this->service->recordContext($org, $first, 40, ['role' => 'VP Sales']);
        $newer = $this->service->recordContext($org, $second, 40, ['role' => 'VP Product']);

        $this->assertSame($newer->id, $org->fresh()->active_context_id);
    }

    public function test_recording_a_context_sets_the_users_rank(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $ceo = $this->member($org, 'ceo@acme.com');

        $this->service->recordContext($org, $ceo, 50, ['role' => 'CEO']);

        $this->assertSame(50, (int) $ceo->fresh()->hierarchy_rank);
    }

    public function test_an_invalid_rank_is_rejected_and_writes_nothing(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = $this->member($org, 'someone@acme.com');

        try {
            $this->service->recordContext($org, $user, 99, ['role' => 'Emperor']);
            $this->fail('Expected an InvalidArgumentException for rank 99.');
        } catch (\InvalidArgumentException $e) {
            // Validation runs before the transaction, so nothing may have been written.
            $this->assertDatabaseCount('org_context_versions', 0);
            $this->assertNull($user->fresh()->hierarchy_rank);
            $this->assertNull($org->fresh()->active_context_id);
        }
    }

    public function test_the_interview_transcript_is_persisted(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = $this->member($org, 'ceo@acme.com');

        $transcript = [
            ['question' => 'What is your role?', 'answer' => 'CEO'],
            ['question' => 'How large is the org?', 'answer' => '4,000 people'],
        ];

        $version = $this->service->recordContext($org, $user, 50, ['role' => 'CEO'], $transcript);

        $this->assertSame($transcript, $version->fresh()->transcript);
    }

    public function test_two_organizations_do_not_see_each_others_context(): void
    {
        $acme = Organization::create(['domain' => 'acme.com']);
        $globex = Organization::create(['domain' => 'globex.com']);

        $acmeCeo = $this->member($acme, 'ceo@acme.com');
        $globexCeo = $this->member($globex, 'ceo@globex.com');

        $this->service->recordContext($acme, $acmeCeo, 50, ['role' => 'Acme CEO']);
        $this->service->recordContext($globex, $globexCeo, 50, ['role' => 'Globex CEO']);

        $this->assertSame('Acme CEO', $acme->fresh()->activeContext->profile['role']);
        $this->assertSame('Globex CEO', $globex->fresh()->activeContext->profile['role']);
        $this->assertCount(1, $acme->fresh()->versions);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OrgCascadeTest`
Expected: FAIL — `Call to undefined method App\Services\OrganizationService::recordContext()`.

- [ ] **Step 3: Implement the cascade rule**

Add to `app/Services/OrganizationService.php` — the `use` statements first:

```php
use App\Models\OrgContextVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
```

Then the constant, at the top of the class body:

```php
    /**
     * The only ranks this feature recognises. LLM output is untrusted, so any
     * rank crossing into persistence is checked against this list.
     */
    public const VALID_RANKS = [10, 20, 30, 40, 50, 60];
```

Then the method:

```php
    /**
     * Append a context declaration and re-evaluate which one governs.
     *
     * The insert is unconditional — every input from every hierarchy level is
     * preserved, per the brief's "Full Input Persistence" rule. Only the active
     * pointer is contested, and the highest rank wins it. Ties go to the newer
     * declaration so a peer refreshing a stale baseline needs no escalation.
     */
    public function recordContext(
        Organization $org,
        User $user,
        int $rank,
        array $profile,
        ?array $transcript = null
    ): OrgContextVersion {
        if (! in_array($rank, self::VALID_RANKS, true)) {
            throw new \InvalidArgumentException("Unrecognised hierarchy rank: {$rank}");
        }

        return DB::transaction(function () use ($org, $user, $rank, $profile, $transcript) {
            // Serialise concurrent calibrations for this organization. Without the
            // lock, two members confirming at the same moment each decide against a
            // pre-commit snapshot, and the lower rank can land last and govern — the
            // exact failure this rule exists to prevent. Real on MySQL; Laravel
            // compiles it to an empty string on SQLite, so tests are unaffected.
            $locked = Organization::whereKey($org->id)->lockForUpdate()->first();

            $version = OrgContextVersion::create([
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'rank' => $rank,
                'profile' => $profile,
                'transcript' => $transcript,
            ]);

            $user->forceFill(['hierarchy_rank' => $rank])->save();

            $active = $locked?->activeContext;

            if (! $active || $rank >= $active->rank) {
                // Written through the caller's instance so it stays in sync with
                // the database for the rest of the request.
                $org->forceFill(['active_context_id' => $version->id])->save();
            }

            return $version;
        });
    }
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OrgCascadeTest`
Expected: PASS, 8 tests.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint app/Services/OrganizationService.php
git add app/Services/OrganizationService.php tests/Feature/OrgCascadeTest.php
git commit -m "feat(org): add the rank cascade rule for the active context baseline"
```

---

### Task 5: Assign an organization at registration

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisterController.php` (in `register()`, after `$data = $request->validated();`)
- Test: `tests/Feature/OrgRegistrationTest.php`

**Interfaces:**
- Consumes: `OrganizationService::resolveForEmail()` from Task 3.
- Produces: every newly registered user has a non-null `organization_id`; the first registrant on a domain is that organization's `owner_user_id`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OrgRegistrationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgRegistrationTest extends TestCase
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

    public function test_the_first_registrant_on_a_domain_owns_the_organization(): void
    {
        $service = app(OrganizationService::class);
        $org = $service->resolveForEmail('anoop@acme.com');

        $first = User::factory()->create(['email' => 'anoop@acme.com', 'user_type' => 'customer']);
        $service->attachUser($first, $org);

        $this->assertSame($org->id, $first->fresh()->organization_id);
        $this->assertSame($first->id, $org->fresh()->owner_user_id);
    }

    public function test_a_later_registrant_joins_without_taking_ownership(): void
    {
        $service = app(OrganizationService::class);
        $org = $service->resolveForEmail('anoop@acme.com');

        $first = User::factory()->create(['email' => 'anoop@acme.com', 'user_type' => 'customer']);
        $service->attachUser($first, $org);

        $second = User::factory()->create(['email' => 'raghu@acme.com', 'user_type' => 'customer']);
        $service->attachUser($second, $org);

        $this->assertSame($org->id, $second->fresh()->organization_id);
        $this->assertSame($first->id, $org->fresh()->owner_user_id);
    }

    public function test_the_organization_name_is_seeded_from_the_first_members_company(): void
    {
        $service = app(OrganizationService::class);
        $org = $service->resolveForEmail('anoop@acme.com');

        $first = User::factory()->create([
            'email' => 'anoop@acme.com',
            'user_type' => 'customer',
            'company_name' => 'Acme Corporation',
        ]);
        $service->attachUser($first, $org);

        $this->assertSame('Acme Corporation', $org->fresh()->name);
    }

    public function test_a_user_without_an_email_gets_an_isolated_organization(): void
    {
        // Registration allows phone-only signup, so email may be null. Such a
        // user must still get an organization, and must not share one with any
        // other email-less user.
        $service = app(OrganizationService::class);

        $first = User::factory()->create(['email' => null, 'phone' => '+15550001', 'user_type' => 'customer']);
        $second = User::factory()->create(['email' => null, 'phone' => '+15550002', 'user_type' => 'customer']);

        $orgOne = $service->resolveForUser($first);
        $orgTwo = $service->resolveForUser($second);

        $this->assertNotSame($orgOne->id, $orgTwo->id);
        $this->assertSame('user:'.$first->id, $orgOne->domain);
    }

    public function test_a_phone_only_registration_gets_an_isolated_organization(): void
    {
        // Registration allows signup with no email at all. This exercises the real
        // controller path, not just the service, because that wiring is where an
        // empty address would otherwise reach resolveForEmail() and throw.
        $this->post(route('register'), [
            'name' => 'Phone Only',
            'phone' => '+15550100',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $user = User::where('phone', 'like', '%5550100%')->first();

        $this->assertNotNull($user, 'Phone-only registration did not create the user.');
        $this->assertNotNull($user->organization_id, 'Phone-only user got no organization.');
        $this->assertSame('user:'.$user->id, Organization::find($user->organization_id)->domain);
    }

    public function test_a_registered_user_is_attached_to_an_organization(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Anoop Kumar',
            'email' => 'anoop@newco.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $user = User::where('email', 'anoop@newco.com')->first();

        $this->assertNotNull($user, 'Registration did not create the user.');
        $this->assertNotNull($user->organization_id, 'Registration did not attach an organization.');
        $this->assertSame('newco.com', Organization::find($user->organization_id)->domain);
        $this->assertNull($user->hierarchy_rank, 'Rank is set at interview confirmation, not registration.');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OrgRegistrationTest`
Expected: FAIL — `Call to undefined method App\Services\OrganizationService::attachUser()`.

- [ ] **Step 3: Add attachUser to the service**

Add to `app/Services/OrganizationService.php`:

```php
    /**
     * Put a user in an organization, claiming ownership if it is unowned.
     *
     * Ownership goes to whoever registers first on the domain, independent of
     * who finishes calibrating first — so it is settled here, not in the
     * interview.
     */
    /**
     * Resolve the organization for a user account.
     *
     * Registration permits phone-only signup ("email" => "nullable"), so a user
     * may have no address at all. Those users get their own singleton
     * organization keyed on their id rather than an exception: a colon cannot
     * appear in a domain, so "user:12" can never collide with a real one, and
     * the isolation guarantee holds exactly as it does for free-domain users.
     */
    public function resolveForUser(User $user): Organization
    {
        $email = strtolower(trim((string) $user->email));

        return $email === ''
            ? $this->firstOrCreateDomain('user:'.$user->id)
            : $this->resolveForEmail($email);
    }

    public function attachUser(User $user, Organization $org): void
    {
        // ponytail: the owner_user_id read-then-write is unlocked, unlike
        // recordContext(). Safe only because org creation and the ownership claim
        // happen inside one request's transaction today. If a caller ever
        // pre-creates an unowned org (invites, admin provisioning), wrap this in
        // a transaction with lockForUpdate() the way recordContext() does.
        $user->forceFill(['organization_id' => $org->id])->save();

        $updates = [];

        if (empty($org->owner_user_id)) {
            $updates['owner_user_id'] = $user->id;
        }

        if (empty($org->name) && ! empty($user->company_name)) {
            $updates['name'] = $user->company_name;
        }

        if ($updates !== []) {
            $org->forceFill($updates)->save();
        }
    }
```

- [ ] **Step 4: Wire it into registration**

In `app/Http/Controllers/Auth/RegisterController.php`, add the import:

```php
use App\Services\OrganizationService;
```

Change the `register()` signature to inject the service:

```php
    public function register(
        UserRegistrationStoreReqeust $request,
        UserService $userService,
        OrganizationService $organizationService
    ) {
```

Then, immediately after the existing `$user = $userService->storeUser($data);` line and before the `storeUserAsSubscriber` call, insert:

```php
            // Organization membership is settled at registration from the email
            // domain; hierarchy rank is set later, on interview confirmation.
            // Note: the address is not confirmed at this point, and email
            // verification is a site setting that can be disabled entirely.
            $organizationService->attachUser($user, $organizationService->resolveForUser($user));
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OrgRegistrationTest`
Expected: PASS, 4 tests.

If `test_a_registered_user_is_attached_to_an_organization` fails on a mailer or settings lookup rather than on the assertion, add `$this->withoutExceptionHandling();` at the top of that test to surface the real error before adjusting anything.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint app/Services/OrganizationService.php app/Http/Controllers/Auth/RegisterController.php
git add app/Services/OrganizationService.php app/Http/Controllers/Auth/RegisterController.php tests/Feature/OrgRegistrationTest.php
git commit -m "feat(org): attach an organization at registration"
```

---

### Task 6: Inject org context into every StrategiStudio prompt

This is the payoff step. Doing it before the interview exists means backfilled and manually-set contexts already reach the engine.

**Files:**
- Modify: `app/Services/AI/DocumentContextService.php` (add `orgContextBlock()`, call it from `buildSystemMessage()` at line 84)
- Modify: `app/Http/Controllers/Backend/AI/AiChatController.php:716` (prepend the block to the inline system message)
- Test: `tests/Feature/OrgContextInjectionTest.php`

**Interfaces:**
- Consumes: `Organization::activeContext` from Task 1, `User::organization()` from Task 2.
- Produces: `DocumentContextService::orgContextBlock($user): string` — returns `''` when the user has no organization or no active context.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OrgContextInjectionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\AI\DocumentContextService;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgContextInjectionTest extends TestCase
{
    use RefreshDatabase;

    private DocumentContextService $docs;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        $this->docs = app(DocumentContextService::class);
    }

    private function calibratedUser(): User
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);
        $user = User::factory()->create([
            'email' => 'ceo@acme.com',
            'user_type' => 'customer',
            'organization_id' => $org->id,
        ]);

        app(OrganizationService::class)->recordContext($org, $user, 50, [
            'role' => 'Chief Executive Officer',
            'rank' => 50,
            'scale' => '4,000 employees across four regions',
            'governance' => 'Quarterly OKRs reviewed by the exec committee',
            'frictions' => ['Slow regional handoffs', 'Unclear ownership of adoption metrics'],
        ]);

        return $user->fresh();
    }

    public function test_the_block_carries_the_active_context(): void
    {
        $block = $this->docs->orgContextBlock($this->calibratedUser());

        $this->assertStringContainsString('ORGANIZATIONAL CONTEXT', $block);
        $this->assertStringContainsString('Chief Executive Officer', $block);
        $this->assertStringContainsString('4,000 employees across four regions', $block);
        $this->assertStringContainsString('Quarterly OKRs', $block);
        $this->assertStringContainsString('Slow regional handoffs', $block);
    }

    public function test_an_uncalibrated_user_yields_an_empty_block(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);

        $this->assertSame('', $this->docs->orgContextBlock($user));
    }

    public function test_an_org_without_an_active_context_yields_an_empty_block(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create(['user_type' => 'customer', 'organization_id' => $org->id]);

        $this->assertSame('', $this->docs->orgContextBlock($user));
    }

    public function test_a_null_user_yields_an_empty_block(): void
    {
        $this->assertSame('', $this->docs->orgContextBlock(null));
    }

    public function test_a_backfilled_profile_renders_without_stray_labels(): void
    {
        // The backfill writes governance => '' and frictions => [].
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create(['user_type' => 'customer', 'organization_id' => $org->id]);

        app(OrganizationService::class)->recordContext($org, $user, 30, [
            'role' => 'Director',
            'rank' => 30,
            'scale' => '200 employees — Software',
            'governance' => '',
            'frictions' => [],
            'summary_bullets' => ['Company: Acme'],
        ]);

        $block = $this->docs->orgContextBlock($user->fresh());

        $this->assertStringContainsString('Director', $block);
        $this->assertStringNotContainsString('Governance model', $block);
        $this->assertStringNotContainsString('Key execution friction', $block);
    }

    public function test_the_block_is_bounded_so_one_user_cannot_inflate_every_prompt(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create(['user_type' => 'customer', 'organization_id' => $org->id]);

        app(OrganizationService::class)->recordContext($org, $user, 50, [
            'role' => 'CEO',
            'rank' => 50,
            'scale' => str_repeat('very large ', 500),
            'governance' => str_repeat('committee ', 500),
            'frictions' => array_fill(0, 50, str_repeat('friction ', 100)),
            'summary_bullets' => [],
        ]);

        $block = $this->docs->orgContextBlock($user->fresh());

        // Bounded well under a kilobyte-scale ceiling rather than growing freely.
        $this->assertLessThan(3000, mb_strlen($block));
        // The full text is still preserved in the database, untruncated.
        $this->assertGreaterThan(4000, mb_strlen($org->fresh()->activeContext->profile['scale']));
    }

    public function test_build_system_message_includes_the_org_block(): void
    {
        $message = $this->docs->buildSystemMessage($this->calibratedUser());

        $this->assertStringContainsString('ORGANIZATIONAL CONTEXT', $message);
        $this->assertStringContainsString('Chief Executive Officer', $message);
        $this->assertStringContainsString('strategy assistant', $message);
    }

    public function test_build_system_message_is_unchanged_for_an_uncalibrated_user(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);

        $this->assertStringNotContainsString('ORGANIZATIONAL CONTEXT', $this->docs->buildSystemMessage($user));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OrgContextInjectionTest`
Expected: FAIL — `Call to undefined method App\Services\AI\DocumentContextService::orgContextBlock()`.

- [ ] **Step 3: Add orgContextBlock to DocumentContextService**

Add this method to `app/Services/AI/DocumentContextService.php`, directly above the existing `buildSystemMessage()`:

```php
    /** Per-field character cap for the org block. */
    private const ORG_FIELD_CHARS = 300;

    /** Most friction points rendered into a prompt. */
    private const ORG_MAX_FRICTIONS = 5;

    /**
     * Build the "ORGANIZATIONAL CONTEXT" block for a user's organization.
     *
     * The active baseline is the one declared by the highest-ranking calibrated
     * member — the executive vision is the working truth for everyone
     * downstream. Returns an empty string when there is nothing to say, exactly
     * as SearchUserChat::additionalContextBlock() does.
     *
     * Bounded on purpose. This block goes into EVERY system message on EVERY
     * turn, unlike document text which is sent in full only on the first
     * message. Without a cap, one long governance answer would inflate every
     * request that organization ever makes, on a paid API. Truncation is at
     * render time only — the full text stays in org_context_versions, so the
     * persistence guarantee is untouched.
     */
    public function orgContextBlock($user): string
    {
        $version = optional(optional($user)->organization)->activeContext;

        if (! $version || empty($version->profile)) {
            return '';
        }

        $profile = $version->profile;

        $block = "\n\n--- ORGANIZATIONAL CONTEXT (ACTIVE BASELINE) ---\n";

        foreach ([
            'role' => ['Declared by', self::ORG_FIELD_CHARS],
            'scale' => ['Organizational scale', self::ORG_FIELD_CHARS],
            'governance' => ['Governance model', self::ORG_FIELD_CHARS],
        ] as $key => [$label, $limit]) {
            if (! empty($profile[$key])) {
                $block .= $label.': '.mb_substr((string) $profile[$key], 0, $limit)."\n";
            }
        }

        if (! empty($profile['frictions']) && is_array($profile['frictions'])) {
            $block .= "Key execution friction:\n";

            foreach (array_slice($profile['frictions'], 0, self::ORG_MAX_FRICTIONS) as $friction) {
                $block .= '- '.mb_substr((string) $friction, 0, self::ORG_FIELD_CHARS)."\n";
            }
        }

        return $block."--- END ORGANIZATIONAL CONTEXT ---\n";
    }
```

- [ ] **Step 4: Call it from buildSystemMessage**

In the same file, change `buildSystemMessage()` to prepend the block ahead of the document context:

```php
    public function buildSystemMessage($user, string $base = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.'): string
    {
        $documents = $this->forUser($user);
        $context   = $this->buildContext($documents);

        return $base . $this->orgContextBlock($user) . ($context !== '' ? $context : '');
    }
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OrgContextInjectionTest`
Expected: PASS, 6 tests.

- [ ] **Step 6: Add the block to the one hand-rolled call site**

`users_new_chat_ask()` builds its own system message rather than calling `buildSystemMessage()`, and must keep doing so — it deliberately sends full document text only on the first message and document *names* on follow-ups. Routing it through `buildSystemMessage()` would send full document bodies every turn.

In `app/Http/Controllers/Backend/AI/AiChatController.php`, find this line (near line 716):

```php
        $systemMessage = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.';
```

and append the org block immediately after it:

```php
        $systemMessage = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.';
        $systemMessage .= $this->docs->orgContextBlock($user);
```

- [ ] **Step 7: Run the full suite to confirm nothing regressed**

Run: `vendor/bin/phpunit`
Expected: PASS. The pre-existing StrategiStudio tests (`ExpectedStateTest`, `DriftDetectionTest`, `ObservedStateTest`, `AlignmentInterventionTest`) must still pass — their users have no organization, so the block is empty and their prompts are byte-identical to before.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint app/Services/AI/DocumentContextService.php app/Http/Controllers/Backend/AI/AiChatController.php
git add app/Services/AI/DocumentContextService.php app/Http/Controllers/Backend/AI/AiChatController.php tests/Feature/OrgContextInjectionTest.php
git commit -m "feat(org): inject the active org context into StrategiStudio prompts"
```

---

### Task 7: The onboarding agent

**Files:**
- Create: `app/Services/AI/OnboardingAgentService.php`
- Test: `tests/Feature/OnboardingAgentTest.php`

**Interfaces:**
- Consumes: `AiProviderService::generate(string $system, string $userText, int $maxOutputTokens = 3000, float $temperature = 0.7, bool $jsonMode = false)`, plus its `extractText($response): string` and `parseJson(string $text): ?array`. `OrgContextVersion` from Task 1.
- Produces:
  - `OnboardingAgentService::SEED_QUESTION` (string const)
  - `nextQuestion(array $turns, ?OrgContextVersion $existing): string`
  - `summarize(array $turns, ?OrgContextVersion $existing): ?array` — returns the validated profile array, or `null` when the model's output is unusable.

  `$turns` is a list of `['question' => string, 'answer' => string]`, oldest first.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OnboardingAgentTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\AI\AiProviderService;
use App\Services\AI\OnboardingAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnboardingAgentTest extends TestCase
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

    /** Build the agent with a stubbed provider that returns fixed text. */
    private function agentReturning(string $text): OnboardingAgentService
    {
        $provider = $this->createMock(AiProviderService::class);
        $provider->method('generate')->willReturn(new Response(new \GuzzleHttp\Psr7\Response(200, [], '{}')));
        $provider->method('extractText')->willReturn($text);
        $provider->method('parseJson')->willReturnCallback(
            fn ($t) => is_array($d = json_decode((string) $t, true)) ? $d : null
        );

        return new OnboardingAgentService($provider);
    }

    public function test_the_seed_question_is_the_agreed_copy(): void
    {
        $this->assertSame(
            'To help SkillTricks anchor its intelligence in your daily reality: What is your current role, and what specific team or area of the organization do you directly drive or influence?',
            OnboardingAgentService::SEED_QUESTION
        );
    }

    public function test_a_follow_up_question_is_returned_as_a_single_line(): void
    {
        $agent = $this->agentReturning("  How many people sit under that remit today?  \n");

        $question = $agent->nextQuestion([
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'Head of Learning & OD'],
        ], null);

        $this->assertSame('How many people sit under that remit today?', $question);
    }

    public function test_a_multi_line_answer_is_collapsed_to_the_first_question(): void
    {
        // Rule 1 of the brief: one question per turn, never a list. If the model
        // sends more than one line anyway, only the first survives.
        $agent = $this->agentReturning("How many people report to you?\n- And what is your budget?\n- And your tenure?");

        $question = $agent->nextQuestion([
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'COO'],
        ], null);

        $this->assertSame('How many people report to you?', $question);
        $this->assertStringNotContainsString('budget', $question);
    }

    public function test_summarize_returns_a_validated_profile(): void
    {
        $agent = $this->agentReturning(json_encode([
            'role' => 'Chief Operating Officer',
            'rank' => 50,
            'scale' => '4,000 employees',
            'governance' => 'Quarterly OKRs',
            'frictions' => ['Slow handoffs', 'Unclear ownership'],
            'summary_bullets' => ['Drives operations globally', 'Owns quarterly OKR cadence'],
        ]));

        $profile = $agent->summarize([
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'COO'],
        ], null);

        $this->assertSame('Chief Operating Officer', $profile['role']);
        $this->assertSame(50, $profile['rank']);
        $this->assertSame(['Slow handoffs', 'Unclear ownership'], $profile['frictions']);
        $this->assertCount(2, $profile['summary_bullets']);
    }

    public function test_an_out_of_ladder_rank_is_clamped_to_the_floor(): void
    {
        // The model is untrusted at this boundary. An unrecognised rank must not
        // reach the cascade rule and must never grant unearned seniority.
        $agent = $this->agentReturning(json_encode([
            'role' => 'Supreme Leader',
            'rank' => 999,
            'scale' => 'unknown',
            'governance' => 'unknown',
            'frictions' => [],
            'summary_bullets' => ['Claims an unrecognised rank'],
        ]));

        $profile = $agent->summarize([['question' => 'q', 'answer' => 'a']], null);

        $this->assertSame(10, $profile['rank']);
    }

    public function test_unparseable_output_returns_null(): void
    {
        $agent = $this->agentReturning('I am afraid I cannot do that.');

        $this->assertNull($agent->summarize([['question' => 'q', 'answer' => 'a']], null));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OnboardingAgentTest`
Expected: FAIL — `Class "App\Services\AI\OnboardingAgentService" not found`.

- [ ] **Step 3: Write the agent**

Create `app/Services/AI/OnboardingAgentService.php`:

```php
<?php

namespace App\Services\AI;

use App\Models\OrgContextVersion;
use App\Services\OrganizationService;

class OnboardingAgentService
{
    /**
     * Turn 1 is fixed copy, so it costs no round trip.
     */
    public const SEED_QUESTION = 'To help SkillTricks anchor its intelligence in your daily reality: What is your current role, and what specific team or area of the organization do you directly drive or influence?';

    /** Questions asked before the confirmation turn. Seed + 2 dynamic. */
    public const QUESTION_TURNS = 3;

    public function __construct(protected AiProviderService $ai) {}

    /**
     * Ask the next single question, adapted to what the user has already said.
     */
    public function nextQuestion(array $turns, ?OrgContextVersion $existing): string
    {
        $prompt = $this->transcriptText($turns)
            ."\n\nAsk your next question now. Output the question alone, on one line, with nothing else.";

        $text = $this->ai->extractText($this->ai->generate($this->systemPrompt($existing), $prompt, 200, 0.6));

        return $this->firstLine($text);
    }

    /**
     * Turn 4: extract the structured profile for the confirmation card.
     * Returns null if the model's output cannot be used.
     */
    public function summarize(array $turns, ?OrgContextVersion $existing): ?array
    {
        $prompt = $this->transcriptText($turns)."\n\n".<<<'EOT'
The interview is over. Return ONLY a JSON object, no prose and no code fences:
{
  "role": "their role, in their own words",
  "rank": 10 | 20 | 30 | 40 | 50 | 60,
  "scale": "organizational scale in one phrase",
  "governance": "how decisions get made, in one phrase",
  "frictions": ["their key execution friction points"],
  "summary_bullets": ["3-5 short bullets summarising this profile for confirmation"]
}

Choose rank from this ladder, based on the authority they described:
10 individual contributor or analyst
20 manager or team lead
30 director or senior manager
40 VP or head of function
50 C-Suite
60 Board
EOT;

        $text = $this->ai->extractText($this->ai->generate($this->systemPrompt($existing), $prompt, 1200, 0.4, true));
        $data = $this->ai->parseJson($text);

        if (! is_array($data) || empty($data['role'])) {
            return null;
        }

        return [
            'role' => (string) $data['role'],
            'rank' => $this->clampRank($data['rank'] ?? null),
            'scale' => (string) ($data['scale'] ?? ''),
            'governance' => (string) ($data['governance'] ?? ''),
            'frictions' => $this->stringList($data['frictions'] ?? []),
            'summary_bullets' => $this->stringList($data['summary_bullets'] ?? []),
        ];
    }

    /**
     * The brief's agent instructions, plus the existing baseline when one exists
     * so a later, higher-ranked user reviews and refines rather than starts blank.
     */
    private function systemPrompt(?OrgContextVersion $existing): string
    {
        $prompt = <<<'EOT'
You are the Organizational Intelligence (OI) Calibration Agent for SkillTricks.

Your goal is to interview a newly registered user over 3 to 4 turns to identify
their role, authority, organizational scale, governance model, and key execution
friction.

Behavioral Rules:
1. Ask ONE clear, peer-to-peer question per response. Never send lists.
2. Adapt dynamically based on the user's answers.
3. Limit conversation to 3-4 turns total.
4. On the final turn, present a bulleted summary of their profile for
   single-click confirmation.
EOT;

        if ($existing && ! empty($existing->profile)) {
            $prompt .= "\n\nAn existing organizational baseline is already on record:\n"
                .json_encode($existing->profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                ."\n\nThis user may outrank whoever set it. Have them review and refine "
                ."this baseline rather than starting from nothing. Ask about what looks "
                ."stale or wrong from where they sit.";
        }

        return $prompt;
    }

    private function transcriptText(array $turns): string
    {
        $lines = [];

        foreach ($turns as $turn) {
            $lines[] = 'You asked: '.($turn['question'] ?? '');
            $lines[] = 'They answered: '.($turn['answer'] ?? '');
        }

        return implode("\n", $lines);
    }

    /**
     * Rule 1 says one question, never a list. If the model sends more anyway,
     * keep only the first line so the UI contract holds.
     */
    private function firstLine(string $text): string
    {
        foreach (preg_split('/\R/', trim($text)) ?: [] as $line) {
            $line = trim($line);

            if ($line !== '') {
                return $line;
            }
        }

        return '';
    }

    /**
     * Model output is untrusted. An unrecognised rank falls to the floor rather
     * than granting seniority nobody verified.
     */
    private function clampRank($rank): int
    {
        $rank = (int) $rank;

        return in_array($rank, OrganizationService::VALID_RANKS, true) ? $rank : 10;
    }

    /** @return string[] */
    private function stringList($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (is_scalar($item) && trim((string) $item) !== '') {
                $out[] = trim((string) $item);
            }
        }

        return $out;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OnboardingAgentTest`
Expected: PASS, 6 tests.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint app/Services/AI/OnboardingAgentService.php
git add app/Services/AI/OnboardingAgentService.php tests/Feature/OnboardingAgentTest.php
git commit -m "feat(onboarding): add the OI calibration agent"
```

---

### Task 8: The onboarding HTTP surface

**Files:**
- Create: `app/Http/Controllers/Backend/OnboardingController.php`
- Create: `resources/views/backend/pages/onboarding.blade.php`
- Modify: `routes/backend.php` (inside the `['prefix' => 'dashboard', 'middleware' => ['auth', 'verified']]` group at line 149, next to the chat routes near line 287)
- Test: `tests/Feature/OnboardingFlowTest.php`

**Interfaces:**
- Consumes: `OnboardingAgentService` (Task 7), `OrganizationService::recordContext()` (Task 4).
- Produces: routes `onboarding.index` (GET `dashboard/onboarding`), `onboarding.answer` (POST `dashboard/onboarding/answer`), `onboarding.confirm` (POST `dashboard/onboarding/confirm`).

**Security note for this task:** the confirm endpoint reads the rank from the **session-stored** summary, never from the request body. Accepting a posted rank would let any user POST `rank=60` and take over their organization's baseline without an interview. This is the trust boundary for the whole feature — do not simplify it away.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OnboardingFlowTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\AI\OnboardingAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    private function customer(): User
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        return User::factory()->create([
            'email' => 'ceo@acme.com',
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
        ]);
    }

    public function test_the_interview_opens_with_the_seed_question(): void
    {
        $response = $this->actingAs($this->customer())->get(route('onboarding.index'));

        $response->assertOk();
        $response->assertSee('anchor its intelligence in your daily reality', false);
    }

    public function test_confirm_uses_the_session_profile_not_the_request_body(): void
    {
        $user = $this->customer();

        // A profile the interview actually produced, at rank 10.
        $this->withSession(['onboarding.profile' => [
            'role' => 'Business Analyst',
            'rank' => 10,
            'scale' => '12 people',
            'governance' => 'Weekly standups',
            'frictions' => ['Unclear priorities'],
            'summary_bullets' => ['Analyst on the ops team'],
        ], 'onboarding.turns' => [
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'Business Analyst'],
        ]])->actingAs($user)
            ->post(route('onboarding.confirm'), ['rank' => 60, 'role' => 'Board Member'])
            ->assertRedirect();

        // The posted rank 60 must be ignored entirely.
        $this->assertSame(10, (int) $user->fresh()->hierarchy_rank);
        $this->assertDatabaseHas('org_context_versions', ['user_id' => $user->id, 'rank' => 10]);
        $this->assertDatabaseMissing('org_context_versions', ['user_id' => $user->id, 'rank' => 60]);
    }

    public function test_confirm_without_a_session_profile_is_rejected(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->post(route('onboarding.confirm'), ['rank' => 60])
            ->assertRedirect(route('onboarding.index'));

        $this->assertNull($user->fresh()->hierarchy_rank);
        $this->assertDatabaseCount('org_context_versions', 0);
    }

    public function test_confirm_records_the_context_and_marks_the_user_calibrated(): void
    {
        $user = $this->customer();

        $this->withSession(['onboarding.profile' => [
            'role' => 'Chief Executive Officer',
            'rank' => 50,
            'scale' => '4,000 employees',
            'governance' => 'Quarterly OKRs',
            'frictions' => ['Slow handoffs'],
            'summary_bullets' => ['Runs the company'],
        ], 'onboarding.turns' => [
            ['question' => OnboardingAgentService::SEED_QUESTION, 'answer' => 'CEO'],
        ]])->actingAs($user)->post(route('onboarding.confirm'));

        $user = $user->fresh();

        $this->assertSame(50, (int) $user->hierarchy_rank);
        $this->assertSame(
            'Chief Executive Officer',
            $user->organization->fresh()->activeContext->profile['role']
        );
        // The transcript is preserved alongside the profile.
        $this->assertNotNull($user->organization->fresh()->activeContext->transcript);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OnboardingFlowTest`
Expected: FAIL — `Route [onboarding.index] not defined`.

- [ ] **Step 3: Write the controller**

Create `app/Http/Controllers/Backend/OnboardingController.php`:

```php
<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\AI\OnboardingAgentService;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    private const TURNS_KEY = 'onboarding.turns';

    private const PROFILE_KEY = 'onboarding.profile';

    private const FAILURES_KEY = 'onboarding.failures';

    public function __construct(
        protected OnboardingAgentService $agent,
        protected OrganizationService $organizations,
    ) {
        $this->middleware('auth');
    }

    /** Turn 1: fixed copy, no LLM call. */
    public function index(Request $request)
    {
        $turns = $request->session()->get(self::TURNS_KEY, []);

        return view('backend.pages.onboarding', [
            'turns' => $turns,
            'question' => $turns === [] ? OnboardingAgentService::SEED_QUESTION : end($turns)['question'],
            'profile' => $request->session()->get(self::PROFILE_KEY),
        ]);
    }

    /**
     * Record an answer and either ask the next question or produce the
     * confirmation card.
     */
    public function answer(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:2000',
            'answer' => 'required|string|max:4000',
        ]);

        $turns = $request->session()->get(self::TURNS_KEY, []);
        $turns[] = ['question' => $validated['question'], 'answer' => $validated['answer']];
        $request->session()->put(self::TURNS_KEY, $turns);

        $existing = optional(optional($request->user())->organization)->activeContext;

        if (count($turns) < OnboardingAgentService::QUESTION_TURNS) {
            return response()->json([
                'done' => false,
                'question' => $this->agent->nextQuestion($turns, $existing),
            ]);
        }

        $profile = $this->agent->summarize($turns, $existing);

        if ($profile === null) {
            $failures = $request->session()->increment(self::FAILURES_KEY);

            // Two bad summaries in a row: fall back to a minimal form rather than
            // locking the user out of the platform on a provider hiccup.
            return response()->json([
                'done' => false,
                'fallback' => $failures >= 2,
                'question' => $failures >= 2
                    ? 'One more time, in your own words: what is your role, and how senior is it?'
                    : 'Sorry, could you say that once more?',
            ]);
        }

        $request->session()->put(self::PROFILE_KEY, $profile);
        $request->session()->forget(self::FAILURES_KEY);

        return response()->json(['done' => true, 'profile' => $profile]);
    }

    /**
     * Commit the calibration.
     *
     * The rank comes from the session profile the agent produced, never from the
     * request body — otherwise any user could POST rank 60 and take over their
     * organization's active baseline without an interview.
     */
    public function confirm(Request $request)
    {
        $profile = $request->session()->get(self::PROFILE_KEY);
        $user = $request->user();

        if (! is_array($profile) || empty($profile['role'])) {
            flash(localize('Please finish the calibration before confirming.'))->error();

            return redirect()->route('onboarding.index');
        }

        $org = $user->organization
            ?: $this->organizations->resolveForUser($user);

        if (empty($user->organization_id)) {
            $this->organizations->attachUser($user, $org);
        }

        try {
            $this->organizations->recordContext(
                $org,
                $user,
                (int) $profile['rank'],
                $profile,
                $request->session()->get(self::TURNS_KEY, [])
            );
        } catch (\InvalidArgumentException $e) {
            Log::warning('Onboarding produced an invalid rank', [
                'user_id' => $user->id,
                'rank' => $profile['rank'] ?? null,
            ]);

            flash(localize('Something went wrong saving your profile. Please try again.'))->error();

            return redirect()->route('onboarding.index');
        }

        $request->session()->forget([self::TURNS_KEY, self::PROFILE_KEY, self::FAILURES_KEY]);

        flash(localize('Calibration complete.'))->success();

        return redirect()->route('newusers-new-chat.index');
    }
}
```

- [ ] **Step 4: Register the routes**

In `routes/backend.php`, add the import next to the other `Backend` controller imports:

```php
use App\Http\Controllers\Backend\OnboardingController;
```

Then, inside the `['prefix' => 'dashboard', 'middleware' => ['auth', 'verified']]` group (opened at line 149), directly above the `// chat` comment near line 286, add:

```php
                // onboarding calibration
                Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
                Route::post('/onboarding/answer', [OnboardingController::class, 'answer'])->name('onboarding.answer');
                Route::post('/onboarding/confirm', [OnboardingController::class, 'confirm'])->name('onboarding.confirm');
```

- [ ] **Step 5: Write the view**

Create `resources/views/backend/pages/onboarding.blade.php`:

```blade
@extends('backend.layouts.master')

@section('title')
    {{ localize('Calibration') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-3">{{ localize('Let us calibrate SkillTricks to your organization') }}</h4>

                            <div id="oi-thread" class="mb-3"></div>

                            <div id="oi-ask">
                                <p id="oi-question" class="fw-semibold">{{ $question }}</p>
                                <textarea id="oi-answer" class="form-control mb-2" rows="3"
                                          placeholder="{{ localize('Type your answer...') }}"></textarea>
                                <button id="oi-send" class="btn btn-primary">{{ localize('Send') }}</button>
                            </div>

                            <div id="oi-card" class="d-none">
                                <h5 class="mb-2">{{ localize('Here is what we heard') }}</h5>
                                <ul id="oi-bullets" class="mb-3"></ul>
                                <form method="POST" action="{{ route('onboarding.confirm') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        {{ localize('Confirm & Begin Strategic Mapping') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
<script>
(function () {
    const thread   = document.getElementById('oi-thread');
    const askBox   = document.getElementById('oi-ask');
    const card     = document.getElementById('oi-card');
    const bullets  = document.getElementById('oi-bullets');
    const questionEl = document.getElementById('oi-question');
    const answerEl = document.getElementById('oi-answer');
    const sendBtn  = document.getElementById('oi-send');

    function appendTurn(question, answer) {
        const block = document.createElement('div');
        block.className = 'mb-3';
        const q = document.createElement('p');
        q.className = 'text-muted mb-1';
        q.textContent = question;
        const a = document.createElement('p');
        a.className = 'mb-0';
        a.textContent = answer;
        block.append(q, a);
        thread.append(block);
    }

    sendBtn.addEventListener('click', async function () {
        const answer = answerEl.value.trim();
        if (!answer) return;

        const question = questionEl.textContent;
        sendBtn.disabled = true;

        try {
            const res = await fetch("{{ route('onboarding.answer') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ question: question, answer: answer }),
            });

            const data = await res.json();
            appendTurn(question, answer);
            answerEl.value = '';

            if (data.done) {
                askBox.classList.add('d-none');
                (data.profile.summary_bullets || []).forEach(function (text) {
                    const li = document.createElement('li');
                    li.textContent = text;
                    bullets.append(li);
                });
                card.classList.remove('d-none');
            } else {
                questionEl.textContent = data.question;
            }
        } finally {
            sendBtn.disabled = false;
        }
    });
})();
</script>
@endsection
```

Note: bullet and answer text is written with `textContent`, never `innerHTML` — the strings come from an LLM and from user input, so neither is trusted markup.

- [ ] **Step 6: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OnboardingFlowTest`
Expected: PASS, 4 tests.

If `test_the_interview_opens_with_the_seed_question` fails while rendering `backend.layouts.master` because of a missing setting or package, add `$this->withoutMiddleware();` to `setUp()` and assert on the controller's view data via `$response->assertViewHas('question')` instead of `assertSee`.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/OnboardingController.php routes/backend.php
git add app/Http/Controllers/Backend/OnboardingController.php resources/views/backend/pages/onboarding.blade.php routes/backend.php tests/Feature/OnboardingFlowTest.php
git commit -m "feat(onboarding): add the calibration interview flow"
```

---

### Task 9: Switch the dashboard gate

Only now is it safe to redirect users at `dashboard/onboarding` — the page exists.

**Files:**
- Modify: `app/Http/Controllers/Backend/DashboardController.php:89`
- Modify: `resources/views/backend/inc/userSidebarMenus.blade.php:12`
- Test: `tests/Feature/OnboardingGateTest.php`

**Interfaces:**
- Consumes: `users.hierarchy_rank` (Task 2), route `onboarding.index` (Task 8).
- Produces: no new interfaces. The eight-field profile check is replaced by a calibration check.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OnboardingGateTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingGateTest extends TestCase
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

    public function test_an_uncalibrated_customer_is_sent_to_onboarding(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => null,
        ]);

        $this->actingAs($user)
            ->get(route('writebot.dashboard'))
            ->assertRedirect(route('onboarding.index'));
    }

    public function test_a_calibrated_customer_reaches_the_dashboard(): void
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => 30,
        ]);

        $this->actingAs($user)
            ->get(route('writebot.dashboard'))
            ->assertOk();
    }

    public function test_a_calibrated_customer_with_an_empty_profile_form_is_not_blocked(): void
    {
        // The eight profile fields no longer gate anything.
        $org = Organization::create(['domain' => 'acme.com']);
        $user = User::factory()->create([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => 10,
            'company_name' => null,
            'about_company' => null,
        ]);

        $this->actingAs($user)
            ->get(route('writebot.dashboard'))
            ->assertOk();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OnboardingGateTest`
Expected: FAIL — the uncalibrated customer is redirected to `dashboard/profile`, not `dashboard/onboarding`.

- [ ] **Step 3: Change the controller gate**

In `app/Http/Controllers/Backend/DashboardController.php`, replace the eight-field condition at line 89:

```php
           if($user->name && $user->phone && $user->company_name && $user->company_address && $user->number_employess && $user->chat_role_categories && $user->company_category && $user->about_company){
             return $view;
           }else{
             return redirect('dashboard/profile');
           }
```

with:

```php
           // Calibration, not paperwork, is the gate. The profile page stays
           // editable at dashboard/profile; it just no longer blocks anyone.
           if ($user->organization_id && $user->hierarchy_rank) {
             return $view;
           } else {
             return redirect()->route('onboarding.index');
           }
```

- [ ] **Step 4: Change the sidebar gate**

In `resources/views/backend/inc/userSidebarMenus.blade.php`, replace line 12:

```blade
@if($user->name && $user->phone && $user->company_name && $user->company_address && $user->number_employess && $user->chat_role_categories && $user->company_category && $user->about_company)
```

with:

```blade
@if($user->organization_id && $user->hierarchy_rank)
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OnboardingGateTest`
Expected: PASS, 3 tests.

- [ ] **Step 6: Run the full suite**

Run: `vendor/bin/phpunit`
Expected: PASS. Watch for pre-existing tests that hit `writebot.dashboard` with users that have no `hierarchy_rank` — if any now redirect where they previously did not, set `hierarchy_rank` on that test's user rather than weakening the gate.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint app/Http/Controllers/Backend/DashboardController.php
git add app/Http/Controllers/Backend/DashboardController.php resources/views/backend/inc/userSidebarMenus.blade.php tests/Feature/OnboardingGateTest.php
git commit -m "feat(onboarding): gate on calibration instead of the profile form"
```

---

### Task 10: Owner rank override

**Files:**
- Create: `resources/views/backend/pages/partials/org-members.blade.php`
- Modify: `app/Http/Controllers/Backend/DashboardController.php` (the `profile()` method, to pass members; plus a new `updateMemberRank()` method)
- Modify: `resources/views/backend/pages/profile.blade.php` (include the partial)
- Modify: `routes/backend.php`
- Test: `tests/Feature/OrgRankOverrideTest.php`

**Interfaces:**
- Consumes: `OrganizationService::recordContext()` is *not* used here — a rank correction is not a new declaration. It writes `users.hierarchy_rank` and re-evaluates the active pointer directly via a new service method.
- Produces: `OrganizationService::setMemberRank(User $owner, User $member, int $rank): void`, route `organization.member-rank` (POST `dashboard/organization/member-rank`).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OrgRankOverrideTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgRankOverrideTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        $this->service = app(OrganizationService::class);
    }

    /** @return array{0: Organization, 1: User, 2: User} */
    private function orgWithOwnerAndMember(): array
    {
        $org = Organization::create(['domain' => 'acme.com']);
        $owner = User::factory()->create(['email' => 'owner@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $member = User::factory()->create(['email' => 'member@acme.com', 'user_type' => 'customer', 'organization_id' => $org->id]);
        $org->forceFill(['owner_user_id' => $owner->id])->save();

        return [$org, $owner, $member];
    }

    public function test_the_owner_can_correct_a_members_rank(): void
    {
        [$org, $owner, $member] = $this->orgWithOwnerAndMember();
        $this->service->recordContext($org, $member, 50, ['role' => 'Claimed CEO']);

        $this->service->setMemberRank($owner, $member, 10);

        $this->assertSame(10, (int) $member->fresh()->hierarchy_rank);
    }

    public function test_correcting_a_rank_downward_demotes_their_context(): void
    {
        [$org, $owner, $member] = $this->orgWithOwnerAndMember();
        $this->service->recordContext($org, $owner, 30, ['role' => 'Director']);
        $this->service->recordContext($org, $member, 50, ['role' => 'Claimed CEO']);

        // The overclaimed context is active before the correction.
        $this->assertSame('Claimed CEO', $org->fresh()->activeContext->profile['role']);

        $this->service->setMemberRank($owner, $member, 10);

        // After the correction the highest legitimate rank governs again.
        $this->assertSame('Director', $org->fresh()->activeContext->profile['role']);
        // The overclaimed input itself is still on record — nothing is deleted.
        $this->assertDatabaseHas('org_context_versions', ['user_id' => $member->id, 'rank' => 50]);
    }

    public function test_a_non_owner_cannot_change_a_rank(): void
    {
        [$org, , $member] = $this->orgWithOwnerAndMember();
        $impostor = User::factory()->create(['user_type' => 'customer', 'organization_id' => $org->id]);
        $member->forceFill(['hierarchy_rank' => 20])->save();

        $this->expectException(\RuntimeException::class);
        $this->service->setMemberRank($impostor, $member, 60);
    }

    public function test_an_owner_cannot_change_a_rank_in_another_organization(): void
    {
        [, $owner, ] = $this->orgWithOwnerAndMember();
        $otherOrg = Organization::create(['domain' => 'globex.com']);
        $outsider = User::factory()->create(['user_type' => 'customer', 'organization_id' => $otherOrg->id]);

        $this->expectException(\RuntimeException::class);
        $this->service->setMemberRank($owner, $outsider, 10);
    }

    public function test_an_invalid_rank_is_rejected(): void
    {
        [, $owner, $member] = $this->orgWithOwnerAndMember();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->setMemberRank($owner, $member, 77);
    }

    public function test_the_endpoint_rejects_a_non_owner(): void
    {
        [$org, , $member] = $this->orgWithOwnerAndMember();
        $impostor = User::factory()->create([
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => 10,
        ]);

        $this->actingAs($impostor)
            ->post(route('organization.member-rank'), ['user_id' => $member->id, 'rank' => 60])
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OrgRankOverrideTest`
Expected: FAIL — `Call to undefined method App\Services\OrganizationService::setMemberRank()`.

- [ ] **Step 3: Add setMemberRank to the service**

Add to `app/Services/OrganizationService.php`:

```php
    /**
     * Let an organization's owner correct a member's self-declared rank.
     *
     * A correction is not a new declaration, so no version row is written. The
     * member's existing inputs stay on record; only which one governs can change.
     */
    public function setMemberRank(User $owner, User $member, int $rank): void
    {
        // Known limitation: a corrected member who runs the interview again can
        // re-declare the higher rank, and recordContext() will take it. The owner
        // can correct it again, and every claim stays on record. Locking a
        // corrected rank is deferred until it is actually asked for.
        if (! in_array($rank, self::VALID_RANKS, true)) {
            throw new \InvalidArgumentException("Unrecognised hierarchy rank: {$rank}");
        }

        $org = $member->organization;

        if (! $org || (int) $org->owner_user_id !== (int) $owner->id) {
            throw new \RuntimeException('Only the organization owner can change a member rank.');
        }

        DB::transaction(function () use ($org, $member, $rank) {
            $member->forceFill(['hierarchy_rank' => $rank])->save();

            $this->recomputeActiveContext($org);
        });
    }

    /**
     * Re-elect the governing context after a rank correction: the highest-ranked
     * version whose declarer still holds at least that rank today.
     */
    private function recomputeActiveContext(Organization $org): void
    {
        $ranks = User::where('organization_id', $org->id)
            ->pluck('hierarchy_rank', 'id');

        $winner = $org->versions()
            ->orderByDesc('rank')
            ->orderByDesc('id')
            ->get()
            ->first(fn ($version) => (int) ($ranks[$version->user_id] ?? 0) >= (int) $version->rank);

        $org->forceFill(['active_context_id' => $winner?->id])->save();
    }
```

- [ ] **Step 4: Add the controller method**

In `app/Http/Controllers/Backend/DashboardController.php`, add the imports:

```php
use App\Services\OrganizationService;
use App\Models\User;
```

(`DashboardController` does not currently import `App\Models\User`, so there is no collision.)

Add this method next to `updateProfile()`:

```php
    # organization owner corrects a member's hierarchy rank
    public function updateMemberRank(Request $request, OrganizationService $organizations)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'rank' => 'required|integer|in:'.implode(',', OrganizationService::VALID_RANKS),
        ]);

        $member = User::find($validated['user_id']);

        if (! $member) {
            abort(404);
        }

        try {
            $organizations->setMemberRank(auth()->user(), $member, (int) $validated['rank']);
        } catch (\RuntimeException $e) {
            abort(403);
        }

        flash(localize('Rank updated.'))->success();

        return back();
    }
```

And in the existing `profile()` method, pass the members list. Replace:

```php
        return view('backend.pages.profile', compact('user','chatrolecategories'));
```

with:

```php
        $orgMembers = ($user->organization && (int) $user->organization->owner_user_id === (int) $user->id)
            ? $user->organization->members()->orderBy('name')->get()
            : collect();

        return view('backend.pages.profile', compact('user','chatrolecategories','orgMembers'));
```

- [ ] **Step 5: Add the route**

In `routes/backend.php`, next to the onboarding routes added in Task 8:

```php
                Route::post('/organization/member-rank', [DashboardController::class, 'updateMemberRank'])->name('organization.member-rank');
```

`routes/backend.php:47` already imports `DashboardController`, so no new import is needed.

- [ ] **Step 6: Write the partial**

Create `resources/views/backend/pages/partials/org-members.blade.php`:

```blade
@if($orgMembers->isNotEmpty())
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="mb-3">{{ localize('Organization members') }}</h5>
            <p class="text-muted small">
                {{ localize('As the owner of this organization you can correct a member\'s declared seniority. The highest rank sets the active strategic context for everyone.') }}
            </p>

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>{{ localize('Name') }}</th>
                        <th>{{ localize('Email') }}</th>
                        <th>{{ localize('Rank') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orgMembers as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td colspan="2">
                            <form method="POST" action="{{ route('organization.member-rank') }}" class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $member->id }}">
                                <select name="rank" class="form-control">
                                    @foreach([10 => 'Individual Contributor', 20 => 'Manager', 30 => 'Director', 40 => 'Vice President', 50 => 'C-Suite', 60 => 'Board'] as $value => $label)
                                        <option value="{{ $value }}" {{ (int) $member->hierarchy_rank === $value ? 'selected' : '' }}>
                                            {{ localize($label) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-primary">{{ localize('Save') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
```

- [ ] **Step 7: Include the partial**

In `resources/views/backend/pages/profile.blade.php`, immediately before the closing `@endsection` of the `contents` section, add:

```blade
    @includeWhen(isset($orgMembers), 'backend.pages.partials.org-members')
```

- [ ] **Step 8: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OrgRankOverrideTest`
Expected: PASS, 6 tests.

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint app/Services/OrganizationService.php app/Http/Controllers/Backend/DashboardController.php routes/backend.php
git add app/Services/OrganizationService.php app/Http/Controllers/Backend/DashboardController.php resources/views/backend/pages/partials/org-members.blade.php resources/views/backend/pages/profile.blade.php routes/backend.php tests/Feature/OrgRankOverrideTest.php
git commit -m "feat(org): let the organization owner correct a member rank"
```

---

### Task 11: Backfill existing users

**Files:**
- Create: `database/migrations/2026_08_22_000005_backfill_organizations_from_users.php`
- Test: `tests/Feature/OrgBackfillTest.php`

**Interfaces:**
- Consumes: everything from Tasks 1–4.
- Produces: no new interfaces. After this migration, users with a complete profile are calibrated without re-interviewing.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OrgBackfillTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
        (new \Database\Migrations\BackfillRunner)->run();
    }

    public function test_a_complete_profile_is_calibrated_without_an_interview(): void
    {
        $user = User::factory()->create([
            'email' => 'anoop@acme.com',
            'user_type' => 'customer',
            'company_name' => 'Acme Corporation',
            'company_address' => '1 Acme Way',
            'number_employess' => '1000-10000',
            'chat_role_categories' => 'C-Suite',
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
    }

    public function test_an_unmatched_role_falls_to_the_rank_floor(): void
    {
        $user = User::factory()->create([
            'email' => 'someone@acme.com',
            'user_type' => 'customer',
            'company_name' => 'Acme',
            'company_address' => '1 Acme Way',
            'number_employess' => '0-10',
            'chat_role_categories' => 'Chief Vibes Officer',
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
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter OrgBackfillTest`
Expected: FAIL — `Class "Database\Migrations\BackfillRunner" not found`.

- [ ] **Step 3: Register the support namespace**

The backfill logic lives in a named class so both the migration and the test can
invoke it — an anonymous migration class cannot be re-run from a test.

In `composer.json`, add one entry under `autoload.psr-4` (the map currently holds
`App\\`, `Modules\\`, `Database\\Factories\\`, and `Database\\Seeders\\`):

```json
            "Database\\Migrations\\": "database/migrations/support/"
```

Then run:

```bash
composer dump-autoload
```

- [ ] **Step 4: Write the backfill runner**

Create `database/migrations/support/BackfillRunner.php`:

```php
<?php

namespace Database\Migrations;

use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Support\Facades\DB;

class BackfillRunner
{
    /** The eight fields the old profile gate required. */
    private const REQUIRED = [
        'name', 'phone', 'company_name', 'company_address',
        'number_employess', 'chat_role_categories', 'company_category', 'about_company',
    ];

    public function run(): void
    {
        $service = app(OrganizationService::class);
        $ladder = DB::table('chat_role_categories')->whereNotNull('rank')->pluck('rank', 'name');

        User::orderBy('id')->chunkById(200, function ($users) use ($service, $ladder) {
            foreach ($users as $user) {
                if (! empty($user->organization_id)) {
                    continue;
                }

                // resolveForUser handles email-less accounts (phone-only signup)
                // by giving them their own singleton organization.
                $org = $service->resolveForUser($user);
                $service->attachUser($user, $org);

                if (! $this->profileIsComplete($user)) {
                    // Membership is assigned but rank is not, so the gate routes
                    // them into the interview on next login.
                    continue;
                }

                $rank = (int) ($ladder[$user->chat_role_categories] ?? 10);
                $rank = in_array($rank, OrganizationService::VALID_RANKS, true) ? $rank : 10;

                $service->recordContext($org, $user, $rank, [
                    'role' => (string) $user->chat_role_categories,
                    'rank' => $rank,
                    'scale' => trim($user->number_employess.' employees — '.$user->company_category),
                    'governance' => '',
                    'frictions' => [],
                    'summary_bullets' => array_values(array_filter([
                        $user->company_name ? 'Company: '.$user->company_name : null,
                        $user->company_address ? 'Based in: '.$user->company_address : null,
                        $user->about_company ? 'About: '.$user->about_company : null,
                    ])),
                ], null); // a null transcript marks this row as backfilled, not interviewed
            }
        });
    }

    private function profileIsComplete(User $user): bool
    {
        foreach (self::REQUIRED as $field) {
            if (empty($user->{$field})) {
                return false;
            }
        }

        return true;
    }
}
```

- [ ] **Step 5: Write the migration**

Create `database/migrations/2026_08_22_000005_backfill_organizations_from_users.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        (new \Database\Migrations\BackfillRunner)->run();
    }

    public function down()
    {
        // organizations and org_context_versions are dropped by their own
        // migrations; only the pointers on users need clearing here.
        DB::table('users')->update(['organization_id' => null, 'hierarchy_rank' => null]);
    }
};
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter OrgBackfillTest`
Expected: PASS, 4 tests.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint database/migrations/support/BackfillRunner.php
composer dump-autoload
git add database/migrations/2026_08_22_000005_backfill_organizations_from_users.php database/migrations/support/BackfillRunner.php composer.json tests/Feature/OrgBackfillTest.php
git commit -m "feat(org): backfill organizations and ranks from existing profiles"
```

---

### Task 12: Full verification

**Files:** none changed.

- [ ] **Step 1: Run the whole suite**

Run: `vendor/bin/phpunit`
Expected: PASS, no failures and no errors.

- [ ] **Step 2: Run the security suite that CI gates on**

Run: `vendor/bin/phpunit tests/Feature/Security/`
Expected: PASS.

- [ ] **Step 3: Run static analysis**

Run: `vendor/bin/phpstan analyse --no-progress --memory-limit=2G`
Expected: PASS. The baseline suppresses pre-existing issues, so any reported problem is new code. Fix it rather than adding to the baseline.

- [ ] **Step 4: Confirm formatting is clean**

Run: `vendor/bin/pint --test`
Expected: PASS.

- [ ] **Step 5: Verify migrations apply to a fresh database**

Run: `php artisan migrate:fresh --env=testing`
Expected: all migrations run in order with no errors. This is the closest local check on what staging will do on deploy.

- [ ] **Step 6: Report status**

Report which tasks are complete, the full-suite result verbatim, and anything left outstanding. Do not push to `main` — that autodeploys to staging and runs migrations there.

---

## Manual verification on staging

After the branch is merged and deployed, confirm the parts no test covers, because they depend on the live LLM provider:

1. Register a new user on a fresh corporate domain. Confirm the redirect lands on `dashboard/onboarding`, not `dashboard/profile`.
2. Confirm turn 1 shows the seed copy exactly, and that turns 2 and 3 ask **one** question each with no bullet lists.
3. Confirm the turn-4 card renders bullets and the **Confirm & Begin Strategic Mapping** button.
4. After confirming, open StrategiStudio and verify the reply reflects the stated role and friction points — this is the injection from Task 6 working end to end.
5. Register a second, lower-ranked user on the same domain. Confirm they are shown the existing baseline to review, and that confirming does **not** change `organizations.active_context_id`.
6. Register a higher-ranked user on the same domain. Confirm the active pointer moves and both earlier rows are still present in `org_context_versions`.

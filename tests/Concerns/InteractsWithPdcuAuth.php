<?php

namespace Tests\Concerns;

use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\FacilitatorSector;
use App\Models\Framework;
use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

/**
 * Test helpers for the v2 auth feature: seed a Passport personal-access client
 * (so real token issuance works against the isolated test DB) and create PDCU
 * users with the real `users` column shape (full_name/phone_number/role/…),
 * setting attributes directly since those columns are intentionally not in the
 * model's $fillable.
 */
trait InteractsWithPdcuAuth
{
    protected function seedPersonalAccessClient(): void
    {
        app(ClientRepository::class)->createPersonalAccessClient(
            null,
            'PDCU Mobile Test',
            'http://localhost'
        );
    }

    protected function makeUser(array $attrs = [], ?string $role = null): User
    {
        $user = new User();
        $user->full_name = $attrs['full_name'] ?? 'Test User';
        $user->email = $attrs['email'] ?? 'user_'.Str::lower(Str::random(8)).'@pdcu.gov.ng';
        $user->phone_number = $attrs['phone_number'] ?? '+2348000000000';
        $user->password = $attrs['password'] ?? 'secret123';
        $user->image_url = $attrs['image_url'] ?? '';
        $user->role = 0;

        if (array_key_exists('must_change_password', $attrs)) {
            $user->must_change_password = $attrs['must_change_password'];
        }

        $user->save();

        if ($role !== null) {
            UserRole::create([
                'user_id' => $user->id,
                'role' => $role,
                'target_entity' => $attrs['target_entity'] ?? 'System',
                'entity_id' => $attrs['entity_id'] ?? 0,
                'role_status' => 'Active',
            ]);
        }

        return $user;
    }

    /** A user who is Sector Head of a specific sector. */
    protected function makeSectorHead(Sector $sector, array $attrs = []): User
    {
        return $this->makeUser(
            array_merge($attrs, ['target_entity' => 'Sector', 'entity_id' => $sector->id]),
            'Sector Head'
        );
    }

    /** A user who is Data Admin of a specific sector. */
    protected function makeDataAdmin(Sector $sector, array $attrs = []): User
    {
        return $this->makeUser(
            array_merge($attrs, ['target_entity' => 'Sector', 'entity_id' => $sector->id]),
            'Data Admin'
        );
    }

    /** A facilitator assigned (via facilitator_sectors) to a specific sector. */
    protected function makeFacilitator(Sector $sector, array $attrs = []): User
    {
        // target_entity defaults to 'System' in makeUser(); a real Facilitator
        // role row has target_entity='Sector' (see UserRole::roleToTargetEntity).
        // Setting it correctly here avoids fixtures that look like a System
        // Admin to any code that checks target_entity.
        $user = $this->makeUser(array_merge(['target_entity' => 'Sector'], $attrs), 'Facilitator');
        FacilitatorSector::create([
            'user_role_id' => $user->getCurrentRole()->id,
            'sector_id' => $sector->id,
        ]);

        return $user;
    }

    // --- hierarchy seeding (real column shapes) ------------------------------

    protected function makeFramework(array $attrs = []): Framework
    {
        // The baseline data migration seeds a 2024 framework, and `year` is unique.
        // Normalize so the test controls exactly one Active framework.
        Framework::query()->update(['status' => 'Archived']);

        return Framework::updateOrCreate(
            ['year' => $attrs['year'] ?? 2024],
            array_merge(['title' => 'FY 2024', 'status' => 'Active'], $attrs),
        );
    }

    protected function makeSector(Framework $framework, array $attrs = []): Sector
    {
        return Sector::create(array_merge([
            'sector_name' => 'Health',
            'ministry' => 'Ministry of Health',
            'status' => 'active',
            'framework_id' => $framework->id,
        ], $attrs));
    }

    protected function makeCommitment(Sector $sector, array $attrs = []): Commitment
    {
        return Commitment::create(array_merge([
            'name' => 'Maternal Health Expansion',
            'status' => 'In Progress',
            'sector_id' => $sector->id,
            'framework_id' => $sector->framework_id,
        ], $attrs));
    }

    protected function makeDeliverable(Commitment $commitment, array $attrs = []): Deliverable
    {
        return Deliverable::create(array_merge([
            'deliverable' => 'Rural Clinic Digitization',
            'status' => 'active',
            'commitment_id' => $commitment->id,
            'framework_id' => $commitment->framework_id,
        ], $attrs));
    }

    protected function makeKpi(Deliverable $deliverable, array $attrs = []): Kpi
    {
        return Kpi::create(array_merge([
            'kpi' => 'Clinics with EHR',
            'unit_of_measurement' => '%',
            'status' => 'active',
            'year' => 2024,
            'deliverable_id' => $deliverable->id,
            'framework_id' => $deliverable->framework_id,
        ], $attrs));
    }

    protected function makeTracking(Kpi $kpi, array $attrs = []): PerformanceTracking
    {
        return PerformanceTracking::create(array_merge([
            'kpi_id' => $kpi->id,
            'framework_id' => $kpi->framework_id,
            'quarter' => 1,
            'year' => 2024,
            'milestone' => '100',
            'actual_value' => '80',
            'confirmation_status' => 'Pending Sector Head Approval',
        ], $attrs));
    }

    protected function makeKpiTarget(Kpi $kpi, int|float|string $target = 85, int $year = 2024): KpiTarget
    {
        $t = new KpiTarget();
        $t->kpi_id = $kpi->id;
        $t->year = $year;
        $t->target = $target;
        $t->status = 'active';
        $t->save();

        return $t;
    }
}

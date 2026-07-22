<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Property;
use App\Models\PropertyCoOwner;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgreementContractTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $this->owner->id]);
        PropertyCoOwner::factory()->create(['property_id' => $property->id, 'user_id' => $this->owner->id]);
        $this->unit = Unit::factory()->create(['property_id' => $property->id]);
        Sanctum::actingAs($this->owner);
    }

    public function test_plain_index_is_agreement_shape(): void
    {
        Agreement::factory()->create(['unit_id' => $this->unit->id]);
        $res = $this->getJson('/api/agreements')->assertOk();
        $this->assertSame(
            ['id', 'unitId', 'tenantId', 'startDate', 'endDate', 'rentAmount', 'depositAmount', 'lateFee', 'rentDueDay', 'status', 'createdAt'],
            array_keys($res->json()[0])
        );
    }

    public function test_expand_returns_withrefs_envelopes(): void
    {
        Agreement::factory()->create(['unit_id' => $this->unit->id]);
        $res = $this->getJson('/api/agreements?expand=unit,property,tenant')->assertOk();
        $row = $res->json()[0];
        $this->assertSame(['agreement', 'unit', 'property', 'tenant'], array_keys($row));
        $this->assertSame($this->unit->id, $row['unit']['id']);
        $this->assertArrayHasKey('coOwners', $row['property']);
        $this->assertArrayHasKey('status', $row['tenant']);
    }

    public function test_store_accepts_camel_case_input(): void
    {
        $tenant = User::factory()->tenant()->create();
        $res = $this->postJson('/api/agreements', [
            'unitId' => $this->unit->id, 'tenantId' => $tenant->id,
            'startDate' => '2026-08-01', 'endDate' => '2027-07-31',
            'rentAmount' => 180000, 'depositAmount' => 360000, 'lateFee' => 5000,
            'rentDueDay' => 1, 'status' => 'active',
        ])->assertCreated();
        $this->assertSame(180000, $res->json('rentAmount'));
        $this->assertSame('2026-08-01', $res->json('startDate'));
    }

    public function test_store_rejects_unit_of_other_owner(): void
    {
        $foreignUnit = Unit::factory()->create();
        $this->postJson('/api/agreements', [
            'unitId' => $foreignUnit->id, 'tenantId' => User::factory()->tenant()->create()->id,
            'startDate' => '2026-08-01', 'endDate' => '2027-07-31',
            'rentAmount' => 180000, 'depositAmount' => 360000, 'lateFee' => 0,
            'rentDueDay' => 1, 'status' => 'draft',
        ])->assertForbidden();
    }

    public function test_update_rejects_reassigning_to_foreign_unit(): void
    {
        $agreement = Agreement::factory()->create(['unit_id' => $this->unit->id]);
        $foreignUnit = Unit::factory()->create();

        $this->patchJson("/api/agreements/{$agreement->id}", ['unitId' => $foreignUnit->id])
            ->assertForbidden();

        $this->assertSame($this->unit->id, $agreement->fresh()->unit_id);
    }
}

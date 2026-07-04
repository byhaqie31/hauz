<?php

namespace Database\Seeders;

use App\Enums\AgreementStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PropertyType;
use App\Enums\ReporterRole;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UnitStatus;
use App\Enums\UserRole;
use App\Models\Agreement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyCoOwner;
use App\Models\Tenancy;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Demo Owner ──────────────────────────────────────────────────────
        $owner = User::factory()->create([
            'name'         => 'Aminah Yusof',
            'email'        => 'owner@roofly.my',
            'phone'        => '+60 12-345 6789',
            'role'         => UserRole::OWNER,
            'business_name' => 'AY Property Management',
            'plan_tier'    => 'free',
            'owner_preferences' => [
                'locale' => 'en',
                'theme'  => 'system',
                'money_locale' => 'en-MY',
            ],
            'notification_preferences' => [
                'events' => [
                    'rent_reminder'    => true,
                    'agreement_expiry' => true,
                    'payment_received' => true,
                    'ticket_update'    => true,
                    'invite_accepted'  => true,
                ],
                'channels' => [
                    'email'    => true,
                    'whatsapp' => false,
                    'in_app'   => true,
                ],
            ],
        ]);

        // ── Demo Tenants ────────────────────────────────────────────────────
        $tenantArif = User::factory()->create([
            'name'  => 'Arif Hakim',
            'email' => 'tenant@example.com',
            'phone' => '+60 17-888 1234',
            'role'  => UserRole::TENANT,
            'personal_info' => [
                'ic_number'      => '980101-14-5678',
                'date_of_birth'  => '1998-01-01',
                'occupation'     => 'Software Engineer',
                'employer'       => 'Axiata Digital',
                'monthly_income_cents' => 800000,
                'nationality'    => 'Malaysian',
            ],
            'emergency_contact' => [
                'name'         => 'Hakim Abdul',
                'phone'        => '+60 11-222 3333',
                'relationship' => 'Father',
            ],
        ]);

        $tenantSiti = User::factory()->create([
            'name'  => 'Siti Khadijah',
            'email' => 'siti@example.com',
            'phone' => '+60 16-777 5678',
            'role'  => UserRole::TENANT,
        ]);

        // ── Property 1: KLCC Condo (3 units) ───────────────────────────────
        $klcc = Property::create([
            'owner_id'     => $owner->id,
            'name'         => 'Suria KLCC #12-3A',
            'type'         => PropertyType::CONDO,
            'address'      => 'Level 12, Suria KLCC, Jalan Ampang',
            'city'         => 'Kuala Lumpur',
            'state'        => 'W.P. Kuala Lumpur',
            'postcode'     => '50088',
            'built_up_sqft' => 1200,
            'bedrooms'     => 3,
            'bathrooms'    => 2,
            'furnishing'   => 'fully',
            'ownership'    => [
                'title_type'    => 'freehold',
                'purchase_date' => '2018-06-15',
                'purchase_price_cents' => 120000000, // RM 1.2M
                'current_market_value_cents' => 145000000, // RM 1.45M
            ],
            'utilities' => [
                'monthly_maintenance_fee_cents' => 80000, // RM 800
                'quit_rent_annual_cents'        => 20000,
                'assessment_rate_annual_cents'  => 35000,
            ],
        ]);

        PropertyCoOwner::create([
            'property_id' => $klcc->id,
            'user_id'     => $owner->id,
            'name'        => $owner->name,
            'share_pct'   => 100.00,
            'is_primary'  => true,
        ]);

        $unitA = Unit::create([
            'property_id' => $klcc->id,
            'label'       => 'Master bedroom',
            'bedrooms'    => 1,
            'bathrooms'   => 1,
            'sqft'        => 400,
            'status'      => UnitStatus::OCCUPIED,
        ]);

        $unitB = Unit::create([
            'property_id' => $klcc->id,
            'label'       => 'Middle bedroom',
            'bedrooms'    => 1,
            'bathrooms'   => 1,
            'sqft'        => 350,
            'status'      => UnitStatus::OCCUPIED,
        ]);

        Unit::create([
            'property_id' => $klcc->id,
            'label'       => 'Small bedroom',
            'bedrooms'    => 1,
            'bathrooms'   => 1,
            'sqft'        => 300,
            'status'      => UnitStatus::VACANT,
        ]);

        // Active agreement — Arif in Unit A
        $agreementArif = Agreement::create([
            'unit_id'              => $unitA->id,
            'tenant_id'            => $tenantArif->id,
            'start_date'           => '2025-01-01',
            'end_date'             => '2026-12-31',
            'rent_amount_cents'    => 150000, // RM 1,500
            'deposit_amount_cents' => 300000, // RM 3,000
            'late_fee_cents'       => 5000,   // RM 50
            'rent_due_day'         => 5,
            'status'               => AgreementStatus::ACTIVE,
        ]);

        Tenancy::create([
            'agreement_id' => $agreementArif->id,
            'tenant_id'    => $tenantArif->id,
            'moved_in_at'  => '2025-01-01',
        ]);

        // Generate 6 months of invoices (5 paid, 1 pending)
        $invoiceNumber = 1;
        for ($m = 1; $m <= 6; $m++) {
            $dueDate  = "2025-{$m}-05";
            $isPast   = $m <= 5;
            $invoice  = Invoice::create([
                'agreement_id'   => $agreementArif->id,
                'invoice_number' => 'INV-' . str_pad($invoiceNumber++, 4, '0', STR_PAD_LEFT),
                'amount_cents'   => 150000,
                'late_fee_cents' => 0,
                'due_date'       => $dueDate,
                'status'         => $isPast ? InvoiceStatus::PAID : InvoiceStatus::PENDING,
            ]);

            if ($isPast) {
                Payment::create([
                    'invoice_id'   => $invoice->id,
                    'amount_cents' => 150000,
                    'method'       => PaymentMethod::FPX,
                    'status'       => PaymentStatus::SUCCESSFUL,
                    'paid_at'      => "2025-{$m}-04 10:00:00",
                ]);
            }
        }

        // Overdue invoice — Siti in Unit B
        $agreementSiti = Agreement::create([
            'unit_id'              => $unitB->id,
            'tenant_id'            => $tenantSiti->id,
            'start_date'           => '2025-03-01',
            'end_date'             => '2026-02-28',
            'rent_amount_cents'    => 120000,
            'deposit_amount_cents' => 240000,
            'late_fee_cents'       => 5000,
            'rent_due_day'         => 1,
            'status'               => AgreementStatus::ACTIVE,
        ]);

        Tenancy::create([
            'agreement_id' => $agreementSiti->id,
            'tenant_id'    => $tenantSiti->id,
            'moved_in_at'  => '2025-03-01',
        ]);

        Invoice::create([
            'agreement_id'   => $agreementSiti->id,
            'invoice_number' => 'INV-' . str_pad($invoiceNumber++, 4, '0', STR_PAD_LEFT),
            'amount_cents'   => 120000,
            'late_fee_cents' => 5000,
            'due_date'       => '2025-06-01',
            'status'         => InvoiceStatus::OVERDUE,
        ]);

        // ── Property 2: Wangsa Walk Shoplot ───────────────────────────────
        $shoplot = Property::create([
            'owner_id' => $owner->id,
            'name'     => 'Wangsa Walk Shoplot G-12',
            'type'     => PropertyType::SHOPLOT,
            'address'  => 'Ground floor, Wangsa Walk Mall, Jalan Wangsa Delima',
            'city'     => 'Kuala Lumpur',
            'state'    => 'W.P. Kuala Lumpur',
            'postcode' => '53300',
        ]);

        PropertyCoOwner::create([
            'property_id' => $shoplot->id,
            'user_id'     => $owner->id,
            'name'        => $owner->name,
            'share_pct'   => 100.00,
            'is_primary'  => true,
        ]);

        Unit::create([
            'property_id' => $shoplot->id,
            'label'       => 'Ground floor shop',
            'status'      => UnitStatus::VACANT,
        ]);

        // ── Maintenance Tickets ─────────────────────────────────────────────
        $ticket1 = Ticket::create([
            'unit_id'      => $unitA->id,
            'reporter_id'  => $tenantArif->id,
            'reporter_role' => ReporterRole::TENANT,
            'category'     => TicketCategory::ELECTRICAL,
            'priority'     => TicketPriority::URGENT,
            'title'        => 'Power trip in master bedroom',
            'description'  => 'The circuit breaker trips every time the air conditioner and water heater are used simultaneously. This started two days ago.',
            'status'       => TicketStatus::NEW,
        ]);

        $ticket2 = Ticket::create([
            'unit_id'      => $unitB->id,
            'reporter_id'  => $tenantSiti->id,
            'reporter_role' => ReporterRole::TENANT,
            'category'     => TicketCategory::PLUMBING,
            'priority'     => TicketPriority::HIGH,
            'title'        => 'Bathroom drain blocked',
            'description'  => 'Water is not draining in the shower. The bathroom floods after a 5-minute shower.',
            'status'       => TicketStatus::IN_PROGRESS,
        ]);

        TicketComment::create([
            'ticket_id'   => $ticket2->id,
            'author_id'   => $owner->id,
            'author_role' => ReporterRole::OWNER,
            'body'        => 'Plumber scheduled for this Friday between 10am-12pm. Please make sure someone is home.',
        ]);

        TicketComment::create([
            'ticket_id'   => $ticket2->id,
            'author_id'   => $tenantSiti->id,
            'author_role' => ReporterRole::TENANT,
            'body'        => 'Confirmed, I will be home. Thank you.',
        ]);

        $ticket3 = Ticket::create([
            'unit_id'       => $unitA->id,
            'reporter_id'   => $tenantArif->id,
            'reporter_role' => ReporterRole::TENANT,
            'category'      => TicketCategory::PLUMBING,
            'priority'      => TicketPriority::LOW,
            'title'         => 'Kitchen tap dripping',
            'description'   => 'The kitchen tap has a slow drip. Not urgent but should be fixed to save water.',
            'status'        => TicketStatus::RESOLVED,
            'resolved_at'   => now()->subDays(5),
        ]);

        TicketComment::create([
            'ticket_id'   => $ticket3->id,
            'author_id'   => $owner->id,
            'author_role' => ReporterRole::OWNER,
            'body'        => 'Fixed — replaced the washer. Let me know if it drips again.',
        ]);

        $this->command->info('Roofly demo seed complete.');
        $this->command->info("Owner login:  owner@roofly.my / password");
        $this->command->info("Tenant login: tenant@example.com / password");
    }
}

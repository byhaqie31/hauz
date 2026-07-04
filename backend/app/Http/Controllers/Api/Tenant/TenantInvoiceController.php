<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with(['payments', 'agreement.unit.property'])
            ->whereHas('agreement', fn ($q) =>
                $q->where('tenant_id', $request->user()->id)
            )
            ->orderBy('due_date', 'desc')
            ->get();

        return response()->json($invoices);
    }

    /** Simulate FPX pay → paid round-trip (mock-compatible). Phase 3: wire to Billplz. */
    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        abort_if(
            $invoice->agreement->tenant_id !== $request->user()->id,
            403
        );
        abort_if(
            $invoice->status === InvoiceStatus::PAID,
            422,
            'Invoice is already paid.'
        );

        $data = $request->validate([
            'method' => 'required|in:fpx,card,cash,transfer',
        ]);

        // TODO Phase 3: create Billplz bill, redirect to payment URL
        // For now: simulate instant success
        $payment = Payment::create([
            'invoice_id'   => $invoice->id,
            'amount_cents' => $invoice->totalDueCents(),
            'method'       => $data['method'],
            'status'       => PaymentStatus::SUCCESSFUL,
            'paid_at'      => now(),
        ]);

        $invoice->update(['status' => InvoiceStatus::PAID]);

        return response()->json([
            'payment' => $payment,
            'invoice' => $invoice->fresh(),
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api\Owner;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['agreement.unit.property', 'agreement.tenant', 'payments'])
            ->whereHas('agreement.unit.property', fn ($q) =>
                $q->where('owner_id', $request->user()->id)
            );

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('year')) {
            $query->whereYear('due_date', $request->year);
        }
        if ($request->filled('month')) {
            $query->whereMonth('due_date', $request->month);
        }

        return response()->json($query->orderBy('due_date', 'desc')->get());
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeOwner($request, $invoice);

        return response()->json($invoice->load(['agreement.unit.property', 'agreement.tenant', 'payments']));
    }

    public function updateStatus(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeOwner($request, $invoice);

        $data = $request->validate([
            'status' => 'required|in:pending,paid,overdue,cancelled',
        ]);

        $invoice->update($data);

        return response()->json($invoice);
    }

    public function send(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeOwner($request, $invoice);

        // TODO Phase 4: dispatch InvoiceSentNotification via email + WhatsApp
        return response()->json(['sent_at' => now()->toISOString()]);
    }

    public function recordPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeOwner($request, $invoice);

        $data = $request->validate([
            'amount_cents' => 'required|integer|min:1',
            'method'       => 'required|in:fpx,card,cash,transfer',
            'paid_at'      => 'required|date',
            'reference'    => 'nullable|string|max:255',
        ]);

        $payment = Payment::create(array_merge($data, [
            'invoice_id' => $invoice->id,
            'status'     => PaymentStatus::SUCCESSFUL,
        ]));

        $invoice->update(['status' => InvoiceStatus::PAID]);

        return response()->json([
            'payment' => $payment,
            'invoice' => $invoice,
        ], 201);
    }

    private function authorizeOwner(Request $request, Invoice $invoice): void
    {
        abort_if(
            $invoice->agreement->unit->property->owner_id !== $request->user()->id,
            403
        );
    }
}

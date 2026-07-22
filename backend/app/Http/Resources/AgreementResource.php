<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgreementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'unitId'        => $this->unit_id,
            'tenantId'      => $this->tenant_id,
            'startDate'     => $this->start_date->format('Y-m-d'),
            'endDate'       => $this->end_date->format('Y-m-d'),
            'rentAmount'    => $this->rent_amount_cents,
            'depositAmount' => $this->deposit_amount_cents,
            'lateFee'       => $this->late_fee_cents,
            'rentDueDay'    => $this->rent_due_day,
            'status'        => $this->status?->value,
            'createdAt'     => $this->created_at?->toISOString(),
        ];
    }
}

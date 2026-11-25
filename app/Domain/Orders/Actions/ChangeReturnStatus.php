<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\ReturnStatus;
use App\Domain\Orders\Mail\ReturnRequestStatusChanged;
use App\Domain\Orders\Models\ReturnRequest;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Moves a return request to its next status and tells the customer by e-mail.
 * Marking it refunded only records that staff already paid the money back (cash at the counter or the
 * original payment method): no money moves from here.
 */
class ChangeReturnStatus
{
    public function handle(ReturnRequest $request, ReturnStatus $to, ?string $rejectionReason = null): ReturnRequest
    {
        if (! in_array($to, $request->status->next($request->type), true)) {
            throw new DomainRuleException("No se puede pasar de «{$request->status->getLabel()}» a «{$to->getLabel()}».");
        }

        if ($to === ReturnStatus::Rejected && blank($rejectionReason)) {
            throw new DomainRuleException('Indica el motivo del rechazo: se lo enviaremos al cliente.');
        }

        return DB::transaction(function () use ($request, $to, $rejectionReason): ReturnRequest {
            $changes = ['status' => $to];

            match ($to) {
                ReturnStatus::Approved => $changes['approved_at'] = now(),
                ReturnStatus::Rejected => [$changes['rejected_at'] = now(), $changes['rejection_reason'] = trim((string) $rejectionReason)],
                ReturnStatus::Received => $changes['received_at'] = now(),
                ReturnStatus::Refunded, ReturnStatus::Exchanged => $changes['resolved_at'] = now(),
                default => null,
            };

            $request->update($changes);

            $order = $request->order;
            if ($order->email) {
                Mail::to($order->email)->locale($request->locale)->queue(new ReturnRequestStatusChanged($request));
            }

            return $request;
        });
    }
}

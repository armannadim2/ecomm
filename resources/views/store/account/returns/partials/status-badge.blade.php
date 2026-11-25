{{-- Variables: $status (ReturnStatus) --}}
<span @class([
    'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold',
    'bg-secondary-container text-secondary' => $status === \App\Domain\Orders\Enums\ReturnStatus::Requested,
    'bg-tertiary-fixed text-primary' => in_array($status, [\App\Domain\Orders\Enums\ReturnStatus::Approved, \App\Domain\Orders\Enums\ReturnStatus::Received], true),
    'bg-error-container text-on-error-container' => $status === \App\Domain\Orders\Enums\ReturnStatus::Rejected,
    'bg-success-container text-on-success-fixed-variant' => in_array($status, [\App\Domain\Orders\Enums\ReturnStatus::Refunded, \App\Domain\Orders\Enums\ReturnStatus::Exchanged], true),
])>{{ __($status->getLabel()) }}</span>

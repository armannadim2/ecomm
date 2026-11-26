<?php

namespace App\Http\Controllers;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Staff\Models\Staff;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice PDF. Customers: signed link (order page, e-mail) or their own account.
 * Staff: from the admin order page (staff guard, permission to view the order).
 * Generated from the stored, immutable invoice data, so the PDF is always the same.
 */
class InvoiceController extends Controller
{
    public function customer(Request $request, Invoice $invoice): Response
    {
        $customerId = $request->user('web')?->id;

        abort_unless($request->hasValidSignature() || ($customerId && $invoice->order()->value('customer_id') === $customerId), 404);

        return $this->pdf($invoice);
    }

    public function staff(Request $request, Invoice $invoice): Response
    {
        $staff = $request->user(Staff::GUARD);
        abort_unless($staff && $staff->can('view', $invoice->order), 403);

        return $this->pdf($invoice);
    }

    public static function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['order', 'corrects']);

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4')->output();
    }

    private function pdf(Invoice $invoice): Response
    {
        return response(self::render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->fileName().'"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}

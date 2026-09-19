<?php

namespace App\Http\Controllers;

use App\Models\Act;
use App\Models\FinanceInvoice;
use App\Services\Finance\Act\DocumentGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActPrintController extends Controller
{
    public function __construct(
        protected DocumentGenerationService $documentService
    ) {}

    /**
     * Print Act of Acceptance.
     */
    public function printAct(Act $act)
    {
        if ($act->user_id !== Auth::id()) {
            abort(403);
        }

        $act->loadMissing(['fop.bill', 'fop.fopGroup', 'customer', 'counterparty', 'invoice', 'items']);

        $fopDetails = $this->documentService->getFopDetails($act->fop);
        $customerDetails = $this->documentService->getCustomerDetails($act->customer, $act->counterparty, $act);

        return view('finance.act.print', [
            'act' => $act,
            'fopDetails' => $fopDetails,
            'customerDetails' => $customerDetails,
        ]);
    }

    /**
     * Print Invoice.
     */
    public function printInvoice(FinanceInvoice $invoice)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        $invoice->loadMissing(['fop.bill', 'fop.fopGroup', 'customer', 'counterparty', 'act']);

        $fop = $invoice->fop ?: ($invoice->customer?->fop ?: \App\Models\Fop::where('user_id', $invoice->user_id)->first());

        if (empty($invoice->contract_number)) {
            $invoice->contract_number = $invoice->counterparty?->contract_number ?: $fop?->contract_number;
        }
        if (empty($invoice->contract_date)) {
            $invoice->contract_date = $invoice->counterparty?->contract_date ?: $fop?->contract_date;
        }

        $fopDetails = $this->documentService->getFopDetails($fop);
        $customerDetails = $this->documentService->getCustomerDetails($invoice->customer, $invoice->counterparty, $invoice->act);

        return view('finance.invoice.print', [
            'invoice' => $invoice,
            'fopDetails' => $fopDetails,
            'customerDetails' => $customerDetails,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Act;
use App\Models\FinanceInvoice;
use App\Services\Finance\Act\DocumentGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ZipArchive;

class ActPrintController extends Controller
{
    public function __construct(
        protected DocumentGenerationService $documentService
    ) {}

    /**
     * Check if current user has access to resource (owner or shared budget partner).
     */
    protected function checkAccess(int $resourceUserId): void
    {
        $allowedUserIds = Auth::user()?->getSharedUserIds() ?? [Auth::id()];
        if (!in_array($resourceUserId, $allowedUserIds, true)) {
            abort(403);
        }
    }

    /**
     * Print Act of Acceptance (HTML View).
     */
    public function printAct(Act $act)
    {
        $this->checkAccess($act->user_id);

        $act->loadMissing(['fop.bill', 'fop.fopGroup', 'customer', 'counterparty', 'invoice', 'items']);

        $fopDetails = $this->documentService->getFopDetails($act->fop);
        $customerDetails = $this->documentService->getCustomerDetails($act->customer, $act->counterparty, $act);
        $filename = $this->documentService->generateActFilename($act);

        return view('finance.act.print', [
            'act' => $act,
            'fopDetails' => $fopDetails,
            'customerDetails' => $customerDetails,
            'documentTitle' => pathinfo($filename, PATHINFO_FILENAME),
            'isPdf' => false,
        ]);
    }

    /**
     * Generate & Download or Stream Act of Acceptance as clean PDF.
     */
    public function pdfAct(Act $act, Request $request)
    {
        $this->checkAccess($act->user_id);

        $act->loadMissing(['fop.bill', 'fop.fopGroup', 'customer', 'counterparty', 'invoice', 'items']);

        $fopDetails = $this->documentService->getFopDetails($act->fop);
        $customerDetails = $this->documentService->getCustomerDetails($act->customer, $act->counterparty, $act);
        $filename = $this->documentService->generateActFilename($act);

        $pdf = Pdf::loadView('finance.act.print', [
            'act' => $act,
            'fopDetails' => $fopDetails,
            'customerDetails' => $customerDetails,
            'documentTitle' => pathinfo($filename, PATHINFO_FILENAME),
            'isPdf' => true,
        ])->setPaper('a4', 'portrait')
          ->setOption([
              'defaultFont' => 'Arial',
              'enable_font_subsetting' => true,
              'isHtml5ParserEnabled' => true,
              'isRemoteEnabled' => true,
          ]);

        $response = $request->boolean('download', true)
            ? $pdf->download($filename)
            : $pdf->stream($filename);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }

    /**
     * Print Invoice (HTML View).
     */
    public function printInvoice(FinanceInvoice $invoice)
    {
        $this->checkAccess($invoice->user_id);

        $invoice->loadMissing(['fop.bill', 'fop.fopGroup', 'customer', 'counterparty', 'act.items', 'act.fop']);

        $fop = $invoice->fop ?: ($invoice->customer?->fop ?: \App\Models\Fop::where('user_id', $invoice->user_id)->first());

        if (empty($invoice->contract_number)) {
            $invoice->contract_number = $invoice->counterparty?->contract_number ?: $fop?->contract_number;
        }
        if (empty($invoice->contract_date)) {
            $invoice->contract_date = $invoice->counterparty?->contract_date ?: $fop?->contract_date;
        }

        if ($invoice->act && $invoice->act->act_date) {
            $invoice->invoice_date = $invoice->act->act_date;
        }

        $fopDetails = $this->documentService->getFopDetails($fop);
        $customerDetails = $this->documentService->getCustomerDetails($invoice->customer, $invoice->counterparty, $invoice->act);
        $filename = $this->documentService->generateInvoiceFilename($invoice);

        return view('finance.invoice.print', [
            'invoice' => $invoice,
            'fopDetails' => $fopDetails,
            'customerDetails' => $customerDetails,
            'documentTitle' => pathinfo($filename, PATHINFO_FILENAME),
            'isPdf' => false,
        ]);
    }

    /**
     * Generate & Download or Stream Invoice as clean PDF.
     */
    public function pdfInvoice(FinanceInvoice $invoice, Request $request)
    {
        $this->checkAccess($invoice->user_id);

        $invoice->loadMissing(['fop.bill', 'fop.fopGroup', 'customer', 'counterparty', 'act.items', 'act.fop']);

        $fop = $invoice->fop ?: ($invoice->customer?->fop ?: ($invoice->act?->fop ?: \App\Models\Fop::where('user_id', $invoice->user_id)->first()));

        if (empty($invoice->contract_number)) {
            $invoice->contract_number = $invoice->counterparty?->contract_number ?: $fop?->contract_number;
        }
        if (empty($invoice->contract_date)) {
            $invoice->contract_date = $invoice->counterparty?->contract_date ?: $fop?->contract_date;
        }

        if ($invoice->act && $invoice->act->act_date) {
            $invoice->invoice_date = $invoice->act->act_date;
        }

        $fopDetails = $this->documentService->getFopDetails($fop);
        $customerDetails = $this->documentService->getCustomerDetails($invoice->customer, $invoice->counterparty, $invoice->act);
        $filename = $this->documentService->generateInvoiceFilename($invoice);

        $pdf = Pdf::loadView('finance.invoice.print', [
            'invoice' => $invoice,
            'fopDetails' => $fopDetails,
            'customerDetails' => $customerDetails,
            'documentTitle' => pathinfo($filename, PATHINFO_FILENAME),
            'isPdf' => true,
        ])->setPaper('a4', 'portrait')
          ->setOption([
              'defaultFont' => 'Arial',
              'enable_font_subsetting' => true,
              'isHtml5ParserEnabled' => true,
              'isRemoteEnabled' => true,
          ]);

        $response = $request->boolean('download', true)
            ? $pdf->download($filename)
            : $pdf->stream($filename);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }

    /**
     * Export all acts for user (optionally filtered by year) into a single ZIP archive of PDFs.
     */
    public function zipActs(Request $request)
    {
        $allowedUserIds = Auth::user()?->getSharedUserIds() ?? [Auth::id()];
        $query = Act::whereIn('user_id', $allowedUserIds)
            ->with(['fop.bill', 'fop.fopGroup', 'customer', 'counterparty', 'invoice', 'items']);

        if ($year = $request->get('year')) {
            $query->whereYear('act_date', $year);
        }

        $acts = $query->orderBy('act_date', 'asc')->get();

        if ($acts->isEmpty()) {
            return back()->with('error', 'Актів для експорту не знайдено');
        }

        $zipFileName = 'Akti_' . ($year ? "{$year}_" : '') . now()->format('Y-m-d') . '.zip';
        $tempZipPath = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Не вдалося створити ZIP-архів');
        }

        $usedNames = [];
        foreach ($acts as $act) {
            $fopDetails = $this->documentService->getFopDetails($act->fop);
            $customerDetails = $this->documentService->getCustomerDetails($act->customer, $act->counterparty, $act);
            $baseFilename = $this->documentService->generateActFilename($act);

            // Avoid collisions inside ZIP archive
            if (isset($usedNames[$baseFilename])) {
                $usedNames[$baseFilename]++;
                $entryName = preg_replace('/\.pdf$/i', " ({$usedNames[$baseFilename]}).pdf", $baseFilename);
            } else {
                $usedNames[$baseFilename] = 1;
                $entryName = $baseFilename;
            }

            $pdf = Pdf::loadView('finance.act.print', [
                'act' => $act,
                'fopDetails' => $fopDetails,
                'customerDetails' => $customerDetails,
                'documentTitle' => pathinfo($baseFilename, PATHINFO_FILENAME),
                'isPdf' => true,
            ])->setPaper('a4', 'portrait')
              ->setOption([
                  'defaultFont' => 'Arial',
                  'enable_font_subsetting' => true,
                  'isHtml5ParserEnabled' => true,
                  'isRemoteEnabled' => true,
              ]);

            $zip->addFromString($entryName, $pdf->output());
        }

        $zip->close();

        return response()->download($tempZipPath, $zipFileName)->deleteFileAfterSend(true);
    }
}

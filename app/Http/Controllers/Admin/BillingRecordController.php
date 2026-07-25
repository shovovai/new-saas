<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

class BillingRecordController extends Controller
{
    public function invoices(): Response
    {
        return Inertia::render('Admin/Billing/Invoices', [
            'invoices' => Invoice::query()->with('team:id,name')->latest()->paginate(25),
        ]);
    }

    public function payments(): Response
    {
        return Inertia::render('Admin/Billing/Payments', [
            'payments' => Payment::query()->with('team:id,name')->latest()->paginate(25),
        ]);
    }
}

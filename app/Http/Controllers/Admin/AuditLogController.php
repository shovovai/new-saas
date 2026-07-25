<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with(['team:id,name', 'user:id,name'])
            ->when($request->string('action')->toString(), fn ($q, $action) => $q->where('action', 'like', "%{$action}%"))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only('action'),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        $logs   = $query->latest()->take(300)->get();
        $tables = AuditLog::distinct()->pluck('table_name')->filter()->sort()->values();

        return view('pages.audit-logs', compact('logs', 'tables'));
    }
}

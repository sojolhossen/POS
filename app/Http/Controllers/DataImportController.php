<?php

namespace App\Http\Controllers;

use App\Models\DataImport;
use App\Services\DataImportService;
use Illuminate\Http\Request;

class DataImportController extends Controller
{
    public function __construct(
        protected DataImportService $importService
    ) {}

    public function index()
    {
        $imports = DataImport::with(['creator', 'confirmer'])->orderBy('id', 'desc')->paginate(15);
        return view('imports.index', compact('imports'));
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'dataset_type' => 'required|string|in:products,opening_stock,suppliers,customers,employees',
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        try {
            $import = $this->importService->parseAndValidate(
                $request->file('file'),
                $validated['dataset_type'],
                auth()->user(),
                $validated['idempotency_key'] ?? null
            );

            return redirect()->route('imports.preview', $import)
                ->with('info', "File parsed successfully. Please review the validation summary below before confirming execution.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function preview(DataImport $import)
    {
        return view('imports.preview', compact('import'));
    }

    public function confirm(DataImport $import)
    {
        try {
            $this->importService->executeImport($import, auth()->user());
            return redirect()->route('imports.index')
                ->with('success', "Import batch {$import->import_batch_id} confirmed and successfully committed to database.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("DataImport confirm error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->with('error', $e->getMessage());
        }
    }
}

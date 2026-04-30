<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ProductImportController extends Controller
{
    public function showForm()
    {
        return view('products.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv|max:2048',
        ]);

        $import = new ProductsImport();

        try {
            Excel::import($import, $request->file('file'));
            $stats = $import->getStats();

            return redirect()->back()->with([
                'success' => true,
                'stats'   => $stats
            ]);
        } catch (ValidationException $e) {
            $failures = $e->failures();
            return redirect()->back()->with([
                'error'  => 'Import failed due to validation errors.',
                'import_errors' => $failures
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Import failed: ' . $e->getMessage()
            ]);
        }
    }
}
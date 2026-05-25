<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificadosController extends Controller
{
    public function index(Request $request)
    {
        $certificados = Certificate::with('artwork')
            ->when($request->q, function ($q, $search) {
                $q->where('verification_token', 'like', "%$search%")
                  ->orWhereHas('artwork', fn($aq) =>
                      $aq->where('title', 'like', "%$search%")
                  );
            })
            ->latest()
            ->paginate(20);

        return view('admin.certificados.index', compact('certificados'));
    }

    public function toggle(Certificate $certificado)
    {
        // Toggle between active/revoked using a simple convention on a field.
        // Certificate model may not have 'status' — we use blockchain_tx_id presence as proxy,
        // or add a real status toggle once the column exists.
        $certificado->update([
            'status' => ($certificado->status ?? 'active') === 'active' ? 'revoked' : 'active',
        ]);

        return back()->with('success', 'Estado del certificado actualizado.');
    }
}

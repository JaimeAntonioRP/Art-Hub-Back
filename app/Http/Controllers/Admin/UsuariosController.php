<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::query()
            ->when($request->q, fn($q, $search) =>
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
            )
            ->latest()
            ->paginate(25);

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function updateRole(Request $request, User $usuario)
    {
        $request->validate([
            'role' => 'required|in:user,collector,admin',
        ]);

        $usuario->update(['role' => $request->role]);

        return back()->with('success', "Rol de {$usuario->name} actualizado.");
    }
}

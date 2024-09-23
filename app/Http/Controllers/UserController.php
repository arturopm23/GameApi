<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $request)
{
    // Validar la solicitud
    $request->validate([
        'email' => 'required|email|unique:users',
        'name' => 'nullable|string|unique:users,name',
        'password' => 'required|string|min:6',
    ]);

    // Crear el usuario
    $user = User::create([
        'email' => $request->input('email'),
        'name' => $request->input('name') ?? 'Anònim', // Defecto: 'Anònim'
        'password' => Hash::make($request->input('password')),
    ]);

    // Asignar un rol predeterminado (por ejemplo, 'player')
    $user->assignRole('player');

    // Retornar una respuesta exitosa en JSON
    return response()->json([
        'message' => 'User created successfully',
        'user' => $user
    ], 201);
}


}

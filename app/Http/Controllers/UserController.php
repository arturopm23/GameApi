<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function store(Request $request)
{
    // Validar la solicitud
    $request->validate([
        'email' => 'required|email|unique:users',
        'name' => 'nullable|string|unique:users,name|max:255',
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

public function updateName(Request $request, $id)
{
    // Check if the authenticated user is the same as the user being updated
    if (auth()->id() != $id) {
        return response()->json(['message' => 'You do not have permission to update this user.'], 403);
    }

    // Validate the request
    $request->validate([
        'name' => 'required|string|unique:users,name,' . $id . '|max:255',
    ]);

    // Find the user
    $user = User::findOrFail($id);

    // Update the user's name
    $user->update([
        'name' => $request->input('name'),
    ]);

    return response()->json(['message' => 'Name updated successfully.', 'name' => $user->name], 200);
}




    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('YourAppName')->accessToken;

        return response()->json(['token' => $token]);
    }

    return response()->json(['error' => 'Unauthorized'], 401);
}

}

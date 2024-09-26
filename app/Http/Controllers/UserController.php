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
        $token = $user->createToken(env('APP_NAME'))->accessToken;

        return response()->json(['token' => $token]);
    }

    return response()->json(['error' => 'Unauthorized'], 401);
}

public function index()
{
    // Get all players
    $players = User::with('games')->get()->map(function ($player) {
        // Calculate average success percentage using the model method
        $averageSuccessPercentage = $player->calculateSuccessRate();

        return [
            'id' => $player->id,
            'name' => $player->name,
            'email' => $player->email,
            'average_success_percentage' => $averageSuccessPercentage,
        ];
        // Sort players by success rate in descending order
    $rankedPlayers = $playersWithSuccessRates->sortByDesc('success_rate');
    });

    return response()->json($players, 200);
}

public function ranking()
{
    // Get all players with their games
    $players = User::with('games')->get();

    // Calculate the total success percentage and the count of players
    $totalSuccessPercentage = 0;
    $totalPlayers = $players->count();

    foreach ($players as $player) {
        $totalSuccessPercentage += $player->calculateSuccessRate(); // Call the method you created in the User model
    }

    // Calculate average success percentage across all players
    $averageSuccessPercentage = $totalPlayers > 0 ? $totalSuccessPercentage / $totalPlayers : 0;

    return response()->json([
        'average_success_percentage' => $averageSuccessPercentage,
    ], 200);
}

public function loser()
{
    // Get all players with their games
    $players = User::with('games')->get();

    // Calculate each player's success rate
    $playersWithSuccessRates = $players->map(function ($player) {
        // Calculate average success percentage
        $totalGames = $player->games->count();
        $totalWins = $player->games->where('win', true)->count();
        $averageSuccessPercentage = $totalGames > 0 ? ($totalWins / $totalGames) * 100 : 0;

        return [
            'id' => $player->id,
            'name' => $player->name,
            'email' => $player->email,
            'success_rate' => $averageSuccessPercentage,
        ];
    });

    // Find the player with the lowest success rate
    $loser = $playersWithSuccessRates->sortBy('success_rate')->first();

    return response()->json($loser, 200);
}

public function winner()
{
    // Get all players with their games
    $players = User::with('games')->get();

    // Calculate each player's success rate
    $playersWithSuccessRates = $players->map(function ($player) {
        // Calculate average success percentage
        $totalGames = $player->games->count();
        $totalWins = $player->games->where('win', true)->count();
        $averageSuccessPercentage = $totalGames > 0 ? ($totalWins / $totalGames) * 100 : 0;

        return [
            'id' => $player->id,
            'name' => $player->name,
            'email' => $player->email,
            'success_rate' => $averageSuccessPercentage,
        ];
    });

    // Find the player with the highest success rate
    $winner = $playersWithSuccessRates->sortByDesc('success_rate')->first();

    return response()->json($winner, 200);
}

public function getGamesByPlayer($id)
{
    //Make sure is the user
    if (auth()->id() != $id) {
        return response()->json(['message' => 'You do not have permission to watch this user\'s play history.'], 403);    }
    // Find the player by ID
    $player = User::with('games')->findOrFail($id);

    // Retrieve the games of the player
    $games = $player->games;

    return response()->json($games, 200);
}


}

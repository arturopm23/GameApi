<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function rollDice(Request $request, $id)
    {
        if (auth()->id() != $id) {
            return response()->json(['message' => 'You do not have permission to roll for this player.'], 403);
        }

        // Roll two dice
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);

        // Create a new game record
        $game = Game::create([
            'user_id' => $id,
            'dice1' => $dice1,
            'dice2' => $dice2,
            'win' => $dice1 + $dice2 == 7,
        ]);

        return response()->json(['message' => 'Dice rolled successfully.', 'game' => $game], 201);
    }

    public function deleteGames($id)
    {
        // Verify the authenticated user is the same as the player ID
        if (auth()->id() != $id) {
            return response()->json(['message' => 'You do not have permission to delete games for this player.'], 403);
        }

        // Delete all games associated with the player
        Game::where('user_id', $id)->delete();

        return response()->json(['message' => 'All games deleted successfully.'], 200);
    }
}

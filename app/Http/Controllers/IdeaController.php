<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idea;
use App\Models\Contributor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class IdeaController extends Controller
{
    public function get($sessionId, $votingPhaseNumber)
    {
        Log::info('Received request to get ideas', ['sessionId' => $sessionId, 'votingPhaseNumber' => $votingPhaseNumber]);

        $ideas = Idea::where('session_id', $sessionId)->get();
        $ideasCount = $ideas->count();

        return response()->json([
            'success' => true,
            'ideas' => $ideas,
            'ideasCount' => $ideasCount
        ]);
    }
    public function store(Request $request)
    {
        // Validierung der eingehenden Anfrage
        $request->validate([
            'text_input' => 'nullable|string',
            'image_file' => 'nullable|file|mimes:png,jpeg,pdf|max:5000',
            'session_id' => 'required|exists:bf_sessions,id',
            'contributor_id' => 'required|exists:bf_contributors,id',
            'round' => 'required|integer'
        ]);
        // Verarbeite die Datei, falls vorhanden
        $imageFileUrl = null;
        if ($request->hasFile('image_file')) {
            $imageFile = $request->file('image_file');
            $fileName = time() . '' . $imageFile->getClientOriginalName();
            $filePath = $imageFile->storeAs('brainframe/images', $fileName, 'public');
            $imageFileUrl = 'storage/' . $filePath;
        }

        $contributorId = $request->input('contributor_id');
        $sessionId = $request->input('session_id');
        // Erstelle einen neuen Datensatz
        $idea = Idea::create([
            'text_input' => $request->input('text_input'),
            'image_file_url' => $imageFileUrl,
            'session_id' => $sessionId,
            'contributor_id' => $contributorId,
            'round' => $request->input('round')
        ]);
        // Rückgabe einer erfolgreichen Antwort
        return response()->json(['message' => 'Idea stored successfully', 'idea' => $idea], 201);
    }

}

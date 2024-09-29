<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Log;
use App\Events\SessionResumed;
use App\Events\SessionStarted;
use App\Events\SessionPaused;
use App\Events\SessionStopped;
use GuzzleHttp\Client;

class SessionController extends Controller
{
    public function delete(Request $request)
    {

        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');

        $session = Session::find($sessionId);

        if ($userId == $session->host_id) {
            $session->delete();
            return response()->json(['message' => 'Session erfolgreich gelöscht'], 200);
        }
        return response()->json(['message' => 'Keine Berechtigung zum Löschen'], 403);
    }
    public function alter(Request $request)
    {
        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');
        $methodId = $request->input('method_id');
        $target = $request->input('target');

        $session = Session::find($sessionId);

        if (!$session) {
            // Session existiert nicht, also erstellen wir eine neue
            $session = new Session();
            $session->id = $sessionId;
            $session->host_id = $userId;
            $session->method_id = $methodId;
            $session->target = $target;
            $session->phase = 'collecting';
            $session->is_paused = true;
            $session->save();

            return response()->json(['message' => 'Session erfolgreich erstellt'], 201);
        }

        // Session existiert, also überprüfen wir die Berechtigung zum Aktualisieren
        if ($userId == $session->host_id && ($session->phase === 'collecting' || $session->phase === null)) {
            $session->update([
                'method_id' => $methodId,
                'target' => $target
            ]);

            return response()->json(['message' => 'Session erfolgreich aktualisiert'], 200);
        }

        return response()->json(['message' => 'Keine Berechtigung zum Aktualisieren'], 403);
    }
    public function get($sessionId)
    {
        $session = Session::with(['host', 'method'])->findOrFail($sessionId);
        
        $timerKey = "timer_{$sessionId}";
        $endTime = Cache::get($timerKey);
        $secondsLeft = $endTime ? now()->diffInSeconds($endTime, false) : 0;
        
        $roundedSecondsLeft = round($secondsLeft);
        $session->update(['seconds_left' => $roundedSecondsLeft]);
        Log::info('method name type: ' . gettype($session->method->name));
        return response()->json([
            'session' => [
                'id' => $session->id,
                'method' => [
                    'id' => $session->method->id,
                    'name' => $session->method->name,
                    'description' => $session->method->description,
                    'time_limit' => $session->method->time_limit,
                    'round_limit' => $session->method->round_limit
                ],
                'target' => $session->target,
                'seconds_left' => max(0, $roundedSecondsLeft),
                'collecting_round' => $session->collecting_round,
                'vote_round' => $session->vote_round,
                'phase' => $session->phase,
                'isPaused' => $session->is_paused
            ]
        ]);
    }
    
    public function resume(Request $request)
    {
        $sessionId = $request->input('session_id');
        $session = Session::findOrFail($sessionId);
        $session->update(['is_paused' => false]);
        $this->updateCountdown($sessionId, $session->seconds_left);
        event(new SessionResumed($session->id, $session->seconds_left, $session->phase, $session->collecting_round, $session->vote_round));
        return response()->json(['message' => 'Session fortgesetzt']);
    }
    
    public function start(Request $request)
    {
        $sessionId = $request->input('session_id');
        $session = Session::findOrFail($sessionId);
        $collectingRound = $request->input('collecting_round');
        $voteRound = $request->input('vote_round');
        Log::info($collectingRound);
        $session->update(['is_paused' => false, 'seconds_left' => $session->method->time_limit, 'collecting_round'=>$collectingRound, 'vote'=>$voteRound]);
        $this->updateCountdown($sessionId, $session->seconds_left);
        event(new SessionStarted($session->id, $session->seconds_left, $session->phase, $session->collecting_round, $session->vote_round));
        return response()->json(['message' => 'Session gestartet']);
    }
    
    public function stop(Request $request)
    {
        $sessionId = $request->input('session_id');
        $voteRound = $request->input('vote_round');
        $collectingRound = $request->input('collecting_round');
        Log::info($collectingRound);
        $session = Session::findOrFail($sessionId);
        if($collectingRound >= $session->method->round_limit){
            $voteRound++;
            $session->update(['phase'=> 'voting','is_paused' => true, 'seconds_left' => 0, 'vote_round' => $voteRound, 'collecting_round'=> 0]);
        }
        else {
        $session->update(['is_paused' => true, 'seconds_left' => 0, 'vote_round' => $voteRound, 'collecting_round'=> $collectingRound]);}
        $this->stopCountdown($sessionId);
        
        event(new SessionStopped($session->id, $session->seconds_left, $session->phase, $session->collecting_round, $session->vote_round));
        return response()->json(['message' => 'Session gestoppt']);
    }
    
    public function pause(Request $request)
    {
        $sessionId = $request->input('session_id');
        $session = Session::findOrFail($sessionId);
        $timerKey = "timer_{$sessionId}";
        $endTime = Cache::get($timerKey);
        $secondsLeft = $endTime ? now()->diffInSeconds($endTime, false) : 0;
        $session->update(['is_paused' => true, 'seconds_left' => round($secondsLeft)]);
        $this->stopCountdown($sessionId);
        event(new SessionPaused($session->id, $session->seconds_left, $session->phase, $session->collecting_round, $session->vote_round));
        return response()->json(['message' => 'Session pausiert']);
    }
    
    private function updateCountdown($sessionId, $secondsLeft)
    {
        $timerKey = "timer_{$sessionId}";
        $endTime = now()->addSeconds($secondsLeft);
        Cache::put($timerKey, $endTime, $secondsLeft);
    }
    
    private function stopCountdown($sessionId)
    {
        $timerKey = "timer_{$sessionId}";
        Cache::forget($timerKey);
    }
    /* private function generateWordCloudandNextSteps($topIdeas, $ideas, $sessionId)
     {
         $wordCloudData = [];
         $stopWords = ['der', 'die', 'das', 'den', 'dem', 'des', 'ein', 'eine', 'einer', 'eines', 'für', 'und', 'oder', 'aber', 'doch', 'sondern', 'denn'];
         $allIdeas = $ideas->concat($topIdeas)->filter(function($idea) {
             return !empty($idea->tag);
         });
         
         foreach ($allIdeas as $idea) {
             $text = strtolower($idea->idea_title . ' ' . strip_tags($idea->idea_description));
             $text = preg_replace('/[^a-z0-9\s]/', '', $text);
             $words = explode(' ', $text);
             foreach ($words as $word) {
                 $word = trim($word);
                 if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                     $wordCloudData[$word] = ($wordCloudData[$word] ?? 0) + 1;
                 }
             }
         }
         
         $wordCloudData = array_filter($wordCloudData, function($count) {
             return $count >= 2;
         });
         
         $formattedWordCloudData = array_map(function($word, $count) {
             return ["word" => $word, "count" => (string)$count];
         }, array_keys($wordCloudData), $wordCloudData);
         
         $nextSteps = "<ul>
             <li>Ziele & Aufgaben definieren: Konkrete Ergebnisse festlegen, Verantwortlichkeiten zuweisen.</li>
             <li>Zeitplan & Ressourcen planen: Meilensteine setzen, benötigte Mittel zuordnen.</li>
             <li>Umsetzung & Kontrolle: Start der Implementierung, regelmäßige Fortschrittsprüfung.</li>
         </ul>";
         
         return [
             'wordCloudData' => $formattedWordCloudData,
             'nextSteps' => $nextSteps
         ];
     }
     public function sendPDF(Request $request)
     {
         $contributorEmails = $request->input('contributor_emails', []);
         if (empty($contributorEmails)) {
             return response()->json(['error' => 'Keine E-Mail-Adressen angegeben.'], 400);
         }
         $sessionId = $request->input('session_id');
         $sessionDetails = SessionDetailsCache::findOrFail($sessionId);
         $ideas = $sessionDetails->ideas;
         
         $groupedIdeasByRound = collect($ideas)->groupBy('round')->toArray();
     
         $html = view('pdf.session_details', [
             'sessionDetails' => $sessionDetails,
             'groupedIdeasByRound' => $groupedIdeasByRound
         ])->render();
     
         $pdf = PDF::loadHTML($html);
         $filename = ($sessionDetails->target ?? 'session_details') . '.pdf';
     
         $emailMessage = "Hallo,<br><br>
             Du hast erfolgreich an der Ideen-Session \"{$sessionDetails->target}\" teilgenommen.<br><br>
             Hier ist dein Abschluss PDF mit allen wichtigen Informationen über die Session.<br><br>
             Viele Grüße<br>
             BrainFrame";
     
         foreach ($contributorEmails as $email) {
             if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                 Mail::send([], [], function ($message) use ($email, $emailMessage, $pdf, $filename) {
                     $message->to($email)
                         ->subject("BrainFrame - Abschluss PDF zur Ideen-Session")
                         ->html($emailMessage)
                         ->attachData($pdf->output(), $filename);
                 });
             }
         }
     
         return response()->json(['info' => 'Emails erfolgreich versendet.'], 200);
     }
     public function invite(Request $request)
     {
         try {
             Log::info('Session Invite Request:', $request->all());
     
             $validated = $request->validate([
                 'session_id' => 'required',
                 'host_id' => 'required', // This is actually contributor_id
                 'contributor_email_addresses' => 'required|array',
                 'contributor_email_addresses.*' => 'email',
             ]);
     
             $sessionId = $validated['session_id'];
             $contributorId = $validated['host_id'];
             $contributorEmailAddresses = $validated['contributor_email_addresses'];
     
             // Find the contributor and get the associated user
             $host = User::whereIn('id', function($query) use ($contributorId) {
                 $query->select('user_id')
                       ->from('bf_contributors')
                       ->where('id', $contributorId);
             })->first();

             if (!$host) {
                 throw new \Exception("No user found for contributor ID: {$contributorId}");
             }
     
             $userName = explode('.', $host->email)[0];
             $emailMessage = "Hallo, <br> Du wurdest von {$userName} zu einer Ideen-Session eingeladen. <br> <br>
                 Du kannst über folgenden Link beitreten: <a href='https://stefan-theissen.de/brainframe/{$sessionId}'>Brainframe</a>. <br> <br>
                 Alternativ kannst du auch unter <a href='https://stefan-theissen.de/brainframe'>Brainframe</a> vorbeischauen und mit dem Code: {$sessionId} beitreten.";
     
             $sentCount = 0;
             foreach ($contributorEmailAddresses as $email) {
                 if ($email) {
                     try {
                         Mail::html($emailMessage, function ($message) use ($email, $userName) {
                             $message->to($email)
                                 ->subject("{$userName} - Einladung zur Ideen-Session");
                         });
                         $sentCount++;
                     } catch (\Exception $e) {
                         Log::error("Failed to send email to {$email}: " . $e->getMessage());
                     }
                 }
             }
     
             Log::info("Invitations sent: {$sentCount} out of " . count($contributorEmailAddresses));
     
             return response()->json([
                 'message' => 'Invitations sent successfully.',
                 'sent_count' => $sentCount,
                 'total_count' => count($contributorEmailAddresses)
             ], 200);
     
         } catch (\Illuminate\Validation\ValidationException $e) {
             Log::warning('Validation failed:', $e->errors());
             return response()->json(['error' => $e->errors()], 422);
         } catch (\Exception $e) {
             Log::error('Error in invite function: ' . $e->getMessage());
             return response()->json(['error' => 'An error occurred while processing your request.'], 500);
         }
     }
     public function getClosingDetails(Request $request, $sessionId)
     {
         $cachedDetails = SessionDetailsCache::where('session_id', $sessionId)->first();
         if (!$cachedDetails) {
             $session = Session::findOrFail($sessionId);
             // Schritt 1: Berechne die durchschnittliche Bewertung für jede Idee und beschränke auf die Top 3
             $topVotes = Vote::where('session_id', $session->id)
                 ->where('voting_phase', Vote::where('session_id', $session->id)
                     ->max('voting_phase'))
                 ->select('idea_id', DB::raw('AVG(vote_value) as avg_vote_value'))
                 ->groupBy('idea_id')
                 ->orderBy('avg_vote_value', 'desc')
                 ->take(3) // Begrenze auf die Top 3
                 ->get();

             // Hole die IDs der Top 3 Ideen
             $ideaIds = $topVotes->pluck('idea_id');

             // Schritt 2: Hole die Ideen mit den spezifischen Feldern und das Icon des Contributors
             $topIdeas = Idea::whereIn('id', $ideaIds)
                 ->select('id', 'idea_title', 'idea_description', 'contributor_id', 'tag') // ID hinzugefügt
                 ->get()
                 ->map(function ($idea) use ($topVotes) {
                     // Füge die durchschnittliche Bewertung zur Idee hinzu
                     $topVote = $topVotes->where('idea_id', $idea->id)->first();

                     if ($topVote) {
                         $idea->avg_vote_value = $topVote->avg_vote_value;
                     } else {
                         $idea->avg_vote_value = null; // Setze auf null, falls keine Bewertung vorhanden
                     }

                     // Hole das Icon des Contributors
                     $contributor = Contributor::find($idea->contributor_id);
                     if ($contributor && $contributor->role) {
                         $idea->contributor_icon = $contributor->role->icon;
                     } else {
                         $idea->contributor_icon = null; // Setze auf null, falls kein Contributor gefunden oder kein Role vorhanden
                     }

                     return $idea;
                 });

             Log::info('Top votes and top ideas', [
                 'topIdeas' => $topIdeas
             ]);

             $ideas = Idea::where('session_id', $session->id)
                 ->whereNotNull('tag')
                 ->get();

             $tagList = Idea::where('session_id', $session->id)
                 ->whereNotNull('tag')
                 ->groupBy('tag')
                 ->selectRaw('tag, COUNT(*) as count')
                 ->get();

             $WordCloudandNextSteps = $this->generateWordCloudandNextSteps($topIdeas, $ideas, $sessionId);
             $wordCloudData = $WordCloudandNextSteps['wordCloudData'] ?? [];
             $nextSteps = $WordCloudandNextSteps['nextSteps'] ?? '';
             $session = Session::find($sessionId)->fresh();
             $firstIdeaTime = Carbon::parse(Idea::where('session_id', $session->id)->min('created_at'));
             $lastVoteTime = Carbon::parse(Vote::whereIn('idea_id', $ideas->pluck('id'))->max('updated_at'));
             $duration = abs($lastVoteTime->diffInMinutes($firstIdeaTime));

             $method = Method::where('id', $session->method_id)->first();
             $contributorsCount = Contributor::where('session_id', $sessionId)->count();
             $ideasCount = Idea::where('session_id', $sessionId)
                 ->whereNull('tag')
                 ->count();
             Log::info('contributorsCount: ' . $contributorsCount);
             Log::info('ideasCount: ' . $ideasCount);
             Log::info('duration: ' . $duration);
             Log::info('date: ' . $session->created_at->toDateString());
             Log::info('method: ' . ($method ? $method->name : 'N/A'));
             Log::info('inputToken: ' . ($session->input_token ?? 'N/A'));
             Log::info('outputToken: ' . ($session->output_token ?? 'N/A'));
             Log::info('tagList: ' . json_encode($tagList, JSON_PRETTY_PRINT));

             $contributor_emails = User::select('users.email')
                 ->join('bf_contributors', 'users.id', '=', 'bf_contributors.user_id')
                 ->where('bf_contributors.session_id', $sessionId)
                 ->whereNotNull('users.email')
                 ->where('users.email', '!=', '')
                 ->get()
                 ->pluck('email');
                 $inputIdeas = Idea::where('session_id', $session->id)
                 ->whereNull('tag')
                 ->get();
             $response = [
                 'session_id' => $session->id,
                 'target' => $session->target,
                 'top_ideas' => $topIdeas,
                 'ideas' => $inputIdeas->map(function ($idea) {
                     return [
                         'id' => $idea->id,
                         'round' => $idea->round,
                         'contributor_icon' => $idea->contributor->role->icon,
                     ];
                 }),
                 'contributors_count' => $contributorsCount,
                 'ideas_count' => $ideasCount,
                 'duration' => $duration,
                 'date' => $session->created_at->toDateString(),
                 'method' => $method ? $method->name : 'N/A',
                 'input_token' => $session->input_token ?? 0,
                 'output_token' => $session->output_token ?? 0,
                 'word_cloud_data' => $wordCloudData,
                 'tag_list' => $tagList,
                 'contributor_emails' => $contributor_emails,
                 'next_steps' => $nextSteps,
             ];
            

             $sessionDetailsCache = SessionDetailsCache::create([
                 'session_id' => $session->id,
                 'target' => $session->target,
                 'top_ideas' => $topIdeas,
                 'ideas' => $inputIdeas->map(function ($idea) {
                     return [
                         'id' => $idea->id,
                         'round' => $idea->round,
                         'contributor_icon' => $idea->contributor->role->icon,
                     ];
                 }),
                 'contributors_count' => $contributorsCount,
                 'ideas_count' => $ideasCount,
                 'duration' => $duration,
                 'date' => $session->created_at->toDateString(),
                 'method' => $method ? $method->name : 'N/A',
                 'input_token' => $session->input_token,
                 'output_token' => $session->output_token,
                 'word_cloud_data' => $wordCloudData,
                 'tag_list' => $tagList,
                 'contributor_emails' => $contributor_emails,
                 'next_steps' => $nextSteps,
             ]);


             return response()->json($response);
         } else {
             return response()->json($cachedDetails);
         }
     }
     public function downloadSessionPDF($sessionId)
     {
         $sessionDetails = SessionDetailsCache::findOrFail($sessionId);
         $ideas = $sessionDetails->ideas;
         $groupedIdeasByRound = array_reduce($ideas, function ($result, $idea) {
             $round = $idea['round'];
             if (!isset($result[$round])) {
                 $result[$round] = [];
             }
             $result[$round][] = $idea;
             return $result;
         }, []);
         // Fügen Sie einen Parameter hinzu, um zwischen HTML und PDF zu unterscheiden
         $format = request('format', 'html');
         if ($format === 'pdf') {
             $html = view('pdf.session_details', [
                 'sessionDetails' => $sessionDetails,
                 'groupedIdeasByRound' => $groupedIdeasByRound
             ])->render();
             $pdf = Pdf::loadHTML($html);
             $filename = $sessionDetails->target ?? 'session_details';
             $filename .= '.pdf';
             return $pdf->download($filename);
         }
         // Standardmäßig HTML zurückgeben
         return view('pdf.session_details', [
             'sessionDetails' => $sessionDetails,
             'groupedIdeasByRound' => $groupedIdeasByRound
         ]);
     }
         */
}

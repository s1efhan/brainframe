<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>{{ $session->target }} - Zusammenfassung</title>
</head>

<body>
    <header>
        <div>
        </div>
    </header>
    <div class="placeholder"></div>
    Session: {{ $session['session_id'] }} - {{ $session['target'] }}
    <div> <strong class="headline__join__brain">Brain</strong><strong>Frame</strong></div>
    <div class="collecting-pdf">
        <table class="session-data" id="first-table">
            <thead>
                <tr>
                    <th>Ziel</th>
                    <th>Teilnehmer</th>
                    <th>Ideen</th>
                    <th>Datum</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $session->target }}</td>
                    <td class="center">{{ count($contributors) }}</td>
                    <td class="center">{{ count($ideas->where('tag', null)) }}</td>
                    <td class="center">{{ \Carbon\Carbon::parse($session->created_at)->format('d.m.Y') }}</td>
                </tr>
            </tbody>
        </table>

        <table class="session-data">
            <thead>
                <tr>
                    <th>Methode</th>
                    <th>Session-ID</th>
                    <th>Token</th>
                    <th>Dauer</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="center">{{ $session->method['name'] }}</td>
                    <td class="center">{{ $session->id }}</td>
                    <td class="token">
                        {{$prompt_tokens }} (prompt)<br>
                        {{ $completion_tokens }} (completion)<br>
                        =>
                        {{ number_format((($prompt_tokens * 0.00000015 + $completion_tokens * 0.00000060) * 100), 2) }}
                        ct
                    </td>
                    <td class="center">
    @php
        $duration = \Carbon\Carbon::parse($ideas->first()->created_at)->diffInMinutes($votes->last()->created_at);
        $hours = floor($duration / 60);
        $minutes = $duration % 60;
    @endphp
    @if ($hours > 0)
        {{ $hours }}h
    @endif
    {{ $minutes }}min
</td>

                </tr>
            </tbody>
        </table>

        <div class="top-ideas">
            <h2>Top Ideen</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Idee</th>
                        <th>Beschreibung</th>
                     <!--   <th>Beitragender</th> -->
                        <th>Bewertung</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $ideasWithTags = $ideas->filter(function ($idea) {
                            return $idea->tag !== null && $idea->tag !== '';
                        })->map(function ($idea) use ($votes) {
                            $ideaVotes = $votes->where('idea_id', $idea->id);
                            $maxRound = $ideaVotes->max('round');
                            $relevantVotes = $ideaVotes->where('round', $maxRound);
                            $avgRating = $relevantVotes->avg('value');
                            $voteType = $relevantVotes->first()->vote_type ?? 'default';
                            $maxVoteValue = [
                                'ranking' => 5,
                                'star' => 3,
                                'swipe' => 1,
                                'leftRightVote' => 1
                            ][$voteType] ?? 1;

                            return [
                                'id' => $idea->id,
                                'title' => $idea->title,
                                'description' => $idea->description,
                                'contributor_id' => $idea->contributor_id,
                                'avgRating' => $avgRating,
                                'maxRound' => $maxRound,
                                'maxVoteValue' => $maxVoteValue
                            ];
                        })->sortByDesc('maxRound')->sortByDesc('avgRating')->values();
                    @endphp

                    @foreach($ideasWithTags->take(3) as $index => $idea)
                                        <tr>
                                            <td class="center">{{ $index + 1 }}</td>
                                            <td>{{ $idea['title'] }}</td>
                                            <td>{!! $idea['description'] !!}</td>
                                           <!-- <td class="center">
                                                @php
                                                    $contributor = $contributors->firstWhere('id', $idea['contributor_id']);
                                                    $name = $contributor ? $contributor->name : '';
                                                @endphp
                                              {{$name}} 
                                            </td> -->
                                            <td class="center">
                                                {{ number_format($idea['avgRating'], 1) }}/{{ number_format($idea['maxVoteValue'], 1) }}
                                            </td>
                                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

       <!-- <div class="collecting-process">
            <h2>{{ $session->method['name'] }}</h2>
            <div class="timeline">
                @php
                    $groupedIdeasByRound = $ideas->groupBy('round');
                @endphp
                @foreach($groupedIdeasByRound as $round => $groupedIdeas)
                            <div class="tag">
                                <div class="round">{{ $round }}</div>
                                <ul>
                                    @foreach($groupedIdeas as $idea)
                                                        <li>
                                                            @php
                                                                $contributor = $contributors->firstWhere('id', $idea->contributor_id);
                                                                $iconName = $contributor ? $contributor->icon : '';
                                                            @endphp
                                                
                                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                @endforeach
            </div>
        </div>
                    -->
        @if(isset($wordCloud))
            <div class="word-cluster">
                <h2>Wort-Cluster</h2>
                <ul>
                    @foreach($wordCloud as $item)
                        <li class="count-{{ $item['count'] }}">{{ $item['word'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(isset($tagList))
            <div class="tags-list">
                <h2>#Tags</h2>
                <ul>
                    @foreach($tagList as $tag)
                        <li class="count-{{ $tag['count'] }}">#{{ $tag['tag'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(isset($nextSteps))
            <div class="next-steps">
                <h2>Nächste Schritte und Empfehlungen</h2>
                <p>{!! $nextSteps !!}</p>
            </div>
        @endif
    </div>
</body>

</html>
<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 1cm;
    }
.session-data, .top-ideas, .word-cluster, .tags-list, .next-steps {
    page-break-inside: avoid;
        break-inside: avoid;
}
    h2 {
        padding-bottom: 0.5em;
        margin-top: 1.5em;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1.5em;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 0.8em;
        text-align: left;
    }

    th {
        font-weight: bold;
    }

    .center {
        text-align: center;
    }

    .timeline,
    .word-cluster,
    .tags-list,
    .next-steps {
        border: 1px solid;
        border-radius: 5px;
        padding: 1em;
        margin-top: 1.5em;
    }

    .timeline div {
        display: inline-block;
        width: 18%;
        margin: 0.5em;
        vertical-align: top;
    }

    .word-cluster ul,
    .tags-list ul {
        padding: 0;
        text-align: center;
    }

    .word-cluster li,
    .tags-list li {
        display: inline-block;
        margin: 0.3em;
        padding: 0.3em 0.6em;
    }

    .word-cluster li {
        font-weight: bold;
    }

    .tags-list li {
        border-radius: 3px;
    }

    .headline__join__brain {
        color: #33d2ca;
    }
    .tags-list li {
        vertical-align: middle;
        margin: 0.5em;
        display: inline-block;
        padding: 0.5em 1em;
        border-radius: 5px;
        border: solid;
    }
    .word-cluster ul {
        padding: 0;
        margin: 0;
        list-style-type: none;
        text-align: center;
        font-size: 0;
    }

    .word-cluster ul li {
        display: inline-block;
        margin: 0.5em;
        vertical-align: middle;
        font-size: 16px;
        padding:0.5em;
    }

    .word-cluster li.count-1 {
        font-size: 12px;
    }

    .word-cluster li.count-2 {
        font-size: 14px;
    }

    .word-cluster li.count-3 {
        font-size: 16px;
    }

    .word-cluster li.count-4 {
        font-size: 18px;
    }

    .word-cluster li.count-5 {
        font-size: 20px;
    }

    .word-cluster li.count-6 {
        font-size: 22px;
    }

    .word-cluster li.count-7 {
        font-size: 24px;
    }

    .word-cluster li.count-8 {
        font-size: 26px;
    }

    .word-cluster li.count-9 {
        font-size: 28px;
    }

    .word-cluster li.count-10 {
        font-size: 30px;
    }

</style>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="initial-scale=1.0, width=device-width">
    <meta charset="utf-8">
    <link rel="icon" href="/favicon.ico">
</head>

<body>
    <header>
        <div>
            Session: {{ $sessionDetails['session_id'] }} - {{ $sessionDetails['target'] }}
        </div>
        <div><strong class="headline__join__brain">Brain</strong><strong>Frame</strong></div>
    </header>
    <div class="placeholder"></div>
    <div class="collecting-pdf">
        <div class="session-data">
            <h2>Session Infos</h2>
        </div>
        <table class="session-data">

            <thead>
                <tr>
                    <th>Methode</th>
                    <th>Teilnehmer</th>
                    <th>Ideen</th>
                    <th>Token</th>
                    <th>Dauer</th>
                    <th>Datum</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="center">{{ $sessionDetails['method'] }}</td>
                    <td class="center">{{ $sessionDetails['contributors_count'] }}</td>
                    <td class="center">{{ $sessionDetails['ideas_count'] }}</td>
                    <td class="center">
                        {{ $sessionDetails['input_token'] }} (input) <br>{{ $sessionDetails['output_token'] }} (output)
                        <br> =>
                        {{ number_format((($sessionDetails['input_token'] * 0.000015 + $sessionDetails['output_token'] * 0.000060) * 100), 2) }}
                        ct
                    </td>
                    <td class="center">
                        {{ floor($sessionDetails['duration'] / 60) }}h
                        {{ round($sessionDetails['duration'] % 60) }}min
                    </td>
                    <td class="center">{{ date('d.m.Y', strtotime($sessionDetails['date'])) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="top-ideas">
    <h2>Top Ideen</h2>
    @if(isset($sessionDetails['top_ideas']))
    <table>
        <thead>
            <tr>
                <th>Platz</th>
                <th>Idee</th>
                <th>Beschreibung</th>
                <th>Punkte</th>
            </tr>
        </thead>
        <tbody>
        @php
            $sortedIdeas = collect($sessionDetails['top_ideas'])->sortByDesc('avg_vote_value');
        @endphp
        @foreach($sortedIdeas as $index => $idea)
            <tr>
                <td class="center">
                    <div class="placement">{{ $index + 1 }}</div>
                </td>
                <td>{{ $idea['idea_title'] }}</td>
                <td>{!! $idea['idea_description'] !!}</td>
                <td class="center">
                    <div class="voting-value">{{ number_format($idea['avg_vote_value'], 1) }} /5.0 </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>

        <div class="collecting-process">
            <h2>{{ $sessionDetails['method'] }}</h2>
            <div class="timeline">
                @foreach($groupedIdeasByRound as $round => $groupedIdeas)
                    <div>
                        <div class="round">{{ $round }}</div>
                        <ul>
                            @foreach($groupedIdeas as $idea)
                                <li>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        @if(isset($sessionDetails['word_cloud_data']))
            <div class="word-cluster">
                <h2>Wort-Cluster</h2>
                <ul>
                    @foreach($sessionDetails['word_cloud_data'] as $item)
                        <li class="count-{{ $item['count'] }}">
                            {{ $item['word'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="tags-list">
            <h2>#Tags</h2>
            <ul>
                @foreach($sessionDetails['tag_list'] as $tag)
                    <li class="count-{{ $tag['count'] }}">
                        #{{ $tag['tag'] }}
                    </li>
                @endforeach
            </ul>
        </div>

        @if(isset($sessionDetails['next_steps']))
            <div class="next-steps">
                <h2>Nächste Schritte und Empfehlungen</h2>
                <p>{!! $sessionDetails['next_steps'] !!}</p>
            </div>
        @endif
    </div>
</body>

</html>
<style>
    body {
        margin: 1cm;
    }

    .placeholder {
        height: 2em;
        width: 100%;
    }
strong {font-size: 1.5em; font-weight: bold}
    svg {
        height: 1em;
        width: 1em;
    }

    header {
        position: relative;
        width: 100%;
    }
    .tags-list, .word-cluster, .top-ideas, .collecting-process, .next-steps {
    page-break-inside: avoid;
    break-inside: avoid; 
}
    header>div:first-child {
        position: absolute;
        left: 0;
    }

    header>div:last-child {
        position: absolute;
        right: 0;
    }

    .word-cluster,
    .session-data,
    .top-ideas,
    .collecting-process {
        text-align: center;
    }

    .word-cluster li {
        text-align: left;
    }

    h2 {
        text-align: center;
        border: solid;
        border-radius: 5px;
        padding: 0.25em 1em;
        display: inline-block;
        width: 25vw;
        margin-right: auto;
    }

    .timeline div,
    .word-cluster,
    .tags-list,
    .next-steps {
        border: solid;
        border-radius: 5px;
        padding: 1em;
        margin: 2em 0;
    }

    .headline__join__brain {
        color: #33d2ca;
    }

    li,
    td {
        text-align: left;
    }

    .collecting-pdf table {
        border-collapse: collapse;
        font-size: 0.9em;
        width: 100%;
    }

    .collecting-pdf th,
    .collecting-pdf td {
        padding: 0.5em 0.8em;
        border-bottom: solid;
        word-wrap: break-word;
    }

    .collecting-pdf th {
        border-top: solid;
    }

    .collecting-pdf .center {
        text-align: center;
    }

    .collecting-pdf .top-ideas table {
        table-layout: fixed;
    }

    .collecting-pdf .top-ideas th:nth-child(1),
    .collecting-pdf .top-ideas td:nth-child(1) {
        width: 10%;
    }

    .collecting-pdf .top-ideas th:nth-child(2),
    .collecting-pdf .top-ideas td:nth-child(2) {
        width: 20%;
    }

    .collecting-pdf .top-ideas th:nth-child(3),
    .collecting-pdf .top-ideas td:nth-child(3) {
        width: 50%;
    }

    .collecting-pdf .top-ideas th:nth-child(4),
    .collecting-pdf .top-ideas td:nth-child(4) {
        width: 20%;
    }

    .placement,
    .voting-value {
        border: solid;
        padding: 0.5em 1em;
        border-radius: 5px;
    }

    .timeline {
        border: solid;
        border-radius: 5px;
    }

    .timeline div {
        display: inline-block;
        width: auto;
        min-width: 20%;
        margin: 1em;
        vertical-align: top;
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

    .tags-list li {
        vertical-align: middle;
        margin: 0.5em;
        display: inline-block;
        padding: 0.5em 1em;
        border-radius: 5px;
        border: solid;
    }

    .tags-list li.count-1 {
        padding: 0.1em;
        font-size: 0.6em;
    }

    .tags-list li.count-2 {
        padding: 0.3em;
        font-size: 0.8em;
    }

    .tags-list li.count-3 {
        padding: 0.6em;
        font-size: 1em;
    }

    .tags-list li.count-4 {
        padding: 0.8em;
        font-size: 1.2em;
    }

    .tags-list li.count-5 {
        padding: 1em;
        font-size: 1.4em;
    }

    .tags-list li.count-6 {
        padding: 1.2em;
        font-size: 1.6em;
    }

    .tags-list li.count-7 {
        padding: 1.4em;
        font-size: 1.8em;
    }

    .tags-list li.count-8 {
        padding: 1.6em;
        font-size: 2em;
    }

    .tags-list li.count-9 {
        padding: 1.8em;
        font-size: 2.2em;
    }

    .tags-list li.count-10 {
        padding: 2em;
        font-size: 2.4em;
    }

    .next-steps h2,
    .tags-list h2,
    .word-cluster h2 {
        border: none;
        border-radius: 0;
        border-bottom: solid;
        display: block;
        width: 100%;
        margin: 0;
        padding: 0.5em 0;
    }

    .next-steps,
    .tags-list,
    .word-cluster {
        box-sizing: border-box;
        margin: 2em 0;
        padding: 0;
    }

    .collecting-process .timeline>div {
        max-width: 20%;
    }

    .collecting-process .timeline>div .round+ul {
        padding: 0;
        margin: 0;
        width: 100%;
        font-size: 0;
        list-style-type: none;
        text-align: center;
    }

    .collecting-process .timeline>div .round+ul li {
        display: inline-block;
        width: 1em;
        height: 1em;
        margin: 0.2em;
        padding: 0;
        border-radius: 50%;
        background-color: black;
        font-size: 16px;
        vertical-align: top;
    }
</style>
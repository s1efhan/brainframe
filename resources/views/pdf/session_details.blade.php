<!DOCTYPE html>
<html lang="de">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="initial-scale=1.0, width=device-width">
    <meta charset="utf-8">
    <link rel="icon" href="/favicon.ico">
</head>

<body>
    <h2>{{$sessionDetails->target}}</h2>
    <table  class="session-data">
        <thead>
            <th> <a href="https://stefan-theissen.de/"> Session PIN </a> </th>
            <th>Ziel</th>
            <th>Methode</th>
            <th>Teilnehmerzahl</th>
            <th>Anzahl Ideen</th>
            <th>Dauer</th>
            <th>Verbrauchte GPT4-o-mini Token</th>
            <th>Datum</th>
        </thead>
        <tbody>
            <tr>
                <td>{{$sessionDetails->session_id}}</td>
                <td>{{$sessionDetails->target}}</td>
                <td>{{$sessionDetails->method}}</td>
                <td>{{$sessionDetails->contributors_count}}</td>
                <td>{{$sessionDetails->ideas_count}}</td>
                <td>{{$sessionDetails->duration}}</td>
                <td>{{$sessionDetails->input_token}} (Input) <br> {{$sessionDetails->output_token}}
                    (output )<br> =>
                    {{ number_format($sessionDetails->output_token * 0.000060 + $sessionDetails->input_token * 0.000015, 2) }}
                    USD</td>
                <td>{{$sessionDetails->date}}</td>
            </tr>
        </tbody>
    </table>
    <table class="top-ideas">
        <thead>
            <th>Platzierung</th>
            <th>Idee</th>
            <th>Beschreibung</th>
            <th>Verfasser</th>
            <th>Tag</th>
            <th>Durchschnittsplatzierung</th>
        </thead>
        <tbody @foreach ($sessionDetails->top_ideas as $topIdea)>
            <tr>
                <td>1</td>
                <td> {{ $topIdea['idea_title'] }}</td>
                <td>{!! $topIdea['idea_description'] !!}</td>
                <td> {{ $topIdea['contributor_icon'] }}</td>
                <td>{{ $topIdea['tag'] }}</td>
                <td> {{ number_format($topIdea['avg_vote_value'], 1) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="tags-list">
        @foreach ($sessionDetails->tag_list as $tagList)
            {{$tagList['tag']}}{{$tagList['count']}}
        @endforeach
</div>
    <div>
        @foreach ($groupedIdeas as $round => $ideas)
            <div>{{ $round }}</div>
            <ul>
                @foreach ($ideas as $idea)
                <li>{{ $idea['id'] }}</li>
                    <li>{{ $idea['contributor_icon'] }}</li>
                @endforeach
            </ul>
        @endforeach

    </div>
    <div>
        <ul @foreach ($sessionDetails->word_cloud_data['content'] as $wordCloud)>
            <li> {{$wordCloud['word']}}: {{$wordCloud['count']}} </li>
        @endforeach
        </ul>
    </div>
    <p>
    {{$sessionDetails->next_steps['content']}}
    </p>
</body>

</html>
<style>
:root,
body,html, 
* {
    padding: 0;
    margin: 0;
    font-family: 'Nohemi-Regular', sans-serif;
}

body {
    width: 100vw;
    color: var(--text);
    background-color: var(--background);
    box-sizing: border-box;
    overflow-x: hidden
}

table {
    box-sizing: border-box;
    width: 100%;
    table-layout: fixed;
    overflow: hidden;

    td {
        overflow: hidden;
    }
}
</style>
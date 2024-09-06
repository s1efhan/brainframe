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
        <h1 class="headline__join">
            <div class="headline__join__icon">
                <svg width="100%" height="100%" viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/"
                    style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
                    <g transform="matrix(1,0,0,1,-14.4443,-18.9271)">
                        <rect x="308.03" y="308.955" width="175.573" height="175.573" />
                    </g>
                    <g transform="matrix(1,0,0,1,-211.467,29.3211)">
                        <circle cx="354.726" cy="353.379" r="99.07" />
                    </g>
                    <g transform="matrix(1.38209,0,0,1.2008,-28.3493,-23.4736)">
                        <path d="M125.267,61.946L199.68,210.772L50.854,210.772L125.267,61.946Z" />
                    </g>
                    <g transform="matrix(1.13862,0,0,1.13862,-109.533,40.5074)">
                        <path
                            d="M429.686,-25.008L453.309,47.697L529.756,47.697L467.909,92.631L491.532,165.336L429.686,120.402L367.839,165.336L391.463,92.631L329.616,47.697L406.063,47.697L429.686,-25.008Z"
                            style="fill:rgb(51,210,203);" />
                    </g>
                </svg>
            </div>
            <div class="headline__join__text">
                <p class="headline__join__brain">Brain</p>
                <p class="headline__join__frame">Frame</p>
            </div>
        </h1>
    </header>
    <div class="collecting-pdf">
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
                        {{ $sessionDetails['input_token'] }} (input) <br>{{ $sessionDetails['output_token'] }} (output) <br> =>
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
                        @foreach(array_reverse($sessionDetails['top_ideas']) as $index => $idea)
                            <tr>
                                <td class="center">{{ $index + 1 }}</td>
                                <td>{{ $idea['idea_title'] }}</td>
                                <td>{!! $idea['idea_description'] !!}</td>
                                <td class="center">{{ number_format($idea['avg_vote_value'], 1) }} /5.0</td>
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
                    <div class="tag">
                        <div class="round">{{ $round }}</div>
                        <ul>
                            @foreach($groupedIdeas as $idea)
                                <li>

                                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 114.1 122.88">
                                        <defs></defs>
                                        <title>bulb</title>
                                        <path class="cls-1"
                                            d="M75.84,27.1a35.68,35.68,0,0,1,8.61,7.09,32.45,32.45,0,0,1,5.76,9.26h0a36.84,36.84,0,0,1,1.85,6,34.64,34.64,0,0,1,.24,14,38.69,38.69,0,0,1-2.15,7.32l-.12.25c-2.06,5-5.59,9.86-9,14.66-1.75,2.42-3.48,4.82-4.94,7.15A4.69,4.69,0,0,1,71.73,95l-27.56,4.1A4.7,4.7,0,0,1,39,95.69a40.19,40.19,0,0,0-2.54-5.82,24.85,24.85,0,0,0-3-4.49c-1.43-1.63-2.88-3.29-4.29-5.2A40.42,40.42,0,0,1,25,73.24h0a41.08,41.08,0,0,1-2.81-8,35.84,35.84,0,0,1-.95-8.45v0A35.39,35.39,0,0,1,22.35,48a41.69,41.69,0,0,1,3.42-8.85l.2-.35a35.55,35.55,0,0,1,7.13-8.63,33.72,33.72,0,0,1,9.46-5.83l.28-.1a35.41,35.41,0,0,1,8-2.14,37.78,37.78,0,0,1,8.77-.2,39.14,39.14,0,0,1,8.4,1.71,38.44,38.44,0,0,1,7.79,3.49Zm-4,87.26a17.37,17.37,0,0,1-6.28,6.29,16.46,16.46,0,0,1-7.2,2.2A14.87,14.87,0,0,1,51,121.4a15.1,15.1,0,0,1-4.39-3.27l25.29-3.77Zm2.41-14.15,0,1.65,0,.58a22,22,0,0,1,0,3.25l-.49,2.39-30.64,4.56-.54-1.23-1.19-4.9,0-1.42,32.79-4.88ZM56.34,3.77A3.84,3.84,0,0,1,60.23,0h0l.27,0A3.84,3.84,0,0,1,64,3.89h0a1.27,1.27,0,0,1,0,.2l-.21,8.21h0a2.11,2.11,0,0,1,0,.26,3.84,3.84,0,0,1-3.87,3.54h0l-.27,0a3.84,3.84,0,0,1-3.53-3.88h0a1.09,1.09,0,0,1,0-.19l.2-8.25ZM14,18.15a3.84,3.84,0,0,1,2.47-6.66,3.83,3.83,0,0,1,2.76,1l6.16,5.73a3.91,3.91,0,0,1,1.22,2.68,3.82,3.82,0,0,1-1,2.76,3.86,3.86,0,0,1-2.67,1.22,3.8,3.8,0,0,1-2.76-1L14,18.15ZM3.92,60.48A3.86,3.86,0,0,1,0,56.75a3.84,3.84,0,0,1,3.73-4l8.41-.28a3.84,3.84,0,0,1,4,3.72v.06h0v.14a3.84,3.84,0,0,1-3.73,3.77h-.15l-8.3.27Zm106-11.92H110a3.84,3.84,0,0,1,2.66.86,3.81,3.81,0,0,1,1.4,2.59v0a.49.49,0,0,1,0,.13,3.84,3.84,0,0,1-3.44,4.06l-8.37.89a3.83,3.83,0,0,1-2.81-.85,3.84,3.84,0,0,1,2-6.8c2.79-.31,5.6-.63,8.4-.9ZM93.33,15.09A3.83,3.83,0,0,1,98.65,14h0a3.73,3.73,0,0,1,1.63,2.44,3.84,3.84,0,0,1-.58,2.88l-4.68,7A3.8,3.8,0,0,1,92.58,28a3.88,3.88,0,0,1-2.88-.57A3.92,3.92,0,0,1,88.06,25a3.84,3.84,0,0,1,.58-2.88l4.69-7ZM38.23,80.87A42.19,42.19,0,0,1,31,70.56,31.2,31.2,0,0,1,27.89,57,31.7,31.7,0,0,1,31.7,42.56a.47.47,0,0,0,.05-.1h0a27.5,27.5,0,0,1,13.4-11.71,29.65,29.65,0,0,1,13.93-2A32.09,32.09,0,0,1,72.39,33,27.43,27.43,0,0,1,84,46.2,28.85,28.85,0,0,1,84,68.45C81.31,75,75.15,82,71.11,88.4a1.67,1.67,0,0,0-.67,0L44.36,92.25a35,35,0,0,0-6.13-11.38Z" />
                                    </svg>

                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        @if(isset($sessionDetails['word_cloud_data']['content']))
            <div class="word-cluster">
                <h2>Wort-Cluster</h2>
                <ul>
                    @foreach($sessionDetails['word_cloud_data']['content'] as $item)
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

        @if(isset($sessionDetails['next_steps']['content']))
            <div class="next-steps">
                <h2>Nächste Schritte und Empfehlungen</h2>
                <p>{!! $sessionDetails['next_steps']['content'] !!}</p>
            </div>
        @endif
    </div>
</body>

</html>

<style>
    body {
    margin: 1cm;
}

.session-data {
    margin-top: 2vh;
    width: 100%;
}

.collecting-process {
    display: flex;
    flex-direction: column;
    align-items: center;
}

h2 {
    text-align: center;
    border: solid;
    border-radius: 5px;
    padding: 0.25em 1em;
    width: 25vw;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header div, .timeline div, .word-cluster, .tags-list, .next-steps {
    border: solid;
    border-radius: 5px;
    padding: 1em;
}

.headline__join {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-weight: bold;
    font-family: 'Nohemi-Bold', sans-serif;
    font-size: 1em;
}

.headline__join div {
    border: none;
    padding: 0;
}

.headline__join__icon {
    width: 10vw;
}

.headline__join__text {
    margin-top: 0.5em;
    display: flex;
}

.headline__join__brain {
    color: #33d2ca;
}

.headline__join p {
    margin: 0;
}

.timeline {
    width: 100%;
    display: flex;
    justify-content: space-evenly;
    flex-wrap: wrap;
    align-items: stretch;
}

.timeline div {
    margin: 0.4em;
    display: flex;
    flex-direction: column;
    min-width: 25%;
    align-items: center;
    justify-content: start;
    padding: 0.25em;
}

.timeline .round {
    border: solid 2px;
    border-radius: 50%;
    text-align: center;
    width: 2em;
    height: 2em;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1em;
}

.timeline ul {
    display: flex;
    flex-wrap: wrap;
    list-style-type: none;
    margin: 0;
    align-items: center;
    justify-content: center;
    padding: 0.25em;
    max-width: 6em;
}

.timeline li {
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline li svg {
    font-size: 0.4em;
    padding: 1em;
    width: 3em;
    height: 3em;
}

.collecting-pdf table {
    border-collapse: collapse;
    font-size: 0.9em;
    width: 100%;
}

.collecting-pdf th, .collecting-pdf td {
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
.collecting-pdf .top-ideas td:nth-child(1) { width: 10%; }
.collecting-pdf .top-ideas th:nth-child(2), 
.collecting-pdf .top-ideas td:nth-child(2) { width: 25%; }
.collecting-pdf .top-ideas th:nth-child(3), 
.collecting-pdf .top-ideas td:nth-child(3) { width: 40%; }
.collecting-pdf .top-ideas th:nth-child(4), 
.collecting-pdf .top-ideas td:nth-child(4) { width: 10%; }
.collecting-pdf .top-ideas th:nth-child(5), 
.collecting-pdf .top-ideas td:nth-child(5) { width: 15%; }

.top-ideas {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.top-ideas svg {
    fill: var(--secondary);
}

.word-cluster, .tags-list {
    margin-top: 2vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.word-cluster ul, .tags-list ul {
    width: 100%;
    list-style-type: none;
    padding: 0;
    margin: 0 0 2vh;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}

.word-cluster li, .tags-list li {
    margin: 0.5rem;
    display: inline-block;
}
.word-cluster li {
        margin: 0.5rem;
        display: inline-block;
    }

    .word-cluster li.count-1 {
        font-size: 0.6em;
    }

    .word-cluster li.count-2 {
        font-size: 0.8em;
    }

    .word-cluster li.count-3 {
        font-size: 1em;
    }

    .word-cluster li.count-4 {
        font-size: 1.2em;
    }

    .word-cluster li.count-5 {
        font-size: 1.4em;
    }

    .word-cluster li.count-6 {
        font-size: 1.6em;
    }

    .word-cluster li.count-7 {
        font-size: 1.8em;
    }

    .word-cluster li.count-8 {
        font-size: 2em;
    }

    .word-cluster li.count-9 {
        font-size: 2.2em;
    }

    .word-cluster li.count-10 {
        font-size: 2.4em;
    }

    .tags-list li {
        margin: 0.5rem;
        display: inline-block;
        padding: 0.5em 1em;
        border-radius: 5px;
        border: solid;
    }

    .tags-list li.count-1 {
        padding: 0.1em;
    }

    .tags-list li.count-2 {
        padding: 0.3em;
    }

    .tags-list li.count-3 {
        padding: 0.6em;
    }

    .tags-list li.count-4 {
        padding: 0.8em;
    }

    .tags-list li.count-5 {
        padding: 1em;
    }

    .tags-list li.count-6 {
        padding: 1.2em;
    }

    .tags-list li.count-7 {
        padding: 1.4em;
    }

    .tags-list li.count-8 {
        padding: 1.6em;
    }

    .tags-list li.count-9 {
        padding: 1.8em;
    }

    .tags-list li.count-10 {
        padding: 2em;
    }

.next-steps {
    margin: 2vh 0;
}

.next-steps h2, .tags-list h2, .word-cluster h2 {
    width: 100%;
    border: none;
    border-bottom: solid;
    box-sizing: border-box;
    padding: 0.25em;
}

.next-steps ul {
    padding: 1.5em;
}

.next-steps li {
    margin-left: 1em;
    margin-bottom: 0.3em;
}

.newSession__buttons,
.summary__buttons {
    display: flex;
    justify-content: space-between;
    align-items: stretch;
}

.newSession__buttons button,
.summary__buttons button {
    border-radius: 15px;
    margin: 1vh;
    font-size: 1em;
    width: 50%;
    padding: 0.5em;
}

.newSession__buttons .accent,
.summary__buttons .accent,
.newSession__buttons .secondary,
.summary__buttons .secondary {
    border: solid;
}

.newSession__buttons .primary,
.summary__buttons .primary {
    border: solid 4px;
}

.newSession__buttons .primary:hover,
.summary__buttons .primary:hover {
    color: white;
}
.timeline div .round {
        border: solid 2px;
        border-radius: 50%;
        text-align: center;
        width: 2em;
        height: 2em;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1em;
        padding: 0.25em;
        border-radius: 5px;
    }
</style>

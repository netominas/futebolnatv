<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Campeonatos em destaque
    |--------------------------------------------------------------------------
    |
    | A ordem define a prioridade editorial. Os termos são comparados com o
    | nome do campeonato recebido da Wosti, ignorando acentos e maiúsculas.
    |
    */
    'limit' => 4,

    'competitions' => [
        ['label' => 'Champions League', 'terms' => ['champions league', 'liga dos campeoes']],
        ['label' => 'Libertadores', 'terms' => ['copa libertadores', 'libertadores']],
        ['label' => 'Brasileirão Série A', 'terms' => ['brasileirao serie a', 'brasileiro serie a']],
        ['label' => 'Copa do Brasil', 'terms' => ['copa do brasil']],
        ['label' => 'Premier League', 'terms' => ['premier league']],
        ['label' => 'La Liga', 'terms' => ['la liga', 'campeonato espanhol', 'liga espanhola']],
        ['label' => 'Campeonato Italiano', 'terms' => ['serie a italiana', 'campeonato italiano', 'serie a italia']],
        ['label' => 'Bundesliga', 'terms' => ['bundesliga']],
        ['label' => 'Ligue 1', 'terms' => ['ligue 1', 'campeonato frances']],
    ],
];

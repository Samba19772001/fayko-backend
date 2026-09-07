<?php

// Réglages métier propres à Fayko (cahier des charges §3.4, §3.6, §3.7).
// Centralisés ici pour ne jamais écrire ces valeurs "en dur" dans le code.
return [
    'frais_contrat' => (int) env('FAYKO_FRAIS_CONTRAT', 500),
    'delai_code_minutes' => (int) env('FAYKO_DELAI_CODE_MINUTES', 10),
    'retard_impaye_jours' => (int) env('FAYKO_RETARD_IMPAYE_JOURS', 30),
];

<?php
require_once __DIR__ . '/../../../.configs/config.php';

define('PACKLIST_FILE', __DIR__ . '/../../../.configs/data/packliste.json');

function packlist_seed() {
    // Aus der Packliste Kuşadası (23.09.–07.10.2026) übernommen
    $c = function ($name, $items) {
        return ['id' => 'cat' . uniqid(), 'name' => $name, 'items' => array_map(function ($i) {
            return [
                'id' => 'it' . uniqid(),
                'title' => $i[0],
                'qty' => $i[1] ?? '',
                'note' => $i[2] ?? '',
                'checked' => false,
            ];
        }, $items)];
    };
    return [
        $c('Gepäck-Budget', [
            ['Freigepäck gesamt', '80 kg', '20 kg × 4 Pers., Baby ohne Sitzplatz/Koffer, vorher bei Airline bestätigen'],
            ['Babysachen im Elterngepäck', '+5–6 kg', 'einplanen'],
        ]),
        $c('Kleidung – Erwachsene (pro Person)', [
            ['T-Shirts/Tops', '6', ''],
            ['Shorts/Hosen', '3', ''],
            ['Kleider (Frau)', '2', ''],
            ['Unterwäsche & Socken', '8 Paar', ''],
            ['Badesachen', '2', ''],
            ['Schlafkleidung', '2', ''],
            ['Jacke/Pulli', '1', 'Abende/Flug ~5°C'],
            ['Schuhe', '2 Paar', 'bequem + Badeschuhe'],
        ]),
        $c('Kleidung – Kinder (5 & 4 J.)', [
            ['Oberteile', '7', ''],
            ['Hosen/Shorts/Kleider', '5', ''],
            ['Unterwäsche & Socken', '8 Paar', 'Reserve wg. Sand/Kleckern'],
            ['Badesachen', '2', ''],
            ['Schlafanzug', '2', ''],
            ['Jacke/Pulli', '1', ''],
            ['Schuhe', '2 Paar', 'bequem + Badeschuhe'],
            ['Sonnenhut', '1', ''],
        ]),
        $c('Kleidung – Baby (1 J.)', [
            ['Bodys/Strampler', '10', 'leicht – lieber mehr'],
            ['Söckchen/Mützchen', '6', ''],
            ['Schwimmwindeln', '12–15', 'vor Ort teuer/rar'],
            ['Normale Windeln', 'Vorrat 3-4T', 'Rest vor Ort kaufen'],
            ['Lätzchen', '3', ''],
            ['Sonnenhut/-anzug', '1', ''],
        ]),
        $c('Baby & Kleinkind – Zubehör', [
            ['Feuchttücher', '2 Pack.', ''],
            ['Fieberthermometer', '1', ''],
            ['Fieberzäpfchen/-saft', '1 Pack.', ''],
            ['Reiseübelkeit-Mittel', 'b. Bedarf', 'für den Flug'],
            ['Nachtlicht', '1', ''],
            ['Sonnencreme LSF 50', '1–2 Fl.', 'wasserfest'],
            ['Schwimmflügel/-weste', '1 Set', 'Hotel hat oft nur Erw.-Gr.'],
        ]),
        $c('Gesundheit / Apotheke', [
            ['Durchfallmittel', 'je 1x', 'Kinder & Erwachsene'],
            ['Elektrolytpulver', '5–6 Btl.', ''],
            ['Pflaster & Heilsalbe', '1 Set', ''],
            ['Insektenschutz + Fenistil', 'je 1', ''],
            ['Ohrstöpsel fürs Flugzeug', '1/Kind', 'z.B. EarPlanes'],
            ['Nasenspray Kinder', '1', ''],
        ]),
        $c('Handgepäck / Flug', [
            ['Snacks ohne Krümel', '', ''],
            ['Spielzeug/Tablet + Kopfhörer', '1 Set', ''],
            ['Wechseloutfit pro Kind', '1', 'griffbereit'],
        ]),
        $c('Geld / Budget', [
            ['Kreditkarte o. Auslandsgeb.', '1', 'z.B. DKB, Revolut'],
            ['Bargeld in Lira', '50-100€', 'am Automaten abheben'],
            ['Kleine Scheine Trinkgeld', '', 'ca. 5–10 % üblich'],
            ['Budget Basar/Souvenirs', '', 'Feilschen gehört dazu'],
            ['Budget Ausflüge & Eis', '', 'nicht in AI enthalten'],
        ]),
        $c('Sonstiges (oft vergessen)', [
            ['Mehrfachstecker/USB-Hub', '1', 'Typ C/F wie DE'],
            ['Powerbank', '1', ''],
            ['Ausweis/Reisepass', 'je Kind', 'auch Baby – Gültigkeit prüfen'],
            ['Auslandskrankenversicherung', '', 'EHIC gilt in TR NICHT'],
            ['Buchungsunterlagen', 'digital + Papier', ''],
            ['Steckschutz Balkontür', '1 Set', 'wg. 1-Jährigem'],
            ['Pflaster mit Kindermotiven', '1x', ''],
        ]),
    ];
}

function packlist_load() {
    if (!file_exists(PACKLIST_FILE)) {
        $data = ['title' => 'Packliste Kuşadası', 'subtitle' => '23.09.–07.10.2026 · Nürnberg → Izmir', 'categories' => packlist_seed()];
        packlist_save($data);
        return $data;
    }
    $raw = file_get_contents(PACKLIST_FILE);
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = ['title' => 'Packliste', 'subtitle' => '', 'categories' => []];
    $data['categories'] = $data['categories'] ?? [];
    return $data;
}

function packlist_save($data) {
    $dir = dirname(PACKLIST_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(PACKLIST_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

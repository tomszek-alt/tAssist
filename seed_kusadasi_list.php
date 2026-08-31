<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/shared_lists_storage.php';

if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

function ski($title, $qty = '', $note = '') {
    return ['id' => 'it' . uniqid(), 'title' => $title, 'qty' => $qty, 'note' => $note, 'checked' => false];
}
function skc($name, $items) {
    return ['id' => 'cat' . uniqid(), 'name' => $name, 'items' => $items];
}

$categories = [
    skc('Gepäck-Budget', [
        ski('Freigepäck gesamt', '80 kg', '20 kg × 4 Pers., Baby ohne Sitzplatz/Koffer, vorher bei Airline bestätigen'),
        ski('Babysachen im Elterngepäck', '+5–6 kg', 'einplanen'),
    ]),
    skc('Kleidung – Erwachsene (pro Person)', [
        ski('T-Shirts/Tops', '6'), ski('Shorts/Hosen', '3'), ski('Kleider (Frau)', '2'),
        ski('Unterwäsche & Socken', '8 Paar'), ski('Badesachen', '2'), ski('Schlafkleidung', '2'),
        ski('Jacke/Pulli', '1', 'Abende/Flug ~5°C'), ski('Schuhe', '2 Paar', 'bequem + Badeschuhe'),
    ]),
    skc('Kleidung – Kinder (5 & 4 J.)', [
        ski('Oberteile', '7'), ski('Hosen/Shorts/Kleider', '5'),
        ski('Unterwäsche & Socken', '8 Paar', 'Reserve wg. Sand/Kleckern'), ski('Badesachen', '2'),
        ski('Schlafanzug', '2'), ski('Jacke/Pulli', '1'), ski('Schuhe', '2 Paar', 'bequem + Badeschuhe'),
        ski('Sonnenhut', '1'),
    ]),
    skc('Kleidung – Baby (1 J.)', [
        ski('Bodys/Strampler', '10', 'leicht – lieber mehr'), ski('Söckchen/Mützchen', '6'),
        ski('Schwimmwindeln', '12–15', 'vor Ort teuer/rar'), ski('Normale Windeln', 'Vorrat 3-4T', 'Rest vor Ort kaufen'),
        ski('Lätzchen', '3'), ski('Sonnenhut/-anzug', '1'),
    ]),
    skc('Baby & Kleinkind – Zubehör', [
        ski('Feuchttücher', '2 Pack.'), ski('Fieberthermometer', '1'), ski('Fieberzäpfchen/-saft', '1 Pack.'),
        ski('Reiseübelkeit-Mittel', 'b. Bedarf', 'für den Flug'), ski('Nachtlicht', '1'),
        ski('Sonnencreme LSF 50', '1–2 Fl.', 'wasserfest'), ski('Schwimmflügel/-weste', '1 Set', 'Hotel hat oft nur Erw.-Gr.'),
    ]),
    skc('Gesundheit / Apotheke', [
        ski('Durchfallmittel', 'je 1x', 'Kinder & Erwachsene'), ski('Elektrolytpulver', '5–6 Btl.'),
        ski('Pflaster & Heilsalbe', '1 Set'), ski('Insektenschutz + Fenistil', 'je 1'),
        ski('Ohrstöpsel fürs Flugzeug', '1/Kind', 'z.B. EarPlanes'), ski('Nasenspray Kinder', '1'),
    ]),
    skc('Handgepäck / Flug', [
        ski('Snacks ohne Krümel'), ski('Spielzeug/Tablet + Kopfhörer', '1 Set'),
        ski('Wechseloutfit pro Kind', '1', 'griffbereit'),
    ]),
    skc('Geld / Budget', [
        ski('Kreditkarte o. Auslandsgeb.', '1', 'z.B. DKB, Revolut'), ski('Bargeld in Lira', '50-100€', 'am Automaten abheben'),
        ski('Kleine Scheine Trinkgeld', '', 'ca. 5–10 % üblich'), ski('Budget Basar/Souvenirs', '', 'Feilschen gehört dazu'),
        ski('Budget Ausflüge & Eis', '', 'nicht in AI enthalten'),
    ]),
    skc('Sonstiges (oft vergessen)', [
        ski('Mehrfachstecker/USB-Hub', '1', 'Typ C/F wie DE'), ski('Powerbank', '1'),
        ski('Ausweis/Reisepass', 'je Kind', 'auch Baby – Gültigkeit prüfen'),
        ski('Auslandskrankenversicherung', '', 'EHIC gilt in TR NICHT'), ski('Buchungsunterlagen', 'digital + Papier'),
        ski('Steckschutz Balkontür', '1 Set', 'wg. 1-Jährigem'), ski('Pflaster mit Kindermotiven', '1x'),
    ]),
];

$lists = shared_lists_load();
$secret = shared_lists_new_secret();
$lists[] = [
    'id' => 'sl' . uniqid(),
    'secret' => $secret,
    'title' => 'Packliste Kuşadası',
    'subtitle' => '23.09.–07.10.2026 · Nürnberg → Izmir',
    'categories' => $categories,
];
shared_lists_save($lists);

echo "✅ Angelegt! Link zum Teilen:\n\n";
echo "https://hugahuga.com/kira/shared.php?secret={$secret}\n\n";
echo "Diese Datei (seed_kusadasi_list.php) kannst du danach löschen.\n";

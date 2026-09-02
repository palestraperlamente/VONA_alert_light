# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

TPS³ – VONA Alert Light: a two-part system that turns INGV volcanic/seismic bulletins into a physical alert light.

1. **`sniffetto/`** – a PHP REST API ("Sniffetto") that periodically scrapes INGV data sources and stores/serves the latest alert state.
2. **`NodeMcu V3/vonaalertlight.ino`** – an ESP8266 (NodeMCU) Arduino sketch that polls the API and drives an RGB LED to reflect the current VONA alert color.

`TPS³ – VONA Alert Light.pdf` in the repo root is the project writeup/documentation (not source).

## Educational context (from the project PDF)

This repo is the implementation of a didactic project, not a product. `TPS³ – VONA Alert Light.pdf` documents it as a **vertical TPSIT project spanning the 3-year *triennio*** (3rd/4th/5th year) of an Italian *istituto tecnico* (informatica/telecomunicazioni track), authored by carlo.puglisi@palestraperlamente.org. Knowing this framing matters when deciding how "correct" vs. "instructive" a change should be — e.g. quick/hacky code that's easy for students to read may be preferred over production hardening, and deviations from the original plan below are often deliberate simplifications, not oversights.

**Motivation**: INGV (Istituto Nazionale di Geofisica e Vulcanologia) has issued VONA (Volcano Observatory Notice for Aviation) bulletins since 2014 whenever Etna erupts significantly, to warn aviation authorities (Catania airport is nearby) and, secondarily, residents dealing with ashfall. The project's real-world goal is a physical prototype (an alert light) installed in the school's entrance hall.

**Planned 4-phase pipeline** (as designed in the PDF — note it does **not** match the current implementation 1:1, see below):
1. Scrape the INGV "Comunicati VONA" page, find the latest bulletin's PDF link, extract its text, parse it into ~16 key/value fields — planned in **Python**.
2. Cache those key/value pairs into a local DB table — planned in **Python**.
3. Serve the latest record via a RESTful API (Apache/nginx + **PHP**) returning JSON.
4. A microcontroller (planned as **ESP32**) polls the API, decodes the JSON, and drives an RGB LED to the corresponding alert color.

**Deviations in this repo from that plan** (useful context, not bugs to "fix" back toward the PDF unless asked):
- Phases 1+2+3 were all collapsed into PHP (`sniffetto/`) — there is no separate Python scraper; `lib/VONA.php` and `lib/terremoti.php` do the scraping/parsing that the PDF assigned to Python.
- The firmware targets an **ESP8266 NodeMCU**, not the ESP32 the PDF specifies.
- The PDF's Python library picks (`requests`, `htmldom`, `PdfFileReader`) were never used since scraping moved to PHP (`smalot/pdfparser` instead).

**Per-year curriculum breakdown** (for context on why code/comments may be pitched at different skill levels in different parts of the stack):
- **3rd year** – microcontrollers/SoC: what a microcontroller is, using ESP32/ESP8266 for simple components (RGB LED) via Arduino IDE and visual tools (STEAMakersBlocks).
- **4th year** – web scraping & DB: Python scraping, DOM parsing, PDF text extraction, DB storage (as planned; realized in PHP in this repo instead).
- **5th year** – RESTful API & DB: RDBMS storage, a PHP framework for the API (Micron), JSON responses.
- **(optional, transversal)** – package design: 2D/3D CAD + laser cutting for a physical enclosure.

**Other context from the PDF**: recommends Trello/Gantt/GitHub for cross-class project management (GPOI subject); evaluation is informal, based on the working prototype plus technical skill, collaboration, initiative, and documentation rather than traditional grading; suggests documenting the "making of" (photos/screenshots) for a video on the school's website. Listed future extensions: adding Etna seismic-activity data, Sicilia Region Protezione Civile bulletins (wildfire/heatwave, hydro-meteorological warnings), and richer output via LCD 2x16 or OLED displays instead of just an LED.

## Backend architecture (`sniffetto/`)

Built on **Micron**, a small vendored PHP REST framework (`sniffetto/micron/`, loaded via `micron/Micron.php`, not composer-autoloaded — it's `include_once`'d directly). Composer is only used for two real dependencies:
- `gabordemooij/redbean` — schema-less ORM (`RedBeanPHP\R`) used for all DB persistence.
- `smalot/pdfparser` — parses the INGV VONA bulletin PDF.

**Request flow**: all requests hit `sniffetto/index.php` via `.htaccess` rewrite (`RewriteRule ^(.+)$ index.php?uri=$1`, with `RewriteBase /sniffetto/`). `index.php` sets up the DB connection with `R::setup()`, builds a `Route` object, and defines routes inline with closures. Unmatched routes fall through to `404.php`.

**Data flow / domain logic**:
- `GET /sniff` is the ingestion endpoint, meant to be hit by an external cron (see comment in `index.php`: "API chiamata da https://console.cron-job.org/jobs"). It runs two independent scrapers and persists new records:
  - `lib\VONA::sniff()` (`sniffetto/lib/VONA.php`) — for `etna`, downloads the INGV "Comunicati VONA" HTML page, finds the latest bulletin PDF link, parses the PDF text with `smalot/pdfparser`, and extracts numbered fields (`(n) Key: Value`) via regex into a keyed array.
  - `lib\terremoti::sniff()` (`sniffetto/lib/terremoti.php`) — fetches the INGV FDSN earthquake webservice (pipe-delimited text) for the latest event near Etna (lat/lon/radius hardcoded).
  - `dbfunctions.php` has `updateVONA()`/`updateTerremoti()` which dedupe on `notice_number`/`event_id` before inserting a new RedBean bean, and `getLastVONA()`/`getLastTerremoto()` which read the latest row back out.
- `GET /v1/vona/{vulcano}` and `GET /v1/terremoti` are the read endpoints the NodeMCU firmware and any other client poll for current state. Note: the `{vulcano}` path param is accepted but not actually used to filter — `getLastVONA()` always returns the single most recent `vona` row regardless of volcano.

**Config** (`sniffetto/config.php`, `sniffetto/index.php`): DB credentials and JWT secret switch based on `$_SERVER['HTTP_HOST']` (production `*.noexit.it` vs local dev, default `root`/`root` / db `sniffetto`). Both files use `str_ends_with($_SERVER['HTTP_HOST'], 'noexit.it')` to detect production — this was previously two inconsistent exact-match checks (`index.php` required `www.noexit.it`, `config.php` required bare `noexit.it`), which meant requests to whichever hostname variant the *other* file didn't check fell through to the local-dev DSN (`localhost`/`root`/`root`) and failed with "Could not connect to database" in production — this is exactly what caused it to break for anyone (e.g. mobile browsers) hitting the bare `noexit.it` host instead of `www.noexit.it`.

Token/JWT auth (Micron's built-in middleware) is explicitly disabled for this app (`MiddlewareConfiguration::getConfiguration(tokenControl: false)` in `index.php`) — there is no auth on any route.

## Firmware (`NodeMcu V3/vonaalertlight.ino`)

Arduino sketch for ESP8266 (uses `ESP8266WiFi`/`ESP8266HTTPClient`). Every 10s it GETs `http://www.noexit.it/sniffetto/v1/vona`, extracts the `current_color` field from the JSON response via raw substring search (not a JSON parser), and sets an RGB LED (pins 2, 0, 4) accordingly: GREEN/YELLOW/ORANGE/RED map to specific `analogWrite` values, unrecognized values light solid white, and a magenta pulse marks each check-in-progress cycle. Also reconnects Wi-Fi if RSSI reads 0, and self-restarts (`ESP.restart()`) every hour via a pinned task.

## Development notes

- No test suite, build step, linter, or package.json in this repo — it's deployed as-is (plain PHP + Arduino sketch).
- PHP dependencies: run `composer install` inside `sniffetto/` to populate `vendor/`.
- Local run requires Apache with `mod_rewrite` + PHP 8+, DB `sniffetto` reachable at `localhost` with `root`/`root` (per `config.php`/`index.php` defaults), matching the Micron framework's own setup notes in `sniffetto/READ BEFORE TO START!.txt`.
- `sniffetto/micron/`, `sniffetto/vendor/`, and `Micron Basic Example.postman_collection.json` are vendored/example material from the upstream Micron framework, not app-specific code — the app's own logic lives in `sniffetto/index.php`, `sniffetto/dbfunctions.php`, `sniffetto/config.php`, and `sniffetto/lib/`.

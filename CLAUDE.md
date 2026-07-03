# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

TPS³ – VONA Alert Light: a two-part system that turns INGV volcanic/seismic bulletins into a physical alert light.

1. **`sniffetto/`** – a PHP REST API ("Sniffetto") that periodically scrapes INGV data sources and stores/serves the latest alert state.
2. **`NodeMcu V3/vonaalertlight.ino`** – an ESP8266 (NodeMCU) Arduino sketch that polls the API and drives an RGB LED to reflect the current VONA alert color.

`TPS³ – VONA Alert Light.pdf` in the repo root is the project writeup/documentation (not source).

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

**Config** (`sniffetto/config.php`, `sniffetto/index.php`): DB credentials and JWT secret switch based on `$_SERVER['HTTP_HOST']` (production `noexit.it` vs local dev, default `root`/`root` / db `sniffetto`). Note the host check is inconsistent between files — `index.php` checks for `www.noexit.it`, `config.php` checks for `noexit.it` (no `www.`) — be aware of this when touching either.

Token/JWT auth (Micron's built-in middleware) is explicitly disabled for this app (`MiddlewareConfiguration::getConfiguration(tokenControl: false)` in `index.php`) — there is no auth on any route.

## Firmware (`NodeMcu V3/vonaalertlight.ino`)

Arduino sketch for ESP8266 (uses `ESP8266WiFi`/`ESP8266HTTPClient`). Every 10s it GETs `http://www.noexit.it/sniffetto/v1/vona`, extracts the `current_color` field from the JSON response via raw substring search (not a JSON parser), and sets an RGB LED (pins 2, 0, 4) accordingly: GREEN/YELLOW/ORANGE/RED map to specific `analogWrite` values, unrecognized values light solid white, and a magenta pulse marks each check-in-progress cycle. Also reconnects Wi-Fi if RSSI reads 0, and self-restarts (`ESP.restart()`) every hour via a pinned task.

## Development notes

- No test suite, build step, linter, or package.json in this repo — it's deployed as-is (plain PHP + Arduino sketch).
- PHP dependencies: run `composer install` inside `sniffetto/` to populate `vendor/`.
- Local run requires Apache with `mod_rewrite` + PHP 8+, DB `sniffetto` reachable at `localhost` with `root`/`root` (per `config.php`/`index.php` defaults), matching the Micron framework's own setup notes in `sniffetto/READ BEFORE TO START!.txt`.
- `sniffetto/micron/`, `sniffetto/vendor/`, and `Micron Basic Example.postman_collection.json` are vendored/example material from the upstream Micron framework, not app-specific code — the app's own logic lives in `sniffetto/index.php`, `sniffetto/dbfunctions.php`, `sniffetto/config.php`, and `sniffetto/lib/`.

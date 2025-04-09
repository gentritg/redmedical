# Benutzerhandbuch

## Einrichtung und Start der Anwendung

Um die Anwendung zu starten, müssen folgende Dienste gestartet werden:

### 1. RedProviderPortal starten

Das RedProviderPortal ist der externe Dienst, der für die Verarbeitung der Bestellungen zuständig ist. 
Starten Sie den Dienst im Hintergrund mit:

```bash
node ./RedProviderPortal/redproviderportal.js &
```

Der Dienst läuft standardmäßig auf Port 3000.

### 2. Laravel-Server starten

Starten Sie den Laravel-Server im Hintergrund mit:

```bash
php artisan serve &
```

Der Server läuft standardmäßig auf `http://127.0.0.1:8000`.

### 3. Queue Worker starten

Für die asynchrone Verarbeitung der Bestellungsstatusänderungen muss der Queue Worker gestartet werden:

```bash
php artisan queue:work &
```

### 4. Scheduler (optional)

Wenn Sie möchten, dass die Bestellungsstatusabfragen automatisch in regelmäßigen Abständen erfolgen, können Sie auch den Scheduler starten:

```bash
php artisan schedule:work &
```

Dies führt den Befehl `orders:check-statuses` alle 5 Minuten aus.

## API-Endpunkte

Die Anwendung stellt folgende API-Endpunkte bereit:

### Bestellungen auflisten

```bash
curl http://localhost:8000/api/orders
```

Mit Filterung nach Namen:

```bash
curl http://localhost:8000/api/orders?name=Suchbegriff
```

### Bestellung erstellen

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{"name": "Neue Bestellung", "type": "connector"}'
```

Gültige Werte für `type` sind `connector` und `vpn_connection`.

### Bestellung abrufen

```bash
curl http://localhost:8000/api/orders/{id}
```

Ersetzen Sie `{id}` durch die UUID der Bestellung.

### Bestellstatus aktualisieren

```bash
curl -X PUT http://localhost:8000/api/orders/{id} \
  -H "Content-Type: application/json" \
  -d '{"status": "processing"}'
```

Gültige Werte für `status` sind `ordered`, `processing` und `completed`.

### Bestellung löschen

```bash
curl -X DELETE http://localhost:8000/api/orders/{id}
```

**Hinweis:** Bestellungen können nur gelöscht werden, wenn sie den Status `completed` haben.

## Konfiguration

### Umgebungsvariablen

Die Anwendung kann über folgende Umgebungsvariablen konfiguriert werden:

- `SERVICES_RED_PROVIDER_PORTAL_URL`: URL des RedProviderPortals (Standard: http://localhost:3000)
- `SERVICES_RED_PROVIDER_PORTAL_CLIENT_ID`: Client-ID für das RedProviderPortal (Standard: Fun)
- `SERVICES_RED_PROVIDER_PORTAL_CLIENT_SECRET`: Client-Secret für das RedProviderPortal (Standard: =work@red)
- `SERVICES_RED_PROVIDER_PORTAL_MOCK`: Wenn auf `true` gesetzt, wird ein Mock-Service anstelle des echten RedProviderPortals verwendet

## Tests ausführen

Die Anwendung enthält eine umfangreiche Testsammlung. Um alle Tests auszuführen:

```bash
php artisan test
```

Für spezifische Tests können Filter verwendet werden:

```bash
php artisan test --filter=OrderApiTest
```

## Fehlerbehebung

### Port bereits in Verwendung

Wenn beim Starten der Dienste die Meldung "Address already in use" erscheint, läuft bereits ein Dienst auf dem entsprechenden Port. Sie können:

1. Den laufenden Prozess beenden:
   ```bash
   # Für den Laravel-Server (Port 8000)
   lsof -ti:8000 | xargs kill -9
   
   # Für das RedProviderPortal (Port 3000)
   lsof -ti:3000 | xargs kill -9
   ```

2. Oder einen anderen Port für den Laravel-Server verwenden:
   ```bash
   php artisan serve --port=8001 &
   ```

### SSL-Zertifikat-Fehler

Wenn Fehler bezüglich des SSL-Zertifikats auftreten, stellen Sie sicher, dass die Datei `ssl_cert.pem` im Verzeichnis `storage/app/` existiert. Die Anwendung verwendet dieses Zertifikat für die sichere Kommunikation mit dem RedProviderPortal. 
# Two Factor Authentication

Verifica in due passaggi per phpBB 3.3: app di autenticazione (Google Authenticator e simili),
codice via email e codici di riserva monouso.

*Read this in [English](README.en.md).*

| | |
|---|---|
| **Autore** | Salvo Cortesiano |
| **Copyright** | (c) 2026-08-11 20:00 CEST Salvo Cortesiano |
| **Forum** | https://netshadows.de/ombra/ |
| **Licenza** | GNU General Public License, version 2 (GPL-2.0) |
| **Versione** | 1.3.0 |


**Guida passo passo:** [Guida all'uso e alla prima configurazione](docs/GUIDA.it.md)

## Installazione

1. Copia la cartella in `ext/salvocortesiano/twofactor/`
2. ACP → **Personalizza** → **Gestisci estensioni** → **Abilita**
3. ACP → **Estensioni** → **Autenticazione a due fattori** per scegliere i metodi

Requisiti: phpBB 3.3.0+ e PHP 7.1.3+.

## Come funziona

L'amministratore decide **quali metodi il forum mette a disposizione**; ogni utente sceglie poi se
usarli, dal proprio Pannello di controllo → Profilo → Autenticazione a due fattori.

- **App di autenticazione (TOTP)** — codici a sei cifre generati sul telefono, senza connessione.
  Funziona con Google Authenticator, Microsoft Authenticator, Authy, Aegis, 1Password e qualsiasi
  altra app conforme allo standard RFC 6238.
- **Codice via email** — un codice inviato all'indirizzo dell'account, con scadenza e numero
  massimo di tentativi configurabili.
- **Codici di riserva** — otto codici monouso generati quando si attiva il primo metodo, salvati
  solo come impronta e mostrati una volta sola.

Chi ha attivato un metodo, dopo l'accesso viene portato alla schermata di verifica e non può
navigare finché non inserisce un codice valido. La sessione resta verificata fino al logout.

### Obbligo e gruppi esclusi

Con **Rendi obbligatoria la verifica in due passaggi** chi non l'ha ancora configurata viene
portato alla pagina di attivazione e non può navigare finché non sceglie un metodo: resta comunque
libero di scegliere *quale*, fra quelli che hai attivato.

**Gruppi esclusi dall'obbligo** mostra l'elenco completo dei gruppi del forum — amministratori,
moderatori globali, gruppi tuoi — con una casella ciascuno. Chi appartiene a un gruppo spuntato non
viene obbligato; se però attiva la verifica di sua iniziativa, il codice gli viene comunque chiesto,
perché è stata una sua scelta.

### Invito agli utenti

La spunta **Invita gli utenti ad attivarla**, sotto ai metodi disponibili, mostra un avviso verde
in cima a tutte le pagine del forum con un collegamento diretto alla pagina di attivazione. Lo vede
soltanto chi non ha ancora configurato nulla: appena attiva un metodo, l'avviso sparisce da solo.
Il testo contiene il nome dell'utente e quello del forum. Quando l'utente attiva la verifica,
l'avviso non sparisce ma cambia: diventa azzurro e conferma che il suo account è protetto.
Non è chiudibile.

## Il codice QR

Viene disegnato **dal browser**, sul posto: il segreto non passa da servizi online di generazione
QR, che altrimenti lo conoscerebbero e vanificherebbero il secondo fattore. Chi non riesce a
inquadrare può inserire la chiave a mano, scegliendo l'inserimento manuale nell'app.

## Se resti chiuso fuori

Tre vie d'uscita, in ordine di comodità:

1. **Un codice di riserva** al posto del codice normale, nella stessa casella.
2. **Sblocco da ACP** — un altro amministratore apre il pannello dell'estensione, scrive il nome
   utente nella sezione *Sblocca un utente* e rimuove la verifica da quell'account.
3. **Da database**, se nessun amministratore riesce più ad accedere. Da phpMyAdmin:

   ```sql
   DELETE m FROM phpbb_tfa_methods m
     JOIN phpbb_users u ON u.user_id = m.user_id
     WHERE u.username_clean = 'tuonomeutente';
   ```

   Sostituisci il prefisso `phpbb_` con quello del tuo forum e il nome utente con il tuo, scritto
   in minuscolo.

Se hai reso obbligatoria la verifica e resti bloccato prima ancora di arrivare al pannello,
spegni l'obbligo da phpMyAdmin:

```sql
UPDATE phpbb_config SET config_value = '0' WHERE config_name = 'tfa_required';
```

Nel pannello dell'estensione, sezione *Se resti chiuso fuori*, trovi due pulsanti **Esegui adesso**
che fanno il lavoro al posto tuo, senza aprire phpMyAdmin: uno rimuove la verifica dal tuo account,
l'altro spegne l'obbligo per tutti. Le istruzioni SQL sono mostrate accanto, già compilate con il
prefisso delle tue tabelle e con il tuo nome utente, per quando l'ACP non fosse più raggiungibile.

## Prima di attivare il metodo email

Verifica che il forum invii davvero le email, per esempio provando il recupero password. Se la
posta non parte, quel metodo diventa un muro: in quel caso lascia attiva solo l'app.

## Note tecniche

L'algoritmo TOTP e l'encoder QR sono scritti da zero, senza librerie esterne né chiamate a
servizi di terze parti. Il TOTP è stato verificato contro i vettori di prova pubblicati nella
RFC 6238; l'encoder QR è stato verificato decodificando i codici generati.

I codici via email e i codici di riserva sono conservati come impronta SHA-256, mai in chiaro.
Il confronto avviene a tempo costante, per non lasciare indizi sfruttabili.

## Tabelle create

`tfa_methods`, `tfa_backup`, `tfa_sessions`, `tfa_email`, con il prefisso del tuo forum.
Vengono rimosse scegliendo *Elimina i dati* in Gestisci estensioni.

## Licenza

GNU General Public License, version 2 (GPL-2.0)

Copyright (c) 2026-08-11 20:00 CEST Salvo Cortesiano — https://netshadows.de/ombra/

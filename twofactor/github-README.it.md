# phpbb-two-factor

Verifica in due passaggi per phpBB, senza appoggiarsi ai server di nessun altro. Chi accede con la
password giusta deve comunque dimostrare di essere davvero lui, con un codice a sei cifre da un'app
di autenticazione oppure inviato alla sua casella. L'amministratore sceglie quali metodi il forum
offre, ogni iscritto sceglie quale usare, e nessuno resta chiuso fuori quando smarrisce il telefono.
Codici, segreti e generazione del QR restano tutti sul tuo hosting.

![phpBB 3.3+](https://img.shields.io/badge/phpBB-3.3%2B-blue)
![PHP 7.1+](https://img.shields.io/badge/PHP-7.1%2B-8892bf)
![licenza GPL-2.0-only](https://img.shields.io/badge/licenza-GPL--2.0--only-green)

[🇬🇧 English](README.md) · 🇮🇹 Italiano

---

## Indice

- [In sintesi](#in-sintesi)
- [Requisiti](#requisiti)
- [Installazione](#installazione)
- [Configurazione](#configurazione)
- [Caratteristiche](#caratteristiche)
- [Come funziona](#come-funziona)
- [Sicurezza](#sicurezza)
- [Trasparenza verso gli utenti](#trasparenza-verso-gli-utenti)
- [Se resti chiuso fuori](#se-resti-chiuso-fuori)
- [Risoluzione dei problemi](#risoluzione-dei-problemi)
- [Limiti noti](#limiti-noti)
- [Traduzioni](#traduzioni)
- [Licenza e attribuzione](#licenza-e-attribuzione)

---

## In sintesi

| | |
|---|---|
| **Metodi** | App di autenticazione (TOTP), codice via email, codici di riserva monouso |
| **Chi decide** | L'amministratore abilita i metodi, ogni iscritto sceglie fra quelli |
| **Facoltativa o obbligatoria** | Entrambe: lasciala volontaria, oppure rendila obbligatoria escludendo alcuni gruppi |
| **Servizi esterni** | Nessuno. Nessuna chiave API, nessuna chiamata a terzi, nessun account da aprire |
| **Dipendenze** | Nessuna. Nessun pacchetto Composer, nessuna libreria inclusa |
| **Lingue** | Italiano e inglese, complete per ogni schermata e per l'email |

---

## Requisiti

- phpBB **3.3.0** o successivo
- PHP **7.1.3** o successivo, con `hash_hmac`, `random_int` e `hash_equals` (tutte standard)
- Invio email funzionante, **solo** se intendi offrire il metodo via posta

Se qualcosa manca, l'estensione si rifiuta di attivarsi invece di installarsi a metà.

---

## Installazione

1. Copia la cartella nel forum in modo che si trovi in `ext/salvocortesiano/twofactor/`
2. **ACP → Personalizza → Gestisci estensioni**
3. Trova *Two Factor Authentication* e premi **Abilita**

A quel punto vengono create quattro tabelle, tutte con il prefisso del tuo forum. Nient'altro del
forum viene modificato.

Per rimuoverla: **Disabilita** ferma subito la verifica e conserva le impostazioni; **Elimina i
dati** rimuove anche le tabelle e ogni segreto conservato.

---

## Configurazione

Tutto si trova in **ACP → Estensioni → Autenticazione a due fattori**.

| Impostazione | Cosa fa |
|---|---|
| App di autenticazione | Offre i codici TOTP di Google Authenticator, Authy, Aegis, 1Password e simili |
| Codice via email | Offre un codice inviato all'indirizzo dell'account |
| Invita gli utenti ad attivarla | Mostra un avviso su tutte le pagine, con il nome dell'iscritto e del forum |
| Rendi obbligatoria la verifica | Porta chi non ha un metodo alla pagina di attivazione finché non ne sceglie uno |
| Gruppi esclusi | Tutti i gruppi del forum con una casella ciascuno: i membri di quelli spuntati non sono mai obbligati |
| Durata del codice via email | Da 60 a 3600 secondi, valore predefinito 600 |
| Tentativi consentiti | Errori che un codice email sopporta prima di essere scartato, predefinito 5 |
| Sblocca un utente | Rimuove la verifica da un account indicato, per chi ha perso il telefono |

Il pannello riporta anche quanti account usano ciascun metodo, così vedi la diffusione senza
interrogare il database.

---

## Caratteristiche

**App di autenticazione (TOTP).** Codici a sei cifre generati sul telefono, senza connessione.
Funziona qualsiasi app conforme alla RFC 6238: non c'è nulla di specifico di Google, nonostante il
nome con cui quasi tutti la conoscono.

**Codici via email.** Un codice inviato all'indirizzo dell'account, con durata e limite di tentativi
che decidi tu. Conservato cifrato, invalidato al primo utilizzo.

**Codici di riserva.** Otto codici monouso, creati quando si attiva il primo metodo e mostrati una
sola volta. L'iscritto può rigenerarli quando vuole, annullando i precedenti.

**QR disegnato in locale.** La pagina di attivazione genera il QR nel browser, con un encoder scritto
apposta per questa estensione. Il segreto non viene mai consegnato a un generatore di QR online, che
altrimenti saprebbe produrre i codici dei tuoi iscritti. Chi non riesce a inquadrare digita la
chiave.

**Facoltativa o obbligatoria, per gruppo.** Lasciala del tutto volontaria, oppure rendila
obbligatoria escludendo i gruppi che preferisci. Essere esclusi significa non essere obbligati: chi
la attiva comunque continua a ricevere la richiesta del codice, perché è stata una sua scelta.

**Avviso di invito.** Un messaggio in cima a tutte le pagine che invita ad attivarla, con il nome
dell'iscritto e quello del forum. Quando la attiva, il messaggio si trasforma in una conferma che il
suo account è protetto.

**Vie d'uscita incluse.** Un amministratore può azzerare la verifica di qualunque account dal
pannello, e due pulsanti gestiscono le emergenze: rimuovere la verifica dal proprio account, oppure
spegnere l'obbligo per tutti. Entrambi mostrano l'istruzione SQL corrispondente, già compilata con il
prefisso delle tue tabelle e il tuo nome utente, per il caso in cui l'ACP stesso non sia
raggiungibile.

---

## Come funziona

Chi ha attivato un metodo, dopo l'accesso viene portato a una schermata di verifica e non può
navigare finché non inserisce un codice valido. La sessione resta verificata fino all'uscita.

I codici si basano sull'orologio del server, con trenta secondi di tolleranza in entrambe le
direzioni, così un telefono leggermente avanti o indietro funziona lo stesso. La schermata accetta
nella stessa casella un codice TOTP, uno ricevuto via email o uno di riserva: nessuno deve indovinare
il campo giusto mentre ha fretta.

Accesso e uscita restano sempre raggiungibili, e la pagina di attivazione è esclusa dall'obbligo:
una politica obbligatoria non può intrappolare nessuno in un rimando infinito.

---

## Sicurezza

- Codici email e codici di riserva sono conservati come **impronte SHA-256**, mai in chiaro
- Ogni confronto avviene a **tempo costante**, così la durata non lascia indizi
- I segreti TOTP nascono da una sorgente casuale **adatta alla crittografia**
- I codici via email hanno **scadenza e limite di tentativi**: indovinare sei cifre non è praticabile
- I codici di riserva sono **monouso** e vengono marcati come spesi nel momento in cui funzionano
- Nulla lascia il tuo server: **nessun servizio esterno** vede mai un segreto, un codice o un QR

L'implementazione TOTP è stata verificata contro i vettori di prova pubblicati nella RFC 6238.
L'encoder QR è stato verificato decodificando i codici prodotti e confrontando la matrice con
un'implementazione di riferimento indipendente.

---

## Trasparenza verso gli utenti

Gli iscritti non vengono mai colti di sorpresa. Vedono cosa viene chiesto e perché:

- l'avviso di invito spiega il senso prima che cambi qualcosa
- per attivarla serve confermare un codice funzionante, quindi nessuno resta con una configurazione rotta
- i codici di riserva compaiono in quel momento, con l'avvertenza chiara che non potranno essere rimostrati
- la pagina di attivazione è sempre visitabile per disattivare un metodo o rigenerare i codici

---

## Se resti chiuso fuori

Tre vie d'uscita, dalla più comoda.

**1. Un codice di riserva.** Si inserisce nella stessa casella del codice normale.

**2. Dall'ACP.** Un altro amministratore apre il pannello dell'estensione, scrive il nome utente in
*Sblocca un utente* e preme il pulsante. Prima accertati che la persona sia davvero chi dice di
essere: quella funzione è, per sua natura, un modo per aggirare la sua protezione.

**3. Dal database**, quando nessun amministratore riesce più ad accedere. Da phpMyAdmin, sostituendo
prefisso e nome utente con i tuoi:

```sql
DELETE m FROM phpbb_tfa_methods m
  JOIN phpbb_users u ON u.user_id = m.user_id
  WHERE u.username_clean = 'tuonomeutente';
```

Se è l'obbligo a bloccarti:

```sql
UPDATE phpbb_config SET config_value = '0' WHERE config_name = 'tfa_required';
```

Entrambe le istruzioni compaiono anche nel pannello, già compilate con il tuo prefisso e il tuo nome
utente.

---

## Risoluzione dei problemi

**I codici vengono sempre rifiutati.** Controlla prima l'orologio del telefono e attiva data e ora
automatiche. L'estensione tollera già trenta secondi di scarto in entrambe le direzioni.

**Le email non arrivano mai.** Prova la posta del forum con un recupero password. Finché non
funziona, lascia disattivato il metodo via email: un secondo fattore che non consegna è una porta
chiusa. Gli iscritti possono comunque entrare con un codice di riserva.

**Un iscritto resta fermo sulla pagina di attivazione.** È l'obbligo che sta funzionando. Se davvero
non può adeguarsi, niente telefono e niente email, aggiungi il suo gruppo fra quelli esclusi.

**A un amministratore viene chiesto il codice benché il suo gruppo sia escluso.** È previsto: essere
esclusi toglie l'obbligo, non la protezione che si è scelta da soli. Per non ricevere più la
richiesta basta disattivare il metodo dal pannello di controllo.

---

## Limiti noti

- **Le chiavi hardware non sono supportate.** U2F è superato e WebAuthn non è ancora implementato;
  l'architettura tiene i metodi separati, quindi si può aggiungere senza toccare l'esistente.
- **La verifica vale per sessione**, non per singola azione delicata. Non c'è una seconda richiesta
  prima di entrare nell'ACP se la sessione è già verificata.
- **Versioni QR dalla 1 alla 20** nell'encoder incluso: molto oltre quello che serve a un indirizzo
  `otpauth://`, ma non l'intera specifica.
- **Nessun recupero automatico via email.** Riprendere un account rimasto senza codici di riserva
  richiede un amministratore, e la scelta è voluta: un recupero via email ridurrebbe il secondo
  fattore alla robustezza della casella di posta.

---

## Traduzioni

Italiano e inglese sono complete: ogni schermata, ogni errore, l'ACP, il pannello utente e l'email
con il codice. I due file contengono le stesse chiavi, verificate automaticamente perché in nessuna
delle due lingue compaia un segnaposto grezzo.

Per aggiungere una lingua si copia `language/en/` nel proprio codice e si traducono i valori. phpBB
mostra a ogni iscritto la lingua del suo account e ricade sull'inglese per quello che manca.

---

## Licenza e attribuzione

Distribuita sotto **GNU General Public License, versione 2 (GPL-2.0-only)**.

Copyright © 2026 Salvo Cortesiano — [netshadows.de/ombra](https://netshadows.de/ombra/)

L'algoritmo TOTP segue la RFC 6238 e l'encoder QR segue la ISO/IEC 18004: entrambi implementati dalle
specifiche, senza codice di terze parti.

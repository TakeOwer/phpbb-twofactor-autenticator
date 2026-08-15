# Autenticazione a due fattori — Guida all'uso e alla prima configurazione

Autore: Salvo Cortesiano — https://netshadows.de/ombra/
Copyright (c) 2026-08-11 20:00 CEST — GPL-2.0

*This guide is also available [in English](GUIDE.en.md).*

Questa guida accompagna la prima configurazione, dall'installazione fino al primo accesso con il
codice. Leggi tutta la Parte 1 prima di attivare qualcosa: l'ordine conta ed è ciò che ti evita di
restare chiuso fuori.

---

## Parte 1 — Amministratore: prima configurazione

### Passo 1. Installa l'estensione

1. Carica la cartella in modo che si trovi in `ext/salvocortesiano/twofactor/` dentro il forum.
2. Vai in **ACP → Personalizza → Gestisci estensioni**.
3. Trova *Two Factor Authentication* e premi **Abilita**.

In questo momento vengono create le quattro tabelle. Se l'abilitazione fallisse non resta nulla di
sporco: disabiliti, risolvi la causa, riabiliti.

### Passo 2. Verifica le email — prima di contarci

Se pensi di offrire il metodo via email, prova prima la posta del forum. Esci dall'account, usa
*Ho dimenticato la password* e controlla che il messaggio arrivi davvero. Sugli hosting gratuiti
la posta in uscita è spesso la cosa che non funziona in silenzio.

**Se l'email non arriva, lascia quel metodo disattivato.** Un secondo fattore che non riesce a
consegnare il codice è una porta chiusa.

### Passo 3. Scegli i metodi

Vai in **ACP → Estensioni → Autenticazione a due fattori**.

| Impostazione | Cosa fa | Consigliato |
|---|---|---|
| App di autenticazione | Codici a sei cifre da Google Authenticator e simili | Attiva |
| Codice via email | Un codice inviato all'indirizzo dell'account | Attiva solo se il Passo 2 è andato bene |
| Durata del codice via email | Per quanto resta valido il codice inviato | 600 secondi |
| Tentativi consentiti | Errori ammessi prima di buttare via il codice | 5 |

Premi **Invia**. Per i tuoi utenti non è ancora cambiato nulla: hai solo deciso cosa *possono*
usare. Nessuno è obbligato.

### Passo 4. Provalo prima sul tuo account

Fallo prima di dirlo a chiunque altro. Segui la Parte 2 qui sotto sul tuo account e completa un
giro intero: attiva, esci, rientra con il codice. Così impari il percorso e scopri subito se
qualcosa sul tuo forum si comporta male.

### Passo 5. Metti da parte la via di fuga

Copia questo fra i tuoi appunti, sostituendo `phpbb_` con il prefisso delle tabelle del tuo forum
e `tuonomeutente` con il tuo nome utente scritto in minuscolo:

```sql
DELETE m FROM phpbb_tfa_methods m
  JOIN phpbb_users u ON u.user_id = m.user_id
  WHERE u.username_clean = 'tuonomeutente';
```

Lo esegui da phpMyAdmin solo nel caso peggiore: nessuno riesce più ad accedere. Rimuove il secondo
fattore da quell'account e non tocca nient'altro. La stessa istruzione è riportata nel pannello
dell'estensione, così la ritrovi sempre.

### Passo 6. Facoltativo: rendere la verifica obbligatoria

Fallo solo dopo aver completato il Passo 4 e aver verificato che tutto funzioni sul tuo account.

Nella sezione **Obbligo** del pannello:

1. Spunta **Rendi obbligatoria la verifica in due passaggi**.
2. In **Gruppi esclusi dall'obbligo** spunta i gruppi da lasciare fuori. Valuta di escludere i
   gruppi tecnici e chiunque non abbia un indirizzo email valido.
3. Premi **Invia**.

Da quel momento chi non l'ha configurata, al primo accesso, viene portato alla pagina di
attivazione e non può fare altro finché non sceglie un metodo. Sceglie lui se app o email, fra i
metodi che hai attivato.

Avvertenza: se togli tutti i metodi disponibili mentre l'obbligo è acceso, l'obbligo si disattiva
da solo — nessuno resta bloccato davanti a una pagina senza opzioni.

### Passo 7. Facoltativo: invitare gli utenti

Se preferisci non obbligare nessuno ma vuoi comunque farlo sapere, spunta **Invita gli utenti ad
attivarla** nella sezione dei metodi. Comparirà un avviso verde in cima a tutte le pagine, con il
collegamento alla pagina di attivazione: lo vede chi non l'ha ancora configurata, e quando la attiva il messaggio cambia in una conferma.
L'avviso non è chiudibile e riporta il nome dell'utente e quello del forum.

Le due cose convivono: puoi tenere l'invito acceso e l'obbligo spento, che è il modo più gentile di
diffondere la verifica in due passaggi.

---

## Parte 2 — Utente: come si attiva

Questi passi valgono per chiunque, te compreso.

### Con l'app di autenticazione (consigliato)

1. Installa un'app sul telefono se non ne hai già una: Google Authenticator, Microsoft
   Authenticator, Authy, Aegis, 1Password. Vanno bene tutte.
2. Sul forum vai in **Pannello di controllo → Profilo → Autenticazione a due fattori**.
3. Nell'app scegli di aggiungere un account e **inquadra il codice QR** mostrato nella pagina.
   Non riesci a inquadrarlo? Scegli l'inserimento manuale nell'app e digita la chiave scritta
   appena sotto.
4. L'app mostra ora un codice a sei cifre. Scrivilo in **Codice di conferma** e premi **Attiva**.
5. Compaiono i tuoi **codici di riserva**. Copiali subito in un posto sicuro, vedi l'avvertenza.

Il QR serve solo per questo passaggio. Da qui in avanti l'app mostra soltanto numeri.

### Con il codice via email

1. Vai in **Pannello di controllo → Profilo → Autenticazione a due fattori**.
2. Controlla che l'indirizzo mostrato sia uno che leggi davvero.
3. Premi **Attiva** nella sezione *Codice via email*.

### I codici di riserva

Otto codici, ognuno utilizzabile una volta sola, mostrati un'unica volta quando attivi il primo
metodo. Sono conservati in forma cifrata, quindi nessuno — nemmeno un amministratore — può
rimostrarteli.

Stampali, oppure salvali in un posto che non sia il forum e non sia il telefono su cui hai l'app.
Se li perdi puoi generarne di nuovi dalla stessa pagina, cosa che annulla i precedenti.

---

## Parte 3 — Come si accede da quel momento

1. Inserisci nome utente e password come sempre.
2. Compare la schermata di verifica.
3. Scrivi il codice a sei cifre dell'app.
   Usi l'email? Premi **Inviami un codice via email**, aspetta il messaggio e scrivi il codice.
   Hai perso l'accesso a entrambi? Scrivi uno dei codici di riserva nella stessa casella.
4. Sei dentro. La sessione resta verificata fino a quando esci.

Il codice cambia ogni trenta secondi. Se un codice viene rifiutato pur sembrando giusto, di solito
è l'orologio del telefono che va per conto suo: attiva data e ora automatiche e riprova.

---

## Parte 4 — Quando qualcosa va storto

**Un utente non riesce più ad accedere.** Vai in **ACP → Estensioni → Autenticazione a due
fattori**, scrivi il suo nome utente in *Sblocca un utente* e premi il pulsante. Il suo secondo
fattore viene rimosso e potrà riconfigurarlo. Prima però accertati che sia davvero chi dice di
essere: quella funzione è, per sua natura, un modo per aggirare la sua protezione.

**Un utente resta fermo sulla pagina di attivazione.** È l'obbligo che sta facendo il suo lavoro:
deve scegliere un metodo per proseguire. Se per lui non è possibile — niente telefono, niente email
funzionante — inserisci il suo gruppo fra quelli esclusi, oppure spegni l'obbligo.

**Hai acceso l'obbligo e ora nemmeno tu riesci a passare.** Metti il gruppo Amministratori fra
quelli esclusi. Se non riesci più a entrare nell'ACP, spegni l'obbligo da phpMyAdmin:

```sql
UPDATE phpbb_config SET config_value = '0' WHERE config_name = 'tfa_required';
```

**Non riesci ad accedere nemmeno tu, e non c'è un altro amministratore.** Usa l'istruzione SQL del
Passo 5 da phpMyAdmin.

**I codici vengono sempre rifiutati.** Controlla prima l'orologio del telefono. Se il telefono è
giusto, potrebbe essere l'orologio del server: l'estensione tollera già trenta secondi di scarto
in entrambe le direzioni.

**Le email non arrivano.** Guarda nello spam, poi le impostazioni di posta del forum. Nel
frattempo puoi comunque entrare con un codice di riserva.

Nel pannello dell'estensione, sezione *Se resti chiuso fuori*, trovi due pulsanti **Esegui adesso**
che fanno il lavoro al posto tuo, senza aprire phpMyAdmin: uno rimuove la verifica dal tuo account,
l'altro spegne l'obbligo per tutti. Le istruzioni SQL sono mostrate accanto, già compilate con il
prefisso delle tue tabelle e con il tuo nome utente, per quando l'ACP non fosse più raggiungibile.

---

## Parte 5 — Rimuovere l'estensione

**ACP → Gestisci estensioni → Disabilita** ferma subito il secondo fattore: tutti rientrano con la
sola password. Le impostazioni restano, così puoi riabilitarla più avanti.

**Elimina i dati** rimuove anche le quattro tabelle e ogni segreto conservato. Non è reversibile.

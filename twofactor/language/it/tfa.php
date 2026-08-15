<?php
/**
 * Two Factor Authentication - phpBB extension
 *
 * @author     Salvo Cortesiano
 * @copyright  (c) 2026-08-11 20:00 CEST Salvo Cortesiano
 * @link       https://netshadows.de/ombra/
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'UCP_TFA_SETTINGS'			=> 'Autenticazione a due fattori',
	// Schermata di verifica
	'TFA_VERIFY_TITLE'			=> 'Verifica in due passaggi',
	'TFA_VERIFY_TOTP_EXPLAIN'	=> 'Apri la tua app di autenticazione e inserisci il codice a sei cifre che mostra in questo momento.',
	'TFA_VERIFY_EMAIL_EXPLAIN'	=> 'In alternativa richiedi un codice via email con il pulsante qui sotto.',
	'TFA_CODE'					=> 'Codice',
	'TFA_SUBMIT'				=> 'Verifica',
	'TFA_SEND_EMAIL'			=> 'Inviami un codice via email',
	'TFA_EMAIL_SENT'			=> 'Ti abbiamo inviato un codice. Controlla la posta, anche nello spam.',
	'TFA_BACKUP_HINT'			=> 'Puoi usare anche uno dei tuoi codici di riserva. Ne restano:',
	'TFA_LOGOUT'				=> 'Esci e torna al forum',

	// Avviso mostrato sulle pagine del forum
	'TFA_PROMO_TEXT'			=> 'Ciao %1$s, proteggi il tuo account su %2$s: puoi attivare la verifica in due passaggi.',
	'TFA_PROMO_DONE'			=> '%1$s, hai attivato la verifica in due passaggi sul forum %2$s. Il tuo account è protetto.',
	'TFA_PROMO_LINK'			=> 'Attivala adesso',

	// Errori
	'TFA_ERR_EMPTY'				=> 'Inserisci il codice.',
	'TFA_ERR_WRONG'				=> 'Codice non valido o scaduto. Controlla che l\'orario del telefono sia corretto e riprova.',
	'TFA_ERR_METHOD_OFF'		=> 'Questo metodo non è al momento disponibile su questo forum.',
	'TFA_ERR_NO_SECRET'			=> 'La configurazione è scaduta. Ricarica la pagina e riprova con il nuovo codice QR.',
	'TFA_ERR_NO_EMAIL'			=> 'Il tuo account non ha un indirizzo email valido.',

	// Pannello utente
	'TFA_UCP_EXPLAIN'			=> 'La verifica in due passaggi aggiunge un codice usa e getta al tuo accesso: anche chi conoscesse la tua password non potrebbe entrare senza di esso.',
	'TFA_TOTP'					=> 'App di autenticazione (Google Authenticator e simili)',
	'TFA_TOTP_EXPLAIN'			=> 'Funziona con Google Authenticator, Microsoft Authenticator, Authy, Aegis, 1Password e qualunque altra app compatibile. Non serve connessione: i codici si generano sul telefono.',
	'TFA_QR'					=> 'Inquadra il codice',
	'TFA_QR_EXPLAIN'			=> 'Apri l\'app, scegli di aggiungere un account e inquadra questo codice.',
	'TFA_SECRET'				=> 'Oppure inserisci questa chiave',
	'TFA_SECRET_EXPLAIN'		=> 'Da usare se non riesci a inquadrare il codice: nell\'app scegli l\'inserimento manuale.',
	'TFA_CONFIRM'				=> 'Codice di conferma',
	'TFA_CONFIRM_EXPLAIN'		=> 'Scrivi qui le sei cifre che l\'app mostra adesso, così verifichiamo che tutto funzioni.',
	'TFA_ENABLE'				=> 'Attiva',
	'TFA_DISABLE'				=> 'Disattiva',
	'TFA_STATUS'				=> 'Stato',
	'TFA_ACTIVE'				=> 'Attiva',
	'TFA_EMAIL'					=> 'Codice via email',
	'TFA_EMAIL_EXPLAIN'			=> 'A ogni accesso puoi farti inviare un codice a questo indirizzo:',
	'TFA_BACKUP'				=> 'Codici di riserva',
	'TFA_BACKUP_EXPLAIN'		=> 'Servono se perdi il telefono o non ricevi le email. Ognuno funziona una volta sola. Conservali dove non li perdi, lontano dal forum.',
	'TFA_BACKUP_LEFT'			=> 'Codici ancora utilizzabili',
	'TFA_BACKUP_NEW_BTN'		=> 'Genera nuovi codici',
	'TFA_BACKUP_NEW'			=> 'Ecco i tuoi codici di riserva.',
	'TFA_BACKUP_NEW_EXPLAIN'	=> 'Copiali adesso: per sicurezza sono conservati in forma cifrata e non potranno più essere mostrati. Generarne di nuovi annulla i precedenti.',
));

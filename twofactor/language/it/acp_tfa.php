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
	'ACP_TFA_SETTINGS'			=> 'Impostazioni due fattori',
	'ACP_TFA_SETTINGS_EXPLAIN'	=> 'Scegli quali metodi di verifica in due passaggi mettere a disposizione. Ogni utente decide poi se usarli, dal proprio pannello di controllo.',

	'TFA_PROMO'					=> 'Invita gli utenti ad attivarla',
	'TFA_PROMO_EXPLAIN'			=> 'Mostra un avviso in cima a tutte le pagine del forum, con il nome dell\'utente e quello del forum. A chi non l\'ha ancora configurata propone il collegamento per attivarla; a chi l\'ha attivata conferma che il suo account è protetto. Non è chiudibile.',

	'TFA_POLICY'				=> 'Obbligo',
	'TFA_REQUIRED'				=> 'Rendi obbligatoria la verifica in due passaggi',
	'TFA_REQUIRED_EXPLAIN'		=> 'Chi non l\'ha ancora configurata viene portato alla pagina di attivazione e non può navigare finché non sceglie un metodo. Resta libero di scegliere quale, fra quelli che hai attivato qui sopra.',
	'TFA_EXEMPT_GROUPS'			=> 'Gruppi esclusi dall\'obbligo',
	'TFA_EXEMPT_GROUPS_EXPLAIN'	=> 'Chi appartiene a uno di questi gruppi non viene obbligato. Se però attiva la verifica di sua iniziativa, il codice gli viene comunque chiesto. Utile per gli ospiti, i bot e chi non ha un indirizzo email valido.',

	'TFA_METHODS'				=> 'Metodi disponibili',
	'TFA_ENABLE_TOTP'			=> 'App di autenticazione (Google Authenticator e simili)',
	'TFA_ENABLE_TOTP_EXPLAIN'	=> 'Codici a sei cifre generati sul telefono, senza bisogno di connessione. È il metodo più affidabile.',
	'TFA_ENABLE_EMAIL'			=> 'Codice via email',
	'TFA_ENABLE_EMAIL_EXPLAIN'	=> 'Un codice inviato all\'indirizzo dell\'account. Richiede che il forum invii correttamente le email.',

	'TFA_LIMITS'				=> 'Limiti',
	'TFA_CODE_TTL'				=> 'Durata del codice via email',
	'TFA_CODE_TTL_EXPLAIN'		=> 'Dopo quanti secondi il codice inviato smette di funzionare. Fra 60 e 3600.',
	'TFA_MAX_ATTEMPTS'			=> 'Tentativi consentiti',
	'TFA_MAX_ATTEMPTS_EXPLAIN'	=> 'Quanti errori sono ammessi su un codice email prima di doverne chiedere uno nuovo.',
	'TFA_SECONDS'				=> 'secondi',

	'TFA_STATS'					=> 'Utilizzo',
	'TFA_COUNT_TOTP'			=> 'Account con app di autenticazione',
	'TFA_COUNT_EMAIL'			=> 'Account con codice via email',

	'TFA_RESET_USER'			=> 'Sblocca un utente',
	'TFA_RESET_USER_EXPLAIN'	=> 'Rimuove ogni metodo di verifica e i codici di riserva dall\'account indicato: serve a chi ha perso il telefono e non riesce più ad accedere. L\'utente potrà poi riconfigurarlo.',
	'TFA_USERNAME'				=> 'Nome utente',
	'TFA_RESET_BTN'				=> 'Rimuovi la verifica per questo utente',
	'TFA_USER_RESET_DONE'		=> 'La verifica in due passaggi è stata rimossa dall\'account %s.',
	'TFA_ERR_NO_USERNAME'		=> 'Indica il nome utente.',
	'TFA_ERR_NO_SUCH_USER'		=> 'Nessun utente trovato con il nome %s.',

	'TFA_SETTINGS_SAVED'		=> 'Impostazioni salvate.',

	'TFA_LOCKOUT_EXPLAIN'		=> 'Da qui puoi intervenire senza passare da phpMyAdmin: i pulsanti eseguono direttamente l\'istruzione mostrata accanto, già compilata con il prefisso delle tue tabelle e con il tuo nome utente.',
	'TFA_EMERGENCY_SELF'		=> 'Rimuovi la verifica dal tuo account',
	'TFA_EMERGENCY_SELF_EXPLAIN' => 'Cancella i tuoi metodi e i tuoi codici di riserva. Utile se hai perso il telefono ma sei ancora dentro all\'ACP.',
	'TFA_EMERGENCY_SELF_BTN'	=> 'Esegui adesso',
	'TFA_EMERGENCY_REQ'			=> 'Spegni l\'obbligo per tutti',
	'TFA_EMERGENCY_REQ_EXPLAIN'	=> 'Toglie l\'obbligo senza toccare le configurazioni di chi ha già attivato la verifica.',
	'TFA_EMERGENCY_REQ_BTN'		=> 'Esegui adesso',
	'TFA_CONFIRM_RESET_SELF'	=> 'Vuoi davvero rimuovere la verifica in due passaggi dal tuo account %s?',
	'TFA_CONFIRM_DROP_REQUIRED'	=> 'Vuoi davvero disattivare l\'obbligo della verifica in due passaggi per tutti gli utenti?',
	'TFA_DONE_RESET_SELF'		=> 'La verifica in due passaggi è stata rimossa dal tuo account %s.',
	'TFA_DONE_DROP_REQUIRED'	=> 'L\'obbligo della verifica in due passaggi è stato disattivato.',
	'TFA_LOCKOUT_TITLE'			=> 'Se resti chiuso fuori',
));

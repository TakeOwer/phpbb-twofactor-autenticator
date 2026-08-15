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
	'UCP_TFA_SETTINGS'			=> 'Two factor authentication',
	// Challenge screen
	'TFA_VERIFY_TITLE'			=> 'Two step verification',
	'TFA_VERIFY_TOTP_EXPLAIN'	=> 'Open your authentication app and type the six digit code it shows right now.',
	'TFA_VERIFY_EMAIL_EXPLAIN'	=> 'Alternatively, request a code by email with the button below.',
	'TFA_CODE'					=> 'Code',
	'TFA_SUBMIT'				=> 'Verify',
	'TFA_SEND_EMAIL'			=> 'Email me a code',
	'TFA_EMAIL_SENT'			=> 'A code is on its way. Check your inbox, and the spam folder too.',
	'TFA_BACKUP_HINT'			=> 'You can also use one of your backup codes. Remaining:',
	'TFA_LOGOUT'				=> 'Sign out and go back to the board',

	// Banner shown on board pages
	'TFA_PROMO_TEXT'			=> 'Hello %1$s, protect your account on %2$s: you can turn on two step verification.',
	'TFA_PROMO_DONE'			=> '%1$s, you have turned on two step verification on %2$s. Your account is protected.',
	'TFA_PROMO_LINK'			=> 'Turn it on now',

	// Errors
	'TFA_ERR_EMPTY'				=> 'Please enter the code.',
	'TFA_ERR_WRONG'				=> 'That code is not valid or has expired. Check that your phone clock is correct and try again.',
	'TFA_ERR_METHOD_OFF'		=> 'This method is not available on this board at the moment.',
	'TFA_ERR_NO_SECRET'			=> 'The setup has expired. Reload the page and use the new QR code.',
	'TFA_ERR_NO_EMAIL'			=> 'Your account has no valid email address.',

	// User panel
	'TFA_UCP_EXPLAIN'			=> 'Two step verification adds a one time code to your sign in: even someone who knew your password could not get in without it.',
	'TFA_TOTP'					=> 'Authentication app (Google Authenticator and similar)',
	'TFA_TOTP_EXPLAIN'			=> 'Works with Google Authenticator, Microsoft Authenticator, Authy, Aegis, 1Password and any other compatible app. No connection needed: the codes are generated on the phone.',
	'TFA_QR'					=> 'Scan the code',
	'TFA_QR_EXPLAIN'			=> 'Open the app, choose to add an account and scan this code.',
	'TFA_SECRET'				=> 'Or enter this key',
	'TFA_SECRET_EXPLAIN'		=> 'Use this if you cannot scan: pick manual entry in the app.',
	'TFA_CONFIRM'				=> 'Confirmation code',
	'TFA_CONFIRM_EXPLAIN'		=> 'Type the six digits the app shows now, so we can check it all works.',
	'TFA_ENABLE'				=> 'Enable',
	'TFA_DISABLE'				=> 'Disable',
	'TFA_STATUS'				=> 'Status',
	'TFA_ACTIVE'				=> 'Active',
	'TFA_EMAIL'					=> 'Code by email',
	'TFA_EMAIL_EXPLAIN'			=> 'At each sign in you can have a code sent to this address:',
	'TFA_BACKUP'				=> 'Backup codes',
	'TFA_BACKUP_EXPLAIN'		=> 'They are for when you lose your phone or the emails do not arrive. Each one works once. Keep them somewhere safe, away from the board.',
	'TFA_BACKUP_LEFT'			=> 'Codes still usable',
	'TFA_BACKUP_NEW_BTN'		=> 'Generate new codes',
	'TFA_BACKUP_NEW'			=> 'Here are your backup codes.',
	'TFA_BACKUP_NEW_EXPLAIN'	=> 'Copy them now: they are stored hashed for safety and cannot be shown again. Generating new ones cancels the previous set.',
));

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
	'ACP_TFA_SETTINGS'			=> 'Two factor settings',
	'ACP_TFA_SETTINGS_EXPLAIN'	=> 'Choose which two step methods the board offers. Each user then decides whether to use them, from their own control panel.',

	'TFA_PROMO'					=> 'Invite users to switch it on',
	'TFA_PROMO_EXPLAIN'			=> 'Shows a banner at the top of every board page, carrying the member name and the board name. People who have not set it up get a link to turn it on; those who have get a confirmation that their account is protected. It cannot be dismissed.',

	'TFA_POLICY'				=> 'Requirement',
	'TFA_REQUIRED'				=> 'Make two step verification compulsory',
	'TFA_REQUIRED_EXPLAIN'		=> 'Anyone who has not set it up yet is taken to the setup page and cannot browse until they pick a method. They still choose which one, among those enabled above.',
	'TFA_EXEMPT_GROUPS'			=> 'Groups excused from the requirement',
	'TFA_EXEMPT_GROUPS_EXPLAIN'	=> 'Members of these groups are not forced. If they turn it on themselves they are still asked for a code. Useful for guests, bots and anyone without a working email address.',

	'TFA_METHODS'				=> 'Available methods',
	'TFA_ENABLE_TOTP'			=> 'Authentication app (Google Authenticator and similar)',
	'TFA_ENABLE_TOTP_EXPLAIN'	=> 'Six digit codes generated on the phone, no connection required. This is the more dependable method.',
	'TFA_ENABLE_EMAIL'			=> 'Code by email',
	'TFA_ENABLE_EMAIL_EXPLAIN'	=> 'A code sent to the address on the account. Requires the board to send email correctly.',

	'TFA_LIMITS'				=> 'Limits',
	'TFA_CODE_TTL'				=> 'Life of an emailed code',
	'TFA_CODE_TTL_EXPLAIN'		=> 'After how many seconds a sent code stops working. Between 60 and 3600.',
	'TFA_MAX_ATTEMPTS'			=> 'Allowed attempts',
	'TFA_MAX_ATTEMPTS_EXPLAIN'	=> 'How many wrong tries an emailed code takes before a new one must be requested.',
	'TFA_SECONDS'				=> 'seconds',

	'TFA_STATS'					=> 'Usage',
	'TFA_COUNT_TOTP'			=> 'Accounts using an authentication app',
	'TFA_COUNT_EMAIL'			=> 'Accounts using email codes',

	'TFA_RESET_USER'			=> 'Unlock a user',
	'TFA_RESET_USER_EXPLAIN'	=> 'Removes every verification method and the backup codes from the named account: this is for someone who lost their phone and can no longer sign in. They can set it up again afterwards.',
	'TFA_USERNAME'				=> 'Username',
	'TFA_RESET_BTN'				=> 'Remove verification for this user',
	'TFA_USER_RESET_DONE'		=> 'Two step verification has been removed from the account %s.',
	'TFA_ERR_NO_USERNAME'		=> 'Please enter the username.',
	'TFA_ERR_NO_SUCH_USER'		=> 'No user found with the name %s.',

	'TFA_SETTINGS_SAVED'		=> 'Settings saved.',

	'TFA_LOCKOUT_EXPLAIN'		=> 'You can act from here without going through phpMyAdmin: the buttons run the statement shown beside them, already filled in with your table prefix and your username.',
	'TFA_EMERGENCY_SELF'		=> 'Remove verification from your own account',
	'TFA_EMERGENCY_SELF_EXPLAIN' => 'Deletes your methods and your backup codes. Handy if you lost your phone but are still inside the ACP.',
	'TFA_EMERGENCY_SELF_BTN'	=> 'Run now',
	'TFA_EMERGENCY_REQ'			=> 'Switch the requirement off for everyone',
	'TFA_EMERGENCY_REQ_EXPLAIN'	=> 'Drops the requirement without touching the setup of anyone who already enabled verification.',
	'TFA_EMERGENCY_REQ_BTN'		=> 'Run now',
	'TFA_CONFIRM_RESET_SELF'	=> 'Do you really want to remove two step verification from your account %s?',
	'TFA_CONFIRM_DROP_REQUIRED'	=> 'Do you really want to switch off the two step requirement for every user?',
	'TFA_DONE_RESET_SELF'		=> 'Two step verification has been removed from your account %s.',
	'TFA_DONE_DROP_REQUIRED'	=> 'The two step requirement has been switched off.',
	'TFA_LOCKOUT_TITLE'			=> 'If you get locked out',
));

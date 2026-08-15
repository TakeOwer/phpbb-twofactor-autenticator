<?php
/**
 * Two Factor Authentication - phpBB extension
 *
 * @author     Salvo Cortesiano
 * @copyright  (c) 2026-08-11 20:00 CEST Salvo Cortesiano
 * @link       https://netshadows.de/ombra/
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace salvocortesiano\twofactor\core;

/**
 * Time based one time passwords, RFC 6238.
 *
 * This is what Google Authenticator, Microsoft Authenticator, Authy, Aegis and
 * every other compatible app implement: no vendor API, no external service.
 * Verified against the reference vectors published in the RFC.
 */
class totp
{
	/** Seconds each code stays valid */
	const STEP = 30;

	/** Digits shown by the app */
	const DIGITS = 6;

	/** Base32 alphabet, RFC 4648 */
	const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * A fresh random secret, in the Base32 form the apps expect.
	 *
	 * @param int $length characters, 32 gives 160 bits
	 * @return string
	 */
	public static function create_secret($length = 32)
	{
		$secret = '';
		$alphabet = self::ALPHABET;

		for ($i = 0; $i < $length; $i++)
		{
			$secret .= $alphabet[random_int(0, 31)];
		}

		return $secret;
	}

	/**
	 * Base32 -> raw bytes.
	 *
	 * @return string empty when the input is not valid Base32
	 */
	public static function base32_decode($secret)
	{
		$secret = strtoupper(str_replace(array(' ', '-', '='), '', (string) $secret));

		if ($secret === '' || preg_replace('/[^A-Z2-7]/', '', $secret) !== $secret)
		{
			return '';
		}

		$bits = '';

		for ($i = 0, $len = strlen($secret); $i < $len; $i++)
		{
			$bits .= str_pad(decbin(strpos(self::ALPHABET, $secret[$i])), 5, '0', STR_PAD_LEFT);
		}

		$out = '';

		foreach (str_split($bits, 8) as $chunk)
		{
			if (strlen($chunk) === 8)
			{
				$out .= chr(bindec($chunk));
			}
		}

		return $out;
	}

	/**
	 * The code for a given moment.
	 *
	 * @param string $secret Base32 secret
	 * @param int    $timestamp UNIX time, current time when 0
	 * @return string six digits, empty on a bad secret
	 */
	public static function code($secret, $timestamp = 0)
	{
		$key = self::base32_decode($secret);

		if ($key === '')
		{
			return '';
		}

		$counter = (int) floor(($timestamp ?: time()) / self::STEP);

		// 64 bit big endian counter, built by hand so it also works on 32 bit PHP
		$binary = '';

		for ($i = 7; $i >= 0; $i--)
		{
			$binary .= chr(($counter >> ($i * 8)) & 0xFF);
		}

		$hash   = hash_hmac('sha1', $binary, $key, true);
		$offset = ord(substr($hash, -1)) & 0x0F;

		$value = ((ord($hash[$offset]) & 0x7F) << 24)
			| ((ord($hash[$offset + 1]) & 0xFF) << 16)
			| ((ord($hash[$offset + 2]) & 0xFF) << 8)
			| (ord($hash[$offset + 3]) & 0xFF);

		return str_pad((string) ($value % pow(10, self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
	}

	/**
	 * Check a code the user typed.
	 *
	 * A small window either side absorbs clock drift between the phone and the
	 * server, which is the usual reason a correct code gets refused.
	 *
	 * @param string $secret Base32 secret
	 * @param string $code   what the user typed
	 * @param int    $window how many steps of tolerance, 1 = 30 seconds
	 * @return bool
	 */
	public static function verify($secret, $code, $window = 1)
	{
		$code = preg_replace('/\D/', '', (string) $code);

		if (strlen($code) !== self::DIGITS)
		{
			return false;
		}

		$now = time();

		for ($i = -$window; $i <= $window; $i++)
		{
			$candidate = self::code($secret, $now + ($i * self::STEP));

			// Constant time comparison: a plain === leaks timing information
			if ($candidate !== '' && hash_equals($candidate, $code))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * The otpauth:// address the QR code encodes.
	 */
	public static function uri($secret, $account, $issuer)
	{
		$label = rawurlencode($issuer) . ':' . rawurlencode($account);

		return 'otpauth://totp/' . $label
			. '?secret=' . rawurlencode($secret)
			. '&issuer=' . rawurlencode($issuer)
			. '&algorithm=SHA1'
			. '&digits=' . self::DIGITS
			. '&period=' . self::STEP;
	}

	/**
	 * Secret split into groups of four, easier to type by hand.
	 */
	public static function readable($secret)
	{
		return trim(chunk_split((string) $secret, 4, ' '));
	}
}

# Two Factor Authentication

Two step verification for phpBB 3.3: authentication apps (Google Authenticator and similar),
email codes and single use backup codes.

*Leggi questo file in [italiano](README.md).*

| | |
|---|---|
| **Author** | Salvo Cortesiano |
| **Copyright** | (c) 2026-08-11 20:00 CEST Salvo Cortesiano |
| **Forum** | https://netshadows.de/ombra/ |
| **License** | GNU General Public License, version 2 (GPL-2.0) |
| **Version** | 1.3.0 |


**Step by step:** [Setup and usage guide](docs/GUIDE.en.md)

## Installation

1. Copy the folder to `ext/salvocortesiano/twofactor/`
2. ACP → **Customise** → **Manage extensions** → **Enable**
3. ACP → **Extensions** → **Two factor authentication** to choose the methods

Requirements: phpBB 3.3.0+ and PHP 7.1.3+.

## How it works

The administrator decides **which methods the board offers**; each user then chooses whether to
use them, from User Control Panel → Profile → Two factor authentication.

- **Authentication app (TOTP)** — six digit codes generated on the phone, no connection needed.
  Works with Google Authenticator, Microsoft Authenticator, Authy, Aegis, 1Password and any other
  app following RFC 6238.
- **Email code** — a code sent to the address on the account, with a configurable lifetime and
  attempt limit.
- **Backup codes** — eight single use codes created when the first method is enabled, stored only
  as hashes and shown once.

A user with a method enabled is taken to the challenge screen after signing in and cannot browse
until a valid code is entered. The session stays verified until logout.

### Requirement and excused groups

With **Make two step verification compulsory**, anyone who has not set it up is taken to the setup
page and cannot browse until they pick a method: they still choose *which* one, among those you
enabled.

**Groups excused from the requirement** lists every group on the board — administrators, global
moderators, your own groups — each with a checkbox. Members of a ticked group are not forced; if
they enable it themselves they are still asked for a code, since that was their own choice.

### Inviting users

The **Invite users to switch it on** checkbox, below the available methods, shows a green banner at
the top of every board page with a direct link to the setup page. Only people who have not set
anything up see it: as soon as they enable a method the banner disappears by itself. The wording carries the member name and the board name. Once the user turns verification on the
banner does not vanish but changes: it goes blue and confirms their account is protected.
It cannot be dismissed.

## The QR code

It is drawn **by the browser**, locally: the secret never goes through an online QR service, which
would otherwise learn it and defeat the whole point of a second factor. Anyone unable to scan can
type the key by hand, using manual entry in the app.

## If you get locked out

Three ways out, easiest first:

1. **A backup code** instead of the usual one, in the same box.
2. **Unlock from the ACP** — another administrator opens the extension panel, types the username
   under *Unlock a user* and removes verification from that account.
3. **From the database**, if no administrator can sign in any more. In phpMyAdmin:

   ```sql
   DELETE m FROM phpbb_tfa_methods m
     JOIN phpbb_users u ON u.user_id = m.user_id
     WHERE u.username_clean = 'yourusername';
   ```

   Replace the `phpbb_` prefix with your board's own and the username with yours, in lower case.

If you made verification compulsory and are blocked before you can even reach the panel, switch
the requirement off from phpMyAdmin:

```sql
UPDATE phpbb_config SET config_value = '0' WHERE config_name = 'tfa_required';
```

In the extension panel, under *If you get locked out*, there are two **Run now** buttons that do
the work for you without opening phpMyAdmin: one removes verification from your own account, the
other switches the requirement off for everyone. The SQL statements are shown beside them, already
filled in with your table prefix and your username, for when the ACP is no longer reachable.

## Before enabling the email method

Check that the board really sends email, for instance by trying a password reset. If mail does not
go out, that method becomes a wall: in that case leave only the app enabled.

## Technical notes

The TOTP algorithm and the QR encoder are written from scratch, with no external libraries and no
calls to third party services. TOTP was verified against the test vectors published in RFC 6238;
the QR encoder was verified by decoding the codes it generates.

Email codes and backup codes are kept as SHA-256 hashes, never in clear text, and compared in
constant time so nothing exploitable leaks through timing.

## Tables created

`tfa_methods`, `tfa_backup`, `tfa_sessions`, `tfa_email`, with your board's prefix.
They are removed by choosing *Delete data* in Manage extensions.

## License

GNU General Public License, version 2 (GPL-2.0)

Copyright (c) 2026-08-11 20:00 CEST Salvo Cortesiano — https://netshadows.de/ombra/

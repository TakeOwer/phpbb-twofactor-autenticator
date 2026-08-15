# phpbb-twofactor-autenticator
Two step verification for phpBB, without leaning on anyone else's servers. Someone signs in with the right password and still has to prove it is them, with a six digit code from an authenticator app or one sent to their inbox.

Two step verification for phpBB, without leaning on anyone else's servers. Someone signs in with the
right password and still has to prove it is them, with a six digit code from an authenticator app or
one sent to their inbox. The administrator picks which methods the board offers, each member picks
which one to use, and nobody is locked out when a phone goes missing. Codes, secrets and QR
rendering all stay on your own hosting.

![phpBB 3.3+](https://img.shields.io/badge/phpBB-3.3%2B-blue)
![PHP 7.1+](https://img.shields.io/badge/PHP-7.1%2B-8892bf)
![licence GPL-2.0-only](https://img.shields.io/badge/licence-GPL--2.0--only-green)

---

## Contents

- [At a glance](#at-a-glance)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Features](#features)
- [How it works](#how-it-works)
- [Security](#security)
- [Transparency towards users](#transparency-towards-users)
- [If you get locked out](#if-you-get-locked-out)
- [Troubleshooting](#troubleshooting)
- [Known limitations](#known-limitations)
- [Translations](#translations)
- [Licence and attribution](#licence-and-attribution)

---

## At a glance

| | |
|---|---|
| **Methods** | Authenticator app (TOTP), email code, single use backup codes |
| **Who decides** | The administrator enables the methods, each member chooses among them |
| **Optional or compulsory** | Both: leave it voluntary, or require it and excuse chosen groups |
| **External services** | None. No API keys, no third party calls, no accounts to open |
| **Dependencies** | None. No Composer packages, no bundled libraries |
| **Languages** | English and Italian, complete for every screen and email |

---

## Requirements

- phpBB **3.3.0** or later
- PHP **7.1.3** or later, with `hash_hmac`, `random_int` and `hash_equals` (all standard)
- Working outgoing mail, **only** if you intend to offer the email method

The extension refuses to enable itself when these are missing, rather than half installing.

---

## Installation

1. Copy the folder into your board so it sits at `ext/salvocortesiano/twofactor/`
2. **ACP → Customise → Manage extensions**
3. Find *Two Factor Authentication* and press **Enable**

Four tables are created at this point, all carrying your own table prefix. Nothing else on the board
is modified.

To remove it: **Disable** stops verification immediately and keeps the settings; **Delete data**
additionally drops the tables and every stored secret.

---

## Configuration

Everything lives in **ACP → Extensions → Two factor authentication**.

| Setting | What it does |
|---|---|
| Authentication app | Offers TOTP codes from Google Authenticator, Authy, Aegis, 1Password and similar |
| Code by email | Offers a code sent to the address on the account |
| Invite users to switch it on | Shows a banner on every board page, with the member and board name |
| Make verification compulsory | Sends anyone without a method to the setup page until they pick one |
| Groups excused | Every board group listed with a checkbox; members of ticked groups are never forced |
| Life of an emailed code | 60 to 3600 seconds, default 600 |
| Allowed attempts | Wrong tries an emailed code survives before being discarded, default 5 |
| Unlock a user | Removes verification from a named account, for people who lost their phone |

The panel also reports how many accounts use each method, so you can see adoption without querying
the database.

---

## Features

**Authenticator app (TOTP).** Six digit codes generated on the phone with no connection. Any app
following RFC 6238 works; there is nothing Google specific about it despite the name most people
know it by.

**Email codes.** A code sent to the address on the account, with a lifetime and an attempt limit you
control. Stored hashed, invalidated on first use.

**Backup codes.** Eight single use codes, created when the first method is enabled and shown once.
Members can regenerate them at any time, which cancels the previous set.

**QR enrolment, drawn locally.** The setup page renders the QR in the browser from a QR encoder
written for this extension. The secret is never handed to an online QR generator, which would
otherwise know how to produce your members' codes. Anyone who cannot scan can type the key instead.

**Optional or compulsory, per group.** Leave it entirely voluntary, or require it while excusing
chosen groups. Being excused means not being forced; a member who enables it anyway is still asked
for a code, because that was their own decision.

**Invitation banner.** A notice at the top of every page inviting members to switch it on, carrying
their name and the board name. Once they do, it changes into a confirmation that their account is
protected.

**Rescue built in.** An administrator can clear verification for any account from the panel, and two
one click buttons handle the emergencies: remove verification from your own account, or switch the
requirement off for everyone. Both show the equivalent SQL, already filled in with your table prefix
and your username, for the case where the ACP itself is out of reach.

---

## How it works

A member who has enabled a method is taken to a verification screen after signing in and cannot
browse until a valid code is entered. The session stays verified until they sign out.

Codes are anchored to the server clock, with thirty seconds of tolerance either side, so a phone
running slightly fast or slow still works. The verification screen accepts a TOTP code, an emailed
code or a backup code in the same box, so nobody has to pick the right field under pressure.

Signing in and signing out stay reachable at all times, and the setup page is exempt from the
requirement, so a compulsory policy cannot trap anyone in a redirect loop.

---

## Security

- Email codes and backup codes are stored as **SHA-256 hashes**, never in clear text
- Every comparison uses **constant time** functions, so timing gives nothing away
- TOTP secrets are generated with a **cryptographically secure** random source
- Emailed codes carry an **expiry and an attempt limit**, so guessing six digits is not viable
- Backup codes are **single use** and marked spent the moment they work
- Nothing leaves your server: **no external service** ever sees a secret, a code or a QR

The TOTP implementation was verified against the reference test vectors published in RFC 6238. The
QR encoder was verified by decoding the codes it produces and comparing the module matrix against an
independent reference implementation.

---

## Transparency towards users

Members are never surprised by this. They see what is being asked and why:

- the invitation banner explains the point before anything changes
- enabling requires confirming a working code, so no one ends up with a broken setup
- backup codes are shown at that moment, with a plain warning that they cannot be shown again
- the setup page can be revisited any time to disable a method or regenerate codes

---

## If you get locked out

Three ways out, easiest first.

**1. A backup code.** Type it in the same box as the usual code.

**2. From the ACP.** Another administrator opens the extension panel, types the username under
*Unlock a user*, and presses the button. Confirm the person is who they claim to be first: this is,
by design, a way past their protection.

**3. From the database**, when no administrator can sign in at all. In phpMyAdmin, replacing the
prefix and username with your own:

```sql
DELETE m FROM phpbb_tfa_methods m
  JOIN phpbb_users u ON u.user_id = m.user_id
  WHERE u.username_clean = 'yourusername';
```

If you made verification compulsory and that is what blocks you:

```sql
UPDATE phpbb_config SET config_value = '0' WHERE config_name = 'tfa_required';
```

Both statements are shown inside the panel too, already filled in with your own prefix and username.

---

## Troubleshooting

**Codes are always refused.** Check the phone clock first, and turn on automatic date and time. The
extension already tolerates thirty seconds of drift in either direction.

**Emails never arrive.** Test the board's mail with a password reset. Until it works, leave the email
method switched off: a second factor that cannot deliver is a locked door. Members can still sign in
with a backup code.

**A member is stuck on the setup page.** That is the requirement doing its job. If they genuinely
cannot comply, no phone and no working email, add their group to the excused ones.

**An administrator still gets asked for a code although their group is excused.** Expected: being
excused removes the obligation, not the protection they chose for themselves. To stop being asked,
disable the method from the user control panel.

---

## Known limitations

- **Hardware keys are not supported.** U2F is obsolete and WebAuthn is not implemented yet; the
  architecture keeps methods separate, so it can be added later without disturbing what exists.
- **Verification is per session**, not per sensitive action. There is no second prompt before
  entering the ACP once a session is verified.
- **QR versions 1 to 20** are supported by the bundled encoder, which is far beyond what an
  `otpauth://` address needs, but not the full specification.
- **No self service reset by email.** Recovering an account with no backup codes left needs an
  administrator, which is deliberate: an email based reset would weaken the second factor to the
  strength of the mailbox.

---

## Translations

English and Italian ship complete: every screen, every error, the ACP, the user panel and the email
sent with the code. Both files carry the same keys, checked automatically so nothing falls back to a
raw placeholder in either language.

Adding a language means copying `language/en/` to your own code and translating the values. phpBB
serves each member the wording of their own language, and falls back to English for anything not
provided.

---

## Licence and attribution

Released under the **GNU General Public License, version 2 (GPL-2.0-only)**.

Copyright © 2026 Salvo Cortesiano — [netshadows.de/ombra](https://netshadows.de/ombra/)

The TOTP algorithm follows RFC 6238 and the QR encoder follows ISO/IEC 18004; both are implemented
from the specifications, with no third party code included.

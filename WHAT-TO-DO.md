# A4S security fix: what to do

Do Part 1 first. It protects the live site, even though old passwords will stay in your GitHub history.
You only need this file for yourself. Don't upload it to GitHub.

## Part 1: The live site (a4s.site.je)

**1. Change your InfinityFree password**
Client Area > Accounts > your account > change the hosting account password.
On InfinityFree this is also your MySQL password. Check the new one under MySQL Databases.
Until you do this, anyone who read the old db.php on GitHub can get into your database and hosting.

**2. Upload the fixed files with File Manager** (into `htdocs`, replacing the old ones)
- `db.php`
- `login.php`
- `admin/add_admin.php` (goes inside the `admin` folder)
- `config.example.php`

Then make a copy of `config.example.php`, name it `config.php`, and fill in your MySQL host, username,
**new** password and database name. Open a4s.site.je to check the store loads.
If you see "Sorry, the store is unavailable right now", one of the details in config.php is wrong.

Don't upload the `.sql` file or the `tools` folder to the server. You don't need them there.

**3. Change the admin passwords**
Control panel > phpMyAdmin > your database > `admin` table:
- Delete test accounts you don't need, such as `Yan` and `Yan20`.
- For each account you keep, click Edit, type a new long password (12 or more characters) into `password`, and click Go.
  If an account belongs to a teammate, agree the new password with them.
- Log in once at a4s.site.je/login.php with each new password. The new login page turns it into a secure hash.
  In phpMyAdmin the password should now start with `$2y$`.

The old leaked admin passwords keep working until you change them in this step.

## Part 2: GitHub (github.com/dvnialzi/A4s)

**4. Upload the fixed files**
Add file > Upload files, then drag in everything from this folder except `WHAT-TO-DO.md`.
Dragging the folders keeps `admin/` and `tools/` in the right place.
`.gitignore` is a hidden file. On a Mac, press Cmd + Shift + . in Finder to see it.
Commit message: `Remove passwords and personal data, hash admin passwords`

**5. Never upload `config.php` to GitHub.** `.gitignore` now stops Git from adding it,
but GitHub's upload page will still take it if you drag it in.

GitHub keeps older versions of every file, so the old passwords can still be found in the history.
That's why Part 1 matters: once they're changed, the old ones are useless.

## What changed

| File | Change |
|---|---|
| `db.php` | No passwords in it. Reads them from `config.php`, and shows visitors a plain error instead of server details. |
| `config.example.php` | New. A template for `config.php`. |
| `.gitignore` | New. Keeps `config.php` off GitHub. |
| `login.php` | Admin passwords are checked as hashes. An old plain-text password works once, then is replaced with a hash. A new session starts on every login. |
| `admin/add_admin.php` | New admins are saved with a hashed password of at least 8 characters. |
| `dbshoes (3).sql` | Admin accounts, customers, addresses, orders, messages and refunds removed. Products, sizes, colour variants, coupons and settings kept. |
| `tools/create_admin.php` | New. Creates the first admin from the command line after a fresh import. |
| Setup guide | Adds steps for creating an admin and putting the site online. |

## Still open

`forgot_password.php` lets anyone who knows a customer's email set a new password for that account,
because it never checks that the person owns the email. Fixing it means either emailing a reset link
or turning self-service reset off and sending people to Support.

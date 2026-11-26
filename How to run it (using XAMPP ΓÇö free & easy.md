How to run it (using XAMPP — free & easy)
Step 1 — Install XAMPP
Download from https://www.apachefriends.org and install it.

Step 2 — Copy your project
Copy the entire a4s_final folder into:


C:\xampp\htdocs\a4s_final
Step 3 — Start XAMPP
Open XAMPP Control Panel → click Start on Apache and MySQL

Step 4 — Import the database

Open your browser → go to http://localhost/phpmyadmin
Click New → create a database named dbshoes
Click on dbshoes → go to Import tab
Choose the file dbshoes (3).sql from your project folder → click Go

Step 5 — Create your admin account
The database comes with no admin accounts, so nobody can log in with a password from GitHub.
Open a terminal in the a4s_final folder and run:


C:\xampp\php\php.exe tools\create_admin.php "yourname" "you@example.com" "a-long-password"

Then log in at http://localhost/a4s_final/login.php with that email and password.

Step 6 — Open the website
In your browser go to:


http://localhost/a4s_final/

Putting it online (InfinityFree or any PHP host)
1. Upload the project files to htdocs. Don't upload the .sql file.
2. Create a MySQL database in the hosting control panel and import dbshoes (3).sql with phpMyAdmin.
3. Copy config.example.php to config.php on the server and fill in the database details from the control panel.
   config.php is in .gitignore, so the real password never goes to GitHub. Never put it in db.php.
4. Create an admin account: in phpMyAdmin, add a row to the admin table with a temporary password,
   then log in once. The login page replaces the password with a secure hash automatically.

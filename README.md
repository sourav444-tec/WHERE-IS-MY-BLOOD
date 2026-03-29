# WHERE-IS-MY-BLOOD

Simple Blood Bank Management System built with PHP + HTML + CSS.

## Main Options

- Admin
- User
- Contact
- Location

## Features

- Admin login and logout
- Admin dashboard to:
  - update blood inventory by blood group
  - add donor records
  - view blood requests submitted by users
- User portal to:
  - check available blood units
  - submit blood requests
- Contact page with helpline details
- Location page with blood bank center details

## Tech Stack

- PHP (no external framework)
- HTML/CSS
- JSON file storage (`data/storage.json`)

## Default Admin Credentials

- Username: `admin`
- Password: `admin123`

## Run Locally (XAMPP)

1. Place project in:
   - `c:\xampp\htdocs\WHERE-IS-MY-BLOOD`
2. Start Apache from XAMPP Control Panel.
3. Open in browser:
   - `http://localhost/WHERE-IS-MY-BLOOD/`

## Important Files

- `index.php` - home page with 4 main options
- `admin/login.php` - admin login
- `admin/dashboard.php` - admin management panel
- `user/user.php` - user request page
- `contact.php` - contact details
- `location.php` - blood bank locations

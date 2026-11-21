# Personal Expense Tracker

A lightweight PHP and MySQL web application for recording income and expenses, managing monthly budgets, viewing category-wise charts, filtering transactions and exporting CSV reports.

## Requirements
- XAMPP (Apache + MySQL)
- PHP 8.x
- Visual Studio Code
- A modern web browser
- Internet connection for the Chart.js and Google Fonts CDN assets

## Run locally
1. Install and open XAMPP.
2. Start **Apache** and **MySQL**.
3. Copy the `personal-expense-tracker` folder into `C:\xampp\htdocs\`.
4. Open `http://localhost/phpmyadmin`.
5. Select **Import**, choose the `database.sql` file from this project, and click **Import**.
6. Open `config/db.php`. The default XAMPP settings are `localhost`, user `root`, and an empty password. Change them if your MySQL setup differs.
7. Visit `http://localhost/personal-expense-tracker/`.
8. Register an account and start adding transactions.

## Main features
- User registration, login and logout
- Add, edit, delete and view income/expense transactions
- Default personal categories
- Monthly budget and spending warnings
- Monthly dashboard with category-wise doughnut chart
- Search and filter transactions
- CSV export

## Project folders
- `assets/css/` — page styling
- `assets/js/` — chart and browser-side behaviour
- `config/` — database connection
- `includes/` — shared functions, authentication and layout
- `actions/` — form processing
- `database.sql` — MySQL database and table structure

## Hosting note
This project uses PHP and MySQL. It cannot be deployed as a PHP application directly to GitHub Pages or Netlify static hosting. Use a PHP + MySQL hosting provider for a public live link, then update `config/db.php` with the hosting database credentials. Never upload real passwords or private database credentials to a public Git repository.

## Academic scope
This is a compact academic implementation of the modules described in the project synopsis. The optional administrator panel and custom category-management screen are not included in this starter version.

# PersonalHUB

<p align="center">
  <strong>A personal portfolio and content-management platform</strong><br>
  A lightweight PHP backend paired with a responsive, interactive frontend.
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white">
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white">
  <img alt="Deployment" src="https://img.shields.io/badge/Deploy-InfinityFree-blue">
</p>

PersonalHUB is an open-source personal portfolio project designed to present a developer's profile, skills, projects, media, and journey through a clean public website. It also includes a private administration area for managing portfolio content without editing source files for every update.

The repository separates the public-facing website from its PHP application layer, making the project easier to maintain, extend, and deploy to shared hosting.

> **Project status:** Personal project / open-source starter. Features and deployment compatibility may evolve. Review the configuration and security checklist before deploying it to production.

## ✨ Features

### Public portfolio
- Responsive portfolio interface for desktop and mobile.
- Profile, biography, interests, goals, and skills presentation.
- Project showcase with categories, descriptions, technology labels, images, and video fields.
- Journey, media, and social-link content areas.
- Contact form backed by a server-side API.
- Dynamic content loading from the backend, with static JSON data available for frontend content.
- SEO-oriented metadata, sitemap, robots file, and custom 404 page.
- UI interactions, navigation, theme support, and animations.

### Administration and backend
- PHP backend with a MySQL database using PDO.
- Admin dashboard for profile, projects, skills, media, journey, contact messages, and site settings.
- Admin authentication with an email OTP second step.
- Session and request security helpers, CSRF protection, input validation, and rate limiting.
- Audit logging for selected administrative and authentication events.
- Public API endpoints for content, contact messages, and visit tracking.
- Optional integrations configured through environment variables (Brevo, ImgBB, and TikTok-related validation/provider functionality).

### Deployment
- GitHub Actions workflow for FTP deployment to InfinityFree.
- Separate deployment targets for the static public site and PHP application.
- Server-side environment file and uploaded media are excluded from automated deployment synchronization.

## 🧱 Technology

| Layer | Technology |
|---|---|
| Public interface | HTML, CSS, JavaScript |
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ / compatible MariaDB |
| Database access | PDO |
| Web server | Apache with `.htaccess` support |
| Hosting target | InfinityFree or compatible PHP shared hosting |
| CI/CD | GitHub Actions + FTP |

The codebase is designed for PHP 7.4 or newer. The hosting provider must also enable the PHP extensions required by the enabled features, including PDO MySQL, OpenSSL, and JSON. Some optional integrations may require additional PHP extensions or outbound network access.

## 📁 Repository structure

```text
PersonalHUB/
├── .github/
│   └── workflows/
│       └── deploy-infinityfree.yml  # Automated FTP deployment
├── public/                          # Public website document root
│   ├── assets/
│   ├── css/
│   ├── data/
│   ├── js/
│   ├── index.html
│   └── .htaccess
├── app/                             # PHP application directory
│   ├── admin/                       # Private admin interface
│   ├── api/
│   │   ├── admin/
│   │   └── public/
│   ├── assets/uploads/              # User-uploaded media (runtime data)
│   ├── config/
│   │   ├── .env                     # Local/server secrets (not committed)
│   │   └── .env.example             # Safe configuration template
│   ├── database/                    # Schema, seed, and upgrade SQL
│   ├── data/
│   └── includes/                    # Bootstrap, auth, security, providers
├── .gitignore
├── DEPLOYMENT.md
└── README.md
```

`public/` and `app/` are deployed to separate directories. The examples below assume the domain's public document root is `htdocs/` and the PHP application is placed at `htdocs/app/`. Adjust the FTP paths if your InfinityFree account exposes a different directory layout.

## 🚀 Getting started

### 1. Requirements

- PHP 7.4 or newer.
- MySQL 5.7+ or compatible MariaDB.
- Apache hosting with `.htaccess` support and URL rewriting where needed.
- PHP extensions: `pdo_mysql`, `openssl`, `json`; `mbstring` is recommended/required by string validation helpers.
- Git (optional, for cloning and contributing).

### 2. Clone the repository

```bash
git clone https://github.com/<your-username>/PersonalHUB.git
cd PersonalHUB
```

Replace the repository URL with your own GitHub repository URL.

### 3. Configure environment variables

Create your private environment file from the example:

```bash
cd app
cp config/.env.example config/.env
```

On Windows, copy the file manually if `cp` is unavailable.

Edit `app/config/.env` and provide the values for your environment. At minimum, configure:

- `APP_KEY` — a long, random secret used by the application for cryptographic operations.
- `SETUP_KEY` — a private setup key; do not reuse a public or easily guessed value.
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` — database connection details.
- `APP_PUBLIC_URL`, `PORTFOLIO_URL`, `CORS_ALLOWED_ORIGINS` — correct URLs/origins for your deployment.
- Email and media provider credentials only if you enable those integrations.

Generate strong independent secrets. For example, use a cryptographically secure random generator to create `APP_KEY` and `SETUP_KEY`. Never commit the resulting `config/.env` file.

### 4. Create and initialize the database

1. Create a database and database user through your hosting control panel.
2. Import `app/database/schema.sql` into the new, empty database using phpMyAdmin.
3. Import `app/database/seed.sql` to insert the starter portfolio content.
4. Configure the database credentials in `app/config/.env`.
5. Open the site's setup page only when you are ready to initialize the administrator account. Follow the setup flow and use a strong unique password.

The schema and seed files are intended for MySQL-compatible databases. Review the SQL upgrade files before applying them to an existing installation. Back up the database before running any migration.

### 5. Configure hosting directories

Recommended layout:

```text
~/personalhub.my.id/
└── htdocs/
    ├── index.html              # Contents of repository /public
    ├── assets/
    ├── css/
    ├── js/
    ├── app/                    # Contents of repository /app
    │   ├── admin/
    │   ├── api/
    │   ├── config/
    │   │   └── .env            # Created manually on the server
    │   ├── database/
    │   └── includes/
    └── ...
```

The exact FTP root can differ between hosting accounts. In InfinityFree, the website document-root directory is commonly named `htdocs`. Use the directory shown by your FTP account; do not create a second nested `htdocs` directory by mistake.

Keep `app/config/.env` on the server. The `app/config/.htaccess` rule denies direct web access to that directory. Do not remove that protection.

### 6. Open the website

After uploading the files and configuring the database, visit your domain. The public website is served from the document root, while the backend endpoints are available under `/app/` (for example, `/app/api/public/`).

For a more detailed InfinityFree and GitHub Actions setup, see **[DEPLOYMENT.md](DEPLOYMENT.md)**.

## 🔐 Environment and secret management

The following rules are intentional:

- `app/config/.env` is private and must never be committed.
- `app/config/.env.example` contains variable names only; it is safe to commit.
- `.env`, API tokens, database passwords, private keys, and production credentials must be stored in local environment files or hosting/GitHub secret settings.
- GitHub Actions receives FTP credentials through repository **Secrets** and deployment paths through repository **Variables**.
- Upload directories are runtime data. Back them up separately and do not overwrite them during normal code deployment.

If a secret has ever been committed or shared publicly, deleting it from the latest commit is not sufficient. Rotate/revoke the exposed credential and review repository history.

## 🛠️ Development workflow

A typical contribution workflow:

```bash
git checkout -b feature/your-change
# Make and test your changes
git status
git add .
git commit -m "feat: describe your change"
git push -u origin feature/your-change
```

Open a Pull Request against `main` with:
- A clear description of the change.
- Screenshots for visible UI changes.
- Database migration notes when the schema changes.
- A note about new environment variables or hosting requirements.
- Confirmation that no credentials, personal data, or uploaded production files are included.

Before committing, verify ignored files:

```bash
git status --short
git check-ignore -v app/config/.env
```

## 🧪 Testing checklist

Before deploying, verify:

- [ ] Public pages render on desktop and mobile.
- [ ] Backend can connect to the configured database.
- [ ] Admin setup/login and email OTP work.
- [ ] Public content API returns the expected data.
- [ ] Contact form validates and stores messages.
- [ ] Uploads work and remain available after a code deployment.
- [ ] `.env` is not tracked by Git and cannot be downloaded over HTTP.
- [ ] Admin and setup routes are protected appropriately.
- [ ] No production credentials or personal data are present in the commit.

This repository does not currently define an automated test suite. Manual verification is therefore important after changes to authentication, database queries, API behavior, or deployment configuration.

## 🤝 Contributing

Contributions, bug reports, documentation improvements, and feature ideas are welcome.

1. Fork the repository.
2. Create a focused feature branch.
3. Keep changes small and explain their purpose.
4. Test locally before opening a Pull Request.
5. Never include secrets, production database exports, or private uploads.

For substantial changes, open an issue first to discuss the proposed approach.

## 🗺️ Roadmap

Potential areas for future improvement:

- Automated PHP linting and regression tests in GitHub Actions.
- More granular admin permissions and security review.
- Database migration/version management.
- Improved accessibility and performance audits.
- Developer documentation for API endpoints and database entities.
- Additional themes and portfolio presentation options.

## 📄 License

PersonalHUB is released under the **MIT License**. You are free to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the software, provided that the copyright and permission notice are included in substantial portions of the software. See the [`LICENSE`](LICENSE) file for the complete license text.

## 🙌 Acknowledgements

Built as a personal learning and portfolio project. Thanks to the open-source community and the developers whose tools and documentation make projects like this possible.

# Sae_Manager_G4
# 🧩 SAE Manager

A web application for **tracking SAEs** (Situations d’Apprentissage et d’Évaluation — Project-Based Learning) in the Computer Science department.

---

## 🎯 Purpose

- **Streamline SAE tracking and organization** for students, teachers and clients
- **Automate notifications and reminders** for deadlines, deliverables, and oral defenses
- Simplify the **management of evaluations and competencies** with a **centralized platform**
- Provide a user-friendly interface for managing projects, schedules, and student progress

---

## 🌐 Deployment

🔗 **Live site:** [https://sae-manager.alwaysdata.net](https://sae-manager.alwaysdata.net/)  
📁 **Hosted on:** AlwaysData  
💻 **Main language:** PHP (custom MVC framework)

---

## 🐳 Docker (recommended for local development)

This project can be run locally using Docker (PHP/Apache + MySQL + phpMyAdmin).

### Prerequisites
- Docker + Docker Compose (v2)

### Setup
1. Clone the repository
2. Create your environment file:
   ```bash
   cp .env.dist .env
   ```
3. Add the shared SQL dump (provided separately) here:
   ```bash
   docker/init-db/01-init.sql
   ```
4. Start the containers:
   ```bash
   docker compose up --build -d
   ```

### Access
- App: http://localhost:8080
- phpMyAdmin: http://localhost:8081

### Reset database (re-import the SQL dump)
> MySQL init scripts are executed only on the first initialization of the database volume.
```bash
docker compose down -v
docker compose up --build -d
```

---

## 🗂️ Project Structure

```bash
.
├── _assets/
│   ├── css/              # Stylesheets
│   ├── docs/             # Legal documents (PDF)
│   ├── img/              # Images and favicon
│   └── script/           # JavaScript scripts
├── doc/                  # Generated PHPDoc documentation
├── src/
│   ├── Controllers/      # Business logic (controllers)
│   ├── Models/           # Data access and business objects
│   ├── Shared/           # Exceptions and utilities
│   └── Views/            # HTML pages and PHP views
├── tests/                # Unit and integration tests
├── index.php             # Application entry point
├── Autoloader.php        # Class autoloader
├── composer.json         # Dependency configuration
├── phpunit.xml           # Testing configuration
├── robots.txt            # Robot/crawler rules
├── sitemap.xml           # SEO sitemap
└── README.md             # Project documentation
```

---

### ⚙️ Quick start for local development (without Docker)

1. **Clone or download** the repository.
2. **Install dependencies:**
    ```bash
    composer install
    ```
3. **Create an environment configuration file:**
    ```bash
    cp .env.dist .env
    ```
4. **Configure your database settings** in the `.env` file if needed.
5. **Launch a local PHP server** from the root of the project:
    ```bash
    php -S localhost:8000
    ```
6. Visit [http://localhost:8000](http://localhost:8000) in your browser.

### 📖 Documentation

You can verify the code quality or generate the documentation using the following commands:

- **Open PHPDoc**:
    ```bash
    firefox doc/index.html
    ```

---

## 🚀 Features

- Student, teacher and client accounts
- Dashboard with upcoming deadlines, deliverables, and important dates
- Centralized document management for every SAE
- Automated email notifications and reminders
- Skills and evaluations tracking for each student
- File submissions and oral defense scheduling
- Responsive design and accessible interface
- Simple setup and clear folder structure

---

## 📚 References

- **aka dev:** [https://www.youtube.com/@akdevyt](https://www.youtube.com/@akdevyt)
- **MDN Web Docs:** [https://developer.mozilla.org/](https://developer.mozilla.org/)
- **Built-in AI & PHPStorm helpers**

---

## 🤝 Contributing

Pull requests and suggestions are welcome!  
If you have ideas to improve the platform or want to report a bug, please open an issue.

---

## 📜 License

This project is for educational use and demonstration purposes.  
You are encouraged to adapt or extend it for your own needs.

---
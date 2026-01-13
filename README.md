# Sae_Manager_G4
# 🧩 SAE Manager

A web application for **tracking SAEs** (Situations d’Apprentissage et d’Évaluation — Project-Based Learning) in the Computer Science department.

---

## 🎯 Purpose

- **Streamline SAE tracking and organization** for both students and teachers
- **Automate notifications and reminders** for deadlines, deliverables, and oral defenses
- Simplify the **management of evaluations and competencies** with a **centralized platform**
- Provide a user-friendly interface for managing projects, schedules, and student progress

---

## 🌐 Deployment

🔗 **Live site:** [https://sae-manager.alwaysdata.net](https://sae-manager.alwaysdata.net/)  
📁 **Hosted on:** AlwaysData  
💻 **Main language:** PHP (custom MVC framework)

---

## 🗂️ Project Structure

```bash
.
├── _assets/
│   ├── css/              # Stylesheets
│   ├── docs/             # Legal documents (PDF)
│   ├── img/              # Images and favicon
│   └── script/           # JavaScript scripts
├── src/
│   ├── Controllers/      # Business logic (controllers)
│   ├── Models/           # Data access and business objects
│   ├── Shared/           # Exceptions and utilities
│   └── Views/            # HTML pages and PHP views
├── index.php             # Application entry point
├── Autoloader.php        # Class autoloader
├── robots.txt            # Robot/crawler rules
├── sitemap.xml           # SEO sitemap
└── README.md             # Project documentation
```

---

### ⚙️ Quick start for local development

1. **Clone or download** the repository.
2. **Create an environment configuration file:**
    ```bash
    cp .env.dist .env
    ```
3. **Configure your database settings** in the `.env` file if needed.
4. **Launch a local PHP server** from the root of the project:
    ```bash
    php -S localhost:8000
    ```
5. Visit [http://localhost:8000](http://localhost:8000) in your browser.

---

## 🚀 Features

- Student and teacher accounts
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

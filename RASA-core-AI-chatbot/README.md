# Chatbot System

## Overview
This project is a full-stack chatbot platform combining natural language processing, a web-based user interface, and a comprehensive admin dashboard. It leverages Rasa for conversational AI, Flask for the backend API and web interface, and PHP for the admin management panel.

## Architecture
- **Backend**: Python Flask API (`RASA-Chatbot/app.py`) connects the web UI, Rasa, and a MySQL database.
- **Conversational AI**: Rasa (in `RASA-Chatbot/RASA`) for intent classification and dialogue management.
- **Database**: MySQL for storing chat logs, user sessions, FAQs, and feedback.
- **Frontend**: Web interface using Flask templates and static assets.
- **Admin Panel**: PHP-based dashboard (`Admin/`) for managing users, FAQs, chat logs, analytics, and system settings.

## Technologies & Languages
- **Python** (Flask, requests, spellchecker, BeautifulSoup, NLTK, mysql-connector)
- **Rasa** (conversational AI framework)
- **PHP** (Admin panel, PHPMailer)
- **MySQL** (database)
- **HTML/CSS/JS** (web UI, admin panel)
- **Composer** (PHP dependency manager)

## Requirements & Dependencies

### Python (Flask Backend)
- flask
- requests
- spellchecker
- mysql-connector
- beautifulsoup4
- nltk
- pyyaml

### PHP (Admin Panel)
- PHP >= 7.x
- PHPMailer (via Composer, version ^6.9)

### Rasa
- Rasa (see `RASA-Chatbot/RASA` for specific version and setup)

### Database
- MySQL server

## Setup Instructions
1. **Clone the repository**
2. **Install Python dependencies**  
   From `RASA-Chatbot/`: `pip install -r requirements.txt` (if available)
3. **Set up Rasa**  
   Follow instructions in `RASA-Chatbot/RASA` to train models and start the Rasa server.
4. **Install PHP dependencies**  
   From `Admin/`: `composer install`
5. **Configure database**  
   Update database credentials in `RASA-Chatbot/app.py` and `Admin/db.php`.
6. **Run Flask backend**  
   From `RASA-Chatbot/`: `python app.py`
7. **Access Admin Panel**  
   Serve `Admin/` directory using a PHP-enabled web server (e.g., Apache, Nginx with PHP-FPM).

## Features
- Conversational AI chatbot (Rasa NLU)
- Web chat interface
- Admin panel for chat/FAQ management, analytics, and feedback
- Real-time chat logs and reporting
- Spell checking, session management, FAQ frequency tracking
- CRUD operations for intents, stories, rules, and responses

## License
..

## Authors
BrairCodz

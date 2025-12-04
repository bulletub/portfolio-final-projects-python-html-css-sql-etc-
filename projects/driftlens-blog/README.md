# DriftLens Blog

A full-featured social blogging platform with user authentication, posts, comments, messaging, and more.

## Features

- ✍️ Create and edit blog posts
- 💬 Comments and likes
- 👥 User profiles and authentication
- 📨 Private messaging
- 👥 Groups and group management
- 🔔 Notifications
- 🔍 Search functionality
- 👤 Admin dashboard
- 📱 Mobile responsive design
- 🎨 Splash screen and loading animations

## Technologies

- Python 3
- Flask
- SQLAlchemy
- SQLite/PostgreSQL
- Flask-Login
- Flask-Bcrypt
- HTML5, CSS3, JavaScript

## Local Development

1. Install dependencies:
```bash
pip install -r requirements.txt
```

2. Initialize database:
```bash
python migrate_db.py
```

3. Run the application:
```bash
python app.py
```

4. Open browser:
```
http://localhost:5000
```

## Default Admin Account

Check the migration scripts or create a superadmin account using Flask CLI commands.

## Deployment

See `DEPLOYMENT_GUIDE.md` in the parent directory for deployment instructions to Render, Railway, or other platforms.

**Note:** For production, use PostgreSQL instead of SQLite and update the database URI in `app.py`.


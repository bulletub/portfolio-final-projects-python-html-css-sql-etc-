# 🚀 Deploy Flask Projects to Render - Complete Guide

This guide will help you deploy **Weather Analyzer** and **DriftLens Blog** to Render so they can be showcased live.

---

## 📋 Prerequisites

1. **Render Account** (Free): Sign up at https://render.com
2. **GitHub Account**: Your projects should be on GitHub
3. **Both projects** should have `requirements.txt` files ✅ (You have these!)

---

## 🌦️ Part 1: Deploy Weather Analyzer

### Step 1: Prepare Your Project

1. **Go to your Weather Analyzer folder:**
   ```
   C:\Users\seana\Desktop\portfolio\projects\weather-analyzer
   ```

2. **Check if you have these files:**
   - ✅ `weather_analyzer.py` (main app file)
   - ✅ `requirements.txt`
   - ✅ `templates/` folder
   - ✅ `static/` folder

### Step 2: Create Render Configuration File

Create a file named `render.yaml` in the weather-analyzer folder:

```yaml
services:
  - type: web
    name: weather-analyzer
    env: python
    buildCommand: pip install -r requirements.txt
    startCommand: gunicorn weather_analyzer:app
    envVars:
      - key: PYTHON_VERSION
        value: 3.11.0
```

**OR** create a `Procfile` (simpler option):

```
web: gunicorn weather_analyzer:app
```

### Step 3: Update requirements.txt

Make sure your `requirements.txt` includes `gunicorn`:

```
Flask>=2.3.0
requests>=2.28.0
matplotlib>=3.7.0
Werkzeug>=2.3.0
numpy
gunicorn
```

### Step 4: Update weather_analyzer.py

At the bottom of `weather_analyzer.py`, make sure it has:

```python
if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)
```

### Step 5: Push to GitHub

1. Make sure your Weather Analyzer is in a GitHub repository
2. Commit and push all changes:
   ```bash
   git add .
   git commit -m "Prepare for Render deployment"
   git push
   ```

### Step 6: Deploy on Render

1. **Go to Render Dashboard:** https://dashboard.render.com
2. **Click "New +"** → **"Web Service"**
3. **Connect your GitHub repository** (or use "Public Git repository")
4. **Configure the service:**
   - **Name:** `weather-analyzer` (or your choice)
   - **Environment:** `Python 3`
   - **Build Command:** `pip install -r requirements.txt`
   - **Start Command:** `gunicorn weather_analyzer:app`
   - **Plan:** Free (or Starter for better performance)
5. **Click "Create Web Service"**
6. **Wait for deployment** (5-10 minutes)
7. **Your app will be live at:** `https://weather-analyzer.onrender.com` (or your custom name)

---

## 📝 Part 2: Deploy DriftLens Blog

### Step 1: Prepare Your Project

1. **Go to your DriftLens Blog folder:**
   ```
   C:\Users\seana\Desktop\portfolio\projects\driftlens-blog
   ```

2. **Check if you have these files:**
   - ✅ `app.py` (main app file)
   - ✅ `requirements.txt`
   - ✅ `templates/` folder
   - ✅ `static/` folder

### Step 2: Update requirements.txt

Add `gunicorn` to your requirements:

```
flask
flask-sqlalchemy
flask-bcrypt
flask-login
flask-migrate
flask-wtf
python-dotenv
werkzeug
pytz
gunicorn
psycopg2-binary
```

**Note:** We're adding `psycopg2-binary` for PostgreSQL (better than SQLite for production)

### Step 3: Update app.py for Production

**Important:** Update your `app.py` to use PostgreSQL instead of SQLite:

1. **Find this line:**
   ```python
   app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///driftlens.db'
   ```

2. **Replace with:**
   ```python
   import os
   
   # Use PostgreSQL on Render, SQLite locally
   database_url = os.environ.get('DATABASE_URL')
   if database_url:
       # Render provides DATABASE_URL, but SQLAlchemy needs postgresql:// not postgres://
       if database_url.startswith('postgres://'):
           database_url = database_url.replace('postgres://', 'postgresql://', 1)
       app.config['SQLALCHEMY_DATABASE_URI'] = database_url
   else:
       app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///driftlens.db'
   ```

3. **Add at the bottom:**
   ```python
   if __name__ == '__main__':
       port = int(os.environ.get('PORT', 5000))
       app.run(host='0.0.0.0', port=port, debug=False)
   ```

### Step 4: Create Procfile

Create a `Procfile` in the driftlens-blog folder:

```
web: gunicorn app:app
```

### Step 5: Set Up PostgreSQL Database on Render

1. **In Render Dashboard:** Click "New +" → **"PostgreSQL"**
2. **Configure:**
   - **Name:** `driftlens-db`
   - **Database:** `driftlens`
   - **User:** (auto-generated)
   - **Plan:** Free
3. **Click "Create Database"**
4. **Copy the "Internal Database URL"** (you'll need this)

### Step 6: Deploy Web Service

1. **In Render Dashboard:** Click "New +" → **"Web Service"**
2. **Connect your GitHub repository**
3. **Configure:**
   - **Name:** `driftlens-blog`
   - **Environment:** `Python 3`
   - **Build Command:** `pip install -r requirements.txt`
   - **Start Command:** `gunicorn app:app`
   - **Plan:** Free
4. **Environment Variables:**
   - Click "Advanced" → "Add Environment Variable"
   - **Key:** `DATABASE_URL`
   - **Value:** (paste the Internal Database URL from Step 5)
5. **Click "Create Web Service"**

### Step 7: Initialize Database

After deployment, you need to create the database tables:

1. **Go to your deployed service**
2. **Click "Shell"** (or use Render's shell)
3. **Run:**
   ```bash
   python
   from app import app, db
   with app.app_context():
       db.create_all()
   exit()
   ```

**OR** if you have a migration setup:
```bash
flask db upgrade
```

---

## 🔗 Part 3: Update Your Portfolio

After both apps are deployed, update your portfolio:

### Update projects.json

```json
{
  "title": "Weather Analyzer",
  "liveUrl": "https://weather-analyzer.onrender.com",
  ...
},
{
  "title": "DriftLens Blog",
  "liveUrl": "https://driftlens-blog.onrender.com",
  ...
}
```

---

## ⚠️ Important Notes

### Free Tier Limitations:
- **Render Free tier:**
  - Apps spin down after 15 minutes of inactivity
  - First request after spin-down takes 30-60 seconds
  - 750 hours/month free (enough for portfolio)

### For Better Performance:
- Upgrade to **Starter plan** ($7/month) for:
  - Always-on service
  - Faster response times
  - Better for showcasing

### Troubleshooting:

1. **Build fails:**
   - Check `requirements.txt` has all dependencies
   - Make sure Python version is compatible

2. **App crashes:**
   - Check Render logs: Dashboard → Your Service → "Logs"
   - Verify `startCommand` is correct
   - Make sure port uses `$PORT` environment variable

3. **Database issues:**
   - Verify `DATABASE_URL` is set correctly
   - Check database is running
   - Run migrations if needed

---

## 📝 Quick Checklist

### Weather Analyzer:
- [ ] Updated `requirements.txt` with `gunicorn`
- [ ] Created `Procfile` or `render.yaml`
- [ ] Updated `weather_analyzer.py` with port configuration
- [ ] Pushed to GitHub
- [ ] Deployed on Render
- [ ] Tested the live URL
- [ ] Updated portfolio `projects.json`

### DriftLens Blog:
- [ ] Updated `requirements.txt` with `gunicorn` and `psycopg2-binary`
- [ ] Updated `app.py` for PostgreSQL
- [ ] Created `Procfile`
- [ ] Created PostgreSQL database on Render
- [ ] Set `DATABASE_URL` environment variable
- [ ] Deployed web service
- [ ] Initialized database tables
- [ ] Tested the live URL
- [ ] Updated portfolio `projects.json`

---

## 🎉 You're Done!

Once deployed, your Flask projects will be live and accessible from your portfolio!

**Need help?** Check Render's documentation: https://render.com/docs


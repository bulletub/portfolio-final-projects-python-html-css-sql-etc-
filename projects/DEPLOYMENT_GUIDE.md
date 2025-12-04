# Project Deployment Guide

This guide explains how to deploy each project for live demos.

## 🍔 Sandwich Spread (Angular/Ionic App)

### Option 1: GitHub Pages (Current Setup)
The built files are already in `projects/sandwich-spread/`. To deploy:

1. **Enable GitHub Pages for the project folder:**
   - Go to your repository Settings → Pages
   - Source: Deploy from a branch
   - Branch: main
   - Folder: `/projects/sandwich-spread`
   - Save

2. **Access the app:**
   - URL: `https://bulletub.github.io/portfolio-final-projects-python-html-css-sql-etc-/projects/sandwich-spread/`

### Option 2: Netlify (Recommended for Better Performance)
1. Go to [Netlify](https://www.netlify.com/)
2. Drag and drop the `projects/sandwich-spread` folder
3. Your app will be live instantly with a custom URL
4. Update the `liveUrl` in `projects.json` with the Netlify URL

### Option 3: Vercel
```bash
cd projects/sandwich-spread
npm install -g vercel
vercel
```

---

## 🌤️ Weather Analyzer (Flask App)

### Deploy to Render (Free Tier Available)

1. **Create a Render Account:**
   - Go to [render.com](https://render.com)
   - Sign up with GitHub

2. **Create New Web Service:**
   - Click "New +" → "Web Service"
   - Connect your GitHub repository
   - Select the repository

3. **Configure the Service:**
   - **Name:** `weather-analyzer`
   - **Environment:** `Python 3`
   - **Build Command:** `pip install -r requirements.txt`
   - **Start Command:** `python weather_analyzer.py`
   - **Root Directory:** `projects/weather-analyzer`

4. **Environment Variables (if needed):**
   - Add any required environment variables

5. **Deploy:**
   - Click "Create Web Service"
   - Wait for deployment (5-10 minutes)
   - Your app will be live at: `https://weather-analyzer.onrender.com`

### Alternative: Railway
1. Go to [railway.app](https://railway.app)
2. New Project → Deploy from GitHub
3. Select repository and set root directory to `projects/weather-analyzer`
4. Railway will auto-detect Python and deploy

### Update Portfolio
After deployment, update `projects/projects.json` with your live URL.

---

## 📝 DriftLens Blog (Flask App)

### Deploy to Render

1. **Create New Web Service on Render:**
   - Click "New +" → "Web Service"
   - Connect your GitHub repository

2. **Configure:**
   - **Name:** `driftlens-blog`
   - **Environment:** `Python 3`
   - **Build Command:** `pip install -r requirements.txt`
   - **Start Command:** `python app.py` or `gunicorn app:app`
   - **Root Directory:** `projects/driftlens-blog`

3. **Environment Variables:**
   ```
   SECRET_KEY=your-secret-key-here
   FLASK_ENV=production
   ```

4. **Database Setup:**
   - Render provides PostgreSQL (free tier)
   - Update `SQLALCHEMY_DATABASE_URI` to use PostgreSQL
   - Or use SQLite for simple deployment

5. **Deploy:**
   - Click "Create Web Service"
   - Wait for deployment
   - Your app will be live at: `https://driftlens-blog.onrender.com`

### Alternative: Railway
1. Go to [railway.app](https://railway.app)
2. New Project → Deploy from GitHub
3. Add PostgreSQL database (optional)
4. Set environment variables
5. Deploy

### Update Portfolio
After deployment, update `projects/projects.json` with your live URL.

---

## 🔧 Quick Deployment Checklist

### For Static Sites (Sandwich Spread):
- [ ] Files copied to `projects/sandwich-spread/`
- [ ] GitHub Pages enabled OR Netlify/Vercel deployed
- [ ] `liveUrl` updated in `projects.json`

### For Flask Apps (Weather Analyzer & DriftLens):
- [ ] Source code in `projects/` folder
- [ ] `requirements.txt` present
- [ ] Deployed to Render/Railway
- [ ] Environment variables configured
- [ ] Database configured (if needed)
- [ ] `liveUrl` updated in `projects.json`

---

## 📌 Important Notes

1. **Free Tier Limitations:**
   - Render: Apps sleep after 15 minutes of inactivity
   - Railway: Limited hours per month on free tier
   - Consider upgrading for production use

2. **Database:**
   - For production, use PostgreSQL (provided by Render/Railway)
   - Update database URI in Flask apps
   - Run migrations if needed

3. **Static Files:**
   - Ensure `static/` folder is properly configured
   - Check file paths in templates

4. **Environment Variables:**
   - Never commit secrets to GitHub
   - Use environment variables for sensitive data
   - Update `.gitignore` to exclude `.env` files

---

## 🚀 After Deployment

1. Test all features on live URLs
2. Update `projects.json` with correct `liveUrl`
3. Commit and push changes:
   ```bash
   git add projects/projects.json
   git commit -m "Update project live URLs"
   git push
   ```
4. Your portfolio will automatically show the live demos!


# 🚀 Deploy DriftLens Blog to PythonAnywhere (Free)

PythonAnywhere is perfect for Flask apps! Here's how to deploy your DriftLens Blog.

---

## 📋 Step 1: Sign Up for PythonAnywhere

1. **Go to:** https://www.pythonanywhere.com
2. **Click "Beginner"** (Free account)
3. **Sign up** with your email or GitHub
4. **Verify your email** if required

---

## 📦 Step 2: Upload Your Files

### Option A: Using GitHub (Recommended)

1. **Make sure your code is on GitHub** ✅ (You already have this!)

2. **In PythonAnywhere Dashboard:**
   - Click **"Files"** tab
   - Click **"Upload a file"** or use **"Bash"** console

3. **Clone your repository:**
   ```bash
   cd ~
   git clone https://github.com/bulletub/portfolio-final-projects-python-html-css-sql-etc-.git
   ```

### Option B: Manual Upload

1. **Go to Files tab**
2. **Navigate to:** `/home/yourusername/`
3. **Create folder:** `driftlens-blog`
4. **Upload files** from your local `projects/driftlens-blog/` folder

---

## ⚙️ Step 3: Set Up Your Web App

1. **Go to "Web" tab** in PythonAnywhere dashboard
2. **Click "Add a new web app"**
3. **Choose:**
   - **Domain:** `yourusername.pythonanywhere.com` (free subdomain)
   - **Python Web Framework:** Flask
   - **Python version:** 3.10 (or latest available)
4. **Click "Next"**

---

## 🔧 Step 4: Configure Flask App

1. **In the Web app configuration:**
   - **Source code:** `/home/yourusername/portfolio-final-projects-python-html-css-sql-etc-/projects/driftlens-blog`
   - **Working directory:** `/home/yourusername/portfolio-final-projects-python-html-css-sql-etc-/projects/driftlens-blog`
   - **WSGI configuration file:** Click the link to edit

2. **Edit WSGI file:**
   - Find the WSGI file (usually `/var/www/yourusername_pythonanywhere_com_wsgi.py`)
   - Replace the content with:

```python
import sys
import os

# Add your project directory to the path
path = '/home/yourusername/portfolio-final-projects-python-html-css-sql-etc-/projects/driftlens-blog'
if path not in sys.path:
    sys.path.insert(0, path)

# Change to your project directory
os.chdir(path)

# Import your Flask app
from app import app as application

if __name__ == "__main__":
    application.run()
```

**Important:** Replace `yourusername` with your actual PythonAnywhere username!

---

## 📚 Step 5: Install Dependencies

1. **Go to "Tasks" tab** → **"Bash"** console
2. **Navigate to your project:**
   ```bash
   cd ~/portfolio-final-projects-python-html-css-sql-etc-/projects/driftlens-blog
   ```

3. **Install dependencies:**
   ```bash
   pip3.10 install --user -r requirements.txt
   ```

   **Note:** Use `pip3.10` (or your Python version) and `--user` flag for free accounts

---

## 🗄️ Step 6: Set Up Database

PythonAnywhere free tier includes SQLite, which is perfect for your app!

1. **In Bash console, navigate to your project:**
   ```bash
   cd ~/portfolio-final-projects-python-html-css-sql-etc-/projects/driftlens-blog
   ```

2. **Initialize the database:**
   ```bash
   python3.10
   ```
   
   Then in Python:
   ```python
   from app import app, db
   with app.app_context():
       db.create_all()
   exit()
   ```

3. **Your database will be created at:**
   `/home/yourusername/portfolio-final-projects-python-html-css-sql-etc-/projects/driftlens-blog/driftlens.db`

---

## 🔐 Step 7: Configure Environment Variables

1. **Go to "Files" tab**
2. **Navigate to your project folder**
3. **Create/edit `.env` file** (or update `app.py` directly):

```python
SECRET_KEY=your-secret-key-here
```

4. **Update `app.py` to use environment variables:**
   - Make sure it reads from `os.environ.get('SECRET_KEY')` ✅ (Already done!)

---

## 🎯 Step 8: Update Static Files Path

1. **In your WSGI file**, make sure static files are configured:

```python
# Add this if not already there
application.config['STATIC_FOLDER'] = '/home/yourusername/portfolio-final-projects-python-html-css-sql-etc-/projects/driftlens-blog/static'
```

2. **Or update `app.py`** to use absolute paths for uploads:

```python
import os

# Get the absolute path
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
UPLOAD_FOLDER = os.path.join(BASE_DIR, "static/uploads")
```

---

## 🚀 Step 9: Reload Your Web App

1. **Go to "Web" tab**
2. **Click the green "Reload" button** (top right)
3. **Wait 10-20 seconds** for the app to restart
4. **Visit your site:** `https://yourusername.pythonanywhere.com`

---

## ✅ Step 10: Test Your App

1. **Visit your URL:** `https://yourusername.pythonanywhere.com`
2. **Test features:**
   - Homepage loads
   - User registration
   - Login
   - Create posts
   - Upload images

---

## 🔧 Troubleshooting

### Issue 1: Module Not Found
**Solution:**
```bash
pip3.10 install --user <module-name>
```

### Issue 2: Database Errors
**Solution:**
- Make sure database file has write permissions
- Recreate database: Delete `driftlens.db` and run `db.create_all()` again

### Issue 3: Static Files Not Loading
**Solution:**
- Check file paths are correct
- Make sure `static/` folder exists
- Check file permissions

### Issue 4: 500 Internal Server Error
**Solution:**
- Go to "Web" tab → "Error log" to see the error
- Check "Server log" for detailed errors
- Common issues:
  - Missing dependencies
  - Wrong file paths
  - Database connection issues

### Issue 5: Uploads Not Working
**Solution:**
- Make sure `static/uploads/` folder exists
- Check folder permissions
- Use absolute paths in `app.py`

---

## 📝 Important Notes for Free Tier

### Limitations:
- **CPU time:** 100 seconds/day (usually enough for small apps)
- **Disk space:** 512 MB
- **External requests:** Limited (for API calls)
- **Custom domain:** Not available (use subdomain)
- **HTTPS:** Included ✅

### Best Practices:
- Optimize images (reduce file sizes)
- Use SQLite efficiently
- Monitor CPU usage in "Tasks" tab
- Keep database small

---

## 🔄 Updating Your App

1. **Pull latest changes from GitHub:**
   ```bash
   cd ~/portfolio-final-projects-python-html-css-sql-etc-
   git pull
   ```

2. **Reload web app** in "Web" tab

---

## 🔗 Update Your Portfolio

After deployment, update `projects/projects.json`:

```json
{
  "title": "DriftLens Blog",
  "liveUrl": "https://yourusername.pythonanywhere.com",
  ...
}
```

---

## 🎉 You're Done!

Your DriftLens Blog should now be live on PythonAnywhere!

**Your URL will be:** `https://yourusername.pythonanywhere.com`

**Advantages of PythonAnywhere:**
- ✅ Always-on (no spin-down like Render free tier)
- ✅ Fast response times
- ✅ Perfect for Flask apps
- ✅ Free tier is generous
- ✅ Easy to use

---

## 📞 Need Help?

- Check PythonAnywhere docs: https://help.pythonanywhere.com
- Check error logs in "Web" tab
- Common issues are usually path-related or missing dependencies


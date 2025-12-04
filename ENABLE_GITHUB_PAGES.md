# Enable GitHub Pages - Step-by-Step Guide

**IMPORTANT:** You MUST enable GitHub Pages in your repository settings for the site to work!

## Step 1: Enable GitHub Pages (REQUIRED)

1. **Go to your repository Settings:**
   - Direct link: https://github.com/bulletub/portfolio-final-projects-python-html-css-sql-etc-/settings/pages
   - Or: Go to your repo → Click **Settings** (top menu) → Click **Pages** (left sidebar)

2. **Configure GitHub Pages:**
   - Under **"Source"** section:
     - **Branch**: Select `main` from the dropdown
     - **Folder**: Select `/ (root)` from the dropdown
   - Click **Save** button

3. **Wait for confirmation:**
   - You should see a message: "Your site is published at https://bulletub.github.io/portfolio-final-projects-python-html-css-sql-etc-/"
   - If you see an error, make sure:
     - The repository is **Public** (not Private)
     - You're on the `main` branch (not `master`)
     - All files are pushed to GitHub

## Step 2: Wait for Deployment

- GitHub Pages takes **2-5 minutes** to build and deploy
- You'll see a green checkmark when it's ready
- The URL will be: `https://bulletub.github.io/portfolio-final-projects-python-html-css-sql-etc-/`

## Step 3: Access Your Projects

Once GitHub Pages is enabled, you can access:

- **Main Portfolio**: `https://bulletub.github.io/portfolio-final-projects-python-html-css-sql-etc-/`
- **Sandwich Spread**: `https://bulletub.github.io/portfolio-final-projects-python-html-css-sql-etc-/projects/sandwich-spread/`
- **Weather Analyzer Demo**: `https://bulletub.github.io/portfolio-final-projects-python-html-css-sql-etc-/projects/weather-analyzer/`
- **DriftLens Blog Demo**: `https://bulletub.github.io/portfolio-final-projects-python-html-css-sql-etc-/projects/driftlens-blog/`

## Troubleshooting

### If you see 404 error:
1. **Wait 5-10 minutes** - GitHub Pages needs time to build
2. **Check Settings > Pages** - Make sure it shows "Your site is published at..."
3. **Verify branch is `main`** - Not `master`
4. **Clear browser cache** - Try incognito/private mode

### If Sandwich Spread doesn't load:
- The base href is already configured correctly
- Make sure all files are pushed to GitHub
- Check browser console for errors (F12)

### For Flask Apps (Weather Analyzer & DriftLens):
- These are **demo pages** on GitHub Pages (Flask can't run on GitHub Pages)
- For full functionality, deploy to Render/Railway (see DEPLOYMENT_GUIDE.md)
- The demo pages show project information and link to source code

## Quick Check

After enabling, visit: `https://bulletub.github.io/portfolio-final-projects-python-html-css-sql-etc-/`

You should see your portfolio homepage with all three projects listed!


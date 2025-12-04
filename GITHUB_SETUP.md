# GitHub Portfolio Setup Guide

This guide will walk you through setting up your portfolio on GitHub and adding your localhost projects.

## Step 1: Create a GitHub Repository

1. Go to [GitHub.com](https://github.com) and sign in
2. Click the "+" icon in the top right corner
3. Select "New repository"
4. Name your repository (e.g., `portfolio` or `yourname.github.io`)
5. Choose **Public** (required for free GitHub Pages)
6. **DO NOT** initialize with README, .gitignore, or license (we already have these)
7. Click "Create repository"

## Step 2: Initialize Git and Push to GitHub

Open your terminal/command prompt in the portfolio folder and run:

```bash
# Initialize git repository
git init

# Add all files
git add .

# Make your first commit
git commit -m "Initial portfolio commit"

# Add your GitHub repository as remote (replace with your actual URL)
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git

# Rename branch to main (if needed)
git branch -M main

# Push to GitHub
git push -u origin main
```

## Step 3: Enable GitHub Pages

1. Go to your repository on GitHub
2. Click on **Settings** (top menu)
3. Scroll down to **Pages** in the left sidebar
4. Under **Source**, select:
   - Branch: **main**
   - Folder: **/ (root)**
5. Click **Save**
6. Wait a few minutes, then visit: `https://YOUR_USERNAME.github.io/YOUR_REPO_NAME`

## Step 4: Adding Your Localhost Projects

### Method A: Projects in Separate Repositories

If your projects are already in separate GitHub repositories:

1. Edit `projects/projects.json`
2. Add entries for each project:
   ```json
   {
     "title": "Project Name",
     "description": "What it does",
     "technologies": ["React", "Node.js"],
     "githubUrl": "https://github.com/yourusername/project-repo",
     "liveUrl": "https://your-deployed-app.com"
   }
   ```

### Method B: Include Project Code in Portfolio

If you want to include project code in this portfolio:

1. Create a folder for each project in `projects/`:
   ```
   projects/
   ├── todo-app/
   ├── weather-app/
   └── projects.json
   ```

2. Copy your project files into these folders
3. Add project info to `projects/projects.json`
4. Commit and push:
   ```bash
   git add projects/
   git commit -m "Add project: todo-app"
   git push
   ```

### Method C: Link to Localhost Projects (Development)

For projects still running on localhost:

1. Add them to `projects.json` with a note:
   ```json
   {
     "title": "Local Project",
     "description": "Currently running on localhost:3000",
     "technologies": ["React"],
     "githubUrl": "https://github.com/yourusername/local-project",
     "liveUrl": null
   }
   ```

2. Later, when you deploy them, update the `liveUrl`

## Step 5: Deploy Your Projects (Optional but Recommended)

To make your projects accessible online:

### Option 1: Vercel (Recommended for React/Next.js)
```bash
npm i -g vercel
cd your-project
vercel
```

### Option 2: Netlify
- Drag and drop your project folder to [Netlify Drop](https://app.netlify.com/drop)

### Option 3: GitHub Pages (for static sites)
- Similar setup to this portfolio

## Step 6: Update Your Portfolio

1. Edit `index.html`:
   - Update the "About Me" section
   - Add your contact information
   - Customize the hero section

2. Edit `styles.css`:
   - Change colors in `:root` variables
   - Customize fonts and spacing

3. Keep `projects.json` updated with new projects

## Quick Commands Reference

```bash
# Check status
git status

# Add changes
git add .

# Commit changes
git commit -m "Your commit message"

# Push to GitHub
git push

# Pull latest changes
git pull
```

## Troubleshooting

**GitHub Pages not showing?**
- Wait 5-10 minutes after enabling Pages
- Check Settings > Pages for any errors
- Ensure your `index.html` is in the root directory

**Projects not displaying?**
- Check `projects/projects.json` for valid JSON syntax
- Open browser console (F12) for errors
- Ensure file paths are correct

**Need help?**
- Check GitHub documentation: https://docs.github.com/en/pages
- Review the main README.md for more details


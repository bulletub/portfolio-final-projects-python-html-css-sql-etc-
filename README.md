# My Portfolio

A modern portfolio website to showcase your projects and skills.

## 🚀 Getting Started

1. **Clone or download this repository**
   ```bash
   git clone <your-repo-url>
   cd portfolio
   ```

2. **Open the portfolio**
   - Simply open `index.html` in your browser, or
   - Use a local server (recommended):
     ```bash
     # Using Python
     python -m http.server 8000
     
     # Using Node.js (if you have http-server installed)
     npx http-server
     ```

## 📁 Adding Your Localhost Projects

### Option 1: Add Projects as JSON (Recommended)

1. Edit `projects/projects.json` and add your projects:
   ```json
   {
     "projects": [
       {
         "title": "My Awesome Project",
         "description": "A description of what your project does",
         "technologies": ["React", "Node.js", "MongoDB"],
         "githubUrl": "https://github.com/yourusername/project-name",
         "liveUrl": "https://yourproject.com"
       }
     ]
   }
   ```

### Option 2: Include Project Folders

1. Create a folder for each project in the `projects/` directory:
   ```
   projects/
   ├── project-1/
   │   ├── README.md
   │   └── screenshots/
   ├── project-2/
   │   └── ...
   └── projects.json
   ```

2. Add project details to `projects/projects.json` as shown in Option 1.

### Option 3: Link to Separate GitHub Repositories

If your projects are in separate GitHub repositories, just add them to `projects.json` with their GitHub URLs. The portfolio will display them with links.

## 🌐 Publishing to GitHub Pages

1. **Create a GitHub repository**
   - Go to GitHub and create a new repository
   - Name it `portfolio` or `yourname.github.io` (for custom domain)

2. **Push your code to GitHub**
   ```bash
   git init
   git add .
   git commit -m "Initial portfolio commit"
   git branch -M main
   git remote add origin https://github.com/yourusername/your-repo-name.git
   git push -u origin main
   ```

3. **Enable GitHub Pages**
   - Go to your repository on GitHub
   - Click on "Settings"
   - Scroll to "Pages" section
   - Under "Source", select "main" branch
   - Click "Save"
   - Your portfolio will be live at: `https://yourusername.github.io/repository-name`

## 🎨 Customization

- **Edit `index.html`**: Update the navigation, hero section, and about/contact sections
- **Edit `styles.css`**: Customize colors, fonts, and layout
- **Edit `script.js`**: Modify how projects are displayed or add new features

## 📝 Project Structure

```
portfolio/
├── index.html          # Main HTML file
├── styles.css          # Styling
├── script.js           # JavaScript functionality
├── projects/
│   ├── projects.json   # Project data
│   └── [project folders]/
├── .gitignore
└── README.md
```

## 🔗 Adding Live Demo Links

If your projects are deployed (e.g., on Vercel, Netlify, Heroku), add the `liveUrl` field in `projects.json` to link to the live version.

## 💡 Tips

- Keep project descriptions concise but informative
- Use clear, descriptive project titles
- Include relevant technologies used
- Add screenshots to project folders for visual appeal
- Update your About section with your skills and experience
- Add your contact information in the Contact section

## 📄 License

Feel free to use this template for your own portfolio!


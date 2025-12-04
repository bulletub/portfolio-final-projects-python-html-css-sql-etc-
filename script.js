// Projects data - Add your projects here
const projects = [
    // Example project structure:
    // {
    //     title: "Project Name",
    //     description: "A brief description of your project",
    //     technologies: ["React", "Node.js", "MongoDB"],
    //     githubUrl: "https://github.com/yourusername/project-name",
    //     liveUrl: "https://yourproject.com",
    //     imageUrl: "projects/project-name/screenshot.png" // optional
    // }
];

// Load projects from projects.json if it exists
async function loadProjects() {
    try {
        const response = await fetch('projects/projects.json');
        if (response.ok) {
            const data = await response.json();
            return data.projects || [];
        }
    } catch (error) {
        console.log('No projects.json found, using default projects');
    }
    return projects;
}

// Render projects
async function renderProjects() {
    const projectsGrid = document.getElementById('projectsGrid');
    const projectsData = await loadProjects();

    if (projectsData.length === 0) {
        projectsGrid.innerHTML = '<p style="text-align: center; grid-column: 1 / -1;">No projects added yet. Add your projects to projects/projects.json</p>';
        return;
    }

    projectsGrid.innerHTML = projectsData.map(project => `
        <div class="project-card">
            <h3>${project.title}</h3>
            <p>${project.description}</p>
            ${project.technologies ? `<p class="tech-stack"><strong>Tech Stack:</strong> ${project.technologies.join(', ')}</p>` : ''}
            <div class="project-links">
                ${project.githubUrl ? `<a href="${project.githubUrl}" target="_blank">GitHub</a>` : ''}
                ${project.liveUrl ? `<a href="${project.liveUrl}" target="_blank">Live Demo</a>` : ''}
            </div>
        </div>
    `).join('');
}

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', () => {
    renderProjects();
    
    // Mobile menu functionality
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
        
        // Close menu when clicking on a link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                navLinks.classList.remove('active');
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
                menuToggle.classList.remove('active');
                navLinks.classList.remove('active');
            }
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 70; // Account for fixed navbar
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
});


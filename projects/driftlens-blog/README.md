# 📝 DriftLens Blog - Social Blogging Platform

A full-featured social blogging platform with user authentication, posts, comments, messaging, groups, and comprehensive admin features. Built with Python Flask and modern web technologies as part of a 4-month portfolio development period.

## 🎯 What I Built

I developed a complete social blogging platform from scratch, including:

- **User Authentication System**: Secure registration, login, logout with password hashing
- **Blog Post Management**: Create, edit, delete, and view blog posts with rich content
- **Comment System**: Users can comment on posts with nested replies
- **Like/Reaction System**: Like posts and comments
- **Private Messaging**: Direct messaging between users with conversation threads
- **Group Functionality**: Create and join groups, group posts, and group management
- **Notification System**: Real-time notifications for likes, comments, messages, and group invitations
- **User Profiles**: Customizable user profiles with bio, avatar, and activity feed
- **Search Functionality**: Search posts, users, and content across the platform
- **Category System**: Organize posts by categories
- **Admin Dashboard**: Comprehensive admin panel with analytics, user management, and content moderation
- **Report System**: Users can report posts and other users for moderation
- **Guest Browsing**: Browse content without registration
- **Mobile Responsive**: Fully responsive design for all devices
- **Splash Screen**: Animated loading screen
- **View Tracking**: Track post views and engagement

## 🛠️ Technologies Used

- **Python 3** - Backend programming language
- **Flask** - Web framework for building the application
- **SQLAlchemy** - ORM for database management and queries
- **SQLite** - Development database (PostgreSQL for production)
- **Flask-Login** - User session management and authentication
- **Flask-Bcrypt** - Secure password hashing
- **Jinja2** - Template engine for dynamic HTML
- **HTML5** - Modern markup and semantic elements
- **CSS3** - Advanced styling, animations, and responsive design
- **JavaScript** - Frontend interactivity and dynamic content
- **Werkzeug** - WSGI utilities and file handling
- **Pytz** - Timezone handling (Philippine timezone)

## ⏱️ Development Time

This project was developed as part of a **4-month portfolio development period**, demonstrating full-stack development skills, database design, and complex feature implementation.

## ✨ Key Features

### User Features
- ✍️ **Create & Edit Posts**: Rich text posts with image uploads
- 💬 **Comments & Replies**: Engage with posts through comments
- 👍 **Likes**: Like posts and comments
- 📨 **Private Messaging**: Send direct messages to other users
- 👥 **Groups**: Create and join groups, share group posts
- 🔔 **Notifications**: Real-time notifications for all activities
- 🔍 **Search**: Search posts, users, and content
- 👤 **Profiles**: Customizable user profiles
- 📊 **Analytics**: View your post statistics and engagement

### Admin Features
- 🛡️ **User Management**: View, edit, and manage users
- 📝 **Content Moderation**: Manage posts, comments, and reports
- 📊 **Analytics Dashboard**: Platform-wide statistics and insights
- 🚨 **Report Management**: Handle user reports and take actions
- 🔐 **Super Admin**: Full platform control

### Technical Features
- 🔒 **Secure Authentication**: Bcrypt password hashing
- 📱 **Mobile Responsive**: Works on all devices
- 🎨 **Modern UI**: Clean, intuitive interface
- ⚡ **Performance**: Optimized database queries
- 🌐 **Timezone Support**: Philippine timezone handling
- 📸 **Image Upload**: Secure file upload system
- 🔄 **Real-Time Updates**: Dynamic content updates

## 💻 Local Development

### Prerequisites
- Python 3.8 or higher
- pip (Python package manager)

### Setup

1. **Install dependencies:**
   ```bash
   pip install -r requirements.txt
   ```

2. **Initialize database:**
   ```bash
   python migrate_db.py
   ```

3. **Run the application:**
   ```bash
   python app.py
   ```

4. **Access the application:**
   ```
   http://localhost:5000
   ```

## 🗄️ Database Schema

The application uses SQLAlchemy ORM with the following main models:
- **User**: User accounts and profiles
- **Post**: Blog posts with content, images, categories
- **Comment**: Comments on posts with threading
- **Like**: Likes on posts and comments
- **Message**: Private messages between users
- **Group**: User groups and group posts
- **Notification**: User notifications
- **Report**: Content and user reports

## 🔐 Authentication & Security

- **Password Hashing**: Bcrypt for secure password storage
- **Session Management**: Flask-Login for user sessions
- **CSRF Protection**: Built-in Flask security
- **File Upload Security**: Secure filename handling and validation
- **SQL Injection Prevention**: SQLAlchemy ORM protection
- **XSS Protection**: Template escaping

## 📱 Mobile Features

- Fully responsive design
- Touch-optimized interface
- Mobile menu navigation
- Optimized image loading
- Fast page transitions
- Mobile-friendly forms

## 🎨 UI/UX Features

- **Splash Screen**: Animated loading screen
- **Loading States**: Visual feedback during operations
- **Flash Messages**: User feedback for actions
- **Smooth Animations**: CSS transitions and animations
- **Dark/Light Theme Ready**: Theme variables for customization
- **Accessible Design**: Semantic HTML and ARIA labels

## 🚀 Deployment

See `DEPLOYMENT_GUIDE.md` in the parent directory for deployment instructions.

### Production Considerations

- **Database**: Use PostgreSQL instead of SQLite
- **Environment Variables**: Set `SECRET_KEY` and other configs
- **Static Files**: Configure proper static file serving
- **HTTPS**: Enable SSL/TLS for secure connections
- **Database Migrations**: Run migrations before deployment

### Environment Variables

```bash
SECRET_KEY=your-secret-key-here
FLASK_ENV=production
DATABASE_URL=postgresql://user:pass@host/dbname
```

## 📚 What I Learned

- Building complex full-stack applications
- Database design and ORM usage
- User authentication and authorization
- Real-time features (notifications, messaging)
- File upload and handling
- Search functionality implementation
- Admin dashboard development
- Mobile-responsive design
- Security best practices
- API design and RESTful principles

## 🔧 Project Structure

```
driftlens-blog/
├── app.py                  # Main Flask application
├── migrate_db.py           # Database migration script
├── requirements.txt       # Python dependencies
├── static/
│   ├── css/              # Stylesheets
│   ├── js/              # JavaScript files
│   ├── images/          # Static images
│   └── uploads/         # User-uploaded files
└── templates/
    ├── index.html       # Home page
    ├── login.html       # Login page
    ├── register.html    # Registration page
    ├── profile.html     # User profile
    └── superadmin/      # Admin templates
```

## 🎯 Use Cases

- **Bloggers**: Share thoughts, experiences, and content
- **Communities**: Create groups around interests
- **Social Network**: Connect with other users
- **Content Platform**: Discover and engage with content
- **Learning Platform**: Share knowledge and tutorials

---

**Developed in 4 months** | **Python + Flask** | **Full-Stack Social Platform**

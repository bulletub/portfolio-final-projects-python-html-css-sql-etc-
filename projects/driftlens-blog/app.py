from flask import Flask, render_template, request, redirect, url_for, flash, session, jsonify, send_from_directory
from flask_sqlalchemy import SQLAlchemy
from flask_bcrypt import Bcrypt
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from werkzeug.utils import secure_filename
import os
from functools import wraps
import click
from datetime import datetime
import pytz

app = Flask(__name__)
app.config['SECRET_KEY'] = 'supersecretkey'  # Change in production
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///driftlens.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db = SQLAlchemy(app)
bcrypt = Bcrypt(app)

UPLOAD_FOLDER = "static/uploads"
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
app.config["UPLOAD_FOLDER"] = UPLOAD_FOLDER

# Flask-Login
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = "login"

# Philippine Timezone
PHILIPPINE_TZ = pytz.timezone('Asia/Manila')

def to_philippine_time(utc_dt):
    """Convert UTC datetime to Philippine time"""
    if utc_dt is None:
        return None
    if utc_dt.tzinfo is None:
        utc_dt = pytz.utc.localize(utc_dt)
    return utc_dt.astimezone(PHILIPPINE_TZ)

# Register Jinja filter
@app.template_filter('philippine_time')
def philippine_time_filter(utc_dt, format='%B %d, %Y at %I:%M %p'):
    """Convert UTC to Philippine time and format it"""
    if utc_dt is None:
        return 'Recently'
    ph_time = to_philippine_time(utc_dt)
    return ph_time.strftime(format)

# Helper function to get display name
@app.template_filter('display_name')
def get_display_name(user):
    """Get user's display name or fallback to email username"""
    if user.display_name:
        return user.display_name
    return user.email.split('@')[0]

# ================= MODELS =================
class User(db.Model, UserMixin):
    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password = db.Column(db.String(200), nullable=False)
    # New fields for roles and status
    role = db.Column(db.String(20), nullable=False, default='user')  # roles: user, admin, superadmin
    status = db.Column(db.String(20), nullable=False, default='active') # status: active, blocked, warned
    # Warning system fields
    warning_count = db.Column(db.Integer, default=0)
    warning_message = db.Column(db.Text, nullable=True)
    last_warning_at = db.Column(db.DateTime, nullable=True)
    # Profile customization fields
    display_name = db.Column(db.String(100), nullable=True)
    bio = db.Column(db.Text, nullable=True)
    profile_picture = db.Column(db.String(255), nullable=True)

class Post(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text, nullable=False)
    category = db.Column(db.String(50), nullable=False)
    content_type = db.Column(db.String(50), nullable=False)
    file_path = db.Column(db.String(255), nullable=True)
    tags = db.Column(db.String(255), nullable=True)
    # New field for view count
    views = db.Column(db.Integer, default=0)
    likes = db.Column(db.Integer, default=0)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)

    user = db.relationship("User", backref="posts")
    comments = db.relationship("Comment", backref="post", lazy="dynamic", cascade="all, delete-orphan")
    post_likes = db.relationship("PostLike", backref="post", lazy="dynamic", cascade="all, delete-orphan")
    
    # Add author property as alias for user
    @property
    def author(self):
        return self.user

class Comment(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    content = db.Column(db.Text, nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    post_id = db.Column(db.Integer, db.ForeignKey("post.id"), nullable=False)

    user = db.relationship("User", backref="comments")

class PostLike(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    post_id = db.Column(db.Integer, db.ForeignKey("post.id"), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    user = db.relationship("User", backref="post_likes")
    
    # Ensure a user can only like a post once
    __table_args__ = (db.UniqueConstraint('user_id', 'post_id', name='unique_user_post_like'),)

class Friendship(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    requester_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    addressee_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    status = db.Column(db.String(20), nullable=False, default='pending')  # pending, accepted, blocked
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    requester = db.relationship("User", foreign_keys=[requester_id], backref="sent_friend_requests")
    addressee = db.relationship("User", foreign_keys=[addressee_id], backref="received_friend_requests")
    
    __table_args__ = (db.UniqueConstraint('requester_id', 'addressee_id', name='unique_friendship'),)

class Message(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    sender_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    recipient_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    content = db.Column(db.Text, nullable=False)
    is_read = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    sender = db.relationship("User", foreign_keys=[sender_id], backref="sent_messages")
    recipient = db.relationship("User", foreign_keys=[recipient_id], backref="received_messages")

class Group(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text, nullable=True)
    creator_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    is_private = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    creator = db.relationship("User", backref="created_groups")
    members = db.relationship("GroupMember", backref="group", lazy="dynamic", cascade="all, delete-orphan")
    posts = db.relationship("GroupPost", backref="group", lazy="dynamic", cascade="all, delete-orphan")

class GroupMember(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    group_id = db.Column(db.Integer, db.ForeignKey("group.id"), nullable=False)
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    role = db.Column(db.String(20), nullable=False, default='member')  # member, admin, moderator
    joined_at = db.Column(db.DateTime, default=datetime.utcnow)

    user = db.relationship("User", backref="group_memberships")
    
    __table_args__ = (db.UniqueConstraint('group_id', 'user_id', name='unique_group_membership'),)

class GroupPost(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(200), nullable=False)
    content = db.Column(db.Text, nullable=False)
    group_id = db.Column(db.Integer, db.ForeignKey("group.id"), nullable=False)
    author_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    author = db.relationship("User", backref="group_posts")

class GroupInvitation(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    group_id = db.Column(db.Integer, db.ForeignKey("group.id"), nullable=False)
    inviter_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    invitee_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    status = db.Column(db.String(20), nullable=False, default='pending')  # pending, accepted, declined
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    group = db.relationship("Group", backref="invitations")
    inviter = db.relationship("User", foreign_keys=[inviter_id], backref="sent_invitations")
    invitee = db.relationship("User", foreign_keys=[invitee_id], backref="received_invitations")
    
    __table_args__ = (db.UniqueConstraint('group_id', 'invitee_id', name='unique_group_invitation'),)

class UserReport(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    reporter_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    reported_user_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    reason = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text, nullable=True)
    status = db.Column(db.String(20), nullable=False, default='pending')  # pending, reviewed, resolved
    admin_notes = db.Column(db.Text, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    reviewed_at = db.Column(db.DateTime, nullable=True)

    reporter = db.relationship("User", foreign_keys=[reporter_id], backref="reports_made")
    reported_user = db.relationship("User", foreign_keys=[reported_user_id], backref="reports_received")

class PostReport(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    reporter_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    post_id = db.Column(db.Integer, db.ForeignKey("post.id"), nullable=False)
    reason = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text, nullable=True)
    decision = db.Column(db.String(50), nullable=True)  # remove, warn, ignore
    status = db.Column(db.String(20), nullable=False, default='pending')  # pending, reviewed, resolved
    admin_notes = db.Column(db.Text, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    reviewed_at = db.Column(db.DateTime, nullable=True)

    reporter = db.relationship("User", foreign_keys=[reporter_id], backref="post_reports_made")
    post = db.relationship("Post", backref="reports")

class SharedPost(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    post_id = db.Column(db.Integer, db.ForeignKey("post.id"), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    user = db.relationship("User", backref="shared_posts")
    post = db.relationship("Post", backref="shares")
    
    __table_args__ = (db.UniqueConstraint('user_id', 'post_id', name='unique_user_post_share'),)

class Notification(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    type = db.Column(db.String(50), nullable=False)  # friend_request, message, group_invite, report, etc.
    title = db.Column(db.String(200), nullable=False)
    content = db.Column(db.Text, nullable=True)
    is_read = db.Column(db.Boolean, default=False)
    related_id = db.Column(db.Integer, nullable=True)  # ID of related entity (user_id, post_id, group_id, etc.)
    related_type = db.Column(db.String(50), nullable=True)  # Type of related entity (user, post, group, etc.)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    user = db.relationship("User", backref="notifications")

class PostView(db.Model):
    """Track unique post views - only logged-in users, exclude post creator"""
    id = db.Column(db.Integer, primary_key=True)
    post_id = db.Column(db.Integer, db.ForeignKey("post.id"), nullable=False)
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    viewed_at = db.Column(db.DateTime, default=datetime.utcnow)

    post = db.relationship("Post", backref="post_views")
    user = db.relationship("User", backref="viewed_posts")
    
    __table_args__ = (db.UniqueConstraint('post_id', 'user_id', name='unique_post_view'),)

@login_manager.user_loader
def load_user(user_id):
    try:
        return User.query.get(int(user_id))
    except:
        return None

# ================= DECORATORS for Access Control =================
def superadmin_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not current_user.is_authenticated or current_user.role != 'superadmin':
            flash("You do not have permission to access this page.", "danger")
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated_function

# ================= REGULAR USER ROUTES =================

@app.route("/")
def index():
    if current_user.is_authenticated:
        posts = Post.query.order_by(Post.id.desc()).all()
        # Get shared posts for display
        shared_posts = SharedPost.query.order_by(SharedPost.created_at.desc()).limit(10).all()
        return render_template("home_user.html", user=current_user, posts=posts, shared_posts=shared_posts)
    
    # For guest users, show recent posts from database
    recent_posts = Post.query.order_by(Post.created_at.desc()).limit(9).all()
    
    # Get posts by category for guests
    photography_posts = Post.query.filter_by(category='photography').order_by(Post.created_at.desc()).limit(3).all()
    travel_posts = Post.query.filter_by(category='travel').order_by(Post.created_at.desc()).limit(3).all()
    adventure_posts = Post.query.filter_by(category='adventure').order_by(Post.created_at.desc()).limit(3).all()
    
    # Get category counts
    from sqlalchemy import func
    category_counts = db.session.query(
        Post.category,
        func.count(Post.id).label('count')
    ).group_by(Post.category).all()
    
    category_dict = {cat: count for cat, count in category_counts}
    
    return render_template("index.html", 
                         recent_posts=recent_posts,
                         photography_posts=photography_posts,
                         travel_posts=travel_posts,
                         adventure_posts=adventure_posts,
                         category_counts=category_dict)

@app.route("/guest/category/<category>")
def guest_category_posts(category):
    """Allow guests to browse categories but not view individual posts"""
    posts = Post.query.filter_by(category=category).order_by(Post.created_at.desc()).all()
    return render_template("guest_category.html", posts=posts, category=category)

@app.route("/category/<category>")
@login_required
def category_posts(category):
    posts = Post.query.filter_by(category=category).order_by(Post.id.desc()).all()
    return render_template("category_posts.html", user=current_user, posts=posts, category=category)

@app.route("/guest/explore")
def guest_explore():
    """Allow guests to view explore page without login"""
    from datetime import timedelta
    seven_days_ago = datetime.utcnow() - timedelta(days=7)
    trending_posts = Post.query.filter(Post.created_at >= seven_days_ago).order_by(Post.views.desc()).limit(6).all()
    
    popular_posts = Post.query.order_by(Post.likes.desc()).limit(6).all()
    recent_posts = Post.query.order_by(Post.created_at.desc()).limit(6).all()
    
    from sqlalchemy import func
    top_creators = db.session.query(User, func.count(Post.id).label('post_count'))\
        .join(Post, User.id == Post.user_id)\
        .group_by(User.id)\
        .order_by(func.count(Post.id).desc())\
        .limit(8).all()
    
    category_stats = db.session.query(
        Post.category,
        func.count(Post.id).label('count')
    ).group_by(Post.category).all()
    
    active_groups = Group.query.join(GroupMember).group_by(Group.id)\
        .order_by(func.count(GroupMember.id).desc()).limit(4).all()
    
    return render_template("guest_explore.html",
                         trending_posts=trending_posts,
                         popular_posts=popular_posts,
                         recent_posts=recent_posts,
                         top_creators=top_creators,
                         category_stats=category_stats,
                         active_groups=active_groups,
                         is_guest=True)

@app.route("/explore")
@login_required
def explore():
    """Explore page with trending content, top creators, and categories"""
    
    # Get trending posts (most views in last 7 days)
    from datetime import timedelta
    seven_days_ago = datetime.utcnow() - timedelta(days=7)
    trending_posts = Post.query.filter(Post.created_at >= seven_days_ago).order_by(Post.views.desc()).limit(6).all()
    
    # Get most liked posts (all time)
    popular_posts = Post.query.order_by(Post.likes.desc()).limit(6).all()
    
    # Get recent posts
    recent_posts = Post.query.order_by(Post.created_at.desc()).limit(6).all()
    
    # Get top creators (users with most posts)
    from sqlalchemy import func
    top_creators = db.session.query(User, func.count(Post.id).label('post_count'))\
        .join(Post, User.id == Post.user_id)\
        .group_by(User.id)\
        .order_by(func.count(Post.id).desc())\
        .limit(8).all()
    
    # Get category stats
    category_stats = db.session.query(
        Post.category,
        func.count(Post.id).label('count')
    ).group_by(Post.category).all()
    
    # Get active groups (with most members)
    active_groups = Group.query.join(GroupMember).group_by(Group.id)\
        .order_by(func.count(GroupMember.id).desc()).limit(4).all()
    
    return render_template("explore.html",
                         trending_posts=trending_posts,
                         popular_posts=popular_posts,
                         recent_posts=recent_posts,
                         top_creators=top_creators,
                         category_stats=category_stats,
                         active_groups=active_groups)

@app.route("/uploads/<filename>")
def uploaded_file(filename):
    return send_from_directory(app.config['UPLOAD_FOLDER'], filename)

@app.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        email = request.form.get("email")
        password = request.form.get("password")
        user = User.query.filter_by(email=email).first()

        # Check if account is blocked
        if user and user.status == 'blocked':
            return render_template("login.html", 
                                   show_blocked_modal=True,
                                   blocked_message=user.warning_message or "Your account has been blocked due to violations of our terms of service. Please contact support for assistance.")

        if user and bcrypt.check_password_hash(user.password, password):
            login_user(user)
            
            # Check if user has been warned
            if user.status == 'warned' and user.warning_message:
                # Store warning in session to show after redirect
                session['show_warning'] = True
                session['warning_message'] = user.warning_message
                session['warning_count'] = user.warning_count
            
            if user.role == 'superadmin':
                return redirect(url_for('admin_dashboard'))
            return redirect(url_for("index"))
        else:
            flash("Invalid email or password", "danger")
    return render_template("login.html")

@app.route("/register", methods=["GET", "POST"])
def register():
    if request.method == "POST":
        email = request.form.get("email")
        password = request.form.get("password")
        if User.query.filter_by(email=email).first():
            flash("Email already exists", "danger")
            return redirect(url_for("register"))
        hashed_pw = bcrypt.generate_password_hash(password).decode("utf-8")
        new_user = User(email=email, password=hashed_pw)
        db.session.add(new_user)
        db.session.commit()
        flash("Account created! You can log in now.", "success")
        return redirect(url_for("login"))
    return render_template("register.html")
    
@app.route("/post/<int:post_id>")
def view_post(post_id):
    post = Post.query.get_or_404(post_id)
    
    # Track unique views - only for logged-in users who are NOT the post creator
    if current_user.is_authenticated and current_user.id != post.user_id:
        # Check if this user has already viewed this post
        existing_view = PostView.query.filter_by(post_id=post_id, user_id=current_user.id).first()
        
        if not existing_view:
            # Create new view record
            new_view = PostView(post_id=post_id, user_id=current_user.id)
            db.session.add(new_view)
            
            # Increment view count
            post.views += 1
            
            try:
                db.session.commit()
            except Exception as e:
                db.session.rollback()
                # If there's a duplicate constraint error, just ignore it
                pass
    
    # Get comments for this post
    comments = Comment.query.filter_by(post_id=post_id).order_by(Comment.created_at.desc()).all()
    
    # Check if current user has liked this post
    user_liked = False
    if current_user.is_authenticated:
        user_liked = PostLike.query.filter_by(user_id=current_user.id, post_id=post_id).first() is not None
    
    return render_template("view_post.html", post=post, comments=comments, user_liked=user_liked)

@app.route("/create_post", methods=["POST"])
@login_required
def create_post():
    title = request.form.get("title")
    description = request.form.get("description")
    category = request.form.get("category")
    content_type = request.form.get("contentType")
    tags = request.form.get("tags")
    file_path = None
    if "file" in request.files:
        file = request.files["file"]
        if file.filename:
            filename = secure_filename(file.filename)
            save_path = os.path.join(app.config["UPLOAD_FOLDER"], filename)
            file.save(save_path)
            file_path = filename  # Store just the filename, not the full path
    new_post = Post(
        title=title, description=description, category=category,
        content_type=content_type, file_path=file_path, tags=tags,
        user_id=current_user.id
    )
    db.session.add(new_post)
    db.session.commit()
    flash("Post created successfully!", "success")
    return redirect(url_for("index"))

@app.route("/dismiss_warning", methods=["POST"])
@login_required
def dismiss_warning():
    """User acknowledges the warning"""
    if 'show_warning' in session:
        session.pop('show_warning', None)
        session.pop('warning_message', None)
        session.pop('warning_count', None)
    return jsonify({'message': 'Warning acknowledged'})

@app.route("/logout")
@login_required
def logout():
    logout_user()
    return redirect(url_for("index"))

@app.route("/like_post/<int:post_id>", methods=["POST"])
@login_required
def like_post(post_id):
    post = Post.query.get_or_404(post_id)
    existing_like = PostLike.query.filter_by(user_id=current_user.id, post_id=post_id).first()
    
    if existing_like:
        # Unlike the post
        db.session.delete(existing_like)
        post.likes -= 1
        liked = False
    else:
        # Like the post
        new_like = PostLike(user_id=current_user.id, post_id=post_id)
        db.session.add(new_like)
        post.likes += 1
        liked = True
    
    db.session.commit()
    return jsonify({'liked': liked, 'likes_count': post.likes})

@app.route("/comment_post/<int:post_id>", methods=["POST"])
@login_required
def comment_post(post_id):
    post = Post.query.get_or_404(post_id)
    content = request.form.get('content')
    
    if content:
        comment = Comment(content=content, user_id=current_user.id, post_id=post_id)
        db.session.add(comment)
        db.session.commit()
        flash("Comment added successfully!", "success")
    else:
        flash("Comment cannot be empty!", "danger")
    
    return redirect(url_for('view_post', post_id=post_id))

@app.route("/share_post/<int:post_id>", methods=["POST"])
@login_required
def share_post(post_id):
    post = Post.query.get_or_404(post_id)
    
    # Check if user already shared this post
    existing_share = SharedPost.query.filter_by(user_id=current_user.id, post_id=post_id).first()
    
    if existing_share:
        return jsonify({
            'message': 'You have already shared this post',
            'share_url': request.url_root + f'post/{post_id}',
            'title': post.title
        })
    
    # Create a shared post record
    shared = SharedPost(user_id=current_user.id, post_id=post_id)
    db.session.add(shared)
    db.session.commit()
    
    return jsonify({
        'message': 'Post shared successfully!',
        'share_url': request.url_root + f'post/{post_id}',
        'title': post.title
    })

# ================= FRIENDSHIP ROUTES =================

@app.route("/search_users")
@login_required
def search_users():
    query = request.args.get('q', '')
    if len(query) < 2:
        return jsonify([])
    
    users = User.query.filter(
        User.email.contains(query),
        User.id != current_user.id
    ).limit(10).all()
    
    return jsonify([{
        'id': user.id,
        'email': user.email,
        'status': user.status
    } for user in users])

@app.route("/send_friend_request/<int:user_id>", methods=["POST"])
@login_required
def send_friend_request(user_id):
    if user_id == current_user.id:
        return jsonify({'error': 'Cannot send friend request to yourself'}), 400
    
    # Check if friendship already exists
    existing = Friendship.query.filter(
        ((Friendship.requester_id == current_user.id) & (Friendship.addressee_id == user_id)) |
        ((Friendship.requester_id == user_id) & (Friendship.addressee_id == current_user.id))
    ).first()
    
    if existing:
        return jsonify({'error': 'Friendship request already exists'}), 400
    
    friend_request = Friendship(requester_id=current_user.id, addressee_id=user_id)
    db.session.add(friend_request)
    
    # Create notification
    notification = Notification(
        user_id=user_id,
        type='friend_request',
        title='New Friend Request',
        content=f'{current_user.email} sent you a friend request',
        related_id=current_user.id,
        related_type='user'
    )
    db.session.add(notification)
    
    db.session.commit()
    return jsonify({'message': 'Friend request sent successfully'})

@app.route("/respond_friend_request/<int:request_id>", methods=["POST"])
@login_required
def respond_friend_request(request_id):
    action = request.json.get('action')  # 'accept' or 'decline'
    friend_request = Friendship.query.get_or_404(request_id)
    
    if friend_request.addressee_id != current_user.id:
        return jsonify({'error': 'Unauthorized'}), 403
    
    if action == 'accept':
        friend_request.status = 'accepted'
        # Create notification for requester
        notification = Notification(
            user_id=friend_request.requester_id,
            type='friend_request_accepted',
            title='Friend Request Accepted',
            content=f'{current_user.email} accepted your friend request',
            related_id=current_user.id,
            related_type='user'
        )
        db.session.add(notification)
    else:
        friend_request.status = 'declined'
    
    db.session.commit()
    return jsonify({'message': f'Friend request {action}ed successfully'})

@app.route("/get_notification_counts")
@login_required
def get_notification_counts():
    """Get counts for notifications, messages, and friend requests for the current user."""
    
    # Count unread notifications
    unread_notifications = Notification.query.filter_by(user_id=current_user.id, is_read=False).count()
    
    # Count unread messages
    unread_messages = Message.query.filter_by(recipient_id=current_user.id, is_read=False).count()
    
    # Count pending friend requests received
    pending_friend_requests = Friendship.query.filter_by(addressee_id=current_user.id, status='pending').count()
    
    # Count pending group invitations
    pending_group_invitations = GroupInvitation.query.filter_by(invitee_id=current_user.id, status='pending').count()
    
    return jsonify({
        'notifications': unread_notifications,
        'messages': unread_messages,
        'friend_requests': pending_friend_requests,
        'group_invitations': pending_group_invitations
    })

@app.route("/get_friend_requests")
@login_required
def get_friend_requests():
    """Get pending friend requests for the current user."""
    
    friend_requests = Friendship.query.filter_by(addressee_id=current_user.id, status='pending').all()
    
    return jsonify([{
        'id': request.id,
        'requester_email': request.requester.email,
        'created_at': request.created_at.isoformat() if request.created_at else None
    } for request in friend_requests])

# ================= MESSAGING ROUTES =================

@app.route("/messages")
@login_required
def messages():
    # Get all conversations (users who have sent or received messages)
    conversations = db.session.query(User).join(
        Message, (User.id == Message.sender_id) | (User.id == Message.recipient_id)
    ).filter(
        (Message.sender_id == current_user.id) | (Message.recipient_id == current_user.id)
    ).distinct().all()
    
    # Get unread message counts for each conversation
    conversation_data = []
    for user in conversations:
        if user.id == current_user.id:
            continue
        unread_count = Message.query.filter(
            Message.sender_id == user.id,
            Message.recipient_id == current_user.id,
            Message.is_read == False
        ).count()
        conversation_data.append({
            'user': user,
            'unread_count': unread_count
        })
    
    return render_template("messages.html", conversations=conversation_data)

@app.route("/messages/<int:user_id>")
@login_required
def conversation(user_id):
    other_user = User.query.get_or_404(user_id)
    
    # Get messages between current user and other user
    messages = Message.query.filter(
        ((Message.sender_id == current_user.id) & (Message.recipient_id == user_id)) |
        ((Message.sender_id == user_id) & (Message.recipient_id == current_user.id))
    ).order_by(Message.created_at.asc()).all()
    
    # Mark messages as read
    Message.query.filter_by(recipient_id=current_user.id, sender_id=user_id, is_read=False).update({'is_read': True})
    db.session.commit()
    
    return render_template("conversation.html", other_user=other_user, messages=messages)

@app.route("/send_message", methods=["POST"])
@login_required
def send_message():
    recipient_id = request.json.get('recipient_id')
    content = request.json.get('content')
    
    if not recipient_id or not content:
        return jsonify({'error': 'Missing required fields'}), 400
    
    message = Message(
        sender_id=current_user.id,
        recipient_id=recipient_id,
        content=content
    )
    db.session.add(message)
    
    # Create notification
    notification = Notification(
        user_id=recipient_id,
        type='message',
        title='New Message',
        content=f'You have a new message from {current_user.email}',
        related_id=current_user.id,
        related_type='user'
    )
    db.session.add(notification)
    
    db.session.commit()
    return jsonify({'message': 'Message sent successfully'})

# ================= GROUP ROUTES =================

@app.route("/groups")
@login_required
def groups():
    # Get user's groups
    user_groups = Group.query.join(GroupMember).filter(GroupMember.user_id == current_user.id).all()
    
    # Get all public groups
    public_groups = Group.query.filter_by(is_private=False).all()
    
    return render_template("groups.html", groups=user_groups, public_groups=public_groups)

@app.route("/create_group", methods=["GET", "POST"])
@login_required
def create_group():
    if request.method == "POST":
        name = request.form.get('name')
        description = request.form.get('description')
        is_private = request.form.get('is_private') == 'on'
        
        group = Group(
            name=name,
            description=description,
            creator_id=current_user.id,
            is_private=is_private
        )
        db.session.add(group)
        db.session.flush()  # Get the group ID
        
        # Add creator as admin
        member = GroupMember(
            group_id=group.id,
            user_id=current_user.id,
            role='admin'
        )
        db.session.add(member)
        db.session.commit()
        
        flash('Group created successfully!', 'success')
        return redirect(url_for('group_detail', group_id=group.id))
    
    return render_template("create_group.html")

@app.route("/group/<int:group_id>")
@login_required
def group_detail(group_id):
    group = Group.query.get_or_404(group_id)
    
    # Check if user is member
    membership = GroupMember.query.filter_by(group_id=group_id, user_id=current_user.id).first()
    if not membership:
        flash('You are not a member of this group', 'error')
        return redirect(url_for('groups'))
    
    posts = GroupPost.query.filter_by(group_id=group_id).order_by(GroupPost.created_at.desc()).all()
    members = GroupMember.query.filter_by(group_id=group_id).all()
    
    return render_template("group_detail.html", group=group, posts=posts, members=members, membership=membership)

@app.route("/group/<int:group_id>/preview")
@login_required
def group_preview(group_id):
    group = Group.query.get_or_404(group_id)
    
    # Check if user is already a member - if so, redirect to full view
    membership = GroupMember.query.filter_by(group_id=group_id, user_id=current_user.id).first()
    if membership:
        return redirect(url_for('group_detail', group_id=group_id))
    
    # Only allow preview for public groups
    if group.is_private:
        flash('This is a private group. You need an invitation to view it.', 'error')
        return redirect(url_for('groups'))
    
    # Get limited information for preview
    member_count = GroupMember.query.filter_by(group_id=group_id).count()
    # Show only last 3 posts as preview
    preview_posts = GroupPost.query.filter_by(group_id=group_id).order_by(GroupPost.created_at.desc()).limit(3).all()
    # Show first 5 members
    preview_members = GroupMember.query.filter_by(group_id=group_id).limit(5).all()
    
    return render_template("group_preview.html", group=group, preview_posts=preview_posts, 
                          preview_members=preview_members, member_count=member_count)

@app.route("/group/<int:group_id>/join", methods=["POST"])
@login_required
def join_group(group_id):
    group = Group.query.get_or_404(group_id)
    
    # Check if already a member
    existing_member = GroupMember.query.filter_by(group_id=group_id, user_id=current_user.id).first()
    if existing_member:
        return jsonify({'error': 'You are already a member of this group'}), 400
    
    # Only allow joining public groups directly
    if group.is_private:
        return jsonify({'error': 'This is a private group. You need an invitation to join.'}), 403
    
    # Add user as member
    member = GroupMember(
        group_id=group_id,
        user_id=current_user.id,
        role='member'
    )
    db.session.add(member)
    
    # Create notification for group creator
    notification = Notification(
        user_id=group.creator_id,
        type='group_member_joined',
        title='New Member Joined',
        content=f'{current_user.email} joined your group "{group.name}"',
        related_id=group_id,
        related_type='group'
    )
    db.session.add(notification)
    
    db.session.commit()
    
    return jsonify({'message': 'Successfully joined the group!', 'redirect': url_for('group_detail', group_id=group_id)})

@app.route("/group/<int:group_id>/invite", methods=["POST"])
@login_required
def invite_to_group(group_id):
    group = Group.query.get_or_404(group_id)
    
    # Check if user is admin or moderator
    membership = GroupMember.query.filter_by(group_id=group_id, user_id=current_user.id).first()
    if not membership or membership.role not in ['admin', 'moderator']:
        return jsonify({'error': 'Unauthorized'}), 403
    
    invitee_email = request.json.get('email')
    invitee = User.query.filter_by(email=invitee_email).first()
    
    if not invitee:
        return jsonify({'error': 'User not found'}), 404
    
    # Check if already invited or member
    existing_invitation = GroupInvitation.query.filter_by(group_id=group_id, invitee_id=invitee.id).first()
    existing_member = GroupMember.query.filter_by(group_id=group_id, user_id=invitee.id).first()
    
    if existing_invitation or existing_member:
        return jsonify({'error': 'User already invited or is a member'}), 400
    
    invitation = GroupInvitation(
        group_id=group_id,
        inviter_id=current_user.id,
        invitee_id=invitee.id
    )
    db.session.add(invitation)
    
    # Create notification
    notification = Notification(
        user_id=invitee.id,
        type='group_invite',
        title='Group Invitation',
        content=f'You have been invited to join the group "{group.name}"',
        related_id=group_id,
        related_type='group'
    )
    db.session.add(notification)
    
    db.session.commit()
    return jsonify({'message': 'Invitation sent successfully'})

@app.route("/group_invitation/<int:invitation_id>/<action>", methods=["POST"])
@login_required
def respond_group_invitation(invitation_id, action):
    invitation = GroupInvitation.query.get_or_404(invitation_id)
    
    if invitation.invitee_id != current_user.id:
        return jsonify({'error': 'Unauthorized'}), 403
    
    if action == 'accept':
        invitation.status = 'accepted'
        # Add user to group
        member = GroupMember(
            group_id=invitation.group_id,
            user_id=current_user.id,
            role='member'
        )
        db.session.add(member)
        
        # Create notification for inviter
        notification = Notification(
            user_id=invitation.inviter_id,
            type='group_invitation_accepted',
            title='Group Invitation Accepted',
            content=f'{current_user.email} accepted your invitation to join {invitation.group.name}',
            related_id=invitation.group_id,
            related_type='group'
        )
        db.session.add(notification)
    else:
        invitation.status = 'declined'
    
    db.session.commit()
    return jsonify({'message': f'Group invitation {action}ed successfully'})

@app.route("/group/<int:group_id>/post", methods=["POST"])
@login_required
def create_group_post(group_id):
    group = Group.query.get_or_404(group_id)
    
    # Check if user is member
    membership = GroupMember.query.filter_by(group_id=group_id, user_id=current_user.id).first()
    if not membership:
        return jsonify({'error': 'You are not a member of this group'}), 403
    
    title = request.form.get('title')
    content = request.form.get('content')
    
    post = GroupPost(
        title=title,
        content=content,
        group_id=group_id,
        author_id=current_user.id
    )
    db.session.add(post)
    db.session.commit()
    
    flash('Post created successfully!', 'success')
    return redirect(url_for('group_detail', group_id=group_id))

# ================= REPORTING ROUTES =================

@app.route("/report_user/<int:user_id>", methods=["GET", "POST"])
@login_required
def report_user(user_id):
    reported_user = User.query.get_or_404(user_id)
    
    if request.method == "POST":
        reason = request.form.get('reason')
        description = request.form.get('description')
        
        report = UserReport(
            reporter_id=current_user.id,
            reported_user_id=user_id,
            reason=reason,
            description=description
        )
        db.session.add(report)
        
        # Notify the reporter (confirmation)
        notification_to_reporter = Notification(
            user_id=current_user.id,
            type='report_submitted',
            title='Report Submitted',
            content=f'Your report against {reported_user.email} has been submitted successfully. Administrators will review it.'
        )
        db.session.add(notification_to_reporter)
        
        # Notify the reported user
        notification_to_reported = Notification(
            user_id=user_id,
            type='user_reported',
            title='You Have Been Reported',
            content=f'You have been reported for {reason}. The report is under review by administrators.'
        )
        db.session.add(notification_to_reported)
        
        # Notify all admins
        admins = User.query.filter_by(role='superadmin').all()
        for admin in admins:
            notification = Notification(
                user_id=admin.id,
                type='user_report',
                title='New User Report',
                content=f'User {current_user.email} reported {reported_user.email} for {reason}'
            )
            db.session.add(notification)
        
        db.session.commit()
        flash('Report submitted successfully. Admins have been notified.', 'success')
        return redirect(url_for('index'))
    
    return render_template("report_user.html", reported_user=reported_user)

@app.route("/report_post/<int:post_id>", methods=["GET", "POST"])
@login_required
def report_post(post_id):
    post = Post.query.get_or_404(post_id)
    
    if request.method == "POST":
        reason = request.form.get('reason')
        description = request.form.get('description')
        decision = request.form.get('decision')  # What action reporter suggests
        
        report = PostReport(
            reporter_id=current_user.id,
            post_id=post_id,
            reason=reason,
            description=description,
            decision=decision
        )
        db.session.add(report)
        
        # Notify the reporter (confirmation)
        notification_to_reporter = Notification(
            user_id=current_user.id,
            type='report_submitted',
            title='Report Submitted',
            content=f'Your report against post "{post.title}" has been submitted successfully. Administrators will review it.',
            related_id=post_id,
            related_type='post'
        )
        db.session.add(notification_to_reporter)
        
        # Notify the post author
        notification_to_author = Notification(
            user_id=post.user_id,
            type='post_reported',
            title='Your Post Has Been Reported',
            content=f'Your post "{post.title}" has been reported for {reason}. The report is under review by administrators.',
            related_id=post_id,
            related_type='post'
        )
        db.session.add(notification_to_author)
        
        # Notify all admins
        admins = User.query.filter_by(role='superadmin').all()
        for admin in admins:
            notification = Notification(
                user_id=admin.id,
                type='post_report',
                title='New Post Report',
                content=f'User {current_user.email} reported a post: "{post.title}" for {reason}'
            )
            db.session.add(notification)
        
        db.session.commit()
        flash('Post report submitted successfully. Admins have been notified.', 'success')
        return redirect(url_for('view_post', post_id=post_id))
    
    return render_template("report_post.html", post=post)

# ================= NOTIFICATION ROUTES =================

@app.route("/notifications")
@login_required
def notifications():
    user_notifications = Notification.query.filter_by(user_id=current_user.id).order_by(Notification.created_at.desc()).all()
    return render_template("notifications.html", notifications=user_notifications)

@app.route("/my_reports")
@login_required
def my_reports():
    # Get all reports against the current user
    user_reports = UserReport.query.filter_by(reported_user_id=current_user.id).order_by(UserReport.created_at.desc()).all()
    
    # Get all reports against posts by the current user
    user_post_ids = [post.id for post in Post.query.filter_by(user_id=current_user.id).all()]
    post_reports = PostReport.query.filter(PostReport.post_id.in_(user_post_ids)).order_by(PostReport.created_at.desc()).all()
    
    return render_template("my_reports.html", user_reports=user_reports, post_reports=post_reports)

@app.route("/mark_notification_read/<int:notification_id>", methods=["POST"])
@login_required
def mark_notification_read(notification_id):
    notification = Notification.query.get_or_404(notification_id)
    
    if notification.user_id != current_user.id:
        return jsonify({'error': 'Unauthorized'}), 403
    
    notification.is_read = True
    db.session.commit()
    
    return jsonify({'message': 'Notification marked as read'})

@app.route("/notification/<int:notification_id>/click")
@login_required
def notification_click(notification_id):
    notification = Notification.query.get_or_404(notification_id)
    
    if notification.user_id != current_user.id:
        flash('Unauthorized', 'error')
        return redirect(url_for('notifications'))
    
    # Mark as read when clicked
    notification.is_read = True
    db.session.commit()
    
    # Redirect based on notification type
    if notification.type == 'friend_request':
        # Redirect to friends page to see pending requests
        return redirect(url_for('friends'))
    
    elif notification.type == 'friend_request_accepted':
        # Redirect to profile of the user who accepted
        if notification.related_id:
            return redirect(url_for('view_user_profile', user_id=notification.related_id))
        return redirect(url_for('friends'))
    
    elif notification.type == 'message':
        # Redirect to conversation with the sender
        if notification.related_id:
            return redirect(url_for('conversation', user_id=notification.related_id))
        return redirect(url_for('messages'))
    
    elif notification.type == 'group_invite' or notification.type == 'group_invitation':
        # Redirect to group invitations page
        return redirect(url_for('group_invitations'))
    
    elif notification.type == 'group_invitation_accepted':
        # Redirect to the group
        if notification.related_id:
            return redirect(url_for('group_detail', group_id=notification.related_id))
        return redirect(url_for('groups'))
    
    elif notification.type == 'group_member_joined':
        # Redirect to the group
        if notification.related_id:
            return redirect(url_for('group_detail', group_id=notification.related_id))
        return redirect(url_for('groups'))
    
    elif notification.type in ['user_report', 'post_report', 'user_reported', 'post_reported', 'report_submitted']:
        # Redirect to my reports page
        return redirect(url_for('my_reports'))
    
    elif notification.type == 'post_reported':
        # Redirect to the reported post
        if notification.related_id:
            return redirect(url_for('view_post', post_id=notification.related_id))
        return redirect(url_for('my_reports'))
    
    else:
        # Default: stay on notifications page
        return redirect(url_for('notifications'))

# ================= SUPERADMIN ENHANCED ROUTES =================

@app.route("/superadmin/reports")
@login_required
@superadmin_required
def superadmin_reports():
    user_reports = UserReport.query.order_by(UserReport.created_at.desc()).all()
    post_reports = PostReport.query.order_by(PostReport.created_at.desc()).all()
    return render_template("superadmin/reports.html", user_reports=user_reports, post_reports=post_reports)

@app.route("/superadmin/reports/<int:report_id>/resolve", methods=["POST"])
@login_required
@superadmin_required
def resolve_report(report_id):
    report = UserReport.query.get_or_404(report_id)
    action = request.json.get('action')  # 'warn', 'block', 'dismiss'
    admin_notes = request.json.get('admin_notes', '')
    warning_message = request.json.get('warning_message', '')
    
    report.status = 'resolved'
    report.admin_notes = admin_notes
    report.reviewed_at = datetime.utcnow()
    
    reported_user = report.reported_user
    
    # Take action on the reported user
    if action == 'warn':
        reported_user.status = 'warned'
        reported_user.warning_count += 1
        reported_user.warning_message = warning_message or f"Warning #{reported_user.warning_count}: {report.reason}. Please follow our community guidelines."
        reported_user.last_warning_at = datetime.utcnow()
        
        # Create notification for the reported user
        notification = Notification(
            user_id=reported_user.id,
            type='warning',
            title='Account Warning',
            content=reported_user.warning_message
        )
        db.session.add(notification)
        
    elif action == 'block':
        reported_user.status = 'blocked'
        reported_user.warning_message = warning_message or f"Your account has been blocked due to: {report.reason}. Contact support for assistance."
        
        # Create notification for the reported user
        notification = Notification(
            user_id=reported_user.id,
            type='blocked',
            title='Account Blocked',
            content=reported_user.warning_message
        )
        db.session.add(notification)
    
    # Create notification for the reporter
    reporter_notification = Notification(
        user_id=report.reporter_id,
        type='report_resolved',
        title='Report Resolved',
        content=f'Your report against {report.reported_user.email} has been reviewed and action has been taken.'
    )
    db.session.add(reporter_notification)
    
    db.session.commit()
    return jsonify({'message': f'Report resolved successfully. User has been {action}ed.'})

@app.route("/superadmin/post_reports/<int:report_id>/resolve", methods=["POST"])
@login_required
@superadmin_required
def resolve_post_report(report_id):
    report = PostReport.query.get_or_404(report_id)
    action = request.json.get('action')  # 'remove', 'warn', 'dismiss'
    admin_notes = request.json.get('admin_notes', '')
    
    report.status = 'resolved'
    report.admin_notes = admin_notes
    report.reviewed_at = datetime.utcnow()
    
    # If action is to remove the post, delete it
    if action == 'remove':
        post = Post.query.get(report.post_id)
        if post:
            # Delete the file if it exists
            if post.file_path and os.path.exists(os.path.join(app.config['UPLOAD_FOLDER'], post.file_path)):
                os.remove(os.path.join(app.config['UPLOAD_FOLDER'], post.file_path))
            db.session.delete(post)
    
    # Create notification for the reporter
    notification = Notification(
        user_id=report.reporter_id,
        type='post_report_resolved',
        title='Post Report Resolved',
        content=f'Your report on a post has been {action}ed by admin'
    )
    db.session.add(notification)
    
    db.session.commit()
    return jsonify({'message': 'Post report resolved successfully'})

@app.route("/superadmin/users/<int:user_id>/suspend", methods=["POST"])
@login_required
def suspend_user(user_id):
    if current_user.role != 'superadmin':
        return jsonify({'error': 'Unauthorized'}), 403
    
    user = User.query.get_or_404(user_id)
    reason = request.json.get('reason', '')
    duration = request.json.get('duration', 'permanent')  # '1day', '1week', '1month', 'permanent'
    
    # Update user status
    user.status = 'suspended'
    db.session.commit()
    
    # Create notification for the user
    notification = Notification(
        user_id=user_id,
        type='account_suspended',
        title='Account Suspended',
        content=f'Your account has been suspended. Reason: {reason}'
    )
    db.session.add(notification)
    
    db.session.commit()
    return jsonify({'message': 'User suspended successfully'})

@app.route("/superadmin/users/<int:user_id>/activate", methods=["POST"])
@login_required
def activate_user(user_id):
    if current_user.role != 'superadmin':
        return jsonify({'error': 'Unauthorized'}), 403
    
    user = User.query.get_or_404(user_id)
    user.status = 'active'
    db.session.commit()
    
    # Create notification for the user
    notification = Notification(
        user_id=user_id,
        type='account_activated',
        title='Account Activated',
        content='Your account has been reactivated by admin'
    )
    db.session.add(notification)
    
    db.session.commit()
    return jsonify({'message': 'User activated successfully'})

@app.route("/superadmin/analytics")
@login_required
def superadmin_analytics():
    if current_user.role != 'superadmin':
        flash('Access denied', 'error')
        return redirect(url_for('index'))
    
    # Get analytics data
    total_users = User.query.count()
    active_users = User.query.filter_by(status='active').count()
    suspended_users = User.query.filter_by(status='suspended').count()
    total_posts = Post.query.count()
    total_groups = Group.query.count()
    total_reports = UserReport.query.count()
    pending_reports = UserReport.query.filter_by(status='pending').count()
    
    # Recent activity
    recent_posts = Post.query.order_by(Post.created_at.desc()).limit(5).all()
    recent_reports = UserReport.query.order_by(UserReport.created_at.desc()).limit(5).all()
    
    return render_template("superadmin/analytics.html", 
                         total_users=total_users,
                         active_users=active_users,
                         suspended_users=suspended_users,
                         total_posts=total_posts,
                         total_groups=total_groups,
                         total_reports=total_reports,
                         pending_reports=pending_reports,
                         recent_posts=recent_posts,
                         recent_reports=recent_reports)

# ================= USER PROFILE ROUTES =================

@app.route("/profile")
@login_required
def profile():
    # Get user's posts, friends, and groups
    user_posts = Post.query.filter_by(user_id=current_user.id).order_by(Post.created_at.desc()).all()
    
    # Get accepted friendships
    friendships = Friendship.query.filter(
        ((Friendship.requester_id == current_user.id) | (Friendship.addressee_id == current_user.id)),
        Friendship.status == 'accepted'
    ).all()
    
    friends = []
    for friendship in friendships:
        if friendship.requester_id == current_user.id:
            friends.append(friendship.addressee)
        else:
            friends.append(friendship.requester)
    
    # Get user's groups
    user_groups = Group.query.join(GroupMember).filter(GroupMember.user_id == current_user.id).all()
    
    # Get user's shared posts
    shared_posts = SharedPost.query.filter_by(user_id=current_user.id).order_by(SharedPost.created_at.desc()).all()
    
    return render_template("profile.html", 
                         user_posts=user_posts, 
                         friends=friends, 
                         user_groups=user_groups,
                         shared_posts=shared_posts)

@app.route("/profile/edit", methods=["GET", "POST"])
@login_required
def edit_profile():
    if request.method == "POST":
        # Update profile information (no password required for basic info)
        update_type = request.form.get('update_type', 'profile')
        
        if update_type == 'profile':
            # Update display name, bio, and profile picture
            current_user.display_name = request.form.get('display_name', '').strip() or None
            current_user.bio = request.form.get('bio', '').strip() or None
            
            # Handle profile picture upload
            if 'profile_picture' in request.files:
                file = request.files['profile_picture']
                if file and file.filename:
                    filename = secure_filename(file.filename)
                    # Add timestamp to make filename unique
                    import time
                    filename = f"{int(time.time())}_{filename}"
                    filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
                    file.save(filepath)
                    # Delete old profile picture if exists
                    if current_user.profile_picture:
                        old_path = os.path.join(app.config['UPLOAD_FOLDER'], current_user.profile_picture)
                        if os.path.exists(old_path):
                            os.remove(old_path)
                    current_user.profile_picture = filename
            
            db.session.commit()
            flash('Profile updated successfully!', 'success')
            return redirect(url_for('profile'))
        
        elif update_type == 'security':
            # Verify current password for security updates
            current_password = request.form.get('current_password')
            if not bcrypt.check_password_hash(current_user.password, current_password):
                flash('Current password is incorrect', 'error')
                return redirect(url_for('edit_profile'))
            
            # Update email
            new_email = request.form.get('email', '').strip()
            if new_email and new_email != current_user.email:
                # Check if email already exists
                existing_user = User.query.filter_by(email=new_email).first()
                if existing_user:
                    flash('Email already in use', 'error')
                    return redirect(url_for('edit_profile'))
                current_user.email = new_email
            
            # Update password if provided
            new_password = request.form.get('new_password')
            if new_password:
                current_user.password = bcrypt.generate_password_hash(new_password).decode('utf-8')
            
            db.session.commit()
            flash('Security settings updated successfully!', 'success')
            return redirect(url_for('profile'))
    
    return render_template("edit_profile.html")

@app.route("/user/<int:user_id>")
@login_required
def view_user_profile(user_id):
    user = User.query.get_or_404(user_id)
    
    # Get user's public posts
    user_posts = Post.query.filter_by(user_id=user_id).order_by(Post.created_at.desc()).limit(10).all()
    
    # Check if current user is friends with this user
    friendship = Friendship.query.filter(
        ((Friendship.requester_id == current_user.id) & (Friendship.addressee_id == user_id)) |
        ((Friendship.requester_id == user_id) & (Friendship.addressee_id == current_user.id)),
        Friendship.status == 'accepted'
    ).first()
    
    is_friend = friendship is not None
    
    return render_template("user_profile.html", 
                         user=user, 
                         user_posts=user_posts, 
                         is_friend=is_friend)

@app.route("/friends")
@login_required
def friends():
    # Get accepted friendships
    friendships = Friendship.query.filter(
        ((Friendship.requester_id == current_user.id) | (Friendship.addressee_id == current_user.id)),
        Friendship.status == 'accepted'
    ).all()
    
    friends = []
    for friendship in friendships:
        if friendship.requester_id == current_user.id:
            friends.append(friendship.addressee)
        else:
            friends.append(friendship.requester)
    
    return render_template("friends.html", friends=friends)

# ================= GROUP INVITATION ROUTES =================

@app.route("/group_invitations")
@login_required
def group_invitations():
    # Get pending group invitations for current user
    invitations = GroupInvitation.query.filter_by(invitee_id=current_user.id, status='pending').all()
    return render_template("group_invitations.html", invitations=invitations)


# ================= SEARCH AND DISCOVERY ROUTES =================

@app.route("/search")
@login_required
def search():
    query = request.args.get('q', '')
    search_type = request.args.get('type', 'all')  # all, users, posts, groups
    
    results = {
        'users': [],
        'posts': [],
        'groups': []
    }
    
    if search_type in ['all', 'users']:
        results['users'] = User.query.filter(User.email.contains(query)).limit(10).all()
    
    if search_type in ['all', 'posts']:
        results['posts'] = Post.query.filter(
            (Post.title.contains(query)) | (Post.description.contains(query))
        ).limit(10).all()
    
    if search_type in ['all', 'groups']:
        results['groups'] = Group.query.filter(
            (Group.name.contains(query)) | (Group.description.contains(query))
        ).limit(10).all()
    
    return render_template("search_results.html", 
                         query=query, 
                         search_type=search_type, 
                         results=results)

# ================= SUPERADMIN ROUTES =================

@app.route("/superadmin")
@login_required
@superadmin_required
def admin_dashboard():
    from sqlalchemy import func
    total_users = User.query.count()
    total_posts = Post.query.count()
    
    # Top Category Calculation
    category_counts = db.session.query(Post.category, func.count(Post.category)).group_by(Post.category).order_by(func.count(Post.category).desc()).first()
    top_category = category_counts[0] if category_counts else "N/A"

    most_viewed_posts = Post.query.order_by(Post.views.desc()).limit(5).all()

    return render_template("superadmin/dashboard.html", 
                           total_users=total_users, 
                           total_posts=total_posts,
                           top_category=top_category,
                           most_viewed_posts=most_viewed_posts)

@app.route("/superadmin/users")
@login_required
@superadmin_required
def manage_users():
    users = User.query.all()
    return render_template("superadmin/manage_users.html", users=users)

@app.route("/superadmin/users/update/<int:user_id>", methods=["POST"])
@login_required
@superadmin_required
def update_user(user_id):
    user = User.query.get_or_404(user_id)
    new_role = request.form.get('role')
    new_status = request.form.get('status')
    if user.email == "superadmin@driftlens.com": # Prevent changing the main superadmin
        flash("Cannot change the primary superadmin account.", "danger")
        return redirect(url_for('manage_users'))
    if new_role:
        user.role = new_role
    if new_status:
        user.status = new_status
    db.session.commit()
    flash(f"User {user.email} has been updated.", "success")
    return redirect(url_for('manage_users'))

@app.route("/superadmin/posts")
@login_required
@superadmin_required
def manage_posts():
    posts = Post.query.order_by(Post.id.desc()).all()
    return render_template("superadmin/manage_posts.html", posts=posts)

@app.route("/superadmin/posts/delete/<int:post_id>", methods=["POST"])
@login_required
@superadmin_required
def delete_post(post_id):
    post = Post.query.get_or_404(post_id)
    # Optional: Delete the actual file from the server
    if post.file_path and os.path.exists(post.file_path):
        os.remove(post.file_path)
    db.session.delete(post)
    db.session.commit()
    flash("Post has been deleted successfully.", "success")
    return redirect(url_for('manage_posts'))


# ================= COMMANDS =================
@app.cli.command("create-superadmin")
@click.argument("password")
def create_superadmin(password):
    """Creates the superadmin user."""
    email = "superadmin@driftlens.com"
    if User.query.filter_by(email=email).first():
        print("Superadmin user already exists.")
        return
    hashed_pw = bcrypt.generate_password_hash(password).decode('utf-8')
    admin = User(email=email, password=hashed_pw, role='superadmin', status='active')
    db.session.add(admin)
    db.session.commit()
    print("Superadmin user created successfully.")

# ================= ERROR HANDLERS =================

@app.errorhandler(404)
def not_found_error(error):
    return render_template('error.html', error_code=404, error_message='Page not found'), 404

@app.errorhandler(500)
def internal_error(error):
    db.session.rollback()
    return render_template('error.html', error_code=500, error_message='Internal server error'), 500

@app.errorhandler(403)
def forbidden_error(error):
    return render_template('error.html', error_code=403, error_message='Access forbidden'), 403

# ================= RUN =================
if __name__ == "__main__":
    with app.app_context():
        db.create_all()
    app.run(debug=True)
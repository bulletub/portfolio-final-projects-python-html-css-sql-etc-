# 🐾 Pet Pantry - E-Commerce Platform

A comprehensive full-stack e-commerce platform for pet products, built with PHP and MySQL. This project was developed as part of a 4-month portfolio development period, showcasing server-side development, payment processing, and complex business logic implementation.

## 🎯 What I Built

I developed a complete e-commerce platform from scratch, including:

- **Product Management System**: Full product catalog with categories, subcategories, search, and filtering
- **Shopping Cart & Checkout**: Complete cart functionality with secure checkout process
- **Payment Processing**: Integrated payment verification and transaction management
- **Order Management**: Order tracking, order history, and order status updates
- **User Authentication**: Secure login, registration, email verification, and 2FA support
- **Admin Dashboard**: Comprehensive admin panel for managing products, orders, users, inventory, and settings
- **Review System**: Product reviews and ratings with moderation
- **Support Chat**: Real-time customer support chat system
- **Inventory Management**: Stock tracking, low stock notifications, and inventory auditing
- **Promotions & Discounts**: Promotional codes and discount management
- **Invoice Generation**: PDF invoice generation with QR codes
- **Email Notifications**: Automated email system for orders, confirmations, and notifications
- **Wishlist**: Save favorite products for later
- **Analytics**: Sales analytics, profit tracking, and top seller reports
- **CMS System**: Content management for homepage sections
- **Multi-currency Support**: Global currency handling
- **Responsive Design**: Mobile-friendly interface

## 🛠️ Technologies Used

- **PHP** - Server-side programming language
- **MySQL** - Relational database management
- **HTML5 & CSS3** - Frontend markup and styling
- **JavaScript** - Client-side interactivity
- **jQuery** - DOM manipulation and AJAX
- **PHPMailer** - Email sending functionality
- **Dompdf** - PDF generation for invoices
- **PHP QR Code** - QR code generation
- **PDO** - Database abstraction layer
- **Session Management** - User authentication and state management
- **RESTful APIs** - Payment and shipping API integration

## ⏱️ Development Time

This project was developed as part of a **4-month portfolio development period**, demonstrating full-stack PHP development, database design, payment integration, and complex business logic implementation.

## ✨ Key Features

### Customer Features
- 🛍️ **Product Browsing**: Browse products by category with search and filters
- 🛒 **Shopping Cart**: Add/remove items, quantity management, cart persistence
- 💳 **Secure Checkout**: Multi-step checkout with payment verification
- 📦 **Order Tracking**: Track orders from placement to delivery
- ⭐ **Product Reviews**: Leave reviews and ratings for products
- 💝 **Wishlist**: Save favorite products
- 💬 **Support Chat**: Real-time customer support
- 📧 **Email Notifications**: Order confirmations and updates
- 🔐 **Account Management**: Profile settings, order history, address management

### Admin Features
- 📊 **Dashboard**: Sales analytics, profit tracking, and statistics
- 📦 **Inventory Management**: Stock tracking, low stock alerts, audit logs
- 👥 **User Management**: View and manage customer accounts
- 📝 **Product Management**: Add, edit, and manage products
- 💰 **Payment Verification**: Verify and process payments
- 📋 **Order Management**: Process orders, update status, handle refunds
- ⭐ **Review Moderation**: Approve or reject product reviews
- 💬 **Support Chat Management**: Handle customer support requests
- ⚙️ **Settings**: Configure site settings, currency, and preferences
- 📄 **CMS**: Manage homepage content and sections

### Technical Features
- 🔒 **Secure Authentication**: Password hashing, email verification, 2FA
- 💳 **Payment Integration**: Payment gateway integration and verification
- 📄 **PDF Generation**: Automated invoice generation
- 📱 **QR Codes**: QR code generation for invoices and tracking
- 📧 **Email System**: Automated email notifications
- 🔍 **Search Functionality**: Product search and filtering
- 📊 **Analytics**: Sales reports and profit tracking
- 🌍 **Multi-currency**: Support for different currencies
- 📱 **Responsive Design**: Mobile-optimized interface

## 💻 Local Development

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependencies)

### Setup

1. **Import Database:**
   ```bash
   mysql -u root -p < u296524640_pet_pantry.sql
   ```

2. **Configure Database:**
   - Edit `database.php` with your database credentials
   - Update connection settings

3. **Install Dependencies:**
   ```bash
   composer install
   ```

4. **Configure Email (Optional):**
   - Edit `email_helper.php` with your SMTP settings
   - Configure PHPMailer settings

5. **Set Permissions:**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/products/
   chmod 755 uploads/reviews/
   ```

6. **Access the Application:**
   ```
   http://localhost/pet-pantry/
   ```

## 🗄️ Database Structure

The application uses MySQL with tables for:
- Users and authentication
- Products and categories
- Orders and transactions
- Reviews and ratings
- Inventory and stock
- Payments and refunds
- Support chat messages
- Notifications
- CMS content
- Settings and configurations

## 🔐 Security Features

- Password hashing (bcrypt)
- SQL injection prevention (PDO prepared statements)
- XSS protection
- CSRF token validation
- Session security
- Email verification
- Two-factor authentication (2FA)
- Admin access control
- Payment verification

## 📦 Key Files

- `index.php` - Homepage
- `shop.php` - Product catalog
- `cart.php` - Shopping cart
- `checkout.php` - Checkout process
- `adminpanel.php` - Admin dashboard
- `database.php` - Database connection
- `email_helper.php` - Email functionality
- `invoice_pdf.php` - PDF invoice generation

## 🚀 Deployment

For production deployment:
1. Use a production web server (Apache/Nginx)
2. Configure SSL/HTTPS
3. Set up production database
4. Configure environment variables
5. Set proper file permissions
6. Enable error logging (disable display_errors)
7. Configure email SMTP settings
8. Set up payment gateway credentials

## 📚 What I Learned

- Building full-stack PHP applications
- MySQL database design and optimization
- Payment gateway integration
- PDF generation and document processing
- Email system implementation
- Admin panel development
- Inventory management systems
- Real-time chat functionality
- Security best practices
- Session management
- File upload handling
- QR code generation
- Multi-currency support

---

**Developed in 4 months** | **PHP + MySQL** | **Full-Stack E-Commerce Platform**


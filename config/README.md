# Smart Service Finder - Complete PHP Web Application

A production-level service marketplace web application built with Core PHP, MySQL, HTML, CSS, and JavaScript. Similar to UrbanClap, this platform connects customers with local service providers.

## 🎯 Features

### User Roles
- **Customers** - Browse services, book appointments, manage bookings, leave reviews
- **Service Providers** - Add services, manage bookings, accept/reject requests
- **Admin** - Manage users, providers, bookings, view statistics

### Core Functionality
- ✅ Complete authentication system (login, register, logout)
- ✅ Role-based access control
- ✅ Service management (add, edit, delete services)
- ✅ Booking system with status tracking
- ✅ Review and rating system
- ✅ Search and filter functionality
- ✅ Responsive modern UI design
- ✅ Admin dashboard with statistics
- ✅ Provider dashboard with earnings tracking
- ✅ Customer dashboard with booking history

## 📁 Project Structure

```
smart_service/
├── config/
│   └── db.php                    # Database configuration
├── auth/
│   ├── login.php                 # Login page
│   ├── register.php              # Registration page
│   └── logout.php                # Logout handler
├── admin/
│   ├── dashboard.php             # Admin dashboard
│   ├── manage_users.php          # Manage all users
│   ├── manage_providers.php      # Manage service providers
│   ├── manage_bookings.php       # Manage all bookings
│   └── get_provider_details.php  # AJAX provider details
├── provider/
│   ├── dashboard.php             # Provider dashboard
│   ├── add_service.php           # Add new service
│   ├── manage_bookings.php       # Manage provider bookings
│   └── my_services.php           # Manage provider services
├── user/
│   ├── dashboard.php             # User dashboard
│   ├── services.php              # Browse services
│   ├── book_service.php          # Book a service
│   └── my_bookings.php           # User booking history
├── assets/
│   ├── css/
│   │   └── style.css             # Complete CSS styling
│   └── js/
│       └── main.js               # JavaScript functionality
├── index.php                     # Homepage
├── database.sql                  # Database schema
└── README.md                     # This file
```

## 🗄️ Database Design

### Tables
1. **users** - User information and authentication
2. **services** - Service listings by providers
3. **bookings** - Booking records and status
4. **reviews** - Customer reviews and ratings

### Key Features
- Plain text passwords (as requested)
- Foreign key constraints for data integrity
- Proper indexing for performance
- Role-based user management

## 🚀 Installation Instructions

### Prerequisites
- XAMPP (or similar LAMP/WAMP stack)
- PHP 7.4+ 
- MySQL 5.7+
- Modern web browser

### Step 1: Setup Database
1. Start XAMPP and launch Apache & MySQL
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database named `smart_service`
4. Import the `database.sql` file:
   - Click on the `smart_service` database
   - Click "Import" tab
   - Choose the `database.sql` file
   - Click "Go"

### Step 2: Deploy Application
1. Copy the entire `smart_service` folder to:
   ```
   C:\xampp\htdocs\smart_service\
   ```
2. Ensure all files are in the correct directory structure

### Step 3: Access Application
1. Open your web browser
2. Navigate to: `http://localhost/smart_service/`
3. The application should load with the homepage

## 🔐 Default Login Credentials

### Admin Account
- **Email:** admin@smartService.com
- **Password:** admin123

### Sample Users
- **Customer:** john@example.com / user123
- **Customer:** jane@example.com / user123  
- **Provider:** mike@example.com / provider123
- **Provider:** sarah@example.com / provider123

## 🎨 UI Features

### Modern Design
- Clean, professional interface
- Responsive design for all devices
- Modern CSS with variables and grid layouts
- Smooth animations and transitions
- Color-coded status badges
- Interactive hover effects

### User Experience
- Intuitive navigation
- Real-time form validation
- Search and filter functionality
- Modal dialogs for confirmations
- Loading states and error handling
- Mobile-friendly sidebar navigation

## 🛠️ Technical Features

### Backend
- Procedural PHP with OOP elements
- MySQL database with mysqli
- Secure SQL queries
- Session management
- Error handling and validation
- File organization and modularity

### Frontend
- Modern CSS3 with custom properties
- Vanilla JavaScript (no frameworks)
- AJAX for dynamic content
- Form validation
- Responsive grid layouts
- Cross-browser compatibility

### Security
- Input sanitization
- SQL injection prevention
- Session-based authentication
- Role-based access control
- XSS protection

## 📱 Responsive Design

The application is fully responsive and works on:
- Desktop computers (1200px+)
- Tablets (768px - 1199px)  
- Mobile phones (320px - 767px)

## 🔄 Workflow

### Customer Journey
1. Register/Login
2. Browse services by category
3. Search and filter services
4. View service details
5. Book service with date/address
6. Track booking status
7. Leave review after completion

### Provider Journey
1. Register as provider
2. Add services with details
3. Receive booking notifications
4. Accept/reject booking requests
5. Manage service schedule
6. View earnings and statistics

### Admin Journey
1. Login with admin credentials
2. View dashboard statistics
3. Manage all users and providers
4. Monitor all bookings
5. Handle disputes and issues

## 🎯 Key Pages Overview

### Public Pages
- **index.php** - Landing page with hero section and featured services

### Authentication
- **auth/login.php** - Universal login for all roles
- **auth/register.php** - Registration with role selection
- **auth/logout.php** - Session destruction

### Admin Section
- **admin/dashboard.php** - Statistics and overview
- **admin/manage_users.php** - User management
- **admin/manage_providers.php** - Provider oversight
- **admin/manage_bookings.php** - Booking administration

### Provider Section  
- **provider/dashboard.php** - Provider statistics
- **provider/add_service.php** - Service creation
- **provider/manage_bookings.php** - Booking management
- **provider/my_services.php** - Service listings

### Customer Section
- **user/dashboard.php** - Customer overview
- **user/services.php** - Service browsing
- **user/book_service.php** - Service booking
- **user/my_bookings.php** - Booking history

## 🚀 Production Considerations

### For Production Deployment
1. Change database credentials in `config/db.php`
2. Implement password hashing (remove plain text requirement)
3. Add email verification for registration
4. Implement payment gateway integration
5. Add file upload for service images
6. Setup proper error logging
7. Configure HTTPS/SSL certificate
8. Optimize database with proper indexing

### Performance Optimizations
- Database query optimization
- Image compression and CDN
- CSS/JS minification
- Caching implementation
- Lazy loading for images

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check database name in `config/db.php`
   - Verify database was imported correctly

2. **404 Not Found**
   - Ensure files are in `htdocs/smart_service/`
   - Check Apache is running
   - Verify URL: `http://localhost/smart_service/`

3. **Permission Denied**
   - Check folder permissions
   - Ensure XAMPP has proper access rights

4. **Blank White Page**
   - Check PHP error logs
   - Ensure all files are uploaded
   - Verify database connection

## 📞 Support

For issues and questions:
1. Check the troubleshooting section above
2. Verify all installation steps were followed
3. Ensure XAMPP components are running
4. Review database setup in phpMyAdmin

## 🎉 Enjoy!

Your Smart Service Finder application is now ready to use! The platform provides a complete service marketplace experience with modern UI, full functionality, and professional features.

**Built with ❤️ using Core PHP, MySQL, HTML, CSS, and JavaScript**

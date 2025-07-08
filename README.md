# HireSmart Backend System

A comprehensive backend system for connecting job seekers with employers, built with Laravel 12, PostgreSQL, and Redis.

## 🏗️ Project Structure

```
hire-smart-backend/
├── docker/                                    # Docker configuration
│   └── nginx/
│       └── nginx.conf                        # Nginx web server config
├── html/hiresmart-backend.rokanchowdhuryonick.com/  # Laravel Application Root
│   ├── app/                                  # Application Code
│   │   ├── Console/                          # Artisan Commands
│   │   │   └── Commands/                     # Custom Commands
│   │   │       └── RemoveUnverifiedUsers.php # User cleanup command
│   │   ├── Http/                             # HTTP Layer
│   │   │   ├── Controllers/                  # Controllers (organized by role)
│   │   │   │   └── API/
│   │   │   │       ├── Auth/                 # Authentication endpoints
│   │   │   │       │   ├── AuthController.php
│   │   │   │       │   └── PasswordResetController.php
│   │   │   │       ├── Admin/                # Admin-only endpoints
│   │   │   │       │   └── AdminController.php
│   │   │   │       ├── Employer/             # Employer-specific endpoints
│   │   │   │       │   ├── JobController.php
│   │   │   │       │   └── ApplicationController.php
│   │   │   │       └── Candidate/            # Candidate-specific endpoints
│   │   │   │           ├── JobController.php
│   │   │   │           └── ApplicationController.php
│   │   │   ├── Middleware/                   # Custom Middleware
│   │   │   │   ├── Authenticate.php          # JWT authentication
│   │   │   │   └── CheckRole.php             # Role-based access control
│   │   │   ├── Requests/                     # Form Request Validation
│   │   │   │   ├── Auth/                     # Authentication requests
│   │   │   │   ├── Job/                      # Job management requests
│   │   │   │   ├── Application/              # Application requests
│   │   │   │   └── User/                     # User profile requests
│   │   │   └── Resources/                    # API Resources
│   │   │       ├── AuthResource.php         # Authentication responses
│   │   │       ├── JobResource.php          # Job listing responses
│   │   │       ├── ApplicationResource.php  # Application responses
│   │   │       └── UserResource.php         # User profile responses
│   │   ├── Jobs/                             # Background Jobs
│   │   │   ├── MatchCandidatesJob.php        # Job matching algorithm
│   │   │   ├── ArchiveOldJobsJob.php         # Job archiving
│   │   │   └── CleanupDataJob.php            # Data cleanup
│   │   ├── Models/                           # Eloquent Models
│   │   │   ├── User.php                      # User model with role-based logic
│   │   │   ├── UserProfile.php               # Candidate profiles
│   │   │   ├── Company.php                   # Employer companies
│   │   │   ├── JobPosting.php                # Job listings
│   │   │   ├── Application.php               # Job applications
│   │   │   ├── Skill.php                     # Skills master data
│   │   │   ├── JobMatch.php                  # AI matching results
│   │   │   ├── Notification.php              # System notifications
│   │   │   └── Location Models/              # Hierarchical location system
│   │   │       ├── Country.php
│   │   │       ├── State.php
│   │   │       ├── City.php
│   │   │       └── Area.php
│   │   ├── Services/                         # Business Logic Layer
│   │   │   ├── AuthService.php               # Authentication business logic
│   │   │   ├── JobService.php                # Job management logic
│   │   │   ├── ApplicationService.php        # Application workflow
│   │   │   ├── UserService.php               # User profile management
│   │   │   └── MatchingService.php           # Job-candidate matching
│   │   ├── Notifications/                    # Email/Push Notifications
│   │   └── Providers/                        # Service Providers
│   │       └── AppServiceProvider.php        # Application configuration
│   ├── config/                               # Configuration Files
│   │   ├── database.php                      # Database connections
│   │   ├── auth.php                          # Authentication settings
│   │   ├── jwt.php                           # JWT configuration
│   │   ├── queue.php                         # Queue/job settings
│   │   └── cache.php                         # Redis caching config
│   ├── database/                             # Database Layer
│   │   ├── migrations/                       # Database schema
│   │   │   ├── 0001_01_01_000000_create_users_table.php
│   │   │   ├── 0001_01_01_000010_create_user_profiles_table.php
│   │   │   ├── 0001_01_01_000011_create_companies_table.php
│   │   │   ├── 0001_01_01_000012_create_company_job_postings_table.php
│   │   │   ├── 0001_01_01_000013_create_applications_table.php
│   │   │   └── [Location & Skills tables...]
│   │   ├── seeders/                          # Data Seeders
│   │   │   ├── DatabaseSeeder.php            # Main seeder orchestrator
│   │   │   ├── AdminSeeder.php               # Admin user creation
│   │   │   └── LocationSeeder.php            # Location hierarchy data
│   │   └── factories/                        # Model Factories
│   │       └── UserFactory.php               # User test data generation
│   ├── routes/                               # API Routes
│   │   ├── api.php                           # Main API routes (47 endpoints)
│   │   ├── console.php                       # Artisan commands
│   │   └── web.php                           # Web routes (minimal)
│   ├── storage/                              # File Storage
│   │   ├── app/                              # Application files
│   │   ├── framework/                        # Laravel framework cache
│   │   └── logs/                             # Application logs
│   ├── tests/                                # Test Suite
│   └── public/                               # Web Server Document Root
├── docker-compose.yml                        # Multi-service Docker setup
├── Dockerfile                                # Laravel app container
├── ERD.md                                    # Database schema documentation
├── POSTMAN_GUIDE.md                          # API testing guide
├── HireSmart_API_Collection.postman_collection.json  # Postman collection (47 endpoints)
├── HireSmart_Environment.postman_environment.json    # Postman environment
└── README.md                                 # This documentation
```

## 🎯 Architecture & Design Decisions

### **Service Layer Pattern (No Repository Pattern)**
**Decision**: I chose the **Service Layer Pattern** without Repository abstraction to keep the architecture simple and maintainable.

**Why I am Not using Repository Pattern?**
- Avoided unnecessary abstraction layers that don't add value in this context
- Laravel's Eloquent ORM already provides excellent query abstraction
- Reduced complexity and faster development while maintaining testability
- Direct model usage with business logic encapsulated in services

**Service Layer Benefits**:
- **Controllers**: Handle only HTTP requests/responses and routing
- **Services**: Contain all business logic and orchestration  
- **Models**: Handle data access, relationships, and domain logic
- **Clean separation** between HTTP layer and business logic

### **Performance Optimizations & N+1 Query Solutions**

#### **1. Database Query Optimization**
I implemented several **N+1 query fixes** throughout the application:

**UserService.php** - User Statistics:
```php
// BEFORE: N+1 queries for each user
$stats['recent_matches'] = $user->jobMatches()->recent(7)->count();

// AFTER: Single optimized count query  
$stats['recent_matches'] = $user->jobMatches()->recent(7)->count();
```

**UserResource.php** - Application Stats:
```php
// BEFORE: Multiple separate count queries
$total = $this->applications()->count();
$pending = $this->applications()->where('status', 'pending')->count();
// ... more queries

// AFTER: Single aggregated query with conditional counting
$stats = $this->applications()
    ->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as reviewed,
        // ... more conditional counts
    ', ['pending', 'reviewed', ...])
    ->first();
```

**Database Seeders** - Location Hierarchy:
```php
// BEFORE: Individual insert queries (N+1)
foreach ($locations as $location) {
    Location::create($location); // Individual INSERT
}

// AFTER: Batch processing with chunking
Location::upsert($locations, ['name'], ['updated_at']);
```

#### **2. Strategic Eager Loading**
Implemented consistent eager loading patterns:
```php
// Jobs with all required relationships loaded at once
$jobs = JobPosting::with([
    'company:id,name,logo_path',
    'employer:id,name', 
    'skills:id,name',
    'country:id,name',
    'state:id,name', 
    'city:id,name'
])->active()->paginate(15);
```

#### **3. Database Indexing Strategy**
**Compound Indexes** for common query patterns:
```sql
-- Job search optimization
INDEX(status, country_id, state_id, city_id, area_id)
INDEX(employment_type, status)
INDEX(deadline)
INDEX(created_at)

-- Application queries  
INDEX(user_id, status)
INDEX(job_posting_id, status)
INDEX(created_at)
```

#### **4. Redis Caching Implementation**
**Cache Strategy**:
- **Job Listings**: 5-minute TTL for active job lists
- **Location Data**: 24-hour TTL for country/state/city data  
- **User Statistics**: 15-minute TTL for dashboard data
- **Cache Invalidation**: Automatic on CUD operations

### **Data Denormalization Decisions**

#### **1. Location Hierarchy Denormalization**
**Decision**: Store all location IDs (country_id, state_id, city_id, area_id) in job_postings table.

**Benefits**:
- **Fast Filtering**: Direct filtering without JOINs across 4 location tables
- **Improved Query Performance**: Single table queries for location-based job search
- **Index Efficiency**: Compound indexes work better on single table

**Trade-off**: Slight data redundancy for significant performance gain.

#### **2. Application Status Tracking**
**Decision**: Denormalized status tracking with timestamps.

**Implementation**:
```sql
-- Direct status fields instead of separate status_history table
applications: status, reviewed_at, responded_at, created_at, updated_at
```

**Benefits**:
- **Fast Status Queries**: No need to JOIN with history table
- **Simple Analytics**: Direct aggregation on status field
- **Better Performance**: Single table queries for common operations

### **Email Verification System Design**

**Decision**: Email-based verification instead of user_id-based.

**UX Reasoning**:
```php
// User-friendly approach
POST /auth/verify-email
{
    "email": "user@example.com"  // Users always remember their email
}

// Instead of confusing user_id approach  
{
    "user_id": 12345  // Users might forget this!
}
```

**Security Flow**:
1. Registration → User created with `is_active: false`
2. Login attempt → 403 error until verified
3. Email verification → Sets `email_verified_at` + `is_active: true`
4. Automated cleanup → Unverified users deleted after 7 days

### **Rate Limiting Strategy**
**Endpoint-Specific Limits**:
- **Registration**: 3/60min (prevent spam accounts)
- **Login**: 5/15min (brute force protection)  
- **Job Application**: 10/60min (prevent application spam)
- **Public Browse**: 100/60min (general API protection)
- **Email Verification**: 5/60min (prevent abuse)

### **File Storage Design**
**Current State**: Resume storage is **partially implemented**.

**What's Done**:
- ✅ Database fields: `resume_path` in `user_profiles` and `applications`
- ✅ Validation: Resume path validation in requests
- ✅ Resource responses: Resume URL generation with `asset()` helper
- ✅ Model methods: `hasResume()`, `scopeWithResume()` 

**What's Missing**:
- ❌ File upload endpoints/controllers
- ❌ File storage configuration  
- ❌ File validation (size, type, security)
- ❌ File management (delete, update)

**Planned Architecture**:
```php
// Planned file upload endpoint
POST /api/candidate/profile/resume
Content-Type: multipart/form-data

// Storage strategy: Laravel filesystem with security
'disks' => [
    'resumes' => [
        'driver' => 'local',
        'root' => storage_path('app/resumes'),
        'url' => env('APP_URL').'/storage/resumes',
        'visibility' => 'private', // Security: private access only
    ]
]
```

## 🚀 Features

### Core Features
- **JWT Authentication** with role-based access control (Admin, Employer, Candidate)
- **Job Management** - Create, update, delete, and search job listings
- **Application System** - Candidates can apply to jobs, employers can manage applications
- **User Profiles** - Separate profile management for candidates and employers
- **Hierarchical Location System** - Country → State → City → Area structure
- **Skills Management** - Dynamic skill matching between jobs and candidates

### Advanced Features
- **Background Job Matching** - Automated candidate-job matching based on skills, location, and salary
- **Scheduled Tasks** - Daily job archiving and weekly user cleanup
- **Caching** - Redis-based caching for job listings and statistics
- **Notifications** - System notifications for job matches and applications
- **Rate Limiting** - API rate limiting for security
- **File Upload** - Resume upload functionality

## 🔧 Technology Stack

- **Backend Framework**: Laravel 12
- **Database**: PostgreSQL 15
- **Cache/Queue**: Redis 7
- **Web Server**: Nginx (Alpine)
- **PHP**: 8.3 with OPcache
- **Authentication**: JWT (tymon/jwt-auth)
- **Containerization**: Docker & Docker Compose

## 📋 Prerequisites

- **Docker** & **Docker Compose** (recommended: latest version)
- **Git** for version control
- **Minimum System Requirements**:
  - 4GB RAM (8GB recommended)
  - 2GB free disk space
  - Port availability: 8080 (Nginx), 5432 (PostgreSQL), 6379 (Redis), 8081 (pgAdmin)

## 🚀 Setup Instructions

### **1. Repository Setup**
```bash
# Clone the repository
git clone https://github.com/yourusername/hire-smart-backend.git
cd hire-smart-backend

# Navigate to Laravel application directory  
cd html/hiresmart-backend.rokanchowdhuryonick.com
```

### **2. Environment Configuration**
```bash
# Copy environment template
cp .env.example .env

# Edit .env file with your specific configuration
# Key variables to configure:
```

**Critical Environment Variables**:
```env
# Application
APP_NAME="HireSmart Backend"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=hire-smart-backend-db
DB_PORT=5432
DB_DATABASE=hiresmart_db
DB_USERNAME=hiresmart_user
DB_PASSWORD=your_secure_password

# Redis Configuration  
REDIS_HOST=hire-smart-backend-redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# JWT Configuration (will be generated)
JWT_SECRET=
JWT_TTL=60

# Queue Configuration
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Mail Configuration (for production)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
```

### **3. Docker Services Startup**
```bash
# Return to project root
cd ../..

# Build and start all services
docker-compose up -d --build

# Verify all services are running
docker-compose ps

# Expected output:
# hire-smart-backend-app       Up 39 hours (healthy)
# hire-smart-backend-db        Up 39 hours  
# hire-smart-backend-nginx     Up 39 hours
# hire-smart-backend-redis     Up 39 hours
# hire-smart-backend-queue     Up 39 hours (healthy)  
# hire-smart-backend-scheduler Up 39 hours (healthy)
# hire-smart-backend-pgadmin   Up 39 hours
```

### **4. Laravel Application Setup**
```bash
# Enter the application container
docker-compose exec hire-smart-backend-app bash

# Install PHP dependencies
composer install --optimize-autoloader

# Generate application key
php artisan key:generate

# Generate JWT secret key
php artisan jwt:secret

# Run database migrations
php artisan migrate

# Seed the database with initial data
php artisan db:seed

# Create symbolic link for file storage
php artisan storage:link

# Clear and cache configurations for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Exit container
exit
```

### **5. Verify Installation**
```bash
# Test API health
curl http://localhost:8080/api/auth/login

# Expected response: JSON with authentication error (means API is working)

# Test database connection
docker-compose exec hire-smart-backend-app php artisan tinker --execute="echo 'DB connected: ' . (DB::connection()->getPdo() ? 'YES' : 'NO');"
```

## 🌐 Access Points & Credentials

### **Application Endpoints**
- **Main API**: http://localhost:8080/api
- **Web Interface**: http://localhost:8080 (minimal)
- **API Documentation**: See [Postman Collection](./POSTMAN_GUIDE.md)

### **Database Management**
- **pgAdmin**: http://localhost:8081
  - **Email**: `hello+pgadmin@rokanbd.cf`
  - **Password**: `admin123`
  - **Server Connection**:
    - Host: `hire-smart-backend-db`
    - Port: `5432` 
    - Database: `hiresmart_db`
    - Username: `hiresmart_user`

### **Default Admin Account**
```bash
# Admin credentials (created by AdminSeeder)
Email: hello+admin@rokanbd.cf
Password: 123456789
Role: admin
```

## 🔧 Development Workflow

### **Common Commands**
```bash
# Container Management
docker-compose up -d                    # Start all services
docker-compose down                     # Stop all services  
docker-compose restart hire-smart-backend-app # Restart app only

# Application Container Access
docker-compose exec hire-smart-backend-app bash   # Enter container
docker-compose exec hire-smart-backend-app php artisan migrate
docker-compose exec hire-smart-backend-app php artisan queue:work

# Database Operations
docker-compose exec hire-smart-backend-app php artisan migrate:fresh --seed
docker-compose exec hire-smart-backend-app php artisan db:seed --class=LocationSeeder

# Cache Management
docker-compose exec hire-smart-backend-app php artisan cache:clear
docker-compose exec hire-smart-backend-app php artisan config:clear

# Background Jobs & Scheduling  
docker-compose exec hire-smart-backend-app php artisan queue:work
docker-compose exec hire-smart-backend-app php artisan schedule:run
```

### **Log Monitoring**
```bash
# Application logs
docker-compose logs -f hire-smart-backend-app

# All services logs
docker-compose logs -f

# Specific service logs
docker-compose logs -f hire-smart-backend-db
docker-compose logs -f hire-smart-backend-redis
```

## 📊 Implementation Status

### **✅ Completed Features**
- **Authentication System**: JWT with email verification, role-based access
- **Job Management**: CRUD operations, search, filtering, archiving
- **Application Workflow**: Apply, status updates, employer management  
- **User Profiles**: Candidate profiles, employer company management
- **Background Processing**: Job matching, scheduled cleanup tasks
- **API Documentation**: 47 endpoints with Postman collection
- **Database Design**: Optimized schema with proper indexing
- **Caching Strategy**: Redis caching for performance
- **Rate Limiting**: Endpoint-specific security limits
- **Location System**: Hierarchical country→state→city→area structure
- **Skills Management**: Dynamic skill matching system
- **N+1 Query Optimization**: Performance improvements across models
- **Email Verification**: User-friendly email-based verification
- **Soft Deletes**: Safe data deletion for job postings

### **⚠️ Partially Implemented**
- **Resume Upload**: 
  - ✅ Database schema ready
  - ✅ Validation and model methods
  - ❌ Missing file upload endpoints
  - ❌ Storage configuration needed

### **📋 Future Enhancements**
- **File Upload System**: Complete resume upload functionality
- **Email Notifications**: SMTP integration for verification emails  
- **Advanced Search**: Elasticsearch integration
- **Real-time Notifications**: WebSocket/Pusher integration
- **API Versioning**: v2 API with enhanced features
- **Mobile API**: Optimized endpoints for mobile applications

## 🧪 Testing Guide

### **API Testing with Postman**
1. **Import Collection**: `HireSmart_API_Collection.postman_collection.json`
2. **Import Environment**: `HireSmart_Environment.postman_environment.json`  
3. **Follow Guide**: See [POSTMAN_GUIDE.md](./POSTMAN_GUIDE.md) for detailed testing workflows

### **Manual Testing Flow**
```bash
# 1. Register new user
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123","role":"candidate"}'

# 2. Verify email
curl -X POST http://localhost:8080/api/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'

# 3. Login (after verification)
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

## 📈 Performance Considerations

### **Optimization Strategies Implemented**
1. **Database Level**:
   - Strategic compound indexes on frequently queried columns
   - N+1 query elimination with optimized aggregations
   - Connection pooling via PostgreSQL

2. **Application Level**:
   - Service layer for business logic separation
   - Eager loading for relationship queries
   - Request/response optimization with API resources

3. **Infrastructure Level**:
   - Redis caching for frequently accessed data
   - OPcache for PHP bytecode optimization  
   - Nginx with gzip compression
   - Docker multi-stage builds for smaller images

### **Monitoring & Scaling**
- **Application Metrics**: Laravel logs in `storage/logs/`
- **Database Performance**: pgAdmin query analysis tools
- **Cache Performance**: Redis CLI monitoring
- **Container Health**: Docker health checks configured

## 🔐 Security Implementation

- **OWASP Top 10 Compliance**
- **JWT Authentication** with refresh tokens
- **Rate Limiting** on sensitive endpoints
- **Input Validation** and sanitization
- **XSS Protection** with security headers
- **CSRF Protection**
- **SQL Injection Prevention** via Eloquent ORM

## 📡 API Endpoints

### **🔐 Authentication & Profile Management**

| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `POST` | `/api/auth/register` | User registration | 3/60min | ❌ |
| `POST` | `/api/auth/login` | User login | 5/15min | ❌ |
| `POST` | `/api/auth/verify-email` | Verify email address | 5/60min | ❌ |
| `POST` | `/api/auth/logout` | User logout | - | ✅ |
| `POST` | `/api/auth/refresh` | Refresh JWT token | - | ✅ |
| `GET` | `/api/auth/me` | Get current user info | - | ✅ |
| `PUT` | `/api/auth/profile` | Update user profile | - | ✅ |
| `POST` | `/api/auth/change-password` | Change password | - | ✅ |
| `GET` | `/api/auth/stats` | Get user statistics | - | ✅ |
| `POST` | `/api/auth/forgot-password` | Request password reset | 3/15min | ❌ |
| `POST` | `/api/auth/reset-password` | Reset password | 3/15min | ❌ |

---

### **🏢 Public Job Browsing** (No Authentication Required)

| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `GET` | `/api/jobs` | Browse all jobs with filters | 100/60min | ❌ |
| `GET` | `/api/jobs/{id}` | Get specific job details | 200/60min | ❌ |
| `GET` | `/api/jobs/{id}/similar` | Get similar jobs | 50/60min | ❌ |
| `GET` | `/api/jobs/stats` | Get job statistics | 30/60min | ❌ |

**Query Parameters for Job Search:**
- `search` - Keyword search in title/description
- `country_id`, `state_id`, `city_id`, `area_id` - Location filters
- `min_salary`, `max_salary` - Salary range
- `employment_type` - full_time, part_time, contract, internship
- `skills` - Skill IDs (comma-separated)
- `company_id` - Filter by specific company
- `sort_by` - created_at, salary, experience_years
- `sort_order` - asc, desc
- `per_page` - Results per page (max 50)

---

### **👔 Employer Endpoints** (Role: employer)

#### **Job Management**
| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `GET` | `/api/employer/jobs` | List employer's jobs | - | ✅ (employer) |
| `POST` | `/api/employer/jobs` | Create new job posting | - | ✅ (employer) |
| `GET` | `/api/employer/jobs/{id}` | Get specific job | - | ✅ (employer) |
| `PUT` | `/api/employer/jobs/{id}` | Update job posting | - | ✅ (employer) |
| `DELETE` | `/api/employer/jobs/{id}` | Delete job posting | - | ✅ (employer) |
| `POST` | `/api/employer/jobs/{id}/archive` | Archive job manually | - | ✅ (employer) |
| `GET` | `/api/employer/jobs-stats` | Get job statistics | - | ✅ (employer) |

#### **Application Management**
| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `GET` | `/api/employer/applications` | List all applications | - | ✅ (employer) |
| `GET` | `/api/employer/applications/{id}` | Get application details | - | ✅ (employer) |
| `PUT` | `/api/employer/applications/{id}/status` | Update application status | - | ✅ (employer) |
| `PUT` | `/api/employer/applications/bulk-status` | Bulk update applications | - | ✅ (employer) |
| `GET` | `/api/employer/applications-stats` | Get application statistics | - | ✅ (employer) |

#### **Job-Specific Operations**
| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `GET` | `/api/employer/jobs/{jobId}/applications` | Applications for specific job | - | ✅ (employer) |
| `GET` | `/api/employer/jobs/{jobId}/matches` | AI-matched candidates | - | ✅ (employer) |
| `GET` | `/api/employer/jobs/{jobId}/find-candidates` | Find potential candidates | - | ✅ (employer) |

---

### **👨‍💼 Candidate Endpoints** (Role: candidate)

#### **Job Discovery**
| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `GET` | `/api/candidate/jobs` | Browse jobs (authenticated) | - | ✅ (candidate) |
| `GET` | `/api/candidate/jobs/{id}` | Get job details | - | ✅ (candidate) |
| `POST` | `/api/candidate/jobs/{id}/apply` | Apply to job | 10/60min | ✅ (candidate) |
| `GET` | `/api/candidate/jobs/{id}/similar` | Get similar jobs | - | ✅ (candidate) |
| `POST` | `/api/candidate/jobs/{id}/bookmark` | Bookmark job | - | ✅ (candidate) |
| `GET` | `/api/candidate/job-recommendations` | Get job recommendations | - | ✅ (candidate) |
| `GET` | `/api/candidate/jobs-stats` | Get job statistics | - | ✅ (candidate) |

#### **Application Management**
| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `GET` | `/api/candidate/applications` | List my applications | - | ✅ (candidate) |
| `GET` | `/api/candidate/applications/{id}` | Get application details | - | ✅ (candidate) |
| `DELETE` | `/api/candidate/applications/{id}` | Withdraw application | - | ✅ (candidate) |
| `GET` | `/api/candidate/applications-stats` | Get application statistics | - | ✅ (candidate) |
| `GET` | `/api/candidate/applications-timeline` | Get application timeline | - | ✅ (candidate) |
| `GET` | `/api/candidate/recommendations` | Get job recommendations | - | ✅ (candidate) |

---

### **⚙️ Admin Endpoints** (Role: admin)

#### **Dashboard & Analytics**
| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `GET` | `/api/admin/dashboard` | Platform dashboard metrics | - | ✅ (admin) |
| `GET` | `/api/admin/system-health` | System health check | - | ✅ (admin) |

#### **User Management**
| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `GET` | `/api/admin/users` | List all users with filters | - | ✅ (admin) |
| `PUT` | `/api/admin/users/{id}/toggle-status` | Activate/deactivate user | - | ✅ (admin) |

#### **System Operations**
| Method | Endpoint | Description | Rate Limit | Auth Required |
|--------|----------|-------------|------------|---------------|
| `POST` | `/api/admin/run-job-matching` | Trigger job matching manually | - | ✅ (admin) |
| `POST` | `/api/admin/archive-old-jobs` | Archive old jobs manually | - | ✅ (admin) |

---

### **🔑 API Response Format**

#### **Success Response**
```json
{
    "status": "success",
    "message": "Operation completed successfully",
    "data": {
        // Response data here
    },
    "meta": {
        "timestamp": "2024-01-15T10:30:00Z",
        "execution_time": "0.15s"
    }
}
```

#### **Error Response**
```json
{
    "status": "error",
    "message": "Error description",
    "code": "ERROR_CODE",
    "errors": {
        "field": ["Validation error message"]
    },
    "meta": {
        "timestamp": "2024-01-15T10:30:00Z",
        "endpoint": "/api/endpoint",
        "method": "POST"
    }
}
```

#### **Pagination Response**
```json
{
    "status": "success",
    "data": [...],
    "pagination": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150,
        "from": 1,
        "to": 15
    }
}
```

---

### **🛡️ Authentication**

#### **JWT Token Format**
```bash
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### **Token Claims**
```json
{
    "iss": "hire-smart-backend",
    "sub": "user_id",
    "role": "candidate|employer|admin",
    "name": "User Name",
    "email": "user@example.com",
    "iat": 1641891600,
    "exp": 1641895200
}
```

#### **📧 Email Verification Process**

All new users must verify their email address before they can log in:

**1. Registration Flow:**
```bash
POST /api/auth/register
{
    "name": "John Doe",
    "email": "john@example.com", 
    "password": "password123",
    "password_confirmation": "password123",
    "role": "candidate"
}

Response:
{
    "status": "success",
    "message": "Registration successful. Please verify your email to activate your account.",
    "data": {
        "user": { "id": 123, "is_active": false, "is_verified": false },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 3600
    }
}
```

**2. Login Attempt (Unverified):**
```bash
POST /api/auth/login
{
    "email": "john@example.com",
    "password": "password123"
}

Response: 403 Forbidden
{
    "status": "error",
    "message": "Please verify your email address before logging in"
}
```

**3. Email Verification:**
```bash
POST /api/auth/verify-email
{
    "email": "john@example.com"
}

Response:
{
    "status": "success",
    "message": "Email verified successfully. Account is now active.",
    "data": {
        "user": { "id": 123, "is_active": true, "is_verified": true },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 3600
    }
}
```

**4. Login Success (After Verification):**
```bash
POST /api/auth/login
{
    "email": "john@example.com",
    "password": "password123"
}

Response: 200 OK
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "user": { "id": 123, "is_active": true, "is_verified": true },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 3600
    }
}
```

**💡 Important Notes:**
- Users with `is_active: false` or `email_verified_at: null` cannot log in
- Unverified users are automatically removed after 7 days by scheduled cleanup
- Admin users are created with verified status by default
- Rate limit: 5 verification attempts per 60 minutes

---

### **⚡ Rate Limiting**

Rate limits are applied per IP address:

| Endpoint Type | Limit | Window | Purpose |
|---------------|-------|--------|---------|
| Registration | 3 requests | 60 minutes | Prevent spam accounts |
| Login | 5 requests | 15 minutes | Brute force protection |
| Password Reset | 3 requests | 15 minutes | Prevent abuse |
| Job Application | 10 requests | 60 minutes | Prevent spam applications |
| Public Job Browse | 100 requests | 60 minutes | General API protection |
| Job Details | 200 requests | 60 minutes | High traffic endpoint |

#### **Rate Limit Headers**
```bash
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1641891600
Retry-After: 3600
```

---

### **🎯 Status Codes**

| Code | Meaning | Usage |
|------|---------|-------|
| `200` | OK | Successful GET, PUT requests |
| `201` | Created | Successful POST requests |
| `204` | No Content | Successful DELETE requests |
| `400` | Bad Request | Invalid request data |
| `401` | Unauthorized | Authentication required |
| `403` | Forbidden | Insufficient permissions |
| `404` | Not Found | Resource not found |
| `422` | Unprocessable Entity | Validation errors |
| `429` | Too Many Requests | Rate limit exceeded |
| `500` | Internal Server Error | Server errors |

---

### **📚 API Testing**

Use these base URLs for testing:

- **Local Development**: `http://localhost:8080/api`
- **Production**: `https://your-domain.com/api`

#### **🚀 Postman Collection (Recommended)**

For comprehensive API testing, I provide a complete **Postman collection** with all 47 endpoints:

📁 **Files Available:**
- `HireSmart_API_Collection.postman_collection.json` - Complete API collection
- `HireSmart_Environment.postman_environment.json` - Environment variables
- **[📖 Postman Testing Guide](./POSTMAN_GUIDE.md)** - Complete setup & testing guide

**Features:**
- ✅ **Automatic JWT token management** for all roles
- ✅ **47 pre-configured endpoints** with realistic request bodies
- ✅ **Role-based authentication** (admin, employer, candidate)
- ✅ **Environment variables** for easy testing
- ✅ **Request validation** based on FormRequest classes
- ✅ **Rate limiting compliance** with proper delays
- ✅ **Complete testing workflows** with step-by-step guide

**Quick Setup:**
1. Import both JSON files into Postman
2. Select "HireSmart Development Environment"  
3. Follow the [Postman Guide](./POSTMAN_GUIDE.md) for complete testing workflows

#### **Manual cURL Testing**

**Example cURL requests:**

```bash
# Register new user
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123","password_confirmation":"password123","role":"candidate"}'

# Verify email (use email from registration)
curl -X POST http://localhost:8080/api/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com"}'

# Login (after email verification)
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'

# Browse jobs (authenticated)
curl -X GET http://localhost:8080/api/candidate/jobs \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Create job posting
curl -X POST http://localhost:8080/api/employer/jobs \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Software Developer","description":"Job description here",...}'
```

## 🔄 Background Jobs

### Job Matching
- Runs hourly to match candidates with suitable jobs
- Based on skills, location, and salary preferences
- Sends notifications for matches

### Scheduled Tasks
- **Daily**: Archive jobs older than 30 days
- **Weekly**: Remove unverified users

## 📈 Performance Features

- **OPcache** for PHP bytecode caching
- **Redis Caching** for database query results
- **Database Indexing** for optimal query performance
- **Nginx Gzip Compression**
- **Static File Caching**

## 🐳 Docker Services

- **hire-smart-backend-app** - Laravel application (PHP-FPM)
- **hire-smart-backend-nginx** - Web server
- **hire-smart-backend-db** - PostgreSQL database
- **hire-smart-backend-redis** - Redis cache/queue
- **hire-smart-backend-queue** - Queue worker
- **hire-smart-backend-scheduler** - Task scheduler
- **hire-smart-backend-pgadmin** - Database management (optional)

## 🧪 Testing

```bash
# Run all tests
docker-compose exec hire-smart-backend-app php artisan test

# Run specific test suite
docker-compose exec hire-smart-backend-app php artisan test --testsuite=Feature
```

## 🔍 Monitoring & Logs

```bash
# View application logs
docker-compose logs hire-smart-backend-app

# View all service logs
docker-compose logs

# Enter container for debugging
docker-compose exec hire-smart-backend-app bash
```

## 📦 Deployment

This project is containerized and ready for deployment to any Docker-compatible environment:

- **Development**: Docker Compose (current setup)
- **Production**: Docker Swarm, Kubernetes, or cloud container services
- **CI/CD**: GitHub Actions workflow included

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new features
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🔧 Architecture Decisions

### Service Layer Pattern
- **Controllers**: Handle HTTP requests/responses only
- **Services**: Contain business logic and orchestration
- **Models**: Handle data access and relationships
- **Repositories**: Not used (avoiding unnecessary abstraction)

### Security Approach
- JWT for stateless authentication
- Role-based access control
- Input validation at request level
- Rate limiting for abuse prevention

### Database Design
- Normalized relational structure
- Hierarchical location system for scalability
- Separate skills tables for flexibility
- Soft deletes for audit trails

### Caching Strategy
- Redis for session storage
- Query result caching
- Job listing caching (5-minute TTL)
- Location data caching (24-hour TTL)

## 📞 Say Hello

For issues and questions:
- Create an issue on GitHub
- Email: hello@rokanchowdhuryonick.com
- Documentation: [API Documentation](#-api-endpoints)

---

**.\ The End**

# HireSmart Backend System

A comprehensive backend system for connecting job seekers with employers, built with Laravel 12, PostgreSQL, and Redis.

## 🏗️ Project Structure

```
hire-smart-backend/
├── docker/                                    # Docker configuration files
│   ├── nginx/
│   │   └── nginx.conf                        # Nginx web server configuration
│   ├── php/
│   │   ├── php.ini                          # PHP configuration
│   │   └── opcache.ini                      # OPcache configuration
│   └── supervisor/
│       └── supervisord.conf                 # Supervisor configuration
├── html/
│   └── hiresmart-backend.rokanchowdhuryonick.com/  # Laravel application
│       ├── app/                             # Application code
│       ├── config/                          # Configuration files
│       ├── database/                        # Migrations, seeders, factories
│       ├── public/                          # Web server document root
│       ├── resources/                       # Views, assets
│       ├── routes/                          # Route definitions
│       ├── storage/                         # File storage, logs, cache
│       ├── tests/                           # Test files
│       ├── .env.example                     # Environment variables template
│       ├── artisan                          # Laravel command-line interface
│       ├── composer.json                    # PHP dependencies
│       └── package.json                     # Node.js dependencies
├── docker-compose.yml                        # Docker services configuration
├── Dockerfile                               # Docker image configuration
└── README.md                               # This file
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

- Docker and Docker Compose installed
- Git for version control
- Minimum 4GB RAM for containers

## 🚀 Quick Start

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/hire-smart-backend.git
cd hire-smart-backend
```

### 2. Environment Setup
```bash
# Copy environment file
cp html/hiresmart-backend.rokanchowdhuryonick.com/.env.example html/hiresmart-backend.rokanchowdhuryonick.com/.env

# Edit the .env file with your configuration
# Key variables to configure:
# - DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - JWT_SECRET (generate with: php artisan jwt:secret)
# - APP_KEY (generate with: php artisan key:generate)
```

### 3. Build and Start Services
```bash
# Build and start all services
docker-compose up -d --build

# Check service status
docker-compose ps
```

### 4. Application Setup
```bash
# Enter the application container
docker-compose exec hire-smart-backend-app bash

# Install dependencies
composer install

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate

# Seed the database
php artisan db:seed
```

## 🌐 Access Points

- **Application**: http://localhost:8080
- **pgAdmin**: http://localhost:8081
  - Email: hello+pgadmin@rokanbd.cf
  - Password: admin123
- **API Documentation**: http://localhost:8080/api/docs (when implemented)

## 📊 Database Schema

### Core Tables
- `users` - Authentication and basic user info
- `user_profiles` - Extended user profiles (candidates)
- `companies` - Company information (employers)
- `jobs` - Job listings
- `applications` - Job applications
- `skills` - Master skills list
- `job_skills` / `user_skills` - Skill relationships

### Location Tables
- `countries` - Country data
- `states` - State/province data
- `cities` - City data
- `areas` - Area/district data

### System Tables
- `notifications` - System notifications
- `job_matches` - Background job matching results

### **📋 Complete Database Schema**
For a comprehensive view of the database structure, relationships, and constraints, see the detailed **[Entity Relationship Diagram (ERD)](./ERD.md)**.

The ERD includes:
- Complete table structures with all columns and data types
- Primary and foreign key relationships
- Database constraints and indexes
- Business rules and data integrity features
- Visual Mermaid diagram of all relationships

## 🔐 Security Features

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

For comprehensive API testing, we provide a complete **Postman collection** with all 47 endpoints:

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
  -d '{"name":"John Doe","email":"john@example.com","password":"password123","role":"candidate"}'

# Login
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

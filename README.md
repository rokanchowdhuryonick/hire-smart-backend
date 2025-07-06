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

## 🔐 Security Features

- **OWASP Top 10 Compliance**
- **JWT Authentication** with refresh tokens
- **Rate Limiting** on sensitive endpoints
- **Input Validation** and sanitization
- **XSS Protection** with security headers
- **CSRF Protection**
- **SQL Injection Prevention** via Eloquent ORM

## 📡 API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/refresh` - Refresh JWT token
- `POST /api/auth/logout` - User logout

### Jobs
- `GET /api/jobs` - List jobs (with filtering)
- `POST /api/jobs` - Create job (employer only)
- `GET /api/jobs/{id}` - Get job details
- `PUT /api/jobs/{id}` - Update job (employer only)
- `DELETE /api/jobs/{id}` - Delete job (employer only)

### Applications
- `POST /api/jobs/{id}/apply` - Apply to job
- `GET /api/applications` - List applications (role-based)
- `PUT /api/applications/{id}/status` - Update application status

### Admin
- `GET /api/admin/metrics` - Platform statistics
- `GET /api/admin/users` - User management

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
- Documentation: [Link to docs]

---

**.\ The End**

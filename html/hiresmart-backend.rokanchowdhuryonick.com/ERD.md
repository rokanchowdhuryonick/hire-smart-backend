# HireSmart Backend - Entity Relationship Diagram (ERD)

This document provides a comprehensive view of the HireSmart backend database structure, including all tables, relationships, and constraints.

## Database Overview

The HireSmart system uses **PostgreSQL** as its primary database with a well-structured relational design supporting:

- **Multi-role User System** (Admin, Employer, Candidate)
- **Hierarchical Location System** (Country → State → City → Area)
- **Job Management System** with skill matching
- **Application Management System** with status tracking
- **Background Job Matching System**
- **Notification System**

---

## Entity Relationship Diagram

```mermaid
erDiagram
    %% Core User System
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string role
        boolean is_active
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    user_profiles {
        bigint id PK
        bigint user_id FK
        text bio
        decimal min_salary
        decimal max_salary
        string currency
        bigint country_id FK
        bigint state_id FK
        bigint city_id FK
        bigint area_id FK
        string phone
        string resume_path
        timestamp created_at
        timestamp updated_at
    }

    companies {
        bigint id PK
        bigint user_id FK
        string name
        text description
        string website
        string logo_path
        bigint country_id FK
        bigint state_id FK
        bigint city_id FK
        bigint area_id FK
        timestamp created_at
        timestamp updated_at
    }

    %% Location Hierarchy
    countries {
        bigint id PK
        string name UK
        timestamp created_at
        timestamp updated_at
    }

    states {
        bigint id PK
        bigint country_id FK
        string name
        timestamp created_at
        timestamp updated_at
    }

    cities {
        bigint id PK
        bigint state_id FK
        string name
        timestamp created_at
        timestamp updated_at
    }

    areas {
        bigint id PK
        bigint country_id FK
        bigint state_id FK
        bigint city_id FK
        string name
        timestamp created_at
        timestamp updated_at
    }

    %% Job System
    job_postings {
        bigint id PK
        bigint company_id FK
        bigint user_id FK
        string title
        text description
        bigint country_id FK
        bigint state_id FK
        bigint city_id FK
        bigint area_id FK
        decimal min_salary
        decimal max_salary
        string currency
        enum employment_type
        enum status
        date deadline
        integer experience_years
        timestamp archived_at
        timestamp created_at
        timestamp updated_at
    }

    applications {
        bigint id PK
        bigint job_posting_id FK
        bigint user_id FK
        text cover_letter
        string resume_path
        enum status
        timestamp applied_at
        timestamp reviewed_at
        timestamp created_at
        timestamp updated_at
    }

    %% Skills System
    skills {
        bigint id PK
        string name UK
        timestamp created_at
        timestamp updated_at
    }

    job_skills {
        bigint id PK
        bigint job_posting_id FK
        bigint skill_id FK
        boolean is_required
        timestamp created_at
        timestamp updated_at
    }

    user_skills {
        bigint id PK
        bigint user_id FK
        bigint skill_id FK
        enum proficiency_level
        integer years_of_experience
        timestamp created_at
        timestamp updated_at
    }

    %% Matching & Notification System
    job_matches {
        bigint id PK
        bigint job_posting_id FK
        bigint candidate_id FK
        decimal match_score
        json match_reasons
        boolean notification_sent
        timestamp created_at
        timestamp updated_at
    }

    notifications {
        bigint id PK
        bigint user_id FK
        string type
        string title
        text message
        json data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }

    %% System Tables
    password_reset_tokens {
        string email PK
        string token
        timestamp created_at
    }

    sessions {
        string id PK
        bigint user_id FK
        string ip_address
        text user_agent
        longtext payload
        integer last_activity
    }

    cache {
        string key PK
        mediumtext value
        integer expiration
    }

    jobs {
        bigint id PK
        string queue
        longtext payload
        tinyint attempts
        integer reserved_at
        integer available_at
        integer created_at
    }

    job_batches {
        string id PK
        string name
        integer total_jobs
        integer pending_jobs
        integer failed_jobs
        longtext failed_job_ids
        mediumtext options
        integer cancelled_at
        integer created_at
        integer finished_at
    }

    failed_jobs {
        bigint id PK
        string uuid UK
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at
    }

    %% Relationships
    
    %% User System Relationships
    users ||--o{ user_profiles : "has profile (candidate)"
    users ||--o{ companies : "owns (employer)"
    users ||--o{ job_postings : "creates (employer)"
    users ||--o{ applications : "submits (candidate)"
    users ||--o{ notifications : "receives"
    users ||--o{ job_matches : "matched to (candidate)"
    users ||--o{ user_skills : "has skills"
    users ||--o{ sessions : "has sessions"

    %% Location Relationships
    countries ||--o{ states : "contains"
    states ||--o{ cities : "contains"
    cities ||--o{ areas : "contains"
    countries ||--o{ areas : "located in"
    states ||--o{ areas : "located in"

    countries ||--o{ user_profiles : "candidate location"
    states ||--o{ user_profiles : "candidate location"
    cities ||--o{ user_profiles : "candidate location"
    areas ||--o{ user_profiles : "candidate location"

    countries ||--o{ companies : "company location"
    states ||--o{ companies : "company location"
    cities ||--o{ companies : "company location"
    areas ||--o{ companies : "company location"

    countries ||--o{ job_postings : "job location"
    states ||--o{ job_postings : "job location"
    cities ||--o{ job_postings : "job location"
    areas ||--o{ job_postings : "job location"

    %% Job System Relationships
    companies ||--o{ job_postings : "posts"
    job_postings ||--o{ applications : "receives"
    job_postings ||--o{ job_skills : "requires"
    job_postings ||--o{ job_matches : "matched with"

    %% Skills Relationships
    skills ||--o{ job_skills : "required for jobs"
    skills ||--o{ user_skills : "possessed by users"

    %% Password Reset
    users ||--o{ password_reset_tokens : "requests reset"
```

---

## Table Descriptions

### **Core User Tables**

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `users` | Main user authentication and basic info | Role-based (admin/employer/candidate), email verification, active status |
| `user_profiles` | Extended candidate profiles | Salary expectations, location preferences, resume storage |
| `companies` | Employer company information | Company details, location, branding |

### **Location Hierarchy Tables**

| Table | Purpose | Relationship |
|-------|---------|--------------|
| `countries` | Country master data | Root level |
| `states` | State/Province data | Belongs to country |
| `cities` | City data | Belongs to state |
| `areas` | District/Area data | Belongs to city, state, country |

### **Job Management Tables**

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `job_postings` | Job listings | Status management, salary range, location, deadlines |
| `applications` | Job applications | Status tracking, document storage, timestamps |
| `skills` | Master skills database | Unique skill names |
| `job_skills` | Job skill requirements | Required vs optional skills |
| `user_skills` | User skill profiles | Proficiency levels, experience years |

### **Matching & Communication Tables**

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `job_matches` | AI job matching results | Match scores, reasoning, notification tracking |
| `notifications` | System notifications | User alerts, read status, metadata |

### **System Tables**

| Table | Purpose | Usage |
|-------|---------|-------|
| `password_reset_tokens` | Password reset flow | Temporary token storage |
| `sessions` | User sessions | Web session storage (backup) |
| `cache` | Application cache | Redis alternative/fallback |
| `jobs` | Background job queue | Async task processing |
| `job_batches` | Batch job management | Bulk operation tracking |
| `failed_jobs` | Failed job logging | Error tracking and retry |

---

## Key Constraints and Indexes

### **Unique Constraints**
- `users.email` - Prevent duplicate user accounts
- `skills.name` - Prevent duplicate skills
- `countries.name` - Prevent duplicate countries
- `applications(job_posting_id, user_id)` - Prevent duplicate applications
- `failed_jobs.uuid` - Unique failure tracking

### **Foreign Key Constraints**
- **Cascade Deletes**: User deletion cascades to profiles, companies, jobs, applications
- **Set Null**: Location deletion sets related fields to null (data preservation)
- **Restrict**: Prevent deletion of referenced skills or system data

### **Performance Indexes**
- **Job Search**: `job_postings(status, country_id, state_id, city_id, area_id)`
- **Application Status**: `applications(status, applied_at)`
- **Employment Type**: `job_postings(employment_type, status)`
- **Location Hierarchy**: Multi-column indexes on location foreign keys
- **Salary Range**: `user_profiles(min_salary, max_salary)`

---

## Business Rules

### **User Roles**
- **Admin**: Full system access, user management, analytics
- **Employer**: Job posting, application management, candidate search
- **Candidate**: Job search, application submission, profile management

### **Application Flow**
1. Candidate applies to job posting
2. Status: `pending` → `reviewed` → `shortlisted`/`rejected` → `hired`
3. Each status change creates notification
4. Background matching suggests relevant candidates

### **Location Hierarchy**
- **Required**: Country → State → City → Area
- **Flexible**: Allows partial location specification
- **Consistent**: Same hierarchy used for users, companies, and jobs

### **Skill Matching**
- **Job Skills**: Required vs Optional skill designation
- **User Skills**: Proficiency levels and experience years
- **Matching Algorithm**: Weighted scoring based on skill overlap

---

## Data Integrity Features

- **Referential Integrity**: Foreign key constraints ensure data consistency
- **Unique Constraints**: Prevent data duplication at database level
- **Enum Validation**: Controlled vocabulary for status fields
- **Timestamp Tracking**: Complete audit trail with created/updated timestamps
- **Soft Deletes**: Available through Laravel (archived_at fields)
- **Index Optimization**: Query performance optimization for common access patterns

---

This ERD represents a production-ready database design that supports scalable job matching, user management, and business analytics for the HireSmart platform. 
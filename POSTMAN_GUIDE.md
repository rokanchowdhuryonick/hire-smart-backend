# HireSmart API - Postman Testing Guide

This guide will help you set up and use the Postman collection to test all HireSmart backend APIs efficiently.

## 📁 Files Included

- `HireSmart_API_Collection.postman_collection.json` - Complete API collection with 40+ endpoints
- `HireSmart_Environment.postman_environment.json` - Environment variables for easy testing
- `POSTMAN_GUIDE.md` - This guide

## 🚀 Quick Setup

### 1. Import Collection & Environment

1. **Open Postman** desktop application
2. **Import Collection**: 
   - Click "Import" button
   - Select `HireSmart_API_Collection.postman_collection.json`
3. **Import Environment**:
   - Click "Import" button  
   - Select `HireSmart_Environment.postman_environment.json`
4. **Select Environment**:
   - Click environment dropdown (top right)
   - Select "HireSmart Development Environment"

### 2. Start Your Backend

Make sure your HireSmart backend is running:

```bash
# Start Docker services
docker-compose up -d

# Verify services are running
docker-compose ps
```

Your API should be accessible at: `http://localhost:8080/api`

## 📋 Collection Structure

The collection is organized into 5 main folders:

### 🔐 **Authentication & Profile** (10 endpoints)
- User registration & login
- Profile management
- Password operations
- JWT token handling

### 🏢 **Public Job Browsing** (4 endpoints)  
- Browse jobs without authentication
- Public job search & filtering
- Job details & statistics

### 👔 **Employer Endpoints** (15 endpoints)
- Job Management (7 endpoints)
- Application Management (5 endpoints)  
- Job-Specific Operations (3 endpoints)

### 👨‍💼 **Candidate Endpoints** (12 endpoints)
- Job Discovery (7 endpoints)
- Application Management (5 endpoints)

### ⚙️ **Admin Endpoints** (6 endpoints)
- Dashboard & Analytics (2 endpoints)
- User Management (2 endpoints)
- System Operations (2 endpoints)

## 🔑 Authentication Workflow

### Step 1: Register Test Users

Create users for each role to test all endpoints:

1. **Register Candidate**:
   ```json
   POST /auth/register
   {
       "name": "John Candidate",
       "email": "candidate@test.com", 
       "password": "password123",
       "password_confirmation": "password123",
       "role": "candidate"
   }
   ```
   ⚠️ **Note**: Remember the email address - you'll need it for email verification.

2. **Verify Candidate Email**:
   ```json
   POST /auth/verify-email
   {
       "email": "candidate@test.com"
   }
   ```
   ✅ **Required**: Users must verify email before they can login successfully.

3. **Register Employer**:
   ```json
   POST /auth/register
   {
       "name": "Jane Employer",
       "email": "employer@test.com",
       "password": "password123", 
       "password_confirmation": "password123",
       "role": "employer"
   }
   ```

4. **Verify Employer Email**:
   ```json
   POST /auth/verify-email
   {
       "email": "employer@test.com"
   }
   ```

### Step 2: Login & Save Tokens

The collection automatically saves JWT tokens when you login:

1. **Login as Candidate** - Token saved to `{{candidateToken}}`
2. **Login as Employer** - Token saved to `{{employerToken}}`  
3. **Login as Admin** - Token saved to `{{adminToken}}`

⚠️ **Important**: Login will fail with 403 error if email is not verified first.

Tokens are automatically used in the appropriate endpoint folders.

## 🧪 Testing Workflows

### Complete API Testing Workflow

Follow this sequence for comprehensive testing:

#### **Phase 1: Authentication Setup**
1. ✅ Register Candidate
2. ✅ Verify Candidate Email (use email from registration)
3. ✅ Register Employer  
4. ✅ Verify Employer Email (use email from registration)
5. ✅ Login as Candidate (saves `candidateToken`)
6. ✅ Login as Employer (saves `employerToken`)
7. ✅ Test "Get Current User" for both roles

#### **Phase 2: Public Endpoints**
6. ✅ Browse All Jobs (no auth required)
7. ✅ Get Job Details (no auth required)
8. ✅ Get Job Statistics (no auth required)

#### **Phase 3: Employer Workflow**
9. ✅ Create Job Posting (employer)
10. ✅ List My Jobs (employer)
11. ✅ Update Job (employer)
12. ✅ Get Job Statistics (employer)

#### **Phase 4: Candidate Workflow**  
13. ✅ Browse Jobs (candidate authenticated view)
14. ✅ Apply to Job (candidate)
15. ✅ List My Applications (candidate)
16. ✅ Get Application Details (candidate)

#### **Phase 5: Application Management**
17. ✅ List All Applications (employer)
18. ✅ Update Application Status (employer)
19. ✅ Get Application Statistics (employer)

#### **Phase 6: Admin Operations** (if admin user available)
20. ✅ Get Dashboard (admin)
21. ✅ List All Users (admin)
22. ✅ System Health Check (admin)

## 🔧 Environment Variables

The environment includes these key variables:

| Variable | Purpose | Auto-Updated |
|----------|---------|--------------|
| `{{baseUrl}}` | API base URL | ❌ |
| `{{token}}` | Current user token | ✅ |
| `{{candidateToken}}` | Candidate JWT | ✅ |
| `{{employerToken}}` | Employer JWT | ✅ |
| `{{adminToken}}` | Admin JWT | ✅ |
| `{{userRole}}` | Current user role | ✅ |

## 📝 Request Examples

### Job Creation (Employer)
```json
POST /employer/jobs
Authorization: Bearer {{employerToken}}

{
    "title": "Senior Software Developer",
    "description": "We are looking for an experienced software developer...",
    "min_salary": 70000,
    "max_salary": 100000,
    "currency": "USD",
    "employment_type": "full_time",
    "deadline": "2024-12-31",
    "experience_years": 5,
    "country_id": 1,
    "state_id": 1,
    "city_id": 1,
    "skills": [
        {"id": 1, "is_required": true},
        {"id": 2, "is_required": false}
    ]
}
```

### Job Application (Candidate)
```json
POST /candidate/jobs/1/apply  
Authorization: Bearer {{candidateToken}}

{
    "cover_letter": "Dear Hiring Manager, I am excited to apply...",
    "resume_path": "/uploads/resumes/john-doe-resume.pdf"
}
```

### Application Status Update (Employer)
```json
PUT /employer/applications/1/status
Authorization: Bearer {{employerToken}}

{
    "status": "shortlisted",
    "notes": "Excellent candidate, scheduling for interview"
}
```

## 🧩 Advanced Features

### Automatic Token Management
- Login requests automatically save tokens to environment
- Logout requests automatically clear tokens
- Refresh token requests update the current token

### Pre-Request Scripts
Some requests include scripts that:
- Validate required environment variables
- Set dynamic values (timestamps, etc.)
- Log request information for debugging

### Test Scripts  
Many requests include basic tests that:
- Verify response status codes
- Check response structure
- Validate required fields in responses
- Extract and save important data

### Rate Limiting Testing
The collection respects API rate limits:
- **Registration**: 3 requests per 60 minutes
- **Login**: 5 requests per 15 minutes  
- **Job Application**: 10 requests per 60 minutes
- **Public Browse**: 100 requests per 60 minutes

## 🐛 Troubleshooting

### Common Issues

**1. "Unauthorized" Errors**
- ✅ Check if you're logged in (token exists)
- ✅ Verify you're using the correct role token
- ✅ Try refreshing the token

**2. "Email Verification Required" (403 Forbidden)**
- ✅ Error: "Please verify your email address before logging in"
- ✅ Solution: Use "Verify Email" endpoint with email address from registration
- ✅ Check if user is already verified (is_verified: true in response)
- ✅ Note: Unverified users are deleted after 7 days automatically

**3. "Connection Refused" Errors**  
- ✅ Ensure Docker containers are running
- ✅ Check if API is accessible at `http://localhost:8080`
- ✅ Verify database connections are working

**4. "Validation Errors"**
- ✅ Check request body format matches examples
- ✅ Ensure required fields are included
- ✅ Verify data types (strings, numbers, booleans)

**5. "Rate Limit Exceeded"**
- ✅ Wait for the rate limit window to reset
- ✅ Check rate limit headers in response
- ✅ Space out your requests appropriately

### Debug Tips

1. **Enable Postman Console**:
   - View → Show Postman Console
   - See request/response details and script logs

2. **Check Environment Variables**:
   - Click the "eye" icon next to environment name
   - Verify tokens and URLs are set correctly

3. **Use Request/Response Inspector**:
   - Review actual HTTP requests/responses
   - Check headers, body, and status codes

## 📊 Expected Response Formats

### Success Response
```json
{
    "status": "success",
    "message": "Operation completed successfully",
    "data": { ... },
    "meta": {
        "timestamp": "2024-01-15T10:30:00Z",
        "execution_time": "0.15s"
    }
}
```

### Error Response
```json
{
    "status": "error", 
    "message": "Error description",
    "code": "ERROR_CODE",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

### Pagination Response
```json
{
    "status": "success",
    "data": [...],
    "pagination": {
        "current_page": 1,
        "last_page": 10, 
        "per_page": 15,
        "total": 150
    }
}
```

## 🎯 Testing Checklist

Use this checklist to ensure complete API coverage:

### Authentication ✅
- [ ] User registration (candidate & employer)
- [ ] Email verification (required before login)
- [ ] User login with different roles
- [ ] Profile updates
- [ ] Password changes
- [ ] Token refresh
- [ ] Logout functionality

### Job Management ✅  
- [ ] Job creation (employer)
- [ ] Job listing & filtering
- [ ] Job details retrieval
- [ ] Job updates (employer)
- [ ] Job archiving (employer)

### Applications ✅
- [ ] Job application (candidate)
- [ ] Application listing (both roles)
- [ ] Application status updates (employer)
- [ ] Bulk application updates (employer)
- [ ] Application withdrawal (candidate)

### Security ✅
- [ ] Role-based access control
- [ ] JWT token validation
- [ ] Rate limiting enforcement
- [ ] Input validation errors

### Performance ✅
- [ ] Response times under load
- [ ] Pagination functionality
- [ ] Search and filtering performance
- [ ] Caching behavior

---

## 🎉 Ready to Test!

You now have everything needed to comprehensively test the HireSmart API. Start with the authentication workflow, then move through each role's functionality systematically.

**Happy Testing!** 🚀

---

**For Support**: 
- Check the main [README.md](./README.md) for API documentation
- Review the [ERD.md](./ERD.md) for database structure
- Create GitHub issues for bugs or questions 
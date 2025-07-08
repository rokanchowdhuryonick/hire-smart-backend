# HireSmart API - Postman Testing Guide

Complete guide for testing the HireSmart backend API using Postman.

## Setup Instructions

### 1. Environment Setup
Create a new environment in Postman with these variables:
- `baseUrl`: `http://localhost:8080/api` (adjust port if different)
- `token`: (will be auto-set after successful login)

### 2. Collection Import
Import the `HireSmart_API_Collection.postman_collection.json` file into Postman.

---

## 🔐 Authentication Flow

### User Registration & Login Process

**Step 1: Register a new user**
```
POST {{baseUrl}}/auth/register
```
**Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com", 
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "role": "candidate"
}
```
**Available roles:** `candidate`, `employer`, `admin`

**Step 2: Verify email (if implemented)**
```
POST {{baseUrl}}/auth/verify-email
```

**Step 3: Login**
```
POST {{baseUrl}}/auth/login
```
**Body:**
```json
{
    "email": "john@example.com",
    "password": "SecurePass123!"
}
```
The JWT token will be automatically saved to your environment variables.

---

## 📚 Reference Data Endpoints

### **IMPORTANT: Get IDs First!**

Before creating job postings or updating profiles, you need to get the valid IDs for skills, locations, etc. **All reference endpoints are public (no authentication required).**

### 🔧 Skills
Get all available skills:
```
GET {{baseUrl}}/reference/skills
```
**Response includes:** `id`, `name` - Use skill IDs in job postings and user profiles.

### 🌍 Location Hierarchy
Follow this order to get location IDs:

**1. Get all countries:**
```
GET {{baseUrl}}/reference/countries
```

**2. Get states for a country:**
```
GET {{baseUrl}}/reference/states?country_id=1
```

**3. Get cities for a state:**
```
GET {{baseUrl}}/reference/cities?state_id=1
```

**4. Get areas for a city:**
```
GET {{baseUrl}}/reference/areas?city_id=1
```

### 📋 Job Constants
Get valid values for job fields:

**Employment types:**
```
GET {{baseUrl}}/reference/employment-types
```
Returns: `full_time`, `part_time`, `contract`, `internship`

**Job statuses:**
```
GET {{baseUrl}}/reference/job-statuses
```
Returns: `active`, `draft`, `closed`, `archived`

### 🚀 Quick Setup (Get Everything)
```
GET {{baseUrl}}/reference/all
```
Returns skills, countries, employment types, and job statuses in one call.

---

## Candidate Workflow

### 4. Resume Upload (Required Before Applying)
```http
POST {{base_url}}/candidate/profile/resume
Authorization: Bearer {{auth_token}}
Content-Type: multipart/form-data

Body: form-data
Key: resume
Value: [Select PDF/DOC/DOCX file - max 5MB]
```

**Expected Response**:
```json
{
    "success": true,
    "message": "Resume uploaded successfully",
    "data": {
        "resume_url": "http://localhost:8080/storage/resumes/resume_1_1234567890.pdf"
    }
}
```

### 5. Get Resume Info
```http
GET {{base_url}}/candidate/profile/resume
Authorization: Bearer {{auth_token}}
```

### 6. Browse Jobs
```http
GET {{base_url}}/candidate/jobs?per_page=10&sort_by=created_at&sort_order=desc
Authorization: Bearer {{auth_token}}
```

### 7. Apply to Job (Automatic Resume Usage)
```http
POST {{base_url}}/candidate/jobs/{job_id}/apply
Authorization: Bearer {{auth_token}}
Content-Type: application/json

{
    "cover_letter": "I am very interested in this position...",
    "additional_info": "Available to start immediately"
}
```

**Note**: 
- Resume from profile is used automatically
- If no resume uploaded, you'll get error: "Please upload your resume to your profile before applying to jobs."

### 8. View Applications
```http
GET {{base_url}}/candidate/applications
Authorization: Bearer {{auth_token}}
```

## Testing Scenarios

### Scenario 1: Complete Candidate Flow
1. Register → Verify Email → Login
2. **Upload Resume** (New Step!)
3. Browse Jobs → Apply to Job
4. View Applications

### Scenario 2: Apply Without Resume (Error Case)
1. Register → Verify Email → Login
2. Skip resume upload
3. Try to apply to job
4. **Expected**: 422 error with message to upload resume first

### Scenario 3: Resume Management
1. Upload resume
2. Get resume info
3. Delete resume
4. Try to apply (should fail)
5. Upload new resume
6. Apply successfully

## Employer Workflow

### 9. Employer Registration & Login
```http
POST {{base_url}}/auth/register
Content-Type: application/json

{
    "name": "Jane Employer",
    "email": "jane@company.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "employer"
}
```

### 10. Create Job Posting
```http
POST {{base_url}}/employer/jobs
Authorization: Bearer {{auth_token}}
Content-Type: application/json

{
    "title": "Frontend Developer",
    "description": "We are looking for an experienced frontend developer...",
    "requirements": "- 3+ years React experience\n- TypeScript knowledge",
    "location": "New York, NY",
    "job_type": "full-time",
    "experience_level": "mid",
    "salary_min": 70000,
    "salary_max": 90000,
    "application_deadline": "2024-12-31",
    "skills": [1, 2, 3]
}
```

### 11. View Applications
```http
GET {{base_url}}/employer/applications
Authorization: Bearer {{auth_token}}
```

### 12. Update Application Status
```http
PUT {{base_url}}/employer/applications/{application_id}/status
Authorization: Bearer {{auth_token}}
Content-Type: application/json

{
    "status": "reviewed",
    "notes": "Good candidate, proceed to interview"
}
```

## Error Handling

### Common Error Responses

1. **Resume Required** (422):
```json
{
    "status": "error",
    "message": "Please upload your resume to your profile before applying to jobs.",
    "action_required": "upload_resume"
}
```

2. **File Validation** (422):
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "resume": ["The resume must be a file of type: pdf, doc, docx."]
    }
}
```

3. **Already Applied** (400):
```json
{
    "status": "error",
    "message": "You have already applied to this job"
}
```

## File Upload Notes

### Resume Upload Requirements
- **Accepted formats**: PDF, DOC, DOCX
- **Max file size**: 5MB
- **Min file size**: 10KB
- **Rate limit**: 5 uploads per hour
- **Filename**: Auto-generated as `resume_{user_id}_{timestamp}.{ext}`

### Storage Location
- Files stored in: `/storage/app/public/resumes/`
- Accessible via: `http://localhost:8080/storage/resumes/{filename}`

## Quick Test Checklist

- [ ] Candidate can register and verify email
- [ ] Candidate can login and get token
- [ ] **Candidate can upload resume**
- [ ] **Application without resume shows proper error**
- [ ] **Application with resume works automatically**
- [ ] Candidate can view their applications
- [ ] Employer can view applications with resume info
- [ ] **Resume files are accessible via URL**

## Troubleshooting

### File Upload Issues
1. **"413 Request Entity Too Large"**: File too big (>5MB)
2. **"422 Validation Error"**: Wrong file type or corrupted file
3. **"403 Forbidden"**: Not authenticated or wrong role
4. **"500 Server Error"**: Check storage directory exists and permissions

### Resume Access Issues
1. **File not found**: Run `php artisan storage:link` in container
2. **Permission denied**: Check storage directory permissions
3. **Old resume not deleted**: Check file cleanup logic

## Collection Import

Import the complete Postman collection: [Download HireSmart API Collection](./postman_collection.json)

The collection includes:
- Pre-request scripts for token management
- Automatic environment variable updates  
- Complete test scenarios
- **Resume upload with file selection**
- Error case testing 
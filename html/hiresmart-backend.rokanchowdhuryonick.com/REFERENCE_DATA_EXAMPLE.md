# Reference Data Usage Guide

## Problem: Getting IDs for Job Creation

When creating a job posting, you need valid IDs for:
- `skill_id` (for required/optional skills)
- `country_id`, `state_id`, `city_id`, `area_id` (for location)
- Valid values for `employment_type` and `status`

This guide shows you exactly how to get these IDs using the new reference data endpoints.

---

## 🔄 Complete Workflow Example

### Step 1: Get Available Skills
```http
GET /api/reference/skills
```

**Response:**
```json
{
    "status": "success",
    "data": [
        {"id": 1, "name": "JavaScript"},
        {"id": 2, "name": "PHP"},
        {"id": 3, "name": "Python"},
        {"id": 4, "name": "React"},
        {"id": 5, "name": "Laravel"}
    ],
    "meta": {
        "total": 5,
        "note": "Use skill IDs when creating job postings or updating profiles"
    }
}
```

### Step 2: Get Location Hierarchy

**2a. Get Countries:**
```http
GET /api/reference/countries
```
**Response:**
```json
{
    "status": "success",
    "data": [
        {"id": 1, "name": "United States"},
        {"id": 2, "name": "Canada"},
        {"id": 3, "name": "Bangladesh"}
    ]
}
```

**2b. Get States for USA (country_id=1):**
```http
GET /api/reference/states?country_id=1
```
**Response:**
```json
{
    "status": "success",
    "data": [
        {"id": 1, "name": "California", "country_id": 1},
        {"id": 2, "name": "New York", "country_id": 1},
        {"id": 3, "name": "Texas", "country_id": 1}
    ],
    "meta": {
        "country_id": 1,
        "note": "Use state_id to get cities for that state"
    }
}
```

**2c. Get Cities for California (state_id=1):**
```http
GET /api/reference/cities?state_id=1
```
**Response:**
```json
{
    "status": "success",
    "data": [
        {"id": 1, "name": "Los Angeles", "state_id": 1},
        {"id": 2, "name": "San Francisco", "state_id": 1},
        {"id": 3, "name": "San Diego", "state_id": 1}
    ],
    "meta": {
        "state_id": 1,
        "note": "Use city_id to get areas for that city"
    }
}
```

**2d. Get Areas for Los Angeles (city_id=1):**
```http
GET /api/reference/areas?city_id=1
```
**Response:**
```json
{
    "status": "success",
    "data": [
        {"id": 1, "name": "Downtown", "city_id": 1, "state_id": 1, "country_id": 1},
        {"id": 2, "name": "Hollywood", "city_id": 1, "state_id": 1, "country_id": 1},
        {"id": 3, "name": "Santa Monica", "city_id": 1, "state_id": 1, "country_id": 1}
    ],
    "meta": {
        "city_id": 1,
        "note": "Use area_id in job postings and profiles for specific location"
    }
}
```

### Step 3: Get Job Constants

**Employment Types:**
```http
GET /api/reference/employment-types
```
**Response:**
```json
{
    "status": "success",
    "data": [
        {"value": "full_time", "label": "Full Time"},
        {"value": "part_time", "label": "Part Time"},
        {"value": "contract", "label": "Contract"},
        {"value": "internship", "label": "Internship"}
    ]
}
```

---

## 🎯 Now Create Your Job Posting

Using the IDs from above, here's your complete job posting:

```http
POST /api/employer/jobs
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
    "title": "Senior Software Developer",
    "description": "We are looking for an experienced software developer to join our team. The ideal candidate will have expertise in modern web technologies and a passion for building scalable applications.",
    "min_salary": 70000,
    "max_salary": 100000,
    "currency": "USD",
    "employment_type": "full_time",
    "status": "active",
    "deadline": "2024-12-31",
    "experience_years": 5,
    "country_id": 1,
    "state_id": 1,
    "city_id": 1,
    "area_id": 1,
    "skills": [
        {
            "id": 1,
            "is_required": true
        },
        {
            "id": 2,
            "is_required": false
        }
    ],
    "company": {
        "name": "Tech Solutions Inc",
        "description": "A leading technology company specializing in innovative software solutions.",
        "website": "https://techsolutions.com"
    }
}
```

**Where the IDs came from:**
- `country_id: 1` = United States
- `state_id: 1` = California  
- `city_id: 1` = Los Angeles
- `area_id: 1` = Downtown
- `skills[0].id: 1` = JavaScript (required)
- `skills[1].id: 2` = PHP (optional)
- `employment_type: "full_time"` = Full Time
- `status: "active"` = Active

---

## 🚀 Quick Reference

### All-in-One Endpoint
If you just need skills and countries quickly:
```http
GET /api/reference/all
```

### Typical Workflow
1. `GET /api/reference/skills` → Get skill IDs
2. `GET /api/reference/countries` → Get country IDs  
3. `GET /api/reference/states?country_id=X` → Get state IDs
4. `GET /api/reference/cities?state_id=X` → Get city IDs
5. `GET /api/reference/areas?city_id=X` → Get area IDs
6. Use all these IDs in your job posting

### Important Notes
- All reference endpoints are **public** (no authentication required)
- All endpoints are cached for 1 hour for performance
- Location hierarchy: Country → State → City → Area
- Always get location IDs in order (can't get cities without state_id)

---

## 🔍 Error Examples

**Missing country_id:**
```http
GET /api/reference/states
```
**Response:**
```json
{
    "status": "error", 
    "message": "country_id parameter is required"
}
```

**Invalid job creation with wrong IDs:**
```json
{
    "country_id": 999,  // Non-existent country
    "skills": [{"id": 999, "is_required": true}]  // Non-existent skill
}
```
Will return validation errors with specific field messages. 
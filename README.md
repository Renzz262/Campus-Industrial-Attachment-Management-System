# Campus-Industrial-Attachment-Management-System
A web-based platform that connects students looking for industrial attachment opportunities with organizations offering them. Students can browse, apply and track applications. Organizations can publish opportunities and accept or reject applicants.

Group project for **BBT 2103 Software Engineering** (August to November 2026), BBIT Advanced Entry, Strathmore University.

## Features

### Students
- Register and log in securely
- View and edit their profile
- Browse open attachment opportunities
- Search by title and filter by location
- Apply with an optional cover letter (one application per opportunity)
- Track application status: Pending, Accepted or Rejected

### Organizations
- Register and log in securely
- View and edit their organization profile
- Post attachment opportunities
- Edit and close their own opportunities
- Review applications with student details and cover letters
- Accept or reject applications

### Scope
The system has exactly two user roles: **Student** and **Organization**. There is no administrator role.

## Technologies

| Component | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP (plain PHP, PDO) |
| Database | MySQL / MariaDB |
| Local server | XAMPP |
| Tools | Visual Studio Code, phpMyAdmin |

## Project Structure

```
CampusIndustrialAttachmentManagementSystem/
├── index.php                  Landing page
├── authentication/
│   ├── register.php           Student and organization registration
│   ├── login.php              Login for both roles
│   └── logout.php             Ends the session
├── config/
│   └── database.php           PDO database connection
├── database/
│   └── schema.sql             Database structure
├── assets/
│   └── style.css              Shared styles
├── student/
│   ├── dashboard.php          Student home and statistics
│   ├── opportunities.php      Browse, search and filter opportunities
│   ├── apply.php              Application form
│   ├── my_applications.php    Application tracking
│   └── profile.php            Edit student profile
└── organization/
    ├── dashboard.php          Organization home and statistics
    ├── add_opportunity.php    Post an opportunity
    ├── opportunities.php      List and close own opportunities
    ├── edit_opportunity.php   Edit an opportunity
    ├── applications.php       Review, accept or reject applications
    └── profile.php            Edit organization profile
```

## Database

Database name: `campusindustrialattachmentmanagementsystem`

| Table | Purpose |
|---|---|
| `students` | Student accounts and academic details |
| `organizations` | Organization accounts and profiles |
| `opportunities` | Attachment positions posted by organizations |
| `applications` | Applications submitted by students |

Relationships:
- One organization has many opportunities
- One student has many applications
- One opportunity receives many applications
- A `UNIQUE (student_id, opportunity_id)` constraint prevents duplicate applications

## Setup (Windows, XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy the project folder to:
   ```
   C:\xampp\htdocs\CampusIndustrialAttachmentManagementSystem
   ```
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Create a database named `campusindustrialattachmentmanagementsystem`.
5. Select it, click **Import**, and choose `database/schema.sql`.
6. Check `config/database.php`. The defaults are host `localhost`, user `root` and an empty password. Change them if your MySQL differs.
7. Open the system in your browser:
   ```
   http://localhost/CampusIndustrialAttachmentManagementSystem/
   ```

## How to Use

1. **Register** as a Student or an Organization.
2. **Organizations:** post an opportunity from the dashboard.
3. **Students:** browse opportunities, apply, then track the status under *My Applications*.
4. **Organizations:** open *Applications* to accept or reject applicants. The student sees the decision immediately.

## Security Measures

- Passwords hashed with `password_hash()` and verified with `password_verify()`
- PDO prepared statements on every query (protection against SQL injection)
- Role-based access control on every protected page
- Ownership checks, so an organization can only edit its own opportunities and review its own applications
- CSRF tokens on all state-changing forms
- Output escaping with `htmlspecialchars()` (protection against XSS)
- Server-side input validation
- Session ID regeneration after login

## Sample Data

Any organizations and opportunities in the demo database are **sample data for demonstration only** and are not verified vacancies.

## Team

| Member | Role | Responsibility in this repository |
|---|---|---|
| Warren Maina | Project / Requirements and Integration Lead | Authentication module, landing page, documentation |
| Kerosi | Database and Backend Lead | Database schema, PDO configuration |
| Raani Kassam | Student Module and Frontend Lead | Student module, shared styles |
| Sweeny Chemoiwa | Organization and Opportunity Lead | Organization module |

## Module Plan

| Module | Description |
|---|---|
| M1 | Authentication and user management |
| M2 | Profile management |
| M3 | Opportunity management |
| M4 | Application management |
